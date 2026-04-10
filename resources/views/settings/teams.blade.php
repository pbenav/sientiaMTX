<x-app-layout>
    @section('title', 'Gestión Global de Equipos')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white heading">Gestión de Equipos</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Administración global de todos los equipos del sistema.</p>
            </div>
            <div>
                <a href="{{ route('teams.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-violet-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nuevo Equipo
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12 px-4">
        <div class="max-w-7xl mx-auto">
            @include('settings.partials.tabs')

            <div class="mb-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-4 shadow-sm">
                <form action="{{ route('settings.teams') }}" method="GET" class="flex flex-wrap items-center gap-3">
                    <div class="relative flex-1 min-w-[250px]">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" 
                            class="block w-full pl-9 pr-3 py-2 text-sm border border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-gray-800/50 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 transition-all"
                            placeholder="Buscar por nombre o descripción del equipo...">
                    </div>
                    
                    <select name="per_page" onchange="this.form.submit()"
                        class="text-sm bg-gray-50/50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-800 rounded-xl px-3 pr-12 py-2 focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 outline-none text-gray-700 dark:text-gray-300 cursor-pointer">
                        <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                        <option value="25" {{ request('per_page', 25) == 25 || request('per_page') == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                    </select>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="px-5 py-2 bg-violet-600 hover:bg-violet-700 text-white text-xs font-black uppercase tracking-widest rounded-xl transition-all shadow-lg shadow-violet-500/20">
                            Filtrar
                        </button>
                        @if(request()->anyFilled(['search', 'per_page']))
                            <a href="{{ route('settings.teams') }}" class="px-4 py-2 text-xs font-bold text-gray-500 hover:text-red-500 transition-colors uppercase tracking-widest">
                                Limpiar
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800">
                                @foreach([
                                    'name' => 'Equipo',
                                    'created_at' => 'Creado'
                                ] as $field => $label)
                                    <th class="px-6 py-4">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => $field, 'direction' => $sort === $field && $direction === 'asc' ? 'desc' : 'asc']) }}" 
                                           class="flex items-center gap-1.5 text-xs font-black uppercase tracking-widest leading-none {{ $sort === $field ? 'text-violet-600 dark:text-violet-400' : 'text-gray-500 dark:text-gray-400' }} hover:text-violet-500 transition-colors group">
                                            {{ $label }}
                                            <div class="flex flex-col {{ $sort === $field ? 'opacity-100' : 'opacity-0 group-hover:opacity-50' }} transition-opacity">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2 {{ $sort === $field && $direction === 'asc' ? 'text-violet-600' : 'text-gray-300' }}" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
                                                </svg>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2 -mt-1 {{ $sort === $field && $direction === 'desc' ? 'text-violet-600' : 'text-gray-300' }}" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </a>
                                    </th>
                                @endforeach
                                <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">Creador</th>
                                <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">Miembros</th>
                                <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($teams as $team)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-2xl bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 flex items-center justify-center font-black text-xs uppercase shadow-sm">
                                                {{ substr($team->name, 0, 2) }}
                                            </div>
                                            <div>
                                                <a href="{{ route('teams.show', $team) }}" class="text-sm font-bold text-gray-900 dark:text-white hover:text-violet-600 transition-colors">
                                                    {{ $team->name }}
                                                </a>
                                                <div class="text-[10px] text-gray-400 uppercase font-black tracking-tight mt-0.5">
                                                    {{ $team->slug }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $team->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $team->creator->name ?? 'Sistema' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                            {{ $team->members->count() }} miembros
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-2">
                                        <a href="{{ route('teams.edit', $team) }}" class="inline-flex items-center p-2 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-all">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                        
                                        <form action="{{ route('teams.destroy', $team) }}" method="POST" class="inline" id="delete-team-{{ $team->id }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" 
                                                onclick="confirmDelete({{ $team->id }}, '{{ $team->name }}')"
                                                class="inline-flex items-center p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-all">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($teams->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
                        {{ $teams->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function confirmDelete(teamId, teamName) {
            Swal.fire({
                title: '¿Eliminar equipo?',
                text: "Estás a punto de eliminar el equipo '" + teamName + "'. Esta acción no se puede deshacer y se borrarán todas sus tareas y datos asociados.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, eliminar equipo',
                cancelButtonText: 'Cancelar',
                background: document.documentElement.classList.contains('dark') ? '#111827' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#ffffff' : '#111827',
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-team-' + teamId).submit();
                }
            });
        }
    </script>
    @endpush
</x-app-layout>
