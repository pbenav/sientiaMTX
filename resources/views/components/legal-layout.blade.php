<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $metaDescription ?? config('app.name', 'Sientia') . ' — ' . ($title ?? 'Documentos Legales') }}">
    <meta name="robots" content="index, follow">
    <title>{{ config('app.name', 'Sientia') }} · {{ $title ?? 'Legal' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Inter', sans-serif; font-size: 13px; }

        /* ── Layout ── */
        .legal-wrap { max-width: 820px; margin: 0 auto; padding: 2.5rem 1.5rem 4rem; }

        /* ── Header strip ── */
        .legal-header { border-bottom: 1px solid #e5e7eb; background: #fafafa; padding: 0.75rem 1.5rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
        .dark .legal-header { background: #0f0f13; border-color: #1f2937; }
        .legal-nav-link { font-size: 11px; font-weight: 500; color: #6b7280; padding: 0.25rem 0.6rem; border-radius: 4px; transition: all 0.15s; text-decoration: none; }
        .legal-nav-link:hover, .legal-nav-link.active { background: #7c3aed; color: #fff; }
        .dark .legal-nav-link { color: #9ca3af; }

        /* ── Prose ── */
        .legal-prose { color: #374151; line-height: 1.75; font-size: 13px; }
        .dark .legal-prose { color: #d1d5db; }

        .legal-prose h1 { font-size: 1.5rem; font-weight: 800; color: #111827; margin-bottom: 0.25rem; letter-spacing: -0.02em; }
        .dark .legal-prose h1 { color: #f9fafb; }
        .legal-prose .meta { font-size: 11px; color: #9ca3af; margin-bottom: 1.5rem; }

        .legal-prose h2 { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #6b7280; margin-top: 2rem; margin-bottom: 0.5rem; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.3rem; }
        .dark .legal-prose h2 { color: #9ca3af; border-color: #1f2937; }
        .legal-prose h3 { font-size: 0.8rem; font-weight: 700; color: #1f2937; margin-top: 1.25rem; margin-bottom: 0.35rem; }
        .dark .legal-prose h3 { color: #e5e7eb; }

        .legal-prose p { margin-bottom: 0.9rem; }
        .legal-prose ul, .legal-prose ol { padding-left: 1.25rem; margin-bottom: 0.9rem; }
        .legal-prose li { margin-bottom: 0.35rem; }
        .legal-prose a { color: #7c3aed; text-underline-offset: 2px; }
        .legal-prose strong { font-weight: 600; color: #111827; }
        .dark .legal-prose strong { color: #f3f4f6; }

        .legal-prose .callout { background: #f5f3ff; border-left: 3px solid #7c3aed; border-radius: 4px; padding: 0.75rem 1rem; margin: 1rem 0; font-size: 12px; }
        .dark .legal-prose .callout { background: rgba(124,58,237,0.07); }

        .legal-prose table { width: 100%; border-collapse: collapse; font-size: 12px; margin-bottom: 1rem; }
        .legal-prose th { background: #f9fafb; padding: 0.4rem 0.75rem; text-align: left; font-weight: 600; border: 1px solid #e5e7eb; }
        .dark .legal-prose th { background: #1f2937; border-color: #374151; }
        .legal-prose td { padding: 0.4rem 0.75rem; border: 1px solid #e5e7eb; vertical-align: top; }
        .dark .legal-prose td { border-color: #374151; }

        /* ── Field placeholders ── */
        .legal-prose .field { display: inline; background: #fef9c3; border-radius: 2px; padding: 0 3px; font-style: italic; color: #92400e; font-size: 11px; }
        .dark .legal-prose .field { background: rgba(250,204,21,0.12); color: #fcd34d; }
    </style>
</head>

<body class="antialiased bg-white dark:bg-gray-950 text-gray-900 dark:text-gray-100">

    {{-- ── Top bar ── --}}
    <header class="legal-header">
        <div class="flex items-center gap-3">
            <a href="/" class="flex items-center gap-1.5 text-xs font-semibold text-gray-400 hover:text-violet-600 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                {{ config('app.name', 'Sientia') }}
            </a>
        </div>
        <nav class="flex items-center gap-1" aria-label="Documentos legales">
            <a href="{{ route('terms') }}"   class="legal-nav-link {{ request()->routeIs('terms')   ? 'active' : '' }}">Términos</a>
            <a href="{{ route('privacy') }}" class="legal-nav-link {{ request()->routeIs('privacy') ? 'active' : '' }}">Privacidad</a>
            <a href="{{ route('cookies') }}" class="legal-nav-link {{ request()->routeIs('cookies') ? 'active' : '' }}">Cookies</a>
        </nav>
    </header>

    {{-- ── Main content ── --}}
    <main class="legal-wrap">
        <article class="legal-prose">
            {{ $slot }}
        </article>

        <footer class="mt-12 pt-6 border-t border-gray-100 dark:border-gray-800 flex flex-wrap gap-4 justify-between items-center" style="font-size:11px; color:#9ca3af;">
            <span>© {{ date('Y') }} {{ config('app.name', 'Sientia') }}. Todos los derechos reservados.</span>
            <div class="flex gap-3">
                <a href="{{ route('terms') }}"   class="hover:text-violet-500 transition-colors">Términos</a>
                <a href="{{ route('privacy') }}" class="hover:text-violet-500 transition-colors">Privacidad</a>
                <a href="{{ route('cookies') }}" class="hover:text-violet-500 transition-colors">Cookies</a>
            </div>
        </footer>
    </main>

</body>
</html>
