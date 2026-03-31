<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required
                autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')"
                required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required
                autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password"
                name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="locale" :value="__('navigation.language')" />
            <select id="locale" name="locale"
                class="mt-1 block w-full bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm outline-none transition-all cursor-pointer">
                <option value="es" {{ old('locale', $detectedLocale) === 'es' ? 'selected' : '' }}>
                    {{ __('Spanish') }}</option>
                <option value="en" {{ old('locale', $detectedLocale) === 'en' ? 'selected' : '' }}>
                    {{ __('English') }}</option>
            </select>
            <x-input-error :messages="$errors->get('locale')" class="mt-2" />
        </div>

        <!-- GDPR Consents -->
        <div class="mt-6 space-y-4">
            <div class="flex items-start">
                <div class="flex items-center h-5">
                    <input id="terms" name="terms" type="checkbox" required
                        class="w-5 h-5 text-violet-600 bg-gray-50 dark:bg-gray-800 border-gray-300 dark:border-gray-700 rounded-lg focus:ring-violet-500/20 focus:ring-4 transition-all cursor-pointer">
                </div>
                <div class="ml-3 text-sm">
                    <label for="terms" class="text-gray-600 dark:text-gray-400">
                        {{ __('Acepto los') }}
                        <a href="{{ route('terms') }}" target="_blank"
                            class="text-violet-600 dark:text-violet-400 font-semibold hover:underline">{{ __('Términos de Servicio') }}</a>
                        {{ __('y la') }}
                        <a href="{{ route('privacy') }}" target="_blank"
                            class="text-violet-600 dark:text-violet-400 font-semibold hover:underline">{{ __('Política de Privacidad') }}</a>.
                    </label>
                    <x-input-error :messages="$errors->get('terms')" class="mt-1" />
                </div>
            </div>

            <div class="flex items-start">
                <div class="flex items-center h-5">
                    <input id="marketing" name="marketing" type="checkbox"
                        class="w-5 h-5 text-violet-600 bg-gray-50 dark:bg-gray-800 border-gray-300 dark:border-gray-700 rounded-lg focus:ring-violet-500/20 focus:ring-4 transition-all cursor-pointer">
                </div>
                <div class="ml-3 text-sm text-gray-600 dark:text-gray-400">
                    <label for="marketing">
                        {{ __('Deseo recibir actualizaciones y noticias sobre Sientia (Opcional).') }}
                    </label>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end mt-8 pt-6 border-t border-gray-100 dark:border-gray-800">
            <a class="underline text-sm text-gray-500 hover:text-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500"
                href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
