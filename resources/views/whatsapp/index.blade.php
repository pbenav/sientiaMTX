<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Integración con WhatsApp Web') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    Estado del Servicio Node.js
                </h3>

                @if(!isset($status) || (!isset($status['ready']) && !isset($status['qr'])))
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                        <p class="font-bold">Servicio no disponible</p>
                        <p>Parece que el servicio de Node.js no está corriendo. Debes arrancarlo para poder vincular WhatsApp.</p>
                    </div>
                @elseif(isset($status['ready']) && $status['ready'])
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 flex items-center" role="alert">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <div>
                            <p class="font-bold">¡WhatsApp Conectado!</p>
                            <p>El bot está listo y escuchando mensajes en tiempo real.</p>
                        </div>
                    </div>
                @elseif(isset($status['qr']) && $status['qr'])
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6" role="alert">
                        <p class="font-bold">Requiere vinculación</p>
                        <p>Abre WhatsApp en tu teléfono, ve a "Dispositivos vinculados" y escanea este código QR.</p>
                    </div>
                    
                    <div class="flex justify-center my-8">
                        <div class="p-4 bg-white rounded-xl shadow-md border border-gray-200 inline-block">
                            <img src="{{ $status['qr'] }}" alt="WhatsApp QR Code" class="w-64 h-64">
                            <p class="text-center text-sm text-gray-500 mt-2">El QR caduca rápido, <a href="#" onclick="window.location.reload();" class="text-blue-500 hover:underline">recarga la página</a> si no funciona.</p>
                        </div>
                    </div>
                @else
                    <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6" role="alert">
                        <p class="font-bold">Iniciando el cliente...</p>
                        <p>El servicio está corriendo pero aún está cargando WhatsApp. Recarga la página en unos segundos.</p>
                    </div>
                @endif

                <div class="mt-8 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-2">Instrucciones para el servidor:</h4>
                    <ol class="list-decimal list-inside text-sm text-gray-600 dark:text-gray-400 space-y-2">
                        <li>Abre una terminal en tu servidor (donde está alojado SientiaMTX).</li>
                        <li>Navega a la carpeta <code>whatsapp-service</code> que está en la raíz del proyecto.</li>
                        <li>Ejecuta <code>npm install</code> (solo la primera vez).</li>
                        <li>Para arrancar el servicio ejecuta: <code>node server.js</code>.</li>
                        <li><em>Recomendado:</em> Usa un gestor de procesos como PM2 para que no se apague al cerrar la terminal: <code>pm2 start server.js --name "sientia-whatsapp"</code>.</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
