<!DOCTYPE html>
<html lang="es" class="h-full bg-gray-50 dark:bg-gray-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asistente de Firma Digital — {{ $circuit->title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Cliente oficial AutoFirma (AutoScript) --}}
    <script src="{{ asset('js/autoscript.js') }}"></script>
</head>
<body class="h-full flex flex-col font-sans antialiased text-gray-900 dark:text-gray-100">

    <header class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 py-3.5 px-6 shrink-0 shadow-xs flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-violet-600 flex items-center justify-center text-white shadow-lg shadow-violet-500/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <div>
                <h1 class="text-lg font-black tracking-tight text-gray-900 dark:text-white flex items-center gap-2">
                    <span>Portal de Firma Sientia<span class="text-violet-500">.</span>MTX</span>
                    <span class="text-xs px-2 py-0.5 rounded-md bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300 font-mono font-bold">Circuito #{{ $circuit->id }}</span>
                </h1>
                <p class="text-[11px] font-bold uppercase tracking-widest text-gray-400">Entorno Oficial PAdES — AutoFirma / Certificado Digital</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('teams.circuits.show', [$team, $circuit]) }}"
               class="flex items-center gap-1.5 px-3.5 py-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-black uppercase tracking-wider rounded-xl transition-all active:scale-95 border border-gray-200 dark:border-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Volver al Trámite</span>
            </a>
            <div class="text-xs font-medium text-gray-500 bg-gray-100 dark:bg-gray-800 px-3.5 py-2 rounded-xl hidden sm:block">
                Firmante: <strong class="text-gray-900 dark:text-gray-100">{{ $signerUser->name }}</strong> ({{ $signerUser->email }})
            </div>
        </div>
    </header>

    <main id="portal-split-container" class="flex-1 overflow-hidden flex flex-col md:flex-row relative">
        {{-- Lado Izquierdo: Previsualización del Documento PDF Oficial (Redimensionable) --}}
        <div id="pdf-split-panel" class="w-full md:w-[65%] bg-gray-100 dark:bg-gray-800 border-r border-gray-200 dark:border-gray-800 p-3 sm:p-4 flex flex-col min-w-[280px]">
            <div class="flex items-center justify-between pb-2 mb-2 text-xs font-bold text-gray-600 dark:text-gray-300">
                <span class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Diligencia Oficial en Tramitación
                </span>
                <div class="flex items-center gap-2">
                    {{-- Botones de ajuste de ancho rápido --}}
                    <div class="hidden sm:flex items-center bg-gray-200 dark:bg-gray-700/60 rounded-lg p-0.5 text-[10px] font-bold text-gray-600 dark:text-gray-300">
                        <button type="button" onclick="setSplitRatio(50)" class="px-2 py-0.5 rounded hover:bg-white dark:hover:bg-gray-600 transition-colors" title="50% visor / 50% controles">50%</button>
                        <button type="button" onclick="setSplitRatio(65)" class="px-2 py-0.5 rounded hover:bg-white dark:hover:bg-gray-600 transition-colors active font-black text-violet-600 dark:text-violet-400" title="65% visor / 35% controles">65%</button>
                        <button type="button" onclick="setSplitRatio(80)" class="px-2 py-0.5 rounded hover:bg-white dark:hover:bg-gray-600 transition-colors" title="80% visor / 20% controles">80%</button>
                    </div>
                    <a href="{{ route('teams.circuits.document', [$team, $circuit, 'download' => 1, 'for_step' => $step->id]) }}" download class="text-violet-600 dark:text-violet-400 hover:underline text-[11px] font-semibold flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>Descargar PDF</span>
                    </a>
                </div>
            </div>
            <div class="w-full flex-1 bg-white dark:bg-gray-900 rounded-2xl shadow-inner overflow-hidden border border-gray-300 dark:border-gray-700 relative">
                <iframe id="pdf-viewer-iframe" src="{{ route('teams.circuits.document', [$team, $circuit, 'for_step' => $step->id]) }}" class="w-full h-full border-0" title="Diligencia de tramitación"></iframe>
            </div>
        </div>

        {{-- Divisor Redimensionable (Resizer Handle) --}}
        <div id="split-resizer"
             class="hidden md:flex items-center justify-center w-2.5 hover:w-3 bg-gray-200 hover:bg-violet-500 dark:bg-gray-800 dark:hover:bg-violet-600 cursor-col-resize select-none transition-all group z-30 shrink-0"
             title="Arrastra para redimensionar el visor">
            <div class="h-8 w-1 rounded-full bg-gray-400 dark:bg-gray-600 group-hover:bg-white transition-colors"></div>
        </div>

        {{-- Lado Derecho: Controles del Asistente de Firma --}}
        <div id="controls-split-panel" class="flex-1 bg-white dark:bg-gray-900 p-6 sm:p-8 flex flex-col shadow-xl z-10 min-w-[340px] overflow-y-auto">
            <div class="mb-4">
                <span class="px-2.5 py-1 rounded-md text-[10px] font-black uppercase tracking-widest bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300">
                    Paso {{ $step->order }}: {{ $step->action_type_label }}
                </span>
                <h2 class="text-xl font-black text-gray-900 dark:text-white mt-2 leading-tight">
                    {{ $step->name }}
                </h2>
                @if($step->description)
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">
                        {{ $step->description }}
                    </p>
                @endif
            </div>

            {{-- Requisitos AutoFirma --}}
            <div class="bg-violet-50 dark:bg-violet-900/10 border border-violet-100 dark:border-violet-800/40 rounded-2xl p-4 mb-4">
                <h3 class="text-xs font-black uppercase tracking-widest text-violet-700 dark:text-violet-300 mb-2 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Requisitos para la Firma Digital
                </h3>
                <ul class="text-xs text-violet-900 dark:text-violet-200 space-y-1.5">
                    <li class="flex items-start gap-2">
                        <svg class="h-4 w-4 shrink-0 text-violet-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Aplicación <strong>AutoFirma</strong> abierta o instalada en tu equipo.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="h-4 w-4 shrink-0 text-violet-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span><strong>Certificado digital</strong> válido (FNMT, DNIe, etc.).</span>
                    </li>
                </ul>
                <a href="https://firmaelectronica.gob.es/Home/Descargas.html" target="_blank"
                   class="mt-3 inline-flex items-center gap-1 text-[11px] font-bold text-violet-600 dark:text-violet-400 hover:underline">
                    <span>Descargar AutoFirma oficial</span>
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
            </div>

            {{-- Observaciones o Justificación --}}
            <div class="mb-4">
                <label for="step-comments" class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">
                    Observaciones o Dictamen del Visto Bueno (Opcional)
                </label>
                <textarea id="step-comments" rows="2" placeholder="Indica alguna observación o diligencia adicional..."
                    class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-violet-500 focus:border-violet-500"></textarea>
            </div>

            {{-- Alternativa: subida manual --}}
            <div id="manual-upload-section" class="hidden bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800/50 rounded-2xl p-4 mb-4">
                <h3 class="text-xs font-black uppercase tracking-widest text-amber-700 dark:text-amber-400 mb-1.5">Firma Manual Externa</h3>
                <p class="text-xs text-amber-800 dark:text-amber-300 mb-3 leading-relaxed">
                    Si no dispones de AutoFirma en este ordenador, puedes descargar el PDF, firmarlo con tu aplicación externa habitual y subir el archivo firmado.
                </p>
                <a href="{{ route('teams.circuits.document', [$team, $circuit, 'download' => 1, 'for_step' => $step->id]) }}"
                   class="block text-center text-xs font-bold text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700 rounded-xl py-2 mb-3 hover:bg-amber-100 dark:hover:bg-amber-900/30 transition-colors" download>
                    ⬇ Descargar PDF para firmar
                </a>
                <label class="block text-xs font-bold text-amber-800 dark:text-amber-300 mb-1">Subir documento PDF firmado:</label>
                <input type="file" id="manual-file-input" accept=".pdf,application/pdf"
                       class="block w-full text-xs text-gray-600 dark:text-gray-300 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-amber-200 dark:file:bg-amber-800 file:text-amber-900 dark:file:text-amber-100 hover:file:bg-amber-300 cursor-pointer">
                <button type="button" id="btn-upload-manual"
                        class="mt-3 w-full py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl text-xs transition-colors shadow-sm">
                    Enviar documento firmado y aprobar paso
                </button>
            </div>

            {{-- Botones de Acción --}}
            <div class="mt-auto space-y-3 pt-2">
                {{-- Botón principal: Firmar con AutoFirma --}}
                <button type="button" id="btn-sign-autofirma"
                        class="w-full py-3.5 bg-violet-600 hover:bg-violet-700 text-white font-black rounded-2xl shadow-xl shadow-violet-600/20 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-2.5 text-base group">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                    <span>Firmar con AutoFirma</span>
                </button>

                {{-- Botón alternativo: Firma Interna Acreditada --}}
                <form id="form-internal-sign" action="{{ route('teams.circuits.steps.process', [$team, $circuit, $step]) }}" method="POST">
                    @csrf
                    <input type="hidden" name="decision" value="approved">
                    <input type="hidden" name="sign_now" value="1">
                    <input type="hidden" name="signature_method" value="internal_certificate">
                    <input type="hidden" name="comments" id="internal-comments-input" value="">

                    <button type="button" id="btn-internal-sign"
                            class="w-full py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 font-bold rounded-xl text-xs transition-colors flex items-center justify-center gap-1.5 border border-gray-200 dark:border-gray-700"
                            title="Aplica una conformidad administrativa acreditada mediante registro del sistema con CSV">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <span>Conformidad Acreditada (CSV)</span>
                    </button>
                </form>

                {{-- Toggle manual upload --}}
                <button type="button" id="btn-toggle-manual"
                        class="w-full py-1 text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 underline transition-colors text-center">
                    ¿Problemas con AutoFirma? Subir PDF firmado manualmente
                </button>
            </div>
        </div>
    </main>

    <script>
        const DOWNLOAD_URL  = '{{ route('teams.circuits.document', [$team, $circuit, 'for_step' => $step->id]) }}';
        const PROCESS_URL   = '{{ route('teams.circuits.steps.process', [$team, $circuit, $step]) }}';
        const CSRF_TOKEN    = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const STEP_POSITION = {{ $stepIndex ?? max(0, $step->order - 1) }};
        const EXISTING_SIGNATURES = {{ $existingSignaturesCount }};

        // ─── Redimensionamiento de Paneles (Split Resizer) ──────────────────
        (function() {
            const container = document.getElementById('portal-split-container');
            const pdfPanel = document.getElementById('pdf-split-panel');
            const resizer = document.getElementById('split-resizer');
            const iframe = document.getElementById('pdf-viewer-iframe');

            if (!container || !pdfPanel || !resizer) return;

            // Cargar proporción guardada (por defecto 65% para lectura óptima del documento)
            const savedRatio = localStorage.getItem('sientia_circuit_split_ratio');
            const initialRatio = savedRatio ? parseFloat(savedRatio) : 65;
            if (window.innerWidth >= 768 && initialRatio >= 25 && initialRatio <= 85) {
                pdfPanel.style.width = initialRatio + '%';
            }

            window.setSplitRatio = function(ratio) {
                if (window.innerWidth < 768) return;
                pdfPanel.style.width = ratio + '%';
                localStorage.setItem('sientia_circuit_split_ratio', ratio);
            };

            let isDragging = false;

            function onMouseDown(e) {
                if (window.innerWidth < 768) return;
                isDragging = true;
                document.body.style.cursor = 'col-resize';
                document.body.style.userSelect = 'none';
                if (iframe) iframe.style.pointerEvents = 'none'; // Evita interferencia del iframe con el cursor
            }

            function onMouseMove(e) {
                if (!isDragging) return;
                const clientX = e.clientX !== undefined ? e.clientX : (e.touches && e.touches[0].clientX);
                if (clientX === undefined) return;

                const rect = container.getBoundingClientRect();
                const totalWidth = rect.width;
                const offsetX = clientX - rect.left;

                // Restricciones: Mínimo 280px para visor, mínimo 340px para controles de firma
                const minPdfPx = Math.max(280, totalWidth * 0.25);
                const maxPdfPx = Math.min(totalWidth - 340, totalWidth * 0.85);

                const clampedPx = Math.max(minPdfPx, Math.min(maxPdfPx, offsetX));
                const ratio = (clampedPx / totalWidth) * 100;

                pdfPanel.style.width = ratio.toFixed(2) + '%';
                localStorage.setItem('sientia_circuit_split_ratio', ratio.toFixed(2));
            }

            function onMouseUp() {
                if (!isDragging) return;
                isDragging = false;
                document.body.style.cursor = '';
                document.body.style.userSelect = '';
                if (iframe) iframe.style.pointerEvents = 'auto';
            }

            resizer.addEventListener('mousedown', onMouseDown);
            window.addEventListener('mousemove', onMouseMove);
            window.addEventListener('mouseup', onMouseUp);

            resizer.addEventListener('touchstart', onMouseDown, { passive: true });
            window.addEventListener('touchmove', onMouseMove, { passive: true });
            window.addEventListener('touchend', onMouseUp);
        })();

        // Inicialización de AutoFirma / AutoScript
        window.addEventListener('DOMContentLoaded', () => {
            if (typeof AutoScript !== 'undefined') {
                try {
                    AutoScript.cargarAppAfirma();
                } catch (e) {
                    console.error("Error al inicializar AutoScript:", e);
                }
            }
        });

        // Toggle subida manual
        document.getElementById('btn-toggle-manual').addEventListener('click', function () {
            const section = document.getElementById('manual-upload-section');
            section.classList.toggle('hidden');
            this.textContent = section.classList.contains('hidden')
                ? '¿Problemas con AutoFirma? Subir PDF firmado manualmente'
                : 'Ocultar firma manual';
        });

        // Conformidad Acreditada con CSV
        document.getElementById('btn-internal-sign').addEventListener('click', function() {
            const comments = document.getElementById('step-comments').value;
            document.getElementById('internal-comments-input').value = comments;

            Swal.fire({
                title: '¿Registrar Conformidad Acreditada (CSV)?',
                text: 'Se estampará tu conformidad oficial con Código Seguro de Verificación (CSV), huella SHA-256 completa y registro de auditoría.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, registrar conformidad',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#7c3aed',
            }).then(result => {
                if (result.isConfirmed) {
                    document.getElementById('form-internal-sign').submit();
                }
            });
        });

        // Botón principal: Firmar con AutoFirma
        document.getElementById('btn-sign-autofirma').addEventListener('click', async function () {
            const comments = document.getElementById('step-comments').value;

            Swal.fire({
                title: 'Preparando documento...',
                text: 'Descargando el PDF para estampar la firma.',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            let pdfBase64;
            try {
                const resp = await fetch(DOWNLOAD_URL);
                if (!resp.ok) throw new Error('No se pudo obtener el documento del circuito.');
                const blob = await resp.blob();
                pdfBase64 = await new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onloadend = () => resolve(reader.result.split(',')[1]);
                    reader.onerror = reject;
                    reader.readAsDataURL(blob);
                });
            } catch (err) {
                Swal.fire('Error', 'No se pudo descargar el documento: ' + err.message, 'error');
                return;
            }

            Swal.fire({
                title: 'Abriendo AutoFirma...',
                html: 'Acepta el cuadro de diálogo del navegador y<br>selecciona tu certificado digital en AutoFirma.',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            if (typeof AutoScript === 'undefined') {
                Swal.fire('Error de configuración', 'No se ha podido cargar el cliente de AutoFirma. Recarga la página.', 'error');
                return;
            }

            // Calculamos la posición en formato grid (2 columnas) en la página dedicada de firmas
            const rowIndex = Math.floor(STEP_POSITION / 2);
            const colIndex = STEP_POSITION % 2;

            // Coordenadas X: Columna izquierda y Columna derecha ajustadas a los márgenes del PDF
            const startX = colIndex === 0 ? 67 : 305;
            const endX = startX + 165; // Deja margen para el código QR a la derecha del cajetín

            // Coordenadas Y: Empezamos en la altura del cajetín correspondiente a la etapa en la página dedicada
            const yEnd = 640 - (rowIndex * 134);
            const yStart = yEnd - 50;

            const extraParams = "signatureFormat=PADES-BES\n" +
                                "signaturePage=-1\n" +
                                "signaturePositionOnPageLowerLeftX=" + startX + "\n" +
                                "signaturePositionOnPageLowerLeftY=" + yStart + "\n" +
                                "signaturePositionOnPageUpperRightX=" + endX + "\n" +
                                "signaturePositionOnPageUpperRightY=" + yEnd + "\n" +
                                "layer2FontFamily=1\n" +
                                "layer2FontSize=6\n" +
                                "layer2Text=DILIGENCIA DE FIRMA ELECTRÓNICA\\n" +
                                "-----------------------------------------------------------\\n" +
                                "FIRMADO POR: $$SUBJECTCN$$\\n" +
                                "FECHA: $$SIGNDATE=dd/MM/yyyy$$ a las $$SIGNDATE=HH:mm$$\\n" +
                                "-----------------------------------------------------------\\n" +
                                "Sello de validez PAdES oficial verificado en Sientia.MTX\n";

            AutoScript.sign(
                pdfBase64,
                'SHA256withRSA',
                'PAdES',
                extraParams,
                function (signatureB64) {
                    uploadSignedDocument(signatureB64, comments, 'autofirma');
                },
                function (errorType, errorMsg) {
                    console.error('AutoFirma error:', errorType, errorMsg);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error en AutoFirma',
                        html: errorMsg || 'No se pudo completar la firma con AutoFirma.',
                        footer: '<a href="https://firmaelectronica.gob.es/Home/Descargas.html" target="_blank">Comprobar instalación de AutoFirma</a>',
                    });
                }
            );
        });

        // Subida manual
        document.getElementById('btn-upload-manual').addEventListener('click', function () {
            const fileInput = document.getElementById('manual-file-input');
            const comments = document.getElementById('step-comments').value;

            if (!fileInput.files || !fileInput.files[0]) {
                Swal.fire('Aviso', 'Selecciona un archivo PDF firmado antes de continuar.', 'warning');
                return;
            }
            const file = fileInput.files[0];
            if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
                Swal.fire('Aviso', 'El archivo seleccionado debe ser un PDF.', 'warning');
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                const b64 = e.target.result.split(',')[1];
                uploadSignedDocument(b64, comments, 'manual_upload');
            };
            reader.readAsDataURL(file);
        });

        // Función de subida y procesamiento del paso firmado
        async function uploadSignedDocument(base64Data, comments, method) {
            Swal.fire({
                title: 'Registrando firma...',
                text: 'Subiendo la diligencia firmada y concluyendo la etapa.',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const blob = base64ToBlob(base64Data, 'application/pdf');
                const formData = new FormData();
                formData.append('decision', 'approved');
                formData.append('sign_now', '1');
                formData.append('signature_method', method);
                formData.append('comments', comments || '');
                formData.append('signed_file', blob, 'Diligencia_Firmada.pdf');

                const response = await fetch(PROCESS_URL, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                if (!response.ok) {
                    const text = await response.text();
                    console.error('HTTP error', response.status, text);
                    Swal.fire('Error ' + response.status, 'El servidor rechazó la solicitud.', 'error');
                    return;
                }

                const data = await response.json();
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Firma Registrada!',
                        text: data.message || 'El paso ha sido firmado y aprobado correctamente.',
                        timer: 2000,
                        showConfirmButton: false,
                    }).then(() => {
                        window.location.href = data.redirect;
                    });
                } else {
                    Swal.fire('Error', data.error || 'Error al guardar el documento.', 'error');
                }
            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'Ocurrió un error inesperado: ' + error.message, 'error');
            }
        }

        function base64ToBlob(b64, mimeType) {
            const byteChars   = atob(b64);
            const byteNumbers = new Uint8Array(byteChars.length);
            for (let i = 0; i < byteChars.length; i++) {
                byteNumbers[i] = byteChars.charCodeAt(i);
            }
            return new Blob([byteNumbers], { type: mimeType });
        }
    </script>
</body>
</html>

