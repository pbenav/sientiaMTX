<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $publication->document_code }} — {{ $publication->title }}</title>
    <meta name="description" content="{{ $publication->summary ?? $publication->title }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; font-size: 11pt; }
            .print-border { border: 1px solid #ddd !important; }
            .shadow-xs, .shadow-sm, .shadow-md, .shadow-lg { box-shadow: none !important; }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 font-sans antialiased min-h-screen flex flex-col"
      x-data="{ activeTab: 'pdf' }">

    @php
        $csvCode = $csv ?? ($publication->metadata['csv'] ?? $publication->public_token);
        $pdfStreamUrl = route('public.circuits.pdf', $publication->public_slug);
        $pdfDownloadUrl = route('public.circuits.download', $publication->public_slug);
        $verifyUrl = route('public.circuits.verify', ['csv' => $csvCode]);
        $attachmentsList = $attachments ?? collect();
    @endphp

    <!-- Official Navbar -->
    <header class="bg-white border-b border-gray-200 shadow-xs no-print sticky top-0 z-30">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex items-center justify-between gap-3 flex-wrap">
            <a href="{{ route('public.circuits.board', $publication->team) }}"
                class="inline-flex items-center gap-2 text-xs font-bold text-gray-600 hover:text-teal-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                <span>Volver al Tablón Edictal</span>
            </a>

            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ $verifyUrl }}" target="_blank"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-teal-50 hover:bg-teal-100 text-teal-800 border border-teal-200 transition-colors"
                    title="Cotejar validez oficial mediante Código Seguro de Verificación">
                    <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span>Cotejar CSV</span>
                </a>

                <a href="{{ $pdfDownloadUrl }}"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-black bg-teal-600 hover:bg-teal-700 text-white shadow-sm shadow-teal-600/20 transition-all hover:scale-105 active:scale-95"
                    title="Descargar el documento original consolidado en PDF con firmas reconocidas">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Descargar PDF Firmado</span>
                </a>

                <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span class="hidden sm:inline">Imprimir</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <!-- Document Sheet Card -->
        <div class="bg-white rounded-3xl p-6 sm:p-10 border border-gray-200 shadow-sm print-border space-y-6">

            <!-- Document Header -->
            <div class="border-b-2 border-gray-900 pb-6 flex flex-col sm:flex-row justify-between items-start gap-4">
                <div class="space-y-1">
                    <span class="text-xs font-black tracking-widest uppercase text-teal-700 block">
                        {{ $publication->team->name }}
                    </span>
                    <span class="text-[11px] font-bold text-gray-400 block">
                        Tablón Edictal Oficial · Sede Electrónica
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight pt-1">
                        {{ $publication->title }}
                    </h1>
                </div>

                <div class="text-left sm:text-right shrink-0 bg-gray-50 p-4 rounded-2xl border border-gray-200 min-w-[200px]">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Código Oficial</div>
                    <div class="text-base font-black font-mono text-teal-700">{{ $publication->document_code }}</div>
                    <div class="text-[11px] text-gray-500 mt-1">Fecha: <strong>{{ $publication->publication_date->format('d/m/Y') }}</strong></div>
                    @if($publication->expiration_date)
                        <div class="text-[10px] text-gray-400 mt-0.5">Vence: {{ $publication->expiration_date->format('d/m/Y') }}</div>
                    @endif
                </div>
            </div>

            <!-- Summary / Extract -->
            @if($publication->summary)
                <div class="p-4 bg-teal-50/50 rounded-2xl border border-teal-100 text-sm text-teal-900 font-medium leading-relaxed">
                    {{ $publication->summary }}
                </div>
            @endif

            <!-- Navigation Tabs -->
            <div class="border-b border-gray-200 no-print flex items-center gap-2 overflow-x-auto">
                <button type="button" @click="activeTab = 'pdf'"
                    class="flex items-center gap-2 py-3 px-4 text-xs font-black uppercase tracking-wider border-b-2 transition-all shrink-0"
                    :class="activeTab === 'pdf' ? 'border-teal-600 text-teal-700 bg-teal-50/50 rounded-t-xl' : 'border-transparent text-gray-500 hover:text-gray-900'">
                    <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    <span>Visor PDF Oficial Firmado</span>
                </button>

                <button type="button" @click="activeTab = 'content'"
                    class="flex items-center gap-2 py-3 px-4 text-xs font-black uppercase tracking-wider border-b-2 transition-all shrink-0"
                    :class="activeTab === 'content' ? 'border-teal-600 text-teal-700 bg-teal-50/50 rounded-t-xl' : 'border-transparent text-gray-500 hover:text-gray-900'">
                    <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Transcripción del Documento</span>
                </button>

                @if($attachmentsList->isNotEmpty())
                    <button type="button" @click="activeTab = 'attachments'"
                        class="flex items-center gap-2 py-3 px-4 text-xs font-black uppercase tracking-wider border-b-2 transition-all shrink-0"
                        :class="activeTab === 'attachments' ? 'border-teal-600 text-teal-700 bg-teal-50/50 rounded-t-xl' : 'border-transparent text-gray-500 hover:text-gray-900'">
                        <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        <span>Anexos Adjuntos ({{ $attachmentsList->count() }})</span>
                    </button>
                @endif
            </div>

            <!-- TAB 1: VISOR PDF OFICIAL FIRMADO -->
            <div x-show="activeTab === 'pdf'" class="space-y-4">
                <!-- PDF Toolbar -->
                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </span>
                        <div>
                            <p class="font-bold text-gray-900">Documento Oficial Consolidado con Diligencia de Firmas</p>
                            <p class="text-[11px] text-gray-500 font-mono">CSV: {{ $csvCode }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <a href="{{ $pdfStreamUrl }}" target="_blank"
                            class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            <span>Pantalla Completa</span>
                        </a>
                        <a href="{{ $pdfDownloadUrl }}"
                            class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-black bg-teal-600 hover:bg-teal-700 text-white transition-all shadow-xs">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            <span>Descargar</span>
                        </a>
                    </div>
                </div>

                <!-- Embedded PDF Viewer -->
                <div class="w-full bg-gray-100 rounded-2xl border border-gray-200 overflow-hidden shadow-inner">
                    <iframe src="{{ $pdfStreamUrl }}#toolbar=1&navpanes=0"
                            class="w-full h-[780px] border-0"
                            title="Visor PDF Documento Oficial Firmado">
                        <div class="p-8 text-center space-y-4">
                            <p class="text-sm text-gray-600">Su navegador no soporta la visualización incrustada de PDFs.</p>
                            <a href="{{ $pdfDownloadUrl }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-black bg-teal-600 text-white">
                                Descargar documento PDF
                            </a>
                        </div>
                    </iframe>
                </div>
            </div>

            <!-- TAB 2: TRANSCRIPCIÓN MARKDOWN / CONTENIDO -->
            <div x-show="activeTab === 'content'" class="space-y-4">
                <div class="prose max-w-none text-gray-800 leading-relaxed text-sm sm:text-base border border-gray-100 bg-gray-50/40 p-6 sm:p-8 rounded-2xl">
                    @if(!empty($publication->content_html))
                        {!! str($publication->content_html)->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                    @else
                        <p class="italic text-gray-400">El cuerpo íntegro del documento se encuentra autenticado en el expediente y documento PDF oficial.</p>
                    @endif
                </div>
            </div>

            <!-- TAB 3: ANEXOS Y ARCHIVOS ADJUNTOS -->
            @if($attachmentsList->isNotEmpty())
                <div x-show="activeTab === 'attachments'" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($attachmentsList as $att)
                            @php
                                $isPdf = str_contains($att->mime_type ?? '', 'pdf') || str_ends_with(strtolower($att->file_name ?? ''), '.pdf');
                            @endphp
                            <div class="p-3.5 rounded-2xl border border-gray-200 bg-gray-50 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 {{ $isPdf ? 'bg-rose-100 text-rose-600' : 'bg-violet-100 text-violet-600' }}">
                                        @if($isPdf)
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                        @else
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-gray-900 truncate" title="{{ $att->file_name }}">{{ $att->file_name }}</p>
                                        <p class="text-[10px] text-gray-400 mt-0.5">{{ round(($att->file_size ?? 0) / 1024, 1) }} KB</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Diligencia Oficial de Aprobación y Trazabilidad de Firmas -->
            <div class="mt-8 p-6 rounded-2xl bg-gray-50 border border-gray-200 print-border">
                <div class="flex items-center gap-2 mb-3">
                    <svg class="w-5 h-5 text-teal-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider">
                        Diligencia Oficial de Validación y Aprobación
                    </h3>
                </div>
                <p class="text-xs text-gray-500 mb-4">
                    Este documento ha superado el circuito preceptivo de tramitación interna conforme a la siguiente trazabilidad colegiada:
                </p>

                <!-- Signatures / Audit Trail Table -->
                @php
                    $signatures = $publication->metadata['signatures_audit'] ?? [];
                @endphp

                @if(!empty($signatures))
                    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-100 text-gray-600 font-bold uppercase text-[10px] tracking-wider border-b border-gray-200">
                                <tr>
                                    <th class="py-2.5 px-3">Etapa</th>
                                    <th class="py-2.5 px-3">Acción</th>
                                    <th class="py-2.5 px-3">Interviniente / Cargo</th>
                                    <th class="py-2.5 px-3">Fecha y Hora</th>
                                    <th class="py-2.5 px-3">Resultado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($signatures as $sig)
                                    <tr>
                                        <td class="py-2.5 px-3 font-bold">{{ $sig['step_name'] ?? 'Etapa ' . ($sig['step_order'] ?? '') }}</td>
                                        <td class="py-2.5 px-3 font-mono capitalize">{{ $sig['action_type'] ?? 'Validación' }}</td>
                                        <td class="py-2.5 px-3 font-semibold">{{ $sig['signer_name'] ?? 'Responsable' }}</td>
                                        <td class="py-2.5 px-3 text-gray-500">
                                            {{ !empty($sig['completed_at']) ? \Carbon\Carbon::parse($sig['completed_at'])->format('d/m/Y H:i:s') : '—' }}
                                        </td>
                                        <td class="py-2.5 px-3">
                                            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                {{ ucfirst($sig['decision'] ?? 'Aprobado') }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <!-- Electronic Verification Code (CSV) -->
                <div class="mt-4 pt-4 border-t border-gray-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs text-gray-600 font-mono">
                    <div class="space-y-0.5">
                        <span class="text-gray-400 block text-[10px] uppercase font-bold">Código Seguro de Verificación (CSV):</span>
                        <strong class="text-gray-900 select-all text-xs bg-gray-100 px-2 py-0.5 rounded border border-gray-200">{{ $csvCode }}</strong>
                    </div>
                    <div>
                        <a href="{{ $verifyUrl }}" target="_blank"
                            class="inline-flex items-center gap-1 text-teal-700 hover:text-teal-800 font-bold underline">
                            <span>Sede Electrónica de Cotejo Oficial</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                    </div>
                </div>
            </div>

        </div>

    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-6 text-center text-xs text-gray-400 no-print">
        <div class="max-w-5xl mx-auto px-4">
            <p>{{ $publication->team->name }} · Tablón Edictal Oficial y Transparencia</p>
        </div>
    </footer>
</body>
</html>