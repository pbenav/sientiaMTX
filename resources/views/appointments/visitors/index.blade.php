<x-app-layout maxWidth="max-w-none">
@section('title', 'Directorio de Personas')

<x-slot name="header">
    <div class="flex items-center justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-2 min-w-0">
            <a href="{{ route('appointments.index', $team) }}"
                class="p-1.5 text-gray-400 hover:text-cyan-600 dark:hover:text-cyan-400 rounded-lg transition-all shrink-0">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            @include('teams.partials.breadcrumb')
            <span class="text-gray-300 dark:text-gray-700 mx-1">/</span>
            <h1 class="text-base font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight flex items-center gap-1.5">
                <svg class="h-4 w-4 text-cyan-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span class="truncate">Personas</span>
            </h1>
        </div>
    </div>
    <div class="mt-2 border-t border-gray-100 dark:border-gray-800 pt-3">
        <x-demo-hint>
            Este es el <strong>directorio de personas (solicitantes)</strong> que han solicitado citas en alguno de los servicios de este equipo. Aquí puedes consultar y actualizar sus datos de contacto y gestionar sus registros.
        </x-demo-hint>
    </div>
    <!-- Sub-Menú de Navegación -->
    @include('appointments.partials.nav')
</x-slot>

<div class="py-8">
    <div class="max-w-full mx-auto sm:px-6 lg:px-8">

        @if(session('success'))
            <div class="mb-6 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-2xl p-4 text-sm font-bold flex items-center gap-3">
                <svg class="w-5 h-5 shrink-0 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 rounded-2xl p-4 text-sm font-bold flex items-center gap-3">
                <svg class="w-5 h-5 shrink-0 text-red-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('error') }}
            </div>
        @endif

        <!-- Filtros -->
        <div class="mb-4 bg-gray-50 dark:bg-gray-800/30 border border-gray-100 dark:border-gray-700/30 rounded-2xl p-4">
            <div class="flex items-center justify-between mb-3">
                <button type="button" id="filterToggle" class="flex items-center gap-2 text-xs font-black uppercase tracking-wider text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.01a1 1 0 01-.293.707l-4.206 4.206A1 1 0 0115 12v4a1 1 0 01-.293.707l-4.206 4.206A1 1 0 0110 17v4a1 1 0 01-1 1H4a1 1 0 01-1-1v-2.01a1 1 0 01.293-.707l4.206-4.206A1 1 0 018 12V8a1 1 0 01.293-.707l4.206-4.206A1 1 0 0112 5V4a1 1 0 01-1-1z"/></svg>
                    Filtros
                    <span id="filterBadge" class="hidden bg-cyan-500 text-white text-[10px] font-black px-2 py-0.5 rounded-full">0</span>
                </button>
                <a href="{{ route('appointments.visitors.index', $team) }}" class="text-xs font-bold text-gray-400 hover:text-red-500 transition-colors">Limpiar todo</a>
            </div>
            <div id="filterPanel" class="hidden space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Nombre</label>
                        <input type="text" name="filter_name" value="{{ $filterName ?? '' }}" placeholder="Nombre..." 
                               class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-xl text-xs font-medium focus:ring-2 focus:ring-cyan-500/20 focus:border-cyan-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">DNI</label>
                        <input type="text" name="filter_dni" value="{{ $filterDni ?? '' }}" placeholder="DNI..." 
                               class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-xl text-xs font-medium focus:ring-2 focus:ring-cyan-500/20 focus:border-cyan-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Ubicación</label>
                        <input type="text" name="filter_city" value="{{ $filterCity ?? '' }}" placeholder="Ciudad..." 
                               class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-xl text-xs font-medium focus:ring-2 focus:ring-cyan-500/20 focus:border-cyan-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Mín. citas</label>
                        <input type="number" name="filter_min_appointments" value="{{ $filterMinAppointments ?? '' }}" placeholder="0..." min="0"
                               class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-xl text-xs font-medium focus:ring-2 focus:ring-cyan-500/20 focus:border-cyan-500 transition-all">
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <a href="{{ route('appointments.visitors.index', $team) }}" class="px-4 py-2 text-xs font-black uppercase tracking-wider text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">Cancelar</a>
                    <form method="GET" action="{{ route('appointments.visitors.index', $team) }}" class="flex gap-2">
                        <input type="hidden" name="search" value="{{ request('search') }}">
                        <button type="submit" class="px-4 py-2 text-xs font-black uppercase tracking-wider bg-cyan-500 hover:bg-cyan-600 text-white rounded-xl transition-all shadow-sm">Aplicar</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Búsqueda -->
        <div class="mb-4 flex flex-col sm:flex-row gap-4 items-center justify-between">
            <form method="GET" action="{{ route('appointments.visitors.index', $team) }}" class="w-full md:w-1/2 xl:w-1/3 relative">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre, DNI, correo o teléfono..." 
                       class="w-full pl-11 pr-4 py-3 border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-2xl text-sm font-medium focus:ring-2 focus:ring-cyan-500/20 focus:border-cyan-500 transition-all shadow-sm">
                <svg class="w-5 h-5 absolute left-4 top-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                @if(request('search'))
                    <a href="{{ route('appointments.visitors.index', $team) }}" class="absolute right-3 top-3.5 p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 bg-gray-100 dark:bg-gray-800 rounded-full transition-colors">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </a>
                @endif
            </form>
        </div>

        @if($visitors->isEmpty())
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-16 text-center flex flex-col items-center">
                <div class="w-24 h-24 bg-cyan-50 dark:bg-cyan-900/20 rounded-full flex items-center justify-center text-cyan-500 mb-6">
                    <svg class="h-10 w-10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-black text-gray-900 dark:text-white">Aún no hay personas registradas</h3>
                <p class="text-gray-500 dark:text-gray-400 mt-2 max-w-md">Cuando los ciudadanos soliciten citas a través del portal público, sus datos aparecerán automáticamente en este directorio.</p>
            </div>
        @else
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full whitespace-nowrap text-left text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider text-[10px]">
                            <tr>
                                <th class="px-6 py-4">
                                    <a href="{{ route('appointments.visitors.index', $team) }}?{{ http_build_query(array_merge(request()->all(), ['sort_by' => 'first_name', 'sort_dir' => $sortDir === 'asc' ? 'desc' : 'asc']) + ['search' => null]) }}"
                                       class="flex items-center gap-1.5 hover:text-cyan-600 dark:hover:text-cyan-400 transition-colors">
                                        Nombre / DNI
                                        @if($sortBy === 'first_name' || $sortBy === 'last_name')
                                            <svg class="w-3 h-3 {{ $sortDir === 'asc' ? 'rotate-0' : 'rotate-180' }}" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4l-8 8h5v8h6v-8h5z"/></svg>
                                        @else
                                            <svg class="w-3 h-3 opacity-30" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4l-8 8h5v8h6v-8h5z"/></svg>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-6 py-4">
                                    <a href="{{ route('appointments.visitors.index', $team) }}?{{ http_build_query(array_merge(request()->all(), ['sort_by' => 'email', 'sort_dir' => $sortDir === 'asc' ? 'desc' : 'asc']) + ['search' => null]) }}"
                                       class="flex items-center gap-1.5 hover:text-cyan-600 dark:hover:text-cyan-400 transition-colors">
                                        Contacto
                                        @if($sortBy === 'email')
                                            <svg class="w-3 h-3 {{ $sortDir === 'asc' ? 'rotate-0' : 'rotate-180' }}" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4l-8 8h5v8h6v-8h5z"/></svg>
                                        @else
                                            <svg class="w-3 h-3 opacity-30" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4l-8 8h5v8h6v-8h5z"/></svg>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-6 py-4">
                                    <a href="{{ route('appointments.visitors.index', $team) }}?{{ http_build_query(array_merge(request()->all(), ['sort_by' => 'city', 'sort_dir' => $sortDir === 'asc' ? 'desc' : 'asc']) + ['search' => null]) }}"
                                       class="flex items-center gap-1.5 hover:text-cyan-600 dark:hover:text-cyan-400 transition-colors">
                                        Ubicación
                                        @if($sortBy === 'city')
                                            <svg class="w-3 h-3 {{ $sortDir === 'asc' ? 'rotate-0' : 'rotate-180' }}" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4l-8 8h5v8h6v-8h5z"/></svg>
                                        @else
                                            <svg class="w-3 h-3 opacity-30" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4l-8 8h5v8h6v-8h5z"/></svg>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-6 py-4 text-center">
                                    <a href="{{ route('appointments.visitors.index', $team) }}?{{ http_build_query(array_merge(request()->all(), ['sort_by' => 'appointments_count', 'sort_dir' => $sortDir === 'asc' ? 'desc' : 'asc']) + ['search' => null]) }}"
                                       class="flex items-center justify-center gap-1.5 hover:text-cyan-600 dark:hover:text-cyan-400 transition-colors">
                                        Citas
                                        @if($sortBy === 'appointments_count')
                                            <svg class="w-3 h-3 {{ $sortDir === 'asc' ? 'rotate-0' : 'rotate-180' }}" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4l-8 8h5v8h6v-8h5z"/></svg>
                                        @else
                                            <svg class="w-3 h-3 opacity-30" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4l-8 8h5v8h6v-8h5z"/></svg>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-6 py-4 text-right">{{ __('Acciones') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($visitors as $visitor)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/25 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="font-black text-gray-900 dark:text-white">{{ $visitor->full_name }}</div>
                                    @if($visitor->dni)
                                        <div class="text-xs font-mono text-gray-500 mt-0.5">{{ $visitor->dni }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($visitor->email)
                                        <div class="text-xs text-gray-600 dark:text-gray-400 flex items-center gap-1.5 mb-1">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                            <span class="truncate max-w-[150px] sm:max-w-[200px]" title="{{ $visitor->email }}">{{ $visitor->email }}</span>
                                        </div>
                                    @endif
                                    @if($visitor->phone)
                                        <div class="text-xs text-gray-600 dark:text-gray-400 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                            {{ $visitor->phone }}
                                        </div>
                                    @endif
                                    @if(!$visitor->email && !$visitor->phone)
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($visitor->city)
                                        <div class="text-xs text-gray-600 dark:text-gray-400 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                            {{ $visitor->city }} {{ $visitor->postal_code ? '('.$visitor->postal_code.')' : '' }}
                                        </div>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-xs font-bold bg-cyan-50 text-cyan-600 dark:bg-cyan-900/30 dark:text-cyan-400">
                                        {{ $visitor->appointments_count }} citas
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('appointments.visitors.edit', [$team, $visitor]) }}"
                                           class="p-2 text-gray-400 hover:text-cyan-600 dark:hover:text-cyan-400 hover:bg-cyan-50 dark:hover:bg-cyan-900/20 rounded-xl transition-all"
                                           title="Editar datos de persona">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </a>
                                        @if(auth()->user()->is_admin)
                                        <form method="POST" action="{{ route('appointments.visitors.destroy', [$team, $visitor]) }}"
                                              onsubmit="return confirm('¿Eliminar esta persona? Solo es posible si no tiene citas.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-all" title="Eliminar persona">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="mt-6">
                {{ $visitors->links() }}
            </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle filter panel
    const filterToggle = document.getElementById('filterToggle');
    const filterPanel = document.getElementById('filterPanel');
    const filterBadge = document.getElementById('filterBadge');
    
    if (filterToggle && filterPanel) {
        filterToggle.addEventListener('click', function() {
            filterPanel.classList.toggle('hidden');
        });
    }
    
    // Update badge count
    function updateBadge() {
        const params = new URLSearchParams(window.location.search);
        let count = 0;
        if (params.get('filter_name')) count++;
        if (params.get('filter_dni')) count++;
        if (params.get('filter_city')) count++;
        if (params.get('filter_min_appointments')) count++;
        
        if (count > 0) {
            filterBadge.textContent = count;
            filterBadge.classList.remove('hidden');
        } else {
            filterBadge.classList.add('hidden');
        }
    }
    
    updateBadge();
});
</script>
</x-app-layout>
