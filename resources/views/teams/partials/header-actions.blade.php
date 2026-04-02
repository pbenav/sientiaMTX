@php
    $teamId = $team->id;
    $isMatrix = request()->routeIs('teams.dashboard');
    $isTaskList = request()->routeIs('teams.tasks.index') || request()->routeIs('teams.tasks.show');
    $isGantt = request()->routeIs('teams.gantt');
    $isForum = request()->routeIs('teams.forum.*');
    $isMembers = request()->routeIs('teams.members');
    $isSettings = request()->routeIs('teams.edit');
@endphp

<div class="flex items-center gap-2 sm:gap-3 flex-wrap">
    <!-- Management Actions -->
    <div class="flex items-center gap-2 flex-wrap">

        <a href="{{ route('teams.tasks.create', $team) }}"
            class="flex items-center gap-1.5 text-xs bg-violet-600 hover:bg-violet-500 text-white px-4 py-2.5 rounded-xl transition-all font-bold shadow-lg shadow-violet-500/20 active:scale-95 ml-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            <span class="hidden lg:inline">{{ __('tasks.create') }}</span>
        </a>
    </div>
</div>
