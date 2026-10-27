<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Client\CaptchaAltcha;

use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\Challenge;
use AltchaOrg\Altcha\CreateChallengeOptions;
use AltchaOrg\Altcha\Payload;
use AltchaOrg\Altcha\Solution;
use AltchaOrg\Altcha\SolveChallengeOptions;
use Codeception\Actor;
use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;
use Generated\Shared\Transfer\CaptchaChallengeResponseTransfer;
use Generated\Shared\Transfer\CaptchaVerificationRequestTransfer;
use Generated\Shared\Transfer\CaptchaVerificationResponseTransfer;
use SprykerEco\Client\CaptchaAltcha\CaptchaAltchaFactory;
use SprykerEco\Client\CaptchaAltcha\Plugin\Captcha\AltchaCaptchaProviderPlugin;
use SprykerEco\Shared\CaptchaAltcha\CaptchaAltchaConstants;

/**
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
 */
class CaptchaAltchaClientTester extends Actor
{
    use _generated\CaptchaAltchaClientTesterActions;

    public const string HMAC_SECRET = 'test-captcha-altcha-hmac-secret';

    public const string OTHER_HMAC_SECRET = 'other-captcha-altcha-hmac-secret';

    /**
     * Keeps solving in the test below a millisecond-range budget: about `counter × cost` PBKDF2 iterations.
     */
    public const int COST = 10;

    public const int CHALLENGE_TTL = 1200;

    public const string CONTEXT_CUSTOMER = 'customer';

    public const string CONTEXT_USER = 'user';

    public const string WIDGET_ATTRIBUTE_CHALLENGE = 'challenge';

    public const string CHALLENGE_DATA_KEY_CONTEXT = 'context';

    public function createCaptchaAltchaFactory(): CaptchaAltchaFactory
    {
        return new CaptchaAltchaFactory();
    }

    public function haveAltchaConfiguration(string $hmacSecret = self::HMAC_SECRET, int $challengeTtl = self::CHALLENGE_TTL): void
    {
        $this->setConfig(CaptchaAltchaConstants::HMAC_SECRET, $hmacSecret);
        $this->setConfig(CaptchaAltchaConstants::COST, static::COST);
        $this->setConfig(CaptchaAltchaConstants::CHALLENGE_TTL, $challengeTtl);
    }

    public function createChallengeThroughPlugin(string $context): CaptchaChallengeResponseTransfer
    {
        return (new AltchaCaptchaProviderPlugin())->createChallenge((new CaptchaChallengeRequestTransfer())->setContext($context));
    }

    public function verifyThroughPlugin(string $payload, string $context): CaptchaVerificationResponseTransfer
    {
        return (new AltchaCaptchaProviderPlugin())->verify(
            (new CaptchaVerificationRequestTransfer())->setPayload($payload)->setContext($context),
        );
    }

    public function getChallengeFromResponse(CaptchaChallengeResponseTransfer $captchaChallengeResponseTransfer): Challenge
    {
        $challengeJson = $captchaChallengeResponseTransfer->getWidgetOrFail()->getAttributes()[static::WIDGET_ATTRIBUTE_CHALLENGE];

        return Challenge::fromArray(json_decode($challengeJson, true, 512, JSON_THROW_ON_ERROR));
    }

    public function solveChallenge(Challenge $challenge): Solution
    {
        $solution = (new Altcha())->solveChallenge(new SolveChallengeOptions($challenge, new Pbkdf2()));
        $this->assertNotNull($solution);

        return $solution;
    }

    public function createPayload(Challenge $challenge, Solution $solution): string
    {
        return (new Payload($challenge, $solution))->toBase64();
    }

    public function haveSolvedPayload(string $context): string
    {
        $challenge = $this->getChallengeFromResponse($this->createChallengeThroughPlugin($context));

        return $this->createPayload($challenge, $this->solveChallenge($challenge));
    }

    /**
     * Signs a challenge directly with the library, for cases the challenge creator never produces (a past expiry,
     * another secret).
     *
     * @param array<string, string>|null $data
     */
    public function createSignedChallenge(?array $data, int $expiresAt, string $hmacSecret = self::HMAC_SECRET): Challenge
    {
        return (new Altcha($hmacSecret))->createChallenge(new CreateChallengeOptions(
            algorithm: new Pbkdf2(),
            cost: static::COST,
            counter: static::COST,
            expiresAt: $expiresAt,
            data: $data,
        ));
    }

    /**
     * @param array<string, string>|null $data
     */
    public function haveSolvedPayloadForSignedChallenge(?array $data, int $expiresAt, string $hmacSecret = self::HMAC_SECRET): string
    {
        $challenge = $this->createSignedChallenge($data, $expiresAt, $hmacSecret);

        return $this->createPayload($challenge, $this->solveChallenge($challenge));
    }
}
