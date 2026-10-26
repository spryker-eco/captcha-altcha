<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Client\CaptchaAltcha\ChallengeCreator;

use AltchaOrg\Altcha\Algorithm\DeriveKeyInterface;
use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\CreateChallengeOptions;
use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;
use Generated\Shared\Transfer\CaptchaChallengeResponseTransfer;
use Generated\Shared\Transfer\CaptchaWidgetTransfer;
use Generated\Shared\Transfer\MessageTransfer;
use SprykerEco\Client\CaptchaAltcha\CaptchaAltchaConfig;
use SprykerEco\Client\CaptchaAltcha\Checker\AltchaSecretCheckerInterface;
use SprykerEco\Client\CaptchaAltcha\Mapper\AltchaWidgetMapperInterface;
use SprykerEco\Shared\CaptchaAltcha\CaptchaAltchaConfig as SharedCaptchaAltchaConfig;

class AltchaChallengeCreator implements AltchaChallengeCreatorInterface
{
    public function __construct(
        protected Altcha $altcha,
        protected DeriveKeyInterface $keyDerivationAlgorithm,
        protected AltchaSecretCheckerInterface $altchaSecretChecker,
        protected AltchaWidgetMapperInterface $altchaWidgetMapper,
        protected CaptchaAltchaConfig $captchaAltchaConfig,
    ) {
    }

    public function createChallenge(CaptchaChallengeRequestTransfer $captchaChallengeRequestTransfer): CaptchaChallengeResponseTransfer
    {
        if (!$this->altchaSecretChecker->isHmacSecretConfigured()) {
            return (new CaptchaChallengeResponseTransfer())
                ->setIsSuccessful(false)
                ->addMessage((new MessageTransfer())->setValue(SharedCaptchaAltchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_UNAVAILABLE));
        }

        $challenge = $this->altcha->createChallenge(
            $this->createChallengeOptions((string)$captchaChallengeRequestTransfer->getContext()),
        );

        return (new CaptchaChallengeResponseTransfer())
            ->setIsSuccessful(true)
            ->setWidget($this->altchaWidgetMapper->mapChallengeToCaptchaWidgetTransfer($challenge, new CaptchaWidgetTransfer()));
    }

    protected function createChallengeOptions(string $context): CreateChallengeOptions
    {
        [$minimumCounter, $maximumCounter] = $this->captchaAltchaConfig->getCounterRange();

        return new CreateChallengeOptions(
            algorithm: $this->keyDerivationAlgorithm,
            cost: $this->captchaAltchaConfig->getCost(),
            counter: random_int($minimumCounter, $maximumCounter),
            expiresAt: time() + $this->captchaAltchaConfig->getChallengeTtl(),
            data: [static::CHALLENGE_DATA_KEY_CONTEXT => $context],
        );
    }
}
