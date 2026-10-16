@props(['team', 'link'])

@php
    $meta = $link->metadata ?? [];
    $url = method_exists($link, 'getUrl') ? ($link->getUrl() ?? '') : ($meta['url'] ?? ($meta['links'][0]['url'] ?? ''));
    $parsedHost = $url ? parse_url($url, PHP_URL_HOST) : null;
    $statusValue = $link->status_value ?? ($meta['status'] ?? 'active');
    $linksCount = !empty($meta['links']) && is_array($meta['links']) ? count($meta['links']) : ($url ? 1 : 0);
@endphp

<div x-data="{
        menuOpen: false,
        copied: false
    }"
    data-url="{{ $url }}"
    draggable="true"
    @dragstart="startDrag($event, 'link', {{ $link->id }})"
    class="group relative bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 transition-all duration-200 shadow-sm hover:shadow-md hover:border-purple-300 dark:hover:border-purple-800/60 flex flex-col justify-between cursor-pointer"
    :class="selectedLinks.includes({{ $link->id }}) ? 'ring-2 ring-purple-500 border-purple-500 bg-purple-50/25 dark:bg-purple-950/20' : ''"
    @click="if ($el.dataset.url) { window.open($el.dataset.url, '_blank', 'noopener,noreferrer'); } else { window.location.href = '{{ route('teams.activities.show', [$team, $link]) }}'; }">

    <div>
        <!-- Cabecera de la tarjeta: Checkbox, Icono, Badge de estado y Menú -->
        <div class="flex items-start justify-between gap-2 mb-3">
            <div class="flex items-center gap-2.5">
                <label class="relative flex items-center cursor-pointer shrink-0" @click.stop title="{{ __('Seleccionar enlace') }}">
                    <input type="checkbox"
                           :value="{{ $link->id }}"
                           x-model.number="selectedLinks"
                           class="rounded-md border-gray-300 dark:border-gray-700 text-purple-600 focus:ring-purple-500 dark:bg-gray-800 w-4 h-4 transition-colors">
                </label>

                <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 border border-purple-100 dark:border-purple-800/50 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                    </svg>
                </div>
            </div>

            <div class="flex items-center gap-1.5 shrink-0">
                <span class="px-2 py-0.5 text-[10px] font-bold rounded-lg uppercase tracking-wider border {{ $statusValue === 'active' || $statusValue === 'approved' || $statusValue === 'completed' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800' : ($statusValue === 'draft' ? 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800' : ($statusValue === 'broken' ? 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-900/30 dark:text-rose-400 dark:border-rose-800' : 'bg-gray-50 text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700')) }}">
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

                    <!-- Dropdown de acciones del enlace -->
                    <div x-show="menuOpen"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 mt-1 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl z-20 py-1 text-xs"
                         style="display: none;">

                        @if($url)
                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                               class="w-full text-left px-3 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                {{ __('Abrir Enlace Externo') }}
                            </a>

                            <button type="button"
                                    @click="navigator.clipboard.writeText($el.closest('[data-url]').dataset.url); copied = true; setTimeout(() => { copied = false; }, 2000)"
                                    class="w-full text-left px-3 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <span x-text="copied ? '{{ __('¡Copiado!') }}' : '{{ __('Copiar URL') }}'"></span>
                            </button>
                        @endif

                        <a href="{{ route('teams.activities.show', [$team, $link]) }}"
                           class="w-full text-left px-3 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            {{ __('Ver Ficha de Actividad') }}
                        </a>

                        <button type="button"
                                @click="menuOpen = false; selectedLinks.includes({{ $link->id }}) && totalSelected > 1 ? $dispatch('open-bulk-move-modal', { docIds: [...selectedDocs, ...selectedLinks], folderIds: selectedFolders }) : $dispatch('open-move-modal', { type: 'link', id: {{ $link->id }}, name: '{{ addslashes($link->title) }}', current_folder_id: {{ $link->folder_id ?? 'null' }} })"
                                class="w-full text-left px-3 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            <span x-text="selectedLinks.includes({{ $link->id }}) && totalSelected > 1 ? '{{ __('Mover seleccionados') }} (' + totalSelected + ')...' : '{{ __('Mover a carpeta...') }}'"></span>
                        </button>

                        <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>

                        <a href="{{ route('teams.activities.edit', [$team, $link]) }}"
                           class="w-full text-left px-3 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            {{ __('Editar Enlace') }}
                        </a>

                        <button type="button"
                                @click="menuOpen = false; selectedLinks.includes({{ $link->id }}) && totalSelected > 1 ? $dispatch('open-bulk-delete-modal', { docIds: [...selectedDocs, ...selectedLinks], folderIds: selectedFolders }) : $dispatch('open-delete-link-modal', { id: {{ $link->id }}, title: '{{ addslashes($link->title) }}' })"
                                class="w-full text-left px-3 py-2 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            {{ __('Eliminar') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Título y Dominio del Enlace -->
        <h4 class="text-sm font-bold text-gray-900 dark:text-white line-clamp-2 mb-1.5 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors"
            title="{{ $link->title }}">
            {{ $link->title }}
        </h4>

        @if($parsedHost)
            <div class="flex items-center gap-1.5 mb-2">
                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-purple-600 dark:text-purple-400 bg-purple-50/70 dark:bg-purple-900/30 px-2 py-0.5 rounded-lg border border-purple-100 dark:border-purple-800/40 truncate max-w-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                    </svg>
                    <span class="truncate">{{ $parsedHost }}</span>
                </span>
                @if($linksCount > 1)
                    <span class="text-[10px] font-bold text-gray-400 bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded-md">
                        +{{ $linksCount - 1 }}
                    </span>
                @endif
            </div>
        @endif

        @if($link->description)
            <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2 mb-3 leading-relaxed">
                {{ Str::limit(strip_tags($link->description), 100) }}
            </p>
        @endif

        @if(request('q') && $link->folder)
            <div class="mb-2">
                <span class="inline-flex items-center gap-1 text-[10px] font-medium text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-md">
                    📁 {{ $link->folder->name }}
                </span>
            </div>
        @endif
    </div>

    <!-- Pie de tarjeta: Autor y Fecha -->
    <div class="pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-[11px] text-gray-400 mt-2">
        <span class="truncate max-w-[120px]" title="{{ $link->creator?->name }}">
            {{ $link->creator?->name ?? '—' }}
        </span>
        <span class="shrink-0 font-medium">
            {{ $link->created_at->format('d/m/Y') }}
        </span>
    </div>
</div>
