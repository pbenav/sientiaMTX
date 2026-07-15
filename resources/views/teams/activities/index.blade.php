<x-app-layout>
    @section('title', 'Actividades — ' . $team->name)

    <x-slot name="header">
        {{-- Fila única compacta: breadcrumb + título + acciones --}}
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2 min-w-0">
                <a href="{{ route('teams.dashboard', $team) }}"
                    class="p-1.5 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-lg transition-all shrink-0"
                    title="Volver al escritorio">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                @include('teams.partials.breadcrumb')
                <span class="text-gray-300 dark:text-gray-700 mx-1">/</span>
                <h1 class="text-base font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span class="truncate">Actividades</span>
                </h1>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @include('teams.partials.header-toolbar')
            </div>
        </div>

        @include('teams.partials.team-view-nav', ['showCreateActions' => true])
    </x-slot>

    <div class="space-y-6">
        <!-- Filtros y Búsqueda -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm transition-all">
            <form action="{{ route('teams.activities.index', $team) }}" method="GET" class="flex flex-wrap items-center gap-4">
                <!-- Search -->
                <div class="relative flex-1 min-w-[240px] group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400 group-focus-within:text-violet-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                        placeholder="Buscar actividades..."
                        class="w-full pl-10 pr-10 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 dark:text-white transition-all shadow-sm">
                </div>

                <!-- Tipo Filter -->
                <div class="w-44">
                    <select name="type" onchange="this.form.submit()"
                        class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-bold uppercase tracking-wider py-2.5 pr-10 focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 cursor-pointer transition-all shadow-sm">
                        <option value="">Tipo de Actividad</option>
                        <option value="task" {{ ($filters['type'] ?? '') === 'task' ? 'selected' : '' }}>📋 Tarea</option>
                        <option value="document" {{ ($filters['type'] ?? '') === 'document' ? 'selected' : '' }}>📄 Documento</option>
                        <option value="note" {{ ($filters['type'] ?? '') === 'note' ? 'selected' : '' }}>📝 Nota Rápida</option>
                        <option value="link" {{ ($filters['type'] ?? '') === 'link' ? 'selected' : '' }}>🔗 Enlace</option>
                        <option value="decision" {{ ($filters['type'] ?? '') === 'decision' ? 'selected' : '' }}>⚖️ Acuerdo</option>
                        <option value="meeting" {{ ($filters['type'] ?? '') === 'meeting' ? 'selected' : '' }}>🎥 Reunión</option>
                        <option value="reminder" {{ ($filters['type'] ?? '') === 'reminder' ? 'selected' : '' }}>🔔 Recordatorio</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="w-40">
                    <select name="status" onchange="this.form.submit()"
                        class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-bold uppercase tracking-wider py-2.5 pr-10 focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 cursor-pointer transition-all shadow-sm">
                        <option value="">Estado</option>
                        <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pendiente</option>
                        <option value="in_progress" {{ ($filters['status'] ?? '') === 'in_progress' ? 'selected' : '' }}>En Progreso</option>
                        <option value="completed" {{ ($filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>Completada</option>
                        <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelada</option>
                        <option value="blocked" {{ ($filters['status'] ?? '') === 'blocked' ? 'selected' : '' }}>Bloqueada</option>
                    </select>
                </div>

                <!-- Priority Filter -->
                <div class="w-36">
                    <select name="priority" onchange="this.form.submit()"
                        class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-bold uppercase tracking-wider py-2.5 pr-10 focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 cursor-pointer transition-all shadow-sm">
                        <option value="">Prioridad</option>
                        <option value="low" {{ ($filters['priority'] ?? '') === 'low' ? 'selected' : '' }}>Baja</option>
                        <option value="medium" {{ ($filters['priority'] ?? '') === 'medium' ? 'selected' : '' }}>Media</option>
                        <option value="high" {{ ($filters['priority'] ?? '') === 'high' ? 'selected' : '' }}>Alta</option>
                        <option value="critical" {{ ($filters['priority'] ?? '') === 'critical' ? 'selected' : '' }}>Crítica</option>
                    </select>
                </div>

                <!-- Assigned To Filter -->
                <div class="w-40">
                    <select name="assigned_to" onchange="this.form.submit()"
                        class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-bold uppercase tracking-wider py-2.5 pr-10 focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 cursor-pointer transition-all shadow-sm">
                        <option value="">Asignado a</option>
                        @foreach ($members as $member)
                            <option value="{{ $member->id }}" {{ ($filters['assigned_to'] ?? '') == $member->id ? 'selected' : '' }}>{{ $member->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Skill Filter -->
                <div class="w-40">
                    <select name="skill_id" onchange="this.form.submit()"
                        class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-bold uppercase tracking-wider py-2.5 pr-10 focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 cursor-pointer transition-all shadow-sm">
                        <option value="">Especialidad</option>
                        @foreach ($skills as $skill)
                            <option value="{{ $skill->id }}" {{ ($filters['skill_id'] ?? '') == $skill->id ? 'selected' : '' }}>{{ $skill->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Template Type Filter -->
                <div class="w-40">
                    <select name="template_type" onchange="this.form.submit()"
                        class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-bold uppercase tracking-wider py-2.5 pr-10 focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 cursor-pointer transition-all shadow-sm">
                        <option value="">Modo (Todas)</option>
                        <option value="normal" {{ ($filters['template_type'] ?? '') === 'normal' ? 'selected' : '' }}>Tareas Normales</option>
                        <option value="template" {{ ($filters['template_type'] ?? '') === 'template' ? 'selected' : '' }}>Plantillas</option>
                        <option value="instance" {{ ($filters['template_type'] ?? '') === 'instance' ? 'selected' : '' }}>Instancias (Subtareas)</option>
                    </select>
                </div>

                <!-- Per Page Filter -->
                <div class="w-32">
                    <select name="per_page" onchange="this.form.submit()"
                        class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-bold uppercase tracking-wider py-2.5 pr-10 focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 cursor-pointer transition-all shadow-sm">
                        @foreach([10, 15, 20, 30, 50, 100] as $num)
                            <option value="{{ $num }}" {{ ($filters['per_page'] ?? 15) == $num ? 'selected' : '' }}>{{ $num }} / pág.</option>
                        @endforeach
                    </select>
                </div>

                @if (collect($filters)->filter()->isNotEmpty())
                    <a href="{{ route('teams.activities.index', [$team, 'reset_filters' => 1]) }}"
                        class="text-xs font-bold text-red-500 hover:text-red-600 transition-colors uppercase tracking-widest">
                        Limpiar Filtros
                    </a>
                @endif
            </form>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-xl rounded-2xl overflow-hidden transition-all">
            <div id="bulkActionBar"
                class="hidden bg-white/95 dark:bg-gray-900/95 backdrop-blur-md border-b border-gray-100 dark:border-gray-800 p-4 sticky top-0 z-[40] flex flex-col xl:flex-row items-start xl:items-center justify-between gap-4 transition-all duration-300 shadow-sm">
                
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 w-full xl:w-auto">
                    <!-- Icon & Badge & X Button -->
                    <div class="flex items-center gap-3 bg-violet-50 dark:bg-violet-900/20 px-4 py-2 rounded-xl border border-violet-100 dark:border-violet-800/50 shrink-0">
                        <div class="relative">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="absolute -top-1.5 -right-1.5 flex h-4 w-4">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-violet-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-4 w-4 bg-violet-600 text-[9px] font-black text-white items-center justify-center" id="selectedCount">0</span>
                            </span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[10px] font-black uppercase tracking-widest text-violet-700 dark:text-violet-300">Selección</span>
                            <span class="text-xs font-semibold text-violet-500 dark:text-violet-400 leading-tight">Acción Masiva</span>
                        </div>
                        
                        <div class="w-px h-6 bg-violet-200 dark:bg-violet-800/60 mx-1"></div>
                        
                        <button type="button" onclick="deselectAll()"
                            class="p-1.5 hover:bg-violet-200 dark:hover:bg-violet-800 text-violet-500 rounded-lg transition-colors active:scale-95" title="Deseleccionar todo">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Actions/Selects -->
                    <div class="flex flex-wrap sm:flex-nowrap items-center gap-3 w-full sm:w-auto">
                        <!-- Bulk Status -->
                        <select onchange="applyBulkUpdate('status', this.value)" 
                            class="w-full sm:w-auto flex-1 bg-gray-50 dark:bg-gray-800 border-none hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-400 py-2.5 pl-4 pr-10 focus:ring-2 focus:ring-violet-500/50 cursor-pointer transition-all min-w-[140px]">
                            <option value="">🎯 Estado</option>
                            @foreach (['pending' => 'Pendiente', 'in_progress' => 'En Progreso', 'completed' => 'Completada', 'blocked' => 'Bloqueada'] as $val => $label)
                                <option value="{{ $val }}" class="text-gray-900 dark:text-white">{{ $label }}</option>
                            @endforeach
                        </select>

                        <!-- Bulk Priority -->
                        <select onchange="applyBulkUpdate('priority', this.value)" 
                            class="w-full sm:w-auto flex-1 bg-gray-50 dark:bg-gray-800 border-none hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-400 py-2.5 pl-4 pr-10 focus:ring-2 focus:ring-violet-500/50 cursor-pointer transition-all min-w-[140px]">
                            <option value="">⚡ Prioridad</option>
                            @foreach (['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta', 'critical' => 'Crítica'] as $val => $label)
                                <option value="{{ $val }}" class="text-gray-900 dark:text-white">{{ $label }}</option>
                            @endforeach
                        </select>

                        <!-- Bulk Assignee -->
                        <select onchange="applyBulkUpdate('assigned_user_id', this.value)" 
                            class="w-full sm:w-auto flex-1 bg-gray-50 dark:bg-gray-800 border-none hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-400 py-2.5 pl-4 pr-10 focus:ring-2 focus:ring-violet-500/50 cursor-pointer transition-all min-w-[140px]">
                            <option value="">👤 Responsable</option>
                            @foreach ($members as $member)
                                <option value="{{ $member->id }}" class="text-gray-900 dark:text-white">{{ $member->name }}</option>
                            @endforeach
                        </select>

                        <div class="hidden sm:block w-px h-6 bg-gray-200 dark:bg-gray-800 mx-1"></div>

                        <!-- Bulk Merge Button -->
                        <button type="button" onclick="openBulkMergeModal()"
                            class="flex-1 sm:flex-none px-4 py-2.5 bg-amber-500 text-white font-black uppercase text-xs rounded-xl hover:bg-amber-600 active:scale-95 transition-all shadow-sm flex items-center justify-center gap-1.5" title="Fusionar actividades seleccionadas">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                            </svg>
                            <span>Fusionar</span>
                        </button>

                        <!-- Bulk Delete Button -->
                        <button type="button" onclick="confirmBulkDelete()"
                            class="flex-1 sm:flex-none px-4 py-2.5 bg-red-500 text-white font-black uppercase text-xs rounded-xl hover:bg-red-600 active:scale-95 transition-all shadow-sm flex items-center justify-center gap-1.5" title="Eliminar actividades seleccionadas">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span>Eliminar</span>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto min-h-[200px] no-scrollbar">
                <table class="w-full text-left border-collapse min-w-[700px]">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800">
                            <th class="px-4 py-4 w-10 text-center">
                                <input type="checkbox" id="selectAllCheckbox" onchange="toggleAll(this)"
                                    class="rounded border-gray-300 text-red-600 shadow-sm focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 cursor-pointer transition-colors">
                            </th>
                            <th class="px-6 py-4">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'title', 'direction' => request('sort') == 'title' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                                    class="group flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                    {{ __('tasks.name') }}
                                    <x-sort-icon column="title" />
                                </a>
                            </th>
                            <th class="px-4 py-4 whitespace-nowrap">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'type', 'direction' => request('sort') == 'type' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                                    class="group flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                    Tipo
                                    <x-sort-icon column="type" />
                                </a>
                            </th>
                            <th class="px-4 py-4 whitespace-nowrap">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'direction' => request('sort') == 'status' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                                    class="group flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                    {{ __('tasks.status') }}
                                    <x-sort-icon column="status" />
                                </a>
                            </th>
                            <th class="px-4 py-4 whitespace-nowrap">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'priority', 'direction' => request('sort') == 'priority' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                                    class="group flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                    {{ __('tasks.priority') }} / {{ __('tasks.urgency') }}
                                    <x-sort-icon column="priority" />
                                </a>
                            </th>
                            <th
                                class="px-4 py-4 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 whitespace-nowrap hidden lg:table-cell">
                                {{ __('tasks.owner') ?? 'Responsable' }}
                            </th>
                            <th
                                class="px-4 py-4 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                {{ __('tasks.assigned_to') }}
                            </th>
                            <th class="px-4 py-4 whitespace-nowrap hidden xl:table-cell">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'progress_percentage', 'direction' => request('sort') == 'progress_percentage' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                                    class="group flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                    {{ __('tasks.progress') }}
                                    <x-sort-icon column="progress_percentage" />
                                </a>
                            </th>
                            <th class="px-4 py-4 whitespace-nowrap hidden md:table-cell">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'direction' => request('sort') == 'created_at' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                                    class="group flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                    Creada
                                    <x-sort-icon column="created_at" />
                                </a>
                            </th>
                            <th class="px-4 py-4 whitespace-nowrap hidden md:table-cell">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'due_date', 'direction' => request('sort') == 'due_date' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                                    class="group flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                    {{ __('tasks.due_date') }}
                                    <x-sort-icon column="due_date" />
                                </a>
                            </th>
                            <th
                                class="px-4 py-4 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 text-right whitespace-nowrap min-w-[100px]">
                                {{ __('tasks.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($activities as $activity)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors group cursor-pointer"
                                data-href="{{ route('teams.activities.show', [$team, $activity]) }}"
                                onclick="if(!event.target.closest('button, a, input, select')) window.location=this.dataset.href">
                                <td class="px-4 py-4 w-10 text-center" onclick="event.stopPropagation()">
                                    <input type="checkbox" value="{{ $activity->id }}"
                                        class="activity-checkbox rounded border-gray-300 text-red-600 shadow-sm focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 cursor-pointer transition-colors"
                                        onchange="updateSelectedCount()">
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-2 h-2 rounded-full {{ $activity->status_value === 'completed' ? 'bg-emerald-500' : ($activity->status_value === 'blocked' ? 'bg-red-500' : 'bg-violet-500') }} shrink-0">
                                        </div>

                                        @if ($activity->children->isNotEmpty())
                                            <button type="button"
                                                onclick="event.stopPropagation(); toggleSubtasks({{ $activity->id }}, this)"
                                                class="toggle-subtasks p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition-all mr-1"
                                                data-id="{{ $activity->id }}">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="h-3 w-3 transform transition-transform {{ session('show_all_subtasks') ? 'rotate-90' : '' }}" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </button>
                                        @else
                                            <div class="w-5 mr-1"></div>
                                        @endif
                                        <div class="min-w-0">
                                            <a href="{{ route('teams.activities.show', [$team, $activity]) }}"
                                                class="text-sm font-semibold text-gray-900 dark:text-white hover:text-violet-600 dark:hover:text-violet-400 transition-all truncate flex items-center gap-1.5 max-w-[140px] sm:max-w-xs md:max-w-md lg:max-w-lg"
                                                title="{{ $activity->type_label }}: {{ $activity->title }}">
                                                <span class="text-{{ $activity->type_badge_color }}-500 shrink-0">
                                                    {!! $activity->type_icon !!}
                                                </span>
                                                <span class="truncate">{{ $activity->title }}</span>
                                            </a>
                                            @if ($activity->expediente)
                                                <a href="{{ route('teams.expedientes.show', [$team, $activity->expediente]) }}" 
                                                   class="mr-2 px-1.5 py-0.5 text-[9px] font-black font-mono uppercase tracking-tighter bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300 rounded shadow-sm hover:bg-violet-200 dark:hover:bg-violet-900/60 transition-colors inline-flex items-center gap-1 border border-violet-200/50 dark:border-violet-500/20 mb-1"
                                                   title="{{ __('Expediente') }}: {{ $activity->expediente->title }}"
                                                   onclick="event.stopPropagation();">
                                                   <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                                   </svg>
                                                   {{ $activity->expediente->code }}
                                                </a>
                                            @endif
                                            @if ($activity->google_task_id)
                                                <span class="ml-2 px-1.5 py-0.5 text-[10px] font-bold text-blue-500 bg-blue-50 dark:bg-blue-900/20 rounded shadow-sm inline-flex items-center gap-1 border border-blue-200/50 dark:border-blue-700/50" title="Sincronizada con Google Tasks">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                                                </span>
                                            @endif
                                            @if ($activity->google_calendar_event_id)
                                                <span class="ml-2 px-1.5 py-0.5 text-[10px] font-bold text-amber-600 bg-amber-50 dark:bg-amber-900/20 rounded shadow-sm inline-flex items-center gap-1 border border-amber-200/50 dark:border-amber-700/50" title="Sincronizada con Google Calendar">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                </span>
                                            @endif
                                            @if ($activity->is_autoprogrammable)
                                                <span class="ml-2 px-1.5 py-0.5 text-[10px] font-bold text-violet-600 bg-violet-50 dark:bg-violet-900/20 rounded shadow-sm inline-flex items-center gap-1 border border-violet-200/50 dark:border-violet-700/50" title="Plantilla de Autoprogramación">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                                </span>
                                            @elseif (!$activity->is_autoprogrammable && $activity->parent && $activity->parent->is_autoprogrammable)
                                                <a href="{{ route('teams.activities.show', [$team, $activity->parent_id]) }}" class="ml-2 px-1.5 py-0.5 text-[10px] font-bold text-violet-500 bg-violet-50 dark:bg-violet-900/20 rounded shadow-sm inline-flex items-center gap-1 border border-violet-200/50 dark:border-violet-700/50 hover:bg-violet-100 dark:hover:bg-violet-900/40 transition-colors" title="Actividad autoprogramada (Ir a plantilla maestra)" onclick="event.stopPropagation();">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                                </a>
                                            @endif
                                            @if ($activity->privacy_level === 'private')
                                                <span
                                                    class="ml-2 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-tighter bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 rounded shadow-sm inline-flex items-center"
                                                    title="{{ __('tasks.private') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="h-2.5 w-2.5 mr-0.5" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="3"
                                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                    </svg>
                                                    {{ __('tasks.private') }}
                                                </span>
                                            @elseif ($activity->privacy_level === 'semi-private')
                                                <span
                                                    class="ml-2 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-tighter bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 rounded shadow-sm inline-flex items-center"
                                                    title="{{ __('Compartida con varios miembros') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="h-2.5 w-2.5 mr-0.5" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="3"
                                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                    </svg>
                                                    {{ __('Semiprivada') }}
                                                </span>
                                            @else
                                                <span
                                                    class="ml-2 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-tighter bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 rounded shadow-sm inline-flex items-center"
                                                    title="{{ __('tasks.public') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    {{ __('tasks.public') }}
                                                </span>
                                            @endif
                                            @if ($activity->is_template)
                                                @php
                                                    $isCollaborative = isset($activity->metadata['assignment_mode']) && $activity->metadata['assignment_mode'] === 'distributed' && $activity->assignedTo->count() > 0;
                                                @endphp
                                                @if($isCollaborative)
                                                    <span class="ml-2 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-tighter bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 rounded shadow-sm inline-flex items-center">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                                        </svg>
                                                        {{ __('activities.collaborative_task') ?? 'Tarea Colaborativa' }}
                                                    </span>
                                                @else
                                                    <span class="ml-2 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-tighter bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300 rounded shadow-sm inline-flex items-center">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                                        </svg>
                                                        {{ __('tasks.plan_master') }}
                                                    </span>
                                                @endif
                                            @endif

                                            @if ($activity->avg_quality_score > 0)
                                                <span class="ml-2 px-1.5 py-0.5 text-[10px] font-bold text-amber-500 bg-amber-50 dark:bg-amber-900/20 rounded shadow-sm inline-flex items-center gap-1 border border-amber-200/50 dark:border-amber-700/50" title="Valoración promedio: {{ number_format($activity->avg_quality_score, 1) }} estrellas">
                                                    <svg class="w-3 h-3 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                    {{ number_format($activity->avg_quality_score, 1) }}
                                                </span>
                                            @else
                                                <span class="ml-2 px-1.5 py-0.5 text-[10px] font-bold text-gray-400 bg-gray-50 dark:bg-gray-800/50 rounded shadow-sm inline-flex items-center gap-1 border border-gray-200/50 dark:border-gray-700/50" title="Sin valoraciones">
                                                    <svg class="w-3 h-3 fill-current opacity-50" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                    0
                                                </span>
                                            @endif

                                            @if ($activity->assigned_user_id === auth()->id() && $activity->parent_id)
                                                <span
                                                    class="ml-2 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-tighter bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 rounded shadow-sm inline-flex items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                    </svg>
                                                    {{ __('tasks.your_execution') }}
                                                </span>
                                            @elseif ($activity->isInstance())
                                                <div class="flex items-center gap-1.5 mt-1">
                                                    <span
                                                        class="px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-tighter bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-400 border border-violet-100 dark:border-violet-500/20 rounded-md shadow-sm">
                                                        ↳ {{ __('tasks.subtask') }}
                                                    </span>
                                                    @if ($activity->parent)
                                                        <span
                                                            class="text-[10px] text-gray-400 dark:text-gray-500 font-medium truncate max-w-[150px]">
                                                            {{ $activity->parent->title }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-[10px] font-bold rounded bg-{{ $activity->type_badge_color }}-50 text-{{ $activity->type_badge_color }}-700 dark:bg-{{ $activity->type_badge_color }}-900/40 dark:text-{{ $activity->type_badge_color }}-300 uppercase tracking-wider flex items-center gap-1 max-w-fit border border-{{ $activity->type_badge_color }}-200 dark:border-{{ $activity->type_badge_color }}-800">
                                        {!! $activity->type_icon !!}
                                        {{ $activity->type_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    @php
                                        $statusClasses = [
                                            'completed'   => 'bg-emerald-50 border-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400',
                                            'in_progress' => 'bg-blue-50 border-blue-100 text-blue-700 dark:bg-blue-500/10 dark:border-blue-500/20 dark:text-blue-400',
                                            'blocked'     => 'bg-red-50 border-red-100 text-red-700 dark:bg-red-500/10 dark:border-red-500/20 dark:text-red-400',
                                            'deprecated'  => 'bg-orange-50 border-orange-100 text-orange-700 dark:bg-orange-500/10 dark:border-orange-500/20 dark:text-orange-400',
                                            'legacy'      => 'bg-amber-50 border-amber-100 text-amber-700 dark:bg-amber-500/10 dark:border-amber-500/20 dark:text-amber-400',
                                            'default'     => 'bg-gray-50 border-gray-100 text-gray-600 dark:bg-gray-500/10 dark:border-gray-500/20 dark:text-gray-400'
                                        ];
                                        $currentClass = $statusClasses[$activity->status_value] ?? $statusClasses['default'];
                                    @endphp
                                    <span class="px-2.5 py-1 text-[11px] font-bold rounded-lg border {{ $activity->trashed() ? 'bg-red-50 border-red-200 text-red-600 dark:bg-red-900/30 dark:border-red-800 dark:text-red-400' : $currentClass }} uppercase">
                                        {{ $activity->trashed() ? '🗑️ PAPELERA' : __("activities.statuses.{$activity->status_value}") }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-xs whitespace-nowrap">
                                    {{ __("tasks.priorities.{$activity->priority}") }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap hidden lg:table-cell">
                                    <div class="flex items-center gap-2">
                                        <img src="{{ $activity->creator ? $activity->creator->profile_photo_url : 'https://ui-avatars.com/api/?name=?&color=7F9CF5&background=EBF4FF' }}" 
                                            alt="{{ $activity->creator?->name ?? '?' }}"
                                            class="w-5 h-5 rounded-full object-cover shadow-sm border border-white dark:border-gray-800">
                                        <span class="text-xs text-gray-600 dark:text-gray-400">{{ $activity->creator?->name ?? '—' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                     @if($activity->assignedUser)
                                         <div class="flex items-center gap-2">
                                             <div class="flex -space-x-2">
                                                <img src="{{ $activity->assignedUser->profile_photo_url }}" 
                                                     alt="{{ $activity->assignedUser->name }}"
                                                     class="w-5 h-5 rounded-full object-cover shadow-sm border-2 border-white dark:border-gray-800" title="{{ $activity->assignedUser->name }}">
                                             </div>
                                             <span class="text-xs font-medium text-gray-700 dark:text-gray-300">1 {{ __('tasks.member') ?? 'Miembro' }}</span>
                                         </div>
                                     @elseif($activity->assignedTo->count() > 0)
                                         <div class="flex items-center gap-2">
                                             <div class="flex -space-x-2">
                                                 @foreach($activity->assignedTo->take(3) as $u)
                                                     <img src="{{ $u->profile_photo_url }}" alt="{{ $u->name }}" class="w-5 h-5 rounded-full object-cover shadow-sm border-2 border-white dark:border-gray-800" title="{{ $u->name }}">
                                                 @endforeach
                                             </div>
                                             <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $activity->assignedTo->count() }} {{ $activity->assignedTo->count() == 1 ? (__('tasks.member') ?? 'Miembro') : (__('tasks.members') ?? 'Miembros') }}</span>
                                         </div>
                                     @elseif($activity->assignedGroups->count() > 0)
                                         <div class="flex items-center gap-2">
                                             <div class="w-5 h-5 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 border border-white dark:border-gray-800 shrink-0">
                                                 <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                             </div>
                                             <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $activity->assignedGroups->count() }} {{ $activity->assignedGroups->count() == 1 ? (__('tasks.group') ?? 'Grupo') : (__('tasks.groups') ?? 'Grupos') }}</span>
                                         </div>
                                     @else
                                        <div class="flex items-center gap-2 opacity-75">
                                            <img src="{{ $activity->creator ? $activity->creator->profile_photo_url : 'https://ui-avatars.com/api/?name=?&color=7F9CF5&background=EBF4FF' }}" 
                                                alt="{{ $activity->creator?->name ?? '?' }}"
                                                class="w-5 h-5 rounded-full object-cover shadow-sm border border-white dark:border-gray-800">
                                            <div class="flex flex-col">
                                                <span class="text-[11px] text-gray-500 dark:text-gray-400">{{ $activity->creator?->name ?? '—' }}</span>
                                                <span class="text-[8px] font-black uppercase tracking-widest text-violet-500/70 dark:text-violet-400/50">
                                                    {{ $activity->is_template ? (__('tasks.template') ?? 'Plantilla') : (__('tasks.owner_short') ?? 'Prop.') }}
                                                </span>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap hidden xl:table-cell">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="flex-1 w-20 h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden shadow-inner border border-gray-200 dark:border-gray-700">
                                            <div class="h-full bg-gradient-to-r from-violet-500 to-violet-600 shadow-sm"
                                                style="width: {{ $activity->progress }}%"></div>
                                        </div>
                                        <span
                                            class="text-[10px] font-bold text-gray-400 dark:text-gray-500 w-6">{{ $activity->progress }}%</span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap hidden md:table-cell">
                                    <span
                                        class="text-xs text-gray-500">{{ $activity->created_at ? $activity->created_at->format('d/m/y') : '—' }}</span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap hidden md:table-cell">
                                    <span
                                        class="text-xs text-gray-500">{{ $activity->due_date ? $activity->due_date->format('d/m/y') : '—' }}</span>
                                </td>
                                <td class="px-4 py-4 text-right whitespace-nowrap min-w-[124px]">
                                    <div
                                        class="flex items-center justify-end gap-1 transition-opacity">
                                        @can('update', $activity)
                                            <a href="{{ route('teams.activities.edit', [$team, $activity]) }}"
                                                class="p-1.5 text-gray-400 hover:text-blue-500 transition-colors" title="{{ __('tasks.edit') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </a>

                                            <button type="button"
                                                onclick="event.stopPropagation(); window.cloneTask('{{ route('teams.activities.clone', [$team, $activity]) }}')"
                                                class="p-1.5 text-gray-400 hover:text-violet-500 transition-colors"
                                                title="{{ __('tasks.clone') ?? 'Clonar actividad' }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                            </button>

                                            <button type="button"
                                                onclick="event.stopPropagation(); confirmDeleteTask({{ $activity->id }}, '{{ addslashes($activity->title) }}')"
                                                class="p-1.5 text-gray-400 hover:text-red-500 transition-colors"
                                                title="{{ __('tasks.delete') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>

                            {{-- Subtasks loop --}}
                            @php
                                $maxProgress = $activity->children->max('progress_percentage');
                            @endphp
                            @foreach ($activity->children as $subtask)
                                <tr class="subtask-row {{ session('show_all_subtasks') ? '' : 'hidden' }} bg-gray-50/50 dark:bg-gray-800/20 transition-colors group cursor-pointer border-b border-gray-100 dark:border-gray-800/40"
                                    {!! session('show_all_subtasks') ? '' : 'style="display: none;"' !!}
                                    data-parent="{{ $activity->id }}"
                                    onclick="if(!event.target.closest('button, a, input, select')) window.location='{{ route('teams.activities.show', [$team, $subtask]) }}'">
                                    <td class="px-4 py-3 w-10 text-center" onclick="event.stopPropagation()">
                                        <input type="checkbox" value="{{ $subtask->id }}"
                                            class="activity-checkbox rounded border-gray-300 text-red-600 shadow-sm focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 cursor-pointer transition-colors"
                                            onchange="updateSelectedCount()">
                                    </td>
                                    <td class="px-6 py-3 pl-16">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-1.5 h-1.5 rounded-full {{ $subtask->status_value === 'completed' ? 'bg-emerald-500' : 'bg-gray-400' }} shrink-0">
                                            </div>
                                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                                {{ $subtask->title }}
                                            </span>
                                            @if ($subtask->google_task_id)
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold text-blue-500 bg-blue-50 dark:bg-blue-900/20 border border-blue-200/50 dark:border-blue-700/50 flex items-center gap-1" title="Sincronizada con Google Tasks">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                                                </span>
                                            @endif
                                            @if ($subtask->google_calendar_event_id)
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold text-amber-600 bg-amber-50 dark:bg-amber-900/20 border border-amber-200/50 dark:border-amber-700/50 flex items-center gap-1" title="Sincronizada con Google Calendar">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                </span>
                                            @endif
                                            @if ($subtask->is_autoprogrammable)
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold text-violet-600 bg-violet-50 dark:bg-violet-900/20 border border-violet-200/50 dark:border-violet-700/50 flex items-center gap-1" title="Plantilla de Autoprogramación">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                                </span>
                                            @elseif (!$subtask->is_autoprogrammable && $subtask->parent && $subtask->parent->is_autoprogrammable)
                                                <a href="{{ route('teams.activities.show', [$team, $subtask->parent_id]) }}" class="px-1.5 py-0.5 rounded text-[10px] font-bold text-violet-500 bg-violet-50 dark:bg-violet-900/20 border border-violet-200/50 dark:border-violet-700/50 hover:bg-violet-100 dark:hover:bg-violet-900/40 transition-colors flex items-center gap-1" title="Actividad autoprogramada (Ir a plantilla maestra)" onclick="event.stopPropagation();">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                                </a>
                                            @endif
                                            @if($maxProgress > 0 && $subtask->progress_percentage === $maxProgress)
                                                <span class="px-1 py-0.5 rounded text-[8px] font-black uppercase tracking-tighter bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 border border-amber-200 dark:border-amber-800/50 flex items-center gap-0.5 animate-pulse" title="{{ __('tasks.leading_progress') ?? 'Máximo progreso' }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                    TOP
                                                </span>
                                            @endif

                                            @if($subtask->avg_quality_score > 0)
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold text-amber-500 bg-amber-50 dark:bg-amber-900/20 border border-amber-200/50 dark:border-amber-700/50 flex items-center gap-1" title="Valoración promedio: {{ number_format($subtask->avg_quality_score, 1) }} estrellas">
                                                    <svg class="w-2.5 h-2.5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                    {{ number_format($subtask->avg_quality_score, 1) }}
                                                </span>
                                            @else
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold text-gray-400 bg-gray-50 dark:bg-gray-800/50 border border-gray-200/50 dark:border-gray-700/50 flex items-center gap-1" title="Sin valoraciones">
                                                    <svg class="w-2.5 h-2.5 fill-current opacity-50" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                    0
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="px-2 py-1 text-[9px] font-bold rounded bg-{{ $subtask->type_badge_color }}-50 text-{{ $subtask->type_badge_color }}-700 dark:bg-{{ $subtask->type_badge_color }}-900/40 dark:text-{{ $subtask->type_badge_color }}-300 uppercase tracking-wider flex items-center gap-1 max-w-fit border border-{{ $subtask->type_badge_color }}-200 dark:border-{{ $subtask->type_badge_color }}-800 opacity-80">
                                            {!! $subtask->type_icon !!}
                                            {{ $subtask->type_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="px-1.5 py-0.5 text-[9px] font-bold rounded-md {{ $subtask->trashed() ? 'bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400' : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400' }} uppercase tracking-tight">
                                            {{ $subtask->trashed() ? '🗑️ PAPELERA' : __("activities.statuses.{$subtask->status_value}") }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-[10px] text-gray-400 whitespace-nowrap flex items-center gap-1.5">
                                        {{ __("tasks.priorities.{$subtask->priority}") }}
                                        @if($subtask->priority !== $activity->priority)
                                            <span class="p-0.5 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400" title="{{ __('tasks.priority_changed') ?? 'Prioridad modificada por el usuario' }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-[10px] text-gray-400 whitespace-nowrap hidden lg:table-cell">
                                        {{ $subtask->creator?->name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-[10px] text-gray-400 whitespace-nowrap">
                                        @if($subtask->assignedUser)
                                            1 {{ __('tasks.member') ?? 'Miembro' }}
                                        @elseif($subtask->assignedTo->count() > 0)
                                            {{ $subtask->assignedTo->count() }} {{ $subtask->assignedTo->count() == 1 ? (__('tasks.member') ?? 'Miembro') : (__('tasks.members') ?? 'Miembros') }}
                                        @elseif($subtask->assignedGroups->count() > 0)
                                            {{ $subtask->assignedGroups->count() }} {{ $subtask->assignedGroups->count() == 1 ? (__('tasks.group') ?? 'Grupo') : (__('tasks.groups') ?? 'Grupos') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap hidden xl:table-cell">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="flex-1 w-16 h-1 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden shadow-inner border border-gray-200 dark:border-gray-700">
                                                <div class="h-full bg-gradient-to-r from-violet-500 to-violet-600 shadow-sm"
                                                    style="width: {{ $subtask->progress }}%"></div>
                                            </div>
                                            <span
                                                class="text-[9px] font-bold text-gray-400 dark:text-gray-500 w-5">{{ $subtask->progress }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-[10px] text-gray-400 whitespace-nowrap hidden md:table-cell">
                                        {{ $subtask->created_at ? $subtask->created_at->format('d/m/y') : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-[10px] text-gray-400 whitespace-nowrap hidden md:table-cell">
                                        {{ $subtask->due_date ? $subtask->due_date->format('d/m/y') : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap min-w-[124px]">
                                        <div
                                            class="flex items-center justify-end gap-1 transition-opacity">
                                            <a href="{{ route('teams.activities.show', [$team, $subtask]) }}"
                                                class="p-1 text-gray-400 hover:text-violet-400 transition-colors" title="{{ __('tasks.view') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </a>

                                            @can('update', $subtask)
                                                <button type="button"
                                                    onclick="event.stopPropagation(); window.cloneTask('{{ route('teams.activities.clone', [$team, $subtask]) }}')"
                                                    class="p-1 text-gray-400 hover:text-violet-500 transition-colors"
                                                    title="{{ __('tasks.clone') ?? 'Clonar subtarea' }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                    </svg>
                                                </button>
                                            @endcan

                                            @can('delete', $subtask)
                                                <button type="button"
                                                    onclick="event.stopPropagation(); confirmDeleteTask({{ $subtask->id }}, '{{ addslashes($subtask->title) }}')"
                                                    class="p-1 text-gray-400 hover:text-red-500 transition-colors"
                                                    title="{{ __('tasks.delete') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                        @if ($activities->isEmpty())
                            <tr>
                                <td colspan="10" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <div
                                            class="w-12 h-12 rounded-2xl bg-gray-50 dark:bg-gray-800 flex items-center justify-center text-gray-400 border border-gray-100 dark:border-gray-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
                                        </div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('tasks.no_tasks') }}
                                        </p>
                                        <a href="{{ route('teams.activities.create', $team) }}"
                                            class="mt-2 text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline">
                                            Nueva Actividad
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            @if ($activities->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50/30 dark:bg-transparent">
                    {{ $activities->links() }}
                </div>
            @endif
        </div>
    </div>
    @push('scripts')
            <script>
                function toggleAll(source) {
                    const checkboxes = document.querySelectorAll('.activity-checkbox');
                    checkboxes.forEach(cb => cb.checked = source.checked);
                    updateSelectedCount();
                }

                function updateSelectedCount() {
                    const selected = document.querySelectorAll('.activity-checkbox:checked').length;
                    const counter = document.getElementById('selectedCount');
                    if (counter) counter.textContent = selected;

                    const bulkBar = document.getElementById('bulkActionBar');
                    if (bulkBar) {
                        if (selected > 0) {
                            bulkBar.classList.remove('hidden');
                        } else {
                            bulkBar.classList.add('hidden');
                        }
                    }
                }

                function confirmBulkDelete() {
                    const selected = document.querySelectorAll('.activity-checkbox:checked');
                    if (selected.length === 0) return;

                    Swal.fire({
                        title: '¿Eliminar selección?',
                        text: `Estás a punto de eliminar ${selected.length} actividades. Esta acción no se puede deshacer.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar',
                        background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                        color: document.documentElement.classList.contains('dark') ? '#f3f4f6' : '#111827'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const container = document.getElementById('bulkDeleteInputs');
                            container.innerHTML = '';
                            selected.forEach(cb => {
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'task_ids[]';
                                input.value = cb.value;
                                container.appendChild(input);
                            });
                            document.getElementById('bulkDeleteForm').submit();
                        }
                    });
                }

                function applyBulkUpdate(field, value) {
                    if (!value) return;
                    
                    const selected = document.querySelectorAll('.activity-checkbox:checked');
                    if (selected.length === 0) return;

                    const fieldLabels = {
                        'status': 'Estado',
                        'priority': 'Prioridad',
                        'assigned_user_id': 'Responsable'
                    };

                    Swal.fire({
                        title: `¿Cambiar ${fieldLabels[field]}?`,
                        text: `Vas a actualizar ${selected.length} actividades seleccionadas.`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, actualizar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#7c3aed',
                        background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                        color: document.documentElement.classList.contains('dark') ? '#f3f4f6' : '#111827'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const form = document.getElementById('bulkUpdateForm');
                            const container = document.getElementById('bulkUpdateInputs');
                            container.innerHTML = '';
                            
                            // Field to update
                            const fieldInput = document.createElement('input');
                            fieldInput.type = 'hidden';
                            fieldInput.name = 'field';
                            fieldInput.value = field;
                            container.appendChild(fieldInput);

                            const valueInput = document.createElement('input');
                            valueInput.type = 'hidden';
                            valueInput.name = 'value';
                            valueInput.value = value;
                            container.appendChild(valueInput);

                            // Tasks IDs
                            selected.forEach(cb => {
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'task_ids[]';
                                input.value = cb.value;
                                container.appendChild(input);
                            });
                            
                            form.submit();
                        } else {
                            // Reset select
                            event.target.value = '';
                        }
                    });
                }

                function deselectAll() {
                    document.getElementById('selectAllCheckbox').checked = false;
                    toggleAll(document.getElementById('selectAllCheckbox'));
                }

                // ── Bulk Merge ──────────────────────────────────────────
                // Mapa de IDs → títulos generado desde Blade para usarlo en el modal
                const taskTitles = {};
                @foreach($activities as $t)
                    taskTitles[{{ $t->id }}] = @json($t->title);
                    @foreach($t->children as $sub)
                        taskTitles[{{ $sub->id }}] = @json($sub->title);
                    @endforeach
                @endforeach

                function openBulkMergeModal() {
                    const selected = [...document.querySelectorAll('.activity-checkbox:checked')];
                    if (selected.length < 2) {
                        Swal.fire({ title: 'Selección insuficiente', text: 'Debes seleccionar al menos 2 actividades para fusionarlas.', icon: 'info',
                            background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                            color: document.documentElement.classList.contains('dark') ? '#f3f4f6' : '#111827' });
                        return;
                    }

                    const list = document.getElementById('mergeTaskList');
                    list.innerHTML = '';

                    selected.forEach((cb, i) => {
                        const id = cb.value;
                        const title = taskTitles[id] ?? `Actividad #${id}`;
                        const div = document.createElement('label');
                        div.className = 'flex items-center gap-3 p-3 rounded-xl border border-gray-100 dark:border-gray-800 hover:border-amber-300 dark:hover:border-amber-600 hover:bg-amber-50/50 dark:hover:bg-amber-900/10 cursor-pointer transition-all group';
                        div.innerHTML = `
                            <input type="radio" name="merge_target" value="${id}" ${ i === 0 ? 'checked' : '' }
                                class="text-amber-500 focus:ring-amber-400 dark:bg-gray-800 cursor-pointer">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-gray-800 dark:text-white truncate">${title}</p>
                                <p class="text-[10px] text-gray-400 font-medium">ID #${id}</p>
                            </div>
                            <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-lg bg-amber-100 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 opacity-0 group-hover:opacity-100 transition-opacity" id="target-badge-${id}">Destino</span>
                        `;
                        // Show badge only on checked radio
                        div.querySelector('input').addEventListener('change', () => {
                            document.querySelectorAll('[id^="target-badge-"]').forEach(b => b.classList.add('opacity-0'));
                            document.getElementById(`target-badge-${id}`).classList.remove('opacity-0');
                        });
                        list.appendChild(div);
                    });

                    // Show badge on first item immediately
                    const first = list.querySelector('[id^="target-badge-"]');
                    if (first) first.classList.remove('opacity-0');

                    document.getElementById('bulkMergeModal').classList.remove('hidden');
                }

                function closeBulkMergeModal() {
                    document.getElementById('bulkMergeModal').classList.add('hidden');
                }

                function confirmBulkMerge() {
                    const targetId = document.querySelector('#mergeTaskList input[name="merge_target"]:checked')?.value;
                    if (!targetId) return;

                    const selected = [...document.querySelectorAll('.activity-checkbox:checked')];
                    const targetTitle = taskTitles[targetId] ?? `Actividad #${targetId}`;

                    closeBulkMergeModal();

                    Swal.fire({
                        title: '¿Confirmas la fusión?',
                        html: `<p class="text-sm">Se fusionarán <strong>${selected.length} actividades</strong> en:</p><p class="mt-2 font-bold text-amber-600">«${targetTitle}»</p><p class="text-xs text-gray-500 mt-1">Las demás actividades serán eliminadas tras migrar su contenido.</p>`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, fusionar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#f59e0b',
                        background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                        color: document.documentElement.classList.contains('dark') ? '#f3f4f6' : '#111827'
                    }).then(result => {
                        if (!result.isConfirmed) return;

                        const container = document.getElementById('bulkMergeInputs');
                        container.innerHTML = '';

                        // target_task_id
                        const targetInput = document.createElement('input');
                        targetInput.type = 'hidden';
                        targetInput.name = 'target_task_id';
                        targetInput.value = targetId;
                        container.appendChild(targetInput);

                        // task_ids[]
                        selected.forEach(cb => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'task_ids[]';
                            input.value = cb.value;
                            container.appendChild(input);
                        });

                        document.getElementById('bulkMergeForm').submit();
                    });
                }

                window.cloneTask = function(url) {
                    Swal.fire({
                        title: '¿Clonar actividad?',
                        text: 'Se creará una copia exacta de esta actividad en este mismo equipo.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#7c3aed',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'Sí, clonar',
                        cancelButtonText: 'Cancelar',
                        background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                        color: document.documentElement.classList.contains('dark') ? '#f3f4f6' : '#111827',
                        customClass: {
                            popup: 'rounded-[2rem]',
                            confirmButton: 'rounded-xl font-black uppercase text-xs tracking-widest',
                            cancelButton: 'rounded-xl font-black uppercase text-xs tracking-widest'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const form = document.getElementById('individualCloneForm');
                            form.action = url;
                            form.submit();
                        }
                    });
                }

                function confirmDeleteTask(taskId, taskTitle) {
                    Swal.fire({
                        title: '¿Eliminar actividad?',
                        text: `¿Estás seguro de que quieres eliminar la actividad "${taskTitle}"? Esta acción no se puede deshacer y su progreso dejará de sumar.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar',
                        background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                        color: document.documentElement.classList.contains('dark') ? '#f3f4f6' : '#111827'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const form = document.getElementById('individualDeleteForm');
                            form.action = `{{ url('/teams/' . $team->id . '/activities') }}/${taskId}`;
                            form.submit();
                        }
                    });
                }

                function toggleSubtasks(taskId, button) {
                    const subtasks = document.querySelectorAll(`.subtask-row[data-parent="${taskId}"]`);
                    const icon = button.querySelector('svg');

                    subtasks.forEach(st => {
                        const isHidden = st.classList.toggle('hidden');
                        st.style.display = isHidden ? 'none' : '';
                    });

                    icon.classList.toggle('rotate-90');
                }

                // Document event listener for data-href rows if any left (though we used inline onclick)
                document.addEventListener('DOMContentLoaded', function() {
                    updateSelectedCount();
                });

                // Manejar la restauración de checkboxes cuando el navegador usa bfcache o navega hacia atrás
                window.addEventListener('pageshow', function(event) {
                    updateSelectedCount();
                });

                async function toggleHideCompleted() {
                    const btn = document.getElementById('hideCompletedBtn');
                    btn.disabled = true;
                    btn.style.opacity = '0.6';

                    try {
                        const response = await fetch('{{ route('tasks.toggle-hide-completed') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        const data = await response.json();
                        // Reload to apply server-side filter
                        window.location.reload();
                    } catch (e) {
                        btn.disabled = false;
                        btn.style.opacity = '1';
                    }
                }

                function confirmPurgeTrash() {
                    Swal.fire({
                        title: '¿Vaciar papelera?',
                        text: 'Se eliminarán PERMANENTEMENTE todas las actividades de este equipo que estén en la papelera, junto con sus historiales y archivos. Esta acción no se puede deshacer.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'Sí, vaciar papelera',
                        cancelButtonText: 'Cancelar',
                        background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                        color: document.documentElement.classList.contains('dark') ? '#f3f4f6' : '#111827'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('purgeTrashForm').submit();
                        }
                    });
                }
            </script>
        </div>
    @endpush
        <form id="individualDeleteForm" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>

        <form id="individualCloneForm" method="POST" class="hidden">
            @csrf
        </form>

        <form id="bulkDeleteForm" action="{{ route('teams.activities.bulk-delete', $team) }}" method="POST"
            class="hidden">
            @csrf
            @method('DELETE')
            <div id="bulkDeleteInputs"></div>
        </form>

        <form id="bulkUpdateForm" action="{{ route('teams.activities.bulk-update', $team) }}" method="POST"
            class="hidden">
            @csrf
            @method('PATCH')
            <div id="bulkUpdateInputs"></div>
        </form>

        <form id="purgeTrashForm" action="{{ route('teams.activities.purge-trash', $team) }}" method="POST" class="hidden">
            @csrf
        </form>
        {{-- Formulario de fusión masiva --}}
        <form id="bulkMergeForm" action="{{ route('teams.activities.bulk-merge', $team) }}" method="POST" class="hidden">
            @csrf
            <div id="bulkMergeInputs"></div>
        </form>

        {{-- Modal de selección de tarea destino para fusión masiva --}}
        <div id="bulkMergeModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 hidden" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeBulkMergeModal()"></div>
            <div class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 w-full max-w-lg max-h-[80vh] flex flex-col overflow-hidden">
                {{-- Header --}}
                <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-100 dark:bg-amber-500/10 flex items-center justify-center text-amber-600 dark:text-amber-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Fusión masiva de actividades</h3>
                            <p class="text-[11px] text-gray-400">Elige cuál será la actividad principal (destino)</p>
                        </div>
                    </div>
                    <button type="button" onclick="closeBulkMergeModal()" class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Info banner --}}
                <div class="mx-5 mt-4 px-3 py-2.5 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 rounded-xl">
                    <p class="text-[11px] text-amber-700 dark:text-amber-400 font-medium leading-snug">
                        <strong>Todas las actividades</strong> se fusionarán en la que marques como destino. Sus contenidos, adjuntos, tiempo, notas y asignaciones se migrarán. Las demás serán eliminadas.
                    </p>
                </div>

                {{-- Lista de tareas seleccionadas con radio --}}
                <div class="flex-1 overflow-y-auto p-5 space-y-2" id="mergeTaskList"></div>

                {{-- Footer --}}
                <div class="p-5 border-t border-gray-100 dark:border-gray-800 flex gap-3">
                    <button type="button" onclick="closeBulkMergeModal()"
                        class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-xs font-bold rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                        Cancelar
                    </button>
                    <button type="button" onclick="confirmBulkMerge()"
                        class="flex-1 px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl transition-all shadow-sm shadow-amber-500/20 active:scale-95">
                        Fusionar ahora
                    </button>
                </div>
            </div>
        </div>
</x-app-layout>
