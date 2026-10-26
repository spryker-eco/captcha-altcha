<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Client\CaptchaAltcha\Mapper;

use AltchaOrg\Altcha\Challenge;
use Generated\Shared\Transfer\CaptchaWidgetTransfer;

interface AltchaWidgetMapperInterface
{
    public function mapChallengeToCaptchaWidgetTransfer(Challenge $challenge, CaptchaWidgetTransfer $captchaWidgetTransfer): CaptchaWidgetTransfer;
}
