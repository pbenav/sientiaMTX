<section class="space-y-6" x-data="{
    mfaEnabled: {{ $user->two_factor_confirmed_at ? 'true' : 'false' }},
    showQr: false,
    secret: '',
    qrUri: '',
    code: '',
    password: '',
    errorMsg: '',
    successMsg: '',
    qrImageUrl: '',

    initEnable() {
        this.errorMsg = '';
        this.successMsg = '';
        fetch('{{ route('profile.two-factor.enable') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                this.secret = data.secret;
                this.qrUri = data.qr_code_uri;
                this.qrImageUrl = 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=' + encodeURIComponent(this.qrUri);
                this.showQr = true;
            } else {
                this.errorMsg = 'Error al iniciar la autenticación de doble factor.';
            }
        })
        .catch(() => {
            this.errorMsg = 'Error de red. Inténtelo de nuevo.';
        });
    },

    confirmEnable() {
        this.errorMsg = '';
        fetch('{{ route('profile.two-factor.confirm') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ code: this.code })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                this.mfaEnabled = true;
                this.showQr = false;
                this.code = '';
                this.successMsg = '¡Autenticación en dos pasos activada con éxito!';
            } else {
                this.errorMsg = data.message || 'Código incorrecto. Inténtelo de nuevo.';
            }
        })
        .catch(() => {
            this.errorMsg = 'Error al confirmar el código.';
        });
    },

    disableMfa() {
        this.errorMsg = '';
        this.successMsg = '';
        if (!confirm('¿Está seguro de que desea desactivar la autenticación en dos pasos? Su cuenta estará menos protegida.')) {
            return;
        }

        fetch('{{ route('profile.two-factor.disable') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ password: this.password })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                this.mfaEnabled = false;
                this.password = '';
                this.successMsg = 'La autenticación en dos pasos ha sido desactivada.';
            } else {
                this.errorMsg = data.message || 'Contraseña incorrecta.';
            }
        })
        .catch(() => {
            this.errorMsg = 'Error al desactivar la autenticación.';
        });
    }
}">
    <header>
        <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">
            🛡️ {{ __('Autenticación de Doble Factor (MFA / 2FA)') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Añada una capa adicional de seguridad a su cuenta siguiendo las directrices del Esquema Nacional de Seguridad (ENS).') }}
        </p>
    </header>

    <!-- Success Messages -->
    <div x-show="successMsg" class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 border border-green-200 dark:border-green-800" role="alert" style="display: none;">
        <span class="font-semibold">✔️ {{ __('Éxito:') }}</span> <span x-text="successMsg"></span>
    </div>

    <!-- Error Messages -->
    <div x-show="errorMsg" class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 border border-red-200 dark:border-red-800" role="alert" style="display: none;">
        <span class="font-semibold">⚠️ {{ __('Error:') }}</span> <span x-text="errorMsg"></span>
    </div>

    <!-- MFA Status: Active -->
    <div x-show="mfaEnabled" class="space-y-4">
        <div class="flex items-center gap-3 p-4 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800 rounded-2xl">
            <span class="text-2xl">🔒</span>
            <div>
                <h3 class="font-bold text-emerald-800 dark:text-emerald-400 text-sm">
                    {{ __('Autenticación en dos pasos activada') }}
                </h3>
                <p class="text-xs text-emerald-700 dark:text-emerald-500">
                    {{ __('Su cuenta está protegida de accesos no autorizados mediante un token dinámico (TOTP).') }}
                </p>
            </div>
        </div>

        <div class="mt-4 max-w-xl">
            <x-input-label for="disable_password" :value="__('Para desactivar, introduzca su contraseña actual:')" />
            <div class="flex gap-4 mt-1">
                <x-text-input id="disable_password" type="password" class="block w-full" x-model="password" placeholder="Contraseña actual" />
                <button type="button" @click="disableMfa()" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Desactivar') }}
                </button>
            </div>
        </div>
    </div>

    <!-- MFA Status: Inactive -->
    <div x-show="!mfaEnabled && !showQr" class="space-y-4" style="display: none;">
        @if (!\App\Models\Setting::get('mfa_enabled', false))
            <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-800/30 border border-gray-200 dark:border-gray-800 rounded-2xl">
                <span class="text-2xl">⚙️</span>
                <div>
                    <h3 class="font-bold text-gray-500 dark:text-gray-400 text-sm">
                        {{ __('Doble factor inactivo y desactivado globalmente') }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('El administrador de la aplicación ha desactivado el uso global del Doble Factor (MFA/2FA). No es posible activarlo en este momento.') }}
                    </p>
                </div>
            </div>
        @else
            <div class="flex items-center gap-3 p-4 bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 rounded-2xl">
                <span class="text-2xl">🔓</span>
                <div>
                    <h3 class="font-bold text-amber-800 dark:text-amber-400 text-sm">
                        {{ __('Autenticación en dos pasos inactiva') }}
                    </h3>
                    <p class="text-xs text-amber-700 dark:text-amber-500">
                        {{ __('Le recomendamos encarecidamente activar la verificación en dos pasos para cumplir con los estándares ENS y blindar su acceso.') }}
                    </p>
                </div>
            </div>

            <x-primary-button type="button" @click="initEnable()">
                {{ __('Activar Verificación en Dos Pasos') }}
            </x-primary-button>
        @endif
    </div>

    <!-- MFA Flow: Scan QR & Confirm -->
    <div x-show="showQr" class="p-6 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl space-y-6" style="display: none;">
        <div class="text-sm font-bold text-gray-800 dark:text-gray-200">
            {{ __('Paso 1: Escanee este código QR con su aplicación de autenticación') }}
        </div>

        <div class="flex flex-col md:flex-row gap-6 items-center">
            <div class="p-2 bg-white rounded-xl shadow-sm border border-gray-100">
                <template x-if="qrImageUrl">
                    <img :src="qrImageUrl" alt="MFA QR Code" class="w-48 h-48" />
                </template>
            </div>

            <div class="space-y-2 text-xs text-gray-600 dark:text-gray-400">
                <p>
                    {{ __('¿No puede escanear el código QR? Introduzca esta clave manualmente en su aplicación:') }}
                </p>
                <div class="p-3 bg-gray-100 dark:bg-gray-800 rounded-lg font-mono text-center text-sm font-bold tracking-widest text-violet-600 dark:text-violet-400 select-all" x-text="secret"></div>
                <p>
                    {{ __('Compatible con Google Authenticator, Microsoft Authenticator, Authy, Bitwarden, etc.') }}
                </p>
            </div>
        </div>

        <hr class="border-gray-200 dark:border-gray-800" />

        <div class="space-y-3">
            <div class="text-sm font-bold text-gray-800 dark:text-gray-200">
                {{ __('Paso 2: Introduzca el código de 6 dígitos generado por su app para confirmar:') }}
            </div>

            <div class="flex gap-4 max-w-xs items-end">
                <div class="flex-1">
                    <x-text-input id="mfa_confirm_code" type="text" class="block w-full text-center font-mono text-lg tracking-widest" x-model="code" placeholder="000000" maxlength="6" />
                </div>
                <x-primary-button type="button" @click="confirmEnable()">
                    {{ __('Confirmar') }}
                </x-primary-button>
            </div>
        </div>

        <div class="flex justify-end mt-4">
            <button type="button" @click="showQr = false" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                {{ __('Cancelar') }}
            </button>
        </div>
    </div>
</section>
