<x-app-layout>
    @section('title', __('navigation.task_list') . ' — ' . $team->name)

    <x-slot name="header">
        <div class="flex flex-col xl:flex-row xl:items-start justify-between gap-6">
            <div class="flex items-start gap-4 min-w-0 flex-1">
                <a href="{{ route('teams.dashboard', $team) }}"
                    class="mt-1 p-2.5 bg-gray-50 dark:bg-gray-800/50 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-2xl transition-all shadow-sm border border-gray-100 dark:border-gray-700/50 shrink-0"
                    title="{{ __('navigation.back') ?? 'Volver' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div class="min-w-0 flex-1">
                    @include('teams.partials.breadcrumb')
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight">
                        {{ __('navigation.task_list') }}
                    </h1>
                </div>
            </div>
        </div>

        <!-- View Switcher Sub-Header -->
        <div class="mt-4 mb-2 flex w-full">
            @include('teams.partials.view-switcher')
        </div>

        <!-- Action Buttons Row -->
        <div class="flex items-center gap-3 shrink-0 mt-2 border-t border-gray-100 dark:border-gray-800 pt-3">
            @include('teams.partials.header-actions')
        </div>
    </x-slot>
    <div class="space-y-4">
        <!-- Filters and Search Bar -->
        <div
            class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm transition-all">
            <form action="{{ route('teams.tasks.index', $team) }}" method="GET"
                class="flex flex-wrap items-center gap-4">
                <!-- Search -->
                <div class="flex-1 min-w-[280px] flex gap-2">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="{{ __('tasks.search') }}..."
                            class="w-full pl-10 pr-4 py-2 {{ request('search') ? 'bg-violet-50 dark:bg-violet-900/20 ring-2 ring-violet-500/30' : 'bg-gray-50 dark:bg-gray-800' }} border-none rounded-xl text-sm focus:ring-2 focus:ring-violet-500/50 dark:text-white transition-all">
                    </div>
                    <button type="submit" 
                        class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-xl shadow-sm transition-all flex items-center justify-center lg:hidden"
                        title="{{ __('tasks.search') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </div>

                <!-- Status Filter -->
                <div class="w-40">
                    <select name="status" onchange="this.form.submit()"
                        class="w-full {{ request('status') ? 'bg-violet-50 dark:bg-violet-900/20 ring-2 ring-violet-500/30 text-violet-700 dark:text-violet-300' : 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400' }} border-none rounded-xl text-xs font-bold uppercase tracking-wider py-2 pr-10 focus:ring-2 focus:ring-violet-500/50 cursor-pointer transition-all">
                        <option value="">{{ __('tasks.status') }}</option>
                        @foreach (['pending', 'in_progress', 'completed', 'cancelled', 'blocked'] as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                                {{ __("tasks.statuses.{$status}") }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Priority Filter -->
                <div class="w-40">
                    <select name="priority" onchange="this.form.submit()"
                        class="w-full {{ request('priority') ? 'bg-violet-50 dark:bg-violet-900/20 ring-2 ring-violet-500/30 text-violet-700 dark:text-violet-300' : 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400' }} border-none rounded-xl text-xs font-bold uppercase tracking-wider py-2 pr-10 focus:ring-2 focus:ring-violet-500/50 cursor-pointer transition-all">
                        <option value="">{{ __('tasks.priority') }}</option>
                        @foreach (['low', 'medium', 'high', 'critical'] as $priority)
                            <option value="{{ $priority }}"
                                {{ request('priority') === $priority ? 'selected' : '' }}>
                                {{ __("tasks.priorities.{$priority}") }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Assigned To Filter -->
                <div class="w-48">
                    <select name="assigned_to" onchange="this.form.submit()"
                        class="w-full {{ request('assigned_to') ? 'bg-violet-50 dark:bg-violet-900/20 ring-2 ring-violet-500/30 text-violet-700 dark:text-violet-300' : 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400' }} border-none rounded-xl text-xs font-bold uppercase tracking-wider py-2 pr-10 focus:ring-2 focus:ring-violet-500/50 cursor-pointer transition-all">
                        <option value="">{{ __('tasks.assigned_to') }}</option>
                        @foreach ($members as $member)
                            <option value="{{ $member->id }}"
                                {{ request('assigned_to') == $member->id ? 'selected' : '' }}>
                                {{ $member->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Skill Filter -->
                <div class="w-48">
                    <select name="skill_id" onchange="this.form.submit()"
                        class="w-full {{ request('skill_id') ? 'bg-violet-50 dark:bg-violet-900/20 ring-2 ring-violet-500/30 text-violet-700 dark:text-violet-300' : 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400' }} border-none rounded-xl text-xs font-bold uppercase tracking-wider py-2 pr-10 focus:ring-2 focus:ring-violet-500/50 cursor-pointer transition-all">
                        <option value="">{{ __('tasks.skill') ?? 'Especialidad' }}</option>
                        @foreach($skills as $skill)
                            <option value="{{ $skill->id }}" {{ request('skill_id') == $skill->id ? 'selected' : '' }}>{{ $skill->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Type Filter -->
                <div class="w-40">
                    <select name="type" onchange="this.form.submit()"
                        class="w-full {{ request('type') ? 'bg-violet-50 dark:bg-violet-900/20 ring-2 ring-violet-500/30 text-violet-700 dark:text-violet-300' : 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400' }} border-none rounded-xl text-xs font-bold uppercase tracking-wider py-2 pr-10 focus:ring-2 focus:ring-violet-500/50 cursor-pointer transition-all">
                        <option value="">{{ __('tasks.type') }}</option>
                        <option value="template" {{ request('type') === 'template' ? 'selected' : '' }}>
                            {{ __('tasks.template') }}</option>
                        <option value="instance" {{ request('type') === 'instance' ? 'selected' : '' }}>
                            {{ __('tasks.subtask') }}</option>
                        <option value="plain" {{ request('type') === 'plain' ? 'selected' : '' }}>
                            {{ __('tasks.task') }}</option>
                    </select>
                </div>

                <!-- Per Page -->
                <div class="w-32">
                    <select name="per_page" onchange="this.form.submit()"
                        class="w-full {{ request('per_page') ? 'bg-violet-50 dark:bg-violet-900/20 ring-2 ring-violet-500/30 text-violet-700 dark:text-violet-300' : 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400' }} border-none rounded-xl text-xs font-bold uppercase tracking-wider py-2 pr-10 focus:ring-2 focus:ring-violet-500/50 cursor-pointer transition-all">
                        <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 {{ __('tasks.per_page') ?? 'por pág.' }}</option>
                        <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 {{ __('tasks.per_page') ?? 'por pág.' }}</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 {{ __('tasks.per_page') ?? 'por pág.' }}</option>
                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100 {{ __('tasks.per_page') ?? 'por pág.' }}</option>
                        <option value="all" {{ request('per_page') == 'all' ? 'selected' : '' }}>{{ __('tasks.all_tasks') ?? 'Todas' }}</option>
                    </select>
                </div>

                @if (request()->anyFilled(['search', 'status', 'priority', 'assigned_to', 'type', 'per_page']))
                    <a href="{{ route('teams.tasks.index', $team) }}"
                        class="text-xs font-bold text-red-500 hover:text-red-600 transition-colors uppercase tracking-widest">
                        {{ __('tasks.clear_filters') }}
                    </a>
                @endif
            </form>
        </div>

        <div
            class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-xl rounded-2xl overflow-hidden transition-all">
            <div id="bulkActionBar"
                class="hidden bg-white/95 dark:bg-gray-900/95 backdrop-blur-md border-b-2 border-violet-500 p-4 sticky top-0 z-[40] items-center justify-between gap-6 transition-all animate-in slide-in-from-top duration-500 shadow-2xl shadow-indigo-500/10">
                
                <div class="flex items-center gap-6">
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <div class="w-10 h-10 bg-violet-600 rounded-2xl shadow-lg shadow-violet-500/30 flex items-center justify-center text-white rotate-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="absolute -top-1 -right-1 flex h-4 w-4">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-violet-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-4 w-4 bg-violet-500 text-[8px] font-black text-white items-center justify-center" id="selectedCount">0</span>
                            </span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-violet-600 dark:text-violet-400">Acción Masiva</span>
                            <span class="text-xs font-bold text-gray-500 dark:text-gray-400">Tareas seleccionadas</span>
                        </div>
                    </div>

                    <div class="h-10 w-px bg-gray-100 dark:bg-gray-800 hidden sm:block"></div>

                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Bulk Status -->
                        <div class="relative group">
                            <select onchange="applyBulkUpdate('status', this.value)" 
                                class="appearance-none bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 hover:border-violet-300 dark:hover:border-violet-500/50 rounded-xl text-[10px] font-black uppercase tracking-widest text-gray-600 dark:text-gray-300 py-2 pl-4 pr-10 focus:ring-2 focus:ring-violet-500/20 cursor-pointer transition-all min-w-[140px]">
                                <option value="">🎯 Estado</option>
                                @foreach (['pending' => 'Pendiente', 'in_progress' => 'En Progreso', 'completed' => 'Completada', 'blocked' => 'Bloqueada'] as $val => $label)
                                    <option value="{{ $val }}" class="text-gray-900 dark:text-white">{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>

                        <!-- Bulk Priority -->
                        <div class="relative group">
                            <select onchange="applyBulkUpdate('priority', this.value)" 
                                class="appearance-none bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 hover:border-violet-300 dark:hover:border-violet-500/50 rounded-xl text-[10px] font-black uppercase tracking-widest text-gray-600 dark:text-gray-300 py-2 pl-4 pr-10 focus:ring-2 focus:ring-violet-500/20 cursor-pointer transition-all min-w-[140px]">
                                <option value="">⚡ Prioridad</option>
                                @foreach (['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta', 'critical' => 'Crítica'] as $val => $label)
                                    <option value="{{ $val }}" class="text-gray-900 dark:text-white">{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>

                        <!-- Bulk Assignee -->
                        <div class="relative group">
                            <select onchange="applyBulkUpdate('assigned_user_id', this.value)" 
                                class="appearance-none bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 hover:border-violet-300 dark:hover:border-violet-500/50 rounded-xl text-[10px] font-black uppercase tracking-widest text-gray-600 dark:text-gray-300 py-2 pl-4 pr-10 focus:ring-2 focus:ring-violet-500/20 cursor-pointer transition-all min-w-[140px]">
                                <option value="">👤 Responsable</option>
                                @foreach ($members as $member)
                                    <option value="{{ $member->id }}" class="text-gray-900 dark:text-white">{{ $member->name }}</option>
                                @endforeach
                            </select>
                            <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" onclick="confirmBulkDelete()"
                        class="px-5 py-2.5 bg-red-50 hover:bg-red-500 dark:bg-red-900/10 dark:hover:bg-red-600 text-red-600 dark:text-red-400 hover:text-white dark:hover:text-white text-[10px] font-black uppercase tracking-widest rounded-xl transition-all shadow-sm active:scale-95 flex items-center gap-2 border border-red-100 dark:border-red-900/50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Eliminar selección
                    </button>
                    
                    <div class="h-6 w-px bg-gray-100 dark:bg-gray-800"></div>

                    <button type="button" onclick="deselectAll()"
                        class="group p-2.5 bg-gray-50 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-400 hover:text-gray-900 dark:hover:text-white rounded-xl transition-all shadow-sm border border-gray-100 dark:border-gray-700 flex items-center justify-center" title="Deseleccionar todo">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:rotate-90 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
            <div class="overflow-x-auto min-h-[200px]">
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
                        @foreach ($tasks as $task)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors group cursor-pointer"
                                data-href="{{ route('teams.tasks.show', [$team, $task]) }}"
                                onclick="if(!event.target.closest('button, a, input, select')) window.location=this.dataset.href">
                                <td class="px-4 py-4 w-10 text-center" onclick="event.stopPropagation()">
                                    <input type="checkbox" value="{{ $task->id }}"
                                        class="task-checkbox rounded border-gray-300 text-red-600 shadow-sm focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 cursor-pointer transition-colors"
                                        onchange="updateSelectedCount()">
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-2 h-2 rounded-full {{ $task->status === 'completed' ? 'bg-emerald-500' : ($task->status === 'blocked' ? 'bg-red-500' : 'bg-violet-500') }} shrink-0">
                                        </div>

                                        @if ($task->children->isNotEmpty())
                                            <button type="button"
                                                onclick="event.stopPropagation(); toggleSubtasks({{ $task->id }}, this)"
                                                class="toggle-subtasks p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded transition-all mr-1"
                                                data-id="{{ $task->id }}">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="h-3 w-3 transform transition-transform" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </button>
                                        @else
                                            <div class="w-5 mr-1"></div>
                                        @endif
                                        <div class="min-w-0">
                                            <a href="{{ route('teams.tasks.show', [$team, $task]) }}"
                                                class="text-sm font-semibold text-gray-900 dark:text-white hover:text-violet-600 dark:hover:text-violet-400 transition-all truncate block max-w-[140px] sm:max-w-xs md:max-w-md lg:max-w-lg"
                                                title="{{ $task->title }}">
                                                {{ $task->title }}
                                            </a>
                                            @if ($task->visibility === 'private')
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
                                            @else
                                                <span
                                                    class="ml-2 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-tighter bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 rounded shadow-sm inline-flex items-center"
                                                    title="{{ __('tasks.public') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="h-2.5 w-2.5 mr-0.5" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="3"
                                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                    </svg>
                                                    {{ __('tasks.public') }}
                                                </span>
                                            @endif
                                            @if ($task->is_template)
                                                <span
                                                    class="ml-2 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-tighter bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300 rounded shadow-sm inline-flex items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                                    </svg>
                                                    {{ __('tasks.plan_master') }}
                                                </span>
                                            @endif

                                            @if ($task->assigned_user_id === auth()->id() && $task->parent_id)
                                                <span
                                                    class="ml-2 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-tighter bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 rounded shadow-sm inline-flex items-center">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                    </svg>
                                                    {{ __('tasks.your_execution') }}
                                                </span>
                                            @elseif ($task->isInstance())
                                                <div class="flex items-center gap-1.5 mt-1">
                                                    <span
                                                        class="px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-tighter bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-500/20 rounded-md shadow-sm">
                                                        ↳ {{ __('tasks.subtask') }}
                                                    </span>
                                                    @if ($task->parent)
                                                        <span
                                                            class="text-[10px] text-gray-400 dark:text-gray-500 font-medium truncate max-w-[150px]">
                                                            {{ $task->parent->title }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    @php
                                        $statusClasses = [
                                            'completed'   => 'bg-emerald-50 border-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400',
                                            'in_progress' => 'bg-blue-50 border-blue-100 text-blue-700 dark:bg-blue-500/10 dark:border-blue-500/20 dark:text-blue-400',
                                            'blocked'     => 'bg-red-50 border-red-100 text-red-700 dark:bg-red-500/10 dark:border-red-500/20 dark:text-red-400',
                                            'default'     => 'bg-gray-50 border-gray-100 text-gray-600 dark:bg-gray-500/10 dark:border-gray-500/20 dark:text-gray-400'
                                        ];
                                        $currentClass = $statusClasses[$task->status] ?? $statusClasses['default'];
                                    @endphp
                                    <span class="px-2.5 py-1 text-[11px] font-bold rounded-lg border {{ $currentClass }} uppercase">
                                        {{ __("tasks.statuses.{$task->status}") }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-xs whitespace-nowrap">
                                    {{ __("tasks.priorities.{$task->priority}") }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap hidden lg:table-cell">
                                    <div class="flex items-center gap-2">
                                        <div class="w-5 h-5 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-[8px] font-bold text-gray-500">
                                            {{ strtoupper(substr($task->creator?->name ?? '?', 0, 2)) }}
                                        </div>
                                        <span class="text-xs text-gray-600 dark:text-gray-400">{{ $task->creator?->name ?? '—' }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    @if($task->assignedUser)
                                        <div class="flex items-center gap-2">
                                            <div class="w-5 h-5 rounded-full bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center text-[8px] font-bold text-violet-600 dark:text-violet-400">
                                                {{ strtoupper(substr($task->assignedUser->name, 0, 2)) }}
                                            </div>
                                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $task->assignedUser->name }}</span>
                                        </div>
                                    @else
                                        <div class="flex items-center gap-2 opacity-75">
                                            <div class="w-5 h-5 rounded-full bg-gray-50 dark:bg-gray-800/50 flex items-center justify-center text-[8px] font-bold text-gray-400 border border-gray-100 dark:border-gray-700">
                                                {{ strtoupper(substr($task->creator?->name ?? '?', 0, 2)) }}
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-[11px] text-gray-500 dark:text-gray-400">{{ $task->creator?->name ?? '—' }}</span>
                                                <span class="text-[8px] font-black uppercase tracking-widest text-violet-500/70 dark:text-violet-400/50">
                                                    {{ $task->is_template ? (__('tasks.template') ?? 'Plantilla') : (__('tasks.owner_short') ?? 'Prop.') }}
                                                </span>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap hidden xl:table-cell">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="flex-1 w-20 h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden shadow-inner border border-gray-200 dark:border-gray-700">
                                            <div class="h-full bg-gradient-to-r from-violet-500 to-indigo-600 shadow-sm"
                                                style="width: {{ $task->progress }}%"></div>
                                        </div>
                                        <span
                                            class="text-[10px] font-bold text-gray-400 dark:text-gray-500 w-6">{{ $task->progress }}%</span>
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap hidden md:table-cell">
                                    <span
                                        class="text-xs text-gray-500">{{ $task->due_date ? $task->due_date->format('d/m/y') : '—' }}</span>
                                </td>
                                <td class="px-4 py-4 text-right whitespace-nowrap min-w-[124px]">
                                    <div
                                        class="flex items-center justify-end gap-1 transition-opacity">
                                        @include('tasks.partials.task-timer-button', ['task' => $task, 'size' => 'xs'])
                                        @can('update', $task)
                                            <a href="{{ route('teams.tasks.edit', [$team, $task]) }}"
                                                class="p-1.5 text-gray-400 hover:text-blue-500 transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </a>
                                            <button type="button"
                                                onclick="event.stopPropagation(); confirmDeleteTask({{ $task->id }}, '{{ addslashes($task->title) }}')"
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
                                $maxProgress = $task->children->max('progress_percentage');
                            @endphp
                            @foreach ($task->children as $subtask)
                                <tr class="subtask-row hidden bg-gray-50/50 dark:bg-gray-800/20 transition-colors group cursor-pointer border-b border-gray-100 dark:border-gray-800/40"
                                    data-parent="{{ $task->id }}"
                                    onclick="if(!event.target.closest('button, a, input, select')) window.location='{{ route('teams.tasks.show', [$team, $subtask]) }}'">
                                    <td class="px-4 py-3 w-10 text-center" onclick="event.stopPropagation()">
                                        <input type="checkbox" value="{{ $subtask->id }}"
                                            class="task-checkbox rounded border-gray-300 text-red-600 shadow-sm focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 cursor-pointer transition-colors"
                                            onchange="updateSelectedCount()">
                                    </td>
                                    <td class="px-6 py-3 pl-16">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-1.5 h-1.5 rounded-full {{ $subtask->status === 'completed' ? 'bg-emerald-500' : 'bg-gray-400' }} shrink-0">
                                            </div>
                                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                                {{ $subtask->title }}
                                            </span>
                                            @if($maxProgress > 0 && $subtask->progress_percentage === $maxProgress)
                                                <span class="px-1 py-0.5 rounded text-[8px] font-black uppercase tracking-tighter bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 border border-amber-200 dark:border-amber-800/50 flex items-center gap-0.5 animate-pulse" title="{{ __('tasks.leading_progress') ?? 'Máximo progreso' }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                    TOP
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span
                                            class="px-1.5 py-0.5 text-[9px] font-bold rounded-md bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 uppercase tracking-tight">
                                            {{ __("tasks.statuses.{$subtask->status}") }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-[10px] text-gray-400 whitespace-nowrap flex items-center gap-1.5">
                                        {{ __("tasks.priorities.{$subtask->priority}") }}
                                        @if($subtask->priority !== $task->priority)
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
                                        {{ $subtask->assignedUser?->name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap hidden xl:table-cell">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="flex-1 w-16 h-1 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden shadow-inner border border-gray-200 dark:border-gray-700">
                                                <div class="h-full bg-gradient-to-r from-violet-500 to-indigo-600 shadow-sm"
                                                    style="width: {{ $subtask->progress }}%"></div>
                                            </div>
                                            <span
                                                class="text-[9px] font-bold text-gray-400 dark:text-gray-500 w-5">{{ $subtask->progress }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-[10px] text-gray-400 whitespace-nowrap hidden md:table-cell">
                                        {{ $subtask->due_date ? $subtask->due_date->format('d/m/y') : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap min-w-[124px]">
                                        <div
                                            class="flex items-center justify-end gap-1 transition-opacity">
                                            @include('tasks.partials.task-timer-button', ['task' => $subtask, 'size' => 'xs'])
                                            <a href="{{ route('teams.tasks.show', [$team, $subtask]) }}"
                                                class="p-1 text-gray-400 hover:text-violet-400 transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </a>
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
                        @if ($tasks->isEmpty())
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center">
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
                                        <a href="{{ route('teams.tasks.create', $team) }}"
                                            class="mt-2 text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline">
                                            {{ __('tasks.create') }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            @if ($tasks->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50/30 dark:bg-transparent">
                    {{ $tasks->links() }}
                </div>
            @endif
        </div>

        @push('scripts')
            <script>
                function toggleAll(source) {
                    const checkboxes = document.querySelectorAll('.task-checkbox');
                    checkboxes.forEach(cb => cb.checked = source.checked);
                    updateSelectedCount();
                }

                function updateSelectedCount() {
                    const selected = document.querySelectorAll('.task-checkbox:checked').length;
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
                    const selected = document.querySelectorAll('.task-checkbox:checked');
                    if (selected.length === 0) return;

                    Swal.fire({
                        title: '¿Eliminar selección?',
                        text: `Estás a punto de eliminar ${selected.length} tareas. Esta acción no se puede deshacer.`,
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
                    
                    const selected = document.querySelectorAll('.task-checkbox:checked');
                    if (selected.length === 0) return;

                    const fieldLabels = {
                        'status': 'Estado',
                        'priority': 'Prioridad',
                        'assigned_user_id': 'Responsable'
                    };

                    Swal.fire({
                        title: `¿Cambiar ${fieldLabels[field]}?`,
                        text: `Vas a actualizar ${selected.length} tareas seleccionadas.`,
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

                function confirmDeleteTask(taskId, taskTitle) {
                    Swal.fire({
                        title: '¿Eliminar tarea?',
                        text: `¿Estás seguro de que quieres eliminar la tarea "${taskTitle}"? Esta acción no se puede deshacer y su progreso dejará de sumar.`,
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
                            form.action = `{{ url('/teams/' . $team->id . '/tasks') }}/${taskId}`;
                            form.submit();
                        }
                    });
                }

                function toggleSubtasks(taskId, button) {
                    const subtasks = document.querySelectorAll(`.subtask-row[data-parent="${taskId}"]`);
                    const icon = button.querySelector('svg');

                    subtasks.forEach(st => {
                        st.classList.toggle('hidden');
                    });

                    icon.classList.toggle('rotate-90');
                }

                // Document event listener for data-href rows if any left (though we used inline onclick)
                document.addEventListener('DOMContentLoaded', function() {
                    // inline onclick already handles row clicks
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
                        text: 'Se eliminarán PERMANENTEMENTE todas las tareas de este equipo que estén en la papelera, junto con sus historiales y archivos. Esta acción no se puede deshacer.',
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

        <form id="bulkDeleteForm" action="{{ route('teams.tasks.bulk-delete', $team) }}" method="POST"
            class="hidden">
            @csrf
            @method('DELETE')
            <div id="bulkDeleteInputs"></div>
        </form>

        <form id="bulkUpdateForm" action="{{ route('teams.tasks.bulk-update', $team) }}" method="POST"
            class="hidden">
            @csrf
            @method('PATCH')
            <div id="bulkUpdateInputs"></div>
        </form>

        <form id="purgeTrashForm" action="{{ route('teams.tasks.purge-trash', $team) }}" method="POST" class="hidden">
            @csrf
        </form>
</x-app-layout>
