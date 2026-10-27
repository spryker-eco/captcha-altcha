<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Client\CaptchaAltcha\Plugin;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;
use Generated\Shared\Transfer\CaptchaVerificationRequestTransfer;
use Spryker\Client\Captcha\CaptchaDependencyProvider;
use Spryker\Client\Captcha\CaptchaFactory;
use Spryker\Shared\Captcha\CaptchaConstants;
use SprykerEco\Client\CaptchaAltcha\Plugin\Captcha\AltchaCaptchaProviderPlugin;
use SprykerEcoTest\Client\CaptchaAltcha\CaptchaAltchaClientTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerEcoTest
 * @group Client
 * @group CaptchaAltcha
 * @group Plugin
 * @group AltchaCaptchaProviderPluginTest
 * Add your own group annotations below this line
 */
class AltchaCaptchaProviderPluginTest extends Unit
{
    protected const string CONNECTION_KEY = 'CAPTCHA_ALTCHA_REDIS';

    protected const string SPENT_KEY_PATTERN = 'captcha:altcha:spent:%s';

    protected CaptchaAltchaClientTester $tester;

    protected function _before(): void
    {
        parent::_before();

        $this->tester->haveAltchaConfiguration();
    }

    public function testAcceptsSolvedChallengeExactlyOnce(): void
    {
        // Arrange
        $payload = $this->tester->haveSolvedPayload(CaptchaAltchaClientTester::CONTEXT_CUSTOMER);

        // Act
        $firstCaptchaVerificationResponseTransfer = $this->tester->verifyThroughPlugin($payload, CaptchaAltchaClientTester::CONTEXT_CUSTOMER);
        $secondCaptchaVerificationResponseTransfer = $this->tester->verifyThroughPlugin($payload, CaptchaAltchaClientTester::CONTEXT_CUSTOMER);

        // Assert
        $this->assertTrue($firstCaptchaVerificationResponseTransfer->getIsValid());
        $this->assertFalse($secondCaptchaVerificationResponseTransfer->getIsValid());
    }

    public function testKeepsReplayMarkUntilChallengeExpires(): void
    {
        // Arrange
        $inMemoryCaptchaAltchaRedisClient = $this->tester->haveInMemoryCaptchaAltchaRedisClient();
        $challenge = $this->tester->getChallengeFromResponse(
            $this->tester->createChallengeThroughPlugin(CaptchaAltchaClientTester::CONTEXT_CUSTOMER),
        );
        $payload = $this->tester->createPayload($challenge, $this->tester->solveChallenge($challenge));
        $expectedKey = sprintf(static::SPENT_KEY_PATTERN, hash('sha256', (string)$challenge->signature));

        // Act
        $this->tester->verifyThroughPlugin($payload, CaptchaAltchaClientTester::CONTEXT_CUSTOMER);

        // Assert
        $this->assertSame([$expectedKey], $inMemoryCaptchaAltchaRedisClient->getStoredKeys(static::CONNECTION_KEY));
        $this->assertEqualsWithDelta(
            $challenge->parameters->expiresAt,
            $inMemoryCaptchaAltchaRedisClient->getExpiresAt(static::CONNECTION_KEY, $expectedKey),
            1,
        );
    }

    public function testRejectsChallengeSolvedForAnotherRealm(): void
    {
        // Arrange
        $payload = $this->tester->haveSolvedPayload(CaptchaAltchaClientTester::CONTEXT_CUSTOMER);

        // Act
        $captchaVerificationResponseTransfer = $this->tester->verifyThroughPlugin($payload, CaptchaAltchaClientTester::CONTEXT_USER);

        // Assert
        $this->assertFalse($captchaVerificationResponseTransfer->getIsValid());
    }

    public function testFailsClosedWithoutHmacSecret(): void
    {
        // Arrange
        $payload = $this->tester->haveSolvedPayload(CaptchaAltchaClientTester::CONTEXT_CUSTOMER);
        $this->tester->haveAltchaConfiguration('');

        // Act
        $captchaChallengeResponseTransfer = $this->tester->createChallengeThroughPlugin(CaptchaAltchaClientTester::CONTEXT_CUSTOMER);
        $captchaVerificationResponseTransfer = $this->tester->verifyThroughPlugin($payload, CaptchaAltchaClientTester::CONTEXT_CUSTOMER);

        // Assert
        $this->assertFalse($captchaChallengeResponseTransfer->getIsSuccessful());
        $this->assertFalse($captchaVerificationResponseTransfer->getIsValid());
    }

    public function testServesAsCaptchaProviderOfCaptchaClient(): void
    {
        // Arrange
        $this->tester->setConfig(CaptchaConstants::IS_CAPTCHA_ENABLED, true);
        $this->tester->setDependency(CaptchaDependencyProvider::PLUGIN_CAPTCHA_PROVIDER, new AltchaCaptchaProviderPlugin(), CaptchaFactory::class);
        $captchaClient = $this->tester->getLocator()->captcha()->client();
        $challenge = $this->tester->getChallengeFromResponse(
            $captchaClient->createChallenge((new CaptchaChallengeRequestTransfer())->setContext(CaptchaAltchaClientTester::CONTEXT_CUSTOMER)),
        );
        $payload = $this->tester->createPayload($challenge, $this->tester->solveChallenge($challenge));

        // Act
        $captchaVerificationResponseTransfer = $captchaClient->verifyCaptcha(
            (new CaptchaVerificationRequestTransfer())->setPayload($payload)->setContext(CaptchaAltchaClientTester::CONTEXT_CUSTOMER),
        );

        // Assert
        $this->assertTrue($captchaClient->isCaptchaEnabled());
        $this->assertTrue($captchaVerificationResponseTransfer->getIsValid());
    }
}
