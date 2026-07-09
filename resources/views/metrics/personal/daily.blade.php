@extends('metrics.layouts.app')

@section('title', __('Dashboard Personal'))
@section('breadcrumb', __('Dashboard Personal'))

@section('content')

<div class="max-w-7xl mx-auto space-y-8 pb-12" x-data="{ moodSelected: null, noteText: '' }">

    {{-- Hero Section with Greeting + Quote --}}
    <div class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-violet-600 via-indigo-700 to-purple-800 p-8 text-white shadow-2xl shadow-indigo-500/20 border border-white/10">
        <!-- Decorative subtle pattern -->
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjIiIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSIvPjwvc3ZnPg==')] opacity-30"></div>
        <!-- Decorative blur blob -->
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div class="space-y-2">
                <p class="text-indigo-200 font-medium tracking-wide uppercase text-xs">{{ now()->format('l, d \d\e F \d\e Y') }}</p>
                <h1 class="text-4xl font-extrabold tracking-tight">
                    {{ $greeting }}, <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-200 to-pink-200">{{ explode(' ', $user->name)[0] }}</span> 👋
                </h1>
                <p class="text-indigo-100/80 max-w-xl text-sm leading-relaxed mt-2">
                    Aquí tienes tu resumen operativo de hoy. Tienes <strong class="text-white">{{ $activitiesToday->count() }} prioridades activas</strong> y un nivel de bienestar del <strong class="text-white">{{ number_format($wellnessData['score'] ?? 0, 0) }}%</strong>. ¡A por todas!
                </p>
            </div>
        </div>
    </div>

    {{-- Main KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Productivity Score --}}
        <div class="bg-white dark:bg-gray-900 rounded-[1.5rem] p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] dark:shadow-none border border-gray-100 dark:border-gray-800 relative overflow-hidden group hover:shadow-lg transition-all duration-300">
            <div class="absolute top-0 right-0 w-24 h-24 bg-violet-500/10 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-violet-500 to-indigo-500 flex items-center justify-center text-white shadow-lg shadow-violet-500/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Productividad</h3>
                        <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-3.5 h-3.5 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Puntuación global de tu productividad hoy en base a horas y tareas completadas.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
                    </div>
                    <p class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($productivityScore['score'] ?? ($productivityScore['productivity_score'] ?? 0), 0) }}<span class="text-sm text-gray-400 font-medium">/100</span></p>
                </div>
            </div>
            <div class="flex items-center gap-2 text-xs">
                <span class="text-emerald-500 bg-emerald-50 dark:bg-emerald-500/10 px-2 py-1 rounded-md font-bold">+{{ number_format($hoursPercentage, 0) }}%</span>
                <span class="text-gray-500 dark:text-gray-400">del tiempo objetivo hoy</span>
            </div>
        </div>

        {{-- Wellness Score --}}
        <div class="bg-white dark:bg-gray-900 rounded-[1.5rem] p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] dark:shadow-none border border-gray-100 dark:border-gray-800 relative overflow-hidden group hover:shadow-lg transition-all duration-300">
            <div class="absolute top-0 right-0 w-24 h-24 bg-rose-500/10 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-rose-400 to-pink-500 flex items-center justify-center text-white shadow-lg shadow-rose-500/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Bienestar</h3>
                        <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-3.5 h-3.5 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Tu nivel de bienestar diario basado en la carga de trabajo y descansos.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
                    </div>
                    <p class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($wellnessData['score'] ?? 0, 0) }}<span class="text-sm text-gray-400 font-medium">%</span></p>
                </div>
            </div>
            @php $risk = $wellnessData['burnout_risk'] ?? 'BAJO'; @endphp
            <div class="flex items-center gap-2 text-xs">
                <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md font-bold
                    {{ $risk === 'ALTO' ? 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400' : ($risk === 'MEDIO' ? 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400') }}">
                    {{ $risk === 'ALTO' ? '⚠️' : ($risk === 'MEDIO' ? '⚠️' : '✅') }} {{ $risk }} RIESGO
                </span>
                <span class="text-gray-500 dark:text-gray-400">de Burnout</span>
            </div>
        </div>

        {{-- Streak & Gamification --}}
        <div class="bg-white dark:bg-gray-900 rounded-[1.5rem] p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] dark:shadow-none border border-gray-100 dark:border-gray-800 relative overflow-hidden group hover:shadow-lg transition-all duration-300">
            <div class="absolute top-0 right-0 w-24 h-24 bg-amber-500/10 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white shadow-lg shadow-amber-500/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"/></svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Racha Activa</h3>
                        <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-3.5 h-3.5 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Días consecutivos manteniendo una buena actividad y productividad.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
                    </div>
                    <p class="text-2xl font-black text-gray-900 dark:text-white">{{ $streakDays }}<span class="text-sm text-gray-400 font-medium"> días</span></p>
                </div>
            </div>
            <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-1.5 overflow-hidden">
                <div class="h-full bg-gradient-to-r from-amber-400 to-orange-500 rounded-full" style="width: min({{ ($streakDays / 30) * 100 }}%, 100%)"></div>
            </div>
            <p class="text-[10px] font-medium text-gray-400 mt-2 text-right">Camino a los 30 días</p>
        </div>

        {{-- Week Completed --}}
        <div class="bg-white dark:bg-gray-900 rounded-[1.5rem] p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] dark:shadow-none border border-gray-100 dark:border-gray-800 relative overflow-hidden group hover:shadow-lg transition-all duration-300">
            <div class="absolute top-0 right-0 w-24 h-24 bg-sky-500/10 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-sky-400 to-blue-500 flex items-center justify-center text-white shadow-lg shadow-sky-500/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Tareas Semana</h3>
                        <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-3.5 h-3.5 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Número total de tareas completadas en lo que va de semana.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
                    </div>
                    <p class="text-2xl font-black text-gray-900 dark:text-white">{{ $weekCompleted }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2 text-xs">
                @if($weekCompleted > $lastWeekCompleted)
                    <span class="text-emerald-500 bg-emerald-50 dark:bg-emerald-500/10 px-2 py-1 rounded-md font-bold">↗ +{{ $weekCompleted - $lastWeekCompleted }}</span>
                @elseif($weekCompleted < $lastWeekCompleted)
                    <span class="text-red-500 bg-red-50 dark:bg-red-500/10 px-2 py-1 rounded-md font-bold">↘ -{{ $lastWeekCompleted - $weekCompleted }}</span>
                @else
                    <span class="text-gray-500 bg-gray-50 dark:bg-gray-500/10 px-2 py-1 rounded-md font-bold">=</span>
                @endif
                <span class="text-gray-500 dark:text-gray-400">vs semana pasada</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        {{-- Priority Activities List --}}
        <div class="xl:col-span-2 space-y-6">
            <div class="bg-white dark:bg-gray-900 rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] dark:shadow-none border border-gray-100 dark:border-gray-800 overflow-hidden">
                <div class="flex items-center justify-between px-8 py-6 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-center gap-2">
                        <div>
                            <h2 class="text-lg font-black text-gray-900 dark:text-white">Actividades Desarrolladas</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Resumen de actividades de hoy por tipo.</p>
                        </div>
                        <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Resumen estadístico de las actividades y tareas que has completado o gestionado hoy.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-violet-50 dark:bg-violet-500/10 text-violet-600 dark:text-violet-400 font-bold">
                        {{ $activitiesByType->sum() }}
                    </span>
                </div>
                
                <div class="p-6 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                    @forelse($activitiesByType as $type => $count)
                        @php
                            $typeLabel = match($type) {
                                'task' => 'Tareas',
                                'meeting' => 'Reuniones',
                                'document' => 'Documentos',
                                'note' => 'Notas',
                                'link' => 'Enlaces',
                                'decision' => 'Decisiones',
                                'reminder' => 'Recordatorios',
                                default => ucfirst($type),
                            };
                            $typeColor = match($type) {
                                'task' => 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400',
                                'meeting' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400',
                                'document' => 'bg-orange-50 text-orange-600 dark:bg-orange-500/10 dark:text-orange-400',
                                'note' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400',
                                'link' => 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400',
                                'decision' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400',
                                'reminder' => 'bg-purple-50 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400',
                                default => 'bg-gray-50 text-gray-600 dark:bg-gray-500/10 dark:text-gray-400',
                            };
                            $typeIcon = match($type) {
                                'task' => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
                                'meeting' => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>',
                                'document' => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>',
                                'note' => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>',
                                'link' => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>',
                                'decision' => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" /></svg>',
                                'reminder' => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>',
                                default => '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6z" /></svg>',
                            };
                        @endphp
                        
                        <div class="flex flex-col items-center justify-center p-6 rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-all duration-300">
                            <div class="w-12 h-12 rounded-full mb-3 flex items-center justify-center {{ $typeColor }}">
                                {!! $typeIcon !!}
                            </div>
                            <span class="text-3xl font-black text-gray-900 dark:text-white mb-1">{{ $count }}</span>
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider text-center">{{ $typeLabel }}</span>
                        </div>
                    @empty
                        <div class="col-span-full py-12 text-center flex flex-col items-center">
                            <div class="w-20 h-20 bg-gray-50 dark:bg-gray-800/50 rounded-full flex items-center justify-center mb-4">
                                <span class="text-3xl">☕</span>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Aún no hay actividades</h3>
                            <p class="text-sm text-gray-500 mt-1">No se han registrado actividades desarrolladas en el día de hoy.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Eisenhower Grid View --}}
            <div class="bg-white dark:bg-gray-900 rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] dark:shadow-none border border-gray-100 dark:border-gray-800 p-8">
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center gap-2">
                        <div>
                            <h2 class="text-lg font-black text-gray-900 dark:text-white">Matriz de Eficiencia (Eisenhower)</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Visión estratégica de tus tareas actuales.</p>
                        </div>
                        <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Clasificación de tareas según su importancia y urgencia para priorizar el trabajo diario.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @php
                        $quadrants = [
                            ['id' => 1, 'title' => 'HACER AHORA', 'desc' => 'Urgente e Importante', 'bg' => 'bg-red-50 dark:bg-red-500/5', 'border' => 'border-red-100 dark:border-red-500/20', 'text' => 'text-red-700 dark:text-red-400', 'badge' => 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400'],
                            ['id' => 2, 'title' => 'PROGRAMAR', 'desc' => 'Importante, No Urgente', 'bg' => 'bg-blue-50 dark:bg-blue-500/5', 'border' => 'border-blue-100 dark:border-blue-500/20', 'text' => 'text-blue-700 dark:text-blue-400', 'badge' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-400'],
                            ['id' => 3, 'title' => 'DELEGAR', 'desc' => 'Urgente, No Importante', 'bg' => 'bg-amber-50 dark:bg-amber-500/5', 'border' => 'border-amber-100 dark:border-amber-500/20', 'text' => 'text-amber-700 dark:text-amber-400', 'badge' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400'],
                            ['id' => 4, 'title' => 'ELIMINAR', 'desc' => 'Ni Urgente, Ni Importante', 'bg' => 'bg-gray-50 dark:bg-gray-800/50', 'border' => 'border-gray-200 dark:border-gray-700', 'text' => 'text-gray-600 dark:text-gray-400', 'badge' => 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300'],
                        ];
                    @endphp
                    
                    @foreach($quadrants as $q)
                        <div class="rounded-2xl {{ $q['bg'] }} border {{ $q['border'] }} p-6 transition-all hover:shadow-md">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-[11px] font-black uppercase tracking-widest {{ $q['text'] }}">{{ $q['title'] }}</h3>
                                    <p class="text-[10px] text-gray-500 mt-1 font-medium">{{ $q['desc'] }}</p>
                                </div>
                                @php
                                    $qActivities = $activitiesToday->filter(fn($a) => $a->getQuadrant($a) == $q['id'])->take(4);
                                @endphp
                                <span class="px-2 py-1 rounded-md text-[10px] font-bold {{ $q['badge'] }}">{{ $qActivities->count() }}</span>
                            </div>
                            
                            <ul class="space-y-2.5">
                                @forelse($qActivities as $qa)
                                    <li class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300 font-medium">
                                        <svg class="w-4 h-4 mt-0.5 opacity-50 {{ $q['text'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <span class="line-clamp-2 leading-tight">{{ $qa->title }}</span>
                                    </li>
                                @empty
                                    <li class="text-xs text-gray-400 italic py-2">Ninguna en este cuadrante.</li>
                                @endforelse
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Right Column (Analytics & Social) --}}
        <div class="space-y-6">
            {{-- Productivity Sparkline Card --}}
            <div class="bg-white dark:bg-gray-900 rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] dark:shadow-none border border-gray-100 dark:border-gray-800 p-6 overflow-hidden relative">
                <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-500/5 rounded-bl-full -mr-8 -mt-8"></div>
                <div class="flex justify-between items-start">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-1">Tendencia de Productividad</h3>
                    <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Evolución de tu puntuación de productividad a lo largo de los últimos 7 días.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
                </div>
                <p class="text-xs text-gray-500 mb-6">Puntuación diaria en los últimos 7 días</p>
                <div id="productivitySparkline" class="w-full relative z-10" style="min-height: 200px;"></div>
            </div>

            {{-- Weekly Distribution Card --}}
            <div class="bg-white dark:bg-gray-900 rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] dark:shadow-none border border-gray-100 dark:border-gray-800 p-6">
                <div class="flex justify-between items-start">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-1">Volumen Semanal</h3>
                    <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Comparativa semanal entre las nuevas tareas asignadas y las tareas que has completado.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
                </div>
                <p class="text-xs text-gray-500 mb-6">Nuevas vs Completadas</p>
                <div id="weeklySparkline" class="w-full" style="min-height: 220px;"></div>
            </div>

            {{-- Social / Kudos Card --}}
            <div class="bg-gradient-to-br from-rose-50 to-pink-50 dark:from-rose-500/5 dark:to-pink-500/5 rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] dark:shadow-none border border-rose-100 dark:border-rose-500/10 p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-white dark:bg-gray-800 shadow-sm flex items-center justify-center text-xl">
                        🎯
                    </div>
                    <div class="flex items-center gap-2">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Reconocimientos de Hoy</h3>
                            <p class="text-[10px] text-gray-500 uppercase tracking-wide">Kudos recibidos</p>
                        </div>
                        <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Mensajes de agradecimiento o reconocimiento recibidos hoy de parte de tus compañeros.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
                    </div>
                </div>
                
                @if($kudosToday->count())
                    <div class="space-y-3">
                        @foreach($kudosToday as $kudo)
                            <div class="flex items-start gap-3 p-4 rounded-2xl bg-white dark:bg-gray-800 shadow-sm border border-rose-50 dark:border-gray-700/50 transition-transform hover:-translate-y-0.5">
                                <img src="{{ $kudo->sender->profile_photo_url ?? 'https://ui-avatars.com/api/?name=User' }}" class="w-8 h-8 rounded-full shadow-sm mt-0.5">
                                <div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200 leading-snug">"{{ $kudo->message ?? 'Gran trabajo en equipo hoy.' }}"</p>
                                    <p class="text-[10px] text-gray-400 mt-1 font-semibold uppercase tracking-wider">— {{ $kudo->sender->name ?? 'Compañero' }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 text-center border border-dashed border-rose-200 dark:border-gray-700">
                        <span class="text-3xl opacity-50 block mb-2">🤝</span>
                        <p class="text-xs text-gray-500 font-medium">Aún no has recibido kudos hoy.</p>
                        <p class="text-[10px] text-gray-400 mt-1">¡Sigue con el buen trabajo!</p>
                    </div>
                @endif
            </div>
            
            {{-- Mood Check-in Mini Widget --}}
            <div class="bg-white dark:bg-gray-900 rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] dark:shadow-none border border-gray-100 dark:border-gray-800 p-6 text-center">
                <div class="flex items-center justify-center gap-2 mb-4">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">¿Cómo está tu energía?</p>
                    <div x-data="{ tooltip: false }" class="relative flex items-center z-20" @mouseenter="tooltip = true" @mouseleave="tooltip = false">
    <svg class="w-3.5 h-3.5 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div x-cloak x-show="tooltip" x-transition.opacity.duration.200ms class="absolute bottom-full right-0 mb-2 w-max max-w-xs px-3 py-2 bg-gray-900 dark:bg-gray-700 text-white text-[11px] font-medium rounded-lg shadow-xl pointer-events-none z-50 whitespace-normal text-left">
        {{ __('Registro diario de tu estado de ánimo para monitorizar tu bienestar a lo largo del tiempo.') }}
        <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 transform rotate-45 -mt-1"></div>
    </div>
</div>
                </div>
                <div class="flex justify-center gap-2 sm:gap-4" x-data="{ mood: {{ $latestMood ? $latestMood->score : 'null' }} }">
                    <template x-for="(emoji, index) in ['😫', '🙁', '😐', '🙂', '🤩']" :key="index">
                        <button @click="mood = index + 1"
                                :class="mood === index + 1 ? 'scale-125 ring-4 ring-violet-500/30' : 'opacity-60 hover:opacity-100 hover:scale-110'"
                                class="text-2xl sm:text-3xl transition-all duration-300 rounded-full focus:outline-none"
                                x-text="emoji">
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#9ca3af' : '#6b7280';
    const gridColor = isDark ? 'rgba(255,255,255,0.05)' : '#f3f4f6';

    // Productivity Sparkline
    if (document.getElementById('productivitySparkline')) {
        var prodOptions = {
            series: [{
                name: 'Productividad',
                data: {{ json_encode($productivityData['daily_scores'] ?? [65, 72, 58, 80, 75, 68, 85]) }}
            }],
            chart: { 
                type: 'area', 
                height: 200, 
                toolbar: { show: false }, 
                fontFamily: 'Inter, sans-serif',
                sparkline: { enabled: true }
            },
            colors: ['#8b5cf6'], // Violet-500
            fill: { 
                type: 'gradient', 
                gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 90, 100] } 
            },
            stroke: { curve: 'smooth', width: 3 },
            tooltip: { 
                theme: isDark ? 'dark' : 'light',
                y: { formatter: function(val) { return val + " pts" } }
            }
        };
        new ApexCharts(document.getElementById('productivitySparkline'), prodOptions).render();
    }

    // Weekly Summary Bar Chart
    if (document.getElementById('weeklySparkline')) {
        var weekOptions = {
            series: [{
                name: 'Completadas',
                data: {{ json_encode($wellnessData['weekly_completed'] ?? [3, 5, 4, 7, 6, 2, 4]) }}
            }, {
                name: 'Nuevas',
                data: {{ json_encode($wellnessData['weekly_new'] ?? [4, 3, 5, 4, 5, 3, 2]) }}
            }],
            chart: { 
                type: 'bar', 
                height: 220, 
                toolbar: { show: false }, 
                fontFamily: 'Inter, sans-serif' 
            },
            colors: ['#10b981', '#6366f1'], // Emerald & Indigo
            plotOptions: { 
                bar: { borderRadius: 4, columnWidth: '50%', dataLabels: { position: 'top' } } 
            },
            dataLabels: {
                enabled: false
            },
            stroke: { show: true, width: 2, colors: ['transparent'] },
            xaxis: { 
                categories: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: { style: { colors: textColor, fontSize: '11px', fontWeight: 600 } }
            },
            yaxis: {
                labels: { style: { colors: textColor, fontSize: '11px' } }
            },
            grid: { 
                borderColor: gridColor,
                strokeDashArray: 4,
                yaxis: { lines: { show: true } }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                markers: { radius: 12 },
                labels: { colors: textColor }
            },
            tooltip: { theme: isDark ? 'dark' : 'light' }
        };
        new ApexCharts(document.getElementById('weeklySparkline'), weekOptions).render();
    }
});
</script>
@endpush
@endsection
