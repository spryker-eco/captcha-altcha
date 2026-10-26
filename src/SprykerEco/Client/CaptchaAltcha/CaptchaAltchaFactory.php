<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Client\CaptchaAltcha;

use AltchaOrg\Altcha\Algorithm\DeriveKeyInterface;
use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\Altcha;
use Spryker\Client\Kernel\AbstractFactory;
use Spryker\Client\Redis\RedisClientInterface;
use Spryker\Shared\Log\LoggerTrait;
use SprykerEco\Client\CaptchaAltcha\ChallengeCreator\AltchaChallengeCreator;
use SprykerEco\Client\CaptchaAltcha\ChallengeCreator\AltchaChallengeCreatorInterface;
use SprykerEco\Client\CaptchaAltcha\Checker\AltchaSecretChecker;
use SprykerEco\Client\CaptchaAltcha\Checker\AltchaSecretCheckerInterface;
use SprykerEco\Client\CaptchaAltcha\Mapper\AltchaWidgetMapper;
use SprykerEco\Client\CaptchaAltcha\Mapper\AltchaWidgetMapperInterface;
use SprykerEco\Client\CaptchaAltcha\ReplayStore\AltchaReplayStore;
use SprykerEco\Client\CaptchaAltcha\ReplayStore\AltchaReplayStoreInterface;
use SprykerEco\Client\CaptchaAltcha\Verifier\AltchaVerifier;
use SprykerEco\Client\CaptchaAltcha\Verifier\AltchaVerifierInterface;

/**
 * @method \SprykerEco\Client\CaptchaAltcha\CaptchaAltchaConfig getConfig()
 */
class CaptchaAltchaFactory extends AbstractFactory
{
    use LoggerTrait;

    public function createAltchaChallengeCreator(): AltchaChallengeCreatorInterface
    {
        return new AltchaChallengeCreator(
            $this->createAltcha(),
            $this->createKeyDerivationAlgorithm(),
            $this->createAltchaSecretChecker(),
            $this->createAltchaWidgetMapper(),
            $this->getConfig(),
        );
    }

    public function createAltchaVerifier(): AltchaVerifierInterface
    {
        return new AltchaVerifier(
            $this->createAltcha(),
            $this->createKeyDerivationAlgorithm(),
            $this->createAltchaSecretChecker(),
            $this->createAltchaReplayStore(),
            $this->getConfig(),
        );
    }

    public function createAltchaReplayStore(): AltchaReplayStoreInterface
    {
        return new AltchaReplayStore($this->getRedisClient(), $this->getConfig());
    }

    public function createAltchaSecretChecker(): AltchaSecretCheckerInterface
    {
        return new AltchaSecretChecker($this->getConfig(), $this->getLogger());
    }

    public function createAltchaWidgetMapper(): AltchaWidgetMapperInterface
    {
        return new AltchaWidgetMapper();
    }

    public function createAltcha(): Altcha
    {
        return new Altcha($this->getConfig()->getHmacSecret());
    }

    public function createKeyDerivationAlgorithm(): DeriveKeyInterface
    {
        return new Pbkdf2();
    }

    public function getRedisClient(): RedisClientInterface
    {
        return $this->getProvidedDependency(CaptchaAltchaDependencyProvider::CLIENT_REDIS);
    }
}
