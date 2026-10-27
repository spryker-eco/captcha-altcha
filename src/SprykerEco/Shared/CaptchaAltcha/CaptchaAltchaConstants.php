<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Shared\CaptchaAltcha;

/**
 * Declares global environment configuration keys. Do not use it for other class constants.
 */
interface CaptchaAltchaConstants
{
    /**
     * Specification:
     * - Defines the secret that signs (HMAC) ALTCHA challenges.
     * - Required: without it, challenges are neither created nor accepted (fail closed).
     * - Provide it from an environment variable, never hard-code it.
     *
     * @api
     */
    public const string HMAC_SECRET = 'CAPTCHA_ALTCHA:HMAC_SECRET';

    /**
     * Specification:
     * - Defines the PBKDF2 iteration count of one key derivation of an ALTCHA challenge.
     *
     * @api
     */
    public const string COST = 'CAPTCHA_ALTCHA:COST';

    /**
     * Specification:
     * - Defines the lifetime of an ALTCHA challenge in seconds.
     *
     * @api
     */
    public const string CHALLENGE_TTL = 'CAPTCHA_ALTCHA:CHALLENGE_TTL';

    /**
     * Specification:
     * - Defines the scheme (tcp:// or redis://) of the Redis connection that stores spent ALTCHA challenges.
     *
     * @api
     */
    public const string REDIS_SCHEME = 'CAPTCHA_ALTCHA:REDIS_SCHEME';

    /**
     * Specification:
     * - Defines the host of the Redis connection that stores spent ALTCHA challenges.
     *
     * @api
     */
    public const string REDIS_HOST = 'CAPTCHA_ALTCHA:REDIS_HOST';

    /**
     * Specification:
     * - Defines the port of the Redis connection that stores spent ALTCHA challenges.
     *
     * @api
     */
    public const string REDIS_PORT = 'CAPTCHA_ALTCHA:REDIS_PORT';

    /**
     * Specification:
     * - Defines the username for Redis ACL authentication of the connection that stores spent ALTCHA challenges.
     *
     * @api
     */
    public const string REDIS_USER = 'CAPTCHA_ALTCHA:REDIS_USER';

    /**
     * Specification:
     * - Defines the password of the Redis connection that stores spent ALTCHA challenges.
     *
     * @api
     */
    public const string REDIS_PASSWORD = 'CAPTCHA_ALTCHA:REDIS_PASSWORD';

    /**
     * Specification:
     * - Defines the Redis database that stores spent ALTCHA challenges.
     *
     * @api
     */
    public const string REDIS_DATABASE = 'CAPTCHA_ALTCHA:REDIS_DATABASE';

    /**
     * Specification:
     * - Enables or disables a persistent Redis connection for spent ALTCHA challenges.
     *
     * @api
     */
    public const string REDIS_PERSISTENT_CONNECTION = 'CAPTCHA_ALTCHA:REDIS_PERSISTENT_CONNECTION';

    /**
     * Specification:
     * - Defines an array of DSN strings for a multi-instance cluster or replication Redis setup.
     *
     * @api
     */
    public const string REDIS_DATA_SOURCE_NAMES = 'CAPTCHA_ALTCHA:REDIS_DATA_SOURCE_NAMES';

    /**
     * Specification:
     * - Defines an array of Redis client options.
     *
     * @api
     */
    public const string REDIS_CONNECTION_OPTIONS = 'CAPTCHA_ALTCHA:REDIS_CONNECTION_OPTIONS';
}
