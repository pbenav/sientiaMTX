<x-app-layout>
    @section('title', 'Expedientes — ' . $team->name)

    <x-slot name="header">
        <div class="flex flex-col xl:flex-row xl:items-start justify-between gap-6">
            <div class="flex items-start gap-4 min-w-0 flex-1">
                <a href="{{ route('teams.dashboard', $team) }}"
                    class="mt-1 p-2.5 bg-gray-50 dark:bg-gray-800/50 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-2xl transition-all shadow-sm border border-gray-100 dark:border-gray-700/50 shrink-0"
                    title="Volver">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div class="min-w-0 flex-1">
                    @include('teams.partials.breadcrumb')
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        Gestión de Expedientes
                    </h1>
                    <x-demo-hint>
                        Los expedientes son contenedores lógicos que agrupan y estructuran conjuntos de tareas, notas y documentos relacionados con un mismo asunto o proyecto. Facilitan la trazabilidad y la gestión documental dentro del equipo.
                    </x-demo-hint>
                </div>
            </div>
            
        </div>

        <!-- View Switcher Sub-Header -->
        <div class="mt-8 mb-4 flex w-full">
            @include('teams.partials.view-switcher')
        </div>

        <!-- Action Buttons Row -->
        <div class="flex items-center gap-3 shrink-0 mt-2 border-t border-gray-100 dark:border-gray-800 pt-3">
            <a href="{{ route('teams.expedientes.create', $team) }}"
                class="flex items-center gap-2 text-xs bg-violet-600 hover:bg-violet-500 text-white px-5 py-2.5 rounded-xl transition-all font-black shadow-lg shadow-violet-500/20 active:scale-95 group">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform group-hover:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                <span>Nuevo Expediente</span>
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        
        <!-- Search & Filters -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm">
            <form action="{{ route('teams.expedientes.index', $team) }}" method="GET" class="flex gap-4">
                <div class="relative flex-1 group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400 group-focus-within:text-violet-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Buscar por título o código..."
                        enterkeyhint="search"
                        class="w-full pl-10 pr-12 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-sm outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 dark:text-white transition-all shadow-sm">
                    <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 text-gray-400 hover:text-violet-600 transition-colors" title="Filtrar">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                </div>
            </form>
        </div>

        <!-- Listing Grid -->
        @if($expedientes->isEmpty())
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-12 text-center flex flex-col items-center justify-center">
                <div class="w-24 h-24 bg-violet-50 dark:bg-violet-900/20 rounded-full flex items-center justify-center text-violet-500 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">No hay expedientes</h3>
                <p class="text-gray-500 dark:text-gray-400 mt-1 mb-6">Comienza creando el primer expediente para agrupar tareas y documentación.</p>
                <a href="{{ route('teams.expedientes.create', $team) }}" class="px-6 py-3 bg-violet-600 hover:bg-violet-700 text-white rounded-xl font-bold shadow-lg transition-all">
                    Crear Primer Expediente
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach($expedientes as $expediente)
                    <a href="{{ route('teams.expedientes.show', [$team, $expediente]) }}" class="group bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-violet-300 dark:hover:border-violet-500/50 rounded-2xl p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md flex flex-col relative overflow-hidden">
                        
                        <!-- Status Bar Header -->
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded-md">
                                    {{ $expediente->code }}
                                </span>
                                @if($expediente->visibility === 'private')
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-rose-50 dark:bg-rose-900/30 text-rose-500 dark:text-rose-400 shadow-sm border border-rose-100 dark:border-rose-800/50" title="Expediente Privado (Acceso Restringido)">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    </span>
                                @endif
                            </div>
                            @php
                                $statusColors = [
                                    'open' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                                    'active' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                    'on_hold' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                    'closed' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400',
                                ];
                                $currentStatusColor = $statusColors[$expediente->status] ?? 'bg-gray-100 text-gray-600';
                            @endphp
                            <span class="text-xs font-bold px-2.5 py-1 rounded-lg {{ $currentStatusColor }}">
                                {{ ucfirst($expediente->status) }}
                            </span>
                        </div>

                        <!-- Title -->
                        <h4 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors leading-tight mb-2">
                            {{ $expediente->title }}
                        </h4>

                        <!-- Description short snippet -->
                        <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2 mb-4 flex-grow">
                            {{ Str::limit($expediente->description, 120) ?? 'Sin descripción.' }}
                        </p>

                        <!-- Footer Metadata -->
                        <div class="mt-auto pt-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-[11px] font-medium text-gray-400 dark:text-gray-500">
                            <div class="flex items-center gap-2">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                <span>{{ $expediente->activities_count }} Actividades</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <img src="{{ $expediente->creator->profile_photo_url ?? 'https://ui-avatars.com/api/?name='.urlencode($expediente->creator->name) }}" class="w-4 h-4 rounded-full border border-white dark:border-gray-800">
                                <span class="truncate max-w-[80px]">{{ explode(' ', $expediente->creator->name)[0] }}</span>
                            </div>
                        </div>

                        <!-- Accent background glow effect on hover -->
                        <div class="absolute -right-6 -top-6 w-24 h-24 bg-violet-500/5 dark:bg-violet-400/5 rounded-full blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
                    </a>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $expedientes->links() }}
            </div>
        @endif

    </div>
</x-app-layout>
