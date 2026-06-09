<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $microsite->title }}</title>
    
    <meta name="description" content="Micrositio publicado por {{ $microsite->team->name }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @if($usesTailwind ?? false)
    <script src="https://cdn.tailwindcss.com"></script>
    @endif

    <!-- CSS del Micrositio (con scaffold premium y variables por defecto) -->
    @if(!empty($cssContent) || $microsite->css_content)
    <style>
        {!! $cssContent ?? $microsite->css_content !!}
    </style>
    @endif

    <style>
        /* Aislar el micrositio del tema global (dark mode, colores heredados) */
        .microsite-canvas {
            isolation: isolate;
            width: 100%;
        }
        .microsite-canvas .ms-root {
            min-height: 50vh;
        }
    </style>
</head>
<body class="font-sans antialiased min-h-screen flex flex-col bg-gray-50 dark:bg-gray-950">
    
    <!-- Barra superior Sientia MTX (Opcional, para dar contexto de que es parte del portal) -->
    <div class="bg-gray-900 text-white py-2 px-4 shadow-md text-xs sm:text-sm flex justify-between items-center z-50 relative">
        <div class="flex items-center gap-2">
            <span class="font-bold opacity-80 uppercase tracking-widest text-[10px] bg-white/10 px-2 py-0.5 rounded-md">
                Publicado por
            </span>
            <span class="font-semibold">{{ $microsite->team->name }}</span>
        </div>
        <div>
            <a href="{{ route('public.microsites.directory') }}" class="hover:text-pink-400 transition-colors flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <span class="hidden sm:inline">Directorio de Micrositios</span>
            </a>
        </div>
    </div>

    <!-- Contenido Principal del Micrositio -->
    <main class="microsite-canvas flex-1 w-full relative">
        {!! $htmlContent ?? $microsite->html_content !!}
    </main>

    <!-- Footer Sientia MTX -->
    <footer class="bg-gray-100 dark:bg-gray-900 py-6 border-t border-gray-200 dark:border-gray-800 text-center text-xs text-gray-500 dark:text-gray-400 mt-auto">
        <p>&copy; {{ date('Y') }} Sientia MTX. Plataforma de Resiliencia y Gestión Eficiente.</p>
        <p class="mt-1 opacity-60">Este contenido ha sido publicado por un usuario y no refleja necesariamente la opinión de Sientia MTX.</p>
    </footer>

    <script>
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('[data-ms-fullscreen]');
            if (!btn) return;
            const viewer = btn.closest('.ms-pdf-viewer');
            if (!viewer) return;
            if (document.fullscreenElement) {
                document.exitFullscreen?.();
            } else {
                viewer.requestFullscreen?.().catch(function () {
                    window.open(viewer.querySelector('.ms-pdf-frame')?.src || viewer.querySelector('iframe')?.src, '_blank');
                });
            }
        });
        document.addEventListener('fullscreenchange', function () {
            document.querySelectorAll('[data-ms-fullscreen]').forEach(function (btn) {
                btn.textContent = document.fullscreenElement ? '✕ Salir' : '⛶ Pantalla completa';
            });
        });
    </script>

</body>
</html>
