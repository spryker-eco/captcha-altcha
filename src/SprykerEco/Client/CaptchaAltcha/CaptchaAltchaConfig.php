<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Client\CaptchaAltcha;

use Generated\Shared\Transfer\RedisConfigurationTransfer;
use Generated\Shared\Transfer\RedisCredentialsTransfer;
use Spryker\Client\Kernel\AbstractBundleConfig;
use SprykerEco\Shared\CaptchaAltcha\CaptchaAltchaConstants;

class CaptchaAltchaConfig extends AbstractBundleConfig
{
    protected const string REDIS_CONNECTION_KEY = 'CAPTCHA_ALTCHA_REDIS';

    protected const string DEFAULT_HMAC_SECRET = '';

    /**
     * Solving takes about `1.5 × cost²` PBKDF2-SHA256 iterations because the counter range grows with the cost. Measured
     * on one core of the Spryker CLI container: about 1.8 s at 1000, about 45 s at 5000 (the library's README example).
     * The widget spreads the work over web workers, so a mid-range phone stays in the same order of magnitude.
     */
    protected const int DEFAULT_COST = 1000;

    protected const int DEFAULT_CHALLENGE_TTL = 1200;

    protected const int COUNTER_RANGE_UPPER_BOUND_COST_MULTIPLIER = 2;

    protected const string DEFAULT_REDIS_SCHEME = 'tcp';

    /**
     * Specification:
     * - Returns the secret that signs (HMAC) ALTCHA challenges from `CaptchaAltchaConstants::HMAC_SECRET`.
     * - Returns an empty string by default: without a secret, challenges are neither created nor accepted (fail closed).
     *
     * @api
     */
    public function getHmacSecret(): string
    {
        return (string)$this->get(CaptchaAltchaConstants::HMAC_SECRET, static::DEFAULT_HMAC_SECRET);
    }

    /**
     * Specification:
     * - Returns the PBKDF2 iteration count of one key derivation from `CaptchaAltchaConstants::COST`, `1000` by default.
     * - A client solves a challenge with about `counter × cost` iterations, i.e. about `1.5 × cost²` with the default counter range, see {@link getCounterRange()}.
     *
     * @api
     */
    public function getCost(): int
    {
        return (int)$this->get(CaptchaAltchaConstants::COST, static::DEFAULT_COST);
    }

    /**
     * Specification:
     * - Returns the inclusive range `[minimum, maximum]` the secret counter of a challenge is drawn from.
     * - Returns `[cost, 2 × cost]` by default.
     *
     * @api
     *
     * @return array<int, int>
     */
    public function getCounterRange(): array
    {
        $cost = $this->getCost();

        return [$cost, $cost * static::COUNTER_RANGE_UPPER_BOUND_COST_MULTIPLIER];
    }

    /**
     * Specification:
     * - Returns the lifetime of a challenge in seconds from `CaptchaAltchaConstants::CHALLENGE_TTL`, `1200` by default.
     *
     * @api
     */
    public function getChallengeTtl(): int
    {
        return (int)$this->get(CaptchaAltchaConstants::CHALLENGE_TTL, static::DEFAULT_CHALLENGE_TTL);
    }

    /**
     * Specification:
     * - Returns the key of the Redis connection that stores spent challenges.
     *
     * @api
     */
    public function getRedisConnectionKey(): string
    {
        return static::REDIS_CONNECTION_KEY;
    }

    /**
     * Specification:
     * - Returns the configuration of the Redis connection that stores spent challenges, from the `CaptchaAltchaConstants::REDIS_*` constants.
     *
     * @api
     */
    public function getRedisConnectionConfiguration(): RedisConfigurationTransfer
    {
        return (new RedisConfigurationTransfer())
            ->setDataSourceNames($this->getDataSourceNames())
            ->setConnectionCredentials($this->getConnectionCredentials())
            ->setClientOptions($this->getConnectionOptions());
    }

    /**
     * @return array<string>
     */
    protected function getDataSourceNames(): array
    {
        return $this->get(CaptchaAltchaConstants::REDIS_DATA_SOURCE_NAMES, []);
    }

    protected function getConnectionCredentials(): RedisCredentialsTransfer
    {
        return (new RedisCredentialsTransfer())
            ->setScheme($this->get(CaptchaAltchaConstants::REDIS_SCHEME, static::DEFAULT_REDIS_SCHEME))
            ->setHost($this->get(CaptchaAltchaConstants::REDIS_HOST))
            ->setPort($this->get(CaptchaAltchaConstants::REDIS_PORT))
            ->setUsername($this->get(CaptchaAltchaConstants::REDIS_USER, false))
            ->setDatabase($this->get(CaptchaAltchaConstants::REDIS_DATABASE))
            ->setPassword($this->get(CaptchaAltchaConstants::REDIS_PASSWORD, false))
            ->setIsPersistent($this->get(CaptchaAltchaConstants::REDIS_PERSISTENT_CONNECTION, false));
    }

    /**
     * @return array<string, mixed>
     */
    protected function getConnectionOptions(): array
    {
        return $this->get(CaptchaAltchaConstants::REDIS_CONNECTION_OPTIONS, []);
    }
}
