<x-app-layout>
    @section('title', $task->title)

    <x-slot name="header">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('teams.dashboard', $team) }}"
                    class="text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div class="min-w-0">
                    @include('teams.partials.breadcrumb')
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white heading truncate">{{ $task->title }}
                    </h1>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                @can('update', $task)
                    <a href="{{ route('teams.tasks.edit', [$team, $task]) }}"
                        class="shrink-0 flex items-center gap-1.5 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 px-3 py-2 rounded-xl transition-all shadow-sm dark:shadow-none font-medium">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        {{ __('tasks.edit') }}
                    </a>
                @endcan

                @can('delete', $task)
                    <form id="delete-task-form-{{ $task->id }}"
                        action="{{ route('teams.tasks.destroy', [$team, $task]) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="button"
                            onclick="confirmDelete('delete-task-form-{{ $task->id }}', '{{ __('tasks.delete_confirm') }}')"
                            class="shrink-0 flex items-center gap-1.5 text-sm bg-red-50 hover:bg-red-100 dark:bg-red-500/10 dark:hover:bg-red-500/20 border border-red-200 dark:border-red-500/20 text-red-600 dark:text-red-400 px-3 py-2 rounded-xl transition-all shadow-sm dark:shadow-none font-medium">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            {{ __('tasks.delete') }}
                        </button>
                    </form>
                @endcan
            </div>
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
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main content -->
        <div class="lg:col-span-2 space-y-5">
            @if ($task->is_template || $task->children()->exists())
                @php
                    $isRoadmap = $task->is_template;
                    $instances = $isRoadmap
                        ? $task->instances()->with('assignedUser')->get()
                        : $task->children()->with('assignedTo')->get();
                    $totalInst = $instances->count();
                    $doneInst = $instances->where('status', 'completed')->count();
                    $prog = $totalInst > 0 ? ($doneInst / $totalInst) * 100 : 0;
                    $hasBlocker = $instances->where('status', 'blocked')->isNotEmpty();

                    // Find if the current user has a personal instance of this goal
                    $personalInstance = $task
                        ->instances()
                        ->where('assigned_user_id', auth()->id())
                        ->first();
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
                        <div class="text-right">
                            <span
                                class="text-2xl font-black text-violet-600 dark:text-violet-400 heading">{{ round($prog) }}%</span>
                        </div>
                    </div>

                    <div
                        class="w-full h-3 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden mb-8 border border-gray-200 dark:border-gray-700">
                        <div class="h-full bg-gradient-to-r from-violet-500 to-indigo-600 transition-all duration-1000 shadow-lg shadow-violet-500/20"
                            style="width: {{ $prog }}%"></div>
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
                                    <th class="px-4 py-3 text-right">{{ __('tasks.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                                @foreach ($instances as $inst)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-8 h-8 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 flex items-center justify-center text-[10px] font-bold text-gray-600 dark:text-gray-400 shadow-inner">
                                                    {{ strtoupper(substr($inst->assignedUser?->name ?? '?', 0, 2)) }}
                                                </div>
                                                <span
                                                    class="font-medium text-gray-700 dark:text-gray-300">{{ $inst->assignedUser?->name ?? 'User' }}</span>
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
                                            @if ($inst->status !== 'completed')
                                                <button onclick="nudgeUser({{ $inst->id }})"
                                                    class="p-2 text-violet-600 hover:bg-violet-50 dark:text-violet-400 dark:hover:bg-violet-400/10 rounded-lg transition-all"
                                                    title="{{ __('tasks.nudge_user') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
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
                </div>
            @endif

            @if ($task->isInstance())
                <div
                    class="bg-indigo-50/80 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/20 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm dark:shadow-none transition-colors mb-6">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 rounded-2xl bg-white dark:bg-indigo-500/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0 shadow-sm border border-indigo-100 dark:border-indigo-500/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-extrabold text-indigo-900 dark:text-indigo-300 tracking-tight">
                                {{ __('tasks.personal_instance_notice') }}</p>
                            <p class="text-xs text-indigo-700/70 dark:text-indigo-400/80 font-medium">
                                {{ __('tasks.personal_instance_description') }}</p>
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

            <!-- Observations -->
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

            <!-- History -->
            @if ($task->histories->isNotEmpty())
                <div
                    class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm dark:shadow-none transition-colors">
                    <div
                        class="px-5 py-3 border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-transparent">
                        <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                            {{ __('tasks.history') }}</h3>
                    </div>
                    @foreach ($task->histories->take(10) as $h)
                        <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-800/60 last:border-0 text-xs">
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-gray-600 dark:text-gray-400 font-medium">{{ $h->user?->name ?? '—' }}</span>
                                <span
                                    class="text-gray-400 dark:text-gray-600">{{ $h->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-gray-500 mt-0.5 capitalize">{{ $h->action }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-4">
            <!-- Quick Actions -->
            @if ($task->assigned_user_id === auth()->id() || $team->isCoordinator(auth()->user()))
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

                    @if ($task->status !== 'blocked')
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

                    <!-- Progress Slider for Individual Tasks or Personal Instance -->
                    @php
                        $showSlider = (!$task->is_template && !$task->children()->exists()) || $personalInstance;
                        $sliderTask = $personalInstance ?: $task;
                    @endphp

                    @if ($showSlider)
                        <div class="pt-2 border-t border-gray-100 dark:border-gray-800 mt-2">
                            <label
                                class="text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-widest font-bold mb-3 block">
                                {{ $personalInstance ? 'Tu ' : '% ' }}{{ __('tasks.progress') }}: <span
                                    id="progress-val" class="text-violet-500">{{ $sliderTask->progress }}</span>%
                            </label>
                            <input type="range" min="0" max="100" value="{{ $sliderTask->progress }}"
                                class="w-full h-1.5 bg-gray-100 dark:bg-gray-800 rounded-lg appearance-none cursor-pointer accent-violet-600"
                                oninput="document.getElementById('progress-val').innerText = this.value"
                                onchange="updateTaskProgress(this.value, {{ $sliderTask->id }})">
                        </div>
                    @endif
                </div>
            @endif

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
            </div>

            <!-- Dates -->
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
                <div class="flex items-center justify-between py-2 border-t border-gray-100 dark:border-gray-800 mt-2">
                    <span
                        class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider">{{ __('tasks.owner') }}</span>
                    <div class="flex items-center gap-2">
                        <div
                            class="w-5 h-5 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-[9px] font-bold text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700">
                            {{ strtoupper(substr($task->creator?->name ?? '?', 0, 2)) }}
                        </div>
                        <span
                            class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ $task->creator?->name ?? '—' }}</span>
                    </div>
                </div>
                <div class="flex items-center justify-between pt-1">
                    <span
                        class="text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ __('tasks.created_at') }}</span>
                    <span
                        class="text-[10px] text-gray-500 dark:text-gray-600">{{ $task->created_at->format('d M Y') }}</span>
                </div>
            </div>

            <!-- Assigned To Users -->
            @if ($task->assignedTo->isNotEmpty())
                <div
                    class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm dark:shadow-none transition-colors">
                    <p
                        class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-widest font-bold mb-4 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-violet-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        {{ __('tasks.assigned_to') }}
                    </p>
                    <div class="space-y-3">
                        @foreach ($task->assignedTo as $u)
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-7 h-7 rounded-full bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center text-[10px] font-bold text-white shrink-0 shadow-sm">
                                    {{ strtoupper(substr($u->name, 0, 2)) }}
                                </div>
                                <span
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate">{{ $u->name }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Assigned To Groups -->
            @if ($task->assignedGroups->isNotEmpty())
                <div
                    class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm dark:shadow-none transition-colors">
                    <p
                        class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-widest font-bold mb-4 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-indigo-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        {{ __('tasks.groups') }}
                    </p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($task->assignedGroups as $g)
                            <div
                                class="bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-100 dark:border-indigo-800/60 text-indigo-600 dark:text-indigo-300 text-[10px] px-2.5 py-1.5 rounded-lg font-bold uppercase tracking-wider shadow-sm flex items-center gap-1.5 transition-colors">
                                <div class="w-1 h-1 rounded-full bg-indigo-400"></div>
                                {{ $g->name }}
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            function nudgeUser(taskId) {
                Swal.fire({
                    title: '{{ __('tasks.nudge_user') }}?',
                    text: '{{ __('tasks.nudge_received') }}',
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
                                    });
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

            function updateTaskStatus(status) {
                const messages = {
                    'completed': '¿Marcar como completada?',
                    'blocked': '¿Informar un bloqueo en esta tarea?',
                    'pending': '¿Reabrir esta tarea?'
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
                            // If progress is 100, we might want to reload to show "Completed" status
                            if (progress == 100) {
                                window.location.reload();
                            } else {
                                // Subtle toast or just keep it as is
                                const valSpan = document.getElementById('progress-val');
                                valSpan.classList.add('animate-pulse', 'text-emerald-500');
                                setTimeout(() => {
                                    valSpan.classList.remove('animate-pulse', 'text-emerald-500');
                                }, 1000);
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
        </script>
    @endpush
</x-app-layout>
