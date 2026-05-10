import './bootstrap';

import Alpine from 'alpinejs';

import { Passkeys } from '@laravel/passkeys';

window.Alpine = Alpine;
window.Passkeys = Passkeys;

Alpine.start();
