@extends('metrics.layouts.app')

@section('title', __('War Room - Auditoría Global'))
@section('breadcrumb', __('Auditoría Global'))

@push('scripts')
<style>
    .glass-panel { background: rgba(17, 24, 39, 0.7); backdrop-filter: blur(16px); border: 1px solid rgba(255,255,255,0.05); }
    .neon-text-blue { text-shadow: 0 0 10px rgba(59, 130, 246, 0.5); }
    .neon-text-emerald { text-shadow: 0 0 10px rgba(16, 185, 129, 0.5); }
    .neon-border { box-shadow: 0 0 15px rgba(59, 130, 246, 0.1) inset; border: 1px solid rgba(59, 130, 246, 0.2); }
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto space-y-6 pb-12">

            <!-- Hero System Status -->
            <div class="relative overflow-hidden rounded-[2rem] bg-gray-900 p-8 neon-border">
                <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZD0iTTAgMGg0MHY0MEgweiIgZmlsbD0ibm9uZSIvPjxwYXRoIGQ9Ik0wIDM5LjVoNDBNMzkuNSAwdiM0MCIgc3Ryb2tlPSJyZ2JhKDI1NSwyNTUsMjU1LDAuMDIpIiBzdHJva2Utd2lkdGg9IjEiLz48L3N2Zz4=')]"></div>
                
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-8">
                    <div>
                        <div class="flex items-center gap-3 mb-3">
                            <span class="flex h-3 w-3 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                            </span>
                            <p class="text-emerald-400 font-mono text-xs uppercase tracking-[0.2em] font-bold">Auditoría Global de Sistemas</p>
                        </div>
                        <h1 class="text-5xl font-black tracking-tighter text-white">Centro de Mando</h1>
                        <p class="text-gray-400 max-w-xl text-sm leading-relaxed mt-2 font-medium">
                            Análisis en tiempo real del rendimiento organizacional. Monitorización de carga transaccional, flujos de comunicación y adopción del sistema.
                        </p>
                    </div>
                    
                    <div class="flex gap-4">
                        <div class="glass-panel rounded-2xl p-4 min-w-[160px] text-center border border-white/5">
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-1">Carga Operativa (30d)</p>
                            <p class="text-3xl font-black font-mono text-white">{{ number_format($auditData['system']['activities_30d']) }}</p>
                        </div>
                        <div class="glass-panel rounded-2xl p-4 min-w-[160px] text-center border border-emerald-500/20 bg-emerald-500/5">
                            <p class="text-[10px] text-emerald-500 font-bold uppercase tracking-widest mb-1">Uptime SLA</p>
                            <p class="text-3xl font-black font-mono text-emerald-400">{{ $auditData['system']['uptime'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Macro KPIs Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Users -->
                <div class="glass-panel rounded-[1.5rem] p-6 relative overflow-hidden group">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-blue-500/10 rounded-full blur-2xl group-hover:bg-blue-500/20 transition-all"></div>
                    <div class="flex justify-between items-start mb-4 relative z-10">
                        <div class="text-blue-400">
                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                        @php $growth = $auditData['users']['growth_percent']; @endphp
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider {{ $growth >= 0 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20' }}">
                            {{ $growth >= 0 ? '↑' : '↓' }} {{ abs($growth) }}%
                        </span>
                    </div>
                    <div class="relative z-10">
                        <p class="text-3xl font-black text-white neon-text-blue">{{ number_format($auditData['users']['total']) }}</p>
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mt-1">Usuarios Totales</h3>
                    </div>
                </div>

                <!-- Active Now -->
                <div class="glass-panel rounded-[1.5rem] p-6 relative overflow-hidden group">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-500/10 rounded-full blur-2xl group-hover:bg-emerald-500/20 transition-all"></div>
                    <div class="flex justify-between items-start mb-4 relative z-10">
                        <div class="text-emerald-400">
                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <span class="flex h-2.5 w-2.5 relative mt-1">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                        </span>
                    </div>
                    <div class="relative z-10">
                        <p class="text-3xl font-black text-white neon-text-emerald">{{ number_format($auditData['users']['active_now']) }}</p>
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mt-1">Conectados Ahora</h3>
                    </div>
                </div>

                <!-- Database Size -->
                <div class="glass-panel rounded-[1.5rem] p-6 relative overflow-hidden group">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-purple-500/10 rounded-full blur-2xl group-hover:bg-purple-500/20 transition-all"></div>
                    <div class="flex justify-between items-start mb-4 relative z-10">
                        <div class="text-purple-400">
                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                        </div>
                        <span class="text-[10px] font-black uppercase tracking-wider text-gray-500 bg-white/5 px-2 py-1 rounded-full border border-white/10">{{ number_format($auditData['system']['total_attachments']) }} Ficheros</span>
                    </div>
                    <div class="relative z-10">
                        <p class="text-3xl font-black text-white">{{ $auditData['system']['db_size_mb'] }}<span class="text-lg text-purple-400 ml-1">MB</span></p>
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mt-1">Volumen Base de Datos</h3>
                    </div>
                </div>

                <!-- Session Time -->
                <div class="glass-panel rounded-[1.5rem] p-6 relative overflow-hidden group">
                    <div class="absolute -right-6 -top-6 w-24 h-24 bg-amber-500/10 rounded-full blur-2xl group-hover:bg-amber-500/20 transition-all"></div>
                    <div class="flex justify-between items-start mb-4 relative z-10">
                        <div class="text-amber-400">
                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="relative z-10">
                        <p class="text-3xl font-black text-white">{{ number_format($auditData['users']['avg_session_minutes']) }}<span class="text-lg text-amber-400 ml-1">min</span></p>
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mt-1">Media de Sesión</h3>
                    </div>
                </div>
            </div>

            <!-- Deep Dive Modules Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Comms -->
                <div class="bg-gray-900/50 rounded-2xl p-5 border border-white/5 hover:border-indigo-500/30 transition-all">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 rounded-lg bg-indigo-500/10 flex items-center justify-center text-indigo-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        </div>
                        <h4 class="text-sm font-bold text-gray-300 uppercase tracking-wider">Comunicaciones</h4>
                    </div>
                    <p class="text-2xl font-black text-white">{{ number_format($auditData['modules']['comms']['total']) }} <span class="text-[10px] text-gray-500 font-normal uppercase tracking-widest block mt-1">Mensajes Totales</span></p>
                    <div class="mt-3 pt-3 border-t border-white/5 flex justify-between items-center text-xs">
                        <span class="text-gray-500">Volumen Mes</span>
                        <span class="text-indigo-400 font-bold">+{{ number_format($auditData['modules']['comms']['this_month']) }}</span>
                    </div>
                </div>

                <!-- Gamification -->
                <div class="bg-gray-900/50 rounded-2xl p-5 border border-white/5 hover:border-pink-500/30 transition-all">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 rounded-lg bg-pink-500/10 flex items-center justify-center text-pink-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                        </div>
                        <h4 class="text-sm font-bold text-gray-300 uppercase tracking-wider">Gamificación</h4>
                    </div>
                    <p class="text-2xl font-black text-white">{{ number_format($auditData['modules']['gamification']['total_kudos']) }} <span class="text-[10px] text-gray-500 font-normal uppercase tracking-widest block mt-1">Kudos Repartidos</span></p>
                    <div class="mt-3 pt-3 border-t border-white/5 flex justify-between items-center text-xs">
                        <span class="text-gray-500">Últimos 30d</span>
                        <span class="text-pink-400 font-bold">+{{ number_format($auditData['modules']['gamification']['kudos_30d']) }}</span>
                    </div>
                </div>

                <!-- Expedientes -->
                <div class="bg-gray-900/50 rounded-2xl p-5 border border-white/5 hover:border-orange-500/30 transition-all">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 rounded-lg bg-orange-500/10 flex items-center justify-center text-orange-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <h4 class="text-sm font-bold text-gray-300 uppercase tracking-wider">Expedientes</h4>
                    </div>
                    <p class="text-2xl font-black text-white">{{ number_format($auditData['modules']['expedientes']['total']) }} <span class="text-[10px] text-gray-500 font-normal uppercase tracking-widest block mt-1">Registros Históricos</span></p>
                    <div class="mt-3 pt-3 border-t border-white/5 flex justify-between items-center text-xs">
                        <span class="text-gray-500">Abiertos Ahora</span>
                        <span class="text-orange-400 font-bold">{{ number_format($auditData['modules']['expedientes']['active']) }}</span>
                    </div>
                </div>

                <!-- Encuestas / Satisfacción -->
                <div class="bg-gray-900/50 rounded-2xl p-5 border border-white/5 hover:border-teal-500/30 transition-all">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 rounded-lg bg-teal-500/10 flex items-center justify-center text-teal-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        </div>
                        <h4 class="text-sm font-bold text-gray-300 uppercase tracking-wider">Satisfacción</h4>
                    </div>
                    <p class="text-2xl font-black text-white">{{ number_format($auditData['modules']['surveys']['total']) }} <span class="text-[10px] text-gray-500 font-normal uppercase tracking-widest block mt-1">Encuestas Creadas</span></p>
                    <div class="mt-3 pt-3 border-t border-white/5 flex justify-between items-center text-xs">
                        <span class="text-gray-500">Votos Emitidos</span>
                        <span class="text-teal-400 font-bold">{{ number_format($auditData['modules']['surveys']['votes']) }}</span>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Login Trend -->
                <div class="glass-panel rounded-[2rem] p-6 lg:p-8">
                    <div class="mb-4 flex justify-between items-start">
                        <div>
                            <h2 class="text-base font-black text-white uppercase tracking-widest">Flujo de Accesos</h2>
                            <p class="text-xs text-gray-500 mt-1">Conexiones únicas diarias (14 días)</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 cursor-help mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Tendencia del número de usuarios únicos que inician sesión diariamente en el sistema.') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div id="loginTrendChart" class="w-full" style="min-height: 280px;"></div>
                </div>

                <!-- System Load Hourly -->
                <div class="glass-panel rounded-[2rem] p-6 lg:p-8">
                    <div class="mb-4 flex justify-between items-start">
                        <div>
                            <h2 class="text-base font-black text-white uppercase tracking-widest">Carga Transaccional</h2>
                            <p class="text-xs text-gray-500 mt-1">Peticiones y operaciones por hora (Últimas 24h)</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 cursor-help mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Volumen de actividad y operaciones realizadas en la plataforma segmentado por horas.') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div id="systemActivityChart" class="w-full" style="min-height: 280px;"></div>
                </div>
            </div>

            <!-- Bottom Grid: Top Users & Status Distribution -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Top Users -->
                <div class="lg:col-span-2 glass-panel rounded-[2rem] p-6 lg:p-8">
                    <div class="mb-6 flex flex-wrap justify-between items-end gap-4">
                        <div>
                            <h2 class="text-base font-black text-white uppercase tracking-widest">Líderes de Operativa</h2>
                            <p class="text-xs text-gray-500 mt-1">Usuarios que gestionan el mayor volumen de tareas (30d)</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-[10px] font-black uppercase tracking-widest text-white bg-blue-600 px-3 py-1.5 rounded-full shadow-[0_0_10px_rgba(37,99,235,0.5)]">Top 5 Creadores</span>
                            <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Ranking de los usuarios más activos en base a la cantidad de tareas gestionadas.') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        @forelse($auditData['top_performers'] as $index => $user)
                            <div class="flex items-center justify-between p-4 rounded-2xl bg-white/5 border border-white/5 hover:bg-white/10 hover:border-white/10 transition-all group">
                                <div class="flex items-center gap-4">
                                    <div class="w-8 text-center font-black text-xl {{ $index === 0 ? 'text-amber-400 drop-shadow-[0_0_8px_rgba(251,191,36,0.5)]' : ($index === 1 ? 'text-gray-300' : ($index === 2 ? 'text-amber-700' : 'text-gray-600')) }}">
                                        #{{ $index + 1 }}
                                    </div>
                                    <img src="{{ $user->profile_photo_path ? '/storage/'.$user->profile_photo_path : 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=1f2937&color=fff' }}" class="w-10 h-10 rounded-full border border-gray-700 shadow-sm group-hover:border-blue-500 transition-colors">
                                    <div>
                                        <h3 class="text-sm font-bold text-gray-200 group-hover:text-white">{{ $user->name }}</h3>
                                        <p class="text-[10px] text-gray-500 uppercase tracking-wider font-bold">Motor Operativo</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-xl font-black text-white group-hover:text-blue-400 transition-colors">{{ number_format($user->total_tasks) }}</p>
                                    <p class="text-[9px] uppercase font-bold text-gray-500 tracking-widest">Actividades</p>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-10 bg-white/5 rounded-2xl border border-white/5">
                                <p class="text-sm text-gray-400 font-bold uppercase tracking-wider">No hay datos suficientes</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Status Breakdown -->
                <div class="glass-panel rounded-[2rem] p-6 lg:p-8 flex flex-col">
                    <div class="mb-4 flex justify-between items-start">
                        <div>
                            <h2 class="text-base font-black text-white uppercase tracking-widest">Distribución de Estados</h2>
                            <p class="text-xs text-gray-500 mt-1">Visión macro del volumen de trabajo actual</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 cursor-help mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ __('Proporción de todas las tareas del sistema según su estado (ej. completadas, en progreso, canceladas).') }}"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div id="statusDistributionChart" class="w-full flex-grow flex items-center justify-center min-h-[280px]"></div>
                </div>

            </div>
        </div>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const textColor = '#9ca3af';
        const gridColor = 'rgba(255,255,255,0.05)';

        // 1. Regularidad de Accesos (Bar)
        if (document.getElementById('loginTrendChart')) {
            const loginData = @json($auditData['charts']['login_trend']);
            const loginOptions = {
                series: [{ name: 'Accesos', data: loginData.map(d => d.logins) }],
                chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
                colors: ['#3b82f6'],
                plotOptions: { bar: { borderRadius: 4, columnWidth: '50%' } },
                dataLabels: { enabled: false },
                xaxis: { 
                    categories: loginData.map(d => d.date),
                    axisBorder: { show: false }, axisTicks: { show: false },
                    labels: { style: { colors: textColor, fontSize: '10px', fontWeight: 600 } }
                },
                yaxis: { labels: { style: { colors: textColor, fontSize: '10px', fontWeight: 600 } } },
                grid: { borderColor: gridColor, strokeDashArray: 4, position: 'back' },
                tooltip: { theme: 'dark' }
            };
            new ApexCharts(document.getElementById('loginTrendChart'), loginOptions).render();
        }

        // 2. Carga Transaccional (Area)
        if (document.getElementById('systemActivityChart')) {
            const hourlyData = @json($auditData['charts']['hourly_activity']);
            const activityOptions = {
                series: [{ name: 'Operaciones', data: hourlyData.map(d => d.requests) }],
                chart: { type: 'area', height: 280, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
                colors: ['#10b981'],
                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.5, opacityTo: 0.05, stops: [0, 90, 100] } },
                stroke: { curve: 'smooth', width: 3 },
                dataLabels: { enabled: false },
                xaxis: { 
                    categories: hourlyData.map(d => d.hour),
                    axisBorder: { show: false }, axisTicks: { show: false },
                    labels: { style: { colors: textColor, fontSize: '10px', fontWeight: 600 } }
                },
                yaxis: { labels: { style: { colors: textColor, fontSize: '10px', fontWeight: 600 } } },
                grid: { borderColor: gridColor, strokeDashArray: 4 },
                tooltip: { theme: 'dark' }
            };
            new ApexCharts(document.getElementById('systemActivityChart'), activityOptions).render();
        }

        // 3. Status Distribution (Donut)
        if (document.getElementById('statusDistributionChart')) {
            const statusData = @json($auditData['charts']['status_distribution']);
            const labels = Object.keys(statusData).map(k => k.charAt(0).toUpperCase() + k.slice(1).replace('_', ' '));
            const series = Object.values(statusData);
            
            const colors = ['#8b5cf6', '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#6b7280'];

            const donutOptions = {
                series: series.length ? series : [1],
                labels: labels.length ? labels : ['Sin datos'],
                chart: { type: 'donut', height: 300, fontFamily: 'Inter, sans-serif' },
                colors: colors,
                stroke: { show: true, colors: ['#111827'], width: 3 },
                dataLabels: { enabled: false },
                plotOptions: {
                    pie: { 
                        donut: { 
                            size: '75%', 
                            labels: { 
                                show: true, 
                                name: { show: true, color: '#9ca3af', fontSize: '12px' }, 
                                value: { show: true, fontWeight: 900, color: '#fff', fontSize: '24px' }, 
                                total: { show: true, label: 'TOTAL', color: '#6b7280', fontSize: '10px', fontWeight: 800 } 
                            } 
                        } 
                    }
                },
                legend: { position: 'bottom', labels: { colors: textColor } },
                tooltip: { theme: 'dark' }
            };
            new ApexCharts(document.getElementById('statusDistributionChart'), donutOptions).render();
        }
    });
    </script>
    @endpush
@endsection
