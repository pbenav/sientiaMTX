@props(['team', 'doc'])

@php
    $chaptersCount = is_array($doc->metadata['chapters'] ?? null) ? count($doc->metadata['chapters']) : 0;
    $attachmentsCount = $doc->attachments->count();
    $statusValue = $doc->status_value ?? 'draft';
@endphp

<div x-data="{
        menuOpen: false
    }"
    draggable="true"
    @dragstart="startDrag($event, 'document', {{ $doc->id }})"
    class="group relative bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 transition-all duration-200 shadow-sm hover:shadow-md hover:border-violet-300 dark:hover:border-violet-800/60 flex flex-col justify-between cursor-pointer"
    :class="selectedDocs.includes({{ $doc->id }}) ? 'ring-2 ring-violet-500 border-violet-500 bg-violet-50/25 dark:bg-violet-950/20' : ''"
    @click="window.location.href = '{{ route('teams.library', [$team, 'doc' => $doc->id, 'mode' => 'reader']) }}'">

    <div>
        <!-- Cabecera de la tarjeta: Checkbox, Icono, Badge de estado y Menú -->
        <div class="flex items-start justify-between gap-2 mb-3">
            <div class="flex items-center gap-2.5">
                <label class="relative flex items-center cursor-pointer shrink-0" @click.stop title="{{ __('Seleccionar documento') }}">
                    <input type="checkbox"
                           :value="{{ $doc->id }}"
                           x-model.number="selectedDocs"
                           class="rounded-md border-gray-300 dark:border-gray-700 text-violet-600 focus:ring-violet-500 dark:bg-gray-800 w-4 h-4 transition-colors">
                </label>

                <div class="w-10 h-10 rounded-xl bg-violet-50 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 border border-violet-100 dark:border-violet-800/50 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>

            <div class="flex items-center gap-1.5 shrink-0">
                <span class="px-2 py-0.5 text-[10px] font-bold rounded-lg uppercase tracking-wider border {{ $statusValue === 'completed' || $statusValue === 'approved' || $statusValue === 'published' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800' : 'bg-gray-50 text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700' }}">
                    {{ __("activities.statuses.{$statusValue}") }}
                </span>

                <!-- Botón de opciones -->
                <div class="relative" @click.stop @click.away="menuOpen = false">
                    <button @click="menuOpen = !menuOpen"
                            type="button"
                            class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors"
                            title="{{ __('Opciones') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                        </svg>
                    </button>

                    <!-- Dropdown de acciones del documento -->
                    <div x-show="menuOpen"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 mt-1 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl z-20 py-1 text-xs"
                         style="display: none;">

                        <a href="{{ route('teams.library', [$team, 'doc' => $doc->id, 'mode' => 'reader']) }}"
                           class="w-full text-left px-3 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            {{ __('Abrir en Lector') }}
                        </a>

                        <a href="{{ route('teams.activities.show', [$team, $doc]) }}"
                           class="w-full text-left px-3 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                            {{ __('Ver Ficha de Actividad') }}
                        </a>

                        @if($chaptersCount > 0)
                            <button type="button"
                                    @click="menuOpen = false; $dispatch('print-document-by-id', { id: {{ $doc->id }} })"
                                    class="w-full text-left px-3 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                {{ __('Imprimir Libro') }}
                            </button>
                        @endif

                        <button type="button"
                                @click="menuOpen = false; selectedDocs.includes({{ $doc->id }}) && totalSelected > 1 ? $dispatch('open-bulk-move-modal', { docIds: selectedDocs, folderIds: selectedFolders }) : $dispatch('open-move-modal', { type: 'document', id: {{ $doc->id }}, name: '{{ addslashes($doc->title) }}', current_folder_id: {{ $doc->folder_id ?? 'null' }} })"
                                class="w-full text-left px-3 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            <span x-text="selectedDocs.includes({{ $doc->id }}) && totalSelected > 1 ? '{{ __('Mover seleccionados') }} (' + totalSelected + ')...' : '{{ __('Mover a carpeta...') }}'"></span>
                        </button>

                        <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>

                        <button type="button"
                                @click="menuOpen = false; selectedDocs.includes({{ $doc->id }}) && totalSelected > 1 ? $dispatch('open-bulk-delete-modal', { docIds: selectedDocs, folderIds: selectedFolders }) : $dispatch('open-delete-doc-modal', { id: {{ $doc->id }}, title: '{{ addslashes($doc->title) }}' })"
                                class="w-full text-left px-3 py-2 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/20 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span x-text="selectedDocs.includes({{ $doc->id }}) && totalSelected > 1 ? '{{ __('Eliminar seleccionados') }} (' + totalSelected + ')' : '{{ __('Eliminar documento') }}'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Título del documento -->
        <h4 class="text-sm font-bold text-gray-900 dark:text-white line-clamp-2 group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors mb-1.5" title="{{ $doc->title }}">
            {{ $doc->title }}
        </h4>

        @if($doc->description)
            <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2 mb-3">
                {{ Str::limit(strip_tags($doc->description), 110) }}
            </p>
        @endif
    </div>

    <!-- Pie de la tarjeta: Metadatos y Fecha -->
    <div class="pt-3 border-t border-gray-100 dark:border-gray-800/80 flex items-center justify-between text-[11px] text-gray-400 mt-2">
        <div class="flex items-center gap-2.5">
            @if($chaptersCount > 0)
                <span class="flex items-center gap-1 font-medium text-gray-600 dark:text-gray-300" title="{{ $chaptersCount }} capítulos estructurados">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    {{ $chaptersCount }} cap.
                </span>
            @endif

            @if($attachmentsCount > 0)
                <span class="flex items-center gap-1 font-medium text-blue-600 dark:text-blue-400" title="{{ $attachmentsCount }} archivos adjuntos (OnlyOffice)">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                    </svg>
                    {{ $attachmentsCount }}
                </span>
            @endif
        </div>

        <span class="text-[10px] text-gray-400">
            {{ $doc->created_at->format('d/m/Y') }}
        </span>
    </div>
</div>

