<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="h-full {{ (auth()->check() && auth()->user()->theme === 'dark') || (!auth()->check() && request()->cookie('theme') === 'dark') || (auth()->check() && auth()->user()->theme === 'system' && request()->cookie('theme') === 'dark') ? 'dark' : '' }}">
<script>
    (function() {
        const theme =
            "{{ auth()->check() ? auth()->user()->theme : (isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'system') }}";
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
    <meta name="description" content="@yield('meta_description', 'sientiaMTX — Eisenhower Matrix task management for focused teams.')">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap"
        rel="stylesheet">

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
    </style>
</head>

<body class="h-full bg-white text-gray-900 dark:bg-gray-950 dark:text-gray-100 antialiased">

    <!-- Navigation -->
    <nav
        class="bg-white border-b border-gray-200 dark:bg-gray-900 dark:border-gray-800 sticky top-0 z-50 transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">

                <!-- Logo -->
                <a href="{{ route('home') }}" class="flex items-center gap-2 group">
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
                    @endauth

                    <!-- Theme Switcher -->
                    <div class="relative" x-data="{
                        open: false,
                        theme: '{{ auth()->check() ? auth()->user()->theme : 'system' }}',
                        updateTheme(newTheme) {
                            this.theme = newTheme;
                            this.open = false;
                    
                            // Set cookie regardless of auth
                            document.cookie = 'theme=' + newTheme + '; path=/; max-age=' + (30 * 24 * 60 * 60) + '; SameSite=Lax';
                    
                            if (newTheme === 'dark' || (newTheme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                                document.documentElement.classList.add('dark');
                            } else {
                                document.documentElement.classList.remove('dark');
                            }
                    
                            @auth
fetch('{{ route('theme.update') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({ theme: newTheme })
                            }).then(response => response.json())
                              .then(data => console.log('Theme updated:', data))
                              .catch(error => console.error('Error updating theme:', error)); @endauth
                        }
                    }">
                        <button @click="open = !open" @click.outside="open = false"
                            class="flex items-center justify-center text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 w-9 h-9 rounded-lg transition-all">
                            <!-- Sun -->
                            <svg x-show="theme === 'light'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 3v1m0 16v1m9-9h-1M4 9H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M14 12a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <!-- Moon -->
                            <svg x-show="theme === 'dark'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                            <!-- System -->
                            <svg x-show="theme === 'system'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </button>
                        <div x-show="open" x-transition
                            class="absolute right-0 mt-2 w-32 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl overflow-hidden z-50">
                            <button @click="updateTheme('light')"
                                class="w-full flex items-center gap-2 px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                :class="theme === 'light' ? 'text-violet-600 dark:text-violet-400 font-semibold' :
                                    'text-gray-600 dark:text-gray-300'">
                                ☀️ Light
                            </button>
                            <button @click="updateTheme('dark')"
                                class="w-full flex items-center gap-2 px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                :class="theme === 'dark' ? 'text-violet-600 dark:text-violet-400 font-semibold' :
                                    'text-gray-600 dark:text-gray-300'">
                                🌙 Dark
                            </button>
                            <button @click="updateTheme('system')"
                                class="w-full flex items-center gap-2 px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                :class="theme === 'system' ? 'text-violet-600 dark:text-violet-400 font-semibold' :
                                    'text-gray-600 dark:text-gray-300'">
                                💻 System
                            </button>
                        </div>
                    </div>

                    <!-- Language Switcher -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" @click.outside="open = false"
                            class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 px-2.5 py-1.5 rounded-lg transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                            </svg>
                            <span class="font-semibold uppercase text-xs">{{ app()->getLocale() }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform"
                                :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div x-show="open" x-transition
                            class="absolute right-0 mt-2 w-36 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl overflow-hidden z-50">
                            <a href="{{ route('locale.switch', 'en') }}"
                                class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors {{ app()->getLocale() === 'en' ? 'text-violet-600 dark:text-violet-400 font-semibold' : 'text-gray-600 dark:text-gray-300' }}">
                                🇬🇧 English
                            </a>
                            <a href="{{ route('locale.switch', 'es') }}"
                                class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors {{ app()->getLocale() === 'es' ? 'text-violet-600 dark:text-violet-400 font-semibold' : 'text-gray-600 dark:text-gray-300' }}">
                                🇪🇸 Español
                            </a>
                        </div>
                    </div>

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
            class="fixed top-20 right-4 z-50 max-w-sm w-full bg-emerald-900/90 border border-emerald-700 text-emerald-200 px-4 py-3 rounded-xl shadow-2xl flex items-center gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-emerald-400" fill="none"
                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            <span class="text-sm">{{ session('success') }}</span>
            <button @click="show = false" class="ml-auto text-emerald-400 hover:text-white">✕</button>
        </div>
    @endif

    @if (session('error') || $errors->any())
        <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 6000)"
            class="fixed top-20 right-4 z-50 max-w-sm w-full bg-red-900/90 border border-red-700 text-red-200 px-4 py-3 rounded-xl shadow-2xl flex items-start gap-3">
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
            <button @click="show = false" class="ml-auto text-red-400 hover:text-white shrink-0">✕</button>
        </div>
    @endif

    <!-- Page content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if (isset($header))
            <div class="mb-6">
                {{ $header }}
            </div>
        @endif

        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="border-t border-gray-200 dark:border-gray-800 mt-16 transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex items-center justify-between">
            <div class="flex flex-col gap-1">
                <span class="text-xs font-bold text-gray-900 dark:text-white">
                    sientia<span class="text-violet-600 dark:text-violet-400">MTX</span> <span
                        class="text-gray-400 font-normal">v{{ config('app.version', '0.0.1') }}</span>
                </span>
                <span class="text-[10px] uppercase tracking-widest text-gray-400 font-semibold">Eisenhower
                    Matrix</span>
            </div>
            <div class="flex items-center gap-4 text-[10px] font-bold uppercase tracking-widest text-gray-400">
                <a href="#" class="hover:text-violet-500 transition-colors">Privacy</a>
                <a href="#" class="hover:text-violet-500 transition-colors">Terms</a>
                <a href="#" class="hover:text-violet-500 transition-colors">Support</a>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>

</html>
