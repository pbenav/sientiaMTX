<x-app-layout>
    @section('title', __('teams.eisenhower_matrix') . ' — ' . $team->name)

    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('teams.show', $team) }}" class="text-gray-500 hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-xl font-bold text-white heading">{{ __('teams.eisenhower_matrix') }}</h1>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $team->name }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('teams.members', $team) }}"
                    class="flex items-center gap-1.5 text-xs text-gray-300 border border-gray-700 hover:border-gray-600 px-3 py-2 rounded-xl transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    {{ __('teams.view_members') }}
                </a>
                <a href="{{ route('teams.tasks.create', $team) }}"
                    class="flex items-center gap-1.5 text-xs bg-violet-600 hover:bg-violet-500 text-white px-3 py-2 rounded-xl transition-all font-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ __('tasks.create') }}
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $quadrantConfig = [
            1 => [
                'color' => '#ef4444',
                'bg' => 'bg-red-950/40 border-red-900/60',
                'badge' => 'bg-red-900/60 text-red-300',
                'dot' => 'bg-red-400',
            ],
            2 => [
                'color' => '#3b82f6',
                'bg' => 'bg-blue-950/40 border-blue-900/60',
                'badge' => 'bg-blue-900/60 text-blue-300',
                'dot' => 'bg-blue-400',
            ],
            3 => [
                'color' => '#f59e0b',
                'bg' => 'bg-amber-950/40 border-amber-900/60',
                'badge' => 'bg-amber-900/60 text-amber-300',
                'dot' => 'bg-amber-400',
            ],
            4 => [
                'color' => '#6b7280',
                'bg' => 'bg-gray-900/80 border-gray-700',
                'badge' => 'bg-gray-700 text-gray-300',
                'dot' => 'bg-gray-400',
            ],
        ];
    @endphp

    <!-- Urgency / Importance legend -->
    <div class="grid grid-cols-3 mb-3 text-xs text-gray-500 px-1">
        <div></div>
        <div class="text-center font-semibold text-gray-400 uppercase tracking-widest pb-2">← Not Urgent · Urgent →
        </div>
        <div></div>
    </div>

    <!-- Matrix grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @foreach ([1, 2, 3, 4] as $q)
            @php
                $cfg = $quadrantConfig[$q];
                $qTasks = $quadrants[$q];
            @endphp
            <div class="border {{ $cfg['bg'] }} rounded-2xl flex flex-col min-h-56">
                <!-- Quadrant header -->
                <div class="px-4 py-3 border-b border-white/5 flex items-center gap-3">
                    <div class="w-5 h-5 rounded-full shrink-0"
                        style="background:{{ $cfg['color'] }}; box-shadow: 0 0 12px {{ $cfg['color'] }}80"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-white heading">{{ __('tasks.quadrants.' . $q . '.label') }}
                        </p>
                        <p class="text-xs text-gray-400">{{ __('tasks.quadrants.' . $q . '.description') }}</p>
                    </div>
                    <span class="shrink-0 text-xs font-medium px-2 py-0.5 rounded-full {{ $cfg['badge'] }}">
                        {{ count($qTasks) }}
                    </span>
                </div>
                <!-- Tip -->
                <div class="px-4 py-1.5 text-xs text-gray-600 italic border-b border-white/5">
                    💡 {{ __('tasks.quadrants.' . $q . '.tip') }}
                </div>
                <!-- Task list -->
                <div class="flex-1 overflow-y-auto divide-y divide-white/5">
                    @forelse($qTasks as $task)
                        @php
                            $statusBadge = match ($task->status) {
                                'completed' => 'text-emerald-400',
                                'in_progress' => 'text-blue-400',
                                'cancelled' => 'text-red-400',
                                default => 'text-gray-400',
                            };
                        @endphp
                        <div class="px-4 py-2.5 flex items-center gap-3 hover:bg-white/5 group transition-colors">
                            <!-- Status dot -->
                            <div
                                class="w-1.5 h-1.5 rounded-full shrink-0 {{ $cfg['dot'] }}
                                {{ $task->status === 'completed' ? 'opacity-30' : '' }}">
                            </div>
                            <a href="{{ route('teams.tasks.show', [$team, $task]) }}"
                                class="flex-1 text-sm text-gray-300 hover:text-white truncate transition-colors
                              {{ $task->status === 'completed' ? 'line-through opacity-50' : '' }}">
                                {{ $task->title }}
                            </a>
                            @if ($task->assignedGroups->isNotEmpty())
                                <span class="shrink-0" title="{{ $task->assignedGroups->pluck('name')->join(', ') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-violet-400"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </span>
                            @endif
                            <!-- Due date -->
                            @if ($task->due_date)
                                <span
                                    class="shrink-0 text-xs {{ now()->isAfter($task->due_date) && $task->status !== 'completed' ? 'text-red-400' : 'text-gray-600' }}">
                                    {{ $task->due_date->format('d/m') }}
                                </span>
                            @endif
                            <!-- Edit link (on hover) -->
                            <a href="{{ route('teams.tasks.edit', [$team, $task]) }}"
                                class="shrink-0 opacity-0 group-hover:opacity-100 text-gray-600 hover:text-gray-300 transition-all">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </a>
                        </div>
                    @empty
                        <div class="px-4 py-8 text-center text-xs text-gray-600">{{ __('teams.no_tasks') }}</div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    <!-- Summary row -->
    <div class="mt-6 flex flex-wrap gap-3 text-xs text-gray-500">
        <span>{{ __('teams.tasks_count', ['count' => $tasks->count()]) }} total</span>
        <span>·</span>
        <span>{{ $tasks->where('status', 'completed')->count() }}
            {{ strtolower(__('tasks.statuses.completed')) }}</span>
        <span>·</span>
        <span>{{ $tasks->where('status', 'in_progress')->count() }}
            {{ strtolower(__('tasks.statuses.in_progress')) }}</span>
        <span>·</span>
        <span>{{ $tasks->where('due_date', '<', now())->whereNotIn('status', ['completed', 'cancelled'])->count() }}
            overdue</span>
    </div>
</x-app-layout>
