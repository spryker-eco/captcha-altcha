<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Client\CaptchaAltcha\Plugin;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;
use Generated\Shared\Transfer\CaptchaChallengeResponseTransfer;
use Generated\Shared\Transfer\CaptchaVerificationRequestTransfer;
use Generated\Shared\Transfer\CaptchaVerificationResponseTransfer;
use Psr\Log\LoggerInterface;
use Spryker\Client\Captcha\CaptchaClientInterface;
use Spryker\Client\Captcha\CaptchaDependencyProvider;
use Spryker\Client\ZedRequest\ZedRequestClientInterface;
use Spryker\Shared\Captcha\CaptchaConstants;
use Spryker\Shared\Kernel\Transfer\TransferInterface;
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
    protected const string CONNECTION_KEY = 'CAPTCHA_REDIS';

    protected const string SPENT_KEY_PATTERN = 'captcha:spent:%s';

    protected const string ZED_CREATE_CAPTCHA_CHALLENGE_URL = '/captcha-altcha/gateway/create-captcha-challenge';

    protected const string ZED_VERIFY_CAPTCHA_PAYLOAD_URL = '/captcha-altcha/gateway/verify-captcha-payload';

    protected const string PAYLOAD_VERIFIED_BY_ZED = 'payload-verified-by-zed';

    protected CaptchaAltchaClientTester $tester;

    protected function _before(): void
    {
        parent::_before();

        $this->tester->haveAltchaConfiguration();
    }

    public function testServesAsCaptchaProviderOfCaptchaClient(): void
    {
        // Arrange
        $captchaClient = $this->haveCaptchaClientWithAltchaProvider();
        $payload = $this->haveSolvedPayloadThroughCaptchaClient($captchaClient);

        // Act
        $captchaVerificationResponseTransfer = $captchaClient->verifyCaptcha($this->createVerificationRequest($payload, CaptchaAltchaClientTester::CONTEXT_CUSTOMER));

        // Assert
        $this->assertTrue($captchaClient->isCaptchaEnabled((new CaptchaChallengeRequestTransfer())->setContext(CaptchaAltchaClientTester::CONTEXT_CUSTOMER)));
        $this->assertTrue($captchaVerificationResponseTransfer->getIsValid());
    }

    public function testPostsPayloadInCaptchaFormField(): void
    {
        // Arrange
        $captchaClient = $this->haveCaptchaClientWithAltchaProvider();

        // Act
        $responseFieldName = $captchaClient->findResponseFieldName();

        // Assert
        $this->assertNull($responseFieldName);
    }

    public function testCaptchaClientAcceptsSolvedChallengeExactlyOnce(): void
    {
        // Arrange
        $captchaClient = $this->haveCaptchaClientWithAltchaProvider();
        $payload = $this->haveSolvedPayloadThroughCaptchaClient($captchaClient);
        $captchaClient->verifyCaptcha($this->createVerificationRequest($payload, CaptchaAltchaClientTester::CONTEXT_CUSTOMER));

        // Act
        $captchaVerificationResponseTransfer = $captchaClient->verifyCaptcha($this->createVerificationRequest($payload, CaptchaAltchaClientTester::CONTEXT_CUSTOMER));

        // Assert
        $this->assertFalse($captchaVerificationResponseTransfer->getIsValid());
    }

    public function testCaptchaClientKeepsReplayMarkUntilChallengeExpires(): void
    {
        // Arrange
        $inMemoryCaptchaRedisClient = $this->tester->haveInMemoryCaptchaRedisClient();
        $captchaClient = $this->haveCaptchaClientWithAltchaProvider();
        $challenge = $this->tester->getChallengeFromResponse(
            $captchaClient->createChallenge((new CaptchaChallengeRequestTransfer())->setContext(CaptchaAltchaClientTester::CONTEXT_CUSTOMER)),
        );
        $payload = $this->tester->createPayload($challenge, $this->tester->solveChallenge($challenge));
        $expectedKey = sprintf(static::SPENT_KEY_PATTERN, hash('sha256', (string)$challenge->signature));

        // Act
        $captchaClient->verifyCaptcha($this->createVerificationRequest($payload, CaptchaAltchaClientTester::CONTEXT_CUSTOMER));

        // Assert
        $this->assertSame([$expectedKey], $inMemoryCaptchaRedisClient->getStoredKeys(static::CONNECTION_KEY));
        $this->assertEqualsWithDelta(
            $challenge->parameters->expiresAt,
            $inMemoryCaptchaRedisClient->getExpiresAt(static::CONNECTION_KEY, $expectedKey),
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

    public function testCreatesAndVerifiesInProcessWhereConfigurationServiceIsProvided(): void
    {
        // Arrange
        $zedRequestClientMock = $this->createMock(ZedRequestClientInterface::class);
        $zedRequestClientMock->expects($this->never())->method('call');
        $this->tester->haveZedRequestClient($zedRequestClientMock, true);
        $payload = $this->tester->haveSolvedPayload(CaptchaAltchaClientTester::CONTEXT_CUSTOMER);

        // Act
        $captchaVerificationResponseTransfer = $this->tester->verifyThroughPlugin($payload, CaptchaAltchaClientTester::CONTEXT_CUSTOMER);

        // Assert
        $this->assertTrue($captchaVerificationResponseTransfer->getIsValid());
    }

    /**
     * The local secret is deliberately empty: the Storefront cannot read the secret setting, so Zed signs the challenge.
     *
     * @dataProvider provideApplicationsWithoutConfigurationService
     */
    public function testCreatesChallengeThroughZedGatewayWhereConfigurationServiceIsMissing(bool $isZedApplication, int $expectedWarningCount): void
    {
        // Arrange
        $this->tester->haveAltchaConfiguration('');
        $this->tester->mockConfigMethod('isZedApplication', $isZedApplication);
        $captchaChallengeRequestTransfer = (new CaptchaChallengeRequestTransfer())
            ->setContext(CaptchaAltchaClientTester::CONTEXT_CUSTOMER)
            ->setServedUntil(time());
        $expectedCaptchaChallengeResponseTransfer = (new CaptchaChallengeResponseTransfer())->setIsSuccessful(true);
        $this->haveZedRequestClientAnswering(static::ZED_CREATE_CAPTCHA_CHALLENGE_URL, $captchaChallengeRequestTransfer, $expectedCaptchaChallengeResponseTransfer);
        $this->haveLoggerExpectingWarnings($expectedWarningCount);

        // Act
        $captchaChallengeResponseTransfer = $this->tester->createPlugin()->createChallenge($captchaChallengeRequestTransfer);

        // Assert
        $this->assertSame($expectedCaptchaChallengeResponseTransfer, $captchaChallengeResponseTransfer);
    }

    /**
     * The local secret is deliberately empty: the Storefront cannot read the secret setting, so Zed verifies the payload.
     *
     * @dataProvider provideApplicationsWithoutConfigurationService
     */
    public function testVerifiesPayloadThroughZedGatewayWhereConfigurationServiceIsMissing(bool $isZedApplication, int $expectedWarningCount): void
    {
        // Arrange
        $this->tester->haveAltchaConfiguration('');
        $this->tester->mockConfigMethod('isZedApplication', $isZedApplication);
        $captchaVerificationRequestTransfer = $this->tester->createVerificationRequest(static::PAYLOAD_VERIFIED_BY_ZED, CaptchaAltchaClientTester::CONTEXT_CUSTOMER);
        $expectedCaptchaVerificationResponseTransfer = (new CaptchaVerificationResponseTransfer())->setIsValid(true);
        $this->haveZedRequestClientAnswering(static::ZED_VERIFY_CAPTCHA_PAYLOAD_URL, $captchaVerificationRequestTransfer, $expectedCaptchaVerificationResponseTransfer);
        $this->haveLoggerExpectingWarnings($expectedWarningCount);

        // Act
        $captchaVerificationResponseTransfer = $this->tester->createPlugin()->verify($captchaVerificationRequestTransfer);

        // Assert
        $this->assertSame($expectedCaptchaVerificationResponseTransfer, $captchaVerificationResponseTransfer);
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function provideApplicationsWithoutConfigurationService(): array
    {
        return [
            'Storefront' => [false, 0],
            'Zed application without ConfigurationApplicationPlugin' => [true, 1],
        ];
    }

    protected function haveZedRequestClientAnswering(string $url, TransferInterface $requestTransfer, TransferInterface $responseTransfer): void
    {
        $zedRequestClientMock = $this->createMock(ZedRequestClientInterface::class);
        $zedRequestClientMock->expects($this->once())
            ->method('call')
            ->with($url, $requestTransfer)
            ->willReturn($responseTransfer);
        $this->tester->haveZedRequestClient($zedRequestClientMock, false);
    }

    /**
     * No error is expected: the Zed gateway path never reads the local secret.
     */
    protected function haveLoggerExpectingWarnings(int $expectedWarningCount): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->exactly($expectedWarningCount))
            ->method('warning')
            ->with($this->stringContains('ConfigurationApplicationPlugin'));
        $loggerMock->expects($this->never())->method('error');
        $this->tester->mockFactoryMethod('getLogger', $loggerMock);
    }

    protected function haveCaptchaClientWithAltchaProvider(): CaptchaClientInterface
    {
        $this->tester->setConfig(CaptchaConstants::IS_CAPTCHA_ENABLED, true);
        $this->tester->setDependency(CaptchaDependencyProvider::PLUGIN_CAPTCHA_PROVIDER, $this->tester->createPlugin());

        return $this->tester->getLocator()->captcha()->client();
    }

    protected function haveSolvedPayloadThroughCaptchaClient(CaptchaClientInterface $captchaClient): string
    {
        $challenge = $this->tester->getChallengeFromResponse(
            $captchaClient->createChallenge((new CaptchaChallengeRequestTransfer())->setContext(CaptchaAltchaClientTester::CONTEXT_CUSTOMER)),
        );

        return $this->tester->createPayload($challenge, $this->tester->solveChallenge($challenge));
    }

    protected function createVerificationRequest(string $payload, string $context): CaptchaVerificationRequestTransfer
    {
        return (new CaptchaVerificationRequestTransfer())->setPayload($payload)->setContext($context);
    }
}
