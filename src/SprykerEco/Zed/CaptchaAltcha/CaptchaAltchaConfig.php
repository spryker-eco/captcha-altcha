<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\CaptchaAltcha;

use Spryker\Zed\Kernel\AbstractBundleConfig;

class CaptchaAltchaConfig extends AbstractBundleConfig
{
    /**
     * @uses \SprykerEco\Client\CaptchaAltcha\CaptchaAltchaConfig::CONFIGURATION_KEY_HMAC_SECRET
     */
    protected const string CONFIGURATION_KEY_HMAC_SECRET = 'integrations:altcha:credentials:hmac_secret';

    /**
     * @uses \Spryker\Shared\Configuration\ConfigurationConstants::SCOPE_GLOBAL
     */
    protected const string CONFIGURATION_SCOPE_GLOBAL = 'global';

    protected const int GENERATED_HMAC_SECRET_BYTE_LENGTH = 32;

    /**
     * Specification:
     * - Returns the key of the Back Office setting that holds the ALTCHA HMAC secret.
     *
     * @api
     */
    public function getHmacSecretConfigurationKey(): string
    {
        return static::CONFIGURATION_KEY_HMAC_SECRET;
    }

    /**
     * Specification:
     * - Returns the configuration scope the HMAC secret is stored at: the setting is global.
     *
     * @api
     */
    public function getHmacSecretConfigurationScope(): string
    {
        return static::CONFIGURATION_SCOPE_GLOBAL;
    }

    /**
     * Specification:
     * - Returns the number of random bytes of a generated HMAC secret, `32` by default.
     * - The secret is stored hex-encoded, so it is twice as many characters long.
     *
     * @api
     */
    public function getGeneratedHmacSecretByteLength(): int
    {
        return static::GENERATED_HMAC_SECRET_BYTE_LENGTH;
    }
}
