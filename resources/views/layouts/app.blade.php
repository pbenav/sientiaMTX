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

    <!-- Marked.js (Markdown Rendering) -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Dynamic Markdown Styles -->
    <x-markdown-styles :team="request()->route('team') && is_object(request()->route('team')) ? request()->route('team') : (request()->route('team') ? \App\Models\Team::find(request()->route('team')) : null)" />

    <!-- Global Alpine Store for Timer (Performance optimization N -> 1) -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('timer', {
                activeTaskId: {{ auth()->check() ? (auth()->user()->activeTaskLog()?->task_id ?? 'null') : 'null' }},
                elapsed: {{ auth()->check() && auth()->user()->activeTaskLog() ? auth()->user()->activeTaskLog()->start_at->diffInSeconds(now()) : 0 }},
                timer: null,
                
                async fetch() {
                    try {
                        const res = await fetch('{{ route('time-logs.status') }}');
                        const data = await res.json();
                        this.activeTaskId = data.active_task_id;
                        this.elapsed = Math.floor(data.task_elapsed);
                        if (this.activeTaskId) this.tick();
                        else this.stop();
                    } catch(e) { console.error('Timer sync failed', e); }
                },
                tick() {
                    if (this.timer) clearInterval(this.timer);
                    this.timer = setInterval(() => { this.elapsed++; }, 1000);
                },
                stop() {
                    if (this.timer) clearInterval(this.timer);
                    this.timer = null;
                    this.activeTaskId = null;
                },
                init() {
                    if (this.activeTaskId) this.tick();
                    
                    // Listeners Centralized
                    window.addEventListener('task-started', (e) => {
                        this.activeTaskId = e.detail.taskId;
                        this.elapsed = 0;
                        this.tick();
                    });
                    
                    window.addEventListener('workday-toggled', (e) => {
                        if (!e.detail.working) this.stop();
                    });
                }
            });
            
            Alpine.store('notifications', {
                count: {{ auth()->check() ? Auth::user()->unreadNotifications->count() : 0 }},
                lastChecked: Date.now(),
                firstCheck: true,
                
                async check() {
                    try {
                        const res = await fetch('{{ route('notifications.unread-count') }}');
                        const data = await res.json();
                        
                        // If it's the first check and there are unread notifications, notify about pending work
                        if (this.firstCheck && data.count > 0) {
                            if (data.count === 1) {
                                this.showToast(data.unread[0]);
                            } else {
                                Swal.fire({
                                    title: '{{ __("Pendientes") }}',
                                    text: '{{ __("Tienes :count notificaciones pendientes", ["count" => ""]) }}'.replace('""', data.count) + data.count,
                                    icon: 'info',
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 7000,
                                    timerProgressBar: true,
                                    background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#ffffff',
                                    color: document.documentElement.classList.contains('dark') ? '#f3f4f6' : '#1f2937',
                                    didOpen: (toast) => {
                                        toast.style.zIndex = '9999'; // Force super high z-index
                                        toast.addEventListener('click', () => { window.location.href = '{{ route("notifications.index") }}'; })
                                    }
                                });
                            }
                            this.firstCheck = false;
                        } 
                        // If count increased during session, show the new one
                        else if (data.count > this.count && data.unread.length > 0) {
                            this.showToast(data.unread[0]);
                        }
                        
                        if (data.count !== this.count) {
                            window.dispatchEvent(new CustomEvent('notifications-updated', { detail: { count: data.count } }));
                        }
                        
                        this.count = data.count;
                    } catch(e) { console.error('Notification check failed', e); }
                },

                showToast(notification) {
                    Swal.fire({
                        title: '{{ __("Nueva notificación") }}',
                        text: notification.data.message || '{{ __("Tienes una nueva actualización") }}',
                        icon: 'info',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 6000,
                        timerProgressBar: true,
                        background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#ffffff',
                        color: document.documentElement.classList.contains('dark') ? '#f3f4f6' : '#1f2937',
                        didOpen: (toast) => {
                            toast.style.zIndex = '9999'; // Ensure it's above everything
                            toast.addEventListener('mouseenter', Swal.stopTimer)
                            toast.addEventListener('mouseleave', Swal.resumeTimer)
                            toast.addEventListener('click', () => {
                                window.location.href = '{{ route("notifications.index") }}';
                            })
                        }
                    });
                },
                
                init() {
                    @auth
                    // Initial check delayed to avoid conflict with welcome modal/greeting
                    setTimeout(() => this.check(), 30000);

                    // Poll more frequently for "real-time" (15 seconds)
                    setInterval(() => this.check(), 15000);

                    // Re-check when coming back to the tab
                    document.addEventListener('visibilitychange', () => {
                        if (document.visibilityState === 'visible') {
                            this.check();
                        }
                    });
                    @endauth
                }
            });

            // Initial sync (background)
            Alpine.store('timer').fetch();
        });
    </script>

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
            overflow-x: hidden !important;
        }

        html {
            overflow-x: hidden !important;
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

        /* Prevent layout clipping on mobile for wide content like Kanban */
        @media (max-width: 1024px) {
            /* Only allow horizontal scroll for specific views that need it */
            #mainContent[data-wide-content] {
                overflow-x: auto !important;
            }
            #mainContent {
                max-width: none !important;
                width: 100% !important;
                overflow-x: hidden !important;
            }
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

    <div id="app-root" class="min-h-screen flex flex-col">
        @include('partials.welcome-modal')
    @include('layouts.navigation-sidebar')

    <!-- Navigation -->
    <nav x-show="layout === 'horizontal'" {{ $layout === 'vertical' ? 'style=display:none' : '' }}
        x-data="{ mobileMenuOpen: false }"
        class="bg-white border-b border-gray-200 dark:bg-gray-950 dark:border-gray-800 sticky top-0 z-[80] w-full overflow-visible">
        <div class="max-w-none lg:{{ $maxWidth }} mx-auto px-2 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">

                <!-- Logo -->
                <a href="{{ auth()->check() ? (request()->route('team') ? route('teams.dashboard', request()->route('team')) : route('dashboard')) : route('home') }}"
                    class="flex items-center gap-2 group shrink-0">
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

                <!-- Right side: flex container taking remaining space -->
                <div class="flex items-center gap-1 sm:gap-3 flex-1 justify-end min-w-0">

                    <!-- 1. DESKTOP (lg+) & MOBILE (<md): Inline Icons (Labels only on lg+) -->
                    <div class="flex md:hidden lg:flex items-center gap-1 sm:gap-3 overflow-x-auto min-w-0 [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                    @auth
                        <!-- My Teams -->
                        <a href="{{ route('teams.index') }}"
                            class="flex flex-col items-center justify-center min-w-[3rem] lg:min-w-[4rem] px-2 h-14 text-gray-500 dark:text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-all rounded-xl hover:bg-violet-50 dark:hover:bg-violet-500/10 relative group"
                            title="{{ __('navigation.my_teams') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mb-0.5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="hidden lg:block text-[9px] font-bold uppercase tracking-tight leading-none">{{ __('navigation.my_teams') }}</span>
                            @php $teamCount = auth()->user()->teams()->count(); @endphp
                            @if($teamCount > 0)
                                <span class="absolute top-1 right-2 flex h-4 w-4 items-center justify-center rounded-full bg-violet-600 text-[10px] font-bold text-white shadow-sm ring-2 ring-white dark:ring-gray-950 px-1">
                                    {{ $teamCount }}
                                </span>
                            @endif
                        </a>

                        <!-- Disk Usage -->
                        <a href="{{ route('media.index') }}"
                            class="flex flex-col items-center justify-center min-w-[3rem] lg:min-w-[4rem] px-2 h-14 text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-all rounded-xl hover:bg-blue-50 dark:hover:bg-blue-500/10 {{ request()->routeIs('media.index') ? 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400' : '' }}"
                            title="{{ __('tasks.disk_quota') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mb-0.5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                            </svg>
                            <span class="hidden lg:block text-[9px] font-bold uppercase tracking-tight leading-none text-center px-1">{{ __('tasks.disk_quota') }}</span>
                        </a>

                        <a href="{{ route('docs') }}"
                            class="flex flex-col items-center justify-center min-w-[3rem] lg:min-w-[3.5rem] px-2 h-14 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-all rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 {{ request()->is('docs*') ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white' : '' }}"
                            title="{{ __('Documentación') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mb-0.5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18 18.247 18.477 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <span class="hidden lg:block text-[9px] font-bold uppercase tracking-tight leading-none text-center px-1">Doc</span>
                        </a>

                        @can('admin')
                            <a href="{{ route('settings.users') }}"
                                class="flex flex-col items-center justify-center min-w-[3rem] lg:min-w-[4.5rem] px-2 h-14 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-all rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 {{ request()->routeIs('settings.users') ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white' : '' }}"
                                title="{{ __('navigation.users') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mb-0.5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <span class="hidden lg:block text-[9px] font-bold uppercase tracking-tight leading-none text-center px-1">{{ __('navigation.users') }}</span>
                            </a>

                            <a href="{{ route('settings.mail') }}"
                                class="flex flex-col items-center justify-center min-w-[3rem] lg:min-w-[5.5rem] px-2 h-14 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-all rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 {{ request()->routeIs('settings.mail*') ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white' : '' }}"
                                title="{{ __('navigation.settings') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mb-0.5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="hidden lg:block text-[9px] font-bold uppercase tracking-tight leading-none text-center px-1">{{ __('navigation.settings') }}</span>
                            </a>
                        @endcan
                    </div>

                    <!-- 2. TABLET (md only): Main Menu Dropdown -->
                    <div class="hidden md:block lg:hidden relative shrink-0" x-data="{ open: false }">
                        <button @click="open = !open" @click.outside="open = false"
                                class="flex items-center gap-2 px-3 h-11 text-sm font-bold uppercase tracking-tight text-gray-500 hover:text-violet-600 bg-gray-50 dark:bg-gray-800/80 rounded-xl transition-all shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            Menú
                        </button>
                        <div x-show="open" x-transition x-cloak style="display: none;"
                             class="absolute left-0 mt-2 w-56 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl overflow-hidden z-[90]">
                            @auth
                             <a href="{{ route('teams.index') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-violet-50 dark:hover:bg-gray-700 hover:text-violet-600 dark:hover:text-violet-400 transition-colors border-b border-gray-100 dark:border-gray-700">
                                 <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                 <span class="font-bold">{{ __('navigation.my_teams') }}</span>
                             </a>
                             <a href="{{ route('media.index') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-gray-700 hover:text-blue-600 dark:hover:text-blue-400 transition-colors border-b border-gray-100 dark:border-gray-700">
                                 <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" /></svg>
                                 <span class="font-bold">{{ __('tasks.disk_quota') }}</span>
                             </a>
                             <a href="{{ route('docs') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 transition-colors">
                                 <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18 18.247 18.477 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                                 <span class="font-bold">Doc</span>
                             </a>
                             @can('admin')
                                 <div class="px-4 py-2 mt-1 text-[10px] font-bold uppercase tracking-wider text-gray-400 bg-gray-50 dark:bg-gray-800/80">{{ __('Administración') }}</div>
                                 <a href="{{ route('settings.users') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 transition-colors border-t border-gray-100 dark:border-gray-700">
                                     <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                     <span class="font-bold">{{ __('navigation.users') }}</span>
                                 </a>
                                 <a href="{{ route('settings.mail') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 transition-colors border-t border-gray-100 dark:border-gray-700">
                                     <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                     <span class="font-bold">{{ __('navigation.settings') }}</span>
                                 </a>
                             @endcan
                             @endauth
                        </div>
                    </div>
                    <!-- Right Utilities & User Profile (Fixed) -->
                    <div class="flex items-center gap-1 sm:gap-3 shrink-0">

                    @endauth

                    <!-- Utility controls: hidden on mobile, shown on md+ (tablets and desktop) -->
                    <div class="hidden md:flex items-center gap-1 pl-2 ml-1 border-l border-gray-200 dark:border-gray-800">
                        @auth @include('layouts.partials.workday-timer') @endauth
                        @include('layouts.partials.theme-toggle')
                        @include('layouts.partials.layout-toggle')
                        @include('layouts.partials.zoom-controls')
                        @include('layouts.partials/language-toggle')
                    </div>

                    <!-- Mobile: just notifications bell + hamburger -->
                    <div class="flex items-center sm:hidden gap-2 ml-auto">
                        @auth
                        <a href="{{ route('notifications.index') }}" class="relative p-2 text-gray-400" x-data>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <template x-if="$store.notifications.count > 0">
                                <span class="absolute top-0.5 right-0.5 h-4 w-4 rounded-full bg-red-500 text-[9px] font-bold text-white flex items-center justify-center"
                                      x-text="$store.notifications.count > 9 ? '9+' : $store.notifications.count"></span>
                            </template>
                        </a>
                        @endauth
                        <!-- Hamburger -->
                        <button @click="window.dispatchEvent(new CustomEvent('mobile-menu-open'))"
                            class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                            aria-label="Menu">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>



                    @auth
                        <!-- Notifications Bell: hidden on mobile (in mobile block above) -->
                        <a href="{{ route('notifications.index') }}" 
                           class="hidden sm:inline-flex relative p-2 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-colors duration-150 rounded-xl hover:bg-violet-50 dark:hover:bg-violet-500/10" 
                           title="{{ __('Notificaciones') }}"
                           x-data
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <template x-if="$store.notifications.count > 0">
                                <span class="absolute top-1 right-1 flex h-4 w-4">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-4 w-4 bg-red-500 text-[10px] text-white font-bold items-center justify-center"
                                          x-text="$store.notifications.count > 99 ? '99+' : $store.notifications.count">
                                    </span>
                                </span>
                            </template>
                        </a>

                        <!-- User menu: hidden on mobile -->
                        <div class="hidden sm:block relative" x-data="{ open: false }">
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
                            <div x-show="open" x-transition x-cloak style="display: none"
                                class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl overflow-visible z-[90]">
                                <div
                                    class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-transparent rounded-t-xl">
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
                                <a href="{{ route('credits') }}"
                                    class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-amber-600 dark:hover:text-amber-400 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                    {{ __('credits.title') }}
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

                                <!-- Embedded Utilities for Mobile/Small tablets (Hidden when visible in header) -->
                                <div class="hidden sm:flex md:hidden flex-wrap items-center justify-center gap-2 px-4 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50 sm:justify-start dark:bg-gray-800/50">
                                    @auth @include('layouts.partials.workday-timer') @endauth
                                    @include('layouts.partials.theme-toggle')
                                    @include('layouts.partials.layout-toggle')
                                    @include('layouts.partials.language-toggle')
                                </div>

                                <div class="border-t border-gray-100 dark:border-gray-700">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit"
                                            class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors text-left font-medium rounded-b-xl">
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
        </div>
    </nav>

    {{-- ============================================================
         MOBILE SLIDE-IN DRAWER
         Full navigation panel triggered by hamburger button
         ============================================================ --}}
    @auth
    @php
        $drawerTeamId = null;
        if (request()->route('team')) {
            $drawerTeamId = is_object(request()->route('team'))
                ? request()->route('team')->id
                : request()->route('team');
        }
    @endphp
    {{-- Drawer controlled via custom window event 'mobile-menu-open' --}}
    <div id="mobile-drawer"
         x-data="{ open: false }"
         x-init="
            window.addEventListener('mobile-menu-open', () => open = true);
            window.addEventListener('mobile-menu-close', () => open = false);
         "
         class="sm:hidden">

        {{-- Backdrop --}}
        <div x-show="open"
             x-transition:enter="transition-opacity ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="open = false"
             class="fixed inset-0 z-[999] bg-black/40 backdrop-blur-sm">
        </div>

        {{-- Drawer panel --}}
        <div x-show="open"
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full"
             class="fixed inset-y-0 left-0 z-[9999] w-72 bg-white dark:bg-gray-900 shadow-2xl flex flex-col overflow-y-auto transform">
        

            {{-- Drawer header --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                <span class="font-bold text-gray-900 dark:text-white text-lg" style="font-family:'Space Grotesk',sans-serif">
                    sientia<span class="text-violet-600 dark:text-violet-400">MTX</span>
                </span>
                <button @click="open = false" class="p-2 rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- User info --}}
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center text-sm font-bold text-white shadow">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                <div class="min-w-0">
                    <p class="font-semibold text-sm text-gray-900 dark:text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-400 truncate">{{ auth()->user()->email }}</p>
                </div>
            </div>

            {{-- Navigation links --}}
            <nav class="flex-1 px-3 py-4 space-y-1">

                {{-- Main --}}
                <p class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Principal</p>
                <a href="{{ route('teams.index') }}" @click="open = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
                          {{ request()->routeIs('teams.index') ? 'bg-violet-50 dark:bg-violet-500/10 text-violet-700 dark:text-violet-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    {{ __('navigation.my_teams') }}
                </a>
                <a href="{{ route('notifications.index') }}" @click="open = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
                          {{ request()->routeIs('notifications.*') ? 'bg-violet-50 dark:bg-violet-500/10 text-violet-700 dark:text-violet-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    Notificaciones
                    @if(auth()->user()->unreadNotifications->count() > 0)
                        <span class="ml-auto bg-red-500 text-white text-[10px] font-bold rounded-full px-2 py-0.5">{{ auth()->user()->unreadNotifications->count() }}</span>
                    @endif
                </a>
                <a href="{{ route('media.index') }}" @click="open = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
                          {{ request()->routeIs('media.index') ? 'bg-violet-50 dark:bg-violet-500/10 text-violet-700 dark:text-violet-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                    </svg>
                    {{ __('tasks.disk_quota') }}
                </a>
                <a href="{{ route('docs') }}" @click="open = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18 18.247 18.477 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    Documentación
                </a>

                {{-- Team views (if inside a team) --}}
                @if($drawerTeamId)
                <div class="pt-3">
                    <p class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Vistas del Equipo</p>
                    @php
                        $drawerViews = [
                            ['name' => 'Escritorio', 'route' => route('teams.time-reports', $drawerTeamId), 'active' => request()->routeIs('teams.time-reports')],
                            ['name' => __('forum.title') ?? 'Foro', 'route' => route('teams.forum.index', $drawerTeamId), 'active' => request()->routeIs('teams.forum.*')],
                            ['name' => __('navigation.task_list'), 'route' => route('teams.tasks.index', $drawerTeamId), 'active' => request()->routeIs('teams.tasks.*')],
                            ['name' => __('teams.eisenhower_matrix'), 'route' => route('teams.dashboard', $drawerTeamId), 'active' => request()->routeIs('teams.dashboard')],
                            ['name' => __('navigation.gantt'), 'route' => route('teams.gantt', $drawerTeamId), 'active' => request()->routeIs('teams.gantt')],
                            ['name' => __('navigation.kanban'), 'route' => route('teams.kanban', $drawerTeamId), 'active' => request()->routeIs('teams.kanban')],
                            ['name' => __('teams.view_members'), 'route' => route('teams.members', $drawerTeamId), 'active' => request()->routeIs('teams.members')],
                        ];
                    @endphp
                    @foreach($drawerViews as $dv)
                        <a href="{{ $dv['route'] }}" @click="open = false"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
                                  {{ $dv['active'] ? 'bg-violet-50 dark:bg-violet-500/10 text-violet-700 dark:text-violet-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                            {{ $dv['name'] }}
                        </a>
                    @endforeach
                </div>
                @endif

                @can('admin')
                <div class="pt-3">
                    <p class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Administración</p>
                    <a href="{{ route('settings.users') }}" @click="open = false"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        {{ __('navigation.users') }}
                    </a>
                    <a href="{{ route('settings.mail') }}" @click="open = false"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        {{ __('navigation.settings') }}
                    </a>
                </div>
                @endcan

                {{-- Mobile Utilities --}}
                <div class="pt-4 mt-4 border-t border-gray-100 dark:border-gray-800">
                    <p class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Preferencias</p>
                    <div class="flex flex-wrap items-center gap-3 px-3">
                        @auth @include('layouts.partials.workday-timer') @endauth
                        @include('layouts.partials.theme-toggle')
                        @include('layouts.partials.layout-toggle')
                        @include('layouts.partials/language-toggle')
                    </div>
                </div>
            </nav>

            {{-- Footer actions --}}
            <div class="px-3 py-4 border-t border-gray-100 dark:border-gray-800 space-y-1">
                <a href="{{ route('profile.edit') }}" @click="open = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Mi Perfil
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        {{ __('navigation.logout') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endauth

    <!-- Flash Messages -->
    @if (session('success'))
        <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)" x-cloak
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
        <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 6000)" x-cloak
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
        <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 6000)" x-cloak
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

    <!-- Header for Vertical Layout -->
    <div x-show="layout === 'vertical'" x-cloak
        class="sticky top-0 z-20 w-full bg-white/80 dark:bg-gray-950/80 backdrop-blur-xl border-b border-gray-200 dark:border-gray-800 transition-all duration-300"
        :class="sidebarOpen ? 'lg:pl-72' : ''">
        <div class="w-full">
            <!-- Row 1: Global Navigation & System Tools -->
            <div class="flex items-center justify-between px-2 sm:px-6 lg:px-8 py-2 border-b border-gray-100 dark:border-gray-800/50">
                <div class="flex items-center shrink-0">
                    <!-- Toggle button -->
                    <button x-show="!sidebarOpen" @click="sidebarOpen = true"
                        class="p-2 rounded-lg text-gray-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-500/10 transition-all shadow-sm"
                        title="{{ __('Open Sidebar') }}" x-cloak>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    @if(isset($team))
                        <span class="ml-2 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest hidden sm:block">{{ $team->name }}</span>
                    @endif
                </div>

                <!-- System Tools (Top Right) -->
                <div class="flex items-center gap-1.5 shrink-0">
                    @include('teams.partials.header-actions-extra', ['layout' => 'vertical'])
                </div>
            </div>

            <!-- Row 2: Page specific content (Slot) - Takes 100% width -->
            <div class="w-full px-2 sm:px-6 lg:px-8 py-3">
                <div class="min-w-0 prose-headers:m-0">
                    @if (isset($header))
                        {{ $header }}
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Page content -->
    <main id="mainContent" class="px-3 sm:px-6 lg:px-8 py-8 pb-24 sm:pb-8 overflow-x-hidden"
        :class="[
            layout === 'vertical' ? (sidebarOpen ? 'lg:pl-72' : '') : '',
            'w-full max-w-none lg:{{ $maxWidth }} lg:mx-auto'
        ]">

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
            class="max-w-none lg:{{ $maxWidth }} lg:mx-auto px-2 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center text-xs text-gray-500 dark:text-gray-400 font-medium">
            <div class="mb-2 md:mb-0 flex items-center gap-2">
                <span class="font-bold">© {{ date('Y') }} <a href="https://www.sientia.com" class="hover:underline hover:text-violet-600 transition-colors">Sientia</a></span>
                <span class="mx-1">|</span>
                <span>v{{ config('app.version', '1.0.0') }}</span>
                <span class="mx-1">|</span>
                <a href="https://www.gnu.org/licenses/agpl-3.0.txt" target="_blank"
                    class="hover:text-violet-600 dark:hover:text-violet-400 transition-colors">Licencia AGPL v3</a>
            </div>
            <div class="flex items-center space-x-6">
                <!-- Open Source Links -->
                <div class="flex items-center gap-3 border-r border-gray-200 dark:border-gray-800 pr-4 mr-2">
                    <a href="https://github.com/pbenav" target="_blank" title="GitHub" class="hover:text-gray-900 dark:hover:text-white transition-colors">
                        <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>
                    </a>
                    <a href="https://gitlab.com/pbenav" target="_blank" title="GitLab" class="hover:text-gray-900 dark:hover:text-white transition-colors">
                        <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24"><path d="M23.955 13.587l-1.342-4.135-2.664-8.189c-.135-.417-.724-.417-.86 0L16.425 9.452h-8.85l-2.664-8.189c-.135-.417-.724-.417-.86 0L1.387 9.452.045 13.587c-.11.34.01.711.306.925l11.65 8.458 11.648-8.458c.296-.214.416-.585.306-.925z"/></svg>
                    </a>
                </div>
                
                <div class="flex items-center gap-4">
                    <a href="{{ route('privacy') }}" class="hover:text-violet-600 dark:hover:text-violet-400 transition-colors">{{ __('Privacidad') }}</a>
                    <a href="{{ route('terms') }}" class="hover:text-violet-600 dark:hover:text-violet-400 transition-colors">{{ __('Términos') }}</a>
                    <a href="{{ route('cookies') }}" class="hover:text-violet-600 dark:hover:text-violet-400 transition-colors">{{ __('Cookies') }}</a>
                </div>
                <span class="text-gray-300 dark:text-gray-700">|</span>
                <div class="flex items-center gap-5">
                    <a href="https://www.patreon.com/cw/sientia" target="_blank"
                        class="text-orange-600 hover:text-orange-700 font-bold transition-colors flex items-center gap-1.5 group">
                        <i class="fab fa-patreon group-hover:scale-110 transition-transform"></i>
                        Patreon
                    </a>
                    <span class="text-gray-300 dark:text-gray-700 mx-1">|</span>
                    <a href="https://buymeacoffee.com/sientia" target="_blank"
                        class="text-yellow-600 hover:text-yellow-700 font-bold transition-colors flex items-center gap-1.5 group">
                        <i class="fas fa-coffee group-hover:scale-110 transition-transform"></i>
                        Buy me a coffee
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Telegram Chat Experiment -->
    @auth
        @include('partials.telegram-widget')

        @php
            $currTeam = request()->route('team');
            $currTeamId = $currTeam ? (is_object($currTeam) ? $currTeam->id : $currTeam) : null;
            $currTask = request()->route('task');
            $currTaskId = $currTask ? (is_object($currTask) ? $currTask->id : $currTask) : null;
            $currThread = request()->route('thread');
            $currThreadId = $currThread ? (is_object($currThread) ? $currThread->id : $currThread) : null;
            $currMessage = request()->route('message');
            $currMessageId = $currMessage ? (is_object($currMessage) ? $currMessage->id : $currMessage) : null;
        @endphp
        <x-ai-assistant :team-id="$currTeamId" :task-id="$currTaskId" :thread-id="$currThreadId" :message-id="$currMessageId" />
    @endauth

    {{-- ============================================================
         MOBILE BOTTOM NAVIGATION BAR
         Visible only on small screens (< sm = 640px)
         ============================================================ --}}
    @auth
    @php
        $mobileTeamId = null;
        if (request()->route('team')) {
            $mobileTeamId = is_object(request()->route('team'))
                ? request()->route('team')->id
                : request()->route('team');
        }
    @endphp
    <nav class="fixed bottom-0 left-0 right-0 z-50 sm:hidden bg-white dark:bg-gray-950 border-t border-gray-200 dark:border-gray-800 shadow-2xl">
        <div class="flex items-stretch h-16">

            {{-- Dashboard / My Teams --}}
            <a href="{{ route('teams.index') }}"
               class="flex flex-col items-center justify-center flex-1 gap-1 text-gray-400 dark:text-gray-500 transition-colors
                      {{ request()->routeIs('teams.index') || request()->routeIs('dashboard') ? 'text-violet-600 dark:text-violet-400' : 'hover:text-gray-700 dark:hover:text-gray-300' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="text-[9px] font-bold uppercase tracking-tight leading-none">Inicio</span>
            </a>

            {{-- Tasks (only if inside a team) --}}
            @if($mobileTeamId)
            <a href="{{ route('teams.tasks.index', $mobileTeamId) }}"
               class="flex flex-col items-center justify-center flex-1 gap-1 text-gray-400 dark:text-gray-500 transition-colors
                      {{ request()->routeIs('teams.tasks.*') ? 'text-violet-600 dark:text-violet-400' : 'hover:text-gray-700 dark:hover:text-gray-300' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <span class="text-[9px] font-bold uppercase tracking-tight leading-none">Tareas</span>
            </a>
            @else
            <a href="{{ route('dashboard') }}"
               class="flex flex-col items-center justify-center flex-1 gap-1 text-gray-400 dark:text-gray-500 transition-colors hover:text-gray-700 dark:hover:text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <span class="text-[9px] font-bold uppercase tracking-tight leading-none">Dashboard</span>
            </a>
            @endif

            {{-- Notifications --}}
            <a href="{{ route('notifications.index') }}"
               class="flex flex-col items-center justify-center flex-1 gap-1 relative text-gray-400 dark:text-gray-500 transition-colors
                      {{ request()->routeIs('notifications.*') ? 'text-violet-600 dark:text-violet-400' : 'hover:text-gray-700 dark:hover:text-gray-300' }}">
                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    @if(auth()->user()->unreadNotifications->count() > 0)
                        <span class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white">
                            {{ auth()->user()->unreadNotifications->count() > 9 ? '9+' : auth()->user()->unreadNotifications->count() }}
                        </span>
                    @endif
                </div>
                <span class="text-[9px] font-bold uppercase tracking-tight leading-none">Avisos</span>
            </a>

            {{-- Views (team switcher trigger) - only inside a team --}}
            @if($mobileTeamId)
            <a href="{{ route('teams.dashboard', $mobileTeamId) }}"
               class="flex flex-col items-center justify-center flex-1 gap-1 text-gray-400 dark:text-gray-500 transition-colors
                      {{ request()->routeIs('teams.dashboard') ? 'text-violet-600 dark:text-violet-400' : 'hover:text-gray-700 dark:hover:text-gray-300' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                <span class="text-[9px] font-bold uppercase tracking-tight leading-none">Matriz</span>
            </a>
            @else
            <a href="{{ route('media.index') }}"
               class="flex flex-col items-center justify-center flex-1 gap-1 text-gray-400 dark:text-gray-500 transition-colors hover:text-gray-700 dark:hover:text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                </svg>
                <span class="text-[9px] font-bold uppercase tracking-tight leading-none">Archivos</span>
            </a>
            @endif

            {{-- Profile / User --}}
            <a href="{{ route('profile.edit') }}"
               class="flex flex-col items-center justify-center flex-1 gap-1 text-gray-400 dark:text-gray-500 transition-colors
                      {{ request()->routeIs('profile.*') ? 'text-violet-600 dark:text-violet-400' : 'hover:text-gray-700 dark:hover:text-gray-300' }}">
                <div class="w-6 h-6 rounded-full bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center text-[10px] font-bold text-white">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
                <span class="text-[9px] font-bold uppercase tracking-tight leading-none">Perfil</span>
            </a>

        </div>
    </nav>
    @endauth

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

        window.openGoogleAuth = function(teamId = null) {
            const width = 600;
            const height = 700;
            const left = (window.innerWidth - width) / 2;
            const top = (window.innerHeight - height) / 2;
            
            // Mark that we are starting a Google Auth process
            localStorage.setItem('google_auth_in_progress', '1');
            
            let url = "{{ route('google.auth') }}?popup=1";
            if (teamId) url += "&team_id=" + teamId;

            const popup = window.open(url, 'GoogleAuth', `width=${width},height=${height},top=${top},left=${left}`);

            const messageHandler = function(event) {
                if (event.data === 'google-auth-success') {
                    localStorage.removeItem('google_auth_in_progress');
                    window.removeEventListener('message', messageHandler);
                    location.reload();
                }
            };

            window.addEventListener('message', messageHandler);

            // Redundant fallback listener via LocalStorage
            window.addEventListener('storage', function(event) {
                if (event.key === 'google-auth-trigger') {
                    localStorage.removeItem('google_auth_in_progress');
                    location.reload();
                }
            });
        };

        // GHOST POPUP DETECTOR:
        // If this page loads in a window that has an opener AND we marked an auth in progress,
        // it means we are a popup that was redirected to the dashboard by mistake.
        (function() {
            if (window.opener && localStorage.getItem('google_auth_in_progress') === '1') {
                console.log("Ghost popup detected! Closing and notifying parent...");
                localStorage.removeItem('google_auth_in_progress');
                localStorage.setItem('google_auth_trigger', Date.now());
                if (window.opener.postMessage) {
                    window.opener.postMessage('google-auth-success', '*');
                }
                window.close();
            }
        })();

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
                const appRoot = document.getElementById('app-root');
                if (appRoot) {
                    const zoomPercent = (parseFloat(val) * 100);
                    appRoot.style.zoom = zoomPercent + '%';
                }
                // Avisar a los componentes de UI del cambio de zoom
                window.dispatchEvent(new CustomEvent('global-zoom-changed', { detail: val }));
            }

            window.adjustGlobalZoom = function(delta) {
                let currentZoom = parseFloat(localStorage.getItem('global_zoom') || '1.0');
                currentZoom = Math.round((currentZoom + delta) * 100) / 100;
                currentZoom = Math.max(0.5, Math.min(1.5, currentZoom));
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
    </div>



    @stack('modals')
    @stack('scripts')
</body>

</html>
