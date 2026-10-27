<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerEcoTest\Client\CaptchaAltcha\Helper;

use Generated\Shared\Transfer\RedisConfigurationTransfer;
use LogicException;
use Spryker\Client\Redis\RedisClientInterface;

/**
 * Stateful in-memory stand-in for the Redis connection the ALTCHA replay store opens.
 *
 * It keeps the `SET ... EX ... NX` semantics the replay protection relies on: a second `NX` write of a key that has not
 * expired yet is refused. An always-succeeding stub would accept every replayed payload and turn the replay assertions
 * into tautologies. Commands the replay store does not use fail loudly instead of answering with made-up data.
 */
class InMemoryCaptchaAltchaRedisClient implements RedisClientInterface
{
    protected const string EXPIRE_RESOLUTION_SECONDS = 'EX';

    protected const string FLAG_ONLY_IF_NOT_EXISTS = 'NX';

    /**
     * @var array<string, array{value: string, expiresAt: int|null}>
     */
    protected array $entries = [];

    /**
     * @var array<string, \Generated\Shared\Transfer\RedisConfigurationTransfer>
     */
    protected array $connectionConfigurations = [];

    public function get(string $connectionKey, string $key): ?string
    {
        $entry = $this->readEntry($connectionKey, $key);

        return $entry === null ? null : $entry['value'];
    }

    public function setex(string $connectionKey, string $key, int $seconds, string $value): bool
    {
        $this->entries[$this->buildEntryKey($connectionKey, $key)] = [
            'value' => $value,
            'expiresAt' => time() + $seconds,
        ];

        return true;
    }

    public function set(
        string $connectionKey,
        string $key,
        string $value,
        ?string $expireResolution = null,
        ?int $expireTTL = null,
        ?string $flag = null
    ): bool {
        if ($flag === static::FLAG_ONLY_IF_NOT_EXISTS && $this->readEntry($connectionKey, $key) !== null) {
            return false;
        }

        $this->entries[$this->buildEntryKey($connectionKey, $key)] = [
            'value' => $value,
            'expiresAt' => $expireResolution === static::EXPIRE_RESOLUTION_SECONDS && $expireTTL !== null ? time() + $expireTTL : null,
        ];

        return true;
    }

    /**
     * @param array<string> $keys
     */
    public function del(string $connectionKey, array $keys): int
    {
        $deletedKeyCount = 0;

        foreach ($keys as $key) {
            if ($this->readEntry($connectionKey, $key) === null) {
                continue;
            }

            unset($this->entries[$this->buildEntryKey($connectionKey, $key)]);
            $deletedKeyCount++;
        }

        return $deletedKeyCount;
    }

    /**
     * @param mixed ...$keysOrArgs
     *
     * @throws \LogicException
     */
    public function eval(string $connectionKey, string $script, int $numKeys, ...$keysOrArgs): bool
    {
        throw $this->createUnsupportedCommandException(__FUNCTION__);
    }

    /**
     * @throws \LogicException
     */
    public function connect(string $connectionKey): void
    {
        throw $this->createUnsupportedCommandException(__FUNCTION__);
    }

    /**
     * @throws \LogicException
     */
    public function disconnect(string $connectionKey): void
    {
        throw $this->createUnsupportedCommandException(__FUNCTION__);
    }

    public function isConnected(string $connectionKey): bool
    {
        return isset($this->connectionConfigurations[$connectionKey]);
    }

    /**
     * @param array<string> $keys
     *
     * @throws \LogicException
     *
     * @return array<string|null>
     */
    public function mget(string $connectionKey, array $keys): array
    {
        throw $this->createUnsupportedCommandException(__FUNCTION__);
    }

    /**
     * @param array<string, string> $dictionary
     *
     * @throws \LogicException
     */
    public function mset(string $connectionKey, array $dictionary): bool
    {
        throw $this->createUnsupportedCommandException(__FUNCTION__);
    }

    /**
     * @throws \LogicException
     *
     * @return array<mixed>
     */
    public function info(string $connectionKey, ?string $section = null): array
    {
        throw $this->createUnsupportedCommandException(__FUNCTION__);
    }

    /**
     * @throws \LogicException
     *
     * @return array<string>
     */
    public function keys(string $connectionKey, string $pattern): array
    {
        throw $this->createUnsupportedCommandException(__FUNCTION__);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws \LogicException
     *
     * @return array<mixed>
     */
    public function scan(string $connectionKey, int $cursor, array $options): array
    {
        throw $this->createUnsupportedCommandException(__FUNCTION__);
    }

    /**
     * @throws \LogicException
     */
    public function dbSize(string $connectionKey): int
    {
        throw $this->createUnsupportedCommandException(__FUNCTION__);
    }

    /**
     * @throws \LogicException
     */
    public function flushDb(string $connectionKey): void
    {
        throw $this->createUnsupportedCommandException(__FUNCTION__);
    }

    public function setupConnection(string $connectionKey, RedisConfigurationTransfer $configurationTransfer): void
    {
        $this->connectionConfigurations[$connectionKey] = $configurationTransfer;
    }

    /**
     * @throws \LogicException
     */
    public function incr(string $connectionKey, string $key): int
    {
        throw $this->createUnsupportedCommandException(__FUNCTION__);
    }

    /**
     * @throws \LogicException
     */
    public function ttl(string $connectionKey, string $key): int
    {
        throw $this->createUnsupportedCommandException(__FUNCTION__);
    }

    /**
     * @throws \LogicException
     */
    public function expire(string $connectionKey, string $key, int $seconds): bool
    {
        throw $this->createUnsupportedCommandException(__FUNCTION__);
    }

    /**
     * Test-only probe: lets a test assert the expiry a write requested.
     */
    public function getExpiresAt(string $connectionKey, string $key): ?int
    {
        return $this->readEntry($connectionKey, $key)['expiresAt'] ?? null;
    }

    /**
     * Test-only probe: lets a test assert which keys a call wrote.
     *
     * @return array<string>
     */
    public function getStoredKeys(string $connectionKey): array
    {
        $storedKeys = [];

        foreach (array_keys($this->entries) as $entryKey) {
            [$entryConnectionKey, $key] = explode('|', $entryKey, 2);

            if ($entryConnectionKey !== $connectionKey || $this->readEntry($connectionKey, $key) === null) {
                continue;
            }

            $storedKeys[] = $key;
        }

        return $storedKeys;
    }

    /**
     * Test-only probe: lets a test assert that the store opened its own connection.
     */
    public function findConnectionConfiguration(string $connectionKey): ?RedisConfigurationTransfer
    {
        return $this->connectionConfigurations[$connectionKey] ?? null;
    }

    /**
     * @return array{value: string, expiresAt: int|null}|null
     */
    protected function readEntry(string $connectionKey, string $key): ?array
    {
        $entryKey = $this->buildEntryKey($connectionKey, $key);
        $entry = $this->entries[$entryKey] ?? null;

        if ($entry === null) {
            return null;
        }

        if ($entry['expiresAt'] !== null && $entry['expiresAt'] <= time()) {
            unset($this->entries[$entryKey]);

            return null;
        }

        return $entry;
    }

    protected function buildEntryKey(string $connectionKey, string $key): string
    {
        return sprintf('%s|%s', $connectionKey, $key);
    }

    protected function createUnsupportedCommandException(string $command): LogicException
    {
        return new LogicException(sprintf('The "%s" command is not supported by the in-memory CAPTCHA ALTCHA Redis double.', $command));
    }
}
