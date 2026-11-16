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
 * @group VerifyCaptchaPayloadTest
 * Add your own group annotations below this line
 */
class VerifyCaptchaPayloadTest extends Unit
{
    protected CaptchaAltchaBusinessTester $tester;

    public function testAcceptsPayloadOfChallengeSignedInZedAndReturnsReplayKey(): void
    {
        // Arrange
        $this->tester->haveCaptchaAltchaClient(CaptchaAltchaBusinessTester::HMAC_SECRET);
        $challenge = $this->tester->getChallengeFromResponse(
            $this->tester->getFacade()->createCaptchaChallenge($this->tester->createChallengeRequest()),
        );

        // Act
        $captchaVerificationResponseTransfer = $this->tester->getFacade()->verifyCaptchaPayload(
            $this->tester->createVerificationRequestForSolvedChallenge($challenge),
        );

        // Assert
        $this->assertTrue($captchaVerificationResponseTransfer->getIsValid());
        $this->assertSame($challenge->signature, $captchaVerificationResponseTransfer->getReplayKey());
        $this->assertSame((int)$challenge->parameters->expiresAt, $captchaVerificationResponseTransfer->getReplayExpiresAt());
    }

    public function testAnswersUnavailableWhenHmacSecretIsNotReadable(): void
    {
        // Arrange
        $this->tester->haveCaptchaAltchaClient(CaptchaAltchaBusinessTester::HMAC_SECRET);
        $challenge = $this->tester->getChallengeFromResponse(
            $this->tester->getFacade()->createCaptchaChallenge($this->tester->createChallengeRequest()),
        );
        $captchaVerificationRequestTransfer = $this->tester->createVerificationRequestForSolvedChallenge($challenge);
        $this->tester->haveCaptchaAltchaClient('');

        // Act
        $captchaVerificationResponseTransfer = $this->tester->getFacade()->verifyCaptchaPayload($captchaVerificationRequestTransfer);

        // Assert
        $this->assertFalse($captchaVerificationResponseTransfer->getIsValid());
        $this->assertSame(CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_UNAVAILABLE, $captchaVerificationResponseTransfer->getMessages()->offsetGet(0)->getValue());
    }
}
