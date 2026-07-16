<div class="space-y-6">
    <!-- Decision Status -->
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ __('Estado de la Decisión') }}
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="status" class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2 font-sans">
                    {{ __('Estado') }}
                </label>
                <select name="status" id="status" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-emerald-400 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer focus:ring focus:ring-emerald-100 dark:focus:ring-emerald-900/30">
                    <option value="proposal" {{ old('status', $activity->metadata['status'] ?? 'proposal') === 'proposal' ? 'selected' : '' }}>
                        {{ __('Propuesta') }}
                    </option>
                    <option value="approved" {{ old('status', $activity->metadata['status'] ?? 'proposal') === 'approved' ? 'selected' : '' }}>
                        {{ __('Aprobada') }}
                    </option>
                    <option value="rejected" {{ old('status', $activity->metadata['status'] ?? 'proposal') === 'rejected' ? 'selected' : '' }}>
                        {{ __('Rechazada') }}
                    </option>
                    <option value="implemented" {{ old('status', $activity->metadata['status'] ?? 'proposal') === 'implemented' ? 'selected' : '' }}>
                        {{ __('Implementada') }}
                    </option>
                </select>
            </div>
            <div>
                <label for="agreement_type" class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2 font-sans">
                    {{ __('Tipo de Decisión') }}
                </label>
                <select name="agreement_type" id="agreement_type" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-emerald-400 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer focus:ring focus:ring-emerald-100 dark:focus:ring-emerald-900/30">
                    <option value="technical" {{ old('agreement_type', $activity->metadata['agreement_type'] ?? 'technical') === 'technical' ? 'selected' : '' }}>
                        {{ __('Técnica') }}
                    </option>
                    <option value="organizational" {{ old('agreement_type', $activity->metadata['agreement_type'] ?? 'technical') === 'organizational' ? 'selected' : '' }}>
                        {{ __('Organizativa') }}
                    </option>
                    <option value="architectural" {{ old('agreement_type', $activity->metadata['agreement_type'] ?? 'technical') === 'architectural' ? 'selected' : '' }}>
                        {{ __('Arquitectónica') }}
                    </option>
                    <option value="operational" {{ old('agreement_type', $activity->metadata['agreement_type'] ?? 'technical') === 'operational' ? 'selected' : '' }}>
                        {{ __('Operativa') }}
                    </option>
                </select>
            </div>
        </div>
    </div>

    <!-- Justification -->
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            {{ __('Justificación') }}
        </h3>
        <div x-data="{ showPreview: false }">
            <textarea name="justification" id="justification" rows="8" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-emerald-400 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all resize-y font-mono focus:ring focus:ring-emerald-100 dark:focus:ring-emerald-900/30" placeholder="Explica el razonamiento detrás de esta decisión...">{{ old('justification', $activity->metadata['justification'] ?? $activity->description) }}</textarea>
            <div class="flex items-center justify-between mt-3">
                <p class="text-[10px] text-gray-400 italic">{{ __('Soporta formato Markdown') }}</p>
                <button type="button" @click="showPreview = !showPreview" class="text-xs font-bold text-emerald-500 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors flex items-center gap-1">
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

    <!-- Observations -->
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            {{ __('Observaciones') }}
        </h3>
        <textarea name="observations" id="observations" rows="4" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-emerald-400 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all resize-y focus:ring focus:ring-emerald-100 dark:focus:ring-emerald-900/30" placeholder="Agrega notas o consideraciones adicionales...">{{ old('observations', $activity->metadata['observations'] ?? $activity->observations) }}</textarea>
    </div>
</div>
