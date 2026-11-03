<x-app-layout>
    @section('title', 'Plantillas de Circuitos — ' . $team->name)

    <x-slot name="header">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2 min-w-0">
                <a href="{{ route('teams.circuits.index', $team) }}"
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="truncate">Plantillas de Circuitos</span>
                </h1>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @include('teams.partials.header-toolbar')
            </div>
        </div>

        <div class="mt-2">
            <x-demo-hint>
                Plantillas preconfiguradas con secuencias ordenadas de pasos (visto bueno, justificación, resolución y publicación opcional) aplicables a actividades y expedientes.
            </x-demo-hint>
        </div>

        @include('teams.partials.team-view-nav')

        @include('circuits.partials.circuits-nav')
    </x-slot>

    <div class="space-y-6">
        @if($templates->isEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-12 text-center border border-gray-100 dark:border-gray-700 shadow-sm max-w-lg mx-auto">
                <div class="w-16 h-16 rounded-3xl bg-violet-50 dark:bg-violet-900/20 text-violet-600 dark:text-violet-400 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
                <h3 class="text-lg font-black text-gray-900 dark:text-white mb-1">
                    Aún no hay plantillas creadas
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-6">
                    Crea tu primera plantilla de circuito (por ejemplo: Visto Bueno -> Justificación -> Aprobación).
                </p>
                <a href="{{ route('teams.circuits.templates.create', $team) }}"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-black bg-violet-600 text-white hover:bg-violet-700 shadow-md shadow-violet-600/20">
                    Crear Primera Plantilla
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($templates as $template)
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <span class="px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-md
                                    @if($template->target_type === 'activity') bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300
                                    @elseif($template->target_type === 'expediente') bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300
                                    @else bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 @endif">
                                    Aplica a: {{ match($template->target_type) { 'activity' => 'Actividades', 'expediente' => 'Expedientes', default => 'Ambos' } }}
                                </span>

                                @if($template->is_active)
                                    <span class="w-2 h-2 rounded-full bg-emerald-500" title="Activa"></span>
                                @else
                                    <span class="w-2 h-2 rounded-full bg-gray-400" title="Inactiva"></span>
                                @endif
                            </div>

                            <h3 class="text-base font-black text-gray-900 dark:text-white">
                                {{ $template->name }}
                            </h3>

                            @if($template->description)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">
                                    {{ $template->description }}
                                </p>
                            @endif

                            <!-- Steps Sequence Preview -->
                            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <span class="text-[10px] font-black uppercase tracking-wider text-gray-400 block mb-2">
                                    Etapas del circuito ({{ $template->steps->count() }})
                                </span>
                                <div class="space-y-1.5">
                                    @foreach($template->steps as $step)
                                        <div class="flex items-center gap-2 text-xs">
                                            <span class="w-5 h-5 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 font-bold flex items-center justify-center text-[10px] shrink-0">
                                                {{ $step->order }}
                                            </span>
                                            <span class="font-bold text-gray-700 dark:text-gray-300 truncate flex-1">
                                                {{ $step->name }}
                                            </span>
                                            <span class="text-[10px] px-1.5 py-0.5 rounded-md font-semibold bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 shrink-0">
                                                {{ $step->action_type_label }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between text-xs">
                            <span class="text-gray-400 text-[11px]">
                                {{ $template->executions_count }} tramitaciones
                            </span>

                            <div class="flex items-center gap-2">
                                <a href="{{ route('teams.circuits.templates.edit', [$team, $template]) }}"
                                    class="p-2 text-gray-500 hover:text-violet-600 dark:hover:text-violet-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors" title="Editar plantilla">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>

                                <form action="{{ route('teams.circuits.templates.destroy', [$team, $template]) }}" method="POST"
                                    onsubmit="return confirm('¿Seguro que deseas eliminar esta plantilla de circuito?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="p-2 text-gray-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-xl transition-colors" title="Eliminar plantilla">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $templates->links() }}
            </div>
        @endif
    </div>
</x-app-layout>

