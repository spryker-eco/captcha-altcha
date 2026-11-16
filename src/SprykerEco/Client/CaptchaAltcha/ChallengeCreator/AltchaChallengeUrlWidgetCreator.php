<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Client\CaptchaAltcha\ChallengeCreator;

use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;
use Generated\Shared\Transfer\CaptchaChallengeResponseTransfer;
use Generated\Shared\Transfer\CaptchaWidgetTransfer;
use SprykerEco\Client\CaptchaAltcha\Mapper\AltchaWidgetMapperInterface;

class AltchaChallengeUrlWidgetCreator implements AltchaChallengeUrlWidgetCreatorInterface
{
    public function __construct(protected AltchaWidgetMapperInterface $altchaWidgetMapper)
    {
    }

    public function createChallengeUrlWidget(CaptchaChallengeRequestTransfer $captchaChallengeRequestTransfer): CaptchaChallengeResponseTransfer
    {
        $captchaChallengeRequestTransfer->requireContext()->requireChallengeUrl();

        return (new CaptchaChallengeResponseTransfer())
            ->setIsSuccessful(true)
            ->setWidget($this->altchaWidgetMapper->mapChallengeUrlToCaptchaWidgetTransfer(
                (string)$captchaChallengeRequestTransfer->getChallengeUrl(),
                new CaptchaWidgetTransfer(),
            ));
    }
}
