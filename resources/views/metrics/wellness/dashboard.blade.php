@extends('metrics.layouts.app')

@section('title', 'Wellness Dashboard — sientiaMTX')
@section('breadcrumb', __('metrics.categories.wellness'))

@section('content')
<div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white" style="font-family:'Space Grotesk',sans-serif">
                {{ __('metrics.wellness.title') }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ $team->name }} &mdash; {{ __('metrics.period', ['days' => $days ?? 30]) }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold
                {{ $teamWellness['overall_score'] >= 70 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : ($teamWellness['overall_score'] >= 40 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400') }}">
                <span class="w-2 h-2 rounded-full {{ $teamWellness['overall_score'] >= 70 ? 'bg-emerald-500' : ($teamWellness['overall_score'] >= 40 ? 'bg-amber-500' : 'bg-red-500') }}"></span>
                {{ __('metrics.wellness.team_score') }} {{ $teamWellness['overall_score'] ?? __('metrics.wellness.na') }}
            </span>
        </div>
    </div>

    {{-- Row 1: Team wellness gauge + Individual wellness bars + Mood heatmap --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Team Wellness Score Gauge --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-6">
            <div class="flex justify-between items-start mb-4">
                <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('metrics.wellness.team_wellness_score') }}</h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Índice de bienestar general calculado en base a las métricas del equipo.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
            </div>
            <div class="flex items-center justify-center">
                <div id="teamWellnessGauge" class="w-full"></div>
            </div>
        </div>

        {{-- Member Wellness Horizontal Bars --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-6">
            <div class="flex justify-between items-start mb-4">
                <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('metrics.wellness.member_wellness') }}</h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Nivel de bienestar individual registrado por cada miembro.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
            </div>
            <div class="space-y-3 overflow-y-auto max-h-[320px] pr-1">
                @forelse($memberWellness as $member)
                <a href="{{ route('metrics.wellness.individual', $member['user_id']) }}"
                   class="block group">
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300 w-32 shrink-0 truncate group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors">
                            {{ $member['name'] }}
                        </span>
                        <div class="flex-1 h-6 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500
                                {{ $member['wellness_score'] >= 70 ? 'bg-gradient-to-r from-emerald-400 to-emerald-500' : ($member['wellness_score'] >= 40 ? 'bg-gradient-to-r from-amber-400 to-amber-500' : 'bg-gradient-to-r from-red-400 to-red-500') }}"
                                 style="width: {{ $member['wellness_score'] }}%">
                            </div>
                        </div>
                        <span class="text-sm font-bold text-gray-900 dark:text-white w-12 text-right">
                            {{ $member['wellness_score'] }}
                        </span>
                    </div>
                </a>
                @empty
                <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-8">{{ __('metrics.wellness.no_member_data') }}</p>
                @endforelse
            </div>
        </div>

        {{-- Mood Heatmap Calendar --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-6">
            <div class="flex justify-between items-start mb-4">
                <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('metrics.wellness.mood_heatmap') }}</h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Distribución histórica del estado de ánimo a lo largo del mes.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
            </div>
            <div class="overflow-x-auto overflow-y-hidden">
                <div id="moodHeatmap" style="min-width: 600px;"></div>
            </div>
        </div>

    </div>

    {{-- Row 2: Stress trend + Energy trend --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

        {{-- Stress Trend Line Chart --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-6">
            <div class="flex justify-between items-start mb-4">
                <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('metrics.wellness.stress_trend') }}</h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Evolución del nivel de estrés reportado por el equipo a lo largo del tiempo.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
            </div>
            <div id="stressTrendChart" class="w-full"></div>
        </div>

        {{-- Energy Trend Line Chart --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-6">
            <div class="flex justify-between items-start mb-4">
                <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('metrics.wellness.energy_trend') }}</h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Evolución general del nivel de energía y motivación en el entorno de trabajo.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
            </div>
            <div id="energyTrendChart" class="w-full"></div>
        </div>

    </div>

    {{-- Row 3: Burnout risk traffic light + Overtime bar chart --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

        {{-- Burnout Risk Traffic Light --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-6">
            <div class="flex justify-between items-start mb-4">
                <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('metrics.wellness.burnout_risk') }}</h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Alertas de miembros que presentan un riesgo elevado de desgaste profesional o sobrecarga.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
            </div>
            <div class="space-y-3">
                @forelse($burnoutRiskList as $burnout)
                <div class="flex items-center justify-between py-2.5 px-3 rounded-xl
                    {{ $burnout['risk_level'] === 'ALTO' ? 'bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-800/50' : 'bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800/50' }}">
                    <div class="flex items-center gap-3">
                        <span class="w-3.5 h-3.5 rounded-full {{ $burnout['risk_level'] === 'ALTO' ? 'bg-red-500 shadow-lg shadow-red-500/40' : 'bg-amber-500 shadow-lg shadow-amber-500/40' }}"></span>
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $burnout['name'] }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-bold px-2 py-0.5 rounded-md
                            {{ $burnout['risk_level'] === 'ALTO' ? 'bg-red-200 text-red-800 dark:bg-red-800 dark:text-red-100' : 'bg-amber-200 text-amber-800 dark:bg-amber-800 dark:text-amber-100' }}">
                            {{ $burnout['risk_level'] }}
                        </span>
                        <span class="text-sm font-bold text-gray-900 dark:text-white">
                            {{ $burnout['risk_score'] }}
                        </span>
                        <a href="{{ route('metrics.wellness.individual', $burnout['user_id']) }}"
                           class="text-xs text-violet-600 dark:text-violet-400 hover:underline">{{ __('metrics.wellness.view_profile') }}</a>
                    </div>
                </div>
                @empty
                <p class="text-sm text-emerald-600 dark:text-emerald-400 text-center py-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ __('metrics.wellness.no_burnout_risk') }}
                </p>
                @endforelse
            </div>
        </div>

        {{-- Team Overtime Bar Chart --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-6">
            <div class="flex justify-between items-start mb-4">
                <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('metrics.wellness.overtime_weeks') }}</h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Volumen de horas extraordinarias reportadas de forma semanal.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
            </div>
            <div class="overflow-x-auto overflow-y-hidden">
                <div id="overtimeChart" style="min-width: 600px;"></div>
            </div>
        </div>

    </div>

    {{-- Row 4: Load distribution + Work-life balance + Mood-productivity --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Load Distribution Box Plot --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-6">
            <div class="flex justify-between items-start mb-4">
                <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('metrics.wellness.load_distribution') }}</h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Distribución estadística de la carga operativa y tareas asignadas dentro del equipo.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
            </div>
            <div id="loadDistributionChart" class="w-full"></div>
        </div>

        {{-- Work-Life Balance Radar Chart --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-6">
            <div class="flex justify-between items-start mb-4">
                <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('metrics.wellness.work_life_balance') }}</h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Métricas y valoración relacionadas con el equilibrio entre la vida personal y laboral.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
            </div>
            <div id="workLifeBalanceChart" class="w-full"></div>
        </div>

        {{-- Mood-Productivity Correlation Scatter Plot --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-6">
            <div class="flex justify-between items-start mb-4">
                <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('metrics.wellness.mood_vs_productivity') }}</h2>
                <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Análisis de correlación que evalúa cómo impacta el estado de ánimo en el volumen de tareas completadas.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
            </div>
            <div id="moodProductivityChart" class="w-full"></div>
        </div>

    </div>

    {{-- Row 5: Active Alerts + Recommendations --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

        {{-- Active Alerts List --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-6">
            <div class="flex items-center gap-2 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('metrics.wellness.active_alerts') }}</h2>
                @if(count($activeAlerts) > 0)
                <span class="ml-auto text-xs font-bold px-2 py-0.5 rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                    {{ count($activeAlerts) }}
                </span>
                @endif
            </div>
            <div class="space-y-2">
                @forelse($activeAlerts as $alert)
                <div class="flex items-start gap-3 p-3 rounded-xl bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-800/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-800 dark:text-gray-200">{{ $alert['message'] }}</p>
                        @if(!empty($alert['user_id']))
                        <a href="{{ route('metrics.wellness.individual', $alert['user_id']) }}"
                           class="text-xs text-violet-600 dark:text-violet-400 hover:underline mt-1 inline-block">
                            {!! __('metrics.wellness.view_full_profile') !!}
                        </a>
                        @endif
                    </div>
                </div>
                @empty
                <div class="flex items-center gap-3 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-200 dark:border-emerald-800/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm text-emerald-700 dark:text-emerald-400">{{ __('metrics.wellness.no_active_alerts') }}</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Recommendations Card --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-6">
            <div class="flex items-center gap-2 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
                <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('metrics.wellness.recommendations') }}</h2>
            </div>
            <div class="space-y-3">
                @forelse($recommendations as $rec)
                <div class="flex items-start gap-3 p-3 rounded-xl bg-violet-50 dark:bg-violet-900/10 border border-violet-200 dark:border-violet-800/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-violet-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm text-gray-800 dark:text-gray-200">{{ $rec }}</p>
                </div>
                @empty
                <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-4">{{ __('metrics.wellness.no_recommendations') }}</p>
                @endforelse
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // ── Shared ApexCharts options ──
    const commonChartOptions = {
        chart: {
            type: 'line',
            height: 300,
            toolbar: { show: true },
            zoom: { enabled: true },
        },
        stroke: { curve: 'smooth', width: 2.5 },
        theme: {
            mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
        },
        grid: {
            borderColor: document.documentElement.classList.contains('dark') ? '#1f2937' : '#f3f4f6',
            strokeDashArray: 3,
        },
        xaxis: {
            labels: { style: { fontSize: '11px' } },
        },
        yaxis: {
            labels: { style: { fontSize: '11px' } },
        },
        tooltip: {
            theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
        },
    };

    // ── Team Wellness Gauge ──
    const teamScore = {{ json_encode($teamWellness['overall_score'] ?? 0) }};
    const teamGauge = new ApexCharts(document.querySelector('#teamWellnessGauge'), {
        chart: { type: 'radialBar', height: 320 },
        series: [teamScore],
        colors: [teamScore >= 70 ? '#10b981' : (teamScore >= 40 ? '#f59e0b' : '#ef4444')],
        plotOptions: {
            radialBar: {
                hollow: { size: '65%' },
                dataLabels: {
                    name: { fontSize: '14px', fontWeight: 600, offsetY: -5 },
                    value: { fontSize: '36px', fontWeight: 700, offsetY: 5 },
                    total: {
                        show: true,
                        label: '{{ __('metrics.wellness.average') }}',
                        fontSize: '12px',
                        color: '#9ca3af',
                        formatter: () => teamScore + '/100',
                    },
                },
                track: {
                    background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#f3f4f6',
                },
            },
        },
        labels: ['Bienestar'],
    });
    teamGauge.render();

    // ── Stress Trend Chart ──
    const stressData = @json($stressHistory);
    const stressCategories = stressData.map(d => d.date || d.created_at);
    const stressValues = stressData.map(d => d.stress ?? d.avg_stress ?? 0);
    const stressThreshold = 70;

    const stressChart = new ApexCharts(document.querySelector('#stressTrendChart'), {
        ...commonChartOptions,
        chart: { ...commonChartOptions.chart, type: 'line', height: 300 },
        series: [{ name: '{{ __('metrics.wellness.stress') }}', data: stressValues }],
        xaxis: { categories: stressCategories },
        yaxis: { min: 0, max: 100, title: { text: '{{ __('metrics.wellness.level') }}' } },
        colors: ['#ef4444'],
        annotations: {
            yaxis: [{
                y: stressThreshold,
                borderColor: '#ef4444',
                strokeDashArray: 6,
                label: {
                    text: '{{ __('metrics.wellness.alert') }} (' + stressThreshold + ')',
                    style: { color: '#fff', background: '#ef4444' },
                },
            }],
        },
        markers: { size: 3 },
    });
    stressChart.render();

    // ── Energy Trend Chart ──
    const energyData = @json($moodHistory);
    const energyCategories = energyData.map(d => d.date || d.created_at);
    const energyValues = energyData.map(d => d.energy ?? d.avg_energy ?? 0);

    const energyChart = new ApexCharts(document.querySelector('#energyTrendChart'), {
        ...commonChartOptions,
        chart: { ...commonChartOptions.chart, type: 'line', height: 300 },
        series: [{ name: '{{ __('metrics.wellness.energy') }}', data: energyValues }],
        xaxis: { categories: energyCategories },
        yaxis: { min: 0, max: 100, title: { text: '{{ __('metrics.wellness.level') }}' } },
        colors: ['#10b981'],
        markers: { size: 3 },
    });
    energyChart.render();

    // ── Mood Heatmap (ApexCharts heatmap) ──
    const heatMapData = @json($heatMapData);
    const heatmapSeries = heatMapData.map(d => ({
        x: d.date ? new Date(d.date).toLocaleDateString('es-ES', { day: 'numeric', month: 'short' }) : '',
        y: d.avg_mood !== null ? (d.avg_mood / 5 * 100) : null,
    }));

    const moodHeatmap = new ApexCharts(document.querySelector('#moodHeatmap'), {
        chart: { type: 'heatmap', height: 280, toolbar: { show: true }, zoom: { enabled: true } },
        plotOptions: {
            heatmap: {
                enableShades: false,
                colorScale: {
                    ranges: [
                        { from: 0, to: 20, color: '#fecaca', name: '{{ __('metrics.wellness.very_low') }}' },
                        { from: 20.01, to: 40, color: '#fde68a', name: '{{ __('metrics.wellness.low') }}' },
                        { from: 40.01, to: 60, color: '#fde047', name: '{{ __('metrics.wellness.neutral') }}' },
                        { from: 60.01, to: 80, color: '#86efac', name: '{{ __('metrics.wellness.high') }}' },
                        { from: 80.01, to: 100, color: '#22c55e', name: '{{ __('metrics.wellness.very_high') }}' },
                    ],
                },
            },
        },
        series: heatmapSeries.length > 0 ? [{ name: '{{ __('metrics.wellness.avg_mood') }}', data: heatmapSeries }] : [],
        xaxis: { labels: { rotate: -45, style: { fontSize: '10px' } } },
        tooltip: {
            y: { formatter: (val) => val ? val.toFixed(1) + '%' : '{{ __('metrics.wellness.no_data') }}' },
            theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
        },
    });
    moodHeatmap.render();

    // ── Overtime Bar Chart ──
    const overtimeData = @json($overtimeByMember);
    const overtimeWeeks = [];
    const overtimeNames = [];
    const overtimeMemberMap = {};

    if (overtimeData.length > 0) {
        const numWeeks = overtimeData[0].weekly.length;
        for (let w = 0; w < numWeeks; w++) {
            overtimeWeeks.push(
                '{{ __('metrics.wellness.week') }} ' + (w + 1)
            );
        }
        overtimeData.forEach(member => {
            overtimeNames.push(member.name);
            overtimeMemberMap[member.name] = member.weekly.map(w => w.hours);
        });
    }

    const overtimeSeries = overtimeNames.map(name => ({
        name: name,
        data: overtimeMemberMap[name] || [],
    }));

    const overtimeChart = new ApexCharts(document.querySelector('#overtimeChart'), {
        chart: { type: 'bar', height: 300, stacked: false, toolbar: { show: true } },
        series: overtimeSeries,
        plotOptions: {
            bar: { columnWidth: '60%' },
        },
        xaxis: { categories: overtimeWeeks },
        yaxis: { title: { text: '{{ __('metrics.wellness.overtime') }}' }, min: 0 },
        colors: overtimeNames.map((_, i) => {
            const hue = (i * 37) % 360;
            return `hsl(${hue}, 60%, 50%)`;
        }),
        dataLabels: { enabled: false },
        legend: { position: 'bottom', fontSize: '11px' },
        annotations: {
            yaxis: overtimeWeeks.length > 0 ? [{
                y: 5,
                borderColor: '#ef4444',
                strokeDashArray: 6,
                label: {
                    text: '{{ __('metrics.wellness.limit') }} (5h)',
                    style: { color: '#fff', background: '#ef4444' },
                },
            }] : [],
        },
        tooltip: {
            theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
        },
    });
    overtimeChart.render();

    // ── Load Distribution Box Plot ──
    const loadDistribution = @json($loadDistribution ?? []);
    if (loadDistribution.length === 5 && loadDistribution[4] > 0) {
        new ApexCharts(document.querySelector('#loadDistributionChart'), {
            series: [{
                type: 'boxPlot',
                data: [{
                    x: 'Equipo',
                    y: loadDistribution
                }]
            }],
            chart: { type: 'boxPlot', height: 300, toolbar: { show: true }, zoom: { enabled: true } },
            colors: ['#8b5cf6'],
            plotOptions: {
                boxPlot: {
                    colors: {
                        upper: '#a78bfa',
                        lower: '#c4b5fd'
                    }
                }
            },
            yaxis: {
                title: { text: 'Actividades asignadas' },
                labels: { style: { colors: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280' } }
            },
            tooltip: { theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
            grid: {
                borderColor: document.documentElement.classList.contains('dark') ? '#1f2937' : '#f3f4f6',
            }
        }).render();
    } else {
        document.querySelector('#loadDistributionChart').innerHTML = '<p class="text-sm text-gray-400 text-center mt-12">Sin datos suficientes</p>';
    }

    // ── Work-Life Balance Radar Chart ──
    const radarData = @json($radarData ?? []);
    if (radarData.categories) {
        new ApexCharts(document.querySelector('#workLifeBalanceChart'), {
            series: [
                { name: 'Usuario', data: radarData.user },
                { name: 'Promedio Equipo', data: radarData.team }
            ],
            chart: { type: 'radar', height: 300, toolbar: { show: false } },
            labels: radarData.categories,
            stroke: { width: 2 },
            fill: { opacity: 0.2 },
            markers: { size: 4 },
            colors: ['#10b981', '#3b82f6'],
            xaxis: {
                labels: {
                    style: {
                        colors: radarData.categories.map(() => document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280'),
                        fontSize: '11px'
                    }
                }
            },
            yaxis: { show: false, min: 0, max: 100 },
            legend: {
                position: 'bottom',
                labels: { colors: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280' }
            },
            tooltip: { theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }
        }).render();
    }

    // ── Mood-Productivity Scatter Plot ──
    const scatterDataRaw = @json($scatterData ?? []);
    if (scatterDataRaw.length > 0) {
        new ApexCharts(document.querySelector('#moodProductivityChart'), {
            series: [{
                name: 'Productividad',
                data: scatterDataRaw.map(d => [d[0], d[1]])
            }],
            chart: { type: 'scatter', height: 300, toolbar: { show: true }, zoom: { enabled: true } },
            colors: ['#f59e0b'],
            xaxis: {
                title: { text: 'Índice de Ánimo (0-100)' },
                min: 0, max: 100,
                labels: { 
                    style: { colors: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280' },
                    formatter: function(val) { return Math.round(val); }
                }
            },
            yaxis: {
                title: { text: 'Actividades Completadas' },
                labels: { 
                    style: { colors: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280' },
                    formatter: function(val) { return Math.round(val); }
                }
            },
            grid: {
                borderColor: document.documentElement.classList.contains('dark') ? '#1f2937' : '#f3f4f6',
                strokeDashArray: 3
            },
            markers: { size: 6, hover: { size: 8 } },
            tooltip: { theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }
        }).render();
    } else {
        document.querySelector('#moodProductivityChart').innerHTML = '<p class="text-sm text-gray-400 text-center mt-12">Sin datos suficientes</p>';
    }

});
</script>
@endpush
