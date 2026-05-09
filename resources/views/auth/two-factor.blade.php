<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        🛡️ {{ __('Autenticación de Dos Factores (MFA)') }}
    </div>

    <div class="mb-4 text-xs text-gray-500">
        @if (isset($user) && $user->two_factor_method === 'email')
            {{ __('Por favor, introduzca el código de seguridad de 6 dígitos que hemos enviado a su correo electrónico para verificar su identidad.') }}
        @else
            {{ __('Por favor, introduzca el código de seguridad de 6 dígitos generado por su aplicación de autenticación (Google Authenticator, Microsoft Authenticator, Authy, etc.) para verificar su identidad.') }}
        @endif
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login.two-factor') }}">
        @csrf

        <!-- Verification Code -->
        <div>
            <x-input-label for="code" :value="__('Código de Verificación')" />
            <x-text-input id="code" class="block mt-1 w-full text-center tracking-widest text-lg font-mono" 
                type="text" 
                name="code" 
                placeholder="000000"
                required 
                autofocus 
                autocomplete="one-time-code" 
                maxlength="6"
                pattern="[0-9]{6}" />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-6">
            <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                href="{{ route('login') }}">
                {{ __('Volver al Login') }}
            </a>

            <x-primary-button class="ms-3">
                {{ __('Verificar y Acceder') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
