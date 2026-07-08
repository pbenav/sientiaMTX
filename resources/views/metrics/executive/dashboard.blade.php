@extends('metrics.layouts.app')

@section('title', __('metrics.executive_dashboard') . ' — ' . __('metrics.dashboard'))

@push('styles')
<style>
    .kpi-card { @apply bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm transition-all hover:shadow-md hover:border-gray-300 dark:hover:border-gray-700; }
    .section-card { @apply bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden; }
    .section-header { @apply px-5 py-4 border-b border-gray-100 dark:border-gray-800; }
    .section-title { @apply text-base font-bold text-gray-900 dark:text-white flex items-center gap-2; }
    .section-subtitle { @apply text-xs text-gray-500 dark:text-gray-400 mt-0.5; }
    .section-body { @apply p-5; }
    .gauge-wrapper { @apply flex flex-col items-center justify-center; }
    .gauge-label { @apply text-sm font-semibold text-gray-600 dark:text-gray-300 mt-2; }
    .gauge-value { @apply text-3xl font-black; }
    .alert-item { @apply flex items-start gap-3 p-3 rounded-xl transition-colors; }
    .alert-item--critical { @apply bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-800/50; }
    .alert-item--warning { @apply bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800/50; }
    .alert-item--info { @apply bg-sky-50 dark:bg-sky-900/10 border border-sky-200 dark:border-sky-800/50; }
    .kudos-card { @apply flex items-center gap-4 p-4 rounded-xl bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/10 dark:to-teal-900/10 border border-emerald-200 dark:border-emerald-800/40; }
    .pulse-dot { @apply w-2 h-2 rounded-full bg-emerald-400 animate-pulse inline-block; }
</style>
@endpush

@section('content')
<div x-data="metricsRefresh" class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white tracking-tight">{{ __('metrics.executive_dashboard') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-2">
                <span class="pulse-dot"></span>
                {{ __('metrics.updated') }} {{ now()->format(__('metrics.time_format')) }}
                <button @click="refresh" :disabled="refreshing" class="text-xs text-violet-600 dark:text-violet-400 hover:underline ml-1" title="{{ __('metrics.refresh') }}">
                    {{ __('metrics.refresh') }}
                </button>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg {{ $executiveSummary['overall_wellness_score'] >= 70 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400' : ($executiveSummary['overall_wellness_score'] >= 50 ? 'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-400' : 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400') }}">
                <span class="w-2 h-2 rounded-full {{ $executiveSummary['overall_wellness_score'] >= 70 ? 'bg-emerald-400' : ($executiveSummary['overall_wellness_score'] >= 50 ? 'bg-amber-400' : 'bg-red-400') }}"></span>
                {{ __('metrics.overall_status') }}: {{ number_format($executiveSummary['overall_wellness_score'] ?? 0, 1) }}
            </span>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        {{-- Wellness Score --}}
        <div class="kpi-card">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('metrics.wellness_score') }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M43 4.5v4.5m0 0l-3-3m3 3l3-3M2 13h4.5m1.5 0h4.5m1.5 0h4.5m1.5 0h4.5M2 21h4.5m1.5 0h4.5m1.5 0h4.5m1.5 0h4.5M2 5l0 0M21.75 1.5v15a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25v-15A2.25 2.25 0 014.5 1.5h15z" /></svg>
            </div>
            <div class="gauge-value text-3xl font-black text-gray-900 dark:text-white">{{ number_format($pulse['wellness_score'] ?? 0, 1) }}</div>
            <div class="flex items-center gap-1 mt-1">
                <span class="text-xs {{ ($pulse['wellness_trend'] ?? 0) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ ($pulse['wellness_trend'] ?? 0) >= 0 ? '↑' : '↓' }} {{ abs($pulse['wellness_trend'] ?? 0) }}%
                </span>
                <span class="text-xs text-gray-400 ml-1">{{ __('metrics.trend') }}</span>
            </div>
        </div>

        {{-- Productivity --}}
        <div class="kpi-card">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('metrics.productivity') }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
            </div>
            <div class="gauge-value text-3xl font-black text-gray-900 dark:text-white">{{ number_format($pulse['productivity_score'] ?? 0, 1) }}</div>
            <div class="flex items-center gap-1 mt-1">
                <span class="text-xs {{ ($pulse['productivity_trend'] ?? 0) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ ($pulse['productivity_trend'] ?? 0) >= 0 ? '↑' : '↓' }} {{ abs($pulse['productivity_trend'] ?? 0) }}%
                </span>
                <span class="text-xs text-gray-400 ml-1">{{ __('metrics.trend') }}</span>
            </div>
        </div>

        {{-- Engagement --}}
        <div class="kpi-card">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('metrics.engagement') }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672l-3.618-2.038a8 8 0 01-3.042-1.082L4.47 19.38a1 1 0 01-1.535-1.252l1.272-4.16a8 8 0 01-.67-3.238L2.27 8.27a1 1 0 01.565-1.69l4.27-1.27a8 8 0 013.618-.82m4.042 16.182l.618-.348a8 8 0 013.042-1.082l3.73-1.082a1 1 0 011.285.892l.18 4.27a1 1 0 01-.665 1.082l-3.73 1.082a8 8 0 01-3.042 1.082l-3.618 2.038a1 1 0 01-1.47-1.252z" /></svg>
            </div>
            <div class="gauge-value text-3xl font-black text-gray-900 dark:text-white">{{ number_format($pulse['engagement_score'] ?? 0, 1) }}</div>
            <div class="flex items-center gap-1 mt-1">
                <span class="text-xs {{ ($pulse['engagement_trend'] ?? 0) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ ($pulse['engagement_trend'] ?? 0) >= 0 ? '↑' : '↓' }} {{ abs($pulse['engagement_trend'] ?? 0) }}%
                </span>
                <span class="text-xs text-gray-400 ml-1">{{ __('metrics.trend') }}</span>
            </div>
        </div>

        {{-- Retention --}}
        <div class="kpi-card">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('metrics.retention') }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.198m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a4.968 4.968 0 00-3.548 1.31M18 12.75a4.98 4.98 0 00-3.548-1.31M12 3.75l-.818.818M12 3.75l.818.818m-4.09 4.09L6.6 9.86m10.8-4.09l-.818.818" /></svg>
            </div>
            <div class="gauge-value text-3xl font-black text-gray-900 dark:text-white">{{ number_format($pulse['retention_rate'] ?? 0, 1) }}%</div>
            <div class="flex items-center gap-1 mt-1">
                <span class="text-xs {{ ($pulse['retention_trend'] ?? 0) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ ($pulse['retention_trend'] ?? 0) >= 0 ? '↑' : '↓' }} {{ abs($pulse['retention_trend'] ?? 0) }}%
                </span>
                <span class="text-xs text-gray-400 ml-1">{{ __('metrics.trend') }}</span>
            </div>
        </div>

        {{-- Satisfaction --}}
        <div class="kpi-card">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('metrics.satisfaction') }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-pink-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.664.326.99l-4.192 3.674a.563.563 0 00-.173.558l1.284 5.283c.137.564-.407.99-.876.713L12 19.047l-4.602 2.903c-.47.297-1.012-.13-.876-.713l1.284-5.283a.563.563 0 00-.173-.558l-4.192-3.674a.562.562 0 00.326-.99l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" /></svg>
            </div>
            <div class="gauge-value text-3xl font-black text-gray-900 dark:text-white">{{ number_format($pulse['satisfaction_score'] ?? 0, 1) }}</div>
            <div class="flex items-center gap-1 mt-1">
                <span class="text-xs {{ ($pulse['satisfaction_trend'] ?? 0) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ ($pulse['satisfaction_trend'] ?? 0) >= 0 ? '↑' : '↓' }} {{ abs($pulse['satisfaction_trend'] ?? 0) }}%
                </span>
                <span class="text-xs text-gray-400 ml-1">{{ __('metrics.trend') }}</span>
            </div>
        </div>
    </div>

    {{-- Row 1: Organizational Wellness Gauge + Productivity Area Chart --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Organizational Wellness Gauge --}}
        <div class="section-card lg:col-span-1">
            <div class="section-header">
                <h2 class="section-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ __('metrics.org_wellness') }}
                </h2>
                <p class="section-subtitle">{{ __('metrics.overall_organizational_health') }}</p>
            </div>
            <div class="section-body">
                <div class="gauge-wrapper">
                    <div id="orgWellnessGauge"></div>
                </div>
            </div>
        </div>

        {{-- Organizational Productivity Area Chart --}}
        <div class="section-card lg:col-span-2">
            <div class="section-header">
                <h2 class="section-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                    {{ __('metrics.org_productivity') }}
                </h2>
                <p class="section-subtitle">{{ __('metrics.last_12_months_with_projection') }}</p>
            </div>
            <div class="section-body">
                <div id="orgProductivityChart" class="chart-container"></div>
            </div>
        </div>
    </div>

    {{-- Row 2: Engagement Gauge + Team Health Radar --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Engagement Gauge --}}
        <div class="section-card lg:col-span-1">
            <div class="section-header">
                <h2 class="section-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672l-3.618-2.038a8 8 0 01-3.042-1.082L4.47 19.38a1 1 0 01-1.535-1.252l1.272-4.16a8 8 0 01-.67-3.238L2.27 8.27a1 1 0 01.565-1.69l4.27-1.27a8 8 0 013.618-.82m4.042 16.182l.618-.348a8 8 0 013.042-1.082l3.73-1.082a1 1 0 011.285.892l.18 4.27a1 1 0 01-.665 1.082l-3.73 1.082a8 8 0 01-3.042 1.082l-3.618 2.038a1 1 0 01-1.47-1.252z" /></svg>
                    {{ __('metrics.org_engagement') }}
                </h2>
                <p class="section-subtitle">{{ __('metrics.current_engagement_level') }}</p>
            </div>
            <div class="section-body">
                <div class="gauge-wrapper">
                    <div id="orgEngagementGauge"></div>
                </div>
            </div>
        </div>

        {{-- Team Health Radar --}}
        <div class="section-card lg:col-span-2">
            <div class="section-header">
                <h2 class="section-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" /></svg>
                    {{ __('metrics.team_health') }}
                </h2>
                <p class="section-subtitle">{{ __('metrics.five_key_metrics') }}</p>
            </div>
            <div class="section-body">
                <div id="teamHealthRadar" class="chart-container"></div>
            </div>
        </div>
    </div>

    {{-- Row 3: Satisfaction Trend + Burnout Risk Donut --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Satisfaction Trend --}}
        <div class="section-card lg:col-span-2">
            <div class="section-header">
                <h2 class="section-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-pink-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.664.326.99l-4.192 3.674a.563.563 0 00-.173.558l1.284 5.283c.137.564-.407.99-.876.713L12 19.047l-4.602 2.903c-.47.297-1.012-.13-.876-.713l1.284-5.283a.563.563 0 00-.173-.558l-4.192-3.674a.562.562 0 00.326-.99l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" /></svg>
                    {{ __('metrics.satisfaction_trend') }}
                </h2>
                <p class="section-subtitle">{{ __('metrics.nps_and_overall_satisfaction_last_12_months') }}</p>
            </div>
            <div class="section-body">
                <div id="satisfactionTrendChart" class="chart-container"></div>
            </div>
        </div>

        {{-- Burnout Risk Donut --}}
        <div class="section-card lg:col-span-1">
            <div class="section-header">
                <h2 class="section-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                    {{ __('metrics.burnout_risk') }}
                </h2>
                <p class="section-subtitle">{{ __('metrics.by_team') }}</p>
            </div>
            <div class="section-body">
                <div id="burnoutDonutChart" class="chart-container"></div>
            </div>
        </div>
    </div>

    {{-- Row 4: Talent Retention + Wellness Investment ROI --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Talent Retention Line Chart --}}
        <div class="section-card lg:col-span-2">
            <div class="section-header">
                <h2 class="section-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.198m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a4.968 4.968 0 00-3.548 1.31M18 12.75a4.98 4.98 0 00-3.548-1.31M12 3.75l-.818.818M12 3.75l.818.818m-4.09 4.09L6.6 9.86m10.8-4.09l-.818.818" /></svg>
                    {{ __('metrics.talent_retention') }}
                </h2>
                <p class="section-subtitle">{{ __('metrics.monthly_rate_with_benchmark') }}</p>
            </div>
            <div class="section-body">
                <div id="retentionChart" class="chart-container"></div>
            </div>
        </div>

        {{-- Wellness Investment ROI --}}
        <div class="section-card lg:col-span-1">
            <div class="section-header">
                <h2 class="section-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ __('metrics.wellness_investment') }}
                </h2>
                <p class="section-subtitle">{{ __('metrics.roi_of_wellness_initiatives') }}</p>
            </div>
            <div class="section-body">
                <div id="wellnessInvestmentChart" class="chart-container"></div>
            </div>
        </div>
    </div>

    {{-- Row 5: Critical Alerts + Capacity Vision --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Critical Alerts Panel --}}
        <div class="section-card lg:col-span-1">
            <div class="section-header">
                <h2 class="section-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
                    {{ __('metrics.critical_alerts') }}
                    <span class="ml-auto inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold rounded-full {{ count($criticalAlerts) > 0 ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' }}">
                        {{ count($criticalAlerts) }}
                    </span>
                </h2>
                <p class="section-subtitle">{{ __('metrics.actions_required') }}</p>
            </div>
            <div class="section-body max-h-[400px] overflow-y-auto">
                @forelse($criticalAlerts as $alert)
                    <div class="alert-item alert--{{ $alert['severity'] ?? 'warning' }} mb-2 last:mb-0">
                        <div class="flex-shrink-0 mt-0.5">
                            @if($alert['severity'] === 'critical')
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                            @elseif($alert['severity'] === 'warning')
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $alert['message'] ?? $alert['title'] ?? __('metrics.alert') }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ $alert['team'] ?? $alert['department'] ?? __('metrics.organization') }} &middot; {{ \Carbon\Carbon::parse($alert['created_at'] ?? now())->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">{{ __('metrics.no_critical_alerts') }}</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Capacity Vision Bar Chart --}}
        <div class="section-card lg:col-span-2">
            <div class="section-header">
                <h2 class="section-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                    {{ __('metrics.capacity_vision') }}
                </h2>
                <p class="section-subtitle">{{ __('metrics.capacity_vs_demand_by_team') }}</p>
            </div>
            <div class="section-body">
                <div id="capacityVisionChart" class="chart-container"></div>
            </div>
        </div>
    </div>

    {{-- Row 6: Communication Trends --}}
    <div class="grid grid-cols-1 gap-6">
        <div class="section-card">
            <div class="section-header">
                <h2 class="section-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" /></svg>
                    {{ __('metrics.communication_trends') }}
                </h2>
                <p class="section-subtitle">{{ __('metrics.message_volume_and_response_times') }}</p>
            </div>
            <div class="section-body">
                <div id="communicationTrendsChart" class="chart-container"></div>
            </div>
        </div>
    </div>

    {{-- Row 7: Survey Summary Cards + Kudos --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Survey Summary Cards --}}
        <div class="section-card lg:col-span-2">
            <div class="section-header">
                <h2 class="section-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                    {{ __('metrics.survey_summary') }}
                </h2>
                <p class="section-subtitle">{{ __('metrics.latest_survey_results') }}</p>
            </div>
            <div class="section-body">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @php
                        $surveyCards = [
                            ['label' => __('metrics.response_rate'), 'value' => ($pulse['response_rate'] ?? 0) . '%', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.198m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a4.968 4.968 0 00-3.548 1.31M18 12.75a4.98 4.98 0 00-3.548-1.31M12 3.75l-.818.818M12 3.75l.818.818m-4.09 4.09L6.6 9.86m10.8-4.09l-.818.818" />', 'color' => 'violet'],
                            ['label' => __('metrics.survey_count'), 'value' => $pulse['total_surveys'] ?? 0, 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />', 'color' => 'sky'],
                            ['label' => __('metrics.avg_completion_time'), 'value' => ($pulse['avg_completion_time'] ?? 0) . ' min', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />', 'color' => 'amber'],
                            ['label' => __('metrics.net_promoter_score'), 'value' => $pulse['nps_score'] ?? 'N/A', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.664.326.99l-4.192 3.674a.563.563 0 00-.173.558l1.284 5.283c.137.564-.407.99-.876.713L12 19.047l-4.602 2.903c-.47.297-1.012-.13-.876-.713l1.284-5.283a.563.563 0 00-.173-.558l-4.192-3.674a.562.562 0 00.326-.99l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />', 'color' => 'emerald'],
                            ['label' => __('metrics.top_concern'), 'value' => $pulse['top_concern'] ?? __('metrics.none'), 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />', 'color' => 'red'],
                            ['label' => __('metrics.top_positive'), 'value' => $pulse['top_positive'] ?? __('metrics.none'), 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.664.326.99l-4.192 3.674a.563.563 0 00-.173.558l1.284 5.283c.137.564-.407.99-.876.713L12 19.047l-4.602 2.903c-.47.297-1.012-.13-.876-.713l1.284-5.283a.563.563 0 00-.173-.558l-4.192-3.674a.562.562 0 00.326-.99l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />', 'color' => 'emerald'],
                        ];
                    @endphp
                    @foreach($surveyCards as $card)
                        <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="p-2 rounded-lg bg-{{ $card['color'] }}-100 dark:bg-{{ $card['color'] }}-900/20 text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">{{ $card['icon'] }}</svg>
                                </div>
                                <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ $card['label'] }}</span>
                            </div>
                            <p class="text-lg font-black text-gray-900 dark:text-white">{{ $card['value'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Organizational Kudos Card --}}
        <div class="section-card">
            <div class="section-header">
                <h2 class="section-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.664.326.99l-4.192 3.674a.563.563 0 00-.173.558l1.284 5.283c.137.564-.407.99-.876.713L12 19.047l-4.602 2.903c-.47.297-1.012-.13-.876-.713l1.284-5.283a.563.563 0 00-.173-.558l-4.192-3.674a.562.562 0 00.326-.99l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" /></svg>
                    {{ __('metrics.org_kudos') }}
                </h2>
                <p class="section-subtitle">{{ __('metrics.this_month') }}</p>
            </div>
            <div class="section-body">
                <div class="kudos-card mb-4">
                    <div class="flex-shrink-0 w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.664.326.99l-4.192 3.674a.563.563 0 00-.173.558l1.284 5.283c.137.564-.407.99-.876.713L12 19.047l-4.602 2.903c-.47.297-1.012-.13-.876-.713l1.284-5.283a.563.563 0 00-.173-.558l-4.192-3.674a.562.562 0 00.326-.99l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" /></svg>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-gray-900 dark:text-white">{{ $pulse['total_kudos'] ?? 0 }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-300 font-medium">{{ __('metrics.total_kudos_this_month') }}</p>
                    </div>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-3">{{ __('metrics.top_teams_in_recognition') }}</p>
                    <div class="space-y-2">
                        @php
                            $topTeams = $pulse['top_teams_kudos'] ?? $pulse['team_comparison'] ?? [];
                            $rank = 0;
                        @endphp
                        @foreach(array_slice($topTeams, 0, 3) as $team)
                            @php $rank++; @endphp
                            <div class="flex items-center gap-3 p-2.5 rounded-lg bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                                <span class="flex-shrink-0 w-7 h-7 rounded-full {{ $rank === 1 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : ($rank === 2 ? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' : 'bg-orange-50 text-orange-700 dark:bg-orange-900/20 dark:text-orange-400') }} flex items-center justify-center text-xs font-black">
                                    {{ $rank }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $team['name'] ?? $team['team'] ?? __('metrics.team') }} {{ $rank }}</p>
                                </div>
                                <span class="text-xs font-bold text-gray-500 dark:text-gray-400">{{ $team['kudos_count'] ?? $team['count'] ?? 0 }}</span>
                            </div>
                        @endforeach
                        @if(empty($topTeams))
                            <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-4">{{ __('metrics.no_kudos_yet') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isDark = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? '#2d3748' : '#e5e7eb';
    const textColor = isDark ? '#9ca3af' : '#6b7280';
    const tooltipBg = isDark ? '#1f2937' : '#ffffff';
    const tooltipText = isDark ? '#f3f4f6' : '#1f2937';
    const tooltipBorder = isDark ? '#374151' : '#e5e7eb';

    const commonOptions = {
        chart: { type: 'bar', height: 320, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
        colors: ['#8b5cf6', '#06b6d4'],
        grid: { borderColor: gridColor, strokeDashArray: 3, xaxis: { lines: { show: false } } },
        stroke: { curve: 'smooth', width: 2 },
        tooltip: { theme: isDark ? 'dark' : 'light', style: { fontSize: '12px' }, backgroundColor: tooltipBg, textColor: tooltipText, borderColor: tooltipBorder },
        legend: { labels: { colors: textColor }, fontSize: '12px', fontWeight: 600 },
    };

    // --- Organizational Wellness Gauge (Circular Gauge) ---
    const wellnessScore = {{ $pulse['wellness_score'] ?? 0 }};
    new ApexCharts(document.querySelector('#orgWellnessGauge'), {
        chart: { type: 'radialBar', height: 320, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
        series: [wellnessScore],
        colors: [wellnessScore >= 70 ? '#10b981' : (wellnessScore >= 50 ? '#f59e0b' : '#ef4444')],
        plotOptions: {
            radialBar: {
                hollow: { size: '65%' },
                dataLabels: {
                    name: { fontSize: '14px', fontWeight: 600, color: textColor, offsetY: 8 },
                    value: { fontSize: '36px', fontWeight: 900, color: '#111827', offsetY: -8, formatter: (val) => val.toFixed(1) },
                    total: {
                        show: true,
                        label: '{{ __("metrics.org_wellness") }}',
                        fontSize: '12px',
                        fontWeight: 600,
                        color: textColor,
                        formatter: () => wellnessScore.toFixed(1) + '/100'
                    }
                },
                track: { background: isDark ? '#1f2937' : '#f3f4f6' }
            }
        },
        stroke: { lineCap: 'round' },
        labels: ['{{ __("metrics.wellness_score") }}'],
    }).render();

    // --- Organizational Productivity Area Chart (12 months + projection) ---
    const prodData = {{ json_encode($1 ?? []) }};
    const prodMonths = prodData.map(d => d.month || d.date || '');
    const prodActual = prodData.map(d => d.value || d.score || 0);
    const prodProjection = prodData.map(d => d.projection || d.forecast || 0);

    new ApexCharts(document.querySelector('#orgProductivityChart'), {
        chart: { type: 'area', height: 360, toolbar: { show: false }, fontFamily: 'Inter, sans-serif', zoom: { enabled: true } },
        series: [
            { name: '{{ __("metrics.actual") }}', data: prodActual },
            { name: '{{ __("metrics.projection") }}', data: prodProjection }
        ],
        colors: ['#8b5cf6', '#06b6d4'],
        fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05, stops: [0, 100] } },
        stroke: { curve: 'smooth', width: [2, 2], dashArray: [0, 6] },
        xaxis: { categories: prodMonths, labels: { style: { fontSize: '11px', colors: textColor } }, axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: { labels: { style: { fontSize: '11px', colors: textColor }, formatter: (v) => v.toFixed(1) } },
        grid: { borderColor: gridColor, strokeDashArray: 3, xaxis: { lines: { show: false } } },
        tooltip: { theme: isDark ? 'dark' : 'light', style: { fontSize: '12px' }, backgroundColor: tooltipBg, textColor: tooltipText, borderColor: tooltipBorder },
        legend: { labels: { colors: textColor }, fontSize: '12px', fontWeight: 600 },
        markers: { size: 3 },
    }).render();

    // --- Organizational Engagement Gauge ---
    const engagementScore = {{ $pulse['engagement_score'] ?? 0 }};
    new ApexCharts(document.querySelector('#orgEngagementGauge'), {
        chart: { type: 'radialBar', height: 320, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
        series: [engagementScore],
        colors: [engagementScore >= 70 ? '#f59e0b' : (engagementScore >= 50 ? '#f97316' : '#ef4444')],
        plotOptions: {
            radialBar: {
                hollow: { size: '65%' },
                dataLabels: {
                    name: { fontSize: '14px', fontWeight: 600, color: textColor, offsetY: 8 },
                    value: { fontSize: '36px', fontWeight: 900, color: '#111827', offsetY: -8, formatter: (val) => val.toFixed(1) },
                    total: {
                        show: true,
                        label: '{{ __("metrics.org_engagement") }}',
                        fontSize: '12px',
                        fontWeight: 600,
                        color: textColor,
                        formatter: () => engagementScore.toFixed(1) + '/100'
                    }
                },
                track: { background: isDark ? '#1f2937' : '#f3f4f6' }
            }
        },
        stroke: { lineCap: 'round' },
        labels: ['{{ __("metrics.engagement") }}'],
    }).render();

    // --- Team Health Radar Chart ---
    const healthData = {{ json_encode($1 ?? []) }};
    const healthLabels = healthData.map(d => d.metric || d.label || '');
    const healthValues = healthData.map(d => d.value || d.score || 0);

    new ApexCharts(document.querySelector('#teamHealthRadar'), {
        chart: { type: 'radar', height: 380, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
        series: [{ name: '{{ __("metrics.team_health_score") }}', data: healthValues }],
        colors: ['#14b8a6'],
        xaxis: { categories: healthLabels },
        fill: { opacity: 0.2 },
        stroke: { width: 2, curve: 'smooth' },
        markers: { size: 4 },
        yaxis: { max: 100, labels: { style: { fontSize: '11px', colors: textColor }, formatter: (v) => v.toFixed(0) } },
        grid: { borderColor: gridColor, strokeDashArray: 3 },
        tooltip: { theme: isDark ? 'dark' : 'light', style: { fontSize: '12px' }, backgroundColor: tooltipBg, textColor: tooltipText, borderColor: tooltipBorder },
        legend: { labels: { colors: textColor }, fontSize: '12px', fontWeight: 600 },
    }).render();

    // --- Satisfaction Trend Line Chart (NPS + Overall Satisfaction, 12 months) ---
    const satData = {{ json_encode($1 ?? []) }};
    const satMonths = satData.map(d => d.month || d.date || '');
    const satNps = satData.map(d => d.nps || d.nps_score || 0);
    const satOverall = satData.map(d => d.overall || d.satisfaction || 0);

    new ApexCharts(document.querySelector('#satisfactionTrendChart'), {
        chart: { type: 'line', height: 360, toolbar: { show: false }, fontFamily: 'Inter, sans-serif', zoom: { enabled: true } },
        series: [
            { name: 'NPS', data: satNps },
            { name: '{{ __("metrics.overall_satisfaction") }}', data: satOverall }
        ],
        colors: ['#10b981', '#ec4899'],
        stroke: { curve: 'smooth', width: [2.5, 2.5] },
        xaxis: { categories: satMonths, labels: { style: { fontSize: '11px', colors: textColor } }, axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: { labels: { style: { fontSize: '11px', colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 3, xaxis: { lines: { show: false } } },
        tooltip: { theme: isDark ? 'dark' : 'light', style: { fontSize: '12px' }, backgroundColor: tooltipBg, textColor: tooltipText, borderColor: tooltipBorder },
        legend: { labels: { colors: textColor }, fontSize: '12px', fontWeight: 600 },
        markers: { size: 4, hover: { size: 6 } },
    }).render();

    // --- Burnout Risk Donut Chart (Low/Medium/High by Team) ---
    const burnoutData = {{ json_encode($1 ?? []) }};
    const burnoutLabels = burnoutData.map(d => d.level || d.category || '');
    const burnoutValues = burnoutData.map(d => d.count || d.value || 0);

    new ApexCharts(document.querySelector('#burnoutDonutChart'), {
        chart: { type: 'donut', height: 320, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
        series: burnoutValues,
        labels: burnoutLabels,
        colors: ['#10b981', '#f59e0b', '#ef4444'],
        legend: { position: 'bottom', labels: { colors: textColor }, fontSize: '12px', fontWeight: 600 },
        plotOptions: {
            pie: {
                donut: {
                    size: '65%',
                    labels: {
                        show: true,
                        name: { fontSize: '13px', fontWeight: 600, color: textColor },
                        value: { fontSize: '28px', fontWeight: 900, color: '#111827', formatter: (val) => val },
                        total: {
                            show: true,
                            label: '{{ __("metrics.total") }}',
                            fontSize: '13px',
                            fontWeight: 600,
                            color: textColor,
                            formatter: () => burnoutValues.reduce((a, b) => a + b, 0)
                        }
                    }
                }
            }
        },
        stroke: { width: 0 },
        dataLabels: { enabled: false },
        tooltip: { theme: isDark ? 'dark' : 'light', style: { fontSize: '12px' }, backgroundColor: tooltipBg, textColor: tooltipText, borderColor: tooltipBorder, fillSeriesColor: false },
    }).render();

    // --- Talent Retention Line Chart (Monthly Rate with Benchmark) ---
    const retentionData = {{ json_encode($1 ?? []) }};
    const retTeams = retentionData.map(d => d.team || d.name || '');
    const retRates = retentionData.map(d => d.rate || d.retention_rate || 0);
    const retBenchmark = retRates.map(() => 85);

    new ApexCharts(document.querySelector('#retentionChart'), {
        chart: { type: 'line', height: 360, toolbar: { show: false }, fontFamily: 'Inter, sans-serif', zoom: { enabled: true } },
        series: [
            { name: '{{ __("metrics.retention_rate") }}', data: retRates },
            { name: '{{ __("metrics.benchmark") }}', data: retBenchmark }
        ],
        colors: ['#0ea5e9', '#94a3b8'],
        stroke: { curve: 'smooth', width: [2.5, 2], dashArray: [0, 8] },
        xaxis: { categories: retTeams, labels: { style: { fontSize: '11px', colors: textColor } }, axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: { min: 0, max: 100, labels: { style: { fontSize: '11px', colors: textColor }, formatter: (v) => v + '%' } },
        grid: { borderColor: gridColor, strokeDashArray: 3, xaxis: { lines: { show: false } } },
        tooltip: { theme: isDark ? 'dark' : 'light', style: { fontSize: '12px' }, backgroundColor: tooltipBg, textColor: tooltipText, borderColor: tooltipBorder },
        legend: { labels: { colors: textColor }, fontSize: '12px', fontWeight: 600 },
        markers: { size: 4 },
    }).render();

    // --- Wellness Investment Bar Chart (ROI of Wellness Initiatives) ---
    const roiData = {{ json_encode($1 ?? []) }};
    const roiLabels = roiData.map(d => d.initiative || d.label || '');
    const roiInvestment = roiData.map(d => d.investment || d.cost || 0);
    const roiReturn = roiData.map(d => d.return || d.roi || 0);

    new ApexCharts(document.querySelector('#wellnessInvestmentChart'), {
        chart: { type: 'bar', height: 380, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
        series: [
            { name: '{{ __("metrics.investment") }}', data: roiInvestment },
            { name: '{{ __("metrics.return") }}', data: roiReturn }
        ],
        colors: ['#8b5cf6', '#10b981'],
        plotOptions: { bar: { horizontal: false, columnWidth: '55%', borderRadius: 6 } },
        dataLabels: { enabled: false },
        stroke: { show: true, width: [0, 0], curve: 'smooth' },
        xaxis: { categories: roiLabels, labels: { style: { fontSize: '10px', colors: textColor, rotate: -45 } }, axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: { labels: { style: { fontSize: '11px', colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 3, xaxis: { lines: { show: false } } },
        tooltip: { theme: isDark ? 'dark' : 'light', style: { fontSize: '12px' }, backgroundColor: tooltipBg, textColor: tooltipText, borderColor: tooltipBorder },
        legend: { labels: { colors: textColor }, fontSize: '12px', fontWeight: 600 },
        fill: { opacity: 1 },
    }).render();

    // --- Capacity Vision Bar Chart (Capacity vs Demand by Team) ---
    const capData = {{ json_encode($1 ?? []) }};
    const capLabels = capData.map(d => d.team || d.name || '');
    const capCapacity = capData.map(d => d.capacity || d.available_capacity || 0);
    const capDemand = capData.map(d => d.demand || d.workload || 0);

    new ApexCharts(document.querySelector('#capacityVisionChart'), {
        chart: { type: 'bar', height: 360, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
        series: [
            { name: '{{ __("metrics.capacity") }}', data: capCapacity },
            { name: '{{ __("metrics.demand") }}', data: capDemand }
        ],
        colors: ['#06b6d4', '#f59e0b'],
        plotOptions: { bar: { horizontal: false, columnWidth: '55%', borderRadius: 6, dataLabels: { position: 'top' } } },
        dataLabels: { enabled: true, offsetY: -4, style: { fontSize: '10px', fontWeight: 700, colors: [isDark ? '#f3f4f6' : '#111827'] } },
        xaxis: { categories: capLabels, labels: { style: { fontSize: '11px', colors: textColor } }, axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: { labels: { style: { fontSize: '11px', colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 3, xaxis: { lines: { show: false } } },
        tooltip: { theme: isDark ? 'dark' : 'light', style: { fontSize: '12px' }, backgroundColor: tooltipBg, textColor: tooltipText, borderColor: tooltipBorder },
        legend: { labels: { colors: textColor }, fontSize: '12px', fontWeight: 600 },
        fill: { opacity: 1 },
    }).render();

    // --- Communication Trends Line Chart ---
    const commData = {{ json_encode($1 ?? []) }};
    const commLabels = commData.map(d => d.month || d.date || d.label || '');
    const commMessages = commData.map(d => d.messages || d.message_count || 0);
    const commResponses = commData.map(d => d.responses || d.response_time || 0);

    new ApexCharts(document.querySelector('#communicationTrendsChart'), {
        chart: { type: 'line', height: 360, toolbar: { show: false }, fontFamily: 'Inter, sans-serif', zoom: { enabled: true } },
        series: [
            { name: '{{ __("metrics.messages_sent") }}', data: commMessages },
            { name: '{{ __("metrics.avg_response_hours") }}', data: commResponses }
        ],
        colors: ['#8b5cf6', '#06b6d4'],
        stroke: { curve: 'smooth', width: [2.5, 2] },
        xaxis: { categories: commLabels, labels: { style: { fontSize: '11px', colors: textColor } }, axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: { labels: { style: { fontSize: '11px', colors: textColor } } },
        grid: { borderColor: gridColor, strokeDashArray: 3, xaxis: { lines: { show: false } } },
        tooltip: { theme: isDark ? 'dark' : 'light', style: { fontSize: '12px' }, backgroundColor: tooltipBg, textColor: tooltipText, borderColor: tooltipBorder },
        legend: { labels: { colors: textColor }, fontSize: '12px', fontWeight: 600 },
        markers: { size: 4, hover: { size: 6 } },
    }).render();
});
</script>
@endpush
