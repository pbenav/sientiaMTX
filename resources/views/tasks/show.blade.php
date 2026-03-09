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
                <h1 class="text-xl font-bold text-gray-900 dark:text-white heading truncate">{{ $task->title }}</h1>
            </div>
            <a href="{{ route('teams.tasks.edit', [$team, $task]) }}"
                class="shrink-0 flex items-center gap-1.5 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 px-3 py-2 rounded-xl transition-all shadow-sm dark:shadow-none font-medium">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                {{ __('tasks.edit') }}
            </a>
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
            default
                => 'text-amber-600 bg-amber-50 border-amber-100 dark:text-yellow-400 dark:bg-yellow-400/10 dark:border-yellow-800',
        };
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main content -->
        <div class="lg:col-span-2 space-y-5">
            @if ($task->description)
                <div
                    class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm dark:shadow-none transition-colors">
                    <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">
                        {{ __('tasks.description') }}</h3>
                    <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-wrap">
                        {{ $task->description }}</p>
                </div>
            @endif

            <!-- Observations -->
            @if ($task->observations)
                <div
                    class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm dark:shadow-none transition-colors">
                    <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">
                        {{ __('tasks.observations') }}</h3>
                    <div class="text-sm text-gray-700 dark:text-gray-300 prose dark:prose-invert max-w-none prose-sm">
                        {!! str($task->observations)->markdown() !!}
                    </div>
                </div>
            @endif

            <!-- History -->
            @if ($task->histories->isNotEmpty())
                <div
                    class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm dark:shadow-none transition-colors">
                    <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-transparent">
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
                <div class="flex items-center justify-between py-1 border-t border-gray-50 dark:border-gray-800 mt-2">
                    <span
                        class="text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ __('tasks.created_at') }}</span>
                    <span
                        class="text-[10px] text-gray-500 dark:text-gray-600">{{ $task->created_at->format('d M Y') }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span
                        class="text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ __('tasks.created_by') }}</span>
                    <span
                        class="text-[10px] text-gray-500 dark:text-gray-600 font-medium">{{ $task->creator?->name ?? '—' }}</span>
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
</x-app-layout>
