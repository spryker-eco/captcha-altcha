<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Client\CaptchaAltcha\Checker;

use Psr\Log\LoggerInterface;
use SprykerEco\Client\CaptchaAltcha\CaptchaAltchaConfig;
use SprykerEco\Shared\CaptchaAltcha\CaptchaAltchaConfig as SharedCaptchaAltchaConfig;

class AltchaSecretChecker implements AltchaSecretCheckerInterface
{
    protected const string LOG_MESSAGE_HMAC_SECRET_MISSING = 'The ALTCHA HMAC secret (Back Office setting %s) is empty in this application: CAPTCHA challenges are neither created nor accepted, so every login that requires a CAPTCHA is rejected. Generate it with the captcha-altcha:hmac-secret:generate console command or set it in the Back Office; it is readable only where ConfigurationApplicationPlugin is registered.';

    protected const string LOG_MESSAGE_HMAC_SECRET_TOO_SHORT = 'The ALTCHA HMAC secret (Back Office setting %s) is shorter than %d bytes, so it can be brute-forced from a signed challenge: CAPTCHA challenges are neither created nor accepted, so every login that requires a CAPTCHA is rejected. Replace it with the captcha-altcha:hmac-secret:generate console command or in the Back Office.';

    public function __construct(
        protected CaptchaAltchaConfig $captchaAltchaConfig,
        protected LoggerInterface $logger,
    ) {
    }

    public function isHmacSecretConfigured(): bool
    {
        $hmacSecretLength = strlen($this->captchaAltchaConfig->getHmacSecret());

        if ($hmacSecretLength === 0) {
            $this->logger->error(sprintf(static::LOG_MESSAGE_HMAC_SECRET_MISSING, SharedCaptchaAltchaConfig::CONFIGURATION_KEY_HMAC_SECRET));

            return false;
        }

        $hmacSecretMinimumLength = $this->captchaAltchaConfig->getHmacSecretMinimumLength();

        if ($hmacSecretLength < $hmacSecretMinimumLength) {
            $this->logger->error(sprintf(static::LOG_MESSAGE_HMAC_SECRET_TOO_SHORT, SharedCaptchaAltchaConfig::CONFIGURATION_KEY_HMAC_SECRET, $hmacSecretMinimumLength));

            return false;
        }

        return true;
    }
}
