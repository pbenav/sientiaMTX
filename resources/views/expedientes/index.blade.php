<x-app-layout>
    @section('title', 'Expedientes — ' . $team->name)

    <x-slot name="header">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2 min-w-0">
                <a href="{{ route('teams.dashboard', $team) }}"
                    class="p-1.5 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-lg transition-all shrink-0"
                    title="Volver">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                @include('teams.partials.breadcrumb')
                <span class="text-gray-300 dark:text-gray-700 mx-1">/</span>
                <h1 class="text-base font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <span class="truncate">Gestión de Expedientes</span>
                </h1>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @include('teams.partials.header-toolbar')
            </div>
        </div>

        @include('teams.partials.team-view-nav')

        <div class="mt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-t border-gray-100 dark:border-gray-800 pt-3">
            <x-demo-hint>
                Los expedientes son contenedores lógicos que agrupan y estructuran conjuntos de tareas, notas y documentos relacionados con un mismo asunto o proyecto. Facilitan la trazabilidad y la gestión documental dentro del equipo.
            </x-demo-hint>
            
            <div class="shrink-0 self-start sm:self-center">
                <a href="{{ route('teams.expedientes.create', $team) }}"
                    class="flex items-center gap-2 text-xs bg-violet-600 hover:bg-violet-500 text-white px-4 py-2 rounded-xl transition-all font-bold shadow-sm active:scale-95 group">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform group-hover:rotate-90 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="hidden sm:inline">Nuevo Expediente</span>
                </a>
            </div>
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
                            <div class="flex items-center gap-2 relative z-10">
                                <span class="text-xs font-bold px-2.5 py-1 rounded-lg {{ $currentStatusColor }}">
                                    {{ ucfirst($expediente->status) }}
                                </span>
                                @can('delete', $expediente)
                                    <form id="delete-exp-{{ $expediente->id }}" action="{{ route('teams.expedientes.destroy', [$team, $expediente]) }}" method="POST" class="shrink-0" onclick="event.preventDefault(); event.stopPropagation();">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="cascade_delete" id="cascade_delete_{{ $expediente->id }}" value="0">
                                        <button type="button" onclick="confirmDeleteExpediente({{ $expediente->id }})" class="p-1 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/30 rounded-lg transition-colors" title="Eliminar Expediente">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </form>
                                @endcan
                            </div>
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

@push('scripts')
    <script>
        window.confirmDeleteExpediente = function(id) {
            Swal.fire({
                title: '¿{{ __('Eliminar Expediente') }}?',
                html: `
                    <div class="text-left text-sm text-gray-600 dark:text-gray-300 space-y-4">
                        <p>{{ __('Esta acción moverá el expediente a la papelera.') }}</p>
                        <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-100 dark:border-red-900/30">
                            <label class="flex items-start gap-3 cursor-pointer group">
                                <input type="checkbox" id="swal-cascade-delete" class="mt-1 w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-red-600 focus:ring-red-500 bg-white dark:bg-gray-800 transition-colors">
                                <div class="flex-1">
                                    <span class="block text-sm font-bold text-red-700 dark:text-red-400">{{ __('Eliminación en cascada (Profilaxis)') }}</span>
                                    <span class="block text-xs text-red-600/70 dark:text-red-400/70 mt-0.5">{{ __('Eliminará también física y permanentemente TODAS las tareas, notas y archivos adjuntos vinculados. ¡Esta acción física es irreversible y liberará espacio en el servidor!') }}</span>
                                </div>
                            </label>
                        </div>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: '{{ __('Sí, Eliminar') }}',
                cancelButtonText: '{{ __('Cancelar') }}',
                customClass: {
                    popup: 'rounded-[2rem] border-0 shadow-2xl dark:bg-gray-900 dark:text-white p-4',
                    title: 'text-xl font-black text-gray-900 dark:text-white pt-4',
                    confirmButton: 'rounded-xl px-6 py-2.5 font-bold text-xs uppercase tracking-widest shadow-lg shadow-red-500/30 transition-all',
                    cancelButton: 'rounded-xl px-6 py-2.5 font-bold text-xs uppercase tracking-widest transition-all'
                },
                preConfirm: () => {
                    return document.getElementById('swal-cascade-delete').checked;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('cascade_delete_' + id).value = result.value ? '1' : '0';
                    document.getElementById('delete-exp-' + id).submit();
                }
            });
        }
    </script>
@endpush
