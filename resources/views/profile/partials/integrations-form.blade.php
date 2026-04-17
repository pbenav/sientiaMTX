@php
    $user = auth()->user();
    $teams = $user->teams()->get(); // Forzamos carga fresca de la DB
    
    // Mapa de conexiones por equipo (Pivots)
    $teamConns = [];
    foreach($teams as $team) {
        $teamConns[$team->id] = !empty($team->pivot->google_token);
    }
    
    // Preferencias de IA
    $prefs = $user->aiPreferences->keyBy(fn($p) => $p->team_id ?? 'global');
@endphp

<section x-data="{ 
    context: '', 
    teamConns: {{ json_encode($teamConns) }},
    allPrefs: {{ $prefs->toJson() }},
    apiKey: '',
    aiModel: '',

    init() { this.updateContext(); },
    
    updateContext() {
        const p = this.allPrefs[this.context || 'global'] || {};
        this.apiKey = p.api_key || '';
        this.aiModel = p.ai_model || 'gemini-1.5-flash-latest';
    },

    isGoogleConnected() {
        // La conexión de Google SOLO existe vinculada a equipos
        if (!this.context) return false;
        return !!this.teamConns[this.context];
    }
}">
    
    <div class="space-y-8">
        <!-- 🎯 SELECTOR DE CONTEXTO (Maestro absoluto) -->
        <div class="p-6 bg-gray-50 dark:bg-gray-800/50 rounded-3xl border border-gray-100 dark:border-gray-800 space-y-2">
            <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 block px-1">Integraciones actuales del contexto:</label>
            <select x-model="context" @change="updateContext()" class="w-full bg-transparent border-none text-lg font-black text-gray-900 dark:text-white focus:ring-0 p-0 cursor-pointer">
                <option value="">🌍 Mi Configuración Global (IA)</option>
                @foreach($teams as $team)
                    <option value="{{ $team->id }}">👥 Equipo: {{ $team->name }}</option>
                @endforeach
            </select>
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
                            <p class="text-[10px] text-gray-400 font-medium">Calendario, Drive y Tareas sincronizados</p>
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
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest px-1">Modelo Seleccionado</label>
                        <select name="ai_model" x-model="aiModel" class="w-full text-xs bg-gray-50 dark:bg-gray-800 border-none rounded-2xl focus:ring-2 focus:ring-violet-500">
                            <optgroup label="Modelos Gemini (Versiones 3 y 3.1)">
                                <option value="gemini-3-flash-preview">Gemini 3 Flash Preview</option>
                                <option value="gemini-3.1-pro-preview">Gemini 3.1 Pro Preview</option>
                                <option value="gemini-3.1-flash-lite-preview">Gemini 3.1 Flash Lite Preview</option>
                                <option value="gemini-3.1-flash-tts-preview">Gemini 3.1 Flash TTS Preview (Voz)</option>
                            </optgroup>
                            <optgroup label="Modelos Gemini (Versiones 2 y 2.5)">
                                <option value="gemini-2.5-pro">Gemini 2.5 Pro</option>
                                <option value="gemini-2.5-flash">Gemini 2.5 Flash</option>
                                <option value="gemini-2.5-flash-lite">Gemini 2.5 Flash-Lite</option>
                                <option value="gemini-2.5-pro-preview-tts">Gemini 2.5 Pro Preview TTS</option>
                                <option value="gemini-2.5-flash-preview-tts">Gemini 2.5 Flash Preview TTS</option>
                                <option value="gemini-2.0-flash">Gemini 2.0 Flash</option>
                                <option value="gemini-2.0-flash-lite">Gemini 2.0 Flash-Lite</option>
                            </optgroup>
                            <optgroup label="Generación de Imágenes (Nano Banana)">
                                <option value="nano-banana-2">Nano Banana 2</option>
                                <option value="nano-banana-pro">Nano Banana Pro</option>
                                <option value="nano-banana">Nano Banana</option>
                            </optgroup>
                            <optgroup label="Modelos Especializados y Otros">
                                <option value="gemini-robotics-er-1.6-preview">Gemini Robotics-ER 1.6 Preview</option>
                                <option value="gemini-robotics-er-1.5-preview">Gemini Robotics-ER 1.5 Preview</option>
                                <option value="gemma-4-26b-a4b-it">Gemma 4 26B A4B IT</option>
                                <option value="gemma-4-31b-it">Gemma 4 31B IT</option>
                                <option value="gemini-pro-latest">Gemini Pro Latest</option>
                                <option value="gemini-flash-latest">Gemini Flash Latest</option>
                                <option value="gemini-flash-lite-latest">Gemini Flash-Lite Latest</option>
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
                    </div>
                    <button type="submit" class="px-8 py-3 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-[10px] font-black uppercase rounded-2xl hover:scale-105 active:scale-95 transition-all shadow-lg">
                        Guardar Preferencias de IA
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
