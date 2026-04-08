@auth
    @php
        $layout = auth()->check() ? (auth()->user()->layout ?: 'horizontal') : request()->cookie('layout', 'horizontal');
    @endphp

    @if ($layout === 'vertical')
        <!-- Overlay for mobile when sidebar is open -->
    <div x-show="sidebarOpen" @click="sidebarOpen = false" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-30 lg:hidden"
        style="display: none" x-cloak></div>

    <!-- Sidebar for Vertical Layout -->
    <aside id="sidebar"
        class="fixed inset-y-0 left-0 z-40 w-64 transition-transform duration-300 border-r border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 backdrop-blur-xl"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
        <div class="flex flex-col h-full px-4 py-6 overflow-y-auto">
            <!-- Logo area with Toggle Button -->
            <div class="mb-10 px-2 flex items-center justify-between gap-2">
                <a href="{{ auth()->check() ? (request()->route('team') ? route('teams.dashboard', request()->route('team')) : route('dashboard')) : route('home') }}"
                    class="flex items-center gap-2 group min-w-0">
                    <div
                        class="w-8 h-8 rounded-lg bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center shadow-lg group-hover:shadow-violet-500/30 transition-all duration-300 shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                            stroke-linejoin="round">
                            <rect x="3" y="3" width="8" height="8" rx="1" />
                            <rect x="13" y="3" width="8" height="8" rx="1" />
                            <rect x="3" y="13" width="8" height="8" rx="1" />
                            <rect x="13" y="13" width="8" height="8" rx="1" />
                        </svg>
                    </div>
                    <span class="font-bold text-gray-900 dark:text-white text-lg tracking-tight truncate"
                        style="font-family:'Space Grotesk',sans-serif">sientia<span
                            class="text-violet-600 dark:text-violet-400">MTX</span></span>
                </a>

                <!-- Toggle button inside sidebar (Visible when deployed) -->
                <button @click="sidebarOpen = false"
                    class="p-2 -mr-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors shrink-0"
                    title="{{ __('Close Sidebar') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 space-y-2">
                @auth
                    <!-- Workday Compact Timer -->
                    <div class="px-3 mb-4">
                        @include('layouts.partials.workday-timer', ['compact' => true])
                    </div>

                    <div class="pt-4 pb-2 px-3">
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ __('navigation.my_teams') }}</span>
                    </div>
                    <a href="{{ route('teams.index') }}"
                        class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-xl transition-all {{ request()->routeIs('teams.index') ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <span class="truncate">{{ __('Ver todos') }}</span>
                    </a>

                    @if (request()->route('team'))
                        @php
                            $currentTeamId = is_object(request()->route('team'))
                                ? request()->route('team')->id
                                : request()->route('team');
                            $currentTeamLabel = is_object(request()->route('team'))
                                ? request()->route('team')->name
                                : \App\Models\Team::find($currentTeamId)->name;
                            $isTeamRoute =
                                request()->routeIs('teams.dashboard') ||
                                request()->routeIs('teams.tasks.*') ||
                                request()->routeIs('teams.gantt') ||
                                request()->routeIs('teams.kanban') ||
                                request()->routeIs('teams.forum.*') ||
                                request()->routeIs('teams.members') ||
                                request()->routeIs('teams.edit');
                        @endphp
                        @if ($isTeamRoute)
                            <div x-data="{ open: true }"
                                class="mt-1 mb-2 ml-4 relative before:absolute before:inset-y-0 before:left-[-11px] before:w-px before:bg-gray-200 dark:before:bg-gray-800">
                                <button @click="open = !open"
                                    class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors cursor-pointer rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <div class="flex items-center gap-2 overflow-hidden relative">
                                        <!-- Connective line -->
                                        <div class="absolute left-[-24px] top-1/2 w-4 h-[1px] bg-gray-200 dark:bg-gray-800">
                                        </div>
                                        <div
                                            class="w-5 h-5 rounded-md bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 flex items-center justify-center text-[10px] font-bold shrink-0 z-10 relative">
                                            {{ strtoupper(substr($currentTeamLabel, 0, 1)) }}
                                        </div>
                                        <span class="truncate">{{ $currentTeamLabel }}</span>
                                    </div>
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="h-4 w-4 shrink-0 transition-transform duration-200 text-gray-400"
                                        :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>

                                <div x-show="open" x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 -translate-y-2"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-150"
                                    x-transition:leave-start="opacity-100 translate-y-0"
                                    x-transition:leave-end="opacity-0 -translate-y-2" class="pl-2 pr-1 py-1 space-y-1">
                                    <a href="{{ route('teams.forum.index', $currentTeamId) }}"
                                        class="flex items-center gap-2 px-3 py-2 text-xs rounded-xl transition-all {{ request()->routeIs('teams.forum.*') ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                                        </svg>
                                        <span class="truncate">{{ __('forum.title') ?? 'Anuncios' }}</span>
                                    </a>
                                    <a href="{{ route('teams.tasks.index', $currentTeamId) }}"
                                        class="flex items-center gap-2 px-3 py-2 text-xs rounded-xl transition-all {{ request()->routeIs('teams.tasks.index') || request()->routeIs('teams.tasks.show') ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        <span
                                            class="truncate">{{ __('navigation.task_list') ?? 'Tareas' }}</span>
                                    </a>
                                    <a href="{{ route('teams.dashboard', $currentTeamId) }}"
                                        class="flex items-center gap-2 px-3 py-2 text-xs rounded-xl transition-all {{ request()->routeIs('teams.dashboard') ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                        </svg>
                                        <span
                                            class="truncate">{{ __('teams.eisenhower_matrix') ?? 'Eisenhower' }}</span>
                                    </a>
                                    <a href="{{ route('teams.gantt', $currentTeamId) }}"
                                        class="flex items-center gap-2 px-3 py-2 text-xs rounded-xl transition-all {{ request()->routeIs('teams.gantt') ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 012 2h2a2 2 0 012-2V7a2 2 0 01-2-2h-2a2 2 0 01-2 2" />
                                        </svg>
                                        <span class="truncate">{{ __('navigation.gantt') ?? 'Diagrama de Gantt' }}</span>
                                    </a>
                                    <a href="{{ route('teams.kanban', $currentTeamId) }}"
                                        class="flex items-center gap-2 px-3 py-2 text-xs rounded-xl transition-all {{ request()->routeIs('teams.kanban') ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                                        </svg>
                                        <span class="truncate">{{ __('navigation.kanban') ?? 'Tablero Kanban' }}</span>
                                    </a>
                                    <a href="{{ route('teams.members', $currentTeamId) }}"
                                        class="flex items-center gap-2 px-3 py-2 text-xs rounded-xl transition-all {{ request()->routeIs('teams.members') ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span class="truncate">{{ __('teams.members') }}</span>
                                    </a>
                                    @if ($currentTeam = \App\Models\Team::find($currentTeamId))
                                        @can('update', $currentTeam)
                                            <a href="{{ route('teams.edit', $currentTeamId) }}"
                                                class="flex items-center gap-2 px-3 py-2 text-xs rounded-xl transition-all {{ request()->routeIs('teams.edit') ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white' }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                <span class="truncate">{{ __('teams.settings') }}</span>
                                            </a>
                                            <a href="{{ route('teams.skills.index', $currentTeamId) }}"
                                                class="flex items-center gap-2 px-3 py-2 text-xs rounded-xl transition-all {{ request()->routeIs('teams.skills.index') ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white' }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                                </svg>
                                                <span class="truncate">Especialidades</span>
                                            </a>
                                        @endcan
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endif

                    <div class="pt-4 pb-2 px-3">
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ __('GESTIÓN') }}</span>
                    </div>

                    <!-- Storage -->
                    <a href="{{ route('media.index') }}"
                        class="group flex flex-col gap-2 px-3 py-3 rounded-xl transition-all {{ request()->routeIs('media.index') ? 'bg-violet-50 dark:bg-violet-500/10' : 'hover:bg-gray-50 dark:hover:bg-gray-800/50' }}">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 {{ request()->routeIs('media.index') ? 'text-violet-600 dark:text-violet-400' : 'text-gray-400 dark:text-gray-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                                </svg>
                                <span class="text-sm font-medium {{ request()->routeIs('media.index') ? 'text-violet-700 dark:text-violet-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">{{ __('ALMACENAMIENTO') }}</span>
                            </div>
                            <span class="text-[10px] font-bold text-gray-400">75%</span>
                        </div>
                        <div class="w-full h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden shadow-inner flex">
                            <div class="h-full bg-gradient-to-r from-violet-500 to-indigo-600 rounded-full" style="width: 75%"></div>
                        </div>
                    </a>

                    <a href="{{ route('docs') }}"
                        class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-xl transition-all {{ request()->is('docs*') ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18 18.247 18.477 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        {{ __('Documentación') }}
                    </a>

                    @can('admin')
                        <div class="pt-4 pb-2">
                            <span
                                class="px-3 text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ __('Admin') }}</span>
                        </div>

                        <a href="{{ route('settings.teams') }}"
                            class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-xl transition-all {{ request()->routeIs('settings.teams') ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ __('navigation.teams_management') ?? 'Equipos' }}
                        </a>

                        <a href="{{ route('settings.users') }}"
                            class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-xl transition-all {{ request()->routeIs('settings.users') ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            {{ __('navigation.users') }}
                        </a>

                        <a href="{{ route('settings.mail') }}"
                            class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-xl transition-all {{ request()->routeIs('settings.mail*') ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ __('navigation.settings') }}
                        </a>
                    @endcan
                @endauth
            </nav>

            @auth
                <div class="mt-auto pt-6 border-t border-gray-100 dark:border-gray-800">
                    <div class="flex items-center gap-3 px-2 mb-4">
                        <div
                            class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center text-xs font-bold text-white shadow-lg shrink-0">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-black text-gray-900 dark:text-white truncate uppercase tracking-tight">
                                {{ explode(' ', auth()->user()->name)[0] }}</p>
                            <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate">{{ auth()->user()->email }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <a href="{{ route('profile.edit') }}"
                            class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-gray-500 dark:text-gray-400 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            {{ __('navigation.profile') }}
                        </a>
                        <a href="{{ route('credits') }}"
                            class="flex items-center gap-3 px-3 py-2 text-sm font-medium text-gray-500 dark:text-gray-400 rounded-xl hover:bg-amber-50 dark:hover:bg-amber-500/10 hover:text-amber-600 dark:hover:text-amber-400 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                            {{ __('credits.title') }}
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="w-full flex items-center gap-3 px-3 py-2 text-sm font-bold text-red-600 dark:text-red-400 rounded-xl hover:bg-red-50 dark:hover:bg-red-500/10 transition-all text-left">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                {{ __('navigation.logout') }}
                            </button>
                        </form>
                    </div>
                </div>
            @endauth
        </div>
    </aside>
@endif
@endauth
