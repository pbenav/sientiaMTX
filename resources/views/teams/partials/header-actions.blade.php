@php
    $teamId = $team->id;
    $isMatrix = request()->routeIs('teams.dashboard');
    $isTaskList = request()->routeIs('teams.tasks.index') || request()->routeIs('teams.tasks.show');
    $isGantt = request()->routeIs('teams.gantt');
    $isKanban = request()->routeIs('teams.kanban');
    $isForum = request()->routeIs('teams.forum.*');
    $isMembers = request()->routeIs('teams.members');
    $isSettings = request()->routeIs('teams.edit');
    $isTimeReports = request()->routeIs('teams.time-reports');

    $shouldShowCreateTask = $isTaskList || $isMatrix || $isGantt || $isKanban;
@endphp

<div class="flex items-center gap-2 sm:gap-3 flex-wrap">
    <!-- Management Actions -->
    <!-- PRIMARY ACTION: NEW TASK HUB -->
    @if($shouldShowCreateTask)
        <x-dropdown align="left" width="60">
            <x-slot name="trigger">
                <button type="button" 
                    class="flex items-center gap-2 text-xs bg-violet-600 hover:bg-violet-500 text-white px-5 py-2.5 rounded-xl transition-all font-black shadow-lg shadow-violet-500/20 active:scale-95 group">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform group-hover:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>NUEVA</span>
                    <svg class="h-3 w-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
            </x-slot>

            <x-slot name="content">
                <div class="px-3 py-2 text-[10px] font-black uppercase tracking-widest text-gray-400 border-b border-gray-50 dark:border-gray-700/50 mb-1">
                    Acciones de Creación
                </div>
                <x-dropdown-link :href="route('teams.tasks.create', $team)" class="flex items-center gap-3 py-3 px-4">
                    <div class="p-1.5 bg-violet-100 dark:bg-violet-900/40 text-violet-600 dark:text-violet-400 rounded-lg shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-bold text-gray-900 dark:text-white text-xs">Crear Manualmente</span>
                        <span class="text-[9px] text-gray-500 font-medium tracking-tight">Formulario paso a paso</span>
                    </div>
                </x-dropdown-link>

                @if($team->isCoordinator(auth()->user()) || auth()->user()->is_admin)
                    <!-- Importar Archivo -->
                    <button type="button" onclick="openImportTaskModal('file')" class="w-full flex items-center gap-3 py-3 px-4 text-start hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150 ease-in-out group">
                        <div class="p-1.5 bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 rounded-lg shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </div>
                        <div class="flex flex-col">
                            <span class="font-bold text-gray-900 dark:text-white text-xs">Cargar Archivo (.json)</span>
                            <span class="text-[9px] text-gray-500 font-medium tracking-tight">Importar desde almacenamiento local</span>
                        </div>
                    </button>

                    <!-- Pegar JSON -->
                    <button type="button" onclick="openImportTaskModal('paste')" class="w-full flex items-center gap-3 py-3 px-4 text-start hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150 ease-in-out group">
                        <div class="p-1.5 bg-teal-100 dark:bg-teal-900/40 text-teal-600 dark:text-teal-400 rounded-lg shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <div class="flex flex-col">
                            <span class="font-bold text-gray-900 dark:text-white text-xs">Pegar JSON</span>
                            <span class="text-[9px] text-gray-500 font-medium tracking-tight">Importar desde el portapapeles</span>
                        </div>
                    </button>
                @endif
            </x-slot>
        </x-dropdown>
    @endif

        @if(request()->routeIs('teams.tasks.index') && ($team->isCoordinator(auth()->user()) || auth()->user()->is_admin))
            <button type="button" onclick="confirmPurgeTrash()"
                class="flex items-center gap-1.5 text-xs bg-red-100 hover:bg-red-200 text-red-600 dark:bg-red-900/30 dark:hover:bg-red-900/40 dark:text-red-400 px-4 py-2.5 rounded-xl transition-all font-bold active:scale-95">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <span class="hidden sm:inline">Vaciar Papelera</span>
            </button>
        @endif

        @php
            $isGoogleConnected = auth()->user()->teams()->where('team_id', $team->id)->wherePivotNotNull('google_token')->exists();
        @endphp

        <!-- Hub de Integraciones -->
        @if(!$isForum && $isGoogleConnected)
            <x-dropdown align="right" width="80">
                <x-slot name="trigger">
                    <button type="button" class="flex items-center gap-1.5 text-xs bg-white dark:bg-white/5 border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300 px-4 py-2.5 rounded-xl transition-all font-bold hover:bg-gray-50 dark:hover:bg-white/10 active:scale-95 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 4a2 2 0 114 0v1a2 2 0 01-2 2h-1a2 2 0 01-2-2V4zm-5 6a2 2 0 114 0v1a2 2 0 01-2 2H7a2 2 0 01-2-2v-1zm10 0a2 2 0 114 0v1a2 2 0 01-2 2h-1a2 2 0 01-2-2v-1zM6 20a2 2 0 114 0v1a2 2 0 01-2 2H7a2 2 0 01-2-2v-1zm10 0a2 2 0 114 0v1a2 2 0 01-2 2h-1a2 2 0 01-2-2v-1z" />
                        </svg>
                        <span class="hidden sm:inline">Integraciones</span>
                        <svg class="h-3 w-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <div class="px-3 py-2 text-[10px] font-black uppercase tracking-widest text-gray-400 border-b border-gray-50 dark:border-gray-700/50 mb-1">
                        Workspace & Tools
                    </div>
                    
                    @if($isGoogleConnected)
                        <x-dropdown-link :href="route('google.sync', ['team_id' => $team->id])" class="flex items-center gap-4 py-4 px-5">
                            <div class="shrink-0 p-2 bg-emerald-50 dark:bg-emerald-500/10 rounded-xl text-emerald-600 group-hover:scale-110 transition-transform">
                                <svg class="h-5 w-5" viewBox="0 0 48 48">
                                    <path fill="#FFC107" d="M17 6H11L2 22l3 5h6l9-16z"/><path fill="#2196F3" d="M37 42H11l-9-15 4-7h26l9 16z"/><path fill="#4CAF50" d="M15 6l9 16 9-16H15z"/>
                                </svg>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-bold text-gray-900 dark:text-white text-sm">Sincronizar Google</span>
                                <span class="text-[10px] text-gray-500 font-medium tracking-normal mt-0.5">Sincronización total de tareas y calendario</span>
                            </div>
                        </x-dropdown-link>
                    @endif
                </x-slot>
            </x-dropdown>
        @endif
    </div>

    @include('tasks.partials.import-modal-script')
