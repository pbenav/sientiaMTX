@props(['team'])

{{-- Barra flotante de acciones masivas --}}
<div x-show="totalSelected > 0"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 translate-y-6 scale-95"
     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
     x-transition:leave-end="opacity-0 translate-y-6 scale-95"
     style="display: none;"
     class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 bg-gray-900/95 dark:bg-gray-800/95 backdrop-blur-md text-white px-5 py-3 rounded-2xl shadow-2xl border border-gray-700/80 flex items-center gap-3 sm:gap-5 select-none max-w-xl w-auto">

    <!-- Contador de elementos seleccionados -->
    <div class="flex items-center gap-2.5 shrink-0">
        <span class="w-6 h-6 rounded-full bg-violet-600 text-white text-xs font-black flex items-center justify-center shrink-0 shadow-xs"
              x-text="totalSelected"></span>
        <div class="flex flex-col">
            <span class="text-xs font-bold text-gray-100 whitespace-nowrap">
                <span x-text="totalSelected"></span>
                <span x-text="totalSelected === 1 ? '{{ __('seleccionado') }}' : '{{ __('seleccionados') }}'"></span>
            </span>
            <span class="text-[10px] text-gray-400 font-medium hidden sm:inline">
                <template x-if="selectedDocs.length > 0">
                    <span x-text="`${selectedDocs.length} ${selectedDocs.length === 1 ? '{{ __('documento') }}' : '{{ __('documentos') }}'}`"></span>
                </template>
                <template x-if="selectedDocs.length > 0 && (selectedLinks.length > 0 || selectedFolders.length > 0)">
                    <span> • </span>
                </template>
                <template x-if="selectedLinks.length > 0">
                    <span x-text="`${selectedLinks.length} ${selectedLinks.length === 1 ? '{{ __('enlace') }}' : '{{ __('enlaces') }}'}`"></span>
                </template>
                <template x-if="selectedLinks.length > 0 && selectedFolders.length > 0">
                    <span> • </span>
                </template>
                <template x-if="selectedFolders.length > 0">
                    <span x-text="`${selectedFolders.length} ${selectedFolders.length === 1 ? '{{ __('carpeta') }}' : '{{ __('carpetas') }}'}`"></span>
                </template>
            </span>
        </div>
    </div>

    <div class="h-6 w-px bg-gray-700 shrink-0"></div>

    <!-- Botones de Acción -->
    <div class="flex items-center gap-2 shrink-0">
        <!-- Mover Selección -->
        <button type="button"
                @click="$dispatch('open-bulk-move-modal', { docIds: [...selectedDocs, ...selectedLinks], folderIds: selectedFolders })"
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-violet-600 hover:bg-violet-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs active:scale-95"
                title="{{ __('Mover elementos seleccionados a otra carpeta') }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
            </svg>
            <span>{{ __('Mover') }}</span>
        </button>

        <!-- Eliminar Selección -->
        <button type="button"
                @click="$dispatch('open-bulk-delete-modal', { docIds: [...selectedDocs, ...selectedLinks], folderIds: selectedFolders })"
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-rose-600/90 hover:bg-rose-600 text-white rounded-xl text-xs font-bold transition-all shadow-xs active:scale-95"
                title="{{ __('Eliminar elementos seleccionados') }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            <span>{{ __('Eliminar') }}</span>
        </button>

        <!-- Deseleccionar / Cancelar -->
        <button type="button"
                @click="clearSelection()"
                class="p-1.5 text-gray-400 hover:text-white hover:bg-gray-800 rounded-lg transition-colors ml-1"
                title="{{ __('Deseleccionar todo') }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</div>
