@php
    $user = auth()->user();
    $teams = $user->teams()->get(); // Forzamos carga fresca de la DB
    
    // Mapa de conexiones por equipo (Pivots)
    $teamConns = [];
    foreach($teams as $team) {
        $teamConns[$team->id] = [
            'connected' => !empty($team->pivot->google_token),
            'email' => $team->pivot->google_email ?? ''
        ];
    }
    
    // Preferencias de IA
    $prefs = $user->aiPreferences->keyBy(fn($p) => $p->team_id ?? 'global');
@endphp

<section x-data="{ 
    context: '{{ request()->query('team_id', '') }}', 
    teamConns: {{ json_encode($teamConns) }},
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
        this.aiModel = p.ai_model || 'gemini-3-flash-preview';
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
            if (this.context == '{{ $team->id }}') return '👥 Equipo: {{ $team->name }}';
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
    }
}">
    
    <div class="space-y-8">
        <!-- 🎯 SELECTOR DE CONTEXTO (Maestro absoluto) -->
        <div class="p-6 bg-gray-50 dark:bg-gray-800/50 rounded-3xl border border-gray-100 dark:border-gray-800 space-y-2 relative">
            <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 block px-1">Integraciones actuales del contexto:</label>
            
            <div class="relative">
                <button @click="openSelector = !openSelector" type="button" class="w-full flex items-center justify-between text-left group">
                    <span x-text="getCurrentContextName()" class="text-xl font-bold tracking-tight text-gray-900 dark:text-white transition-colors group-hover:text-violet-600" style="font-family: 'Space Grotesk', sans-serif"></span>
                    <svg class="w-5 h-5 text-gray-400 transition-transform duration-300" :class="openSelector ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

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
                            <div class="flex items-center gap-4">
                                <span class="px-3 py-1 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 text-[10px] font-black uppercase rounded-lg border border-emerald-100 dark:border-emerald-800">Conectado</span>
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
                            <!-- Opción actual (para evitar que el navegador resetee el valor mientras carga) -->
                            <template x-if="aiModel && !availableModels.some(m => m.id === aiModel) && !['gemini-3-flash-preview', 'gemini-2.5-flash', 'gemini-2.0-flash'].includes(aiModel)">
                                <option :value="aiModel" x-text="'Cargando ' + aiModel + '...'" selected></option>
                            </template>

                            <!-- Lista Dinámica (Si hay modelos detectados) -->
                            <template x-if="availableModels.length > 0">
                                <optgroup label="Modelos disponibles en tu cuenta">
                                    <template x-for="model in availableModels" :key="model.id">
                                        <option :value="model.id" x-text="model.display_name"></option>
                                    </template>
                                </optgroup>
                            </template>

                            <!-- Lista de Emergencia (Siempre visible como fallback) -->
                            <optgroup label="Era Gemini 3 (2026)">
                                <option value="gemini-3-flash-preview">Gemini 3 Flash (Recomendado)</option>
                                <option value="gemini-2.5-flash">Gemini 2.5 Flash</option>
                                <option value="gemini-2.0-flash">Gemini 2.0 Flash</option>
                            </optgroup>
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
    </div>
</section>
