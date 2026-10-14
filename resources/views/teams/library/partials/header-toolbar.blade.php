@props([
    'team',
    'currentFolder' => null,
    'breadcrumbs' => [],
    'mode' => 'browser',
    'searchQuery' => '',
    'searchScope' => 'folder',
    'subfoldersCount' => 0,
    'documentsCount' => 0,
])

<div class="space-y-4 mb-6">
    <!-- Barra superior: Breadcrumbs y Acciones Principales -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-gray-100 dark:border-gray-800">

        <!-- Breadcrumbs navegables y zonas de soltar -->
        <nav class="flex items-center gap-1.5 text-xs font-bold text-gray-500 overflow-x-auto no-scrollbar py-1">
            <a href="{{ route('teams.library', [$team, 'mode' => $mode]) }}"
               x-data="{ isDragTarget: false }"
               @dragover.prevent="isDragTarget = true"
               @dragleave.prevent="isDragTarget = false"
               @drop.prevent="
                   isDragTarget = false;
                   let data = {};
                   try { data = JSON.parse($event.dataTransfer.getData('text/plain') || '{}'); } catch(e) {}
                   let dIds = data.document_ids || (data.type === 'document' ? [data.id] : []);
                   let fIds = data.folder_ids || (data.type === 'folder' ? [data.id] : []);
                   if (dIds.length || fIds.length) {
                       $dispatch('bulk-move-items', { document_ids: dIds, folder_ids: fIds, target_folder_id: null });
                   }
               "
               :class="isDragTarget ? 'ring-2 ring-violet-500 bg-violet-100 dark:bg-violet-900/50 scale-105' : ''"
               class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-all {{ !$currentFolder ? 'text-violet-600 dark:text-violet-400 bg-violet-50/70 dark:bg-violet-900/20 font-black' : 'text-gray-600 dark:text-gray-400' }}"
               title="{{ __('Arrastra aquí para mover a la raíz de la biblioteca') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span>{{ __('Biblioteca') }}</span>
            </a>

            @foreach($breadcrumbs as $bc)
                <span class="text-gray-300 dark:text-gray-700 select-none">/</span>
                @if($loop->last)
                    <span class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl bg-violet-50/70 dark:bg-violet-900/20 text-violet-700 dark:text-violet-300 font-black">
                        @if(($bc['visibility'] ?? 'public') === 'private')
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        @else
                            📁
                        @endif
                        <span>{{ $bc['name'] }}</span>
                    </span>
                @else
                    <a href="{{ route('teams.library', [$team, 'folder' => $bc['id'], 'mode' => $mode]) }}"
                       x-data="{ isDragTarget: false }"
                       @dragover.prevent="isDragTarget = true"
                       @dragleave.prevent="isDragTarget = false"
                       @drop.prevent="
                           isDragTarget = false;
                           let data = {};
                           try { data = JSON.parse($event.dataTransfer.getData('text/plain') || '{}'); } catch(e) {}
                           let dIds = data.document_ids || (data.type === 'document' ? [data.id] : []);
                           let fIds = data.folder_ids || (data.type === 'folder' ? [data.id] : []);
                           if (fIds.includes({{ $bc['id'] }})) return;
                           if (dIds.length || fIds.length) {
                               $dispatch('bulk-move-items', { document_ids: dIds, folder_ids: fIds, target_folder_id: {{ $bc['id'] }} });
                           }
                       "
                       :class="isDragTarget ? 'ring-2 ring-violet-500 bg-violet-100 dark:bg-violet-900/50 scale-105' : ''"
                       class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-400 transition-all"
                       title="{{ __('Arrastra aquí para mover a esta carpeta') }}">
                        @if(($bc['visibility'] ?? 'public') === 'private')
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        @else
                            📁
                        @endif
                        <span>{{ $bc['name'] }}</span>
                    </a>
                @endif
            @endforeach
        </nav>

        <!-- Botones de Acción y Toggle de Modo -->
        <div class="flex items-center gap-2.5 flex-wrap shrink-0">
            <!-- Selector de Modo de Visualización (Explorador vs Lector Wiki) -->
            <div class="flex items-center bg-gray-100 dark:bg-gray-800 p-1 rounded-xl text-xs font-bold border border-gray-200 dark:border-gray-700/80">
                <a href="{{ route('teams.library', array_filter(['team' => $team, 'folder' => $currentFolder?->id, 'mode' => 'browser', 'q' => $searchQuery])) }}"
                   class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition-all {{ $mode === 'browser' ? 'bg-white dark:bg-gray-900 text-violet-600 dark:text-violet-400 shadow-xs' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}"
                   title="{{ __('Navegador de archivos y carpetas') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    <span>{{ __('Explorador') }}</span>
                </a>

                <a href="{{ route('teams.library', array_filter(['team' => $team, 'folder' => $currentFolder?->id, 'mode' => 'reader', 'q' => $searchQuery])) }}"
                   class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition-all {{ $mode === 'reader' ? 'bg-white dark:bg-gray-900 text-violet-600 dark:text-violet-400 shadow-xs' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}"
                   title="{{ __('Vista de lectura de capítulos y notas') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span>{{ __('Lector Wiki') }}</span>
                </a>
            </div>

            <!-- Botón Nueva Carpeta -->
            <button type="button"
                    @click="$dispatch('open-create-folder-modal', { parent_id: {{ $currentFolder ? $currentFolder->id : 'null' }} })"
                    class="flex items-center gap-1.5 px-3.5 py-2 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 rounded-xl text-xs font-bold transition-all shadow-xs active:scale-95">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                </svg>
                <span>{{ __('Nueva Carpeta') }}</span>
            </button>

            <!-- Botón Nuevo Documento -->
            <a href="{{ route('teams.activities.create', array_filter(['team' => $team, 'type' => 'document', 'folder_id' => $currentFolder?->id])) }}"
               class="flex items-center gap-1.5 px-3.5 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs active:scale-95">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span>{{ __('Nuevo Documento') }}</span>
            </a>
        </div>
    </div>

    <!-- Barra de Filtros y Búsqueda -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gray-50/70 dark:bg-gray-800/30 p-3 rounded-2xl border border-gray-100 dark:border-gray-800/80">
        <!-- Buscador -->
        <form action="{{ route('teams.library', $team) }}" method="GET" class="flex items-center gap-2 flex-1 max-w-xl">
            <input type="hidden" name="mode" value="{{ $mode }}">
            @if($currentFolder)
                <input type="hidden" name="folder" value="{{ $currentFolder->id }}">
            @endif

            <div class="relative flex-1">
                <input type="text" name="q" value="{{ $searchQuery }}"
                       placeholder="{{ $currentFolder ? __('Buscar en esta carpeta...') : __('Buscar en la biblioteca...') }}"
                       class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs py-2 pl-8 pr-8 focus:ring-2 focus:ring-violet-500 outline-none transition-all placeholder-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 absolute left-2.5 top-2.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                @if($searchQuery)
                    <a href="{{ route('teams.library', array_filter(['team' => $team, 'folder' => $currentFolder?->id, 'mode' => $mode])) }}"
                       class="absolute right-2.5 top-2.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </a>
                @endif
            </div>

            @if($currentFolder)
                <select name="scope" onchange="this.form.submit()"
                        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs py-2 px-2.5 focus:ring-2 focus:ring-violet-500 outline-none transition-all text-gray-600 dark:text-gray-300">
                    <option value="folder" {{ $searchScope === 'folder' ? 'selected' : '' }}>{{ __('En esta carpeta') }}</option>
                    <option value="all" {{ $searchScope === 'all' ? 'selected' : '' }}>{{ __('Toda la biblioteca') }}</option>
                </select>
            @endif

            <button type="submit" class="px-3.5 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 text-xs font-bold rounded-xl transition-colors shrink-0">
                {{ __('Buscar') }}
            </button>
        </form>

        <!-- Conteo de elementos en la vista actual -->
        <div class="text-[11px] font-medium text-gray-400 flex items-center gap-3 shrink-0">
            @if($subfoldersCount > 0)
                <span>📁 {{ $subfoldersCount }} {{ trans_choice('carpeta|carpetas', $subfoldersCount) }}</span>
            @endif
            <span>📄 {{ $documentsCount }} {{ trans_choice('documento|documentos', $documentsCount) }}</span>
        </div>
    </div>
</div>

