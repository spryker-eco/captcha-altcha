import Component from 'ShopUi/models/component';
import { error as logError } from 'ShopUi/app/logger';

const ARGON2ID_ALGORITHM = 'ARGON2ID';

/**
 * Loads the ALTCHA widget library only on pages that render a CAPTCHA, so the `altcha-widget` it wraps gets defined.
 * The widget bundles the PBKDF2 and SHA workers only; the Argon2id worker is registered here.
 */
export default class CaptchaAltcha extends Component {
    protected readyCallback(): void {}

    protected async init(): Promise<void> {
        try {
            await import(/* webpackChunkName: "altcha" */ 'altcha');
            $altcha.algorithms.set(
                ARGON2ID_ALGORITHM,
                () => new Worker(new URL('altcha/workers/argon2id', import.meta.url)),
            );
        } catch (importError) {
            logError(`${this.name}: the ALTCHA widget could not be loaded.`, importError);
        }
    }
}
