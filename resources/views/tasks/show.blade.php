<x-app-layout>
    @section('title', $task->title)    @php
        // Find if the current user has a personal instance of this goal (regardless of being a template or not)
        $personalInstance =
            $task->is_template || $task->children()->exists()
                ? $task
                    ->instances()
                    ->where('assigned_user_id', auth()->id())
                    ->first()
                : null;
    @endphp

    <x-slot name="header">
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4">
            <div class="flex items-start gap-3 min-w-0 flex-1">
                <a href="{{ $backUrl ?? route('teams.dashboard', $team) }}"
                    class="mt-1 p-2 bg-gray-50 dark:bg-gray-800/50 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-xl transition-all shadow-sm border border-gray-100 dark:border-gray-700/50 shrink-0"
                    title="{{ __('navigation.back') ?? 'Volver' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div class="min-w-0 flex-1">
                    @include('teams.partials.breadcrumb')
                    <h1 class="text-xl sm:text-3xl font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight">
                        {{ __('tasks.detail') }}
                    </h1>
                </div>
            </div>
        </div>

        <!-- View Switcher Sub-Header -->
        <div class="mt-8 mb-4 flex w-full">
            @include('teams.partials.view-switcher')
        </div>

        <!-- Task Actions Footer Row -->
        <div class="flex items-center gap-2 flex-wrap shrink-0 mt-4 border-t border-gray-100 dark:border-gray-800 pt-6">
            @if($team->isCoordinator(auth()->user()) || auth()->user()->is_admin)
                <a href="{{ route('teams.tasks.create', $team) }}"
                    class="shrink-0 flex items-center gap-1.5 text-xs bg-violet-600 hover:bg-violet-500 text-white px-4 py-2.5 rounded-xl transition-all shadow-lg shadow-violet-500/20 font-bold active:scale-95">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="hidden lg:inline">{{ __('tasks.create') }}</span>
                </a>
            @endif

            @can('update', $task)
                <a href="{{ route('teams.tasks.edit', [$team, $task]) }}"
                    class="shrink-0 flex items-center gap-1.5 text-xs bg-white dark:bg-white/5 border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300 px-4 py-2.5 rounded-xl transition-all font-bold hover:bg-gray-50 dark:hover:bg-white/10 active:scale-95 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    {{ __('tasks.edit') }}
                </a>
            @endcan

            @if ($task->is_template && ($team->isCoordinator(auth()->user()) || auth()->id() === $task->created_by_id))
                <form action="{{ route('teams.tasks.sync-to-children', [$team, $task]) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="shrink-0 flex items-center gap-1.5 text-xs bg-white dark:bg-white/5 border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300 px-4 py-2.5 rounded-xl transition-all font-bold hover:bg-gray-50 dark:hover:bg-white/10 active:scale-95 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span class="hidden sm:inline">{{ __('tasks.sync_members') }}</span>
                    </button>
                </form>
            @endif

            <!-- TIMER BUTTON (Start/Stop) -->
            @if (!$task->is_template)
                @include('tasks.partials.task-timer-button', ['task' => $task])
            @elseif ($personalInstance)
                @include('tasks.partials.task-timer-button', ['task' => $personalInstance])
            @endif

            <!-- Hub de Acciones Secundarias (Acciones) -->
            <x-dropdown align="right" width="80">
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
                    </div>

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
                                <span class="text-[10px] text-gray-500 font-medium tracking-normal mt-0.5">Clonar tarea en otro espacio de trabajo</span>
                            </div>
                        </button>
                    @endif

                    <!-- Exportar JSON -->
                    <div class="flex flex-col border-t border-gray-50 dark:border-gray-800 pt-1 mt-1">
                        <div class="px-5 py-2 text-[9px] font-black uppercase tracking-widest text-gray-400">Portabilidad (Outbound)</div>
                        <x-dropdown-link :href="route('teams.tasks.export-json', [$team, $task])" class="flex items-center gap-4 py-3 px-5 group">
                            <div class="shrink-0 p-2 bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-xl group-hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-bold text-gray-900 dark:text-white text-sm">Exportar Tarea (.json)</span>
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
                        <button type="button" onclick="openImportTaskModal('file')" class="w-full flex items-center gap-4 py-3 px-5 text-start hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150 ease-in-out group">
                            <div class="shrink-0 p-2 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 rounded-xl group-hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-bold text-gray-900 dark:text-white text-sm">Cargar Archivo (.json)</span>
                                <span class="text-[10px] text-gray-500 font-medium tracking-normal mt-0.5">Crear tarea desde un archivo local</span>
                            </div>
                        </button>

                        <!-- Pegar JSON -->
                        <button type="button" onclick="openImportTaskModal('paste')" class="w-full flex items-center gap-4 py-3 px-5 text-start hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150 ease-in-out group">
                            <div class="shrink-0 p-2 bg-teal-50 dark:bg-teal-900/20 text-teal-600 dark:text-teal-400 rounded-xl group-hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m.5 4l3 3m0 0l3-3m-3 3V10" />
                                </svg>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-bold text-gray-900 dark:text-white text-sm">Pegar JSON</span>
                                <span class="text-[10px] text-gray-500 font-medium tracking-normal mt-0.5">Crear tarea desde el portapapeles</span>
                            </div>
                        </button>
                    </div>
                </x-slot>
            </x-dropdown>

            <!-- Hub de Integraciones (Integraciones) -->
            @php
                $isGoogleConnected = auth()->user()->teams()->where('team_id', $team->id)->wherePivotNotNull('google_token')->exists();
            @endphp
            @if($isGoogleConnected || $team->isCoordinator(auth()->user()) || auth()->user()->is_admin)
                <x-dropdown align="right" width="80">
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
                        
                        @can('update', $task)
                            <!-- Sincronización Google Tasks -->
                            <form action="{{ route('google.sync_task', [$team, $task]) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-4 py-4 px-5 text-start hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150 ease-in-out group">
                                    <div class="shrink-0 p-2 {{ $task->google_task_id ? 'bg-indigo-50 text-indigo-600' : 'bg-amber-50 text-amber-600' }} rounded-xl group-hover:scale-110 transition-transform">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-gray-900 dark:text-white text-sm">Sincronizar Google Tasks</span>
                                        <span class="text-[10px] text-gray-500 font-medium tracking-normal mt-0.5">Vínculo bidireccional con Google</span>
                                    </div>
                                </button>
                            </form>

                            <!-- Google Calendar -->
                            <form action="{{ route('google.export_calendar', [$team, $task]) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-4 py-4 px-5 text-start hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150 ease-in-out group">
                                    <div class="shrink-0 p-2 {{ $task->google_calendar_event_id ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600' }} rounded-xl group-hover:scale-110 transition-transform">
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
                        @endcan
                    </x-slot>
                </x-dropdown>
            @endif

            @can('delete', $task)
                <form id="delete-task-form-{{ $task->id }}" action="{{ route('teams.tasks.destroy', [$team, $task]) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="button" onclick="confirmDelete('delete-task-form-{{ $task->id }}', '{{ __('tasks.delete_confirm') }}')" class="shrink-0 flex items-center gap-1.5 text-xs bg-red-50 hover:bg-red-100 dark:bg-red-900/30 dark:hover:bg-red-900/40 text-red-600 dark:text-red-400 px-4 py-2.5 rounded-xl transition-all font-bold active:scale-95 border border-red-100 dark:border-red-900/50 shadow-sm ml-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <span class="hidden sm:inline">{{ __('tasks.delete') }}</span>
                    </button>
                </form>
            @endcan

            <script>
                function reproduceInTeam() {
                    const teams = @json($otherTeams->pluck('name', 'id'));
                    let options = '';
                    for (const [id, name] of Object.entries(teams)) {
                        options += `<option value="${id}">${name}</option>`;
                    }

                    Swal.fire({
                        title: '¿A qué equipo lo enviamos?',
                        html: `
                            <div class="text-left mt-4 border-t border-gray-100 dark:border-gray-800 pt-6">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2 ml-1">Selecciona el equipo de destino</label>
                                <select id="target-team-select" class="w-full bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 rounded-2xl px-5 py-4 text-sm font-bold text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500/20 outline-none">
                                    ${options}
                                </select>
                                <p class="mt-4 text-[10px] text-gray-500 font-medium italic">* Se creará una copia de esta tarea asignada a ti en el equipo seleccionado, manteniendo la descripción y preferencias básicas.</p>
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
                            fetch("{{ route('teams.tasks.copy-to-team', [$team, $task]) }}", {
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
                                        confirmButtonText: 'Ir a la nueva tarea',
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
            </script>

    </div>
    </x-slot>

    @php
        $highLevels = ['high', 'critical'];
        $imp = in_array($task->priority, $highLevels);
        $urg = in_array($task->urgency, $highLevels);
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

        $statusColor = match ($task->status) {
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
        $taskIds = $task->children()->pluck('id')->push($task->id);
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main content -->
        <div class="lg:col-span-2 space-y-5">
            <!-- Task Name Card -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm dark:shadow-none transition-colors">
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                        {{ __('tasks.name') }}</h3>
                    @if ($task->is_template)
                        <span class="px-2 py-0.5 rounded-full bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300 text-[9px] font-black uppercase tracking-widest border border-violet-200 dark:border-violet-700/50 shadow-sm flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            {{ __('tasks.plan_master') }}
                        </span>
                    @endif
                    @if ($task->assigned_user_id === auth()->id() && $task->parent_id)
                        <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 text-[9px] font-black uppercase tracking-widest border border-emerald-200 dark:border-emerald-700/50 shadow-sm flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            {{ __('tasks.your_execution') }}
                        </span>
                    @endif
                </div>
                <p class="text-xl font-bold text-gray-900 dark:text-white heading leading-tight">
                    {{ $task->title }}
                </p>
            </div>



            @if ($task->is_template || $task->children()->exists() || $task->assignedTo->isNotEmpty())
                @php
                    $isRoadmap = $task->is_template || $task->children()->exists();
                    if ($isRoadmap) {
                        $instances = $task->is_template
                            ? $task->instances()->with('assignedUser')->get()->sortBy(function($inst) {
                                return mb_strtolower(($inst->assignedUser?->name ?? '') . ' ' . $inst->title);
                            })
                            : $task->children()->with('assignedTo')->get()->sortBy(function($inst) {
                                return mb_strtolower(($inst->assignedTo->first()?->name ?? '') . ' ' . $inst->title);
                            });
                    } else {
                        // For regular tasks, we "simulate" instances using the assigned users
                        $instances = $task->assignedTo->map(function($user) use ($task) {
                            return (object)[
                                'id' => $task->id, // Reference to same task
                                'name' => $task->title,
                                'status' => $task->status,
                                'progress' => $task->progress_percentage,
                                'assignedUser' => $user,
                                'timeLogs' => $task->timeLogs()->where('user_id', $user->id)->get(),
                                'is_simulated' => true,
                                'user_id' => $user->id
                            ];
                        });
                    }

                    $totalInst = $instances->count();
                    $sumProg = $isRoadmap ? $instances->sum('progress_percentage') : ($totalInst > 0 ? $task->progress_percentage * $totalInst : 0);
                    $prog = $totalInst > 0 ? $sumProg / $totalInst : 0;
                    $doneInst = $isRoadmap ? $instances->where('status', 'completed')->count() : ($task->status === 'completed' ? $totalInst : 0);
                    $hasBlocker = $isRoadmap ? $instances->where('status', 'blocked')->isNotEmpty() : ($task->status === 'blocked');
                @endphp

                <!-- Progress Dashboard -->
                <div
                    class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm dark:shadow-none transition-colors">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3
                                class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">
                                {{ $isRoadmap ? __('tasks.roadmap_progress') : __('teams.members') }}</h3>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white heading">
                                {{ $isRoadmap ? $doneInst . '/' . $totalInst : $totalInst }} 
                                <span class="text-sm font-medium text-gray-400">
                                    {{ $isRoadmap ? __('tasks.completed') : ($totalInst == 1 ? __('tasks.assigned_to_one') : __('tasks.assigned_to_many')) }}
                                </span>
                            </p>
                        </div>
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 flex-1">
                            <div class="relative flex-1 max-w-sm" x-data="{ rSearch: '' }" x-init="$watch('rSearch', v => $dispatch('roadmap-filter', v))">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <input type="text" x-model="rSearch" 
                                    placeholder="{{ __('Filtrar por miembro...') }}" 
                                    class="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700/50 rounded-2xl text-xs outline-none focus:border-violet-500 focus:ring-4 focus:ring-violet-500/5 transition-all font-sans">
                            </div>

                            <div class="text-right min-w-[6rem]">
                                <div class="flex items-center justify-end gap-2 mb-1.5">
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ __('Progreso') }}</span>
                                    <span class="text-sm font-black text-violet-600 dark:text-violet-400 tabular-nums">{{ round($prog) }}%</span>
                                </div>
                                <div class="w-24 bg-gray-100 dark:bg-gray-800 rounded-full h-1.5 overflow-hidden ml-auto">
                                    <div class="bg-gradient-to-r from-violet-500 to-indigo-500 h-full rounded-full transition-all duration-700 ease-out"
                                        style="width: {{ $prog }}%">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="w-full h-3 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden mb-8 border border-gray-200 dark:border-gray-700">
                        <div id="global-progress-bar"
                            class="h-full bg-gradient-to-r from-violet-500 to-indigo-600 shadow-lg shadow-violet-500/20"
                            style="width: {{ $prog }}%; transition: none !important;"></div>
                    </div>

                    @if ($hasBlocker)
                        <div
                            class="mb-6 p-4 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900/50 rounded-xl flex items-center gap-3 animate-pulse">
                            <div
                                class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center text-red-600 dark:text-red-400 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-red-700 dark:text-red-400">
                                    {{ __('tasks.blocker_detected') }}</p>
                                <p class="text-xs text-red-600/80 dark:text-red-400/70">
                                    {{ __('tasks.blocker_description') }}</p>
                            </div>
                        </div>
                    @endif
                    <div class="overflow-y-auto max-h-[600px] scrollbar-thin scrollbar-thumb-gray-200 dark:scrollbar-thumb-gray-800 border border-gray-100 dark:border-gray-800 rounded-xl custom-scrollbar"
                        x-data="{ 
                            sortKey: 'name', 
                            sortDir: 'asc',
                            sort(key) {
                                if (this.sortKey === key) {
                                    this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
                                } else {
                                    this.sortKey = key;
                                    this.sortDir = 'asc';
                                }
                                const tbody = this.$refs.roadmapBody;
                                const rows = Array.from(tbody.querySelectorAll('tr'));
                                rows.sort((a, b) => {
                                    let va = a.dataset[this.sortKey];
                                    let vb = b.dataset[this.sortKey];
                                    if (!isNaN(va) && !isNaN(vb)) {
                                        va = parseFloat(va);
                                        vb = parseFloat(vb);
                                    }
                                    if (va < vb) return this.sortDir === 'asc' ? -1 : 1;
                                    if (va > vb) return this.sortDir === 'asc' ? 1 : -1;
                                    return 0;
                                });
                                rows.forEach(row => tbody.appendChild(row));
                            }
                        }">
                        <table class="w-full text-left text-sm">
                            <thead
                                class="sticky top-0 z-10 bg-gray-50/95 dark:bg-gray-800/95 backdrop-blur-sm text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th class="px-4 py-3 cursor-pointer hover:text-violet-500 transition-colors group" @click="sort('name')">
                                        <div class="flex items-center gap-2">
                                            {{ __('teams.members') }}
                                            <svg class="h-3 w-3 opacity-0 group-hover:opacity-100 transition-opacity" :class="sortKey === 'name' ? 'opacity-100' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path x-show="sortKey !== 'name' || sortDir === 'asc'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                                <path x-show="sortKey === 'name' && sortDir === 'desc'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" />
                                            </svg>
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 cursor-pointer hover:text-violet-500 transition-colors group" @click="sort('status')">
                                        <div class="flex items-center gap-2">
                                            {{ __('tasks.status') }}
                                            <svg class="h-3 w-3 opacity-0 group-hover:opacity-100 transition-opacity" :class="sortKey === 'status' ? 'opacity-100' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path x-show="sortKey !== 'status' || sortDir === 'asc'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                                <path x-show="sortKey === 'status' && sortDir === 'desc'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" />
                                            </svg>
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-right cursor-pointer hover:text-violet-500 transition-colors group" @click="sort('time')">
                                        <div class="flex items-center justify-end gap-2">
                                            {{ __('tasks.time_spent') ?? 'Tiempo' }}
                                            <svg class="h-3 w-3 opacity-0 group-hover:opacity-100 transition-opacity" :class="sortKey === 'time' ? 'opacity-100' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path x-show="sortKey !== 'time' || sortDir === 'asc'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                                <path x-show="sortKey === 'time' && sortDir === 'desc'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" />
                                            </svg>
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-right">{{ __('tasks.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody x-ref="roadmapBody" class="divide-y divide-gray-100 dark:divide-gray-800/60" x-data="{ roadmapQuery: '' }" @roadmap-filter.window="roadmapQuery = $event.detail">

                                @foreach ($instances as $inst)
                                    @php
                                        $instMember = $inst->assignedUser;
                                        $instMemberName = $instMember?->name ?? '—';
                                        $instSeconds = (int) $inst->timeLogs->sum(fn($l) => $l->start_at->diffInSeconds($l->end_at ?: now()));
                                        $instFormatted = (floor($instSeconds / 3600) > 0 ? floor($instSeconds / 3600) . "h " : "") . floor(($instSeconds % 3600) / 60) . "m";
                                        $isInstActive = $inst->timeLogs->whereNull('end_at')->isNotEmpty();
                                        
                                        // Team membership date
                                        $teamMember = $instMember ? $team->members()->where('users.id', $instMember->id)->first() : null;
                                        $joinedAt = $teamMember?->pivot?->joined_at;
                                        $joinedDate = ($joinedAt instanceof \Carbon\Carbon) ? $joinedAt->format('d/m/Y') : null;

                                        $isSimulated = isset($inst->is_simulated) && $inst->is_simulated;
                                        $subtasksCount = $isSimulated ? 0 : $inst->children()->count();
                                        $subtasksDone = $isSimulated ? 0 : $inst->children()->where('status', 'completed')->count();
                                    @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors cursor-pointer group" 
                                        data-name="{{ strtolower($instMemberName) }}"
                                        data-status="{{ $inst->status }}"
                                        data-time="{{ $instSeconds }}"
                                        x-show="roadmapQuery === '' || '{{ strtolower($instMemberName) }}'.includes(roadmapQuery.toLowerCase()) || '{{ strtolower($inst->name) }}'.includes(roadmapQuery.toLowerCase())"
                                        x-transition
                                        @if(!$isSimulated) onclick="if(!event.target.closest('button, select, a')) window.location='{{ route('teams.tasks.show', [$team->id, $inst->id]) }}'" @endif>
                                        <td class="px-4 py-4 group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors" onclick="event.stopPropagation()">
                                            <div class="flex items-center gap-4">
                                                <div class="relative">
                                                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 flex items-center justify-center text-xs font-bold text-gray-600 dark:text-gray-400 shadow-inner border border-white dark:border-gray-800 {{ $isInstActive ? 'ring-2 ring-red-500 ring-offset-2 dark:ring-offset-gray-900 animate-pulse' : '' }}">
                                                        @if ($instMember)
                                                            {{ strtoupper(substr($instMember->name, 0, 2)) }}
                                                        @else
                                                            ?
                                                        @endif
                                                    </div>
                                                    @if($isInstActive)
                                                        <span class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 border-2 border-white dark:border-gray-900 rounded-full"></span>
                                                    @endif
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-sm font-black text-gray-900 dark:text-white truncate">{{ $isSimulated ? $instMemberName : $inst->name }}</span>
                                                        @if($isInstActive)
                                                            <span class="text-[7px] font-black bg-red-500 text-white px-1 rounded animate-pulse">LIVE</span>
                                                        @endif
                                                    </div>
                                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-0.5">
                                                        <span class="text-[10px] font-medium text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                                            {{ $isSimulated ? __('tasks.assigned') : ($instMemberName ?: (__('tasks.unassigned') ?? '?')) }}
                                                        </span>

                                                        @if($joinedDate)
                                                            <span class="text-[9px] text-gray-400 flex items-center gap-1">
                                                                <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                                {{ __('tasks.member_since', ['date' => $joinedDate]) ?? "Desde $joinedDate" }}
                                                            </span>
                                                        @endif

                                                        @if($subtasksCount > 0)
                                                            <span class="text-[9px] font-bold text-violet-500 bg-violet-50 dark:bg-violet-900/30 px-1.5 py-0.5 rounded flex items-center gap-1 shrink-0">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                                                                {{ $subtasksDone }}/{{ $subtasksCount }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4">
                                            @php
                                                $instStatusColor = match ($inst->status) {
                                                    'completed' => 'text-emerald-500 dark:text-emerald-400',
                                                    'in_progress' => 'text-blue-500 dark:text-blue-400',
                                                    'blocked' => 'text-red-600 dark:text-red-400 font-bold',
                                                    default => 'text-gray-500 dark:text-gray-400',
                                                };
                                            @endphp
                                            <div class="flex flex-col gap-1.5">
                                                <div class="flex items-center gap-1.5 {{ $instStatusColor }}">
                                                    <div
                                                        class="w-1.5 h-1.5 rounded-full {{ str_contains($instStatusColor, 'text-') ? str_replace('text-', 'bg-', explode(' ', $instStatusColor)[0]) : 'bg-gray-400' }}">
                                                    </div>
                                                    <span
                                                        class="text-xs font-bold uppercase tracking-tight">{{ __('tasks.statuses.' . $inst->status) }}</span>
                                                </div>
                                                <div class="flex items-center gap-2 w-28">
                                                    <div class="flex-1 h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                                        <div class="h-full bg-gradient-to-r from-violet-500 to-indigo-500 transition-all duration-300" style="width: {{ $inst->progress }}%"></div>
                                                    </div>
                                                    <span class="text-[9px] text-gray-400 font-bold w-5 tabular-nums">{{ $inst->progress }}%</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-right tabular-nums">
                                            <div class="flex flex-col items-end">
                                                <span class="text-xs font-black text-gray-900 dark:text-white tabular-nums flex items-center gap-1.5">
                                                    @if($isInstActive)
                                                        <span class="relative flex h-1.5 w-1.5">
                                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-red-500"></span>
                                                        </span>
                                                    @endif
                                                    {{ $instSeconds > 0 ? $instFormatted : '—' }}
                                                </span>
                                                @if($isInstActive)
                                                    <span class="text-[7px] font-bold text-red-500 uppercase tracking-widest mt-0.5 animate-pulse">{{ __('En curso') }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-right">
                                            @if ($inst->status !== 'completed' && $team->isCoordinator(auth()->user()))
                                                <button onclick="event.stopPropagation(); nudgeUser({{ $isSimulated ? $task->id : $inst->id }})"
                                                    class="p-2 text-violet-600 hover:bg-violet-50 dark:text-violet-400 dark:hover:bg-violet-400/10 rounded-lg transition-all"
                                                    title="{{ __('tasks.nudge_user') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                        stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                                    </svg>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if (!$isRoadmap && $task->assignedGroups->isNotEmpty())
                        <div class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-800/60">
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-widest font-bold mb-3">
                                {{ __('tasks.groups') }}
                            </p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($task->assignedGroups as $g)
                                    <span class="bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-[9px] px-2 py-1 rounded-lg font-bold uppercase tracking-wider border border-indigo-100 dark:border-indigo-800/50 shadow-sm">
                                        {{ $g->name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            @if ($task->isInstance())
                <div
                    class="bg-indigo-50/80 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/20 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm dark:shadow-none transition-colors mb-6">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 rounded-2xl bg-white dark:bg-indigo-500/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0 shadow-sm border border-indigo-100 dark:border-indigo-500/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-extrabold text-indigo-900 dark:text-indigo-300 tracking-tight">
                                @if($task->assigned_user_id === auth()->id())
                                    {{ __('tasks.personal_instance_notice') }}
                                @else
                                    {{ __('tasks.personal_instance_notice_others', ['name' => ($task->assignedUser?->name ?? ($task->creator?->name ?? 'User'))]) }}
                                @endif
                                
                                @if ($team->isCoordinator(auth()->user()))
                                    <div class="inline-block relative">
                                        <select onchange="reassignTask({{ $task->id }}, this.value)" class="text-xs bg-white dark:bg-indigo-900 border border-indigo-200 dark:border-indigo-700 hover:border-indigo-300 rounded-lg ml-2 px-2 py-1 shadow-sm font-bold text-indigo-700 dark:text-indigo-300 cursor-pointer">
                                            <option value="" disabled {{ !$task->assigned_user_id ? 'selected' : '' }}>{{ __('Reasignar a...') }}</option>
                                            <option value="unassign">-- {{ __('Pendiente de asignación') }} --</option>
                                            @foreach($team->members()->orderBy('name')->get() as $member)
                                                <option value="{{ $member->id }}" {{ $task->assigned_user_id === $member->id ? 'selected' : '' }}>
                                                    {{ $member->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                            </p>
                            <p class="text-xs text-indigo-700/70 dark:text-indigo-400/80 font-medium">
                                @if($task->assigned_user_id === auth()->id())
                                    {{ __('tasks.personal_instance_description') }}
                                @else
                                    {{ __('tasks.personal_instance_description_others') }}
                                @endif
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('teams.tasks.show', [$team, $task->parent_id]) }}"
                        class="text-xs font-bold text-indigo-600 dark:text-indigo-300 hover:text-white hover:bg-indigo-600 dark:hover:bg-indigo-500 px-5 py-2.5 bg-white dark:bg-indigo-500/10 rounded-xl shadow-sm border border-indigo-100 dark:border-indigo-500/20 transition-all text-center">
                        {{ __('tasks.view_global_goal') }}
                    </a>
                </div>
            @endif

            <!-- Skills / Árbol de Capacidades -->
            @php $taskSkills = $task->skills; @endphp
            @if($taskSkills->isNotEmpty())
                <div class="flex flex-wrap gap-2 mb-6 ml-1">
                    @foreach($taskSkills as $skill)
                        <div class="group inline-flex items-center gap-2.5 px-3.5 py-2 bg-white dark:bg-gray-900 border border-amber-100 dark:border-amber-900/40 rounded-2xl shadow-sm hover:shadow-md hover:border-amber-300 dark:hover:border-amber-700 transition-all duration-300 cursor-default">
                            <div class="w-2 h-2 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 shadow-sm shadow-amber-500/20 group-hover:scale-125 transition-transform"></div>
                            <div class="flex flex-col">
                                <span class="text-[10px] font-black text-amber-700 dark:text-amber-400 uppercase tracking-widest leading-none">{{ $skill->name }}</span>
                                <span class="text-[8px] text-amber-600/50 dark:text-amber-500/30 font-bold uppercase tracking-tighter mt-0.5 leading-none">{{ $skill->category }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
            @php
                $displayDescription = $task->description ?: ($task->parent?->description ?? null);
                $displayObservations = $task->observations ?: ($task->parent?->observations ?? null);
            @endphp

            @if ($displayDescription)
                <div
                    class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm dark:shadow-none transition-colors">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                            {{ __('tasks.description') }}
                        </h3>
                        @if($team->isCoordinator(auth()->user()) || auth()->id() === $task->assigned_user_id)
                        <button @click="$dispatch('ai:analyze-task', { taskId: {{ $task->id }}, teamId: {{ $team->id }}, taskTitle: '{{ addslashes($task->title) }}', section: 'description' })" 
                                class="p-2 bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 rounded-xl transition-all shadow-sm flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest border border-indigo-100 dark:border-indigo-800/50"
                                title="Mejorar Resumen con IA">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Ax.ia
                        </button>
                        @endif
                    </div>
                    <div
                        class="text-sm text-gray-700 dark:text-gray-300 prose dark:prose-invert max-w-none prose-sm leading-relaxed">
                        {!! str($displayDescription)->markdown() !!}
                    </div>
                </div>
            @endif

            @if ($displayObservations)
                <div
                    class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm dark:shadow-none transition-colors">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                            {{ __('tasks.observations') }}
                        </h3>
                        <div class="flex items-center gap-2">
                            @if($team->isCoordinator(auth()->user()) || auth()->id() === $task->assigned_user_id)
                            <button @click="$dispatch('ai:analyze-task', { taskId: {{ $task->id }}, teamId: {{ $team->id }}, taskTitle: '{{ addslashes($task->title) }}', section: 'observaciones' })" 
                                    class="p-2 bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 rounded-xl transition-all shadow-sm flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest border border-indigo-100 dark:border-indigo-800/50"
                                    title="Desarrollar contenido con IA">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                Ax.ia
                            </button>
                            @endif
                            <button onclick="printObservations()" 
                                    class="p-2 bg-gray-50 dark:bg-gray-800 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-xl transition-all border border-transparent hover:border-indigo-100 dark:hover:border-indigo-800 shadow-sm flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest"
                                    title="Imprimir observaciones">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                Imprimir
                            </button>
                        </div>
                    </div>
                    <div id="observations-content"
                        class="text-sm text-gray-700 dark:text-gray-300 prose dark:prose-invert max-w-none prose-sm leading-relaxed">
                        {!! str($displayObservations)->markdown() !!}
                    </div>
                </div>

                <script>
                    function printObservations() {
                        const content = document.getElementById('observations-content').innerHTML;
                        const taskTitle = @json($task->title);
                        const isDark = document.documentElement.classList.contains('dark');
                        
                        const printWin = window.open('', '_blank', 'width=800,height=900');
                        printWin.document.write(`
                            <html>
                                <head>
                                    <title>Imprimir - ${taskTitle}</title>
                                    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
                                    <style>
                                        body { 
                                            font-family: 'Inter', -apple-system, sans-serif; 
                                            padding: 50px; 
                                            color: #1e293b; 
                                            line-height: 1.6;
                                            background-color: #fff;
                                        }
                                        .header { 
                                            border-bottom: 4px solid #4f46e5; 
                                            margin-bottom: 40px; 
                                            padding-bottom: 20px; 
                                            display: flex;
                                            justify-content: space-between;
                                            align-items: flex-end;
                                        }
                                        .title-container { flex: 1; }
                                        .brand { 
                                            font-weight: 900; 
                                            font-size: 10px; 
                                            text-transform: uppercase; 
                                            letter-spacing: 0.3em; 
                                            color: #6366f1; 
                                            margin-bottom: 8px;
                                            display: block;
                                        }
                                        .title { 
                                            font-size: 28px; 
                                            font-weight: 900; 
                                            color: #0f172a; 
                                            margin: 0; 
                                            line-height: 1.1;
                                            letter-spacing: -0.02em;
                                        }
                                        .meta { 
                                            font-size: 10px; 
                                            color: #94a3b8; 
                                            font-weight: 700; 
                                            text-transform: uppercase;
                                            margin-top: 10px;
                                        }
                                        .content { 
                                            font-size: 15px; 
                                            color: #334155;
                                        }
                                        .content h1 { font-size: 20px; margin-top: 30px; }
                                        .content h2 { font-size: 18px; margin-top: 25px; }
                                        .content p { margin-bottom: 15px; }
                                        .content ul, .content ol { padding-left: 20px; margin-bottom: 15px; }
                                        .logo-watermark {
                                            position: fixed;
                                            bottom: 40px;
                                            right: 40px;
                                            opacity: 0.1;
                                            font-weight: 900;
                                            font-size: 24px;
                                            letter-spacing: -0.05em;
                                            color: #4f46e5;
                                        }
                                        @media print {
                                            body { padding: 0; }
                                            .header { border-color: #000; }
                                        }
                                    </style>
                                </head>
                                <body>
                                    <div class="header">
                                        <div class="title-container">
                                            <span class="brand">Sientia MTX &bull; Observaciones</span>
                                            <h1 class="title">${taskTitle}</h1>
                                            <div class="meta">Documento generado el ${new Date().toLocaleDateString()} a las ${new Date().toLocaleTimeString()}</div>
                                        </div>
                                    </div>
                                    <div class="content">${content}</div>
                                    <div class="logo-watermark">Sientia.</div>
                                    <script>
                                        window.onload = function() {
                                            window.print();
                                            setTimeout(() => window.close(), 500);
                                        };
                                    <\/script>
                                </body>
                            </html>
                        `);
                        printWin.document.close();
                    }
                </script>
            @endif

            <!-- Private Notes -->
            <div x-data="{ 
                saving: false,
                doSave() {
                    this.saving = true;
                    // Fuerza la lectura del DOM justo antes de enviar
                    const editor = document.getElementById('reply-content-private');
                    const contentValue = editor ? editor.value : '';

                    fetch('{{ route('teams.tasks.private-notes.update', [$team, $task]) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ content: contentValue })
                    })
                    .then(res => res.json())
                    .then(data => {
                        this.saving = false;
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Guardado',
                                text: data.message,
                                toast: true,
                                position: 'top-end',
                                timer: 3000,
                                showConfirmButton: false
                            });
                        }
                    })
                    .catch(err => {
                        this.saving = false;
                        console.error(err);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudo guardar la nota.',
                            toast: true,
                            position: 'top-end',
                            timer: 3000,
                            showConfirmButton: false
                        });
                    });
                }
            }"
            class="bg-white dark:bg-gray-900 border border-amber-200/50 dark:border-amber-900/30 rounded-2xl p-5 shadow-sm dark:shadow-none transition-colors relative overflow-hidden group">
                
                {{-- Decorative background icon --}}
                <div class="absolute -right-4 -top-4 text-amber-500/5 dark:text-amber-500/10 rotate-12 group-hover:rotate-6 transition-transform duration-500 pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-32 h-32" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>

                <div class="flex items-center justify-between mb-4 relative z-10">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xs font-black text-amber-700 dark:text-amber-500 uppercase tracking-widest">
                                {{ __('Mis Notas Privadas') }}
                            </h3>
                            <p class="text-[9px] text-amber-600/50 dark:text-amber-500/40 font-bold uppercase tracking-tighter">Solo visibles para ti</p>
                        </div>
                    </div>

                    <button type="button" @click="doSave()" 
                        :disabled="saving"
                        class="px-4 py-1.5 bg-amber-600 hover:bg-amber-500 disabled:opacity-50 text-white text-[10px] font-black uppercase tracking-widest rounded-xl transition-all shadow-lg shadow-amber-600/20 flex items-center gap-2">
                        <template x-if="!saving">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                            </svg>
                        </template>
                        <template x-if="saving">
                            <svg class="animate-spin h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                        <span x-text="saving ? 'Guardando...' : 'Guardar Notas'"></span>
                    </button>
                </div>

                <div class="relative z-10" id="private-notes-editor">
                    <x-markdown-editor 
                        name="private_content" 
                        :value="$task->currentPrivateNote?->content ?? ''"
                        id="reply-content-private"
                        placeholder="Escribe aquí tus reflexiones, avances o notas que nadie más deba ver..."
                        rows="8"
                        :upload-url="route('teams.forum.upload_image', $team)"
                    />
                </div>
            </div>


            <!-- History -->
            @if ($task->histories->isNotEmpty())
                <div
                    class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm dark:shadow-none transition-colors">
                    <div
                        class="px-5 py-3 border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-transparent">
                        <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                            {{ __('tasks.history') }}</h3>
                    </div>
                    <div class="divide-y divide-gray-50 dark:divide-gray-800 max-h-[250px] overflow-y-auto">
                        @foreach ($task->histories->sortByDesc('created_at')->take(20) as $h)
                            <div class="px-5 py-2 text-xs transition-colors hover:bg-gray-50 dark:hover:bg-white/5 border-b border-gray-50 dark:border-gray-800/50 last:border-none" x-data="{ open: false }">
                                <div class="flex items-center justify-between gap-4 cursor-pointer" @click="open = !open">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-6 h-6 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-[10px] font-bold text-gray-600 dark:text-gray-400 shrink-0 shadow-sm border border-white dark:border-gray-800">
                                            {{ strtoupper(substr($h->user?->name ?? '?', 0, 2)) }}
                                        </div>
                                        <div class="truncate">
                                            <span class="font-bold text-gray-700 dark:text-gray-300">{{ $h->user?->name ?? '—' }}</span>
                                            <span class="text-[10px] bg-indigo-50 dark:bg-indigo-900/40 text-indigo-500 dark:text-indigo-400 px-1.5 py-0.5 rounded ml-1 font-black uppercase tracking-tighter">{{ $h->action }}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 shrink-0">
                                        <span class="text-[10px] text-gray-400 font-medium tabular-nums">{{ $h->created_at->format('d/m H:i') }}</span>
                                        <svg class="h-3 w-3 text-gray-300 transition-transform duration-300" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
                                    </div>
                                </div>
                                
                                <div x-show="open" style="display: none;" class="mt-3 ml-9 pb-2 border-l-2 border-indigo-100 dark:border-indigo-900/50 pl-4 space-y-2 animate-fade-in-down" x-transition>
                                    @if($h->old_values || $h->new_values)
                                        <div class="flex flex-col gap-2">
                                            @foreach(($h->new_values ?? []) as $key => $val)
                                                <div class="flex flex-col gap-0.5">
                                                    <span class="text-[9px] font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400">{{ $key }}</span>
                                                    <div class="flex items-center gap-2 text-[10px]">
                                                        <span class="bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 px-1.5 py-0.5 rounded line-through opacity-70">
                                                            {{ is_array($h->old_values[$key] ?? null) ? 'JSON' : ($h->old_values[$key] ?? '—') }}
                                                        </span>
                                                        <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 7l5 5m0 0l-5 5m5-5H6" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                        <span class="bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 px-1.5 py-0.5 rounded font-bold">
                                                            {{ is_array($val) ? 'JSON' : $val }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if($h->notes)
                                        <p class="text-[10px] text-gray-500 dark:text-gray-400 italic bg-gray-50 dark:bg-gray-800/50 p-2 rounded-xl">{{ $h->notes }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Attachments Section -->
            <div x-data="{}"
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm dark:shadow-none transition-colors">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                        {{ __('tasks.attachments') }}
                    </h3>
                    <div class="flex flex-col items-end">
                        <button type="button" onclick="document.getElementById('attachment-input').click()"
                            class="text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            {{ __('tasks.add_attachment') }}
                        </button>
                        @php 
                            $isTeamLinked = auth()->user()->teams()->where('team_id', $team->id)->wherePivotNotNull('google_token')->exists();
                        @endphp

                        @if($isTeamLinked)
                            <button type="button" onclick="window.openSientiaDrivePicker()"
                                class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1 ml-3">
                                <svg class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse mr-1" viewBox="0 0 24 24"></svg>
                                {{ __('Google Drive') }}
                            </button>
                        @else
                            <a href="{{ route('profile.edit', ['tab' => 'integrations']) }}" 
                                class="text-[10px] font-bold text-gray-400 hover:text-violet-500 transition-colors ml-3 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.172 13.828a4 4 0 015.656 0l4 4a4 4 0 01-5.656 5.656l-1.102-1.101" /></svg>
                                Vincular Drive
                            </a>
                        @endif
                        <span class="text-[9px] text-gray-400 dark:text-gray-500 mt-1 uppercase tracking-tighter font-medium">
                            {{ __('Máx. :size por archivo', ['size' => ini_get('upload_max_filesize')]) }}
                        </span>
                    </div>
                    <form id="attachment-form" action="{{ route('teams.tasks.attachments.upload', [$team, $task]) }}"
                        method="POST" enctype="multipart/form-data" class="hidden">
                        @csrf
                        <input type="file" id="attachment-input" name="file"
                            onchange="handleAttachmentUpload(this)">
                    </form>
                </div>

                @php $allAttachments = $task->all_attachments; @endphp
                @if ($allAttachments->isEmpty())
                    <p class="text-xs text-gray-400 italic">{{ __('tasks.no_attachments') }}</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach ($allAttachments as $attachment)
                            @php 
                                $isFromMe = $attachment->user_id === auth()->id();
                                $isFromParent = $attachment->task_id === $task->parent_id;
                                $isFromChild = $attachment->task_id !== $task->id && $attachment->task_id !== $task->parent_id;
                            @endphp
                            <div
                                class="group flex items-center justify-between p-3 {{ $isFromParent ? 'bg-indigo-50/30 dark:bg-indigo-900/10 border-indigo-100/50' : 'bg-gray-50 dark:bg-gray-800/50 border-gray-100 dark:border-gray-700/50' }} border rounded-xl hover:border-violet-200 dark:hover:border-violet-800 transition-all">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div
                                        class="w-10 h-10 rounded-lg flex items-center justify-center shadow-sm border shrink-0 {{ $attachment->storage_provider === 'google' ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-100 dark:border-blue-800' : ($isFromParent ? 'bg-indigo-50 dark:bg-gray-800 text-indigo-500 border-gray-100 dark:border-gray-700' : 'bg-white dark:bg-gray-800 text-violet-600 dark:text-violet-400 border-gray-100 dark:border-gray-700') }}">
                                        @if($attachment->storage_provider === 'google')
                                            <svg class="w-6 h-6" viewBox="0 0 48 48">
                                                <path fill="#FFC107" d="M17 6H11L2 22l3 5h6l9-16z"/>
                                                <path fill="#2196F3" d="M37 42H11l-9-15 4-7h26l9 16z"/>
                                                <path fill="#4CAF50" d="M15 6l9 16 9-16H15z"/>
                                            </svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[12px] font-bold text-gray-800 dark:text-white truncate"
                                            title="{{ $attachment->file_name }}">
                                            @if($attachment->storage_provider === 'google' && $attachment->web_view_link)
                                                <a href="{{ $attachment->web_view_link }}" 
                                                   target="_blank" 
                                                   class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors flex items-center gap-1">
                                                    {{ $attachment->file_name }}
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                </a>
                                            @else
                                                <a href="{{ route('teams.attachments.view', [$team, $attachment]) }}" 
                                                   target="_blank" 
                                                   class="hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                                    {{ $attachment->file_name }}
                                                </a>
                                            @endif
                                        </p>
                                        <p class="text-[10px] text-gray-400 flex items-center gap-1.5">
                                            @if($attachment->storage_provider === 'google')
                                                <span class="bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-1.5 rounded font-black uppercase text-[8px]">Google Drive</span>
                                            @else
                                                {{ number_format($attachment->file_size / 1024 / 1024, 2) }} MB
                                            @endif
                                            •
                                            @if($isFromParent) 
                                                <span class="text-indigo-500 font-bold uppercase tracking-tighter">{{ __('tasks.shared') ?? 'Plan' }}</span>
                                            @elseif($isFromChild)
                                                <span class="text-amber-500 font-bold uppercase tracking-tighter">{{ $attachment->task?->assignedUser?->name ?? 'Equipo' }}</span>
                                            @else
                                                {{ $attachment->created_at->diffForHumans() }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div
                                    class="flex items-center gap-1 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity">
                                    @if($attachment->storage_provider === 'local' && auth()->user()->google_token)
                                        <form action="{{ route('teams.attachments.to-drive', [$team, $attachment]) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" 
                                                class="p-1.5 text-gray-500 hover:text-blue-600 transition-colors"
                                                title="Subir a Google Drive">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                    
                                    {{-- Botón de Historial --}}
                                    <button type="button" 
                                        onclick="showAttachmentHistory({{ $attachment->id }})"
                                        class="p-1.5 text-amber-500 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 transition-colors"
                                        title="Ver histórico de movimientos">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>

                                    {{-- Botón de Inyección IA --}}
                                    <button type="button" 
                                        @click="$dispatch('ai:analyze-file', { 
                                            fileName: '{{ addslashes($attachment->file_name) }}', 
                                            fileId: {{ $attachment->id }},
                                            fileUrl: '{{ $attachment->storage_provider === 'google' ? $attachment->web_view_link : route('teams.attachments.view', [$team, $attachment]) }}',
                                            fileType: '{{ $attachment->mime_type }}',
                                            taskId: {{ $task->id }},
                                            teamId: {{ $team->id }},
                                            autoSubmit: false 
                                        })"
                                        class="p-1.5 text-indigo-500 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors"
                                        title="Preguntar a la IA sobre este archivo">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </button>

                                    <a href="{{ route('teams.attachments.download', [$team, $attachment]) }}"
                                        target="_blank" rel="noopener noreferrer"
                                        class="p-1.5 text-gray-500 hover:text-violet-600 dark:hover:text-violet-400 transition-colors"
                                        title="{{ __('tasks.view_or_download') ?? 'Ver o descargar' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                    @if($attachment->task_id === $task->id)
                                        @can('update', $task)
                                            <button type="button"
                                                onclick="renameAttachment({{ $attachment->id }}, '{{ addslashes($attachment->file_name) }}')"
                                                class="p-1.5 text-gray-500 hover:text-blue-600 transition-colors"
                                                title="{{ __('tasks.edit') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </button>
                                            <form
                                                action="{{ route('teams.attachments.destroy', [$team, $attachment]) }}"
                                                method="POST" class="inline"
                                                id="delete-attachment-{{ $attachment->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button"
                                                    onclick="confirmAttachmentDelete({{ $attachment->id }})"
                                                    class="p-1.5 text-gray-500 hover:text-red-600 transition-colors"
                                                    title="{{ __('tasks.delete') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endcan
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Disk Quota Widget -->
            <div
                class="bg-violet-50 dark:bg-violet-500/10 border border-violet-100 dark:border-violet-500/20 rounded-2xl p-5 shadow-sm dark:shadow-none transition-colors">
                <div class="flex items-center justify-between mb-4">
                    <span
                        class="text-xs text-violet-600 dark:text-violet-400 font-bold uppercase tracking-widest">{{ __('tasks.disk_quota') }}</span>
                    <span class="text-xs text-gray-400 font-medium">
                        {{ number_format(auth()->user()->disk_used / 1024 / 1024, 1) }} /
                        {{ number_format(auth()->user()->disk_quota / 1024 / 1024, 0) }} MB
                    </span>
                </div>
                @php
                    $perc =
                        auth()->user()->disk_quota > 0
                            ? (auth()->user()->disk_used / auth()->user()->disk_quota) * 100
                            : 0;
                    $barColor = $perc > 90 ? 'bg-red-500' : ($perc > 70 ? 'bg-amber-500' : 'bg-violet-500');
                @endphp
                <div class="w-full h-2 bg-gray-200 dark:bg-gray-800 rounded-full overflow-hidden shadow-inner">
                    <div class="h-full {{ $barColor }} shadow-lg" style="width: {{ $perc }}%"></div>
                </div>
                <p
                    class="text-[11px] text-gray-500 dark:text-gray-400 mt-3 font-medium flex items-center gap-1.5 font-sans">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ __('tasks.quota_usage_tip') }}
                </p>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-4">
            <!-- Personal Execution Card (if applicable) -->
            @if ($personalInstance && $personalInstance->assigned_user_id === auth()->id())
                <div class="bg-indigo-50/50 dark:bg-indigo-900/10 border border-indigo-100 dark:border-indigo-800/30 rounded-2xl p-4 space-y-3 shadow-sm dark:shadow-none transition-colors relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 to-purple-500/5 pointer-events-none"></div>
                    <p class="relative text-[10px] text-indigo-600 dark:text-indigo-400 uppercase tracking-widest font-bold mb-1 flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        {{ __('tasks.your_execution') ?? 'Tu Ejecución' }}
                    </p>

                    @if ($personalInstance->status !== 'completed')
                        <button onclick="updateTaskStatus('completed', {{ $personalInstance->id }})"
                            class="relative w-full flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold py-2.5 rounded-xl transition-all shadow-md shadow-indigo-600/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ __('tasks.mark_complete') }}
                        </button>
                    @else
                        <button onclick="updateTaskStatus('pending', {{ $personalInstance->id }})"
                            class="relative w-full flex items-center justify-center gap-2 bg-white dark:bg-gray-800 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/50 text-xs font-bold py-2.5 rounded-xl transition-all border border-indigo-200 dark:border-indigo-700">
                            {{ __('tasks.reopen_task') }}
                        </button>
                    @endif

                    @if ($personalInstance->status === 'blocked')
                        <button onclick="updateTaskStatus('in_progress', {{ $personalInstance->id }})"
                            class="relative w-full flex items-center justify-center gap-2 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-500/10 dark:hover:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs font-bold py-2.5 rounded-xl transition-all border border-emerald-200 dark:border-emerald-500/20 shadow-sm animate-pulse">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            {{ __('tasks.unblock_task') }}
                        </button>
                    @else
                        <button onclick="updateTaskStatus('blocked', {{ $personalInstance->id }})"
                            class="relative w-full flex items-center justify-center gap-2 bg-red-50 hover:bg-red-100 dark:bg-red-500/10 dark:hover:bg-red-500/20 text-red-600 dark:text-red-400 text-xs font-bold py-2.5 rounded-xl transition-all border border-red-200 dark:border-red-500/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            {{ __('tasks.report_blocker') }}
                        </button>
                    @endif

                    <div class="relative pt-2 border-t border-indigo-100/50 dark:border-indigo-800/30 mt-2">
                        <label class="flex items-center justify-between text-[10px] text-indigo-400 dark:text-indigo-500 uppercase tracking-widest font-bold mb-3">
                            <span>{{ __('tasks.your_progress') ?? 'Tu Progreso' }}</span>
                            <div class="flex items-center gap-1 min-w-[3rem] justify-end">
                                <span id="personal-progress-val" class="text-indigo-600 dark:text-indigo-400 tabular-nums">{{ $personalInstance->progress }}</span>
                                <span class="text-indigo-400 text-[8px]">%</span>
                            </div>
                        </label>
                        <div class="flex items-center gap-3">
                            <input type="range" min="0" max="100" value="{{ $personalInstance->progress }}"
                                class="flex-1 h-1.5 bg-indigo-100 dark:bg-indigo-900/40 rounded-lg appearance-none cursor-pointer accent-indigo-600 border border-indigo-200 dark:border-indigo-700/50 shadow-inner"
                                oninput="document.getElementById('personal-progress-val').innerText = this.value"
                                onchange="updateTaskProgress(this.value, {{ $personalInstance->id }}, '{{ $personalInstance->status }}')">
                        </div>
                    </div>
                </div>
            @endif

            <!-- Global / Generic Actions -->
            @php
                $showGlobalActions = (!$task->is_template && ($task->assigned_user_id === auth()->id() || $team->isCoordinator(auth()->user()) || $task->created_by_id === auth()->id())) || 
                                      ($task->is_template && ($team->isCoordinator(auth()->user()) || $task->created_by_id === auth()->id()));
            @endphp

            @if ($showGlobalActions)
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 space-y-3 shadow-sm dark:shadow-none transition-colors">
                    <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-widest font-bold mb-1">
                        {{ $task->is_template ? __('Acciones del Plan Maestro') : __('tasks.actions') }}
                    </p>

                    @if ($task->status !== 'completed')
                        <button onclick="updateTaskStatus('completed', {{ $task->id }})"
                            class="w-full flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-500 text-white dark:text-white text-xs font-bold py-2.5 rounded-xl transition-all shadow-lg shadow-emerald-600/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ $task->is_template ? __('Cerrar Plan Maestro') : __('tasks.mark_complete') }}
                        </button>
                    @else
                        <button onclick="updateTaskStatus('pending', {{ $task->id }})"
                            class="w-full flex items-center justify-center gap-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-bold py-2.5 rounded-xl transition-all border border-gray-200 dark:border-gray-700">
                            {{ $task->is_template ? __('Reabrir Plan Maestro') : __('tasks.reopen_task') }}
                        </button>
                    @endif

                    @if ($task->status === 'blocked')
                        <button onclick="updateTaskStatus('in_progress', {{ $task->id }})"
                            class="w-full flex items-center justify-center gap-2 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-500/10 dark:hover:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs font-bold py-2.5 rounded-xl transition-all border border-emerald-200 dark:border-emerald-500/20 shadow-sm animate-pulse">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            {{ __('tasks.unblock_task') }}
                        </button>
                    @else
                        <button onclick="updateTaskStatus('blocked', {{ $task->id }})"
                            class="w-full flex items-center justify-center gap-2 bg-red-50 hover:bg-red-100 dark:bg-red-500/10 dark:hover:bg-red-500/20 text-red-600 dark:text-red-400 text-xs font-bold py-2.5 rounded-xl transition-all border border-red-200 dark:border-red-500/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            {{ __('tasks.report_blocker') }}
                        </button>
                    @endif

                    @if (!$personalInstance)
                        @php
                            $isAutomatic = $task->is_template || $task->children()->exists();
                        @endphp
                        <div class="pt-2 border-t border-gray-100 dark:border-gray-800 mt-2">
                            <label class="flex items-center justify-between text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-widest font-bold mb-3">
                                <span>{{ $isAutomatic ? (__('tasks.global_progress') ?? 'Progreso Global') : __('tasks.progress') }}</span>
                                <div class="flex items-center gap-1 min-w-[3rem] justify-end">
                                    <span id="global-progress-val-sidebar" class="text-violet-600 dark:text-violet-400 tabular-nums">{{ $task->progress }}</span>
                                    <span class="text-gray-400 text-[8px]">%</span>
                                </div>
                            </label>
                            <div class="flex items-center gap-3">
                                <input type="range" min="0" max="100" value="{{ $task->progress }}"
                                    class="flex-1 h-1.5 bg-gray-100 dark:bg-gray-800 rounded-lg appearance-none transition-none {{ $isAutomatic ? 'cursor-not-allowed opacity-60' : 'cursor-pointer accent-violet-600' }}"
                                    {{ $isAutomatic ? 'disabled' : '' }}
                                    oninput="document.getElementById('global-progress-val-sidebar').innerText = this.value"
                                    onchange="updateTaskProgress(this.value, {{ $task->id }}, '{{ $task->status }}')">

                                @if ($isAutomatic)
                                    <span class="text-[10px] text-gray-400 italic">({{ __('tasks.automatic') ?? 'Auto' }})</span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- My Tracking -->
            @php
                $trackingTask = $task->is_template ? $personalInstance : $task;
            @endphp

            @if ($trackingTask && !$task->is_template)
                <div x-data="{ 
                    taskId: {{ $trackingTask->id }},
                    get isActive() { return Alpine.store('timer').activeTaskId == this.taskId },
                    get elapsed() { return this.isActive ? Alpine.store('timer').elapsed : 0 },
                    formatTime(seconds) {
                        const h = Math.floor(seconds / 3600);
                        const m = Math.floor((seconds % 3600) / 60);
                        const s = seconds % 60;
                        return [h, m, s].map(v => v < 10 ? '0' + v : v).join(':');
                    }
                }"
                class="bg-white dark:bg-gray-900 border-2 transition-all duration-500 rounded-2xl p-5 shadow-sm overflow-hidden group"
                :class="isActive ? 'border-violet-500 shadow-violet-500/10' : 'border-gray-100 dark:border-gray-800'">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-widest font-black">
                            {{ __('Mi Seguimiento') }}
                        </p>
                        <div x-show="isActive" class="flex items-center gap-1.5" x-cloak>
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                            </span>
                            <span class="text-[9px] font-bold text-red-500 uppercase tracking-tighter animate-pulse">{{ __('En curso') }}</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-2xl font-black text-gray-900 dark:text-white tabular-nums tracking-tighter" x-text="isActive ? formatTime(elapsed) : '00:00:00'">
                            </p>
                            <p class="text-[10px] font-medium text-gray-400 dark:text-gray-500 mt-1">
                                {{ __('Total hoy') }}: <span class="font-bold text-gray-600 dark:text-gray-300">{{ $trackingTask->trackedTimeTodayHuman() }}</span>
                            </p>
                        </div>
                        
                        @include('tasks.partials.task-timer-button', ['task' => $trackingTask])
                    </div>

                    @php
                        $otherWorkersCount = count(array_filter($activeUserIds, fn($id) => $id !== auth()->id()));
                    @endphp
                    @if($otherWorkersCount > 0)
                        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800 flex items-center gap-3">
                            <div class="flex -space-x-2 overflow-hidden">
                                @foreach($activeUserIds as $oid)
                                    @if($oid !== auth()->id())
                                        @php $ou = \App\Models\User::find($oid); @endphp
                                        <div class="inline-block h-6 w-6 rounded-full ring-2 ring-white dark:ring-gray-900 bg-violet-100 dark:bg-violet-900/50 flex items-center justify-center text-[8px] font-bold text-violet-600 dark:text-violet-400" title="{{ $ou->name }} {{ __('está trabajando ahora') }}">
                                            {{ strtoupper(substr($ou->name, 0, 2)) }}
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                            <span class="text-[9px] font-black text-violet-500 uppercase tracking-widest animate-pulse">
                                {{ $otherWorkersCount }} {{ $otherWorkersCount == 1 ? __('MIEMBRO TRABAJANDO') : __('MIEMBROS TRABAJANDO') }}
                            </span>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Owner -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm dark:shadow-none transition-colors">
                <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-widest font-bold mb-3">
                    {{ __('tasks.owner') }}
                </p>
                <div class="flex items-center gap-3">
                    <div
                        class="w-8 h-8 rounded-full bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center text-[10px] font-bold text-white shadow-sm">
                        {{ strtoupper(substr($task->creator?->name ?? '?', 0, 2)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-gray-700 dark:text-gray-300 truncate">
                            {{ $task->creator?->name ?? '—' }}</p>
                        <p class="text-[10px] text-gray-500 dark:text-gray-600">{{ __('tasks.created_at') }}:
                            {{ $task->created_at->format('d M Y') }}</p>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 space-y-4 shadow-sm dark:shadow-none transition-colors">
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide font-bold">{{ __('tasks.status') }}</span>
                    <span
                        class="text-[11px] font-bold px-3 py-1 rounded-full border {{ $statusColor }} uppercase tracking-wider">
                        {{ __('tasks.statuses.' . $task->status) }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span
                        class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide font-bold">{{ __('tasks.quadrant') }}</span>
                    <span class="text-[11px] font-bold {{ $qCfg['color'] }} uppercase tracking-wider">
                        Q{{ $q }}: {{ __('tasks.quadrants.' . $q . '.label') }}
                    </span>
                </div>
                <div class="pt-1 border-t border-gray-100 dark:border-gray-800 mt-2">
                    <div class="{{ $qCfg['bg'] }} rounded-xl p-3 text-[11px]">
                        <p class="font-bold {{ $qCfg['color'] }} uppercase tracking-tighter">
                            {{ __('tasks.quadrants.' . $q . '.description') }}</p>
                        <p class="text-gray-500 dark:text-gray-400 mt-1.5 italic font-medium leading-relaxed">💡
                            {{ __('tasks.quadrants.' . $q . '.tip') }}</p>
                    </div>
                </div>
            </div>

            <!-- Priority / Urgency -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 space-y-3 shadow-sm dark:shadow-none transition-colors">
                @foreach ([['tasks.priority', $task->priority, 'tasks.priorities'], ['tasks.urgency', $task->urgency, 'tasks.urgencies']] as [$lbl, $val, $map])
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wide">{{ __($lbl) }}</span>
                        <span
                            class="text-xs font-semibold text-gray-800 dark:text-gray-200">{{ __($map . '.' . $val) }}</span>
                    </div>
                @endforeach

                <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-800">
                    <span
                        class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wide">{{ __('tasks.visibility') }}</span>
                    <div class="flex items-center gap-1.5">
                        <div
                            class="w-2 h-2 rounded-full {{ $task->visibility === 'public' ? 'bg-violet-500' : 'bg-amber-500' }}">
                        </div>
                        <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">
                            {{ $task->visibility === 'public' ? __('tasks.public') : __('tasks.private') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Dates -->
            @if ($task->due_date || $task->scheduled_date)
                <div
                    class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm dark:shadow-none transition-colors">
                    
                    @if ($task->scheduled_date)
                        <div class="flex items-center justify-between font-mono mb-3 pb-3 border-b border-gray-50 dark:border-gray-800/50">
                            <span class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wide font-sans">{{ __('tasks.scheduled_date') ?? 'Fecha de Inicio' }}</span>
                            <span class="text-[11px] text-gray-700 dark:text-gray-300 font-medium">{{ $task->scheduled_date->format('d M Y, H:i') }}</span>
                        </div>
                    @endif

                    @if ($task->due_date)
                        <div class="flex items-center justify-between font-mono pt-2">
                            <span class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wide font-sans">{{ __('tasks.due_date') }}</span>
                            @php
                                $isPast = now()->isAfter($task->due_date) && $task->status !== 'completed';
                                $isNear = now()->diffInDays($task->due_date, false) <= 2 && now()->diffInDays($task->due_date, false) >=0 && $task->status !== 'completed';
                                $dueBg = $isPast ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-900/50 text-red-700 dark:text-red-400' : 
                                        ($isNear ? 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-900/50 text-amber-700 dark:text-amber-400' : 
                                        'bg-gray-50 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200');
                            @endphp
                            <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border {{ $dueBg }} transition-colors shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-[11px] font-bold tracking-tight">{{ $task->due_date->format('d M Y, H:i') }}</span>
                                @if($isPast)
                                    <span class="text-[9px] font-black uppercase tracking-widest ml-1.5 border-l border-current pl-2 py-0.5 opacity-90">{{ __('tasks.overdue') }}</span>
                                @elseif($isNear)
                                    <span class="text-[9px] font-black uppercase tracking-widest ml-1.5 border-l border-current pl-2 py-0.5 opacity-90">{{ __('tasks.expires_soon') }}</span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Autoprogram Settings -->
            @if ($task->is_autoprogrammable)
                <div class="bg-white dark:bg-gray-900 border border-violet-100 dark:border-violet-900/30 rounded-2xl p-4 space-y-3 shadow-sm dark:shadow-none transition-colors border-l-4 border-l-violet-500 mb-4">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] text-violet-600 dark:text-violet-400 uppercase tracking-widest font-bold">
                            {{ __('tasks.autoprogram_active') ?? 'Autoprogramación JIT' }}
                        </p>
                        <div class="w-2 h-2 rounded-full bg-violet-500 animate-pulse"></div>
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between text-[11px]">
                            <span class="text-gray-400">{{ __('tasks.frequency') }}:</span>
                            <span class="font-bold text-gray-700 dark:text-gray-300">{{ __('tasks.' . ($task->autoprogram_settings['frequency'] ?? 'daily')) }} (x{{ $task->autoprogram_settings['interval'] ?? 1 }})</span>
                        </div>
                        <div class="flex justify-between text-[11px]">
                            <span class="text-gray-400">{{ __('tasks.lead_time') }}:</span>
                            <span class="font-bold text-gray-700 dark:text-gray-300">{{ $task->autoprogram_settings['lead_value'] ?? 7 }} {{ __('tasks.' . ($task->autoprogram_settings['lead_unit'] ?? 'days')) }}</span>
                        </div>
                        @if(isset($task->autoprogram_settings['next_occurrence_at']))
                        <div class="flex justify-between text-[11px] pt-1 border-t border-gray-50 dark:border-gray-800">
                            <span class="text-gray-400">{{ __('tasks.next_wakeup') ?? 'Próximo despertar' }}:</span>
                            <span class="text-violet-600 dark:text-violet-400 font-bold">
                                {{ \Carbon\Carbon::parse($task->autoprogram_settings['next_occurrence_at'])->format('d M Y') }}
                            </span>
                        </div>
                        @endif
                    </div>
                </div>
            @endif



            <!-- Forum Thread Widget -->
            @include('teams.forum.partials.thread-widget')

        </div>
    </div>

    @push('scripts')
        <script>
            function nudgeUser(taskId) {
                Swal.fire({
                    title: '{{ __('tasks.nudge_user') }}?',
                    text: '{{ __('tasks.nudge_confirm_text') }}',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#6366f1',
                    cancelButtonColor: '#94a3b8',
                    confirmButtonText: 'Sí, enviar',
                    cancelButtonText: 'Cancelar',
                    background: document.documentElement.classList.contains('dark') ? '#111827' : '#fff',
                    color: document.documentElement.classList.contains('dark') ? '#fff' : '#111827'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/teams/{{ $team->id }}/tasks/${taskId}/nudge`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                        'content')
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        title: '¡Enviado!',
                                        text: data.message,
                                        icon: 'success',
                                        timer: 2000,
                                        showConfirmButton: false,
                                        background: document.documentElement.classList.contains('dark') ?
                                            '#111827' : '#fff',
                                        color: document.documentElement.classList.contains('dark') ?
                                            '#fff' : '#111827'
                                    }).then(() => location.reload());
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                Swal.fire({
                                    title: 'Error',
                                    text: 'No se pudo enviar el recordatorio',
                                    icon: 'error',
                                    background: document.documentElement.classList.contains('dark') ?
                                        '#111827' : '#fff',
                                    color: document.documentElement.classList.contains('dark') ? '#fff' :
                                        '#111827'
                                });
                            });
                    }
                });
            }

            function reassignTask(taskId, userId) {
                if (!userId) return;
                
                const payloadValue = userId === 'unassign' ? null : userId;
                
                fetch(`/teams/{{ $team->id }}/tasks/${taskId}/move`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        assigned_user_id: payloadValue
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'bottom-end',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        });
                        Toast.fire({
                            icon: 'success',
                            title: 'Asignación actualizada'
                        }).then(() => location.reload());
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'No se ha podido cambiar la asignación.',
                        icon: 'error'
                    });
                });
            }

            function updateTaskStatus(status, taskId = {{ $task->id }}) {
                const messages = {
                    'completed': '¿Marcar como completada?',
                    'blocked': '¿Informar un bloqueo en esta tarea?',
                    'pending': '¿Reabrir esta tarea?',
                    'in_progress': '¿Quitar el bloqueo de esta tarea?'
                };

                Swal.fire({
                    title: messages[status] || '¿Cambiar estado?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: status === 'blocked' ? '#ef4444' : '#6366f1',
                    cancelButtonColor: '#94a3b8',
                    confirmButtonText: 'Confirmar',
                    cancelButtonText: 'Cancelar',
                    background: document.documentElement.classList.contains('dark') ? '#111827' : '#fff',
                    color: document.documentElement.classList.contains('dark') ? '#fff' : '#111827'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/teams/{{ $team->id }}/tasks/${taskId}/move`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                        'content')
                                },
                                body: JSON.stringify({
                                    status: status
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        title: '¡Actualizado!',
                                        text: 'El estado se ha actualizado correctamente.',
                                        icon: 'success',
                                        timer: 1500,
                                        showConfirmButton: false,
                                        background: document.documentElement.classList.contains('dark') ?
                                            '#111827' : '#fff',
                                        color: document.documentElement.classList.contains('dark') ?
                                            '#fff' : '#111827'
                                    }).then(() => {
                                        window.location.reload();
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                Swal.fire({
                                    title: 'Error',
                                    text: 'No se pudo actualizar el estado',
                                    icon: 'error',
                                    background: document.documentElement.classList.contains('dark') ?
                                        '#111827' : '#fff',
                                    color: document.documentElement.classList.contains('dark') ? '#fff' :
                                        '#111827'
                                });
                            });
                    }
                });
            }

            function updateTaskProgress(progress, taskId = {{ $task->id }}, currentStatus = '{{ $task->status }}') {

                fetch(`/teams/{{ $team->id }}/tasks/${taskId}/move`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content')
                        },
                        body: JSON.stringify({
                            progress_percentage: progress
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // If status has changed (e.g. from completed back to in_progress), reload
                            if (data.task_status !== currentStatus || progress == 100) {
                                window.location.reload();
                            } else {
                                // Subtle label update without animations that feel like glitches
                                const valSpan = document.getElementById('progress-val');
                                const gVal = document.getElementById('global-progress-val');
                                const gBar = document.getElementById('global-progress-bar');
                                const instBar = document.getElementById(`inst-progress-bar-${taskId}`);
                                const instVal = document.getElementById(`inst-progress-val-${taskId}`);

                                if (valSpan) valSpan.innerText = progress;
                                if (instBar) instBar.style.width = progress + '%';
                                if (instVal) instVal.innerText = progress + '%';

                                // Update global progress factors if we have them
                                if (data.parent_progress !== null) {
                                    if (gVal) gVal.innerText = Math.round(data.parent_progress) + '%';
                                    if (gBar) {
                                        gBar.style.transition = 'none';
                                        gBar.style.width = data.parent_progress + '%';
                                    }
                                }
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error',
                            text: 'No se pudo actualizar el progreso',
                            icon: 'error',
                            background: document.documentElement.classList.contains('dark') ?
                                '#111827' : '#fff',
                            color: document.documentElement.classList.contains('dark') ? '#fff' : '#111827'
                        });
                    });
            }

            function renameAttachment(id, currentName) {
                Swal.fire({
                    title: "{{ __('tasks.rename_attachment') }}",
                    input: 'text',
                    inputLabel: "{{ __('tasks.new_name') }}",
                    inputValue: currentName,
                    showCancelButton: true,
                    confirmButtonText: "{{ __('Save Changes') }}",
                    cancelButtonText: "{{ __('Cancel') }}",
                    inputValidator: (value) => {
                        if (!value) {
                            return '¡El nombre no puede estar vacío!'
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = `/teams/{{ $team->id }}/attachments/${id}`;
                        form.innerHTML = `
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="file_name" value="${result.value}">
                        `;
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            }

            function confirmAttachmentDelete(id) {
                Swal.fire({
                    title: "{{ __('tasks.delete_attachment_confirm') }}",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: '{{ __('Yes, delete user') }}'.replace('user', ''), // Reutilizando estilo
                    cancelButtonText: '{{ __('Cancel') }}'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById(`delete-attachment-${id}`).submit();
                    }
                });
            }

            function handleAttachmentUpload(input) {
                const file = input.files[0];
                if (!file) return;

                const limit = "{{ ini_get('upload_max_filesize') }}";
                const limitBytes = parsePHPSize(limit);

                if (file.size > limitBytes) {
                    Swal.fire({
                        title: '{{ __('Archivo demasiado grande') }}',
                        text: `El archivo excede el límite de ${limit} configurado en el servidor.`,
                        icon: 'error',
                        background: document.documentElement.classList.contains('dark') ? '#111827' : '#fff',
                        color: document.documentElement.classList.contains('dark') ? '#fff' : '#111827'
                    });
                    input.value = '';
                    return;
                }

                document.getElementById('attachment-form').submit();
            }

            function parsePHPSize(size) {
                const unit = size.slice(-1).toUpperCase();
                const value = parseFloat(size);
                switch (unit) {
                    case 'G': return value * 1024 * 1024 * 1024;
                    case 'M': return value * 1024 * 1024;
                    case 'K': return value * 1024;
                    default: return value;
                }
            }
        </script>


    @endpush

    @push('modals')
        <!-- Google Drive Picker Modal -->
        <div x-data="drivePicker()" 
             @open-drive-picker.window="openModal()" 
             x-show="isOpen" 
             class="fixed inset-0 z-[100] overflow-y-auto" 
             x-cloak>
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="isOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"></div>
                </div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="isOpen" 
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                     class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-200 dark:border-gray-700">
                    
                    <script>
                        window.openSientiaDrivePicker = function() {
                            console.log('Global trigger: openSientiaDrivePicker called');
                            window.dispatchEvent(new CustomEvent('open-drive-picker'));
                        };

                        function drivePicker() {
                            console.log('Drive Picker Initialized');
                            return {
                                isOpen: false,
                                loading: false,
                                files: [],
                                breadcrumbs: [],
                                currentFolderId: null,

                                openModal() {
                                    console.log('Opening Drive Modal');
                                    this.isOpen = true;
                                    this.loadFolder(null);
                                },

                                async loadFolder(folderId) {
                                    this.loading = true;
                                    try {
                                        const response = await fetch(`{{ route('google.drive.list') }}?folderId=${folderId || ''}&team_id={{ $team->id }}`);
                                        const data = await response.json();
                                        this.files = data.files || [];
                                        if (folderId === null) this.breadcrumbs = [];
                                    } catch (error) {
                                        console.error('Error loading Drive folder:', error);
                                    } finally {
                                        this.loading = false;
                                    }
                                },

                                handleAction(file) {
                                    if (file.mimeType === 'application/vnd.google-apps.folder') {
                                        this.loadFolder(file.id);
                                        this.breadcrumbs.push({ id: file.id, name: file.name });
                                    } else {
                                        this.attachFile(file);
                                    }
                                },

                                async attachFile(file) {
                                    this.loading = true;
                                    try {
                                        const response = await fetch('{{ route('teams.tasks.attachments.from-drive', [$team, $task]) }}', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                                'Accept': 'application/json'
                                            },
                                            body: JSON.stringify({
                                                file_id: file.id,
                                                file_name: file.name,
                                                web_view_link: file.webViewLink,
                                                file_size: file.size || 0,
                                                mime_type: file.mimeType
                                            })
                                        });

                                        const data = await response.json();
                                        if (data.success) window.location.reload();
                                        else alert('Error: ' + data.message);
                                    } catch (error) {
                                        console.error('Error attaching from Drive:', error);
                                    } finally {
                                        this.loading = false;
                                    }
                                }
                            }
                        }
                    </script>

                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-blue-50 dark:bg-blue-900/30 rounded-xl">
                                <svg class="w-6 h-6 text-blue-600" viewBox="0 0 48 48">
                                    <path fill="#FFC107" d="M17 6H11L2 22l3 5h6l9-16z"/>
                                    <path fill="#2196F3" d="M37 42H11l-9-15 4-7h26l9 16z"/>
                                    <path fill="#4CAF50" d="M15 6l9 16 9-16H15z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-white">Google Drive</h3>
                        </div>
                        <button @click="isOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="p-6">
                        <!-- Breadcrumbs -->
                        <div class="flex items-center gap-2 mb-4 text-xs font-medium text-gray-500 overflow-x-auto whitespace-nowrap pb-2">
                            <button @click="loadFolder(null)" class="hover:text-blue-600 transition-colors">Mi Unidad</button>
                            <template x-for="crumb in breadcrumbs">
                                <div class="flex items-center gap-2">
                                    <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    <button @click="loadFolder(crumb.id)" class="hover:text-blue-600 transition-colors" x-text="crumb.name"></button>
                                </div>
                            </template>
                        </div>

                        <div class="relative min-h-[300px] max-h-[400px] overflow-y-auto pr-2 scrollbar-thin scrollbar-thumb-gray-200 dark:scrollbar-thumb-gray-700">
                            <!-- Loading State -->
                            <div x-show="loading" class="absolute inset-0 bg-white/50 dark:bg-gray-800/50 flex items-center justify-center z-10">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                            </div>

                            <!-- Files List -->
                            <div class="grid grid-cols-1 gap-1">
                                <template x-for="file in files" :key="file.id">
                                    <div @click="handleAction(file)" 
                                         class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 group cursor-pointer border border-transparent hover:border-blue-100 dark:hover:border-blue-900/50 transition-all">
                                        <div class="flex items-center gap-3 truncate">
                                            <img :src="file.iconLink" class="w-5 h-5 opacity-70 group-hover:opacity-100" />
                                            <div class="truncate">
                                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200 truncate" x-text="file.name"></p>
                                                <p class="text-[10px] text-gray-400" x-text="file.mimeType.includes('folder') ? 'Carpeta' : 'Archivo de Drive'"></p>
                                            </div>
                                        </div>
                                        <div class="shrink-0 flex items-center gap-2">
                                            <svg x-show="file.mimeType.includes('folder')" class="w-4 h-4 text-gray-300 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            <button x-show="!file.mimeType.includes('folder')" 
                                                    class="text-[10px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 px-2.5 py-1 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity">
                                                Seleccionar
                                            </button>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="files.length === 0 && !loading">
                                    <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                                        <svg class="w-12 h-12 mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                                        <p class="text-sm font-medium">Esta carpeta está vacía</p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700 text-right">
                        <button @click="isOpen = false" class="px-5 py-2 text-sm font-bold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white transition-colors">
                            Cancelar
                        </button>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    <!-- Attachment History Modal -->
    <div id="attachment-history-modal" class="hidden fixed inset-0 z-[110] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-950/80 backdrop-blur-sm transition-opacity" onclick="closeAttachmentHistory()"></div>
            <div class="relative bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-2xl w-full overflow-hidden border border-gray-200 dark:border-gray-800">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gray-50 dark:bg-gray-900/50">
                    <div>
                        <h3 class="text-lg font-black text-gray-900 dark:text-white heading uppercase tracking-tight">Historial del Archivo</h3>
                        <p id="history-filename" class="text-xs text-gray-500 dark:text-gray-400 font-medium truncate max-w-sm"></p>
                    </div>
                    <button onclick="closeAttachmentHistory()" class="text-gray-400 hover:text-gray-500 p-2">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
                <div class="px-6 py-6 max-h-[60vh] overflow-y-auto" id="history-content">
                    <!-- Logs will be injected here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        function showAttachmentHistory(id) {
            const attachments = @json($allAttachments);
            const attachment = attachments.find(a => a.id == id);
            
            if (!attachment) return;

            document.getElementById('history-filename').innerText = attachment.file_name;
            const content = document.getElementById('history-content');
            content.innerHTML = '';

            if (attachment.logs && attachment.logs.length > 0) {
                const logs = attachment.logs.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                
                let html = '<div class="space-y-6 relative ml-4 border-l-2 border-gray-100 dark:border-gray-800 pl-8">';
                
                logs.forEach(log => {
                    const date = new Date(log.created_at).toLocaleString();
                    const actionColors = {
                        'upload': 'bg-emerald-500',
                        'download': 'bg-blue-500',
                        'view': 'bg-violet-500',
                        'rename': 'bg-amber-500',
                        'move_to_drive': 'bg-indigo-500',
                        'delete': 'bg-red-500'
                    };
                    
                    const actionIcons = {
                        'upload': '<path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v8" />',
                        'download': '<path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />',
                        'view': '<path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />',
                        'rename': '<path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />',
                        'move_to_drive': '<path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" /><path d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />'
                    };

                    const actionLabel = {
                        'upload': 'Subida de archivo',
                        'download': 'Descarga realizada',
                        'view': 'Visualización online',
                        'rename': 'Cambio de nombre',
                        'move_to_drive': 'Traspaso a Google Drive',
                        'delete': 'Eliminación'
                    };

                    let metaHtml = '';
                    if (log.metadata) {
                        if (log.metadata.original_name) metaHtml = `<p class="mt-1 text-gray-400">Origen: <span class="font-bold text-gray-600 dark:text-gray-300 italic">${log.metadata.original_name}</span></p>`;
                        if (log.metadata.old_name) metaHtml = `<p class="mt-1 text-gray-400">De <span class="line-through">${log.metadata.old_name}</span> a <span class="font-bold text-gray-600 dark:text-gray-300">${log.metadata.new_name}</span></p>`;
                    }

                    html += `
                        <div class="relative">
                            <div class="absolute -left-[45px] top-1 w-8 h-8 rounded-full border-4 border-white dark:border-gray-900 ${actionColors[log.action] || 'bg-gray-400'} flex items-center justify-center text-white shadow-sm ring-4 ring-gray-100 dark:ring-gray-800/30">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    ${actionIcons[log.action] || '<circle cx="12" cy="12" r="10" />'}
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-tight">${actionLabel[log.action]}</span>
                                    <span class="text-[10px] bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 px-2 py-0.5 rounded-full font-bold tabular-nums">${date}</span>
                                </div>
                                <div class="flex items-center gap-2 group">
                                    <div class="w-5 h-5 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-[9px] font-black text-gray-500 uppercase">${(log.user?.name || '?').substring(0, 2)}</div>
                                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-tighter">${log.user?.name || 'Sistema'}</span>
                                    ${log.ip_address ? `<span class="text-[9px] text-gray-400 font-mono bg-gray-50 dark:bg-gray-800/50 px-1.5 py-0.5 rounded">IP: ${log.ip_address}</span>` : ''}
                                </div>
                                ${metaHtml}
                            </div>
                        </div>
                    `;
                });
                
                html += '</div>';
                content.innerHTML = html;
            } else {
                content.innerHTML = '<div class="text-center py-10"><p class="text-gray-500 italic">No hay movimientos registrados para este archivo todavía.</p></div>';
            }

            document.getElementById('attachment-history-modal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeAttachmentHistory() {
            document.getElementById('attachment-history-modal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Close on ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeAttachmentHistory();
        });

        function copyTaskJson() {
            const btn = event.currentTarget;
            
            btn.disabled = true;
            btn.style.opacity = '0.5';

            fetch("{{ route('teams.tasks.export-json', [$team, $task]) }}", {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => r.json())
            .then(data => {
                const jsonStr = JSON.stringify(data, null, 4);
                navigator.clipboard.writeText(jsonStr).then(() => {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Copiado!',
                        text: 'El JSON de la tarea está en tu portapapeles.',
                        timer: 2000,
                        showConfirmButton: false,
                        background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                        color: document.documentElement.classList.contains('dark') ? '#f1f5f9' : '#1e293b',
                    });
                });
            })
            .catch(e => {
                console.error(e);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo obtener el JSON de la tarea.'
                });
            })
            .finally(() => {
                btn.disabled = false;
                btn.style.opacity = '1';
            });
        }
    </script>
        @include('tasks.partials.import-modal-script')
    @endpush
</x-app-layout>
