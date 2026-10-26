<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Client\CaptchaAltcha\ReplayStore;

interface AltchaReplayStoreInterface
{
    /**
     * Returns `false` when the challenge was already marked as spent, i.e. its payload is replayed.
     */
    public function markChallengeAsSpent(string $challengeSignature, int $ttl): bool;
}
