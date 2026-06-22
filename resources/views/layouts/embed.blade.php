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
    <title>@yield('title', config('app.name'))</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 & Marked -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-markdown-styles :team="request()->route('team') && is_object(request()->route('team')) ? request()->route('team') : (request()->route('team') ? \App\Models\Team::find(request()->route('team')) : null)" />
    
    <!-- Basic styling overrides for seamless inner frame -->
    <style>
        :root { --color-q1: #ef4444; --color-q2: #3b82f6; --color-q3: #f59e0b; --color-q4: #6b7280; }
        body { font-family: 'Inter', sans-serif; background: transparent !important; }
        h1, h2, h3, h4, .heading { font-family: 'Space Grotesk', sans-serif; }
        [x-cloak] { display: none !important; }
        /* Hide navigation, breadcrumbs in embeds to save space if contained by parent modal */
        .breadcrumb-nav, #topNav, #sideNav { display: none !important; }
    </style>
</head>
<body class="antialiased overflow-x-hidden text-gray-900 dark:text-gray-100">
    <div class="p-2 sm:p-4">
        @if (isset($header))
            <header class="mb-4 px-2">
                {{ $header }}
            </header>
        @endif
        
        <main class="w-full h-full">
            {{ $slot }}
        </main>
    </div>

    @stack('modals')
    @stack('scripts')
</body>
</html>
