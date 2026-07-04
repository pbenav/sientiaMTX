import './bootstrap';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

import { Passkeys } from '@laravel/passkeys';

window.Alpine = Alpine;
Alpine.plugin(collapse);
window.Passkeys = Passkeys;

// Dispatch the alpine:init event so that Blade-defined components can register themselves
document.dispatchEvent(new CustomEvent('alpine:init'));

Alpine.start();
