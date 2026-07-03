<div class="space-y-6">
    <!-- Duration & Location -->
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ __('Duración y Ubicación') }}
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="duration_minutes" class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2 font-sans">
                    {{ __('Duración (minutos)') }}
                </label>
                <input type="number" name="duration_minutes" id="duration_minutes" value="{{ old('duration_minutes', $activity->metadata['duration_minutes'] ?? 60) }}" min="5" step="5" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-teal-400 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all focus:ring focus:ring-teal-100 dark:focus:ring-teal-900/30">
                <p class="text-[10px] text-gray-400 mt-1">{{ __('Duración estimada de la reunión') }}</p>
            </div>
            <div>
                <label for="location" class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2 font-sans">
                    {{ __('Ubicación / Link') }}
                </label>
                <input type="text" name="location" id="location" value="{{ old('location', $activity->metadata['location'] ?? '') }}" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-teal-400 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all font-mono focus:ring focus:ring-teal-100 dark:focus:ring-teal-900/30" placeholder="Sala de reuniones o link de videoconferencia">
                <p class="text-[10px] text-gray-400 mt-1">{{ __('Lugar físico o enlace de reunión virtual') }}</p>
            </div>
        </div>
    </div>

    <!-- Agenda -->
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>
            {{ __('Descripción / Agenda') }}
        </h3>
        <div x-data="{ showPreview: false }">
            <textarea name="description" id="description" rows="8" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-teal-400 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all resize-y font-mono focus:ring focus:ring-teal-100 dark:focus:ring-teal-900/30" placeholder="Escribe la agenda de la reunión...">{{ old('description', $activity->description) }}</textarea>
            <div class="flex items-center justify-between mt-3">
                <p class="text-[10px] text-gray-400 italic">{{ __('Soporta formato Markdown') }}</p>
                <button type="button" @click="showPreview = !showPreview" class="text-xs font-bold text-teal-500 hover:text-teal-600 dark:hover:text-teal-400 transition-colors flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <span x-text="showPreview ? 'Ocultar Preview' : 'Preview'"></span>
                </button>
            </div>
            <div x-show="showPreview" x-transition class="mt-4 p-4 bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl">
                <p class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">{{ __('Vista Previa Markdown') }}</p>
                <div x-html="marked(TurndownService ? TurndownService.marked(this.$el.previousElementSibling.value) : this.$el.previousElementSibling.value)" class="text-sm text-gray-700 dark:text-gray-300 prose prose-sm dark:prose-invert max-w-none"></div>
            </div>
        </div>
    </div>

    <!-- Minutes / Observations -->
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            {{ __('Observaciones / Acta') }}
        </h3>
        <textarea name="observations" id="observations" rows="6" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-teal-400 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all resize-y focus:ring focus:ring-teal-100 dark:focus:ring-teal-900/30" placeholder="Registro de acuerdos, acta de la reunión, observaciones...">{{ old('observations', $activity->metadata['observations'] ?? $activity->observations) }}</textarea>
        <p class="text-[10px] text-gray-400 mt-1">{{ __('Documenta los acuerdos y acta de la reunión') }}</p>
    </div>
</div>
