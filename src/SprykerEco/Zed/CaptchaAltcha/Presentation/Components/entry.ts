// spy/merchant-portal:single-entry-marker
import 'altcha';

// The widget bundles the PBKDF2 and SHA workers only.
$altcha.algorithms.set('ARGON2ID', () => new Worker(new URL('altcha/workers/argon2id', import.meta.url)));
