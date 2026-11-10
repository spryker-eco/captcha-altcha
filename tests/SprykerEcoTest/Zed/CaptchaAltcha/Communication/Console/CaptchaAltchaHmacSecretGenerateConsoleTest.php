<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Zed\CaptchaAltcha\Communication\Console;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ConfigurationErrorTransfer;
use Generated\Shared\Transfer\ConfigurationSettingValueCollectionTransfer;
use Generated\Shared\Transfer\ConfigurationValueCollectionRequestTransfer;
use Generated\Shared\Transfer\ConfigurationValueCollectionResponseTransfer;
use Spryker\Zed\Configuration\Business\ConfigurationFacadeInterface;
use Spryker\Zed\Kernel\Communication\Console\Console;
use Spryker\Zed\Locale\LocaleDependencyProvider;
use SprykerEco\Zed\CaptchaAltcha\CaptchaAltchaDependencyProvider;
use SprykerEcoTest\Zed\CaptchaAltcha\CaptchaAltchaCommunicationTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerEcoTest
 * @group Zed
 * @group CaptchaAltcha
 * @group Communication
 * @group Console
 * @group CaptchaAltchaHmacSecretGenerateConsoleTest
 * Add your own group annotations below this line
 */
class CaptchaAltchaHmacSecretGenerateConsoleTest extends Unit
{
    protected const string GENERATED_HMAC_SECRET_PATTERN = '/^[0-9a-f]{64}$/';

    protected const string STORED_HMAC_SECRET = 'administrator-rotated-captcha-altcha-hmac-secret';

    protected const string SAVE_ERROR_MESSAGE = 'Must be at least 32 characters long.';

    protected const string SHORT_HMAC_SECRET = 'short-hmac-secret';

    protected const string ENCRYPTED_SHORT_HMAC_SECRET = 'encrypted-value-of-the-short-hmac-secret-that-is-longer-than-32-characters';

    protected const int BYTE_LENGTH_BELOW_MINIMUM_LENGTH = 8;

    protected const string LOCALE_NAME_EN_US = 'en_US';

    protected CaptchaAltchaCommunicationTester $tester;

    public function testGeneratesSecretStoredEncryptedAndReadableInZedWhenNoneIsStored(): void
    {
        // Arrange
        $this->tester->haveNoStoredHmacSecret();

        // Act
        $commandTester = $this->tester->runHmacSecretGenerateConsole();

        // Assert
        $hmacSecret = $this->tester->readHmacSecretInZedApplication();
        $this->assertSame(Console::CODE_SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('generated', $commandTester->getDisplay());
        $this->assertMatchesRegularExpression(static::GENERATED_HMAC_SECRET_PATTERN, $hmacSecret);
        $this->assertNotSame($hmacSecret, $this->tester->findStoredHmacSecretValue());
        $this->assertStringNotContainsString($hmacSecret, $commandTester->getDisplay());
    }

    public function testKeepsStoredSecretUnchanged(): void
    {
        // Arrange
        $this->tester->haveStoredHmacSecret(static::STORED_HMAC_SECRET);
        $storedHmacSecretValue = $this->tester->findStoredHmacSecretValue();

        // Act
        $commandTester = $this->tester->runHmacSecretGenerateConsole();

        // Assert
        $this->assertSame(Console::CODE_SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('kept', $commandTester->getDisplay());
        $this->assertSame($storedHmacSecretValue, $this->tester->findStoredHmacSecretValue());
        $this->assertSame(static::STORED_HMAC_SECRET, $this->tester->readHmacSecretInZedApplication());
        $this->assertStringNotContainsString(static::STORED_HMAC_SECRET, $commandTester->getDisplay());
    }

    /**
     * A secret this short cannot be stored through the validated save, so the facade answers with one.
     */
    public function testReplacesStoredSecretShorterThanTheMinimumLength(): void
    {
        // Arrange
        $this->tester->setDependency(CaptchaAltchaDependencyProvider::FACADE_CONFIGURATION, $this->createConfigurationFacadeWithShortStoredSecret());

        // Act
        $commandTester = $this->tester->runHmacSecretGenerateConsole();

        // Assert
        $this->assertSame(Console::CODE_SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('generated', $commandTester->getDisplay());
    }

    public function testExitsWithTheValidationErrorWhenTheGeneratedSecretWouldBeShorterThanTheMinimumLength(): void
    {
        // Arrange
        $this->tester->haveNoStoredHmacSecret();
        $this->tester->setDependency(LocaleDependencyProvider::LOCALE_CURRENT, static::LOCALE_NAME_EN_US);
        $captchaAltchaBusinessFactory = $this->tester->createBusinessFactoryWithGeneratedHmacSecretByteLength(static::BYTE_LENGTH_BELOW_MINIMUM_LENGTH);

        // Act
        $commandTester = $this->tester->runHmacSecretGenerateConsole($captchaAltchaBusinessFactory);

        // Assert
        $this->assertSame(Console::CODE_ERROR, $commandTester->getStatusCode());
        $this->assertStringContainsString(static::SAVE_ERROR_MESSAGE, $commandTester->getDisplay());
        $this->assertNull($this->tester->findStoredHmacSecretValue());
    }

    public function testExitsWithErrorAndPrintsTheReasonWhenSavingFails(): void
    {
        // Arrange
        $this->tester->setDependency(CaptchaAltchaDependencyProvider::FACADE_CONFIGURATION, $this->createConfigurationFacadeFailingToSave());

        // Act
        $commandTester = $this->tester->runHmacSecretGenerateConsole();

        // Assert
        $this->assertSame(Console::CODE_ERROR, $commandTester->getStatusCode());
        $this->assertStringContainsString(static::SAVE_ERROR_MESSAGE, $commandTester->getDisplay());
    }

    protected function createConfigurationFacadeFailingToSave(): ConfigurationFacadeInterface
    {
        $configurationFacadeMock = $this->createMock(ConfigurationFacadeInterface::class);
        $configurationFacadeMock->method('getConfigurationSettingValues')->willReturn(new ConfigurationSettingValueCollectionTransfer());
        $configurationFacadeMock->expects($this->once())
            ->method('saveConfigurationValues')
            ->willReturn(
                (new ConfigurationValueCollectionResponseTransfer())
                    ->setIsSuccess(false)
                    ->setSavedCount(0)
                    ->addError((new ConfigurationErrorTransfer())->setMessage(static::SAVE_ERROR_MESSAGE)),
            );

        return $configurationFacadeMock;
    }

    protected function createConfigurationFacadeWithShortStoredSecret(): ConfigurationFacadeInterface
    {
        $configurationFacadeMock = $this->createMock(ConfigurationFacadeInterface::class);
        $configurationFacadeMock->method('getConfigurationSettingValues')->willReturn(
            (new ConfigurationSettingValueCollectionTransfer())
                ->setDirectValues([CaptchaAltchaCommunicationTester::HMAC_SECRET_CONFIGURATION_KEY => static::ENCRYPTED_SHORT_HMAC_SECRET]),
        );
        $configurationFacadeMock->method('getConfigurationValue')->willReturn(static::SHORT_HMAC_SECRET);
        $configurationFacadeMock->expects($this->once())
            ->method('saveConfigurationValues')
            ->with($this->callback(fn (ConfigurationValueCollectionRequestTransfer $configurationValueCollectionRequestTransfer): bool => preg_match(
                static::GENERATED_HMAC_SECRET_PATTERN,
                (string)$configurationValueCollectionRequestTransfer->getConfigurationValues()->offsetGet(0)->getValue(),
            ) === 1))
            ->willReturn((new ConfigurationValueCollectionResponseTransfer())->setIsSuccess(true)->setSavedCount(1));

        return $configurationFacadeMock;
    }
}
