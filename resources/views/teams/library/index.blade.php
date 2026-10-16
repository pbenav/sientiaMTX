<x-app-layout>
    @section('title', __('Biblioteca') . ' de Documentos — ' . $team->name)

    <x-slot name="header">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2 min-w-0">
                <a href="{{ route('teams.dashboard', $team) }}"
                    class="p-1.5 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-lg transition-all shrink-0"
                    title="{{ __('Back') }} al escritorio">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                @include('teams.partials.breadcrumb')
                <span class="text-gray-300 dark:text-gray-700 mx-1">/</span>
                <h1 class="text-base font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span class="truncate">{{ __('Biblioteca y Navegador de Archivos') }}</span>
                </h1>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @include('teams.partials.header-toolbar')
            </div>
        </div>

        <div class="mt-2">
            <x-demo-hint>
                {{ __('Organiza los documentos del equipo en carpetas y subcarpetas, arrastra para mover, gestiona capítulos y edita archivos de oficina en tiempo real.') }}
            </x-demo-hint>
        </div>

        @include('teams.partials.team-view-nav', ['showCreateActions' => false])
    </x-slot>

    <script>
    function libraryBrowser(config) {
        return {
            viewLayout: localStorage.getItem('library_view_layout') || 'grid',
            setLayout(val) {
                this.viewLayout = val;
                localStorage.setItem('library_view_layout', val);
            },
            selectedDocs: [],
            selectedLinks: [],
            selectedFolders: [],
            allDocIds: config.allDocIds || [],
            allLinkIds: config.allLinkIds || [],
            allFolderIds: config.allFolderIds || [],
            selectAll() {
                this.selectedDocs = [...this.allDocIds];
                this.selectedLinks = [...this.allLinkIds];
                this.selectedFolders = [...this.allFolderIds];
            },
            clearSelection() {
                this.selectedDocs = [];
                this.selectedLinks = [];
                this.selectedFolders = [];
            },
            get isAllSelected() {
                const total = this.allDocIds.length + this.allLinkIds.length + this.allFolderIds.length;
                return total > 0 && (this.selectedDocs.length + this.selectedLinks.length + this.selectedFolders.length) === total;
            },
            get totalSelected() {
                return this.selectedDocs.length + this.selectedLinks.length + this.selectedFolders.length;
            },
            isMovingItems: false,
            startDrag(event, type, id) {
                let docIds = [];
                let folderIds = [];
                const combinedSelected = [...this.selectedDocs, ...this.selectedLinks];

                if (type === 'document' || type === 'link') {
                    if (this.selectedDocs.includes(id) || this.selectedLinks.includes(id)) {
                        docIds = [...combinedSelected];
                        folderIds = [...this.selectedFolders];
                    } else if (combinedSelected.length > 0 || this.selectedFolders.length > 0) {
                        docIds = Array.from(new Set([...combinedSelected, id]));
                        folderIds = [...this.selectedFolders];
                    } else {
                        docIds = [id];
                        folderIds = [];
                    }
                } else if (type === 'folder') {
                    if (this.selectedFolders.includes(id)) {
                        docIds = [...combinedSelected];
                        folderIds = [...this.selectedFolders];
                    } else if (combinedSelected.length > 0 || this.selectedFolders.length > 0) {
                        docIds = [...combinedSelected];
                        folderIds = Array.from(new Set([...this.selectedFolders, id]));
                    } else {
                        docIds = [];
                        folderIds = [id];
                    }
                }

                const payload = {
                    document_ids: docIds,
                    folder_ids: folderIds,
                    type: type,
                    id: id
                };

                event.dataTransfer.setData('text/plain', JSON.stringify(payload));
                event.dataTransfer.effectAllowed = 'move';

                this.createDragGhost(event, docIds.length, folderIds.length);
            },
            createDragGhost(event, docCount, folderCount) {
                const total = docCount + folderCount;
                if (total <= 1) return;

                const ghost = document.createElement('div');
                ghost.style.position = 'absolute';
                ghost.style.top = '-9999px';
                ghost.style.left = '-9999px';
                ghost.style.padding = '6px 14px';
                ghost.style.background = '#7c3aed';
                ghost.style.color = '#ffffff';
                ghost.style.borderRadius = '12px';
                ghost.style.fontSize = '12px';
                ghost.style.fontWeight = '700';
                ghost.style.boxShadow = '0 10px 25px -5px rgba(0, 0, 0, 0.4)';
                ghost.style.display = 'flex';
                ghost.style.alignItems = 'center';
                ghost.style.gap = '8px';
                ghost.style.pointerEvents = 'none';
                ghost.style.zIndex = '99999';

                let label = '';
                if (docCount > 0 && folderCount > 0) {
                    label = `${docCount} elem. y ${folderCount} carp.`;
                } else if (docCount > 0) {
                    label = docCount === 1 ? '1 elemento' : `${docCount} elementos`;
                } else {
                    label = folderCount === 1 ? '1 carpeta' : `${folderCount} carpetas`;
                }

                const badge = document.createElement('span');
                badge.style.background = 'rgba(255,255,255,0.25)';
                badge.style.borderRadius = '9999px';
                badge.style.padding = '1px 7px';
                badge.style.fontSize = '11px';
                badge.textContent = total;

                const textSpan = document.createElement('span');
                textSpan.textContent = label;

                ghost.appendChild(badge);
                ghost.appendChild(textSpan);

                document.body.appendChild(ghost);
                if (event.dataTransfer && event.dataTransfer.setDragImage) {
                    event.dataTransfer.setDragImage(ghost, 20, 20);
                }
                setTimeout(() => {
                    if (ghost.parentNode) {
                        ghost.parentNode.removeChild(ghost);
                    }
                }, 0);
            }
        };
    }
    </script>

    <div x-data="libraryBrowser({
            allDocIds: @json($documents->pluck('id')->values()),
            allLinkIds: @json($links->pluck('id')->values()),
            allFolderIds: @json($subfolders->pluck('id')->values())
        })"
        @bulk-move-items.window="isMovingItems = true">
        
        {{-- Barra superior de navegación por carpetas y herramientas --}}
        @include('teams.library.partials.header-toolbar', [
            'team' => $team,
            'currentFolder' => $currentFolder,
            'breadcrumbs' => $breadcrumbs,
            'mode' => $mode,
            'searchQuery' => $searchQuery,
            'searchScope' => $searchScope,
            'subfoldersCount' => $subfolders->count(),
            'documentsCount' => $documents->count(),
            'linksCount' => $links->count(),
        ])

        {{-- Modo 1: EXPLORADOR DE ARCHIVOS (Grid o Lista) --}}
        @if($mode === 'browser')
            <div class="space-y-6">
                <!-- Barra de control de vista (Grid vs List y Selección) -->
                <div class="flex items-center justify-between gap-3 text-xs">
                    <div class="flex items-center gap-3 text-gray-500 font-semibold">
                        @if($currentFolder)
                            <span class="flex items-center gap-1.5 text-gray-800 dark:text-gray-200">
                                <span class="w-2.5 h-2.5 rounded-full bg-violet-500"></span>
                                {{ $currentFolder->name }}
                            </span>
                        @else
                            <span>{{ __('Raíz de la Biblioteca') }}</span>
                        @endif

                        @if($documents->isNotEmpty() || $links->isNotEmpty() || $subfolders->isNotEmpty())
                            <span class="text-gray-300 dark:text-gray-700">|</span>
                            <button type="button"
                                    @click="isAllSelected ? clearSelection() : selectAll()"
                                    class="text-[11px] font-bold text-violet-600 dark:text-violet-400 hover:underline flex items-center gap-1 transition-colors">
                                <span x-text="isAllSelected ? '{{ __('Deseleccionar todo') }}' : '{{ __('Seleccionar todo') }}'"></span>
                            </button>
                        @endif
                    </div>

                    <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 p-0.5 rounded-xl border border-gray-200 dark:border-gray-700">
                        <button type="button" 
                                @click="setLayout('grid')" 
                                :class="viewLayout === 'grid' ? 'bg-white dark:bg-gray-900 text-violet-600 dark:text-violet-400 shadow-xs' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                                class="p-1.5 rounded-lg transition-all"
                                title="{{ __('Vista en cuadrícula') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                        </button>
                        <button type="button" 
                                @click="setLayout('list')" 
                                :class="viewLayout === 'list' ? 'bg-white dark:bg-gray-900 text-violet-600 dark:text-violet-400 shadow-xs' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                                class="p-1.5 rounded-lg transition-all"
                                title="{{ __('Vista en lista detallada') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Visualización en Cuadrícula (Grid) --}}
                <div x-show="viewLayout === 'grid'" class="space-y-8">
                    {{-- Sección de Subcarpetas --}}
                    @if($subfolders->isNotEmpty())
                        <div>
                            <h3 class="text-xs font-black uppercase tracking-wider text-gray-400 mb-3 flex items-center gap-2 select-none">
                                <span>{{ __('Carpetas') }}</span>
                                <span class="text-[10px] bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-full font-bold text-gray-500">
                                    {{ $subfolders->count() }}
                                </span>
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                @foreach($subfolders as $folder)
                                    @include('teams.library.partials.folder-card', ['team' => $team, 'folder' => $folder, 'mode' => $mode])
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Sección de Documentos --}}
                    @if($documents->isNotEmpty())
                        <div>
                            <h3 class="text-xs font-black uppercase tracking-wider text-gray-400 mb-3 flex items-center gap-2 select-none">
                                <span>{{ __('Documentos') }}</span>
                                <span class="text-[10px] bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-full font-bold text-gray-500">
                                    {{ $documents->count() }}
                                </span>
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                @foreach($documents as $doc)
                                    @include('teams.library.partials.document-card', ['team' => $team, 'doc' => $doc])
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Sección de Enlaces --}}
                    @if($links->isNotEmpty())
                        <div>
                            <h3 class="text-xs font-black uppercase tracking-wider text-purple-600 dark:text-purple-400 mb-3 flex items-center gap-2 select-none">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                                <span>{{ __('Enlaces y Recursos Web') }}</span>
                                <span class="text-[10px] bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 border border-purple-100 dark:border-purple-800/50 px-2 py-0.5 rounded-full font-bold">
                                    {{ $links->count() }}
                                </span>
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                @foreach($links as $link)
                                    @include('teams.library.partials.link-card', ['team' => $team, 'link' => $link])
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Estado vacío si no hay documentos ni enlaces --}}
                    @if($documents->isEmpty() && $links->isEmpty())
                        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-12 text-center">
                            <div class="w-14 h-14 bg-gray-50 dark:bg-gray-800 rounded-2xl flex items-center justify-center mx-auto mb-3 text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white mb-1">{{ __('No hay documentos ni enlaces aquí') }}</h4>
                            <p class="text-xs text-gray-400 max-w-sm mx-auto mb-5">{{ __('Puedes crear un nuevo documento, guardar enlaces web o mover elementos existentes a esta carpeta.') }}</p>
                            <div class="flex items-center justify-center gap-2.5 flex-wrap">
                                <a href="{{ route('teams.activities.create', array_filter(['team' => $team, 'type' => 'document', 'folder_id' => $currentFolder?->id])) }}"
                                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-xl text-xs font-bold transition-colors shadow-xs">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                    </svg>
                                    {{ __('Crear Documento') }}
                                </a>
                                <a href="{{ route('teams.activities.create', array_filter(['team' => $team, 'type' => 'link', 'folder_id' => $currentFolder?->id])) }}"
                                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 rounded-xl text-xs font-bold transition-colors shadow-xs">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                    </svg>
                                    {{ __('Añadir Enlace') }}
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Visualización en Lista (List / Table) --}}
                <div x-show="viewLayout === 'list'" style="display: none;">
                    @include('teams.library.partials.list-view', [
                        'team' => $team,
                        'subfolders' => $subfolders,
                        'documents' => $documents,
                        'links' => $links,
                        'currentFolder' => $currentFolder,
                    ])
                </div>
            </div>

        {{-- Modo 2: LECTOR WIKI --}}
        @else
            @include('teams.library.partials.reader-view', [
                'team' => $team,
                'documents' => $documents,
                'activeDocument' => $activeDocument,
                'currentFolder' => $currentFolder,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ])
        @endif

        {{-- Barra flotante de acciones masivas --}}
        @include('teams.library.partials.bulk-bar', ['team' => $team])

        {{-- Modales de Creación, Edición, Mover y Eliminar --}}
        @include('teams.library.partials.modals', [
            'team' => $team,
            'currentFolder' => $currentFolder,
            'folderOptions' => $folderOptions,
        ])
        {{-- Indicador flotante mientras se mueven elementos en segundo plano --}}
        <div x-show="isMovingItems"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             style="display: none;"
             class="fixed inset-0 z-50 bg-gray-900/40 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl px-6 py-4 shadow-2xl flex items-center gap-3 text-sm font-bold text-gray-800 dark:text-gray-200">
                <svg class="animate-spin h-5 w-5 text-violet-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span>{{ __('Moviendo elementos...') }}</span>
            </div>
        </div>
    </div>

    {{-- Script de impresión y eventos auxiliares --}}
    <script>
    window.addEventListener('print-document-by-id', async (e) => {
        const docId = e.detail.id;
        try {
            const res = await fetch(`{{ route('teams.activities.show', [$team, ':DOC_ID']) }}`.replace(':DOC_ID', docId), {
                headers: { 'Accept': 'application/json' }
            });
            // Si la vista normal tiene el objeto, o bien abrimos el lector
            window.location.href = `{{ route('teams.library', [$team]) }}?doc=${docId}&mode=reader`;
        } catch(err) {
            console.error(err);
        }
    });

    @if(isset($activeDocument))
    function printDocumentBook() {
        if (typeof SientiaPrint !== 'undefined') {
            SientiaPrint.printDocumentBook({
                title:    @json($activeDocument->title ?? ''),
                teamName: @json($team->name ?? ''),
                version:  @json($activeDocument->metadata['version'] ?? '1.0.0'),
                chapters: @json($activeDocument->metadata['chapters'] ?? []),
                attachments: {!! json_encode(isset($activeDocument) && $activeDocument->attachments ? $activeDocument->attachments->map(fn($att) => ['name' => $att->file_name, 'url' => route('teams.activities.attachments.view', [$team, $activeDocument, $att]), 'mime_type' => $att->mime_type, 'is_image' => str_starts_with($att->mime_type, 'image/')])->toArray() : []) !!}
            });
        } else {
            window.print();
        }
    }
    @endif
    </script>
</x-app-layout>
