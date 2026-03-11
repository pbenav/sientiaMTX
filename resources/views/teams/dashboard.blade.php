<x-app-layout>
    @section('title', __('teams.eisenhower_matrix') . ' — ' . $team->name)

    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('teams.show', $team) }}"
                    class="text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white heading">
                        {{ __('teams.eisenhower_matrix') }}</h1>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $team->name }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <!-- Google Sync Controls -->
                @if (!auth()->user()->google_token)
                    <button onclick="openGoogleAuth()"
                        class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:border-red-500 hover:text-red-600 dark:hover:text-red-400 px-3 py-2 rounded-xl transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24"
                            fill="currentColor">
                            <path
                                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                            <path
                                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                                fill="#34A853" />
                            <path
                                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                                fill="#FBBC05" />
                            <path
                                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                                fill="#EA4335" />
                        </svg>
                        {{ __('Connect Google') }}
                    </button>
                @else
                    <form action="{{ route('google.sync') }}" method="POST" class="flex items-center gap-1">
                        @csrf
                        <input type="hidden" name="team_id" value="{{ $team->id }}">
                        <select name="visibility"
                            class="text-[10px] py-1 pl-2 pr-6 border-gray-200 dark:border-gray-700 bg-transparent rounded-lg focus:ring-violet-500 focus:border-violet-500 text-gray-500 dark:text-gray-400">
                            <option value="private" selected>{{ __('Private') }}</option>
                            <option value="public">{{ __('Public') }}</option>
                        </select>
                        <button type="submit"
                            class="flex items-center gap-1.5 text-xs text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50 hover:bg-emerald-50 dark:hover:bg-emerald-500/5 px-3 py-2 rounded-xl transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            {{ __('Sync') }}
                        </button>
                    </form>
                @endif

                <a href="{{ route('teams.members', $team) }}"
                    class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:border-violet-500 hover:text-violet-600 dark:hover:text-violet-400 px-3 py-2 rounded-xl transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    {{ __('teams.view_members') }}
                </a>
                @can('update', $team)
                    <a href="{{ route('teams.edit', $team) }}"
                        class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:border-violet-500 hover:text-violet-600 dark:hover:text-violet-400 px-3 py-2 rounded-xl transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37a1.724 1.724 0 002.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        {{ __('teams.settings') }}
                    </a>
                @endcan
                <a href="{{ route('teams.gantt', $team) }}"
                    class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:border-violet-500 hover:text-violet-600 dark:hover:text-violet-400 px-3 py-2 rounded-xl transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 012 2h2a2 2 0 012-2V7a2 2 0 01-2-2h-2a2 2 0 01-2 2" />
                    </svg>
                    {{ __('tasks.view_gantt') }}
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
                    <div class="border {{ $cfg['bg'] }} rounded-2xl sm:rounded-[2.5rem] flex flex-col min-h-[180px] sm:min-h-[320px] shadow-lg sm:shadow-2xl transition-all group/q"
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
                                    <div class="px-2 py-1.5 sm:px-3 sm:py-2 flex items-center gap-1.5 sm:gap-3 hover:bg-white/5 group transition-all cursor-grab active:cursor-grabbing rounded-xl relative overflow-hidden"
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
            class="bg-gray-50/50 dark:bg-gray-950/20 border border-gray-200 dark:border-gray-800/40 rounded-[2.5rem] overflow-hidden shadow-sm dark:shadow-none transition-colors">
            <div
                class="px-8 py-5 border-b border-gray-100 dark:border-white/5 bg-gray-50 dark:bg-gray-900/10 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="p-1.5 rounded-full border border-emerald-500/20 bg-emerald-500/5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-500/60"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                d="M5 13l4 4L19 7" />
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
            function openGoogleAuth() {
                const width = 600;
                const height = 700;
                const left = (window.innerWidth - width) / 2;
                const top = (window.innerHeight - height) / 2;
                const url = "{{ route('google.auth') }}?popup=1";

                const popup = window.open(url, 'GoogleAuth', `width=${width},height=${height},top=${top},left=${left}`);

                const messageHandler = function(event) {
                    if (event.data === 'google-auth-success') {
                        window.removeEventListener('message', messageHandler);
                        location.reload();
                    }
                };

                window.addEventListener('message', messageHandler);
            }

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
                        filter: 'a',
                        preventOnFilter: false,
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
                            link.classList.remove('line-through', 'opacity-50', 'text-[10px]', 'sm:text-[11px]',
                                'text-gray-500');
                            link.classList.add('text-[11px]', 'sm:text-sm', 'text-gray-700', 'dark:text-gray-400');
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
