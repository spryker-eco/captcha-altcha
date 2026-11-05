<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Client\CaptchaAltcha;

use Spryker\Client\Kernel\AbstractBundleConfig;
use SprykerEco\Shared\CaptchaAltcha\CaptchaAltchaConstants;

class CaptchaAltchaConfig extends AbstractBundleConfig
{
    /**
     * Specification:
     * - Memory-hard key derivation of the proof-of-work, needs the `sodium` PHP extension.
     *
     * @api
     */
    public const string KEY_DERIVATION_ALGORITHM_ARGON2ID = 'ARGON2ID';

    /**
     * Specification:
     * - CPU-bound key derivation of the proof-of-work, bundled with the ALTCHA widget.
     *
     * @api
     */
    public const string KEY_DERIVATION_ALGORITHM_PBKDF2_SHA256 = 'PBKDF2/SHA-256';

    protected const string CONFIGURATION_KEY_HMAC_SECRET = 'integrations:altcha:credentials:hmac_secret';

    protected const string DEFAULT_HMAC_SECRET = '';

    protected const int HMAC_SECRET_MINIMUM_LENGTH = 32;

    protected const string KEY_SIGNATURE_SECRET_LABEL = 'altcha-key-signature';

    protected const string KEY_SIGNATURE_SECRET_HASH_ALGORITHM = 'sha256';

    protected const string DEFAULT_KEY_DERIVATION_ALGORITHM = self::KEY_DERIVATION_ALGORITHM_ARGON2ID;

    /**
     * @var array<string, int>
     */
    protected const array DEFAULT_COSTS = [
        self::KEY_DERIVATION_ALGORITHM_ARGON2ID => 2,
        self::KEY_DERIVATION_ALGORITHM_PBKDF2_SHA256 => 1000,
    ];

    /**
     * One `ARGON2ID` derivation with 2 iterations and 16 MiB takes about 18 ms on one core of the Spryker CLI container;
     * the server pays it once per created challenge.
     */
    protected const int DEFAULT_MEMORY_COST = 16384;

    /**
     * @var array<string, int>
     */
    protected const array DEFAULT_MINIMUM_COUNTERS = [
        self::KEY_DERIVATION_ALGORITHM_ARGON2ID => 20,
        self::KEY_DERIVATION_ALGORITHM_PBKDF2_SHA256 => 1000,
    ];

    protected const int COUNTER_RANGE_UPPER_BOUND_MULTIPLIER = 2;

    protected const int DEFAULT_CHALLENGE_TTL = 300;

    protected const int MAXIMUM_PAYLOAD_LENGTH = 4096;

    /**
     * @var list<string>
     */
    protected const array ZED_APPLICATIONS = ['ZED', 'MERCHANT_PORTAL'];

    /**
     * Specification:
     * - Returns the secret that signs (HMAC) ALTCHA challenges from the Back Office configuration (`integrations:altcha:credentials:hmac_secret`), an empty string when it is not configured.
     * - The secret is a secret setting: it is published to no key-value store, so only an application that registers `ConfigurationApplicationPlugin` reads it; elsewhere it reads as an empty string.
     * - Without a secret, challenges are neither created nor accepted (fail closed).
     *
     * @api
     */
    public function getHmacSecret(): string
    {
        return (string)$this->getModuleConfig(static::CONFIGURATION_KEY_HMAC_SECRET, static::DEFAULT_HMAC_SECRET);
    }

    /**
     * Specification:
     * - Returns the minimum length in bytes of the HMAC secret, `32` by default.
     * - A shorter secret counts as not configured: challenges are neither created nor accepted (fail closed).
     *
     * @api
     */
    public function getHmacSecretMinimumLength(): int
    {
        return static::HMAC_SECRET_MINIMUM_LENGTH;
    }

    /**
     * Specification:
     * - Returns the secret that signs the derived key of a challenge, so a solution is verified without deriving the key again.
     * - Derives it from the HMAC secret, returns an empty string when the HMAC secret is empty.
     *
     * @api
     */
    public function getHmacKeySignatureSecret(): string
    {
        $hmacSecret = $this->getHmacSecret();

        if ($hmacSecret === '') {
            return '';
        }

        return hash_hmac(static::KEY_SIGNATURE_SECRET_HASH_ALGORITHM, static::KEY_SIGNATURE_SECRET_LABEL, $hmacSecret);
    }

    /**
     * Specification:
     * - Returns the key derivation algorithm from `CaptchaAltchaConstants::KEY_DERIVATION_ALGORITHM`, `ARGON2ID` by default.
     *
     * @api
     */
    public function getKeyDerivationAlgorithm(): string
    {
        return (string)$this->get(CaptchaAltchaConstants::KEY_DERIVATION_ALGORITHM, static::DEFAULT_KEY_DERIVATION_ALGORITHM);
    }

    /**
     * Specification:
     * - Returns the iteration count of one key derivation from `CaptchaAltchaConstants::COST`.
     * - Returns `2` for `ARGON2ID` and `1000` for `PBKDF2/SHA-256` by default.
     *
     * @api
     */
    public function getCost(): int
    {
        return (int)$this->get(CaptchaAltchaConstants::COST, static::DEFAULT_COSTS[$this->getKeyDerivationAlgorithm()] ?? 0);
    }

    /**
     * Specification:
     * - Returns the memory of one `ARGON2ID` key derivation in KiB from `CaptchaAltchaConstants::MEMORY_COST`, `16384` (16 MiB) by default.
     *
     * @api
     */
    public function getMemoryCost(): int
    {
        return (int)$this->get(CaptchaAltchaConstants::MEMORY_COST, static::DEFAULT_MEMORY_COST);
    }

    /**
     * Specification:
     * - Returns the inclusive range `[minimum, maximum]` the secret counter of a challenge is drawn from.
     * - Takes the minimum from `CaptchaAltchaConstants::MINIMUM_COUNTER`: `20` for `ARGON2ID` and `1000` for `PBKDF2/SHA-256` by default.
     * - Returns `[minimum, 2 × minimum]`.
     *
     * @api
     *
     * @return array<int, int>
     */
    public function getCounterRange(): array
    {
        $minimumCounter = (int)$this->get(
            CaptchaAltchaConstants::MINIMUM_COUNTER,
            static::DEFAULT_MINIMUM_COUNTERS[$this->getKeyDerivationAlgorithm()] ?? 0,
        );

        return [$minimumCounter, $minimumCounter * static::COUNTER_RANGE_UPPER_BOUND_MULTIPLIER];
    }

    /**
     * Specification:
     * - Returns the lifetime of a challenge in seconds from `CaptchaAltchaConstants::CHALLENGE_TTL`, `300` by default.
     *
     * @api
     */
    public function getChallengeTtl(): int
    {
        return (int)$this->get(CaptchaAltchaConstants::CHALLENGE_TTL, static::DEFAULT_CHALLENGE_TTL);
    }

    /**
     * Specification:
     * - Returns the maximum length in bytes of a submitted payload, `4096` by default.
     * - A longer payload is rejected as invalid before it is decoded.
     *
     * @api
     */
    public function getMaximumPayloadLength(): int
    {
        return static::MAXIMUM_PAYLOAD_LENGTH;
    }

    /**
     * Specification:
     * - Returns `true` in the Back Office, Backend Gateway, Backend API and Merchant Portal applications.
     * - These applications create and verify challenges in-process, so they need `ConfigurationApplicationPlugin` to read the HMAC secret.
     *
     * @api
     */
    public function isZedApplication(): bool
    {
        return defined('APPLICATION') && in_array(constant('APPLICATION'), static::ZED_APPLICATIONS, true);
    }
}
