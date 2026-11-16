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
use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * @method \SprykerEco\Zed\CaptchaAltcha\Business\CaptchaAltchaBusinessFactory getFactory()
 */
class CaptchaAltchaFacade extends AbstractFacade implements CaptchaAltchaFacadeInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function createCaptchaChallenge(CaptchaChallengeRequestTransfer $captchaChallengeRequestTransfer): CaptchaChallengeResponseTransfer
    {
        return $this->getFactory()
            ->getCaptchaAltchaClient()
            ->createCaptchaChallengeLocally($captchaChallengeRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function verifyCaptchaPayload(CaptchaVerificationRequestTransfer $captchaVerificationRequestTransfer): CaptchaVerificationResponseTransfer
    {
        return $this->getFactory()
            ->getCaptchaAltchaClient()
            ->verifyCaptchaPayloadLocally($captchaVerificationRequestTransfer);
    }
}
