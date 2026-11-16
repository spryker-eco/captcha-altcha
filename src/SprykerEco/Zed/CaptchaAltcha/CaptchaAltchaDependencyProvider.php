<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\CaptchaAltcha;

use Spryker\Zed\Configuration\Business\ConfigurationFacadeInterface;
use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;
use SprykerEco\Client\CaptchaAltcha\CaptchaAltchaClientInterface;

/**
 * @method \SprykerEco\Zed\CaptchaAltcha\CaptchaAltchaConfig getConfig()
 */
class CaptchaAltchaDependencyProvider extends AbstractBundleDependencyProvider
{
    public const string CLIENT_CAPTCHA_ALTCHA = 'CLIENT_CAPTCHA_ALTCHA';

    public const string FACADE_CONFIGURATION = 'FACADE_CONFIGURATION';

    public function provideBusinessLayerDependencies(Container $container): Container
    {
        $container = parent::provideBusinessLayerDependencies($container);
        $container = $this->addCaptchaAltchaClient($container);
        $container = $this->addConfigurationFacade($container);

        return $container;
    }

    protected function addCaptchaAltchaClient(Container $container): Container
    {
        $container->set(static::CLIENT_CAPTCHA_ALTCHA, function (Container $container): CaptchaAltchaClientInterface {
            return $container->getLocator()->captchaAltcha()->client();
        });

        return $container;
    }

    protected function addConfigurationFacade(Container $container): Container
    {
        $container->set(static::FACADE_CONFIGURATION, function (Container $container): ConfigurationFacadeInterface {
            return $container->getLocator()->configuration()->facade();
        });

        return $container;
    }
}
