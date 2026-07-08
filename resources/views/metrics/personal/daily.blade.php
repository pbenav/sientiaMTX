@extends('metrics.layouts.app')

@section('title', 'Dashboard Personal - sientiaMTX')
@section('header-title', 'Dashboard Personal')

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="{ moodSelected: null, noteText: '' }">

    {{-- Greeting + Quote --}}
    <div class="bg-gradient-to-r from-violet-600 via-indigo-600 to-purple-600 rounded-2xl p-6 text-white shadow-lg">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">
                    @php
                        $hour = now()->hour;
                        if ($hour < 12) $greeting = 'Buenos d\u00edas';
                        elseif ($hour < 18) $greeting = 'Buenas tardes';
                        else $greeting = 'Buenas noches';
                    @endphp
                    {{ $greeting }}, {{ explode(' ', $user->name)[0] }} \u{200D}\u{1F44B}
                </h1>
                <p class="text-violet-200 mt-1 text-sm">{{ now()->format('l, d \d\e F \d\e Y') }}</p>
            </div>
            <div class="bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3 max-w-xs">
                <p class="text-sm italic leading-relaxed">"{!! $motivationalQuote['text'] !!}"</p>
                <p class="text-xs text-violet-200 mt-1 text-right">\u2014 {{ $motivationalQuote['author'] }}</p>
            </div>
        </div>
    </div>

    {{-- KPI Cards Row --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Hours Card --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 card-shadow border border-gray-100 dark:border-gray-800">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-500/10 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Horas Hoy</span>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($hoursLoggedToday, 1) }}<span class="text-sm text-gray-400 font-normal">/{{ $dailyGoal }}</span></p>
            <div class="mt-2 w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2 overflow-hidden">
                <div class="h-full bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full transition-all duration-500" style="width: min({{ min($hoursPercentage, 100) }}%, 100%)"></div>
            </div>
        </div>

        {{-- Streak Card --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 card-shadow border border-gray-100 dark:border-gray-800">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-500/10 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"/></svg>
                </div>
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Racha</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $streakDays }}</span>
                <span class="text-sm text-gray-400">d\u00edas</span>
                <span class="text-lg">\u{1F525}</span>
            </div>
        </div>

        {{-- Week Completed --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 card-shadow border border-gray-100 dark:border-gray-800">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-500/10 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Esta Semana</span>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $weekCompleted }}</p>
            <p class="text-xs text-gray-400 mt-1">
                @if($weekCompleted > $lastWeekCompleted)
                    <span class="text-emerald-500">\u{2197}</span> +{{ $weekCompleted - $lastWeekCompleted }} vs semana pasada
                @elseif($weekCompleted < $lastWeekCompleted)
                    <span class="text-red-500">\u{2198}</span> -{{ $lastWeekCompleted - $weekCompleted }} vs semana pasada
                @else
                    Sin cambios
                @endif
            </p>
        </div>

        {{-- Wellness Score --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 card-shadow border border-gray-100 dark:border-gray-800">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-500/10 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                </div>
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Bienestar</span>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($wellnessData['score'] ?? 0, 0) }}%</p>
            <p class="text-xs mt-1">
                @php $risk = $wellnessData['burnout_risk'] ?? 'BAJO'; @endphp
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold
                    {{ $risk === 'ALTO' ? 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400' : ($risk === 'MEDIO' ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400') }}">
                    {{ $risk === 'ALTO' ? '\u{26A0}\ufe0f' : ($risk === 'MEDIO' ? '\u{26A0}' : '\u{2705}') }} {{ $risk }} RIESGO
                </span>
            </p>
        </div>
    </div>

    {{-- Main Grid: Activities + Eisenhower + Mood --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Today's Activities --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-2xl card-shadow border border-gray-100 dark:border-gray-800">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                <h2 class="text-base font-bold text-gray-900 dark:text-white">Actividades de Hoy</h2>
                <span class="text-xs font-semibold bg-violet-100 dark:bg-violet-500/10 text-violet-700 dark:text-violet-400 px-2.5 py-1 rounded-full">{{ $activitiesToday->count() }}</span>
            </div>
            <div class="divide-y divide-gray-50 dark:divide-gray-800/50">
                @forelse($activitiesToday as $activity)
                    <div class="px-5 py-3.5 hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors">
                        <div class="flex items-start gap-3">
                            <div class="w-2 h-2 rounded-full mt-2 shrink-0
                                {{ $activity->priority === 'critical' ? 'bg-red-500' : ($activity->priority === 'high' ? 'bg-orange-500' : ($activity->priority === 'medium' ? 'bg-amber-500' : 'bg-gray-400')) }}">
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $activity->title }}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-[10px] text-gray-400 uppercase font-semibold">{{ $activity->status ?? 'pending' }}</span>
                                    @if($activity->quadrant)
                                        <span class="text-[10px] bg-gray-100 dark:bg-gray-800 text-gray-500 px-1.5 py-0.5 rounded">{{ $activity->quadrant->name ?? 'Q' . ($activity->quadrant_id ?? '?') }}</span>
                                    @endif
                                    @if($activity->assignedTo && $activity->assignedTo->first())
                                        <span class="text-[10px] text-gray-400">para {{ $activity->assignedTo->first()->name }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-12 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-300 dark:text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-sm text-gray-400 mt-2">No tienes actividades pendientes para hoy. \u{1F389}</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Right Column --}}
        <div class="space-y-6">

            {{-- Mood Check-in --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl card-shadow border border-gray-100 dark:border-gray-800 p-5">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3">\u{1F60A}\u200D\ud83d\udcbb Como te sientes hoy?</h3>
                <div class="flex justify-between" x-data>
                    <template x-for="i in 5" :key="i">
                        <button @click="moodSelected = i"
                                :class="moodSelected === i ? 'scale-110' : 'scale-100 opacity-50 hover:opacity-80'"
                                class="text-3xl transition-all duration-200 focus:outline-none"
                                :title="i === 1 ? 'Muy mal' : i === 2 ? 'Mal' : i === 3 ? 'Regular' : i === 4 ? 'Bien' : 'Muy bien'">
                            <span x-text="i === 1 ? '\U{1F625}' : i === 2 ? '\U{1F62E}' : i === 3 ? '\U{1F610}' : i === 4 ? '\U{1F60A}' : '\U{1F601}'"></span>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Streak Badge --}}
            <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-5 text-white shadow-lg">
                <div class="flex items-center gap-3">
                    <div class="w-14 h-14 rounded-xl bg-white/20 flex items-center justify-center text-3xl">
                        \u{1F525}
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-amber-100">Racha Activa</p>
                        <p class="text-3xl font-bold">{{ $streakDays }}</p>
                        <p class="text-xs text-amber-200">d\u00edas consecutivos</p>
                    </div>
                </div>
                <div class="mt-3 flex gap-1">
                    @for($d = 1; $d <= 7; $d++)
                        <div class="flex-1 h-1.5 rounded-full {{ $d <= $streakDays ? 'bg-white' : 'bg-white/30' }}"></div>
                    @endfor
                </div>
            </div>

            {{-- Today's Kudos --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl card-shadow border border-gray-100 dark:border-gray-800 p-5">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3">\u{1F3AF} Kudos de Hoy</h3>
                @if($kudosToday->count())
                    <div class="space-y-3">
                        @foreach($kudosToday as $kudo)
                            <div class="flex items-start gap-2.5 p-2.5 rounded-xl bg-rose-50 dark:bg-rose-500/5">
                                <span class="text-lg">\u{2764}\ufe0f</span>
                                <div>
                                    <p class="text-xs text-gray-700 dark:text-gray-300">{{ $kudo->message ?? 'Te felicita' }}</p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">de {{ $kudo->sender->name ?? 'Alguien' }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-gray-400 text-center py-4">A\u00fan no hay kudos hoy</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Eisenhower Matrix --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl card-shadow border border-gray-100 dark:border-gray-800 p-5">
        <h2 class="text-base font-bold text-gray-900 dark:text-white mb-4">\u{1F4CA} Matriz de Eisenhower</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @php
                $quadrants = [
                    ['title' => 'Urgente e Importante', 'desc' => 'Hazlo ya', 'color' => 'red', 'class' => 'border-red-200 dark:border-red-500/30 bg-red-50 dark:bg-red-500/5'],
                    ['title' => 'Importante, No Urgente', 'desc' => 'Programalo', 'color' => 'blue', 'class' => 'border-blue-200 dark:border-blue-500/30 bg-blue-50 dark:bg-blue-500/5'],
                    ['title' => 'Urgente, No Importante', 'desc' => 'Delega', 'color' => 'amber', 'class' => 'border-amber-200 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/5'],
                    ['title' => 'Ni Urgente, Ni Importante', 'desc' => 'Eliminalo', 'color' => 'gray', 'class' => 'border-gray-200 dark:border-gray-500/30 bg-gray-50 dark:bg-gray-500/5'],
                ];
            @endphp
            @foreach($quadrants as $q)
                <div class="rounded-xl border-2 p-4 {{ $q['class'] }}">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">{{ $q['title'] }}</h3>
                        <span class="text-[10px] font-semibold text-gray-500">{{ $q['desc'] }}</span>
                    </div>
                    <div class="space-y-1.5">
                        @php
                            $qId = match($loop->index) { 0 => 1, 1 => 2, 2 => 3, 3 => 4 };
                            $qActivities = $activitiesToday->filter(fn($a) => ($a->quadrant_id ?? 0) == $qId);
                        @endphp
                        @if($qActivities->count())
                            @foreach($qActivities as $qa)
                                <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                    <span class="w-1 h-1 rounded-full bg-current shrink-0"></span>
                                    <span class="truncate">{{ $qa->title }}</span>
                                </div>
                            @endforeach
                        @else
                            <p class="text-[10px] text-gray-400 italic">Sin actividades</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Hours vs Progress + Weekly Sparkline --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-gray-900 rounded-2xl card-shadow border border-gray-100 dark:border-gray-800 p-5">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4">\u{1F4CA} Productividad vs Progreso</h3>
            <div id="productivitySparkline" class="w-full" style="min-height: 250px;"></div>
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-2xl card-shadow border border-gray-100 dark:border-gray-800 p-5">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4">\u{1F4C8} Resumen Semanal</h3>
            <div id="weeklySparkline" class="w-full" style="min-height: 250px;"></div>
        </div>
    </div>

    {{-- Quick Notes + Next Meeting --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-gray-900 rounded-2xl card-shadow border border-gray-100 dark:border-gray-800 p-5">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3">\u{1F4DD} Notas R\u00e1pidas</h3>
            <textarea x-model="noteText" rows="5" placeholder="Escribe tus notas del d\u00eda..."
                      class="w-full rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 resize-none p-3"></textarea>
            <div class="flex justify-end mt-2">
                <button class="px-4 py-2 text-xs font-semibold text-white bg-violet-600 rounded-xl hover:bg-violet-700 transition-colors">Guardar Nota</button>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-2xl card-shadow border border-gray-100 dark:border-gray-800 p-5">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-3">\u{1F4C5} Siguiente Reuni\u00f3n</h3>
            @if($nextAppointment)
                <div class="bg-gradient-to-r from-violet-500 to-indigo-600 rounded-xl p-4 text-white">
                    <p class="font-semibold text-sm">{{ $nextAppointment->localizador ?? 'Cita' }}</p>
                    <div class="flex items-center gap-2 mt-2 text-xs text-violet-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ \Carbon\Carbon::parse($nextAppointment->appointment_date . ' ' . $nextAppointment->appointment_time)->format('H:i') }}h
                    </div>
                    @if($nextAppointment->slot_duration_minutes)
                        <div class="text-xs text-violet-200 mt-1">
                            Duración: {{ $nextAppointment->slot_duration_minutes }} min
                        </div>
                    @endif
                </div>
            @else
                <p class="text-sm text-gray-400 text-center py-8">No hay citas programadas</p>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Productivity Sparkline
    if (document.getElementById('productivitySparkline')) {
        var prodOptions = {
            series: [{
                name: 'Score',
                data: {{ json_encode($productivityData['daily_scores'] ?? [65, 72, 58, 80, 75, 68, 85]) }}
            }],
            chart: { type: 'area', height: 250, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
            colors: ['#7c3aed'],
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.5, opacityTo: 0.1 } },
            stroke: { curve: 'smooth', width: 2 },
            xaxis: { categories: ['Lun', 'Mar', 'Mi\u00e9', 'Jue', 'Vie', 'S\u00e1b', 'Dom'] },
            yaxis: { min: 0, max: 100 },
            grid: { borderColor: getComputedStyle(document.documentElement).getPropertyValue('--tw-border-opacity') ? '#e5e7eb' : 'rgba(255,255,255,0.06)' },
            tooltip: { theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }
        };
        new ApexCharts(document.getElementById('productivitySparkline'), prodOptions).render();
    }

    // Weekly Summary Sparkline
    if (document.getElementById('weeklySparkline')) {
        var weekOptions = {
            series: [{
                name: 'Completadas',
                data: {{ json_encode($wellnessData['weekly_completed'] ?? [3, 5, 4, 7, 6, 2, 4]) }}
            }, {
                name: 'Nuevas',
                data: {{ json_encode($wellnessData['weekly_new'] ?? [4, 3, 5, 4, 5, 3, 2]) }}
            }],
            chart: { type: 'bar', height: 250, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
            colors: ['#10b981', '#6366f1'],
            plotOptions: { bar: { borderRadius: 4, columnWidth: '60%' } },
            xaxis: { categories: ['Lun', 'Mar', 'Mi\u00e9', 'Jue', 'Vie', 'S\u00e1b', 'Dom'] },
            grid: { borderColor: '#e5e7eb' },
            tooltip: { theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light' }
        };
        new ApexCharts(document.getElementById('weeklySparkline'), weekOptions).render();
    }
});
</script>
@endpush
@endsection
