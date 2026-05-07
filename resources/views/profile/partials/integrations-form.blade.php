@php
    $user = auth()->user();
    $teams = $user->teams()->get(); // Forzamos carga fresca de la DB
    
    // Mapa de conexiones por equipo (Pivots)
    $teamConns = [];
    $teamTelegramIds = [];
    foreach($teams as $team) {
        $teamConns[$team->id] = [
            'connected' => !empty($team->pivot->google_token),
            'email' => $team->pivot->google_email ?? ''
        ];
        $teamTelegramIds[$team->id] = $team->telegram_chat_id;
    }
    
    // Preferencias de IA
    $prefs = $user->aiPreferences->keyBy(fn($p) => $p->team_id ?? 'global');

    // Preferencias de Notificaciones (para los toggles)
    $notifSettings = $user->notification_settings ?? $user->defaultNotificationSettings();
@endphp

<section x-data="{ 
    context: '{{ request()->query('team_id', '') }}', 
    teamConns: {{ json_encode($teamConns) }},
    teamTelegramIds: {{ json_encode($teamTelegramIds) }},
    userTelegramId: '{{ $user->telegram_chat_id }}',
    allPrefs: {{ $prefs->toJson() }},
    apiKey: '',
    aiModel: '',
    openSelector: false,
    availableModels: [],
    loadingModels: false,

    init() { 
        this.updateContext(); 
        this.$watch('apiKey', () => this.fetchModels());
        this.$watch('context', () => this.fetchModels());
    },
    
    updateContext() {
        const p = this.allPrefs[this.context || 'global'] || {};
        this.apiKey = p.api_key || '';
        this.aiModel = p.ai_model || '';
        this.fetchModels();
    },

    async fetchModels() {
        if (!this.apiKey || this.apiKey.length < 10) {
            this.availableModels = [];
            return;
        }
        
        this.loadingModels = true;
        try {
            const response = await fetch(`{{ route('ai.models') }}?team_id=${this.context}&api_key=${this.apiKey}`);
            const data = await response.json();
            this.availableModels = data.models || [];
        } catch (e) {
            console.error('Error fetching models:', e);
        } finally {
            this.loadingModels = false;
        }
    },

    setContext(val) {
        this.context = val;
        this.updateContext();
        this.openSelector = false;
    },

    getCurrentContextName() {
        if (this.context === '') return '🌍 Mi Configuración Global';
        @foreach($teams as $team)
            if (this.context == '{{ $team->id }}') return '👥 Equipo: {{ addslashes($team->name) }}';
        @endforeach
        return 'Seleccionar contexto';
    },

    isGoogleConnected() {
        // La conexión de Google SOLO existe vinculada a equipos
        if (!this.context) return false;
        return !!this.teamConns[this.context]?.connected;
    },

    getGoogleEmail() {
        if (!this.context) return '';
        return this.teamConns[this.context]?.email || '';
    },

    async testTelegram() {
        const chatId = '{{ $user->telegram_chat_id }}';
        if (!chatId) return;

        Swal.fire({
            title: 'Enviando prueba...',
            text: 'Ax.ia está enviando un mensaje a tu Telegram',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        try {
            const response = await fetch('{{ route('profile.telegram.test') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ chat_id: chatId })
            });
            const data = await response.json();
            
            if (data.success) {
                Swal.fire('¡Éxito!', data.message, 'success');
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (e) {
            Swal.fire('Error', 'No se pudo conectar con el servidor', 'error');
        }
    }
}">
    
    <div class="space-y-8">
        <!-- 🎯 SELECTOR DE CONTEXTO (Maestro absoluto) -->
        <div class="p-6 bg-gray-50 dark:bg-gray-800/50 rounded-3xl border border-gray-100 dark:border-gray-800 space-y-2 relative">
            <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 block px-1">Integraciones actuales del contexto:</label>
            
            <div class="relative">
                <div class="flex items-center gap-4 w-full">
                    <button @click="openSelector = !openSelector" type="button" class="flex-1 flex items-center justify-between text-left group">
                        <span x-text="getCurrentContextName()" class="text-xl font-bold tracking-tight text-gray-900 dark:text-white transition-colors group-hover:text-violet-600" style="font-family: 'Space Grotesk', sans-serif"></span>
                        <svg class="w-5 h-5 text-gray-400 transition-transform duration-300" :class="openSelector ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Botón de salto directo al equipo -->
                    <template x-if="context !== ''">
                        <a :href="'/teams/' + context + '/time-reports'" class="px-4 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-[10px] font-black uppercase text-gray-600 dark:text-gray-400 rounded-xl hover:bg-violet-50 dark:hover:bg-violet-900/20 hover:text-violet-600 dark:hover:text-violet-400 transition-all flex items-center gap-2 shadow-sm shrink-0">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7" /></svg>
                            Ir al equipo
                        </a>
                    </template>
                </div>

                <!-- Dropdown List -->
                <div x-show="openSelector" 
                     @click.outside="openSelector = false"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="absolute left-0 right-0 mt-4 p-2 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-3xl shadow-2xl z-50 overflow-hidden max-h-64 overflow-y-auto">
                    
                    <button @click="setContext('')" class="w-full text-left px-4 py-3 rounded-2xl hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-all flex items-center gap-3 group">
                        <span class="text-sm font-bold text-gray-600 dark:text-gray-400 group-hover:text-violet-600" style="font-family: 'Space Grotesk', sans-serif">🌍 Mi Configuración Global (IA)</span>
                    </button>

                    <div class="h-px bg-gray-50 dark:bg-gray-800 my-1"></div>

                    @foreach($teams as $team)
                        <button @click="setContext('{{ $team->id }}')" class="w-full text-left px-4 py-3 rounded-2xl hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-all flex items-center gap-3 group">
                            <span class="text-sm font-bold text-gray-600 dark:text-gray-400 group-hover:text-violet-600" style="font-family: 'Space Grotesk', sans-serif">👥 Equipo: {{ $team->name }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <!-- 📂 GOOGLE WORKSPACE (Sólo disponible en Equipos) -->
            <div x-show="context !== ''" x-transition class="p-6 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-3xl space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="p-2 bg-gray-50 dark:bg-gray-800 rounded-xl">
                            <svg class="w-8 h-8" viewBox="0 0 48 48">
                                <path fill="#FFC107" d="M17 6H11L2 22l3 5h6l9-16z"/><path fill="#2196F3" d="M37 42H11l-9-15 4-7h26l9 16z"/><path fill="#4CAF50" d="M15 6l9 16 9-16H15z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">Google Workspace</h4>
                            <p class="text-[10px] text-gray-400 font-medium" x-show="!isGoogleConnected()">Calendario, Drive y Tareas sincronizados</p>
                            <p class="text-[10px] text-emerald-500 font-bold" x-show="isGoogleConnected()" x-text="getGoogleEmail()"></p>
                        </div>
                    </div>

                    <div>
                        <template x-if="isGoogleConnected()">
                            <div class="flex items-center gap-3">
                                <a :href="'{{ route('google.sync') }}?team_id=' + context" class="px-4 py-2 bg-emerald-600 text-white text-[10px] font-black uppercase rounded-xl hover:scale-105 active:scale-95 transition-all shadow-md shadow-emerald-500/20 flex items-center gap-2">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                    Sincronizar / Importar
                                </a>
                                <div class="h-4 w-px bg-gray-100 dark:bg-gray-800 mx-1"></div>
                                <form method="POST" :action="'{{ route('google.disconnect') }}?team_id=' + context">
                                    @csrf
                                    <button type="submit" class="text-[10px] text-gray-400 hover:text-red-500 font-bold uppercase transition-colors">Desvincular</button>
                                </form>
                            </div>
                        </template>
                        <template x-if="!isGoogleConnected()">
                            <button @click="window.openGoogleAuth(context)" class="px-6 py-2 bg-violet-600 text-white text-[11px] font-black uppercase rounded-xl hover:scale-105 active:scale-95 transition-all shadow-md shadow-violet-500/20">
                                Vincular Equipo
                            </button>
                        </template>
                    </div>
                </div>
            </div>



            <!-- Aviso cuando está en global -->
            <div x-show="context === ''" class="p-6 bg-gray-50/50 dark:bg-gray-800/30 border border-dashed border-gray-200 dark:border-gray-700 rounded-3xl text-center">
                <p class="text-xs text-gray-500 font-medium">
                    Selecciona un equipo arriba para sincronizar archivos y calendarios de Google.
                </p>
            </div>

            <!-- 🤖 ASISTENTE AX.IA (IA - Disponible siempre) -->
            <form method="POST" action="{{ route('profile.ai.update') }}" class="p-6 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-3xl space-y-6">
                @csrf
                @method('PATCH')
                <input type="hidden" name="team_id" :value="context">
                
                <div class="flex items-center gap-4">
                    <div class="w-8 h-8 flex items-center justify-center bg-violet-100 dark:bg-violet-900/50 rounded-xl text-violet-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">Motor Gemini de <span x-text="context === '' ? 'Perfil' : 'Equipo'"></span></h4>
                        <p class="text-[10px] text-gray-400 font-medium">Configura la potencia de tu asistente Ax.ia</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest px-1">Clave API Gemini</label>
                        <input name="api_key" type="password" x-model="apiKey" class="w-full text-xs bg-gray-50 dark:bg-gray-800 border-none rounded-2xl focus:ring-2 focus:ring-violet-500" placeholder="••••••••••••••••">
                        <p class="text-[9px] text-gray-400 italic px-1" x-show="context !== ''">Dejádlo vacío para usar tu clave global</p>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between px-1">
                            <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest">Modelo Seleccionado</label>
                            <span x-show="loadingModels" class="flex h-2 w-2 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-violet-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-violet-500"></span>
                            </span>
                        </div>
                        <select name="ai_model" x-model="aiModel" class="w-full text-xs bg-gray-50 dark:bg-gray-800 border-none rounded-2xl focus:ring-2 focus:ring-violet-500">
                            <!-- Estado Sin Clave -->
                            <template x-if="!apiKey">
                                <option value="">⚠️ Primero introduce tu Clave API</option>
                            </template>

                            <!-- Estado Cargando -->
                            <template x-if="apiKey && loadingModels">
                                <option value="">⏳ Conectando con Google Gemini...</option>
                            </template>

                            <!-- Lista Dinámica (Si hay modelos detectados) -->
                            <template x-if="apiKey && !loadingModels && availableModels.length > 0">
                                <optgroup label="Modelos disponibles en tu cuenta">
                                    <template x-for="model in availableModels" :key="model.id">
                                        <option :value="model.id" x-text="model.display_name" :selected="aiModel === model.id"></option>
                                    </template>
                                </optgroup>
                            </template>

                            <!-- Error o Lista Vacía -->
                            <template x-if="apiKey && !loadingModels && availableModels.length === 0">
                                <option value="">❌ No se encontraron modelos (Revisa tu clave)</option>
                            </template>
                        </select>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 pt-4 border-t border-gray-50 dark:border-gray-800">
                    <div class="flex gap-6">
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="checkbox" name="mood_tracking_enabled" value="1" x-model="allPrefs[context || 'global']?.mood_tracking_enabled" class="w-5 h-5 rounded-lg border-none bg-gray-100 dark:bg-gray-800 text-violet-600 focus:ring-violet-500">
                            <span class="text-[10px] font-black text-gray-500 uppercase group-hover:text-violet-600 transition-colors">Energía</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="checkbox" name="smart_matching_opt_in" value="1" x-model="allPrefs[context || 'global']?.smart_matching_opt_in" class="w-5 h-5 rounded-lg border-none bg-gray-100 dark:bg-gray-800 text-violet-600 focus:ring-violet-500">
                            <span class="text-[10px] font-black text-gray-500 uppercase group-hover:text-violet-600 transition-colors">Smart Context</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="checkbox" name="apply_to_all" value="1" class="w-5 h-5 rounded-lg border-none bg-amber-100 dark:bg-amber-900/50 text-amber-600 focus:ring-amber-500">
                            <span class="text-[10px] font-black text-amber-600 uppercase group-hover:text-amber-700 transition-colors">💥 Aplicar a TODOS mis equipos</span>
                        </label>
                    </div>
                    <button type="submit" 
                        :disabled="loadingModels"
                        :class="loadingModels ? 'opacity-50 cursor-not-allowed' : 'hover:scale-105 active:scale-95'"
                        class="px-8 py-3 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-[10px] font-black uppercase rounded-2xl transition-all shadow-lg">
                        <span x-show="!loadingModels">Guardar Preferencias de IA</span>
                        <span x-show="loadingModels">Cargando Modelos...</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- 🔌 SISTEMAS DE CHAT (Habilitar / Deshabilitar) -->
        <div id="chat-systems" class="p-6 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-3xl space-y-4">
            <div class="flex items-center gap-4">
                <div class="p-2 bg-gray-50 dark:bg-gray-800 rounded-xl text-gray-500">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">Sistemas de Chat</h4>
                    <p class="text-[10px] text-gray-400 font-medium">Habilita o deshabilita los módulos de chat y widgets en la plataforma</p>
                </div>
            </div>

            <form method="POST" action="{{ route('profile.chat-integrations.update') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4" 
                  x-data="{ 
                      telegram: {{ ($notifSettings['telegram'] ?? false) ? 'true' : 'false' }}, 
                      whatsapp: {{ ($notifSettings['whatsapp'] ?? false) ? 'true' : 'false' }}, 
                      sync_chats: {{ ($notifSettings['sync_chats'] ?? false) ? 'true' : 'false' }} 
                  }" 
                  x-init="
                      $watch('telegram', v => { if(!v) { sync_chats = false; $nextTick(() => $el.submit()) } else { $nextTick(() => $el.submit()) } });
                      $watch('whatsapp', v => { if(!v) { sync_chats = false; $nextTick(() => $el.submit()) } else { $nextTick(() => $el.submit()) } });
                      $watch('sync_chats', v => { $nextTick(() => $el.submit()) });
                  ">
                @csrf
                @method('PATCH')
                <input type="hidden" name="tab" value="integrations">
                
                <!-- Interruptor Telegram -->
                <label class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700 rounded-2xl cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-all shadow-sm">
                    <span class="text-sm font-bold text-gray-700 dark:text-gray-300">Activar módulo Telegram</span>
                    <div class="relative inline-flex items-center">
                        <input type="hidden" name="telegram" value="0">
                        <input type="checkbox" name="telegram" value="1" x-model="telegram" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-300 dark:bg-gray-700 rounded-full peer peer-focus:ring-2 peer-focus:ring-sky-300 dark:peer-focus:ring-sky-800 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-sky-500"></div>
                    </div>
                </label>
                
                <!-- Interruptor WhatsApp -->
                <label class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700 rounded-2xl cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 transition-all shadow-sm">
                    <span class="text-sm font-bold text-gray-700 dark:text-gray-300">Activar módulo WhatsApp</span>
                    <div class="relative inline-flex items-center">
                        <input type="hidden" name="whatsapp" value="0">
                        <input type="checkbox" name="whatsapp" value="1" x-model="whatsapp" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-300 dark:bg-gray-700 rounded-full peer peer-focus:ring-2 peer-focus:ring-emerald-300 dark:peer-focus:ring-emerald-800 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-emerald-500"></div>
                    </div>
                </label>

                <!-- Interruptor Sincronizar Canales -->
                <label class="flex items-center justify-between p-4 border rounded-2xl transition-all shadow-sm"
                       :class="(!telegram || !whatsapp) ? 'opacity-40 bg-gray-100/50 dark:bg-gray-800/20 border-gray-100 dark:border-gray-800 cursor-not-allowed pointer-events-none' : 'bg-gray-50 dark:bg-gray-800/50 border-gray-100 dark:border-gray-700 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800'">
                    <span class="text-sm font-bold text-gray-700 dark:text-gray-300">Sincronizar canales</span>
                    <div class="relative inline-flex items-center">
                        <input type="hidden" name="sync_chats" value="0">
                        <input type="checkbox" name="sync_chats" value="1" x-model="sync_chats" :disabled="!telegram || !whatsapp" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-300 dark:bg-gray-700 rounded-full peer peer-focus:ring-2 peer-focus:ring-violet-300 dark:peer-focus:ring-violet-800 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-violet-500"></div>
                    </div>
                </label>
            </form>
        </div>

        @if($notifSettings['telegram'] ?? false)
        <!-- 📱 TELEGRAM NOTIFICATIONS (Global Maestro) -->
        <div x-transition class="p-6 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-3xl space-y-6">
            <form method="POST" action="{{ route('profile.notifications.update') }}" class="space-y-4">
                @csrf
                @method('PATCH')
                <input type="hidden" name="tab" value="integrations">

                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="p-2 bg-sky-50 dark:bg-sky-900/20 rounded-xl text-sky-500">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06.01.24 0 .38z"/></svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">Telegram Alerts</h4>
                            <p class="text-[10px] text-gray-400 font-medium">Recibe alertas críticas y resúmenes privados</p>
                        </div>
                    </div>
                    <template x-if="userTelegramId">
                        <span class="px-3 py-1 bg-sky-50 dark:bg-sky-900/20 text-sky-600 text-[10px] font-black uppercase rounded-lg border border-sky-100 dark:border-sky-800">Vinculado</span>
                    </template>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start bg-gray-50/50 dark:bg-gray-800/30 p-6 rounded-2xl border border-gray-100 dark:border-gray-800">
                    <div class="space-y-4">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest px-1">Tu Telegram Chat ID (Personal)</label>
                        <div class="space-y-3">
                            <input name="telegram_chat_id" type="text" value="{{ $user->telegram_chat_id }}" 
                                class="w-full text-sm bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-sky-500 py-2.5 px-4 shadow-sm" placeholder="Ej: 123456789">
                            <button type="submit" class="w-full py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-[10px] font-black uppercase rounded-xl hover:scale-[1.02] transition-all shadow-md active:scale-95">
                                Actualizar ID Personal
                            </button>
                        </div>
                        <p class="text-[9px] text-gray-400 italic px-1">Esta configuración es única para tu perfil y afecta a tus resúmenes matutinos.</p>
                    </div>
                    <div class="space-y-3 border-t md:border-t-0 md:border-l border-gray-100 dark:border-gray-800 pt-4 md:pt-0 md:pl-8">
                        <h5 class="text-[10px] font-black uppercase text-gray-400 tracking-widest px-1">Instrucciones</h5>
                        <ul class="space-y-2">
                            <li class="flex items-start gap-3">
                                <span class="w-5 h-5 rounded-full bg-sky-100 dark:bg-sky-900/40 text-sky-600 flex items-center justify-center text-[10px] font-bold flex-shrink-0 mt-0.5">1</span>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Busca a <strong class="text-sky-600">@SientiaBot</strong> en Telegram.</p>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-5 h-5 rounded-full bg-sky-100 dark:bg-sky-900/40 text-sky-600 flex items-center justify-center text-[10px] font-bold flex-shrink-0 mt-0.5">2</span>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Envíale <code class="bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded text-sky-600 font-bold">/start</code> para obtener tu ID.</p>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-5 h-5 rounded-full bg-sky-100 dark:bg-sky-900/40 text-sky-600 flex items-center justify-center text-[10px] font-bold flex-shrink-0 mt-0.5">3</span>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Pega el número recibido en el campo de la izquierda y guarda.</p>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="pt-2 border-t border-gray-50 dark:border-gray-800 flex justify-end">
                    <button type="button" @click="testTelegram()" 
                        class="text-[10px] font-black text-sky-600 uppercase hover:text-sky-700 transition-colors flex items-center gap-1.5">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        Probar mi conexión
                    </button>
                </div>
            </form>
        </div>
        @endif

        @if(($notifSettings['whatsapp'] ?? false) && (auth()->user()->notification_settings['whatsapp_personal_allowed'] ?? false))
        <!-- 🟢 WHATSAPP PERSONAL (Vinculación Individual Premium) -->
        <div x-data="{
                ready: false,
                qr: null,
                loading: false,
                initSession: false,
                pollingInterval: null,
                async checkStatus() {
                    try {
                        const url = '{{ route('whatsapp.personal-status') }}' + (this.initSession ? '?init=true' : '');
                        const response = await fetch(url);
                        const data = await response.json();
                        this.ready = data.ready;
                        this.qr = data.qr;
                        if (this.ready) {
                            this.initSession = false;
                        }
                    } catch (e) {
                        console.error('Error consultando estado de WhatsApp Personal:', e);
                    }
                },
                startPolling() {
                    this.loading = true;
                    this.checkStatus();
                    this.pollingInterval = setInterval(() => {
                        this.checkStatus();
                    }, 3000);
                },
                stopPolling() {
                    if (this.pollingInterval) {
                        clearInterval(this.pollingInterval);
                        this.pollingInterval = null;
                    }
                    this.loading = false;
                },
                async startConnection() {
                    this.initSession = true;
                    await this.checkStatus();
                },
                async restartSession() {
                    if (!confirm('¿Deseas desvincular o reiniciar tu cuenta de WhatsApp Personal?')) return;
                    try {
                        await fetch('{{ route('whatsapp.personal-restart') }}', {
                            method: 'POST',
                            headers: { 
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        this.ready = false;
                        this.qr = null;
                        this.initSession = true;
                        this.startPolling();
                    } catch (e) {
                        console.error('Error al reiniciar sesión:', e);
                    }
                }
             }"
             x-init="checkStatus(); startPolling()"
             x-on:destroy="stopPolling()"
             class="p-6 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-3xl space-y-4">
            
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="p-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl text-emerald-500">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span>Mi WhatsApp Personal</span>
                            <span class="px-2 py-0.5 bg-gradient-to-r from-amber-500 to-orange-500 text-white text-[8px] font-black uppercase rounded-full">Premium</span>
                        </h4>
                        <p class="text-[10px] text-gray-400 font-medium">Vincula tu propio número de WhatsApp móvil privado de forma aislada</p>
                    </div>
                </div>
                <div>
                    <template x-if="ready">
                        <span class="px-3 py-1 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 text-[10px] font-black uppercase rounded-lg border border-emerald-100 dark:border-emerald-800">Conectado</span>
                    </template>
                    <template x-if="!ready && qr">
                        <span class="px-3 py-1 bg-amber-50 dark:bg-amber-900/20 text-amber-600 text-[10px] font-black uppercase rounded-lg border border-amber-100 dark:border-amber-800 animate-pulse">Esperando Escaneo</span>
                    </template>
                    <template x-if="!ready && !qr">
                        <span class="px-3 py-1 bg-gray-50 dark:bg-gray-800 text-gray-400 text-[10px] font-black uppercase rounded-lg border border-gray-200 dark:border-gray-700">Desconectado</span>
                    </template>
                </div>
            </div>

            <!-- Interfaz de Conexión en Caja de Coherencia Gris idéntica a Telegram -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center bg-gray-50/50 dark:bg-gray-800/30 p-6 rounded-2xl border border-gray-100 dark:border-gray-800">
                <div class="space-y-4">
                    <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                        Al vincular tu WhatsApp personal, podrás enviar y recibir mensajes directamente con tu número privado en las alertas automatizadas y chats autorizados. Toda la comunicación está encriptada y aislada en tu perfil.
                    </p>
                    
                    <div class="flex gap-3 pt-2">
                        <!-- Botón de Conexión Pasiva -->
                        <template x-if="!ready && !qr && !initSession">
                            <button @click="startConnection()" type="button" class="px-6 py-2.5 bg-emerald-500 hover:bg-emerald-600 active:scale-95 text-white text-[10px] font-black uppercase rounded-xl transition-all shadow-md shadow-emerald-500/10">
                                Vincular mi WhatsApp
                            </button>
                        </template>

                        <!-- Botón de Desvinculación -->
                        <template x-if="ready || qr || initSession">
                            <button @click="restartSession()" type="button" class="px-4 py-2 bg-red-50 hover:bg-red-100 dark:bg-red-950/20 dark:hover:bg-red-900/30 text-red-600 text-[10px] font-black uppercase rounded-xl transition-all">
                                Desvincular Cuenta
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Mostrar QR o Estado Activo -->
                <div class="flex justify-center md:justify-end">
                    <!-- Estado Conectado -->
                    <template x-if="ready">
                        <div class="p-6 bg-emerald-50/50 dark:bg-emerald-900/10 border border-emerald-100/30 rounded-2xl flex flex-col items-center text-center max-w-xs w-full">
                            <div class="w-16 h-16 bg-emerald-500 rounded-full flex items-center justify-center text-white mb-3 shadow-lg shadow-emerald-500/20">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs font-bold text-emerald-800 dark:text-emerald-400">¡Sincronización Activa!</span>
                            <span class="text-[9px] text-gray-400 mt-1">Tu WhatsApp personal ya está plenamente enlazado.</span>
                        </div>
                    </template>

                    <!-- Estado Esperando Escaneo (Muestra el QR) -->
                    <template x-if="!ready && qr">
                        <div class="p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl flex flex-col items-center max-w-xs shadow-inner w-full">
                            <img :src="qr" class="w-48 h-48 rounded-xl" alt="QR WhatsApp Personal">
                            <span class="text-[9px] font-black text-gray-500 uppercase tracking-widest mt-3 text-center">Escanea desde WhatsApp Móvil<br><span class="text-gray-400 font-medium">Dispositivos vinculados > Vincular un dispositivo</span></span>
                        </div>
                    </template>

                    <!-- Estado Pasivo Desconectado -->
                    <template x-if="!ready && !qr && !initSession">
                        <div class="p-6 bg-gray-50/50 dark:bg-gray-800/30 rounded-2xl flex flex-col items-center justify-center max-w-xs text-center border border-dashed border-gray-200 dark:border-gray-700 w-full min-h-[14rem]">
                            <div class="w-12 h-12 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center text-gray-400 mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                            </div>
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">WhatsApp Desconectado</span>
                            <span class="text-[8px] text-gray-400 mt-1 px-4">Pulsa el botón "Vincular" para iniciar el navegador y generar tu código QR de acceso.</span>
                        </div>
                    </template>

                    <!-- Estado Cargando / Generando sesión en segundo plano -->
                    <template x-if="!ready && !qr && initSession">
                        <div class="p-6 bg-gray-50 dark:bg-gray-800 rounded-2xl flex flex-col items-center justify-center max-w-xs text-center border border-dashed border-gray-200 dark:border-gray-700 w-full min-h-[14rem]">
                            <svg class="w-8 h-8 text-gray-400 animate-spin mb-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Iniciando Puppeteer...</span>
                            <span class="text-[8px] text-gray-400 mt-1">Generando canal multi-sesión aislado en segundo plano</span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
        @endif



        @if($notifSettings['whatsapp'] ?? false)
        <!-- 🟢 WHATSAPP WEB BRIDGE (Global Maestro - Con Autorefresco en Tiempo Real) -->
        <div id="whatsapp-bridge" x-transition x-data="{
            ready: {{ ($whatsappStatus['ready'] ?? false) ? 'true' : 'false' }},
            qr: '{{ $whatsappStatus['qr'] ?? '' }}',
            serviceAvailable: {{ (isset($whatsappStatus['ready']) || isset($whatsappStatus['qr'])) ? 'true' : 'false' }},
            pollInterval: null,
            
            init() {
                this.startPolling();
            },
            startPolling() {
                if (this.pollInterval) clearInterval(this.pollInterval);
                this.pollInterval = setInterval(() => this.checkStatus(), 5000);
            },
            destroy() {
                if (this.pollInterval) clearInterval(this.pollInterval);
            },
            async checkStatus() {
                try {
                    const res = await fetch('{{ route('whatsapp.status') }}');
                    if (res.ok) {
                        const data = await res.json();
                        this.serviceAvailable = true;
                        this.ready = !!data.ready;
                        this.qr = data.qr || '';
                    } else {
                        this.serviceAvailable = false;
                    }
                } catch(e) {
                    this.serviceAvailable = false;
                    console.error('Error polling WhatsApp status:', e);
                }
            },
            async restartClient(btn) {
                const oldText = btn.innerText;
                btn.disabled = true;
                btn.innerText = 'Reiniciando...';
                try {
                    await fetch('{{ route('whatsapp.restart') }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    });
                    this.qr = '';
                    this.ready = false;
                    setTimeout(() => this.checkStatus(), 2000);
                } catch(e) {
                    console.error('Error al reiniciar el cliente:', e);
                } finally {
                    setTimeout(() => {
                        btn.disabled = false;
                        btn.innerText = oldText;
                    }, 4000);
                }
            }
        }" class="p-6 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-3xl space-y-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-4">
                    <div class="p-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl text-emerald-500">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.35-.01-1.02-.2-1.53-.37-.6-.2-1.07-.31-1.03-.66.02-.18.27-.36.75-.55 2.94-1.28 4.9-2.13 5.88-2.54 2.8-.1.5.15.5.99c.01.26-.01.52-.06.78z"/></svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">WhatsApp Bridge</h4>
                        <p class="text-[10px] text-gray-400 font-medium">Vincula WhatsApp para recibir y enviar mensajes desde Sientia</p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50/50 dark:bg-gray-800/30 p-6 rounded-2xl border border-gray-100 dark:border-gray-800">
                <!-- 1. Servicio no disponible -->
                <div x-show="!serviceAvailable" class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-500 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <div>
                        <h5 class="text-sm font-bold text-gray-900 dark:text-white">Servicio no disponible</h5>
                        <p class="text-xs text-gray-500 mt-1">Parece que el servicio interno (Node.js) no está corriendo en el servidor. Contacta al administrador para que inicie el <code>whatsapp-service</code>.</p>
                    </div>
                </div>

                <!-- 2. ¡WhatsApp Conectado! -->
                <div x-show="serviceAvailable && ready" class="flex items-start gap-4" style="display: none;">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <div>
                        <h5 class="text-sm font-bold text-gray-900 dark:text-white">¡WhatsApp Conectado!</h5>
                        <p class="text-xs text-gray-500 mt-1">El bot está listo y escuchando mensajes en tiempo real. Ahora puedes configurar los grupos o chats en las opciones de cada Equipo.</p>
                    </div>
                </div>

                <!-- 3. Requiere vinculación (QR activo) -->
                <div x-show="serviceAvailable && !ready && qr" class="flex flex-col md:flex-row gap-6 items-start" style="display: none;">
                    <div class="flex-1">
                        <h5 class="text-sm font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                            Requiere vinculación
                        </h5>
                        <ul class="space-y-2">
                            <li class="flex items-start gap-3">
                                <span class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 flex items-center justify-center text-[10px] font-bold flex-shrink-0 mt-0.5">1</span>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Abre WhatsApp en tu teléfono personal o de empresa.</p>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 flex items-center justify-center text-[10px] font-bold flex-shrink-0 mt-0.5">2</span>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Ve a <strong>Configuración > Dispositivos vinculados</strong>.</p>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 flex items-center justify-center text-[10px] font-bold flex-shrink-0 mt-0.5">3</span>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Escanea el código QR que ves a la derecha.</p>
                            </li>
                        </ul>
                        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                            <p class="text-[10px] text-gray-400 italic">El QR se refresca automáticamente cada 5 segundos.</p>
                            <button type="button" @click="restartClient($event.target)" class="mt-2 text-[10px] font-black text-emerald-600 uppercase hover:text-emerald-700 transition-colors flex items-center gap-1 disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                Generar nuevo QR
                            </button>
                        </div>
                    </div>
                    <div class="w-full md:w-auto flex justify-center">
                        <div class="p-3 bg-white rounded-2xl shadow-xl border border-gray-100 inline-block">
                            <img :src="qr" alt="WhatsApp QR Code" class="w-48 h-48 md:w-56 md:h-56 object-contain">
                        </div>
                    </div>
                </div>

                <!-- 4. Iniciando el cliente (sin QR todavía) -->
                <div x-show="serviceAvailable && !ready && !qr" class="flex items-start gap-4" style="display: none;">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-500 shrink-0">
                        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </div>
                    <div>
                        <h5 class="text-sm font-bold text-gray-900 dark:text-white">Generando código QR...</h5>
                        <p class="text-xs text-gray-500 mt-1">El servicio está procesando la solicitud en segundo plano. El código QR aparecerá aquí automáticamente en unos instantes sin necesidad de recargar.</p>
                    </div>
                </div>
            </div>
        </div>
        @endif

    </div>
</section>
