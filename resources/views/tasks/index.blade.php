<x-app-layout>
    @section('title', __('navigation.task_list') . ' — ' . $team->name)

    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 overflow-hidden">
            <div class="flex items-start gap-3 min-w-0 flex-1">
                <a href="{{ route('teams.dashboard', $team) }}"
                    class="mt-1 p-2 bg-gray-50 dark:bg-gray-800 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-xl transition-all shadow-sm shrink-0"
                    title="{{ __('navigation.back') ?? 'Volver' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div class="min-w-0 flex-1">
                    @include('teams.partials.breadcrumb')
                    <h1 class="text-2xl font-black text-gray-900 dark:text-white heading truncate select-none">
                        {{ __('navigation.task_list') }}
                    </h1>
                </div>
            </div>

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
                <div class="flex-1 min-w-[200px] relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="{{ __('tasks.search') }}..."
                        class="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-800 border-none rounded-xl text-sm focus:ring-2 focus:ring-violet-500/50 dark:text-white transition-all">
                </div>

                <!-- Status Filter -->
                <div class="w-40">
                    <select name="status" onchange="this.form.submit()"
                        class="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-xl text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-400 py-2 focus:ring-2 focus:ring-violet-500/50 cursor-pointer">
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
                        class="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-xl text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-400 py-2 focus:ring-2 focus:ring-violet-500/50 cursor-pointer">
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
                        class="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-xl text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-400 py-2 focus:ring-2 focus:ring-violet-500/50 cursor-pointer">
                        <option value="">{{ __('tasks.assigned_to') }}</option>
                        @foreach ($members as $member)
                            <option value="{{ $member->id }}"
                                {{ request('assigned_to') == $member->id ? 'selected' : '' }}>
                                {{ $member->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Type Filter -->
                <div class="w-40">
                    <select name="type" onchange="this.form.submit()"
                        class="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-xl text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-400 py-2 focus:ring-2 focus:ring-violet-500/50 cursor-pointer">
                        <option value="">{{ __('tasks.type') }}</option>
                        <option value="template" {{ request('type') === 'template' ? 'selected' : '' }}>
                            {{ __('tasks.template') }}</option>
                        <option value="instance" {{ request('type') === 'instance' ? 'selected' : '' }}>
                            {{ __('tasks.subtask') }}</option>
                        <option value="plain" {{ request('type') === 'plain' ? 'selected' : '' }}>
                            {{ __('tasks.task') }}</option>
                    </select>
                </div>

                @if (request()->anyFilled(['search', 'status', 'priority', 'assigned_to', 'type']))
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
                class="hidden bg-red-50 dark:bg-red-900/20 border-b border-red-100 dark:border-red-900/50 p-3 flex justify-between items-center transition-all animate-fade-in">
                <span class="text-sm font-bold text-red-600 dark:text-red-400 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span><span id="selectedCount">0</span> tareas seleccionadas</span>
                </span>
                <button type="button" onclick="confirmBulkDelete()"
                    class="px-4 py-1.5 bg-red-500 text-white text-xs font-bold uppercase tracking-wider rounded-lg shadow-sm hover:bg-red-600 transition focus:ring focus:ring-red-500/30">
                    Eliminar Selección
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
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
                            <th class="px-6 py-4">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'direction' => request('sort') == 'status' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                                    class="group flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                    {{ __('tasks.status') }}
                                    <x-sort-icon column="status" />
                                </a>
                            </th>
                            <th class="px-6 py-4">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'priority', 'direction' => request('sort') == 'priority' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                                    class="group flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                    {{ __('tasks.priority') }} / {{ __('tasks.urgency') }}
                                    <x-sort-icon column="priority" />
                                </a>
                            </th>
                            <th
                                class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('tasks.owner') }}
                            </th>
                            <th
                                class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ __('tasks.assigned_to') }}
                            </th>
                            <th class="px-6 py-4">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'progress_percentage', 'direction' => request('sort') == 'progress_percentage' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                                    class="group flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                    {{ __('tasks.progress') }}
                                    <x-sort-icon column="progress_percentage" />
                                </a>
                            </th>
                            <th class="px-6 py-4">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'due_date', 'direction' => request('sort') == 'due_date' && request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                                    class="group flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                    {{ __('tasks.due_date') }}
                                    <x-sort-icon column="due_date" />
                                </a>
                            </th>
                            <th
                                class="px-6 py-4 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 text-right">
                                {{ __('tasks.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($tasks as $task)
                            @if ($task->parent_id && $tasks->contains('id', $task->parent_id))
                                @continue
                            @endif

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
                                        <div>
                                            <a href="{{ route('teams.tasks.show', [$team, $task]) }}"
                                                class="text-sm font-semibold text-gray-900 dark:text-white hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                                {{ $task->title }}
                                            </a>
                                            @if ($task->visibility === 'private')
                                                <span
                                                    class="ml-2 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-tighter bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 rounded relative z-30"
                                                    title="{{ __('tasks.private') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="h-2.5 w-2.5 inline mr-0.5 mb-0.5" fill="none"
                                                        viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="3"
                                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                    </svg>
                                                    {{ __('tasks.private') }}
                                                </span>
                                            @else
                                                <span
                                                    class="ml-2 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-tighter bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 rounded relative z-30"
                                                    title="{{ __('tasks.public') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="h-2.5 w-2.5 inline mr-0.5 mb-0.5" fill="none"
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
                                                    class="ml-2 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-tighter bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300 rounded relative z-30">{{ __('tasks.template') }}</span>
                                            @elseif ($task->isInstance())
                                                <div class="flex items-center gap-1.5 mt-1 relative z-30">
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
                                <td class="px-6 py-4">
                                    <span
                                        class="px-2.5 py-1 text-[11px] font-bold rounded-lg border 
                                    @if ($task->status === 'completed') bg-emerald-50 border-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400
                                    @elseif($task->status === 'in_progress') bg-blue-50 border-blue-100 text-blue-700 dark:bg-blue-500/10 dark:border-blue-500/20 dark:text-blue-400
                                    @elseif($task->status === 'blocked') bg-red-50 border-red-100 text-red-700 dark:bg-red-500/10 dark:border-red-500/20 dark:text-red-400
                                    @else bg-gray-50 border-gray-100 text-gray-600 dark:bg-gray-500/10 dark:border-gray-500/20 dark:text-gray-400 @endif uppercase">
                                        {{ __("tasks.statuses.{$task->status}") }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    {{ __("tasks.priorities.{$task->priority}") }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ $task->creator?->name ?? '—' }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ $task->assignedUser?->name ?? __('tasks.unassigned') }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="flex-1 w-20 h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden shadow-inner border border-gray-200 dark:border-gray-700">
                                            <div class="h-full bg-gradient-to-r from-violet-500 to-indigo-600 transition-all shadow-sm"
                                                style="width: {{ $task->progress }}%"></div>
                                        </div>
                                        <span
                                            class="text-[10px] font-bold text-gray-400 dark:text-gray-500 w-6">{{ $task->progress }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="text-xs text-gray-500">{{ $task->due_date ? $task->due_date->format('d/m/y') : '—' }}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div
                                        class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
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
                                        </div>
                                    </td>
                                    <td class="px-6 py-3">
                                        <span
                                            class="px-1.5 py-0.5 text-[9px] font-bold rounded-md bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 uppercase tracking-tight">
                                            {{ __("tasks.statuses.{$subtask->status}") }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-[10px] text-gray-400">
                                        {{ __("tasks.priorities.{$subtask->priority}") }}
                                    </td>
                                    <td class="px-6 py-3 text-[10px] text-gray-400">
                                        {{ $subtask->creator?->name ?? '—' }}
                                    </td>
                                    <td class="px-6 py-3 text-[10px] text-gray-400">
                                        {{ $subtask->assignedUser?->name ?? '—' }}
                                    </td>
                                    <td class="px-6 py-3">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="flex-1 w-16 h-1 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden shadow-inner border border-gray-200 dark:border-gray-700">
                                                <div class="h-full bg-gradient-to-r from-violet-500 to-indigo-600 transition-all shadow-sm"
                                                    style="width: {{ $subtask->progress }}%"></div>
                                            </div>
                                            <span
                                                class="text-[9px] font-bold text-gray-400 dark:text-gray-500 w-5">{{ $subtask->progress }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3 text-[10px] text-gray-400">
                                        {{ $subtask->due_date ? $subtask->due_date->format('d/m/y') : '—' }}
                                    </td>
                                    <td class="px-6 py-3 text-right">
                                        <div
                                            class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
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
                    // This is extra protection, but inline onclick is already handling it
                });
            </script>
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
</x-app-layout>
