@props(['team', 'currentFolder' => null, 'folderOptions' => []])

<div x-data="{
        // Modal Crear Carpeta
        createOpen: false,
        createColor: 'violet',
        createVisibility: '{{ ($currentFolder && $currentFolder->isPrivate()) ? "private" : "public" }}',
        createParentId: {{ $currentFolder ? $currentFolder->id : 'null' }},

        // Modal Editar Carpeta
        editOpen: false,
        editId: null,
        editName: '',
        editDescription: '',
        editColor: 'violet',
        editVisibility: 'public',

        // Modal Mover
        moveOpen: false,
        moveIsBulk: false,
        bulkDocIds: [],
        bulkFolderIds: [],
        moveType: 'document',
        moveId: null,
        moveName: '',
        moveCurrentFolderId: null,
        moveTargetFolderId: '',
        moveLoading: false,

        // Modal Eliminar
        deleteOpen: false,
        deleteIsBulk: false,
        bulkDeleteDocIds: [],
        bulkDeleteFolderIds: [],
        deleteType: 'document',
        deleteId: null,
        deleteTitle: '',
        deleteDocCount: 0,
        deleteCascade: false,

        // Inicializador de eventos
        init() {
            window.addEventListener('open-create-folder-modal', (e) => {
                this.createParentId = e.detail?.parent_id ?? {{ $currentFolder ? $currentFolder->id : 'null' }};
                this.createColor = 'violet';
                this.createVisibility = e.detail?.visibility ?? '{{ ($currentFolder && $currentFolder->isPrivate()) ? "private" : "public" }}';
                this.createOpen = true;
            });

            window.addEventListener('open-edit-folder-modal', (e) => {
                this.editId = e.detail.id;
                this.editName = e.detail.name;
                this.editDescription = e.detail.description || '';
                this.editColor = e.detail.color || 'violet';
                this.editVisibility = e.detail.visibility || 'public';
                this.editOpen = true;
            });

            window.addEventListener('open-move-modal', (e) => {
                this.moveIsBulk = false;
                this.bulkDocIds = [];
                this.bulkFolderIds = [];
                this.moveType = e.detail.type;
                this.moveId = e.detail.id;
                this.moveName = e.detail.name;
                this.moveCurrentFolderId = e.detail.current_folder_id;
                this.moveTargetFolderId = '';
                this.moveOpen = true;
            });

            window.addEventListener('open-bulk-move-modal', (e) => {
                this.moveIsBulk = true;
                this.bulkDocIds = e.detail.docIds || [];
                this.bulkFolderIds = e.detail.folderIds || [];
                this.moveType = 'bulk';
                this.moveId = null;
                this.moveName = '';
                this.moveTargetFolderId = '';
                this.moveOpen = true;
            });

            window.addEventListener('open-delete-folder-modal', (e) => {
                this.deleteIsBulk = false;
                this.deleteType = 'folder';
                this.deleteId = e.detail.id;
                this.deleteTitle = e.detail.name;
                this.deleteDocCount = e.detail.documents_count || 0;
                this.deleteCascade = false;
                this.deleteOpen = true;
            });

            window.addEventListener('open-delete-doc-modal', (e) => {
                this.deleteIsBulk = false;
                this.deleteType = 'document';
                this.deleteId = e.detail.id;
                this.deleteTitle = e.detail.title;
                this.deleteOpen = true;
            });

            window.addEventListener('open-delete-link-modal', (e) => {
                this.deleteIsBulk = false;
                this.deleteType = 'link';
                this.deleteId = e.detail.id;
                this.deleteTitle = e.detail.title;
                this.deleteOpen = true;
            });

            window.addEventListener('open-bulk-delete-modal', (e) => {
                this.deleteIsBulk = true;
                this.bulkDeleteDocIds = e.detail.docIds || [];
                this.bulkDeleteFolderIds = e.detail.folderIds || [];
                this.deleteCascade = false;
                this.deleteOpen = true;
            });

            window.addEventListener('bulk-move-items', (e) => {
                this.quickBulkMove(
                    e.detail.document_ids || [],
                    e.detail.folder_ids || [],
                    e.detail.target_folder_id ?? null
                );
            });

            window.addEventListener('move-item', (e) => {
                const docIds = (e.detail.type === 'document' || e.detail.type === 'link') ? [e.detail.id] : [];
                const folderIds = e.detail.type === 'folder' ? [e.detail.id] : [];
                this.quickBulkMove(docIds, folderIds, e.detail.target_folder_id ?? null);
            });
        },

        async quickBulkMove(documentIds, folderIds, targetFolderId) {
            if (documentIds.length === 0 && folderIds.length === 0) return;

            try {
                const res = await fetch('{{ route('teams.library.bulk-move', $team) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        document_ids: documentIds,
                        folder_ids: folderIds,
                        target_folder_id: targetFolderId
                    })
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Error al mover elementos');
                }
            } catch(e) {
                console.error(e);
                alert('Error de conexión al mover los elementos seleccionados.');
            }
        }
    }">

    {{-- MODAL CREAR CARPETA --}}
    <div x-show="createOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;"
         @keydown.escape.window="createOpen = false">

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl max-w-md w-full p-6 shadow-2xl relative"
             @click.away="createOpen = false">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                </svg>
                {{ __('Nueva Carpeta') }}
            </h3>
            <p class="text-xs text-gray-500 mb-4">
                {{ $currentFolder ? __('Se creará dentro de') . ' "' . $currentFolder->name . '"' : __('Se creará en la raíz de la biblioteca') }}
            </p>

            <form action="{{ route('teams.library.folders.store', $team) }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="parent_id" :value="createParentId">

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Nombre de la carpeta') }} *
                    </label>
                    <input type="text" name="name" required autofocus
                           class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-violet-500 outline-none transition-all placeholder-gray-400"
                           placeholder="Ej. Procedimientos, Informes 2026...">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Color de identificación') }}
                    </label>
                    <div class="flex items-center gap-2 flex-wrap">
                        @foreach(['violet' => 'bg-violet-500', 'blue' => 'bg-blue-500', 'emerald' => 'bg-emerald-500', 'amber' => 'bg-amber-500', 'rose' => 'bg-rose-500', 'indigo' => 'bg-indigo-500', 'cyan' => 'bg-cyan-500', 'gray' => 'bg-gray-500'] as $cKey => $cBg)
                            <button type="button"
                                    @click="createColor = '{{ $cKey }}'"
                                    :class="createColor === '{{ $cKey }}' ? 'ring-2 ring-offset-2 ring-violet-500 scale-110' : 'opacity-70 hover:opacity-100'"
                                    class="w-7 h-7 rounded-full {{ $cBg }} transition-all focus:outline-none"
                                    title="{{ ucfirst($cKey) }}">
                            </button>
                        @endforeach
                    </div>
                    <input type="hidden" name="color" :value="createColor">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ __('Visibilidad y Privacidad') }}
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button"
                                @click="createVisibility = 'public'"
                                :class="createVisibility === 'public' ? 'border-violet-500 bg-violet-50/50 dark:bg-violet-950/30 text-violet-700 dark:text-violet-300 ring-1 ring-violet-500' : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-gray-300'"
                                class="flex items-center gap-2.5 p-2.5 rounded-xl border text-xs font-bold text-left transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <div>
                                <div class="text-[11px] leading-tight">{{ __('Pública') }}</div>
                                <div class="text-[9px] font-normal text-gray-400">{{ __('Todo el equipo') }}</div>
                            </div>
                        </button>
                        <button type="button"
                                @click="createVisibility = 'private'"
                                :class="createVisibility === 'private' ? 'border-violet-500 bg-violet-50/50 dark:bg-violet-950/30 text-violet-700 dark:text-violet-300 ring-1 ring-violet-500' : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-gray-300'"
                                class="flex items-center gap-2.5 p-2.5 rounded-xl border text-xs font-bold text-left transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <div>
                                <div class="text-[11px] leading-tight">{{ __('Privada') }}</div>
                                <div class="text-[9px] font-normal text-gray-400">{{ __('Solo tú y managers') }}</div>
                            </div>
                        </button>
                    </div>
                    <input type="hidden" name="visibility" :value="createVisibility">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Descripción (opcional)') }}
                    </label>
                    <textarea name="description" rows="2"
                              class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-violet-500 outline-none transition-all placeholder-gray-400"
                              placeholder="Breve nota sobre qué contiene esta carpeta..."></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                    <button type="button" @click="createOpen = false"
                            class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-bold hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                        {{ __('Cancelar') }}
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-xl text-xs font-bold transition-colors shadow-xs">
                        {{ __('Crear Carpeta') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL EDITAR CARPETA --}}
    <div x-show="editOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;"
         @keydown.escape.window="editOpen = false">

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl max-w-md w-full p-6 shadow-2xl relative"
             @click.away="editOpen = false">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                {{ __('Editar Carpeta') }}
            </h3>

            <form :action="'{{ route('teams.library.folders.update', [$team, 'FOLDER_ID']) }}'.replace('FOLDER_ID', editId)"
                  method="POST" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Nombre de la carpeta') }} *
                    </label>
                    <input type="text" name="name" x-model="editName" required
                           class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-violet-500 outline-none transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Color de identificación') }}
                    </label>
                    <div class="flex items-center gap-2 flex-wrap">
                        @foreach(['violet' => 'bg-violet-500', 'blue' => 'bg-blue-500', 'emerald' => 'bg-emerald-500', 'amber' => 'bg-amber-500', 'rose' => 'bg-rose-500', 'indigo' => 'bg-indigo-500', 'cyan' => 'bg-cyan-500', 'gray' => 'bg-gray-500'] as $cKey => $cBg)
                            <button type="button"
                                    @click="editColor = '{{ $cKey }}'"
                                    :class="editColor === '{{ $cKey }}' ? 'ring-2 ring-offset-2 ring-violet-500 scale-110' : 'opacity-70 hover:opacity-100'"
                                    class="w-7 h-7 rounded-full {{ $cBg }} transition-all focus:outline-none"
                                    title="{{ ucfirst($cKey) }}">
                            </button>
                        @endforeach
                    </div>
                    <input type="hidden" name="color" :value="editColor">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ __('Visibilidad y Privacidad') }}
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button"
                                @click="editVisibility = 'public'"
                                :class="editVisibility === 'public' ? 'border-violet-500 bg-violet-50/50 dark:bg-violet-950/30 text-violet-700 dark:text-violet-300 ring-1 ring-violet-500' : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-gray-300'"
                                class="flex items-center gap-2.5 p-2.5 rounded-xl border text-xs font-bold text-left transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <div>
                                <div class="text-[11px] leading-tight">{{ __('Pública') }}</div>
                                <div class="text-[9px] font-normal text-gray-400">{{ __('Todo el equipo') }}</div>
                            </div>
                        </button>
                        <button type="button"
                                @click="editVisibility = 'private'"
                                :class="editVisibility === 'private' ? 'border-violet-500 bg-violet-50/50 dark:bg-violet-950/30 text-violet-700 dark:text-violet-300 ring-1 ring-violet-500' : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-gray-300'"
                                class="flex items-center gap-2.5 p-2.5 rounded-xl border text-xs font-bold text-left transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <div>
                                <div class="text-[11px] leading-tight">{{ __('Privada') }}</div>
                                <div class="text-[9px] font-normal text-gray-400">{{ __('Solo tú y managers') }}</div>
                            </div>
                        </button>
                    </div>
                    <input type="hidden" name="visibility" :value="editVisibility">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Descripción') }}
                    </label>
                    <textarea name="description" x-model="editDescription" rows="2"
                              class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-xs focus:ring-2 focus:ring-violet-500 outline-none transition-all"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                    <button type="button" @click="editOpen = false"
                            class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-bold hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                        {{ __('Cancelar') }}
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-xl text-xs font-bold transition-colors">
                        {{ __('Guardar Cambios') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL MOVER A OTRA CARPETA --}}
    <div x-show="moveOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;"
         @keydown.escape.window="moveOpen = false">

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl max-w-md w-full p-6 shadow-2xl relative"
             @click.away="moveOpen = false">
            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
                <template x-if="!moveIsBulk">
                    <span x-text="'Mover ' + (moveType === 'folder' ? '{{ __('carpeta') }}' : (moveType === 'link' ? '{{ __('enlace') }}' : '{{ __('documento') }}'))"></span>
                </template>
                <template x-if="moveIsBulk">
                    <span>{{ __('Mover elementos seleccionados') }}</span>
                </template>
            </h3>

            <template x-if="!moveIsBulk">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4 truncate font-medium">
                    "<strong x-text="moveName"></strong>"
                </p>
            </template>
            <template x-if="moveIsBulk">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4 font-medium">
                    {{ __('Mover') }} <strong x-text="bulkDocIds.length + bulkFolderIds.length"></strong> {{ __('elementos seleccionados') }}
                    <span class="block text-gray-400 text-[11px] mt-0.5" x-text="`(${bulkDocIds.length} {{ __('documentos/enlaces') }}, ${bulkFolderIds.length} {{ __('carpetas') }})`"></span>
                </p>
            </template>

            <form :action="moveIsBulk ? '{{ route('teams.library.bulk-move', $team) }}' : '{{ route('teams.library.move', $team) }}'" method="POST" class="space-y-4">
                @csrf
                <template x-if="!moveIsBulk">
                    <div>
                        <input type="hidden" name="type" :value="moveType">
                        <input type="hidden" name="id" :value="moveId">
                    </div>
                </template>

                <template x-if="moveIsBulk">
                    <div>
                        <template x-for="id in bulkDocIds" :key="'bulk-doc-' + id">
                            <input type="hidden" name="document_ids[]" :value="id">
                        </template>
                        <template x-for="id in bulkFolderIds" :key="'bulk-folder-' + id">
                            <input type="hidden" name="folder_ids[]" :value="id">
                        </template>
                    </div>
                </template>

                <div>
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('Seleccionar carpeta de destino:') }}
                    </label>

                    <div class="border border-gray-200 dark:border-gray-700 rounded-2xl max-h-56 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800 text-xs">
                        {{-- Opción Raíz --}}
                        <label class="flex items-center gap-2.5 p-3 hover:bg-gray-50 dark:hover:bg-gray-800/60 cursor-pointer transition-colors"
                                :class="moveTargetFolderId === '' ? 'bg-violet-50/70 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300 font-bold' : ''">
                            <input type="radio" name="target_folder_id" value="" x-model="moveTargetFolderId" class="text-violet-600 focus:ring-violet-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                            </svg>
                            <span>🏠 {{ __('Raíz de la Biblioteca (Sin carpeta)') }}</span>
                        </label>

                        {{-- Árbol de carpetas --}}
                        @foreach($folderOptions as $opt)
                            <label class="flex items-center gap-2.5 p-3 hover:bg-gray-50 dark:hover:bg-gray-800/60 cursor-pointer transition-colors"
                                   :class="moveTargetFolderId == '{{ $opt['id'] }}' ? 'bg-violet-50/70 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300 font-bold' : ''"
                                   style="padding-left: {{ 12 + ($opt['level'] * 18) }}px;">
                                <input type="radio" name="target_folder_id" value="{{ $opt['id'] }}" x-model="moveTargetFolderId" class="text-violet-600 focus:ring-violet-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-500 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M19.5 21a3 3 0 003-3v-4.5a3 3 0 00-3-3h-1.5V9a3 3 0 00-3-3h-3.379a3 3 0 01-2.121-.879L8.379 4.043A3 3 0 006.258 3.164H4.5A3 3 0 001.5 6.164v11.836A3 3 0 004.5 21h15z" />
                                </svg>
                                <span class="truncate">{{ $opt['name'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                    <button type="button" @click="moveOpen = false"
                            class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-bold hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                        {{ __('Cancelar') }}
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-xl text-xs font-bold transition-colors">
                        <span x-text="moveIsBulk ? '{{ __('Mover Selección') }}' : '{{ __('Mover Aquí') }}'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL ELIMINAR --}}
    <div x-show="deleteOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;"
         @keydown.escape.window="deleteOpen = false">

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl max-w-md w-full p-6 shadow-2xl relative"
             @click.away="deleteOpen = false">
            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <template x-if="!deleteIsBulk">
                    <span x-text="'¿Eliminar ' + (deleteType === 'folder' ? '{{ __('carpeta') }}' : (deleteType === 'link' ? '{{ __('enlace') }}' : '{{ __('documento') }}')) + '?'"></span>
                </template>
                <template x-if="deleteIsBulk">
                    <span>{{ __('¿Eliminar elementos seleccionados?') }}</span>
                </template>
            </h3>

            <template x-if="!deleteIsBulk">
                <p class="text-xs text-gray-600 dark:text-gray-300 mb-4">
                    {{ __('¿Estás seguro de que deseas eliminar') }} "<strong x-text="deleteTitle"></strong>"?
                </p>
            </template>
            <template x-if="deleteIsBulk">
                <p class="text-xs text-gray-600 dark:text-gray-300 mb-4">
                    {{ __('¿Estás seguro de que deseas eliminar los') }} <strong x-text="bulkDeleteDocIds.length + bulkDeleteFolderIds.length"></strong> {{ __('elementos seleccionados?') }}
                    <span class="block text-gray-400 text-[11px] mt-1" x-text="`(${bulkDeleteDocIds.length} {{ __('documentos/enlaces') }}, ${bulkDeleteFolderIds.length} {{ __('carpetas') }})`"></span>
                </p>
            </template>

            <template x-if="!deleteIsBulk && deleteType === 'folder' && deleteDocCount > 0">
                <div class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl mb-4 text-xs text-amber-800 dark:text-amber-300">
                    <p class="font-bold mb-1">⚠️ {{ __('Esta carpeta contiene documentos') }} (<span x-text="deleteDocCount"></span>)</p>
                    <p class="text-[11px] mb-2">{{ __('Por defecto, los documentos se moverán a la carpeta superior para no perderlos.') }}</p>
                    <label class="flex items-center gap-2 text-xs font-bold text-rose-700 dark:text-rose-400 cursor-pointer">
                        <input type="checkbox" x-model="deleteCascade" class="rounded text-rose-600 focus:ring-rose-500">
                        <span>{{ __('Eliminar también todos los documentos y subcarpetas') }}</span>
                    </label>
                </div>
            </template>

            <template x-if="deleteIsBulk && bulkDeleteFolderIds.length > 0">
                <div class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl mb-4 text-xs text-amber-800 dark:text-amber-300">
                    <p class="font-bold mb-1">⚠️ {{ __('Hay carpetas entre los elementos seleccionados') }}</p>
                    <p class="text-[11px] mb-2">{{ __('Por defecto, los contenidos se conservarán subiéndolos de nivel.') }}</p>
                    <label class="flex items-center gap-2 text-xs font-bold text-rose-700 dark:text-rose-400 cursor-pointer">
                        <input type="checkbox" x-model="deleteCascade" class="rounded text-rose-600 focus:ring-rose-500">
                        <span>{{ __('Eliminar también el contenido de las carpetas seleccionadas') }}</span>
                    </label>
                </div>
            </template>

            {{-- Formulario para eliminación masiva --}}
            <template x-if="deleteIsBulk">
                <form action="{{ route('teams.library.bulk-delete', $team) }}" method="POST">
                    @csrf
                    <template x-for="id in bulkDeleteDocIds" :key="'bulk-del-doc-' + id">
                        <input type="hidden" name="document_ids[]" :value="id">
                    </template>
                    <template x-for="id in bulkDeleteFolderIds" :key="'bulk-del-folder-' + id">
                        <input type="hidden" name="folder_ids[]" :value="id">
                    </template>
                    <input type="hidden" name="delete_contents" :value="deleteCascade ? '1' : '0'">

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                        <button type="button" @click="deleteOpen = false"
                                class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-bold hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                            {{ __('Cancelar') }}
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition-colors">
                            {{ __('Eliminar Seleccionados') }}
                        </button>
                    </div>
                </form>
            </template>

            {{-- Formulario para eliminar carpeta individual --}}
            <template x-if="!deleteIsBulk && deleteType === 'folder'">
                <form :action="'{{ route('teams.library.folders.destroy', [$team, 'FOLDER_ID']) }}'.replace('FOLDER_ID', deleteId)"
                      method="POST">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="delete_contents" :value="deleteCascade ? '1' : '0'">

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                        <button type="button" @click="deleteOpen = false"
                                class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-bold hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                            {{ __('Cancelar') }}
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition-colors">
                            {{ __('Eliminar') }}
                        </button>
                    </div>
                </form>
            </template>

            {{-- Formulario para eliminar documento o enlace individual --}}
            <template x-if="!deleteIsBulk && (deleteType === 'document' || deleteType === 'link')">
                <form :action="'{{ route('teams.activities.destroy', [$team, 'DOC_ID']) }}'.replace('DOC_ID', deleteId)"
                      method="POST">
                    @csrf
                    @method('DELETE')

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                        <button type="button" @click="deleteOpen = false"
                                class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-bold hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                            {{ __('Cancelar') }}
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition-colors">
                            {{ __('Eliminar') }}
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>
</div>

