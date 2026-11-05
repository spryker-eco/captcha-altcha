<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Client\CaptchaAltcha\Client;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;
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
 * @group CreateCaptchaChallengeLocallyTest
 * Add your own group annotations below this line
 */
class CreateCaptchaChallengeLocallyTest extends Unit
{
    protected CaptchaAltchaClientTester $tester;

    /**
     * The Zed gateway calls this method, so it must sign in-process even where the selection would route to Zed.
     */
    public function testSignsChallengeInProcessWhereConfigurationServiceIsMissing(): void
    {
        // Arrange
        $this->tester->haveAltchaConfiguration();
        $zedRequestClientMock = $this->createMock(ZedRequestClientInterface::class);
        $zedRequestClientMock->expects($this->never())->method('call');
        $this->tester->haveZedRequestClient($zedRequestClientMock, false);

        // Act
        $captchaChallengeResponseTransfer = $this->tester->createClient()->createCaptchaChallengeLocally(
            (new CaptchaChallengeRequestTransfer())->setContext(CaptchaAltchaClientTester::CONTEXT_CUSTOMER),
        );

        // Assert
        $this->assertTrue($captchaChallengeResponseTransfer->getIsSuccessful());
        $this->assertNotEmpty($this->tester->getChallengeFromResponse($captchaChallengeResponseTransfer)->signature);
    }

    public function testAnswersUnavailableWhenHmacSecretIsNotReadable(): void
    {
        // Arrange
        $this->tester->haveAltchaConfiguration('');

        // Act
        $captchaChallengeResponseTransfer = $this->tester->createClient()->createCaptchaChallengeLocally(
            (new CaptchaChallengeRequestTransfer())->setContext(CaptchaAltchaClientTester::CONTEXT_CUSTOMER),
        );

        // Assert
        $this->assertFalse($captchaChallengeResponseTransfer->getIsSuccessful());
        $this->assertNull($captchaChallengeResponseTransfer->getWidget());
        $this->assertSame(CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_UNAVAILABLE, $captchaChallengeResponseTransfer->getMessages()->offsetGet(0)->getValue());
    }
}
