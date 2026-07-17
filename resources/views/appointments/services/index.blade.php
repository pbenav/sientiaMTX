<x-app-layout maxWidth="max-w-none">
@section('title', 'Mis Servicios de Cita Previa')

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
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <span class="truncate">Mis Servicios</span>
            </h1>
        </div>
    </div>
    <div class="mt-2 border-t border-gray-100 dark:border-gray-800 pt-3">
        <x-demo-hint>
            En esta sección configuras el <strong>catálogo de servicios</strong> que se ofrecerá al ciudadano. Puedes definir la duración, si son de pago y descripciones detalladas. Cada servicio configurado y activo aparecerá de forma automática en el portal de solicitud pública de citas.
        </x-demo-hint>
    </div>
    <!-- Sub-Menú de Navegación -->
    @include('appointments.partials.nav')

    <!-- Action Buttons Row -->
    <div class="flex items-center gap-3 shrink-0 mt-2 border-t border-gray-100 dark:border-gray-800 pt-3">
        <a href="{{ route('appointments.services.create', $team) }}"
           class="flex items-center gap-2 text-xs bg-cyan-600 hover:bg-cyan-500 text-white px-5 py-2.5 rounded-xl transition-all font-black shadow-lg shadow-cyan-500/20 active:scale-95 group shrink-0">
            <svg class="h-4 w-4 transition-transform group-hover:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            <span>Nuevo Servicio</span>
        </a>
    </div>
</x-slot>

<div class="py-8">
    <div class="max-w-full mx-auto sm:px-6 lg:px-8">

        @if(session('success'))
            <div class="mb-6 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-2xl p-4 text-sm font-bold flex items-center gap-3">
                <svg class="w-5 h-5 shrink-0 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                {{ session('success') }}
            </div>
        @endif

        @if($services->isEmpty())
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-16 text-center flex flex-col items-center">
                <div class="w-24 h-24 bg-cyan-50 dark:bg-cyan-900/20 rounded-full flex items-center justify-center text-cyan-500 mb-6">
                    <svg class="h-10 w-10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <h3 class="text-xl font-black text-gray-900 dark:text-white">Sin servicios definidos</h3>
                <p class="text-gray-500 dark:text-gray-400 mt-2 mb-8 max-w-md">Crea los servicios que ofreces para que los ciudadanos puedan solicitar cita. Cada servicio tendrá su propia duración, descripción y disponibilidad.</p>
                <a href="{{ route('appointments.services.create', $team) }}" class="px-8 py-3 bg-cyan-600 hover:bg-cyan-500 text-white rounded-2xl font-black shadow-lg shadow-cyan-500/20 transition-all active:scale-95">
                    Crear Primer Servicio
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                @foreach($services as $service)
                    <div class="group bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-cyan-300 dark:hover:border-cyan-500/50 rounded-2xl p-5 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md relative overflow-hidden">
                        <div class="absolute -right-6 -top-6 w-24 h-24 bg-cyan-500/5 rounded-full blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>

                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-base font-black text-gray-900 dark:text-white truncate">{{ $service->name }}</h3>
                                @if($service->team)
                                    <p class="text-[9px] font-black uppercase text-violet-600 dark:text-violet-400 tracking-wider mt-0.5">{{ $service->team->name }}</p>
                                @endif
                                <div class="flex items-center gap-3 mt-1.5 flex-wrap">
                                    <span class="text-[10px] font-black text-cyan-700 dark:text-cyan-400 bg-cyan-50 dark:bg-cyan-900/30 px-2 py-0.5 rounded-md">
                                        ⏱ {{ $service->duration_minutes }}min
                                    </span>
                                    @if($service->price !== null)
                                        <span class="text-[10px] font-black text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 px-2 py-0.5 rounded-md">
                                            {{ $service->price > 0 ? '€'.$service->price : 'Gratuito' }}
                                        </span>
                                    @endif
                                    <span class="text-[10px] font-black px-2 py-0.5 rounded-md
                                        {{ $service->is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400' }}">
                                        {{ $service->is_active ? '● Activo' : '● Inactivo' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        @if($service->description)
                            <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2 mb-4">{{ Str::limit($service->description, 120) }}</p>
                        @else
                            <p class="text-xs text-gray-400 italic mb-4">Sin descripción</p>
                        @endif

                        <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                            <div class="text-[10px] text-gray-400 font-medium flex items-center gap-3">
                                <span>{{ $service->appointments()->whereNotIn('status', ['cancelled'])->count() }} citas activas</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('appointments.services.edit', [$team, $service]) }}"
                                   class="p-2 text-gray-400 hover:text-cyan-600 dark:hover:text-cyan-400 hover:bg-cyan-50 dark:hover:bg-cyan-900/20 rounded-xl transition-all"
                                   title="Editar servicio">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form method="POST" action="{{ route('appointments.services.destroy', [$team, $service]) }}"
                                      onsubmit="return confirm('¿Eliminar este servicio? Solo se puede si no tiene citas activas.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-all" title="Eliminar servicio">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
</x-app-layout>
