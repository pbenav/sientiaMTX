@php
    $showWelcome = session('show_welcome_modal');
    $messages = app(\App\Services\QuoteService::class)->getWelcomeMessage();
    $greeting = $messages['greeting'];
    $quote = $messages['quote'];
@endphp

@if ($showWelcome && $greeting && $quote)
    <div x-data="{ show: true }" 
         x-show="show" 
         x-init="setTimeout(() => $el.classList.remove('invisible'), 100)"
         class="fixed inset-0 z-[999999] flex items-center justify-center p-4 sm:p-6 invisible"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="show = false"></div>

        <!-- Modal Content -->
        <div class="relative w-full max-w-lg bg-white dark:bg-gray-900 rounded-3xl shadow-2xl overflow-hidden border border-gray-100 dark:border-gray-800">
            <!-- Decorative Background -->
            <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-br from-violet-600 to-indigo-700 opacity-10 dark:opacity-20"></div>
            
            <div class="relative p-8 pt-10 text-center">
                <!-- Icon/Avatar -->
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-violet-500 to-indigo-600 shadow-xl shadow-violet-500/30 mb-6 transform -rotate-3 hover:rotate-0 transition-transform duration-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <!-- Greeting -->
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2 leading-tight">
                    {{ str_replace('Usuario', auth()->user()->name, $greeting->text) }}
                </h3>
                
                <p class="text-gray-500 dark:text-gray-400 mb-8 max-w-xs mx-auto">
                    {{ __('¡Qué bueno tenerte de vuelta en tu panel de control!') }}
                </p>

                <!-- Quote Box -->
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-6 mb-8 border border-gray-100 dark:border-gray-700 italic relative group">
                    <svg class="absolute top-4 left-4 h-6 w-6 text-violet-200 dark:text-violet-800 opacity-50" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M14.017 21L14.017 18C14.017 16.8954 14.9124 16 16.017 16H19.017C19.5693 16 20.017 15.5523 20.017 15V9C20.017 8.44772 19.5693 8 19.017 8H16.017C14.9124 8 14.017 7.10457 14.017 6V4H21.017V15C21.017 17.2091 19.2261 19 17.017 19H14.017V21H14.017Z" />
                        <path d="M3.017 21L3.017 18C3.017 16.8954 3.91243 16 5.017 16H8.017C8.56928 16 9.017 15.5523 9.017 15V9C9.017 8.44772 8.56928 8 8.017 8H5.017C3.91243 8 3.017 7.10457 3.017 6V4H10.017V15C10.017 17.2091 8.22614 19 6.017 19H3.017V21H3.017Z" />
                    </svg>
                    <p class="text-gray-700 dark:text-gray-300 relative z-10 leading-relaxed">
                        "{{ $quote->text }}"
                    </p>
                    @if($quote->author)
                        <footer class="mt-3 text-sm text-violet-600 dark:text-violet-400 font-semibold not-italic">— {{ $quote->author }}</footer>
                    @endif
                </div>

                <!-- Action Button -->
                <button @click="show = false" 
                        class="w-full py-4 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-700 hover:to-indigo-700 text-white font-bold rounded-2xl shadow-lg shadow-violet-500/25 transition-all duration-300 transform active:scale-[0.98]">
                    {{ __('¡Vamos a por ello!') }}
                </button>

                <!-- Shortcut to Settings -->
                <p class="mt-6 text-xs text-gray-400 dark:text-gray-500">
                    {{ __('¿No quieres ver esto más?') }} 
                    <a href="{{ route('profile.edit') }}" class="text-violet-500 hover:text-violet-600 underline transition-colors">
                        {{ __('Ajusta tus preferencias en tu perfil.') }}
                    </a>
                </p>
            </div>
        </div>
    </div>
@endif
