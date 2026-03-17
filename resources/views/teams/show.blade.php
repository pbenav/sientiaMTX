<x-app-layout>
    @section('title', $team->name)

    <x-slot name="header">
        <div class="flex items-center justify-between gap-4 overflow-hidden">
            <div class="flex items-center gap-3 min-w-0 flex-1">
                <a href="{{ route('teams.index') }}"
                    class="text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white heading truncate">
                    {{ $team->name }}
                </h1>
            </div>
            @include('teams.partials.header-actions')
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Members List -->
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">
                    {{ __('teams.members') }} ({{ $team->members->count() }})</h3>
                <a href="{{ route('teams.tasks.create', $team) }}"
                    class="bg-violet-600 hover:bg-violet-700 text-white px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-lg shadow-violet-500/30">
                    + {{ __('tasks.create') }}
                </a>
            </div>

            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm rounded-2xl overflow-hidden divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($team->members as $member)
                    <div class="px-5 py-4 flex items-center justify-between group hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-full bg-violet-100 dark:bg-violet-900/40 border border-violet-200 dark:border-violet-700/50 flex items-center justify-center text-xs font-bold text-violet-700 dark:text-violet-300">
                                {{ strtoupper(substr($member->name, 0, 2)) }}
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $member->name }}</p>
                                <p class="text-[10px] text-gray-400 font-medium uppercase tracking-tighter">
                                    {{ $member->pivot->role_id === 1 ? 'Coordinator' : 'Member' }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Recent Tasks -->
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">
                    {{ __('teams.recent_tasks') }}</h3>
                <a href="{{ route('teams.tasks.index', $team) }}"
                    class="text-[10px] font-bold uppercase tracking-widest text-violet-600 dark:text-violet-400 hover:text-violet-700 dark:hover:text-violet-300 transition-colors">{{ __('teams.view_dashboard') }}
                    →</a>
            </div>
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm rounded-2xl overflow-hidden divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($team->tasks->take(10) as $task)
                    @php
                        $statusColor = match ($task->status) {
                            'completed' => 'text-emerald-600 bg-emerald-50 dark:text-emerald-400 dark:bg-emerald-400/10',
                            'in_progress' => 'text-blue-600 bg-blue-50 dark:text-blue-400 dark:bg-blue-400/10',
                            'cancelled' => 'text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-400/10',
                            default => 'text-amber-600 bg-amber-50 dark:text-yellow-400 dark:bg-yellow-400/10',
                        };
                    @endphp
                    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800/60 last:border-0 flex items-center gap-4 hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors cursor-pointer task-row"
                        data-href="{{ route('teams.tasks.show', [$team, $task]) }}"
                        onclick="if(!event.target.closest('a, button')) window.location=this.dataset.href">
                        <div class="flex-1 min-w-0 relative">
                            <a href="{{ route('teams.tasks.show', [$team, $task]) }}"
                                class="text-sm font-semibold text-gray-700 dark:text-gray-200 hover:text-violet-600 dark:hover:text-white truncate block transition-colors">
                                {{ $task->title }}
                            </a>
                            @if ($task->due_date)
                                <span class="text-xs text-gray-500">{{ __('tasks.due_date') }}:
                                    {{ $task->due_date->format('d M Y') }}</span>
                            @endif
                        </div>
                        <span class="shrink-0 text-xs font-medium px-2.5 py-1 rounded-full {{ $statusColor }}">
                            {{ __('tasks.statuses.' . $task->status) }}
                        </span>
                        @can('update', $task)
                            <a href="{{ route('teams.tasks.edit', [$team, $task]) }}"
                                class="shrink-0 text-gray-400 hover:text-blue-500 transition-colors" title="{{ __('tasks.edit') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </a>
                        @endcan
                    </div>
                @empty
                    <div class="px-5 py-10 text-center text-gray-500 text-sm">{{ __('teams.no_tasks') }}</div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
