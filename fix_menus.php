<?php

// 1. layouts/navigation.blade.php
$navPath = 'resources/views/layouts/navigation.blade.php';
$navContent = file_get_contents($navPath);

// Dropdown (desktop)
$navDropdownOld = <<< 'EOT'
                                    <x-slot name="content">
                                        <x-dropdown-link :href="route('teams.forum.index', $currentTeamId)">
                                            {{ __('forum.title') ?? 'Foro' }}
                                        </x-dropdown-link>
                                        <x-dropdown-link :href="route('teams.tasks.index', $currentTeamId)">
                                            {{ __('navigation.task_list') ?? 'Tareas' }}
                                        </x-dropdown-link>
                                        <x-dropdown-link :href="route('teams.surveys.index', $currentTeamId)">
                                            Encuestas
                                        </x-dropdown-link>
                                        <x-dropdown-link :href="route('teams.expedientes.index', $currentTeamId)">
                                            {{ __('Expedientes') }}
                                        </x-dropdown-link>
                                        @if(auth()->user()->hasAppointmentsEnabledForTeam($currentTeamId))
                                            <x-dropdown-link :href="route('appointments.index', $currentTeamId)">
                                                Citas Previas
                                            </x-dropdown-link>
                                        @endif
                                    </x-slot>
EOT;

$navDropdownNew = <<< 'EOT'
                                    <x-slot name="content">
                                        <x-dropdown-link :href="route('teams.forum.index', $currentTeamId)">
                                            {{ __('forum.title') ?? 'Foro' }}
                                        </x-dropdown-link>
                                        <div class="border-t border-gray-100 dark:border-gray-800"></div>
                                        <x-dropdown-link :href="route('teams.expedientes.index', $currentTeamId)">
                                            {{ __('Expedientes') }}
                                        </x-dropdown-link>
                                        <x-dropdown-link :href="route('teams.tasks.index', $currentTeamId)">
                                            {{ __('navigation.task_list') ?? 'Tareas' }}
                                        </x-dropdown-link>
                                        <div class="border-t border-gray-100 dark:border-gray-800"></div>
                                        <x-dropdown-link :href="route('teams.surveys.index', $currentTeamId)">
                                            Encuestas
                                        </x-dropdown-link>
                                        @if(auth()->user()->hasAppointmentsEnabledForTeam($currentTeamId))
                                            <x-dropdown-link :href="route('appointments.index', $currentTeamId)">
                                                Citas Previas
                                            </x-dropdown-link>
                                        @endif
                                    </x-slot>
EOT;

$navContent = str_replace($navDropdownOld, $navDropdownNew, $navContent);

// Mobile Nav Block
$navMobileOld = <<< 'EOT'
                        <x-responsive-nav-link :href="route('teams.forum.index', $currentTeamId)" :active="request()->routeIs('teams.forum.*')" class="text-sm">
                            {{ __('forum.title') ?? 'Foro' }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('teams.tasks.index', $currentTeamId)" :active="request()->routeIs('teams.tasks.*')" class="text-sm">
                            {{ __('navigation.task_list') ?? 'Tareas' }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('teams.surveys.index', $currentTeamId)" :active="request()->routeIs('teams.surveys.*')" class="text-sm">
                            Encuestas
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('teams.expedientes.index', $currentTeamId)" :active="request()->routeIs('teams.expedientes.*')" class="text-sm">
                            {{ __('Expedientes') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('teams.dashboard', $currentTeamId)" :active="request()->routeIs('teams.dashboard')" class="text-sm">
                            {{ __('teams.eisenhower_matrix') ?? 'Eisenhower' }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('teams.gantt', $currentTeamId)" :active="request()->routeIs('teams.gantt')" class="text-sm">
                            {{ __('navigation.gantt') ?? 'Diagrama de Gantt' }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('teams.kanban', $currentTeamId)" :active="request()->routeIs('teams.kanban')" class="text-sm">
                            {{ __('navigation.kanban') ?? 'Tablero Kanban' }}
                        </x-responsive-nav-link>
                        @if(auth()->user()->hasAppointmentsEnabledForTeam($currentTeamId))
                            <x-responsive-nav-link :href="route('appointments.index', $currentTeamId)" :active="request()->routeIs('appointments.*')" class="text-sm">
                                Citas Previas
                            </x-responsive-nav-link>
                        @endif
EOT;

$navMobileNew = <<< 'EOT'
                        <x-responsive-nav-link :href="route('teams.forum.index', $currentTeamId)" :active="request()->routeIs('teams.forum.*')" class="text-sm">
                            {{ __('forum.title') ?? 'Foro' }}
                        </x-responsive-nav-link>
                        <div class="border-t border-violet-200 dark:border-violet-800/50 my-1 mx-4"></div>
                        <x-responsive-nav-link :href="route('teams.expedientes.index', $currentTeamId)" :active="request()->routeIs('teams.expedientes.*')" class="text-sm">
                            {{ __('Expedientes') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('teams.tasks.index', $currentTeamId)" :active="request()->routeIs('teams.tasks.*')" class="text-sm">
                            {{ __('navigation.task_list') ?? 'Tareas' }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('teams.dashboard', $currentTeamId)" :active="request()->routeIs('teams.dashboard')" class="text-sm">
                            {{ __('teams.eisenhower_matrix') ?? 'Eisenhower' }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('teams.gantt', $currentTeamId)" :active="request()->routeIs('teams.gantt')" class="text-sm">
                            {{ __('navigation.gantt') ?? 'Diagrama de Gantt' }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('teams.kanban', $currentTeamId)" :active="request()->routeIs('teams.kanban')" class="text-sm">
                            {{ __('navigation.kanban') ?? 'Tablero Kanban' }}
                        </x-responsive-nav-link>
                        <div class="border-t border-violet-200 dark:border-violet-800/50 my-1 mx-4"></div>
                        <x-responsive-nav-link :href="route('teams.surveys.index', $currentTeamId)" :active="request()->routeIs('teams.surveys.*')" class="text-sm">
                            Encuestas
                        </x-responsive-nav-link>
                        @if(auth()->user()->hasAppointmentsEnabledForTeam($currentTeamId))
                            <x-responsive-nav-link :href="route('appointments.index', $currentTeamId)" :active="request()->routeIs('appointments.*')" class="text-sm">
                                Citas Previas
                            </x-responsive-nav-link>
                        @endif
EOT;

$navContent = str_replace($navMobileOld, $navMobileNew, $navContent);
file_put_contents($navPath, $navContent);

// 2. layouts/app.blade.php
$appPath = 'resources/views/layouts/app.blade.php';
$appContent = file_get_contents($appPath);
$appDrawerViewsRegex = '/\$drawerViews = \[\s*\[\'name\' => \'Escritorio\'(.*?)\$drawerViews as \$dv\)/s';
$appDrawerViewsNew = <<< 'EOT'
$drawerViews = [
                            ['name' => 'Escritorio', 'route' => route('teams.time-reports', $drawerTeamId), 'active' => request()->routeIs('teams.time-reports')],
                            ['name' => __('forum.title') ?? 'Foro', 'route' => route('teams.forum.index', $drawerTeamId), 'active' => request()->routeIs('teams.forum.*')],
                            ['divider' => true],
                            ['name' => 'Expedientes', 'route' => route('teams.expedientes.index', $drawerTeamId), 'active' => request()->routeIs('teams.expedientes.*')],
                            ['name' => __('navigation.task_list'), 'route' => route('teams.tasks.index', $drawerTeamId), 'active' => request()->routeIs('teams.tasks.*')],
                            ['name' => __('teams.eisenhower_matrix'), 'route' => route('teams.eisenhower', $drawerTeamId), 'active' => request()->routeIs('teams.eisenhower')],
                            ['name' => __('navigation.gantt'), 'route' => route('teams.gantt', $drawerTeamId), 'active' => request()->routeIs('teams.gantt')],
                            ['name' => __('navigation.kanban'), 'route' => route('teams.kanban', $drawerTeamId), 'active' => request()->routeIs('teams.kanban')],
                            ['divider' => true],
                            ['name' => __('Encuestas'), 'route' => route('teams.surveys.index', $drawerTeamId), 'active' => request()->routeIs('teams.surveys.*')],
                        ];
                        if (auth()->user()->hasAppointmentsEnabledForTeam($drawerTeamId)) {
                            $drawerViews[] = [
                                'name' => 'Citas Previas',
                                'route' => route('appointments.index', $drawerTeamId),
                                'active' => request()->routeIs('appointments.*')
                            ];
                        }
                        $drawerViews[] = [
                            'name' => __('teams.view_members'),
                            'route' => route('teams.members', $drawerTeamId),
                            'active' => request()->routeIs('teams.members')
                        ];

                    @endphp
                    @foreach($drawerViews as $dv)
                        @if(isset($dv['divider']))
                            <div class="border-t border-gray-100 dark:border-gray-800 my-1 mx-3"></div>
                        @else
                            <a href="{{ $dv['route'] }}" @click="open = false"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
                                      {{ $dv['active'] ? 'bg-violet-50 dark:bg-violet-500/10 text-violet-700 dark:text-violet-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                                {{ $dv['name'] }}
                            </a>
                        @endif
                    @endforeach
EOT;
$appContent = preg_replace('/\$drawerViews = \[\s*\[\'name\' => \'Escritorio\'.*?@endforeach/s', $appDrawerViewsNew, $appContent);
file_put_contents($appPath, $appContent);

// 3. layouts/navigation-sidebar.blade.php
$sidePath = 'resources/views/layouts/navigation-sidebar.blade.php';
$sideContent = file_get_contents($sidePath);

$sideViewsOld = <<< 'EOT'
                                    <a href="{{ route('teams.dashboard', $currentTeamId) }}"
                                        class="flex items-center gap-2 px-3 py-2 text-xs rounded-xl transition-all {{ request()->routeIs('teams.dashboard') ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                        </svg>
                                        <span class="truncate">Escritorio</span>
                                    </a>
                                    <a href="{{ route('teams.forum.index', $currentTeamId) }}"
                                        class="flex items-center gap-2 px-3 py-2 text-xs rounded-xl transition-all {{ request()->routeIs('teams.forum.*') ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                                        </svg>
                                        <span class="truncate">{{ __('forum.title') ?? 'Foro' }}</span>
                                    </a>
                                    <a href="{{ route('teams.surveys.index', $currentTeamId) }}"
                                        class="flex items-center gap-2 px-3 py-2 text-xs rounded-xl transition-all {{ request()->routeIs('teams.surveys.*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                        </svg>
                                        <span class="truncate">Encuestas</span>
                                    </a>
                                    <div class="h-px bg-gray-100 dark:bg-gray-800/50 my-1.5 mx-3"></div>
                                    <a href="{{ route('teams.expedientes.index', $currentTeamId) }}"
                                        class="flex items-center gap-2 px-3 py-2 text-xs rounded-xl transition-all {{ request()->routeIs('teams.expedientes.*') ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                        <span class="truncate">Expedientes</span>
                                    </a>
                                    <a href="{{ route('teams.tasks.index', $currentTeamId) }}"
                                        class="flex items-center gap-2 px-3 py-2 text-xs rounded-xl transition-all {{ request()->routeIs('teams.tasks.index') || request()->routeIs('teams.tasks.show') ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        <span class="truncate">{{ __('navigation.task_list') ?? 'Tareas' }}</span>
                                    </a>
                                    <a href="{{ route('teams.eisenhower', $currentTeamId) }}"
                                        class="flex items-center gap-2 px-3 py-2 text-xs rounded-xl transition-all {{ request()->routeIs('teams.eisenhower') ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                        </svg>
                                        <span class="truncate">{{ __('teams.eisenhower_matrix') ?? 'Priorización (MTX)' }}</span>
                                    </a>
                                    <a href="{{ route('teams.gantt', $currentTeamId) }}"
                                        class="flex items-center gap-2 px-3 py-2 text-xs rounded-xl transition-all {{ request()->routeIs('teams.gantt') ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h4V7a1 1 0 011-1h3a1 1 0 011 1v3h4V7a1 1 0 011-1h3a1 1 0 011 1v3h1M3 14h18M3 18h5v-3a1 1 0 011-1h3a1 1 0 011 1v3h4v-3a1 1 0 011-1h3a1 1 0 011 1v3h1" />
                                        </svg>
                                        <span class="truncate">{{ __('navigation.gantt') ?? 'Gantt' }}</span>
                                    </a>
                                    <a href="{{ route('teams.kanban', $currentTeamId) }}"
                                        class="flex items-center gap-2 px-3 py-2 text-xs rounded-xl transition-all {{ request()->routeIs('teams.kanban') ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                                        </svg>
                                        <span class="truncate">{{ __('navigation.kanban') ?? 'Kanban' }}</span>
                                    </a>
EOT;

$sideViewsNew = <<< 'EOT'
                                    <a href="{{ route('teams.dashboard', $currentTeamId) }}"
                                        class="flex items-center gap-2 px-3 py-2 text-xs rounded-xl transition-all {{ request()->routeIs('teams.dashboard') ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                        </svg>
                                        <span class="truncate">Escritorio</span>
                                    </a>
                                    <a href="{{ route('teams.forum.index', $currentTeamId) }}"
                                        class="flex items-center gap-2 px-3 py-2 text-xs rounded-xl transition-all {{ request()->routeIs('teams.forum.*') ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                                        </svg>
                                        <span class="truncate">{{ __('forum.title') ?? 'Foro' }}</span>
                                    </a>
                                    <div class="h-px bg-gray-100 dark:bg-gray-800/50 my-1.5 mx-3"></div>
                                    <a href="{{ route('teams.expedientes.index', $currentTeamId) }}"
                                        class="flex items-center gap-2 px-3 py-2 text-xs rounded-xl transition-all {{ request()->routeIs('teams.expedientes.*') ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                        <span class="truncate">Expedientes</span>
                                    </a>
                                    <a href="{{ route('teams.tasks.index', $currentTeamId) }}"
                                        class="flex items-center gap-2 px-3 py-2 text-xs rounded-xl transition-all {{ request()->routeIs('teams.tasks.index') || request()->routeIs('teams.tasks.show') ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        <span class="truncate">{{ __('navigation.task_list') ?? 'Tareas' }}</span>
                                    </a>
                                    <a href="{{ route('teams.eisenhower', $currentTeamId) }}"
                                        class="flex items-center gap-2 px-3 py-2 text-xs rounded-xl transition-all {{ request()->routeIs('teams.eisenhower') ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                        </svg>
                                        <span class="truncate">{{ __('teams.eisenhower_matrix') ?? 'Priorización (MTX)' }}</span>
                                    </a>
                                    <a href="{{ route('teams.gantt', $currentTeamId) }}"
                                        class="flex items-center gap-2 px-3 py-2 text-xs rounded-xl transition-all {{ request()->routeIs('teams.gantt') ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h4V7a1 1 0 011-1h3a1 1 0 011 1v3h4V7a1 1 0 011-1h3a1 1 0 011 1v3h1M3 14h18M3 18h5v-3a1 1 0 011-1h3a1 1 0 011 1v3h4v-3a1 1 0 011-1h3a1 1 0 011 1v3h1" />
                                        </svg>
                                        <span class="truncate">{{ __('navigation.gantt') ?? 'Gantt' }}</span>
                                    </a>
                                    <a href="{{ route('teams.kanban', $currentTeamId) }}"
                                        class="flex items-center gap-2 px-3 py-2 text-xs rounded-xl transition-all {{ request()->routeIs('teams.kanban') ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                                        </svg>
                                        <span class="truncate">{{ __('navigation.kanban') ?? 'Kanban' }}</span>
                                    </a>
                                    <div class="h-px bg-gray-100 dark:bg-gray-800/50 my-1.5 mx-3"></div>
                                    <a href="{{ route('teams.surveys.index', $currentTeamId) }}"
                                        class="flex items-center gap-2 px-3 py-2 text-xs rounded-xl transition-all {{ request()->routeIs('teams.surveys.*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400 font-bold' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                        </svg>
                                        <span class="truncate">Encuestas</span>
                                    </a>
EOT;

$sideContent = str_replace($sideViewsOld, $sideViewsNew, $sideContent);
file_put_contents($sidePath, $sideContent);

// 4. teams/index.blade.php
$indexPath = 'resources/views/teams/index.blade.php';
$indexContent = file_get_contents($indexPath);

// In teams/index.blade.php, we need to completely swap the blocks.
$indexBlockRegex = '/(<!-- Foro -->.*?<\/a>).*?(<!-- Encuestas -->.*?<\/a>).*?(<!-- Expedientes -->.*?<\/a>).*?(<!-- Tareas -->.*?<\/a>).*?(<!-- Eisenhower Matrix -->.*?<\/a>).*?(<!-- Gantt -->.*?<\/a>).*?(<!-- Kanban -->.*?<\/a>).*?(<!-- Miembros -->.*?<\/a>)/s';

preg_match($indexBlockRegex, $indexContent, $matches);

if (count($matches) === 9) {
    $foro = $matches[1];
    $encuestas = $matches[2];
    $expedientes = $matches[3];
    $tareas = $matches[4];
    $matrix = $matches[5];
    $gantt = $matches[6];
    $kanban = $matches[7];
    $miembros = $matches[8];

    // We add Citas Previas logic here!
    $citasPrevias = <<< 'EOT'
                        <!-- Citas Previas -->
                        @if(auth()->user()->hasAppointmentsEnabledForTeam($team->id))
                            <a href="{{ route('appointments.index', $team) }}" @click.stop
                                class="flex-1 flex items-center justify-center py-2 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-all border border-gray-200 dark:border-gray-700"
                                title="Citas Previas">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </a>
                        @endif
EOT;

    $newBlockStr = "\n                        $foro\n\n                        $expedientes\n\n                        $tareas\n\n                        $matrix\n\n                        $gantt\n\n                        $kanban\n\n                        $encuestas\n\n$citasPrevias\n\n                        $miembros\n";

    $indexContent = preg_replace($indexBlockRegex, trim($newBlockStr), $indexContent);
    file_put_contents($indexPath, $indexContent);
}

