<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Client\CaptchaAltcha;

use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;
use Generated\Shared\Transfer\CaptchaChallengeResponseTransfer;
use Generated\Shared\Transfer\CaptchaVerificationRequestTransfer;
use Generated\Shared\Transfer\CaptchaVerificationResponseTransfer;

interface CaptchaAltchaClientInterface
{
    /**
     * Specification:
     * - Creates an ALTCHA proof-of-work challenge in the current process, signed with the HMAC secret of the Back Office configuration; the signed data carries `CaptchaChallengeRequestTransfer.context`.
     * - Uses the key derivation algorithm, cost, memory cost and counter of the environment configuration (`CaptchaAltchaConstants`).
     * - The challenge expires `CaptchaAltchaConstants::CHALLENGE_TTL` seconds after `CaptchaChallengeRequestTransfer.servedUntil` when it is set, otherwise after the current time.
     * - Returns `isSuccessful=true` with the challenge inline as the `challenge` attribute of an `altcha-widget` element.
     * - Returns `isSuccessful=false` with `captcha.error.unavailable` and logs an error when the HMAC secret is empty or shorter than 32 bytes.
     * - The HMAC secret can be read only in an application that registers `ConfigurationApplicationPlugin`.
     * - Never calls Zed.
     *
     * @api
     */
    public function createCaptchaChallengeLocally(CaptchaChallengeRequestTransfer $captchaChallengeRequestTransfer): CaptchaChallengeResponseTransfer;

    /**
     * Specification:
     * - Verifies the base64-encoded ALTCHA payload `CaptchaVerificationRequestTransfer.payload` for `CaptchaVerificationRequestTransfer.context` in the current process, with the HMAC secret of the Back Office configuration.
     * - Returns `isValid=false` with `captcha.error.invalid` for a payload longer than 4096 bytes, without an expiry, or whose signature, solution or signed context does not match.
     * - Returns `isValid=false` with `isExpired=true` and `captcha.error.expired` when the challenge has expired.
     * - Returns `isValid=true` with the challenge signature as `replayKey` and the challenge expiry as `replayExpiresAt` for a valid payload; it does not remember spent payloads itself.
     * - Returns `isValid=false` with `captcha.error.unavailable` and logs an error when the HMAC secret is empty or shorter than 32 bytes.
     * - The HMAC secret can be read only in an application that registers `ConfigurationApplicationPlugin`.
     * - Never calls Zed.
     *
     * @api
     */
    public function verifyCaptchaPayloadLocally(CaptchaVerificationRequestTransfer $captchaVerificationRequestTransfer): CaptchaVerificationResponseTransfer;
}
