@props(['team', 'folder', 'mode' => 'browser'])

@php
    $colorClasses = $folder->getColorStyles();
@endphp

<div x-data="{
        isHovered: false,
        isDragTarget: false,
        menuOpen: false,
        handleDrop(e) {
            this.isDragTarget = false;
            let data = {};
            try {
                data = JSON.parse(e.dataTransfer.getData('text/plain') || '{}');
            } catch(err) {
                return;
            }

            const docIds = data.document_ids || ((data.type === 'document' || data.type === 'link') ? [data.id] : []);
            const folderIds = data.folder_ids || (data.type === 'folder' ? [data.id] : []);

            if (docIds.length === 0 && folderIds.length === 0) return;
            if (folderIds.includes({{ $folder->id }})) return;

            $dispatch('bulk-move-items', {
                document_ids: docIds,
                folder_ids: folderIds,
                target_folder_id: {{ $folder->id }}
            });
        }
    }"
    draggable="true"
    @dragstart="startDrag($event, 'folder', {{ $folder->id }})"
    @dragover.prevent="isDragTarget = true"
    @dragleave.prevent="isDragTarget = false"
    @drop.prevent="handleDrop($event)"
    :class="selectedFolders.includes({{ $folder->id }}) ? 'ring-2 ring-violet-500 border-violet-500 bg-violet-50/25 dark:bg-violet-950/20' : (isDragTarget ? 'ring-2 ring-violet-500 scale-[1.02] bg-violet-50/50 dark:bg-violet-900/30' : 'bg-white dark:bg-gray-900 hover:border-gray-300 dark:hover:border-gray-700')"
    class="group relative border border-gray-200 dark:border-gray-800 rounded-2xl p-4 transition-all duration-200 shadow-sm hover:shadow-md flex flex-col justify-between select-none">

    <div class="flex items-start justify-between gap-3">
        <div class="flex items-start gap-2.5 min-w-0 flex-1">
            <label class="relative flex items-center cursor-pointer shrink-0 mt-3" @click.stop title="{{ __('Seleccionar carpeta') }}">
                <input type="checkbox"
                       :value="{{ $folder->id }}"
                       x-model.number="selectedFolders"
                       class="rounded-md border-gray-300 dark:border-gray-700 text-violet-600 focus:ring-violet-500 dark:bg-gray-800 w-4 h-4 transition-colors">
            </label>

            <!-- Enlace a la carpeta -->
            <a href="{{ route('teams.library', [$team, 'folder' => $folder->id, 'mode' => $mode]) }}"
               class="flex items-start gap-3 min-w-0 flex-1">
                <div class="w-11 h-11 rounded-xl {{ $colorClasses['bg'] }} {{ $colorClasses['text'] }} border {{ $colorClasses['border'] }} flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform shadow-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19.5 21a3 3 0 003-3v-4.5a3 3 0 00-3-3h-1.5V9a3 3 0 00-3-3h-3.379a3 3 0 01-2.121-.879L8.379 4.043A3 3 0 006.258 3.164H4.5A3 3 0 001.5 6.164v11.836A3 3 0 004.5 21h15z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-1.5 min-w-0">
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white truncate group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors" title="{{ $folder->name }}">
                            {{ $folder->name }}
                        </h4>
                        @if($folder->isPrivate())
                            <span class="inline-flex items-center shrink-0 text-amber-500" title="{{ __('Carpeta privada (solo tú y managers)') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </span>
                        @endif
                    </div>
                <div class="flex items-center gap-2 mt-1 text-[11px] text-gray-500 dark:text-gray-400 font-medium">
                    <span>{{ $folder->documents_count }} {{ trans_choice('documento|documentos', $folder->documents_count) }}</span>
                    @if(($folder->links_count ?? 0) > 0)
                        <span>•</span>
                        <span>{{ $folder->links_count }} {{ trans_choice('enlace|enlaces', $folder->links_count) }}</span>
                    @endif
                    @if($folder->children_count > 0)
                        <span>•</span>
                        <span>{{ $folder->children_count }} {{ trans_choice('carpeta|carpetas', $folder->children_count) }}</span>
                    @endif
                </div>
            </div>
        </a>
    </div>

    <!-- Menú contextual -->
        <div class="relative shrink-0" @click.away="menuOpen = false">
            <button @click.stop="menuOpen = !menuOpen"
                    type="button"
                    class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors"
                    title="{{ __('Opciones') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                </svg>
            </button>

            <!-- Dropdown desplegable -->
            <div x-show="menuOpen"
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="transform opacity-0 scale-95"
                 x-transition:enter-end="transform opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="transform opacity-100 scale-100"
                 x-transition:leave-end="transform opacity-0 scale-95"
                 class="absolute right-0 mt-1 w-44 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl z-20 py-1 text-xs"
                 style="display: none;">

                <button type="button"
                        @click="menuOpen = false; $dispatch('open-edit-folder-modal', { id: {{ $folder->id }}, name: '{{ addslashes($folder->name) }}', description: '{{ addslashes($folder->description ?? '') }}', color: '{{ $folder->color }}', visibility: '{{ $folder->visibility }}' })"
                        class="w-full text-left px-3 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    {{ __('Editar carpeta') }}
                </button>

                <button type="button"
                        @click="menuOpen = false; selectedFolders.includes({{ $folder->id }}) && totalSelected > 1 ? $dispatch('open-bulk-move-modal', { docIds: selectedDocs, folderIds: selectedFolders }) : $dispatch('open-move-modal', { type: 'folder', id: {{ $folder->id }}, name: '{{ addslashes($folder->name) }}', current_folder_id: {{ $folder->parent_id ?? 'null' }} })"
                        class="w-full text-left px-3 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                    <span x-text="selectedFolders.includes({{ $folder->id }}) && totalSelected > 1 ? '{{ __('Mover seleccionados') }} (' + totalSelected + ')...' : '{{ __('Mover carpeta') }}'"></span>
                </button>

                <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>

                <button type="button"
                        @click="menuOpen = false; selectedFolders.includes({{ $folder->id }}) && totalSelected > 1 ? $dispatch('open-bulk-delete-modal', { docIds: selectedDocs, folderIds: selectedFolders }) : $dispatch('open-delete-folder-modal', { id: {{ $folder->id }}, name: '{{ addslashes($folder->name) }}', documents_count: {{ $folder->documents_count }} })"
                        class="w-full text-left px-3 py-2 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/20 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span x-text="selectedFolders.includes({{ $folder->id }}) && totalSelected > 1 ? '{{ __('Eliminar seleccionados') }} (' + totalSelected + ')' : '{{ __('Eliminar carpeta') }}'"></span>
                </button>
            </div>
        </div>
    </div>

    @if($folder->description)
        <p class="text-[11px] text-gray-400 line-clamp-1 mt-2.5 pl-0.5">
            {{ $folder->description }}
        </p>
    @endif
</div>

