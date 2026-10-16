@props(['team', 'subfolders', 'documents', 'links' => collect([]), 'currentFolder' => null])

<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
    <div x-data="tableScrollDrag"
        @mousedown="handleMouseDown($event)"
        @mousemove.window="handleMouseMove($event)"
        @mouseup.window="handleMouseUp()"
        @click.capture="if (hasMoved) { $event.preventDefault(); $event.stopPropagation(); $event.stopImmediatePropagation(); }"
        class="overflow-x-auto custom-scrollbar"
        :class="{ 'cursor-grab': hasOverflow && !isDown, 'cursor-grabbing select-none': isDown && hasMoved }">
        <table class="w-full text-left text-xs min-w-[650px]">
            <thead class="bg-gray-50/75 dark:bg-gray-800/40 border-b border-gray-100 dark:border-gray-800 text-[11px] font-bold text-gray-500 uppercase tracking-wider select-none">
                <tr>
                    <th scope="col" class="py-3 px-3 w-10 text-center">
                        <label class="relative inline-flex items-center cursor-pointer" title="{{ __('Seleccionar todos') }}">
                            <input type="checkbox"
                                   @change="isAllSelected ? clearSelection() : selectAll()"
                                   :checked="isAllSelected"
                                   class="rounded-md border-gray-300 dark:border-gray-700 text-violet-600 focus:ring-violet-500 dark:bg-gray-800 w-4 h-4 transition-colors">
                        </label>
                    </th>
                    <th scope="col" class="py-3 px-4">{{ __('Nombre') }}</th>
                    <th scope="col" class="py-3 px-4 hidden sm:table-cell">{{ __('Tipo') }}</th>
                    <th scope="col" class="py-3 px-4 hidden md:table-cell">{{ __('Estado') }}</th>
                    <th scope="col" class="py-3 px-4 hidden lg:table-cell">{{ __('Contenido') }}</th>
                    <th scope="col" class="py-3 px-4 hidden sm:table-cell">{{ __('Fecha') }}</th>
                    <th scope="col" class="py-3 px-4 text-right">{{ __('Acciones') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60 font-medium">

                {{-- Fila para subir de nivel si estamos dentro de una subcarpeta --}}
                @if($currentFolder)
                    <tr x-data="{
                            isDragTarget: false,
                            handleDrop(e) {
                                this.isDragTarget = false;
                                let data = {};
                                try {
                                    data = JSON.parse(e.dataTransfer.getData('text/plain') || '{}');
                                } catch(err) {
                                    return;
                                }
                                const docIds = data.document_ids || (data.type === 'document' ? [data.id] : []);
                                const folderIds = data.folder_ids || (data.type === 'folder' ? [data.id] : []);
                                if (docIds.length === 0 && folderIds.length === 0) return;

                                $dispatch('bulk-move-items', {
                                    document_ids: docIds,
                                    folder_ids: folderIds,
                                    target_folder_id: {{ $currentFolder->parent_id ? $currentFolder->parent_id : 'null' }}
                                });
                            }
                        }"
                        @dragover.prevent="isDragTarget = true"
                        @dragleave.prevent="isDragTarget = false"
                        @drop.prevent="handleDrop($event)"
                        :class="isDragTarget ? 'bg-violet-100 dark:bg-violet-900/50' : 'hover:bg-gray-50/60 dark:hover:bg-gray-800/30'"
                        class="transition-colors">
                        <td class="py-3 px-4" colspan="7">
                            <a href="{{ route('teams.library', array_filter([$team, 'folder' => $currentFolder->parent_id, 'mode' => 'browser'])) }}"
                               class="flex items-center gap-2 text-violet-600 dark:text-violet-400 font-bold hover:underline"
                               title="{{ __('Arrastra aquí para mover elementos a la carpeta superior') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
                                </svg>
                                <span>.. ({{ __('Subir un nivel') }})</span>
                            </a>
                        </td>
                    </tr>
                @endif

                {{-- Carpetas --}}
                @foreach($subfolders as $folder)
                    @php
                        $colorClasses = $folder->getColorStyles();
                    @endphp
                    <tr x-data="{
                            isDragTarget: false,
                            handleDrop(e) {
                                this.isDragTarget = false;
                                let data = {};
                                try {
                                    data = JSON.parse(e.dataTransfer.getData('text/plain') || '{}');
                                } catch(err) {
                                    return;
                                }
                                const docIds = data.document_ids || (data.type === 'document' ? [data.id] : []);
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
                        :class="selectedFolders.includes({{ $folder->id }}) ? 'bg-violet-50/50 dark:bg-violet-950/20' : (isDragTarget ? 'bg-violet-50 dark:bg-violet-900/40' : 'hover:bg-gray-50/60 dark:hover:bg-gray-800/30')"
                        class="transition-colors group">

                        <td class="py-3 px-3 text-center">
                            <label class="relative inline-flex items-center cursor-pointer" @click.stop title="{{ __('Seleccionar carpeta') }}">
                                <input type="checkbox"
                                       :value="{{ $folder->id }}"
                                       x-model.number="selectedFolders"
                                       class="rounded-md border-gray-300 dark:border-gray-700 text-violet-600 focus:ring-violet-500 dark:bg-gray-800 w-4 h-4 transition-colors">
                            </label>
                        </td>

                        <td class="py-3 px-4">
                            <a href="{{ route('teams.library', [$team, 'folder' => $folder->id, 'mode' => 'browser']) }}"
                               class="flex items-center gap-3 text-gray-900 dark:text-white font-bold group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors">
                                <div class="w-8 h-8 rounded-lg {{ $colorClasses['bg'] }} {{ $colorClasses['text'] }} flex items-center justify-center shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M19.5 21a3 3 0 003-3v-4.5a3 3 0 00-3-3h-1.5V9a3 3 0 00-3-3h-3.379a3 3 0 01-2.121-.879L8.379 4.043A3 3 0 006.258 3.164H4.5A3 3 0 001.5 6.164v11.836A3 3 0 004.5 21h15z" />
                                    </svg>
                                </div>
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="truncate max-w-xs md:max-w-md">{{ $folder->name }}</span>
                                    @if($folder->isPrivate())
                                        <span class="inline-flex items-center shrink-0 text-amber-500" title="{{ __('Carpeta privada (solo tú y managers)') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                        </span>
                                    @endif
                                </div>
                            </a>
                        </td>

                        <td class="py-3 px-4 hidden sm:table-cell text-gray-400">
                            <span class="inline-flex items-center gap-1">
                                {{ __('Carpeta') }}
                            </span>
                        </td>

                        <td class="py-3 px-4 hidden md:table-cell text-gray-400">
                            —
                        </td>

                        <td class="py-3 px-4 hidden lg:table-cell text-gray-500 dark:text-gray-400">
                            <span>{{ $folder->documents_count }} {{ trans_choice('doc.|docs.', $folder->documents_count) }}</span>
                            @if($folder->children_count > 0)
                                <span class="text-gray-300 dark:text-gray-600">•</span>
                                <span>{{ $folder->children_count }} {{ trans_choice('subcarp.', $folder->children_count) }}</span>
                            @endif
                        </td>

                        <td class="py-3 px-4 hidden sm:table-cell text-gray-400">
                            {{ $folder->created_at->format('d/m/Y') }}
                        </td>

                        <td class="py-3 px-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button type="button"
                                        @click="$dispatch('open-edit-folder-modal', { id: {{ $folder->id }}, name: '{{ addslashes($folder->name) }}', description: '{{ addslashes($folder->description ?? '') }}', color: '{{ $folder->color }}', visibility: '{{ $folder->visibility }}' })"
                                        class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                        title="{{ __('Editar') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button type="button"
                                        @click="selectedFolders.includes({{ $folder->id }}) && totalSelected > 1 ? $dispatch('open-bulk-move-modal', { docIds: selectedDocs, folderIds: selectedFolders }) : $dispatch('open-move-modal', { type: 'folder', id: {{ $folder->id }}, name: '{{ addslashes($folder->name) }}', current_folder_id: {{ $folder->parent_id ?? 'null' }} })"
                                        class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                        :title="selectedFolders.includes({{ $folder->id }}) && totalSelected > 1 ? '{{ __('Mover seleccionados') }} (' + totalSelected + ')...' : '{{ __('Mover') }}'">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                </button>
                                <button type="button"
                                        @click="selectedFolders.includes({{ $folder->id }}) && totalSelected > 1 ? $dispatch('open-bulk-delete-modal', { docIds: selectedDocs, folderIds: selectedFolders }) : $dispatch('open-delete-folder-modal', { id: {{ $folder->id }}, name: '{{ addslashes($folder->name) }}', documents_count: {{ $folder->documents_count }} })"
                                        class="p-1.5 text-gray-400 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                        :title="selectedFolders.includes({{ $folder->id }}) && totalSelected > 1 ? '{{ __('Eliminar seleccionados') }} (' + totalSelected + ')' : '{{ __('Eliminar') }}'">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach

                {{-- Documentos --}}
                @foreach($documents as $doc)
                    @php
                        $chaptersCount = is_array($doc->metadata['chapters'] ?? null) ? count($doc->metadata['chapters']) : 0;
                        $attachmentsCount = $doc->attachments->count();
                        $statusValue = $doc->status_value ?? 'draft';
                    @endphp
                    <tr draggable="true"
                        @dragstart="startDrag($event, 'document', {{ $doc->id }})"
                        :class="selectedDocs.includes({{ $doc->id }}) ? 'bg-violet-50/50 dark:bg-violet-950/20' : 'hover:bg-gray-50/60 dark:hover:bg-gray-800/30'"
                        class="transition-colors group">

                        <td class="py-3 px-3 text-center">
                            <label class="relative inline-flex items-center cursor-pointer" @click.stop title="{{ __('Seleccionar documento') }}">
                                <input type="checkbox"
                                       :value="{{ $doc->id }}"
                                       x-model.number="selectedDocs"
                                       class="rounded-md border-gray-300 dark:border-gray-700 text-violet-600 focus:ring-violet-500 dark:bg-gray-800 w-4 h-4 transition-colors">
                            </label>
                        </td>

                        <td class="py-3 px-4">
                            <a href="{{ route('teams.library', [$team, 'doc' => $doc->id, 'mode' => 'reader']) }}"
                               class="flex items-center gap-3 text-gray-900 dark:text-white font-bold group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors">
                                <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 flex items-center justify-center shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <span class="truncate block max-w-xs md:max-w-md">{{ $doc->title }}</span>
                                    @if(request('q') && $doc->folder)
                                        <span class="text-[10px] text-gray-400 font-normal flex items-center gap-1">
                                            📁 {{ $doc->folder->name }}
                                        </span>
                                    @endif
                                </div>
                            </a>
                        </td>

                        <td class="py-3 px-4 hidden sm:table-cell text-gray-400">
                            {{ __('Documento') }}
                        </td>

                        <td class="py-3 px-4 hidden md:table-cell">
                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-lg uppercase tracking-wider border {{ $statusValue === 'completed' || $statusValue === 'approved' || $statusValue === 'published' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800' : 'bg-gray-50 text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700' }}">
                                {{ __("activities.statuses.{$statusValue}") }}
                            </span>
                        </td>

                        <td class="py-3 px-4 hidden lg:table-cell text-gray-500 dark:text-gray-400">
                            <div class="flex items-center gap-2">
                                @if($chaptersCount > 0)
                                    <span class="flex items-center gap-1 text-gray-600 dark:text-gray-300">
                                        {{ $chaptersCount }} cap.
                                    </span>
                                @endif
                                @if($attachmentsCount > 0)
                                    <span class="flex items-center gap-1 text-blue-600 dark:text-blue-400 font-medium">
                                        📎 {{ $attachmentsCount }}
                                    </span>
                                @endif
                                @if($chaptersCount === 0 && $attachmentsCount === 0)
                                    <span class="text-gray-400">—</span>
                                @endif
                            </div>
                        </td>

                        <td class="py-3 px-4 hidden sm:table-cell text-gray-400">
                            {{ $doc->created_at->format('d/m/Y') }}
                        </td>

                        <td class="py-3 px-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('teams.library', [$team, 'doc' => $doc->id, 'mode' => 'reader']) }}"
                                   class="p-1.5 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                   title="{{ __('Leer') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                </a>

                                @if($chaptersCount > 0)
                                    <button type="button"
                                            @click="$dispatch('print-document-by-id', { id: {{ $doc->id }} })"
                                            class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                            title="{{ __('Imprimir Libro') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                        </svg>
                                    </button>
                                @endif

                                <button type="button"
                                        @click="selectedDocs.includes({{ $doc->id }}) && totalSelected > 1 ? $dispatch('open-bulk-move-modal', { docIds: selectedDocs, folderIds: selectedFolders }) : $dispatch('open-move-modal', { type: 'document', id: {{ $doc->id }}, name: '{{ addslashes($doc->title) }}', current_folder_id: {{ $doc->folder_id ?? 'null' }} })"
                                        class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                        :title="selectedDocs.includes({{ $doc->id }}) && totalSelected > 1 ? '{{ __('Mover seleccionados') }} (' + totalSelected + ')...' : '{{ __('Mover') }}'">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                </button>

                                <button type="button"
                                        @click="selectedDocs.includes({{ $doc->id }}) && totalSelected > 1 ? $dispatch('open-bulk-delete-modal', { docIds: selectedDocs, folderIds: selectedFolders }) : $dispatch('open-delete-doc-modal', { id: {{ $doc->id }}, title: '{{ addslashes($doc->title) }}' })"
                                        class="p-1.5 text-gray-400 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                        :title="selectedDocs.includes({{ $doc->id }}) && totalSelected > 1 ? '{{ __('Eliminar seleccionados') }} (' + totalSelected + ')' : '{{ __('Eliminar') }}'">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach

                {{-- Enlaces --}}
                @foreach($links as $link)
                    @php
                        $meta = $link->metadata ?? [];
                        $url = method_exists($link, 'getUrl') ? ($link->getUrl() ?? '') : ($meta['url'] ?? ($meta['links'][0]['url'] ?? ''));
                        $parsedHost = $url ? parse_url($url, PHP_URL_HOST) : null;
                        $statusValue = $link->status_value ?? ($meta['status'] ?? 'active');
                    @endphp
                    <tr draggable="true"
                        @dragstart="startDrag($event, 'link', {{ $link->id }})"
                        :class="selectedLinks.includes({{ $link->id }}) ? 'bg-purple-50/50 dark:bg-purple-950/20' : 'hover:bg-gray-50/60 dark:hover:bg-gray-800/30'"
                        class="transition-colors group">

                        <td class="py-3 px-3 text-center">
                            <label class="relative inline-flex items-center cursor-pointer" @click.stop title="{{ __('Seleccionar enlace') }}">
                                <input type="checkbox"
                                       :value="{{ $link->id }}"
                                       x-model.number="selectedLinks"
                                       class="rounded-md border-gray-300 dark:border-gray-700 text-purple-600 focus:ring-purple-500 dark:bg-gray-800 w-4 h-4 transition-colors">
                            </label>
                        </td>

                        <td class="py-3 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-purple-50 dark:bg-purple-900/30 text-purple-500 dark:text-purple-400 flex items-center justify-center shrink-0 border border-purple-100 dark:border-purple-800/50">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <a href="{{ $url ?: route('teams.activities.show', [$team, $link]) }}"
                                       @if($url) target="_blank" rel="noopener noreferrer" @endif
                                       class="text-gray-900 dark:text-white font-bold group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors truncate block max-w-xs md:max-w-md">
                                        {{ $link->title }}
                                    </a>
                                    @if(request('q') && $link->folder)
                                        <span class="text-[10px] text-gray-400 font-normal flex items-center gap-1">
                                            📁 {{ $link->folder->name }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td class="py-3 px-4 hidden sm:table-cell text-purple-600 dark:text-purple-400 font-semibold">
                            {{ __('Enlace') }}
                        </td>

                        <td class="py-3 px-4 hidden md:table-cell">
                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-lg uppercase tracking-wider border {{ $statusValue === 'active' || $statusValue === 'approved' || $statusValue === 'completed' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800' : ($statusValue === 'draft' ? 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800' : ($statusValue === 'broken' ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-900/30 dark:text-rose-400 dark:border-rose-800' : 'bg-gray-50 text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700')) }}">
                                {{ __("activities.statuses.{$statusValue}") }}
                            </span>
                        </td>

                        <td class="py-3 px-4 hidden lg:table-cell text-gray-500 dark:text-gray-400">
                            @if($parsedHost)
                                <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-purple-600 dark:text-purple-400 hover:underline max-w-[180px] truncate" title="{{ $url }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    <span class="truncate">{{ $parsedHost }}</span>
                                </a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>

                        <td class="py-3 px-4 hidden sm:table-cell text-gray-400">
                            {{ $link->created_at->format('d/m/Y') }}
                        </td>

                        <td class="py-3 px-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                @if($url)
                                    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                                       class="p-1.5 text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                       title="{{ __('Abrir enlace externo') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                @endif

                                <a href="{{ route('teams.activities.show', [$team, $link]) }}"
                                   class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                   title="{{ __('Ver Ficha de Actividad') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>

                                <button type="button"
                                        @click="selectedLinks.includes({{ $link->id }}) && totalSelected > 1 ? $dispatch('open-bulk-move-modal', { docIds: [...selectedDocs, ...selectedLinks], folderIds: selectedFolders }) : $dispatch('open-move-modal', { type: 'link', id: {{ $link->id }}, name: '{{ addslashes($link->title) }}', current_folder_id: {{ $link->folder_id ?? 'null' }} })"
                                        class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                        :title="selectedLinks.includes({{ $link->id }}) && totalSelected > 1 ? '{{ __('Mover seleccionados') }} (' + totalSelected + ')...' : '{{ __('Mover') }}'">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                </button>

                                <button type="button"
                                        @click="selectedLinks.includes({{ $link->id }}) && totalSelected > 1 ? $dispatch('open-bulk-delete-modal', { docIds: [...selectedDocs, ...selectedLinks], folderIds: selectedFolders }) : $dispatch('open-delete-link-modal', { id: {{ $link->id }}, title: '{{ addslashes($link->title) }}' })"
                                        class="p-1.5 text-gray-400 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                        :title="selectedLinks.includes({{ $link->id }}) && totalSelected > 1 ? '{{ __('Eliminar seleccionados') }} (' + totalSelected + ')' : '{{ __('Eliminar') }}'">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach

                @if($subfolders->isEmpty() && $documents->isEmpty() && $links->isEmpty())
                    <tr>
                        <td colspan="7" class="py-12 text-center text-gray-400 text-xs">
                            {{ __('Esta carpeta está vacía.') }}
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

