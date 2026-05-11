<!DOCTYPE html>
<html lang="es" class="h-full overflow-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $attachment->file_name }} - Sientia Office</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
            font-family: system-ui, -apple-system, sans-serif;
            background: #f8fafc;
        }
        #editor-container {
            height: 100%;
            width: 100%;
        }
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.5s ease;
        }
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e2e8f0;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
    <!-- Inyectar Script de OnlyOffice Server -->
    <script type="text/javascript" src="{{ $apiUrl }}"></script>
</head>
<body>
    <div id="loading" class="loading-overlay">
        <div class="spinner"></div>
        <p style="color: #64748b; font-weight: 500;">Iniciando Sientia Office...</p>
    </div>

    <div id="editor-container"></div>

    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            try {
                // Get configuration injected from Laravel backend
                const config = @json($config);

                // Add events wrapper
                config.events = {
                    "onAppReady": function() {
                        const loading = document.getElementById('loading');
                        if (loading) {
                            loading.style.opacity = '0';
                            setTimeout(() => loading.remove(), 500);
                        }
                    },
                    "onError": function(event) {
                        console.error("OnlyOffice Error:", event);
                        alert("Error al cargar el documento en el editor.");
                    },
                    "onDocumentStateChange": function(event) {
                        // Example: track when document is modified but not saved yet
                        if (event.data) {
                            document.title = "* {{ $attachment->file_name }} - Sientia Office";
                        } else {
                            document.title = "{{ $attachment->file_name }} - Sientia Office";
                        }
                    }
                };

                // Initialize DocsAPI globally registered by the external script
                window.docEditor = new DocsAPI.DocEditor("editor-container", config);

            } catch (err) {
                console.error("Initialization Failed", err);
                document.getElementById('loading').innerHTML = "<p style='color:red'>Error crítico de conexión con el servidor de documentos. Verifica que office.sientia.com está operativo.</p>";
            }
        });
    </script>
</body>
</html>
