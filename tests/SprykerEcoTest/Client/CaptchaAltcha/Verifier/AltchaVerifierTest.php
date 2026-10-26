<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEcoTest\Client\CaptchaAltcha\Verifier;

use AltchaOrg\Altcha\Challenge;
use AltchaOrg\Altcha\ChallengeParameters;
use AltchaOrg\Altcha\Solution;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\CaptchaVerificationRequestTransfer;
use Generated\Shared\Transfer\CaptchaVerificationResponseTransfer;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SprykerEco\Client\CaptchaAltcha\Plugin\Captcha\AltchaCaptchaProviderPlugin;
use SprykerEco\Client\CaptchaAltcha\Verifier\AltchaVerifierInterface;
use Spryker\Client\Redis\RedisClientInterface;
use SprykerEcoTest\Client\CaptchaAltcha\CaptchaAltchaClientTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerEcoTest
 * @group Client
 * @group CaptchaAltcha
 * @group Verifier
 * @group AltchaVerifierTest
 * Add your own group annotations below this line
 */
class AltchaVerifierTest extends Unit
{
    protected const string GLOSSARY_KEY_INVALID = 'captcha.error.invalid';

    protected const string GLOSSARY_KEY_EXPIRED = 'captcha.error.expired';

    protected const string GLOSSARY_KEY_UNAVAILABLE = 'captcha.error.unavailable';

    protected const string FORGED_SIGNATURE = '0000000000000000000000000000000000000000000000000000000000000000';

    protected const string FORGED_SALT = 'ffffffffffffffffffffffffffffffff';

    protected CaptchaAltchaClientTester $tester;

    protected function _before(): void
    {
        parent::_before();

        $this->tester->haveAltchaConfiguration();
    }

    public function testAcceptsPayloadSolvedForSameContext(): void
    {
        // Arrange
        $payload = $this->tester->haveSolvedPayload(CaptchaAltchaClientTester::CONTEXT_CUSTOMER);

        // Act
        $captchaVerificationResponseTransfer = $this->createAltchaVerifier()->verify(
            $this->createVerificationRequest($payload, CaptchaAltchaClientTester::CONTEXT_CUSTOMER),
        );

        // Assert
        $this->assertTrue($captchaVerificationResponseTransfer->getIsValid());
        $this->assertFalse($captchaVerificationResponseTransfer->getIsExpired());
        $this->assertCount(0, $captchaVerificationResponseTransfer->getMessages());
    }

    public function testRejectsPayloadWithTamperedSignature(): void
    {
        // Arrange
        $challenge = $this->createChallenge(CaptchaAltchaClientTester::CONTEXT_CUSTOMER);
        $solution = $this->tester->solveChallenge($challenge);
        $payload = $this->tester->createPayload(new Challenge($challenge->parameters, static::FORGED_SIGNATURE), $solution);

        // Act
        $captchaVerificationResponseTransfer = $this->createAltchaVerifier()->verify(
            $this->createVerificationRequest($payload, CaptchaAltchaClientTester::CONTEXT_CUSTOMER),
        );

        // Assert
        $this->assertRejectedWith(static::GLOSSARY_KEY_INVALID, $captchaVerificationResponseTransfer);
    }

    public function testRejectsPayloadWithTamperedCounter(): void
    {
        // Arrange
        $challenge = $this->createChallenge(CaptchaAltchaClientTester::CONTEXT_CUSTOMER);
        $solution = $this->tester->solveChallenge($challenge);
        $payload = $this->tester->createPayload($challenge, new Solution($solution->counter + 1, $solution->derivedKey));

        // Act
        $captchaVerificationResponseTransfer = $this->createAltchaVerifier()->verify(
            $this->createVerificationRequest($payload, CaptchaAltchaClientTester::CONTEXT_CUSTOMER),
        );

        // Assert
        $this->assertRejectedWith(static::GLOSSARY_KEY_INVALID, $captchaVerificationResponseTransfer);
    }

    public function testRejectsPayloadWithTamperedSalt(): void
    {
        // Arrange
        $challenge = $this->createChallenge(CaptchaAltchaClientTester::CONTEXT_CUSTOMER);
        $solution = $this->tester->solveChallenge($challenge);
        $tamperedParameters = ChallengeParameters::fromArray(['salt' => static::FORGED_SALT] + $challenge->parameters->toArray());
        $payload = $this->tester->createPayload(new Challenge($tamperedParameters, $challenge->signature), $solution);

        // Act
        $captchaVerificationResponseTransfer = $this->createAltchaVerifier()->verify(
            $this->createVerificationRequest($payload, CaptchaAltchaClientTester::CONTEXT_CUSTOMER),
        );

        // Assert
        $this->assertRejectedWith(static::GLOSSARY_KEY_INVALID, $captchaVerificationResponseTransfer);
    }

    public function testRejectsChallengeSignedWithAnotherSecret(): void
    {
        // Arrange
        $payload = $this->tester->haveSolvedPayloadForSignedChallenge(
            [CaptchaAltchaClientTester::CHALLENGE_DATA_KEY_CONTEXT => CaptchaAltchaClientTester::CONTEXT_CUSTOMER],
            time() + CaptchaAltchaClientTester::CHALLENGE_TTL,
            CaptchaAltchaClientTester::OTHER_HMAC_SECRET,
        );

        // Act
        $captchaVerificationResponseTransfer = $this->createAltchaVerifier()->verify(
            $this->createVerificationRequest($payload, CaptchaAltchaClientTester::CONTEXT_CUSTOMER),
        );

        // Assert
        $this->assertRejectedWith(static::GLOSSARY_KEY_INVALID, $captchaVerificationResponseTransfer);
    }

    public function testReportsExpiredChallenge(): void
    {
        // Arrange
        $payload = $this->tester->haveSolvedPayloadForSignedChallenge(
            [CaptchaAltchaClientTester::CHALLENGE_DATA_KEY_CONTEXT => CaptchaAltchaClientTester::CONTEXT_CUSTOMER],
            time() - 1,
        );

        // Act
        $captchaVerificationResponseTransfer = $this->createAltchaVerifier()->verify(
            $this->createVerificationRequest($payload, CaptchaAltchaClientTester::CONTEXT_CUSTOMER),
        );

        // Assert
        $this->assertTrue($captchaVerificationResponseTransfer->getIsExpired());
        $this->assertRejectedWith(static::GLOSSARY_KEY_EXPIRED, $captchaVerificationResponseTransfer);
    }

    public function testRejectsPayloadSolvedForAnotherContext(): void
    {
        // Arrange
        $payload = $this->tester->haveSolvedPayload(CaptchaAltchaClientTester::CONTEXT_CUSTOMER);

        // Act
        $captchaVerificationResponseTransfer = $this->createAltchaVerifier()->verify(
            $this->createVerificationRequest($payload, CaptchaAltchaClientTester::CONTEXT_USER),
        );

        // Assert
        $this->assertRejectedWith(static::GLOSSARY_KEY_INVALID, $captchaVerificationResponseTransfer);
    }

    public function testRejectsSignedChallengeWithoutContext(): void
    {
        // Arrange
        $payload = $this->tester->haveSolvedPayloadForSignedChallenge(null, time() + CaptchaAltchaClientTester::CHALLENGE_TTL);

        // Act
        $captchaVerificationResponseTransfer = $this->createAltchaVerifier()->verify(
            $this->createVerificationRequest($payload, CaptchaAltchaClientTester::CONTEXT_CUSTOMER),
        );

        // Assert
        $this->assertRejectedWith(static::GLOSSARY_KEY_INVALID, $captchaVerificationResponseTransfer);
    }

    /**
     * @dataProvider provideMalformedPayloads
     */
    public function testRejectsMalformedPayloadWithoutThrowing(string $payload): void
    {
        // Act
        $captchaVerificationResponseTransfer = $this->createAltchaVerifier()->verify(
            $this->createVerificationRequest($payload, CaptchaAltchaClientTester::CONTEXT_CUSTOMER),
        );

        // Assert
        $this->assertRejectedWith(static::GLOSSARY_KEY_INVALID, $captchaVerificationResponseTransfer);
    }

    /**
     * @return array<string, array<string>>
     */
    public function provideMalformedPayloads(): array
    {
        return [
            'empty' => [''],
            'not base64' => ['%%% not base64 %%%'],
            'not JSON' => [base64_encode('not json')],
            'JSON scalar' => [base64_encode('1')],
            'missing solution' => [base64_encode('{"challenge":{"parameters":{}}}')],
            'missing challenge parameters' => [base64_encode('{"challenge":{},"solution":{"counter":1,"derivedKey":"00"}}')],
            'non-integer counter' => [base64_encode('{"challenge":{"parameters":{}},"solution":{"counter":"1","derivedKey":"00"}}')],
        ];
    }

    public function testRejectsReplayedPayload(): void
    {
        // Arrange
        $payload = $this->tester->haveSolvedPayload(CaptchaAltchaClientTester::CONTEXT_CUSTOMER);
        $altchaVerifier = $this->createAltchaVerifier();
        $altchaVerifier->verify($this->createVerificationRequest($payload, CaptchaAltchaClientTester::CONTEXT_CUSTOMER));

        // Act
        $captchaVerificationResponseTransfer = $altchaVerifier->verify(
            $this->createVerificationRequest($payload, CaptchaAltchaClientTester::CONTEXT_CUSTOMER),
        );

        // Assert
        $this->assertRejectedWith(static::GLOSSARY_KEY_INVALID, $captchaVerificationResponseTransfer);
    }

    /**
     * Only an accepted payload is spent: otherwise anyone could burn a victim's solved payload by submitting it to
     * the wrong form first.
     */
    public function testRejectedPayloadIsNotSpent(): void
    {
        // Arrange
        $payload = $this->tester->haveSolvedPayload(CaptchaAltchaClientTester::CONTEXT_CUSTOMER);
        $altchaVerifier = $this->createAltchaVerifier();
        $altchaVerifier->verify($this->createVerificationRequest($payload, CaptchaAltchaClientTester::CONTEXT_USER));

        // Act
        $captchaVerificationResponseTransfer = $altchaVerifier->verify(
            $this->createVerificationRequest($payload, CaptchaAltchaClientTester::CONTEXT_CUSTOMER),
        );

        // Assert
        $this->assertTrue($captchaVerificationResponseTransfer->getIsValid());
    }

    /**
     * An unreachable replay store must fail the request: catching the error would either reject every login silently
     * or accept a payload that could not be marked as spent.
     */
    public function testLetsReplayStoreFailureSurfaceInsteadOfAccepting(): void
    {
        // Arrange
        $payload = $this->tester->haveSolvedPayload(CaptchaAltchaClientTester::CONTEXT_CUSTOMER);
        $redisClientMock = $this->createMock(RedisClientInterface::class);
        $redisClientMock->method('set')->willThrowException(new RuntimeException('Connection refused'));
        $this->tester->haveCaptchaAltchaRedisClient($redisClientMock);

        // Assert
        $this->expectException(RuntimeException::class);

        // Act
        $this->createAltchaVerifier()->verify($this->createVerificationRequest($payload, CaptchaAltchaClientTester::CONTEXT_CUSTOMER));
    }

    public function testFailsClosedAndLogsErrorWithoutHmacSecret(): void
    {
        // Arrange
        $payload = $this->tester->haveSolvedPayload(CaptchaAltchaClientTester::CONTEXT_CUSTOMER);
        $this->tester->haveAltchaConfiguration('');
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method('error');
        $this->tester->mockFactoryMethod('getLogger', $loggerMock);

        // Act
        $captchaVerificationResponseTransfer = (new AltchaCaptchaProviderPlugin())
            ->setFactory($this->tester->getFactory())
            ->verify($this->createVerificationRequest($payload, CaptchaAltchaClientTester::CONTEXT_CUSTOMER));

        // Assert
        $this->assertRejectedWith(static::GLOSSARY_KEY_UNAVAILABLE, $captchaVerificationResponseTransfer);
    }

    protected function createAltchaVerifier(): AltchaVerifierInterface
    {
        return $this->tester->createCaptchaAltchaFactory()->createAltchaVerifier();
    }

    protected function createChallenge(string $context): Challenge
    {
        return $this->tester->getChallengeFromResponse($this->tester->createChallengeThroughPlugin($context));
    }

    protected function createVerificationRequest(string $payload, string $context): CaptchaVerificationRequestTransfer
    {
        return (new CaptchaVerificationRequestTransfer())->setPayload($payload)->setContext($context);
    }

    protected function assertRejectedWith(string $expectedGlossaryKey, CaptchaVerificationResponseTransfer $captchaVerificationResponseTransfer): void
    {
        $this->assertFalse($captchaVerificationResponseTransfer->getIsValid());
        $this->assertCount(1, $captchaVerificationResponseTransfer->getMessages());
        $this->assertSame($expectedGlossaryKey, $captchaVerificationResponseTransfer->getMessages()->offsetGet(0)->getValue());
    }
}
