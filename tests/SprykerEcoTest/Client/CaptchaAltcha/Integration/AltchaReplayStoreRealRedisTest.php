<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Client\CaptchaAltcha\Integration;

use Codeception\Stub;
use Codeception\Test\Unit;
use SprykerEco\Client\CaptchaAltcha\CaptchaAltchaConfig;
use SprykerEco\Client\CaptchaAltcha\ReplayStore\AltchaReplayStore;
use SprykerEco\Client\CaptchaAltcha\ReplayStore\AltchaReplayStoreInterface;
use Spryker\Client\Redis\RedisClient;
use Spryker\Client\Redis\RedisClientInterface;
use Spryker\Client\Redis\RedisConfig;
use Spryker\Client\Redis\RedisFactory;
use SprykerEco\Shared\CaptchaAltcha\CaptchaAltchaConstants;
use SprykerEcoTest\Client\CaptchaAltcha\CaptchaAltchaClientTester;

/**
 * Proves the replay protection against a real Redis server on both adapters: the in-memory double cannot show how
 * each adapter reports an `NX` collision (phpredis `false`, predis `null`), and a wrong report would silently accept
 * every replayed payload.
 *
 * Auto-generated group annotations
 *
 * @group SprykerEcoTest
 * @group Client
 * @group CaptchaAltcha
 * @group Integration
 * @group AltchaReplayStoreRealRedisTest
 * Add your own group annotations below this line
 */
class AltchaReplayStoreRealRedisTest extends Unit
{
    protected const string ENV_REDIS_HOST = 'SPRYKER_KEY_VALUE_STORE_HOST';

    protected const string ENV_REDIS_PORT = 'SPRYKER_KEY_VALUE_STORE_PORT';

    protected const string ENV_REDIS_PASSWORD = 'SPRYKER_KEY_VALUE_PASSWORD';

    protected const int DEFAULT_REDIS_PORT = 6379;

    protected const int REDIS_DATABASE = 7;

    protected const string CONNECTION_KEY_PATTERN = 'CAPTCHA_ALTCHA_REDIS_REAL_REDIS_TEST_%s';

    protected const string SPENT_KEY_PATTERN = 'captcha:altcha:spent:%s';

    protected const string PHPREDIS_EXTENSION = 'redis';

    protected const int TTL = 60;

    protected CaptchaAltchaClientTester $tester;

    protected function _before(): void
    {
        parent::_before();

        $redisHost = (string)getenv(static::ENV_REDIS_HOST);

        if (!$this->isRedisHostResolvable($redisHost)) {
            $this->markTestSkipped(sprintf(
                'Needs a reachable Redis server: set %s (and %s) to it, e.g. run the suite through "docker/sdk cli" or "docker/sdk testing".',
                static::ENV_REDIS_HOST,
                static::ENV_REDIS_PORT,
            ));
        }

        $this->tester->setConfig(CaptchaAltchaConstants::REDIS_SCHEME, 'tcp');
        $this->tester->setConfig(CaptchaAltchaConstants::REDIS_HOST, $redisHost);
        $this->tester->setConfig(CaptchaAltchaConstants::REDIS_PORT, (int)(getenv(static::ENV_REDIS_PORT) ?: static::DEFAULT_REDIS_PORT));
        $this->tester->setConfig(CaptchaAltchaConstants::REDIS_PASSWORD, getenv(static::ENV_REDIS_PASSWORD) ?: false);
        $this->tester->setConfig(CaptchaAltchaConstants::REDIS_DATABASE, static::REDIS_DATABASE);
    }

    /**
     * @dataProvider provideRedisAdapters
     */
    public function testAcceptsChallengeOnceAndRefusesReplay(bool $usePhpredis): void
    {
        // Arrange
        $redisClient = $this->createRedisClient($usePhpredis);
        $connectionKey = $this->buildConnectionKey($usePhpredis);
        $altchaReplayStore = $this->createAltchaReplayStore($redisClient, $connectionKey);
        $challengeSignature = bin2hex(random_bytes(32));
        $spentKey = sprintf(static::SPENT_KEY_PATTERN, hash('sha256', $challengeSignature));

        // Act
        $isFirstAttemptMarkedAsSpent = $altchaReplayStore->markChallengeAsSpent($challengeSignature, static::TTL);
        $isReplayMarkedAsSpent = $altchaReplayStore->markChallengeAsSpent($challengeSignature, static::TTL);

        // Assert
        $this->assertTrue($isFirstAttemptMarkedAsSpent);
        $this->assertFalse($isReplayMarkedAsSpent);
        $this->assertSame('1', $redisClient->get($connectionKey, $spentKey));

        $redisClient->del($connectionKey, [$spentKey]);
    }

    /**
     * @return array<string, array<bool>>
     */
    public function provideRedisAdapters(): array
    {
        return [
            'phpredis' => [true],
            'predis' => [false],
        ];
    }

    protected function isRedisHostResolvable(string $redisHost): bool
    {
        if ($redisHost === '') {
            return false;
        }

        return filter_var($redisHost, FILTER_VALIDATE_IP) !== false || gethostbyname($redisHost) !== $redisHost;
    }

    protected function createRedisClient(bool $usePhpredis): RedisClientInterface
    {
        if ($usePhpredis && !extension_loaded(static::PHPREDIS_EXTENSION)) {
            $this->markTestSkipped('Needs the phpredis extension ("redis") to test the phpredis adapter.');
        }

        $redisFactory = new RedisFactory();
        $redisFactory->setConfig(Stub::make(RedisConfig::class, ['usePhpredis' => $usePhpredis]));

        $redisClient = new RedisClient();
        $redisClient->setFactory($redisFactory);

        return $redisClient;
    }

    protected function createAltchaReplayStore(RedisClientInterface $redisClient, string $connectionKey): AltchaReplayStoreInterface
    {
        /** @var \SprykerEco\Client\CaptchaAltcha\CaptchaAltchaConfig $captchaAltchaConfig */
        $captchaAltchaConfig = Stub::make(CaptchaAltchaConfig::class, ['getRedisConnectionKey' => $connectionKey]);

        return new AltchaReplayStore($redisClient, $captchaAltchaConfig);
    }

    protected function buildConnectionKey(bool $usePhpredis): string
    {
        return sprintf(static::CONNECTION_KEY_PATTERN, $usePhpredis ? 'PHPREDIS' : 'PREDIS');
    }
}
