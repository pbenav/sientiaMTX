<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Privacidad y Portabilidad de Datos') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
            {{ __('De acuerdo con el RGPD, tienes derecho a descargar toda la información que hemos recopilado sobre ti en esta plataforma. Recibirás un archivo en formato JSON con tus datos de perfil, tareas, equipos y mensajes.') }}
        </p>
    </header>

    <div class="flex items-center gap-4">
        <a href="{{ route('profile.export') }}" class="inline-flex items-center px-4 py-2.5 bg-violet-600 hover:bg-violet-700 dark:bg-violet-500 dark:hover:bg-violet-600 text-white text-sm font-bold rounded-xl shadow-lg shadow-violet-500/20 transition-all duration-300 hover:scale-105 active:scale-95 cursor-pointer">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
            </svg>
            {{ __('Descargar todos mis datos') }}
        </a>
    </div>

    <div class="mt-4 p-4 rounded-2xl bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800">
        <p class="text-xs text-blue-700 dark:text-blue-300 leading-relaxed">
            <strong>{{ __('Nota:') }}</strong> {{ __('Tus consentimientos actuales:') }}
            <br>
            - {{ __('Políticas aceptadas el:') }} {{ auth()->user()->privacy_policy_accepted_at?->format('d/m/Y H:i') ?? __('No disponible') }}
            <br>
            - {{ __('Marketing:') }} {{ auth()->user()->marketing_accepted_at ? __('Aceptado') : __('No aceptado') }}
        </p>
    </div>
</section>
