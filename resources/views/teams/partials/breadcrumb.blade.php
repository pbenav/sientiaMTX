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

<nav class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500 mb-1 font-medium select-none"
    aria-label="breadcrumb">
    {{-- Root: Team list --}}
    <a href="{{ route('teams.index') }}"
        class="hover:text-violet-600 dark:hover:text-violet-400 transition-colors truncate max-w-[8rem]"
        title="{{ __('navigation.my_teams') ?? 'Mis equipos' }}">
        {{ __('navigation.my_teams') ?? 'Mis equipos' }}
    </a>

    {{-- Separator --}}
    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0 opacity-50" fill="none" viewBox="0 0 24 24"
        stroke="currentColor" stroke-width="2.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
    </svg>

    {{-- Current team --}}
    <a href="{{ route('teams.show', $team) }}"
        class="text-violet-600 dark:text-violet-400 font-bold hover:underline truncate max-w-[10rem]"
        title="{{ $team->name }}">
        {{ $team->name }}
    </a>

    @if ($currentPageLabel)
        {{-- Separator --}}
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0 opacity-50" fill="none" viewBox="0 0 24 24"
            stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
        </svg>

        {{-- Current page --}}
        <span class="text-gray-500 dark:text-gray-400 truncate max-w-[10rem]" aria-current="page">
            {{ $currentPageLabel }}
        </span>
    @endif
</nav>
