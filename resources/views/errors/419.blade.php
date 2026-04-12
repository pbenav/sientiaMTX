<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sesión Expirada</title>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    <script>
        // Redirect to home after 1 second
        setTimeout(function() {
            window.location.href = "{{ route('dashboard') }}";
        }, 1000);
    </script>

    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 text-center">
            <div>
                <h2 class="mt-6 text-3xl font-bold text-gray-900">
                    Sesión Expirada
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Tu sesión ha expirado por seguridad. Te redirigiremos al inicio en un momento...
                </p>
            </div>

            <div>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700">
                    Ir al inicio ahora
                </a>
            </div>

            <p class="text-xs text-gray-500">
                Si no eres redirigido automáticamente, haz clic en el botón anterior.
            </p>
        </div>
    </div>
</body>
</html>
