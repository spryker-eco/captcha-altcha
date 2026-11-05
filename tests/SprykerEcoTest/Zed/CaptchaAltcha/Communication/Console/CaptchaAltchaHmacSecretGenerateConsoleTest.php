<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Zed\CaptchaAltcha\Communication\Console;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ConfigurationErrorTransfer;
use Generated\Shared\Transfer\ConfigurationSettingValueCollectionTransfer;
use Generated\Shared\Transfer\ConfigurationValueCollectionResponseTransfer;
use Spryker\Zed\Configuration\Business\ConfigurationFacadeInterface;
use Spryker\Zed\Kernel\Communication\Console\Console;
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
}
