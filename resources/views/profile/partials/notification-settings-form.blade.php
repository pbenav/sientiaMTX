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

    <form method="post" action="{{ route('profile.notifications.update') }}" class="mt-6 space-y-6" x-data="{ 
        telegramEnabled: {{ ($settings['telegram'] ?? false) ? 'true' : 'false' }}
    }">
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
                <div class="flex flex-col gap-2 bg-gray-50 dark:bg-gray-800/50 p-4 rounded-2xl border border-gray-100 dark:border-gray-700 group hover:shadow-sm transition-all shadow-inner">
                    <div class="flex items-center gap-3">
                        <div class="flex-1">
                            <label for="web_push" class="block text-sm font-bold text-gray-700 dark:text-gray-300 cursor-pointer">
                                {{ __('notifications.channel_web_push') }}
                            </label>
                        </div>
                        <input type="checkbox" id="web_push" name="web_push" value="1" {{ ($settings['web_push'] ?? false) ? 'checked' : '' }}
                            class="w-6 h-6 rounded-lg border-gray-300 dark:border-gray-700 text-violet-600 focus:ring-violet-500 shadow-sm transition-all cursor-pointer">
                    </div>
                    <div id="webpush-status" style="display:none"></div>
                </div>

                <!-- Telegram -->
                <div class="flex items-center gap-3 bg-gray-50 dark:bg-gray-800/50 p-4 rounded-2xl border border-gray-100 dark:border-gray-700 group hover:shadow-sm transition-all shadow-inner">
                    <div class="flex-1">
                        <label for="telegram" class="block text-sm font-bold text-gray-700 dark:text-gray-300 cursor-pointer">
                            {{ __('notifications.channel_telegram') }}
                        </label>
                    </div>
                    <input type="checkbox" id="telegram" name="telegram" value="1" {{ ($settings['telegram'] ?? false) ? 'checked' : '' }}
                        x-model="telegramEnabled"
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

        <!-- Morning Summary -->
        <div class="space-y-4 pt-4 border-t border-gray-100 dark:border-gray-800">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-widest">
                        🌅 {{ __('Resumen Matutino Ax.ia') }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Recibe cada mañana tus tareas del día y una frase motivacional generada por la IA.') }}
                    </p>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="morning_summary" name="morning_summary" value="1" {{ ($settings['morning_summary'] ?? false) ? 'checked' : '' }}
                        class="w-6 h-6 rounded-lg border-gray-300 dark:border-gray-700 text-violet-600 focus:ring-violet-500 shadow-sm transition-all cursor-pointer">
                </div>
            </div>

            <div x-show="document.getElementById('morning_summary').checked" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="morning_summary_time" :value="__('Hora de envío')" />
                    <x-text-input id="morning_summary_time" name="morning_summary_time" type="time" class="mt-1 block w-full" :value="old('morning_summary_time', $settings['morning_summary_time'] ?? '08:00')" />
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
            const statusMsg = document.getElementById('webpush-status');

            // Comprobar soporte
            if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                if (webPushCheckbox) {
                    webPushCheckbox.disabled = true;
                    webPushCheckbox.closest('.flex').style.opacity = '0.4';
                    showStatus('⚠️ Tu navegador no soporta notificaciones push.', 'warning');
                }
                return;
            }

            // Registrar Service Worker y comprobar si ya hay suscripción activa
            navigator.serviceWorker.register('/sw.js').then(registration => {
                return registration.pushManager.getSubscription();
            }).then(subscription => {
                if (subscription) {
                    // Ya suscrito — asegurar que el checkbox refleja la realidad
                    webPushCheckbox.dataset.subscribed = 'true';
                } else {
                    webPushCheckbox.dataset.subscribed = 'false';
                }
            }).catch(err => {
                console.warn('SW registration failed:', err);
            });

            webPushCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    // Comprobar si los permisos ya están denegados
                    if (Notification.permission === 'denied') {
                        this.checked = false;
                        showStatus('🚫 Has bloqueado los permisos de notificación. Debes habilitarlos manualmente en la configuración del navegador (icono 🔒 en la barra de direcciones).', 'error');
                        return;
                    }
                    subscribeUser();
                } else {
                    unsubscribeUser();
                }
            });

            function subscribeUser() {
                const vapidKey = "{{ config('webpush.vapid.public_key') }}";

                if (!vapidKey || vapidKey.trim() === '') {
                    webPushCheckbox.checked = false;
                    showStatus('⚠️ Las claves VAPID no están configuradas en el servidor. Contacta con el administrador.', 'error');
                    return;
                }

                showStatus('⏳ Solicitando permiso de notificaciones...', 'info');

                Notification.requestPermission().then(permission => {
                    if (permission !== 'granted') {
                        webPushCheckbox.checked = false;
                        showStatus('❌ Permiso denegado. Activa las notificaciones en tu navegador.', 'error');
                        return;
                    }

                    navigator.serviceWorker.ready.then(registration => {
                        return registration.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: urlBase64ToUint8Array(vapidKey)
                        });
                    }).then(pushSubscription => {
                        return storeSubscription(pushSubscription);
                    }).then(response => {
                        if (response && response.ok) {
                            showStatus('✅ Notificaciones de navegador activadas.', 'success');
                        } else {
                            showStatus('⚠️ Suscripción guardada pero el servidor devolvió un error. Recarga la página.', 'warning');
                        }
                    }).catch(async err => {
                        console.error('Failed to subscribe:', err);
                        
                        // Si el error es por cambio de claves VAPID (común si se resetearon en el servidor), 
                        // intentamos desuscribir la antigua y volver a intentar automáticamente.
                        if (err.message.includes('application server key already exists')) {
                            showStatus('🔄 Actualizando claves de servidor... por favor espera.', 'info');
                            try {
                                const registration = await navigator.serviceWorker.ready;
                                const subscription = await registration.pushManager.getSubscription();
                                if (subscription) {
                                    await subscription.unsubscribe();
                                }
                                // Reintentar suscripción
                                setTimeout(() => subscribeUser(), 500);
                                return;
                            } catch (retryErr) {
                                console.error('Error al intentar limpiar suscripción antigua:', retryErr);
                            }
                        }

                        webPushCheckbox.checked = false;
                        
                        // Traducción amigable de errores comunes del navegador
                        let friendlyMsg = err.message;
                        if (err.name === 'NotAllowedError' || err.message.includes('permission denied')) {
                            friendlyMsg = 'Permiso denegado. Por favor, habilita las notificaciones en la configuración de tu navegador.';
                        } else if (err.message.includes('application server key already exists')) {
                            friendlyMsg = 'Conflicto con una suscripción anterior. Intenta recargar la página.';
                        } else if (err.message.includes('ServiceWorker')) {
                            friendlyMsg = 'Error relacionado con el Service Worker. Intenta recargar la página.';
                        } else if (err.message.includes('push service error')) {
                            // Detección especial para Brave
                            const isBrave = navigator.brave && await navigator.brave.isBrave();
                            if (isBrave) {
                                friendlyMsg = 'Brave bloquea las notificaciones por defecto. Ve a brave://settings/privacy y activa "Usar servicios de Google para la mensajería push", luego reinicia el navegador.';
                            } else {
                                friendlyMsg = 'El navegador no pudo conectar con el servicio de notificaciones. Verifica tu conexión o intenta con otro navegador.';
                            }
                        }

                        showStatus('❌ Error al suscribirse: ' + friendlyMsg, 'error');
                    });
                });
            }

            function unsubscribeUser() {
                navigator.serviceWorker.ready.then(registration => {
                    return registration.pushManager.getSubscription();
                }).then(subscription => {
                    if (subscription) {
                        const endpoint = subscription.endpoint;
                        return subscription.unsubscribe().then(() => deleteSubscription(endpoint));
                    }
                }).then(() => {
                    showStatus('🔕 Notificaciones de navegador desactivadas.', 'info');
                }).catch(err => {
                    console.error('Failed to unsubscribe:', err);
                });
            }

            function storeSubscription(subscription) {
                return fetch("{{ route('webpush.subscribe') }}", {
                    method: 'POST',
                    body: JSON.stringify(subscription.toJSON()),
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

            function showStatus(msg, type) {
                if (!statusMsg) return;
                const colors = {
                    success: 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800',
                    error:   'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
                    warning: 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800',
                    info:    'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800',
                };
                statusMsg.className = `mt-2 p-3 rounded-xl border text-xs font-medium ${colors[type] ?? colors.info}`;
                statusMsg.textContent = msg;
                statusMsg.style.display = 'block';
            }
        });
    </script>
</section>
