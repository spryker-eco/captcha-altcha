<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Client\CaptchaAltcha\Verifier;

use AltchaOrg\Altcha\Algorithm\DeriveKeyInterface;
use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\Payload;
use AltchaOrg\Altcha\VerifySolutionOptions;
use Generated\Shared\Transfer\CaptchaVerificationRequestTransfer;
use Generated\Shared\Transfer\CaptchaVerificationResponseTransfer;
use Generated\Shared\Transfer\MessageTransfer;
use InvalidArgumentException;
use SprykerEco\Client\CaptchaAltcha\CaptchaAltchaConfig;
use SprykerEco\Client\CaptchaAltcha\ChallengeCreator\AltchaChallengeCreatorInterface;
use SprykerEco\Client\CaptchaAltcha\Checker\AltchaSecretCheckerInterface;
use SprykerEco\Client\CaptchaAltcha\ReplayStore\AltchaReplayStoreInterface;
use SprykerEco\Shared\CaptchaAltcha\CaptchaAltchaConfig as SharedCaptchaAltchaConfig;

class AltchaVerifier implements AltchaVerifierInterface
{
    protected const int MINIMUM_REPLAY_TTL = 1;

    public function __construct(
        protected Altcha $altcha,
        protected DeriveKeyInterface $keyDerivationAlgorithm,
        protected AltchaSecretCheckerInterface $altchaSecretChecker,
        protected AltchaReplayStoreInterface $altchaReplayStore,
        protected CaptchaAltchaConfig $captchaAltchaConfig,
    ) {
    }

    public function verify(CaptchaVerificationRequestTransfer $captchaVerificationRequestTransfer): CaptchaVerificationResponseTransfer
    {
        if (!$this->altchaSecretChecker->isHmacSecretConfigured()) {
            return $this->createRejectedResponse(SharedCaptchaAltchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_UNAVAILABLE);
        }

        try {
            $payload = Payload::fromBase64((string)$captchaVerificationRequestTransfer->getPayload());
        } catch (InvalidArgumentException) {
            return $this->createRejectedResponse(SharedCaptchaAltchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_INVALID);
        }

        return $this->verifyPayload($payload, $captchaVerificationRequestTransfer->getContext());
    }

    protected function verifyPayload(Payload $payload, ?string $context): CaptchaVerificationResponseTransfer
    {
        $verifySolutionResult = $this->altcha->verifySolution(new VerifySolutionOptions($payload, $this->keyDerivationAlgorithm));

        if ($verifySolutionResult->expired) {
            return $this->createRejectedResponse(SharedCaptchaAltchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_EXPIRED)->setIsExpired(true);
        }

        if (!$verifySolutionResult->verified || !$this->isSignedContextMatching($payload, $context)) {
            return $this->createRejectedResponse(SharedCaptchaAltchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_INVALID);
        }

        if (!$this->altchaReplayStore->markChallengeAsSpent((string)$payload->challenge->signature, $this->calculateReplayTtl($payload))) {
            return $this->createRejectedResponse(SharedCaptchaAltchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_INVALID);
        }

        return (new CaptchaVerificationResponseTransfer())
            ->setIsValid(true)
            ->setIsExpired(false);
    }

    protected function isSignedContextMatching(Payload $payload, ?string $context): bool
    {
        $signedContext = $payload->challenge->parameters->data[AltchaChallengeCreatorInterface::CHALLENGE_DATA_KEY_CONTEXT] ?? null;

        return is_string($signedContext) && $signedContext === $context;
    }

    protected function calculateReplayTtl(Payload $payload): int
    {
        $expiresAt = $payload->challenge->parameters->expiresAt ?: time() + $this->captchaAltchaConfig->getChallengeTtl();

        return max(static::MINIMUM_REPLAY_TTL, (int)ceil($expiresAt - time()));
    }

    protected function createRejectedResponse(string $glossaryKey): CaptchaVerificationResponseTransfer
    {
        return (new CaptchaVerificationResponseTransfer())
            ->setIsValid(false)
            ->setIsExpired(false)
            ->addMessage((new MessageTransfer())->setValue($glossaryKey));
    }
}
