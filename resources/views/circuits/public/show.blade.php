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
<body class="bg-gray-50 text-gray-900 font-sans antialiased min-h-screen flex flex-col">

    <!-- Official Navbar -->
    <header class="bg-white border-b border-gray-200 shadow-xs no-print">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
            <a href="{{ route('public.circuits.board', $publication->team) }}"
                class="inline-flex items-center gap-2 text-xs font-bold text-gray-600 hover:text-teal-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                <span>Volver al Tablón Edictal</span>
            </a>

            <div class="flex items-center gap-3">
                <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-black bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span>Imprimir Documento</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <!-- Document Sheet Card -->
        <div class="bg-white rounded-3xl p-8 sm:p-12 border border-gray-200 shadow-sm print-border">
            
            <!-- Document Header -->
            <div class="border-b-2 border-gray-900 pb-6 mb-8 flex flex-col sm:flex-row justify-between items-start gap-4">
                <div>
                    <span class="text-xs font-black tracking-widest uppercase text-teal-700 block">
                        {{ $publication->team->name }}
                    </span>
                    <span class="text-[11px] font-bold text-gray-400 block mt-0.5">
                        Tablón Edictal Oficial · Sede Electrónica
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight mt-3">
                        {{ $publication->title }}
                    </h1>
                </div>

                <div class="text-right sm:text-right shrink-0 bg-gray-50 p-4 rounded-2xl border border-gray-200">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Código Oficial</div>
                    <div class="text-base font-black font-mono text-teal-700">{{ $publication->document_code }}</div>
                    <div class="text-[11px] text-gray-500 mt-1">Fecha: <strong>{{ $publication->publication_date->format('d/m/Y') }}</strong></div>
                    @if($publication->expiration_date)
                        <div class="text-[10px] text-gray-400 mt-0.5">Vence: {{ $publication->expiration_date->format('d/m/Y') }}</div>
                    @endif
                </div>
            </div>

            <!-- Summary / Subtitle -->
            @if($publication->summary)
                <div class="mb-8 p-4 bg-teal-50/50 rounded-2xl border border-teal-100 text-sm text-teal-900 font-medium">
                    {{ $publication->summary }}
                </div>
            @endif

            <!-- Document Body Content -->
            <div class="prose max-w-none text-gray-800 leading-relaxed text-sm sm:text-base border-b border-gray-100 pb-8 mb-8">
                @if($publication->content_html)
                    {!! $publication->content_html !!}
                @else
                    <p class="italic text-gray-400">El cuerpo íntegro del documento se encuentra autenticado en el expediente de referencia.</p>
                @endif
            </div>

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
                    Este documento ha superado el circuito preceptivo de tramitación interna conforme a la siguiente trazabilidad:
                </p>

                <!-- Signatures / Audit Trail Table -->
                @php
                    $signatures = $publication->metadata['signatures_audit'] ?? [];
                @endphp

                @if(!empty($signatures))
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-100 text-gray-600 font-bold uppercase text-[10px] tracking-wider">
                                <tr>
                                    <th class="py-2 px-3">Etapa</th>
                                    <th class="py-2 px-3">Acción</th>
                                    <th class="py-2 px-3">Interviniente / Cargo</th>
                                    <th class="py-2 px-3">Fecha y Hora</th>
                                    <th class="py-2 px-3">Resultado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($signatures as $sig)
                                    <tr>
                                        <td class="py-2.5 px-3 font-bold">{{ $sig['step_name'] ?? 'Etapa ' . ($sig['step_order'] ?? '') }}</td>
                                        <td class="py-2.5 px-3 font-mono capitalize">{{ $sig['action_type'] ?? 'Validación' }}</td>
                                        <td class="py-2.5 px-3 font-semibold">{{ $sig['signer_name'] ?? 'Responsable' }}</td>
                                        <td class="py-2.5 px-3 text-gray-500">
                                            {{ !empty($sig['completed_at']) ? \Carbon\Carbon::parse($sig['completed_at'])->format('d/m/Y H:i:s') : '—' }}
                                        </td>
                                        <td class="py-2.5 px-3">
                                            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700">
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
                <div class="mt-4 pt-4 border-t border-gray-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 text-[11px] text-gray-500 font-mono">
                    <div>
                        Código Seguro de Verificación (CSV): <strong class="text-gray-900 select-all">{{ $publication->public_token }}</strong>
                    </div>
                    <div>
                        Sede de cotejo: {{ url('/tablon/doc/' . $publication->public_slug) }}
                    </div>
                </div>
            </div>

        </div>

    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-6 text-center text-xs text-gray-400 no-print">
        <div class="max-w-5xl mx-auto px-4">
            <p>{{ $publication->team->name }} · Tablón Edictal Oficial</p>
        </div>
    </footer>
</body>
</html>
