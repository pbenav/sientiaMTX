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
            class="flex justify-between mb-8 ml-16 mr-4 text-[11px] font-bold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">
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
            <div class="flex items-center py-8">
                <span
                    class="text-[11px] font-bold uppercase tracking-[0.2em] text-gray-500 [writing-mode:vertical-rl] rotate-180 whitespace-nowrap select-none">
                    ← {{ __('tasks.not_important') }} · {{ __('tasks.important') }} →
                </span>
            </div>

            <!-- Matrix grid -->
            <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-8">
                @foreach ([2, 1, 4, 3] as $q)
                    @php
                        $cfg = $quadrantConfig[$q];
                        $qTasks = $quadrants[$q];
                    @endphp
                    <div class="border {{ $cfg['bg'] }} rounded-[2.5rem] flex flex-col min-h-[320px] shadow-2xl transition-all group/q"
                        data-quadrant="{{ $q }}">
                        <!-- Quadrant header -->
                        <div class="px-8 py-6 border-b border-white/5 flex flex-col gap-1 relative">
                            <div class="flex items-center gap-3">
                                <div class="w-3 h-3 rounded-full shrink-0"
                                    style="background:{{ $cfg['color'] }}; box-shadow: 0 0 20px {{ $cfg['color'] }}">
                                </div>
                                <span
                                    class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-tighter">Q{{ $q }}</span>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white heading mt-1">
                                {{ __('tasks.quadrants.' . $q . '.label') }}
                            </h2>
                            <p class="text-[11px] text-gray-500 dark:text-gray-500 font-medium">
                                {{ __('tasks.quadrants.' . $q . '.description') }}
                            </p>

                            <span
                                class="absolute top-8 right-8 text-[11px] font-bold bg-gray-100 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-gray-500 dark:text-gray-400 w-7 h-7 flex items-center justify-center rounded-full q-count">
                                {{ count($qTasks) }}
                            </span>
                        </div>

                        <!-- Task list -->
                        <div class="flex-1 overflow-y-auto quadrant-list p-4 min-h-[180px] space-y-2"
                            data-q="{{ $q }}">
                            @forelse($qTasks as $task)
                                <div class="px-3 py-2 flex items-center gap-3 hover:bg-white/5 group transition-all cursor-grab active:cursor-grabbing rounded-xl"
                                    data-id="{{ $task->id }}">
                                    <!-- Status dot -->
                                    <div class="w-1.5 h-1.5 rounded-full shrink-0 {{ $cfg['dot'] }}"></div>
                                    <a href="{{ route('teams.tasks.show', [$team, $task]) }}"
                                        class="flex-1 text-sm text-gray-700 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white truncate transition-colors">
                                        {{ $task->title }}
                                    </a>
                                    @if ($task->due_date)
                                        <span class="shrink-0 text-[10px] text-gray-600 font-mono">
                                            {{ $task->due_date->format('d/m') }}
                                        </span>
                                    @endif
                                </div>
                            @empty
                                <div
                                    class="flex items-center justify-center flex-1 text-[11px] text-gray-600 italic empty-msg py-12">
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
            class="bg-gray-50/50 dark:bg-gray-950/20 border border-gray-200 dark:border-gray-800/40 rounded-[2.5rem] overflow-hidden shadow-sm dark:shadow-none transition-colors">
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
                    <div class="px-4 py-3 flex items-center gap-4 bg-white dark:bg-gray-900/20 hover:bg-gray-100 dark:hover:bg-white/10 group transition-all cursor-grab active:cursor-grabbing rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm dark:shadow-none"
                        data-id="{{ $task->id }}">
                        <div class="w-1.5 h-1.5 rounded-full shrink-0 bg-emerald-500/20"></div>
                        <a href="{{ route('teams.tasks.show', [$team, $task]) }}"
                            class="flex-1 text-[12px] text-gray-400 dark:text-gray-600 line-through truncate group-hover:text-gray-600 dark:group-hover:text-gray-400 transition-colors">
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
                        ghostClass: 'bg-white/10',
                        chosenClass: 'bg-white/5',
                        dragClass: 'opacity-50',
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
                            link.classList.add('line-through', 'opacity-50', 'text-[11px]', 'text-gray-500');
                            link.classList.remove('text-sm', 'text-gray-300');
                        }
                        item.classList.add('bg-gray-900/40');
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
                            link.classList.remove('line-through', 'opacity-50', 'text-[11px]', 'text-gray-500');
                            link.classList.add('text-sm', 'text-gray-300');
                        }
                        item.classList.remove('bg-gray-900/40');
                        item.classList.remove('px-3', 'py-2');
                        item.classList.add('px-3', 'py-2.5');
                    }

                    // Update counts
                    const fromCount = from.closest('.border') ? from.closest('.border').querySelector('.q-count') :
                        (from.closest('.bg-gray-950/30') ? from.closest('.bg-gray-950/30').querySelector('.q-count') :
                            null);
                    const toCount = to.closest('.border') ? to.closest('.border').querySelector('.q-count') :
                        (to.closest('.bg-gray-950/30') ? to.closest('.bg-gray-950/30').querySelector('.q-count') :
                            null);

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
