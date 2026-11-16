<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Zed\CaptchaAltcha\Communication\Configuration;

use Codeception\Test\Unit;
use SprykerEco\Shared\CaptchaAltcha\CaptchaAltchaConfig;
use Symfony\Component\Yaml\Yaml;

/**
 * Auto-generated group annotations
 *
 * @group SprykerEcoTest
 * @group Zed
 * @group CaptchaAltcha
 * @group Communication
 * @group Configuration
 * @group CaptchaAltchaConfigurationSchemaTest
 * Add your own group annotations below this line
 */
class CaptchaAltchaConfigurationSchemaTest extends Unit
{
    protected const string CONFIGURATION_SCHEMA_FILE_PATH = '/resources/configuration/captcha_altcha.configuration.yml';

    protected const string CONSTRAINT_TYPE_MINIMUM = 'min';

    protected const string KEY_SEPARATOR = ':';

    public function testDefinesTheHmacSecretSettingUnderTheSharedConfigurationKey(): void
    {
        // Arrange
        $configurationSchema = $this->readConfigurationSchema();

        // Act
        $settingsByKey = $this->indexSettingsByKey($configurationSchema);

        // Assert
        $this->assertArrayHasKey(CaptchaAltchaConfig::CONFIGURATION_KEY_HMAC_SECRET, $settingsByKey);
    }

    public function testRequiresTheSharedMinimumLengthForTheHmacSecret(): void
    {
        // Arrange
        $settingsByKey = $this->indexSettingsByKey($this->readConfigurationSchema());

        // Act
        $minimumLength = $this->findMinimumConstraint($settingsByKey[CaptchaAltchaConfig::CONFIGURATION_KEY_HMAC_SECRET] ?? []);

        // Assert
        $this->assertSame(CaptchaAltchaConfig::HMAC_SECRET_MINIMUM_LENGTH, $minimumLength);
    }

    /**
     * @return array<string, mixed>
     */
    protected function readConfigurationSchema(): array
    {
        return Yaml::parseFile(dirname(__DIR__, 6) . static::CONFIGURATION_SCHEMA_FILE_PATH);
    }

    /**
     * @param array<string, mixed> $configurationSchema
     *
     * @return array<string, array<string, mixed>>
     */
    protected function indexSettingsByKey(array $configurationSchema): array
    {
        $settingsByKey = [];

        foreach ($configurationSchema['features'] ?? [] as $feature) {
            foreach ($feature['tabs'] ?? [] as $tab) {
                $settingsByKey += $this->indexGroupSettingsByKey($tab['groups'] ?? [], [$feature['key'], $tab['key']]);
            }
        }

        return $settingsByKey;
    }

    /**
     * @param array<array<string, mixed>> $groups
     * @param array<string> $keyPrefixes
     *
     * @return array<string, array<string, mixed>>
     */
    protected function indexGroupSettingsByKey(array $groups, array $keyPrefixes): array
    {
        $settingsByKey = [];

        foreach ($groups as $group) {
            foreach ($group['settings'] ?? [] as $setting) {
                $settingsByKey[implode(static::KEY_SEPARATOR, [...$keyPrefixes, $group['key'], $setting['key']])] = $setting;
            }
        }

        return $settingsByKey;
    }

    /**
     * @param array<string, mixed> $setting
     */
    protected function findMinimumConstraint(array $setting): ?int
    {
        foreach ($setting['constraints'] ?? [] as $constraint) {
            if ($constraint['type'] === static::CONSTRAINT_TYPE_MINIMUM) {
                return (int)$constraint['options'][static::CONSTRAINT_TYPE_MINIMUM];
            }
        }

        return null;
    }
}
