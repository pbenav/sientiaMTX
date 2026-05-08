<x-guest-layout>
    <div class="text-center">
        <!-- Icono Animado Sutil -->
        <div class="flex justify-center mb-6">
            <div class="relative w-20 h-20 bg-violet-100 dark:bg-violet-950/50 rounded-full flex items-center justify-center animate-pulse">
                <svg class="w-10 h-10 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path>
                </svg>
                <span class="absolute -top-1 -right-1 flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-violet-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-violet-500"></span>
                </span>
            </div>
        </div>

        <!-- Título -->
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-3 tracking-tight">
            {{ __('¡Estás en la lista de espera!') }}
        </h2>

        <!-- Descripción cálida -->
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-6 leading-relaxed">
            {!! __('¡Hola, <strong>:name</strong>! Sientia está creciendo con mucho mimo para garantizar un rendimiento óptimo. Tu cuenta ha sido registrada con éxito, pero se encuentra temporalmente en cola de espera para ser aprobada por el administrador en la próxima oleada.', ['name' => auth()->user()->name]) !!}
        </p>

        <!-- Badge de Estado -->
        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-amber-50 dark:bg-amber-950/30 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-900/50 mb-8">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
            {{ __('Pendiente de Aprobación') }}
        </div>

        <!-- Botones de Acción -->
        <div class="border-t border-gray-100 dark:border-gray-800 pt-6 flex flex-col gap-3">
            <p class="text-xs text-gray-500 dark:text-gray-500">
                {{ __('¿Tienes un pase VIP o quieres entrar con otra cuenta?') }}
            </p>
            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold text-sm rounded-xl transition-all active:scale-[0.98]">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75"></path>
                    </svg>
                    {{ __('Cerrar Sesión') }}
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>
