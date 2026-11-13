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
`npm install altcha@~3.5.0`.

## Configuration

`config/Shared/config_default.php`:

```php
use Spryker\Shared\Captcha\CaptchaConstants;
use SprykerEco\Client\CaptchaAltcha\CaptchaAltchaConfig;
use SprykerEco\Shared\CaptchaAltcha\CaptchaAltchaConstants;

// The Captcha module accepts a solved challenge once; it keeps the spent challenges in Redis.
$config[CaptchaConstants::REDIS_PERSISTENT_CONNECTION] = true;
$config[CaptchaConstants::REDIS_SCHEME] = getenv('SPRYKER_KEY_VALUE_STORE_PROTOCOL') ?: 'tcp';
$config[CaptchaConstants::REDIS_HOST] = getenv('SPRYKER_KEY_VALUE_STORE_HOST');
$config[CaptchaConstants::REDIS_PORT] = getenv('SPRYKER_KEY_VALUE_STORE_PORT');
$config[CaptchaConstants::REDIS_USER] = getenv('SPRYKER_KEY_VALUE_USERNAME');
$config[CaptchaConstants::REDIS_PASSWORD] = getenv('SPRYKER_KEY_VALUE_PASSWORD');
$config[CaptchaConstants::REDIS_DATABASE] = 7;

$config[CaptchaAltchaConstants::KEY_DERIVATION_ALGORITHM] = CaptchaAltchaConfig::KEY_DERIVATION_ALGORITHM_ARGON2ID;
$config[CaptchaAltchaConstants::COST] = 2;               // Argon2id iterations per key derivation
$config[CaptchaAltchaConstants::MEMORY_COST] = 16384;    // KiB per key derivation (16 MiB)
$config[CaptchaAltchaConstants::MINIMUM_COUNTER] = 20;   // the browser derives 20 to 40 keys
$config[CaptchaAltchaConstants::CHALLENGE_TTL] = 300;    // seconds
```

The environment settings above are optional and show their defaults. CAPTCHA is off by default: once the secret below
is set, turn on **Configuration → Security → CAPTCHA → Enable CAPTCHA** in the Back Office.

| Setting | Default | Meaning |
|---|---|---|
| `KEY_DERIVATION_ALGORITHM` | `ARGON2ID` | `ARGON2ID` (memory-hard, resists GPU solvers) or `PBKDF2/SHA-256`. |
| `COST` | 2 (Argon2id), 1000 (PBKDF2) | Iterations of one key derivation. |
| `MEMORY_COST` | 16384 | Memory of one Argon2id key derivation in KiB. |
| `MINIMUM_COUNTER` | 20 (Argon2id), 1000 (PBKDF2) | The secret counter is drawn from `[minimum, 2 × minimum]`; the browser derives up to that many keys. |
| `CHALLENGE_TTL` | 300 | A challenge expires this many seconds after it is created. A challenge without an expiry is rejected. |

### HMAC secret

The secret that signs the challenges is a secret setting of the Configuration module, on **Configuration →
Integrations → ALTCHA** in the Back Office:

| Setting | Key | Notes |
|---|---|---|
| HMAC Secret | `integrations:altcha:credentials:hmac_secret` | Required, at least 32 characters. Stored encrypted, never published to the key-value store. |

`console configuration:sync` registers the setting; the install recipes run it. Every environment gets its own random
secret from the `captcha-altcha:hmac-secret:generate` console command:

- When no secret is stored, it generates 32 random bytes, stores them hex-encoded (64 characters) at the global scope
  and prints that the secret was generated.
- When the stored secret is shorter than 32 characters, which the provider rejects, it replaces it the same way.
- When a secret of 32 characters or more is stored, it keeps it and prints that it was kept, so it is safe to run on
  every deployment.
- The key of the setting and the minimum length come from `SprykerEco\Shared\CaptchaAltcha\CaptchaAltchaConfig`, which
  the generator and the provider share.
- It never prints or logs the secret, and it exits with an error code and the reason when the secret cannot be saved.

Register the command in `Pyz\Zed\Console\ConsoleDependencyProvider::getConsoleCommands()`:

```php
use SprykerEco\Zed\CaptchaAltcha\Communication\Console\CaptchaAltchaHmacSecretGenerateConsole;

new CaptchaAltchaHmacSecretGenerateConsole(),
```

Run it in the install recipe after `configuration:sync` and the database initialization (`setup:init-db`), for example
next to the step that initializes the database:

```yaml
captcha-altcha-hmac-secret-generate:
    command: 'vendor/bin/console captcha-altcha:hmac-secret:generate'
```

An administrator can rotate the secret on **Configuration → Integrations → ALTCHA** in the Back Office. Challenges
signed with the previous secret are rejected afterwards, so a visitor who had a login form open solves a new challenge.

Without a secret, or with a shorter one, the provider fails closed and logs an error: no challenge is created, and a
login that requires a CAPTCHA is rejected. With the CAPTCHA mode of the `Captcha` module set to `always`, this blocks
every login form, the Back Office login included, so the secret cannot be set there. The install step above prevents
this. To recover an environment without a valid secret, run `console captcha-altcha:hmac-secret:generate`, or turn CAPTCHA
off for the time being without the Back Office:
`CAPTCHA_ENABLED=false console configuration:import-environment security:captcha:login:is_enabled=CAPTCHA_ENABLED`.

### Where challenges are created and verified

The secret can be read only in an application that registers
`Spryker\Zed\Configuration\Communication\Plugin\Application\ConfigurationApplicationPlugin`:

- The **Back Office**, the **Backend Gateway**, the **Merchant Portal** and the Zed console create and verify challenges
  in-process. The Merchant Portal needs the plugin in
  `Pyz\Zed\MerchantPortalApplication\MerchantPortalApplicationDependencyProvider::getMerchantPortalApplicationPlugins()`;
  the Back Office, the Backend Gateway and the console register it already. Without it, a Zed application goes through
  the Zed gateway and logs a warning for every challenge and payload.
- The **Storefront** creates and verifies challenges through the Zed gateway
  (`/captcha-altcha/gateway/create-captcha-challenge`, `/captcha-altcha/gateway/verify-captcha-payload`), served by
  `CaptchaAltchaFacade`. A challenge costs one Zed request, which the Backend Gateway signs; with the challenge URL
  widget below, that request happens when the user ticks the checkbox, once per tick, not while the page renders.

A challenge is bound to the context of its login form, so a solution is accepted only by the same form:
`storefront_customer_login`, `storefront_agent_login`, `backoffice_user_login`, `merchant_portal_user_login` and
`merchant_portal_agent_login`.

The server derives one key when it creates a challenge and none when it verifies one: a key signature (an HMAC derived
from the secret) lets the verifier check the solution without repeating the work. With the defaults, creating a
challenge costs about 9 ms of CPU time and 16 MiB of memory on the server.

Payloads longer than 4096 bytes are rejected before they are decoded. A valid payload returns its signature as the
replay key; the `Captcha` module stores it in Redis (`captcha:spent:*`, until the challenge expires) and accepts the
payload once.

### Challenge URL widget

`AltchaCaptchaProviderPlugin` implements `Spryker\Client\CaptchaExtension\Dependency\Plugin\CaptchaChallengeUrlProviderPluginInterface`
next to `CaptchaProviderPluginInterface`. When the `Captcha` module passes a challenge URL (the rate-limited challenge
endpoint of the application that renders the login form), the plugin returns the URL widget and creates no challenge
while the page renders:

```html
<altcha-widget challenge="<challenge URL>" auto="off"></altcha-widget>
```

- When the user ticks the checkbox, the widget fetches its challenge from the challenge URL and solves it (about 1 s),
  and the checkbox shows as ticked; the user then submits the form. That is one challenge per tick; a page render and
  typing create none.
- When the challenge endpoint answers 429 or 503, the widget shows "Verification failed. Try again later." and the
  checkbox stays unticked.
- The checkbox is a required field. A form that uses the browser's form validation, like the Back Office login form,
  asks the user to tick it and sends nothing. The Storefront login forms (`novalidate`) and the Merchant Portal login
  forms (submitted by Angular) send the form, and the `Captcha` module rejects it with "Please complete the security
  check."
- `auto` comes from `SprykerEco\Client\CaptchaAltcha\CaptchaAltchaConfig::getChallengeUrlWidgetAutoMode()`: `off` by
  default; the widget also supports `onfocus` (when the user enters the form), `onsubmit` (when the user submits the
  form, which the widget submits once the challenge is solved) and `onload`. With these values the widget ticks the
  checkbox itself.
- For each request to the challenge endpoint, the `Captcha` client calls the plugin's `createChallengeData()`, which
  creates the challenge and returns its JSON document. On the Storefront it creates the challenge through the Zed
  gateway, because the secret is readable only in Zed.
- A challenge expires `CHALLENGE_TTL` seconds after it is created.
- The Merchant Portal moves the form fields into its Angular form after the widget mounted, so the widget's own form
  listeners are never attached. For the `onsubmit` and `onfocus` values, the entry script
  `Zed/CaptchaAltcha/Presentation/Components/entry.ts` therefore binds the start of the widget to the document: with
  `onsubmit` it holds a submit until the widget is verified, then submits again; a submit while the widget is verifying
  is held; after an error or expiry the next submit verifies again. With `onfocus` it starts the widget when the focus
  enters the form while the widget is unverified, failed or expired. A failed verification or submit is logged to the
  browser console. With the default `off`, the user starts the widget with its checkbox, which needs no form listener.
- A login form without a challenge URL renders the widget with a challenge created while the page renders.

The challenge endpoints, their rate limit and their wiring (the Storefront route provider plugin, anonymous access to
the Back Office and Merchant Portal endpoints) belong to the `Captcha` module.

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
