<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Client\CaptchaAltcha\Mapper;

use AltchaOrg\Altcha\Challenge;
use Generated\Shared\Transfer\CaptchaWidgetTransfer;

class AltchaWidgetMapper implements AltchaWidgetMapperInterface
{
    protected const string TAG_NAME = 'altcha-widget';

    /**
     * The `altcha` widget 3.x parses a `challenge` attribute value that starts with `{` as an inline challenge instead
     * of fetching one from a URL.
     */
    protected const string ATTRIBUTE_CHALLENGE = 'challenge';

    protected const string NAME_ATTRIBUTE = 'name';

    protected const string LOADER_TAG_NAME = 'captcha-altcha';

    protected const string SCRIPT_ASSET_PATH = 'js/spryker-zed-captcha-altcha.js';

    public function mapChallengeToCaptchaWidgetTransfer(Challenge $challenge, CaptchaWidgetTransfer $captchaWidgetTransfer): CaptchaWidgetTransfer
    {
        return $captchaWidgetTransfer
            ->setTagName(static::TAG_NAME)
            ->setAttributes([static::ATTRIBUTE_CHALLENGE => $challenge->toJson()])
            ->setNameAttribute(static::NAME_ATTRIBUTE)
            ->setLoaderTagName(static::LOADER_TAG_NAME)
            ->setScriptAssetPaths([static::SCRIPT_ASSET_PATH]);
    }
}
