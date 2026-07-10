<x-app-layout>
    <!-- Dynamic Activity Context Beacon -->
    <div id="sientia-active-activity-beacon" data-activity-id="{{ $activity->id }}" style="display: none;"></div>
    @section('title', $activity->title)    @php
        // 1. Identify the personal execution instance
        $isAssigned = $activity->assigned_user_id === auth()->id() || $activity->assignedTo->contains(auth()->id());

        // Lógica de Notas Privadas e Instancia de ejecución:
        // 1. Si la tarea es una plantilla (is_template), buscamos si el usuario tiene una instancia propia para esa tarea.
        // 2. Si no es plantilla, la tarea en sí es la instancia de ejecución.
        // 2. Si la tarea es una plantilla (is_template), buscamos si el usuario tiene una instancia propia.
        // Si no la tiene, o si no es plantilla, la instancia personal para notas es la propia tarea.
        $personalInstance = $activity;
        if ($activity->is_template) {
            $instance = $activity->instances()
                ->whereHas('assignedTo', fn($q) => $q->where('users.id', auth()->id()))
                ->first();
            
            if ($instance) {
                $personalInstance = $instance;
            }
        }

        // Get private notes
        $privateNote = \App\Models\TaskPrivateNote::where('task_id', $activity->id)
            ->where('user_id', auth()->id())
            ->first();
    @endphp

    <x-slot name="header">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2 min-w-0">
                <a href="{{ $backUrl ?? route('teams.dashboard', $team) }}"
                    class="p-1.5 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-lg transition-all shrink-0"
                    title="{{ __('navigation.back') ?? 'Volver' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                @include('teams.partials.breadcrumb')
                <span class="text-gray-300 dark:text-gray-700 mx-1">/</span>
                <h1 class="text-base font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight flex items-center gap-1.5">
                    {{ __('activities.detail') }}
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-200 dark:border-blue-700/50 shadow-sm flex items-center gap-1 shrink-0 ml-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        {{ $activity->type_label ?? __('Actividad') }}
                    </span>
                </h1>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                {{-- Actions --}}
            </div>
        </div>
        
        <div class="mt-2">
            <x-demo-hint>
                La ficha técnica de la actividad centraliza toda la ejecución: permite el registro de tiempos (Time-tracking), subdividir el trabajo mediante el desglose, sincronizar fechas con Google Calendar/Activities y gestionar archivos asociados. Además, facilita la clonación y exportación de actividades (Portabilidad JSON).
            </x-demo-hint>
        </div>

        <!-- View Switcher Sub-Header -->
        <div class="mt-4 mb-2 flex w-full">
            @include('teams.partials.team-view-nav')
        </div>

        @if($activity->trashed())
            <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/50 flex items-start gap-4">
                <div class="shrink-0 p-2 bg-red-100 dark:bg-red-900/50 rounded-full text-red-600 dark:text-red-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-black text-red-800 dark:text-red-300">Esta actividad está en la papelera</h3>
                    <p class="text-sm text-red-700 dark:text-red-400 mt-1">La actividad ha sido eliminada y ya no aparece en el listado ni en los cuadrantes de trabajo. Solo es visible mediante su enlace directo. Puedes vaciar la papelera desde el listado general de actividades para eliminarla permanentemente.</p>
                </div>
            </div>
        @endif

        <!-- Activity Actions Footer Row -->
        <div class="flex items-center gap-2 flex-wrap shrink-0 mt-4 border-t border-gray-100 dark:border-gray-800 pt-6">
            @if($team->isCoordinator(auth()->user()) || auth()->user()->is_admin)
                <a href="{{ route('teams.activities.create', $team) }}"
                    class="shrink-0 flex items-center gap-1.5 text-xs bg-violet-600 hover:bg-violet-500 text-white px-4 py-2.5 rounded-xl transition-all shadow-lg shadow-violet-500/20 font-bold active:scale-95">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="hidden lg:inline">{{ __('activities.create') }}</span>
                </a>
            @endif

            @if(!$activity->trashed())
                @can('update', $activity)
                    <a href="{{ route('teams.activities.edit', [$team, $activity]) }}"
                        class="shrink-0 flex items-center gap-1.5 text-xs bg-white dark:bg-white/5 border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300 px-4 py-2.5 rounded-xl transition-all font-bold hover:bg-gray-50 dark:hover:bg-white/10 active:scale-95 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        {{ __('activities.edit') }}
                    </a>
                @endcan
            @endif

            @php
                $canConvert = auth()->user()->is_admin || $team->isCoordinator(auth()->user()) || auth()->id() === $activity->created_by_id || auth()->id() === $activity->assigned_user_id || $activity->assignedTo->contains(auth()->id());
            @endphp
            @if($canConvert && !$activity->isDeprecatedByConversion())
                <button type="button" @click="$dispatch('open-convert-activity-modal')" class="shrink-0 flex items-center gap-1.5 text-xs bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white px-4 py-2.5 rounded-xl transition-all font-bold active:scale-95 shadow-lg shadow-violet-500/25 ml-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                    <span class="hidden sm:inline">Convertir Actividad</span>
                </button>
            @endif

            @if ($activity->is_template && ($team->isCoordinator(auth()->user()) || auth()->id() === $activity->created_by_id))
                <div class="flex items-center gap-2">


                    <form action="{{ route('teams.tasks.sync-to-children', [$team, $activity]) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="shrink-0 flex items-center gap-1.5 text-xs bg-white dark:bg-white/5 border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300 px-4 py-2.5 rounded-xl transition-all font-bold hover:bg-gray-50 dark:hover:bg-white/10 active:scale-95 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span class="hidden sm:inline">{{ __('activities.sync_members') }}</span>
                        </button>
                    </form>
                </div>
            @endif

            <!-- TIMER BUTTON (Start/Stop) -->
            @if (!$activity->is_template)
                @include('teams.activities.partials.activity-timer-button', ['activity' => $activity])
            @elseif ($personalInstance)
                @include('teams.activities.partials.activity-timer-button', ['activity' => $personalInstance])
            @endif

            <!-- Hub de Acciones Secundarias (Acciones) -->
            <x-dropdown align="center" width="80">
                <x-slot name="trigger">
                    <button type="button" class="shrink-0 flex items-center gap-1.5 text-xs bg-white dark:bg-white/5 border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300 px-4 py-2.5 rounded-xl transition-all font-bold hover:bg-gray-50 dark:hover:bg-white/10 active:scale-95 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                        </svg>
                        <span>{{ __('Acciones') }}</span>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <div class="px-3 py-2 text-[10px] font-black uppercase tracking-widest text-gray-400 border-b border-gray-50 dark:border-gray-700/50 mb-1">
                        Workspace & Portabilidad
                                      <!-- Imprimir Ficha -->
                    <button type="button" onclick="printFullTask()" class="w-full flex items-center gap-4 py-4 px-5 text-start hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150 ease-in-out group">
                        <div class="shrink-0 p-2 bg-violet-50 dark:bg-violet-900/20 text-violet-600 dark:text-violet-400 rounded-xl group-hover:scale-110 transition-transform shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                        </div>
                        <div class="flex flex-col">
                            <span class="font-bold text-gray-900 dark:text-white text-sm">Imprimir Ficha Técnica</span>
                            <span class="text-[10px] text-gray-500 font-medium tracking-normal mt-0.5">Informe completo y detallado de la actividad</span>
                        </div>
                    </button>

                    @if ($activity->type === 'document')
                    <!-- Imprimir Libro -->
                    <button type="button" onclick="printDocumentBook()" class="w-full flex items-center gap-4 py-4 px-5 text-start hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150 ease-in-out group">
                        <div class="shrink-0 p-2 bg-orange-50 dark:bg-orange-900/20 text-orange-600 dark:text-orange-400 rounded-xl group-hover:scale-110 transition-transform shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <div class="flex flex-col">
                            <span class="font-bold text-gray-900 dark:text-white text-sm">Imprimir como libro...</span>
                            <span class="text-[10px] text-gray-500 font-medium tracking-normal mt-0.5">Generar libro digital con todos los capítulos</span>
                        </div>
                    </button>
                    @endif

                    <!-- Reproducir en Equipo -->
                    @php
                        $otherTeams = auth()->user()->teams()->where('teams.id', '!=', $team->id)->get();
                    @endphp
                    @if($otherTeams->count() > 0)
                        <button type="button" onclick="reproduceInTeam()" class="w-full flex items-center gap-4 py-4 px-5 text-start hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150 ease-in-out group">
                            <div class="shrink-0 p-2 bg-violet-50 text-violet-600 rounded-xl group-hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-bold text-gray-900 dark:text-white text-sm">Reproducir en Equipo</span>
                                <span class="text-[10px] text-gray-500 font-medium tracking-normal mt-0.5">Clonar actividad en otro espacio de trabajo</span>
                            </div>
                        </button>
                    @endif

                    <!-- Fusionar Tarea -->
                    @can('delete', $activity)
                    <button type="button" onclick="mergeTaskModal()" class="w-full flex items-center gap-4 py-4 px-5 text-start hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150 ease-in-out group">
                        <div class="shrink-0 p-2 bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 rounded-xl group-hover:scale-110 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                        </div>
                        <div class="flex flex-col">
                            <span class="font-bold text-gray-900 dark:text-white text-sm">Fusionar con Actividad</span>
                            <span class="text-[10px] text-gray-500 font-medium tracking-normal mt-0.5">Fusionar esta actividad y sus datos en otra existente</span>
                        </div>
                    </button>
                    @endcan

                    <!-- Exportar JSON -->
                    <div class="flex flex-col border-t border-gray-50 dark:border-gray-800 pt-1 mt-1">
                        <div class="px-5 py-2 text-[9px] font-black uppercase tracking-widest text-gray-400">Portabilidad (Outbound)</div>
                        <x-dropdown-link :href="route('teams.activities.export-json', [$team, $activity])" class="flex items-center gap-4 py-3 px-5 group">
                            <div class="shrink-0 p-2 bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-xl group-hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-bold text-gray-900 dark:text-white text-sm">Exportar Actividad (.json)</span>
                                <span class="text-[10px] text-gray-500 font-medium tracking-normal mt-0.5">Descargar backup portátil</span>
                            </div>
                        </x-dropdown-link>

                        <button type="button" onclick="copyTaskJson()" class="w-full flex items-center gap-4 py-3 px-5 text-start hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150 ease-in-out group">
                            <div class="shrink-0 p-2 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-xl group-hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-bold text-gray-900 dark:text-white text-sm">Copiar JSON</span>
                                <span class="text-[10px] text-gray-500 font-medium tracking-normal mt-0.5">Copia para transferir a otro equipo</span>
                            </div>
                        </button>

                        <div class="px-5 py-2 text-[9px] font-black uppercase tracking-widest text-gray-400 mt-2 border-t border-gray-50 dark:border-gray-800 pt-3">Portabilidad (Inbound)</div>
                        <!-- Cargar Archivo -->
                        <button type="button" onclick="openImportActivityModal('file')" class="w-full flex items-center gap-4 py-3 px-5 text-start hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150 ease-in-out group">
                            <div class="shrink-0 p-2 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 rounded-xl group-hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-bold text-gray-900 dark:text-white text-sm">Cargar Archivo (.json)</span>
                                <span class="text-[10px] text-gray-500 font-medium tracking-normal mt-0.5">Crear actividad desde un archivo local</span>
                            </div>
                        </button>

                        <!-- Pegar JSON -->
                        <button type="button" onclick="openImportActivityModal('paste')" class="w-full flex items-center gap-4 py-3 px-5 text-start hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150 ease-in-out group">
                            <div class="shrink-0 p-2 bg-teal-50 dark:bg-teal-900/20 text-teal-600 dark:text-teal-400 rounded-xl group-hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m.5 4l3 3m0 0l3-3m-3 3V10" />
                                </svg>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-bold text-gray-900 dark:text-white text-sm">Pegar JSON</span>
                                <span class="text-[10px] text-gray-500 font-medium tracking-normal mt-0.5">Crear actividad desde el portapapeles</span>
                            </div>
                        </button>
                    </div>      </div>
                </x-slot>
            </x-dropdown>

            <!-- Hub de Integraciones (Integraciones) -->
            @php
                $isGoogleConnected = auth()->user()->teams()->where('team_id', $team->id)->wherePivotNotNull('google_token')->exists();
            @endphp
            @if($isGoogleConnected || $team->isCoordinator(auth()->user()) || auth()->user()->is_admin)
                <x-dropdown align="center" width="80">
                    <x-slot name="trigger">
                        <button type="button" class="shrink-0 flex items-center gap-1.5 text-xs bg-white dark:bg-white/5 border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300 px-4 py-2.5 rounded-xl transition-all font-bold hover:bg-gray-50 dark:hover:bg-white/10 active:scale-95 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 4a2 2 0 114 0v1a2 2 0 01-2 2h-1a2 2 0 01-2-2V4zm-5 6a2 2 0 114 0v1a2 2 0 01-2 2H7a2 2 0 01-2-2v-1zm10 0a2 2 0 114 0v1a2 2 0 01-2 2h-1a2 2 0 01-2-2v-1zM6 20a2 2 0 114 0v1a2 2 0 01-2 2H7a2 2 0 01-2-2v-1zm10 0a2 2 0 114 0v1a2 2 0 01-2 2h-1a2 2 0 01-2-2v-1z" />
                            </svg>
                            <span class="hidden sm:inline">Integraciones</span>
                            <svg class="h-3 w-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-3 py-2 text-[10px] font-black uppercase tracking-widest text-gray-400 border-b border-gray-50 dark:border-gray-700/50 mb-1">
                            Google Workspace
                        </div>
                        
                        @can('update', $activity)
                            <!-- Sincronización Google Activities -->
                            <form action="{{ route('google.sync_activity', [$team, $activity]) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-4 py-4 px-5 text-start hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150 ease-in-out group">
                                    <div class="shrink-0 p-2 {{ $activity->google_task_id ? 'bg-violet-50 text-violet-600' : 'bg-amber-50 text-amber-600' }} rounded-xl group-hover:scale-110 transition-transform">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-gray-900 dark:text-white text-sm">Sincronizar Google Activities</span>
                                        <span class="text-[10px] text-gray-500 font-medium tracking-normal mt-0.5">Vínculo bidireccional con Google</span>
                                    </div>
                                </button>
                            </form>
                            @if ($activity->google_task_id)
                                <form action="{{ route('google.disconnect_activity', [$team, $activity]) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center gap-4 py-4 px-5 text-start hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150 ease-in-out group">
                                        <div class="shrink-0 p-2 bg-gray-50 text-gray-400 rounded-xl group-hover:scale-110 transition-transform">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-bold text-gray-900 dark:text-white text-sm">Desconectar de Google Activities</span>
                                            <span class="text-[10px] text-gray-500 font-medium tracking-normal mt-0.5">Romper el vínculo con Google Activities</span>
                                        </div>
                                    </button>
                                </form>
                            @endif

                            <!-- Google Calendar -->
                            <form action="{{ route('google.export_calendar_activity', [$team, $activity]) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-4 py-4 px-5 text-start hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150 ease-in-out group">
                                    <div class="shrink-0 p-2 {{ $activity->google_calendar_event_id ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600' }} rounded-xl group-hover:scale-110 transition-transform">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-gray-900 dark:text-white text-sm">Calendario de Google</span>
                                        <span class="text-[10px] text-gray-500 font-medium tracking-normal mt-0.5">Gestionar evento en calendario</span>
                                    </div>
                                </button>
                            </form>
                            @if ($activity->google_calendar_event_id)
                                <form action="{{ route('google.disconnect_activity', [$team, $activity]) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center gap-4 py-4 px-5 text-start hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150 ease-in-out group">
                                        <div class="shrink-0 p-2 bg-gray-50 text-gray-400 rounded-xl group-hover:scale-110 transition-transform">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-bold text-gray-900 dark:text-white text-sm">Desconectar Calendario</span>
                                            <span class="text-[10px] text-gray-500 font-medium tracking-normal mt-0.5">Romper el vínculo con Google Calendar</span>
                                        </div>
                                    </button>
                                </form>
                            @endif
                        @endcan
                    </x-slot>
                </x-dropdown>
            @endif

            <!-- Cloning Button -->
            <form id="clone-activity-form-{{ $activity->id }}" action="{{ route('teams.activities.clone', [$team, $activity]) }}" method="POST" class="inline">
                @csrf
                <button type="button" onclick="confirmCloneTask('clone-activity-form-{{ $activity->id }}')" class="shrink-0 flex items-center gap-1.5 text-xs bg-violet-50 hover:bg-violet-100 dark:bg-violet-900/30 dark:hover:bg-violet-900/40 text-violet-600 dark:text-violet-400 px-4 py-2.5 rounded-xl transition-all font-bold active:scale-95 border border-violet-100 dark:border-violet-900/50 shadow-sm ml-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                    </svg>
                    <span class="hidden sm:inline">Clonar</span>
                </button>
            </form>




            @if(!$activity->trashed())
                @can('delete', $activity)
                    <form id="delete-activity-form-{{ $activity->id }}" action="{{ route('teams.activities.destroy', [$team, $activity]) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="button" onclick="confirmDelete('delete-activity-form-{{ $activity->id }}', '{{ __('activities.delete_confirm') }}')" class="shrink-0 flex items-center gap-1.5 text-xs bg-red-50 hover:bg-red-100 dark:bg-red-900/30 dark:hover:bg-red-900/40 text-red-600 dark:text-red-400 px-4 py-2.5 rounded-xl transition-all font-bold active:scale-95 border border-red-100 dark:border-red-900/50 shadow-sm ml-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span class="hidden sm:inline">{{ __('activities.delete') }}</span>
                        </button>
                    </form>
                @endcan
            @endif

            <script>
                function confirmRestoreMetadata(formId) {
                    Swal.fire({
                        title: '¿Restaurar metadatos?',
                        text: 'Esto sobreescribirá la descripción, fecha límite y otras configuraciones de esta actividad con los datos de su versión original. Las notas y archivos actuales no se borrarán.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#7c3aed',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'Sí, restaurar',
                        cancelButtonText: 'Cancelar',
                        background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                        color: document.documentElement.classList.contains('dark') ? '#f1f5f9' : '#1e293b',
                        customClass: {
                            popup: 'rounded-[2rem]',
                            confirmButton: 'rounded-xl font-black uppercase text-xs tracking-widest',
                            cancelButton: 'rounded-xl font-black uppercase text-xs tracking-widest'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById(formId).submit();
                        }
                    });
                }

                function confirmCloneTask(formId) {
                    Swal.fire({
                        title: '¿Clonar actividad?',
                        text: 'Se creará una copia exacta de esta actividad en este mismo equipo.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#7c3aed',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'Sí, clonar',
                        cancelButtonText: 'Cancelar',
                        background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                        color: document.documentElement.classList.contains('dark') ? '#f1f5f9' : '#1e293b',
                        customClass: {
                            popup: 'rounded-[2rem]',
                            confirmButton: 'rounded-xl font-black uppercase text-xs tracking-widest',
                            cancelButton: 'rounded-xl font-black uppercase text-xs tracking-widest'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById(formId).submit();
                        }
                    });
                }

                function reproduceInTeam() {
                    const teams = @json($otherTeams->pluck('name', 'id'));
                    let options = '';
                    for (const [id, name] of Object.entries(teams)) {
                        options += `<option value="${id}">${name}</option>`;
                    }

                    Swal.fire({
                        title: '¿A qué equipo la enviamos?',
                        html: `
                            <div class="text-left mt-4 border-t border-gray-100 dark:border-gray-800 pt-6">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2 ml-1">Selecciona el equipo de destino</label>
                                <select id="target-team-select" class="w-full bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 rounded-2xl px-5 py-4 text-sm font-bold text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500/20 outline-none">
                                    ${options}
                                </select>
                                <p class="mt-4 text-[10px] text-gray-500 font-medium italic">* Se creará una copia de esta actividad asignada a ti en el equipo seleccionado, manteniendo la descripción y preferencias básicas.</p>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'Reproducir 🚀',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#7c3aed',
                        background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                        color: document.documentElement.classList.contains('dark') ? '#f1f5f9' : '#1e293b',
                        customClass: {
                            popup: 'rounded-[2rem]',
                            confirmButton: 'rounded-xl font-black uppercase text-xs tracking-widest',
                            cancelButton: 'rounded-xl font-black uppercase text-xs tracking-widest'
                        },
                        preConfirm: () => {
                            return document.getElementById('target-team-select').value;
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch("{{ route('teams.activities.copy-to-team', [$team, $activity], false) }}", {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({ target_team_id: result.value })
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: '¡Listo!',
                                        text: data.message,
                                        showConfirmButton: true,
                                        confirmButtonText: 'Ir a la nueva actividad',
                                        confirmButtonColor: '#7c3aed'
                                    }).then(() => {
                                        window.location.href = data.url;
                                    });
                                } else {
                                    Swal.fire('Error', data.message, 'error');
                                }
                            });
                        }
                    });
                }

                function mergeTaskModal() {
                    const isDark = document.documentElement.classList.contains('dark');
                    Swal.fire({
                        title: '¿Fusionar esta actividad?',
                        html: `
                            <div class="text-left mt-4 text-gray-600 dark:text-gray-300 text-xs mb-4 border-b border-gray-100 dark:border-gray-800 pb-4 leading-relaxed">
                                ⚠️ Esta acción moverá todos los <strong>comentarios, registros de tiempo, archivos y subtareas</strong> a la actividad que elijas a continuación. Esta actividad actual (#{{ $activity->id }}) será <strong>eliminada permanentemente</strong> tras completarse.
                            </div>
                            <div class="text-left">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2 ml-1">Actividad Destino (Buscador Autocompletable)</label>
                                <div class="w-full relative min-h-[48px]">
                                    <select id="merge-target-select" class="w-full"></select>
                                </div>
                                <p class="mt-4 text-[9px] text-gray-500 font-medium italic">* La fusión de actividades es irreversible.</p>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: '🔥 Fusionar y Borrar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#e11d48',
                        background: isDark ? '#0f172a' : '#ffffff',
                        color: isDark ? '#f1f5f9' : '#1e293b',
                        customClass: {
                            popup: 'rounded-[2rem]',
                            confirmButton: 'rounded-xl font-black uppercase text-xs tracking-widest',
                            cancelButton: 'rounded-xl font-black uppercase text-xs tracking-widest'
                        },
                        didOpen: () => {
                            if (window.TomSelect) {
                                new TomSelect("#merge-target-select", {
                                    valueField: 'id',
                                    labelField: 'text',
                                    searchField: 'text',
                                    create: false,
                                    placeholder: 'Escribe para buscar...',
                                    load: function(query, callback) {
                                        const url = "{{ route('teams.activities.search', $team, false) }}?query=" + encodeURIComponent(query) + "&exclude_id={{ $activity->id }}";
                                        fetch(url)
                                            .then(response => response.json())
                                            .then(json => {
                                                callback(json);
                                            }).catch(() => {
                                                callback();
                                            });
                                    },
                                    shouldLoad: function(query) {
                                        return true;
                                    },
                                    render: {
                                        option: function(data, escape) {
                                            return '<div class="p-2 text-xs font-semibold text-gray-700 dark:text-gray-200 hover:bg-violet-50 dark:hover:bg-violet-950/30 rounded-lg">' + escape(data.text) + '</div>';
                                        },
                                        item: function(data, escape) {
                                            return '<div class="text-xs font-bold text-gray-900 dark:text-white">' + escape(data.text) + '</div>';
                                        }
                                    }
                                });
                            }
                        },
                        preConfirm: () => {
                            const selectEl = document.getElementById('merge-target-select');
                            const val = selectEl ? selectEl.value : '';
                            if (!val) {
                                Swal.showValidationMessage('Por favor selecciona una actividad válida.');
                            }
                            return val;
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            Swal.fire({
                                title: 'Fusionando...',
                                text: 'Estamos migrando toda la información...',
                                allowOutsideClick: false,
                                didOpen: () => { Swal.showLoading(); }
                            });

                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = "{{ route('teams.activities.merge-deprecated', [$team, $activity]) }}";
                            
                            const token = document.createElement('input');
                            token.type = 'hidden';
                            token.name = '_token';
                            token.value = '{{ csrf_token() }}';
                            form.appendChild(token);

                            const target = document.createElement('input');
                            target.type = 'hidden';
                            target.name = 'target_task_id';
                            target.value = result.value;
                            form.appendChild(target);

                            document.body.appendChild(form);
                            form.submit();
                        }
                    });
                }

                function printSection(sectionLabel, contentId) {
                    const el = document.getElementById(contentId);
                    if (!el) {
                        console.error('Print section element not found:', contentId);
                        return;
                    }
                    const content = el.innerHTML;
                    const taskTitle = @json($activity->title);
                    SientiaPrint.print(taskTitle, content, { brand: 'Sientia MTX • ' + sectionLabel });
                }

                function printPrivateNotes() {
                    const editor = document.getElementById('reply-content-private');
                    let rawContent = editor ? editor.value : '';
                    const taskTitle = @json($activity->title);
                    let htmlContent = typeof marked !== 'undefined' ? marked.parse(rawContent) : rawContent.replace(/\n/g, '<br>');
                    SientiaPrint.print(taskTitle, htmlContent, { brand: 'Sientia MTX • Notas Privadas' });
                }

                async function printFullTask() {
                    const isDark = document.documentElement.classList.contains('dark');
                    
                    const result = await Swal.fire({
                        title: '<span class="text-xs font-black uppercase tracking-widest text-indigo-600">Imprimir Ficha Técnica</span>',
                        background: isDark ? '#0f172a' : '#ffffff',
                        color: isDark ? '#f3f4f6' : '#1f2937',
                        showConfirmButton: false,
                        showCloseButton: true,
                        customClass: {
                            popup: 'rounded-[2.5rem] shadow-2xl border border-gray-200 dark:border-gray-800 p-6',
                        },
                        html: `
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-6 text-center px-4">
                                ¿Deseas imprimir la ficha técnica completa incluyendo el membrete e identidad de Sientia MTX?
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-2">
                                <button type="button" id="print-full-btn-with" class="flex flex-col items-center gap-3 p-5 rounded-[2rem] border-2 border-indigo-100 dark:border-indigo-950 bg-indigo-50/50 dark:bg-indigo-950/30 hover:border-indigo-600 transition-all text-center group">
                                    <div class="w-12 h-12 rounded-2xl bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 group-hover:scale-110 transition-transform shadow-sm">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    <div class="font-black text-[10px] uppercase tracking-widest text-indigo-700 dark:text-indigo-300">Con Cabeceras</div>
                                    <div class="text-[9px] font-bold text-gray-400 uppercase tracking-tight">Estilo oficial</div>
                                </button>
                                <button type="button" id="print-full-btn-without" class="flex flex-col items-center gap-3 p-5 rounded-[2rem] border-2 border-gray-100 dark:border-gray-800 bg-white dark:bg-slate-900 hover:border-gray-600 transition-all text-center group">
                                    <div class="w-12 h-12 rounded-2xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-600 dark:text-gray-400 group-hover:scale-110 transition-transform shadow-sm">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"/></svg>
                                    </div>
                                    <div class="font-black text-[10px] uppercase tracking-widest text-gray-700 dark:text-gray-300">Sin Cabeceras</div>
                                    <div class="text-[9px] font-bold text-gray-400 uppercase tracking-tight">Ficha limpia</div>
                                </button>
                            </div>
                        `,
                        didOpen: (el) => {
                            el.querySelector('#print-full-btn-with').onclick = () => { window._sientiaPrintFullMode = 'with'; Swal.close(); };
                            el.querySelector('#print-full-btn-without').onclick = () => { window._sientiaPrintFullMode = 'without'; Swal.close(); };
                        }
                    });

                    if (!window._sientiaPrintFullMode) return;
                    const withHeaders = window._sientiaPrintFullMode === 'with';
                    window._sientiaPrintFullMode = null;

                    const taskTitle = @json($activity->title);
                    const taskId = @json($activity->id);
                    const taskUuid = @json($activity->uuid ?? $activity->id);
                    const status = @json(__('activities.statuses.' . $activity->status_value));
                    const progress = @json($activity->progress_percentage);
                    const priorityLabel = @json(__('activities.priorities.' . $activity->priority));
                    const urgencyLabel = @json(__('activities.urgencies.' . $activity->urgency));
                    const scheduled = @json($activity->scheduled_date?->format('d/m/y H:i') ?? '—');
                    const due = @json($activity->due_date?->format('d/m/y H:i') ?? '—');
                    const teamName = @json($team->name);
                    const creator = @json($activity->creator?->name ?? '—');
                    
                    const description = document.getElementById('description-content')?.innerHTML ?? '—';
                    const observations = document.getElementById('observations-content')?.innerHTML ?? '—';
                    
                    const members = @json($activity->assignedTo->pluck('name')->toArray());
                    const skills = @json($activity->skills->map(fn($s) => $s->name)->toArray());

                    const printWin = window.open('', '_blank', 'width=950,height=1100');
                    printWin.document.write(`
                        <!DOCTYPE html>
                        <html lang="es">
                            <head>
                                <meta charset="UTF-8">
                                <title>Ficha Técnica - ${taskTitle}</title>
                                <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
                                <style>
                                    ${!withHeaders ? '.header, .side-accent { display: none !important; }' : ''}
                                    @page { size: A4; margin: 0; }
                                    body { 
                                        font-family: 'Outfit', sans-serif; 
                                        color: #1e293b; 
                                        line-height: 1.2; 
                                        margin: 0; padding: 0; background: #fff; 
                                        -webkit-print-color-adjust: exact; print-color-adjust: exact;
                                    }
                                    
                                    .sheet {
                                        position: relative;
                                        max-width: 210mm;
                                        width: 100%;
                                        margin: 0 auto;
                                        padding: 18mm 22mm;
                                        box-sizing: border-box;
                                        page-break-after: avoid;
                                    }

                                    .side-accent {
                                        position: absolute;
                                        top: 100px; left: 0; height: 60%; width: 3px;
                                        background: linear-gradient(to bottom, #ef4444, #f87171);
                                        border-radius: 0 3px 3px 0;
                                        opacity: 0.5;
                                    }

                                    .header {
                                        display: flex; justify-content: space-between; align-items: flex-end;
                                        margin-bottom: 12px; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;
                                    }

                                    .logo-text { 
                                        font-family: 'Outfit', sans-serif; 
                                        font-weight: 900; 
                                        font-size: 22px; 
                                        color: #0f172a;
                                        letter-spacing: -0.04em;
                                        line-height: 1;
                                    }
                                    .logo-text .dot { color: #ef4444; }
                                    .logo-text .suffix { color: #94a3b8; font-weight: 400; font-size: 16px; margin-left: 2px; }
                                    
                                    .document-info { text-align: right; }
                                    .doc-type { font-size: 7px; font-weight: 800; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.15em; margin-bottom: 2px; }
                                    .activity-uuid { font-family: 'JetBrains Mono', monospace; font-size: 6.5px; color: #cbd5e1; }

                                    .main-title {
                                        font-size: 19px; font-weight: 900; color: #0f172a;
                                        letter-spacing: -0.02em; margin: 0 0 10px 0;
                                    }

                                    .meta-strip {
                                        display: flex; gap: 1px; background: #f1f5f9;
                                        border: 1px solid #f1f5f9; border-radius: 6px;
                                        overflow: hidden; margin-bottom: 15px;
                                    }
                                    .meta-strip-item {
                                        flex: 1; background: #fff; padding: 6px 10px;
                                        display: flex; flex-direction: column;
                                    }
                                    .meta-strip-label { font-size: 6px; font-weight: 800; text-transform: uppercase; color: #94a3b8; margin-bottom: 1px; }
                                    .meta-strip-value { font-size: 9px; font-weight: 700; color: #1e293b; white-space: nowrap; }

                                    .content-layout {
                                        display: flex; gap: 20px;
                                    }
                                    .main-content { flex: 1; min-width: 0; order: 1; }
                                    .sidebar-content { width: 180px; flex-shrink: 0; order: 2; }

                                    .section { margin-bottom: 12px; }
                                    .section-title { 
                                        font-size: 8px; font-weight: 900; text-transform: uppercase; 
                                        color: #ef4444; margin-bottom: 4px;
                                        display: flex; align-items: center; gap: 6px;
                                    }
                                    .section-title::after { content: ''; flex: 1; height: 1px; background: #fef2f2; }

                                    .section-body { font-size: 11.5px; color: #334155; line-height: 1.4; }
                                    .section-body img { max-width: 100%; border-radius: 4px; margin: 4px 0; }

                                    .sidebar-box {
                                        background: #f8fafc; padding: 10px; border-radius: 6px; border: 1px solid #f1f5f9;
                                    }
                                    .sidebar-item { margin-bottom: 8px; }
                                    .sidebar-label { font-size: 6.5px; font-weight: 800; text-transform: uppercase; color: #94a3b8; margin-bottom: 2px; display: block; }
                                    .sidebar-value { font-size: 9px; font-weight: 700; color: #475569; }

                                    .pill-list { display: flex; flex-wrap: wrap; gap: 3px; margin-top: 2px; }
                                    .pill { font-size: 7.5px; font-weight: 700; background: #fff; border: 1px solid #e2e8f0; padding: 1px 5px; border-radius: 3px; color: #64748b; }

                                    .validation-area {
                                        margin-top: 15px; display: flex; gap: 40px; clear: both;
                                    }
                                    .signature-box { flex: 1; border-top: 1px solid #f1f5f9; padding-top: 4px; min-height: 30px; }
                                    .signature-label { font-size: 7px; font-weight: 700; color: #cbd5e1; text-transform: uppercase; }

                                    .footer { 
                                        margin-top: 15px; padding-top: 8px; border-top: 1px solid #f1f5f9;
                                        display: flex; justify-content: space-between;
                                        font-size: 6.5px; font-weight: 600; color: #cbd5e1; text-transform: uppercase;
                                        clear: both;
                                    }
                                    
                                    @media print {
                                        html, body { height: auto; }
                                        .sheet { border: none; height: auto; min-height: 0; }
                                        .content-layout { display: block; }
                                        .sidebar-content { float: right; width: 180px; margin-left: 20px; margin-bottom: 15px; }
                                        .main-content { display: block; }
                                        .content-layout::after { content: ""; display: table; clear: both; }
                                    }
                                </style>
                            </head>
                            <body>
                                <div class="sheet">
                                    <div class="side-accent"></div>
                                    
                                    <header class="header">
                                        <div class="logo-text">
                                            sientia<span class="dot">.</span><span class="suffix">MTX</span>
                                        </div>
                                        <div class="document-info">
                                            <div class="doc-type">Ficha Técnica &bull; ${teamName}</div>
                                            <div class="activity-uuid">ID: ${taskUuid.toString().toUpperCase()}</div>
                                        </div>
                                    </header>

                                    <h1 class="main-title">${taskTitle}</h1>

                                    <div class="meta-strip">
                                        <div class="meta-strip-item">
                                            <span class="meta-strip-label">Estado</span>
                                            <span class="meta-strip-value" style="color: #ef4444">${status}</span>
                                        </div>
                                        <div class="meta-strip-item">
                                            <span class="meta-strip-label">Progreso</span>
                                            <span class="meta-strip-value">${progress}%</span>
                                        </div>
                                        <div class="meta-strip-item">
                                            <span class="meta-strip-label">Prioridad</span>
                                            <span class="meta-strip-value">${priorityLabel}</span>
                                        </div>
                                        <div class="meta-strip-item">
                                            <span class="meta-strip-label">Urgencia</span>
                                            <span class="meta-strip-value">${urgencyLabel}</span>
                                        </div>
                                        <div class="meta-strip-item" style="flex: 1.2;">
                                            <span class="meta-strip-label">Inicio</span>
                                            <span class="meta-strip-value">${scheduled}</span>
                                        </div>
                                        <div class="meta-strip-item" style="flex: 1.2;">
                                            <span class="meta-strip-label">Límite</span>
                                            <span class="meta-strip-value" style="color: #ef4444">${due}</span>
                                        </div>
                                    </div>

                                    <div class="content-layout">
                                        <aside class="sidebar-content">
                                            <div class="sidebar-box">
                                                <div class="sidebar-item">
                                                    <span class="sidebar-label">Propietario</span>
                                                    <div class="sidebar-value">${creator}</div>
                                                </div>

                                                <div class="sidebar-item">
                                                    <span class="sidebar-label">Asignados</span>
                                                    <div class="pill-list">
                                                        ${members.map(m => `<span class="pill">${m}</span>`).join('') || '<span class="pill">Sin asignar</span>'}
                                                    </div>
                                                </div>

                                                <div class="sidebar-item" style="border-top: 1px dashed #f1f5f9; padding-top: 6px; margin-top: 6px;">
                                                    <span class="sidebar-label">Capacidades</span>
                                                    <div class="pill-list">
                                                        ${skills.map(s => `<span class="pill" style="color: #ef4444; border-color: #fee2e2;">${s}</span>`).join('') || '<span class="pill">—</span>'}
                                                    </div>
                                                </div>
                                            </div>
                                        </aside>

                                        <div class="main-content">
                                            <div class="section">
                                                <div class="section-title">Descripción</div>
                                                <div class="section-body">${description}</div>
                                            </div>

                                            <div class="section">
                                                <div class="section-title">Observaciones</div>
                                                <div class="section-body">${observations}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="validation-area">
                                        <div class="signature-box">
                                            <div class="signature-label">Firma Responsable</div>
                                        </div>
                                        <div class="signature-box">
                                            <div class="signature-label">Validación Sistema</div>
                                            <div style="font-size: 5px; color: #cbd5e1; margin-top: 1px; font-family: monospace;">TSR: ${new Date().getTime()}</div>
                                        </div>
                                    </div>

                                    <footer class="footer">
                                        <span>Sientia MTX Ecosystem &bull; v0.9.5</span>
                                        <span>${new Date().toLocaleString()}</span>
                                    </footer>
                                </div>
                                <script>
                                    window.onload = function() { 
                                        setTimeout(() => {
                                            window.print(); 
                                            setTimeout(() => window.close(), 500);
                                        }, 300);
                                    };
                                <\/script>
                            </body>
                        </html>
                    `);
                    printWin.document.close();
                }

                function printDocumentBook() {
                    const printWin = window.open('', '_blank');
                    const title = @json($activity->title);
                    const teamName = @json($team->name);
                    const docVersion = @json($activity->metadata['version'] ?? '1.0.0');
                    const chapters = @json($activity->metadata['chapters'] ?? []);
                    
                    let chaptersHtml = '';
                    let tocHtml = '';
                    
                    chapters.forEach((chap, idx) => {
                        tocHtml += `
                            <div class="toc-item">
                                <span class="toc-title">${idx + 1}. ${chap.title}</span>
                                <span class="toc-dots"></span>
                                <span class="toc-page">Capítulo ${idx + 1}</span>
                            </div>
                        `;
                        
                        chaptersHtml += `
                            <div class="chapter-page">
                                <div class="chapter-header">
                                    <span class="chapter-num">CAPÍTULO ${idx + 1}</span>
                                    <h2 class="chapter-title">${chap.title}</h2>
                                    <div class="chapter-meta">Por ${chap.author_name || 'Autor'} • ${chap.updated_at || ''}</div>
                                </div>
                                <div class="chapter-body">${marked.parse ? marked.parse(chap.content || '') : (chap.content || '')}</div>
                            </div>
                        `;
                    });

                    printWin.document.write(`
                        <!DOCTYPE html>
                        <html>
                            <head>
                                <title>${title} - Libro Digital</title>
                                <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&family=Merriweather:wght@300;400;700&display=swap" rel="stylesheet">
                                <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"><\/script>
                                <style>
                                    @page { size: A4; margin: 2.5cm 2cm; }
                                    body { font-family: 'Merriweather', serif; color: #1e293b; line-height: 1.8; margin: 0; padding: 0; font-size: 14px; }
                                    h1, h2, h3, h4, h5, h6, .outfit { font-family: 'Outfit', sans-serif; }
                                    
                                    /* Portada */
                                    .cover-page { height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; page-break-after: always; padding: 2rem; box-sizing: border-box; }
                                    .cover-team { font-size: 16px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 4px; margin-bottom: 2rem; font-family: 'Outfit', sans-serif; }
                                    .cover-title { font-size: 42px; font-weight: 900; color: #0f172a; line-height: 1.2; margin-bottom: 2rem; font-family: 'Outfit', sans-serif; }
                                    .cover-badge { display: inline-block; background: #f1f5f9; color: #475569; padding: 8px 24px; border-radius: 50px; font-size: 14px; font-weight: 700; margin-bottom: 4rem; font-family: 'Outfit', sans-serif; border: 1px solid #e2e8f0; }
                                    .cover-footer { margin-top: auto; font-size: 14px; color: #64748b; font-family: 'Outfit', sans-serif; }
                                    
                                    /* Índice */
                                    .toc-page { page-break-after: always; padding: 2rem 0; }
                                    .toc-main-title { font-size: 28px; font-weight: 800; color: #0f172a; margin-bottom: 3rem; font-family: 'Outfit', sans-serif; border-bottom: 2px solid #e2e8f0; padding-bottom: 1rem; }
                                    .toc-item { display: flex; align-items: baseline; margin-bottom: 1.5rem; font-family: 'Outfit', sans-serif; font-size: 16px; }
                                    .toc-title { font-weight: 600; color: #334155; }
                                    .toc-dots { flex: 1; border-bottom: 1px dotted #cbd5e1; margin: 0 12px; }
                                    .toc-page { font-weight: 700; color: #64748b; font-size: 14px; }
                                    
                                    /* Capítulos */
                                    .chapter-page { page-break-before: always; padding: 2rem 0; }
                                    .chapter-header { margin-bottom: 3rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 2rem; }
                                    .chapter-num { font-size: 14px; font-weight: 800; color: #8b5cf6; text-transform: uppercase; letter-spacing: 3px; font-family: 'Outfit', sans-serif; display: block; margin-bottom: 0.5rem; }
                                    .chapter-title { font-size: 32px; font-weight: 800; color: #0f172a; margin: 0 0 1rem 0; font-family: 'Outfit', sans-serif; line-height: 1.2; }
                                    .chapter-meta { font-size: 13px; color: #64748b; font-family: 'Outfit', sans-serif; }
                                    .chapter-body { color: #334155; }
                                    .chapter-body p { margin-bottom: 1.5rem; }
                                    .chapter-body h1, .chapter-body h2, .chapter-body h3 { font-family: 'Outfit', sans-serif; color: #0f172a; margin-top: 2.5rem; margin-bottom: 1rem; font-weight: 700; }
                                </style>
                            </head>
                            <body>
                                <div class="cover-page">
                                    <div class="cover-team">${teamName}</div>
                                    <h1 class="cover-title">${title}</h1>
                                    <div class="cover-badge">DOCUMENTO VERSIÓN ${docVersion}</div>
                                    <div class="cover-footer">Sientia MTX • Exportado el ${new Date().toLocaleDateString('es-ES')}</div>
                                </div>
                                
                                <div class="toc-page">
                                    <h2 class="toc-main-title">Índice General</h2>
                                    ${tocHtml}
                                </div>

                                ${chaptersHtml}
                                
                                <script>
                                    window.onload = () => {
                                        setTimeout(() => window.print(), 500);
                                    };
                                <\/script>
                            </body>
                        </html>
                    `);
                    printWin.document.close();
                }
            </script>

    </div>
    </x-slot>

    @php
        $highLevels = ['high', 'critical'];
        $imp = in_array($activity->priority, $highLevels);
        $urg = in_array($activity->urgency, $highLevels);
        $q = 4;
        if ($imp && $urg) {
            $q = 1;
        } elseif ($imp) {
            $q = 2;
        } elseif ($urg) {
            $q = 3;
        }

        $qCfg = [
            1 => [
                'color' => 'text-red-500 dark:text-red-400',
                'bg' => 'bg-red-50 dark:bg-red-950/40 border-red-100 dark:border-red-900/60 font-medium',
            ],
            2 => [
                'color' => 'text-blue-500 dark:text-blue-400',
                'bg' => 'bg-blue-50 dark:bg-blue-950/40 border-blue-100 dark:border-blue-900/60 font-medium',
            ],
            3 => [
                'color' => 'text-amber-500 dark:text-amber-400',
                'bg' => 'bg-amber-50 dark:bg-amber-950/40 border-amber-100 dark:border-blue-900/60 font-medium',
            ],
            4 => [
                'color' => 'text-gray-500 dark:text-gray-400',
                'bg' => 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 font-medium',
            ],
        ][$q];

        $statusColor = match ($activity->status_value) {
            'completed'
                => 'text-emerald-600 bg-emerald-50 border-emerald-100 dark:text-emerald-400 dark:bg-emerald-400/10 dark:border-emerald-800',
            'in_progress'
                => 'text-blue-600 bg-blue-50 border-blue-100 dark:text-blue-400 dark:bg-blue-400/10 dark:border-blue-800',
            'cancelled'
                => 'text-red-600 bg-red-50 border-red-100 dark:text-red-400 dark:bg-red-400/10 dark:border-red-800',
            'blocked'
                => 'text-white bg-red-600 border-red-700 dark:bg-red-500 dark:border-red-600 font-bold animate-pulse',
            default
                => 'text-amber-600 bg-amber-50 border-amber-100 dark:text-yellow-400 dark:bg-yellow-400/10 dark:border-yellow-800',
        };



        // Calculate Time Tracking Statistics
        $userObj = auth()->user();
        $isUserObjMgr = $team->isManager($userObj);
        $taskIds = $activity->children()->getQuery()->visibleTo($userObj, $isUserObjMgr)->pluck('activities.id')->push($activity->id);
        $allLogs = \App\Models\TimeLog::whereIn('task_id', $taskIds)->with('user')->get();
        
        $activeUserIds = $allLogs->whereNull('end_at')->pluck('user_id')->unique()->toArray();

        $timeStats = $allLogs->groupBy('user_id')
            ->map(function ($logs) {
                $totalSeconds = $logs->sum(function($log) {
                    $end = $log->end_at ?: now();
                    return $log->start_at->diffInSeconds($end);
                });
                return [
                    'user' => $logs->first()->user,
                    'seconds' => $totalSeconds,
                    'formatted' => (floor($totalSeconds / 3600) > 0 ? floor($totalSeconds / 3600) . "h " : "") . floor(($totalSeconds % 3600) / 60) . "m"
                ];
            })
            ->sortByDesc('seconds');

        $totalSecondsTask = $timeStats->sum('seconds');
        $totalFormattedTask = (floor($totalSecondsTask / 3600) > 0 ? floor($totalSecondsTask / 3600) . "h " : "") . floor(($totalSecondsTask % 3600) / 60) . "m";
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 pb-24 lg:pb-0">
        <!-- Main content -->
        @if($activity->isDeprecatedByConversion() && $activity->convertedToActivity)
                <div class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/10 dark:to-orange-900/10 border border-amber-200 dark:border-amber-800/50 rounded-2xl p-4 shadow-sm mb-6">
                    <div class="flex items-start sm:items-center gap-4 pl-2">
                        <div class="w-10 h-10 rounded-2xl bg-amber-500/20 text-amber-600 dark:text-amber-500 flex items-center justify-center shrink-0 border border-amber-500/30">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="text-sm font-black text-amber-800 dark:text-amber-400 uppercase tracking-wider">Actividad Deprecada (Archivada por Conversión)</h3>
                                <span class="px-2.5 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 text-[10px] font-black uppercase tracking-widest border border-amber-200 dark:border-amber-800/50 shadow-sm">
                                    LEGACY
                                </span>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-300 font-medium leading-relaxed">
                                Esta actividad fue convertida a una nueva actividad unificada. Se ha cerrado y archivado en este formato antiguo para preservar el rastro de auditoría.
                            </p>
                            <a href="{{ route('teams.activities.show', [$team, $activity->convertedToActivity]) }}" class="inline-flex items-center gap-1 text-[11px] font-bold text-violet-600 dark:text-violet-400 hover:underline mt-2">
                                👉 Ver la nueva actividad resultante ({{ $activity->convertedToActivity->type_label ?? $activity->convertedToActivity->type }})
                            </a>
                        </div>
                    </div>
                </div>
            @endif
            
            @if(!$activity->isDeprecatedByConversion() && $activity->convertedFromActivity)
                <div class="bg-gradient-to-r from-violet-50 to-indigo-50 dark:from-violet-900/10 dark:to-indigo-900/10 border border-violet-200 dark:border-violet-800/50 rounded-2xl p-4 shadow-sm mb-6">
                    <div class="flex items-start sm:items-center gap-4 pl-2">
                        <div class="w-10 h-10 rounded-2xl bg-violet-500/20 text-violet-600 dark:text-violet-500 flex items-center justify-center shrink-0 border border-violet-500/30">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="w-full">
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="text-sm font-black text-violet-800 dark:text-violet-400 uppercase tracking-wider">Historial de Conversión (Vidas Pasadas)</h3>
                                <span class="px-2.5 py-0.5 rounded-full bg-violet-100 dark:bg-violet-950/50 text-violet-700 dark:text-violet-300 text-[10px] font-black uppercase tracking-widest border border-violet-200 dark:border-violet-800/50 shadow-sm">
                                    {{ $activity->convertedFromActivity->type_label ?? $activity->convertedFromActivity->type }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-300 font-medium leading-relaxed">
                                Esta actividad nació como una conversión de <strong>"{{ $activity->convertedFromActivity->title }}"</strong> ({{ $activity->convertedFromActivity->type_label ?? $activity->convertedFromActivity->type }}). La versión original se encuentra archivada pero mantiene todos sus datos y configuraciones intactos.
                            </p>
                            
                            <div class="mt-3 flex items-center gap-3">
                                <a href="{{ route('teams.activities.show', [$team, $activity->convertedFromActivity]) }}" class="inline-flex items-center gap-1.5 text-xs bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 px-3 py-1.5 rounded-lg transition-all font-bold hover:bg-gray-50 dark:hover:bg-gray-700 shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Ver actividad original
                                </a>
                                
                                @if(auth()->user()->can('update', $activity))
                                <form id="restore-metadata-form-{{ $activity->id }}" action="{{ route('teams.activities.restore-metadata', [$team, $activity]) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="button" onclick="confirmRestoreMetadata('restore-metadata-form-{{ $activity->id }}')" class="inline-flex items-center gap-1.5 text-xs bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-400 px-3 py-1.5 rounded-lg transition-all font-bold hover:bg-violet-100 dark:hover:bg-violet-900/40">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        Restaurar Metadatos
                                    </button>
                                </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @php $allAttachments = $activity->all_attachments; @endphp

        <div class="lg:col-span-2 space-y-5">
            @include('teams.activities.types.' . $activity->type . '.show-content')
        </div>

        <!-- Sidebar -->
        @include('teams.activities.partials.show-sidebar')
    </div>

    @push('scripts')
        @include('teams.activities.partials.import-modal-script')
    @endpush

    @push('scripts')
{{-- ============================================================
     BARRA FLOTANTE DE ACCIONES RÁPIDAS
     ============================================================ --}}
<div id="activity-floating-bar"
     x-data="floatingDraggable"
     @mousedown="startDrag"
     @touchstart.passive="startDrag"
     @window:mousemove="drag"
     @window:touchmove.passive="drag"
     @window:mouseup="stopDrag"
     @window:touchend="stopDrag"
     class="fixed bottom-6 left-1/2 -translate-x-1/2 translate-y-4 z-[800] flex items-center gap-2 px-4 py-2.5 bg-white/93 dark:bg-gray-900/93 backdrop-blur-xl border border-gray-100 dark:border-gray-800 rounded-2xl shadow-2xl opacity-0 pointer-events-none transition-all duration-300 whitespace-nowrap cursor-move"
     :class="isDragging ? 'scale-105 shadow-[0_20px_50px_rgba(0,0,0,0.2)]' : ''">

    {{-- Volver --}}
    <a href="{{ $backUrl ?? route('teams.dashboard', $team) }}"
       style="display:flex;align-items:center;gap:0.375rem;font-size:0.75rem;font-weight:700;color:#6b7280;padding:0.375rem 0.75rem;border-radius:0.625rem;text-decoration:none;transition:all 0.15s ease;"
       onmouseover="this.style.color='#7c3aed';this.style.background='#f5f3ff'"
       onmouseout="this.style.color='#6b7280';this.style.background='transparent'">
        <svg style="width:1rem;height:1rem;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        <span>{{ __('navigation.back') ?? 'Volver' }}</span>
    </a>

    <div style="width:1px;height:1.25rem;background:#e5e7eb;flex-shrink:0"></div>

    {{-- Título truncado --}}
    <span style="font-size:0.75rem;font-weight:900;color:#1f2937;max-width:200px;overflow:hidden;text-overflow:ellipsis;">
        {{ $activity->title }}
    </span>

    @can('update', $activity)
        <div style="width:1px;height:1.25rem;background:#e5e7eb;flex-shrink:0"></div>
        <a href="{{ route('teams.activities.edit', [$team, $activity]) }}"
           style="display:flex;align-items:center;gap:0.375rem;font-size:0.75rem;font-weight:700;color:#fff;background:#7c3aed;padding:0.375rem 0.75rem;border-radius:0.625rem;text-decoration:none;transition:background 0.15s ease;"
           onmouseover="this.style.background='#6d28d9'"
           onmouseout="this.style.background='#7c3aed'">
            <svg style="width:1rem;height:1rem;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            <span>{{ __('activities.edit') }}</span>
        </a>
    @endcan
</div>

<script>
    function savePrivateNotes() {
        const content = document.getElementById('reply-content-private').value;
        const button = event.currentTarget;
        const originalText = button.innerHTML;
        
        button.disabled = true;
        button.innerHTML = '<svg class="animate-spin h-3 w-3 mr-2 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> GUARDANDO...';

        fetch("{{ route('teams.activities.private-notes.update', [$team, $personalInstance], false) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ content: content })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: '{{ __("Notas guardadas correctamente") }}',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '{{ __("No se pudieron guardar las notas") }}'
            });
        })
        .finally(() => {
            button.disabled = false;
            button.innerHTML = originalText;
        });
    }
</script>

    <script>
        (function() {
            const bar = document.getElementById('activity-floating-bar');
            if (!bar) return;

            const checkScroll = (e) => {
                let scrollY = 0;
                if (e && e.target && e.target !== document) {
                    scrollY = e.target.scrollTop;
                } else {
                    scrollY = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
                }
                
                if (scrollY > 150) {
                    bar.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none');
                    bar.classList.add('opacity-100', 'translate-y-0', 'pointer-events-auto');
                } else {
                    bar.classList.add('opacity-0', 'translate-y-4', 'pointer-events-none');
                    bar.classList.remove('opacity-100', 'translate-y-0', 'pointer-events-auto');
                }
            };

            window.addEventListener('scroll', checkScroll, { passive: true, capture: true });
            
            setTimeout(() => checkScroll(), 100);
        })();
    </script>
@endpush

    @if(isset($activity) && $activity)
    <!-- MODAL DE CONVERSIÓN DE ACTIVIDAD -->
    <div x-data="{ 
        show: false, 
        targetType: 'activity',
        types: [
            { id: 'activity', label: 'Tarea General', icon: '📝', desc: 'Actividad estándar con seguimiento de urgencia, carga cognitiva y gestión de progreso.' },
            { id: 'document', label: 'Documento / Base de Conocimiento', icon: '📄', desc: 'Registro centrado en la documentación colaborativa y control de versiones.' },
            { id: 'link', label: 'Enlace / Recurso Externo', icon: '🔗', desc: 'Referencia a un sitio web, herramienta externa o repositorio de información.' },
            { id: 'meeting', label: 'Reunión / Encuentro', icon: '🤝', desc: 'Evento programado con modalidad (remota/presencial), duración y ubicación.' },
            { id: 'reminder', label: 'Recordatorio / Notificación', icon: '🔔', desc: 'Aviso puntual con canales de distribución (Email, Push, etc.).' }
        ]
    }"
        @open-convert-activity-modal.window="show = true"
        x-show="show"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        @click.self="show = false">
        
        <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] w-full max-w-2xl overflow-hidden shadow-2xl border border-gray-100 dark:border-gray-800 transform transition-all text-left flex flex-col max-h-[90vh]"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95">
            
            <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-violet-50/50 dark:bg-violet-950/20">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xl">✨</span>
                        <h3 class="text-lg font-black text-gray-900 dark:text-white uppercase tracking-tight">
                            {{ __('Convertir Actividad') }}
                        </h3>
                    </div>
                    <p class="text-xs text-gray-500 font-medium">
                        {{ __('Transforma esta actividad a un nuevo tipo. La original quedará archivada como deprecada para mantener el rastro de auditoría.') }}
                    </p>
                </div>
                <button @click="show = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors p-2 rounded-2xl hover:bg-white dark:hover:bg-gray-800 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form action="{{ route('teams.activities.convert', [$team, $activity]) }}" method="POST" class="flex flex-col flex-1 overflow-hidden m-0">
                @csrf
                <div class="p-8 overflow-y-auto custom-scrollbar flex-1">
                    
                    <div class="mb-6 p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/50">
                        <div class="flex gap-3">
                            <div class="text-amber-500 mt-0.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-amber-800 dark:text-amber-400 uppercase tracking-widest mb-1">Aviso de Integridad</h4>
                                <p class="text-xs text-amber-700/80 dark:text-amber-500/80 leading-relaxed font-medium">
                                    Solo se conservarán los metadatos y atributos compatibles con el nuevo tipo seleccionado. Los campos exclusivos de "{{ $activity->type_label ?? 'Actividad' }}" que no existan en el nuevo esquema se descartarán en la nueva versión.
                                </p>
                            </div>
                        </div>
                    </div>

                    <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-3 ml-1">
                        Selecciona el nuevo tipo de actividad
                    </label>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <template x-for="type in types" :key="type.id">
                            <label class="relative flex cursor-pointer rounded-2xl border bg-white dark:bg-gray-800/50 p-4 shadow-sm focus:outline-none transition-all group hover:border-violet-300 dark:hover:border-violet-700"
                                :class="targetType === type.id ? 'border-violet-500 ring-2 ring-violet-500/20 bg-violet-50/30 dark:bg-violet-900/10' : 'border-gray-200 dark:border-gray-700'">
                                
                                <input type="radio" name="type" :value="type.id" x-model="targetType" class="sr-only">
                                
                                <div class="flex w-full items-start justify-between gap-4">
                                    <div class="flex items-start gap-4">
                                        <div class="text-2xl mt-1 p-2 rounded-xl bg-gray-50 dark:bg-gray-800 group-hover:scale-110 transition-transform" 
                                             :class="targetType === type.id ? 'bg-violet-100 dark:bg-violet-900/50 text-violet-600 dark:text-violet-400' : ''"
                                             x-text="type.icon">
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-gray-900 dark:text-white mb-1" x-text="type.label"></span>
                                            <span class="text-[10px] text-gray-500 dark:text-gray-400 font-medium leading-relaxed" x-text="type.desc"></span>
                                        </div>
                                    </div>
                                    
                                    <div class="shrink-0 text-violet-500" x-show="targetType === type.id">
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                            <circle cx="12" cy="12" r="10" stroke-opacity="0.2" fill="currentColor" fill-opacity="0.1"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4" />
                                        </svg>
                                    </div>
                                </div>
                            </label>
                        </template>
                    </div>

                </div>

                <div class="px-8 py-5 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/20 flex items-center justify-between gap-3 shrink-0">
                    <button type="button" @click="show = false" class="px-6 py-2.5 text-xs font-bold text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm active:scale-95">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-2.5 text-xs font-bold text-white bg-violet-600 hover:bg-violet-500 rounded-xl shadow-lg shadow-violet-500/25 transition-all active:scale-95 flex items-center gap-2">
                        <span>Proceder a la Conversión</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @push('scripts')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <style>
        /* Estilos personalizados para TomSelect dentro de SweetAlert */
        .swal2-html-container .ts-wrapper {
            border: none !important;
            background: transparent !important;
            padding: 0 !important;
            box-shadow: none !important;
        }
        .swal2-html-container .ts-control {
            border-radius: 1rem !important;
            border-width: 1px !important;
            background-color: #f9fafb !important;
            border-color: #e5e7eb !important;
            padding: 0.75rem 1.25rem !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
            min-height: 48px !important;
            display: flex !important;
            align-items: center !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            text-align: left !important;
        }
        .dark .swal2-html-container .ts-control {
            background-color: #1f2937 !important;
            border-color: #374151 !important;
            color: #f3f4f6 !important;
        }
        .swal2-html-container .ts-wrapper.focus .ts-control {
            border-color: #7c3aed !important;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.2) !important;
        }
        .swal2-html-container .ts-dropdown {
            border-radius: 1rem !important;
            border: 1px solid #e5e7eb !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
            margin-top: 6px !important;
            padding: 0.5rem !important;
            z-index: 99999 !important;
            background-color: #ffffff !important;
            position: absolute !important;
        }
        .dark .swal2-html-container .ts-dropdown {
            background-color: #111827 !important;
            border-color: #374151 !important;
        }
        .swal2-html-container .ts-dropdown .option {
            padding: 0.625rem 0.75rem !important;
            border-radius: 0.6rem !important;
            margin-bottom: 2px !important;
            transition: all 0.15s ease !important;
            color: #374151 !important;
            text-align: left !important;
        }
        .dark .swal2-html-container .ts-dropdown .option {
            color: #e5e7eb !important;
        }
        .swal2-html-container .ts-dropdown .active {
            background-color: #f5f3ff !important;
            color: #4f46e5 !important;
        }
        .dark .swal2-html-container .ts-dropdown .active {
            background-color: #4f46e5 !important;
            color: #ffffff !important;
        }
    </style>
    @endpush
</x-app-layout>
