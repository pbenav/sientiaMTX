<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <!-- Session Expired Warning -->
    @if (session('warning'))
        <div class="mb-4 font-medium text-sm text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg px-4 py-3">
            ⚠️ {{ session('warning') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required
                autofocus autocomplete="username webauthn" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required
                autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4 gap-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-500 hover:text-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500"
                    href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
    <div class="mt-6 flex items-center">
        <div class="flex-grow border-t border-gray-200 dark:border-gray-800"></div>
        <span class="mx-4 text-xs font-bold tracking-widest uppercase text-gray-400 dark:text-gray-600">{{ __('O continúa con') }}</span>
        <div class="flex-grow border-t border-gray-200 dark:border-gray-800"></div>
    </div>

    <div class="mt-6" x-data="passkeyLogin()" x-init="init()">
        <button type="button" 
                @click="loginWithPasskey()" 
                x-show="isSupported"
                x-cloak
                :disabled="loading"
                class="w-full flex items-center justify-center gap-3 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-200 border-2 border-gray-200 dark:border-gray-800 hover:border-emerald-400 dark:hover:border-emerald-500/50 px-5 py-3.5 rounded-xl font-black text-xs tracking-widest uppercase shadow-sm hover:shadow-md transition-all active:scale-[0.98]">
            
            <div class="p-1.5 bg-emerald-50 dark:bg-emerald-500/10 rounded-lg text-emerald-600 dark:text-emerald-400" x-show="!loading">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                </svg>
            </div>
            
            <svg x-show="loading" class="animate-spin h-5 w-5 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>

            <span x-text="loading ? 'Validando...' : 'Acceder con Passkey'"></span>
        </button>

        <script>
            function passkeyLogin() {
                return {
                    isSupported: false,
                    loading: false,
                    
                    init() {
                        // Check globally injected Passkeys instance from app.js
                        this.isSupported = window.Passkeys && window.Passkeys.isSupported();
                        
                        // If supported, enable Autofill automatically on page load!
                        if (this.isSupported) {
                            this.enableAutofill();
                        }
                    },

                    async enableAutofill() {
                        try {
                            // autofill returns a verification response object if successfully engaged
                            const response = await window.Passkeys.autofill();
                            if (response && response.redirect) {
                                window.location.href = response.redirect;
                            }
                        } catch (e) {
                            console.debug('Autofill interaction closed or unsupported.');
                        }
                    },

                    async loginWithPasskey() {
                        this.loading = true;
                        try {
                            const response = await window.Passkeys.verify();
                            if (response && response.redirect) {
                                window.location.href = response.redirect;
                            } else {
                                // Fallback in case standard redirect logic differs
                                window.location.reload();
                            }
                        } catch (e) {
                            console.error('Passkey login fail:', e);
                            // Ignore cancel errors, alert others
                            if (e.name !== 'UserCancelledError' && e.name !== 'NotAllowedError' && !(e.message && e.message.includes('cancelled'))) {
                                Swal.fire({
                                    icon: 'error',
                                    title: '¡Vaya!',
                                    text: 'No se pudo iniciar sesión con Passkey. Inténtalo usando tu email y contraseña habitual.',
                                    footer: '<code class="text-[10px] text-gray-400">' + (e.name || 'Error') + ': ' + (e.message || 'Sesión fallida') + '</code>',
                                    confirmButtonColor: '#4F46E5',
                                    customClass: {
                                        popup: 'rounded-[2.5rem] border-0 shadow-2xl dark:bg-gray-900 dark:text-white',
                                        confirmButton: 'rounded-2xl px-6 py-3 uppercase tracking-widest font-black text-xs focus:ring-0',
                                    }
                                });
                            }
                        } finally {
                            this.loading = false;
                        }
                    }
                }
            }
        </script>
    </div>
</x-guest-layout>
