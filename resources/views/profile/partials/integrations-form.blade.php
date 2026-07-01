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

    // Estructurar datos para Alpine
    $contextsData = [
        'global' => [
            'id' => '',
            'name' => 'Mi Configuración Global (Base)',
            'type' => 'global',
            'api_key' => $prefs->get('global')->api_key ?? '',
            'ai_model' => $prefs->get('global')->ai_model ?? '',
            'mood_tracking_enabled' => (bool)($prefs->get('global')->mood_tracking_enabled ?? false),
            'smart_matching_opt_in' => (bool)($prefs->get('global')->smart_matching_opt_in ?? false),
            'google_connected' => false,
            'google_email' => '',
        ]
    ];

    foreach($teams as $team) {
        $p = $prefs->get($team->id);
        $contextsData[$team->id] = [
            'id' => $team->id,
            'name' => $team->name,
            'type' => 'team',
            'api_key' => $p->api_key ?? '',
            'ai_model' => $p->ai_model ?? '',
            'mood_tracking_enabled' => (bool)($p->mood_tracking_enabled ?? false),
            'smart_matching_opt_in' => (bool)($p->smart_matching_opt_in ?? false),
            'google_connected' => !empty($team->pivot->google_token),
            'google_email' => $team->pivot->google_email ?? '',
        ];
    }
@endphp

<section class="space-y-12" x-data="{
    contexts: {{ json_encode($contextsData) }},
    activeContextId: null,
    showModal: false,
    loadingModels: false,
    availableModels: [],
    form: {
        team_id: '',
        api_key: '',
        ai_model: '',
        mood_tracking_enabled: false,
        smart_matching_opt_in: false,
        apply_to_all: false
    },
    
    openModal(id) {
        this.activeContextId = id;
        const current = this.contexts[id];
        this.form.team_id = current.id;
        this.form.api_key = current.api_key || '';
        this.form.ai_model = current.ai_model || '';
        this.form.mood_tracking_enabled = !!current.mood_tracking_enabled;
        this.form.smart_matching_opt_in = !!current.smart_matching_opt_in;
        this.form.apply_to_all = false;
        this.showModal = true;
        this.fetchModels();
    },

    closeModal() {
        this.showModal = false;
        this.activeContextId = null;
    },

    async fetchModels() {
        const keyToUse = this.form.api_key || this.contexts['global'].api_key || '';
        if (!keyToUse || keyToUse.length < 10) {
            this.availableModels = [];
            return;
        }
        this.loadingModels = true;
        try {
            const response = await fetch(`{{ route('ai.models') }}?team_id=${this.form.team_id}&api_key=${keyToUse}`);
            const data = await response.json();
            this.availableModels = data.models || [];
        } catch (e) {
            console.error('Error fetching models:', e);
        } finally {
            this.loadingModels = false;
        }
    },

    getActiveContext() {
        return this.contexts[this.activeContextId] || null;
    }
}">

    <!-- Encabezado de la sección -->
    <div class="border-b border-gray-200 dark:border-gray-800 pb-5">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <span class="p-2 bg-violet-100 dark:bg-violet-900/40 text-violet-600 dark:text-violet-400 rounded-xl">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z" />
                </svg>
            </span>
            <span>Listado General de Integraciones e IA (CRUD)</span>
        </h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Resumen unificado de tus credenciales de IA (Google Gemini) y enlaces con Google Workspace. Haz clic en <strong class="text-gray-700 dark:text-gray-300">"Configurar"</strong> en cualquier elemento para gestionar sus claves y preferencias.
        </p>
    </div>

    <!-- ================= 📊 TABLA CRUD COMPACTA ================= -->
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30 text-[10px] font-black uppercase text-gray-400 tracking-widest">
                        <th class="py-4 px-6">Contexto / Espacio</th>
                        <th class="py-4 px-6">Google Workspace</th>
                        <th class="py-4 px-6">Modelo Gemini (IA)</th>
                        <th class="py-4 px-6">Toggles Activos</th>
                        <th class="py-4 px-6 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-xs text-gray-600 dark:text-gray-300">
                    
                    <!-- 1. FILA GLOBAL -->
                    <tr class="hover:bg-violet-50/20 dark:hover:bg-violet-900/10 transition-colors">
                        <td class="py-4 px-6 font-bold text-gray-900 dark:text-white flex items-center gap-3">
                            <span class="text-xl">🌍</span>
                            <div>
                                <span class="block text-sm" style="font-family: 'Space Grotesk', sans-serif">Mi Configuración Global</span>
                                <span class="text-[9px] text-violet-600 dark:text-violet-400 font-black uppercase tracking-wider">Perfil Base Maestro</span>
                            </div>
                        </td>
                        <td class="py-4 px-6 text-gray-400 italic text-[11px]">
                            <span class="px-2.5 py-1 bg-gray-100 dark:bg-gray-800 text-gray-500 rounded-lg text-[10px] font-bold">No aplicable a global</span>
                        </td>
                        <td class="py-4 px-6 font-medium">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full {{ ($contextsData['global']['api_key'] ?? '') ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                <span class="font-mono text-[11px] {{ ($contextsData['global']['ai_model'] ?? '') ? 'text-violet-600 dark:text-violet-400 font-bold' : 'text-gray-400' }}">
                                    {{ ($contextsData['global']['ai_model'] ?? '') ?: 'gemini-1.5-flash (Por defecto)' }}
                                </span>
                            </div>
                        </td>
                        <td class="py-4 px-6">
                            <div class="flex gap-1.5">
                                <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase {{ $contextsData['global']['mood_tracking_enabled'] ? 'bg-violet-100 text-violet-600 dark:bg-violet-900/40 dark:text-violet-400' : 'bg-gray-100 text-gray-400 dark:bg-gray-800' }}">Energía</span>
                                <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase {{ $contextsData['global']['smart_matching_opt_in'] ? 'bg-violet-100 text-violet-600 dark:bg-violet-900/40 dark:text-violet-400' : 'bg-gray-100 text-gray-400 dark:bg-gray-800' }}">Smart</span>
                            </div>
                        </td>
                        <td class="py-4 px-6 text-right space-x-2">
                            <button @click="openModal('global')" type="button" class="px-4 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-black uppercase text-[10px] rounded-xl hover:scale-105 active:scale-95 transition-all shadow-sm flex items-center gap-1.5 inline-flex">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                Configurar
                            </button>
                        </td>
                    </tr>

                    <!-- 2. FILAS DE EQUIPOS -->
                    @foreach($teams as $team)
                        @php
                            $c = $contextsData[$team->id];
                        @endphp
                        <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-800/40 transition-colors">
                            <td class="py-4 px-6 font-bold text-gray-900 dark:text-white flex items-center gap-3">
                                <div class="w-8 h-8 rounded-xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-xs font-bold text-gray-700 dark:text-gray-300 shadow-inner shrink-0">
                                    {{ mb_substr($team->name, 0, 2) }}
                                </div>
                                <div>
                                    <span class="block text-sm" style="font-family: 'Space Grotesk', sans-serif">{{ $team->name }}</span>
                                    <span class="text-[9px] text-gray-400 font-medium uppercase tracking-wider">Espacio de Equipo</span>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                @if($c['google_connected'])
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 rounded-lg text-[11px] font-bold border border-emerald-100 dark:border-emerald-800">
                                        <svg class="w-3 h-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        <span>{{ $c['google_email'] }}</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-100 dark:bg-gray-800 text-gray-500 rounded-lg text-[10px] font-medium">
                                        Sin vincular
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6 font-medium">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full {{ $c['api_key'] ? 'bg-violet-500' : 'bg-gray-300 dark:bg-gray-700' }}"></span>
                                    <span class="font-mono text-[11px] {{ $c['ai_model'] ? 'text-violet-600 dark:text-violet-400 font-bold' : 'text-gray-400' }}">
                                        {{ $c['ai_model'] ?: 'Heredado (Global)' }}
                                    </span>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex gap-1.5">
                                    <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase {{ $c['mood_tracking_enabled'] ? 'bg-violet-100 text-violet-600 dark:bg-violet-900/40 dark:text-violet-400' : 'bg-gray-100 text-gray-400 dark:bg-gray-800' }}">Energía</span>
                                    <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase {{ $c['smart_matching_opt_in'] ? 'bg-violet-100 text-violet-600 dark:bg-violet-900/40 dark:text-violet-400' : 'bg-gray-100 text-gray-400 dark:bg-gray-800' }}">Smart</span>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-right space-x-2 shrink-0">
                                <a href="{{ url('/teams/' . $team->id . '/time-reports') }}" class="px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-[10px] font-black uppercase text-gray-600 dark:text-gray-400 rounded-xl hover:bg-violet-50 dark:hover:bg-violet-900/20 hover:text-violet-600 dark:hover:text-violet-400 transition-all inline-flex items-center gap-1 shadow-sm">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 5l7 7-7 7" /></svg>
                                    Equipo
                                </a>
                                <button @click="openModal('{{ $team->id }}')" type="button" class="px-4 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-black uppercase text-[10px] rounded-xl hover:scale-105 active:scale-95 transition-all shadow-sm flex items-center gap-1.5 inline-flex">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    Configurar
                                </button>
                            </td>
                        </tr>
                    @endforeach

                </tbody>
            </table>
        </div>
    </div>

    <!-- ================= 🛠️ MODAL DE EDICIÓN (CRUD) ================= -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-gray-900/60 backdrop-blur-sm" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div @click.outside="closeModal()" class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-[2.5rem] shadow-2xl max-w-2xl w-full overflow-hidden flex flex-col max-h-[90vh]" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-8 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100">
            
            <!-- Header del Modal -->
            <div class="p-6 sm:p-8 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between gap-4 bg-gray-50/50 dark:bg-gray-800/30">
                <div class="flex items-center gap-4">
                    <span class="text-3xl" x-text="getActiveContext()?.type === 'global' ? '🌍' : '👥'"></span>
                    <div>
                        <h4 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight" style="font-family: 'Space Grotesk', sans-serif" x-text="'Configurar: ' + (getActiveContext()?.name || '')"></h4>
                        <p class="text-xs text-gray-400 font-medium" x-text="getActiveContext()?.type === 'global' ? 'Preferencias globales de tu cuenta' : 'Ajustes dedicados de integración para este equipo'"></p>
                    </div>
                </div>
                <button @click="closeModal()" type="button" class="w-10 h-10 rounded-2xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700 transition-all">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <!-- Body del Modal (Scrollable) -->
            <div class="p-6 sm:p-8 overflow-y-auto space-y-8 flex-1">
                
                <!-- Tarjeta Google Workspace (Sólo en Modal de Equipo) -->
                <div x-show="getActiveContext()?.type === 'team'" class="p-6 bg-gray-50/80 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-800 rounded-3xl space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="p-2.5 bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 shrink-0">
                                <svg class="w-7 h-7" viewBox="0 0 48 48">
                                    <path fill="#FFC107" d="M17 6H11L2 22l3 5h6l9-16z"/><path fill="#2196F3" d="M37 42H11l-9-15 4-7h26l9 16z"/><path fill="#4CAF50" d="M15 6l9 16 9-16H15z"/>
                                </svg>
                            </div>
                            <div>
                                <h5 class="text-sm font-bold text-gray-900 dark:text-white">Google Workspace</h5>
                                <p x-show="getActiveContext()?.google_connected" class="text-[11px] text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1 mt-0.5">
                                    <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    <span x-text="getActiveContext()?.google_email"></span>
                                </p>
                                <p x-show="!getActiveContext()?.google_connected" class="text-[11px] text-gray-400 font-medium mt-0.5">Sin vincular con Google Drive / Calendar</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <div x-show="getActiveContext()?.google_connected" class="flex items-center gap-3">
                                <a :href="'{{ route('google.sync') }}?team_id=' + (getActiveContext()?.id || '')" class="px-4 py-2.5 bg-emerald-600 text-white text-[10px] font-black uppercase rounded-xl hover:scale-105 active:scale-95 transition-all shadow-md shadow-emerald-500/20 flex items-center gap-2">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                    Sincronizar
                                </a>
                                <div class="h-4 w-px bg-gray-200 dark:bg-gray-700 mx-1"></div>
                                <form method="POST" :action="'{{ route('google.disconnect') }}?team_id=' + (getActiveContext()?.id || '')">
                                    @csrf
                                    <button type="submit" class="text-[10px] text-gray-400 hover:text-rose-600 font-bold uppercase transition-colors">Desvincular</button>
                                </form>
                            </div>
                            <button x-show="!getActiveContext()?.google_connected" @click="window.openGoogleAuth(getActiveContext()?.id)" type="button" class="px-6 py-2.5 bg-violet-600 text-white text-[11px] font-black uppercase rounded-xl hover:scale-105 active:scale-95 transition-all shadow-md shadow-violet-500/20">
                                Vincular Google Workspace
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Formulario IA Gemini -->
                <form method="POST" action="{{ route('profile.ai.update') }}" id="modal-ai-form" class="space-y-6">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="team_id" :value="form.team_id">

                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 flex items-center justify-center bg-violet-100 dark:bg-violet-900/50 rounded-xl text-violet-600 shrink-0">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        </div>
                        <div>
                            <h5 class="text-sm font-bold text-gray-900 dark:text-white">Motor IA Gemini</h5>
                            <p class="text-[10px] text-gray-400 font-medium">Ajusta la potencia y el modelo cognitivo para este espacio</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest px-1">Clave API Gemini</label>
                            <input name="api_key" type="password" x-model="form.api_key" @input.debounce.500ms="fetchModels()" class="w-full text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl focus:ring-2 focus:ring-violet-500 shadow-inner" placeholder="••••••••••••••••">
                            <p class="text-[10px] text-gray-400 italic px-1" x-text="getActiveContext()?.type === 'global' ? 'Clave maestra de Google Gemini AI para tu perfil.' : 'Si lo dejas vacío, se utilizará tu Clave Global.'"></p>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between px-1">
                                <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest">Modelo Seleccionado</label>
                                <div class="flex items-center gap-2">
                                    <span x-show="loadingModels" class="flex h-2 w-2 relative">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-violet-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-violet-500"></span>
                                    </span>
                                    <button type="button" @click="fetchModels()" :disabled="loadingModels" class="flex items-center gap-1 px-2 py-0.5 rounded-lg text-gray-400 hover:text-violet-600 transition-all text-[9px] font-black uppercase tracking-widest">
                                        <svg class="w-2.5 h-2.5" :class="loadingModels ? 'animate-spin' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        Actualizar
                                    </button>
                                </div>
                            </div>
                            <select name="ai_model" x-model="form.ai_model" class="w-full text-xs bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl focus:ring-2 focus:ring-violet-500 shadow-inner">
                                <option x-show="getActiveContext()?.type === 'team'" value="">🌐 Usar modelo por defecto / Global</option>
                                <option x-show="getActiveContext()?.type === 'global' && !form.api_key" value="">⚠️ Introduce una clave para cargar modelos</option>
                                <option x-show="loadingModels" value="">⏳ Conectando con Google Gemini...</option>
                                <optgroup x-show="!loadingModels && availableModels.length > 0" label="Modelos disponibles">
                                    <template x-for="model in availableModels" :key="model.id">
                                        <option :value="model.id" x-text="model.display_name" :selected="form.ai_model === model.id"></option>
                                    </template>
                                </optgroup>
                                <option x-show="!loadingModels && availableModels.length === 0 && (form.api_key || getActiveContext()?.type === 'team')" value="">⚠️ No se encontraron modelos o usa clave global</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 pt-4 border-t border-gray-100 dark:border-gray-800">
                        <div class="flex flex-wrap gap-6">
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" name="mood_tracking_enabled" value="1" x-model="form.mood_tracking_enabled" class="w-5 h-5 rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-violet-600 focus:ring-violet-500">
                                <span class="text-[10px] font-black text-gray-500 uppercase group-hover:text-violet-600 transition-colors">Energía</span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" name="smart_matching_opt_in" value="1" x-model="form.smart_matching_opt_in" class="w-5 h-5 rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-violet-600 focus:ring-violet-500">
                                <span class="text-[10px] font-black text-gray-500 uppercase group-hover:text-violet-600 transition-colors">Smart Context</span>
                            </label>
                            <div x-show="getActiveContext()?.type === 'global'">
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input type="checkbox" name="apply_to_all" value="1" x-model="form.apply_to_all" class="w-5 h-5 rounded-lg border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/30 text-amber-600 focus:ring-amber-500">
                                    <span class="text-[10px] font-black text-amber-600 uppercase group-hover:text-amber-700 transition-colors">💥 Aplicar a TODOS mis equipos</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Footer del Modal -->
            <div class="p-6 sm:p-8 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30 flex items-center justify-end gap-4">
                <button @click="closeModal()" type="button" class="px-6 py-3 bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-black uppercase text-[10px] rounded-2xl hover:bg-gray-300 dark:hover:bg-gray-700 transition-all">
                    Cancelar
                </button>
                <button @click="document.getElementById('modal-ai-form').submit()" type="button" :disabled="loadingModels" :class="loadingModels ? 'opacity-50 cursor-not-allowed' : 'hover:scale-105 active:scale-95'" class="px-8 py-3 bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-black uppercase text-[10px] rounded-2xl transition-all shadow-lg">
                    <span x-show="!loadingModels">Guardar Cambios</span>
                    <span x-show="loadingModels">Cargando...</span>
                </button>
            </div>
        </div>
    </div>
</section>

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

            <form method="POST" action="{{ route('profile.chat-integrations.update') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-6 mt-4" 
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
                <label class="flex items-center justify-between p-5 bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700 rounded-3xl cursor-pointer hover:bg-white dark:hover:bg-gray-800 transition-all duration-300 shadow-sm hover:shadow-md gap-4 group">
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-sky-50 dark:bg-sky-900/30 flex items-center justify-center text-sky-500 shrink-0 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <span class="text-sm font-bold text-gray-800 dark:text-gray-200 leading-tight block">Telegram</span>
                            <span class="text-[10px] text-gray-400 font-medium block mt-0.5">Alertas y Chats</span>
                        </div>
                    </div>
                    <div class="relative inline-flex items-center flex-shrink-0">
                        <input type="hidden" name="telegram" value="0">
                        <input type="checkbox" name="telegram" value="1" x-model="telegram" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-300 dark:bg-gray-700 rounded-full peer peer-focus:ring-2 peer-focus:ring-sky-300 dark:peer-focus:ring-sky-800 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-sky-500"></div>
                    </div>
                </label>
                
                <!-- Interruptor WhatsApp -->
                @php
                    $whatsappAllowed = $notifSettings['whatsapp_personal_allowed'] ?? false;
                @endphp
                <label class="flex items-center justify-between p-5 bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700 rounded-3xl transition-all duration-300 shadow-sm hover:shadow-md gap-4 group {{ $whatsappAllowed ? 'cursor-pointer hover:bg-white dark:hover:bg-gray-800' : 'opacity-60 cursor-not-allowed' }}">
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-500 shrink-0 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                        </div>
                        <div class="flex-1 min-w-0 flex flex-col">
                            <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                <span class="text-sm font-bold text-gray-800 dark:text-gray-200 leading-tight">WhatsApp</span>
                                @if(!$whatsappAllowed)
                                    <span class="flex-shrink-0 px-2 py-0.5 bg-gradient-to-r from-amber-500 to-orange-500 text-white text-[7px] font-black uppercase rounded-full shadow-sm">VIP</span>
                                @endif
                            </div>
                            <span class="text-[10px] text-gray-400 font-medium truncate">Módulo Personal</span>
                        </div>
                    </div>
                    <div class="relative inline-flex items-center flex-shrink-0">
                        <input type="hidden" name="whatsapp" value="0">
                        <input type="checkbox" name="whatsapp" value="1" x-model="whatsapp" {{ !$whatsappAllowed ? 'disabled' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-300 dark:bg-gray-700 rounded-full peer peer-focus:ring-2 peer-focus:ring-emerald-300 dark:peer-focus:ring-emerald-800 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-emerald-500"></div>
                    </div>
                </label>

                <!-- Interruptor Sincronizar Canales -->
                <label class="flex items-center justify-between p-5 border rounded-3xl transition-all duration-300 shadow-sm hover:shadow-md gap-4 group"
                       :class="(!telegram || !whatsapp) ? 'opacity-40 bg-gray-100/50 dark:bg-gray-800/20 border-gray-100 dark:border-gray-800 cursor-not-allowed pointer-events-none' : 'bg-gray-50 dark:bg-gray-800/50 border-gray-100 dark:border-gray-700 cursor-pointer hover:bg-white dark:hover:bg-gray-800'">
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-violet-50 dark:bg-violet-900/30 flex items-center justify-center text-violet-500 shrink-0 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <span class="text-sm font-bold text-gray-800 dark:text-gray-200 leading-tight block">Sincronización</span>
                            <span class="text-[10px] text-gray-400 font-medium block mt-0.5">Puente Unificado</span>
                        </div>
                    </div>
                    <div class="relative inline-flex items-center flex-shrink-0">
                        <input type="hidden" name="sync_chats" value="0">
                        <input type="checkbox" name="sync_chats" value="1" x-model="sync_chats" :disabled="!telegram || !whatsapp" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-300 dark:bg-gray-700 rounded-full peer peer-focus:ring-2 peer-focus:ring-violet-300 dark:peer-focus:ring-violet-800 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-violet-500"></div>
                    </div>
                </label>
            </form>
        </div>

        @if($notifSettings['telegram'] ?? false)
        <!-- 📱 TELEGRAM NOTIFICATIONS (Global Maestro) -->
        <div x-transition x-data="{
            userTelegramId: {{ !empty($user->telegram_chat_id) ? 'true' : 'false' }},
            testTelegram() {
                if (typeof window.testTelegram === 'function') {
                    window.testTelegram();
                } else {
                    console.warn('testTelegram no está definido en el contexto actual.');
                }
            }
        }" class="p-6 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-3xl space-y-6">
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
                    <div x-show="userTelegramId">
                        <span class="px-3 py-1 bg-sky-50 dark:bg-sky-900/20 text-sky-600 text-[10px] font-black uppercase rounded-lg border border-sky-100 dark:border-sky-800">Vinculado</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start bg-gray-50/50 dark:bg-gray-800/30 p-6 rounded-2xl border border-gray-100 dark:border-gray-800">
                    <div class="space-y-4">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest px-1">Tu Telegram Chat ID (Personal)</label>
                        <div class="space-y-3" x-data="{ show: false }">
                            <div class="relative">
                                <input name="telegram_chat_id" :type="show ? 'text' : 'password'"
                                    value="{{ $isDemoMode ? app(\App\Services\DemoModeService::class)->mask($user->telegram_chat_id ?? '', 'id') : $user->telegram_chat_id }}"
                                    class="w-full text-sm border rounded-xl py-2.5 px-4 pr-10 shadow-sm {{ $isDemoMode ? 'bg-gray-100 dark:bg-gray-700 border-gray-200 dark:border-gray-600 demo-text cursor-not-allowed' : 'bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-sky-500' }}"
                                    placeholder="Ej: 123456789"
                                    {{ $isDemoMode ? 'readonly' : '' }}>
                                <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" {{ $isDemoMode ? 'disabled' : '' }}>
                                    <svg x-show="!show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    <svg x-show="show" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                </button>
                            </div>
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
                    if (document.hidden) return;
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
                    }, 6000);
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
                    const result = await Swal.fire({
                        title: '¿Desvincular WhatsApp?',
                        text: '¿Deseas desvincular o reiniciar tu cuenta de WhatsApp Personal?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, desvincular',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#e11d48',
                        cancelButtonColor: '#6b7280',
                        customClass: {
                            popup: 'rounded-[2rem] border-0 shadow-2xl dark:bg-gray-900 dark:text-white',
                            confirmButton: 'rounded-xl px-5 py-2.5 text-[11px] font-black uppercase tracking-widest text-white focus:ring-0',
                            cancelButton: 'rounded-xl px-5 py-2.5 text-[11px] font-black uppercase tracking-widest text-white focus:ring-0'
                        }
                    });
                    if (!result.isConfirmed) return;
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



        @if(false)
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
                this.pollInterval = setInterval(() => this.checkStatus(), 10000);
            },
            destroy() {
                if (this.pollInterval) clearInterval(this.pollInterval);
            },
            async checkStatus() {
                if (document.hidden) return;
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
                        <p class="text-[10px] text-gray-400 font-medium">Vincula WhatsApp para recibir y enviar mensajes desde Sientia Open Source Lab</p>
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
