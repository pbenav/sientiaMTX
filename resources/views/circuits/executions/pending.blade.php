<x-app-layout>
    @section('title', 'Pendientes de Firma y Visto Bueno — ' . $team->name)

    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs font-black uppercase tracking-widest text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-900/30 border border-violet-100 dark:border-violet-800/50 px-2.5 py-0.5 rounded-lg">
                        Bandeja de Despacho
                    </span>
                    <span class="px-2 py-0.5 text-xs font-black rounded-full bg-violet-600 text-white">
                        {{ $executions->total() }}
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white heading tracking-tight">
                    Pendientes de mi Visto Bueno / Firma
                </h1>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Trámites de actividades y expedientes que requieren tu validación, justificación o aprobación
                </p>
            </div>

            <div class="flex items-center gap-2.5">
                <a href="{{ route('teams.circuits.index', $team) }}"
                    class="px-4 py-2.5 text-xs font-bold rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                    Ver Todas las Tramitaciones
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($executions->isEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-12 text-center border border-gray-100 dark:border-gray-700 shadow-sm max-w-xl mx-auto my-8">
                <div class="w-16 h-16 rounded-3xl bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto mb-4 shadow-lg shadow-emerald-500/10">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h3 class="text-lg font-black text-gray-900 dark:text-white mb-1">
                    ¡Tu mesa de despacho está al día!
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 max-w-sm mx-auto">
                    No tienes ningún visto bueno, justificación técnica o aprobación pendiente en este momento.
                </p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4">
                @foreach($executions as $execution)
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 sm:p-6 border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md transition-all flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                        <div class="space-y-1.5 flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-md
                                    @if($execution->currentStep?->action_type === 'endorsement') bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300
                                    @elseif($execution->currentStep?->action_type === 'justification') bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300
                                    @elseif($execution->currentStep?->action_type === 'approval') bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300
                                    @else bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 @endif">
                                    {{ $execution->currentStep?->action_type_label }}
                                </span>

                                <span class="text-xs font-bold text-gray-400">·</span>
                                <span class="text-xs font-bold text-gray-600 dark:text-gray-300">
                                    Paso {{ $execution->currentStep?->order }}: {{ $execution->currentStep?->name }}
                                </span>

                                @if($execution->status === 'returned')
                                    <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-widest rounded-md bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                        Devuelto para Subsanar
                                    </span>
                                @endif
                            </div>

                            <h3 class="text-base font-black text-gray-900 dark:text-white truncate">
                                <a href="{{ route('teams.circuits.show', [$team, $execution]) }}" class="hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                    {{ $execution->title }}
                                </a>
                            </h3>

                            <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400 flex-wrap">
                                <span>Iniciado por: <strong class="text-gray-700 dark:text-gray-300">{{ $execution->initiator?->name }}</strong></span>
                                <span>Fecha: {{ $execution->started_at?->format('d/m/Y H:i') ?? $execution->created_at->format('d/m/Y H:i') }}</span>
                                @if($execution->circuitable)
                                    <span>Elemento: <strong class="text-gray-700 dark:text-gray-300">{{ class_basename($execution->circuitable_type) }}</strong></span>
                                @endif
                            </div>
                        </div>

                        <!-- Progress Bar & Action Button -->
                        <div class="flex items-center gap-4 shrink-0 w-full md:w-auto justify-between md:justify-end border-t md:border-t-0 pt-3 md:pt-0 border-gray-100 dark:border-gray-700">
                            <div class="hidden sm:flex flex-col items-end">
                                <span class="text-[10px] uppercase font-bold text-gray-400">Progreso</span>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <div class="w-20 h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-violet-600 rounded-full" style="width: {{ $execution->progress_percentage }}%"></div>
                                    </div>
                                    <span class="text-xs font-black text-violet-600 dark:text-violet-400">{{ $execution->progress_percentage }}%</span>
                                </div>
                            </div>

                            <a href="{{ route('teams.circuits.show', [$team, $execution]) }}"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-black bg-violet-600 hover:bg-violet-700 text-white shadow-md shadow-violet-600/20 transition-all hover:scale-105 active:scale-95">
                                <span>Resolver Trámite</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                        </div>
                    </div>
                @endforeach

                <div class="mt-4">
                    {{ $executions->links() }}
                </div>
            </div>
        @endif
    </div>
</x-app-layout>

