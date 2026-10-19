<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tablón Edictal Oficial — {{ $team->name }}</title>
    <meta name="description" content="Tablón edictal y portal de documentos públicos de {{ $team->name }}. Publicaciones oficiales, bandos y resoluciones para la ciudadanía.">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900 font-sans antialiased min-h-screen flex flex-col">

    <!-- Official Header -->
    <header class="bg-white border-b border-gray-200 shadow-xs">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-teal-600 text-white flex items-center justify-center shrink-0 shadow-lg shadow-teal-600/20">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-[11px] font-black uppercase tracking-widest text-teal-700 bg-teal-50 px-2.5 py-0.5 rounded-md border border-teal-200">
                            Tablón Edictal Oficial
                        </span>
                        <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight mt-1">
                            {{ $team->name }}
                        </h1>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Portal oficial de puesta a disposición de documentos y resoluciones a la ciudadanía
                        </p>
                    </div>
                </div>

                <div class="text-xs text-gray-400 text-left sm:text-right">
                    <span class="font-bold text-gray-700 block">Fecha actual:</span>
                    <span>{{ now()->isoFormat('D [de] MMMM [de] YYYY') }}</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-black text-gray-900">
                    Publicaciones Oficiales Vigentes ({{ $publications->total() }})
                </h2>
                <p class="text-xs text-gray-500">
                    Documentos y acuerdos aprobados en vigor
                </p>
            </div>
        </div>

        @if($publications->isEmpty())
            <div class="bg-white rounded-3xl p-12 text-center border border-gray-200 shadow-sm max-w-lg mx-auto my-8">
                <div class="w-16 h-16 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h3 class="text-base font-black text-gray-900 mb-1">
                    No hay publicaciones vigentes en este momento
                </h3>
                <p class="text-xs text-gray-500">
                    Actualmente no constan edictos o documentos oficiales expuestos al público.
                </p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($publications as $pub)
                    <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-xs hover:shadow-md transition-all flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="space-y-2 flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-2.5 py-0.5 text-xs font-mono font-black text-teal-800 bg-teal-50 border border-teal-200 rounded-lg">
                                    {{ $pub->document_code }}
                                </span>
                                <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-md bg-emerald-100 text-emerald-800">
                                    En Vigor
                                </span>
                                <span class="text-xs text-gray-400">
                                    Publicado el {{ $pub->publication_date->format('d/m/Y') }}
                                </span>
                                @if($pub->expiration_date)
                                    <span class="text-xs text-gray-400">
                                        · Límite de exposición: {{ $pub->expiration_date->format('d/m/Y') }}
                                    </span>
                                @endif
                            </div>

                            <h3 class="text-lg font-black text-gray-900">
                                <a href="{{ route('public.circuits.show', $pub->public_slug) }}" class="hover:text-teal-600 transition-colors">
                                    {{ $pub->title }}
                                </a>
                            </h3>

                            @if($pub->summary)
                                <p class="text-xs text-gray-600 line-clamp-2">
                                    {{ $pub->summary }}
                                </p>
                            @endif
                        </div>

                        <div class="shrink-0 w-full sm:w-auto pt-3 sm:pt-0 border-t sm:border-t-0 border-gray-100">
                            <a href="{{ route('public.circuits.show', $pub->public_slug) }}"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-xs font-black bg-teal-600 hover:bg-teal-700 text-white shadow-md shadow-teal-600/20 transition-all hover:scale-105 active:scale-95">
                                <span>Consultar Documento</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                        </div>
                    </div>
                @endforeach

                <div class="mt-6">
                    {{ $publications->links() }}
                </div>
            </div>
        @endif
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-12 py-6 text-center text-xs text-gray-500">
        <div class="max-w-6xl mx-auto px-4">
            <p>© {{ date('Y') }} {{ $team->name }}. Portal oficial de transparencia y tablón edictal.</p>
            <p class="text-[11px] text-gray-400 mt-1">Plataforma impulsada por Sientia.</p>
        </div>
    </footer>
</body>
</html>
