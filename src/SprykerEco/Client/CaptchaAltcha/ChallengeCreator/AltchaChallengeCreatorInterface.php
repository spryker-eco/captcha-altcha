<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Client\CaptchaAltcha\ChallengeCreator;

use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;
use Generated\Shared\Transfer\CaptchaChallengeResponseTransfer;

interface AltchaChallengeCreatorInterface
{
    /**
     * Key of the signed challenge data that binds a challenge to the context it was created for.
     */
    public const string CHALLENGE_DATA_KEY_CONTEXT = 'context';

    public function createChallenge(CaptchaChallengeRequestTransfer $captchaChallengeRequestTransfer): CaptchaChallengeResponseTransfer;
}
