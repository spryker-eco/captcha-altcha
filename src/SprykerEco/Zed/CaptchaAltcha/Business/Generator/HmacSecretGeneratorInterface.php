<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\CaptchaAltcha\Business\Generator;

use Generated\Shared\Transfer\ConfigurationValueCollectionResponseTransfer;

interface HmacSecretGeneratorInterface
{
    /**
     * `savedCount` is `1` when a secret was generated and `0` when a stored secret was kept.
     */
    public function generateMissingHmacSecret(): ConfigurationValueCollectionResponseTransfer;
}
