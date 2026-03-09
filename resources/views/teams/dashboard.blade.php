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

    <!-- Matrix Labels & Grid -->
    <div class="relative max-w-7xl mx-auto">
        <!-- Horizontal Urgency labels -->
        <div class="grid grid-cols-2 mb-2 ml-8 text-[10px] font-bold uppercase tracking-widest text-gray-500">
            <div class="text-center">{{ __('tasks.urgent') }}</div>
            <div class="text-center">{{ __('tasks.not_urgent') }}</div>
        </div>

        <div class="flex gap-2">
            <!-- Vertical Importance label -->
            <div
                class="flex flex-col justify-around py-12 text-[10px] font-bold uppercase tracking-widest text-gray-500 [writing-mode:vertical-lr] rotate-180">
                <div class="text-center whitespace-nowrap">{{ __('tasks.important') }}</div>
                <div class="text-center whitespace-nowrap">{{ __('tasks.not_important') }}</div>
            </div>

            <!-- Matrix grid -->
            <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach ([1, 2, 3, 4] as $q)
                    @php
                        $cfg = $quadrantConfig[$q];
                        $qTasks = $quadrants[$q];
                    @endphp
                    <div class="border {{ $cfg['bg'] }} rounded-2xl flex flex-col min-h-64 shadow-lg transition-all"
                        data-quadrant="{{ $q }}">
                        <!-- Quadrant header -->
                        <div class="px-4 py-3 border-b border-white/5 flex items-center gap-3">
                            <div class="w-5 h-5 rounded-full shrink-0"
                                style="background:{{ $cfg['color'] }}; box-shadow: 0 0 12px {{ $cfg['color'] }}80">
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-white heading">
                                    {{ __('tasks.quadrants.' . $q . '.label') }}
                                </p>
                                <p class="text-xs text-gray-400">{{ __('tasks.quadrants.' . $q . '.description') }}</p>
                            </div>
                            <span
                                class="shrink-0 text-xs font-medium px-2 py-0.5 rounded-full {{ $cfg['badge'] }} q-count">
                                {{ count($qTasks) }}
                            </span>
                        </div>
                        <!-- Tip -->
                        <div class="px-4 py-1.5 text-xs text-gray-600 italic border-b border-white/5">
                            💡 {{ __('tasks.quadrants.' . $q . '.tip') }}
                        </div>
                        <!-- Task list -->
                        <div class="flex-1 overflow-y-auto divide-y divide-white/5 quadrant-list p-1 min-h-[150px]"
                            data-q="{{ $q }}">
                            @forelse($qTasks as $task)
                                <div class="px-3 py-2.5 flex items-center gap-3 hover:bg-white/5 group transition-all cursor-grab active:cursor-grabbing rounded-lg mx-1 my-0.5 border border-transparent hover:border-white/10"
                                    data-id="{{ $task->id }}">
                                    <!-- Draggable handle -->
                                    <div class="text-gray-700 group-hover:text-gray-500 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 8h16M4 16h16" />
                                        </svg>
                                    </div>
                                    <!-- Status dot -->
                                    <div class="w-2 h-2 rounded-full shrink-0 {{ $cfg['dot'] }}"></div>
                                    <a href="{{ route('teams.tasks.show', [$team, $task]) }}"
                                        class="flex-1 text-sm text-gray-300 hover:text-white truncate transition-colors">
                                        {{ $task->title }}
                                    </a>
                                    @if ($task->due_date)
                                        <span class="shrink-0 text-xs text-gray-500">
                                            {{ $task->due_date->format('d/m') }}
                                        </span>
                                    @endif
                                </div>
                            @empty
                                <div class="px-4 py-8 text-center text-xs text-gray-600 empty-msg">
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
    <div class="mt-8 max-w-7xl mx-auto px-6">
        <div class="bg-gray-950/30 border border-gray-800/50 rounded-2xl overflow-hidden">
            <div class="px-5 py-3 border-b border-white/5 bg-gray-900/20 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-500/70" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400">
                        {{ __('teams.completed_tasks') }}</h3>
                </div>
                <span
                    class="text-[10px] font-medium text-gray-600 q-count">{{ $tasks->where('status', 'completed')->count() }}</span>
            </div>

            <div class="min-h-[100px] quadrant-list p-2 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2"
                data-q="completed">
                @forelse($tasks->where('status', 'completed') as $task)
                    <div class="px-3 py-2 flex items-center gap-3 bg-gray-900/40 hover:bg-white/5 group transition-all cursor-grab active:cursor-grabbing rounded-xl border border-white/5"
                        data-id="{{ $task->id }}">
                        <div class="w-1.5 h-1.5 rounded-full shrink-0 bg-emerald-500/40"></div>
                        <a href="{{ route('teams.tasks.show', [$team, $task]) }}"
                            class="flex-1 text-[11px] text-gray-500 line-through truncate">
                            {{ $task->title }}
                        </a>
                    </div>
                @empty
                    <div class="col-span-full py-10 text-center text-xs text-gray-700 italic empty-msg">
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
