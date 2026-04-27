<x-app-layout>
    @section('title', $task->title)    @php
        // 1. Identify the personal execution instance
        $isAssigned = $task->assigned_user_id === auth()->id() || $task->assignedTo->contains(auth()->id());

        $personalInstance = null;
        if ($task->is_template) {
            $personalInstance = $task->instances()
                ->where('assigned_user_id', auth()->id())
                ->first();
        } elseif (!$task->is_template) {
            // Root task or child of something that is not a template
            // If I am assigned or it's a collaborative root task
            if ($isAssigned || (!$task->parent_id && !$task->isInstance())) {
                $personalInstance = $task;
            }
        }

        // Get private notes
        $privateNote = \App\Models\TaskPrivateNote::where('task_id', $task->id)
            ->where('user_id', auth()->id())
            ->first();
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
                    </div>

                    <!-- Imprimir Ficha -->
                    <button type="button" onclick="printFullTask()" class="w-full flex items-center gap-4 py-4 px-5 text-start hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150 ease-in-out group">
                        <div class="shrink-0 p-2 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 rounded-xl group-hover:scale-110 transition-transform shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                        </div>
                        <div class="flex flex-col">
                            <span class="font-bold text-gray-900 dark:text-white text-sm">Imprimir Ficha Técnica</span>
                            <span class="text-[10px] text-gray-500 font-medium tracking-normal mt-0.5">Informe completo y detallado de la tarea</span>
                        </div>
                    </button>

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
        $userObj = auth()->user();
        $isUserObjMgr = $team->isManager($userObj);
        $taskIds = $task->children()->getQuery()->visibleTo($userObj, $isUserObjMgr)->pluck('tasks.id')->push($task->id);
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
                        <div class="flex items-center gap-2">
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
                            <button onclick="printSection('Descripción', 'description-content')" 
                                    class="p-1.5 bg-gray-50 dark:bg-gray-800 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-xl transition-all border border-transparent hover:border-indigo-100 dark:hover:border-indigo-800 shadow-sm flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest"
                                    title="Imprimir descripción">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                Imprimir
                            </button>
                        </div>
                    </div>
                    <div id="description-content"
                        class="text-sm text-gray-700 dark:text-gray-300 prose dark:prose-invert max-w-none prose-sm leading-relaxed">
                        {!! str($displayDescription)->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
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
                            <button onclick="printSection('Observaciones', 'observations-content')" 
                                    class="p-1.5 bg-gray-50 dark:bg-gray-800 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-xl transition-all border border-transparent hover:border-indigo-100 dark:hover:border-indigo-800 shadow-sm flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest"
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
                        {!! str($displayObservations)->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                    </div>
                </div>
            @endif

            @if ($task->is_template || $task->children()->exists() || $task->assignedTo->isNotEmpty())
                @php
                    $isRoadmap = $task->is_template || $task->children()->exists();
                    $currentUser = auth()->user();
                    $isUserMgr = $team->isManager($currentUser);

                    if ($isRoadmap) {
                        $instancesQuery = $task->is_template ? $task->instances() : $task->children();
                        $withRelation = $task->is_template ? 'assignedUser' : 'assignedTo';
                        
                        $instances = $instancesQuery->getQuery()
                            ->visibleTo($currentUser, $isUserMgr)
                            ->with($withRelation)
                            ->get()
                            ->sortBy(function($inst) use ($task) {
                                if ($task->is_template) {
                                    return mb_strtolower(($inst->assignedUser?->name ?? '') . ' ' . $inst->title);
                                }
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
                                    @if(!$isRoadmap && $totalInst > 1)
                                        <span class="text-[9px] font-bold text-violet-500/70 border border-violet-200 dark:border-violet-800 rounded-lg px-2 py-0.5 bg-violet-50/50 dark:bg-violet-900/10 ml-1 animate-fade-in uppercase tracking-wider">
                                            {{ __('tasks.collaborative_hint') }}
                                        </span>
                                    @endif
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
                            selectedMembers: [],
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
                            },
                            toggleAll() {
                                const checkboxes = Array.from(document.querySelectorAll('.member-checkbox:not(:disabled)'));
                                if (this.selectedMembers.length === checkboxes.length) {
                                    this.selectedMembers = [];
                                } else {
                                    this.selectedMembers = checkboxes.map(c => c.value);
                                }
                            }
                        }">
                        <!-- Bulk Actions Bar -->
                        <div x-show="selectedMembers.length > 0" 
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 -translate-y-4"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="m-4 p-3 bg-violet-50 dark:bg-violet-900/20 border border-violet-100 dark:border-violet-800/50 rounded-2xl flex items-center justify-between shadow-sm sticky top-2 z-20">
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-black text-violet-700 dark:text-violet-400 uppercase tracking-widest">
                                    <span x-text="selectedMembers.length"></span> {{ __('seleccionados') }}
                                </span>
                            </div>
                            <button @click="nudgeUser(selectedMembers)" 
                                    class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-[10px] font-black uppercase tracking-widest rounded-xl transition-all shadow-md flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                {{ __('Recordatorio Masivo') }}
                            </button>
                        </div>

                        <table class="w-full text-left text-sm">
                            <thead
                                class="sticky top-0 z-10 bg-gray-50/95 dark:bg-gray-800/95 backdrop-blur-sm text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    @if($team->isCoordinator(auth()->user()))
                                    <th class="px-4 py-3 w-10">
                                        <input type="checkbox" 
                                               @click="toggleAll()" 
                                               :checked="selectedMembers.length > 0 && selectedMembers.length === document.querySelectorAll('.member-checkbox:not(:disabled)').length"
                                               class="rounded border-gray-300 dark:border-gray-700 text-violet-600 focus:ring-violet-500 bg-white dark:bg-gray-900 cursor-pointer">
                                    </th>
                                    @endif
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
                                        @if(!$isSimulated) onclick="if(!event.target.closest('button, select, a, input')) window.location='{{ route('teams.tasks.show', [$team->id, $inst->id]) }}'" @endif>
                                        
                                        @if($team->isCoordinator(auth()->user()))
                                        <td class="px-4 py-4" onclick="event.stopPropagation()">
                                            <input type="checkbox" 
                                                   value="{{ $isSimulated ? $task->id : $inst->id }}" 
                                                   x-model="selectedMembers" 
                                                   class="member-checkbox rounded border-gray-300 dark:border-gray-700 text-violet-600 focus:ring-violet-500 bg-white dark:bg-gray-900 cursor-pointer"
                                                   {{ $inst->status === 'completed' ? 'disabled' : '' }}>
                                        </td>
                                        @endif

                                        <td class="px-4 py-4 group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors" onclick="event.stopPropagation()">
                                            <div class="flex items-center gap-4">
                                                    <div class="relative">
                                                        <img src="{{ $instMember ? $instMember->profile_photo_url : 'https://ui-avatars.com/api/?name=?&color=7F9CF5&background=EBF4FF' }}" 
                                                            alt="{{ $instMemberName }}"
                                                            class="w-10 h-10 rounded-2xl object-cover shadow-inner border border-white dark:border-gray-800 {{ $isInstActive ? 'ring-2 ring-red-500 ring-offset-2 dark:ring-offset-gray-900 animate-pulse' : '' }}">
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
                                                <button onclick="event.stopPropagation(); nudgeUser('{{ $isSimulated ? $task->id : $inst->id }}')"
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

            @if($personalInstance && ($personalInstance->assigned_user_id === auth()->id() || $personalInstance->assignedTo->contains(auth()->id())))
                <div class="bg-white dark:bg-gray-900 border border-amber-100 dark:border-amber-900/30 rounded-2xl p-5 shadow-sm mt-5">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/40 flex items-center justify-center text-amber-600 border border-amber-100 dark:border-amber-800/50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </div>
                            <h3 class="text-xs font-black text-amber-700 dark:text-amber-400 uppercase tracking-widest">
                                {{ __('tasks.private_notes') }}
                            </h3>
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="printPrivateNotes()" class="p-1.5 bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300 rounded-xl transition-all border border-amber-100 dark:border-amber-800/50 shadow-sm flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest" title="Imprimir notas privadas">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                Imprimir
                            </button>
                        </div>
                    </div>
                    
                    <form action="{{ route('teams.tasks.private-notes.update', [$team, $personalInstance]) }}" method="POST" id="private-notes-form">
                        @csrf
                        <x-markdown-editor 
                            name="content" 
                            id="reply-content-private"
                            :value="old('content', $personalInstance->currentPrivateNote?->content)"
                            :label="null"
                            rows="6"
                            placeholder="Escribe aquí tus notas personales sobre esta tarea... Nadie más podrá verlas."
                        />
                        <div class="mt-3 flex justify-end">
                            <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-xl shadow-lg shadow-amber-500/20 transition-all active:scale-95">
                                {{ __('tasks.save_notes') }}
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            <script>
                function printSection(sectionLabel, contentId) {
                    const content = document.getElementById(contentId).innerHTML;
                    const taskTitle = @json($task->title);
                    
                    const printWin = window.open('', '_blank', 'width=800,height=900');
                        printWin.document.write(`
                            <html>
                                <head>
                                    <title>Imprimir ${sectionLabel} - ${taskTitle}</title>
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
                                            <span class="brand">Sientia MTX &bull; ${sectionLabel}</span>
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

                    function printPrivateNotes() {
                        // Get current editor content
                        const editor = document.getElementById('reply-content-private');
                        let rawContent = editor ? editor.value : '';
                        
                        // If there is no EasyMDE instance yet, or we want to render the current markdown:
                        // In SientiaMTX, we use a custom markdown renderer or a simple replacement for printing
                        // But since we want it to look nice, we'll use a hidden div to render it if possible, 
                        // or just send the raw text if it's simpler. 
                        // However, EasyMDE has a .markdown() method but we don't have direct access here easily.
                        
                        // Better: If the user is printing "Private Notes", they likely want to print what they SEE.
                        // But since it's an editor, let's just use the current value and wrap it in a pre or simple renderer.
                        
                        // Actually, I'll use a temporary hidden div to render markdown using the same logic as the backend if possible, 
                        // or just use a simple converter. 
                        
                        // For now, let's just print it. If it's markdown, it might look a bit raw.
                        // I'll add a simple marked.js-like logic or just a fallback.
                        
                        const taskTitle = @json($task->title);
                        const printWin = window.open('', '_blank', 'width=800,height=900');
                        
                        // Basic markdown to HTML for the print view (very simple fallback)
                        let htmlContent = rawContent
                            .replace(/^### (.*$)/gim, '<h3>$1</h3>')
                            .replace(/^## (.*$)/gim, '<h2>$1</h2>')
                            .replace(/^# (.*$)/gim, '<h1>$1</h1>')
                            .replace(/^\> (.*$)/gim, '<blockquote>$1</blockquote>')
                            .replace(/\*\*(.*)\*\*/gim, '<strong>$1</strong>')
                            .replace(/\*(.*)\*/gim, '<em>$1</em>')
                            .replace(/\n/gim, '<br>');

                        printWin.document.write(`
                            <html>
                                <head>
                                    <title>Mis Notas Privadas - ${taskTitle}</title>
                                    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
                                    <style>
                                        body { font-family: 'Inter', sans-serif; padding: 50px; color: #1e293b; line-height: 1.6; }
                                        .header { border-bottom: 4px solid #d97706; margin-bottom: 40px; padding-bottom: 20px; }
                                        .brand { font-weight: 900; font-size: 10px; text-transform: uppercase; letter-spacing: 0.3em; color: #d97706; }
                                        .title { font-size: 28px; font-weight: 900; color: #0f172a; margin: 5px 0; }
                                        .content { font-size: 15px; white-space: pre-wrap; }
                                        @media print { body { padding: 0; } }
                                    </style>
                                </head>
                                <body>
                                    <div class="header">
                                        <span class="brand">Sientia MTX &bull; Notas Privadas (Confidencial)</span>
                                        <h1 class="title">${taskTitle}</h1>
                                        <div style="font-size: 10px; color: #94a3b8; font-weight: 700; margin-top: 10px;">GENERADO EL ${new Date().toLocaleDateString()} - SOLO PARA USO PERSONAL</div>
                                    </div>
                                    <div class="content">${htmlContent}</div>
                                    <script>window.onload = function() { window.print(); setTimeout(() => window.close(), 500); };<\/script>
                                </body>
                            </html>
                        `);
                        printWin.document.close();
                    }

                    function printFullTask() {
                        const taskTitle = @json($task->title);
                        const taskId = @json($task->id);
                        const taskUuid = @json($task->uuid);
                        const status = @json(__('tasks.statuses.' . $task->status));
                        const progress = @json($task->progress_percentage);
                        const priorityLabel = @json(__('tasks.priorities.' . $task->priority));
                        const urgencyLabel = @json(__('tasks.urgencies.' . $task->urgency));
                        const scheduled = @json($task->scheduled_date?->format('d/m/y H:i') ?? '—');
                        const due = @json($task->due_date?->format('d/m/y H:i') ?? '—');
                        const teamName = @json($team->name);
                        const creator = @json($task->creator?->name ?? '—');
                        
                        const description = document.getElementById('description-content')?.innerHTML ?? '—';
                        const observations = document.getElementById('observations-content')?.innerHTML ?? '—';
                        
                        const members = @json($task->assignedTo->pluck('name')->toArray());
                        const skills = @json($task->skills->map(fn($s) => $s->name)->toArray());

                        const printWin = window.open('', '_blank', 'width=950,height=1100');
                        printWin.document.write(`
                            <!DOCTYPE html>
                            <html lang="es">
                                <head>
                                    <meta charset="UTF-8">
                                    <title>Ficha Técnica - ${taskTitle}</title>
                                    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
                                    <style>
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
                                        .task-uuid { font-family: 'JetBrains Mono', monospace; font-size: 6.5px; color: #cbd5e1; }

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
                                            display: grid; grid-template-cols: 1fr 180px; gap: 20px;
                                        }

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
                                            margin-top: 15px; display: flex; gap: 40px;
                                        }
                                        .signature-box { flex: 1; border-top: 1px solid #f1f5f9; padding-top: 4px; min-height: 30px; }
                                        .signature-label { font-size: 7px; font-weight: 700; color: #cbd5e1; text-transform: uppercase; }

                                        .footer { 
                                            margin-top: 15px; padding-top: 8px; border-top: 1px solid #f1f5f9;
                                            display: flex; justify-content: space-between;
                                            font-size: 6.5px; font-weight: 600; color: #cbd5e1; text-transform: uppercase;
                                        }
                                        
                                        @media print {
                                            html, body { height: auto; }
                                            .sheet { border: none; height: auto; min-height: 0; }
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
                                                <div class="task-uuid">ID: ${taskUuid.toUpperCase()}</div>
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
                </script>






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
                            <button type="button" @click="$dispatch('open-drive-picker', { id: {{ $task->id }}, type: 'App\\Models\\Task' })"
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
                                $isTaskType = $attachment->attachable_type === 'App\Models\Task';
                                $isFromParent = $isTaskType && $attachment->attachable_id === $task->parent_id;
                                $isFromChild = $isTaskType && $attachment->attachable_id !== $task->id && $attachment->attachable_id !== $task->parent_id;
                            @endphp
                            <div
                                class="group flex items-center justify-between p-3 {{ $isFromParent ? 'bg-indigo-50/30 dark:bg-indigo-900/10 border-indigo-100/50' : 'bg-gray-50 dark:bg-gray-800/50 border-gray-100 dark:border-gray-700/50' }} border rounded-xl hover:border-violet-200 dark:hover:border-violet-800 transition-all">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div
                                        class="w-10 h-10 rounded-lg flex items-center justify-center shadow-sm border shrink-0 {{ $attachment->storage_provider === 'google' ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-100 dark:border-blue-800' : ($isFromParent ? 'bg-indigo-50 dark:bg-gray-800 text-indigo-500 border-gray-100 dark:border-gray-700' : 'bg-white dark:bg-gray-800 text-violet-600 dark:text-violet-400 border-gray-100 dark:border-gray-700') }}">
                                        @if(!$attachment->exists)
                                            <div class="text-red-500/50">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                            </div>
                                        @elseif($attachment->storage_provider === 'google')
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
                                            @if(!$attachment->exists)
                                                <span class="text-gray-400 line-through decoration-red-500/30">{{ $attachment->file_name }}</span>
                                            @elseif($attachment->storage_provider === 'google' && $attachment->web_view_link)
                                                <a href="{{ $attachment->web_view_link }}" 
                                                   target="_blank" 
                                                   class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors flex items-center gap-1">
                                                    {{ $attachment->file_name }}
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                </a>
                                            @else
                                                <a href="{{ route('teams.attachments.download', [$team, $attachment]) }}" 
                                                   target="_blank" 
                                                   class="hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                                    {{ $attachment->file_name }}
                                                </a>
                                            @endif
                                        </p>
                                        <p class="text-[10px] text-gray-400 flex items-center gap-1.5">
                                            @if(!$attachment->exists)
                                                <span class="text-red-500/70 font-bold uppercase tracking-tighter">{{ __('Archivo Purgado') }}</span>
                                            @elseif($attachment->storage_provider === 'google')
                                                <span class="bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-1.5 rounded font-black uppercase text-[8px]">Google Drive</span>
                                            @else
                                                {{ number_format($attachment->file_size / 1024 / 1024, 2) }} MB
                                            @endif
                                            •
                                            @if($isFromParent) 
                                                <span class="text-indigo-500 font-bold uppercase tracking-tighter">{{ __('tasks.shared') ?? 'Plan' }}</span>
                                            @elseif($isFromChild)
                                                <span class="text-amber-500 font-bold uppercase tracking-tighter">{{ $attachment->attachable->assignedUser?->name ?? 'Equipo' }}</span>
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
                                        title="{{ __('tasks.history') ?? 'Ver histórico' }}">
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
                                    @if($isTaskType && $attachment->attachable_id === $task->id)
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
        </div>

        <!-- Sidebar -->
        <div class="space-y-4">



            <!-- 1. Plan Maestro Related (Only if template/child) -->
            @if ($task->is_template)
                <div class="bg-violet-50/30 dark:bg-violet-900/10 border border-violet-100 dark:border-violet-900/30 rounded-2xl p-4 shadow-sm space-y-4">
                    <p class="text-[10px] font-bold text-violet-600 dark:text-violet-400 uppercase tracking-widest">{{ __('ACCIONES DEL PLAN MAESTRO') }}</p>
                    
                    <div class="space-y-2">
                        @if ($task->status !== 'completed')
                            <button onclick="updateTaskStatus('completed')"
                                class="w-full flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold py-3 rounded-xl transition-all shadow-md shadow-emerald-600/20">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ __('Cerrar Plan Maestro') }}
                            </button>
                        @endif

                        @if ($task->status !== 'blocked')
                            <button onclick="updateTaskStatus('blocked')"
                                class="w-full flex items-center justify-center gap-2 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-xs font-bold py-3 rounded-xl transition-all border border-red-100 dark:border-red-900/30">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                {{ __('Informar bloqueo') }}
                            </button>
                        @endif
                    </div>

                    <div class="pt-2 border-t border-violet-100 dark:border-violet-900/20">
                        <div class="flex items-center justify-between text-[9px] font-black uppercase tracking-widest text-violet-400 mb-1">
                            <span>{{ __('tasks.roadmap_progress') }}</span>
                            <span>{{ $task->progress }}%</span>
                        </div>
                        <div class="w-full h-1 bg-violet-100 dark:bg-violet-900/30 rounded-full overflow-hidden">
                            <div class="h-full bg-violet-500 transition-all duration-1000" style="width: {{ $task->progress }}%"></div>
                        </div>
                    </div>
                </div>
            @elseif ($task->isInstance())
                <div class="bg-indigo-50/50 dark:bg-indigo-500/5 border border-indigo-100 dark:border-indigo-500/10 rounded-2xl p-4 space-y-3 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white dark:bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0 shadow-sm border border-indigo-50 dark:border-indigo-500/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-black text-indigo-700 dark:text-indigo-400 uppercase tracking-widest">{{ __('Plan Maestro Relacionado') }}</p>
                            @if ($team->isCoordinator(auth()->user()))
                                <div class="mt-1">
                                    <select onchange="reassignTask({{ $task->id }}, this.value)" class="w-full text-[10px] bg-white dark:bg-indigo-900 border border-indigo-100 dark:border-indigo-800 rounded-lg px-2 py-1 shadow-sm font-bold text-indigo-700 dark:text-indigo-300 cursor-pointer">
                                        <option value="" disabled {{ !$task->assigned_user_id ? 'selected' : '' }}>{{ __('Reasignar a...') }}</option>
                                        <option value="unassign">-- {{ __('Pendiente') }} --</option>
                                        @foreach($team->members()->orderBy('name')->get() as $member)
                                            <option value="{{ $member->id }}" {{ $task->assigned_user_id === $member->id ? 'selected' : '' }}>{{ $member->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @else
                                <p class="text-[11px] font-bold text-indigo-900 dark:text-indigo-200 truncate">{{ $task->assignedUser?->name ?? __('Sin asignar') }}</p>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('teams.tasks.show', [$team, $task->parent_id]) }}" class="block w-full text-center text-[10px] font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-300 hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-500 py-2 bg-white dark:bg-indigo-500/10 rounded-xl border border-indigo-100 dark:border-indigo-500/20 transition-all">
                        {{ __('VER PLAN MAESTRO') }}
                    </a>
                </div>
            @endif

            <!-- 2. TU EJECUCIÓN Card -->
            @if ($personalInstance)
                <div class="bg-indigo-50/40 dark:bg-indigo-900/10 border border-indigo-100/50 dark:border-indigo-800/50 rounded-2xl p-5 space-y-5 shadow-sm transition-colors relative overflow-hidden">
                    <p class="text-[10px] text-indigo-600 dark:text-indigo-400 uppercase tracking-widest font-black flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        {{ __('TU EJECUCIÓN') }}
                    </p>

                    <div class="space-y-2.5">
                        @if ($personalInstance->status !== 'completed')
                            <button onclick="updateTaskStatus('completed', {{ $personalInstance->id }})"
                                class="w-full flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-3.5 rounded-xl transition-all shadow-md shadow-indigo-600/20 active:scale-[0.98]">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ __('Marcar como completada') }}
                            </button>
                        @else
                            <button onclick="updateTaskStatus('pending', {{ $personalInstance->id }})"
                                class="w-full flex items-center justify-center gap-2 bg-white dark:bg-gray-800 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/50 text-xs font-bold py-3.5 rounded-xl transition-all border border-indigo-200 dark:border-indigo-700 shadow-sm active:scale-[0.98]">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                {{ __('Reabrir tarea') }}
                            </button>
                        @endif

                        @if ($personalInstance->status !== 'blocked')
                            <button onclick="updateTaskStatus('blocked', {{ $personalInstance->id }})"
                                class="w-full flex items-center justify-center gap-2 bg-red-50/80 hover:bg-red-100 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-xs font-bold py-3.5 rounded-xl transition-all border border-red-100/50 dark:border-red-900/30 active:scale-[0.98]">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                {{ __('Informar un bloqueo') }}
                            </button>
                        @endif
                    </div>

                    <div class="relative pt-4 border-t border-indigo-100/30 dark:border-indigo-800/30">
                        <label class="flex items-center justify-between text-[9px] text-indigo-400/80 dark:text-indigo-500/50 uppercase tracking-widest font-black mb-3">
                            <span>{{ __('TU PROGRESO') }}</span>
                            <div class="flex items-center gap-1 min-w-[3rem] justify-end font-bold">
                                <span id="personal-progress-val" class="text-indigo-600 dark:text-indigo-400 tabular-nums text-sm">{{ $personalInstance->progress_percentage }}</span>
                                <span class="text-indigo-400 text-[10px]">%</span>
                            </div>
                        </label>
                        <div class="flex items-center gap-3">
                            <input type="range" min="0" max="100" value="{{ $personalInstance->progress_percentage }}"
                                class="flex-1 h-1 bg-indigo-100 dark:bg-indigo-900/50 rounded-full appearance-none cursor-pointer accent-indigo-600"
                                oninput="document.getElementById('personal-progress-val').innerText = this.value"
                                onchange="updateTaskProgress(this.value, {{ $personalInstance->id }}, '{{ $personalInstance->status }}')">
                        </div>
                    </div>
                </div>
            @endif

            <!-- 3. TIEMPO DEDICADO Card -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm transition-colors">
                <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-widest font-black mb-4">{{ __('TIEMPO DEDICADO') }}</p>
                <div class="flex items-center justify-between">
                    <div x-data="{ 
                        active: {{ auth()->user()->isTrackingTask($personalInstance->id ?? $task->id) ? 'true' : 'false' }},
                        seconds: {{ auth()->user()->getTaskTrackingSeconds($personalInstance->id ?? $task->id) }},
                        totalToday: '{{ $task->totalTrackedTimeTodayHuman() }}',
                        
                        get formatted() {
                            const h = Math.floor(this.seconds / 3600);
                            const m = Math.floor((this.seconds % 3600) / 60);
                            const s = this.seconds % 60;
                            return [h,m,s].map(v => v.toString().padStart(2, '0')).join(':');
                        },
                        init() {
                            if (this.active) {
                                setInterval(() => { this.seconds++ }, 1000);
                            }
                        }
                    }" class="flex-1">
                        <div class="text-3xl font-black text-gray-900 dark:text-white tabular-nums tracking-tight mb-0.5" x-text="formatted">00:00:00</div>
                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wide">
                            Total hoy: <span class="text-gray-600 dark:text-gray-300" x-text="totalToday">0m</span>
                        </div>
                    </div>
                    
                    <div class="shrink-0">
                        @include('tasks.partials.task-timer-button', ['task' => $personalInstance ?? $task])
                    </div>
                </div>
            </div>

            <!-- 5. Propietario Card -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm dark:shadow-none">
                <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-widest font-bold mb-3">
                    {{ __('tasks.owner') }}
                </p>
                <div class="flex items-center gap-3">
                    <img src="{{ $task->creator ? $task->creator->profile_photo_url : 'https://ui-avatars.com/api/?name=?&color=7F9CF5&background=EBF4FF' }}" 
                        alt="{{ $task->creator?->name ?? '?' }}"
                        class="w-10 h-10 rounded-xl object-cover shadow-sm border border-gray-100 dark:border-gray-800 shrink-0">
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-gray-700 dark:text-gray-300 truncate">{{ $task->creator?->name ?? '—' }}</p>
                        <p class="text-[10px] text-gray-500 dark:text-gray-600 uppercase font-black tracking-tighter">{{ $task->created_at->format('d M Y') }}</p>
                    </div>
                </div>
            </div>

            <!-- 6. Estado Card -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 space-y-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide font-bold">{{ __('tasks.status') }}</span>
                    <span class="text-[11px] font-bold px-3 py-1 rounded-full border {{ $statusColor }} uppercase tracking-wider">
                        {{ __('tasks.statuses.' . $task->status) }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide font-bold">{{ __('tasks.quadrant') }}</span>
                    <span class="text-[11px] font-bold {{ $qCfg['color'] }} uppercase tracking-wider">
                        Q{{ $q }}: {{ __('tasks.quadrants.' . $q . '.label') }}
                    </span>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-800">
                    <span class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wide">{{ __('tasks.visibility') }}</span>
                    <div class="flex items-center gap-1.5">
                        <div class="w-2 h-2 rounded-full {{ $task->visibility === 'public' ? 'bg-violet-500' : 'bg-amber-500' }}"></div>
                        <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">
                            {{ $task->visibility === 'public' ? __('tasks.public') : __('tasks.private') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- 7. Prioridad Card -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 space-y-3 shadow-sm">
                @foreach ([['tasks.priority', $task->priority, 'tasks.priorities'], ['tasks.urgency', $task->urgency, 'tasks.urgencies']] as [$lbl, $val, $map])
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wide">{{ __($lbl) }}</span>
                        <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">{{ __($map . '.' . $val) }}</span>
                    </div>
                @endforeach
            </div>

            <!-- 8. Fechas Card -->
            @if ($task->due_date || $task->scheduled_date)
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 space-y-3 shadow-sm">
                    @if ($task->scheduled_date)
                        <div class="flex items-center justify-between pb-3 border-b border-gray-50 dark:border-gray-800/50">
                            <span class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wide">{{ __('tasks.scheduled_date') ?? 'Fecha de Inicio' }}</span>
                            <span class="text-[11px] text-gray-700 dark:text-gray-300 font-bold tabular-nums">{{ $task->scheduled_date->format('d M Y, H:i') }}</span>
                        </div>
                    @endif
                    @if ($task->due_date)
                        <div class="flex items-center justify-between pt-1">
                            <span class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wide">{{ __('tasks.due_date') }}</span>
                            <span class="text-[11px] text-gray-700 dark:text-gray-300 font-bold tabular-nums">{{ $task->due_date->format('d M Y, H:i') }}</span>
                        </div>
                    @endif
                </div>
            @endif

            <!-- 9. Autoprogramación Card -->
            @if ($task->is_autoprogrammable)
                <div class="bg-white dark:bg-gray-900 border border-violet-100 dark:border-violet-900/30 rounded-2xl p-4 space-y-3 shadow-sm border-l-4 border-l-violet-500">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] text-violet-600 dark:text-violet-400 uppercase tracking-widest font-bold">{{ __('tasks.autoprogram_active') ?? 'Autoprogramación JIT' }}</p>
                        <div class="w-2 h-2 rounded-full bg-violet-500 animate-pulse"></div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between text-[11px]">
                            <span class="text-gray-400 font-medium">{{ __('tasks.frequency') }}:</span>
                            <span class="font-bold text-gray-700 dark:text-gray-300">{{ __('tasks.' . ($task->autoprogram_settings['frequency'] ?? 'daily')) }} (x{{ $task->autoprogram_settings['interval'] ?? 1 }})</span>
                        </div>
                        @if(isset($task->autoprogram_settings['next_occurrence_at']))
                            <div class="flex justify-between text-[11px] pt-1 border-t border-gray-50 dark:border-gray-800">
                                <span class="text-gray-400 font-medium">{{ __('tasks.next_wakeup') ?? 'Próximo despertar' }}:</span>
                                <span class="text-violet-600 dark:text-violet-400 font-black">{{ \Carbon\Carbon::parse($task->autoprogram_settings['next_occurrence_at'])->format('d M Y') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- 10. Quota de disco Card -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ __('tasks.disk_quota') }}</h3>
                    <span class="text-[10px] text-gray-400 font-black tabular-nums">{{ number_format(auth()->user()->disk_used / 1024 / 1024, 1) }}MB / {{ number_format(auth()->user()->disk_quota / 1024 / 1024, 0) }}MB</span>
                </div>
                @php
                    $perc = auth()->user()->disk_quota > 0 ? (auth()->user()->disk_used / auth()->user()->disk_quota) * 100 : 0;
                    $barColor = $perc > 90 ? 'bg-red-500' : ($perc > 70 ? 'bg-amber-500' : 'bg-blue-500');
                @endphp
                <div class="w-full h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                    <div class="h-full {{ $barColor }} transition-all duration-1000 shadow-sm" style="width: {{ $perc }}%"></div>
                </div>
            </div>


            <!-- 11. Etiquetas (Capacidades) -->
            @php $taskSkills = $task->skills; @endphp
            @if($taskSkills->isNotEmpty())
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach($taskSkills as $skill)
                        <div class="group inline-flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-gray-900 border border-amber-100 dark:border-amber-900/40 rounded-xl shadow-sm hover:shadow-md hover:border-amber-300 dark:hover:border-amber-700 transition-all duration-300 cursor-default">
                            <div class="w-1.5 h-1.5 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 shadow-sm shadow-amber-500/20 group-hover:scale-125 transition-transform"></div>
                            <div class="flex flex-col min-w-0">
                                <span class="text-[9px] font-black text-amber-700 dark:text-amber-400 uppercase tracking-widest truncate leading-tight">{{ $skill->name }}</span>
                                <span class="text-[7px] text-amber-600/40 dark:text-amber-500/20 font-bold uppercase tracking-tighter truncate leading-none">{{ $skill->category }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- 12. Historial Card -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm">
                <div class="bg-gray-50/50 dark:bg-gray-800/50 px-4 py-3 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ __('tasks.activity_history') ?? 'HISTORIAL DE CAMBIOS' }}</h3>
                </div>
                <div class="divide-y divide-gray-50 dark:divide-gray-800/50 max-h-80 overflow-y-auto custom-scrollbar">
                    @forelse (($task->histories?->sortByDesc('created_at') ?? collect())->take(15) as $log)
                        <div class="flex items-center justify-between px-4 py-3 hover:bg-gray-50/30 dark:hover:bg-gray-800/20 transition-colors group">
                            <div class="flex items-center gap-3">
                                <img src="{{ $log->user ? $log->user->profile_photo_url : 'https://ui-avatars.com/api/?name=S&color=7c3aed&background=f5f3ff' }}" 
                                    alt="{{ $log->user?->name ?? 'System' }}"
                                    class="w-7 h-7 rounded-lg object-cover shadow-sm border border-gray-100 dark:border-gray-700">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $log->user?->name ?? 'Sistema' }}</span>
                                    <span class="text-[8px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 border border-indigo-100/50 dark:border-indigo-800/50">
                                        {{ $log->action_label ?? 'UPDATED' }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-[10px] text-gray-400 dark:text-gray-500 font-medium tabular-nums">{{ $log->created_at->format('d/m H:i') }}</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-gray-300 dark:text-gray-600 group-hover:text-gray-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center">
                            <p class="text-xs text-gray-400 italic">{{ __('tasks.no_history') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- 10. Discusion en el foro Card -->
            @include('teams.forum.partials.thread-widget')
        </div>
    </div>

    @push('scripts')
        <script>
            function nudgeUser(taskIds) {
                const isBulk = Array.isArray(taskIds);
                const ids = isBulk ? taskIds : [taskIds];
                
                Swal.fire({
                    title: isBulk ? '¿Enviar recordatorio masivo?' : '¿Enviar recordatorio?',
                    html: `
                        <p class="text-sm text-gray-500 mb-4">${isBulk ? 'Se enviará un recordatorio a todos los miembros seleccionados.' : 'Se enviará un recordatorio al miembro responsable.'}</p>
                        <textarea id="nudge-message" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-sm focus:ring-violet-500 min-h-[100px] p-3 shadow-inner" placeholder="Escribe un mensaje personalizado del coordinador (opcional)..."></textarea>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#7c3aed',
                    cancelButtonColor: '#94a3b8',
                    confirmButtonText: 'Enviar',
                    cancelButtonText: 'Cancelar',
                    background: document.documentElement.classList.contains('dark') ? '#111827' : '#fff',
                    color: document.documentElement.classList.contains('dark') ? '#fff' : '#111827',
                    preConfirm: () => {
                        return document.getElementById('nudge-message').value;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const customMessage = result.value;
                        const url = isBulk ? `/teams/{{ $team->id }}/tasks/bulk-nudge` : `/teams/{{ $team->id }}/tasks/${taskIds}/nudge`;
                        const payload = isBulk ? { task_ids: ids, custom_message: customMessage } : { custom_message: customMessage };

                        fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify(payload)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    title: '¡Listo!',
                                    text: data.message,
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false,
                                    background: document.documentElement.classList.contains('dark') ? '#111827' : '#fff',
                                    color: document.documentElement.classList.contains('dark') ? '#fff' : '#111827'
                                }).then(() => {
                                    if (isBulk) {
                                        window.location.reload();
                                    }
                                });
                            } else {
                                Swal.fire('Error', data.message || 'No se pudo enviar el recordatorio.', 'error');
                            }
                        })
                        .catch(error => {
                            Swal.fire('Error', 'Ocurrió un error en la conexión.', 'error');
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

            async function handleAttachmentUpload(input) {
                const file = input.files[0];
                if (!file) return;

                const limit = "{{ ini_get('upload_max_filesize') }}";
                const limitBytes = parsePHPSize(limit);

                // 1. Check PHP upload limit
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

                // 2. Check team quota BEFORE uploading
                try {
                    const res = await fetch('{{ route("teams.quota-status", $team) }}', {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (res.ok) {
                        const quota = await res.json();
                        if (file.size > quota.available_bytes) {
                            const usedMB = (quota.disk_used / 1024 / 1024).toFixed(1);
                            const totalMB = (quota.disk_quota / 1024 / 1024).toFixed(1);
                            Swal.fire({
                                title: '⚠️ Almacenamiento lleno',
                                html: `El equipo ha alcanzado su límite de almacenamiento.<br><small style="opacity:.7">${usedMB} MB / ${totalMB} MB usados</small><br><br>Un coordinador debe liberar espacio antes de poder subir más archivos.`,
                                icon: 'warning',
                                background: document.documentElement.classList.contains('dark') ? '#111827' : '#fff',
                                color: document.documentElement.classList.contains('dark') ? '#fff' : '#111827',
                                confirmButtonColor: '#7c3aed'
                            });
                            input.value = '';
                            return;
                        }
                    }
                } catch (e) {
                    // If quota check fails, let the server handle it
                    console.warn('Quota pre-check failed, proceeding with upload.', e);
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
        <x-google-drive-picker :team="$team" />
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
                                    <img src="${log.user ? (log.user.profile_photo_path ? '/storage/' + log.user.profile_photo_path : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(log.user.name) + '&color=7F9CF5&background=EBF4FF') : 'https://ui-avatars.com/api/?name=?&color=7F9CF5&background=EBF4FF'}" 
                                        class="w-5 h-5 rounded-full object-cover shadow-sm" alt="${log.user?.name || '?'}">
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

{{-- ============================================================
     BARRA FLOTANTE DE ACCIONES RÁPIDAS
     ============================================================ --}}
<div id="task-floating-bar"
     style="
        position: fixed;
        bottom: 1.5rem;
        left: 50%;
        transform: translateX(-50%) translateY(1rem);
        z-index: 800;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.625rem 1rem;
        background: rgba(255,255,255,0.93);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid #e5e7eb;
        border-radius: 1rem;
        box-shadow: 0 20px 40px -8px rgba(0,0,0,0.15);
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.25s ease, transform 0.25s ease;
        white-space: nowrap;
     "
     class="dark:[background:rgba(17,24,39,0.93)] dark:[border-color:#374151]">

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
        {{ $task->title }}
    </span>

    @can('update', $task)
        <div style="width:1px;height:1.25rem;background:#e5e7eb;flex-shrink:0"></div>
        <a href="{{ route('teams.tasks.edit', [$team, $task]) }}"
           style="display:flex;align-items:center;gap:0.375rem;font-size:0.75rem;font-weight:700;color:#fff;background:#7c3aed;padding:0.375rem 0.75rem;border-radius:0.625rem;text-decoration:none;transition:background 0.15s ease;"
           onmouseover="this.style.background='#6d28d9'"
           onmouseout="this.style.background='#7c3aed'">
            <svg style="width:1rem;height:1rem;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            <span>{{ __('tasks.edit') }}</span>
        </a>
    @endcan
</div>

<script>
    function savePrivateNotes() {
        const content = document.getElementById('private-notes-area').value;
        const button = event.currentTarget;
        const originalText = button.innerHTML;
        
        button.disabled = true;
        button.innerHTML = '<svg class="animate-spin h-3 w-3 mr-2 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> GUARDANDO...';

        fetch("{{ route('teams.tasks.private-notes.update', [$team, $task]) }}", {
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
        const bar = document.getElementById('task-floating-bar');
        let visible = false;

        function updateBar(scrollY) {
            const shouldShow = scrollY > 150;
            if (shouldShow === visible) return;
            visible = shouldShow;
            if (visible) {
                bar.style.opacity = '1';
                bar.style.transform = 'translateX(-50%) translateY(0)';
                bar.style.pointerEvents = 'auto';
            } else {
                bar.style.opacity = '0';
                bar.style.transform = 'translateX(-50%) translateY(1rem)';
                bar.style.pointerEvents = 'none';
            }
        }

        // Catch scroll on any container
        const checkScroll = (e) => {
            const target = e.target === document ? document.documentElement : e.target;
            const scrollY = target.scrollTop || window.scrollY || 0;
            updateBar(scrollY);
        };

        window.addEventListener('scroll', checkScroll, { passive: true, capture: true });
    })();
</script>
</x-app-layout>
