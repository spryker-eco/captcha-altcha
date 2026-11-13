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
use SprykerEco\Client\CaptchaAltcha\Plugin\Captcha\AltchaCaptchaProviderPlugin;
use SprykerEco\Client\CaptchaAltcha\Verifier\AltchaVerifierInterface;
use SprykerEcoTest\Client\CaptchaAltcha\CaptchaAltchaClientTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerEcoTest
 * @group Client
 * @group CaptchaAltcha
 * @group Verifier
 * @group LocalAltchaVerifierTest
 * Add your own group annotations below this line
 */
class LocalAltchaVerifierTest extends Unit
{
    protected const string GLOSSARY_KEY_INVALID = 'captcha.error.invalid';

    protected const string GLOSSARY_KEY_EXPIRED = 'captcha.error.expired';

    protected const string GLOSSARY_KEY_UNAVAILABLE = 'captcha.error.unavailable';

    protected const string FORGED_SIGNATURE = '0000000000000000000000000000000000000000000000000000000000000000';

    protected const int MAXIMUM_PAYLOAD_LENGTH = 4096;

    protected const string PADDING_KEY = 'padding';

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

    /**
     * A challenge with a key signature is verified by the HMAC of the submitted derived key, which is the proof of work.
     */
    public function testRejectsPayloadWithTamperedDerivedKey(): void
    {
        // Arrange
        $challenge = $this->createChallenge(CaptchaAltchaClientTester::CONTEXT_CUSTOMER);
        $solution = $this->tester->solveChallenge($challenge);
        $payload = $this->tester->createPayload($challenge, new Solution($solution->counter, strrev($solution->derivedKey)));

        // Act
        $captchaVerificationResponseTransfer = $this->createAltchaVerifier()->verify(
            $this->createVerificationRequest($payload, CaptchaAltchaClientTester::CONTEXT_CUSTOMER),
        );

        // Assert
        $this->assertRejectedWith(static::GLOSSARY_KEY_INVALID, $captchaVerificationResponseTransfer);
    }

    /**
     * Without a key signature the library derives the key from the submitted counter, so the counter has to match.
     */
    public function testRejectsPayloadWithTamperedCounterForChallengeWithoutKeySignature(): void
    {
        // Arrange
        $challenge = $this->tester->createSignedChallenge(
            [CaptchaAltchaClientTester::CHALLENGE_DATA_KEY_CONTEXT => CaptchaAltchaClientTester::CONTEXT_CUSTOMER],
            time() + CaptchaAltchaClientTester::CHALLENGE_TTL,
        );
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

    /**
     * The verifier itself does not remember spent challenges: it hands the signature to the Captcha client, which
     * accepts each replay key once for as long as the challenge could be submitted.
     */
    public function testReturnsChallengeSignatureAndExpiryAsReplayKey(): void
    {
        // Arrange
        $challenge = $this->createChallenge(CaptchaAltchaClientTester::CONTEXT_CUSTOMER);
        $payload = $this->tester->createPayload($challenge, $this->tester->solveChallenge($challenge));

        // Act
        $captchaVerificationResponseTransfer = $this->createAltchaVerifier()->verify(
            $this->createVerificationRequest($payload, CaptchaAltchaClientTester::CONTEXT_CUSTOMER),
        );

        // Assert
        $this->assertTrue($captchaVerificationResponseTransfer->getIsValid());
        $this->assertSame($challenge->signature, $captchaVerificationResponseTransfer->getReplayKey());
        $this->assertSame((int)$challenge->parameters->expiresAt, $captchaVerificationResponseTransfer->getReplayExpiresAt());
    }

    /**
     * Only an accepted payload may be spent: otherwise anyone could burn a victim's solved payload by submitting it to
     * the wrong form first.
     */
    public function testRejectedPayloadCarriesNoReplayKey(): void
    {
        // Arrange
        $payload = $this->tester->haveSolvedPayload(CaptchaAltchaClientTester::CONTEXT_CUSTOMER);

        // Act
        $captchaVerificationResponseTransfer = $this->createAltchaVerifier()->verify(
            $this->createVerificationRequest($payload, CaptchaAltchaClientTester::CONTEXT_USER),
        );

        // Assert
        $this->assertFalse($captchaVerificationResponseTransfer->getIsValid());
        $this->assertNull($captchaVerificationResponseTransfer->getReplayKey());
    }

    /**
     * The library ignores unknown top-level keys, so the padded payload would be accepted if its length were not checked.
     */
    public function testRejectsOtherwiseValidPayloadLongerThanTheMaximum(): void
    {
        // Arrange
        $payloadData = json_decode(base64_decode($this->tester->haveSolvedPayload(CaptchaAltchaClientTester::CONTEXT_CUSTOMER)), true);
        $payload = base64_encode(json_encode($payloadData + [static::PADDING_KEY => str_repeat('a', static::MAXIMUM_PAYLOAD_LENGTH)]));

        // Act
        $captchaVerificationResponseTransfer = $this->createAltchaVerifier()->verify(
            $this->createVerificationRequest($payload, CaptchaAltchaClientTester::CONTEXT_CUSTOMER),
        );

        // Assert
        $this->assertRejectedWith(static::GLOSSARY_KEY_INVALID, $captchaVerificationResponseTransfer);
    }

    /**
     * The library treats a challenge without expiry as valid forever; the challenge creator never produces one.
     */
    public function testRejectsSignedChallengeWithoutExpiry(): void
    {
        // Arrange
        $payload = $this->tester->haveSolvedPayloadForSignedChallenge(
            [CaptchaAltchaClientTester::CHALLENGE_DATA_KEY_CONTEXT => CaptchaAltchaClientTester::CONTEXT_CUSTOMER],
            0,
        );

        // Act
        $captchaVerificationResponseTransfer = $this->createAltchaVerifier()->verify(
            $this->createVerificationRequest($payload, CaptchaAltchaClientTester::CONTEXT_CUSTOMER),
        );

        // Assert
        $this->assertRejectedWith(static::GLOSSARY_KEY_INVALID, $captchaVerificationResponseTransfer);
    }

    /**
     * Challenges signed before the key signature was introduced carry none; the library derives the key once instead.
     */
    public function testAcceptsChallengeWithoutKeySignature(): void
    {
        // Arrange
        $payload = $this->tester->haveSolvedPayloadForSignedChallenge(
            [CaptchaAltchaClientTester::CHALLENGE_DATA_KEY_CONTEXT => CaptchaAltchaClientTester::CONTEXT_CUSTOMER],
            time() + CaptchaAltchaClientTester::CHALLENGE_TTL,
        );

        // Act
        $captchaVerificationResponseTransfer = $this->createAltchaVerifier()->verify(
            $this->createVerificationRequest($payload, CaptchaAltchaClientTester::CONTEXT_CUSTOMER),
        );

        // Assert
        $this->assertTrue($captchaVerificationResponseTransfer->getIsValid());
    }

    public function testAcceptsPayloadSolvedWithArgon2id(): void
    {
        // Arrange
        $this->tester->haveArgon2idConfiguration();
        $payload = $this->tester->haveSolvedPayload(CaptchaAltchaClientTester::CONTEXT_CUSTOMER);

        // Act
        $captchaVerificationResponseTransfer = $this->createAltchaVerifier()->verify(
            $this->createVerificationRequest($payload, CaptchaAltchaClientTester::CONTEXT_CUSTOMER),
        );

        // Assert
        $this->assertTrue($captchaVerificationResponseTransfer->getIsValid());
    }

    public function testFailsClosedAndLogsErrorWithTooShortHmacSecret(): void
    {
        // Arrange
        $payload = $this->tester->haveSolvedPayload(CaptchaAltchaClientTester::CONTEXT_CUSTOMER);
        $this->tester->haveAltchaConfiguration(CaptchaAltchaClientTester::TOO_SHORT_HMAC_SECRET);
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
        return $this->tester->createCaptchaAltchaFactory()->createLocalAltchaVerifier();
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
