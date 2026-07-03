<div class="space-y-6">
    <!-- Version -->
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
            {{ __('Version del Documento') }}
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="version" class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2 font-sans">
                    {{ __('Versión') }}
                </label>
                <input type="text" name="version" id="version" value="{{ old('version', $activity->metadata['version'] ?? '1.0.0') }}" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-orange-400 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all font-mono focus:ring focus:ring-orange-100 dark:focus:ring-orange-900/30">
                <p class="text-[10px] text-gray-400 mt-1">Formato: MAJOR.MINOR.PATCH</p>
            </div>
            <div>
                <label for="is_ephemeral" class="flex items-center gap-2 text-xs font-bold text-gray-600 dark:text-gray-300 cursor-pointer">
                    <input type="checkbox" name="is_ephemeral" id="is_ephemeral" value="1" {{ old('is_ephemeral', $activity->metadata['is_ephemeral'] ?? false) ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-orange-500 focus:ring-orange-400 focus:ring-offset-0 dark:focus:ring-offset-gray-900">
                    <span>{{ __('¿Efímero?') }}</span>
                </label>
                <p class="text-[10px] text-gray-400 mt-2">{{ __('Los documentos efímeros no se almacenan permanentemente') }}</p>
            </div>
        </div>
    </div>

    <!-- Description -->
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" />
            </svg>
            {{ __('Descripción / Contenido') }}
        </h3>
        <div x-data="{ showPreview: false }">
            <textarea name="description" id="description" rows="10" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-orange-400 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all resize-y font-mono focus:ring focus:ring-orange-100 dark:focus:ring-orange-900/30" placeholder="Escribe la descripción del documento. Soporta formato Markdown...">{{ old('description', $activity->description) }}</textarea>
            <div class="flex items-center justify-between mt-3">
                <p class="text-[10px] text-gray-400 italic">{{ __('Soporta formato Markdown') }}</p>
                <button type="button" @click="showPreview = !showPreview" class="text-xs font-bold text-orange-500 hover:text-orange-600 dark:hover:text-orange-400 transition-colors flex items-center gap-1">
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
</div>
