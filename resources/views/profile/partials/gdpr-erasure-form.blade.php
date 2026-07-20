<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-red-600 dark:text-red-400 heading">
            {{ __('Derecho al olvido (Artículo 17 RGPD)') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-500">
            {{ __('De acuerdo con el Reglamento General de Protección de Datos (RGPD), tienes derecho a solicitar la eliminación de todos tus datos personales. Esta acción anonimizará tu identidad en las tareas, actividades y citas que compartiste, y eliminará tus mensajes, notas y registros asociados.') }}
        </p>

        <div class="mt-4 p-4 rounded-2xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
            <p class="text-sm text-red-700 dark:text-red-300 leading-relaxed">
                <strong>{{ __('Advertencia:') }}</strong> {{ __('Esta acción es irreversible. Se eliminarán permanentemente:') }}
                <ul class="mt-2 list-disc list-inside space-y-1">
                    <li>{{ __('Tus notas rápidas, registros de estado de ánimo y preferencias de IA') }}</li>
                    <li>{{ __('Tus mensajes privados y chats con IA') }}</li>
                    <li>{{ __('Tus calificaciones, historiales y asignaciones') }}</li>
                    <li>{{ __('Tus hilos de foro y mensajes') }}</li>
                    <li>{{ __('Tus bloques de citas, servicios y horarios') }}</li>
                    <li>{{ __('Tus registros de seguridad y adjuntos') }}</li>
                    <li>{{ __('Tu sesión actual y cuenta de usuario') }}</li>
                </ul>
            </p>
        </div>
    </header>

    <x-danger-button x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-gdpr-erasure')">{{ __('Solicitar eliminación de datos') }}</x-danger-button>

    <x-modal name="confirm-gdpr-erasure" :show="$errors->gdprErasure->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.erasure') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900 dark:text-white heading">
                {{ __('¿Confirmar eliminación de datos?') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Escribe tu nombre de usuario para confirmar que deseas eliminar todos tus datos personales.') }}
            </p>

            <div class="mt-6">
                <label for="confirmation_text" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('Escribe tu nombre para confirmar') }}
                </label>
                <input type="text" id="confirmation_text" name="confirmation_text" class="mt-1 block w-3/4 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-red-500 focus:ring-red-500" placeholder="{{ auth()->user()->name }}" required x-ref="confirmationInput" />
                <x-input-error :messages="$errors->gdprErasure->get('confirmation_text')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancelar') }}
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    {{ __('Eliminar todos mis datos') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
