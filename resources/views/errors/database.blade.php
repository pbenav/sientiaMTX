<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servicio temporalmente no disponible</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,900&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-950 text-gray-800 dark:text-gray-200 antialiased min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-800 overflow-hidden">
        <div class="p-6 sm:p-8 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 11v6m-3-3h6" />
                </svg>
            </div>
            
            <h1 class="text-2xl font-black text-gray-900 dark:text-white mb-2 tracking-tight">Problema de conexión</h1>
            <p class="text-gray-500 dark:text-gray-400 mb-8 leading-relaxed">
                No hemos podido establecer conexión con la base de datos principal. Nuestro equipo técnico probablemente ya está al tanto y trabajando en ello.
            </p>
            
            <button onclick="window.location.reload()" class="w-full inline-flex justify-center items-center gap-2 px-4 py-3 bg-gray-900 hover:bg-gray-800 dark:bg-white dark:hover:bg-gray-100 text-white dark:text-gray-900 font-semibold rounded-xl transition-colors active:scale-[0.98]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Reintentar conexión
            </button>
        </div>
        <div class="bg-gray-50 dark:bg-gray-800/50 px-6 py-4 border-t border-gray-100 dark:border-gray-800 flex justify-between items-center text-xs text-gray-500">
            <span>Sientia MTX</span>
            <span class="font-mono">ERR_DB_CONN</span>
        </div>
    </div>
</body>
</html>
