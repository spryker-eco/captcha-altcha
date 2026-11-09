// spy/merchant-portal:single-entry-marker
import 'altcha';

interface AltchaWidgetElement extends HTMLElement {
    getState(): string;
    verify(): Promise<unknown>;
}

const ON_FOCUS_WIDGET_SELECTOR = 'altcha-widget[auto="onfocus"]';

const ON_SUBMIT_WIDGET_SELECTOR = 'altcha-widget[auto="onsubmit"]';

const UNVERIFIED_STATE = 'unverified';

const VERIFIED_STATE = 'verified';

const VERIFYING_STATE = 'verifying';

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

document.addEventListener(
    'submit',
    (event: SubmitEvent): void => {
        const form = event.target instanceof HTMLFormElement ? event.target : null;
        const widget = form?.querySelector<AltchaWidgetElement>(ON_SUBMIT_WIDGET_SELECTOR);

        if (!form || !widget || widget.getState() === VERIFIED_STATE) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        if (widget.getState() === VERIFYING_STATE) {
            return;
        }

        const submitter = event.submitter;

        void widget.verify().then((verifyResult: unknown): void => {
            if (verifyResult) {
                form.requestSubmit(submitter);
            }
        });
    },
    true,
);
