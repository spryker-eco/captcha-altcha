import Component from 'ShopUi/models/component';
import { error as logError } from 'ShopUi/app/logger';

/**
 * Loads the ALTCHA widget library only on pages that render a CAPTCHA, so the `altcha-widget` it wraps gets defined.
 */
export default class CaptchaAltcha extends Component {
    protected readyCallback(): void {}

    protected async init(): Promise<void> {
        try {
            await import(/* webpackChunkName: "altcha" */ 'altcha');
        } catch (importError) {
            logError(`${this.name}: the ALTCHA widget could not be loaded.`, importError);
        }
    }
}
