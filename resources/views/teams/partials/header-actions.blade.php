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
    <div class="flex items-center gap-2 flex-wrap">
        @if($shouldShowCreateTask)
            <a href="{{ route('teams.tasks.create', $team) }}"
                class="flex items-center gap-1.5 text-xs bg-violet-600 hover:bg-violet-500 text-white px-4 py-2.5 rounded-xl transition-all font-bold shadow-lg shadow-violet-500/20 active:scale-95">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span class="hidden lg:inline">{{ __('tasks.create') }}</span>
            </a>
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

        @if($isGoogleConnected)
            <a href="{{ route('google.sync', ['team_id' => $team->id]) }}"
                class="flex items-center gap-1.5 text-xs bg-emerald-50 hover:bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/40 dark:text-emerald-400 px-4 py-2.5 rounded-xl transition-all font-bold active:scale-95 border border-emerald-100 dark:border-emerald-800/50"
                title="Sincronizar con Google Tasks/Calendar">
                <svg class="h-4 w-4" viewBox="0 0 48 48">
                    <path fill="#FFC107" d="M17 6H11L2 22l3 5h6l9-16z"/><path fill="#2196F3" d="M37 42H11l-9-15 4-7h26l9 16z"/><path fill="#4CAF50" d="M15 6l9 16 9-16H15z"/>
                </svg>
                <span class="hidden xl:inline">Sincronizar Google</span>
            </a>
        @endif

        @if($team->isCoordinator(auth()->user()) || auth()->user()->is_admin)
            <button type="button" @click="importTaskJson()" 
                class="flex items-center gap-1.5 text-xs bg-indigo-50 hover:bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/40 dark:text-indigo-400 px-4 py-2.5 rounded-xl transition-all font-bold active:scale-95 border border-indigo-100 dark:border-indigo-800/50"
                title="Importar tarea desde archivo JSON">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                <span class="hidden xl:inline">Importar JSON</span>
            </button>
            <input type="file" id="jsonTaskInput" class="hidden" accept=".json" @change="handleTaskJsonUpload($event)">

            <script>
                function importTaskJson() {
                    document.getElementById('jsonTaskInput').click();
                }

                function handleTaskJsonUpload(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    const formData = new FormData();
                    formData.append('file', file);
                    formData.append('_token', '{{ csrf_token() }}');

                    const url = "{{ route('teams.tasks.import-json', $team) }}";
                    
                    Swal.fire({
                        title: 'Importando tarea...',
                        text: 'Por favor, espera un momento.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch(url, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Éxito!',
                                text: data.message,
                                confirmButtonText: 'Ver tarea'
                            }).then(() => {
                                window.location.href = data.url;
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de servidor',
                            text: 'Hubo un fallo al procesar la subida.'
                        });
                    })
                    .finally(() => {
                        event.target.value = ''; // Reset input
                    });
                }
            </script>
        @endif
    </div>
</div>
