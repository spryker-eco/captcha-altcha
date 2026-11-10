<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Client\CaptchaAltcha\Verifier;

use Generated\Shared\Transfer\CaptchaVerificationRequestTransfer;
use Generated\Shared\Transfer\CaptchaVerificationResponseTransfer;
use Psr\Log\LoggerInterface;
use SprykerEco\Client\CaptchaAltcha\CaptchaAltchaConfig;
use SprykerEco\Client\CaptchaAltcha\Zed\CaptchaAltchaStubInterface;

class ZedAltchaVerifier implements AltchaVerifierInterface
{
    protected const string LOG_MESSAGE_CONFIGURATION_SERVICE_MISSING = 'This application verifies ALTCHA payloads through the Zed gateway, because it does not provide the configuration service. Register ConfigurationApplicationPlugin in it to verify the payloads in-process.';

    public function __construct(
        protected CaptchaAltchaStubInterface $captchaAltchaStub,
        protected CaptchaAltchaConfig $captchaAltchaConfig,
        protected LoggerInterface $logger,
    ) {
    }

    public function verify(CaptchaVerificationRequestTransfer $captchaVerificationRequestTransfer): CaptchaVerificationResponseTransfer
    {
        if ($this->captchaAltchaConfig->isZedApplication()) {
            $this->logger->warning(static::LOG_MESSAGE_CONFIGURATION_SERVICE_MISSING);
        }

        return $this->captchaAltchaStub->verifyCaptchaPayload($captchaVerificationRequestTransfer);
    }
}
