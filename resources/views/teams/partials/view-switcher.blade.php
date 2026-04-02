@php
    $teamId = $team->id;
    $isMatrix = request()->routeIs('teams.dashboard');
    $isTaskList = request()->routeIs('teams.tasks.index') || request()->routeIs('teams.tasks.show');
    $isGantt = request()->routeIs('teams.gantt');
    $isKanban = request()->routeIs('teams.kanban');
    $isForum = request()->routeIs('teams.forum.*');
    $isMembers = request()->routeIs('teams.members');
    $isSettings = request()->routeIs('teams.edit');

    $layout = auth()->check() ? (auth()->user()->layout ?: 'horizontal') : request()->cookie('layout', 'horizontal');
@endphp

    <div
        class="flex items-center bg-gray-100/50 dark:bg-gray-800/50 p-1.5 rounded-2xl border border-gray-200/50 dark:border-gray-700/50 mr-2 shadow-sm overflow-x-auto no-scrollbar">
        @php
            $views = [
                [
                    'name' => __('forum.title') ?? 'Foro',
                    'route' => route('teams.forum.index', $teamId),
                    'active' => $isForum,
                    'icon' =>
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />',
                ],
                [
                    'name' => __('navigation.task_list'),
                    'route' => route('teams.tasks.index', $teamId),
                    'active' => $isTaskList,
                    'icon' =>
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />',
                ],
                [
                    'name' => __('teams.eisenhower_matrix'),
                    'route' => route('teams.dashboard', $teamId),
                    'active' => $isMatrix,
                    'icon' =>
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />',
                ],
                [
                    'name' => __('navigation.gantt'),
                    'route' => route('teams.gantt', $teamId),
                    'active' => $isGantt,
                    'icon' =>
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 012 2h2a2 2 0 012-2V7a2 2 0 01-2-2h-2a2 2 0 01-2 2" />',
                ],
                [
                    'name' => __('navigation.kanban'),
                    'route' => route('teams.kanban', $teamId),
                    'active' => $isKanban,
                    'icon' =>
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />',
                ],
                [
                    'name' => __('tasks.worked_time'),
                    'route' => route('teams.time-reports', $teamId),
                    'active' => request()->routeIs('teams.time-reports'),
                    'icon' =>
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />',
                ],
                [
                    'name' => __('teams.view_members'),
                    'route' => route('teams.members', $teamId),
                    'active' => $isMembers,
                    'icon' =>
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />',
                ],
            ];

            if (auth()->user()->can('update', $team)) {
                $views[] = [
                    'name' => __('teams.settings'),
                    'route' => route('teams.edit', $teamId),
                    'active' => $isSettings,
                    'icon' =>
                        '<path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37a1.724 1.724 0 002.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />',
                ];
            }
        @endphp

        @foreach ($views as $index => $view)
            @if ($index === 4)
                <div class="h-6 w-px bg-gray-200 dark:bg-gray-700 mx-2 self-center"></div>
            @endif
            <a href="{{ $view['route'] }}"
                class="flex items-center gap-2 px-3 py-2 rounded-xl text-[10px] font-bold transition-all {{ $view['active'] ? 'bg-white dark:bg-gray-700 text-violet-600 dark:text-violet-400 shadow-sm border border-gray-100 dark:border-gray-600' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}"
                title="{{ $view['name'] }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2.5">
                    {!! $view['icon'] !!}
                </svg>
                <span class="text-[9px] uppercase tracking-widest whitespace-nowrap">{{ $view['name'] }}</span>
            </a>
        @endforeach

        <div class="h-6 w-px bg-gray-200 dark:bg-gray-700 mx-2 self-center"></div>
        @include('teams.partials.hide-completed-toggle')
    </div>
