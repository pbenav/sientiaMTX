@php
    $layout = auth()->check() ? (auth()->user()->layout ?: 'horizontal') : request()->cookie('layout', 'horizontal');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="h-full {{ (auth()->check() ? auth()->user()->theme === 'dark' || (auth()->user()->theme === 'system' && request()->cookie('theme') === 'dark') : request()->cookie('theme') === 'dark') ? 'dark' : '' }}">
<script>
    (function() {
        const theme = "{{ auth()->check() ? auth()->user()->theme : request()->cookie('theme', 'system') }}";
        if (theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    })();
</script>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'sientiaMTX') }} — @yield('title', __('navigation.dashboard'))</title>
    <meta name="description" content="@yield('meta_description', 'sientiaMTX — Smart project management with MTX, Gantt, and Kanban for focused teams.')">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap"
        rel="stylesheet">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --color-q1: #ef4444;
            /* Red   – Do First  */
            --color-q2: #3b82f6;
            /* Blue  – Schedule  */
            --color-q3: #f59e0b;
            /* Amber – Delegate  */
            --color-q4: #6b7280;
            /* Gray  – Eliminate */
        }

        body {
            font-family: 'Inter', sans-serif;
        }

        h1,
        h2,
        h3,
        h4,
        .heading {
            font-family: 'Space Grotesk', sans-serif;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="h-full bg-white text-gray-900 dark:bg-gray-950 dark:text-gray-100 antialiased" x-data="{
    layout: '{{ auth()->check() ? (auth()->user()->layout ?: 'horizontal') : request()->cookie('layout', 'horizontal') }}',
    sidebarOpen: window.innerWidth > 1024,
    init() {
        window.addEventListener('resize', () => {
            if (window.innerWidth < 1024) {
                this.sidebarOpen = false;
            } else if (this.layout === 'vertical' && window.innerWidth >= 1024) {
                this.sidebarOpen = true;
            }
        });
    },
    async updateLayout(newLayout) {
        this.layout = newLayout;
        document.cookie = 'layout=' + newLayout + '; path=/; max-age=' + (30 * 24 * 60 * 60) + '; SameSite=Lax';

        @auth
        try {
            await fetch('{{ route('layout.update') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ layout: newLayout })
            });
        } catch (error) {
            console.error('Error updating layout:', error);
        }
        @endauth

        // Reload to apply layout structure
        window.location.reload();
    }
}">

    @include('partials.welcome-modal')
    @include('layouts.navigation-sidebar')

    <!-- Navigation -->
    <nav x-show="layout === 'horizontal'" {{ $layout === 'vertical' ? 'style=display:none' : '' }}
        class="bg-white border-b border-gray-200 dark:bg-gray-900 dark:border-gray-800 sticky top-0 z-50">
        <div class="{{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">

                <!-- Logo -->
                <a href="{{ auth()->check() ? (request()->route('team') ? route('teams.dashboard', request()->route('team')) : route('dashboard')) : route('home') }}"
                    class="flex items-center gap-2 group">
                    <div
                        class="w-8 h-8 rounded-lg bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center shadow-lg group-hover:shadow-violet-500/30 transition-all duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                            stroke-linejoin="round">
                            <rect x="3" y="3" width="8" height="8" rx="1" />
                            <rect x="13" y="3" width="8" height="8" rx="1" />
                            <rect x="3" y="13" width="8" height="8" rx="1" />
                            <rect x="13" y="13" width="8" height="8" rx="1" />
                        </svg>
                    </div>
                    <span class="font-bold text-gray-900 dark:text-white text-lg tracking-tight"
                        style="font-family:'Space Grotesk',sans-serif">sientia<span
                            class="text-violet-600 dark:text-violet-400">MTX</span></span>
                </a>

                <!-- Right side: nav links + locale + user menu -->
                <div class="flex items-center gap-4">

                    @auth
                        <!-- My Teams -->
                        <a href="{{ route('teams.index') }}"
                            class="hidden sm:flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors px-3 py-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ __('navigation.my_teams') }}
                        </a>

                        <!-- Disk Usage -->
                        <a href="{{ route('media.index') }}"
                            class="hidden sm:flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors px-3 py-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 {{ request()->routeIs('media.index') ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                            </svg>
                            {{ __('tasks.disk_quota') }}
                        </a>

                        @can('admin')
                            <a href="{{ route('settings.users') }}"
                                class="hidden sm:flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors px-3 py-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 {{ request()->routeIs('settings.users') ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                {{ __('navigation.users') }}
                            </a>

                            <a href="{{ route('settings.mail') }}"
                                class="hidden sm:flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors px-3 py-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 {{ request()->routeIs('settings.mail*') ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white' : '' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                {{ __('navigation.settings') }}
                            </a>
                        @endcan

                        <!-- Google Sync (Global) -->
                        <div class="hidden md:flex items-center border-l border-gray-200 dark:border-gray-800 pl-4 ml-1">
                            @if (!auth()->user()->google_token)
                                <button onclick="openGoogleAuth()"
                                    class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 px-3 py-1.5 transition-all font-bold rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24"
                                        fill="currentColor">
                                        <path
                                            d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                                        <path
                                            d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                                            fill="#34A853" />
                                        <path
                                            d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                                            fill="#FBBC05" />
                                        <path
                                            d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                                            fill="#EA4335" />
                                    </svg>
                                    <span class="hidden lg:inline">{{ __('Connect Google') }}</span>
                                </button>
                            @else
                                @php
                                    $currentTeamId = null;
                                    // Intenta obtener el ID de la ruta si es una ruta vinculada a equipo
                                    if (request()->route('team')) {
                                        $currentTeamId = is_object(request()->route('team'))
                                            ? request()->route('team')->id
                                            : request()->route('team');
                                    }
                                @endphp

                                @if ($currentTeamId)
                                    <form action="{{ route('google.sync') }}" method="GET"
                                        class="flex items-center gap-1">
                                        <input type="hidden" name="team_id" value="{{ $currentTeamId }}">
                                        <select name="visibility"
                                            class="text-xs py-1.5 border-none bg-gray-50 dark:bg-gray-800 rounded-lg focus:ring-2 focus:ring-violet-500 text-gray-600 dark:text-gray-300 w-28 font-medium">
                                            <option value="private" selected>{{ __('google.private') }}</option>
                                            <option value="public">{{ __('google.public') }}</option>
                                        </select>
                                        <button type="submit"
                                            class="p-2 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 rounded-lg transition-all"
                                            title="{{ __('google.sync') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    @endauth

                    @include('layouts.partials.theme-toggle')
                    @include('layouts.partials.layout-toggle')
                    @include('layouts.partials.zoom-controls')
                    @include('layouts.partials.language-toggle')



                    @auth
                        <!-- User menu -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" @click.outside="open = false"
                                class="flex items-center gap-2 text-sm text-gray-500 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition-colors">
                                <div
                                    class="w-8 h-8 rounded-full bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center text-xs font-bold text-white shadow-sm">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform"
                                    :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition
                                class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl overflow-hidden z-50">
                                <div
                                    class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-transparent">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                        {{ auth()->user()->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                        {{ auth()->user()->email }}</p>
                                </div>
                                <a href="{{ route('profile.edit') }}"
                                    class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    {{ __('navigation.profile') }}
                                </a>
                                <a href="{{ route('media.index') }}"
                                    class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                                    </svg>
                                    {{ __('tasks.disk_quota') }}
                                </a>
                                <div class="border-t border-gray-100 dark:border-gray-700">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit"
                                            class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors text-left font-medium">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                            </svg>
                                            {{ __('navigation.logout') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}"
                            class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors px-3 py-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
                            {{ __('navigation.login') }}
                        </a>
                        <a href="{{ route('register') }}"
                            class="text-sm bg-violet-600 hover:bg-violet-500 text-white px-4 py-1.5 rounded-lg font-medium transition-all shadow-lg hover:shadow-violet-500/30">
                            {{ __('navigation.register') }}
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    @if (session('success'))
        <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)"
            class="fixed top-20 right-4 z-50 max-w-sm w-full bg-emerald-50 dark:bg-emerald-900/90 border border-emerald-200 dark:border-emerald-700 text-emerald-800 dark:text-emerald-200 px-4 py-3 rounded-xl shadow-2xl flex items-center gap-3 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-emerald-400" fill="none"
                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            <span class="text-sm">{{ session('success') }}</span>
            <button @click="show = false"
                class="ml-auto text-emerald-500 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-white transition-colors">✕</button>
        </div>
    @endif

    @if (session('warning'))
        <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 6000)"
            class="fixed top-20 right-4 z-50 max-w-sm w-full bg-amber-50 dark:bg-amber-900/90 border border-amber-200 dark:border-amber-700 text-amber-800 dark:text-amber-200 px-4 py-3 rounded-xl shadow-2xl flex items-center gap-3 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-amber-400" fill="none"
                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span class="text-sm font-medium">{{ session('warning') }}</span>
            <button @click="show = false"
                class="ml-auto text-amber-500 dark:text-amber-400 hover:text-amber-700 dark:hover:text-white transition-colors">✕</button>
        </div>
    @endif

    @if (session('error') || $errors->any())
        <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 6000)"
            class="fixed top-20 right-4 z-50 max-w-sm w-full bg-red-50 dark:bg-red-900/90 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-200 px-4 py-3 rounded-xl shadow-2xl flex items-start gap-3 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 mt-0.5 text-red-400" fill="none"
                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="text-sm">
                @if (session('error'))
                    {{ session('error') }}
                @endif
                @foreach ($errors->all() as $err)
                    <div>{{ $err }}</div>
                @endforeach
            </div>
            <button @click="show = false"
                class="ml-auto text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-white shrink-0 transition-colors">✕</button>
        </div>
    @endif

    <!-- Page content -->
    <main id="mainContent" class="px-4 sm:px-6 lg:px-8 py-8"
        :class="layout === 'vertical' ? (sidebarOpen ? 'lg:pl-72 {{ $maxWidth }} mx-auto' : '{{ $maxWidth }} mx-auto') : '{{ $maxWidth }} mx-auto'">

        <!-- Header for Vertical Layout -->
        <div x-show="layout === 'vertical'"
            class="sticky top-0 z-30 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-4 mb-8 bg-white/80 dark:bg-gray-950/80 backdrop-blur-xl border-b border-gray-200 dark:border-gray-800">
            <div class="flex items-start gap-4">
                <!-- Toggle button ONLY when closed -->
                <button x-show="!sidebarOpen" @click="sidebarOpen = true"
                    class="p-2 -mt-1 rounded-lg text-gray-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-500/10 transition-all shrink-0"
                    title="{{ __('Open Sidebar') }}" x-cloak>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <div class="flex-1 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex-1 min-w-0">
                        @if (isset($header))
                            {{ $header }}
                        @endif
                    </div>

                    <!-- Minimal action bar for vertical layout -->
                    <div class="flex items-center gap-2 shrink-0 self-end lg:self-center">
                        @include('teams.partials.header-actions-extra', ['layout' => 'vertical'])
                    </div>
                </div>
            </div>
        </div>

        @if (isset($header) && $layout === 'horizontal')
            <div class="mb-6">
                {{ $header }}
            </div>
        @endif

        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="mt-auto border-t border-gray-200 dark:border-gray-800 py-4"
        :class="layout === 'vertical' ? (sidebarOpen ? 'lg:pl-72' : '') : ''">
        <div
            class="{{ $maxWidth }} mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center text-xs text-gray-500 dark:text-gray-400 font-medium">
            <div class="mb-2 md:mb-0 flex items-center gap-2">
                <span class="font-bold">© {{ date('Y') }} Sientia</span>
                <span class="mx-1">|</span>
                <span>v{{ config('app.version', '1.0.0') }}</span>
                <span class="mx-1">|</span>
                <a href="https://www.gnu.org/licenses/agpl-3.0.txt" target="_blank"
                    class="hover:text-violet-600 dark:hover:text-violet-400 transition-colors">Licencia AGPL v3</a>
            </div>
            <div class="flex items-center space-x-4">
                <a href="{{ route('privacy') }}" class="hover:text-violet-600 dark:hover:text-violet-400 transition-colors">{{ __('Privacidad') }}</a>
                <a href="{{ route('terms') }}" class="hover:text-violet-600 dark:hover:text-violet-400 transition-colors">{{ __('Términos') }}</a>
                <a href="{{ route('cookies') }}" class="hover:text-violet-600 dark:hover:text-violet-400 transition-colors">{{ __('Cookies') }}</a>
                <span class="text-gray-300 dark:text-gray-700">|</span>
                <a href="https://www.patreon.com/cw/sientia" target="_blank"
                    class="text-orange-500 hover:text-orange-600 font-bold transition-colors flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M2.912 8.411c-.312 0-.533.225-.533.533v11.43c0 .312.225.533.533.533H21.09c.312 0 .533-.225.533-.533V8.944c0-.312-.225-.533-.533-.533H2.912zm0-2.666H21.09c1.782 0 3.2 1.418 3.2 3.2v11.43c0 1.782-1.418 3.2-3.2 3.2H2.912C1.13 23.535-.285 22.117-.285 20.335V8.944c0-1.782 1.418-3.2 3.2-3.2zM4.156 2.666c0-.533.433-.966.966-.966h13.754c.533 0 .966.433.966.966s-.433.966-.966.966H5.122c-.533 0-.966-.433-.966-.966z" />
                    </svg>
                    Apoyar en Patreon
                </a>
            </div>
        </div>
    </footer>

    @stack('scripts')
    <script>
        window.confirmDelete = function(formId, message) {
            Swal.fire({
                title: '{{ __('teams.danger_zone') }}',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: '{{ __('teams.confirm_ok') }}',
                cancelButtonText: '{{ __('teams.confirm_cancel') }}',
                background: document.documentElement.classList.contains('dark') ? '#111827' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#ffffff' : '#111827',
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(formId).submit();
                }
            });
        }
        
        // Alias for compatibility with other views
        window.handleGlobalDelete = window.confirmDelete;

        window.openGoogleAuth = function() {
            const width = 600;
            const height = 700;
            const left = (window.innerWidth - width) / 2;
            const top = (window.innerHeight - height) / 2;
            const url = "{{ route('google.auth') }}?popup=1";

            const popup = window.open(url, 'GoogleAuth', `width=${width},height=${height},top=${top},left=${left}`);

            const messageHandler = function(event) {
                if (event.data === 'google-auth-success') {
                    window.removeEventListener('message', messageHandler);
                    location.reload();
                }
            };

            window.addEventListener('message', messageHandler);
        }

        @if (session('google_reauth_required'))
            document.addEventListener('DOMContentLoaded', function() {
                openGoogleAuth();
            });
        @endif
    </script>

    <!-- Global Zoom Logic -->
    <script>
        (function() {
            window.applyGlobalZoom = function(val) {
                const mainContent = document.getElementById('mainContent');
                if (mainContent) {
                    mainContent.style.zoom = val;
                }
                
                const label = document.getElementById('global-zoom-label');
                if (label) {
                    label.innerText = Math.round(val * 100) + '%';
                }
            }

            window.adjustGlobalZoom = function(delta) {
                let currentZoom = parseFloat(localStorage.getItem('global_zoom') || '1.0');
                currentZoom = Math.round((currentZoom + delta) * 100) / 100;
                currentZoom = Math.max(0.8, Math.min(1.2, currentZoom));
                localStorage.setItem('global_zoom', currentZoom);
                window.applyGlobalZoom(currentZoom);
            }

            // Apply zoom on load
            document.addEventListener('DOMContentLoaded', function() {
                const savedZoom = localStorage.getItem('global_zoom') || '1.0';
                window.applyGlobalZoom(parseFloat(savedZoom));
            });
        })();
    </script>
    
    @stack('scripts')
</body>

</html>
