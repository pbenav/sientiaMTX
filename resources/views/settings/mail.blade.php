<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-white leading-tight heading">
            {{ __('Global Configuration') }}
        </h2>
    </x-slot>

    <div class="py-12 px-4 shadow-sm">
        <div class="max-w-7xl mx-auto">
            @include('settings.partials.tabs')

            <div class="space-y-10">
                <!-- Application Limits -->
                <div
                    class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-xl sm:rounded-2xl transition-all">
                    <div class="p-8">
                        <div class="flex items-center gap-3 mb-8">
                            <div
                                class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 17v-10l8 4" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white heading">
                                    {{ __('Application Limits') }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('Configure global limits and session preferences.') }}</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('settings.mail.update') }}" class="space-y-6">
                            @csrf

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <!-- Default Disk Quota -->
                                <div class="md:col-span-1">
                                    <x-input-label for="DEFAULT_DISK_QUOTA" :value="__('Default Disk Quota (MB)')" />
                                    <x-text-input id="DEFAULT_DISK_QUOTA" name="DEFAULT_DISK_QUOTA" type="number"
                                        class="mt-1 block w-full" :value="old('DEFAULT_DISK_QUOTA', $limits['default_disk_quota'])" required />
                                    <x-input-error :messages="$errors->get('DEFAULT_DISK_QUOTA')" class="mt-2" />
                                </div>

                                <!-- Session Expiration -->
                                <div class="md:col-span-1">
                                    <x-input-label for="SESSION_LIFETIME" :value="__('Session Expiration (Minutes)')" />
                                    <x-text-input id="SESSION_LIFETIME" name="SESSION_LIFETIME" type="number"
                                        class="mt-1 block w-full" :value="old('SESSION_LIFETIME', $limits['session_lifetime'])" required />
                                    <x-input-error :messages="$errors->get('SESSION_LIFETIME')" class="mt-2" />
                                </div>

                                <!-- Kanban Completed Limit -->
                                <div class="md:col-span-1">
                                    <x-input-label for="KANBAN_COMPLETED_LIMIT" :value="__('Max Completed Tasks visible')" />
                                    <x-text-input id="KANBAN_COMPLETED_LIMIT" name="KANBAN_COMPLETED_LIMIT" type="number"
                                        class="mt-1 block w-full" :value="old('KANBAN_COMPLETED_LIMIT', $limits['kanban_completed_limit'])" required min="1" max="100" />
                                    <x-input-error :messages="$errors->get('KANBAN_COMPLETED_LIMIT')" class="mt-2" />
                                </div>

                                <!-- Quick Notes Audio Limit -->
                                <div class="md:col-span-1">
                                    <x-input-label for="quick_notes_audio_max_duration" value="Duración máx. audio notas (segundos)" />
                                    <x-text-input id="quick_notes_audio_max_duration" name="quick_notes_audio_max_duration" type="number"
                                        class="mt-1 block w-full" :value="old('quick_notes_audio_max_duration', $limits['quick_notes_audio_max_duration'])" required min="5" max="300" />
                                    <x-input-error :messages="$errors->get('quick_notes_audio_max_duration')" class="mt-2" />
                                </div>

                                <!-- Zona Horaria Global -->
                                <div class="md:col-span-3" x-data="{ search: '', tz: '{{ old('site_timezone', $site_timezone) }}' }">
                                    <x-input-label for="site_timezone" value="Zona Horaria Global del Sitio" />
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2 mt-0.5">
                                        Zona horaria por defecto para todos los usuarios. Afecta a la visualización de fechas, horas y notificaciones.
                                    </p>
                                    <div class="relative">
                                        <input type="text" x-model="search"
                                            placeholder="Buscar zona horaria (ej: Madrid, London, UTC)..."
                                            class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none focus:border-violet-500 focus:ring focus:ring-violet-500/20 transition-all mb-2" />
                                        <select id="site_timezone" name="site_timezone" x-model="tz"
                                            class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none focus:border-violet-500 transition-all cursor-pointer">
                                            @foreach($timezones as $tz)
                                                <option value="{{ $tz }}"
                                                    x-show="search === '' || '{{ strtolower($tz) }}'.includes(search.toLowerCase())"
                                                    :selected="tz === '{{ $tz }}'">
                                                    {{ $tz }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <p class="mt-1.5 text-xs font-medium text-violet-600 dark:text-violet-400">
                                        Zona activa actualmente: <span class="font-bold">{{ $site_timezone }}</span>
                                        ({{ now()->format('d/m/Y H:i') }})
                                    </p>
                                    <x-input-error :messages="$errors->get('site_timezone')" class="mt-2" />
                                </div>

                                <div class="md:col-span-3 flex items-center gap-2">
                                    <input type="checkbox" id="update_existing_users" name="update_existing_users" value="1"
                                        class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-violet-600 shadow-sm focus:ring-violet-500">
                                    <label for="update_existing_users" class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ __('Update all existing users to this new quota') }}
                                    </label>
                                </div>
                            </div>

                            <div class="flex items-center justify-end pt-6 border-t border-gray-100 dark:border-gray-800">
                                <x-primary-button
                                    class="px-8 py-3 bg-amber-600 hover:bg-amber-500 focus:bg-amber-500 active:bg-amber-700">
                                    {{ __('Save Configuration') }}
                                </x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>

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

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
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
                                <div class="md:col-span-1">
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

                <!-- Google Integration Configuration -->
                <div
                    class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-xl sm:rounded-2xl transition-all">
                    <div class="p-8">
                        <div class="flex items-center gap-3 mb-8">
                            <div
                                class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center text-red-600 dark:text-red-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24"
                                    fill="currentColor">
                                    <path
                                        d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                                    <path
                                        d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                                        fill="#34A853" />
                                    <path
                                        d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                                        fill="#FBBC05" />
                                    <path
                                        d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                                        fill="#EA4335" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white heading">
                                    {{ __('Google OAuth Integration') }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('Configure OAuth2 credentials for Gmail and Calendar synchronization.') }}</p>
                            </div>
                        </div>

                        <!-- Explanatory Text -->
                        <div
                            class="mb-8 p-4 bg-violet-50 dark:bg-gray-800/50 border border-violet-100 dark:border-violet-500/30 rounded-xl text-sm text-violet-800 dark:text-violet-300">
                            <h4 class="font-bold mb-2 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ __('How this works') }}
                            </h4>
                            <p class="mb-2">
                                {{ __('These parameters represent the "Identity" of your app. Once configured, each user can independently authorize the app to access their own data.') }}
                            </p>
                            <p>{{ __('Individual access tokens are stored securely for each user; they never share calendar or mail data.') }}
                            </p>
                        </div>

                        <form method="POST" action="{{ route('settings.mail.update') }}" class="space-y-6">
                            @csrf

                            <div class="grid grid-cols-1 gap-6">
                                <!-- Client ID -->
                                <div>
                                    <x-input-label for="GOOGLE_CLIENT_ID" :value="__('Google Client ID')" />
                                    <x-text-input id="GOOGLE_CLIENT_ID" name="GOOGLE_CLIENT_ID" type="text"
                                        class="mt-1 block w-full" :value="old('GOOGLE_CLIENT_ID', $google['client_id'])"
                                        placeholder="xxxxx.apps.googleusercontent.com" />
                                    <x-input-error :messages="$errors->get('GOOGLE_CLIENT_ID')" class="mt-2" />
                                </div>

                                <!-- Client Secret -->
                                <div>
                                    <x-input-label for="GOOGLE_CLIENT_SECRET" :value="__('Google Client Secret')" />
                                    <x-text-input id="GOOGLE_CLIENT_SECRET" name="GOOGLE_CLIENT_SECRET" type="password"
                                        class="mt-1 block w-full" :value="old('GOOGLE_CLIENT_SECRET', $google['client_secret'])" placeholder="••••••••••••" />
                                    <x-input-error :messages="$errors->get('GOOGLE_CLIENT_SECRET')" class="mt-2" />
                                </div>

                                <!-- Redirect URI Guide -->
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">
                                        {{ __('Authorized Redirect URI') }}</p>
                                    <div
                                        class="bg-gray-100 dark:bg-gray-900 p-2 rounded-lg font-mono text-[10px] break-all select-all border border-gray-200 dark:border-gray-800">
                                        {{ $google['redirect_uri'] }}
                                    </div>
                                    <p class="text-[10px] text-gray-500 mt-1 italic">
                                        {{ __('Add this URL to your Google Cloud Console project settings.') }}</p>
                                </div>
                            </div>

                            <div class="flex items-center justify-end pt-6 border-t border-gray-100 dark:border-gray-800">
                                <x-primary-button
                                    class="px-8 py-3 bg-red-600 hover:bg-red-500 focus:bg-red-500 active:bg-red-700">
                                    {{ __('Save Google Configuration') }}
                                </x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Telegram Integration -->
                <div
                    class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-xl sm:rounded-2xl transition-all">
                    <div class="p-8">
                        <div class="flex items-center gap-3 mb-8">
                            <div
                                class="w-10 h-10 rounded-xl bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center text-sky-600 dark:text-sky-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white heading">
                                    {{ __('notifications.telegram_bot_title') }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('notifications.telegram_bot_desc') }}</p>
                            </div>
                        </div>

                        {{-- Webhook Diagnostic --}}
                        @if(isset($telegram['webhook_info']))
                        <div class="mb-8 p-4 rounded-xl border {{ isset($telegram['webhook_info']['last_error_message']) ? 'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800' : 'bg-emerald-50 border-emerald-200 dark:bg-emerald-900/20 dark:border-emerald-800' }}">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-2 h-2 rounded-full {{ isset($telegram['webhook_info']['last_error_message']) ? 'bg-red-500' : 'bg-emerald-500 animate-pulse' }}"></div>
                                <span class="text-xs font-bold uppercase tracking-wider {{ isset($telegram['webhook_info']['last_error_message']) ? 'text-red-700 dark:text-red-400' : 'text-emerald-700 dark:text-emerald-400' }}">
                                    {{ isset($telegram['webhook_info']['last_error_message']) ? 'Error en Webhook' : 'Webhook Activo' }}
                                </span>
                            </div>
                            <div class="space-y-1">
                                <p class="text-xs text-gray-600 dark:text-gray-400"><strong>URL:</strong> {{ ($telegram['webhook_info']['url'] ?? null) ?: 'No registrada' }}</p>
                                @if(isset($telegram['webhook_info']['last_error_message']))
                                    <p class="text-xs text-red-600 dark:text-red-400 font-mono mt-2"><strong>Último error:</strong> {{ $telegram['webhook_info']['last_error_message'] }}</p>
                                    <p class="text-[10px] text-gray-500 mt-1 italic">{{ __('notifications.webhook_troubleshoot') }}</p>
                                @else
                                    <p class="text-xs text-gray-600 dark:text-gray-400"><strong>Updates pendientes:</strong> {{ $telegram['webhook_info']['pending_update_count'] ?? 0 }}</p>
                                @endif
                            </div>
                        </div>
                        @endif

                        <form method="POST" action="{{ route('settings.mail.update') }}" class="space-y-6">
                            @csrf

                            <div class="grid grid-cols-1 gap-6">
                                <!-- Bot Token -->
                                <div>
                                    <x-input-label for="TELEGRAM_BOT_TOKEN" :value="__('notifications.telegram_bot_token')" />
                                    <x-text-input id="TELEGRAM_BOT_TOKEN" name="TELEGRAM_BOT_TOKEN" type="password"
                                        class="mt-1 block w-full" :value="old('TELEGRAM_BOT_TOKEN', $telegram['bot_token'])"
                                        placeholder="123456789:ABCDefgh-IJKlmno..." />
                                    <x-input-error :messages="$errors->get('TELEGRAM_BOT_TOKEN')" class="mt-2" />
                                </div>

                                <div class="p-4 bg-sky-50 dark:bg-sky-900/10 rounded-xl border border-sky-100 dark:border-sky-800 flex gap-3 text-xs text-sky-700 dark:text-sky-400 leading-relaxed">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div>
                                        {{ __('notifications.telegram_bot_instructions') }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-end pt-6 border-t border-gray-100 dark:border-gray-800">
                                <x-primary-button
                                    class="px-8 py-3 bg-sky-600 hover:bg-sky-500 focus:bg-sky-500 active:bg-sky-700">
                                    {{ __('notifications.save_telegram_config') }}
                                </x-primary-button>
                            </div>
                        </form>

                        {{-- Secondary Actions (placed outside to avoid nested forms) --}}
                        <div class="mt-4 flex items-center gap-4">
                            <button type="button" x-data=""
                                x-on:click.prevent="$dispatch('open-modal', 'test-telegram-modal')"
                                class="text-sm font-bold text-sky-600 dark:text-sky-400 hover:text-sky-500 transition-colors uppercase tracking-widest">{{ __('notifications.test_telegram') }}</button>
                            
                            <form method="POST" action="{{ route('settings.telegram.register') }}">
                                @csrf
                                <button type="submit"
                                    class="text-sm font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 transition-colors uppercase tracking-widest">{{ __('notifications.register_webhook') }}</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Hint about config cache -->
                <div
                    class="p-6 bg-amber-50 dark:bg-gray-800/50 border border-amber-200 dark:border-amber-900/30 rounded-2xl flex gap-4 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-600 dark:text-amber-500 shrink-0"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div class="text-sm text-amber-800 dark:text-amber-200/90 leading-relaxed">
                        <p class="font-bold mb-1">{{ __('Note on .env changes') }}</p>
                        <p>{{ __('Updating these settings will modify your .env file and clear the configuration cache. Changes should take effect immediately, but a web server restart might be needed in some environments.') }}
                        </p>
                    </div>
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

    <!-- Test Telegram Modal -->
    <x-modal name="test-telegram-modal" focusable>
        <form method="POST" action="{{ route('settings.telegram.test') }}" class="p-8">
            @csrf
            <h2 class="text-xl font-bold text-gray-900 dark:text-white heading mb-2">
                {{ __('notifications.send_test_telegram') }}
            </h2>

            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                {{ __('notifications.telegram_bot_test_instruction') }}
            </p>

            <div>
                <x-input-label for="test_chat_id" :value="__('notifications.telegram_chat_id')" />
                <x-text-input id="test_chat_id" name="test_chat_id" type="text" class="mt-1 block w-full"
                    placeholder="Ej: 123456789" required />
                <x-input-error :messages="$errors->get('test_chat_id')" class="mt-2" />
            </div>

            <div class="mt-8 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('profile.cancel') }}
                </x-secondary-button>

                <x-primary-button class="bg-sky-600 hover:bg-sky-500">
                    {{ __('notifications.confirm_ok') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
