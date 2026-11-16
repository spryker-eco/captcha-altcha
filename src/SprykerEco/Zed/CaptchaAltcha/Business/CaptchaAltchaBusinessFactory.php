<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\CaptchaAltcha\Business;

use Spryker\Zed\Configuration\Business\ConfigurationFacadeInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use SprykerEco\Client\CaptchaAltcha\CaptchaAltchaClientInterface;
use SprykerEco\Zed\CaptchaAltcha\Business\Generator\HmacSecretGenerator;
use SprykerEco\Zed\CaptchaAltcha\Business\Generator\HmacSecretGeneratorInterface;
use SprykerEco\Zed\CaptchaAltcha\CaptchaAltchaDependencyProvider;

/**
 * @method \SprykerEco\Zed\CaptchaAltcha\CaptchaAltchaConfig getConfig()
 */
class CaptchaAltchaBusinessFactory extends AbstractBusinessFactory
{
    public function createHmacSecretGenerator(): HmacSecretGeneratorInterface
    {
        return new HmacSecretGenerator($this->getConfigurationFacade(), $this->getConfig());
    }

    public function getCaptchaAltchaClient(): CaptchaAltchaClientInterface
    {
        return $this->getProvidedDependency(CaptchaAltchaDependencyProvider::CLIENT_CAPTCHA_ALTCHA);
    }

    public function getConfigurationFacade(): ConfigurationFacadeInterface
    {
        return $this->getProvidedDependency(CaptchaAltchaDependencyProvider::FACADE_CONFIGURATION);
    }
}
