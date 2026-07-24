<x-app-layout>
    @section('title', 'Papelera de Reciclaje — ' . $team->name)

    <x-slot name="header">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2 min-w-0">
                <a href="{{ route('teams.dashboard', $team) }}"
                    class="p-1.5 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-lg transition-all shrink-0"
                    title="Volver al escritorio">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                @include('teams.partials.breadcrumb')
                <span class="text-gray-300 dark:text-gray-700 mx-1">/</span>
                <h1 class="text-base font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span class="truncate">Papelera del Equipo</span>
                </h1>
            </div>
        </div>

        @include('teams.partials.team-view-nav', ['showCreateActions' => false])
    </x-slot>

    <div class="w-full max-w-7xl mx-auto py-6 sm:px-6 lg:px-8 space-y-6">
        @if($activities->isEmpty() && $expedientes->isEmpty())
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm flex flex-col items-center justify-center p-12 text-center">
                <div class="w-20 h-20 bg-gray-50 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <h2 class="text-xl font-black text-gray-900 dark:text-white mb-2">La papelera está vacía</h2>
                <p class="text-sm text-gray-500 max-w-md">No hay actividades, documentos ni expedientes eliminados recientemente en este equipo.</p>
            </div>
        @else
            <div class="flex justify-end mb-4">
                <form action="{{ route('teams.trash.empty', $team) }}" method="POST" onsubmit="return confirm('¿Estás seguro de que quieres VACIAR LA PAPELERA? Esta acción es irreversible y eliminará todos los elementos y sus archivos adjuntos definitivamente.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="flex items-center gap-1.5 px-4 py-2 bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50 rounded-xl text-sm font-bold transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Vaciar Papelera
                    </button>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
                <div class="p-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Elementos Eliminados</h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left whitespace-nowrap">
                        <thead class="text-xs text-gray-500 dark:text-gray-400 uppercase bg-gray-50 dark:bg-gray-800/50">
                            <tr>
                                <th scope="col" class="px-6 py-3 font-semibold">Tipo</th>
                                <th scope="col" class="px-6 py-3 font-semibold w-full">Título / Descripción</th>
                                <th scope="col" class="px-6 py-3 font-semibold text-center">Fecha de Eliminación</th>
                                <th scope="col" class="px-6 py-3 font-semibold text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($activities as $activity)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider bg-violet-50 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400 border border-violet-100 dark:border-violet-800">
                                            Actividad / Doc
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-900 dark:text-white truncate max-w-sm" title="{{ $activity->title }}">{{ $activity->title }}</div>
                                        <div class="text-xs text-gray-400 truncate max-w-sm">{{ Str::limit(strip_tags($activity->description), 50) }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="text-gray-900 dark:text-white">{{ $activity->deleted_at->format('d/m/Y') }}</div>
                                        <div class="text-xs text-gray-400">{{ $activity->deleted_at->format('H:i') }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-2">
                                            <form action="{{ route('teams.trash.restore.activity', [$team, $activity->id]) }}" method="POST" title="Restaurar">
                                                @csrf
                                                <button type="submit" class="p-1.5 text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-900/30 rounded-lg transition-colors">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                                    </svg>
                                                </button>
                                            </form>
                                            <form action="{{ route('teams.trash.force-delete.activity', [$team, $activity->id]) }}" method="POST" onsubmit="return confirm('¿Eliminar actividad definitivamente? Esta acción no se puede deshacer.');" title="Eliminar definitivamente">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach

                            @foreach($expedientes as $expediente)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 border border-blue-100 dark:border-blue-800">
                                            Expediente
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-900 dark:text-white truncate max-w-sm" title="{{ $expediente->title }}">{{ $expediente->title }}</div>
                                        <div class="text-xs text-gray-400 font-mono">{{ $expediente->code }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="text-gray-900 dark:text-white">{{ $expediente->deleted_at->format('d/m/Y') }}</div>
                                        <div class="text-xs text-gray-400">{{ $expediente->deleted_at->format('H:i') }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-2">
                                            <form action="{{ route('teams.trash.restore.expediente', [$team, $expediente->id]) }}" method="POST" title="Restaurar">
                                                @csrf
                                                <button type="submit" class="p-1.5 text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-900/30 rounded-lg transition-colors">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                                    </svg>
                                                </button>
                                            </form>
                                            <form action="{{ route('teams.trash.force-delete.expediente', [$team, $expediente->id]) }}" method="POST" onsubmit="return confirm('¿Eliminar expediente definitivamente? Esta acción no se puede deshacer.');" title="Eliminar definitivamente">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
