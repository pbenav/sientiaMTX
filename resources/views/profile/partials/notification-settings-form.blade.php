<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('notifications.settings_title') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('notifications.settings_intro') }}
        </p>
    </header>

    @php
        $settings = $user->notification_settings ?? $user->defaultNotificationSettings();
    @endphp

    <form method="post" action="{{ route('profile.notifications.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <!-- Channels -->
        <div class="space-y-4">
            <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-widest">
                {{ __('notifications.channels') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Email -->
                <div class="flex items-center gap-3 bg-gray-50 dark:bg-gray-800/50 p-4 rounded-2xl border border-gray-100 dark:border-gray-700 group hover:shadow-sm transition-all shadow-inner">
                    <div class="flex-1">
                        <label for="mail" class="block text-sm font-bold text-gray-700 dark:text-gray-300 cursor-pointer">
                            {{ __('notifications.channel_email') }}
                        </label>
                    </div>
                    <input type="checkbox" id="mail" name="mail" value="1" {{ ($settings['mail'] ?? false) ? 'checked' : '' }}
                        class="w-6 h-6 rounded-lg border-gray-300 dark:border-gray-700 text-violet-600 focus:ring-violet-500 shadow-sm transition-all cursor-pointer">
                </div>

                <!-- Web Push -->
                <div class="flex items-center gap-3 bg-gray-50 dark:bg-gray-800/50 p-4 rounded-2xl border border-gray-100 dark:border-gray-700 group hover:shadow-sm transition-all shadow-inner">
                    <div class="flex-1">
                        <label for="web_push" class="block text-sm font-bold text-gray-700 dark:text-gray-300 cursor-pointer">
                            {{ __('notifications.channel_web_push') }}
                        </label>
                    </div>
                    <input type="checkbox" id="web_push" name="web_push" value="1" {{ ($settings['web_push'] ?? false) ? 'checked' : '' }}
                        class="w-6 h-6 rounded-lg border-gray-300 dark:border-gray-700 text-violet-600 focus:ring-violet-500 shadow-sm transition-all cursor-pointer">
                </div>

                <!-- Telegram -->
                <div class="flex items-center gap-3 bg-gray-50 dark:bg-gray-800/50 p-4 rounded-2xl border border-gray-100 dark:border-gray-700 group hover:shadow-sm transition-all shadow-inner">
                    <div class="flex-1">
                        <label for="telegram" class="block text-sm font-bold text-gray-700 dark:text-gray-300 cursor-pointer">
                            {{ __('notifications.channel_telegram') }}
                        </label>
                    </div>
                    <input type="checkbox" id="telegram" name="telegram" value="1" {{ ($settings['telegram'] ?? false) ? 'checked' : '' }}
                        class="w-6 h-6 rounded-lg border-gray-300 dark:border-gray-700 text-violet-600 focus:ring-violet-500 shadow-sm transition-all cursor-pointer">
                </div>

                <!-- WhatsApp -->
                <div class="flex items-center gap-3 bg-gray-50 dark:bg-gray-800/50 p-4 rounded-2xl border border-gray-100 dark:border-gray-700 group hover:shadow-sm transition-all opacity-50 shadow-inner">
                    <div class="flex-1">
                        <label class="block text-sm font-bold text-gray-400 dark:text-gray-500">
                            {{ __('notifications.channel_whatsapp') }}
                        </label>
                        <span class="text-[10px] text-amber-500 font-bold uppercase tracking-tighter">{{ __('notifications.coming_soon') }}</span>
                    </div>
                    <input type="checkbox" disabled class="w-6 h-6 rounded-lg border-gray-300 dark:border-gray-700 text-gray-400 cursor-not-allowed">
                </div>
            </div>
        </div>

        <!-- Telegram Config -->
        <div x-data="{ open: {{ ($settings['telegram'] ?? false) ? 'true' : 'false' }} }" x-show="open" x-transition class="p-4 bg-indigo-50/50 dark:bg-indigo-900/10 rounded-2xl border border-indigo-100 dark:border-indigo-800 space-y-4">
            <h3 class="text-xs font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-widest flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
                {{ __('notifications.telegram_setup') }}
            </h3>
            
            <p class="text-xs text-gray-600 dark:text-gray-400">
                {!! __('notifications.telegram_instructions', ['bot_link' => '<a href="https://t.me/SientiaBot" target="_blank" class="text-indigo-600 font-bold hover:underline">@SientiaBot</a>']) !!}
            </p>

            <div>
                <x-input-label for="telegram_chat_id" :value="__('notifications.telegram_chat_id')" />
                <x-text-input id="telegram_chat_id" name="telegram_chat_id" type="text" class="mt-1 block w-full" :value="old('telegram_chat_id', $user->telegram_chat_id)" placeholder="Ej: 123456789" />
                <x-input-error class="mt-2" :messages="$errors->get('telegram_chat_id')" />
            </div>
        </div>

        <!-- Quiet Hours -->
        <div class="space-y-4 pt-4 border-t border-gray-100 dark:border-gray-800">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-widest">
                        {{ __('notifications.quiet_hours') }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('notifications.quiet_hours_desc') }}
                    </p>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="quiet_hours_enabled" name="quiet_hours_enabled" value="1" {{ ($settings['quiet_hours_enabled'] ?? false) ? 'checked' : '' }}
                        class="w-6 h-6 rounded-lg border-gray-300 dark:border-gray-700 text-violet-600 focus:ring-violet-500 shadow-sm transition-all cursor-pointer">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="quiet_hours_start" :value="__('notifications.quiet_hours_start')" />
                    <x-text-input id="quiet_hours_start" name="quiet_hours_start" type="time" class="mt-1 block w-full" :value="old('quiet_hours_start', $settings['quiet_hours_start'] ?? '22:00')" />
                </div>
                <div>
                    <x-input-label for="quiet_hours_end" :value="__('notifications.quiet_hours_end')" />
                    <x-text-input id="quiet_hours_end" name="quiet_hours_end" type="time" class="mt-1 block w-full" :value="old('quiet_hours_end', $settings['quiet_hours_end'] ?? '08:00')" />
                </div>
            </div>
        </div>

        <!-- Lead Time -->
        <div class="pt-4 border-t border-gray-100 dark:border-gray-800">
            <x-input-label for="notify_before_hours" :value="__('notifications.notify_before')" />
            <div class="flex items-center gap-4 mt-1">
                <x-text-input id="notify_before_hours" name="notify_before_hours" type="number" class="block w-24" :value="old('notify_before_hours', $settings['notify_before_hours'] ?? 2)" min="0" max="168" />
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('notifications.hours_before') }}</span>
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('notify_before_hours')" />
        </div>

        <!-- Additional Preferences -->
        <div class="pt-6 border-t border-gray-100 dark:border-gray-800">
            <h4 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                </svg>
                {{ __('profile.preferences') }}
            </h4>

            <div class="flex items-center gap-3">
                <input type="checkbox" id="welcome_messages" name="welcome_messages" value="1" {{ ($settings['welcome_messages'] ?? true) ? 'checked' : '' }}
                    class="w-5 h-5 rounded border-gray-300 dark:border-gray-700 text-violet-600 focus:ring-violet-500 shadow-sm transition-all cursor-pointer">
                <div class="flex-1">
                    <label for="welcome_messages" class="block text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                        {{ __('profile.welcome_messages') }}
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('profile.welcome_messages_desc') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4 pt-4">
            <x-primary-button>{{ __('profile.save') }}</x-primary-button>

            @if (session('status') === 'notifications-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-emerald-600 font-bold flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ __('Saved.') }}
                </p>
            @endif
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const webPushCheckbox = document.getElementById('web_push');
            
            if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                if (webPushCheckbox) {
                    webPushCheckbox.disabled = true;
                    webPushCheckbox.parentElement.parentElement.style.opacity = '0.5';
                }
                return;
            }

            // Register Service Worker
            navigator.serviceWorker.register('/sw.js').then(registration => {
                console.log('SW registered');
            });

            webPushCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    subscribeUser();
                } else {
                    unsubscribeUser();
                }
            });

            function subscribeUser() {
                navigator.serviceWorker.ready.then(registration => {
                    const subscribeOptions = {
                        userVisibleOnly: true,
                        applicationServerKey: urlBase64ToUint8Array("{{ config('webpush.vapid.public_key') }}")
                    };

                    return registration.pushManager.subscribe(subscribeOptions);
                }).then(pushSubscription => {
                    return storeSubscription(pushSubscription);
                }).then(response => {
                    console.log('User is subscribed');
                }).catch(err => {
                    console.error('Failed to subscribe user: ', err);
                    webPushCheckbox.checked = false;
                });
            }

            function unsubscribeUser() {
                navigator.serviceWorker.ready.then(registration => {
                    return registration.pushManager.getSubscription();
                }).then(subscription => {
                    if (subscription) {
                        const endpoint = subscription.endpoint;
                        subscription.unsubscribe();
                        return deleteSubscription(endpoint);
                    }
                }).catch(err => {
                    console.error('Failed to unsubscribe user: ', err);
                });
            }

            function storeSubscription(subscription) {
                return fetch("{{ route('webpush.subscribe') }}", {
                    method: 'POST',
                    body: JSON.stringify(subscription),
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
            }

            function deleteSubscription(endpoint) {
                return fetch("{{ route('webpush.unsubscribe') }}", {
                    method: 'POST',
                    body: JSON.stringify({ endpoint: endpoint }),
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
            }

            function urlBase64ToUint8Array(base64String) {
                const padding = '='.repeat((4 - base64String.length % 4) % 4);
                const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
                const rawData = window.atob(base64);
                const outputArray = new Uint8Array(rawData.length);
                for (let i = 0; i < rawData.length; ++i) {
                    outputArray[i] = rawData.charCodeAt(i);
                }
                return outputArray;
            }
        });
    </script>
</section>
