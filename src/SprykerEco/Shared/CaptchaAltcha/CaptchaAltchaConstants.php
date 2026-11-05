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
     * - Defines the key derivation algorithm of the proof-of-work: `ARGON2ID` or `PBKDF2/SHA-256`.
     * - `ARGON2ID` is memory-hard and resists GPU solvers; it needs the `sodium` PHP extension.
     *
     * @api
     */
    public const string KEY_DERIVATION_ALGORITHM = 'CAPTCHA_ALTCHA:KEY_DERIVATION_ALGORITHM';

    /**
     * Specification:
     * - Defines the iteration count of one key derivation of an ALTCHA challenge.
     *
     * @api
     */
    public const string COST = 'CAPTCHA_ALTCHA:COST';

    /**
     * Specification:
     * - Defines the memory of one `ARGON2ID` key derivation in KiB.
     * - Not used by `PBKDF2/SHA-256`.
     *
     * @api
     */
    public const string MEMORY_COST = 'CAPTCHA_ALTCHA:MEMORY_COST';

    /**
     * Specification:
     * - Defines the lower bound of the secret counter of an ALTCHA challenge: a browser derives up to this many keys to solve it.
     * - The counter is drawn from `[minimum, 2 × minimum]`.
     *
     * @api
     */
    public const string MINIMUM_COUNTER = 'CAPTCHA_ALTCHA:MINIMUM_COUNTER';

    /**
     * Specification:
     * - Defines the lifetime of an ALTCHA challenge in seconds.
     *
     * @api
     */
    public const string CHALLENGE_TTL = 'CAPTCHA_ALTCHA:CHALLENGE_TTL';
}
