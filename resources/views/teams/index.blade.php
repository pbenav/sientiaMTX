<x-app-layout>
    @section('title', __('teams.my_teams'))

    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-gray-900 dark:text-white heading">{{ __('teams.my_teams') }}</h1>
                <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mt-0.5">
                    {{ __('teams.title') }}
                </p>
            </div>
            <a href="{{ route('teams.create') }}"
                class="flex items-center gap-2 bg-violet-600 hover:bg-violet-500 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition-all shadow-lg shadow-violet-500/20 active:scale-95">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span class="hidden sm:inline">{{ __('teams.create') }}</span>
            </a>
        </div>
    </x-slot>

    @if ($teams->isEmpty())
        <div class="flex flex-col items-center justify-center py-24 text-center">
            <div class="w-20 h-20 rounded-2xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400 dark:text-gray-600"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-300 heading mb-2">{{ __('teams.no_teams') }}
            </h2>
            <p class="text-gray-500 text-sm max-w-sm mb-6">{{ __('teams.create_first') }}</p>
            <a href="{{ route('teams.create') }}"
                class="bg-violet-600 hover:bg-violet-500 text-white text-sm font-medium px-6 py-2.5 rounded-xl transition-all">
                {{ __('teams.create') }}
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5" id="teams-grid">
            @foreach ($teams as $team)
                @php
                    $total = $team->tasks()->count();
                    $done = $team->tasks()->where('status', 'completed')->count();
                    $progress = $total > 0 ? round(($done / $total) * 100) : 0;
                @endphp
                <div data-id="{{ $team->id }}"
                    class="group bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-violet-600 dark:hover:border-violet-800 rounded-2xl p-5 flex flex-col gap-4 transition-all hover:shadow-xl hover:shadow-violet-500/10 cursor-grab active:cursor-grabbing">
                    <div class="flex items-start justify-between">
                        <div
                            class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-600 to-indigo-700 flex items-center justify-center text-white font-bold text-sm">
                            {{ strtoupper(substr($team->name, 0, 2)) }}
                        </div>
                        <a href="{{ route('teams.members', $team) }}"
                            class="text-xs text-gray-600 dark:text-gray-500 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-gray-300 px-2 py-1 rounded-full transition-colors"
                            title="{{ __('teams.members') }}">
                            {{ __('teams.members_count', ['count' => $team->members->count()]) }}
                        </a>
                    </div>
                    <div>
                        <a href="{{ route('teams.dashboard', $team) }}">
                            <h3
                                class="text-base font-semibold text-gray-900 dark:text-white heading hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                {{ $team->name }}</h3>
                        </a>
                        @if ($team->description)
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">
                                {{ $team->description }}</p>
                        @endif
                    </div>
                    <!-- Progress bar -->
                    <div>
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1.5">
                            <span>{{ __('teams.tasks_count', ['count' => $total]) }}</span>
                            <span>{{ $progress }}%</span>
                        </div>
                        <div class="h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-violet-500 to-indigo-500 rounded-full"
                                style="width: {{ $progress }}%; transition: none !important;"></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 pt-1">
                        <!-- List View -->
                        <a href="{{ route('teams.tasks.index', $team) }}"
                            class="flex-1 flex items-center justify-center gap-1.5 py-2.5 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-all border border-gray-200 dark:border-gray-700"
                            title="{{ __('navigation.task_list') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </a>

                        <!-- Matrix View -->
                        <a href="{{ route('teams.dashboard', $team) }}"
                            class="flex-1 flex items-center justify-center gap-1.5 py-2.5 bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-400 rounded-xl hover:bg-violet-100 dark:hover:bg-violet-900/40 transition-all border border-violet-100 dark:border-violet-800/50"
                            title="{{ __('teams.eisenhower_matrix') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                        </a>

                        <!-- Gantt View -->
                        <a href="{{ route('teams.gantt', $team) }}"
                            class="flex-1 flex items-center justify-center gap-1.5 py-2.5 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-all border border-gray-200 dark:border-gray-700"
                            title="{{ __('navigation.gantt') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 10h10M3 14h18M11 18h10M3 6h6" />
                            </svg>
                        </a>

                        <!-- Kanban View -->
                        <a href="{{ route('teams.kanban', $team) }}"
                            class="flex-1 flex items-center justify-center gap-1.5 py-2.5 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-all border border-gray-200 dark:border-gray-700"
                            title="{{ __('navigation.kanban') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 012 2h2a2 2 0 012-2V7a2 2 0 01-2-2h-2a2 2 0 01-2 2" />
                            </svg>
                        </a>

                        <!-- Members -->
                        <a href="{{ route('teams.members', $team) }}"
                            class="flex-1 flex items-center justify-center gap-1.5 py-2.5 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-all border border-gray-200 dark:border-gray-700"
                            title="{{ __('teams.members') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </a>

                        @can('update', $team)
                            <!-- Settings -->
                            <a href="{{ route('teams.edit', $team) }}"
                                class="flex-1 flex items-center justify-center gap-1.5 py-2.5 bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-all border border-gray-200 dark:border-gray-700"
                                title="{{ __('teams.settings') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37a1.724 1.724 0 002.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </a>
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-8">{{ $teams->links() }}</div>

        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const grid = document.getElementById('teams-grid');
                if (!grid) return;

                new Sortable(grid, {
                    animation: 250,
                    ghostClass: 'opacity-40',
                    chosenClass: 'scale-[1.02]',
                    dragClass: 'shadow-2xl',
                    onEnd: function() {
                        const order = Array.from(grid.querySelectorAll('[data-id]')).map(el => el.dataset.id);
                        
                        fetch('{{ route('teams.update-order') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ order: order })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Optional: simple success toast or feedback
                                console.log('Orden guardado');
                            }
                        })
                        .catch(err => console.error('Error guardando orden:', err));
                    }
                });
            });
        </script>
        @endpush
    @endif
</x-app-layout>
