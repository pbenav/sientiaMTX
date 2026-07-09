@extends('metrics.layouts.app')

@section('title', __('metrics.manager_dashboard') . ' — ' . $team->name)
@section('breadcrumb', __('metrics.manager_dashboard'))

@section('content')
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    {{ __('metrics.manager_dashboard') }}
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $team->name }} — {{ __('metrics.period', ['days' => $days ?? 90]) }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/40">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    {{ __('metrics.live') }}
                </span>
            </div>
        </div>
    </div>

    {{-- KPI Cards Row --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Completed This Week --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('metrics.completed') }}</span>
                <span class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-900/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </span>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ number_format($teamMetricsData['completed_this_week'] ?? 0) }}
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Últimos {{ $days ?? 90 }} días
            </p>
        </div>

        {{-- Completion Rate --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('metrics.completion_rate') }}</span>
                <span class="p-2 rounded-xl bg-blue-50 dark:bg-blue-900/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </span>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ number_format($teamMetricsData['completion_rate'] ?? 0, 1) }}%
            </p>
            <div class="mt-2 w-full bg-gray-100 dark:bg-gray-800 rounded-full h-1.5">
                <div class="bg-blue-500 h-1.5 rounded-full transition-all" style="width: {{ $teamMetricsData['completion_rate'] ?? 0 }}%"></div>
            </div>
        </div>

        {{-- Velocity --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('metrics.velocity') }}</span>
                <span class="p-2 rounded-xl bg-violet-50 dark:bg-violet-900/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </span>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ number_format($teamMetricsData['velocity'] ?? 0, 1) }}
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ __('metrics.tasks_per_week') }}
            </p>
        </div>

        {{-- At-Risk Members --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('metrics.at_risk') }}</span>
                <span class="p-2 rounded-xl bg-red-50 dark:bg-red-900/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </span>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ number_format($teamMetricsData['at_risk_count'] ?? 0) }}
            </p>
            <p class="mt-1 text-xs text-red-500 dark:text-red-400 font-medium">
                {{ __('metrics.needs_attention') }}
            </p>
        </div>
    </div>

    {{-- Charts Row 1: Velocity + Load Distribution --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Team Velocity Chart --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                    </svg>
                    {{ __('metrics.team_velocity') }}
                </h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Evolución de las tareas completadas por el equipo a lo largo del tiempo.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
            </div>
            <div id="velocityChart"></div>
        </div>

        {{-- Load Distribution Chart --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    {{ __('metrics.load_distribution') }}
                </h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Distribución de tareas completadas por cada miembro del equipo frente al promedio.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
            </div>
            <div class="overflow-y-auto overflow-x-auto max-h-[400px] pr-1">
                <div id="loadChart" style="min-width: 600px;"></div>
            </div>
        </div>
    </div>

    {{-- Bottleneck Table --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ __('metrics.bottleneck_table') }}
            </h2>
            <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Lista de tareas que llevan más tiempo estancadas en progreso y necesitan atención.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
        </div>
        <div class="overflow-x-auto overflow-y-auto max-h-80">
            <table class="w-full text-xs">
                <thead class="sticky top-0 z-10 bg-white dark:bg-gray-900">
                    <tr class="border-b border-gray-200 dark:border-gray-800">
                        <th class="text-left py-3 px-3 font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('metrics.activity') }}</th>
                        <th class="text-left py-3 px-3 font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('metrics.assigned_to') }}</th>
                        <th class="text-left py-3 px-3 font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('metrics.status') }}</th>
                        <th class="text-left py-3 px-3 font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('metrics.days_stuck') }}</th>
                        <th class="text-left py-3 px-3 font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('metrics.priority') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bottlenecks as $bottleneck)
                        <tr class="border-b border-gray-100 dark:border-gray-800/50 hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors">
                            <td class="py-3 px-3 text-gray-900 dark:text-white font-medium">
                                <a href="{{ route('teams.activities.show', [$team, $bottleneck['activity']]) }}" class="hover:text-violet-500 transition-colors">
                                    {{ $bottleneck['activity']->title ?? '—' }}
                                </a>
                            </td>
                            <td class="py-3 px-3 text-gray-600 dark:text-gray-300">
                                <div class="flex items-center gap-2">
                                    <img src="{{ $bottleneck['activity']->assignedUser?->profile_photo_url ?? 'https://ui-avatars.com/api/?name=?&color=7F9CF5&background=EBF4FF' }}"
                                         alt="" class="w-5 h-5 rounded-full object-cover">
                                    {{ $bottleneck['activity']->assignedUser?->name ?? '—' }}
                                </div>
                            </td>
                            <td class="py-3 px-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-400">
                                    {{ __('tasks.statuses.in_progress') }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-gray-900 dark:text-white font-mono font-bold">
                                {{ $bottleneck['days_stuck'] }}
                            </td>
                            <td class="py-3 px-3">
                                @php
                                    $prio = $bottleneck['activity']->priority ?? 'normal';
                                    $prioColors = [
                                        'critical' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                        'high'     => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                                        'medium'   => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                                        'low'      => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                        'normal'   => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                    ];
                                    $prioLabels = [
                                        'critical' => 'Crítica',
                                        'high'     => 'Alta',
                                        'medium'   => 'Media',
                                        'low'      => 'Baja',
                                        'normal'   => 'Normal',
                                    ];
                                @endphp
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $prioColors[$prio] ?? $prioColors['normal'] }}">
                                    {{ $prioLabels[$prio] ?? ucfirst($prio) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-gray-500 dark:text-gray-400 text-xs italic">
                                {{ __('metrics.no_bottlenecks') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Charts Row 2: Member Completion + Priority Scatter --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Member Completion Bullet Chart --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    {{ __('metrics.completion_per_member') }}
                </h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Porcentaje de finalización de tareas asignadas para cada miembro del equipo.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
            </div>
            <div class="overflow-y-auto overflow-x-auto max-h-[400px] pr-1">
                <div id="completionBulletChart" style="min-width: 600px;"></div>
            </div>
        </div>

        {{-- Priority vs Completion Scatter Plot --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-pink-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z" />
                    </svg>
                    {{ __('metrics.priority_vs_completion') }}
                </h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Relación entre la prioridad de las tareas y su nivel de completitud por miembro.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
            </div>
            <div id="scatterChart"></div>
        </div>
    </div>

    {{-- Charts Row 3: Wellness Radar + Management Alerts --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        {{-- Team Wellness Radar --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                    {{ __('metrics.team_wellness') }}
                </h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Evaluación integral del bienestar, productividad y colaboración del equipo.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
            </div>
            <div id="radarChart"></div>
        </div>

        {{-- Management Alerts Panel --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    {{ __('metrics.management_alerts') }}
                </h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Avisos automáticos sobre riesgos de sobrecarga, cuellos de botella y rendimiento del equipo.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
            </div>
            <div class="space-y-3">
                @forelse($alertList as $alert)
                    <div class="flex items-start gap-3 p-3 rounded-xl border {{ $alert['type'] === 'danger' ? 'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-800/40' : 'bg-amber-50 dark:bg-amber-900/10 border-amber-200 dark:border-amber-800/40' }}">
                        <span class="mt-0.5 {{ $alert['type'] === 'danger' ? 'text-red-500' : 'text-amber-500' }}">
                            @if($alert['type'] === 'danger')
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @endif
                        </span>
                        <p class="text-xs {{ $alert['type'] === 'danger' ? 'text-red-700 dark:text-red-400' : 'text-amber-700 dark:text-amber-400' }} font-medium">
                            {{ $alert['message'] }}
                        </p>
                    </div>
                @empty
                    <div class="flex items-center justify-center py-8 text-xs text-gray-500 dark:text-gray-400 italic">
                        {{ __('metrics.no_alerts') }}
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Charts Row 4: Quadrant Distribution --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
                {{ __('metrics.quadrant_distribution') }}
            </h2>
            <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Volumen de tareas distribuidas según la matriz de Eisenhower (urgente vs importante).') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
        </div>
        <div id="quadrantChart"></div>
    </div>

    {{-- Collaboration Index + Sprint Projection --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Collaboration Index Network Graph --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    {{ __('metrics.collaboration_index') }}
                </h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Nivel de interacción y apoyo cruzado entre los miembros del equipo.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
            </div>
            <div id="collaborationChart" class="flex items-center justify-center min-h-[280px]"></div>
        </div>

        {{-- Sprint Projection --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ __('metrics.sprint_projection') }}
                </h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Estimación del progreso y tareas restantes para el ciclo actual.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
            </div>
            <div class="space-y-4">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">{{ __('metrics.progress') }}</span>
                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($sprintProgress['progress'], 1) }}%</span>
                    </div>
                    <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-3 overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-violet-500 to-emerald-500 rounded-full transition-all duration-700"
                             style="width: {{ $sprintProgress['progress'] }}%"></div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-3 text-center">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($sprintProgress['total']) }}</p>
                        <p class="text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mt-1">{{ __('metrics.total') }}</p>
                    </div>
                    <div class="bg-emerald-50 dark:bg-emerald-900/10 rounded-xl p-3 text-center">
                        <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($sprintProgress['completed']) }}</p>
                        <p class="text-[10px] font-medium text-emerald-500 dark:text-emerald-400 uppercase tracking-wider mt-1">{{ __('metrics.completed') }}</p>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-3">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-500 dark:text-gray-400">{{ __('metrics.remaining') }}</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ number_format($sprintProgress['total'] - $sprintProgress['completed']) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Kudos Board Ticker --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>
                {{ __('metrics.kudos_board') }}
            </h2>
            <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Reconocimientos y agradecimientos recientes entre los miembros del equipo.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @forelse($kudosBoard as $kudo)
                <div class="flex items-start gap-3 p-3 rounded-xl bg-gradient-to-br from-amber-50 to-yellow-50 dark:from-amber-900/10 dark:to-yellow-900/10 border border-amber-100 dark:border-amber-800/30 hover:shadow-md transition-shadow">
                    <img src="{{ $kudo->sender?->profile_photo_url ?? 'https://ui-avatars.com/api/?name=?&color=F59E0B&background=FEF3C7' }}"
                         alt="" class="w-8 h-8 rounded-full object-cover border-2 border-amber-200 dark:border-amber-700/50">
                    <div class="min-w-0">
                        <p class="text-xs text-gray-700 dark:text-gray-300">
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $kudo->sender?->name ?? '—' }}</span>
                            {{ __('metrics.kudos_to') }}
                            <span class="font-semibold text-amber-600 dark:text-amber-400">{{ $kudo->receiver?->name ?? '—' }}</span>
                        </p>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 truncate">
                            {{ $kudo->message ?? __('metrics.no_message') }}
                        </p>
                        <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">
                            {{ $kudo->created_at?->diffForHumans() }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="col-span-full flex items-center justify-center py-8 text-xs text-gray-500 dark:text-gray-400 italic">
                    {{ __('metrics.no_kudos_yet') }}
                </div>
            @endforelse
        </div>
    </div>

    {{-- Overloaded / Underloaded Members --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Overloaded Members --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                    {{ __('metrics.overloaded_members') }}
                </h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Miembros del equipo con un volumen de trabajo superior a su capacidad estimada.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
            </div>
            <div class="space-y-2 max-h-[300px] overflow-y-auto pr-1">
                @forelse($overloadedMembers as $member)
                    <div class="flex items-center gap-3 p-2.5 rounded-xl bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-800/30">
                        <img src="{{ $member['profile_photo'] ?? 'https://ui-avatars.com/api/?name=?&color=EF4444&background=FEF2F2' }}"
                             alt="" class="w-8 h-8 rounded-full object-cover">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-gray-900 dark:text-white truncate">{{ $member['name'] ?? '—' }}</p>
                            <p class="text-[10px] text-red-500 dark:text-red-400">{{ __('metrics.high_workload') }}</p>
                        </div>
                        <span class="text-xs font-bold text-red-600 dark:text-red-400">{{ $member['workload'] ?? 0 }}%</span>
                    </div>
                @empty
                    <p class="text-xs text-gray-500 dark:text-gray-400 italic py-4 text-center">{{ __('metrics.no_overloaded') }}</p>
                @endforelse
            </div>
        </div>

        {{-- Underloaded Members --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    {{ __('metrics.underloaded_members') }}
                </h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Miembros del equipo con capacidad disponible para asumir más tareas.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
            </div>
            <div class="space-y-2 max-h-[300px] overflow-y-auto pr-1">
                @forelse($underloadedMembers as $member)
                    <div class="flex items-center gap-3 p-2.5 rounded-xl bg-blue-50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-800/30">
                        <img src="{{ $member['profile_photo'] ?? 'https://ui-avatars.com/api/?name=?&color=3B82F6&background=EFF6FF' }}"
                             alt="" class="w-8 h-8 rounded-full object-cover">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-gray-900 dark:text-white truncate">{{ $member['name'] ?? '—' }}</p>
                            <p class="text-[10px] text-blue-500 dark:text-blue-400">{{ __('metrics.low_utilization') }}</p>
                        </div>
                        <span class="text-xs font-bold text-blue-600 dark:text-blue-400">{{ $member['workload'] ?? 0 }}%</span>
                    </div>
                @empty
                    <p class="text-xs text-gray-500 dark:text-gray-400 italic py-4 text-center">{{ __('metrics.no_underloaded') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Wellness Score Card --}}
    <div class="bg-gradient-to-br from-violet-500 to-purple-600 dark:from-violet-600 dark:to-purple-700 rounded-2xl p-5 shadow-lg mb-6 text-white">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-sm font-semibold text-white/80 uppercase tracking-wider">{{ __('metrics.team_wellness_score') }}</h2>
                <p class="text-4xl font-bold mt-1">{{ number_format($teamWellness['team_wellness_score'] ?? 0, 1) }}%</p>
                <p class="text-xs text-white/70 mt-1">{{ $team->name }}</p>
            </div>
            <div class="flex gap-3">
                <div class="text-center bg-white/10 rounded-xl px-4 py-2">
                    <p class="text-lg font-bold">{{ number_format($teamProductivity['productivity_score'] ?? 0, 1) }}%</p>
                    <p class="text-[10px] text-white/70 uppercase tracking-wider">{{ __('metrics.productivity') }}</p>
                </div>
                <div class="text-center bg-white/10 rounded-xl px-4 py-2">
                    <p class="text-lg font-bold">{{ $teamWellness['burnout_risk_count'] ?? 0 }}</p>
                    <p class="text-[10px] text-white/70 uppercase tracking-wider">{{ __('metrics.burnout_risk') }}</p>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // Common chart options for dark mode
    const commonOptions = {
        chart: {
            toolbar: { show: false },
            zoom: { enabled: false },
        },
        theme: {
            mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
        },
        tooltips: {
            theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
        },
        dataLabels: { enabled: false },
        grid: {
            borderColor: document.documentElement.classList.contains('dark') ? '#1f2937' : '#f3f4f6',
            strokeDashArray: 3,
        },
    };

    // Team Velocity Area Chart (last 8 weeks)
    const velocitySeries = @json($teamMetricsData['velocity_history'] ?? []);
    const velocityChart = new ApexCharts(document.querySelector("#velocityChart"), {
        ...commonOptions,
        chart: { ...commonOptions.chart, type: 'area', height: 300, fontFamily: 'Inter, sans-serif' },
        series: [{
            name: '{{ __("metrics.completed") }}',
            data: velocitySeries.map(v => [v.week, v.count]) || []
        }],
        xaxis: {
            categories: velocitySeries.map(v => v.week) || [],
            labels: { style: { fontSize: '10px', fontFamily: 'Inter' } },
        },
        yaxis: {
            title: { text: '{{ __("metrics.tasks") }}', style: { fontSize: '10px' } },
            labels: { 
                style: { fontSize: '10px', fontFamily: 'Inter' },
                formatter: function (val) { return val.toFixed(0); }
            },
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.7,
                opacityTo: 0.2,
                stops: [0, 90, 100],
                colorStops: [{
                    offset: 0,
                    color: '#8b5cf6',
                    opacity: 0.6
                }, {
                    offset: 100,
                    color: '#06b6d4',
                    opacity: 0.1
                }]
            }
        },
        colors: ['#8b5cf6'],
        stroke: { curve: 'smooth', width: 2 },
        markers: { size: 3, strokeWidth: 0 },
    });
    velocityChart.render();

    // Load Distribution Horizontal Bar Chart
    const loadSeries = @json($teamMetricsData['member_completion_rates'] ?? []);
    const avgLoad = loadSeries.length > 0
        ? loadSeries.reduce((sum, m) => sum + (m.completed ?? 0), 0) / loadSeries.length
        : 0;
    const threshold = avgLoad * 1.5;

    const loadChart = new ApexCharts(document.querySelector("#loadChart"), {
        ...commonOptions,
        chart: { 
            ...commonOptions.chart, 
            type: 'bar', 
            height: Math.max(350, loadSeries.length * 45), 
            horizontal: true, 
            fontFamily: 'Inter, sans-serif',
            toolbar: { show: true },
            zoom: { enabled: true }
        },
        series: [{
            name: '{{ __("metrics.completed") }}',
            data: loadSeries.map(m => ({ x: m.name || '—', y: m.completed ?? 0 }))
        }],
        xaxis: {
            labels: { 
                style: { fontSize: '10px', fontFamily: 'Inter' },
                formatter: function (val) { return Math.round(val); }
            },
        },
        yaxis: {
            labels: { style: { fontSize: '10px', fontFamily: 'Inter' } },
        },
        plotOptions: {
            bar: {
                horizontal: true,
                borderRadius: 4,
                barHeight: '60%',
                distributed: true,
            }
        },
        colors: loadSeries.map(() => '#6b7280'),
        dataLabels: {
            enabled: true,
            formatter: (val) => val,
            style: { fontSize: '10px', fontFamily: 'Inter' },
        },
        fill: {
            type: 'solid',
            colors: loadSeries.map(m => (m.completed ?? 0) > threshold ? '#ef4444' : undefined)
        },
        legend: { show: false },
        states: {
            hover: { filter: { type: 'lighten', value: 0.1 } },
        },
    });
    loadChart.render();

    // Member Completion Bullet Chart
    const bulletSeries = @json($teamMetricsData['member_completion_rates'] ?? []);
    const bulletChart = new ApexCharts(document.querySelector("#completionBulletChart"), {
        ...commonOptions,
        chart: { 
            ...commonOptions.chart, 
            type: 'bar', 
            height: Math.max(350, bulletSeries.length * 45), 
            horizontal: true, 
            fontFamily: 'Inter, sans-serif',
            toolbar: { show: true },
            zoom: { enabled: true }
        },
        series: [{
            name: '{{ __("metrics.completion_rate") }}',
            data: bulletSeries.map(m => ({ x: m.name || '—', y: m.completion_rate ?? 0 }))
        }],
        xaxis: {
            min: 0,
            max: 100,
            labels: {
                style: { fontSize: '10px', fontFamily: 'Inter' },
                formatter: val => val + '%',
            },
        },
        yaxis: {
            labels: { style: { fontSize: '10px', fontFamily: 'Inter' } },
        },
        plotOptions: {
            bar: {
                horizontal: true,
                borderRadius: 3,
                barHeight: '70%',
            }
        },
        colors: ['#10b981'],
        fill: {
            type: 'solid',
            opacity: 0.8,
        },
        annotations: {
            xaxis: [{
                x: bulletSeries.length > 0 ? (bulletSeries.reduce((sum, m) => sum + (m.completion_rate ?? 0), 0) / bulletSeries.length) : 50,
                borderColor: '#f59e0b',
                strokeDashArray: 4,
                label: {
                    text: '{{ __("metrics.average") }}',
                    style: { color: '#fff', fontSize: '9px', fontFamily: 'Inter' },
                    backgroundColor: '#f59e0b',
                }
            }],
        },
        dataLabels: {
            enabled: true,
            formatter: val => val.toFixed(1) + '%',
            style: { fontSize: '10px', fontFamily: 'Inter' },
        },
        legend: { show: false },
    });
    bulletChart.render();

    // Priority vs Completion Scatter Plot
    const scatterSeries = @json($teamMetricsData['priority_completion_data'] ?? []);
    const scatterChart = new ApexCharts(document.querySelector("#scatterChart"), {
        ...commonOptions,
        chart: { ...commonOptions.chart, type: 'scatter', height: 320, fontFamily: 'Inter, sans-serif', zoom: { enabled: true } },
        series: [{
            name: '{{ __("metrics.members") }}',
            data: scatterSeries.map(s => ({
                x: s.priority ?? 0,
                y: s.completion ?? 0,
                marker: { size: (s.completion ?? 0) / 10 }
            })) || []
        }],
        xaxis: {
            title: { text: '{{ __("metrics.priority") }}', style: { fontSize: '10px' } },
            labels: { 
                style: { fontSize: '10px', fontFamily: 'Inter' },
                formatter: function (val) { return val.toFixed(0); }
            },
        },
        yaxis: {
            title: { text: '{{ __("metrics.completion") }}', style: { fontSize: '10px' } },
            labels: { 
                style: { fontSize: '10px', fontFamily: 'Inter' },
                formatter: function (val) { return val.toFixed(0) + "%"; }
            },
        },
        colors: ['#ec4899'],
        markers: {
            radius: 6,
            strokeWidth: 0,
            fillOpacity: 0.7,
        },
        grid: {
            ...commonOptions.grid,
            xaxis: { lines: { show: true } },
            yaxis: { lines: { show: true } },
        },
    });
    scatterChart.render();

    // Collaboration Index Gauge Chart
    const collabScore = {{ $teamMetricsData['collaboration_index'] ?? 0 }};
    const collabChart = new ApexCharts(document.querySelector("#collaborationChart"), {
        ...commonOptions,
        chart: { ...commonOptions.chart, type: 'radialBar', height: 280, fontFamily: 'Inter, sans-serif' },
        series: [collabScore],
        plotOptions: {
            radialBar: {
                hollow: { size: '65%' },
                dataLabels: {
                    name: { show: false },
                    value: {
                        formatter: function (val) { return val.toFixed(1) + "%"; },
                        color: document.documentElement.classList.contains('dark') ? '#fff' : '#111827',
                        fontSize: '28px',
                        fontWeight: 700,
                        offsetY: 10
                    }
                }
            }
        },
        colors: ['#06b6d4'],
        stroke: { lineCap: 'round' },
    });
    collabChart.render();

    // Team Wellness Radar Chart
    const radarData = @json($wellnessRadar);
    const radarChart = new ApexCharts(document.querySelector("#radarChart"), {
        ...commonOptions,
        chart: { ...commonOptions.chart, type: 'radar', height: 320, fontFamily: 'Inter, sans-serif' },
        series: [{
            name: '{{ __("metrics.score") }}',
            data: [
                radarData.wellness ?? 0,
                radarData.productivity ?? 0,
                radarData.collaboration ?? 0,
                radarData.engagement ?? 0,
                radarData.balance ?? 0,
            ]
        }],
        xaxis: {
            categories: ['{{ __("metrics.categories.wellness") }}', '{{ __("metrics.categories.productivity") }}', '{{ __("metrics.categories.collaboration") }}', '{{ __("metrics.categories.engagement") }}', '{{ __("metrics.categories.balance") }}'],
            labels: { style: { fontSize: '9px', fontFamily: 'Inter' } },
        },
        colors: ['#14b8a6'],
        fill: {
            colors: ['#14b8a6'],
            opacity: 0.2,
        },
        markers: {
            radius: 3,
            strokeWidth: 2,
            strokeOpacity: 1,
        },
        stroke: { width: 2 },
        legend: { show: false },
        yaxis: {
            max: 100,
            labels: { style: { fontSize: '9px', fontFamily: 'Inter' } },
        },
    });
    radarChart.render();

    // Quadrant Distribution Stacked Bar Chart
    const quadrantData = @json($quadrantDistribution);
    const quadrantChart = new ApexCharts(document.querySelector("#quadrantChart"), {
        ...commonOptions,
        chart: { ...commonOptions.chart, type: 'bar', height: 300, fontFamily: 'Inter, sans-serif' },
        series: [{
            name: '{{ __("metrics.activities") }}',
            data: quadrantData.map(q => q.activity_count ?? 0) || []
        }],
        xaxis: {
            categories: quadrantData.map(q => q.name ?? '—') || [],
            labels: {
                style: { fontSize: '10px', fontFamily: 'Inter' },
                rotate: -45,
                rotateAlways: true,
            },
        },
        yaxis: {
            labels: { style: { fontSize: '10px', fontFamily: 'Inter' } },
        },
        plotOptions: {
            bar: {
                horizontal: false,
                borderRadius: 6,
                columnWidth: '60%',
            }
        },
        colors: quadrantData.map(q => q.color ?? '#6b7280'),
        dataLabels: {
            enabled: true,
            style: { fontSize: '10px', fontFamily: 'Inter' },
            formatter: val => val,
        },
        legend: { show: false },
        states: {
            hover: { filter: { type: 'lighten', value: 0.15 } },
        },
    });
    quadrantChart.render();
});
</script>
@endpush
