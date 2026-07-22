<x-app-layout maxWidth="max-w-none">
@section('title', 'Lista de Citas Previas')

<x-slot name="header">
    <div class="flex items-center gap-3 flex-wrap">
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
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <span class="truncate">Todas las Citas</span>
            </h1>
        </div>
    </div>
    
    <div class="mt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-t border-gray-100 dark:border-gray-800 pt-3">
        <x-demo-hint>
            Este <strong>listado maestro</strong> ofrece una vista tabular avanzada con todos los expedientes de citas. Permite filtrar por fechas, servicios o estados, realizar aprobaciones, borrar citas permanentemente o iniciar videocitas directamente desde la tabla.
        </x-demo-hint>
    </div>
    @include('appointments.partials.nav')
</x-slot>

<div class="py-8">
    <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">

        {{-- Banner de Contador Activo --}}
        @include('partials.active-timer-banner')

        @if(session('success'))
            <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-2xl p-4 text-sm font-bold flex items-center gap-3">
                <svg class="w-5 h-5 shrink-0 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- Resumen del periodo filtrado --}}
        <div class="flex flex-wrap items-center gap-x-6 gap-y-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm text-sm">
            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Periodo:</span>
            
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="flex flex-col leading-none">
                    <span class="font-black text-gray-900 dark:text-white">{{ $periodStats['pending'] ?? 0 }}</span>
                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mt-0.5">Previstas</span>
                </div>
            </div>

            <div class="hidden sm:block w-px h-6 bg-gray-200 dark:bg-gray-800"></div>

            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-cyan-50 dark:bg-cyan-900/30 text-cyan-600 dark:text-cyan-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <div class="flex flex-col leading-none">
                    <span class="font-black text-gray-900 dark:text-white">{{ $periodStats['confirmed'] ?? 0 }}</span>
                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mt-0.5">Confirmadas</span>
                </div>
            </div>

            <div class="hidden sm:block w-px h-6 bg-gray-200 dark:bg-gray-800"></div>

            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-violet-50 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="flex flex-col leading-none">
                    <span class="font-black text-gray-900 dark:text-white">{{ $periodStats['completed'] ?? 0 }}</span>
                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mt-0.5">Completadas</span>
                </div>
            </div>

            <div class="hidden sm:block w-px h-6 bg-gray-200 dark:bg-gray-800"></div>

            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                </div>
                <div class="flex flex-col leading-none">
                    <span class="font-black text-gray-900 dark:text-white">{{ $periodStats['cancelled'] ?? 0 }}</span>
                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mt-0.5">Canceladas</span>
                </div>
            </div>

            <div class="hidden xl:block w-px h-6 bg-gray-200 dark:bg-gray-800 mx-2"></div>
            
            @php
                $totalList = ($periodStats['pending'] ?? 0) + ($periodStats['confirmed'] ?? 0) + ($periodStats['completed'] ?? 0) + ($periodStats['cancelled'] ?? 0);
                $effectivenessList = $totalList > 0 ? round((($periodStats['completed'] ?? 0) / $totalList) * 100) : 0;
            @endphp
            @if($totalList > 0)
            <div class="flex items-center gap-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Éxito:</span>
                <div class="w-16 h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                    <div class="h-full bg-violet-500 rounded-full" style="width: {{ $effectivenessList }}%"></div>
                </div>
                <span class="text-[10px] font-bold text-violet-600 dark:text-violet-400">{{ $effectivenessList }}%</span>
            </div>
            @endif

            <div class="hidden xl:block w-px h-6 bg-gray-200 dark:bg-gray-800 mx-2"></div>

            <div class="flex items-center gap-5 xl:ml-auto">
                <span class="text-[10px] font-black uppercase tracking-widest text-gray-400" title="Calculado sobre citas completadas con tiempo registrado en el periodo filtrado">⏱️ Tiempos (Periodo):</span>
                
                <div class="flex items-center gap-2.5">
                    <div class="flex flex-col leading-none">
                        <span class="font-black text-gray-700 dark:text-gray-300 tabular-nums">{{ floor(($statsDuration['min'] ?? 0) / 60) }}m</span>
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mt-0.5">Min</span>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <div class="flex flex-col leading-none">
                        <span class="font-black text-indigo-600 dark:text-indigo-400 tabular-nums">{{ floor(($statsDuration['avg'] ?? 0) / 60) }}m</span>
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mt-0.5">Media</span>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <div class="flex flex-col leading-none">
                        <span class="font-black text-gray-700 dark:text-gray-300 tabular-nums">{{ floor(($statsDuration['mode'] ?? 0) / 60) }}m</span>
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mt-0.5">Moda</span>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <div class="flex flex-col leading-none">
                        <span class="font-black text-gray-700 dark:text-gray-300 tabular-nums">{{ floor(($statsDuration['max'] ?? 0) / 60) }}m</span>
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mt-0.5">Max</span>
                    </div>
                </div>
            </div>
        </div>

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
                    <a href="{{ route('appointments.list', $team) }}?clear=1" class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 text-xs font-black rounded-xl transition-all hover:bg-gray-200 dark:hover:bg-gray-700">Limpiar</a>
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
                        <button type="button" onclick="confirmBulkNoShow()" class="px-3 py-1.5 text-xs font-black bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700/50 dark:text-gray-300 rounded-lg transition-colors">No presentado</button>
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
                                    $sort_by = request('sort_by', 'created_at');
                                    $sort_dir = request('sort_dir', 'asc');
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
                                <th class="px-5 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-center">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'time', 'sort_dir' => $sort_by === 'time' ? $next_dir : 'desc']) }}" class="hover:text-cyan-500 flex items-center justify-center gap-1">
                                        Tiempo @if($sort_by === 'time') {!! $sort_dir === 'asc' ? '↑' : '↓' !!} @endif
                                    </a>
                                </th>
                                <th class="px-5 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-center">Completada</th>
                                <th class="px-5 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($appointments as $cita)
                                @php
                                    $userActiveLog = auth()->user()?->activeTaskLog();
                                    $isActiveTimerCita = $userActiveLog && (
                                        ($cita->activity_id && $userActiveLog->task_id == $cita->activity_id) ||
                                        ($cita->task_id && $userActiveLog->task_id == $cita->task_id)
                                    );
                                @endphp
                                <tr onclick="if(!event.target.closest('input, button, a, label, form')) window.location.href='{{ route('appointments.show', [$team, $cita]) }}'"
                                    class="{{ $isActiveTimerCita ? 'bg-amber-50/70 dark:bg-amber-900/30 border-l-4 border-amber-500' : 'hover:bg-gray-50 dark:hover:bg-gray-800/50' }} transition-colors group cursor-pointer">
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
                                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                                                {{ $cita->service->name }}
                                                @if($isActiveTimerCita)
                                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-black bg-amber-500 text-white animate-pulse">⏱️ En curso</span>
                                                @endif
                                            </span>
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
                                            @elseif($cita->status === 'no_show') bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400
                                            @elseif($cita->status === 'blocked') bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400
                                            @else bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 @endif">
                                            {{ $cita->status_label }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        @php
                                            $seconds = 0;
                                            if ($cita->activity) $seconds = $cita->activity->totalTrackedSeconds();
                                            elseif ($cita->task) $seconds = $cita->task->totalTrackedSeconds();
                                        @endphp
                                        @if($isActiveTimerCita)
                                            <div class="flex flex-col items-center gap-1">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-500 text-white animate-pulse shadow-sm">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span>
                                                    En Curso
                                                </span>
                                                @if($cita->activity_id || $cita->task_id)
                                                    <button type="button" 
                                                        onclick="event.stopPropagation(); fetch('{{ route('time-logs.toggle-task', $cita->activity_id ?? $cita->task_id) }}', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(r => r.json()).then(() => { if (window.Alpine && Alpine.store('timer')) Alpine.store('timer').stop(); window.location.reload(); });"
                                                        class="text-[10px] font-black text-rose-600 dark:text-rose-400 hover:underline cursor-pointer">
                                                        [Parar]
                                                    </button>
                                                @endif
                                            </div>
                                        @elseif($seconds > 0)
                                            <div class="flex items-center justify-center gap-1 text-gray-600 dark:text-gray-400" title="Tiempo dedicado a esta cita">
                                                <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                <span class="text-xs font-bold tabular-nums">{{ floor($seconds / 60) }}<span class="text-[10px] ml-0.5 opacity-70">m</span></span>
                                            </div>
                                        @else
                                            <span class="text-gray-300 dark:text-gray-600 font-bold">-</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        @if(!in_array($cita->status, ['cancelled', 'blocked']))
                                            <div x-data="{ loading: false, isCompleted: {{ $cita->status === 'completed' ? 'true' : 'false' }} }" class="inline-block">
                                                <label class="relative inline-flex items-center cursor-pointer" :class="{ 'opacity-50 pointer-events-none': loading }">
                                                    <input type="checkbox" class="sr-only peer" x-model="isCompleted"
                                                           @change="
                                                                loading = true;
                                                                let formData = new FormData();
                                                                formData.append('_token', '{{ csrf_token() }}');
                                                                formData.append('_method', 'PATCH');
                                                                formData.append('status', isCompleted ? 'completed' : 'confirmed');
                                                                
                                                                fetch('{{ route('appointments.update', [$team, $cita]) }}', {
                                                                    method: 'POST',
                                                                    body: formData,
                                                                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                                                                }).then(res => {
                                                                    loading = false;
                                                                }).catch(() => {
                                                                    loading = false;
                                                                    isCompleted = !isCompleted;
                                                                });
                                                           ">
                                                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-violet-500"></div>
                                                </label>
                                            </div>
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
                <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-2 shrink-0">
                        <label for="per_page_bottom" class="text-[10px] font-black uppercase tracking-widest text-gray-400">Mostrar:</label>
                        <select id="per_page_bottom" onchange="window.location.href=this.value" class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-bold py-1.5 pl-3 pr-8 focus:ring-cyan-500 focus:border-cyan-500 text-gray-900 dark:text-white transition-all shadow-sm">
                            <option value="{{ request()->fullUrlWithQuery(['per_page' => 15]) }}" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                            <option value="{{ request()->fullUrlWithQuery(['per_page' => 30]) }}" {{ request('per_page') == 30 ? 'selected' : '' }}>30</option>
                            <option value="{{ request()->fullUrlWithQuery(['per_page' => 50]) }}" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                            <option value="{{ request()->fullUrlWithQuery(['per_page' => 100]) }}" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                        </select>
                    </div>
                    <div class="w-full sm:w-auto overflow-x-auto">
                        {{ $appointments->links() }}
                    </div>
                </div>
            @endif
 <script>
    function confirmSingleDelete(event, id) {
        event.preventDefault();
        const form = document.getElementById(`delete-appointment-${id}`);
        if (typeof Swal === 'undefined') {
            if (confirm('¿Eliminar físicamente esta cita de la base de datos por completo?')) {
                HTMLFormElement.prototype.submit.call(form);
            }
            return;
        }
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
                HTMLFormElement.prototype.submit.call(form);
            }
        });
    }

    function confirmBulkDelete() {
        if (typeof Swal === 'undefined') {
            if (confirm('¿Eliminar permanentemente las citas seleccionadas?')) {
                const form = document.getElementById('bulk-form');
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'bulk_action';
                actionInput.value = 'delete';
                form.appendChild(actionInput);
                document.querySelectorAll('.bulk-cb:checked').forEach(cb => {
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'appointment_ids[]';
                    idInput.value = cb.value;
                    form.appendChild(idInput);
                });
                HTMLFormElement.prototype.submit.call(form);
            }
            return;
        }
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

                HTMLFormElement.prototype.submit.call(form);
            }
        });
    }

    function confirmBulkCancel() {
        if (typeof Swal === 'undefined') {
            if (confirm('¿Cancelar las citas seleccionadas?')) {
                const form = document.getElementById('bulk-form');
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'bulk_action';
                actionInput.value = 'cancel';
                form.appendChild(actionInput);
                document.querySelectorAll('.bulk-cb:checked').forEach(cb => {
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'appointment_ids[]';
                    idInput.value = cb.value;
                    form.appendChild(idInput);
                });
                HTMLFormElement.prototype.submit.call(form);
            }
            return;
        }
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

                HTMLFormElement.prototype.submit.call(form);
            }
        });
    }

    function confirmBulkNoShow() {
        if (typeof Swal === 'undefined') {
            if (confirm('¿Marcar las citas seleccionadas como NO PRESENTADO? (Se cancelarán)')) {
                const form = document.getElementById('bulk-form');
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'bulk_action';
                actionInput.value = 'no_show';
                form.appendChild(actionInput);
                document.querySelectorAll('.bulk-cb:checked').forEach(cb => {
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'appointment_ids[]';
                    idInput.value = cb.value;
                    form.appendChild(idInput);
                });
                HTMLFormElement.prototype.submit.call(form);
            }
            return;
        }
        Swal.fire({
            title: '¿Marcar como NO PRESENTADO?',
            text: 'Las citas seleccionadas pasarán a estado cancelado y se registrará que el ciudadano no se ha presentado.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, marcar',
            cancelButtonText: 'Volver',
            customClass: {
                popup: 'rounded-[2rem] dark:bg-gray-900 border border-gray-150 dark:border-gray-800 p-8',
                confirmButton: 'px-5 py-3 text-xs font-black uppercase tracking-widest bg-gray-800 hover:bg-gray-900 text-white dark:bg-gray-100 dark:hover:bg-white dark:text-gray-900 rounded-xl transition-all shadow-md',
                cancelButton: 'px-5 py-3 text-xs font-black uppercase tracking-widest bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-all ml-3'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('bulk-form');
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'bulk_action';
                actionInput.value = 'no_show';
                form.appendChild(actionInput);
                
                document.querySelectorAll('.bulk-cb:checked').forEach(cb => {
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'appointment_ids[]';
                    idInput.value = cb.value;
                    form.appendChild(idInput);
                });

                HTMLFormElement.prototype.submit.call(form);
            }
        });
    }
</script>
</x-app-layout>


