<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Zed\CaptchaAltcha;

use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\Challenge;
use AltchaOrg\Altcha\Payload;
use AltchaOrg\Altcha\SolveChallengeOptions;
use Codeception\Actor;
use Codeception\Stub;
use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;
use Generated\Shared\Transfer\CaptchaChallengeResponseTransfer;
use Generated\Shared\Transfer\CaptchaVerificationRequestTransfer;
use Psr\Log\LoggerInterface;
use SprykerEco\Client\CaptchaAltcha\CaptchaAltchaClient;
use SprykerEco\Client\CaptchaAltcha\CaptchaAltchaConfig;
use SprykerEco\Client\CaptchaAltcha\CaptchaAltchaFactory;
use SprykerEco\Zed\CaptchaAltcha\CaptchaAltchaDependencyProvider;

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
 * @method \SprykerEco\Zed\CaptchaAltcha\Business\CaptchaAltchaFacadeInterface getFacade()
 *
 * @SuppressWarnings(PHPMD)
 */
class CaptchaAltchaBusinessTester extends Actor
{
    use _generated\CaptchaAltchaBusinessTesterActions;

    public const string HMAC_SECRET = 'test-captcha-altcha-hmac-secret-of-sufficient-length';

    public const string CONTEXT_CUSTOMER = 'storefront_customer_login';

    public const int CHALLENGE_TTL = 300;

    /**
     * Keeps solving in the tests below a millisecond-range budget: about `counter × cost` PBKDF2 iterations.
     */
    protected const int COST = 10;

    /**
     * @var array<int, int>
     */
    protected const array COUNTER_RANGE = [10, 20];

    protected const string WIDGET_ATTRIBUTE_CHALLENGE = 'challenge';

    /**
     * Only the Back Office configuration read is replaced; the facade-to-signing path runs for real.
     */
    public function haveCaptchaAltchaClient(string $hmacSecret): void
    {
        $captchaAltchaConfigStub = Stub::make(CaptchaAltchaConfig::class, [
            'getHmacSecret' => $hmacSecret,
            'getKeyDerivationAlgorithm' => CaptchaAltchaConfig::KEY_DERIVATION_ALGORITHM_PBKDF2_SHA256,
            'getCost' => static::COST,
            'getCounterRange' => static::COUNTER_RANGE,
            'getChallengeTtl' => static::CHALLENGE_TTL,
        ]);
        $captchaAltchaFactoryStub = Stub::make(CaptchaAltchaFactory::class, [
            'getConfig' => $captchaAltchaConfigStub,
            'getLogger' => Stub::makeEmpty(LoggerInterface::class),
        ]);

        $captchaAltchaClient = new CaptchaAltchaClient();
        $captchaAltchaClient->setFactory($captchaAltchaFactoryStub);

        $this->setDependency(CaptchaAltchaDependencyProvider::CLIENT_CAPTCHA_ALTCHA, $captchaAltchaClient);
    }

    public function createChallengeRequest(?int $servedUntil = null): CaptchaChallengeRequestTransfer
    {
        return (new CaptchaChallengeRequestTransfer())
            ->setContext(static::CONTEXT_CUSTOMER)
            ->setServedUntil($servedUntil);
    }

    public function getChallengeFromResponse(CaptchaChallengeResponseTransfer $captchaChallengeResponseTransfer): Challenge
    {
        $challengeJson = $captchaChallengeResponseTransfer->getWidgetOrFail()->getAttributes()[static::WIDGET_ATTRIBUTE_CHALLENGE];

        return Challenge::fromArray(json_decode($challengeJson, true, 512, JSON_THROW_ON_ERROR));
    }

    public function createVerificationRequestForSolvedChallenge(Challenge $challenge): CaptchaVerificationRequestTransfer
    {
        $solution = (new Altcha())->solveChallenge(new SolveChallengeOptions($challenge, new Pbkdf2()));
        $this->assertNotNull($solution);

        return (new CaptchaVerificationRequestTransfer())
            ->setPayload((new Payload($challenge, $solution))->toBase64())
            ->setContext(static::CONTEXT_CUSTOMER);
    }
}
