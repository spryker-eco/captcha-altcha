<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Client\CaptchaAltcha\Config;

use Codeception\Test\Unit;
use SprykerEco\Client\CaptchaAltcha\CaptchaAltchaConfig;
use SprykerEcoTest\Client\CaptchaAltcha\CaptchaAltchaClientTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerEcoTest
 * @group Client
 * @group CaptchaAltcha
 * @group Config
 * @group CaptchaAltchaConfigTest
 * Add your own group annotations below this line
 */
class CaptchaAltchaConfigTest extends Unit
{
    protected const string CONFIGURATION_KEY_HMAC_SECRET = 'integrations:altcha:credentials:hmac_secret';

    public function testReadsHmacSecretFromBackOfficeSettingAndDerivesKeySignatureSecretFromIt(): void
    {
        // Arrange
        $captchaAltchaConfig = $this->createConfigWithStoredValues([static::CONFIGURATION_KEY_HMAC_SECRET => CaptchaAltchaClientTester::HMAC_SECRET]);

        // Act
        $hmacSecret = $captchaAltchaConfig->getHmacSecret();
        $hmacKeySignatureSecret = $captchaAltchaConfig->getHmacKeySignatureSecret();

        // Assert
        $this->assertSame(CaptchaAltchaClientTester::HMAC_SECRET, $hmacSecret);
        $this->assertNotEmpty($hmacKeySignatureSecret);
        $this->assertNotSame($hmacSecret, $hmacKeySignatureSecret);
    }

    /**
     * Yves reads a secret setting as not configured, because secret settings are published to no key-value store.
     */
    public function testReturnsEmptySecretsWhenBackOfficeSettingIsNotReadable(): void
    {
        // Arrange
        $captchaAltchaConfig = $this->createConfigWithStoredValues([]);

        // Act
        $hmacSecret = $captchaAltchaConfig->getHmacSecret();
        $hmacKeySignatureSecret = $captchaAltchaConfig->getHmacKeySignatureSecret();

        // Assert
        $this->assertSame('', $hmacSecret);
        $this->assertSame('', $hmacKeySignatureSecret);
    }

    /**
     * @param array<string, string> $storedValues
     */
    protected function createConfigWithStoredValues(array $storedValues): CaptchaAltchaConfig
    {
        return new class ($storedValues) extends CaptchaAltchaConfig {
            /**
             * @param array<string, string> $storedValues
             */
            public function __construct(protected array $storedValues)
            {
            }

            protected function getModuleConfig(string $key, $default = null, array $configurationScopes = []): mixed
            {
                return $this->storedValues[$key] ?? $default;
            }
        };
    }
}
