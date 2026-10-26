<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEcoTest\Client\CaptchaAltcha\Helper;

use Codeception\Module;
use Codeception\TestInterface;
use Spryker\Client\Redis\RedisClientInterface;
use SprykerEco\Client\CaptchaAltcha\CaptchaAltchaDependencyProvider;
use SprykerEco\Client\CaptchaAltcha\CaptchaAltchaFactory;
use SprykerEco\Shared\CaptchaAltcha\CaptchaAltchaConstants;
use SprykerTest\Shared\Testify\Helper\ConfigHelperTrait;
use SprykerTest\Shared\Testify\Helper\DependencyHelperTrait;

/**
 * Gives the ALTCHA replay store somewhere to keep spent challenges without a Redis server.
 *
 * Binds {@see \SprykerEcoTest\Client\CaptchaAltcha\Helper\InMemoryCaptchaAltchaRedisClient}, which keeps the `SET NX EX`
 * semantics the replay protection relies on. The binding is scoped to the CaptchaAltcha client factory on purpose: an
 * unscoped `CLIENT_REDIS` would be handed to every module that declares a dependency of that name.
 */
class CaptchaAltchaRedisHelper extends Module
{
    use ConfigHelperTrait;
    use DependencyHelperTrait;

    protected const string CONFIG_KEY_BIND_BEFORE_EACH_TEST = 'bindBeforeEachTest';

    protected const string PLACEHOLDER_REDIS_HOST = 'in-memory';

    protected const int PLACEHOLDER_REDIS_PORT = 6379;

    protected const int PLACEHOLDER_REDIS_DATABASE = 0;

    /**
     * @var array<string, mixed>
     */
    protected array $config = [
        self::CONFIG_KEY_BIND_BEFORE_EACH_TEST => true,
    ];

    /**
     * A fresh client per test, so one test's spent challenges cannot reject the next test's payloads.
     */
    public function _before(TestInterface $test): void // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    {
        if (!$this->config[static::CONFIG_KEY_BIND_BEFORE_EACH_TEST]) {
            return;
        }

        $this->haveInMemoryCaptchaAltchaRedisClient();
    }

    /**
     * Binds a fresh in-memory client and returns it, so a test can assert the stored keys and their expiry.
     * The connection settings are placeholders: the replay store builds its connection configuration from them, the
     * in-memory client never connects.
     */
    public function haveInMemoryCaptchaAltchaRedisClient(): InMemoryCaptchaAltchaRedisClient
    {
        $this->getConfigHelper()->setConfig(CaptchaAltchaConstants::REDIS_HOST, static::PLACEHOLDER_REDIS_HOST);
        $this->getConfigHelper()->setConfig(CaptchaAltchaConstants::REDIS_PORT, static::PLACEHOLDER_REDIS_PORT);
        $this->getConfigHelper()->setConfig(CaptchaAltchaConstants::REDIS_DATABASE, static::PLACEHOLDER_REDIS_DATABASE);

        $inMemoryCaptchaAltchaRedisClient = new InMemoryCaptchaAltchaRedisClient();
        $this->haveCaptchaAltchaRedisClient($inMemoryCaptchaAltchaRedisClient);

        return $inMemoryCaptchaAltchaRedisClient;
    }

    public function haveCaptchaAltchaRedisClient(RedisClientInterface $redisClient): void
    {
        $this->getDependencyHelper()->setDependency(
            CaptchaAltchaDependencyProvider::CLIENT_REDIS,
            $redisClient,
            CaptchaAltchaFactory::class,
        );
    }
}
