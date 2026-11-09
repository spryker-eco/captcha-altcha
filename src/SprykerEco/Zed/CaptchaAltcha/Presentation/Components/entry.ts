// spy/merchant-portal:single-entry-marker
import 'altcha';

interface AltchaWidgetElement extends HTMLElement {
    getState(): string;
    verify(): Promise<unknown>;
}

const ON_FOCUS_WIDGET_SELECTOR = 'altcha-widget[auto="onfocus"]';

const UNVERIFIED_STATE = 'unverified';

$altcha.algorithms.set('ARGON2ID', () => new Worker(new URL('altcha/workers/argon2id', import.meta.url)));

document.addEventListener(
    'focusin',
    (event: FocusEvent): void => {
        const form = event.target instanceof Element ? event.target.closest('form') : null;
        const widget = form?.querySelector<AltchaWidgetElement>(ON_FOCUS_WIDGET_SELECTOR);

        if (widget && widget.getState() === UNVERIFIED_STATE) {
            void widget.verify();
        }
    },
    true,
);
