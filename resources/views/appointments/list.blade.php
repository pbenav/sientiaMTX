<x-app-layout maxWidth="[1600px]">
@section('title', 'Lista de Citas Previas')

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
                <svg class="h-7 w-7 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Todas las Citas
            </h1>
            <x-demo-hint>
                Este <strong>listado maestro</strong> ofrece una vista tabular avanzada con todos los expedientes de citas. Permite filtrar por fechas, servicios o estados, realizar aprobaciones, borrar citas permanentemente o iniciar videocitas directamente desde la tabla.
            </x-demo-hint>
        </div>
    </div>
    @include('appointments.partials.nav')
</x-slot>

<div class="py-8">
    <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">

        @if(session('success'))
            <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-2xl p-4 text-sm font-bold flex items-center gap-3">
                <svg class="w-5 h-5 shrink-0 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- Filtros --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm">
            <form method="GET" action="{{ route('appointments.list', $team) }}" class="flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1.5">Buscador</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Localizador, nombre, DNI..."
                           class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 rounded-xl px-3 py-2 text-sm text-gray-900 dark:text-white outline-none transition-all">
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1.5">Estado</label>
                    <select name="status" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 rounded-xl px-3 py-2 text-sm text-gray-900 dark:text-white outline-none transition-all">
                        <option value="">Todos</option>
                        @foreach(\App\Models\Appointment::STATUSES as $val => $label)
                            <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1.5">Servicio</label>
                    <select name="service_id" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 rounded-xl px-3 py-2 text-sm text-gray-900 dark:text-white outline-none transition-all">
                        <option value="">Todos</option>
                        @foreach($services as $s)
                            <option value="{{ $s->id }}" {{ request('service_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1.5">Desde</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 rounded-xl px-3 py-2 text-sm text-gray-900 dark:text-white outline-none transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1.5">Hasta</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 rounded-xl px-3 py-2 text-sm text-gray-900 dark:text-white outline-none transition-all">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-black rounded-xl transition-all">Filtrar</button>
                    <a href="{{ route('appointments.list', $team) }}" class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 text-xs font-black rounded-xl transition-all hover:bg-gray-200 dark:hover:bg-gray-700">Limpiar</a>
                </div>
            </form>
        </div>

        {{-- Tabla --}}
        <div x-data="{ count: 0, updateCount() { this.count = document.querySelectorAll('.bulk-cb:checked').length; }, toggleAll(e) { document.querySelectorAll('.bulk-cb').forEach(cb => cb.checked = e.target.checked); this.updateCount(); } }" 
             class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl shadow-sm overflow-hidden">
            @if($appointments->isEmpty())
                <div class="p-16 text-center">
                    <p class="text-5xl mb-4">📭</p>
                    <p class="text-lg font-black text-gray-900 dark:text-white">Sin citas</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">No hay citas que coincidan con los filtros seleccionados.</p>
                </div>
            @else
                <form method="POST" action="{{ route('appointments.bulk', $team) }}" id="bulk-form" class="hidden">
                    @csrf
                </form>

                <div class="bg-gray-50/50 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-800 px-5 py-3 flex items-center justify-between transition-all" x-show="count > 0" style="display: none;" x-cloak>
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400"><span x-text="count"></span> seleccionadas</span>
                    <div class="flex gap-2">
                        <button type="submit" name="bulk_action" value="complete" form="bulk-form" class="px-3 py-1.5 text-xs font-black bg-violet-50 text-violet-700 hover:bg-violet-100 dark:bg-violet-900/30 dark:text-violet-400 rounded-lg transition-colors">Completar</button>
                        <button type="button" onclick="confirmBulkCancel()" class="px-3 py-1.5 text-xs font-black bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-900/30 dark:text-amber-400 rounded-lg transition-colors">Cancelar</button>
                        <button type="button" onclick="confirmBulkDelete()" class="px-3 py-1.5 text-xs font-black bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-400 rounded-lg transition-colors">Borrar</button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left border-b border-gray-100 dark:border-gray-800">
                                <th class="px-5 py-4 w-12 text-center">
                                    <input type="checkbox" class="w-4 h-4 rounded border-gray-300 text-cyan-600 focus:ring-cyan-500 dark:bg-gray-800 dark:border-gray-600" @change="toggleAll">
                                </th>
                                @php
                                    $sort_by = request('sort_by', 'appointment_date');
                                    $sort_dir = request('sort_dir', 'desc');
                                    $next_dir = $sort_dir === 'asc' ? 'desc' : 'asc';
                                @endphp
                                <th class="px-5 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'localizador', 'sort_dir' => $sort_by === 'localizador' ? $next_dir : 'asc']) }}" class="hover:text-cyan-500 flex items-center gap-1">
                                        Localizador @if($sort_by === 'localizador') {!! $sort_dir === 'asc' ? '↑' : '↓' !!} @endif
                                    </a>
                                </th>
                                <th class="px-5 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'appointment_date', 'sort_dir' => $sort_by === 'appointment_date' ? $next_dir : 'desc']) }}" class="hover:text-cyan-500 flex items-center gap-1">
                                        Fecha @if($sort_by === 'appointment_date') {!! $sort_dir === 'asc' ? '↑' : '↓' !!} @endif
                                    </a>
                                </th>
                                <th class="px-5 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'visitor', 'sort_dir' => $sort_by === 'visitor' ? $next_dir : 'asc']) }}" class="hover:text-cyan-500 flex items-center gap-1">
                                        Ciudadano @if($sort_by === 'visitor') {!! $sort_dir === 'asc' ? '↑' : '↓' !!} @endif
                                    </a>
                                </th>
                                <th class="px-5 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'service', 'sort_dir' => $sort_by === 'service' ? $next_dir : 'asc']) }}" class="hover:text-cyan-500 flex items-center gap-1">
                                        Servicio @if($sort_by === 'service') {!! $sort_dir === 'asc' ? '↑' : '↓' !!} @endif
                                    </a>
                                </th>
                                <th class="px-5 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'status', 'sort_dir' => $sort_by === 'status' ? $next_dir : 'asc']) }}" class="hover:text-cyan-500 flex items-center gap-1">
                                        Estado @if($sort_by === 'status') {!! $sort_dir === 'asc' ? '↑' : '↓' !!} @endif
                                    </a>
                                </th>
                                <th class="px-5 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-center">Completada</th>
                                <th class="px-5 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($appointments as $cita)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors group">
                                    <td class="px-5 py-3.5 text-center">
                                        <input type="checkbox" name="appointment_ids[]" value="{{ $cita->id }}" form="bulk-form" class="bulk-cb w-4 h-4 rounded border-gray-300 text-cyan-600 focus:ring-cyan-500 dark:bg-gray-800 dark:border-gray-600" @change="updateCount()">
                                    </td>
                                    <td class="px-5 py-3.5 font-mono text-xs font-bold text-gray-600 dark:text-gray-400">
                                        {{ $cita->localizador }}
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $cita->appointment_date->format('d/m/Y') }}</p>
                                        <p class="text-xs text-cyan-600 dark:text-cyan-400 font-bold">{{ substr($cita->appointment_time, 0, 5) }}</p>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $cita->visitor->full_name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $cita->visitor->email }}</p>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <div class="flex flex-col">
                                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $cita->service->name }}</span>
                                            @if(in_array($cita->modality, ['jitsi', 'meet']))
                                                <span class="inline-flex items-center gap-1 text-[9px] font-black text-indigo-600 dark:text-indigo-400 uppercase mt-0.5 tracking-wider">
                                                    💻 Videocita ({{ ucfirst($cita->modality) }})
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 text-[9px] font-black text-emerald-600 dark:text-emerald-400 uppercase mt-0.5 tracking-wider">
                                                    🏢 Presencial
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <span class="text-[9px] font-black uppercase px-2.5 py-1 rounded-lg
                                            @if($cita->status === 'confirmed') bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400
                                            @elseif($cita->status === 'cancelled') bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400
                                            @elseif($cita->status === 'completed') bg-violet-50 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400
                                            @elseif($cita->status === 'blocked') bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400
                                            @else bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 @endif">
                                            {{ $cita->status_label }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        @if(!in_array($cita->status, ['cancelled', 'blocked']))
                                            <form method="POST" action="{{ route('appointments.update', [$team, $cita]) }}" x-data class="inline-block">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" x-ref="statusInput" value="{{ $cita->status }}">
                                                <label class="relative inline-flex items-center cursor-pointer">
                                                    <input type="checkbox" class="sr-only peer" {{ $cita->status === 'completed' ? 'checked' : '' }}
                                                           @change="$refs.statusInput.value = $el.checked ? 'completed' : 'confirmed'; $el.closest('form').submit()">
                                                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-violet-500"></div>
                                                </label>
                                            </form>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            @if($cita->status === 'confirmed' && in_array($cita->modality, ['jitsi', 'meet']))
                                                <a href="{{ route('public.appointments.video.auth', $cita) }}?localizador={{ $cita->localizador }}" target="_blank"
                                                   class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:hover:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-[10px] font-black uppercase rounded-lg border border-indigo-150/40 dark:border-indigo-900/30 transition-all select-none">
                                                    💻 Iniciar
                                                </a>
                                            @endif
                                            <a href="{{ route('appointments.show', [$team, $cita]) }}"
                                               class="text-xs font-black text-cyan-600 dark:text-cyan-400 hover:underline">
                                                Ver →
                                            </a>
                                            <form id="delete-appointment-{{ $cita->id }}" method="POST" action="{{ route('appointments.forceDestroy', [$team, $cita]) }}" class="inline-block">
                                                @csrf @method('DELETE')
                                                <button type="button" onclick="confirmSingleDelete(event, '{{ $cita->id }}')" class="p-1 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors" title="Borrar Permanente">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-800">
                    {{ $appointments->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    function confirmSingleDelete(event, id) {
        event.preventDefault();
        const form = document.getElementById(`delete-appointment-${id}`);
        Swal.fire({
            title: '¿Eliminar físicamente esta cita?',
            text: '⚠️ Esta acción no se puede deshacer y borrará la cita de la base de datos por completo, junto con su tarea asociada. Indique a continuación el motivo de la anulación definitiva para el correo del ciudadano (opcional).',
            input: 'textarea',
            inputPlaceholder: 'Escriba el motivo de la eliminación definitiva aquí...',
            icon: 'error',
            showCancelButton: true,
            confirmButtonText: 'Sí, borrar',
            cancelButtonText: 'Cancelar',
            customClass: {
                popup: 'rounded-[2rem] dark:bg-gray-900 border border-gray-150 dark:border-gray-800 p-8',
                confirmButton: 'px-5 py-3 text-xs font-black uppercase tracking-widest bg-red-600 hover:bg-red-700 text-white rounded-xl transition-all shadow-md',
                cancelButton: 'px-5 py-3 text-xs font-black uppercase tracking-widest bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-all ml-3',
                input: 'bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 rounded-2xl text-xs font-bold text-gray-900 dark:text-white outline-none focus:ring-2 focus:ring-cyan-500/20 !mx-auto !w-[90%] !my-4 h-24 p-3'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                const reasonInput = document.createElement('input');
                reasonInput.type = 'hidden';
                reasonInput.name = 'cancellation_reason';
                reasonInput.value = result.value || '';
                form.appendChild(reasonInput);
                form.submit();
            }
        });
    }

    function confirmBulkDelete() {
        Swal.fire({
            title: '¿Eliminar permanentemente las seleccionadas?',
            text: '⚠️ ATENCIÓN: Esta acción no se puede deshacer. Se borrarán todas las citas seleccionadas de la base de datos por completo. Indique a continuación el motivo de la anulación definitiva para el correo de los ciudadanos (opcional).',
            input: 'textarea',
            inputPlaceholder: 'Escriba el motivo de la eliminación definitiva aquí...',
            icon: 'error',
            showCancelButton: true,
            confirmButtonText: 'Sí, borrar todas',
            cancelButtonText: 'Cancelar',
            customClass: {
                popup: 'rounded-[2rem] dark:bg-gray-900 border border-gray-150 dark:border-gray-800 p-8',
                confirmButton: 'px-5 py-3 text-xs font-black uppercase tracking-widest bg-red-600 hover:bg-red-700 text-white rounded-xl transition-all shadow-md',
                cancelButton: 'px-5 py-3 text-xs font-black uppercase tracking-widest bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-all ml-3',
                input: 'bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 rounded-2xl text-xs font-bold text-gray-900 dark:text-white outline-none focus:ring-2 focus:ring-cyan-500/20 !mx-auto !w-[90%] !my-4 h-24 p-3'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('bulk-form');
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'bulk_action';
                actionInput.value = 'delete';
                form.appendChild(actionInput);

                const reasonInput = document.createElement('input');
                reasonInput.type = 'hidden';
                reasonInput.name = 'cancellation_reason';
                reasonInput.value = result.value || '';
                form.appendChild(reasonInput);
                document.querySelectorAll('.bulk-cb:checked').forEach(cb => {
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'appointment_ids[]';
                    idInput.value = cb.value;
                    form.appendChild(idInput);
                });

                form.submit();
            }
        });
    }

    function confirmBulkCancel() {
        Swal.fire({
            title: '¿Cancelar las citas seleccionadas?',
            text: 'Indique a continuación el motivo de la cancelación masiva (opcional). Los ciudadanos correspondientes recibirán esta información por email si prestaron su consentimiento.',
            input: 'textarea',
            inputPlaceholder: 'Escriba el motivo de la cancelación aquí...',
            showCancelButton: true,
            confirmButtonText: 'Sí, cancelar citas',
            cancelButtonText: 'No, mantener',
            customClass: {
                popup: 'rounded-[2rem] dark:bg-gray-900 border border-gray-150 dark:border-gray-800 p-8',
                confirmButton: 'px-5 py-3 text-xs font-black uppercase tracking-widest bg-amber-500 hover:bg-amber-600 text-white rounded-xl transition-all shadow-md',
                cancelButton: 'px-5 py-3 text-xs font-black uppercase tracking-widest bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-all ml-3',
                input: 'bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 rounded-2xl text-xs font-bold text-gray-900 dark:text-white outline-none focus:ring-2 focus:ring-cyan-500/20 !mx-auto !w-[90%] !my-4 h-24 p-3'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('bulk-form');
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'bulk_action';
                actionInput.value = 'cancel';
                form.appendChild(actionInput);

                const reasonInput = document.createElement('input');
                reasonInput.type = 'hidden';
                reasonInput.name = 'cancellation_reason';
                reasonInput.value = result.value || '';
                form.appendChild(reasonInput);
                
                document.querySelectorAll('.bulk-cb:checked').forEach(cb => {
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'appointment_ids[]';
                    idInput.value = cb.value;
                    form.appendChild(idInput);
                });

                form.submit();
            }
        });
    }
</script>
</x-app-layout>


