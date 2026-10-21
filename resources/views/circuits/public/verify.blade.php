<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sede Electrónica — Verificación y Cotejo de Documentos | sientia.MTX</title>
    <meta name="description" content="Servicio de verificación de autenticidad e integridad de documentos electrónicos mediante Código Seguro de Verificación (CSV) según Ley 39/2015.">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 font-sans antialiased min-h-screen flex flex-col">

    <!-- Header Institucional Sede Electrónica -->
    <header class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 shadow-2xs">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-600 to-indigo-700 flex items-center justify-center text-white shadow-md shadow-violet-500/20 font-black text-lg">
                    S
                </div>
                <div>
                    <div class="text-sm font-black tracking-tight text-gray-900 dark:text-white flex items-center gap-1.5">
                        <span>sientia<span class="text-violet-600">.</span>MTX</span>
                        <span class="text-[10px] uppercase font-bold tracking-widest bg-violet-100 dark:bg-violet-900/60 text-violet-700 dark:text-violet-300 px-2 py-0.5 rounded-full">Sede Electrónica</span>
                    </div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 font-medium">Servicio Público de Cotejo y Verificación de Documentos</p>
                </div>
            </div>

            <div class="hidden sm:flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span>Conforme a Ley 39/2015 y Reglamento eIDAS</span>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">

        <!-- Buscador de CSV -->
        <div class="bg-white dark:bg-gray-900 rounded-3xl p-6 sm:p-8 shadow-xs border border-gray-200 dark:border-gray-800 mb-8">
            <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight mb-2">
                Cotejo y Verificación de Documentos Oficiales
            </h1>
            <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mb-6 leading-relaxed">
                Introduce el <strong>Código Seguro de Verificación (CSV)</strong> que figura al pie o en los cajetines de firma de tu documento oficial, o la huella digital SHA-256 para comprobar su autenticidad e integridad.
            </p>

            <form action="{{ route('public.circuits.verify') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <input type="text" name="csv" value="{{ $csv }}" placeholder="Ejemplo: CSV-TRM-PEKW-000006-TASW" required
                        class="w-full pl-11 pr-4 py-3 text-sm font-mono font-bold rounded-2xl border-gray-300 dark:border-gray-700 dark:bg-gray-800/80 text-gray-900 dark:text-white placeholder-gray-400 focus:border-violet-500 focus:ring-violet-500 transition-colors uppercase">
                </div>
                <button type="submit"
                    class="px-6 py-3 bg-violet-600 hover:bg-violet-700 text-white font-black text-xs uppercase tracking-wider rounded-2xl shadow-md shadow-violet-600/20 transition-all active:scale-95 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <span>Verificar Documento</span>
                </button>
            </form>
        </div>

        @if($searched)
            @if($circuit)
                <!-- Tarjeta de Éxito / Resultado Oficial -->
                <div class="bg-white dark:bg-gray-900 rounded-3xl p-6 sm:p-8 shadow-xs border border-emerald-300 dark:border-emerald-800/60 mb-8 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-300 mb-1">
                                    Documento Auténtico y Verificado
                                </span>
                                <h2 class="text-lg sm:text-xl font-black text-gray-900 dark:text-white">
                                    {{ $circuit->title }}
                                </h2>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 flex-wrap">
                            <a href="{{ route('public.circuits.verify.download', $circuit->metadata['csv'] ?? $csv) }}" target="_blank"
                                class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs uppercase tracking-wider rounded-xl shadow-md shadow-emerald-600/20 transition-all active:scale-95 shrink-0"
                                title="Descargar documento firmado original">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span>Documento Firmado</span>
                            </a>
                            <a href="{{ route('public.circuits.verify.download', ['csv' => $circuit->metadata['csv'] ?? $csv, 'type' => 'receipt']) }}" target="_blank"
                                class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white font-black text-xs uppercase tracking-wider rounded-xl shadow-md shadow-violet-600/20 transition-all active:scale-95 shrink-0"
                                title="Descargar recibo oficial de cotejo y diligencia con CSV">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                <span>Recibo Oficial (CSV)</span>
                            </a>
                        </div>
                    </div>

                    <!-- Cuadrícula de Metadatos -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 py-6 border-b border-gray-100 dark:border-gray-800 text-xs">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400 font-medium block mb-0.5">Código Seguro (CSV):</span>
                            <span class="font-mono font-bold text-violet-700 dark:text-violet-400 break-all">{{ $circuit->metadata['csv'] ?? $csv }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400 font-medium block mb-0.5">Expediente de Circuito:</span>
                            <span class="font-bold text-gray-900 dark:text-white">#{{ $circuit->id }} — {{ $circuit->team->name }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400 font-medium block mb-0.5">Fecha de Inicio:</span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ $circuit->started_at?->format('d/m/Y H:i') ?? $circuit->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400 font-medium block mb-0.5">{{ __('circuits.official_status') }}:</span>
                            <span class="font-black uppercase {{ in_array($circuit->status, ['approved', 'published']) ? 'text-emerald-600 dark:text-emerald-400' : 'text-blue-600 dark:text-blue-400' }}">
                                {{ $circuit->status_label }}
                            </span>
                        </div>
                    </div>

                    <!-- Fases y Diligencias de Firma Registradas -->
                    <div class="pt-6">
                        <h3 class="text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-4">
                            Diligencias de Firmas y Conformidades Reconocidas
                        </h3>

                        <div class="space-y-3">
                            @foreach($circuit->steps->sortBy('order') as $st)
                                @php
                                    $isStepHighlighted = ($highlightStep == $st->order);
                                    $stepHash = $st->signature_data['sha256_hash'] ?? '';
                                    $isHashHighlighted = ($highlightHash && strtolower($highlightHash) === strtolower($stepHash));
                                    $isSigned = (bool)$st->signed_at;
                                    $sigMethod = $st->signature_data['method'] ?? 'internal_certificate';
                                    $isAutoFirma = in_array(strtolower($sigMethod), ['autofirma', 'manual_upload']);
                                @endphp
                                <div class="rounded-2xl p-4 transition-all {{ ($isStepHighlighted || $isHashHighlighted) ? 'bg-emerald-50 dark:bg-emerald-950/40 border-2 border-emerald-500' : 'bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700/60' }}">
                                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 mb-2">
                                        <div class="flex items-center gap-2">
                                            <span class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-black {{ $isSigned ? 'bg-emerald-500 text-white' : 'bg-gray-300 dark:bg-gray-700 text-gray-600' }}">
                                                {{ $st->order }}
                                            </span>
                                            <span class="font-bold text-sm text-gray-900 dark:text-white">
                                                {{ $st->name }}
                                            </span>
                                            @if($isSigned)
                                                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full {{ $isAutoFirma ? 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300' : 'bg-teal-100 dark:bg-teal-900/50 text-teal-700 dark:text-teal-300' }}">
                                                    {{ $isAutoFirma ? 'Firma Electrónica PAdES' : 'Conformidad Acreditada (CSV)' }}
                                                </span>
                                            @else
                                                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                                    Pendiente
                                                </span>
                                            @endif
                                        </div>

                                        @if($isSigned)
                                            <div class="text-xs text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                <span>{{ $st->signed_at->format('d/m/Y \a \l\a\s H:i:s') }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    @if($isSigned)
                                        <div class="text-xs text-gray-600 dark:text-gray-300 grid grid-cols-1 md:grid-cols-2 gap-2 mt-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                                            <div>
                                                <span class="text-gray-400 font-medium">Interviniente:</span>
                                                <strong class="text-gray-900 dark:text-white">{{ $st->signature_data['signer_name'] ?? $st->actionUser?->name ?? 'Usuario Acreditado' }}</strong>
                                                <span class="text-gray-500">({{ $st->signature_data['signer_email'] ?? $st->actionUser?->email ?? '' }})</span>
                                            </div>
                                            <div>
                                                <span class="text-gray-400 font-medium">Algoritmo:</span>
                                                <span class="font-mono">{{ $st->signature_data['algorithm'] ?? 'SHA256withRSA' }}</span>
                                            </div>
                                            <div class="md:col-span-2 mt-1">
                                                <span class="text-gray-400 font-medium block mb-0.5">Huella Criptográfica SHA-256:</span>
                                                <code class="block p-2 rounded-lg bg-gray-100 dark:bg-gray-900 font-mono text-[11px] text-gray-800 dark:text-gray-200 break-all select-all border border-gray-200 dark:border-gray-800">
                                                    {{ $stepHash }}
                                                </code>
                                            </div>
                                            @if($st->comments)
                                                <div class="md:col-span-2 text-xs italic text-gray-500 dark:text-gray-400 mt-1">
                                                    "{{ $st->comments }}"
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <!-- No encontrado -->
                <div class="bg-white dark:bg-gray-900 rounded-3xl p-8 shadow-xs border border-rose-200 dark:border-rose-900/60 text-center max-w-2xl mx-auto">
                    <div class="w-12 h-12 rounded-2xl bg-rose-100 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <h2 class="text-lg font-black text-gray-900 dark:text-white mb-1">
                        Código de Verificación No Encontrado
                    </h2>
                    <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                        No se ha localizado ningún documento o trámite oficial con el identificador <strong class="font-mono text-rose-600">{{ $csv }}</strong>.
                        Verifique que ha introducido el código completo (incluyendo el prefijo <code>CSV-TRM-...</code>) respetando los guiones.
                    </p>
                </div>
            @endif
        @endif

    </main>

    <!-- Footer Institucional -->
    <footer class="bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800 py-6 text-center text-xs text-gray-500 dark:text-gray-400 mt-auto">
        <div class="max-w-5xl mx-auto px-4">
            <p class="font-medium">
                Sede Electrónica de Verificación — <strong>sientia.MTX</strong> © {{ date('Y') }}
            </p>
            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">
                La autenticidad e integridad de los documentos emitidos puede ser comprobada mediante este servicio público de conformidad con el artículo 27 de la Ley 39/2015 del Procedimiento Administrativo Común.
            </p>
        </div>
    </footer>

</body>
</html>
