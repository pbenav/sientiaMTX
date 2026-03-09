<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-white leading-tight heading">
            {{ __('Global Mail Configuration') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <!-- Mail Configuration Form -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-xl sm:rounded-2xl transition-all">
                <div class="p-8">
                    <div class="flex items-center gap-3 mb-8">
                        <div
                            class="w-10 h-10 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center text-violet-600 dark:text-violet-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white heading">
                                {{ __('SMTP Server Settings') }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ __('Configure how the application sends automated emails.') }}</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('settings.mail.update') }}" class="space-y-6">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Mailer -->
                            <div>
                                <x-input-label for="MAIL_MAILER" :value="__('Mailer')" />
                                <x-text-input id="MAIL_MAILER" name="MAIL_MAILER" type="text"
                                    class="mt-1 block w-full" :value="old('MAIL_MAILER', $config['mailer'])" required />
                                <x-input-error :messages="$errors->get('MAIL_MAILER')" class="mt-2" />
                            </div>

                            <!-- Encryption -->
                            <div>
                                <x-input-label for="MAIL_ENCRYPTION" :value="__('Encryption (tls/ssl)')" />
                                <x-text-input id="MAIL_ENCRYPTION" name="MAIL_ENCRYPTION" type="text"
                                    class="mt-1 block w-full" :value="old('MAIL_ENCRYPTION', $config['encryption'])" placeholder="tls / ssl" />
                                <x-input-error :messages="$errors->get('MAIL_ENCRYPTION')" class="mt-2" />
                            </div>

                            <!-- Host -->
                            <div class="md:col-span-2">
                                <x-input-label for="MAIL_HOST" :value="__('SMTP Host')" />
                                <x-text-input id="MAIL_HOST" name="MAIL_HOST" type="text" class="mt-1 block w-full"
                                    :value="old('MAIL_HOST', $config['host'])" required />
                                <x-input-error :messages="$errors->get('MAIL_HOST')" class="mt-2" />
                            </div>

                            <!-- Port -->
                            <div>
                                <x-input-label for="MAIL_PORT" :value="__('Port')" />
                                <x-text-input id="MAIL_PORT" name="MAIL_PORT" type="number" class="mt-1 block w-full"
                                    :value="old('MAIL_PORT', $config['port'])" required />
                                <x-input-error :messages="$errors->get('MAIL_PORT')" class="mt-2" />
                            </div>

                            <!-- Username -->
                            <div>
                                <x-input-label for="MAIL_USERNAME" :value="__('Username')" />
                                <x-text-input id="MAIL_USERNAME" name="MAIL_USERNAME" type="text"
                                    class="mt-1 block w-full" :value="old('MAIL_USERNAME', $config['username'])" />
                                <x-input-error :messages="$errors->get('MAIL_USERNAME')" class="mt-2" />
                            </div>

                            <!-- Password -->
                            <div>
                                <x-input-label for="MAIL_PASSWORD" :value="__('Password')" />
                                <x-text-input id="MAIL_PASSWORD" name="MAIL_PASSWORD" type="password"
                                    class="mt-1 block w-full" :value="old('MAIL_PASSWORD', $config['password'])" />
                                <x-input-error :messages="$errors->get('MAIL_PASSWORD')" class="mt-2" />
                            </div>

                            <!-- From Address -->
                            <div>
                                <x-input-label for="MAIL_FROM_ADDRESS" :value="__('From Address')" />
                                <x-text-input id="MAIL_FROM_ADDRESS" name="MAIL_FROM_ADDRESS" type="email"
                                    class="mt-1 block w-full" :value="old('MAIL_FROM_ADDRESS', $config['from_address'])" required />
                                <x-input-error :messages="$errors->get('MAIL_FROM_ADDRESS')" class="mt-2" />
                            </div>

                            <!-- From Name -->
                            <div class="md:col-span-2">
                                <x-input-label for="MAIL_FROM_NAME" :value="__('From Name')" />
                                <x-text-input id="MAIL_FROM_NAME" name="MAIL_FROM_NAME" type="text"
                                    class="mt-1 block w-full" :value="old('MAIL_FROM_NAME', $config['from_name'])" required />
                                <x-input-error :messages="$errors->get('MAIL_FROM_NAME')" class="mt-2" />
                            </div>
                        </div>

                        <div
                            class="flex items-center justify-between pt-6 border-t border-gray-100 dark:border-gray-800">
                            <button type="button" x-data=""
                                x-on:click.prevent="$dispatch('open-modal', 'test-mail-modal')"
                                class="text-sm font-bold text-violet-600 dark:text-violet-400 hover:text-violet-500 transition-colors uppercase tracking-widest">{{ __('Test Connection') }}</button>

                            <x-primary-button class="px-8 py-3">
                                {{ __('Save Configuration') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Hint about config cache -->
            <div
                class="p-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700/50 rounded-2xl flex gap-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-600 dark:text-amber-500 shrink-0"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div class="text-sm text-amber-800 dark:text-amber-200">
                    <p class="font-bold mb-1">{{ __('Note on .env changes') }}</p>
                    <p>{{ __('Updating these settings will modify your .env file and clear the configuration cache. Changes should take effect immediately, but a web server restart might be needed in some environments.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Mail Modal -->
    <x-modal name="test-mail-modal" focusable>
        <form method="POST" action="{{ route('settings.mail.test') }}" class="p-8">
            @csrf
            <h2 class="text-xl font-bold text-gray-900 dark:text-white heading mb-2">
                {{ __('Send Test Email') }}
            </h2>

            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                {{ __('Enter an email address to receive a test message and verify your SMTP configuration.') }}
            </p>

            <div>
                <x-input-label for="test_email" :value="__('Recipient Email')" />
                <x-text-input id="test_email" name="test_email" type="email" class="mt-1 block w-full"
                    placeholder="you@example.com" required />
                <x-input-error :messages="$errors->get('test_email')" class="mt-2" />
            </div>

            <div class="mt-8 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>

                <x-primary-button>
                    {{ __('Send Test') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
