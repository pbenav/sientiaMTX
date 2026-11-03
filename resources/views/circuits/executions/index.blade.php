<x-app-layout>
    @section('title', 'Circuitos de Tramitación — ' . $team->name)

    <x-slot name="header">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2 min-w-0">
                <a href="{{ route('teams.dashboard', $team) }}"
                    class="p-1.5 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-lg transition-all shrink-0"
                    title="{{ __('Back') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                @include('teams.partials.breadcrumb')
                <span class="text-gray-300 dark:text-gray-700 mx-1">/</span>
                <h1 class="text-base font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight flex items-center gap-1.5">
                    <svg class="h-4 w-4 text-violet-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <span class="truncate">Circuitos de Tramitación</span>
                </h1>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @include('teams.partials.header-toolbar')
            </div>
        </div>

        <div class="mt-2">
            <x-demo-hint>
                Los circuitos de tramitación permiten canalizar flujos formales de visto bueno, justificación colegiada, resolución y publicación de expedientes y actividades del equipo.
            </x-demo-hint>
        </div>

        @include('teams.partials.team-view-nav')

        @include('circuits.partials.circuits-nav')
    </x-slot>

    <div class="space-y-6">

        <!-- Filters and search bar -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col md:flex-row items-center justify-between gap-4">
            <!-- Status Tabs -->
            <div class="flex items-center gap-1 overflow-x-auto w-full md:w-auto pb-2 md:pb-0 text-xs font-bold">
                <a href="{{ route('teams.circuits.index', $team) }}"
                    class="px-3 py-1.5 rounded-xl transition-colors {{ empty($status) ? 'bg-violet-600 text-white shadow-xs' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    Todos
                </a>
                <a href="{{ route('teams.circuits.index', [$team, 'status' => 'in_progress']) }}"
                    class="px-3 py-1.5 rounded-xl transition-colors {{ $status === 'in_progress' ? 'bg-violet-600 text-white shadow-xs' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    En Tramitación
                </a>
                <a href="{{ route('teams.circuits.index', [$team, 'status' => 'approved']) }}"
                    class="px-3 py-1.5 rounded-xl transition-colors {{ $status === 'approved' ? 'bg-violet-600 text-white shadow-xs' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    Aprobados
                </a>
                <a href="{{ route('teams.circuits.index', [$team, 'status' => 'returned']) }}"
                    class="px-3 py-1.5 rounded-xl transition-colors {{ $status === 'returned' ? 'bg-violet-600 text-white shadow-xs' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    En Subsanación
                </a>
                <a href="{{ route('teams.circuits.index', [$team, 'status' => 'published']) }}"
                    class="px-3 py-1.5 rounded-xl transition-colors {{ $status === 'published' ? 'bg-violet-600 text-white shadow-xs' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    Publicados
                </a>
            </div>

            <!-- Search input -->
            <form action="{{ route('teams.circuits.index', $team) }}" method="GET" class="w-full md:w-80">
                @if($status) <input type="hidden" name="status" value="{{ $status }}"> @endif
                <div class="relative flex items-center">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por título, #ID, plantilla o autor..."
                        class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-900 dark:text-white pl-9 pr-8 py-2 focus:ring-violet-500 focus:border-violet-500">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    @if($search)
                        <a href="{{ route('teams.circuits.index', array_filter(['team' => $team, 'status' => $status])) }}"
                            class="absolute right-2.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                            title="Limpiar búsqueda">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Executions Table / Cards -->
        @if($executions->isEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-12 text-center border border-gray-100 dark:border-gray-700 shadow-sm max-w-lg mx-auto">
                <div class="w-16 h-16 rounded-3xl bg-violet-50 dark:bg-violet-900/20 text-violet-600 dark:text-violet-400 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        @if($search)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        @endif
                    </svg>
                </div>
                <h3 class="text-lg font-black text-gray-900 dark:text-white mb-1">
                    {{ $search ? 'No se encontraron resultados' : 'No hay tramitaciones registradas' }}
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                    {{ $search ? 'No existe ninguna tramitación que coincida con los criterios de búsqueda.' : 'Puedes iniciar un circuito de tramitación desde la vista de cualquier Actividad o Expediente del equipo.' }}
                </p>
                @if($search)
                    <a href="{{ route('teams.circuits.index', array_filter(['team' => $team, 'status' => $status])) }}"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-900/20 hover:bg-violet-100 dark:hover:bg-violet-900/40 transition-colors">
                        <span>Limpiar búsqueda</span>
                    </a>
                @endif
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-750 text-gray-500 dark:text-gray-400 uppercase font-black tracking-wider text-[10px] border-b border-gray-100 dark:border-gray-700">
                            <tr>
                                <th class="py-3.5 px-4">Trámite / Título</th>
                                <th class="py-3.5 px-4">Elemento</th>
                                <th class="py-3.5 px-4">Estado</th>
                                <th class="py-3.5 px-4">Paso Actual</th>
                                <th class="py-3.5 px-4">Progreso</th>
                                <th class="py-3.5 px-4">Iniciado</th>
                                <th class="py-3.5 px-4 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-gray-700 dark:text-gray-300">
                            @foreach($executions as $exec)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750/30 transition-colors">
                                    <td class="py-3.5 px-4 font-bold text-gray-900 dark:text-white max-w-xs truncate">
                                        <a href="{{ route('teams.circuits.show', [$team, $exec]) }}" class="hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                            {{ $exec->title }}
                                        </a>
                                        @if($exec->template)
                                            <div class="text-[10px] font-normal text-gray-400">
                                                {{ $exec->template->name }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4">
                                        @if($exec->circuitable)
                                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-gray-600 dark:text-gray-300">
                                                <span class="w-1.5 h-1.5 rounded-full bg-violet-400"></span>
                                                {{ class_basename($exec->circuitable_type) }}: {{ \Illuminate\Support\Str::limit($exec->circuitable->title ?? $exec->circuitable->name ?? '', 25) }}
                                            </span>
                                        @else
                                            <span class="text-gray-400 italic">Desvinculado</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4">
                                        @php
                                            $stClass = match($exec->status) {
                                                'approved' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
                                                'in_progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                                                'returned' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                                                'rejected' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300',
                                                'published' => 'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-300',
                                                default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                            };
                                        @endphp
                                        <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-md {{ $stClass }}">
                                            {{ match($exec->status) {
                                                'approved' => 'Aprobado',
                                                'in_progress' => 'En curso',
                                                'returned' => 'Devuelto',
                                                'rejected' => 'Rechazado',
                                                'published' => 'Publicado',
                                                'cancelled' => 'Cancelado',
                                                default => ucfirst($exec->status),
                                            } }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        @if($exec->currentStep && $exec->is_active)
                                            <div class="text-[11px] font-semibold text-gray-800 dark:text-gray-200">
                                                {{ $exec->currentStep->name }}
                                            </div>
                                            <div class="text-[10px] text-gray-400">
                                                {{ $exec->currentStep->action_type_label }}
                                            </div>
                                        @else
                                            <span class="text-gray-400 text-xs">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-2">
                                            <div class="w-16 h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                                <div class="h-full bg-violet-600 rounded-full" style="width: {{ $exec->progress_percentage }}%"></div>
                                            </div>
                                            <span class="text-[11px] font-black text-gray-600 dark:text-gray-400">{{ $exec->progress_percentage }}%</span>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 text-gray-500 dark:text-gray-400 text-[11px]">
                                        <div>{{ $exec->initiator?->name }}</div>
                                        <div class="text-[10px] text-gray-400">{{ $exec->created_at->format('d/m/Y') }}</div>
                                    </td>
                                    <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5 justify-end">
                                            <a href="{{ route('teams.circuits.show', [$team, $exec]) }}"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-900/30 hover:bg-violet-100 dark:hover:bg-violet-900/50 transition-colors">
                                                <span>Ver detalle</span>
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            </a>
                                            @if($isOwnerOrAdmin)
                                                <button type="button"
                                                    @click="$dispatch('open-delete-circuit-modal', { id: {{ $exec->id }}, title: '{{ addslashes($exec->title) }}', action: '{{ route('teams.circuits.destroy', [$team, $exec]) }}' })"
                                                    class="p-1.5 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors"
                                                    title="Eliminar tramitación de prueba (Admin)">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-gray-100 dark:border-gray-700">
                    {{ $executions->links() }}
                </div>
            </div>
        @endif

    </div>

    @if($isOwnerOrAdmin)
        <!-- Modal Confirmación Eliminación (Admin) -->
        <div x-data="{ open: false, circuitId: null, circuitTitle: '', formAction: '' }"
             @open-delete-circuit-modal.window="open = true; circuitId = $event.detail.id; circuitTitle = $event.detail.title; formAction = $event.detail.action"
             x-show="open"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div @click.away="open = false"
                 class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-gray-900 dark:text-white">¿Eliminar tramitación?</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Acción reservada para administradores</p>
                    </div>
                </div>
                <p class="text-xs text-gray-600 dark:text-gray-300 mb-4 leading-relaxed">
                    Estás a punto de eliminar la tramitación <strong class="text-gray-900 dark:text-white" x-text="'#' + circuitId + ' — ' + circuitTitle"></strong>. Esta opción está destinada a purgar pruebas o tramitaciones creadas por error.
                </p>
                <form :action="formAction" method="POST" class="flex justify-end gap-2">
                    @csrf
                    @method('DELETE')
                    <button type="button" @click="open = false"
                        class="px-4 py-2 text-xs font-bold rounded-xl text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-xs font-black rounded-xl bg-rose-600 text-white hover:bg-rose-700 shadow-md shadow-rose-600/20 transition-all">
                        Eliminar Definitivamente
                    </button>
                </form>
            </div>
        </div>
    @endif
</x-app-layout>

