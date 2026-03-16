<x-app-layout>
    @section('title', $team->name)

    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('teams.index') }}"
                class="text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div class="flex-1 min-w-0">
                @include('teams.partials.breadcrumb')
                <h1 class="text-xl font-bold text-gray-900 dark:text-white heading truncate">{{ $team->name }}</h1>
                @if ($team->description)
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 truncate">{{ $team->description }}</p>
                @endif
            </div>
            @include('teams.partials.header-actions')
        </div>
    </x-slot>

    <!-- Stats row -->
    @php
        $total = $team->tasks->count();
        $pending = $team->tasks->where('status', 'pending')->count();
        $inProgress = $team->tasks->where('status', 'in_progress')->count();
        $completed = $team->tasks->where('status', 'completed')->count();
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
        @foreach ([['label' => __('tasks.statuses.pending'), 'value' => $pending, 'color' => 'text-yellow-600 dark:text-yellow-400', 'bg' => 'bg-yellow-50 dark:bg-yellow-400/10 border-yellow-100 dark:border-yellow-700/30'], ['label' => __('tasks.statuses.in_progress'), 'value' => $inProgress, 'color' => 'text-blue-600 dark:text-blue-400', 'bg' => 'bg-blue-50 dark:bg-blue-400/10 border-blue-100 dark:border-blue-700/30'], ['label' => __('tasks.statuses.completed'), 'value' => $completed, 'color' => 'text-emerald-600 dark:text-emerald-400', 'bg' => 'bg-emerald-50 dark:bg-emerald-400/10 border-emerald-100 dark:border-emerald-700/30'], ['label' => __('teams.members'), 'value' => $team->members->count(), 'color' => 'text-violet-600 dark:text-violet-400', 'bg' => 'bg-violet-50 dark:bg-violet-400/10 border-violet-100 dark:border-violet-700/30']] as $stat)
            <div
                class="border {{ $stat['bg'] }} rounded-2xl p-4 text-center shadow-sm dark:shadow-none transition-colors">
                <div class="text-2xl font-bold {{ $stat['color'] }} heading">{{ $stat['value'] }}</div>
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400 mt-1">
                    {{ $stat['label'] }}</div>
            </div>
        @endforeach
    </div>

    <!-- Task list -->
    <div
        class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm dark:shadow-none transition-colors">
        <div
            class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gray-50 dark:bg-transparent">
            <h2 class="font-bold text-xs uppercase tracking-widest text-gray-400 dark:text-gray-500 heading">
                {{ __('teams.tasks') }}</h2>
            <a href="{{ route('teams.dashboard', $team) }}"
                class="text-[10px] font-bold uppercase tracking-widest text-violet-600 dark:text-violet-400 hover:text-violet-700 dark:hover:text-violet-300 transition-colors">{{ __('teams.view_dashboard') }}
                →</a>
        </div>
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
                data-href="{{ route('teams.tasks.show', [$team, $task]) }}">
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
                <a href="{{ route('teams.tasks.edit', [$team, $task]) }}"
                    class="shrink-0 text-gray-600 hover:text-gray-300 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </a>
            </div>
        @empty
            <div class="px-5 py-10 text-center text-gray-500 text-sm">{{ __('teams.no_tasks') }}</div>
        @endforelse
    </div>
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.task-row').forEach(row => {
                    row.addEventListener('click', function(e) {
                        if (e.target.closest('button, a, form, input, select')) {
                            return;
                        }
                        window.location.href = this.getAttribute('data-href');
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
