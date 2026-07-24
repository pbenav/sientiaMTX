<x-app-layout maxWidth="max-w-none">
@section('title', 'Editar Bloqueo — Citas Previas')

<x-slot name="header">
    <div class="flex items-center gap-3 flex-wrap">
        <div class="flex items-center gap-2 min-w-0">
            <a href="{{ route('appointments.blocks.index', $team) }}"
                class="p-1.5 text-gray-400 hover:text-cyan-600 dark:hover:text-cyan-400 rounded-lg transition-all shrink-0">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            @include('teams.partials.breadcrumb')
            <span class="text-gray-300 dark:text-gray-700 mx-1">/</span>
            <a href="{{ route('appointments.blocks.index', $team) }}" class="text-sm font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                Bloqueos
            </a>
            <span class="text-gray-300 dark:text-gray-700 mx-1">/</span>
            <h1 class="text-base font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight flex items-center gap-1.5">
                <span class="truncate">Editar Bloqueo</span>
            </h1>
        </div>
    </div>
</x-slot>

<div class="py-8">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-8">
        <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-gray-100 dark:border-gray-800 bg-blue-50/50 dark:bg-blue-900/10">
                <p class="text-xs font-black uppercase tracking-widest text-blue-500">✏️ Editar Bloqueo</p>
            </div>
            <form method="POST" action="{{ route('appointments.blocks.update', [$team, $block]) }}" class="p-6 space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">Servicio afectado</label>
                    <select name="service_id"
                            class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-blue-500 focus:ring focus:ring-blue-500/20 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all">
                        <option value="">Todos los servicios</option>
                        @foreach($services as $s)
                            <option value="{{ $s->id }}" {{ (old('service_id', $block->service_id) == $s->id) ? 'selected' : '' }}>
                                {{ $s->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('service_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">Inicio *</label>
                        <input type="datetime-local" name="start_datetime" required
                               value="{{ old('start_datetime', $block->start_datetime->format('Y-m-d\TH:i')) }}"
                               class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-blue-500 focus:ring focus:ring-blue-500/20 rounded-xl px-3 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all">
                        @error('start_datetime') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">Fin *</label>
                        <input type="datetime-local" name="end_datetime" required
                               value="{{ old('end_datetime', $block->end_datetime->format('Y-m-d\TH:i')) }}"
                               class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-blue-500 focus:ring focus:ring-blue-500/20 rounded-xl px-3 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all">
                        @error('end_datetime') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">Motivo del bloqueo</label>
                    <input type="text" name="reason" value="{{ old('reason', $block->reason) }}"
                           placeholder="Ej: Vacaciones, Baja, Reunión de urgencia..."
                           class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-blue-500 focus:ring focus:ring-blue-500/20 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all">
                    @error('reason') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-900">
                    <div>
                        <p class="text-xs font-black text-blue-700 dark:text-blue-400">Notificar a citas afectadas</p>
                        <p class="text-[10px] text-blue-600/70 mt-0.5">Si se han visto afectadas nuevas citas por el cambio de horario, se cancelarán.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0">
                        <input type="hidden" name="notify_affected" value="0">
                        <input type="checkbox" name="notify_affected" value="1" {{ old('notify_affected', 1) ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-10 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-500"></div>
                    </label>
                </div>

                <div class="pt-2 flex justify-end gap-3">
                    <a href="{{ route('appointments.blocks.index', $team) }}"
                       class="px-5 py-3 text-xs font-black uppercase tracking-widest bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-xl transition-all">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="px-6 py-3 text-xs font-black uppercase tracking-widest bg-blue-600 hover:bg-blue-500 text-white rounded-xl shadow-lg shadow-blue-500/20 transition-all active:scale-95">
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>
