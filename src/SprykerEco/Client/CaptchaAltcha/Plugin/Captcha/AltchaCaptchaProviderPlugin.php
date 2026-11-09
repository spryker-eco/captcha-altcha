<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Client\CaptchaAltcha\Plugin\Captcha;

use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;
use Generated\Shared\Transfer\CaptchaChallengeResponseTransfer;
use Generated\Shared\Transfer\CaptchaVerificationRequestTransfer;
use Generated\Shared\Transfer\CaptchaVerificationResponseTransfer;
use Spryker\Client\CaptchaExtension\Dependency\Plugin\CaptchaProviderPluginInterface;
use Spryker\Client\Kernel\AbstractPlugin;

/**
 * @method \SprykerEco\Client\CaptchaAltcha\CaptchaAltchaFactory getFactory()
 */
class AltchaCaptchaProviderPlugin extends AbstractPlugin implements CaptchaProviderPluginInterface
{
    /**
     * {@inheritDoc}
     * - Creates an ALTCHA proof-of-work challenge with the key derivation of `CaptchaAltchaConstants::KEY_DERIVATION_ALGORITHM` (Argon2id by default), signed with the HMAC secret of the Back Office configuration (`integrations:altcha:credentials:hmac_secret`); the signed data carries the request context.
     * - The challenge expires `CaptchaAltchaConstants::CHALLENGE_TTL` seconds after `CaptchaChallengeRequestTransfer.servedUntil` when it is set, otherwise after the current time.
     * - Creates the challenge in-process where the application provides the configuration service (`ConfigurationApplicationPlugin`), otherwise through the Zed gateway, which can read the secret. A Zed application without the configuration service logs a warning.
     * - Returns the challenge inline as the `challenge` attribute of an `altcha-widget` element.
     * - Returns `isSuccessful=false` with `captcha.error.unavailable` and logs an error when the HMAC secret is not configured or shorter than 32 bytes.
     *
     * @api
     */
    public function createChallenge(CaptchaChallengeRequestTransfer $captchaChallengeRequestTransfer): CaptchaChallengeResponseTransfer
    {
        return $this->getFactory()
            ->createAltchaChallengeCreator()
            ->createChallenge($captchaChallengeRequestTransfer);
    }

    /**
     * {@inheritDoc}
     * - Expects the base64-encoded payload the `altcha-widget` posts.
     * - Rejects a payload longer than 4096 bytes, without an expiry, or whose signature, solution or signed context does not match, or whose challenge has expired.
     * - Returns the challenge signature as `replayKey` and the challenge expiry as `replayExpiresAt` for a valid payload, so the Captcha client accepts it once.
     * - Verifies the payload in-process where the application provides the configuration service (`ConfigurationApplicationPlugin`), otherwise through the Zed gateway, which can read the secret. A Zed application without the configuration service logs a warning.
     * - Returns `isValid=false` with `captcha.error.unavailable` and logs an error when the HMAC secret is not configured or shorter than 32 bytes.
     *
     * @api
     */
    public function verify(CaptchaVerificationRequestTransfer $captchaVerificationRequestTransfer): CaptchaVerificationResponseTransfer
    {
        return $this->getFactory()
            ->createAltchaVerifier()
            ->verify($captchaVerificationRequestTransfer);
    }

    /**
     * {@inheritDoc}
     * - Returns `null`: the `altcha-widget` posts its payload in the CAPTCHA form field, named through `CaptchaWidget.nameAttribute`.
     *
     * @api
     */
    public function getResponseFieldName(): ?string
    {
        return null;
    }

    /**
     * {@inheritDoc}
     * - Returns `true`: an ALTCHA challenge costs one key derivation to create and its payload is accepted once, so the Captcha
     *   client may create challenges ahead of time.
     *
     * @api
     */
    public function isChallengePoolSupported(): bool
    {
        return true;
    }
}
