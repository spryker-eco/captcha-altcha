<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\CaptchaAltcha\Communication\Console;

use Generated\Shared\Transfer\ConfigurationValueCollectionResponseTransfer;
use Spryker\Zed\Kernel\Communication\Console\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Run it after `configuration:sync` and the database initialization, for example in the install recipe. It is safe to
 * run on every deployment: it never replaces a stored secret, so an administrator can rotate the secret in the Back
 * Office. It never prints the secret.
 *
 * @method \SprykerEco\Zed\CaptchaAltcha\Business\CaptchaAltchaFacadeInterface getFacade()
 * @method \SprykerEco\Zed\CaptchaAltcha\Business\CaptchaAltchaBusinessFactory getBusinessFactory()
 */
class CaptchaAltchaHmacSecretGenerateConsole extends Console
{
    protected const string COMMAND_NAME = 'captcha-altcha:hmac-secret:generate';

    protected const string COMMAND_DESCRIPTION = 'Generates a random ALTCHA HMAC secret in the Back Office configuration when none, or one shorter than the minimum length, is stored. Keeps a stored secret of the minimum length or longer.';

    protected const string MESSAGE_GENERATED = 'The ALTCHA HMAC secret was generated.';

    protected const string MESSAGE_KEPT = 'The ALTCHA HMAC secret is already stored and was kept.';

    protected const string MESSAGE_SAVE_FAILED = 'The ALTCHA HMAC secret could not be saved: %s';

    protected function configure(): void
    {
        $this->setName(static::COMMAND_NAME)
            ->setDescription(static::COMMAND_DESCRIPTION);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    {
        $configurationValueCollectionResponseTransfer = $this->getBusinessFactory()
            ->createHmacSecretGenerator()
            ->generateMissingHmacSecret();

        if (!$configurationValueCollectionResponseTransfer->getIsSuccess()) {
            $this->error(sprintf(static::MESSAGE_SAVE_FAILED, $this->formatErrorMessages($configurationValueCollectionResponseTransfer)));

            return static::CODE_ERROR;
        }

        if ($configurationValueCollectionResponseTransfer->getSavedCount() === 0) {
            $this->info(static::MESSAGE_KEPT);

            return static::CODE_SUCCESS;
        }

        $this->info(static::MESSAGE_GENERATED);

        return static::CODE_SUCCESS;
    }

    protected function formatErrorMessages(ConfigurationValueCollectionResponseTransfer $configurationValueCollectionResponseTransfer): string
    {
        $errorMessages = [];

        foreach ($configurationValueCollectionResponseTransfer->getErrors() as $configurationErrorTransfer) {
            $errorMessages[] = (string)$configurationErrorTransfer->getMessage();
        }

        return implode(' ', $errorMessages);
    }
}
