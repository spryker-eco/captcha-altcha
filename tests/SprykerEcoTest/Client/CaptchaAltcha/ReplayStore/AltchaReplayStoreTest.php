<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Client\CaptchaAltcha\ReplayStore;

use Codeception\Test\Unit;
use Spryker\Client\Redis\RedisClientInterface;
use SprykerEco\Client\CaptchaAltcha\CaptchaAltchaConfig;
use SprykerEco\Client\CaptchaAltcha\Exception\AltchaReplayStoreException;
use SprykerEco\Client\CaptchaAltcha\ReplayStore\AltchaReplayStore;
use SprykerEcoTest\Client\CaptchaAltcha\CaptchaAltchaClientTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerEcoTest
 * @group Client
 * @group CaptchaAltcha
 * @group ReplayStore
 * @group AltchaReplayStoreTest
 * Add your own group annotations below this line
 */
class AltchaReplayStoreTest extends Unit
{
    protected const string CONNECTION_KEY = 'CAPTCHA_ALTCHA_REDIS';

    protected const string CHALLENGE_SIGNATURE = 'a6f0c0b8e3d1a7e3c0f2d4b6a8c0e2f4a6b8c0d2e4f6a8b0c2d4e6f8a0b2c4d6';

    protected const string SPENT_KEY_PATTERN = 'captcha:altcha:spent:%s';

    protected const int TTL = 600;

    protected CaptchaAltchaClientTester $tester;

    public function testMarksUnspentChallengeAsSpentUnderHashedSignatureKeyWithTtl(): void
    {
        // Arrange
        $inMemoryCaptchaAltchaRedisClient = $this->tester->haveInMemoryCaptchaAltchaRedisClient();
        $expectedKey = sprintf(static::SPENT_KEY_PATTERN, hash('sha256', static::CHALLENGE_SIGNATURE));

        // Act
        $isMarkedAsSpent = $this->tester->createCaptchaAltchaFactory()
            ->createAltchaReplayStore()
            ->markChallengeAsSpent(static::CHALLENGE_SIGNATURE, static::TTL);

        // Assert
        $this->assertTrue($isMarkedAsSpent);
        $this->assertSame([$expectedKey], $inMemoryCaptchaAltchaRedisClient->getStoredKeys(static::CONNECTION_KEY));
        $this->assertEqualsWithDelta(time() + static::TTL, $inMemoryCaptchaAltchaRedisClient->getExpiresAt(static::CONNECTION_KEY, $expectedKey), 1);
    }

    public function testRefusesChallengeThatIsAlreadySpent(): void
    {
        // Arrange
        $altchaReplayStore = $this->tester->createCaptchaAltchaFactory()->createAltchaReplayStore();
        $altchaReplayStore->markChallengeAsSpent(static::CHALLENGE_SIGNATURE, static::TTL);

        // Act
        $isMarkedAsSpent = $altchaReplayStore->markChallengeAsSpent(static::CHALLENGE_SIGNATURE, static::TTL);

        // Assert
        $this->assertFalse($isMarkedAsSpent);
    }

    /**
     * phpredis answers a refused write (read-only replica, out of memory) with `false` instead of throwing, the same
     * value `NX` returns for a spent challenge. Reporting it as a replay would reject every challenged login silently.
     */
    public function testFailsLoudlyWhenTheChallengeCannotBeRecorded(): void
    {
        // Arrange
        $redisClientMock = $this->createMock(RedisClientInterface::class);
        $redisClientMock->method('set')->willReturn(false);
        $redisClientMock->method('get')->willReturn(null);

        // Assert
        $this->expectException(AltchaReplayStoreException::class);

        // Act
        (new AltchaReplayStore($redisClientMock, new CaptchaAltchaConfig()))->markChallengeAsSpent(static::CHALLENGE_SIGNATURE, static::TTL);
    }

    public function testOpensItsOwnRedisConnection(): void
    {
        // Arrange
        $inMemoryCaptchaAltchaRedisClient = $this->tester->haveInMemoryCaptchaAltchaRedisClient();

        // Act
        $this->tester->createCaptchaAltchaFactory()
            ->createAltchaReplayStore()
            ->markChallengeAsSpent(static::CHALLENGE_SIGNATURE, static::TTL);

        // Assert
        $this->assertNotNull($inMemoryCaptchaAltchaRedisClient->findConnectionConfiguration(static::CONNECTION_KEY));
    }
}
