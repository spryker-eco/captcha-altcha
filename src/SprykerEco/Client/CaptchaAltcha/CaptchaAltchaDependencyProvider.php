<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Client\CaptchaAltcha;

use Spryker\Client\Kernel\AbstractDependencyProvider;
use Spryker\Client\Kernel\Container;
use Spryker\Client\Redis\RedisClientInterface;

/**
 * @method \SprykerEco\Client\CaptchaAltcha\CaptchaAltchaConfig getConfig()
 */
class CaptchaAltchaDependencyProvider extends AbstractDependencyProvider
{
    public const string CLIENT_REDIS = 'CLIENT_REDIS';

    public function provideServiceLayerDependencies(Container $container): Container
    {
        $container = parent::provideServiceLayerDependencies($container);
        $container = $this->addRedisClient($container);

        return $container;
    }

    protected function addRedisClient(Container $container): Container
    {
        $container->set(static::CLIENT_REDIS, function (Container $container): RedisClientInterface {
            return $container->getLocator()->redis()->client();
        });

        return $container;
    }
}
