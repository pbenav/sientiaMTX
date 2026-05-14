<x-app-layout>
    <div class="py-8 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-gray-50 to-white dark:from-gray-950 dark:to-gray-900 min-h-screen" 
         x-data="surveyManager()">
        <div class="max-w-5xl mx-auto">
            <!-- Header Section -->
            <div class="mb-10">
                <div class="flex items-center gap-4 mb-4">
                    <a href="{{ route('teams.surveys.index', $team) }}" class="p-3 bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 text-gray-500 hover:text-indigo-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                    <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight uppercase">
                        {{ __('Lanzar Nueva Encuesta') }}
                    </h1>
                </div>
                <p class="text-gray-500 dark:text-gray-400 font-medium ml-14">
                    {{ __('Diseña una experiencia de feedback única combinando diferentes tipos de preguntas.') }}
                </p>
            </div>

            <form action="{{ route('teams.surveys.store', $team) }}" method="POST" id="survey-create-form">
                @csrf
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Survey Identity Card -->
                        <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] p-8 shadow-xl shadow-indigo-500/5 border border-gray-100 dark:border-gray-800">
                            <div class="space-y-6">
                                <div>
                                    <label for="title" class="block text-xs font-black uppercase tracking-widest text-indigo-600 mb-3 ml-1">{{ __('Título de la Encuesta') }}</label>
                                    <input type="text" name="title" id="title" required
                                           class="w-full bg-gray-50 dark:bg-gray-800/50 border-2 border-transparent focus:border-indigo-500 rounded-3xl p-4 text-lg font-bold text-gray-900 dark:text-white transition-all placeholder-gray-400"
                                           placeholder="{{ __('Ej: Feedback Trimestral del Equipo') }}">
                                    @error('title') <p class="mt-2 text-sm text-red-500 font-bold ml-2">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="description" class="block text-xs font-black uppercase tracking-widest text-indigo-600 mb-3 ml-1">{{ __('Contexto / Descripción') }}</label>
                                    <textarea name="description" id="description" rows="3"
                                              class="w-full bg-gray-50 dark:bg-gray-800/50 border-2 border-transparent focus:border-indigo-500 rounded-3xl p-4 text-gray-700 dark:text-gray-300 transition-all placeholder-gray-400 font-medium"
                                              placeholder="{{ __('Explica brevemente el objetivo de esta consulta...') }}"></textarea>
                                </div>
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
                                            <div class="flex items-center gap-4">
                                                <div class="w-10 h-10 rounded-2xl bg-indigo-600 text-white flex items-center justify-center font-black text-lg shadow-lg">
                                                    <span x-text="qIndex + 1"></span>
                                                </div>
                                                <div class="flex-grow">
                                                    <input type="text" :name="`questions[${qIndex}][title]`" x-model="question.title" required
                                                           class="w-full bg-transparent border-b-2 border-gray-100 dark:border-gray-800 focus:border-indigo-500 px-0 py-2 text-xl font-black text-gray-900 dark:text-white placeholder-gray-300 transition-all"
                                                           placeholder="{{ __('Escribe tu pregunta aquí...') }}">
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
                                                        <input type="checkbox" :name="`questions[${qIndex}][is_required]`" x-model="question.is_required" class="sr-only peer" checked>
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
                                                            <input type="text" :name="`questions[${qIndex}][options][${oIndex}]`" x-model="question.options[oIndex]"
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
                                        <input type="datetime-local" name="expires_at" id="expires_at"
                                               class="w-full bg-gray-50 dark:bg-gray-800/50 border-2 border-transparent focus:border-indigo-500 rounded-2xl p-3 text-sm font-bold text-gray-700 dark:text-gray-300 transition-all">
                                        <p class="mt-2 text-[10px] text-gray-400 font-medium italic pl-1">{{ __('Opcional. Si no se indica, la encuesta será indefinida.') }}</p>
                                    </div>

                                    <div class="pt-4 space-y-4">
                                        <label class="flex items-center group cursor-pointer">
                                            <div class="relative">
                                                <input type="checkbox" name="show_results_before_voting" value="1" class="sr-only peer">
                                                <div class="w-10 h-5 bg-gray-200 dark:bg-gray-700 rounded-full peer-checked:bg-indigo-600 transition-colors after:content-[''] after:absolute after:top-1 after:left-1 after:bg-white after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:after:translate-x-5"></div>
                                            </div>
                                            <span class="ml-3 text-xs font-bold text-gray-600 dark:text-gray-400 group-hover:text-indigo-600 transition-colors">{{ __('Resultados públicos antes de votar') }}</span>
                                        </label>

                                        <label class="flex items-center group cursor-pointer">
                                            <div class="relative">
                                                <input type="checkbox" name="is_active" value="1" checked class="sr-only peer">
                                                <div class="w-10 h-5 bg-gray-200 dark:bg-gray-700 rounded-full peer-checked:bg-indigo-600 transition-colors after:content-[''] after:absolute after:top-1 after:left-1 after:bg-white after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:after:translate-x-5"></div>
                                            </div>
                                            <span class="ml-3 text-xs font-bold text-gray-600 dark:text-gray-400 group-hover:text-indigo-600 transition-colors">{{ __('Activa inmediatamente') }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" 
                                    class="w-full group relative inline-flex items-center justify-center px-8 py-5 font-black text-white tracking-widest uppercase transition-all duration-500 ease-in-out transform bg-indigo-600 rounded-[2rem] hover:scale-105 active:scale-95 shadow-[0_20px_50px_rgba(79,70,229,0.3)] hover:shadow-[0_20px_50px_rgba(79,70,229,0.5)] overflow-hidden">
                                <div class="absolute inset-0 w-full h-full bg-gradient-to-br from-indigo-500 via-indigo-600 to-violet-700"></div>
                                <span class="relative flex items-center gap-3">
                                    {{ __('Lanzar Encuesta') }}
                                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function surveyManager() {
            return {
                questions: [
                    {
                        id: Date.now(),
                        title: '',
                        type: 'single_choice',
                        is_required: true,
                        options: ['', '']
                    }
                ],
                addQuestion() {
                    this.questions.push({
                        id: Date.now(),
                        title: '',
                        type: 'single_choice',
                        is_required: true,
                        options: ['', '']
                    });
                },
                removeQuestion(index) {
                    if (this.questions.length > 1) {
                        this.questions.splice(index, 1);
                    }
                },
                addOption(qIndex) {
                    this.questions[qIndex].options.push('');
                },
                removeOption(qIndex, oIndex) {
                    if (this.questions[qIndex].options.length > 2) {
                        this.questions[qIndex].options.splice(oIndex, 1);
                    }
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
