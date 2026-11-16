/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

'use strict';

import 'altcha';

$altcha.algorithms.set('ARGON2ID', () => new Worker(new URL('altcha/workers/argon2id', import.meta.url)));
