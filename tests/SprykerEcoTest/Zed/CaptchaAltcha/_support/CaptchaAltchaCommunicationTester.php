<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Zed\CaptchaAltcha;

use Codeception\Actor;
use Generated\Shared\Transfer\ConfigurationSettingValuesCriteriaTransfer;
use Generated\Shared\Transfer\ConfigurationValueCollectionRequestTransfer;
use Generated\Shared\Transfer\ConfigurationValueDeletionTransfer;
use Generated\Shared\Transfer\ConfigurationValueTransfer;
use ReflectionProperty;
use Spryker\Client\Configuration\ConfigurationDependencyProvider;
use Spryker\Shared\Configuration\Reader\AbstractConfigurationValueResolver;
use Spryker\Zed\Configuration\Business\ConfigurationFacadeInterface;
use SprykerEco\Client\CaptchaAltcha\CaptchaAltchaConfig;
use SprykerEco\Zed\CaptchaAltcha\Communication\Console\CaptchaAltchaHmacSecretGenerateConsole;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
 */
class CaptchaAltchaCommunicationTester extends Actor
{
    use _generated\CaptchaAltchaCommunicationTesterActions;

    public const string HMAC_SECRET_CONFIGURATION_KEY = 'integrations:altcha:credentials:hmac_secret';

    public const string SCOPE_GLOBAL = 'global';

    public function haveNoStoredHmacSecret(): void
    {
        $this->getConfigurationFacade()->saveConfigurationValues(
            (new ConfigurationValueCollectionRequestTransfer())->addDeletionKey(
                (new ConfigurationValueDeletionTransfer())
                    ->setSettingKey(static::HMAC_SECRET_CONFIGURATION_KEY)
                    ->setScope(static::SCOPE_GLOBAL),
            ),
        );
    }

    public function haveStoredHmacSecret(string $hmacSecret): void
    {
        $configurationValueCollectionResponseTransfer = $this->getConfigurationFacade()->saveConfigurationValues(
            (new ConfigurationValueCollectionRequestTransfer())->addConfigurationValue(
                (new ConfigurationValueTransfer())
                    ->setSettingKey(static::HMAC_SECRET_CONFIGURATION_KEY)
                    ->setScope(static::SCOPE_GLOBAL)
                    ->setValue($hmacSecret),
            ),
        );
        $this->assertTrue($configurationValueCollectionResponseTransfer->getIsSuccess());
    }

    public function findStoredHmacSecretValue(): ?string
    {
        $configurationSettingValueCollectionTransfer = $this->getConfigurationFacade()->getConfigurationSettingValues(
            (new ConfigurationSettingValuesCriteriaTransfer())
                ->addSettingKey(static::HMAC_SECRET_CONFIGURATION_KEY)
                ->setScope(static::SCOPE_GLOBAL),
        );

        return $configurationSettingValueCollectionTransfer->getDirectValues()[static::HMAC_SECRET_CONFIGURATION_KEY] ?? null;
    }

    /**
     * Provides the configuration service the way `ConfigurationApplicationPlugin` does in a Zed application, then
     * reads the secret through the module config the provider signs with.
     */
    public function readHmacSecretInZedApplication(): string
    {
        $this->resetResolvedConfigurationValueCache();
        $this->setDependency(ConfigurationDependencyProvider::SERVICE_CONFIGURATION, $this->getConfigurationFacade());
        $this->setDependency(ConfigurationDependencyProvider::IS_CONFIGURATION_SERVICE_PROVIDED, true);

        return (new CaptchaAltchaConfig())->getHmacSecret();
    }

    public function runHmacSecretGenerateConsole(): CommandTester
    {
        $commandTester = new CommandTester(new CaptchaAltchaHmacSecretGenerateConsole());
        $commandTester->execute([]);

        return $commandTester;
    }

    protected function getConfigurationFacade(): ConfigurationFacadeInterface
    {
        return $this->getLocator()->configuration()->facade();
    }

    /**
     * The resolved values are cached per process and a save does not invalidate them, so without the reset a test reads
     * the secret an earlier test of the same run resolved.
     */
    protected function resetResolvedConfigurationValueCache(): void
    {
        (new ReflectionProperty(AbstractConfigurationValueResolver::class, 'resolvedValueCache'))->setValue(null, []);
    }
}
