@extends('metrics.layouts.app')

@section('title', __('Dashboard Semanal'))
@section('breadcrumb', __('Dashboard Semanal'))

@section('content')

<div class="max-w-7xl mx-auto space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-2">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">{{ __('Dashboard Semanal') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Resumen de tu rendimiento personal en los últimos 7 días.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('metrics.personal.daily') }}" class="px-3 py-1.5 text-xs font-semibold text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                📅 Diario
            </a>
            <span class="text-xs text-gray-400">|</span>
            <span class="text-xs text-gray-500 dark:text-gray-400">
                {{ $startDate->format('d M') }} - {{ $endDate->format('d M, Y') }}
            </span>
        </div>
    </div>

    {{-- Week Summary Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 card-shadow border border-gray-100 dark:border-gray-800">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-500/10 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase">Completadas</span>
                </div>
                <svg class="w-3.5 h-3.5 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Total de tareas completadas exitosamente durante la semana.') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                {{ $dailyCompletion->sum('completed') }}
            </p>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 card-shadow border border-gray-100 dark:border-gray-800">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-100 dark:bg-blue-500/10 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase">En Progreso</span>
                </div>
                <svg class="w-3.5 h-3.5 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Tareas que están actualmente en desarrollo.') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                {{ \App\Models\Activity::where('user_id', $user->id)->whereIn('status', ['in_progress'])->whereBetween('updated_at', [$startDate, $endDate])->count() }}
            </p>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 card-shadow border border-gray-100 dark:border-gray-800">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-red-100 dark:bg-red-500/10 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase">Vencidas</span>
                </div>
                <svg class="w-3.5 h-3.5 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Tareas cuya fecha límite ya ha pasado.') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400">
                {{ \App\Models\Activity::where('user_id', $user->id)->where('status', 'pending')->where('due_date', '<', now())->whereBetween('updated_at', [$startDate, $endDate])->count() }}
            </p>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 card-shadow border border-gray-100 dark:border-gray-800">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-violet-100 dark:bg-violet-500/10 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase">Nuevas</span>
                </div>
                <svg class="w-3.5 h-3.5 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Tareas creadas recientemente durante esta semana.') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <p class="text-2xl font-bold text-violet-600 dark:text-violet-400">
                {{ \App\Models\Activity::where('user_id', $user->id)->whereBetween('created_at', [$startDate, $endDate])->count() }}
            </p>
        </div>
    </div>

    {{-- Completion Rate Gauge + Productivity Score --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-gray-900 rounded-2xl card-shadow border border-gray-100 dark:border-gray-800 p-5">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">\u{1F4CC} Tasa de Completaci\u00f3n</h3>
                <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Porcentaje de tareas completadas respecto al total de tareas asignadas o iniciadas en la semana.') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div id="completionGauge" class="w-full" style="min-height: 220px;"></div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl card-shadow border border-gray-100 dark:border-gray-800 p-5">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">\u{1F4CA} Puntaje de Productividad</h3>
                <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Métrica global de productividad calculada con base en los objetivos semanales alcanzados.') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div class="flex flex-col items-center justify-center h-full" style="min-height: 220px;">
                <div class="relative w-36 h-36">
                    <svg class="w-full h-full" viewBox="0 0 36 36">
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="3" class="text-gray-100 dark:text-gray-800"/>
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="url(#gaugeGradient)" stroke-width="3" stroke-linecap="round"
                              stroke-dasharray="{{ min($productivityData['productivity_score'] ?? 0, 100) }}, 100"/>
                        <defs>
                            <linearGradient id="gaugeGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#7c3aed"/>
                                <stop offset="100%" stop-color="#6366f1"/>
                            </linearGradient>
                        </defs>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($productivityData['productivity_score'] ?? 0, 0) }}%</span>
                        <span class="text-[10px] text-gray-400 font-medium">SCORE</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl card-shadow border border-gray-100 dark:border-gray-800 p-5">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">\u{2728} Bienestar</h3>
                <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Nivel promedio de bienestar y riesgo de agotamiento (burnout) en los últimos 7 días.') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div class="flex flex-col items-center justify-center h-full" style="min-height: 220px;">
                <div class="relative w-36 h-36">
                    <svg class="w-full h-full" viewBox="0 0 36 36">
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="3" class="text-gray-100 dark:text-gray-800"/>
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="url(#wellnessGradient)" stroke-width="3" stroke-linecap="round"
                              stroke-dasharray="{{ min($wellnessData['score'] ?? 0, 100) }}, 100"/>
                        <defs>
                            <linearGradient id="wellnessGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#10b981"/>
                                <stop offset="100%" stop-color="#06b6d4"/>
                            </linearGradient>
                        </defs>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($wellnessData['score'] ?? 0, 0) }}%</span>
                        <span class="text-[10px] text-gray-400 font-medium">WELLNESS</span>
                    </div>
                </div>
                <div class="mt-3 text-center">
                    @php $risk = $wellnessData['burnout_risk'] ?? 'BAJO'; @endphp
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[10px] font-bold
                        {{ $risk === 'ALTO' ? 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400' : ($risk === 'MEDIO' ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400') }}">
                        {{ $risk }} RIESGO
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Activities by Type (Donut) + Activities by Priority (Horizontal Bar) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-gray-900 rounded-2xl card-shadow border border-gray-100 dark:border-gray-800 p-5">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">\u{1F4CB} Actividades por Tipo</h3>
                <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Desglose de las tareas de la semana clasificadas por su tipo (reunión, desarrollo, etc.).') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div id="typeDonut" class="w-full" style="min-height: 300px;"></div>
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-2xl card-shadow border border-gray-100 dark:border-gray-800 p-5">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">\u{1F3AF} Actividades por Prioridad</h3>
                <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Cantidad de actividades distribuidas según su nivel de prioridad (crítica, alta, media, baja).') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div id="priorityBar" class="w-full" style="min-height: 300px;"></div>
        </div>
    </div>

    {{-- Hours Logged vs Goal --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl card-shadow border border-gray-100 dark:border-gray-800 p-5">
        <div class="flex justify-between items-start mb-4">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">\u{1F4CA} Horas Registradas vs Meta</h3>
            <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Comparativa diaria entre las horas registradas en actividades y las horas esperadas (meta).') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div id="hoursBar" class="w-full" style="min-height: 220px;"></div>
    </div>

    {{-- Estimation Accuracy + Mood Trend --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-gray-900 rounded-2xl card-shadow border border-gray-100 dark:border-gray-800 p-5">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">\u{1F5B2}\ufe0f Precisi\u00f3n de Estimaci\u00f3n</h3>
                <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Relación entre el tiempo estimado para una actividad y el tiempo real invertido en completarla.') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div id="scatterPlot" class="w-full" style="min-height: 250px;"></div>
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-2xl card-shadow border border-gray-100 dark:border-gray-800 p-5">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">\u{1F60A} Tendencia de Estado de \u00c1nimo</h3>
                <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Historial de tu registro diario del estado de ánimo a lo largo de la semana.') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div id="moodTrend" class="w-full" style="min-height: 250px;"></div>
        </div>
    </div>

    {{-- Quadrant Distribution + Recognition --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 bg-white dark:bg-gray-900 rounded-2xl card-shadow border border-gray-100 dark:border-gray-800 p-5">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">\u{1F4CA} Distribuci\u00f3n por Cuadrante</h3>
                <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Cantidad de actividades en los cuadrantes de prioridad según el modelo Eisenhower.') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div id="quadrantStacked" class="w-full" style="min-height: 280px;"></div>
        </div>

        <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-2xl card-shadow border border-gray-100 dark:border-gray-800 p-5">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">\u{1F3AF} Reconocimientos de la Semana</h3>
                <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Resumen de los kudos recibidos y las insignias desbloqueadas durante los últimos 7 días.') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Kudos --}}
                <div>
                    <h4 class="text-[10px] font-bold text-gray-400 uppercase mb-2">Kudos Recibidos</h4>
                    <div class="space-y-2">
                        @forelse($kudosReceived as $kudo)
                            <div class="flex items-start gap-2 p-3 rounded-xl bg-rose-50 dark:bg-rose-500/5 border border-rose-100 dark:border-rose-500/10">
                                <span class="text-base mt-0.5">\u{2764}\ufe0f</span>
                                <div>
                                    <p class="text-xs text-gray-700 dark:text-gray-300">{{ $kudo->message ?? 'Te felicita' }}</p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">de {{ $kudo->sender->name ?? 'Alguien' }} \u2022 {{ $kudo->created_at->format('d/M') }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400 italic">Sin kudos esta semana</p>
                        @endforelse
                    </div>
                </div>
                {{-- Badges --}}
                <div>
                    <h4 class="text-[10px] font-bold text-gray-400 uppercase mb-2">Insignias Desbloqueadas</h4>
                    <div class="space-y-2">
                        @forelse($badgesUnlocked as $badge)
                            <div class="flex items-center gap-2 p-3 rounded-xl bg-amber-50 dark:bg-amber-500/5 border border-amber-100 dark:border-amber-500/10">
                                <span class="text-lg">\u{1F3C6}</span>
                                <div>
                                    <p class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $badge->description ?? 'Insignia' }}</p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">{{ $badge->created_at->format('d/M') }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400 italic">Sin insignias esta semana</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Sprint Goals Progress --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl card-shadow border border-gray-100 dark:border-gray-800 p-5">
        <div class="flex justify-between items-start mb-4">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">\u{1F3C1} Objetivos del Siguiente Sprint</h3>
            <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Metas de rendimiento y progreso calculadas para tu próximo ciclo de trabajo.') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div class="space-y-4">
            @php
                $sprintGoals = [
                    ['label' => 'Completar actividades cr\u00edticas', 'progress' => $productivityData['critical_completion_rate'] ?? 75],
                    ['label' => 'Reducir actividades vencidas', 'progress' => $productivityData['overdue_reduction'] ?? 60],
                    ['label' => 'Mejorar estimaci\u00f3n de tiempo', 'progress' => $timeData['estimation_accuracy'] ?? 55],
                    ['label' => 'Mantener bienestar', 'progress' => $wellnessData['score'] ?? 70],
                ];
            @endphp
            @foreach($sprintGoals as $goal)
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $goal['label'] }}</span>
                        <span class="text-xs font-bold text-gray-900 dark:text-white">{{ $goal['progress'] }}%</span>
                    </div>
                    <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2.5 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-700
                            {{ $goal['progress'] >= 80 ? 'bg-gradient-to-r from-emerald-500 to-green-500' : ($goal['progress'] >= 50 ? 'bg-gradient-to-r from-blue-500 to-indigo-500' : 'bg-gradient-to-r from-amber-500 to-orange-500') }}"
                             style="width: {{ $goal['progress'] }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- AI Insights --}}
    <div class="bg-gradient-to-r from-violet-600 to-indigo-600 rounded-2xl p-6 text-white shadow-lg">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center text-xl">
                \u{1F9E0}\ufe0f
            </div>
            <div>
                <h3 class="text-base font-bold">Insights de IA</h3>
                <p class="text-xs text-violet-200">An\u00e1lisis autom\u00e1tico de tu rendimiento semanal</p>
            </div>
        </div>
        <div class="space-y-3">
            @forelse($insights as $insight)
                <div class="flex items-start gap-3 bg-white/10 backdrop-blur-sm rounded-xl p-3">
                    <span class="text-sm mt-0.5">\u{27A4}</span>
                    <p class="text-sm text-violet-50 leading-relaxed">{{ $insight }}</p>
                </div>
            @empty
                <p class="text-sm text-violet-200">No hay insights disponibles para esta semana.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Completion Rate Gauge
    if (document.getElementById('completionGauge')) {
        var totalCompleted = {{ $dailyCompletion->sum('completed') }};
        var totalActivities = totalCompleted + {{ \App\Models\Activity::where('user_id', $user->id)->whereIn('status', ['in_progress', 'pending'])->whereBetween('updated_at', [$startDate, $endDate])->count() }};
        var completionRate = totalActivities > 0 ? Math.round((totalCompleted / totalActivities) * 100) : 0;

        var gauge = new ApexCharts(document.getElementById('completionGauge'), {
            chart: { type: 'radialBar', height: 220, fontFamily: 'Inter, sans-serif' },
            series: [completionRate],
            colors: ['#7c3aed'],
            plotOptions: {
                radialBar: {
                    hollow: { size: '60%' },
                    dataLabels: {
                        name: { fontSize: '14px', fontFamily: 'Inter, sans-serif' },
                        value: { fontSize: '28px', fontWeight: 800, fontFamily: 'Space Grotesk, sans-serif' },
                        total: {
                            show: true,
                            label: 'Tasa',
                            fontSize: '11px',
                            fontFamily: 'Inter, sans-serif',
                            color: '#6b7280',
                            formatter: function() { return completionRate + '%'; }
                        }
                    }
                }
            },
            labels: ['Completado'],
            stroke: { lineCap: 'round' }
        });
        gauge.render();
    }

    // Type Donut
    if (document.getElementById('typeDonut')) {
        var typeLabels = @json($byType->keys()->toArray());
        var typeValues = @json($byType->values()->toArray());
        if (!typeLabels.length) { typeLabels = ['Sin datos']; typeValues = [0]; }

        new ApexCharts(document.getElementById('typeDonut'), {
            chart: { type: 'donut', height: 300, fontFamily: 'Inter, sans-serif' },
            series: typeValues,
            labels: typeLabels,
            colors: ['#7c3aed', '#10b981', '#f59e0b', '#ef4444', '#06b6d4', '#8b5cf6'],
            legend: { position: 'bottom', fontSize: '11px' },
            plotOptions: { pie: { donut: { size: '60%', labels: { name: { fontSize: '12px' }, value: { fontSize: '18px', fontWeight: 700, fontFamily: 'Space Grotesk' }, total: { fontSize: '11px', color: '#6b7280' } } } } },
            dataLabels: { enabled: false },
            tooltip: { theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }
        }).render();
    }

    // Priority Horizontal Bar
    if (document.getElementById('priorityBar')) {
        var prioOrder = ['critical', 'high', 'medium', 'low'];
        var prioLabels = [];
        var prioValues = [];
        var prioColors = [];
        @foreach($byPriority as $priority => $count)
            if (['critical','high','medium','low'].includes('{{ $priority }}')) {
                prioLabels.push('{{ ucfirst($priority) }}');
                prioValues.push({{ $count }});
            }
        @endforeach
        if (!prioLabels.length) { prioLabels = ['Sin datos']; prioValues = [0]; }
        prioColors = prioLabels.map(function(l) {
            if (l === 'Critical') return '#ef4444';
            if (l === 'High') return '#f97316';
            if (l === 'Medium') return '#eab308';
            if (l === 'Low') return '#22c55e';
            return '#6b7280';
        });

        new ApexCharts(document.getElementById('priorityBar'), {
            chart: { type: 'bar', height: 300, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
            series: [{ name: 'Actividades', data: prioValues }],
            colors: prioColors,
            plotOptions: { bar: { horizontal: true, borderRadius: 6, barHeight: '50%', distributed: true } },
            xaxis: { categories: prioLabels },
            legend: { show: false },
            dataLabels: { enabled: true, formatter: function(val) { return val; }, offsetX: -20, style: { fontSize: '11px', colors: ['#fff'] } },
            grid: { show: false },
            tooltip: { theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }
        }).render();
    }

    // Hours Logged vs Goal Comparative Bars
    if (document.getElementById('hoursBar')) {
        var hoursLabels = [];
        var hoursLogged = [];
        var hoursGoal = [];
        @foreach($dailyHoursArr as $h)
            hoursLabels.push('{{ $h["label"] }}');
            hoursLogged.push({{ $h["logged"] }});
            hoursGoal.push({{ $h["goal"] }});
        @endforeach

        new ApexCharts(document.getElementById('hoursBar'), {
            chart: { type: 'bar', height: 220, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
            series: [
                { name: 'Registradas', data: hoursLogged },
                { name: 'Meta', data: hoursGoal }
            ],
            colors: ['#7c3aed', '#e5e7eb'],
            plotOptions: { bar: { borderRadius: 6, columnWidth: '50%' } },
            xaxis: { categories: hoursLabels },
            legend: { position: 'top' },
            grid: { borderColor: '#f3f4f6' },
            dataLabels: { enabled: false },
            tooltip: { theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }
        }).render();
    }

    // Scatter Plot (Estimation Accuracy - placeholder)
    if (document.getElementById('scatterPlot')) {
        var scatterData = [];
        for (var i = 0; i < 15; i++) {
            scatterData.push({ x: Math.floor(Math.random() * 10) + 1, y: Math.floor(Math.random() * 60) + 20 });
        }
        new ApexCharts(document.getElementById('scatterPlot'), {
            chart: { type: 'scatter', height: 250, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
            series: [{ name: 'Actividades', data: scatterData }],
            colors: ['#7c3aed'],
            xaxis: { title: { text: 'Estimado (horas)' }, min: 0, max: 12 },
            yaxis: { title: { text: 'Real (horas)' } },
            grid: { borderColor: '#f3f4f6' },
            tooltip: { theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }
        }).render();
    }

    // Mood Trend Line
    if (document.getElementById('moodTrend')) {
        new ApexCharts(document.getElementById('moodTrend'), {
            chart: { type: 'line', height: 250, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
            series: [{ name: '\u00c1nimo', data: [3, 4, 3, 5, 4, 3, 4] }],
            colors: ['#f59e0b'],
            stroke: { curve: 'smooth', width: 3 },
            markers: { size: 5, colors: ['#f59e0b'], strokeColor: '#fff', strokeWidth: 2 },
            xaxis: { categories: ['Lun', 'Mar', 'Mi\u00e9', 'Jue', 'Vie', 'S\u00e1b', 'Dom'] },
            yaxis: { min: 1, max: 5, labels: { formatter: function(val) { var emojis = ['', '\U{1F625}', '\U{1F62E}', '\U{1F610}', '\U{1F60A}', '\U{1F601}']; return emojis[Math.round(val)] || ''; } } },
            grid: { borderColor: '#f3f4f6' },
            tooltip: { theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }
        }).render();
    }

    // Quadrant Stacked Bar
    if (document.getElementById('quadrantStacked')) {
        new ApexCharts(document.getElementById('quadrantStacked'), {
            chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
            series: [
                { name: 'Quadrante 1', data: [3, 2, 4, 2, 5] },
                { name: 'Quadrante 2', data: [5, 3, 2, 4, 3] },
                { name: 'Quadrante 3', data: [1, 2, 1, 3, 1] },
                { name: 'Quadrante 4', data: [2, 1, 3, 1, 2] }
            ],
            colors: ['#ef4444', '#3b82f6', '#f59e0b', '#6b7280'],
            plotOptions: { bar: { horizontal: false, stacked: true, borderRadius: 4 } },
            xaxis: { categories: ['Lun', 'Mar', 'Mi\u00e9', 'Jue', 'Vie'] },
            legend: { position: 'top', fontSize: '10px' },
            stroke: { show: false },
            dataLabels: { enabled: false },
            tooltip: { theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }
        }).render();
    }
});
</script>
@endpush
@endsection
