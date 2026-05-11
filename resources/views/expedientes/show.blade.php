<x-app-layout>
    @section('title', $expediente->code . ' — ' . $team->name)

    <x-slot name="header">
        <div class="flex flex-col lg:flex-row justify-between items-start gap-4">
            <div class="flex items-start gap-4">
                <a href="{{ route('teams.expedientes.index', $team) }}"
                    class="mt-1 p-2.5 bg-gray-50 dark:bg-gray-800/50 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-2xl transition-all shadow-sm border border-gray-100 dark:border-gray-700/50 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <span class="text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-md">
                            {{ $expediente->code }}
                        </span>
                        <span class="px-2 py-0.5 text-xs font-bold rounded-md bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400 capitalize">
                            {{ $expediente->status }}
                        </span>
                        @if($expediente->visibility === 'private')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-black uppercase tracking-widest rounded-md bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-800/50">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                Privado
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-black uppercase tracking-widest rounded-md bg-slate-50 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Público
                            </span>
                        @endif
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white heading tracking-tight">
                        {{ $expediente->title }}
                    </h1>
                    
                    @php
                        $totalTasks = $expediente->tasks->count();
                        $completedTasks = $expediente->tasks->where('status', 'completed')->count();
                        $avgProgress = $totalTasks > 0 ? round($expediente->tasks->avg('progress_percentage')) : 0;
                    @endphp

                    <div class="flex flex-wrap items-center gap-x-6 gap-y-2 mt-3 text-xs font-bold text-gray-500">
                        <div class="flex items-center gap-2">
                            <div class="w-32 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden shadow-inner">
                                <div class="h-full bg-gradient-to-r from-violet-500 to-violet-500 transition-all duration-1000" style="width: {{ $avgProgress }}%"></div>
                            </div>
                            <span class="text-violet-600 dark:text-violet-400 font-black text-sm">{{ $avgProgress }}%</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span class="text-gray-600 dark:text-gray-300">{{ $completedTasks }} completadas</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-violet-500"></span>
                            <span class="text-gray-600 dark:text-gray-300">{{ $totalTasks - $completedTasks }} pendientes</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- View Switcher Sub-Header -->
        <div class="mt-8 mb-4 flex w-full">
            @include('teams.partials.view-switcher')
        </div>

        <!-- Expediente Actions Footer Row -->
        <div class="flex items-center gap-2 flex-wrap shrink-0 mt-4 border-t border-gray-100 dark:border-gray-800 pt-6">
            <a href="{{ route('teams.expedientes.edit', [$team, $expediente]) }}"
                class="shrink-0 flex items-center gap-1.5 text-xs bg-white dark:bg-white/5 border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300 px-4 py-2.5 rounded-xl transition-all font-bold hover:bg-gray-50 dark:hover:bg-white/10 active:scale-95 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                {{ __('Editar') }}
            </a>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Card: Details -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm">
                <h3 class="text-xs font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" /></svg>
                    Descripción del Expediente
                </h3>
                <div class="text-gray-800 dark:text-gray-200 text-sm leading-relaxed whitespace-pre-wrap">
                    {{ $expediente->description ?: 'No hay descripción detallada para este expediente.' }}
                </div>
            </div>

            <!-- Card: Expedientes Relacionados (Inline Management) -->
            <div x-data="{ showLinkRelated: false }" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5">
                    <h3 class="text-sm font-black text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                        Expedientes Relacionados ({{ $expediente->relatedExpedientes->count() }})
                    </h3>
                    <button @click="showLinkRelated = !showLinkRelated" type="button"
                        class="inline-flex items-center gap-1.5 text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-1.5 rounded-xl transition-all border border-gray-200 dark:border-gray-700 w-full sm:w-auto justify-center shadow-sm active:scale-95">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Vincular Expedientes
                    </button>
                </div>

                <!-- Drawer: Managing relationships -->
                <div x-show="showLinkRelated" x-collapse x-cloak class="mb-6 p-4 bg-violet-50/50 dark:bg-violet-900/20 border border-violet-100 dark:border-violet-900/30 rounded-2xl">
                    <h4 class="text-xs font-black text-violet-700 dark:text-violet-300 uppercase tracking-widest mb-3">Gestionar Vínculos Cruzados</h4>
                    <form action="{{ route('teams.expedientes.link-related', [$team, $expediente]) }}" method="POST">
                        @csrf
                        <div class="space-y-3">
                            <div>
                                <select name="related_ids[]" id="related-selector" multiple class="w-full">
                                    @foreach($allTeamExpedientes as $availExp)
                                        <option value="{{ $availExp->id }}" {{ $expediente->relatedExpedientes->contains($availExp->id) ? 'selected' : '' }}>
                                            {{ $availExp->code }} — {{ $availExp->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button @click="showLinkRelated = false" type="button" class="text-xs font-bold text-gray-500 px-3 py-1.5 hover:text-gray-700">Cancelar</button>
                                <button type="submit" class="text-xs font-black uppercase bg-violet-600 text-white px-4 py-1.5 rounded-lg shadow-sm hover:bg-violet-700 transition-all">Actualizar Relaciones</button>
                            </div>
                        </div>
                    </form>
                </div>

                @if($expediente->relatedExpedientes->isEmpty())
                    <div class="flex flex-col items-center justify-center py-6 border border-dashed border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-gray-800/30">
                        <p class="text-xs text-gray-400 italic">No hay expedientes vinculados a esta carpeta.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($expediente->relatedExpedientes as $rel)
                            <a href="{{ route('teams.expedientes.show', [$team, $rel]) }}" 
                                class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-800 hover:border-violet-200 dark:hover:border-violet-900/50 hover:bg-white dark:hover:bg-gray-800 hover:shadow-sm rounded-2xl transition-all group">
                                <div class="w-8 h-8 bg-white dark:bg-gray-900 rounded-xl flex items-center justify-center shrink-0 text-violet-500 font-black text-[9px] shadow-sm border border-gray-100 dark:border-gray-700">EXP</div>
                                <div class="min-w-0 flex-1">
                                    <div class="text-[9px] font-black text-violet-600 dark:text-violet-400 tracking-wider">{{ $rel->code }}</div>
                                    <div class="text-xs font-bold text-gray-700 dark:text-gray-200 truncate group-hover:text-gray-900 dark:group-hover:text-white">{{ $rel->title }}</div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Card: Tasks placeholder -->
            <div x-data="{ showLinkBox: false }" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-6">
                    <h3 class="text-sm font-black text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        Tareas Vinculadas ({{ $expediente->tasks->count() }})
                    </h3>
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <button @click="showLinkBox = !showLinkBox" type="button"
                            class="flex-1 sm:flex-none inline-flex justify-center items-center gap-1.5 text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-1.5 rounded-xl transition-all border border-gray-200 dark:border-gray-700">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.172 13.828a4 4 0 015.656 0l4 4a4 4 0 01-5.656 5.656l-1.102-1.101" /></svg>
                            Vincular
                        </button>
                        <a href="{{ route('teams.tasks.create', [$team, 'expediente_id' => $expediente->id]) }}" 
                            class="flex-1 sm:flex-none inline-flex justify-center items-center gap-1.5 text-xs font-black uppercase tracking-widest text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-900/30 hover:bg-violet-100 dark:hover:bg-violet-900/50 px-3 py-1.5 rounded-xl transition-all border border-violet-100 dark:border-violet-500/20">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            Nueva
                        </a>
                    </div>
                </div>

                <!-- Drawer for linking tasks -->
                <div x-show="showLinkBox" x-collapse x-cloak class="mb-6 p-4 bg-violet-50/50 dark:bg-violet-900/20 border border-violet-100 dark:border-violet-900/30 rounded-2xl">
                    <h4 class="text-xs font-black text-violet-700 dark:text-violet-300 uppercase tracking-widest mb-3">Vincular Tareas Existentes</h4>
                    <form action="{{ route('teams.expedientes.link-tasks', [$team, $expediente]) }}" method="POST">
                        @csrf
                        <div class="space-y-3">
                            <div>
                                <select name="task_ids[]" id="task-selector" multiple placeholder="Busca y selecciona tareas para vincular..." class="text-sm">
                                    @foreach($availableTasks as $availTask)
                                        <option value="{{ $availTask->id }}">
                                            [{{ $availTask->id }}] {{ $availTask->title }} 
                                            @if($availTask->expediente) (Cambiar de {{ $availTask->expediente->code }}) @endif
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-[10px] text-gray-500 mt-1">Muestra tareas del equipo que no pertenecen a este expediente. Si ya tienen uno, se moverán a este.</p>
                            </div>
                            <div class="flex justify-end gap-2">
                                <button @click="showLinkBox = false" type="button" class="text-xs font-bold text-gray-500 px-3 py-1.5 hover:text-gray-700">Cancelar</button>
                                <button type="submit" class="text-xs font-black uppercase bg-violet-600 text-white px-4 py-1.5 rounded-lg shadow-sm hover:bg-violet-700 transition-all">Vincular Seleccionadas</button>
                            </div>
                        </div>
                    </form>
                </div>
                
                @if($expediente->tasks->isEmpty())
                    <div class="flex flex-col items-center justify-center py-10 bg-gray-50 dark:bg-gray-800/50 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700 text-center">
                        <div class="w-12 h-12 rounded-full bg-violet-50 dark:bg-violet-900/20 flex items-center justify-center text-violet-400 mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                        </div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Aún no hay tareas en este expediente.</p>
                        <p class="text-[11px] text-gray-400 mt-1 mb-4">Empieza a organizarte creando tu primera tarea vinculada.</p>
                        <a href="{{ route('teams.tasks.create', [$team, 'expediente_id' => $expediente->id]) }}" class="text-xs font-bold text-white bg-violet-600 hover:bg-violet-700 px-4 py-2 rounded-xl transition-all shadow-md">
                            Crear Primera Tarea
                        </a>
                    </div>
                @else
                    <div class="space-y-2">
                        @foreach($expediente->tasks as $task)
                            @php
                                $statusClasses = [
                                    'completed'   => 'bg-emerald-50 border-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400',
                                    'in_progress' => 'bg-blue-50 border-blue-100 text-blue-700 dark:bg-blue-500/10 dark:border-blue-500/20 dark:text-blue-400',
                                    'blocked'     => 'bg-red-50 border-red-100 text-red-700 dark:bg-red-500/10 dark:border-red-500/20 dark:text-red-400',
                                    'default'     => 'bg-gray-50 border-gray-100 text-gray-600 dark:bg-gray-500/10 dark:border-gray-500/20 dark:text-gray-400'
                                ];
                                $badgeClass = $statusClasses[$task->status] ?? $statusClasses['default'];
                            @endphp
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 bg-gray-50 hover:bg-white dark:bg-gray-800/50 dark:hover:bg-gray-800 rounded-2xl border border-gray-100 hover:border-violet-200 dark:border-gray-800 dark:hover:border-violet-900/50 transition-all group shadow-sm hover:shadow-md">
                                
                                <a href="{{ route('teams.tasks.show', [$team, $task]) }}" class="flex flex-col sm:flex-row sm:items-center justify-between flex-1 gap-3 min-w-0">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div @class([
                                            'w-8 h-8 rounded-xl flex items-center justify-center shrink-0 border',
                                            'bg-emerald-100 border-emerald-200 text-emerald-600' => $task->status === 'completed',
                                            'bg-white border-gray-200 text-gray-400 group-hover:border-violet-300 group-hover:text-violet-500 dark:bg-gray-900 dark:border-gray-700' => $task->status !== 'completed',
                                        ])>
                                            @if($task->status === 'completed')
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                            @else
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                            @endif
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <h4 class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors truncate">
                                                {{ $task->title }}
                                            </h4>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="text-[10px] px-1.5 py-0.5 rounded-md font-bold border {{ $badgeClass }} uppercase tracking-wider">
                                                    {{ __("tasks.statuses.{$task->status}") }}
                                                </span>
                                                @if($task->due_date)
                                                    <span class="text-[10px] text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                        {{ $task->due_date->diffForHumans() }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between sm:justify-end gap-4 shrink-0 border-t sm:border-none border-gray-100 dark:border-gray-800 pt-2 sm:pt-0">
                                        @if($task->assignedUser)
                                            <div class="flex items-center gap-2" title="Asignado a {{ $task->assignedUser->name }}">
                                                <img src="{{ $task->assignedUser->profile_photo_url }}" class="w-6 h-6 rounded-full border border-white dark:border-gray-700 shadow-sm">
                                                <span class="text-[11px] font-medium text-gray-600 dark:text-gray-300 hidden md:block">{{ explode(' ', $task->assignedUser->name)[0] }}</span>
                                            </div>
                                        @endif
                                        
                                        <div class="w-12 text-right">
                                            <span class="text-xs font-black tabular-nums text-gray-400 dark:text-gray-500 group-hover:text-violet-600 dark:group-hover:text-violet-400">{{ $task->progress_percentage }}%</span>
                                        </div>
                                    </div>
                                </a>

                                {{-- Divider and Detach Action --}}
                                <div class="flex items-center pl-2 border-t sm:border-t-0 sm:border-l border-gray-100 dark:border-gray-700/50 shrink-0 justify-end sm:justify-start">
                                    @if($task->parent_id && !$task->is_template)
                                        <div class="p-2 text-gray-300 dark:text-gray-600 cursor-help" title="Heredado del Plan Maestro 🔒">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        </div>
                                    @else
                                        <form id="unlink-form-{{ $task->id }}" action="{{ route('teams.expedientes.unlink-task', [$team, $expediente, $task]) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="button" onclick="confirmUnlinkTask({{ $task->id }})" class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-xl transition-all active:scale-90" title="Desvincular Tarea">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Card: Dossier Attachments -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-sm font-black text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                        Adjuntos del Expediente ({{ $expediente->attachments->count() }})
                    </h3>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="$dispatch('open-drive-picker', { id: {{ $expediente->id }}, type: 'App\\Models\\Expediente' })"
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-100 dark:hover:bg-blue-900/50 px-3 py-1.5 rounded-xl transition-all border border-blue-100 dark:border-blue-500/20 shadow-sm active:scale-95">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8.71 4.94L5.33 10.8L8.69 16.64L12.08 10.78L8.71 4.94Z" fill="#0066DA"/><path d="M21.16 16.64H14.44L11.07 22.48H17.78L21.16 16.64Z" fill="#00A668"/><path d="M15.32 4.94L12.08 10.78L15.44 16.64H22.16L18.69 4.94H15.32Z" fill="#FFD04C"/><path d="M15.32 4.94L8.71 4.94L5.33 10.8L12.08 22.48L15.44 16.64L15.32 4.94Z" fill="#00832D"/></svg>
                            Vincular Drive
                        </button>
                        <button type="button" onclick="document.getElementById('exp-attachment-input').click()" 
                            class="inline-flex items-center gap-1.5 text-xs font-black uppercase tracking-widest text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-900/30 hover:bg-violet-100 dark:hover:bg-violet-900/50 px-3 py-1.5 rounded-xl transition-all border border-violet-100 dark:border-violet-500/20 shadow-sm active:scale-95">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            Subir
                        </button>
                        <form id="exp-attachment-form" action="{{ route('teams.expedientes.attachments.upload', [$team, $expediente]) }}" method="POST" enctype="multipart/form-data" class="hidden">
                            @csrf
                            <input type="file" id="exp-attachment-input" name="file" onchange="this.form.submit();">
                        </form>
                    </div>
                </div>

                @if($expediente->attachments->isEmpty())
                    <div class="flex flex-col items-center justify-center py-6 bg-gray-50 dark:bg-gray-800/50 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700 text-center">
                        <p class="text-xs text-gray-400 italic">No hay archivos adjuntos directamente a este expediente.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($expediente->attachments as $attachment)
                            <div class="group flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-800 rounded-2xl hover:border-violet-200 dark:hover:border-violet-900/50 transition-all">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div @class([
                                        'w-10 h-10 rounded-xl flex items-center justify-center border shrink-0 shadow-sm',
                                        'bg-blue-50 dark:bg-blue-900/20 border-blue-100 dark:border-blue-800 text-blue-600' => $attachment->storage_provider === 'google',
                                        'bg-white dark:bg-gray-900 border-gray-100 dark:border-gray-700 text-violet-500' => $attachment->storage_provider !== 'google',
                                    ])>
                                        @if($attachment->storage_provider === 'google')
                                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-6.92 12.29c-.28.43-.67.77-1.15.97-.47.2-1 .29-1.56.29-.55 0-1.09-.09-1.56-.29-.47-.2-.86-.54-1.15-.97-.28-.44-.43-.96-.43-1.55 0-.58.15-1.1.43-1.53.29-.43.67-.76 1.15-.96.47-.2 1.01-.29 1.56-.29.56 0 1.09.1 1.56.29.47.2.86.53 1.15.96.28.43.43.95.43 1.53.01.59-.14 1.11-.43 1.55z"/></svg>
                                        @else
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <a href="{{ $attachment->storage_provider === 'google' ? $attachment->web_view_link : route('teams.attachments.download', [$team, $attachment]) }}" target="_blank" class="text-xs font-bold text-gray-800 dark:text-white hover:text-violet-600 dark:hover:text-violet-400 transition-colors truncate block" title="{{ $attachment->file_name }}">
                                            {{ $attachment->file_name }}
                                        </a>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            @if($attachment->storage_provider === 'google')
                                                <span class="bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-1.5 rounded font-black uppercase text-[8px]">Google Drive</span>
                                            @else
                                                <span class="text-[10px] text-gray-400 font-medium block truncate">
                                                    {{ number_format($attachment->file_size / 1024 / 1024, 2) }} MB
                                                </span>
                                            @endif
                                            <span class="text-[10px] text-gray-400">•</span>
                                            <span class="text-[10px] text-gray-400">{{ $attachment->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                                    <a href="{{ $attachment->storage_provider === 'google' ? $attachment->web_view_link : route('teams.attachments.download', [$team, $attachment]) }}" target="_blank" class="p-1.5 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-colors" title="{{ $attachment->storage_provider === 'google' ? 'Abrir en Drive' : 'Descargar' }}">
                                        @if($attachment->storage_provider === 'google')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                        @else
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                        @endif
                                    </a>
                                    @if($attachment->user_id === auth()->id() || $team->isManager(auth()->user()))
                                        <form action="{{ route('teams.attachments.destroy', [$team, $attachment]) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar este adjunto permanentemente?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="p-1.5 text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-colors" title="Eliminar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar Meta -->
        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm">
                <h3 class="text-xs font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-4">Resumen</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="text-[10px] font-black uppercase text-gray-400 block mb-1">Prioridad</label>
                        <span class="font-bold text-sm capitalize text-gray-800 dark:text-gray-200">{{ $expediente->priority }}</span>
                    </div>
                    <hr class="border-gray-100 dark:border-gray-800">
                    <div>
                        <label class="text-[10px] font-black uppercase text-gray-400 block mb-1">Creado por</label>
                        <div class="flex items-center gap-2 mt-1">
                            <img src="{{ $expediente->creator->profile_photo_url ?? 'https://ui-avatars.com/api/?name='.urlencode($expediente->creator->name) }}" class="w-5 h-5 rounded-full">
                            <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $expediente->creator->name }}</span>
                        </div>
                    </div>
                    <hr class="border-gray-100 dark:border-gray-800">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black uppercase text-gray-400 block mb-1">F. Inicio</label>
                            <span class="text-sm text-gray-600 dark:text-gray-300">{{ $expediente->start_date ? $expediente->start_date->format('d/m/Y') : '—' }}</span>
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-gray-400 block mb-1">F. Fin</label>
                            <span class="text-sm text-gray-600 dark:text-gray-300">{{ $expediente->end_date ? $expediente->end_date->format('d/m/Y') : '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rest of sidebar -->
        </div>
    </div>

    <x-google-drive-picker :team="$team" />

@push('scripts')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <style>
        /* Bulletproof Modern TomSelect Wrapper */
        .ts-control {
            border-radius: 0.75rem !important;
            border-width: 1px !important;
            background-color: #ffffff !important;
            border-color: #e5e7eb !important;
            padding: 0.625rem 1rem !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
            min-height: 44px !important;
            display: flex !important;
            align-items: center !important;
            flex-wrap: wrap !important;
            gap: 4px !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
        }
        .ts-control input { 
            font-size: 14px !important; 
            padding: 0 !important; 
            margin: 0 !important; 
            background: transparent !important; 
            border: none !important; 
            outline: none !important; 
            box-shadow: none !important;
            line-height: 1 !important;
            height: auto !important;
            flex: 1 1 auto !important;
        }
        .ts-control input::placeholder { color: #9ca3af !important; font-weight: 500 !important; }
        
        .dark .ts-control {
            background-color: #1f2937 !important;
            border-color: #374151 !important;
            color: #f3f4f6 !important;
        }
        
        .ts-wrapper.focus .ts-control {
            border-color: #7c3aed !important;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.2) !important;
        }
        
        .ts-dropdown { 
            border-radius: 1rem !important; 
            border: 1px solid #e5e7eb !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important; 
            margin-top: 6px !important; 
            padding: 0.5rem !important; 
            z-index: 9999 !important;
        }
        .dark .ts-dropdown { background-color: #111827 !important; border-color: #374151 !important; }
        
        .ts-dropdown .option { 
            padding: 0.625rem 0.75rem !important; 
            border-radius: 0.6rem !important; 
            margin-bottom: 2px !important; 
            transition: all 0.15s ease !important;
            color: #374151 !important;
        }
        .dark .ts-dropdown .option { color: #e5e7eb !important; }
        
        .ts-dropdown .active { 
            background-color: #f5f3ff !important; 
            color: #7c3aed !important; 
        }
        .dark .ts-dropdown .active { background-color: #7c3aed !important; color: #ffffff !important; }
        
        /* Ensure multi-items look premium */
        .ts-wrapper.multi .ts-control > div {
            background: #f5f3ff !important;
            color: #6d28d9 !important;
            border: 1px solid #ddd6fe !important;
            border-radius: 6px !important;
            padding: 2px 8px !important;
            font-weight: 600 !important;
        }
        .dark .ts-wrapper.multi .ts-control > div {
            background: #374151 !important;
            color: #e0e7ff !important;
            border-color: #4b5563 !important;
        }

        #task-selector { display: none !important; }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new TomSelect('#task-selector', {
                plugins: {
                    'remove_button': { title: 'Quitar' }
                },
                maxItems: null,
                placeholder: 'Escribe para buscar tareas...',
                render: {
                    option: function(data, escape) {
                        return '<div class="py-1.5 px-2 border-b border-gray-50 dark:border-gray-800/50">' +
                            '<div class="font-bold text-gray-900 dark:text-white text-xs">' + escape(data.text) + '</div>' +
                        '</div>';
                    }
                }
            });

            new TomSelect('#related-selector', {
                plugins: {
                    'remove_button': { title: 'Quitar' }
                },
                maxItems: null,
                placeholder: 'Escribe para buscar expedientes...',
                render: {
                    option: function(data, escape) {
                        return '<div class="py-1.5 px-2 border-b border-gray-50 dark:border-gray-800/50">' +
                            '<div class="font-bold text-gray-900 dark:text-white text-xs">' + escape(data.text) + '</div>' +
                        '</div>';
                    }
                }
            });
        });

        window.confirmUnlinkTask = function(taskId) {
            Swal.fire({
                title: '¿Desvincular Tarea?',
                text: "La tarea dejará de pertenecer a este expediente, pero conservará todos sus datos.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#7c3aed',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Sí, desvincular',
                cancelButtonText: 'Cancelar',
                customClass: {
                    popup: 'rounded-[2rem] border-0 shadow-2xl dark:bg-gray-900 dark:text-white p-4',
                    title: 'text-xl font-black text-gray-900 dark:text-white pt-4',
                    htmlContainer: 'text-sm text-gray-500 dark:text-gray-400 px-4 pb-2',
                    confirmButton: 'rounded-xl px-6 py-2.5 font-bold text-xs uppercase tracking-widest shadow-lg shadow-violet-500/30 transition-all',
                    cancelButton: 'rounded-xl px-6 py-2.5 font-bold text-xs uppercase tracking-widest transition-all'
                },
                buttonsStyling: true
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('unlink-form-' + taskId).submit();
                }
            });
        }
    </script>
@endpush

</x-app-layout>
