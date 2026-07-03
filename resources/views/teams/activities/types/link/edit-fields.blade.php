<div class="space-y-6">
    <!-- URL -->
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
            </svg>
            {{ __('URL del Enlace') }}
        </h3>
        <div>
            <label for="url" class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2 font-sans">
                {{ __('Enlace') }}
            </label>
            <input type="url" name="url" id="url" value="{{ old('url', $activity->metadata['url'] ?? '') }}" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-blue-400 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all font-mono focus:ring focus:ring-blue-100 dark:focus:ring-blue-900/30" placeholder="https://...">
            <p class="text-[10px] text-gray-400 mt-1">{{ __('URL completa del recurso enlazado') }}</p>
        </div>
    </div>

    <!-- Status -->
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ __('Estado del Enlace') }}
        </h3>
        <div>
            <label for="status" class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2 font-sans">
                {{ __('Estado') }}
            </label>
            <select name="status" id="status" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-blue-400 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer focus:ring focus:ring-blue-100 dark:focus:ring-blue-900/30">
                <option value="active" {{ old('status', $activity->metadata['status'] ?? 'active') === 'active' ? 'selected' : '' }}>
                    {{ __('Activo') }}
                </option>
                <option value="broken" {{ old('status', $activity->metadata['status'] ?? 'active') === 'broken' ? 'selected' : '' }}>
                    {{ __('Roto') }}
                </option>
                <option value="archived" {{ old('status', $activity->metadata['status'] ?? 'active') === 'archived' ? 'selected' : '' }}>
                    {{ __('Archivado') }}
                </option>
            </select>
            <p class="text-[10px] text-gray-400 mt-1">{{ __('Estado actual del enlace o recurso') }}</p>
        </div>
    </div>

    <!-- Description -->
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7" />
            </svg>
            {{ __('Descripción') }}
        </h3>
        <textarea name="description" id="description" rows="4" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-blue-400 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all resize-y focus:ring focus:ring-blue-100 dark:focus:ring-blue-900/30" placeholder="Describe brevemente este enlace...">{{ old('description', $activity->description) }}</textarea>
    </div>
</div>
