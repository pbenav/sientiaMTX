<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 heading">
            {{ __('Integraciones y Asistencia IA') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Conecta tus cuentas externas y configura cómo Sientia te asistirá.') }}
        </p>
    </header>

    <div class="mt-6 space-y-8">
        <!-- Google / Google Drive -->
        <div class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-700">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 mt-1">
                    <svg class="w-8 h-8 text-blue-500" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853" />
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05" />
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Google y Google Drive</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Conecta tu cuenta de Google para exportar eventos al calendario y permitir que la IA guarde archivos directamente en tu Drive.</p>
                    
                    <div class="mt-3">
                        @if (auth()->user()->google_token)
                            <div class="flex items-center gap-3">
                                <span class="px-2 py-1 text-[10px] font-bold text-emerald-700 bg-emerald-100 rounded-lg dark:bg-emerald-900/40 dark:text-emerald-400">✅ Conectado</span>
                                <form method="POST" action="{{ route('google.disconnect') }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs text-red-600 hover:underline">Desconectar cuenta</button>
                                </form>
                            </div>
                        @else
                            <button onclick="window.openGoogleAuth()" type="button" class="px-4 py-2 text-xs font-bold text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                                Conectar con Google
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Preferences -->
        @php
            $prefs = auth()->user()->aiPreferences->keyBy(function($item) {
                return $item->team_id ?? 'global';
            });
            $teams = auth()->user()->teams;
        @endphp

        <form method="POST" action="{{ route('profile.ai.update') }}" 
              x-data="{ 
                context: '',
                allPrefs: {{ $prefs->toJson() }},
                apiKey: '{{ $prefs['global']->api_key ?? '' }}',
                aiModel: '{{ $prefs['global']->ai_model ?? 'gemini-1.5-flash-latest' }}',
                moodTracking: {{ ($prefs['global']->mood_tracking_enabled ?? true) ? 'true' : 'false' }},
                smartMatching: {{ ($prefs['global']->smart_matching_opt_in ?? true) ? 'true' : 'false' }},
                
                updateContext() {
                    const ctxKey = this.context || 'global';
                    const p = this.allPrefs[ctxKey] || {};
                    this.apiKey = p.api_key || '';
                    this.aiModel = p.ai_model || 'gemini-1.5-flash-latest';
                    this.moodTracking = p.hasOwnProperty('mood_tracking_enabled') ? !!p.mood_tracking_enabled : true;
                    this.smartMatching = p.hasOwnProperty('smart_matching_opt_in') ? !!p.smart_matching_opt_in : true;
                }
              }"
              class="p-4 bg-violet-50/50 dark:bg-violet-900/10 rounded-xl border border-violet-100 dark:border-violet-800/30">
            @csrf
            @method('PATCH')
            
            <div class="flex items-start gap-4 mb-6 pt-6 border-t border-violet-100 dark:border-violet-800/30">
                <div class="flex-shrink-0 mt-1">
                    <div class="w-8 h-8 flex flex-col items-center justify-center bg-blue-100 dark:bg-blue-900/50 rounded-lg text-blue-600">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M7.74 2L2 12l2.35 4.07h11.91l2.35-4.07L12.87 2H7.74zM12.01 13.12l-2.09 3.6h9.17l2.09-3.6h-9.17zM8.86 3.93l6.53 11.23-2.09 3.6L5.77 5.54l3.09-1.61z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider">Almacenamiento Cloud (Google Drive)</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Conecta tu Drive para guardar adjuntos de tareas y permitir que Ax.ia analice tus documentos.</p>
                </div>
            </div>

            <div class="mb-8">
                @if(auth()->user()->google_token)
                    <div class="flex items-center justify-between p-4 bg-green-50 dark:bg-green-900/10 border border-green-100 dark:border-green-800/30 rounded-xl">
                        <div class="flex items-center gap-3">
                            <span class="flex h-3 w-3 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                            </span>
                            <span class="text-sm font-medium text-green-700 dark:text-green-400">Google Drive Conectado</span>
                        </div>
                        <form method="POST" action="{{ route('google.drive.disconnect') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-red-600 hover:underline">Desconectar cuenta</button>
                        </form>
                    </div>
                @else
                    <a href="{{ route('google.drive.redirect') }}" class="inline-flex items-center gap-3 px-6 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm hover:shadow-md transition-all group">
                        <svg class="w-6 h-6" viewBox="0 0 48 48">
                            <path fill="#FFC107" d="M17 6H11L2 22l3 5h6l9-16z"/>
                            <path fill="#2196F3" d="M37 42H11l-9-15 4-7h26l9 16z"/>
                            <path fill="#4CAF50" d="M15 6l9 16 9-16H15z"/>
                        </svg>
                        <span class="text-sm font-bold text-gray-700 dark:text-gray-200">Conectar con Google Drive</span>
                    </a>
                @endif
            </div>

            <div class="flex items-start gap-4 mb-6">
                <div class="flex-shrink-0 mt-1">
                    <div class="w-8 h-8 flex flex-col items-center justify-center bg-violet-100 dark:bg-violet-900/50 rounded-lg text-violet-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Opciones de IA</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Configura Gemini de forma global o específica para cada equipo de trabajo.</p>
                </div>
            </div>

            <div class="space-y-4">
                <!-- Selector de Contexto -->
                <div>
                    <x-input-label for="team_context" :value="__('Contexto de la configuración')" />
                    <select id="team_context" name="team_id" x-model="context" @change="updateContext()" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 focus:border-violet-500 focus:ring-violet-500 text-sm py-2">
                        <option value="">🌍 Global (Todas las áreas)</option>
                        @foreach($teams as $team)
                            <option value="{{ $team->id }}">👥 Equipo: {{ $team->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="p-4 bg-white dark:bg-gray-900/50 rounded-xl border border-violet-100 dark:border-violet-800/20 space-y-4">
                    <div>
                        <x-input-label for="api_key" :value="__('Clave API de Gemini para este contexto')" />
                        <x-text-input id="api_key" name="api_key" type="password" class="mt-1 block w-full text-sm" x-model="apiKey" placeholder="AIzaSy..." autocomplete="off" />
                        <p class="text-[10px] text-gray-500 mt-1 italic">Si se deja vacío en un equipo, se usará la clave Global.</p>
                    </div>

                    <div x-data="{ customModel: false }">
                        <x-input-label for="ai_model" :value="__('Modelo de IA')" />
                        <select id="ai_model_select" x-model="aiModel" @change="customModel = (aiModel === 'custom')" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 focus:border-violet-500 focus:ring-violet-500 text-sm py-2">
                            <optgroup label="Modelos Gemini (V3 / 3.1 Preview)">
                                <option value="gemini-3-flash-preview">Gemini 3 Flash Preview</option>
                                <option value="gemini-3.1-pro-preview">Gemini 3.1 Pro Preview</option>
                                <option value="gemini-3.1-flash-lite-preview">Gemini 3.1 Flash Lite Preview</option>
                                <option value="gemini-3.1-flash-tts-preview">Gemini 3.1 Flash TTS Preview</option>
                            </optgroup>
                            <optgroup label="Modelos Gemini (V2 / 2.5)">
                                <option value="gemini-2.5-pro">Gemini 2.5 Pro</option>
                                <option value="gemini-2.5-flash">Gemini 2.5 Flash</option>
                                <option value="gemini-2.5-flash-lite">Gemini 2.5 Flash-Lite</option>
                                <option value="gemini-2.0-flash">Gemini 2.0 Flash</option>
                                <option value="gemini-2.0-flash-lite">Gemini 2.0 Flash-Lite</option>
                            </optgroup>
                            <optgroup label="Imágenes (Nano Banana)">
                                <option value="nano-banana-2">Nano Banana 2</option>
                                <option value="nano-banana-pro">Nano Banana Pro</option>
                                <option value="nano-banana">Nano Banana Standard</option>
                            </optgroup>
                            <optgroup label="Especializados y Estables">
                                <option value="gemini-1.5-flash-latest">Gemini 1.5 Flash (Recomendado)</option>
                                <option value="gemini-1.5-pro-latest">Gemini 1.5 Pro</option>
                                <option value="gemini-pro">Gemini 1.0 Pro (Máxima compatibilidad)</option>
                                <option value="gemini-robotics-er-1.6-preview">Gemini Robotics-ER 1.6</option>
                                <option value="gemma-4-31b-it">Gemma 4 31B IT</option>
                            </optgroup>
                            <option value="custom">✏️ Otro (Específicar nombre)</option>
                        </select>
                        <x-text-input name="ai_model" x-show="customModel || !['gemini-3-flash-preview', 'gemini-3.1-pro-preview', 'gemini-3.1-flash-lite-preview', 'gemini-2.5-pro', 'gemini-1.5-flash-latest', 'gemini-pro'].includes(aiModel)" x-model="aiModel" type="text" class="mt-2 block w-full text-sm" placeholder="Escribe el nombre del modelo" />
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="mood_tracking" name="mood_tracking_enabled" value="1" x-model="moodTracking" class="rounded border-gray-300 text-violet-600 shadow-sm focus:ring-violet-500">
                        <label for="mood_tracking" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Monitorizar energía en este contexto
                        </label>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="smart_matching" name="smart_matching_opt_in" value="1" x-model="smartMatching" class="rounded border-gray-300 text-violet-600 shadow-sm focus:ring-violet-500">
                        <label for="smart_matching" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Smart Matching en este contexto
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex items-center gap-4">
                <x-primary-button>
                    <span x-text="context === '' ? 'Guardar Configuración Global' : 'Guardar para este Equipo'"></span>
                </x-primary-button>
                @if (session('status') === 'ai-updated')
                    <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-gray-600 dark:text-gray-400">{{ __('Guardado.') }}</p>
                @endif
            </div>
        </form>

        <!-- Telegram -->
        <div x-data="{ 
            telegramEnabled: {{ auth()->user()->telegram_chat_id ? 'true' : 'false' }},
            testingTelegram: false,
            telegramTestStatus: '',
            telegramTestType: '',
            async testTelegram() {
                const chatId = document.getElementById('telegram_chat_id_integrations').value;
                if (!chatId) {
                    this.telegramTestStatus = 'Por favor, introduce tu ID de chat primero.';
                    this.telegramTestType = 'error';
                    return;
                }
                this.testingTelegram = true;
                this.telegramTestStatus = 'Enviando mensaje de prueba...';
                this.telegramTestType = 'info';
                
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
                    this.telegramTestStatus = data.message;
                    this.telegramTestType = data.success ? 'success' : 'error';
                } catch (e) {
                    this.telegramTestStatus = 'Error al conectar con el servidor.';
                    this.telegramTestType = 'error';
                } finally {
                    this.testingTelegram = false;
                    setTimeout(() => { if(this.telegramTestType === 'success') this.telegramTestStatus = ''; }, 5000);
                }
            }
        }" class="p-4 bg-sky-50/50 dark:bg-sky-900/10 rounded-xl border border-sky-100 dark:border-sky-800/30">
            
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 mt-1">
                    <svg class="h-8 w-8 text-sky-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69.01-.03.01-.14-.07-.18-.08-.05-.19-.02-.27 0l-3.21 2.02c-.52.34-1.01.5-1.46.49-.5-.01-1.46-.28-2.18-.52-.89-.3-1.56-.45-1.5-.96.03-.27.4-.55 1.11-.84 4.35-1.89 7.25-3.14 8.7-3.74.45-.19.92-.38 1.25-.38.25 0 .5.06.66.2.14.12.19.29.15.54z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Bot de Telegram</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Recibe notificaciones o interactúa directamente con Sientia mediante Telegram.</p>

                    <div class="mt-4 space-y-4">
                        <p class="text-[11px] text-gray-600 dark:text-gray-400">
                           1. Busca a <a href="https://t.me/SientiaBot" target="_blank" class="text-sky-600 font-bold hover:underline">@SientiaBot</a> en Telegram.<br>
                           2. Escribe <strong>/start</strong> para obtener tu ID de Chat.<br>
                           3. Pégalo aquí abajo para vincular tu cuenta.
                        </p>

                        <form method="POST" action="{{ route('profile.update') }}">
                            @csrf
                            @method('patch')
                            
                            <div class="flex items-end gap-2">
                                <div class="flex-1">
                                    <x-input-label for="telegram_chat_id_integrations" :value="__('ID de Chat de Telegram')" />
                                    <x-text-input id="telegram_chat_id_integrations" name="telegram_chat_id" type="text" class="mt-1 block w-full text-sm" :value="old('telegram_chat_id', auth()->user()->telegram_chat_id)" placeholder="Ej: 123456789" />
                                </div>
                                <x-primary-button>{{ __('Vincular') }}</x-primary-button>
                            </div>
                            <x-input-error class="mt-2" :messages="$errors->get('telegram_chat_id')" />
                        </form>
                        
                        <div class="pt-2">
                             <button type="button" @click="testTelegram()" :disabled="testingTelegram"
                                class="inline-flex items-center gap-2 text-xs font-semibold text-sky-600 hover:text-sky-700 disabled:opacity-50">
                                <span x-show="!testingTelegram">🚀 Probar conexión enviando un mensaje</span>
                                <span x-show="testingTelegram" class="flex items-center gap-2">
                                    <svg class="animate-spin h-3 w-3" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Enviando...
                                </span>
                            </button>
                            <p x-show="telegramTestStatus" x-text="telegramTestStatus" 
                                :class="{
                                    'text-red-500': telegramTestType === 'error',
                                    'text-emerald-500': telegramTestType === 'success',
                                    'text-sky-500': telegramTestType === 'info'
                                }" class="text-[10px] font-bold mt-1"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>
