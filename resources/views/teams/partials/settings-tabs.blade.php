<div class="flex items-center gap-2 mb-8 bg-gray-100/50 dark:bg-gray-800/50 p-1.5 rounded-2xl border border-gray-200/50 dark:border-gray-700/50 w-fit">
    <a href="{{ route('teams.edit', $team) }}" 
        class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all {{ request()->routeIs('teams.edit') ? 'bg-white dark:bg-gray-900 text-violet-600 dark:text-violet-400 shadow-sm border border-gray-100 dark:border-gray-800' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
        Información General
    </a>
    <a href="{{ route('teams.skills.index', $team) }}" 
        class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-widest transition-all {{ request()->routeIs('teams.skills.*') ? 'bg-white dark:bg-gray-900 text-violet-600 dark:text-violet-400 shadow-sm border border-gray-100 dark:border-gray-800' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
        Habilidades / Especialidades
    </a>
</div>
