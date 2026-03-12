<x-app-layout>
    @section('title', __('teams.eisenhower_matrix') . ' — ' . $team->name)

    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('teams.show', $team) }}"
                    class="text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div>
                    @include('teams.partials.breadcrumb')
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white heading">
                        {{ __('teams.eisenhower_matrix') }}</h1>
                </div>
            </div>

            @include('teams.partials.header-actions')
        </div>
    </x-slot>

    @php
        $quadrantConfig = [
            1 => [
                'color' => '#f87171',
                'bg' => 'bg-red-50 border-red-100 dark:bg-red-500/5 dark:border-red-500/20',
                'dot' => 'bg-red-500',
            ],
            2 => [
                'color' => '#60a5fa',
                'bg' => 'bg-blue-50 border-blue-100 dark:bg-blue-500/5 dark:border-blue-500/20',
                'dot' => 'bg-blue-500',
            ],
            3 => [
                'color' => '#fbbf24',
                'bg' => 'bg-amber-50 border-amber-100 dark:bg-amber-500/5 dark:border-amber-500/20',
                'dot' => 'bg-amber-500',
            ],
            4 => [
                'color' => '#9ca3af',
                'bg' => 'bg-gray-50 border-gray-200 dark:bg-gray-500/5 dark:border-gray-500/20',
                'dot' => 'bg-gray-500',
            ],
        ];
    @endphp

    <!-- Matrix Labels & Grid -->
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Horizontal Urgency labels -->
        <div
            class="flex justify-between mb-4 sm:mb-8 ml-10 sm:ml-16 mr-2 sm:mr-4 text-[9px] sm:text-[11px] font-bold uppercase tracking-[0.1em] sm:tracking-[0.2em] text-gray-400 dark:text-gray-500">
            <div class="flex items-center gap-3">
                <span class="text-xs">←</span>
                <span>{{ __('tasks.not_urgent') }}</span>
            </div>
            <div class="flex items-center gap-3">
                <span>{{ __('tasks.urgent') }}</span>
                <span class="text-xs">→</span>
            </div>
        </div>

        <div class="flex gap-4">
            <!-- Vertical Importance label -->
            <div class="flex items-center py-4 sm:py-8">
                <span
                    class="text-[9px] sm:text-[11px] font-bold uppercase tracking-[0.1em] sm:tracking-[0.2em] text-gray-500 [writing-mode:vertical-rl] rotate-180 whitespace-nowrap select-none">
                    ← {{ __('tasks.not_important') }} · {{ __('tasks.important') }} →
                </span>
            </div>

            <!-- Matrix grid -->
            <div class="flex-1 grid grid-cols-2 gap-3 sm:gap-8">
                @foreach ([2, 1, 4, 3] as $q)
                    @php
                        $cfg = $quadrantConfig[$q];
                        $qTasks = $quadrants[$q];
                    @endphp
                    <div class="border {{ $cfg['bg'] }} rounded-2xl sm:rounded-[2.5rem] flex flex-col min-h-[180px] sm:min-h-[320px] shadow-lg sm:shadow-2xl transition-all group/q quadrant-container"
                        data-quadrant="{{ $q }}">
                        <!-- Quadrant header -->
                        <div
                            class="px-4 py-3 sm:px-8 sm:py-6 border-b border-white/5 flex flex-col gap-0.5 sm:gap-1 relative">
                            <div class="flex items-center gap-3">
                                <div class="w-3 h-3 rounded-full shrink-0"
                                    style="background:{{ $cfg['color'] }}; box-shadow: 0 0 20px {{ $cfg['color'] }}">
                                </div>
                                <span
                                    class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-tighter">Q{{ $q }}</span>
                            </div>
                            <h2
                                class="text-sm sm:text-2xl font-bold text-gray-900 dark:text-white heading mt-0.5 sm:mt-1">
                                {{ __('tasks.quadrants.' . $q . '.label') }}
                            </h2>
                            <p
                                class="text-[9px] sm:text-[11px] text-gray-500 dark:text-gray-500 font-medium line-clamp-1 sm:line-clamp-none">
                                {{ __('tasks.quadrants.' . $q . '.description') }}
                            </p>

                            <span
                                class="absolute top-4 right-4 sm:top-8 sm:right-8 text-[9px] sm:text-[11px] font-bold bg-gray-100 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-gray-500 dark:text-gray-400 w-5 h-5 sm:w-7 sm:h-7 flex items-center justify-center rounded-full q-count">
                                {{ count($qTasks) }}
                            </span>
                        </div>

                        <!-- Task list -->
                        <div class="flex-1 overflow-y-auto quadrant-list p-2 sm:p-4 min-h-[100px] sm:min-h-[180px] space-y-1 sm:space-y-2"
                            data-q="{{ $q }}">
                            @forelse($qTasks as $task)
                                @if ($task->status !== 'completed')
                                    <div class="px-2 py-1.5 sm:px-3 sm:py-2 flex items-center gap-1.5 sm:gap-3 hover:bg-black/5 dark:hover:bg-white/5 group transition-all cursor-grab active:cursor-grabbing rounded-xl relative overflow-hidden"
                                        data-id="{{ $task->id }}">
                                        <!-- Status dot -->
                                        <div
                                            class="w-1.5 h-1.5 rounded-full shrink-0 {{ $cfg['dot'] }} z-10 relative">
                                        </div>
                                        <a href="{{ route('teams.tasks.show', [$team, $task]) }}"
                                            class="flex-1 text-[11px] sm:text-sm text-gray-700 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white truncate transition-colors z-10 relative after:absolute after:inset-0 after:z-20">
                                            {{ $task->title }}
                                        </a>
                                        <!-- Owner initials -->
                                        <div class="shrink-0 w-4 h-4 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-[7px] font-bold text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700"
                                            title="{{ __('tasks.owner') }}: {{ $task->creator?->name }}">
                                            {{ strtoupper(substr($task->creator?->name ?? '?', 0, 2)) }}
                                        </div>
                                        @if ($task->due_date)
                                            <span
                                                class="shrink-0 text-[7px] sm:text-[9px] text-gray-600 font-mono z-10 relative">
                                                {{ $task->due_date->format('d/m') }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            @empty
                                <div
                                    class="flex items-center justify-center flex-1 text-[9px] sm:text-[11px] text-gray-600 italic empty-msg py-6 sm:py-12">
                                    {{ __('teams.no_tasks') }}
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Completed Tasks Zone -->
    <div class="mt-16 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
        <div
            class="bg-gray-50/50 dark:bg-gray-950/20 border border-gray-200 dark:border-gray-800/40 rounded-[2.5rem] overflow-hidden shadow-sm dark:shadow-none transition-colors quadrant-container">
            <div
                class="px-8 py-5 border-b border-gray-100 dark:border-white/5 bg-gray-50 dark:bg-gray-900/10 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="p-1.5 rounded-full border border-emerald-500/20 bg-emerald-500/5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-500/60" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h3 class="text-[12px] font-bold uppercase tracking-[0.25em] text-gray-600 dark:text-gray-500">
                        {{ __('teams.completed_tasks') }}</h3>
                </div>
                <span
                    class="text-xs font-bold text-gray-400 dark:text-gray-600 q-count mr-2">{{ $tasks->where('status', 'completed')->count() }}</span>
            </div>

            <div class="min-h-[140px] quadrant-list p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4"
                data-q="completed">
                @forelse($tasks->where('status', 'completed') as $task)
                    <div class="px-4 py-3 flex items-center gap-4 bg-white dark:bg-gray-900/20 hover:bg-gray-100 dark:hover:bg-white/10 group transition-all cursor-grab active:cursor-grabbing rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm dark:shadow-none relative overflow-hidden"
                        data-id="{{ $task->id }}">
                        <div class="w-1.5 h-1.5 rounded-full shrink-0 bg-emerald-500/20 z-10 relative"></div>
                        <a href="{{ route('teams.tasks.show', [$team, $task]) }}"
                            class="flex-1 text-[12px] text-gray-400 dark:text-gray-600 line-through truncate group-hover:text-gray-600 dark:group-hover:text-gray-400 transition-colors z-10 relative after:absolute after:inset-0 after:z-20">
                            {{ $task->title }}
                        </a>
                    </div>
                @empty
                    <div class="col-span-full py-20 text-center text-xs text-gray-700 italic empty-msg">
                        {{ __('teams.drop_to_complete') }}
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Summary row -->
    <div class="mt-6 flex flex-wrap gap-3 text-xs text-gray-500 border-t border-white/5 pt-4">
        <span>{{ __('teams.tasks_count', ['count' => $tasks->count()]) }} {{ __('teams.tasks_total') }}</span>
        <span>·</span>
        <span>{{ $tasks->where('status', 'completed')->count() }}
            {{ strtolower(__('tasks.statuses.completed')) }}</span>
        <span>·</span>
        <span>{{ $tasks->where('status', 'in_progress')->count() }}
            {{ strtolower(__('tasks.statuses.in_progress')) }}</span>
        <span>·</span>
        <span>{{ $tasks->where('due_date', '<', now())->whereNotIn('status', ['completed', 'cancelled'])->count() }}
            {{ __('teams.overdue') }}</span>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const lists = document.querySelectorAll('.quadrant-list');
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const qDotClasses = @json(collect($quadrantConfig)->map->dot);
                const completedDotClass = 'bg-emerald-500/40';

                lists.forEach(list => {
                    new Sortable(list, {
                        group: 'quadrants',
                        animation: 150,
                        ghostClass: document.documentElement.classList.contains('dark') ?
                            'bg-white/10' : 'bg-black/5',
                        chosenClass: document.documentElement.classList.contains('dark') ?
                            'bg-white/5' : 'bg-black/[0.02]',
                        dragClass: 'opacity-50',
                        preventOnFilter: true,
                        onEnd: function(evt) {
                            const taskId = evt.item.getAttribute('data-id');
                            const targetQuadrant = evt.to.getAttribute('data-q');
                            const sourceQuadrant = evt.from.getAttribute('data-q');

                            if (targetQuadrant === sourceQuadrant && evt.oldIndex === evt.newIndex)
                                return;

                            // Update counters, empty messages AND dot colors
                            updateUI(evt.item, evt.from, evt.to, targetQuadrant);

                            // Send to backend
                            fetch(`/teams/{{ $team->id }}/tasks/${taskId}/move`, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken,
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        quadrant: targetQuadrant === 'completed' ?
                                            null : targetQuadrant,
                                        status: targetQuadrant === 'completed' ?
                                            'completed' : 'in_progress'
                                    })
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (!data.success) {
                                        alert('{{ __('tasks.move_error') }}');
                                        location.reload();
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    location.reload();
                                });
                        }
                    });
                });

                function updateUI(item, from, to, targetQ) {
                    // Update task appearance
                    const dot = item.querySelector('.rounded-full.shrink-0');
                    const link = item.querySelector('a');

                    if (targetQ === 'completed') {
                        if (dot) {
                            Object.values(qDotClasses).forEach(cls => cls.split(' ').forEach(c => dot.classList.remove(
                                c)));
                            completedDotClass.split(' ').forEach(c => dot.classList.add(c));
                            dot.classList.add('w-1.5', 'h-1.5');
                            dot.classList.remove('w-2', 'h-2');
                        }
                        if (link) {
                            link.classList.add('line-through', 'opacity-50', 'text-[10px]', 'sm:text-[11px]',
                                'text-gray-500');
                            link.classList.remove('text-[11px]', 'sm:text-sm', 'text-gray-700', 'dark:text-gray-400');
                        }
                        item.classList.add('bg-gray-100', 'dark:bg-gray-900/40');
                        item.classList.remove('px-3', 'py-2.5');
                        item.classList.add('px-3', 'py-2');
                    } else {
                        if (dot && qDotClasses[targetQ]) {
                            Object.values(qDotClasses).forEach(cls => cls.split(' ').forEach(c => dot.classList.remove(
                                c)));
                            dot.classList.remove(...completedDotClass.split(' '));
                            qDotClasses[targetQ].split(' ').forEach(c => dot.classList.add(c));
                            dot.classList.add('w-2', 'h-2');
                            dot.classList.remove('w-1.5', 'h-1.5');
                        }
                        if (link) {
                            link.classList.remove('line-through', 'opacity-50', 'text-[10px]', 'sm:text-[11px]',
                                'text-gray-500');
                            link.classList.add('text-[11px]', 'sm:text-sm', 'text-gray-700', 'dark:text-gray-400');
                        }
                        item.classList.remove('bg-gray-100', 'dark:bg-gray-900/40');
                        item.classList.remove('px-3', 'py-2');
                        item.classList.add('px-3', 'py-2.5');
                    }

                    // Update counts
                    const fromCount = from.closest('.quadrant-container') ? from.closest('.quadrant-container')
                        .querySelector('.q-count') : null;
                    const toCount = to.closest('.quadrant-container') ? to.closest('.quadrant-container').querySelector(
                        '.q-count') : null;

                    if (fromCount) fromCount.textContent = from.querySelectorAll('[data-id]').length;
                    if (toCount) toCount.textContent = to.querySelectorAll('[data-id]').length;

                    // Update empty messages
                    [from, to].forEach(l => {
                        let emptyMsg = l.querySelector('.empty-msg');
                        const hasItems = l.querySelectorAll('[data-id]').length > 0;

                        if (!hasItems) {
                            if (!emptyMsg) {
                                emptyMsg = document.createElement('div');
                                emptyMsg.className =
                                    'col-span-full py-8 text-center text-xs text-gray-700 italic empty-msg';
                                emptyMsg.textContent = l.getAttribute('data-q') === 'completed' ?
                                    '{{ __('teams.drop_to_complete') }}' : '{{ __('teams.no_tasks') }}';
                                l.appendChild(emptyMsg);
                            }
                        } else if (emptyMsg) {
                            emptyMsg.remove();
                        }
                    });
                }
            });
        </script>
    @endpush
</x-app-layout>
