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
        str_contains($routeName, 'surveys') => 'Encuestas',
        str_contains($routeName, 'dashboard') => __('teams.eisenhower_matrix'),
        str_contains($routeName, 'appointments.settings') => 'Configuración de Citas',
        str_contains($routeName, 'appointments.services') => 'Servicios de Citas',
        str_contains($routeName, 'appointments.blocks') => 'Bloqueos de Citas',
        str_contains($routeName, 'appointments.list') => 'Listado de Citas',
        str_contains($routeName, 'appointments.index') => 'Agenda de Citas',
        str_contains($routeName, 'appointments.show') => 'Detalle de Cita',
        default => null,
    };
@endphp

<nav class="flex items-center gap-1 text-[10px] sm:text-xs text-gray-400 dark:text-gray-500 mb-1 font-medium select-none whitespace-nowrap overflow-hidden max-w-full"
    aria-label="breadcrumb">
    {{-- Root: Team list --}}
    <div class="flex items-center min-w-0 flex-shrink">
        <a href="{{ route('teams.index') }}"
            class="hover:text-violet-600 dark:hover:text-violet-400 transition-colors truncate max-w-[5rem] sm:max-w-[8rem]"
            title="{{ __('navigation.my_teams') ?? 'Mis equipos' }}">
            {{ __('navigation.my_teams') ?? 'Mis equipos' }}
        </a>
    </div>

    @if(isset($team) && $team)
        {{-- Separator --}}
        <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 shrink-0 opacity-40 mx-0.5" fill="none"
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
        </svg>

        {{-- Current team --}}
        <div class="flex items-center min-w-0 flex-shrink">
            <a href="{{ route('teams.show', $team) }}"
                class="text-violet-600 dark:text-violet-400 font-bold hover:underline truncate max-w-[8rem] sm:max-w-[12rem]"
                title="{{ $team->name }}">
                {{ $team->name }}
            </a>
        </div>
    @endif

    @if ($currentPageLabel)
        {{-- Separator --}}
        <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 shrink-0 opacity-40 mx-0.5" fill="none"
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
        </svg>

        {{-- Current page --}}
        <div class="flex items-center min-w-0">
            <span class="text-gray-500 dark:text-gray-400 truncate max-w-[10rem] sm:max-w-[15rem]" aria-current="page">
                {{ $currentPageLabel }}
            </span>
        </div>
    @endif
    
    {{-- Global Create Activity Button (Mobile-friendly, unobtrusive) --}}
    @php
        $globalCreateTeam = $team ?? auth()->user()->favoriteTeam ?? auth()->user()->teams()->first();
    @endphp
    @if($globalCreateTeam)
        <div class="flex items-center ml-2 border-l border-gray-200 dark:border-gray-700/80 pl-2">
            <a href="{{ route('teams.activities.create', $globalCreateTeam) }}"
                class="flex items-center justify-center gap-1 px-1.5 py-0.5 bg-violet-50 text-violet-600 hover:bg-violet-600 hover:text-white dark:bg-violet-900/30 dark:text-violet-400 dark:hover:bg-violet-600 dark:hover:text-white transition-all rounded shadow-sm group"
                title="Crear Nueva Actividad Rápida">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 group-hover:rotate-90 transition-transform" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span class="text-[9px] font-black uppercase tracking-widest leading-none mt-0.5">Nueva</span>
            </a>
        </div>
    @endif
</nav>
