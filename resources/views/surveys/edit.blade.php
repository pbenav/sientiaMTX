<x-app-layout>
    @php
        $isGlobal = is_null($survey->team_id);
        $routePrefix = $isGlobal ? 'global-surveys.' : 'teams.surveys.';
        // If it's not global, we ensure we have a team object or ID for the route
        $contextTeam = $isGlobal ? null : ($team ?? $survey->team);
    @endphp

    <x-slot name="header">
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-6">
            <div class="flex items-start gap-4 min-w-0 flex-1">
                @if(!$isGlobal)
                    <a href="{{ route('teams.index') }}"
                        class="mt-1 p-2.5 bg-gray-50 dark:bg-gray-800/50 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-2xl transition-all shadow-sm border border-gray-100 dark:border-gray-700/50 shrink-0"
                        title="{{ __('navigation.back') ?? 'Volver' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>
                @endif
                <div class="min-w-0 flex-1">
                    @if(!$isGlobal)
                        @include('teams.partials.breadcrumb')
                    @endif
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3 mb-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        {{ $isGlobal ? __('Ajustar Encuesta Global') : __('Ajustar Encuesta') }}
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 font-medium text-sm">
                        {{ __('Modifica la estructura o los ajustes de tu encuesta. Ten en cuenta que cambiar preguntas existentes puede afectar a la interpretación de los votos actuales.') }}
                    </p>
                </div>
            </div>
        </div>

        @if(!$isGlobal)
            <div class="mt-8 mb-4 flex w-full">
                @include('teams.partials.view-switcher')
            </div>
        @endif
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-gray-50 to-white dark:from-gray-950 dark:to-gray-900 min-h-screen" 
         x-data="surveyManager()">
        <div class="max-w-[1600px] mx-auto">

            <form action="{{ route($routePrefix . 'update', $contextTeam ? [$contextTeam, $survey] : [$survey]) }}" method="POST" id="survey-edit-form">
                @csrf
                @method('PATCH')
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Survey Identity Card -->
                        <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-8 shadow-xl shadow-indigo-500/5 border border-gray-100 dark:border-gray-800">
                            <div class="space-y-6">
                                <div>
                                    <label for="title" class="block text-[10px] font-black uppercase tracking-widest text-indigo-600 mb-3 ml-1">{{ __('Título de la Encuesta') }}</label>
                                    <input type="text" name="title" id="title" value="{{ old('title', $survey->title) }}" required
                                           class="w-full bg-gray-50 dark:bg-gray-800/50 border-2 border-transparent focus:border-indigo-500 rounded-3xl p-4 text-base font-bold text-gray-900 dark:text-white transition-all placeholder-gray-400"
                                           placeholder="{{ __('Ej: Feedback Trimestral del Equipo') }}">
                                    @error('title') <p class="mt-2 text-sm text-red-500 font-bold ml-2">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="description" class="block text-[10px] font-black uppercase tracking-widest text-indigo-600 mb-3 ml-1">{{ __('Contexto / Descripción') }}</label>
                                    <textarea name="description" id="description" rows="2"
                                              class="w-full bg-gray-50 dark:bg-gray-800/50 border-2 border-transparent focus:border-indigo-500 rounded-3xl p-4 text-sm text-gray-700 dark:text-gray-300 transition-all placeholder-gray-400 font-medium"
                                              placeholder="{{ __('Explica brevemente el objetivo de esta consulta...') }}">{{ old('description', $survey->description) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Questions Builder Header -->
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-6">
                            <h2 class="text-[10px] font-black uppercase tracking-widest text-gray-400 ml-1">{{ __('Constructor de Preguntas') }}</h2>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="previewSurvey()" 
                                        class="inline-flex items-center px-4 py-2 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-100 transition-all gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    {{ __('Previsualizar') }}
                                </button>
                                <button type="button" @click="selectImportSource()" 
                                        class="inline-flex items-center px-4 py-2 bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-100 transition-all gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a2 2 0 002 2h12a2 2 0 002 2h12a2 2 0 002-2v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                    {{ __('Añadir desde JSON') }}
                                </button>
                                <button type="button" @click="downloadExampleJSON()" 
                                        class="inline-flex items-center px-3 py-2 text-[9px] font-bold text-gray-400 hover:text-indigo-500 transition-colors gap-1.5 group">
                                    <svg class="w-3.5 h-3.5 opacity-50 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    {{ __('Descargar Ejemplo') }}
                                </button>
                                <input type="file" x-ref="jsonInput" class="hidden" accept=".json" @change="importJSON($event)">
                            </div>
                        </div>

                        <!-- Questions Builder -->
                        <div class="space-y-6">
                            <template x-for="(question, qIndex) in questions" :key="question.id">
                                <div class="group relative bg-white dark:bg-gray-900 rounded-[2.5rem] p-8 shadow-xl shadow-indigo-500/5 border border-gray-100 dark:border-gray-800 animate-fade-in">
                                    <!-- Delete Question Button -->
                                    <button type="button" @click="removeQuestion(qIndex)" 
                                            class="absolute -top-3 -right-3 w-10 h-10 bg-red-50 dark:bg-red-900/20 text-red-600 rounded-2xl flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all hover:bg-red-600 hover:text-white shadow-lg border border-red-100 dark:border-red-900/30">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>

                                    <div class="flex flex-col md:flex-row gap-6">
                                        <!-- Question Header & Type -->
                                        <div class="flex-grow space-y-6">
                                            <!-- Hidden ID if exists -->
                                            <input type="hidden" :name="`questions[${qIndex}][id]`" :value="question.db_id">

                                            <div class="flex items-center gap-4">
                                                <div class="w-10 h-10 rounded-2xl bg-indigo-600 text-white flex items-center justify-center font-black text-lg shadow-lg">
                                                    <span x-text="qIndex + 1"></span>
                                                </div>
                                                <div class="flex-grow space-y-4">
                                                     <input type="text" :name="`questions[${qIndex}][title]`" x-model="question.title" required
                                                            class="w-full bg-transparent border-b-2 border-gray-100 dark:border-gray-800 focus:border-indigo-500 px-0 py-2 text-lg font-black text-gray-900 dark:text-white placeholder-gray-300 transition-all"
                                                            placeholder="{{ __('Escribe tu pregunta aquí...') }}">
                                                     
                                                     <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                         <div>
                                                             <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2 ml-1">{{ __('Descripción (Opcional)') }}</label>
                                                             <textarea :name="`questions[${qIndex}][description]`" x-model="question.description" rows="1"
                                                                       class="w-full bg-gray-50 dark:bg-gray-800/30 border-2 border-transparent focus:border-indigo-500/30 rounded-xl p-3 text-xs font-medium text-gray-700 dark:text-gray-300 transition-all"
                                                                       placeholder="{{ __('Añade contexto adicional...') }}"></textarea>
                                                         </div>
                                                         <div>
                                                             <label class="block text-[10px] font-black uppercase tracking-widest text-indigo-600/50 mb-2 ml-1">{{ __('Instrucciones (Internas/Guía)') }}</label>
                                                             <textarea :name="`questions[${qIndex}][instructions]`" x-model="question.instructions" rows="1"
                                                                       class="w-full bg-gray-50 dark:bg-gray-800/30 border-2 border-transparent focus:border-indigo-500/30 rounded-xl p-3 text-xs font-medium text-gray-700 dark:text-gray-300 transition-all"
                                                                       placeholder="{{ __('Instrucciones sobre cómo responder...') }}"></textarea>
                                                         </div>
                                                     </div>
                                                 </div>
                                            </div>

                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2 ml-1">{{ __('Tipo de Respuesta') }}</label>
                                                    <select :name="`questions[${qIndex}][type]`" x-model="question.type"
                                                            class="w-full bg-gray-50 dark:bg-gray-800/50 border-2 border-transparent focus:border-indigo-500 rounded-2xl p-3 text-sm font-bold text-gray-700 dark:text-gray-300 transition-all">
                                                        <option value="single_choice">{{ __('Opción Única') }}</option>
                                                        <option value="multiple_choice">{{ __('Opción Múltiple') }}</option>
                                                        <option value="rating">{{ __('Valoración (1-5 estrellas)') }}</option>
                                                        <option value="text">{{ __('Respuesta Libre (Texto)') }}</option>
                                                    </select>
                                                </div>
                                                <div class="flex items-center pl-2 pt-6">
                                                    <label class="relative inline-flex items-center cursor-pointer">
                                                        <input type="hidden" :name="`questions[${qIndex}][is_required]`" :value="question.is_required ? 1 : 0">
                                                        <input type="checkbox" x-model="question.is_required" class="sr-only peer">
                                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                                                        <span class="ml-3 text-xs font-black uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Obligatoria') }}</span>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Options Section (only for choices) -->
                                            <div x-show="question.type === 'single_choice' || question.type === 'multiple_choice'" 
                                                 x-transition:enter="transition ease-out duration-300"
                                                 x-transition:enter-start="opacity-0 -translate-y-4"
                                                 x-transition:enter-end="opacity-100 translate-y-0"
                                                 class="space-y-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                                                
                                                <label class="block text-[10px] font-black uppercase tracking-widest text-indigo-600 mb-2 ml-1">{{ __('Opciones de Respuesta') }}</label>
                                                
                                                <div class="space-y-3">
                                                    <template x-for="(option, oIndex) in question.options" :key="oIndex">
                                                        <div class="flex items-center gap-3 group/opt">
                                                            <div class="w-2 h-2 rounded-full bg-indigo-400"></div>
                                                            <input type="hidden" :name="`questions[${qIndex}][options][${oIndex}][id]`" :value="option.db_id">
                                                            <input type="text" :name="`questions[${qIndex}][options][${oIndex}][label]`" x-model="option.label"
                                                                   class="flex-grow bg-gray-50 dark:bg-gray-800/30 border-2 border-transparent focus:border-indigo-500/30 focus:bg-white dark:focus:bg-gray-800 rounded-xl px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 transition-all"
                                                                   placeholder="{{ __('Escribe una opción...') }}">
                                                            <button type="button" @click="removeOption(qIndex, oIndex)" 
                                                                    class="p-2 text-gray-300 hover:text-red-500 transition-colors opacity-0 group-hover/opt:opacity-100"
                                                                    x-show="question.options.length > 2">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                                            </button>
                                                        </div>
                                                    </template>
                                                </div>
                                                
                                                <button type="button" @click="addOption(qIndex)" 
                                                        class="inline-flex items-center px-4 py-2 text-xs font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 rounded-xl transition-all gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                                    {{ __('Añadir Opción') }}
                                                </button>
                                            </div>

                                            <!-- Rating Preview -->
                                            <div x-show="question.type === 'rating'" class="p-6 bg-amber-50 dark:bg-amber-500/5 rounded-3xl border border-amber-100 dark:border-amber-900/20 text-center">
                                                <div class="flex justify-center gap-2 mb-2">
                                                    <template x-for="i in 5">
                                                        <svg class="w-8 h-8 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                    </template>
                                                </div>
                                                <p class="text-[10px] font-black uppercase tracking-widest text-amber-600 dark:text-amber-500">{{ __('Escala de valoración automática de 1 a 5') }}</p>
                                            </div>

                                            <!-- Text Preview -->
                                            <div x-show="question.type === 'text'" class="p-6 bg-emerald-50 dark:bg-emerald-500/5 rounded-3xl border border-emerald-100 dark:border-emerald-900/20">
                                                <div class="w-full h-12 border-2 border-dashed border-emerald-200 dark:border-emerald-800 rounded-xl"></div>
                                                <p class="mt-3 text-[10px] font-black uppercase tracking-widest text-emerald-600 dark:text-emerald-500 text-center">{{ __('Caja de texto para respuestas abiertas') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Add Question Button -->
                            <button type="button" @click="addQuestion()" 
                                    class="w-full py-8 border-2 border-dashed border-gray-200 dark:border-gray-800 rounded-[2.5rem] text-gray-400 hover:border-indigo-500 hover:text-indigo-600 hover:bg-indigo-50/30 dark:hover:bg-indigo-500/5 transition-all flex flex-col items-center justify-center gap-3 group">
                                <div class="w-12 h-12 rounded-2xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400 group-hover:bg-indigo-600 group-hover:text-white transition-all shadow-lg">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
                                </div>
                                <span class="font-black uppercase tracking-widest text-sm">{{ __('Añadir Nueva Pregunta') }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Sidebar Settings -->
                    <div class="lg:col-span-1">
                        <div class="sticky top-8 space-y-6">
                            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-8 shadow-xl shadow-indigo-500/5 border border-gray-100 dark:border-gray-800">
                                <h3 class="text-xs font-black uppercase tracking-widest text-indigo-600 mb-8 border-b border-gray-100 dark:border-gray-800 pb-4">
                                    {{ __('Configuración Global') }}
                                </h3>

                                <div class="space-y-6">
                                    <div>
                                        <label for="expires_at" class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2 ml-1">{{ __('Fecha de Expiración') }}</label>
                                        <input type="datetime-local" name="expires_at" id="expires_at" value="{{ $survey->expires_at ? $survey->expires_at->format('Y-m-d\TH:i') : '' }}"
                                               class="w-full bg-gray-50 dark:bg-gray-800/50 border-2 border-transparent focus:border-indigo-500 rounded-2xl p-3 text-sm font-bold text-gray-700 dark:text-gray-300 transition-all">
                                    </div>

                                    <div class="pt-4 space-y-4">
                                        <label class="flex items-center group cursor-pointer">
                                            <div class="relative">
                                                <input type="hidden" name="show_results_before_voting" value="0">
                                                <input type="checkbox" name="show_results_before_voting" value="1" {{ old('show_results_before_voting', $survey->show_results_before_voting) ? 'checked' : '' }} class="sr-only peer">
                                                <div class="w-10 h-5 bg-gray-200 dark:bg-gray-700 rounded-full peer-checked:bg-indigo-600 transition-colors after:content-[''] after:absolute after:top-1 after:left-1 after:bg-white after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:after:translate-x-5"></div>
                                            </div>
                                            <span class="ml-3 text-xs font-bold text-gray-600 dark:text-gray-400 group-hover:text-indigo-600 transition-colors">{{ __('Resultados públicos antes de votar') }}</span>
                                        </label>

                                        <label class="flex items-center group cursor-pointer">
                                            <div class="relative">
                                                <input type="hidden" name="is_active" value="0">
                                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $survey->is_active) ? 'checked' : '' }} class="sr-only peer">
                                                <div class="w-10 h-5 bg-gray-200 dark:bg-gray-700 rounded-full peer-checked:bg-indigo-600 transition-colors after:content-[''] after:absolute after:top-1 after:left-1 after:bg-white after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:after:translate-x-5"></div>
                                            </div>
                                            <span class="ml-3 text-xs font-bold text-gray-600 dark:text-gray-400 group-hover:text-indigo-600 transition-colors">{{ __('Encuesta Activa') }}</span>
                                        </label>

                                        <label class="flex items-center group cursor-pointer">
                                            <div class="relative">
                                                <input type="hidden" name="allow_multiple_votes" value="0">
                                                <input type="checkbox" name="allow_multiple_votes" value="1" {{ old('allow_multiple_votes', $survey->allow_multiple_votes) ? 'checked' : '' }} class="sr-only peer">
                                                <div class="w-10 h-5 bg-gray-200 dark:bg-gray-700 rounded-full peer-checked:bg-indigo-600 transition-colors after:content-[''] after:absolute after:top-1 after:left-1 after:bg-white after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:after:translate-x-5"></div>
                                            </div>
                                            <span class="ml-3 text-xs font-bold text-gray-600 dark:text-gray-400 group-hover:text-indigo-600 transition-colors">{{ __('Permitir múltiples votos por usuario') }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" 
                                    class="w-full group relative inline-flex items-center justify-center px-8 py-5 font-black text-white tracking-widest uppercase transition-all duration-500 ease-in-out transform bg-indigo-600 rounded-[2rem] hover:scale-105 active:scale-95 shadow-[0_20px_50px_rgba(79,70,229,0.3)] hover:shadow-[0_20px_50px_rgba(79,70,229,0.5)] overflow-hidden">
                                <div class="absolute inset-0 w-full h-full bg-gradient-to-br from-indigo-500 via-indigo-600 to-violet-700"></div>
                                <span class="relative flex items-center gap-3">
                                    {{ __('Guardar Cambios') }}
                                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <x-google-drive-picker :team="$team" />

    <script>
        function surveyManager() {
            return {
                questions: @json($questions),
                init() {
                    window.addEventListener('drive-file-selected', async (e) => {
                        if (e.detail.targetType === 'survey_import') {
                            const fileId = e.detail.file.id;
                            try {
                                const downloadRoute = @if($contextTeam) `{{ route('google.drive.download-content', $contextTeam) }}` @else `{{ route('google.drive.download-content', ['team' => 'global']) }}` @endif;
                                const response = await fetch(`${downloadRoute}?file_id=${fileId}`);
                                const data = await response.json();
                                if (data.success) {
                                    this.processImportedJSON(data.content);
                                } else {
                                    Swal.fire('Error', data.message, 'error');
                                }
                            } catch (err) {
                                Swal.fire('Error', 'No se pudo conectar con Google Drive', 'error');
                            }
                        }
                    });
                },
                addQuestion() {
                    this.questions.push({
                        id: Date.now(),
                        db_id: null,
                        title: '',
                        description: '',
                        instructions: '',
                        type: 'single_choice',
                        is_required: true,
                        options: [
                            { db_id: null, label: '' },
                            { db_id: null, label: '' }
                        ]
                    });
                },
                removeQuestion(index) {
                    if (this.questions.length > 1) {
                        this.questions.splice(index, 1);
                    }
                },
                addOption(qIndex) {
                    this.questions[qIndex].options.push({ db_id: null, label: '' });
                },
                removeOption(qIndex, oIndex) {
                    if (this.questions[qIndex].options.length > 2) {
                        this.questions[qIndex].options.splice(oIndex, 1);
                    }
                },
                selectImportSource() {
                    const isDark = document.documentElement.classList.contains('dark');
                    Swal.fire({
                        title: '<span class="text-xs font-black uppercase tracking-widest text-indigo-600">{{ __("Origen de Importación") }}</span>',
                        background: isDark ? '#0f172a' : '#ffffff',
                        color: isDark ? '#f3f4f6' : '#1f2937',
                        showConfirmButton: false,
                        showCloseButton: true,
                        customClass: {
                            popup: 'rounded-[2.5rem] shadow-2xl border border-gray-200 dark:border-gray-800 p-6',
                        },
                        html: `
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-6 text-center px-4">
                                {{ __("¿Desde dónde quieres cargar las preguntas de tu encuesta?") }}
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-2">
                                <button type="button" id="import-btn-file" class="flex flex-col items-center gap-3 p-5 rounded-[2rem] border-2 border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30 hover:border-indigo-600 transition-all text-center group">
                                    <div class="w-12 h-12 rounded-2xl bg-white dark:bg-gray-800 flex items-center justify-center text-gray-600 group-hover:scale-110 transition-transform shadow-sm">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a2 2 0 002 2h12a2 2 0 002 2h12a2 2 0 002-2v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                    </div>
                                    <div class="font-black text-[9px] uppercase tracking-widest text-gray-700 dark:text-gray-300">{{ __("Archivo Local") }}</div>
                                </button>
                                <button type="button" id="import-btn-clipboard" class="flex flex-col items-center gap-3 p-5 rounded-[2rem] border-2 border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30 hover:border-indigo-600 transition-all text-center group">
                                    <div class="w-12 h-12 rounded-2xl bg-white dark:bg-gray-800 flex items-center justify-center text-gray-600 group-hover:scale-110 transition-transform shadow-sm">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    </div>
                                    <div class="font-black text-[9px] uppercase tracking-widest text-gray-700 dark:text-gray-300">{{ __("Portapapeles") }}</div>
                                </button>
                                <button type="button" id="import-btn-drive" class="flex flex-col items-center gap-3 p-5 rounded-[2rem] border-2 border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30 hover:border-indigo-600 transition-all text-center group">
                                    <div class="w-12 h-12 rounded-2xl bg-white dark:bg-gray-800 flex items-center justify-center text-gray-600 group-hover:scale-110 transition-transform shadow-sm">
                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M17 6H11L2 22l3 5h6l9-16z" fill="#FFC107"/>
                                            <path d="M37 42H11l-9-15 4-7h26l9 16z" fill="#2196F3"/>
                                            <path d="M15 6l9 16 9-16H15z" fill="#4CAF50"/>
                                        </svg>
                                    </div>
                                    <div class="font-black text-[9px] uppercase tracking-widest text-gray-700 dark:text-gray-300">Google Drive</div>
                                </button>
                            </div>
                        `,
                        didOpen: (el) => {
                            el.querySelector('#import-btn-file').onclick = () => { Swal.close(); this.$refs.jsonInput.click(); };
                            el.querySelector('#import-btn-clipboard').onclick = async () => { 
                                Swal.close(); 
                                Swal.fire({
                                    title: '<span class="text-xs font-black uppercase tracking-widest text-indigo-600">{{ __("Pegar JSON") }}</span>',
                                    html: `
                                        <div class="text-left mt-4">
                                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2 ml-1">{{ __("Pega aquí el contenido de tu JSON") }}</label>
                                            <textarea id="clipboard-json-input" class="w-full h-64 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 rounded-2xl px-5 py-4 text-xs font-mono text-gray-900 dark:text-gray-300 focus:ring-2 focus:ring-indigo-500/20 outline-none resize-none custom-scrollbar" placeholder='[{"title": "..."}]'></textarea>
                                        </div>
                                    `,
                                    showCancelButton: true,
                                    confirmButtonText: '{{ __("Validar e Importar") }}',
                                    cancelButtonText: '{{ __("Cancelar") }}',
                                    confirmButtonColor: '#4f46e5',
                                    background: isDark ? '#0f172a' : '#ffffff',
                                    color: isDark ? '#f3f4f6' : '#1f2937',
                                    customClass: {
                                        popup: 'rounded-[2.5rem]',
                                        confirmButton: 'rounded-xl font-black uppercase text-xs tracking-widest',
                                        cancelButton: 'rounded-xl font-black uppercase text-xs tracking-widest'
                                    },
                                    didOpen: async () => {
                                        try {
                                            const text = await navigator.clipboard.readText();
                                            if (text && (text.trim().startsWith('[') || text.trim().startsWith('{'))) {
                                                document.getElementById('clipboard-json-input').value = text;
                                            }
                                        } catch (e) {}
                                    },
                                    preConfirm: () => {
                                        const val = document.getElementById('clipboard-json-input').value;
                                        if (!val) {
                                            Swal.showValidationMessage('{{ __("Por favor, pega el JSON antes de continuar") }}');
                                        }
                                        return val;
                                    }
                                }).then((res) => {
                                    if (res.isConfirmed) {
                                        this.processImportedJSON(res.value);
                                    }
                                });
                            };
                            el.querySelector('#import-btn-drive').onclick = () => { 
                                Swal.close(); 
                                window.dispatchEvent(new CustomEvent('open-drive-picker', { 
                                    detail: { 
                                        mode: 'collect',
                                        type: 'survey_import' 
                                    } 
                                }));
                            };
                        }
                    });
                },
                downloadExampleJSON() {
                    const example = [
                        {
                            "title": "{{ __('Nivel de satisfacción') }}",
                            "description": "{{ __('Indica cómo te sientes con el proyecto') }}",
                            "type": "rating",
                            "is_required": true
                        },
                        {
                            "title": "{{ __('¿De qué departamento eres?') }}",
                            "type": "single_choice",
                            "options": [
                                "{{ __('Desarrollo') }}",
                                "{{ __('Diseño') }}",
                                "{{ __('Marketing') }}",
                                "{{ __('Ventas') }}"
                            ],
                            "is_required": true
                        },
                        {
                            "title": "{{ __('¿Qué mejorarías?') }}",
                            "type": "multiple_choice",
                            "options": [
                                "{{ __('Comunicación') }}",
                                "{{ __('Herramientas') }}",
                                "{{ __('Plazos') }}",
                                "{{ __('Nada, todo genial') }}"
                            ]
                        },
                        {
                            "title": "{{ __('Sugerencias adicionales') }}",
                            "type": "text",
                            "is_required": false
                        }
                    ];
                    
                    const blob = new Blob([JSON.stringify(example, null, 4)], { type: 'application/json' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'encuesta-ejemplo.json';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                },
                importJSON(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.processImportedJSON(e.target.result);
                    };
                    reader.readAsText(file);
                    event.target.value = ''; // Reset input
                },
                processImportedJSON(jsonString) {
                    try {
                        const imported = JSON.parse(jsonString);
                        if (Array.isArray(imported)) {
                            // Add IDs and ensure structure
                            const validated = imported.map(q => ({
                                id: Date.now() + Math.random(),
                                db_id: null,
                                title: q.title || '',
                                description: q.description || '',
                                instructions: q.instructions || '',
                                type: q.type || 'single_choice',
                                is_required: q.is_required !== undefined ? q.is_required : true,
                                options: Array.isArray(q.options) 
                                    ? q.options.map(opt => ({ db_id: null, label: opt }))
                                    : [{ db_id: null, label: '' }, { db_id: null, label: '' }]
                            }));

                            const isDark = document.documentElement.classList.contains('dark');
                            
                            // Generate Preview HTML
                            let previewHtml = `
                                <div class="text-left mt-4 mb-6">
                                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3 ml-1">{{ __("Vista Previa de Preguntas") }}</div>
                                    <div class="space-y-2 max-h-64 overflow-y-auto pr-2 custom-scrollbar">
                            `;
                            
                            validated.forEach((q, index) => {
                                const typeNames = {
                                    'single_choice': 'Única',
                                    'multiple_choice': 'Múltiple',
                                    'rating': 'Valoración',
                                    'text': 'Libre'
                                };
                                previewHtml += `
                                    <div class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-800 flex items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold text-gray-900 dark:text-white truncate">${q.title || '<em>Sin título</em>'}</div>
                                            <div class="text-[9px] text-gray-500 uppercase font-black tracking-tighter mt-0.5">${typeNames[q.type] || q.type}</div>
                                        </div>
                                        <div class="shrink-0 text-[10px] font-bold text-gray-400">#${index + 1}</div>
                                    </div>
                                `;
                            });
                            
                            previewHtml += `</div></div>`;
                            
                            Swal.fire({
                                title: '<span class="text-xs font-black uppercase tracking-widest text-indigo-600">{{ __("¿Importar preguntas?") }}</span>',
                                html: `
                                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2 px-4">
                                        {{ __("Hemos detectado :count preguntas en el archivo. Esto reemplazará las preguntas actuales.", ["count" => ""]) }}`.replace('""', validated.length) + validated.length + `
                                    </div>
                                    ${previewHtml}
                                `,
                                icon: 'info',
                                showCancelButton: true,
                                confirmButtonText: '{{ __("Sí, importar todo") }}',
                                cancelButtonText: '{{ __("Revisar JSON") }}',
                                confirmButtonColor: '#4f46e5',
                                background: isDark ? '#0f172a' : '#ffffff',
                                color: isDark ? '#f3f4f6' : '#1f2937',
                                customClass: {
                                    popup: 'rounded-[2.5rem] w-full max-w-lg',
                                    confirmButton: 'rounded-xl font-black uppercase text-xs tracking-widest',
                                    cancelButton: 'rounded-xl font-black uppercase text-xs tracking-widest'
                                }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    this.questions = validated;
                                    Swal.fire({
                                        title: '{{ __("¡Hecho!") }}',
                                        text: '{{ __("Se han importado :count preguntas.", ["count" => ""]) }}'.replace('""', validated.length) + validated.length,
                                        icon: 'success',
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 3000
                                    });
                                }
                            });
                        } else {
                            throw new Error('El formato no es un array');
                        }
                    } catch (err) {
                        Swal.fire('Error', '{{ __("Error al procesar el JSON. Asegúrate de que el formato es correcto.") }}', 'error');
                    }
                },
                previewSurvey() {
                    const isDark = document.documentElement.classList.contains('dark');
                    const title = document.getElementById('title').value || '{{ __("Sin título") }}';
                    const description = document.getElementById('description').value || '';
                    
                    let questionsHtml = '';
                    this.questions.forEach((q, index) => {
                        let optionsHtml = '';
                        if (q.type === 'single_choice' || q.type === 'multiple_choice') {
                            optionsHtml = '<div class="space-y-2 mt-3">';
                            q.options.forEach(opt => {
                                if (opt.label && opt.label.trim()) {
                                    optionsHtml += `
                                        <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-800 text-xs font-medium text-gray-700 dark:text-gray-300">
                                            <div class="w-4 h-4 rounded-${q.type === 'single_choice' ? 'full' : 'md'} border-2 border-indigo-400"></div>
                                            ${opt.label}
                                        </div>
                                    `;
                                }
                            });
                            optionsHtml += '</div>';
                        } else if (q.type === 'rating') {
                            optionsHtml = `
                                <div class="flex justify-center gap-2 mt-4">
                                    ${Array(5).fill().map(() => `
                                        <svg class="w-8 h-8 text-gray-200 dark:text-gray-700 hover:text-amber-400 transition-colors cursor-pointer" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    `).join('')}
                                </div>
                            `;
                        } else if (q.type === 'text') {
                            optionsHtml = `
                                <div class="mt-3 w-full h-24 bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-800 rounded-xl p-3 text-[10px] text-gray-400 font-medium italic">
                                    {{ __("Escribe aquí tu respuesta...") }}
                                </div>
                            `;
                        }

                        questionsHtml += `
                            <div class="mb-8 last:mb-0">
                                <div class="flex items-start gap-3">
                                    <div class="shrink-0 w-6 h-6 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-[10px] font-black">${index + 1}</div>
                                    <div class="min-w-0 flex-grow">
                                        <div class="text-sm font-black text-gray-900 dark:text-white leading-tight">
                                            ${q.title || '{{ __("Pregunta sin título") }}'}
                                            ${q.is_required ? '<span class="text-red-500 ml-1">*</span>' : ''}
                                        </div>
                                        ${q.description ? `<div class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 font-medium">${q.description}</div>` : ''}
                                        ${optionsHtml}
                                    </div>
                                </div>
                            </div>
                        `;
                    });

                    Swal.fire({
                        title: '<span class="text-xs font-black uppercase tracking-widest text-indigo-600">{{ __("Vista Previa de la Encuesta") }}</span>',
                        width: '800px',
                        html: `
                            <div class="text-left mt-6 px-2">
                                <div class="mb-10 text-center">
                                    <h2 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tight">${title}</h2>
                                    ${description ? `<p class="mt-2 text-xs text-gray-500 dark:text-gray-400 font-medium">${description}</p>` : ''}
                                    <div class="w-12 h-1 bg-indigo-600 mx-auto mt-6 rounded-full opacity-20"></div>
                                </div>
                                <div class="max-h-[60vh] overflow-y-auto pr-4 custom-scrollbar">
                                    ${questionsHtml}
                                </div>
                                <div class="mt-10 pt-6 border-t border-gray-100 dark:border-gray-800 text-center">
                                    <button class="px-8 py-4 bg-indigo-600 text-white rounded-[1.5rem] font-black uppercase text-[10px] tracking-widest shadow-lg shadow-indigo-500/20 opacity-50 cursor-not-allowed">
                                        {{ __("Enviar Respuestas") }}
                                    </button>
                                    <p class="mt-3 text-[9px] text-gray-400 font-bold italic uppercase tracking-tighter">{{ __("Esto es solo una vista previa del diseño") }}</p>
                                </div>
                            </div>
                        `,
                        showConfirmButton: false,
                        showCloseButton: true,
                        background: isDark ? '#0f172a' : '#ffffff',
                        color: isDark ? '#f3f4f6' : '#1f2937',
                        customClass: {
                            popup: 'rounded-[3rem] p-8',
                        }
                    });
                }
            }
        }
    </script>

    <style>
        .animate-fade-in {
            animation: fadeIn 0.4s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</x-app-layout>
