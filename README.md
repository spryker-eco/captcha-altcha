# CaptchaAltcha Module
[![Latest Stable Version](https://poser.pugx.org/spryker-eco/captcha-altcha/v/stable.svg)](https://packagist.org/packages/spryker-eco/captcha-altcha)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.3-8892BF.svg)](https://php.net/)

CaptchaAltcha provides an [ALTCHA](https://altcha.org) proof-of-work CAPTCHA provider for the Spryker `Captcha` module:
signed, context-bound Argon2id challenges, and the `altcha-widget` assets for the Storefront, the Back Office and the
Merchant Portal. No third-party service is called: challenges are created and verified by the application itself.

## Installation

```bash
composer require spryker-eco/captcha-altcha
```

The module needs the `sodium` PHP extension for Argon2id.

The widget is the npm package `altcha`, declared in the module's own `package.json`. Add the module to the npm
workspaces of the project `package.json`, so `npm install` installs the widget at the version the module was tested with:

```json
"workspaces": [
    "vendor/spryker-eco/captcha-altcha"
]
```

A project that does not use workspaces adds the dependency to its root `package.json` instead:
`npm install altcha@~3.2.0`.

## Configuration

`config/Shared/config_default.php`:

```php
use Spryker\Shared\Captcha\CaptchaConstants;
use SprykerEco\Client\CaptchaAltcha\CaptchaAltchaConfig;
use SprykerEco\Shared\CaptchaAltcha\CaptchaAltchaConstants;

$config[CaptchaConstants::IS_CAPTCHA_ENABLED] = true;

// The Captcha module accepts a solved challenge once; it keeps the spent challenges in Redis.
$config[CaptchaConstants::REDIS_PERSISTENT_CONNECTION] = true;
$config[CaptchaConstants::REDIS_SCHEME] = getenv('SPRYKER_KEY_VALUE_STORE_PROTOCOL') ?: 'tcp';
$config[CaptchaConstants::REDIS_HOST] = getenv('SPRYKER_KEY_VALUE_STORE_HOST');
$config[CaptchaConstants::REDIS_PORT] = getenv('SPRYKER_KEY_VALUE_STORE_PORT');
$config[CaptchaConstants::REDIS_USER] = getenv('SPRYKER_KEY_VALUE_USERNAME');
$config[CaptchaConstants::REDIS_PASSWORD] = getenv('SPRYKER_KEY_VALUE_PASSWORD');
$config[CaptchaConstants::REDIS_DATABASE] = 7;

$config[CaptchaAltchaConstants::HMAC_SECRET] = getenv('SPRYKER_CAPTCHA_ALTCHA_HMAC_SECRET') ?: '';
$config[CaptchaAltchaConstants::KEY_DERIVATION_ALGORITHM] = CaptchaAltchaConfig::KEY_DERIVATION_ALGORITHM_ARGON2ID;
$config[CaptchaAltchaConstants::COST] = 2;               // Argon2id iterations per key derivation
$config[CaptchaAltchaConstants::MEMORY_COST] = 16384;    // KiB per key derivation (16 MiB)
$config[CaptchaAltchaConstants::MINIMUM_COUNTER] = 20;   // the browser derives 20 to 40 keys
$config[CaptchaAltchaConstants::CHALLENGE_TTL] = 300;    // seconds
```

| Setting | Default | Meaning |
|---|---|---|
| `HMAC_SECRET` | none | Signs the challenges. Required, at least 32 bytes. |
| `KEY_DERIVATION_ALGORITHM` | `ARGON2ID` | `ARGON2ID` (memory-hard, resists GPU solvers) or `PBKDF2/SHA-256`. |
| `COST` | 2 (Argon2id), 1000 (PBKDF2) | Iterations of one key derivation. |
| `MEMORY_COST` | 16384 | Memory of one Argon2id key derivation in KiB. |
| `MINIMUM_COUNTER` | 20 (Argon2id), 1000 (PBKDF2) | The secret counter is drawn from `[minimum, 2 × minimum]`; the browser derives up to that many keys. |
| `CHALLENGE_TTL` | 300 | Lifetime of a challenge in seconds. A challenge without an expiry is rejected. |

Every environment that serves logins needs its own random secret of at least 32 bytes in
`SPRYKER_CAPTCHA_ALTCHA_HMAC_SECRET` (for example `openssl rand -hex 32`). Without it, or with a shorter one, the
provider fails closed: no challenge is created, and a login that requires a challenge is rejected.

The server derives one key when it creates a challenge and none when it verifies one: a key signature (an HMAC derived
from the secret) lets the verifier check the solution without repeating the work. With the defaults, creating a
challenge costs about 20 ms and 16 MiB of memory on the server.

Payloads longer than 4096 bytes are rejected before they are decoded. A valid payload returns its signature as the
replay key; the `Captcha` module stores it in Redis (`captcha:spent:*`, until the challenge expires) and accepts the
payload once.

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

The widget ships the PBKDF2 and SHA workers only. Each entry point registers the Argon2id worker
(`altcha/workers/argon2id`), and the build emits it as a separate chunk. The Merchant Portal build needs webpack worker
parsing enabled (`module.parser.javascript.worker`), which `spryker/zed-ui` sets in its webpack configuration.

## Deployment requirements

- **Secure context.** The widget uses Web Crypto, which browsers expose only on HTTPS (and `localhost`).
- **Same-origin assets.** Browsers start a worker only from a script of the page's origin: serve the frontend assets
  from the application host, not from a CDN on another origin.
- **Content-Security-Policy.** The Argon2id worker compiles WebAssembly. A CSP that sets `script-src` must allow
  `'wasm-unsafe-eval'`, and one that sets `worker-src` must allow `'self'`. With `PBKDF2/SHA-256`, the widget starts its
  workers from `blob:` URLs instead, so `worker-src` must allow `blob:`.

## Documentation

[Spryker Documentation](https://docs.spryker.com)
