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
use Spryker\Client\Kernel\AbstractClient;

/**
 * @method \SprykerEco\Client\CaptchaAltcha\CaptchaAltchaFactory getFactory()
 */
class CaptchaAltchaClient extends AbstractClient implements CaptchaAltchaClientInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function createCaptchaChallengeLocally(CaptchaChallengeRequestTransfer $captchaChallengeRequestTransfer): CaptchaChallengeResponseTransfer
    {
        return $this->getFactory()
            ->createLocalAltchaChallengeCreator()
            ->createChallenge($captchaChallengeRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function verifyCaptchaPayloadLocally(CaptchaVerificationRequestTransfer $captchaVerificationRequestTransfer): CaptchaVerificationResponseTransfer
    {
        return $this->getFactory()
            ->createLocalAltchaVerifier()
            ->verify($captchaVerificationRequestTransfer);
    }
}
