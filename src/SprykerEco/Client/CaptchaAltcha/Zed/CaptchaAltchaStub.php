<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Client\CaptchaAltcha\Zed;

use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;
use Generated\Shared\Transfer\CaptchaChallengeResponseTransfer;
use Generated\Shared\Transfer\CaptchaVerificationRequestTransfer;
use Generated\Shared\Transfer\CaptchaVerificationResponseTransfer;
use Spryker\Client\ZedRequest\ZedRequestClientInterface;

class CaptchaAltchaStub implements CaptchaAltchaStubInterface
{
    /**
     * @uses \SprykerEco\Zed\CaptchaAltcha\Communication\Controller\GatewayController::createCaptchaChallengeAction()
     */
    protected const string ZED_CREATE_CAPTCHA_CHALLENGE = '/captcha-altcha/gateway/create-captcha-challenge';

    /**
     * @uses \SprykerEco\Zed\CaptchaAltcha\Communication\Controller\GatewayController::verifyCaptchaPayloadAction()
     */
    protected const string ZED_VERIFY_CAPTCHA_PAYLOAD = '/captcha-altcha/gateway/verify-captcha-payload';

    public function __construct(protected ZedRequestClientInterface $zedRequestClient)
    {
    }

    public function createCaptchaChallenge(CaptchaChallengeRequestTransfer $captchaChallengeRequestTransfer): CaptchaChallengeResponseTransfer
    {
        /** @var \Generated\Shared\Transfer\CaptchaChallengeResponseTransfer $captchaChallengeResponseTransfer */
        $captchaChallengeResponseTransfer = $this->zedRequestClient->call(static::ZED_CREATE_CAPTCHA_CHALLENGE, $captchaChallengeRequestTransfer);

        return $captchaChallengeResponseTransfer;
    }

    public function verifyCaptchaPayload(CaptchaVerificationRequestTransfer $captchaVerificationRequestTransfer): CaptchaVerificationResponseTransfer
    {
        /** @var \Generated\Shared\Transfer\CaptchaVerificationResponseTransfer $captchaVerificationResponseTransfer */
        $captchaVerificationResponseTransfer = $this->zedRequestClient->call(static::ZED_VERIFY_CAPTCHA_PAYLOAD, $captchaVerificationRequestTransfer);

        return $captchaVerificationResponseTransfer;
    }
}
