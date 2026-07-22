@php
    $layout = auth()->check() ? (auth()->user()->layout ?: 'horizontal') : request()->cookie('layout', 'horizontal');
    // Normalize maxWidth to ensure it includes the 'max-w-' prefix if it's a standard size
    if (isset($maxWidth) && !str_starts_with($maxWidth, 'max-w-') && $maxWidth !== 'none') {
        $maxWidth = 'max-w-' . $maxWidth;
    }
    $maxWidth = $maxWidth ?? 'max-w-7xl';

    // Get global team context for background tools like chat or drive
    $currentTeamContext = request()->route('team');
    if (!$currentTeamContext && auth()->check()) {
        $currentTeamContext = auth()->user()->teams()->first();
    }
    if ($currentTeamContext && !is_object($currentTeamContext)) {
        $currentTeamContext = \App\Models\Team::find($currentTeamContext);
    }
    $hasGoogleLinked = false;
    if (auth()->check() && $currentTeamContext) {
        $hasGoogleLinked = auth()->user()->teams()->where('team_id', $currentTeamContext->id)->wherePivotNotNull('google_token')->exists();
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
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

    <!-- Metadatos de Nombre de Sitio para Google (SEO / Open Graph) -->
    <meta property="og:site_name" content="Sientia Open Labs">

    <title>{{ config('app.name', 'sientiaMTX') }} — @yield('title', __('navigation.dashboard'))</title>
    <meta name="description" content="@yield('meta_description', 'sientiaMTX — Smart project management with MTX, Gantt, and Kanban for focused teams.')">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    {{--
        ┌─────────────────────────────────────────────────────────────────┐
        │  CRITICAL INLINE STYLES — Must load before ANY external asset   │
        │  Prevents Alpine.js "flash of unstyled content" (FOUC) on      │
        │  mobile and slow connections where the Vite bundle arrives late  │
        └─────────────────────────────────────────────────────────────────┘
    --}}
    <style>
        /* Hide Alpine.js elements marked with x-cloak until Alpine initialises */
        [x-cloak] { display: none !important; }

        /* Pre-hide elements that are always hidden on load (open=false by default)
           to prevent the FOUC before Alpine boots. These match the x-show
           directives used in this layout. */
        [data-fouc-hide] { display: none !important; }

        /* Forzar que el cursor del ratón nunca desaparezca en modales y backdrops de SweetAlert2 */
        body.swal2-shown,
        body.swal2-shown *,
        .swal2-container,
        .swal2-container *,
        .swal2-popup,
        .swal2-modal,
        .swal2-backdrop-show {
            cursor: auto !important;
            pointer-events: auto !important;
        }
    </style>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=block"
        rel="stylesheet">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        if (typeof Swal === 'undefined') {
            document.write('<script src="https://unpkg.com/sweetalert2@11"><\/script>');
        }
    </script>

    <!-- Marked.js (Markdown Rendering) -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script>
        if (typeof marked === 'undefined') {
            document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/marked/12.0.2/marked.min.js"><\/script>');
        }
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof marked !== 'undefined') {
                marked.use({ breaks: true, gfm: true });
            }
        });
    </script>

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @include('layouts.partials.head-scripts')
</head>

<body class="h-full bg-white text-gray-900 dark:bg-gray-950 dark:text-gray-100 antialiased"
    :class="{ 'sidebar-closed': !sidebarOpen && layout === 'vertical' }"
    x-data="{
    layout: '{{ $layout }}',
    sidebarOpen: false,
    mounted: false,
    cleanMode: localStorage.getItem('cleanMode') === 'true',
    toggleCleanMode() {
        this.cleanMode = !this.cleanMode;
        localStorage.setItem('cleanMode', this.cleanMode);
    },
    init() {
        this.$nextTick(() => {
            this.mounted = true;
            this.sidebarOpen = (window.innerWidth >= 1024 && this.layout === 'vertical');
        });
        window.addEventListener('resize', () => {
            if (window.innerWidth < 1024) {
                this.sidebarOpen = false;
            }
        });
    },
    async updateLayout(newLayout) {
        if (this.layout === newLayout) return;

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

        window.location.reload();
    }
}">

    <div id="app-root" class="min-h-screen flex flex-col">

        {{-- ╔══════════════════════════════════════════════════════════════╗ --}}
        {{-- ║            MODO DEMOSTRACIÓN — BANNER GLOBAL                ║ --}}
        {{-- ║  Sólo visible cuando APP_DEMO_MODE=on en .env               ║ --}}
        {{-- ╚══════════════════════════════════════════════════════════════╝ --}}
        @if($isDemoMode)
        <div id="demo-mode-banner"
             class="relative z-[200] w-full flex items-center justify-center gap-3 px-4 py-2
                    bg-gradient-to-r from-violet-700 via-purple-700 to-violet-700
                    text-white text-xs font-bold tracking-wide shadow-lg shadow-violet-900/40
                    overflow-hidden select-none">

            {{-- Shimmer background animation --}}
            <div class="absolute inset-0 opacity-20 bg-[length:200%_100%]
                         bg-gradient-to-r from-transparent via-white to-transparent
                         animate-[shimmer_3s_linear_infinite]"
                 style="background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.3) 50%, transparent 100%);
                        background-size: 200% 100%;
                        animation: shimmer 3s linear infinite;">
            </div>

            {{-- Pulsing dot --}}
            <span class="relative flex h-2.5 w-2.5 shrink-0">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-300 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-400"></span>
            </span>

            {{-- Text --}}
            <span class="relative">
                🎭 <strong>MODO DEMOSTRACIÓN ACTIVO</strong>
                &nbsp;—&nbsp;
                Los datos sensibles han sido enmascarados para proteger la privacidad de los usuarios.
            </span>

            {{-- Admin link to settings --}}
            @auth
                @can('admin')
                <a href="{{ route('settings.mail') }}"
                   class="relative ml-2 shrink-0 inline-flex items-center gap-1 px-2.5 py-1 rounded-full
                          bg-white/20 hover:bg-white/30 transition-colors duration-200 text-white/90
                          hover:text-white text-[10px] font-black uppercase tracking-widest border border-white/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Configurar
                </a>
                @endcan
            @endauth
        </div>
        @endif
        {{-- / MODO DEMOSTRACIÓN BANNER --}}

        @include('partials.welcome-modal')
        @include('partials.work-schedule-modal')
    @include('layouts.navigation-sidebar')


    @include('layouts.partials.header-horizontal')
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

    <div x-show="layout === 'vertical'"
        style="{{ $layout === 'horizontal' ? 'display:none' : '' }}"
        class="sticky top-0 z-20 w-full bg-white/80 dark:bg-gray-950/80 backdrop-blur-xl border-b border-gray-200 dark:border-gray-800 transition-all duration-300 {{ $layout === 'vertical' ? 'header-v-fix' : '' }}"
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

            <!-- Row 2: Page specific content (Slot) -->
            <div class="w-full px-2 sm:px-6 lg:px-8 py-2">
                <div class="min-w-0">
                    @if (isset($header))
                        {{ $header }}
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Page content -->
    <main id="mainContent"
        class="px-3 sm:px-6 lg:px-8 py-4 pb-20 sm:pb-4 {{ $layout === 'vertical' ? 'lg-layout-v-fix' : '' }}"
        style="{{ $layout === 'vertical' ? 'padding-left: 18rem;' : '' }}"
        data-wide-content="{{ ($maxWidth === 'max-w-full' || $maxWidth === 'max-w-none') ? 'true' : 'false' }}"
        :class="[
            layout === 'vertical' ? (sidebarOpen ? 'lg:pl-72' : '') : '',
            'w-full max-w-none lg:{{ $maxWidth }} lg:mx-auto'
        ]">
        <script>
            if (window.innerWidth < 1024) {
                document.getElementById('mainContent').style.paddingLeft = '0';
            }
        </script>

        @if (isset($header) && $layout === 'horizontal')
            <div class="mb-3">
                {{ $header }}
            </div>
        @endif

        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="mt-auto border-t border-gray-200 dark:border-gray-800 py-4 {{ $layout === 'vertical' ? 'lg-layout-v-fix' : '' }}"
        style="{{ $layout === 'vertical' ? 'padding-left: 18rem;' : '' }}"
        :class="layout === 'vertical' ? (sidebarOpen ? 'lg:pl-72' : '') : ''">
        <script>
            if (window.innerWidth < 1024) {
                document.querySelector('footer').style.paddingLeft = '0';
            }
        </script>
        <div
            class="max-w-none lg:{{ $maxWidth }} lg:mx-auto px-2 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center text-xs text-gray-500 dark:text-gray-400 font-medium">
            <div class="mb-2 md:mb-0 flex items-center gap-2">
                <span class="font-bold">© {{ date('Y') }} <a href="https://www.sientia.com" class="hover:underline hover:text-violet-600 transition-colors">Sientia Open Source Lab</a></span>
                <span class="mx-1">|</span>
                <span>v{{ config('app.version', '1.1.0') }}</span>
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


    @include('layouts.partials.widgets')

    @include('layouts.partials.global-js')
</body>

</html>
