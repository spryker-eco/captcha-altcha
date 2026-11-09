<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Shared\CaptchaAltcha;

use Spryker\Shared\Kernel\AbstractSharedConfig;

class CaptchaAltchaConfig extends AbstractSharedConfig
{
    /**
     * Specification:
     * - Key of the Back Office setting that holds the HMAC secret signing the ALTCHA challenges.
     * - Zed writes the generated secret under this key, and the Client reads the secret from it.
     *
     * @api
     */
    public const string CONFIGURATION_KEY_HMAC_SECRET = 'integrations:altcha:credentials:hmac_secret';

    /**
     * Specification:
     * - Minimum length in bytes of the HMAC secret.
     * - A shorter secret counts as not configured: challenges are neither created nor accepted, and
     *   the `captcha-altcha:hmac-secret:generate` console command replaces it.
     *
     * @api
     */
    public const int HMAC_SECRET_MINIMUM_LENGTH = 32;
}
