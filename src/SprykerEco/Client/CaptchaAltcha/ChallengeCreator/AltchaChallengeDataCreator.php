<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Client\CaptchaAltcha\ChallengeCreator;

use Generated\Shared\Transfer\CaptchaChallengeDataResponseTransfer;
use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;
use Generated\Shared\Transfer\MessageTransfer;
use Spryker\Shared\Captcha\CaptchaConfig;
use SprykerEco\Client\CaptchaAltcha\Mapper\AltchaWidgetMapperInterface;

class AltchaChallengeDataCreator implements AltchaChallengeDataCreatorInterface
{
    public function __construct(
        protected AltchaChallengeCreatorInterface $altchaChallengeCreator,
        protected AltchaWidgetMapperInterface $altchaWidgetMapper
    ) {
    }

    public function createChallengeData(CaptchaChallengeRequestTransfer $captchaChallengeRequestTransfer): CaptchaChallengeDataResponseTransfer
    {
        $captchaChallengeResponseTransfer = $this->altchaChallengeCreator->createChallenge($captchaChallengeRequestTransfer);
        $captchaWidgetTransfer = $captchaChallengeResponseTransfer->getWidget();
        $challengeData = $captchaWidgetTransfer !== null ? $this->altchaWidgetMapper->findChallengeData($captchaWidgetTransfer) : null;

        if (!$captchaChallengeResponseTransfer->getIsSuccessful() || $challengeData === null) {
            return $this->createUnavailableResponse($captchaChallengeResponseTransfer->getMessages()->getArrayCopy());
        }

        return (new CaptchaChallengeDataResponseTransfer())
            ->setIsSuccessful(true)
            ->setChallengeData($challengeData);
    }

    /**
     * @param array<\Generated\Shared\Transfer\MessageTransfer> $messageTransfers
     */
    protected function createUnavailableResponse(array $messageTransfers): CaptchaChallengeDataResponseTransfer
    {
        $captchaChallengeDataResponseTransfer = (new CaptchaChallengeDataResponseTransfer())->setIsSuccessful(false);

        if ($messageTransfers === []) {
            $messageTransfers = [(new MessageTransfer())->setValue(CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_UNAVAILABLE)];
        }

        foreach ($messageTransfers as $messageTransfer) {
            $captchaChallengeDataResponseTransfer->addMessage($messageTransfer);
        }

        return $captchaChallengeDataResponseTransfer;
    }
}
