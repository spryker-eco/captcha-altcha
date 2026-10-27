# CaptchaAltcha Module
[![Latest Stable Version](https://poser.pugx.org/spryker-eco/captcha-altcha/v/stable.svg)](https://packagist.org/packages/spryker-eco/captcha-altcha)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.3-8892BF.svg)](https://php.net/)

CaptchaAltcha provides an [ALTCHA](https://altcha.org) proof-of-work CAPTCHA provider for the Spryker `Captcha` module:
signed, context-bound challenges with single-use payloads, and the `altcha-widget` assets for the Storefront, the
Back Office and the Merchant Portal. No third-party service is called: challenges are created and verified by the
application itself.

## Installation

```bash
composer require spryker-eco/captcha-altcha
npm install altcha@~3.2.0
```

The widget is the npm package `altcha`. Add it to the project `package.json`: the module ships no `package.json` of
its own, so the project workspaces do not install it.

## Configuration

`config/Shared/config_default.php`:

```php
use SprykerEco\Shared\CaptchaAltcha\CaptchaAltchaConstants;

$config[CaptchaAltchaConstants::HMAC_SECRET] = getenv('SPRYKER_CAPTCHA_ALTCHA_HMAC_SECRET') ?: '';
$config[CaptchaAltchaConstants::COST] = 1000;          // PBKDF2 iterations; solving takes about 1.5 × cost² iterations
$config[CaptchaAltchaConstants::CHALLENGE_TTL] = 1200; // seconds
$config[CaptchaAltchaConstants::REDIS_PERSISTENT_CONNECTION] = true;
$config[CaptchaAltchaConstants::REDIS_SCHEME] = getenv('SPRYKER_KEY_VALUE_STORE_PROTOCOL') ?: 'tcp';
$config[CaptchaAltchaConstants::REDIS_HOST] = getenv('SPRYKER_KEY_VALUE_STORE_HOST');
$config[CaptchaAltchaConstants::REDIS_PORT] = getenv('SPRYKER_KEY_VALUE_STORE_PORT');
$config[CaptchaAltchaConstants::REDIS_USER] = getenv('SPRYKER_KEY_VALUE_USERNAME');
$config[CaptchaAltchaConstants::REDIS_PASSWORD] = getenv('SPRYKER_KEY_VALUE_PASSWORD');
$config[CaptchaAltchaConstants::REDIS_DATABASE] = 7;
```

Every environment that serves logins needs its own random secret in `SPRYKER_CAPTCHA_ALTCHA_HMAC_SECRET` (for example
`openssl rand -hex 32`). Without it the provider fails closed: no challenge is created, and a login that requires a
challenge is rejected.

The replay store keeps every solved challenge in Redis (`captcha:altcha:spent:*`, with a TTL) and accepts a payload once.

## Wiring

```php
// src/Pyz/Client/Captcha/CaptchaDependencyProvider.php
namespace Pyz\Client\Captcha;

use Spryker\Client\Captcha\CaptchaDependencyProvider as SprykerCaptchaDependencyProvider;
use Spryker\Client\CaptchaExtension\Dependency\Plugin\CaptchaProviderPluginInterface;
use SprykerEco\Client\CaptchaAltcha\Plugin\Captcha\AltchaCaptchaProviderPlugin;

class CaptchaDependencyProvider extends SprykerCaptchaDependencyProvider
{
    protected function getCaptchaProviderPlugin(): ?CaptchaProviderPluginInterface
    {
        return new AltchaCaptchaProviderPlugin();
    }
}
```

Then build the frontends:

```bash
console transfer:generate
npm run yves      # Storefront: the lazy `captcha-altcha` component loads the widget
npm run zed       # Back Office: js/spryker-zed-captcha-altcha.js
npm run mp:build  # Merchant Portal: Presentation/Components/entry.ts
```

## Deployment requirements

- **Secure context.** The widget uses Web Crypto, which browsers expose only on HTTPS (and `localhost`).
- **Content-Security-Policy.** The widget solves in web workers created from `blob:` URLs; a strict CSP must allow
  `worker-src blob:`.

## Documentation

[Spryker Documentation](https://docs.spryker.com)
