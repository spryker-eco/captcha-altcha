<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Client\CaptchaAltcha\Client;

use Codeception\Test\Unit;
use Spryker\Client\ZedRequest\ZedRequestClientInterface;
use Spryker\Shared\Captcha\CaptchaConfig;
use SprykerEcoTest\Client\CaptchaAltcha\CaptchaAltchaClientTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerEcoTest
 * @group Client
 * @group CaptchaAltcha
 * @group Client
 * @group VerifyCaptchaPayloadLocallyTest
 * Add your own group annotations below this line
 */
class VerifyCaptchaPayloadLocallyTest extends Unit
{
    protected CaptchaAltchaClientTester $tester;

    /**
     * The Zed gateway calls this method, so it must verify in-process even where the selection would route to Zed.
     */
    public function testAcceptsPayloadInProcessWhereConfigurationServiceIsMissing(): void
    {
        // Arrange
        $this->tester->haveAltchaConfiguration();
        $challenge = $this->tester->getChallengeFromResponse($this->tester->createChallengeThroughPlugin(CaptchaAltchaClientTester::CONTEXT_CUSTOMER));
        $payload = $this->tester->createPayload($challenge, $this->tester->solveChallenge($challenge));
        $zedRequestClientMock = $this->createMock(ZedRequestClientInterface::class);
        $zedRequestClientMock->expects($this->never())->method('call');
        $this->tester->haveZedRequestClient($zedRequestClientMock, false);

        // Act
        $captchaVerificationResponseTransfer = $this->tester->createClient()->verifyCaptchaPayloadLocally(
            $this->tester->createVerificationRequest($payload, CaptchaAltchaClientTester::CONTEXT_CUSTOMER),
        );

        // Assert
        $this->assertTrue($captchaVerificationResponseTransfer->getIsValid());
        $this->assertSame($challenge->signature, $captchaVerificationResponseTransfer->getReplayKey());
    }

    public function testAnswersUnavailableWhenHmacSecretIsNotReadable(): void
    {
        // Arrange
        $this->tester->haveAltchaConfiguration();
        $payload = $this->tester->haveSolvedPayload(CaptchaAltchaClientTester::CONTEXT_CUSTOMER);
        $this->tester->haveAltchaConfiguration('');

        // Act
        $captchaVerificationResponseTransfer = $this->tester->createClient()->verifyCaptchaPayloadLocally(
            $this->tester->createVerificationRequest($payload, CaptchaAltchaClientTester::CONTEXT_CUSTOMER),
        );

        // Assert
        $this->assertFalse($captchaVerificationResponseTransfer->getIsValid());
        $this->assertSame(CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_UNAVAILABLE, $captchaVerificationResponseTransfer->getMessages()->offsetGet(0)->getValue());
    }
}
