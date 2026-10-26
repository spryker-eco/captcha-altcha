<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Client\CaptchaAltcha\ReplayStore;

use Spryker\Client\Redis\RedisClientInterface;
use SprykerEco\Client\CaptchaAltcha\CaptchaAltchaConfig;
use SprykerEco\Client\CaptchaAltcha\Exception\AltchaReplayStoreException;

class AltchaReplayStore implements AltchaReplayStoreInterface
{
    protected const string SPENT_KEY_PATTERN = 'captcha:altcha:spent:%s';

    protected const string SPENT_VALUE = '1';

    protected const string SIGNATURE_HASH_ALGORITHM = 'sha256';

    protected const string EXPIRE_RESOLUTION_SECONDS = 'EX';

    protected const string FLAG_ONLY_IF_NOT_EXISTS = 'NX';

    public function __construct(
        protected RedisClientInterface $redisClient,
        protected CaptchaAltchaConfig $captchaAltchaConfig,
    ) {
    }

    /**
     * @throws \SprykerEco\Client\CaptchaAltcha\Exception\AltchaReplayStoreException
     */
    public function markChallengeAsSpent(string $challengeSignature, int $ttl): bool
    {
        $connectionKey = $this->captchaAltchaConfig->getRedisConnectionKey();
        $this->redisClient->setupConnection($connectionKey, $this->captchaAltchaConfig->getRedisConnectionConfiguration());
        $spentKey = sprintf(static::SPENT_KEY_PATTERN, hash(static::SIGNATURE_HASH_ALGORITHM, $challengeSignature));

        $isMarkedAsSpent = $this->redisClient->set(
            $connectionKey,
            $spentKey,
            static::SPENT_VALUE,
            static::EXPIRE_RESOLUTION_SECONDS,
            $ttl,
            static::FLAG_ONLY_IF_NOT_EXISTS,
        );

        if ($isMarkedAsSpent) {
            return true;
        }

        if ($this->redisClient->get($connectionKey, $spentKey) !== null) {
            return false;
        }

        throw new AltchaReplayStoreException(sprintf('The spent ALTCHA challenge could not be recorded under "%s".', $spentKey));
    }
}
