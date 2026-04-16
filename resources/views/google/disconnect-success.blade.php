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
                {{ __('google.window_closing') }}
            </p>

            <div class="space-y-4">
                <button onclick="window.close()" class="px-6 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 rounded-xl text-xs font-bold uppercase tracking-wider transition-all">
                    {{ __('Close Window') }}
                </button>

                <div class="block">
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-gray-50 dark:bg-gray-900 rounded-xl text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-gray-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-gray-500"></span>
                        </span>
                        {{ __('Please wait') }}...
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function finalize() {
            let handled = false;
            try {
                // If we have an opener and it's not closed, we're likely in a popup
                if (window.opener && !window.opener.closed) {
                    window.opener.location.reload();
                    handled = true;
                }
            } catch (e) {
                console.warn('Could not reload parent:', e);
            }
            
            if (handled) {
                // It was a popup, try to close
                setTimeout(() => {
                    window.close();
                    // Fallback if window.close() is blocked or didn't work
                    setTimeout(() => {
                        window.location.href = "{{ $team ? route('teams.dashboard', $team) : route('dashboard') }}";
                    }, 1500);
                }, 500);
            } else {
                // Not a popup or opener inaccessible, redirect after delay
                window.location.href = "{{ $team ? route('teams.dashboard', $team) : route('dashboard') }}";
            }
        }

        // Execute as soon as possible, but wait for visual cue
        if (document.readyState === 'complete') {
            setTimeout(finalize, 1500);
        } else {
            window.addEventListener('load', () => {
                setTimeout(finalize, 1500);
            });
        }
    </script>
    @endpush
</x-app-layout>
