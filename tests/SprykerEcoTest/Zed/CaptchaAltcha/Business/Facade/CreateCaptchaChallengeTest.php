<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Zed\CaptchaAltcha\Business\Facade;

use Codeception\Test\Unit;
use Spryker\Shared\Captcha\CaptchaConfig;
use SprykerEcoTest\Zed\CaptchaAltcha\CaptchaAltchaBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerEcoTest
 * @group Zed
 * @group CaptchaAltcha
 * @group Business
 * @group Facade
 * @group CreateCaptchaChallengeTest
 * Add your own group annotations below this line
 */
class CreateCaptchaChallengeTest extends Unit
{
    protected const string CHALLENGE_DATA_KEY_CONTEXT = 'context';

    protected const int SERVED_UNTIL_OFFSET = 120;

    protected CaptchaAltchaBusinessTester $tester;

    public function testSignsChallengeBoundToContextInZed(): void
    {
        // Arrange
        $this->tester->haveCaptchaAltchaClient(CaptchaAltchaBusinessTester::HMAC_SECRET);
        $servedUntil = time() + static::SERVED_UNTIL_OFFSET;

        // Act
        $captchaChallengeResponseTransfer = $this->tester->getFacade()->createCaptchaChallenge($this->tester->createChallengeRequest($servedUntil));

        // Assert
        $this->assertTrue($captchaChallengeResponseTransfer->getIsSuccessful());
        $challenge = $this->tester->getChallengeFromResponse($captchaChallengeResponseTransfer);
        $this->assertSame([static::CHALLENGE_DATA_KEY_CONTEXT => CaptchaAltchaBusinessTester::CONTEXT_CUSTOMER], $challenge->parameters->data);
        $this->assertSame($servedUntil + CaptchaAltchaBusinessTester::CHALLENGE_TTL, $challenge->parameters->expiresAt);
    }

    public function testAnswersUnavailableWhenHmacSecretIsNotReadable(): void
    {
        // Arrange
        $this->tester->haveCaptchaAltchaClient('');

        // Act
        $captchaChallengeResponseTransfer = $this->tester->getFacade()->createCaptchaChallenge($this->tester->createChallengeRequest());

        // Assert
        $this->assertFalse($captchaChallengeResponseTransfer->getIsSuccessful());
        $this->assertSame(CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_UNAVAILABLE, $captchaChallengeResponseTransfer->getMessages()->offsetGet(0)->getValue());
    }
}
