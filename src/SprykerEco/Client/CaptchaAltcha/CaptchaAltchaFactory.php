<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Client\CaptchaAltcha;

use AltchaOrg\Altcha\Algorithm\Argon2id;
use AltchaOrg\Altcha\Algorithm\DeriveKeyInterface;
use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\Altcha;
use Spryker\Client\Kernel\AbstractFactory;
use Spryker\Client\ZedRequest\ZedRequestClientInterface;
use Spryker\Shared\Kernel\StrategyResolver;
use Spryker\Shared\Kernel\StrategyResolverInterface;
use Spryker\Shared\Log\LoggerTrait;
use SprykerEco\Client\CaptchaAltcha\ChallengeCreator\AltchaChallengeCreatorInterface;
use SprykerEco\Client\CaptchaAltcha\ChallengeCreator\AltchaChallengeDataCreator;
use SprykerEco\Client\CaptchaAltcha\ChallengeCreator\AltchaChallengeDataCreatorInterface;
use SprykerEco\Client\CaptchaAltcha\ChallengeCreator\AltchaChallengeUrlWidgetCreator;
use SprykerEco\Client\CaptchaAltcha\ChallengeCreator\AltchaChallengeUrlWidgetCreatorInterface;
use SprykerEco\Client\CaptchaAltcha\ChallengeCreator\LocalAltchaChallengeCreator;
use SprykerEco\Client\CaptchaAltcha\ChallengeCreator\ZedAltchaChallengeCreator;
use SprykerEco\Client\CaptchaAltcha\Checker\AltchaSecretChecker;
use SprykerEco\Client\CaptchaAltcha\Checker\AltchaSecretCheckerInterface;
use SprykerEco\Client\CaptchaAltcha\Mapper\AltchaWidgetMapper;
use SprykerEco\Client\CaptchaAltcha\Mapper\AltchaWidgetMapperInterface;
use SprykerEco\Client\CaptchaAltcha\Verifier\AltchaVerifierInterface;
use SprykerEco\Client\CaptchaAltcha\Verifier\LocalAltchaVerifier;
use SprykerEco\Client\CaptchaAltcha\Verifier\ZedAltchaVerifier;
use SprykerEco\Client\CaptchaAltcha\Zed\CaptchaAltchaStub;
use SprykerEco\Client\CaptchaAltcha\Zed\CaptchaAltchaStubInterface;

/**
 * @method \SprykerEco\Client\CaptchaAltcha\CaptchaAltchaConfig getConfig()
 */
class CaptchaAltchaFactory extends AbstractFactory
{
    use LoggerTrait;

    public function createAltchaChallengeCreator(): AltchaChallengeCreatorInterface
    {
        if ($this->getIsConfigurationServiceProvided()) {
            return $this->createLocalAltchaChallengeCreator();
        }

        return $this->createZedAltchaChallengeCreator();
    }

    public function createLocalAltchaChallengeCreator(): AltchaChallengeCreatorInterface
    {
        return new LocalAltchaChallengeCreator(
            $this->createAltcha(),
            $this->createKeyDerivationAlgorithm(),
            $this->createAltchaSecretChecker(),
            $this->createAltchaWidgetMapper(),
            $this->getConfig(),
        );
    }

    public function createZedAltchaChallengeCreator(): AltchaChallengeCreatorInterface
    {
        return new ZedAltchaChallengeCreator($this->createZedStub(), $this->getConfig(), $this->getLogger());
    }

    public function createAltchaVerifier(): AltchaVerifierInterface
    {
        if ($this->getIsConfigurationServiceProvided()) {
            return $this->createLocalAltchaVerifier();
        }

        return $this->createZedAltchaVerifier();
    }

    public function createLocalAltchaVerifier(): AltchaVerifierInterface
    {
        return new LocalAltchaVerifier(
            $this->createAltcha(),
            $this->createKeyDerivationAlgorithm(),
            $this->createAltchaSecretChecker(),
            $this->getConfig(),
        );
    }

    public function createZedAltchaVerifier(): AltchaVerifierInterface
    {
        return new ZedAltchaVerifier($this->createZedStub(), $this->getConfig(), $this->getLogger());
    }

    public function createZedStub(): CaptchaAltchaStubInterface
    {
        return new CaptchaAltchaStub($this->getZedRequestClient());
    }

    public function createAltchaSecretChecker(): AltchaSecretCheckerInterface
    {
        return new AltchaSecretChecker($this->getConfig(), $this->getLogger());
    }

    public function createAltchaWidgetMapper(): AltchaWidgetMapperInterface
    {
        return new AltchaWidgetMapper($this->getConfig());
    }

    public function createAltchaChallengeUrlWidgetCreator(): AltchaChallengeUrlWidgetCreatorInterface
    {
        return new AltchaChallengeUrlWidgetCreator($this->createAltchaWidgetMapper());
    }

    public function createAltchaChallengeDataCreator(): AltchaChallengeDataCreatorInterface
    {
        return new AltchaChallengeDataCreator($this->createAltchaChallengeCreator(), $this->createAltchaWidgetMapper());
    }

    public function createAltcha(): Altcha
    {
        return new Altcha($this->getConfig()->getHmacSecret(), $this->getConfig()->getHmacKeySignatureSecret());
    }

    public function createKeyDerivationAlgorithm(): DeriveKeyInterface
    {
        return $this->createKeyDerivationAlgorithmStrategyResolver()->get($this->getConfig()->getKeyDerivationAlgorithm());
    }

    /**
     * @return \Spryker\Shared\Kernel\StrategyResolverInterface<\AltchaOrg\Altcha\Algorithm\DeriveKeyInterface>
     */
    public function createKeyDerivationAlgorithmStrategyResolver(): StrategyResolverInterface
    {
        return new StrategyResolver([
            CaptchaAltchaConfig::KEY_DERIVATION_ALGORITHM_ARGON2ID => fn (): DeriveKeyInterface => $this->createArgon2idKeyDerivationAlgorithm(),
            CaptchaAltchaConfig::KEY_DERIVATION_ALGORITHM_PBKDF2_SHA256 => fn (): DeriveKeyInterface => $this->createPbkdf2KeyDerivationAlgorithm(),
        ]);
    }

    public function createArgon2idKeyDerivationAlgorithm(): DeriveKeyInterface
    {
        return new Argon2id();
    }

    public function createPbkdf2KeyDerivationAlgorithm(): DeriveKeyInterface
    {
        return new Pbkdf2();
    }

    public function getZedRequestClient(): ZedRequestClientInterface
    {
        return $this->getProvidedDependency(CaptchaAltchaDependencyProvider::CLIENT_ZED_REQUEST);
    }

    public function getIsConfigurationServiceProvided(): bool
    {
        return $this->getProvidedDependency(CaptchaAltchaDependencyProvider::IS_CONFIGURATION_SERVICE_PROVIDED);
    }
}
