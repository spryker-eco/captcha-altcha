<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\CaptchaAltcha\Business;

use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;
use Generated\Shared\Transfer\CaptchaChallengeResponseTransfer;
use Generated\Shared\Transfer\CaptchaVerificationRequestTransfer;
use Generated\Shared\Transfer\CaptchaVerificationResponseTransfer;

interface CaptchaAltchaFacadeInterface
{
    /**
     * Specification:
     * - Creates an ALTCHA challenge in Zed, see `CaptchaAltchaClientInterface::createCaptchaChallengeLocally()`.
     * - Serves the Storefront logins through the gateway: Yves cannot read the HMAC secret, a secret setting of the Back Office configuration.
     * - Never calls Zed through a gateway itself.
     *
     * @api
     */
    public function createCaptchaChallenge(CaptchaChallengeRequestTransfer $captchaChallengeRequestTransfer): CaptchaChallengeResponseTransfer;

    /**
     * Specification:
     * - Verifies an ALTCHA payload in Zed, see `CaptchaAltchaClientInterface::verifyCaptchaPayloadLocally()`.
     * - Serves the Storefront logins through the gateway: Yves cannot read the HMAC secret, a secret setting of the Back Office configuration.
     * - Does not remember spent payloads: the Captcha client of the caller accepts the returned `replayKey` once.
     * - Never calls Zed through a gateway itself.
     *
     * @api
     */
    public function verifyCaptchaPayload(CaptchaVerificationRequestTransfer $captchaVerificationRequestTransfer): CaptchaVerificationResponseTransfer;
}
