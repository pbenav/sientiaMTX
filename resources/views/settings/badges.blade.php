<x-app-layout>
    @section('title', 'Gestión de Medallas (Gamificación)')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 rounded-xl shadow-sm border border-violet-200 dark:border-violet-800/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white heading">
                        Catálogo de Medallas
                    </h1>
                    <x-demo-hint>
                        Gestiona las medallas que los usuarios pueden desbloquear en la plataforma.
                    </x-demo-hint>
                </div>
            </div>
            
            <button x-data @click="$dispatch('open-modal', 'create-badge')" class="btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                Nueva Medalla
            </button>
        </div>
    </x-slot>

    <div class="py-12 px-4">
        <div class="max-w-7xl mx-auto">
            @include('settings.partials.tabs')
            
            <!-- Leyenda Explicativa -->
            <div class="mb-8 p-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-2xl">
                <h3 class="text-lg font-bold text-blue-800 dark:text-blue-300 mb-2 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    ¿Cómo funciona el motor de medallas?
                </h3>
                <p class="text-sm text-blue-700 dark:text-blue-400 mb-4">
                    El sistema de gamificación evalúa automáticamente a los usuarios basándose en el <strong>Tipo de Regla (Criteria Type)</strong> y el <strong>Umbral (Threshold)</strong>. 
                    Si deseas crear una regla personalizada que no esté en la lista soportada, necesitarás añadir el código correspondiente en <code>BadgeService.php</code>.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">
                    <div class="bg-white dark:bg-gray-800 p-3 rounded-xl border border-blue-100 dark:border-blue-800/50">
                        <span class="font-mono font-bold text-violet-600 dark:text-violet-400 block mb-1">login_count</span>
                        Se evalúa cada vez que un usuario inicia sesión.
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-3 rounded-xl border border-blue-100 dark:border-blue-800/50">
                        <span class="font-mono font-bold text-violet-600 dark:text-violet-400 block mb-1">streak_days</span>
                        Días consecutivos conectándose y realizando acciones.
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-3 rounded-xl border border-blue-100 dark:border-blue-800/50">
                        <span class="font-mono font-bold text-violet-600 dark:text-violet-400 block mb-1">completed_tasks</span>
                        Número total de tareas o actividades completadas.
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-3 rounded-xl border border-blue-100 dark:border-blue-800/50">
                        <span class="font-mono font-bold text-violet-600 dark:text-violet-400 block mb-1">received_kudos</span>
                        Cantidad de "Kudos" o reconocimientos recibidos de compañeros.
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-3 rounded-xl border border-blue-100 dark:border-blue-800/50">
                        <span class="font-mono font-bold text-violet-600 dark:text-violet-400 block mb-1">user_level</span>
                        Nivel del usuario alcanzado mediante puntos de experiencia (XP).
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-3 rounded-xl border border-blue-100 dark:border-blue-800/50">
                        <span class="font-mono font-bold text-violet-600 dark:text-violet-400 block mb-1">badges_count</span>
                        Cantidad de medallas distintas que ya posee el usuario.
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                        <thead class="bg-gray-50 dark:bg-gray-800/50 text-xs uppercase font-black tracking-widest text-gray-700 dark:text-gray-300">
                            <tr>
                                <th scope="col" class="px-6 py-4">Medalla</th>
                                <th scope="col" class="px-6 py-4">Descripción</th>
                                <th scope="col" class="px-6 py-4">Regla (Criteria)</th>
                                <th scope="col" class="px-6 py-4">Umbral</th>
                                <th scope="col" class="px-6 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($badges as $badge)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="text-3xl bg-gray-100 dark:bg-gray-800 w-12 h-12 rounded-2xl flex items-center justify-center">
                                                {{ $badge->icon }}
                                            </div>
                                            <div class="font-bold text-gray-900 dark:text-white">
                                                {{ $badge->name }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-normal min-w-[250px]">
                                        <span class="text-xs text-gray-600 dark:text-gray-400 block mt-0.5 line-clamp-2" title="{{ $badge->description }}">
                                            {{ $badge->description ?: '-' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400 rounded-lg text-xs font-mono font-bold">
                                            {{ $badge->criteria_type }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-mono font-bold">
                                        {{ $badge->criteria_threshold }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button" 
                                                    x-data=""
                                                    @click="$dispatch('open-modal', 'edit-badge-{{ $badge->id }}')"
                                                    class="p-2 text-gray-400 hover:text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-500/10 rounded-xl transition-all"
                                                    title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button type="button" 
                                                    x-data=""
                                                    @click="$dispatch('open-modal', 'delete-badge-{{ $badge->id }}')"
                                                    class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-xl transition-all"
                                                    title="Eliminar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- Edit Modal -->
                                        <x-modal name="edit-badge-{{ $badge->id }}" maxWidth="lg">
                                            <form method="POST" action="{{ route('settings.badges.update', $badge) }}" class="p-6 text-left">
                                                @csrf
                                                @method('PATCH')
                                                <h2 class="text-xl font-black text-gray-900 dark:text-white mb-6">Editar Medalla</h2>
                                                
                                                <div class="space-y-4">
                                                    <div>
                                                        <x-input-label for="name" value="Nombre de la medalla" />
                                                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="$badge->name" required />
                                                    </div>
                                                    
                                                    <div>
                                                        <x-input-label for="icon" value="Icono (Emoji)" />
                                                        <x-text-input id="icon" name="icon" type="text" class="mt-1 block w-full text-2xl" :value="$badge->icon" required />
                                                    </div>
                                                    
                                                    <div>
                                                        <x-input-label for="description" value="Descripción" />
                                                        <textarea id="description" name="description" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-violet-500 dark:focus:border-violet-600 focus:ring-violet-500 dark:focus:ring-violet-600 rounded-xl shadow-sm" rows="3">{{ $badge->description }}</textarea>
                                                    </div>

                                                    <div class="grid grid-cols-2 gap-4">
                                                        <div>
                                                            <x-input-label for="criteria_type" value="Regla (Criteria Type)" />
                                                            <x-text-input id="criteria_type" name="criteria_type" type="text" class="mt-1 block w-full font-mono text-sm" :value="$badge->criteria_type" required />
                                                        </div>
                                                        <div>
                                                            <x-input-label for="criteria_threshold" value="Umbral" />
                                                            <x-text-input id="criteria_threshold" name="criteria_threshold" type="number" min="1" class="mt-1 block w-full font-mono text-sm" :value="$badge->criteria_threshold" required />
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mt-6 flex justify-end gap-3">
                                                    <button type="button" x-on:click="$dispatch('close')" class="btn-secondary">Cancelar</button>
                                                    <button type="submit" class="btn-primary">Guardar Cambios</button>
                                                </div>
                                            </form>
                                        </x-modal>

                                        <!-- Delete Modal -->
                                        <x-modal name="delete-badge-{{ $badge->id }}" maxWidth="sm">
                                            <form method="POST" action="{{ route('settings.badges.destroy', $badge) }}" class="p-6 text-center">
                                                @csrf
                                                @method('DELETE')
                                                <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                                </div>
                                                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">¿Eliminar medalla?</h2>
                                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Los usuarios que ya la posean podrían perderla o causar inconsistencias en sus perfiles. Esta acción no se puede deshacer.</p>
                                                
                                                <div class="flex justify-center gap-3">
                                                    <button type="button" x-on:click="$dispatch('close')" class="btn-secondary">Cancelar</button>
                                                    <button type="submit" class="btn-danger">Sí, eliminar</button>
                                                </div>
                                            </form>
                                        </x-modal>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                        No hay medallas configuradas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Modal -->
    <x-modal name="create-badge" maxWidth="lg">
        <form method="POST" action="{{ route('settings.badges.store') }}" class="p-6">
            @csrf
            <h2 class="text-xl font-black text-gray-900 dark:text-white mb-6">Crear Nueva Medalla</h2>
            
            <div class="space-y-4">
                <div>
                    <x-input-label for="name" value="Nombre de la medalla" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" required />
                </div>
                
                <div>
                    <x-input-label for="icon" value="Icono (Emoji)" />
                    <x-text-input id="icon" name="icon" type="text" class="mt-1 block w-full text-2xl" placeholder="🏅" required />
                </div>
                
                <div>
                    <x-input-label for="description" value="Descripción" />
                    <textarea id="description" name="description" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-violet-500 dark:focus:border-violet-600 focus:ring-violet-500 dark:focus:ring-violet-600 rounded-xl shadow-sm" rows="3"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="criteria_type" value="Regla (Criteria Type)" />
                        <x-text-input id="criteria_type" name="criteria_type" type="text" class="mt-1 block w-full font-mono text-sm" placeholder="ej. completed_tasks" required />
                    </div>
                    <div>
                        <x-input-label for="criteria_threshold" value="Umbral" />
                        <x-text-input id="criteria_threshold" name="criteria_threshold" type="number" min="1" value="1" class="mt-1 block w-full font-mono text-sm" required />
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" x-on:click="$dispatch('close')" class="btn-secondary">Cancelar</button>
                <button type="submit" class="btn-primary">Crear Medalla</button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
