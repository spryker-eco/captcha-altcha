<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Client\CaptchaAltcha\ChallengeCreator;

use Generated\Shared\Transfer\CaptchaChallengeDataResponseTransfer;
use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;

interface AltchaChallengeDataCreatorInterface
{
    public function createChallengeData(CaptchaChallengeRequestTransfer $captchaChallengeRequestTransfer): CaptchaChallengeDataResponseTransfer;
}
