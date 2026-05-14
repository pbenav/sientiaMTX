<x-app-layout>
    @section('title', 'Log de Seguridad (ENS)')

    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 rounded-xl shadow-sm border border-violet-200 dark:border-violet-800/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white heading">Auditoría de Seguridad (ENS)</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Historial unificado de trazabilidad de accesos y modificaciones críticas bajo el Esquema Nacional de Seguridad.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-12 px-4">
        <div class="max-w-7xl mx-auto">
            @include('settings.partials.tabs')

            <!-- Compliance Status Header Card -->
            <div class="mb-8 p-6 bg-emerald-50/50 dark:bg-emerald-950/10 rounded-3xl border border-emerald-100 dark:border-emerald-800/30 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="p-3 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-2xl shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Estado de Cumplimiento ENS: <span class="text-emerald-600 dark:text-emerald-400">ACTIVO</span></h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">El sistema registra y mantiene inmutable el historial de accesos fallidos, login, cambios de configuración y cambios en roles de equipo.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 bg-white dark:bg-gray-900 border border-emerald-200 dark:border-emerald-800 rounded-lg text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-tight">RD 311/2022</span>
                </div>
            </div>

            <!-- Search and Table Container -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl overflow-hidden shadow-sm">
                <div class="p-6 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30 flex flex-col md:flex-row items-center justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-black uppercase tracking-widest text-violet-600 dark:text-violet-400">Trazabilidad en Tiempo Real</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Inspecciona los eventos de seguridad almacenados de forma segura en la base de datos.</p>
                    </div>
                    
                    <!-- Search Input -->
                    <form action="{{ route('settings.security') }}" method="GET" class="w-full md:w-auto">
                        <div class="relative">
                            <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por evento, IP o descripción..." class="w-full md:w-80 bg-white dark:bg-gray-900 border-gray-100 dark:border-gray-800 rounded-xl text-xs pl-8 focus:ring-violet-500/20 focus:border-violet-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 absolute left-2.5 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </form>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-800 bg-gray-50/30 dark:bg-gray-800/10">
                                <th class="p-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Fecha y Hora</th>
                                <th class="p-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Usuario / Cuenta</th>
                                <th class="p-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Tipo de Evento</th>
                                <th class="p-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Descripción de la Acción</th>
                                <th class="p-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Dirección IP / Navegador</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($logs as $log)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/10 transition-colors">
                                    <td class="p-4 text-xs font-mono text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                        {{ $log->created_at->format('d/m/Y H:i:s') }}
                                    </td>
                                    <td class="p-4 text-xs whitespace-nowrap">
                                        @if($log->user)
                                            <div class="flex items-center gap-2">
                                                <div class="h-6 w-6 rounded-full bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 flex items-center justify-center font-black text-[10px]">
                                                    {{ strtoupper(substr($log->user->name, 0, 2)) }}
                                                </div>
                                                <div>
                                                    <span class="font-bold text-gray-900 dark:text-white">{{ $log->user->name }}</span>
                                                    <span class="block text-[10px] text-gray-500">{{ $log->user->email }}</span>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-gray-400 font-medium">Sistema / Invitado</span>
                                        @endif
                                    </td>
                                    <td class="p-4 text-xs whitespace-nowrap">
                                        @php
                                            $badgeClasses = match($log->event) {
                                                'auth.login' => 'bg-emerald-100 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400',
                                                'auth.failed' => 'bg-rose-100 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400',
                                                'auth.logout' => 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400',
                                                'setting.updated' => 'bg-amber-100 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400',
                                                'team.member_added' => 'bg-blue-100 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400',
                                                'team.role_updated' => 'bg-indigo-100 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400',
                                                'team.member_removed' => 'bg-orange-100 dark:bg-orange-900/20 text-orange-600 dark:text-orange-400',
                                                default => 'bg-violet-100 dark:bg-violet-900/20 text-violet-600 dark:text-violet-400'
                                            };
                                            $eventLabel = match($log->event) {
                                                'auth.login' => 'Inicio de Sesión',
                                                'auth.failed' => 'Intento Fallido',
                                                'auth.logout' => 'Cierre de Sesión',
                                                'setting.updated' => 'Ajustes Modificados',
                                                'team.member_added' => 'Miembro Añadido',
                                                'team.role_updated' => 'Rol Actualizado',
                                                'team.member_removed' => 'Miembro Eliminado',
                                                default => $log->event
                                            };
                                        @endphp
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider {{ $badgeClasses }}">
                                            {{ $eventLabel }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-xs text-gray-700 dark:text-gray-300">
                                        {{ $log->description }}
                                    </td>
                                    <td class="p-4 text-xs whitespace-nowrap">
                                        <div class="font-mono text-gray-600 dark:text-gray-400 font-bold">
                                            {{ $log->ip_address }}
                                        </div>
                                        <div class="text-[9px] text-gray-400 max-w-xs truncate" title="{{ $log->user_agent }}">
                                            {{ $log->user_agent }}
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-12 text-center">
                                        <div class="flex flex-col items-center justify-center gap-3">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <p class="text-sm text-gray-500 font-medium">No se encontraron logs de seguridad registrados.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($logs->hasPages())
                    <div class="p-6 border-t border-gray-100 dark:border-gray-800 bg-gray-50/30 dark:bg-gray-800/10">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
