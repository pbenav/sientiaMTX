<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 rounded-xl shadow-sm border border-violet-200 dark:border-violet-800/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white heading">Configuración Global</h1>
            </div>
        </div>
    </x-slot>

    <div class="py-12 px-4 shadow-sm">
        <div class="max-w-7xl mx-auto">
            @include('settings.partials.tabs')

            <div class="space-y-10">
                <form action="{{ route('settings.integrations.update') }}" method="POST" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-xl sm:rounded-2xl transition-all">
                    @csrf
                    
                    <div class="p-8">
                        <div class="flex items-center gap-3 mb-8">
                            <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-black text-gray-900 dark:text-white">Integración con CTH</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Configura la URL y la clave compartida (Secret) para conectarte con el servidor principal de Sientia CTH.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="CTH_API_URL" value="URL del Servidor CTH" />
                                <x-text-input id="CTH_API_URL" name="CTH_API_URL" type="url" class="mt-2 block w-full bg-gray-50" :value="$cth['url']" placeholder="https://cth.sientia.com/api/mtx" />
                                <p class="text-[11px] text-gray-400 mt-2">La URL base de la API de CTH (ej: https://cth.sientia.com/api/mtx).</p>
                            </div>
                            
                            <div>
                                <x-input-label for="CTH_S2S_SECRET" value="Clave de Seguridad S2S" />
                                <x-text-input id="CTH_S2S_SECRET" name="CTH_S2S_SECRET" type="password" class="mt-2 block w-full bg-gray-50" :value="$cth['secret']" placeholder="Introduce el secreto compartido" />
                                <p class="text-[11px] text-gray-400 mt-2">Debe coincidir exactamente con el Secret configurado en el servidor CTH.</p>
                            </div>
                        </div>
                    </div>

                    <div class="px-8 py-5 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-800 rounded-b-2xl flex items-center justify-end">
                        <x-primary-button>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            Guardar Configuración
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
