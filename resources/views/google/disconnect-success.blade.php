<x-app-layout>
    <div class="flex items-center justify-center min-h-screen bg-gray-100 dark:bg-gray-900 px-4">
        <div class="max-w-md w-full bg-white dark:bg-gray-800 shadow-2xl rounded-3xl p-10 text-center animate-card-appear">
            <div class="w-20 h-20 bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            
            <h1 class="text-2xl font-black text-gray-900 dark:text-white mb-4">
                {{ __('google.disconnected_success') }}
            </h1>
            
            <p class="text-gray-500 dark:text-gray-400 mb-8 font-medium">
                {{ __('google.window_closing') ?? 'Cerrando ventana y actualizando el tablero...' }}
            </p>

            <div class="inline-flex items-center gap-2 px-4 py-2 bg-gray-50 dark:bg-gray-900 rounded-xl text-xs font-bold text-gray-400 uppercase tracking-widest">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-gray-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-gray-500"></span>
                </span>
                {{ __('Please wait') }}...
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Force reload parent window if exists
        try {
            if (window.opener && !window.opener.closed) {
                window.opener.location.reload();
            }
        } catch (e) {
            console.error('Window opener error:', e);
        }

        // Close this window after 2 seconds
        setTimeout(() => {
            window.close();
        }, 1500);
    </script>
    @endpush
</x-app-layout>
