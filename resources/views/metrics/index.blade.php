<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-wider">
            {{ __('WarZone - Cuadros de Mando') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto space-y-8 pb-12">
        <div class="relative overflow-hidden rounded-[2rem] bg-gray-900 p-8 text-white shadow-2xl border border-gray-800">
            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjIiIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSIvPjwvc3ZnPg==')] opacity-30"></div>
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-red-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>

            <div class="relative z-10">
                <p class="text-red-400 font-bold tracking-widest uppercase text-xs mb-2">Centro de Control Táctico</p>
                <h1 class="text-4xl font-extrabold tracking-tight mb-2">WarZone</h1>
                <p class="text-gray-400 max-w-2xl text-sm leading-relaxed">
                    Accede a todos los paneles de mando de la organización. Desde el rendimiento personal hasta las métricas ejecutivas de alto nivel y el bienestar de los equipos.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Personal -->
            <a href="{{ route('metrics.personal.daily') }}" class="group block bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-xl hover:border-violet-500/50 transition-all">
                <div class="w-12 h-12 rounded-2xl bg-violet-100 dark:bg-violet-500/20 text-violet-600 dark:text-violet-400 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Dashboard Personal</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Tu matriz de eficiencia, prioridades diarias y racha activa.</p>
            </a>

            <!-- Ejecutivo -->
            <a href="{{ route('metrics.executive.index') }}" class="group block bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-xl hover:border-sky-500/50 transition-all">
                <div class="w-12 h-12 rounded-2xl bg-sky-100 dark:bg-sky-500/20 text-sky-600 dark:text-sky-400 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Cuadro Ejecutivo</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Métricas de alto nivel para la dirección de la organización.</p>
            </a>

            <!-- Gestión de Equipos -->
            <a href="{{ route('metrics.manager.index') }}" class="group block bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-xl hover:border-amber-500/50 transition-all">
                <div class="w-12 h-12 rounded-2xl bg-amber-100 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Gestión de Equipos</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Rendimiento, capacidad y carga de trabajo de tus equipos.</p>
            </a>

            <!-- Bienestar -->
            <a href="{{ route('metrics.wellness.index') }}" class="group block bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-xl hover:border-rose-500/50 transition-all">
                <div class="w-12 h-12 rounded-2xl bg-rose-100 dark:bg-rose-500/20 text-rose-600 dark:text-rose-400 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Salud y Bienestar</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Riesgo de burnout, monitorización de energía y descansos.</p>
            </a>

            <!-- Gamificación -->
            <a href="{{ route('metrics.gamification.index') }}" class="group block bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-xl hover:border-orange-500/50 transition-all">
                <div class="w-12 h-12 rounded-2xl bg-orange-100 dark:bg-orange-500/20 text-orange-600 dark:text-orange-400 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Gamificación y Logros</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Leaderboards, medallas, rachas e incentivos de equipo.</p>
            </a>
            
            <!-- Citas -->
            <a href="{{ route('metrics.appointments.index') }}" class="group block bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-xl hover:border-emerald-500/50 transition-all">
                <div class="w-12 h-12 rounded-2xl bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Analítica de Citas</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Estadísticas de reuniones, duraciones y asistencia.</p>
            </a>
        </div>
    </div>
</x-app-layout>
