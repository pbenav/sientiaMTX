<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('google.authenticating') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-50 dark:bg-gray-900 flex items-center justify-center min-h-screen font-sans">
    <div class="text-center p-8 bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 max-w-sm mx-auto">
        <div class="mb-6 flex justify-center">
            <div class="w-16 h-16 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center text-emerald-600 dark:text-emerald-400 mx-auto" style="width: 4rem; height: 4rem; display: flex; align-items: center; justify-content: center;">
                <svg class="w-10 h-10" style="width: 2.5rem; height: 2.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
            </div>
        </div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">¡Conexión Exitosa!</h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm mb-6">Ya puedes cerrar esta ventana. Sientia Open Source Lab se actualizará automáticamente.</p>
        
        <button onclick="window.close()" class="w-full py-3 bg-gray-900 dark:bg-violet-600 text-white font-bold rounded-2xl hover:scale-105 active:scale-95 transition-all shadow-lg">
            Cerrar ventana ahora
        </button>
    </div>

    <script>
        // Use multiple ways to notify success to the parent
        function notifyParent() {
            console.log("Notifying parent of auth success...");
            
            // 1. Standard postMessage
            if (window.opener) {
                window.opener.postMessage('google-auth-success', '*');
            }
            
            // 2. LocalStorage fallback (the parent can listen for 'storage' event)
            localStorage.setItem('google-auth-trigger', Date.now());
            
            // Auto-close after a small delay
            setTimeout(function() {
                if (window.opener) {
                    window.close();
                }
            }, 1500);
        }

        // Run as soon as possible
        notifyParent();
    </script>
</body>

</html>
