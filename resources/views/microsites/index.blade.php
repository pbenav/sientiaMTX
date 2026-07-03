<x-app-layout>
    @section('title', 'Gestión de Micrositios')

    <x-slot name="header">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2 min-w-0">
                <a href="{{ route('teams.dashboard', $team) }}"
                    class="p-1.5 text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 rounded-lg transition-all shrink-0"
                    title="{{ __('navigation.back') ?? 'Volver' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                @include('teams.partials.breadcrumb')
                <span class="text-gray-300 dark:text-gray-700 mx-1">/</span>
                <h1 class="text-base font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-pink-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                    </svg>
                    <span class="truncate">Tus Micrositios</span>
                </h1>
            </div>
            
            <div class="flex items-center gap-2 shrink-0">
                @include('teams.partials.header-toolbar')
            </div>
        </div>

        @include('partials.cross-module-nav')
    </x-slot>

    <div class="space-y-6" x-data="{ search: '' }">
            
            <!-- Action Buttons for Microsites -->
            <div class="flex flex-wrap items-center gap-3 pt-2">
                <a href="{{ route('teams.microsites.create', $team) }}" class="flex items-center gap-2 text-xs bg-pink-600 hover:bg-pink-500 text-white px-5 py-2.5 rounded-xl transition-all font-black shadow-lg shadow-pink-500/20 active:scale-95 group">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform group-hover:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Crear Micrositio</span>
                </a>

                <a href="{{ route('public.microsites.directory') }}" target="_blank" class="flex items-center gap-1.5 text-xs bg-white dark:bg-white/5 border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300 px-4 py-2.5 rounded-xl transition-all font-bold hover:bg-gray-50 dark:hover:bg-white/10 active:scale-95 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-pink-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    <span>Directorio Público</span>
                </a>
            </div>

            <!-- Filters and Search Bar -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm">
                <div class="flex gap-4">
                    <div class="relative flex-1 group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400 group-focus-within:text-pink-500 transition-colors"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" x-model="search"
                            placeholder="{{ __('Buscar por título o ruta...') }}" enterkeyhint="search"
                            :class="search !== '' ?
                                'bg-pink-50/50 dark:bg-pink-900/10 border-pink-300 dark:border-pink-800 ring-2 ring-pink-500/20' :
                                'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700'"
                            class="w-full pl-10 pr-12 py-2.5 border rounded-xl text-sm outline-none focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500 dark:text-white transition-all shadow-sm">
                        <button x-show="search !== ''" @click="search = ''"
                            class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 text-gray-400 hover:text-red-500 transition-colors"
                            title="{{ __('Limpiar Filtros') }}" x-cloak>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            @if($microsites->isEmpty())
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-12 text-center shadow-sm">
                    <div class="w-20 h-20 mx-auto bg-pink-50 dark:bg-pink-900/20 rounded-full flex items-center justify-center mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-pink-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Sin Micrositios Aún</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-8 max-w-md mx-auto">
                        Crea tu primera página web personalizada para compartir información, eventos o material con el público.
                    </p>
                    <a href="{{ route('teams.microsites.create', $team) }}" class="inline-flex items-center gap-2 px-6 py-3 bg-pink-600 hover:bg-pink-700 text-white font-bold rounded-xl transition-all shadow-lg shadow-pink-500/20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Crear mi primer Micrositio
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($microsites as $microsite)
                        <div x-show="search === '' || '{{ strtolower($microsite->title) }}'.includes(search.toLowerCase()) || '{{ strtolower($microsite->slug) }}'.includes(search.toLowerCase())"
                            class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl overflow-hidden shadow-sm hover:shadow-lg transition-all group flex flex-col h-full">
                            
                            <!-- Header de la tarjeta -->
                            <div class="p-6 pb-4 border-b border-gray-100 dark:border-gray-800/60 relative">
                                <!-- Estado -->
                                <div class="absolute top-6 right-6">
                                    @if($microsite->is_published)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-[10px] font-black uppercase tracking-wider rounded-lg border border-emerald-100 dark:border-emerald-500/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Público
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-[10px] font-black uppercase tracking-wider rounded-lg border border-gray-200 dark:border-gray-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                            Oculto
                                        </span>
                                    @endif
                                </div>

                                <div class="w-12 h-12 bg-pink-50 dark:bg-pink-500/10 rounded-2xl flex items-center justify-center text-pink-600 dark:text-pink-400 mb-4 border border-pink-100 dark:border-pink-500/20">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white line-clamp-1 mb-1" title="{{ $microsite->title }}">
                                    {{ $microsite->title }}
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 font-mono bg-gray-50 dark:bg-gray-800/50 p-1.5 rounded-lg inline-block break-all max-w-full truncate">
                                    /p/{{ $microsite->slug }}
                                </p>
                            </div>

                            <!-- Metadatos -->
                            <div class="px-6 py-4 flex-1 flex flex-col gap-3">
                                @if($microsite->city)
                                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span class="truncate">{{ $microsite->city }}</span>
                                    </div>
                                @endif
                                
                                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <span>{{ number_format($microsite->views) }} visitas</span>
                                </div>

                                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mt-auto pt-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Act.: {{ $microsite->updated_at->diffForHumans() }}</span>
                                </div>
                            </div>

                            <!-- Acciones -->
                            <div class="p-4 border-t border-gray-100 dark:border-gray-800/60 bg-gray-50/50 dark:bg-gray-800/20 flex items-center justify-between">
                                <a href="{{ route('teams.microsites.edit', [$team, $microsite]) }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-bold text-gray-700 dark:text-gray-300 hover:text-pink-600 dark:hover:text-pink-400 bg-white dark:bg-gray-800 hover:bg-pink-50 dark:hover:bg-pink-900/20 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-pink-200 dark:hover:border-pink-800 transition-all shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Editar
                                </a>

                                <div class="flex items-center gap-2">
                                    <a href="{{ route('public.microsites.show', $microsite->slug) }}" target="_blank" class="p-2 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-colors" title="Ver micrositio">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                    
                                    <form action="{{ route('teams.microsites.destroy', [$team, $microsite]) }}" method="POST" class="inline" id="delete-form-{{ $microsite->id }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" onclick="confirmDelete({{ $microsite->id }}, '{{ addslashes($microsite->title) }}')" class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors" title="Eliminar">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <div class="mt-6">
                    {{ $microsites->links() }}
                </div>
            @endif

    </div>

    @push('scripts')
    <script>
        function confirmDelete(id, title) {
            Swal.fire({
                title: '¿Eliminar Micrositio?',
                text: `Estás a punto de eliminar "${title}". Esta acción no se puede deshacer.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                background: document.documentElement.classList.contains('dark') ? '#111827' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#ffffff' : '#111827',
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + id).submit();
                }
            });
        }
    </script>
    @endpush
</x-app-layout>
