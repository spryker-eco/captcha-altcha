<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Client\CaptchaAltcha\ChallengeCreator;

use AltchaOrg\Altcha\Challenge;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;
use Generated\Shared\Transfer\CaptchaChallengeResponseTransfer;
use Psr\Log\LoggerInterface;
use SprykerEco\Client\CaptchaAltcha\Plugin\Captcha\AltchaCaptchaProviderPlugin;
use SprykerEcoTest\Client\CaptchaAltcha\CaptchaAltchaClientTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerEcoTest
 * @group Client
 * @group CaptchaAltcha
 * @group ChallengeCreator
 * @group AltchaChallengeCreatorTest
 * Add your own group annotations below this line
 */
class AltchaChallengeCreatorTest extends Unit
{
    protected const string EXPECTED_TAG_NAME = 'altcha-widget';

    protected const string EXPECTED_NAME_ATTRIBUTE = 'name';

    protected const string EXPECTED_LOADER_TAG_NAME = 'captcha-altcha';

    protected const string EXPECTED_SCRIPT_ASSET_PATH = 'js/spryker-zed-captcha-altcha.js';

    protected const string EXPECTED_ALGORITHM = 'PBKDF2/SHA-256';

    protected const string GLOSSARY_KEY_UNAVAILABLE = 'captcha.error.unavailable';

    protected const int SOLVED_CHALLENGE_COUNT = 5;

    protected CaptchaAltchaClientTester $tester;

    protected function _before(): void
    {
        parent::_before();

        $this->tester->haveAltchaConfiguration();
    }

    public function testRendersChallengeInlineIntoAltchaWidget(): void
    {
        // Act
        $captchaChallengeResponseTransfer = $this->createChallenge(CaptchaAltchaClientTester::CONTEXT_CUSTOMER);

        // Assert
        $this->assertTrue($captchaChallengeResponseTransfer->getIsSuccessful());
        $this->assertCount(0, $captchaChallengeResponseTransfer->getMessages());
        $captchaWidgetTransfer = $captchaChallengeResponseTransfer->getWidgetOrFail();
        $this->assertSame(static::EXPECTED_TAG_NAME, $captchaWidgetTransfer->getTagName());
        $this->assertSame(static::EXPECTED_NAME_ATTRIBUTE, $captchaWidgetTransfer->getNameAttribute());
        $this->assertSame(static::EXPECTED_LOADER_TAG_NAME, $captchaWidgetTransfer->getLoaderTagName());
        $this->assertSame([static::EXPECTED_SCRIPT_ASSET_PATH], $captchaWidgetTransfer->getScriptAssetPaths());
        $this->assertSame([CaptchaAltchaClientTester::WIDGET_ATTRIBUTE_CHALLENGE], array_keys($captchaWidgetTransfer->getAttributes()));
        $this->assertStringStartsWith('{', $captchaWidgetTransfer->getAttributes()[CaptchaAltchaClientTester::WIDGET_ATTRIBUTE_CHALLENGE]);
    }

    public function testSignsChallengeAndBindsItToRequestContext(): void
    {
        // Act
        $challenge = $this->tester->getChallengeFromResponse($this->createChallenge(CaptchaAltchaClientTester::CONTEXT_USER));

        // Assert
        $this->assertNotEmpty($challenge->signature);
        $this->assertSame(
            [CaptchaAltchaClientTester::CHALLENGE_DATA_KEY_CONTEXT => CaptchaAltchaClientTester::CONTEXT_USER],
            $challenge->parameters->data,
        );
    }

    public function testUsesConfiguredAlgorithmCostAndLifetime(): void
    {
        // Arrange
        $earliestExpiresAt = time() + CaptchaAltchaClientTester::CHALLENGE_TTL;

        // Act
        $challenge = $this->tester->getChallengeFromResponse($this->createChallenge(CaptchaAltchaClientTester::CONTEXT_CUSTOMER));

        // Assert
        $this->assertSame(static::EXPECTED_ALGORITHM, $challenge->parameters->algorithm);
        $this->assertSame(CaptchaAltchaClientTester::COST, $challenge->parameters->cost);
        $this->assertGreaterThanOrEqual($earliestExpiresAt, $challenge->parameters->expiresAt);
        $this->assertLessThanOrEqual(time() + CaptchaAltchaClientTester::CHALLENGE_TTL, $challenge->parameters->expiresAt);
    }

    public function testDrawsSecretCounterBetweenCostAndTwiceTheCost(): void
    {
        // Arrange
        $challenges = [];
        for ($i = 0; $i < static::SOLVED_CHALLENGE_COUNT; $i++) {
            $challenges[] = $this->tester->getChallengeFromResponse($this->createChallenge(CaptchaAltchaClientTester::CONTEXT_CUSTOMER));
        }

        // Act
        $counters = array_map(function (Challenge $challenge): int {
            return $this->tester->solveChallenge($challenge)->counter;
        }, $challenges);

        // Assert
        foreach ($counters as $counter) {
            $this->assertGreaterThanOrEqual(CaptchaAltchaClientTester::COST, $counter);
            $this->assertLessThanOrEqual(2 * CaptchaAltchaClientTester::COST, $counter);
        }
    }

    public function testFailsClosedAndLogsErrorWithoutHmacSecret(): void
    {
        // Arrange
        $this->tester->haveAltchaConfiguration('');
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method('error');
        $this->tester->mockFactoryMethod('getLogger', $loggerMock);

        // Act
        $captchaChallengeResponseTransfer = (new AltchaCaptchaProviderPlugin())
            ->setFactory($this->tester->getFactory())
            ->createChallenge((new CaptchaChallengeRequestTransfer())->setContext(CaptchaAltchaClientTester::CONTEXT_CUSTOMER));

        // Assert
        $this->assertFalse($captchaChallengeResponseTransfer->getIsSuccessful());
        $this->assertNull($captchaChallengeResponseTransfer->getWidget());
        $this->assertSame(static::GLOSSARY_KEY_UNAVAILABLE, $captchaChallengeResponseTransfer->getMessages()->offsetGet(0)->getValue());
    }

    protected function createChallenge(string $context): CaptchaChallengeResponseTransfer
    {
        return $this->tester->createCaptchaAltchaFactory()
            ->createAltchaChallengeCreator()
            ->createChallenge((new CaptchaChallengeRequestTransfer())->setContext($context));
    }
}
