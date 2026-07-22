@php
    use App\Models\AppointmentSettings;
    use App\Models\Appointment;
@endphp

<x-app-layout maxWidth="max-w-none">
@section('title', 'Mis Citas Previas')

<x-slot name="header">
    <div class="flex items-center justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-2 min-w-0">
            <a href="{{ route('global-surveys.index') }}"
                class="p-1.5 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-lg transition-all shrink-0"
                title="{{ __('Volver al Canal Ciudadano') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            @include('teams.partials.breadcrumb')
            <span class="text-gray-300 dark:text-gray-700 mx-1">/</span>
            <h1 class="text-base font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span class="truncate">Mis Citas Previas</span>
            </h1>
        </div>
        
        <div class="flex items-center gap-2 shrink-0">
            @if($team)
                @include('teams.partials.header-toolbar')
            @endif
        </div>
    </div>
    
    @include('appointments.partials.nav')

    <div class="mt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-t border-gray-100 dark:border-gray-800 pt-3">
        <x-demo-hint>
            Este es el <strong>Escritorio Principal de Citas</strong>. Aquí el equipo tiene una visión global del día, estadísticas de rendimiento y acceso rápido a la agenda. Cada miembro solo visualiza las estadísticas y citas de los servicios en los que está asignado.
        </x-demo-hint>

        {{-- Botón Ver Portal Público --}}
        @if($settings && $settings->public_slug)
        <a href="{{ route('public.appointments.member', $settings->public_slug) }}" target="_blank"
           class="flex items-center gap-2 text-xs bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded-xl transition-all font-bold shadow-sm active:scale-95 group shrink-0 self-start sm:self-center">
            <svg class="w-4 h-4 transition-transform group-hover:scale-110 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            <span class="hidden sm:inline">Ver Portal Público</span>
        </a>
        @endif
    </div>
</x-slot>

<div class="py-8">
    <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-8">

        {{-- Banner de Contador Activo --}}
        @include('partials.active-timer-banner')

        {{-- Alerta si no está configurado --}}
        @if(!$settings || !$settings->is_public)
            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl p-5 flex items-start gap-4">
                <div class="shrink-0 w-10 h-10 bg-amber-100 dark:bg-amber-900/40 rounded-xl flex items-center justify-center text-amber-600 dark:text-amber-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <p class="text-sm font-black text-amber-800 dark:text-amber-300">Tu perfil público no está activo</p>
                    <p class="text-xs text-amber-700 dark:text-amber-400 mt-1">Para que los ciudadanos puedan solicitarte citas, activa tu perfil público en la configuración.</p>
                    <a href="{{ route('appointments.settings', $team) }}" class="mt-3 inline-flex items-center gap-1.5 text-xs font-black text-amber-700 dark:text-amber-300 hover:underline">
                        Ir a Configuración →
                    </a>
                </div>
            </div>
        @endif

        {{-- Estadísticas del día --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">

            {{-- Este mes --}}
            <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 p-6 shadow-sm relative overflow-hidden group flex flex-col justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-widest text-gray-400 mb-3">Este Mes</p>
                    <div class="flex flex-col gap-1">
                        <div class="flex items-baseline gap-2">
                            <h3 class="text-4xl font-black text-emerald-600 dark:text-emerald-400 tabular-nums">{{ $totalThisMonth }}</h3>
                            <span class="text-xs font-bold text-gray-400 uppercase">total</span>
                        </div>
                        @if($totalThisMonth > 0 && isset($monthAppointments['completed']))
                            @php
                                $effectiveness = round(($monthAppointments['completed'] / $totalThisMonth) * 100);
                            @endphp
                            <div class="flex items-center gap-2 mt-1">
                                <div class="flex-1 h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $effectiveness }}%"></div>
                                </div>
                                <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400">{{ $effectiveness }}% completadas</span>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    @php
                        $completedCount = $monthAppointments['completed'] ?? 0;
                        $confirmedCount = $monthAppointments['confirmed'] ?? 0;
                        $pendingCount = $monthAppointments['pending'] ?? 0;
                    @endphp
                    @if($completedCount > 0)
                    <div class="flex items-center gap-1.5 bg-gray-50 dark:bg-gray-800/50 px-2 py-1 rounded-lg border border-gray-100 dark:border-gray-700/50">
                        <span class="w-1.5 h-1.5 rounded-full bg-violet-500 shadow-[0_0_8px_rgba(139,92,246,0.5)]"></span>
                        <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $completedCount }} completadas</span>
                    </div>
                    @endif
                    @if($confirmedCount > 0)
                    <div class="flex items-center gap-1.5 bg-gray-50 dark:bg-gray-800/50 px-2 py-1 rounded-lg border border-gray-100 dark:border-gray-700/50">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]"></span>
                        <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $confirmedCount }} confirmadas</span>
                    </div>
                    @endif
                    @if($pendingCount > 0)
                    <div class="flex items-center gap-1.5 bg-gray-50 dark:bg-gray-800/50 px-2 py-1 rounded-lg border border-gray-100 dark:border-gray-700/50">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 shadow-[0_0_8px_rgba(245,158,11,0.5)]"></span>
                        <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $pendingCount }} pendientes</span>
                    </div>
                    @endif
                </div>
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-500/5 rounded-full blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
            </div>

            {{-- Citas hoy --}}
            <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 p-6 shadow-sm relative overflow-hidden group flex flex-col justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-widest text-gray-400 mb-3">
                        @if($selectedDate == now()->toDateString())
                            Citas Hoy
                        @else
                            Citas del {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}
                        @endif
                    </p>
                    <div class="flex flex-col gap-1">
                        <div class="flex items-baseline gap-2">
                            <h3 class="text-4xl font-black text-cyan-600 dark:text-cyan-400 tabular-nums">{{ $todayCitas->count() }}</h3>
                            <span class="text-xs font-bold text-gray-400 uppercase">total</span>
                        </div>
                        @if($todayCitas->count() > 0)
                            @php
                                $todayCompleted = $todayCitas->where('status', 'completed')->count();
                                $todayEffectiveness = round(($todayCompleted / $todayCitas->count()) * 100);
                            @endphp
                            <div class="flex items-center gap-2 mt-1">
                                <div class="flex-1 h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-cyan-500 rounded-full" style="width: {{ $todayEffectiveness }}%"></div>
                                </div>
                                <span class="text-[10px] font-bold text-cyan-600 dark:text-cyan-400">{{ $todayEffectiveness }}% éxito</span>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <div class="flex items-center gap-1.5 bg-gray-50 dark:bg-gray-800/50 px-2 py-1 rounded-lg border border-gray-100 dark:border-gray-700/50">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]"></span>
                        <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $todayCitas->where('status', 'completed')->count() }} completadas</span>
                    </div>
                    <div class="flex items-center gap-1.5 bg-gray-50 dark:bg-gray-800/50 px-2 py-1 rounded-lg border border-gray-100 dark:border-gray-700/50">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 shadow-[0_0_8px_rgba(245,158,11,0.5)]"></span>
                        <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $todayCitas->where('status', '!=', 'completed')->count() }} pendientes</span>
                    </div>
                </div>
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-cyan-500/5 rounded-full blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
            </div>

            {{-- Duración Citas --}}
            <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 p-6 shadow-sm relative overflow-hidden group flex flex-col justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-widest text-gray-400 mb-3" title="Calculado sobre citas completadas con tiempo registrado en los últimos 30 días">⏱️ Tiempos (30d)</p>
                    <div class="flex items-baseline gap-2">
                        <h3 class="text-3xl font-black text-indigo-600 dark:text-indigo-400 tabular-nums">{{ floor(($statsDuration['avg'] ?? 0) / 60) }}<span class="text-lg">m</span></h3>
                        <span class="text-[10px] font-bold text-gray-400 uppercase">MEDIA</span>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-3 gap-2">
                    <div class="bg-gray-50 dark:bg-gray-800/50 p-2 rounded-xl border border-gray-100 dark:border-gray-700/50 flex flex-col items-center">
                        <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">MIN</span>
                        <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ floor(($statsDuration['min'] ?? 0) / 60) }}m</span>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800/50 p-2 rounded-xl border border-gray-100 dark:border-gray-700/50 flex flex-col items-center">
                        <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">MAX</span>
                        <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ floor(($statsDuration['max'] ?? 0) / 60) }}m</span>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800/50 p-2 rounded-xl border border-gray-100 dark:border-gray-700/50 flex flex-col items-center">
                        <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">MODA</span>
                        <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ floor(($statsDuration['mode'] ?? 0) / 60) }}m</span>
                    </div>
                </div>
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-indigo-500/5 rounded-full blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
            </div>

            {{-- Próxima cita --}}
            <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 p-6 shadow-sm relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-violet-500/5 rounded-full blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
                <p class="text-xs font-black uppercase tracking-widest text-gray-400 mb-3">Próxima Cita</p>
                @if($upcoming->first())
                    @php $next = $upcoming->first(); @endphp
                    <p class="text-lg font-black text-gray-900 dark:text-white leading-tight truncate">{{ $next->service->name }}</p>
                    <p class="text-xs font-bold text-violet-600 dark:text-violet-400 mt-1">{{ $next->appointment_date->format('d/m') }} · {{ substr($next->appointment_time, 0, 5) }}</p>
                    <p class="text-[10px] text-gray-400 mt-1 truncate">{{ $next->visitor->full_name }}</p>
                @else
                    <p class="text-sm font-bold text-gray-400 mt-2">Sin citas próximas</p>
                @endif
            </div>

            {{-- Estado del portal --}}
            @if($settings && $settings->public_slug)
            <a href="{{ route('public.appointments.member', $settings->public_slug) }}" target="_blank" class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 p-6 shadow-sm relative overflow-hidden group block hover:border-cyan-200 dark:hover:border-cyan-800 transition-colors">
            @else
            <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 p-6 shadow-sm relative overflow-hidden group">
            @endif
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-rose-500/5 rounded-full blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
                <div class="flex items-start justify-between">
                    <p class="text-xs font-black uppercase tracking-widest text-gray-400 mb-3">Mi Perfil Público</p>
                    @if($settings && $settings->public_slug)
                        <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-cyan-500 transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    @endif
                </div>
                <div class="flex items-center gap-3 mt-2">
                    @if($settings && $settings->is_public)
                        <span class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 text-sm font-black">
                            <span class="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse"></span> Activo
                        </span>
                    @else
                        <span class="flex items-center gap-1.5 text-gray-400 text-sm font-black">
                            <span class="w-2.5 h-2.5 bg-gray-400 rounded-full"></span> Inactivo
                        </span>
                    @endif
                </div>
                @if($settings && $settings->public_slug)
                    <p class="text-[10px] text-cyan-600 dark:text-cyan-400 mt-2 font-mono font-bold truncate">/citas/{{ $settings->public_slug }}</p>
                @endif
            @if($settings && $settings->public_slug)
            </a>
            @else
            </div>
            @endif
        </div>

        {{-- Agenda del día y próximas citas --}}
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

            {{-- Citas de hoy --}}
            <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between flex-wrap gap-3">
                    <div class="flex items-center gap-3">
                        <p class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wide">📅 Agenda del Día</p>
                        <span class="text-[10px] font-black text-cyan-600 bg-cyan-50 dark:bg-cyan-900/30 dark:text-cyan-400 px-2.5 py-1 rounded-lg">{{ $todayCitas->count() }} citas</span>
                    </div>
                    <form method="GET" action="{{ route('appointments.index', $team) }}" class="flex items-center">
                        <input type="date" name="date" value="{{ $selectedDate }}" onchange="this.form.submit()" class="text-xs border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 rounded-lg py-1 px-2 text-gray-600 dark:text-gray-300 shadow-sm focus:ring-cyan-500 focus:border-cyan-500 cursor-pointer">
                    </form>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800 max-h-[500px] overflow-y-auto scrollbar-thin scrollbar-thumb-gray-200 dark:scrollbar-thumb-gray-700">
                    @forelse($todayCitas as $cita)
                        @php
                            $userActiveLog = auth()->user()?->activeTaskLog();
                            $isActiveTimerCita = $userActiveLog && (
                                ($cita->activity_id && $userActiveLog->task_id == $cita->activity_id) ||
                                ($cita->task_id && $userActiveLog->task_id == $cita->task_id)
                            );
                        @endphp
                        <div class="flex items-center gap-4 p-4 {{ $isActiveTimerCita ? 'bg-amber-50/70 dark:bg-amber-900/30 border-l-4 border-amber-500' : 'hover:bg-gray-50 dark:hover:bg-gray-800/50' }} transition-colors">
                            <div class="w-14 text-center shrink-0">
                                <p class="text-lg font-black text-cyan-600 dark:text-cyan-400 tabular-nums leading-none">{{ substr($cita->appointment_time, 0, 5) }}</p>
                                <p class="text-[9px] font-bold text-gray-400 uppercase mt-0.5">{{ $cita->slot_duration_minutes }}min</p>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-gray-900 dark:text-white truncate flex items-center gap-1.5">
                                    <span class="truncate">{{ $cita->visitor->full_name }}</span>
                                    @if($isActiveTimerCita)
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-black bg-amber-500 text-white animate-pulse shrink-0">⏱️ En curso</span>
                                    @endif
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $cita->service->name }}</p>
                            </div>
                                <div class="flex items-center gap-3 shrink-0">
                                    <span class="text-[9px] font-black uppercase px-2 py-1 rounded-lg
                                        @if($cita->status === 'confirmed') bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400
                                        @elseif($cita->status === 'completed') bg-violet-50 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400
                                        @elseif($cita->status === 'cancelled') bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400
                                        @elseif($cita->status === 'no_show') bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400
                                        @else bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400 @endif">
                                        {{ $cita->status_label }}
                                    </span>
                                    
                                    @if(!in_array($cita->status, ['cancelled', 'blocked']))
                                        <div x-data="{ loading: false, isCompleted: {{ $cita->status === 'completed' ? 'true' : 'false' }} }" class="inline-block">
                                            <label class="relative inline-flex items-center cursor-pointer mb-0" :class="{ 'opacity-50 pointer-events-none': loading }">
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
                                                <div class="w-7 h-4 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all dark:border-gray-600 peer-checked:bg-violet-500"></div>
                                            </label>
                                        </div>
                                    @endif
                                </div>
                            </div>
                    @empty
                        <div class="p-8 text-center">
                            <p class="text-4xl mb-2">🌤️</p>
                            <p class="text-sm font-bold text-gray-500 dark:text-gray-400">Sin citas para hoy</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Próximas citas --}}
            <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <p class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-wide">🗓️ Próximas Citas</p>
                    <a href="{{ route('appointments.list', $team) }}" class="text-[10px] font-black text-cyan-600 dark:text-cyan-400 hover:underline">Ver todas →</a>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800 max-h-[500px] overflow-y-auto scrollbar-thin scrollbar-thumb-gray-200 dark:scrollbar-thumb-gray-700">
                    @forelse($upcoming->skip($todayCitas->count() > 0 ? 0 : 0)->take(8) as $cita)
                        <a href="{{ route('appointments.show', [$team, $cita]) }}" class="flex items-center gap-4 p-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors group">
                            <div class="w-14 text-center shrink-0">
                                <p class="text-xs font-black text-gray-500 dark:text-gray-400 tabular-nums">{{ $cita->appointment_date->format('d/m') }}</p>
                                <p class="text-sm font-black text-cyan-600 dark:text-cyan-400 tabular-nums leading-none">{{ substr($cita->appointment_time, 0, 5) }}</p>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-gray-900 dark:text-white truncate group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">{{ $cita->visitor->full_name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $cita->service->name }}</p>
                            </div>
                            <p class="text-[9px] font-mono text-gray-400 hidden sm:block shrink-0">{{ $cita->localizador }}</p>
                        </a>
                    @empty
                        <div class="p-8 text-center">
                            <p class="text-4xl mb-2">📭</p>
                            <p class="text-sm font-bold text-gray-500 dark:text-gray-400">No hay citas próximas</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</div>
</x-app-layout>
