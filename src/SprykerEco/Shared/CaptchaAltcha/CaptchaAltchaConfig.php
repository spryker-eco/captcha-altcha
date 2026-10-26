<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Shared\CaptchaAltcha;

use Spryker\Shared\Kernel\AbstractSharedConfig;

class CaptchaAltchaConfig extends AbstractSharedConfig
{
    /**
     * Specification:
     * - Glossary key of the message returned for a malformed, tampered, replayed or context-mismatching ALTCHA payload.
     *
     * @api
     *
     * @uses \Spryker\Shared\Captcha\CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_INVALID
     */
    public const string GLOSSARY_KEY_CAPTCHA_ERROR_INVALID = 'captcha.error.invalid';

    /**
     * Specification:
     * - Glossary key of the message returned for an ALTCHA payload whose challenge has expired.
     *
     * @api
     *
     * @uses \Spryker\Shared\Captcha\CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_EXPIRED
     */
    public const string GLOSSARY_KEY_CAPTCHA_ERROR_EXPIRED = 'captcha.error.expired';

    /**
     * Specification:
     * - Glossary key of the message returned when ALTCHA cannot create or verify challenges because the HMAC secret is not configured.
     *
     * @api
     *
     * @uses \Spryker\Shared\Captcha\CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_UNAVAILABLE
     */
    public const string GLOSSARY_KEY_CAPTCHA_ERROR_UNAVAILABLE = 'captcha.error.unavailable';
}
