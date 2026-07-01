@php
    $teamId = $team->id;
    $isMatrix = request()->routeIs('teams.eisenhower');
    $isTaskList = request()->routeIs('teams.tasks.index') || request()->routeIs('teams.tasks.show') || request()->routeIs('teams.activities.*');
    $isGantt = request()->routeIs('teams.gantt');
    $isKanban = request()->routeIs('teams.kanban');
    $isForum = request()->routeIs('teams.forum.*');
    $isMembers = request()->routeIs('teams.members');
    $isSettings = request()->routeIs('teams.edit');

    $views = [];

    if (auth()->user()->can('view', $team)) {
        $views = [
            [
                'name' => 'Escritorio',
                'route' => route('teams.time-reports', $teamId),
                'active' => request()->routeIs('teams.time-reports'),
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />',
            ],
            [
                'name' => __('forum.title') ?? 'Foro',
                'route' => route('teams.forum.index', $teamId),
                'active' => $isForum,
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />',
            ],
            [
                'name' => 'Expedientes',
                'route' => route('teams.expedientes.index', $teamId),
                'active' => request()->routeIs('teams.expedientes.*'),
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />',
                'divider' => true,
            ],
            [
                'name' => 'Actividades',
                'route' => route('teams.activities.index', $teamId),
                'active' => $isTaskList,
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />',
            ],
            [
                'name' => __('teams.eisenhower_matrix'),
                'route' => route('teams.eisenhower', $teamId),
                'active' => $isMatrix,
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />',
            ],
            [
                'name' => __('navigation.gantt'),
                'route' => route('teams.gantt', $teamId),
                'active' => $isGantt,
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 10h4V7a1 1 0 011-1h3a1 1 0 011 1v3h4V7a1 1 0 011-1h3a1 1 0 011 1v3h1M3 14h18M3 18h5v-3a1 1 0 011-1h3a1 1 0 011 1v3h4v-3a1 1 0 011-1h3a1 1 0 011 1v3h1" />',
            ],
            [
                'name' => __('navigation.kanban'),
                'route' => route('teams.kanban', $teamId),
                'active' => $isKanban,
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />',
            ],
            [
                'name' => 'Encuestas',
                'route' => route('teams.surveys.index', $teamId),
                'active' => request()->routeIs('teams.surveys.*'),
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />',
                'divider' => true,
            ],
        ];

        if (auth()->user()->hasAppointmentsEnabledForTeam($teamId)) {
            $views[] = [
                'name' => 'Citas Previas',
                'route' => route('appointments.index', $teamId),
                'active' => request()->routeIs('appointments.*'),
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />',
            ];
        }

        if (auth()->user()->hasMicrositesEnabledForTeam($teamId)) {
            $views[] = [
                'name' => 'Micrositios',
                'route' => route('teams.microsites.index', $teamId),
                'active' => request()->routeIs('teams.microsites.*') || request()->routeIs('public.microsites.*'),
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />',
            ];
        }

        $views[] = [
            'name' => __('teams.view_members'),
            'route' => route('teams.members', $teamId),
            'active' => $isMembers,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />',
        ];
    }

    if (auth()->user()->can('update', $team)) {
        $views[] = [
            'name' => __('teams.settings'),
            'route' => route('teams.edit', $teamId),
            'active' => $isSettings,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37a1.724 1.724 0 002.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />',
        ];
    }
@endphp

<div class="w-full">
<div class="flex w-full items-center bg-gray-100/50 dark:bg-gray-800/50 p-1.5 rounded-2xl border border-gray-200/50 dark:border-gray-700/50 shadow-sm overflow-x-auto no-scrollbar gap-1.5">
    {{-- Scrollable tab strip --}}
    <div class="flex-1 min-w-0">
        <div class="flex items-center gap-0.5">
            @foreach ($views as $index => $view)
                @if (!empty($view['divider']))
                    <div class="h-6 w-px bg-gray-300 dark:bg-gray-900 mx-1 shrink-0"></div>
                @endif
                <a href="{{ $view['route'] }}"
                    class="flex flex-col items-center justify-center gap-0.5 px-1.5 sm:px-3 py-2 rounded-xl transition-all shrink-0 min-w-max
                        {{ $view['active']
                            ? 'bg-white dark:bg-gray-800 text-violet-600 dark:text-violet-400 shadow-sm border border-gray-100 dark:border-gray-700'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-white/60 dark:hover:bg-gray-700/60' }}"
                    title="{{ $view['name'] }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 sm:h-5 w-4 sm:w-5 shrink-0" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="{{ $view['active'] ? '2.5' : '2' }}">
                        {!! $view['icon'] !!}
                    </svg>
                    <span class="hidden sm:block text-[9px] font-bold uppercase tracking-tight leading-none whitespace-nowrap">{{ $view['name'] }}</span>
                </a>
            @endforeach
            {{-- Divider + toggle --}}
            @if($isTaskList || $isKanban || $isGantt || $isMatrix)
                <div class="h-6 w-px bg-gray-300 dark:bg-gray-900 shrink-0"></div>
                <div class="flex items-center gap-1 shrink-0 ml-1">
                    @include('teams.partials.hide-completed-toggle')
                    @if($isTaskList)
                        @include('teams.partials.subtasks-visibility-toggle')
                    @endif
                </div>
            @endif
        </div>
    </div>

</div>
</div>
