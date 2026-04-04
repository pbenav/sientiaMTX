<x-app-layout>
    @section('title', $task->title)

    <x-slot name="header">
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-6">
            <div class="flex items-start gap-4 min-w-0 flex-1">
                <a href="{{ $backUrl ?? route('teams.dashboard', $team) }}"
                    class="mt-1 p-2.5 bg-gray-50 dark:bg-gray-800/50 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-2xl transition-all shadow-sm border border-gray-100 dark:border-gray-700/50 shrink-0"
                    title="{{ __('navigation.back') ?? 'Volver' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div class="min-w-0 flex-1">
                    @include('teams.partials.breadcrumb')
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight">
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
            @can('update', $task)
                <a href="{{ route('teams.tasks.edit', [$team, $task]) }}"
                    class="shrink-0 flex items-center gap-1.5 text-xs bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2.5 rounded-xl transition-all shadow-sm font-bold">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    {{ __('tasks.edit') }}
                </a>

                <form action="{{ route('google.sync_task', [$team, $task]) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                        title="{{ $task->google_task_id ? __('google.sync_tasks') : __('google.export_tasks') }}"
                        class="shrink-0 flex items-center gap-1.5 text-xs {{ $task->google_task_id ? 'bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-500/10 dark:hover:bg-indigo-500/20 border border-indigo-200 dark:border-indigo-500/20 text-indigo-600 dark:text-indigo-400' : 'bg-amber-50 hover:bg-amber-100 dark:bg-amber-500/10 dark:hover:bg-amber-500/20 border border-amber-200 dark:border-amber-500/20 text-amber-600 dark:text-amber-400' }} px-4 py-2.5 rounded-xl transition-all shadow-sm font-bold">
                        @if ($task->google_task_id)
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            {{ __('google.sync_tasks') }}
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                            {{ __('google.export_tasks') }}
                        @endif
                    </button>
                </form>
            @endcan

            @can('delete', $task)
                <form id="delete-task-form-{{ $task->id }}"
                    action="{{ route('teams.tasks.destroy', [$team, $task]) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="button"
                        onclick="confirmDelete('delete-task-form-{{ $task->id }}', '{{ __('tasks.delete_confirm') }}')"
                        class="shrink-0 flex items-center gap-1.5 text-xs bg-red-50 hover:bg-red-100 dark:bg-red-500/10 dark:hover:bg-red-500/20 border border-red-200 dark:border-red-500/20 text-red-600 dark:text-red-400 px-4 py-2.5 rounded-xl transition-all shadow-sm font-bold">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        {{ __('tasks.delete') }}
                    </button>
                </form>
            @endcan

            @include('tasks.partials.task-timer-button', ['task' => $task])

            @include('teams.partials.header-actions')
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

        $personalInstance = null;

        // Calculate Time Tracking Statistics
        $taskIds = $task->children()->pluck('id')->push($task->id);
        $allLogs = \App\Models\TimeLog::whereIn('task_id', $taskIds)->whereNotNull('end_at')->with('user')->get();
        
        $timeStats = $allLogs->groupBy('user_id')
            ->map(function ($logs) {
                $totalSeconds = $logs->sum(fn($log) => $log->start_at->diffInSeconds($log->end_at));
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
                <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
                    {{ __('tasks.name') }}</h3>
                <p class="text-xl font-bold text-gray-900 dark:text-white heading leading-tight">
                    {{ $task->title }}
                </p>
            </div>

            @php
                // Find if the current user has a personal instance of this goal (regardless of being a template or not)
                $personalInstance =
                    $task->is_template || $task->children()->exists()
                        ? $task
                            ->instances()
                            ->where('assigned_user_id', auth()->id())
                            ->first()
                        : null;
            @endphp

            @if ($task->is_template || $task->children()->exists())
                @php
                    $isRoadmap = $task->is_template;
                    $instances = $isRoadmap
                        ? $task->instances()->with('assignedUser')->get()
                        : $task->children()->with('assignedTo')->get();
                    $totalInst = $instances->count();
                    $sumProg = $instances->sum('progress_percentage');
                    $prog = $totalInst > 0 ? $sumProg / $totalInst : 0;
                    $doneInst = $instances->where('status', 'completed')->count();
                    $hasBlocker = $instances->where('status', 'blocked')->isNotEmpty();
                @endphp

                <!-- Progress Dashboard -->
                <div
                    class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm dark:shadow-none transition-colors">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3
                                class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">
                                {{ $isRoadmap ? __('tasks.roadmap_progress') : __('tasks.status') }}</h3>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white heading">
                                {{ $doneInst }}/{{ $totalInst }} <span
                                    class="text-sm font-medium text-gray-400">{{ __('tasks.completed') }}</span></p>
                        </div>
                        <div class="text-right min-w-[4rem]">
                            <span id="global-progress-val"
                                class="text-2xl font-black text-violet-600 dark:text-violet-400 heading"
                                style="transition: none !important;">{{ round($prog) }}%</span>
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

                    <div class="overflow-hidden border border-gray-100 dark:border-gray-800 rounded-xl">
                        <table class="w-full text-left text-sm">
                            <thead
                                class="bg-gray-50 dark:bg-gray-800/50 text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">
                                <tr>
                                    <th class="px-4 py-3">{{ __('teams.members') }}</th>
                                    <th class="px-4 py-3">{{ __('tasks.status') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('tasks.time_spent') ?? 'Tiempo' }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('tasks.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                                @php
                                    // Eager load time logs for instances to avoid N+1
                                    $instances->load('timeLogs');
                                @endphp
                                @foreach ($instances as $inst)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors cursor-pointer group" onclick="if(!event.target.closest('button|select')) window.location='{{ route('teams.tasks.show', [$team->id, $inst->id]) }}'">
                                        <td class="px-4 py-3 group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors" onclick="event.stopPropagation()">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-8 h-8 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 flex items-center justify-center text-[10px] font-bold text-gray-600 dark:text-gray-400 shadow-inner">
                                                    @if ($inst->assignedUser)
                                                        {{ strtoupper(substr($inst->assignedUser->name, 0, 2)) }}
                                                    @elseif($inst->assignedTo->count() > 0)
                                                        {{ strtoupper(substr($inst->assignedTo->first()->name, 0, 2)) }}
                                                    @else
                                                        ?
                                                    @endif
                                                </div>
                                                @if ($team->isCoordinator(auth()->user()))
                                                    <select onchange="reassignTask({{ $inst->id }}, this.value)" class="text-xs bg-transparent border border-transparent hover:border-gray-200 dark:hover:border-gray-700 rounded-lg focus:ring-0 cursor-pointer font-medium text-gray-700 dark:text-gray-300 px-2 py-1 -ml-2 transition-colors">
                                                        <option value="unassign">{{ __('tasks.unassigned') ?? 'Sin asignar' }}</option>
                                                        @foreach($team->members as $member)
                                                            <option value="{{ $member->id }}" {{ $inst->assigned_user_id === $member->id ? 'selected' : '' }}>
                                                                {{ $member->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <span class="font-medium text-gray-700 dark:text-gray-300">
                                                        @if ($inst->assignedUser)
                                                            {{ $inst->assignedUser->name }}
                                                        @elseif($inst->assignedTo->count() > 0)
                                                            {{ $inst->assignedTo->pluck('name')->join(', ') }}
                                                        @else
                                                            {{ __('tasks.unassigned') ?? 'Sin asignar' }}
                                                        @endif
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            @php
                                                $instStatusColor = match ($inst->status) {
                                                    'completed' => 'text-emerald-500 dark:text-emerald-400',
                                                    'in_progress' => 'text-blue-500 dark:text-blue-400',
                                                    'blocked' => 'text-red-600 dark:text-red-400 font-bold',
                                                    default => 'text-gray-500 dark:text-gray-400',
                                                };
                                            @endphp
                                            <div class="flex items-center gap-1.5 {{ $instStatusColor }}">
                                                <div
                                                    class="w-1.5 h-1.5 rounded-full {{ str_contains($instStatusColor, 'text-') ? str_replace('text-', 'bg-', explode(' ', $instStatusColor)[0]) : 'bg-gray-400' }}">
                                                </div>
                                                <span
                                                    class="text-xs font-bold uppercase tracking-tight">{{ __('tasks.statuses.' . $inst->status) }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            @php
                                                $instSeconds = (int) $inst->timeLogs->whereNotNull('end_at')->sum(fn($l) => $l->start_at->diffInSeconds($l->end_at));
                                                $instFormatted = (floor($instSeconds / 3600) > 0 ? floor($instSeconds / 3600) . "h " : "") . floor(($instSeconds % 3600) / 60) . "m";
                                            @endphp
                                            <span class="text-xs font-black text-gray-900 dark:text-white tabular-nums">{{ $instSeconds > 0 ? $instFormatted : '—' }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            @if ($inst->status !== 'completed' && $team->isCoordinator(auth()->user()))
                                                <button onclick="event.stopPropagation(); nudgeUser({{ $inst->id }})"
                                                    class="p-2 text-violet-600 hover:bg-violet-50 dark:text-violet-400 dark:hover:bg-violet-400/10 rounded-lg transition-all"
                                                    title="{{ __('tasks.nudge_user') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                        stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                                    </svg>
                                                    @if ($inst->nudge_count > 0)
                                                        <span class="ml-1 text-[10px] font-bold px-1.5 py-0.5 bg-violet-100 dark:bg-violet-900/50 rounded-full">{{ $inst->nudge_count }}</span>
                                                    @endif
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
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
                                    {{ __('tasks.personal_instance_notice_others', ['name' => $task->assignedUser->name ?? 'User']) }}
                                @endif
                                
                                @if ($team->isCoordinator(auth()->user()))
                                    <div class="inline-block relative">
                                        <select onchange="reassignTask({{ $task->id }}, this.value)" class="text-xs bg-white dark:bg-indigo-900 border border-indigo-200 dark:border-indigo-700 hover:border-indigo-300 rounded-lg ml-2 px-2 py-1 shadow-sm font-bold text-indigo-700 dark:text-indigo-300 cursor-pointer">
                                            <option value="" disabled {{ !$task->assigned_user_id ? 'selected' : '' }}>{{ __('Reasignar a...') }}</option>
                                            <option value="unassign">-- {{ __('Eliminar asignación') }} --</option>
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
            @if ($task->description)
                <div
                    class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm dark:shadow-none transition-colors">
                    <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">
                        {{ __('tasks.description') }}</h3>
                    <div
                        class="text-sm text-gray-700 dark:text-gray-300 prose dark:prose-invert max-w-none prose-sm leading-relaxed">
                        {!! str($task->description)->markdown() !!}
                    </div>
                </div>
            @endif

            @if ($task->observations)
                <div
                    class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm dark:shadow-none transition-colors">
                    <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">
                        {{ __('tasks.observations') }}</h3>
                    <div
                        class="text-sm text-gray-700 dark:text-gray-300 prose dark:prose-invert max-w-none prose-sm leading-relaxed">
                        {!! str($task->observations)->markdown() !!}
                    </div>
                </div>
            @endif

            <!-- Time Statistics Card -->
            @if($totalSecondsTask > 0)
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm dark:shadow-none transition-colors">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-transparent flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="p-1.5 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-widest">{{ __('tasks.time_statistics') ?? 'Estadísticas de Tiempo' }}</h3>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-black text-indigo-600 dark:text-indigo-400">{{ $totalFormattedTask }}</span>
                            <span class="text-[10px] text-gray-400 uppercase tracking-tighter ml-1">{{ __('Total') }}</span>
                        </div>
                    </div>

                    <div class="p-6 space-y-5">
                        @foreach($timeStats as $stat)
                            @php
                                $userPerc = $totalSecondsTask > 0 ? ($stat['seconds'] / $totalSecondsTask) * 100 : 0;
                            @endphp
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-7 h-7 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-[10px] font-bold text-gray-600 dark:text-gray-400 shadow-inner">
                                            {{ strtoupper(substr($stat['user']->name ?? '?', 0, 2)) }}
                                        </div>
                                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $stat['user']->name ?? '—' }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-black text-gray-900 dark:text-white">{{ $stat['formatted'] }}</span>
                                        <span class="text-[10px] text-gray-400 tabular-nums">({{ round($userPerc) }}%)</span>
                                    </div>
                                </div>
                                <div class="w-full h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-indigo-500/60 dark:bg-indigo-400/40 rounded-full" style="width: {{ $userPerc }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

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
                            <div class="px-5 py-3 text-xs flex items-center justify-between gap-4">
                                <div class="flex items-center gap-2 min-w-0">
                                    <div
                                        class="w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-[10px] font-bold text-gray-600 dark:text-gray-400 shrink-0">
                                        {{ strtoupper(substr($h->user?->name ?? '?', 0, 2)) }}
                                    </div>
                                    <div class="truncate">
                                        <span
                                            class="font-bold text-gray-700 dark:text-gray-300">{{ $h->user?->name ?? '—' }}</span>
                                        <span class="text-gray-500 ml-1 capitalize">{{ $h->action }}</span>
                                    </div>
                                </div>
                                <span
                                    class="text-[10px] text-gray-400 shrink-0">{{ $h->created_at->diffForHumans() }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Attachments Section -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm dark:shadow-none transition-colors">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                        {{ __('tasks.attachments') }}
                    </h3>
                    <button type="button" onclick="document.getElementById('attachment-input').click()"
                        class="text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4v16m8-8H4" />
                        </svg>
                        {{ __('tasks.add_attachment') }}
                    </button>
                    <form id="attachment-form" action="{{ route('teams.tasks.attachments.upload', [$team, $task]) }}"
                        method="POST" enctype="multipart/form-data" class="hidden">
                        @csrf
                        <input type="file" id="attachment-input" name="file"
                            onchange="document.getElementById('attachment-form').submit()">
                    </form>
                </div>

                @if ($task->attachments->isEmpty() && (!$task->parent || $task->parent->attachments->isEmpty()))
                    <p class="text-xs text-gray-400 italic">{{ __('tasks.no_attachments') }}</p>
                @else
                    <div class="space-y-4">
                        @if ($task->parent && $task->parent->attachments->isNotEmpty())
                            <div>
                                <h4
                                    class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-tight mb-2 flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                    </svg>
                                    {{ __('tasks.parent_attachments') ?? 'Archivos de la Tarea Principal' }}
                                </h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 opacity-80">
                                    @foreach ($task->parent->attachments as $attachment)
                                        <div
                                            class="group flex items-center justify-between p-3 bg-indigo-50/30 dark:bg-indigo-900/10 border border-indigo-100/50 dark:border-indigo-800/20 rounded-xl hover:border-indigo-200 transition-all">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div
                                                    class="w-10 h-10 rounded-lg bg-white dark:bg-gray-800 flex items-center justify-center text-indigo-500 shadow-sm border border-indigo-50 dark:border-indigo-900/30 shrink-0">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-xs font-bold text-gray-700 dark:text-gray-300 truncate"
                                                        title="{{ $attachment->file_name }}">
                                                        {{ $attachment->file_name }}
                                                    </p>
                                                    <p class="text-[10px] text-gray-400">
                                                        {{ number_format($attachment->file_size / 1024 / 1024, 2) }} MB
                                                        • {{ __('tasks.shared') ?? 'Compartido' }}
                                                    </p>
                                                </div>
                                            </div>
                                            <a href="{{ route('teams.attachments.download', [$team, $attachment]) }}"
                                                class="p-1.5 text-gray-400 hover:text-indigo-600 transition-colors"
                                                title="{{ __('tasks.download') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                </svg>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($task->attachments->isNotEmpty())
                            <div>
                                @if ($task->parent_id)
                                    <h4
                                        class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-tight mb-2">
                                        {{ __('tasks.your_attachments') ?? 'Tus Archivos' }}
                                    </h4>
                                @endif
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    @foreach ($task->attachments as $attachment)
                                        <div
                                            class="group flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700/50 rounded-xl hover:border-violet-200 dark:hover:border-violet-800 transition-all">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div
                                                    class="w-10 h-10 rounded-lg bg-white dark:bg-gray-800 flex items-center justify-center text-violet-600 dark:text-violet-400 shadow-sm border border-gray-100 dark:border-gray-700 shrink-0">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-xs font-bold text-gray-700 dark:text-gray-300 truncate"
                                                        title="{{ $attachment->file_name }}">
                                                        {{ $attachment->file_name }}
                                                    </p>
                                                    <p class="text-[10px] text-gray-400">
                                                        {{ number_format($attachment->file_size / 1024 / 1024, 2) }} MB
                                                        •
                                                        {{ $attachment->created_at->diffForHumans() }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div
                                                class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <a href="{{ route('teams.attachments.download', [$team, $attachment]) }}"
                                                    class="p-1.5 text-gray-500 hover:text-violet-600 dark:hover:text-violet-400 transition-colors"
                                                    title="{{ __('tasks.download') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                    </svg>
                                                </a>
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
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
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
            <!-- Quick Actions -->
            @if (
                $task->assigned_user_id === auth()->id() ||
                    $task->created_by_id === auth()->id() ||
                    $team->isCoordinator(auth()->user()) ||
                    ($personalInstance && $personalInstance->assigned_user_id === auth()->id()))
                <div
                    class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 space-y-3 shadow-sm dark:shadow-none transition-colors">
                    <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-widest font-bold mb-1">
                        {{ __('tasks.actions') }}</p>

                    @if ($task->status !== 'completed')
                        <button onclick="updateTaskStatus('completed')"
                            class="w-full flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-500 text-white dark:text-white text-xs font-bold py-2.5 rounded-xl transition-all shadow-lg shadow-emerald-600/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ __('tasks.mark_complete') }}
                        </button>
                    @else
                        <button onclick="updateTaskStatus('pending')"
                            class="w-full flex items-center justify-center gap-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-bold py-2.5 rounded-xl transition-all border border-gray-200 dark:border-gray-700">
                            {{ __('tasks.reopen_task') }}
                        </button>
                    @endif

                    @if ($task->status === 'blocked')
                        <button onclick="updateTaskStatus('in_progress')"
                            class="w-full flex items-center justify-center gap-2 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-500/10 dark:hover:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs font-bold py-2.5 rounded-xl transition-all border border-emerald-200 dark:border-emerald-500/20 shadow-sm animate-pulse">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            {{ __('tasks.unblock_task') }}
                        </button>
                    @else
                        <button onclick="updateTaskStatus('blocked')"
                            class="w-full flex items-center justify-center gap-2 bg-red-50 hover:bg-red-100 dark:bg-red-500/10 dark:hover:bg-red-500/20 text-red-600 dark:text-red-400 text-xs font-bold py-2.5 rounded-xl transition-all border border-red-200 dark:border-red-500/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            {{ __('tasks.report_blocker') }}
                        </button>
                    @endif

                    <!-- Progress Slider for all tasks -->
                    @php
                        $sliderTask = $personalInstance ?: $task;
                        $isAutomatic = $sliderTask->is_template || $sliderTask->children()->exists();
                        $currentProgress = $sliderTask->progress;
                    @endphp

                    <div class="pt-2 border-t border-gray-100 dark:border-gray-800 mt-2">
                        <label
                            class="flex items-center justify-between text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-widest font-bold mb-3">
                            <span>
                                @if ($personalInstance)
                                    {{ __('tasks.your_progress') ?? 'Tu Progreso' }}
                                @elseif($isAutomatic)
                                    {{ __('tasks.global_progress') ?? 'Progreso Global' }}
                                @else
                                    {{ __('tasks.progress') }}
                                @endif
                            </span>
                            <div class="flex items-center gap-1 min-w-[3rem] justify-end">
                                <span id="progress-val"
                                    class="text-violet-600 dark:text-violet-400 tabular-nums">{{ $currentProgress }}</span>
                                <span class="text-gray-400 text-[8px]">%</span>
                            </div>
                        </label>
                        <div class="flex items-center gap-3">
                            <input type="range" min="0" max="100" value="{{ $currentProgress }}"
                                class="flex-1 h-1.5 bg-gray-100 dark:bg-gray-800 rounded-lg appearance-none transition-none {{ $isAutomatic ? 'cursor-not-allowed opacity-60' : 'cursor-pointer accent-violet-600' }}"
                                {{ $isAutomatic ? 'disabled' : '' }}
                                oninput="document.getElementById('progress-val').innerText = this.value"
                                onchange="updateTaskProgress(this.value, {{ $sliderTask->id }})">

                            @if ($isAutomatic)
                                <span
                                    class="text-[10px] text-gray-400 italic">({{ __('tasks.automatic') ?? 'Auto' }})</span>
                            @endif
                        </div>
                    </div>
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
                    class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 space-y-3 shadow-sm dark:shadow-none transition-colors">
                    @if ($task->due_date)
                        <div class="flex items-center justify-between font-mono">
                            <span
                                class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wide font-sans">{{ __('tasks.due_date') }}</span>
                            <span
                                class="text-[11px] {{ now()->isAfter($task->due_date) && $task->status !== 'completed' ? 'text-red-500 font-bold' : 'text-gray-700 dark:text-gray-300' }}">
                                {{ $task->due_date->format('d M Y, H:i') }}
                            </span>
                        </div>
                    @endif
                    @if ($task->scheduled_date)
                        <div class="flex items-center justify-between font-mono">
                            <span
                                class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wide font-sans">{{ __('tasks.scheduled_date') }}</span>
                            <span
                                class="text-[11px] text-gray-700 dark:text-gray-300 font-medium">{{ $task->scheduled_date->format('d M Y, H:i') }}</span>
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

            <!-- Assigned To -->
            @if ($task->assignedTo->isNotEmpty() || $task->assignedGroups->isNotEmpty())
                <div
                    class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 space-y-4 shadow-sm dark:shadow-none transition-colors text-sans">
                    @if ($task->assignedTo->isNotEmpty())
                        <div>
                            <p
                                class="text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-widest font-bold mb-3">
                                {{ __('tasks.assigned_to') }}
                            </p>
                            <div class="space-y-2.5">
                                @foreach ($task->assignedTo as $u)
                                    @php
                                        $instance = $task->is_template
                                            ? $task->instances()->where('assigned_user_id', $u->id)->first()
                                            : null;
                                    @endphp
                                    @if($instance)
                                        <a href="{{ route('teams.tasks.show', [$team, $instance]) }}" class="flex items-center gap-2.5 hover:bg-gray-50 dark:hover:bg-gray-800/50 p-1.5 -ml-1.5 rounded-lg transition-colors group">
                                            <div
                                                class="w-6 h-6 rounded-full bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center text-[9px] font-bold text-violet-600 dark:text-violet-400 shrink-0 group-hover:bg-violet-200 dark:group-hover:bg-violet-900/50 transition-colors">
                                                {{ strtoupper(substr($u->name, 0, 2)) }}
                                            </div>
                                            <span
                                                class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors">{{ $u->name }}</span>
                                        </a>
                                    @else
                                        <div class="flex items-center gap-2.5 p-1.5 -ml-1.5">
                                            <div
                                                class="w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-[9px] font-bold text-gray-500 dark:text-gray-400 shrink-0">
                                                {{ strtoupper(substr($u->name, 0, 2)) }}
                                            </div>
                                            <span
                                                class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate">{{ $u->name }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($task->assignedGroups->isNotEmpty())
                        <div class="pt-3 border-t border-gray-50 dark:border-gray-800">
                            <p
                                class="text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-widest font-bold mb-3">
                                {{ __('tasks.groups') }}
                            </p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($task->assignedGroups as $g)
                                    <span
                                        class="bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-[9px] px-2 py-1 rounded-lg font-bold uppercase tracking-wider">
                                        {{ $g->name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
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

            function updateTaskStatus(status) {
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
                        fetch(`/teams/{{ $team->id }}/tasks/{{ $task->id }}/move`, {
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

            function updateTaskProgress(progress, taskId = {{ $task->id }}) {
                // Store current status to compare later
                const currentStatus = '{{ $task->status }}';

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

                                if (valSpan) valSpan.innerText = progress;

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
        </script>
    @endpush
</x-app-layout>
