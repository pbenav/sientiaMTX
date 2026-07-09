<nav x-data="{ 
        open: false,
        konami: { keys: [], code: ['arrowup','arrowup','arrowdown','arrowdown','arrowleft','arrowright','arrowleft','arrowright','b','a'] }
    }" 
    @keydown.window="
        konami.keys.push($event.key.toLowerCase());
        if (konami.keys.length > konami.code.length) konami.keys.shift();
        if (konami.keys.join(',') === konami.code.join(',')) {
            konami.keys = [];
            if (typeof confetti === 'undefined') {
                let s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js';
                s.onload = () => confetti({ particleCount: 150, spread: 100, origin: { y: 0.6 }, zIndex: 999999 });
                document.head.appendChild(s);
            } else {
                confetti({ particleCount: 150, spread: 100, origin: { y: 0.6 }, zIndex: 999999 });
            }
        }
    "
    class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center relative" x-data="{ clicks: 0, timer: null }">
                    <a href="{{ route('dashboard') }}" 
                       @click.prevent="
                           clicks++;
                           clearTimeout(timer);
                           if (clicks >= 5) {
                               clicks = 0;
                               if (typeof confetti === 'undefined') {
                                   let s = document.createElement('script');
                                   s.src = 'https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js';
                                   s.onload = () => confetti({ particleCount: 150, spread: 100, origin: { y: 0.6 }, zIndex: 999999 });
                                   document.head.appendChild(s);
                               } else {
                                   confetti({ particleCount: 150, spread: 100, origin: { y: 0.6 }, zIndex: 999999 });
                               }
                           } else {
                               timer = setTimeout(() => {
                                   if (clicks > 0 && clicks < 5) {
                                       window.location.href = '{{ route('dashboard') }}';
                                   }
                                   clicks = 0;
                               }, 300);
                           }
                       ">
                        <x-application-logo class="block h-9 w-auto fill-current text-violet-600 dark:text-violet-400 cursor-pointer" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 lg:-my-px lg:ms-10 lg:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('navigation.dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('teams.index')" :active="request()->routeIs('teams.index')">
                        {{ __('navigation.my_teams') ?? 'Mis Equipos' }}
                    </x-nav-link>
                    @if(auth()->user()->hasAppointmentsEnabled() || auth()->user()->hasMicrositesEnabled())
                        @php
                            $routeTeam = request()->route('team');
                            $routeTeamId = $routeTeam instanceof \App\Models\Team ? $routeTeam->id : $routeTeam;
                            
                            $navTeam = null;
                            if (auth()->user()->hasAppointmentsEnabled()) {
                                if ($routeTeamId && auth()->user()->hasAppointmentsEnabledForTeam($routeTeamId)) {
                                    $navTeam = $routeTeam;
                                }
                                if (!$navTeam) {
                                    $navTeam = auth()->user()->firstTeamWithAppointments();
                                }
                            }

                            $navTeamMicro = null;
                            if (auth()->user()->hasMicrositesEnabled()) {
                                if ($routeTeamId && auth()->user()->hasMicrositesEnabledForTeam($routeTeamId)) {
                                    $navTeamMicro = $routeTeam;
                                }
                                if (!$navTeamMicro) {
                                    $navTeamMicro = auth()->user()->firstTeamWithMicrosites();
                                }
                            }
                        @endphp

                        <div class="hidden sm:flex sm:items-center sm:ms-6">
                            <x-dropdown align="left" width="48">
                                <x-slot name="trigger">
                                    <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150 {{ (request()->routeIs('appointments.*') || request()->routeIs('teams.microsites.*')) ? 'text-gray-900 border-b-2 border-violet-400 rounded-none' : '' }}">
                                        <div>{{ __('Ciudadanía') }}</div>

                                        <div class="ms-1">
                                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    <x-dropdown-link :href="route('global-surveys.index')" :active="request()->routeIs('global-surveys.*')">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                            Encuestas Globales
                                        </div>
                                    </x-dropdown-link>

                                    @if($navTeam)
                                        <x-dropdown-link :href="route('appointments.index', $navTeam)" :active="request()->routeIs('appointments.*')">
                                            <div class="flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full bg-violet-500"></span>
                                                Citas Previas
                                            </div>
                                        </x-dropdown-link>
                                    @endif
                                    
                                    @if($navTeamMicro)
                                        <x-dropdown-link :href="route('teams.microsites.index', $navTeamMicro)" :active="request()->routeIs('teams.microsites.*')">
                                            <div class="flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full bg-pink-500"></span>
                                                Micrositios
                                            </div>
                                        </x-dropdown-link>
                                    @endif
                                </x-slot>
                            </x-dropdown>
                        </div>
                    @endif
                    <x-nav-link :href="route('media.index')" :active="request()->routeIs('media.index')">
                        {{ __('tasks.disk_quota') }}
                    </x-nav-link>

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
                                request()->routeIs('teams.activities.*') ||
                                request()->routeIs('teams.gantt') ||
                                request()->routeIs('teams.forum.*') ||
                                request()->routeIs('teams.expedientes.*') ||
                                request()->routeIs('teams.members') ||
                                request()->routeIs('teams.edit') ||
                                request()->routeIs('appointments.*');
                        @endphp
                        @if ($isTeamRoute)
                            <div class="flex items-center ms-4">
                                <x-dropdown align="left" width="48">
                                    <x-slot name="trigger">
                                        <button
                                            class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm leading-4 font-medium rounded-lg text-violet-600 bg-violet-50 hover:bg-violet-100 hover:text-violet-700 dark:bg-violet-500/10 dark:text-violet-400 dark:hover:bg-violet-500/20 dark:hover:text-violet-300 focus:outline-none transition ease-in-out duration-150">
                                            <div
                                                class="w-5 h-5 mr-2 rounded bg-violet-200 text-violet-700 dark:bg-violet-900/50 dark:text-violet-400 flex items-center justify-center text-[10px] font-bold">
                                                {{ strtoupper(substr($currentTeamLabel, 0, 1)) }}
                                            </div>
                                            <div>{{ Str::limit($currentTeamLabel, 20) }}</div>

                                            <div class="ms-2">
                                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </button>
                                    </x-slot>

                                    <x-slot name="content">
                                        <x-dropdown-link :href="route('teams.forum.index', $currentTeamId)">
                                            {{ __('forum.title') ?? 'Foro' }}
                                        </x-dropdown-link>
                                        <div class="border-t border-gray-100 dark:border-gray-800"></div>
                                        <x-dropdown-link :href="route('teams.expedientes.index', $currentTeamId)">
                                            {{ __('Expedientes') }}
                                        </x-dropdown-link>
                                        <x-dropdown-link :href="route('teams.activities.index', $currentTeamId)" :active="request()->routeIs('teams.activities.*') || request()->routeIs('teams.tasks.*')">
                                            Actividades
                                        </x-dropdown-link>
                                        <div class="border-t border-gray-100 dark:border-gray-800"></div>
                                        <x-dropdown-link :href="route('teams.surveys.index', $currentTeamId)">
                                            Encuestas del Equipo
                                        </x-dropdown-link>
                                        @if(auth()->user()->hasAppointmentsEnabledForTeam($currentTeamId))
                                            <x-dropdown-link :href="route('appointments.index', $currentTeamId)">
                                                Citas Previas
                                            </x-dropdown-link>
                                        @endif
                                        @if(auth()->user()->hasMicrositesEnabledForTeam($currentTeamId))
                                            <x-dropdown-link :href="route('teams.microsites.index', $currentTeamId)">
                                                Micrositios
                                            </x-dropdown-link>
                                        @endif
                                    </x-slot>

                                </x-dropdown>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden lg:flex lg:items-center lg:ms-6 gap-2">
                <!-- Dark Mode Toggle -->
                <button type="button" @click="
                    let isDark = document.documentElement.classList.toggle('dark');
                    let newTheme = isDark ? 'dark' : 'light';
                    document.cookie = 'theme=' + newTheme + '; path=/; max-age=31536000';
                    fetch('/theme', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                        body: JSON.stringify({ theme: newTheme })
                    }).then(() => window.location.reload());
                " class="relative p-2 text-gray-400 hover:text-gray-500 transition-colors duration-150 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800">
                    <svg class="w-5 h-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <svg class="w-5 h-5 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>

                <!-- Notifications Bell -->
                <a href="{{ route('notifications.index') }}" class="relative p-2 text-gray-400 hover:text-gray-500 transition-colors duration-150 rounded-full hover:bg-gray-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    @if(Auth::user()->unreadNotifications->count() > 0)
                        <span class="absolute top-1 right-1 flex h-4 w-4">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-4 w-4 bg-red-500 text-[10px] text-white font-bold items-center justify-center">
                                {{ Auth::user()->unreadNotifications->count() > 99 ? '99+' : Auth::user()->unreadNotifications->count() }}
                            </span>
                        </span>
                    @endif
                </a>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            class="inline-flex items-center px-2 py-2 border border-transparent text-sm leading-4 font-medium rounded-xl text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <img src="{{ Auth::user()->profile_photo_url }}" class="h-8 w-8 rounded-lg object-cover me-2 border border-gray-100 shadow-sm">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        @if(Auth::user()->is_admin)
                            <div class="border-t border-gray-100 dark:border-gray-800 my-1"></div>
                            
                            <x-dropdown-link :href="route('metrics.index')" class="font-bold text-indigo-600 dark:text-indigo-400">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                    {{ __('Cuadros de Mando') }}
                                </div>
                            </x-dropdown-link>

                            <x-dropdown-link :href="route('settings.teams')" class="font-bold text-violet-600 dark:text-violet-400">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    {{ __('Gestión de Equipos') }}
                                </div>
                            </x-dropdown-link>

                            <x-dropdown-link :href="route('settings.mail')" class="font-bold text-violet-600 dark:text-violet-400">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    {{ __('Configuración Global') }}
                                </div>
                            </x-dropdown-link>
                        @endif

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center lg:hidden">
                <button @click="open = ! open"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{ 'block': open, 'hidden': !open }" class="hidden lg:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('navigation.dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('teams.index')" :active="request()->routeIs('teams.index')">
                {{ __('navigation.my_teams') ?? 'Mis Equipos' }}
            </x-responsive-nav-link>
                    @if(auth()->user()->hasAppointmentsEnabled() || auth()->user()->hasMicrositesEnabled())
                        @php
                            $routeTeam = request()->route('team');
                            $routeTeamId = $routeTeam instanceof \App\Models\Team ? $routeTeam->id : $routeTeam;
                            
                            $navTeam = null;
                            if (auth()->user()->hasAppointmentsEnabled()) {
                                if ($routeTeamId && auth()->user()->hasAppointmentsEnabledForTeam($routeTeamId)) {
                                    $navTeam = $routeTeam;
                                }
                                if (!$navTeam) {
                                    $navTeam = auth()->user()->firstTeamWithAppointments();
                                }
                            }

                            $navTeamMicro = null;
                            if (auth()->user()->hasMicrositesEnabled()) {
                                if ($routeTeamId && auth()->user()->hasMicrositesEnabledForTeam($routeTeamId)) {
                                    $navTeamMicro = $routeTeam;
                                }
                                if (!$navTeamMicro) {
                                    $navTeamMicro = auth()->user()->firstTeamWithMicrosites();
                                }
                            }
                        @endphp
                        
                        <div class="px-4 py-2 text-xs font-bold text-gray-400 uppercase tracking-wider mt-2 border-t border-gray-100">
                            Ciudadanía
                        </div>
                        
                        <x-responsive-nav-link :href="route('global-surveys.index')" :active="request()->routeIs('global-surveys.*')" class="pl-8">
                            <div class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                Encuestas Globales
                            </div>
                        </x-responsive-nav-link>
                        
                        @if($navTeam)
                            <x-responsive-nav-link :href="route('appointments.index', $navTeam)" :active="request()->routeIs('appointments.*')" class="pl-8">
                                <div class="flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-violet-500"></span>
                                    Citas Previas
                                </div>
                            </x-responsive-nav-link>
                        @endif

                        @if($navTeamMicro)
                            <x-responsive-nav-link :href="route('teams.microsites.index', $navTeamMicro)" :active="request()->routeIs('teams.microsites.*')" class="pl-8">
                                <div class="flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-pink-500"></span>
                                    Micrositios
                                </div>
                            </x-responsive-nav-link>
                        @endif
                    @endif

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
                        request()->routeIs('teams.activities.*') ||
                        request()->routeIs('teams.gantt') ||
                        request()->routeIs('teams.kanban') ||
                        request()->routeIs('teams.forum.*') ||
                        request()->routeIs('teams.expedientes.*') ||
                        request()->routeIs('teams.members') ||
                        request()->routeIs('teams.edit') ||
                        request()->routeIs('appointments.*');
                @endphp
                @if ($isTeamRoute)
                    <div class="pl-6 bg-violet-50/50 dark:bg-violet-900/10 py-2 border-l-4 border-violet-500 my-1">
                        <div
                            class="px-4 text-xs font-bold text-violet-600 dark:text-violet-400 mb-1 tracking-wider uppercase flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ Str::limit($currentTeamLabel, 20) }}
                        </div>
                        <x-responsive-nav-link :href="route('teams.forum.index', $currentTeamId)" :active="request()->routeIs('teams.forum.*')" class="text-sm">
                            {{ __('forum.title') ?? 'Foro' }}
                        </x-responsive-nav-link>
                        <div class="border-t border-violet-200 dark:border-violet-800/50 my-1 mx-4"></div>
                        <x-responsive-nav-link :href="route('teams.expedientes.index', $currentTeamId)" :active="request()->routeIs('teams.expedientes.*')" class="text-sm">
                            {{ __('Expedientes') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('teams.activities.index', $currentTeamId)" :active="request()->routeIs('teams.activities.*') || request()->routeIs('teams.tasks.*')" class="text-sm">
                            Actividades
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
                            Encuestas del Equipo
                        </x-responsive-nav-link>
                        @if(auth()->user()->hasAppointmentsEnabledForTeam($currentTeamId))
                            <x-responsive-nav-link :href="route('appointments.index', $currentTeamId)" :active="request()->routeIs('appointments.*')" class="text-sm">
                                Citas Previas
                            </x-responsive-nav-link>
                        @endif
                        @if(auth()->user()->hasMicrositesEnabledForTeam($currentTeamId))
                            <x-responsive-nav-link :href="route('teams.microsites.index', $currentTeamId)" :active="request()->routeIs('teams.microsites.*')" class="text-sm">
                                Micrositios
                            </x-responsive-nav-link>
                        @endif
                    </div>

                @endif
            @endif

            <x-responsive-nav-link :href="route('media.index')" :active="request()->routeIs('media.index')">
                {{ __('tasks.disk_quota') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4 flex justify-between items-center gap-4">
                <div class="flex items-center gap-3">
                    <img src="{{ Auth::user()->profile_photo_url }}" class="h-10 w-10 rounded-xl object-cover border border-gray-200 shadow-sm">
                    <div>
                        <div class="font-bold text-base text-gray-800 dark:text-gray-200">{{ Auth::user()->name }}</div>
                        <div class="font-medium text-xs text-gray-500">{{ Auth::user()->email }}</div>
                    </div>
                </div>
                <!-- Mobile Dark Mode Toggle -->
                <div class="flex items-center gap-1">
                    <button type="button" @click="
                        let isDark = document.documentElement.classList.toggle('dark');
                        let newTheme = isDark ? 'dark' : 'light';
                        document.cookie = 'theme=' + newTheme + '; path=/; max-age=31536000';
                        fetch('/theme', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                            body: JSON.stringify({ theme: newTheme })
                        }).then(() => window.location.reload());
                    " class="relative p-2 text-gray-400 hover:text-gray-500 transition-colors duration-150 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800">
                        <svg class="w-6 h-6 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg class="w-6 h-6 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>
                    <!-- Mobile Notification Badge -->
                <a href="{{ route('notifications.index') }}" class="relative p-2 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    @if(Auth::user()->unreadNotifications->count() > 0)
                        <span class="absolute top-1 right-1 flex h-4 w-4">
                            <span class="relative inline-flex rounded-full h-4 w-4 bg-red-500 text-[10px] text-white font-bold items-center justify-center">
                                {{ Auth::user()->unreadNotifications->count() > 99 ? '99+' : Auth::user()->unreadNotifications->count() }}
                            </span>
                        </span>
                    @endif
                </a>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.index')">
                    {{ __('Notificaciones') }}
                    @if(Auth::user()->unreadNotifications->count() > 0)
                        <span class="ms-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full">
                            {{ Auth::user()->unreadNotifications->count() }}
                        </span>
                    @endif
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                @if(Auth::user()->is_admin)
                    <x-responsive-nav-link :href="route('metrics.index')" :active="request()->routeIs('metrics.*')" class="font-bold text-indigo-600 dark:text-indigo-400">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            {{ __('Cuadros de Mando') }}
                        </div>
                    </x-responsive-nav-link>

                    <x-responsive-nav-link :href="route('settings.mail')" :active="request()->routeIs('settings.*')" class="font-bold text-violet-600 dark:text-violet-400">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ __('Configuración Global') }}
                        </div>
                    </x-responsive-nav-link>
                @endif

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
