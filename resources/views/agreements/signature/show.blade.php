<!DOCTYPE html>
<html lang="es" class="h-full bg-gray-50 dark:bg-gray-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firmar Acuerdo - {{ $activity->title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Cliente oficial AutoFirma (AutoScript) --}}
    <script src="{{ asset('js/autoscript.js') }}"></script>
</head>
<body class="h-full flex flex-col font-sans antialiased text-gray-900 dark:text-gray-100">

    <header class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 py-4 px-6 shrink-0 shadow-sm flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-violet-600 flex items-center justify-center text-white shadow-lg shadow-violet-500/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-black tracking-tight text-gray-900 dark:text-white">Portal de Firma Sientia<span class="text-violet-500">.</span>MTX</h1>
                <p class="text-[11px] font-bold uppercase tracking-widest text-gray-400">Entorno Seguro PAdES — AutoFirma</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            @if(!empty($isInternal))
                <a href="{{ route('teams.activities.show', [$team->id, $activity->id]) }}" 
                   class="mr-2 flex items-center gap-1.5 px-3 py-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-black uppercase tracking-wider rounded-xl transition-all active:scale-95 border border-gray-200 dark:border-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Volver
                </a>
            @endif
            <div class="text-sm font-medium text-gray-500 bg-gray-100 dark:bg-gray-800 px-4 py-2 rounded-xl">
                Firmante: <span class="font-bold text-gray-900 dark:text-gray-100">{{ $signerEmail }}</span>
            </div>
        </div>
    </header>

    <main class="flex-1 overflow-hidden flex">
        {{-- Lado Izquierdo: Previsualización del PDF --}}
        <div class="flex-1 bg-gray-200 dark:bg-gray-800 border-r border-gray-200 dark:border-gray-800 p-4">
            <div class="w-full h-full bg-white dark:bg-gray-900 rounded-2xl shadow-inner overflow-hidden border border-gray-300 dark:border-gray-700">
                @if($attachment)
                    <iframe src="{{ URL::signedRoute('agreements.signature.download', ['team' => $team->id, 'activity' => $activity->id]) }}" class="w-full h-full" title="Vista previa del documento"></iframe>
                @else
                    <div class="flex items-center justify-center h-full text-gray-500">
                        No se pudo generar o encontrar el documento PDF.
                    </div>
                @endif
            </div>
        </div>

        {{-- Lado Derecho: Controles de Firma --}}
        <div class="w-96 bg-white dark:bg-gray-900 p-8 flex flex-col shadow-xl z-10 shrink-0 overflow-y-auto">
            <h2 class="text-2xl font-black text-gray-900 dark:text-white mb-2">Acuerdo de Adhesión</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6 leading-relaxed">
                Revise el documento a la izquierda. Una vez conforme, pulse el botón para firmar digitalmente con su certificado.
            </p>

            {{-- Requisitos --}}
            <div class="bg-violet-50 dark:bg-violet-900/10 border border-violet-100 dark:border-violet-800/50 rounded-2xl p-5 mb-6">
                <h3 class="text-xs font-black uppercase tracking-widest text-violet-600 dark:text-violet-400 mb-2">Requisitos</h3>
                <ul class="text-sm text-violet-800 dark:text-violet-300 space-y-2">
                    <li class="flex items-start gap-2">
                        <svg class="h-5 w-5 shrink-0 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Aplicación <strong>AutoFirma</strong> instalada en su equipo.
                    </li>
                    <li class="flex items-start gap-2">
                        <svg class="h-5 w-5 shrink-0 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <strong>Certificado Digital</strong> válido (FNMT, DNIe, etc.).
                    </li>
                </ul>
                <a href="https://firmaelectronica.gob.es/Home/Descargas.html" target="_blank"
                   class="mt-3 flex items-center gap-1 text-xs text-violet-500 hover:text-violet-700 underline">
                    Descargar AutoFirma
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
            </div>

            {{-- Alternativa: subida manual --}}
            <div id="manual-upload-section" class="hidden bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800/50 rounded-2xl p-5 mb-6">
                <h3 class="text-xs font-black uppercase tracking-widest text-amber-600 dark:text-amber-400 mb-2">Firma Manual</h3>
                <p class="text-xs text-amber-700 dark:text-amber-300 mb-3 leading-relaxed">
                    Si AutoFirma no está disponible, puede descargar el PDF, firmarlo externamente y subirlo aquí.
                </p>
                <a href="{{ URL::signedRoute('agreements.signature.download', ['team' => $team->id, 'activity' => $activity->id]) }}"
                   class="block text-center text-xs font-bold text-amber-700 border border-amber-300 rounded-xl py-2 mb-3 hover:bg-amber-100 transition-colors" download>
                    ⬇ Descargar PDF para firmar
                </a>
                <label class="block text-xs font-bold text-amber-700 mb-1">Subir PDF firmado:</label>
                <input type="file" id="manual-file-input" accept="application/pdf"
                       class="block w-full text-xs text-gray-600 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-amber-100 file:text-amber-700 hover:file:bg-amber-200 cursor-pointer">
                <button type="button" id="btn-upload-manual"
                        class="mt-3 w-full py-2 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-xl text-sm transition-colors">
                    Enviar documento firmado
                </button>
            </div>

            <div class="mt-auto space-y-3">
                {{-- Botón principal AutoFirma --}}
                <button type="button" id="btn-sign"
                        class="w-full py-4 bg-gray-900 hover:bg-gray-800 dark:bg-white dark:hover:bg-gray-100 dark:text-gray-900 text-white font-black rounded-2xl shadow-xl shadow-gray-900/20 transition-all flex items-center justify-center gap-3 text-lg group">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                    Firmar con AutoFirma
                </button>

                {{-- Toggle manual upload --}}
                <button type="button" id="btn-toggle-manual"
                        class="w-full py-2 text-sm text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 underline transition-colors">
                    ¿Problemas con AutoFirma? Subir PDF manualmente
                </button>
            </div>
        </div>
    </main>

    <script>
        // ─── URLs y configuración ───────────────────────────────────────────────
        const DOWNLOAD_URL  = '{{ URL::signedRoute('agreements.signature.download', ['team' => $team->id, 'activity' => $activity->id]) }}';
        const PROCESS_URL   = '{{ route('agreements.signature.process', [$team, $activity]) }}';
        const SIGNER_EMAIL  = '{{ $signerEmail }}';
        const CSRF_TOKEN    = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        @php
            $meta = $activity->metadata ?? [];
            $existingSignaturesCount = 0;
            foreach ($meta['member_signatures'] ?? [] as $sig) {
                if (!empty($sig['signed_at'])) $existingSignaturesCount++;
            }
            foreach ($meta['guests'] ?? [] as $guest) {
                if (!empty($guest['signed_at'])) $existingSignaturesCount++;
            }
        @endphp
        const EXISTING_SIGNATURES = {{ $existingSignaturesCount }};

        // ─── Inicialización de AutoFirma ─────────────────────────────────────────
        window.addEventListener('DOMContentLoaded', () => {
            if (typeof AutoScript !== 'undefined') {
                try {
                    AutoScript.cargarAppAfirma();
                } catch (e) {
                    console.error("Error al inicializar AutoScript:", e);
                }
            }
        });

        // ─── Toggle firma manual ────────────────────────────────────────────────
        document.getElementById('btn-toggle-manual').addEventListener('click', function () {
            const section = document.getElementById('manual-upload-section');
            section.classList.toggle('hidden');
            this.textContent = section.classList.contains('hidden')
                ? '¿Problemas con AutoFirma? Subir PDF manualmente'
                : 'Ocultar firma manual';
        });

        // ─── Botón principal: AutoFirma ─────────────────────────────────────────
        document.getElementById('btn-sign').addEventListener('click', async function () {
            Swal.fire({
                title: 'Descargando documento...',
                text: 'Preparando el PDF para la firma.',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            let pdfBase64;
            try {
                const resp = await fetch(DOWNLOAD_URL);
                if (!resp.ok) throw new Error('No se pudo descargar el documento.');
                const blob = await resp.blob();
                pdfBase64 = await new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onloadend = () => resolve(reader.result.split(',')[1]);
                    reader.onerror = reject;
                    reader.readAsDataURL(blob);
                });
            } catch (err) {
                Swal.fire('Error', 'No se pudo obtener el documento: ' + err.message, 'error');
                return;
            }

            Swal.fire({
                title: 'Abriendo AutoFirma...',
                html: 'Acepte el cuadro de diálogo del navegador y<br>seleccione su certificado en AutoFirma.',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            // 2. Invocar AutoFirma vía AutoScript
            if (typeof AutoScript === 'undefined') {
                Swal.fire('Error de configuración', 'No se ha podido cargar el cliente de AutoFirma. Recargue la página.', 'error');
                return;
            }

            // Calculamos la posición en formato grid (2 columnas) en la nueva página
            const rowIndex = Math.floor(EXISTING_SIGNATURES / 2);
            const colIndex = EXISTING_SIGNATURES % 2;

            // Coordenadas X: Columna izquierda (50 a 280), Columna derecha (310 a 540)
            const startX = colIndex === 0 ? 50 : 310;
            const endX = startX + 230;

            // Coordenadas Y: Empezamos en la parte superior (700) y bajamos 90 puntos por fila
            const yEnd = 700 - (rowIndex * 95);
            const yStart = yEnd - 80;

            const extraParams = "signatureFormat=PADES-BES\n" +
                                "signaturePage=-1\n" +
                                "signaturePositionOnPageLowerLeftX=" + startX + "\n" +
                                "signaturePositionOnPageLowerLeftY=" + yStart + "\n" +
                                "signaturePositionOnPageUpperRightX=" + endX + "\n" +
                                "signaturePositionOnPageUpperRightY=" + yEnd + "\n" +
                                "layer2FontFamily=1\n" +
                                "layer2FontSize=8\n" +
                                "layer2Text=FIRMA ELECTRÓNICA RECONOCIDA\\n" +
                                "-----------------------------------------------------------\\n" +
                                "FIRMADO POR: $$SUBJECTCN$$\\n" +
                                "FECHA: $$SIGNDATE=dd/MM/yyyy$$ a las $$SIGNDATE=HH:mm$$\\n" +
                                "-----------------------------------------------------------\\n" +
                                "Sello de validez PAdES verificado.\n";

            AutoScript.sign(
                pdfBase64,                  // Documento en Base64
                'SHA256withRSA',            // Algoritmo
                'PAdES',                    // Formato de firma
                extraParams,                // Parámetros PAdES
                function (signatureB64) {
                    // ✔ Firma completada: subir al servidor
                    uploadSignedDocument(signatureB64);
                },
                function (errorType, errorMsg) {
                    console.error('AutoFirma error:', errorType, errorMsg);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error en la firma',
                        html: errorMsg || 'AutoFirma no pudo completar la firma.',
                        footer: '<a href="https://firmaelectronica.gob.es/Home/Descargas.html" target="_blank">Descargar AutoFirma</a>',
                    });
                }
            );
        });

        // ─── Subida manual ──────────────────────────────────────────────────────
        document.getElementById('btn-upload-manual').addEventListener('click', function () {
            const fileInput = document.getElementById('manual-file-input');
            if (!fileInput.files || !fileInput.files[0]) {
                Swal.fire('Aviso', 'Seleccione un archivo PDF firmado antes de continuar.', 'warning');
                return;
            }
            const file = fileInput.files[0];
            if (file.type !== 'application/pdf') {
                Swal.fire('Aviso', 'El archivo debe ser un PDF.', 'warning');
                return;
            }
            // Leer como base64 y reutilizar el flujo de subida
            const reader = new FileReader();
            reader.onload = function (e) {
                // e.target.result = "data:application/pdf;base64,XXXX..."
                const b64 = e.target.result.split(',')[1];
                uploadSignedDocument(b64);
            };
            reader.readAsDataURL(file);
        });

        // ─── Función compartida de subida ───────────────────────────────────────
        async function uploadSignedDocument(base64Data) {
            Swal.fire({
                title: 'Procesando firma...',
                text: 'Subiendo el documento firmado al servidor.',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const blob     = base64ToBlob(base64Data, 'application/pdf');
                const formData = new FormData();
                formData.append('signed_file', blob, 'documento_firmado.pdf');
                formData.append('signer_email', SIGNER_EMAIL);

                const response = await fetch(PROCESS_URL, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
                    body: formData
                });

                if (!response.ok) {
                    const text = await response.text();
                    console.error('HTTP error', response.status, text);
                    Swal.fire('Error ' + response.status, 'El servidor rechazó la solicitud. El enlace puede haber expirado.', 'error');
                    return;
                }

                const data = await response.json();
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    Swal.fire('Error', data.message || 'Error al guardar el documento.', 'error');
                }
            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'Ocurrió un error inesperado: ' + error.message, 'error');
            }
        }

        // ─── Utilidades ─────────────────────────────────────────────────────────
        function arrayBufferToBase64(buffer) {
            let binary = '';
            const bytes = new Uint8Array(buffer);
            const len = bytes.byteLength;
            const chunkSize = 0x8000; 
            for (let i = 0; i < len; i += chunkSize) {
                binary += String.fromCharCode.apply(null, bytes.subarray(i, i + chunkSize));
            }
            return btoa(binary);
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
