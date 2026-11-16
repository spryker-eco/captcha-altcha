<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\CaptchaAltcha\Communication\Controller;

use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;
use Generated\Shared\Transfer\CaptchaChallengeResponseTransfer;
use Generated\Shared\Transfer\CaptchaVerificationRequestTransfer;
use Generated\Shared\Transfer\CaptchaVerificationResponseTransfer;
use Spryker\Zed\Kernel\Communication\Controller\AbstractGatewayController;

/**
 * @method \SprykerEco\Zed\CaptchaAltcha\Business\CaptchaAltchaFacadeInterface getFacade()
 */
class GatewayController extends AbstractGatewayController
{
    public function createCaptchaChallengeAction(CaptchaChallengeRequestTransfer $captchaChallengeRequestTransfer): CaptchaChallengeResponseTransfer
    {
        return $this->getFacade()->createCaptchaChallenge($captchaChallengeRequestTransfer);
    }

    public function verifyCaptchaPayloadAction(CaptchaVerificationRequestTransfer $captchaVerificationRequestTransfer): CaptchaVerificationResponseTransfer
    {
        return $this->getFacade()->verifyCaptchaPayload($captchaVerificationRequestTransfer);
    }
}
