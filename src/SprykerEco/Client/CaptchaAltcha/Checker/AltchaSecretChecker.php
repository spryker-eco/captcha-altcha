<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Client\CaptchaAltcha\Checker;

use Psr\Log\LoggerInterface;
use SprykerEco\Client\CaptchaAltcha\CaptchaAltchaConfig;

class AltchaSecretChecker implements AltchaSecretCheckerInterface
{
    protected const string LOG_MESSAGE_HMAC_SECRET_MISSING = 'The ALTCHA HMAC secret (CaptchaAltchaConstants::HMAC_SECRET) is not configured: CAPTCHA challenges are neither created nor accepted, so every login that requires a CAPTCHA is rejected.';

    public function __construct(
        protected CaptchaAltchaConfig $captchaAltchaConfig,
        protected LoggerInterface $logger,
    ) {
    }

    public function isHmacSecretConfigured(): bool
    {
        if ($this->captchaAltchaConfig->getHmacSecret() !== '') {
            return true;
        }

        $this->logger->error(static::LOG_MESSAGE_HMAC_SECRET_MISSING);

        return false;
    }
}
