<x-app-layout>
    @section('title', $task->title)

    <x-slot name="header">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('teams.dashboard', $team) }}"
                    class="text-gray-500 hover:text-white transition-colors shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <h1 class="text-xl font-bold text-white heading truncate">{{ $task->title }}</h1>
            </div>
            <a href="{{ route('teams.tasks.edit', [$team, $task]) }}"
                class="shrink-0 flex items-center gap-1.5 text-sm bg-gray-800 hover:bg-gray-700 border border-gray-700 text-gray-300 px-3 py-2 rounded-xl transition-all">
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
            1 => ['color' => 'text-red-400', 'bg' => 'bg-red-950/40 border-red-900/60'],
            2 => ['color' => 'text-blue-400', 'bg' => 'bg-blue-950/40 border-blue-900/60'],
            3 => ['color' => 'text-amber-400', 'bg' => 'bg-amber-950/40 border-amber-900/60'],
            4 => ['color' => 'text-gray-400', 'bg' => 'bg-gray-800 border-gray-700'],
        ][$q];

        $statusColor = match ($task->status) {
            'completed' => 'text-emerald-400 bg-emerald-400/10 border-emerald-800',
            'in_progress' => 'text-blue-400 bg-blue-400/10 border-blue-800',
            'cancelled' => 'text-red-400 bg-red-400/10 border-red-800',
            default => 'text-yellow-400 bg-yellow-400/10 border-yellow-800',
        };
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main content -->
        <div class="lg:col-span-2 space-y-5">
            <!-- Description -->
            @if ($task->description)
                <div class="bg-gray-900 border border-gray-800 rounded-2xl p-5">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                        {{ __('tasks.description') }}</h3>
                    <p class="text-sm text-gray-300 leading-relaxed whitespace-pre-wrap">{{ $task->description }}</p>
                </div>
            @endif

            <!-- History -->
            @if ($task->histories->isNotEmpty())
                <div class="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-800">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            {{ __('tasks.history') }}</h3>
                    </div>
                    @foreach ($task->histories->take(10) as $h)
                        <div class="px-5 py-3 border-b border-gray-800/60 last:border-0 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-400">{{ $h->user?->name ?? '—' }}</span>
                                <span class="text-gray-600">{{ $h->created_at->diffForHumans() }}</span>
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
            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500 uppercase tracking-wide">{{ __('tasks.status') }}</span>
                    <span class="text-xs font-medium px-2.5 py-1 rounded-full border {{ $statusColor }}">
                        {{ __('tasks.statuses.' . $task->status) }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500 uppercase tracking-wide">{{ __('tasks.quadrant') }}</span>
                    <span class="text-xs font-semibold {{ $qCfg['color'] }}">
                        Q{{ $q }}: {{ __('tasks.quadrants.' . $q . '.label') }}
                    </span>
                </div>
                <div class="pt-1 border-t border-gray-800 {{ $qCfg['bg'] }} rounded-xl p-3 text-xs">
                    <p class="font-semibold {{ $qCfg['color'] }}">{{ __('tasks.quadrants.' . $q . '.description') }}
                    </p>
                    <p class="text-gray-400 mt-1 italic">💡 {{ __('tasks.quadrants.' . $q . '.tip') }}</p>
                </div>
            </div>

            <!-- Priority / Urgency -->
            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-4 space-y-2">
                @foreach ([['tasks.priority', $task->priority, 'tasks.priorities'], ['tasks.urgency', $task->urgency, 'tasks.urgencies']] as [$lbl, $val, $map])
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500">{{ __($lbl) }}</span>
                        <span class="text-xs font-medium text-gray-200">{{ __($map . '.' . $val) }}</span>
                    </div>
                @endforeach
            </div>

            <!-- Dates -->
            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-4 space-y-2">
                @if ($task->due_date)
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500">{{ __('tasks.due_date') }}</span>
                        <span
                            class="text-xs {{ now()->isAfter($task->due_date) && $task->status !== 'completed' ? 'text-red-400 font-semibold' : 'text-gray-300' }}">
                            {{ $task->due_date->format('d M Y, H:i') }}
                        </span>
                    </div>
                @endif
                @if ($task->scheduled_date)
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500">{{ __('tasks.scheduled_date') }}</span>
                        <span class="text-xs text-gray-300">{{ $task->scheduled_date->format('d M Y, H:i') }}</span>
                    </div>
                @endif
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500">{{ __('tasks.created_at') }}</span>
                    <span class="text-xs text-gray-400">{{ $task->created_at->format('d M Y') }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500">{{ __('tasks.created_by') }}</span>
                    <span class="text-xs text-gray-400">{{ $task->creator?->name ?? '—' }}</span>
                </div>
            </div>

            <!-- Assigned To Users -->
            @if ($task->assignedTo->isNotEmpty())
                <div class="bg-gray-900 border border-gray-800 rounded-2xl p-4">
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-3 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        {{ __('tasks.assigned_to') }}
                    </p>
                    <div class="space-y-2">
                        @foreach ($task->assignedTo as $u)
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-7 h-7 rounded-full bg-gradient-to-br from-violet-600 to-indigo-700 flex items-center justify-center text-[10px] font-bold text-white shrink-0">
                                    {{ strtoupper(substr($u->name, 0, 2)) }}
                                </div>
                                <span class="text-xs text-gray-300 truncate">{{ $u->name }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Assigned To Groups -->
            @if ($task->assignedGroups->isNotEmpty())
                <div class="bg-gray-900 border border-gray-800 rounded-2xl p-4">
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-3 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        {{ __('Groups') }}
                    </p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($task->assignedGroups as $g)
                            <div
                                class="bg-violet-900/30 border border-violet-800/60 text-violet-300 text-[10px] px-2 py-1 rounded-lg">
                                {{ $g->name }}
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
