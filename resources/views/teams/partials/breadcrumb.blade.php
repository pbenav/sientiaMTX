@php
    // Auto-detect current page label from route name
    $routeName = request()->route()->getName();
    $currentPageLabel = match (true) {
        str_contains($routeName, 'tasks.create') => __('tasks.create'),
        str_contains($routeName, 'tasks.edit') => __('tasks.edit'),
        str_contains($routeName, 'tasks.show') => __('navigation.task_detail') ?? __('tasks.view_detail'),
        str_contains($routeName, 'tasks.index') => __('navigation.task_list'),
        str_contains($routeName, 'gantt') => __('navigation.gantt'),
        str_contains($routeName, 'members') => __('teams.view_members'),
        str_contains($routeName, 'teams.edit') => __('teams.settings'),
        str_contains($routeName, 'dashboard') => __('teams.eisenhower_matrix'),
        default => null,
    };
@endphp

<nav class="flex items-center gap-1 text-[10px] sm:text-xs text-gray-400 dark:text-gray-500 mb-1 font-medium select-none whitespace-nowrap overflow-hidden max-w-full"
    aria-label="breadcrumb">
    {{-- Root: Team list --}}
    <div class="flex items-center min-w-0 flex-shrink">
        <a href="{{ route('teams.index') }}"
            class="hover:text-violet-600 dark:hover:text-violet-400 transition-colors truncate max-w-[3rem] sm:max-w-[8rem]"
            title="{{ __('navigation.my_teams') ?? 'Mis equipos' }}">
            {{ __('navigation.my_teams') ?? 'Mis equipos' }}
        </a>
    </div>

    {{-- Separator --}}
    <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 shrink-0 opacity-40 mx-0.5" fill="none"
        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
    </svg>

    {{-- Current team --}}
    <div class="flex items-center min-w-0 flex-shrink">
        <a href="{{ route('teams.show', $team) }}"
            class="text-violet-600 dark:text-violet-400 font-bold hover:underline truncate max-w-[5rem] sm:max-w-[12rem]"
            title="{{ $team->name }}">
            {{ $team->name }}
        </a>
    </div>

    @if ($currentPageLabel)
        {{-- Separator --}}
        <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 shrink-0 opacity-40 mx-0.5" fill="none"
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
        </svg>

        {{-- Current page --}}
        <div class="flex items-center min-w-0">
            <span class="text-gray-500 dark:text-gray-400 truncate max-w-[6rem] sm:max-w-[15rem]" aria-current="page">
                {{ $currentPageLabel }}
            </span>
        </div>
    @endif
</nav>
