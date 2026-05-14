import './bootstrap';

import Alpine from 'alpinejs';

import { Passkeys } from '@laravel/passkeys';

window.Alpine = Alpine;
window.Passkeys = Passkeys;

// Dispatch the alpine:init event so that Blade-defined components can register themselves
document.dispatchEvent(new CustomEvent('alpine:init'));

Alpine.start();
