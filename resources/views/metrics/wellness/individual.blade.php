@extends('metrics.layouts.app')

@section('title', __('metrics.wellness.individual_title') . ' - ' . $member->name)
@section('breadcrumb', __('metrics.wellness.individual_breadcrumb'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white" style="font-family:'Space Grotesk',sans-serif">
                {{ __('metrics.wellness.individual_heading') }}: {{ $member->name }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ __('metrics.wellness.individual_description') }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('metrics.wellness.index') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 dark:bg-gray-900 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800 transition-colors shadow-sm">
                {{ __('metrics.wellness.back_to_dashboard') }}
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Wellness Score --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-6 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-10">
                <svg class="w-16 h-16 text-emerald-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm4.59-12.42L10 14.17l-2.59-2.58L6 13l4 4 8-8z"/></svg>
            </div>
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">{{ __('metrics.wellness.wellness_index') }}</h3>
            <div class="text-4xl font-bold text-gray-900 dark:text-white mb-2">{{ $wellnessScore['wellness_score'] }}</div>
            <div class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium {{ $wellnessScore['trend'] === 'improving' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : ($wellnessScore['trend'] === 'declining' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400') }}">
                {{ __('metrics.wellness.trend') }}: {{ ucfirst($wellnessScore['trend']) }}
            </div>
        </div>

        {{-- Burnout Risk --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-6 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-10">
                <svg class="w-16 h-16 {{ $burnoutRisk['level'] === 'high' ? 'text-red-500' : ($burnoutRisk['level'] === 'medium' ? 'text-amber-500' : 'text-emerald-500') }}" fill="currentColor" viewBox="0 0 24 24"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
            </div>
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">{{ __('metrics.burnout_risk') }}</h3>
            <div class="text-4xl font-bold mb-2 {{ $burnoutRisk['level'] === 'high' ? 'text-red-500' : ($burnoutRisk['level'] === 'medium' ? 'text-amber-500' : 'text-emerald-500') }}">
                {{ strtoupper(__('metrics.wellness.risk_levels.' . $burnoutRisk['level'])) }}
            </div>
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ __('metrics.wellness.score_label') }}: {{ $burnoutRisk['score'] }}
            </div>
        </div>

        {{-- Work Life Balance --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-6 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-10">
                <svg class="w-16 h-16 text-blue-500" fill="currentColor" viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
            </div>
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">{{ __('metrics.wellness.work_life_balance') }}</h3>
            <div class="text-4xl font-bold text-gray-900 dark:text-white mb-2">{{ $workLifeBalance['work_life_balance_index'] }}</div>
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ __('metrics.wellness.overtime_label') }}: {{ $workLifeBalance['overtime_hours'] }}h
            </div>
        </div>
        
        {{-- Mood Index --}}
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-6 relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-10">
                <svg class="w-16 h-16 text-purple-500" fill="currentColor" viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/></svg>
            </div>
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">{{ __('metrics.wellness.mood_index') }}</h3>
            <div class="text-4xl font-bold text-gray-900 dark:text-white mb-2">{{ $wellnessScore['mood_index'] }}</div>
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ __('metrics.wellness.energy_label') }}: {{ $wellnessScore['energy_index'] }} / 100
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-6">
             <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">{{ __('metrics.wellness.mood_heatmap') }}</h2>
             <div class="overflow-x-auto overflow-y-hidden">
                <div id="moodHeatmap" style="min-width: 600px;"></div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm p-6">
            <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">{{ __('metrics.wellness.risk_factors') }}</h2>
            @if(count($burnoutRisk['factors']) > 0)
                <div class="space-y-3">
                    @foreach($burnoutRisk['factors'] as $factor)
                        <div class="flex items-start gap-3 p-3 rounded-xl bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800/30">
                            <span class="w-2.5 h-2.5 mt-1.5 rounded-full bg-amber-500 flex-shrink-0"></span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('metrics.wellness.risk_factors_list.' . $factor) }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex items-center gap-3 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-200 dark:border-emerald-800/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm text-emerald-700 dark:text-emerald-400">{{ __('metrics.wellness.no_risk_factors') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const heatMapData = @json($moodHeatmap);
    const heatmapSeries = heatMapData.map(d => ({
        x: d.date ? new Date(d.date).toLocaleDateString('es-ES', { day: 'numeric', month: 'short' }) : '',
        y: d.mood !== null ? d.mood : null,
    }));

    if (heatmapSeries.length > 0) {
        new ApexCharts(document.querySelector('#moodHeatmap'), {
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
            series: [{ name: '{{ __('metrics.wellness.avg_mood') }}', data: heatmapSeries }],
            xaxis: { labels: { rotate: -45, style: { fontSize: '10px' } } },
            theme: { mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
            tooltip: { theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light' },
        }).render();
    } else {
        document.querySelector('#moodHeatmap').innerHTML = '<p class="text-sm text-gray-400 text-center mt-12">{{ __('metrics.wellness.no_data') }}</p>';
    }
});
</script>
@endpush
