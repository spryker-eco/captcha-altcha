// spy/merchant-portal:single-entry-marker
import 'altcha';

interface AltchaWidgetElement extends HTMLElement {
    getState(): string;
    verify(): Promise<unknown>;
}

const ON_FOCUS_WIDGET_SELECTOR = 'altcha-widget[auto="onfocus"]';

const ON_SUBMIT_WIDGET_SELECTOR = 'altcha-widget[auto="onsubmit"]';

const UNVERIFIED_STATE = 'unverified';

const ERROR_STATE = 'error';

const EXPIRED_STATE = 'expired';

const VERIFIED_STATE = 'verified';

const VERIFYING_STATE = 'verifying';

const STARTABLE_ON_FOCUS_STATES = [UNVERIFIED_STATE, ERROR_STATE, EXPIRED_STATE];

const LOG_MESSAGE_FOCUS_VERIFICATION_FAILED = 'The ALTCHA widget could not verify the challenge when the form got focus.';

const LOG_MESSAGE_SUBMIT_VERIFICATION_FAILED = 'The ALTCHA widget could not verify the challenge or submit the form.';

$altcha.algorithms.set('ARGON2ID', () => new Worker(new URL('altcha/workers/argon2id', import.meta.url)));

document.addEventListener(
    'focusin',
    (event: FocusEvent): void => {
        const form = event.target instanceof Element ? event.target.closest('form') : null;
        const widget = form?.querySelector<AltchaWidgetElement>(ON_FOCUS_WIDGET_SELECTOR);

        if (widget && STARTABLE_ON_FOCUS_STATES.includes(widget.getState())) {
            widget.verify().catch((error: unknown): void => {
                console.error(LOG_MESSAGE_FOCUS_VERIFICATION_FAILED, error);
            });
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

        widget
            .verify()
            .then((verifyResult: unknown): void => {
                if (verifyResult) {
                    form.requestSubmit(submitter);
                }
            })
            .catch((error: unknown): void => {
                console.error(LOG_MESSAGE_SUBMIT_VERIFICATION_FAILED, error);
            });
    },
    true,
);
