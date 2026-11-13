<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Client\CaptchaAltcha\ChallengeCreator;

use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;
use Generated\Shared\Transfer\CaptchaChallengeResponseTransfer;
use Psr\Log\LoggerInterface;
use SprykerEco\Client\CaptchaAltcha\CaptchaAltchaConfig;
use SprykerEco\Client\CaptchaAltcha\Zed\CaptchaAltchaStubInterface;

class ZedAltchaChallengeCreator implements AltchaChallengeCreatorInterface
{
    protected const string LOG_MESSAGE_CONFIGURATION_SERVICE_MISSING = 'This application creates ALTCHA challenges through the Zed gateway, because it does not provide the configuration service. Register ConfigurationApplicationPlugin in it to create the challenges in-process.';

    public function __construct(
        protected CaptchaAltchaStubInterface $captchaAltchaStub,
        protected CaptchaAltchaConfig $captchaAltchaConfig,
        protected LoggerInterface $logger,
    ) {
    }

    public function createChallenge(CaptchaChallengeRequestTransfer $captchaChallengeRequestTransfer): CaptchaChallengeResponseTransfer
    {
        if ($this->captchaAltchaConfig->isZedApplication()) {
            $this->logger->warning(static::LOG_MESSAGE_CONFIGURATION_SERVICE_MISSING);
        }

        return $this->captchaAltchaStub->createCaptchaChallenge($captchaChallengeRequestTransfer);
    }
}
