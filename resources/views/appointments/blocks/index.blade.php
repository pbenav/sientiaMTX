<x-app-layout maxWidth="[1600px]">
@section('title', 'Bloqueos de Tramos — Citas Previas')

<x-slot name="header">
    <div class="flex items-start gap-4 min-w-0 flex-1">
        <a href="{{ route('appointments.index', $team) }}"
           class="mt-1 p-2.5 bg-gray-50 dark:bg-gray-800/50 text-gray-400 hover:text-cyan-600 dark:hover:text-cyan-400 rounded-2xl transition-all shadow-sm border border-gray-100 dark:border-gray-700/50 shrink-0">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <div class="min-w-0 flex-1">
            @include('teams.partials.breadcrumb')
            <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight flex items-center gap-3">
                <svg class="h-7 w-7 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
                Bloqueos de Urgencia
            </h1>
            <x-demo-hint>
                Esta pantalla permite al equipo cerrar de emergencia tramos horarios en sus agendas (por baja médica repentina, reunión urgente, etc). A diferencia de la configuración normal del horario, un "bloqueo" sobrescribe cualquier disponibilidad e incluso puede cancelar y notificar automáticamente a los ciudadanos que ya tenían una cita programada en ese tramo.
            </x-demo-hint>
        </div>
    </div>
    @include('appointments.partials.nav')
</x-slot>

<div class="py-8">
    <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-8">

        @if(session('success'))
            <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-2xl p-4 text-sm font-bold flex items-center gap-3">
                <svg class="w-5 h-5 shrink-0 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 xl:grid-cols-5 gap-8">

            {{-- Formulario nuevo bloqueo --}}
            <div class="xl:col-span-2">
                <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-100 dark:border-gray-800 bg-red-50/50 dark:bg-red-900/10">
                        <p class="text-xs font-black uppercase tracking-widest text-red-500">🚫 Nuevo Bloqueo</p>
                    </div>
                    <form method="POST" action="{{ route('appointments.blocks.store', $team) }}" class="p-6 space-y-4">
                        @csrf

                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">Servicio afectado</label>
                            <select name="service_id"
                                    class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-red-500 focus:ring focus:ring-red-500/20 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all">
                                <option value="">Todos los servicios</option>
                                @foreach($services as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">Inicio *</label>
                                <input type="datetime-local" name="start_datetime" required
                                       value="{{ old('start_datetime') }}"
                                       class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-red-500 focus:ring focus:ring-red-500/20 rounded-xl px-3 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all">
                                @error('start_datetime') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">Fin *</label>
                                <input type="datetime-local" name="end_datetime" required
                                       value="{{ old('end_datetime') }}"
                                       class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-red-500 focus:ring focus:ring-red-500/20 rounded-xl px-3 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all">
                                @error('end_datetime') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">Motivo del bloqueo</label>
                            <input type="text" name="reason" value="{{ old('reason') }}"
                                   placeholder="Ej: Vacaciones, Baja, Reunión de urgencia..."
                                   class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-red-500 focus:ring focus:ring-red-500/20 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all">
                        </div>

                        <div class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-100 dark:border-red-900">
                            <p class="text-xs font-black text-red-700 dark:text-red-400">Notificar a citas afectadas</p>
                            <label class="relative inline-flex items-center cursor-pointer shrink-0">
                                <input type="hidden" name="notify_affected" value="0">
                                <input type="checkbox" name="notify_affected" value="1" checked class="sr-only peer">
                                <div class="w-10 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-red-500"></div>
                            </label>
                        </div>

                        <button type="submit"
                                class="w-full py-3 text-xs font-black uppercase tracking-widest bg-red-600 hover:bg-red-500 text-white rounded-xl shadow-lg shadow-red-500/20 transition-all active:scale-95">
                            🚫 Crear Bloqueo
                        </button>
                    </form>
                </div>
            </div>

            {{-- Listado bloqueos activos --}}
            <div class="xl:col-span-3">
                <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                        <p class="text-xs font-black uppercase tracking-widest text-gray-400">Bloqueos Activos y Futuros</p>
                        <span class="text-[10px] font-black text-red-600 bg-red-50 dark:bg-red-900/30 dark:text-red-400 px-2.5 py-1 rounded-lg">
                            {{ $blocks->count() }} bloqueos
                        </span>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($blocks as $block)
                            <div class="flex items-start gap-4 p-4 hover:bg-red-50/30 dark:hover:bg-red-900/10 transition-colors group">
                                <div class="shrink-0 w-10 h-10 bg-red-50 dark:bg-red-900/20 rounded-xl flex items-center justify-center text-red-500">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-black text-gray-900 dark:text-white">
                                        {{ $block->reason ?? 'Sin motivo especificado' }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ $block->start_datetime->format('d/m/Y H:i') }} → {{ $block->end_datetime->format('d/m/Y H:i') }}
                                    </p>
                                    @if($block->service)
                                        <span class="text-[9px] font-black text-cyan-700 dark:text-cyan-400 bg-cyan-50 dark:bg-cyan-900/30 px-1.5 py-0.5 rounded mt-1 inline-block">
                                            Solo: {{ $block->service->name }}
                                        </span>
                                    @else
                                        <span class="text-[9px] font-black text-gray-500 bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded mt-1 inline-block">
                                            Todos los servicios
                                        </span>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('appointments.blocks.destroy', [$team, $block]) }}"
                                      onsubmit="return confirm('¿Eliminar este bloqueo?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-all opacity-0 group-hover:opacity-100">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        @empty
                            <div class="p-10 text-center">
                                <p class="text-3xl mb-3">✅</p>
                                <p class="text-sm font-bold text-gray-500 dark:text-gray-400">Sin bloqueos activos</p>
                                <p class="text-xs text-gray-400 mt-1">Tu agenda está completamente disponible</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
