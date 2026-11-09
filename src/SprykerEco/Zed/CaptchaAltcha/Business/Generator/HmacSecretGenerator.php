<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\CaptchaAltcha\Business\Generator;

use Generated\Shared\Transfer\ConfigurationValueCollectionRequestTransfer;
use Generated\Shared\Transfer\ConfigurationValueCollectionResponseTransfer;
use Generated\Shared\Transfer\ConfigurationValueRequestTransfer;
use Generated\Shared\Transfer\ConfigurationValueTransfer;
use Spryker\Zed\Configuration\Business\ConfigurationFacadeInterface;
use SprykerEco\Zed\CaptchaAltcha\CaptchaAltchaConfig;

class HmacSecretGenerator implements HmacSecretGeneratorInterface
{
    public function __construct(
        protected ConfigurationFacadeInterface $configurationFacade,
        protected CaptchaAltchaConfig $captchaAltchaConfig,
    ) {
    }

    public function generateMissingHmacSecret(): ConfigurationValueCollectionResponseTransfer
    {
        if ($this->hasUsableStoredHmacSecret()) {
            return (new ConfigurationValueCollectionResponseTransfer())
                ->setIsSuccess(true)
                ->setSavedCount(0);
        }

        $configurationValueTransfer = (new ConfigurationValueTransfer())
            ->setSettingKey($this->captchaAltchaConfig->getHmacSecretConfigurationKey())
            ->setScope($this->captchaAltchaConfig->getHmacSecretConfigurationScope())
            ->setValue(bin2hex(random_bytes(max(1, $this->captchaAltchaConfig->getGeneratedHmacSecretByteLength()))));

        return $this->configurationFacade->saveConfigurationValues(
            (new ConfigurationValueCollectionRequestTransfer())->addConfigurationValue($configurationValueTransfer),
        );
    }

    protected function hasUsableStoredHmacSecret(): bool
    {
        // The stored value is encrypted, so the decrypted secret is measured, with the rule of AltchaSecretChecker.
        $storedHmacSecret = (string)$this->configurationFacade->getConfigurationValue(
            (new ConfigurationValueRequestTransfer())
                ->setKey($this->captchaAltchaConfig->getHmacSecretConfigurationKey())
                ->setScope($this->captchaAltchaConfig->getHmacSecretConfigurationScope()),
        );

        return strlen($storedHmacSecret) >= $this->captchaAltchaConfig->getHmacSecretMinimumLength();
    }
}
