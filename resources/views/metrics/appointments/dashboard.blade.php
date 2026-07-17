@extends('metrics.layouts.app')

@section('title', __('Panel de Citas'))
@section('breadcrumb', __('Métricas de Citas'))

@section('content')
<div class="space-y-6" x-data>
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">{{ __('Panel de Citas') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('Resumen de reservas, confirmaciones y no presentados.') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('Periodo: 30 días') }}</span>
        </div>
    </div>

    {{-- Row 1: 4 KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Today --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Total Hoy') }}</span>
                <div class="w-9 h-9 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] ?? 0 }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('citas hoy') }}</p>
        </div>

        {{-- Confirmed --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Confirmado') }}</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ $stats['confirmed'] ?? 0 }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                @php $p = ($stats['total'] ?? 0) > 0 ? round(($stats['confirmed'] ?? 0) / ($stats['total'] ?? 1) * 100, 1) : 0; @endphp
                {{ number_format($p, 1) }}% {{ __('del total') }}
            </p>
        </div>

        {{-- Cancelled --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Cancelado') }}</span>
                <div class="w-9 h-9 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ $stats['cancelled'] ?? 0 }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                @php $p = ($stats['total'] ?? 0) > 0 ? round(($stats['cancelled'] ?? 0) / ($stats['total'] ?? 1) * 100, 1) : 0; @endphp
                {{ number_format($p, 1) }}% {{ __('del total') }}
            </p>
        </div>

        {{-- No Presentado --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('No Presentado') }}</span>
                <div class="w-9 h-9 rounded-xl bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-3xl font-bold {{ (($stats['no_show_rate'] ?? 0) > 15) ? 'text-rose-600 dark:text-rose-400 animate-pulse' : 'text-rose-600 dark:text-rose-400' }}">
                {{ $stats['no_show'] ?? 0 }}
            </p>
            <p class="text-xs {{ (($stats['no_show_rate'] ?? 0) > 15) ? 'text-rose-600 dark:text-rose-400 font-semibold' : 'text-gray-500 dark:text-gray-400' }} mt-1">
                {{ number_format($stats['no_show_rate'] ?? 0, 1) }}% rate
                @if(($stats['no_show_rate'] ?? 0) > 15)
                <span class="text-[10px] ml-1">⚠️ {{ __('¡alto!') }}</span>
                @endif
            </p>
        </div>
    </div>

    {{-- Row 2: Bookings by Day + Confirmation Rate Gauge --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Bookings by Day Bar Chart --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Reservas por Día') }}</h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Cantidad de citas registradas por día en el periodo seleccionado.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
                <span class="ml-auto text-xs text-gray-400">{{ __('Próximos 7 días') }}</span>
            </div>
            <div id="dailyBookingsChart" class="w-full" style="min-height: 280px;"></div>
        </div>

        {{-- Confirmation Rate Gauge --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Tasa de Confirmación') }}</h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Porcentaje de citas que han sido confirmadas sobre el total.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
            </div>
            <div id="confirmationGaugeChart" class="w-full flex justify-center" style="min-height: 280px;"></div>
        </div>
    </div>

    {{-- Row 3: No Presentado Rate Gauge + State Donut --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- No Presentado Rate Gauge --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Tasa de No Presentado') }}</h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Porcentaje de clientes que no se presentaron a su cita programada.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
                @if(($stats['no_show_rate'] ?? 0) > 15)
                <span class="ml-auto text-xs font-bold px-2 py-0.5 rounded-full bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300 animate-pulse">
                    ⚠️ {{ __('Superior a 15%') }}
                </span>
                @endif
            </div>
            <div id="noShowGaugeChart" class="w-full flex justify-center" style="min-height: 280px;"></div>
        </div>

        {{-- State Distribution Donut --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Distribución por Estado') }}</h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Proporción de citas según su estado actual (confirmado, pendiente, etc.).') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
            </div>
            <div id="stateDonutChart" class="w-full flex justify-center" style="min-height: 280px;"></div>
        </div>
    </div>

    {{-- Row 4: Service Distribution + Peak Hours --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Service Distribution Horizontal Bar --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Distribución por Servicio') }}</h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Cantidad de citas agrupadas por el tipo de servicio ofrecido.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
            </div>
            <div id="serviceDistributionChart" class="w-full" style="min-height: 280px;"></div>
        </div>

        {{-- Peak Hours Line Chart --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Horas Pico') }}</h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Volumen de citas distribuidas por hora del día para identificar los momentos de mayor actividad.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
                <span class="ml-auto text-xs text-gray-400">{{ __('Últimos 7 días') }}</span>
            </div>
            <div id="peakHoursChart" class="w-full" style="min-height: 280px;"></div>
        </div>
    </div>

    {{-- Row 5: Peak Days + Capacity Utilization --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Peak Days Bar Chart --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Días Pico') }}</h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Volumen de citas agrupadas por día de la semana para identificar los días más concurridos.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
            </div>
            <div id="peakDaysChart" class="w-full" style="min-height: 280px;"></div>
        </div>

        {{-- Capacity Utilization Stacked Bar --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Utilización de Capacidad') }}</h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Comparación entre las horas ocupadas y las horas disponibles del equipo o instalación.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
                <span class="ml-auto text-xs text-gray-400">{{ __('Próximos 7 días') }}</span>
            </div>
            <div id="utilizationChart" class="w-full" style="min-height: 280px;"></div>
        </div>
    </div>

    {{-- Row 6: Cancellation Trend + No Presentado Trend --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Cancellation Trend --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Tendencia de Cancelación') }}</h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Evolución histórica de las citas canceladas semana a semana.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
                <span class="ml-auto text-xs text-gray-400">{{ __('Últimas 8 semanas') }}</span>
            </div>
            <div id="cancellationTrendChart" class="w-full" style="min-height: 280px;"></div>
        </div>

        {{-- No Presentado Trend --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Tendencia de No Presentados') }}</h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Evolución histórica de las inasistencias (no presentados) semana a semana.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
                <span class="ml-auto text-xs text-gray-400">{{ __('Últimas 8 semanas') }}</span>
            </div>
            <div id="noShowTrendChart" class="w-full" style="min-height: 280px;"></div>
        </div>
    </div>

    {{-- Row 7: Most Profitable Services Table --}}
    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-100 dark:border-gray-800">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 10v1m0-10-3-1m3 1 3-1m-1 14 3 1m-3-1 3-1"/></svg>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Servicios Más Rentables') }}</h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Ranking de los servicios que generan más citas e ingresos.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wider">
                        <th class="px-6 py-3 text-left font-semibold">{{ __('Servicio') }}</th>
                        <th class="px-6 py-3 text-right font-semibold">{{ __('Citas') }}</th>
                        <th class="px-6 py-3 text-right font-semibold">{{ __('Completado') }}</th>
                        <th class="px-6 py-3 text-right font-semibold">{{ __('Tasa de Finalización') }}</th>
                        <th class="px-6 py-3 text-right font-semibold">{{ __('Ingresos') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @php
                        $services = collect($serviceDistribution ?? [])->map(function($s, $i) {
                            return (object)[
                                'name' => $s['service'] ?? $s['service_name'] ?? $s['name'] ?? __('Servicio ' . ($i + 1)),
                                'count' => $s['count'] ?? 0,
                                'completed' => $s['completed'] ?? 0,
                                'revenue' => $s['revenue'] ?? 0,
                            ];
                        })->sortByDesc('count')->values();
                    @endphp
                    @forelse($services->take(10) as $service)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30 transition">
                        <td class="px-6 py-3 font-medium text-gray-800 dark:text-gray-100">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                                {{ $service->name ?? __('Servicio') }}
                            </div>
                        </td>
                        <td class="px-6 py-3 text-right text-gray-600 dark:text-gray-300">{{ number_format($service->count ?? 0) }}</td>
                        <td class="px-6 py-3 text-right text-gray-600 dark:text-gray-300">{{ number_format($service->completed ?? 0) }}</td>
                        <td class="px-6 py-3 text-right">
                            @php
                                $completionRate = ($service->count ?? 0) > 0 ? round(($service->completed ?? 0) / ($service->count ?? 1) * 100, 1) : 0;
                            @endphp
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $completionRate >= 80 ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300' : ($completionRate >= 50 ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300' : 'bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300') }}">
                                {{ number_format($completionRate, 1) }}%
                            </span>
                        </td>
                        <td class="px-6 py-3 text-right font-bold text-emerald-600 dark:text-emerald-400">
                            ${{ number_format($service->revenue ?? 0, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                            {{ __('No hay datos de servicios disponibles aún.') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Row 8: Return Visitors + Confirmation Time --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Return Visitors Gauge --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Visitantes Recurrentes') }}</h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Porcentaje de clientes que han vuelto a reservar más de una vez.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
            </div>

            <div class="flex flex-col items-center py-4">
                <div class="relative w-40 h-40">
                    <svg class="w-full h-full -rotate-90" viewBox="0 0 36 36">
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                              fill="none" stroke="currentColor" stroke-width="3"
                              class="text-gray-200 dark:text-gray-700"/>
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                              fill="none" stroke="url(#returnGradient)" stroke-width="3"
                              stroke-dasharray="{{ number_format($returnRate['return_rate'] ?? 0, 1) }}, 100"
                              stroke-linecap="round"/>
                        <defs>
                            <linearGradient id="returnGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#6366f1"/>
                                <stop offset="100%" stop-color="#8b5cf6"/>
                            </linearGradient>
                        </defs>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($returnRate['return_rate'] ?? 0, 1) }}%</span>
                    </div>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-4">{{ __('de los visitantes regresan para otra cita') }}</p>
            </div>
        </div>

        {{-- Time to Confirmation Histogram --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
            <div class="flex items-center gap-2 mb-4">
                <svg class="w-5 h-5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('Tiempo hasta Confirmación') }}</h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Tiempo promedio que transcurre desde que se crea la reserva hasta que se confirma.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
                <span class="ml-auto text-xs text-gray-400">
                    {{ __('Promedio: :hours horas', ['hours' => number_format($confirmationTime['avg_hours'] ?? 0, 1)]) }}
                </span>
            </div>
            <div id="confirmationTimeChart" class="w-full" style="min-height: 280px;"></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isDark = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? '#374151' : '#e5e7eb';
    const textColor = isDark ? '#9ca3af' : '#6b7280';
    const tooltipTheme = isDark ? 'dark' : 'light';

    if (typeof ApexCharts === 'undefined') return;

    // --- Bookings by Day (Bar Chart) ---
    const dailyBookingsData = {!! json_encode($trends ?? []) !!};
    const dbChart = new ApexCharts(document.getElementById("dailyBookingsChart"), {
        chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
        series: [{ name: '{{ __('Reservas') }}', data: dailyBookingsData.map(d => d.count ?? 0) }],
        plotOptions: { bar: { borderRadius: 6, columnWidth: '50%' } },
        colors: ['#3b82f6'],
        xaxis: { categories: dailyBookingsData.map(d => d.label || d.date || '') },
        grid: { borderColor: gridColor, yaxis: { lines: { show: true } } },
        tooltip: { theme: tooltipTheme },
        dataLabels: { enabled: false },
        states: { hover: { filter: { type: 'lighten', value: 0.15 } } }
    });
    dbChart.render();

    // --- Confirmation Rate Gauge ---
    const confRate = {{ ($stats['total'] ?? 0) > 0 ? round(($stats['confirmed'] ?? 0) / ($stats['total'] ?? 1) * 100, 1) : 0 }};
    const cgChart = new ApexCharts(document.getElementById("confirmationGaugeChart"), {
        chart: { height: 280, type: 'radialBar', fontFamily: 'Inter, sans-serif' },
        series: [confRate],
        plotOptions: {
            radialBar: {
                hollow: { size: '65%' },
                dataLabels: {
                    name: { fontSize: '13px', color: textColor, offsetY: 20 },
                    value: { fontSize: '28px', fontWeight: 700, offsetY: -10, color: '#10b981', formatter: (val) => val.toFixed(1) + '%' }
                },
                track: { background: isDark ? '#1f2937' : '#f3f4f6' }
            }
        },
        colors: ['#10b981'],
        labels: ['{{ __('Tasa de Confirmación') }}'],
        stroke: { dashArray: 4 },
        fill: { type: 'gradient', gradient: { shade: 'dark', type: 'horizontal', shadeIntensity: 0.3, gradientToColors: ['#059669'], opacityFrom: 1, opacityTo: 1, stops: [0, 100] } }
    });
    cgChart.render();

    // --- No Presentado Rate Gauge ---
    const nsRate = {{ $stats['no_show_rate'] ?? 0 }};
    const nsColor = nsRate > 15 ? '#f43f5e' : (nsRate > 10 ? '#f59e0b' : '#10b981');
    const nsChart = new ApexCharts(document.getElementById("noShowGaugeChart"), {
        chart: { height: 280, type: 'radialBar', fontFamily: 'Inter, sans-serif' },
        series: [nsRate],
        plotOptions: {
            radialBar: {
                hollow: { size: '65%' },
                dataLabels: {
                    name: { fontSize: '13px', color: textColor, offsetY: 20 },
                    value: { fontSize: '28px', fontWeight: 700, offsetY: -10, color: nsColor, formatter: (val) => val.toFixed(1) + '%' }
                },
                track: { background: isDark ? '#1f2937' : '#f3f4f6' }
            }
        },
        colors: [nsColor],
        labels: ['{{ __('Tasa de No Presentado') }}'],
        stroke: { dashArray: 4 },
        fill: { type: 'gradient', gradient: { shade: 'dark', type: 'horizontal', shadeIntensity: 0.3, gradientToColors: nsRate > 15 ? ['#e11d48'] : ['#f59e0b'], opacityFrom: 1, opacityTo: 1, stops: [0, 100] } }
    });
    nsChart.render();

    // --- State Distribution Donut ---
    const stateData = {!! json_encode($stats['distribution'] ?? []) !!};
    const sdChart = new ApexCharts(document.getElementById("stateDonutChart"), {
        chart: { type: 'donut', height: 280, fontFamily: 'Inter, sans-serif' },
        series: stateData.map(d => d.count ?? 0),
        labels: stateData.map(d => {
            const map = { confirmed: '{{ __('Confirmado') }}', cancelled: '{{ __('Cancelado') }}', 'no_show': '{{ __('No Presentado') }}', completed: '{{ __('Completado') }}', pending: '{{ __('Pendiente') }}', scheduled: '{{ __('Programado') }}' };
            return map[d.status] || d.status || '—';
        }),
        colors: ['#10b981', '#f59e0b', '#f43f5e', '#3b82f6', '#8b5cf6', '#06b6d4'],
        legend: { position: 'bottom', fontSize: '11px', labels: { colors: textColor } },
        plotOptions: { pie: { donut: { size: '60%', labels: { name: { fontSize: '12px', color: textColor }, value: { fontSize: '18px', fontWeight: 700, color: '#1f2937', formatter: (val) => val }, total: { show: true, label: '{{ __("Total") }}', fontSize: '12px', color: textColor, formatter: () => stateData.reduce((s,d) => s + (d.count||0), 0) } } } } },
        dataLabels: { enabled: false },
        tooltip: { theme: tooltipTheme, y: { formatter: (val) => val + ' (' + ((val / stateData.reduce((s,d) => s + (d.count||0), 0)) * 100).toFixed(1) + '%)' } }
    });
    sdChart.render();

    // --- Service Distribution Horizontal Bar ---
    const serviceData = {!! json_encode($serviceDistribution ?? []) !!};
    const svcChart = new ApexCharts(document.getElementById("serviceDistributionChart"), {
        chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
        series: [{ name: '{{ __('Cantidad') }}', data: serviceData.map(d => d.count ?? 0) }],
        plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '55%' } },
        colors: ['#8b5cf6'],
        xaxis: { categories: serviceData.map(d => d.service || d.service_name || d.name || '—') },
        grid: { borderColor: gridColor, yaxis: { lines: { show: true } } },
        tooltip: { theme: tooltipTheme },
        dataLabels: { enabled: false },
        states: { hover: { filter: { type: 'lighten', value: 0.15 } } }
    });
    svcChart.render();

    // --- Peak Hours Line Chart ---
    const peakHoursData = {!! json_encode($peakHours ?? []) !!};
    const phChart = new ApexCharts(document.getElementById("peakHoursChart"), {
        chart: { type: 'area', height: 280, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
        series: [{ name: '{{ __('Citas') }}', data: peakHoursData.map(d => d.count ?? 0) }],
        xaxis: { categories: peakHoursData.map(d => d.label || (d.hour !== undefined ? d.hour + ':00' : '')) },
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.7, opacityTo: 0.3, stops: [0, 90, 100] } },
        colors: ['#f59e0b'],
        grid: { borderColor: gridColor, xaxis: { lines: { show: true } } },
        tooltip: { theme: tooltipTheme },
        dataLabels: { enabled: false }
    });
    phChart.render();

    // --- Peak Days Bar Chart ---
    const peakDaysData = {!! json_encode($peakDays ?? []) !!};
    const pdChart = new ApexCharts(document.getElementById("peakDaysChart"), {
        chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
        series: [{ name: '{{ __('Citas') }}', data: peakDaysData.map(d => d.count ?? 0) }],
        plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
        colors: ['#6366f1'],
        xaxis: { categories: peakDaysData.map(d => d.name || d.day_name || d.day_of_week || '') },
        grid: { borderColor: gridColor, yaxis: { lines: { show: true } } },
        tooltip: { theme: tooltipTheme },
        dataLabels: { enabled: false },
        states: { hover: { filter: { type: 'lighten', value: 0.15 } } }
    });
    pdChart.render();

    // --- Capacity Utilization Stacked Bar ---
    const utilData = {!! json_encode($utilization ?? []) !!};
    const utChart = new ApexCharts(document.getElementById("utilizationChart"), {
        chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
        series: [
            { name: '{{ __('Usado') }}', data: utilData.map(d => d.used ?? 0) },
            { name: '{{ __('Disponible') }}', data: utilData.map(d => Math.max(0, (d.available ?? 0) - (d.used ?? 0))) }
        ],
        plotOptions: { bar: { borderRadius: 6, columnWidth: '55%', stacked: true } },
        colors: ['#10b981', '#e5e7eb'],
        xaxis: { categories: utilData.map(d => d.label || d.date || '') },
        grid: { borderColor: gridColor, yaxis: { lines: { show: true } } },
        tooltip: { theme: tooltipTheme, shared: true, intersect: false },
        dataLabels: { enabled: false },
        legend: { position: 'top' }
    });
    utChart.render();

    // --- Cancellation Trend Line Chart ---
    const cancelData = {!! json_encode($cancellationTrend ?? []) !!};
    const ctChart = new ApexCharts(document.getElementById("cancellationTrendChart"), {
        chart: { type: 'line', height: 280, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
        series: [
            { name: '{{ __('Total') }}', data: cancelData.map(d => d.total ?? 0) },
            { name: '{{ __('Cancelado') }}', data: cancelData.map(d => d.cancelled ?? 0) },
            { name: '{{ __('Tasa %') }}', data: cancelData.map(d => d.rate ?? 0) }
        ],
        xaxis: { categories: cancelData.map(d => d.label || d.week_start || '') },
        stroke: { curve: 'smooth', width: [2, 2, 1] },
        colors: ['#3b82f6', '#f59e0b', '#ef4444'],
        grid: { borderColor: gridColor, yaxis: { lines: { show: true } } },
        tooltip: { theme: tooltipTheme },
        dataLabels: { enabled: false },
        legend: { position: 'top' }
    });
    ctChart.render();

    // --- No Presentado Trend Line Chart ---
    const nsTrendData = {!! json_encode($noShowTrend ?? []) !!};
    const nstChart = new ApexCharts(document.getElementById("noShowTrendChart"), {
        chart: { type: 'line', height: 280, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
        series: [
            { name: '{{ __('Total') }}', data: nsTrendData.map(d => d.total ?? 0) },
            { name: '{{ __('No Presentado') }}', data: nsTrendData.map(d => d.no_show ?? 0) },
            { name: '{{ __('Tasa %') }}', data: nsTrendData.map(d => d.rate ?? 0) }
        ],
        xaxis: { categories: nsTrendData.map(d => d.label || d.week_start || '') },
        stroke: { curve: 'smooth', width: [2, 2, 1] },
        colors: ['#3b82f6', '#f59e0b', '#ef4444'],
        grid: { borderColor: gridColor, yaxis: { lines: { show: true } } },
        tooltip: { theme: tooltipTheme },
        dataLabels: { enabled: false },
        legend: { position: 'top' }
    });
    nstChart.render();

    // --- Confirmation Time Histogram ---
    const confTimeData = {!! json_encode($confirmationTime['distribution'] ?? []) !!};
    const ctHistChart = new ApexCharts(document.getElementById("confirmationTimeChart"), {
        chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
        series: [{ name: '{{ __('Cantidad') }}', data: confTimeData.map(d => d.count ?? 0) }],
        plotOptions: { bar: { borderRadius: 4, columnWidth: '70%' } },
        colors: ['#06b6d4'],
        xaxis: { categories: confTimeData.map(d => d.label || ''), labels: { rotate: -45, rotateAlways: true } },
        grid: { borderColor: gridColor, yaxis: { lines: { show: true } } },
        tooltip: { theme: tooltipTheme },
        dataLabels: { enabled: false },
        states: { hover: { filter: { type: 'lighten', value: 0.15 } } }
    });
    ctHistChart.render();
});

function round(a, b) { return Math.round(a * Math.pow(10, b)) / Math.pow(10, b); }
</script>
@endpush
@endsection
