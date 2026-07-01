<x-app-layout>
    @section('title', $activity->title . ' — Actividades')

    @php
        $templateLoader = app(\App\Services\TemplateLoader::class);
        $template = $templateLoader->getTemplate($activity->type);
        $validStatuses = !empty($template['states']) ? array_keys($template['states']) : ($activity::STATUSES ?? ['pending', 'completed']);
        $currentStatus = $activity->status_value;

        // Traducir estados para la interfaz
        $statusTranslations = [
            'pending' => '⏳ Pendiente',
            'in_progress' => '⚡ En Progreso',
            'completed' => '✅ Completado',
            'cancelled' => '❌ Cancelado',
            'blocked' => '🚫 Bloqueado',
            'active' => '🟢 Activo',
            'broken' => '💔 Roto / Caído',
            'draft' => '📝 Borrador',
            'review' => '👁️ En Revisión',
            'reviewing' => '👁️ En Revisión',
            'approved' => '👍 Aprobado',
            'rejected' => '👎 Rechazado',
            'published' => '🌍 Publicado',
            'archived' => '📁 Archivado',
            'proposed' => '💡 Propuesto',
            'deferred' => '📅 Aplazado',
            'superseded' => '🔄 Reemplazado',
            'triggered' => '🔔 Disparado',
            'dismissed' => '🔕 Descartado',
            'snoozed' => '💤 Pospuesto',
            'scheduled' => '📅 Programado',
            'uploaded' => '☁️ Subido',
            'editing' => '✏️ En Edición',
            'reviewed' => '👀 Revisado',
        ];

        $statusColor = match ($currentStatus) {
            'completed', 'approved', 'published', 'active', 'reviewed' => 'text-emerald-700 bg-emerald-50 border-emerald-200 dark:text-emerald-400 dark:bg-emerald-950/30 dark:border-emerald-900',
            'in_progress', 'review', 'reviewing', 'editing', 'uploaded' => 'text-blue-700 bg-blue-50 border-blue-200 dark:text-blue-400 dark:bg-blue-950/30 dark:border-blue-900',
            'cancelled', 'rejected', 'broken' => 'text-red-700 bg-red-50 border-red-200 dark:text-red-400 dark:bg-red-950/30 dark:border-red-900',
            'blocked' => 'text-white bg-rose-600 border-rose-700 dark:bg-rose-900 dark:border-rose-800 font-bold animate-pulse',
            'draft', 'proposed', 'deferred', 'snoozed', 'scheduled', 'archived' => 'text-amber-700 bg-amber-50 border-amber-200 dark:text-amber-400 dark:bg-amber-950/30 dark:border-amber-900',
            default => 'text-gray-700 bg-gray-50 border-gray-200 dark:text-gray-400 dark:bg-gray-900 dark:border-gray-800',
        };

        $canEditActivity = auth()->user()->is_admin || $team->isCoordinator(auth()->user()) || auth()->id() === $activity->created_by_id || auth()->id() === $activity->assigned_user_id || $activity->assignedTo->contains(auth()->id()) || auth()->user()->can('update', $activity);
    @endphp

    <x-slot name="header">
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4">
            <div class="flex items-start gap-3 min-w-0 flex-1">
                <a href="{{ route('teams.activities.index', $team) }}"
                    class="mt-1 p-2 bg-gray-50 dark:bg-gray-800/50 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-xl transition-all shadow-sm border border-gray-100 dark:border-gray-700/50 shrink-0"
                    title="{{ __('navigation.back') ?? 'Volver' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div class="min-w-0 flex-1">
                    @include('teams.partials.breadcrumb')
                    <h1 class="text-xl sm:text-3xl font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight flex items-center gap-3">
                        Detalle de la actividad
                        <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-{{ $activity->type_badge_color }}-100 text-{{ $activity->type_badge_color }}-700 dark:bg-{{ $activity->type_badge_color }}-900/40 dark:text-{{ $activity->type_badge_color }}-300 border border-{{ $activity->type_badge_color }}-200 dark:border-{{ $activity->type_badge_color }}-700/50 shadow-sm flex items-center gap-1.5 shrink-0">
                            {!! $activity->type_icon !!}
                            {{ $activity->type_label }}
                        </span>
                    </h1>
                    <x-demo-hint>
                        La ficha técnica de la actividad centraliza toda su información y configuración específica: permite gestionar estados, adjuntos, consultar metadatos del subtipo y colaborar mediante notas internas.
                    </x-demo-hint>
                </div>
            </div>
        </div>

        <!-- View Switcher Sub-Header -->
        <div class="mt-8 mb-4 flex w-full">
            @include('teams.partials.view-switcher')
        </div>

        <!-- Activity Actions Footer Row -->
        <div class="flex items-center gap-2 flex-wrap shrink-0 mt-4 border-t border-gray-100 dark:border-gray-800 pt-6">
            @if($team->isCoordinator(auth()->user()) || auth()->user()->is_admin)
                <a href="{{ route('teams.activities.create', $team) }}"
                    class="shrink-0 flex items-center gap-1.5 text-xs bg-violet-600 hover:bg-violet-500 text-white px-4 py-2.5 rounded-xl transition-all shadow-lg shadow-violet-500/20 font-bold active:scale-95">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="hidden lg:inline">Crear actividad</span>
                </a>
            @endif

            @can('update', $activity)
                <a href="{{ route('teams.activities.edit', [$team, $activity]) }}"
                    class="shrink-0 flex items-center gap-1.5 text-xs bg-white dark:bg-white/5 border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300 px-4 py-2.5 rounded-xl transition-all font-bold hover:bg-gray-50 dark:hover:bg-white/10 active:scale-95 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edición de actividad
                </a>
            @endcan

            <!-- TIMER BUTTON (Start/Stop) -->
            @include('tasks.partials.task-timer-button', ['task' => $activity])

            <!-- Hub de Acciones Secundarias (Acciones) -->
            <x-dropdown align="center" width="80">
                <x-slot name="trigger">
                    <button type="button" class="shrink-0 flex items-center gap-1.5 text-xs bg-white dark:bg-white/5 border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300 px-4 py-2.5 rounded-xl transition-all font-bold hover:bg-gray-50 dark:hover:bg-white/10 active:scale-95 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                        </svg>
                        <span>{{ __('Acciones') }}</span>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <div class="px-3 py-2 text-[10px] font-black uppercase tracking-widest text-gray-400 border-b border-gray-50 dark:border-gray-700/50 mb-1">
                        Workspace & Portabilidad
                    </div>

                    <!-- Imprimir Ficha -->
                    <button type="button" onclick="printFullActivity()" class="w-full flex items-center gap-4 py-4 px-5 text-start hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150 ease-in-out group">
                        <div class="shrink-0 p-2 bg-violet-50 dark:bg-violet-900/20 text-violet-600 dark:text-violet-400 rounded-xl group-hover:scale-110 transition-transform shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                        </div>
                        <div class="flex flex-col">
                            <span class="font-bold text-gray-900 dark:text-white text-sm">Imprimir Ficha Técnica</span>
                            <span class="text-[10px] text-gray-500 font-medium tracking-normal mt-0.5">Informe completo y detallado de la actividad</span>
                        </div>
                    </button>

                    @if ($activity->type === 'document')
                    <!-- Imprimir Libro Digital -->
                    <button type="button" onclick="printDocumentBook()" class="w-full flex items-center gap-4 py-4 px-5 text-start hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150 ease-in-out group">
                        <div class="shrink-0 p-2 bg-teal-50 dark:bg-teal-900/20 text-teal-600 dark:text-teal-400 rounded-xl group-hover:scale-110 transition-transform shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <div class="flex flex-col">
                            <span class="font-bold text-gray-900 dark:text-white text-sm">Imprimir Libro Digital</span>
                            <span class="text-[10px] text-gray-500 font-medium tracking-normal mt-0.5">Generar documento en formato libro paginado e indexado</span>
                        </div>
                    </button>
                    @endif

                    <!-- Reproducir en Equipo -->
                    @php
                        $otherTeams = auth()->user()->teams()->where('teams.id', '!=', $team->id)->get();
                    @endphp
                    @if($otherTeams->count() > 0)
                        <button type="button" onclick="reproduceInTeam()" class="w-full flex items-center gap-4 py-4 px-5 text-start hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150 ease-in-out group">
                            <div class="shrink-0 p-2 bg-violet-50 text-violet-600 rounded-xl group-hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-bold text-gray-900 dark:text-white text-sm">Reproducir en Equipo</span>
                                <span class="text-[10px] text-gray-500 font-medium tracking-normal mt-0.5">Clonar actividad en otro espacio de trabajo</span>
                            </div>
                        </button>
                    @endif

                    <!-- Archivar / Desarchivar -->
                    @can('archive', $activity)
                        @if ($activity->is_archived)
                            <form action="{{ route('teams.activities.unarchive', [$team, $activity]) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-4 py-4 px-5 text-start hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150 ease-in-out group">
                                    <div class="shrink-0 p-2 bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 rounded-xl group-hover:scale-110 transition-transform">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-gray-900 dark:text-white text-sm">Desarchivar</span>
                                        <span class="text-[10px] text-gray-500 font-medium tracking-normal mt-0.5">Restaurar actividad al workspace activo</span>
                                    </div>
                                </button>
                            </form>
                        @else
                            <form action="{{ route('teams.activities.archive', [$team, $activity]) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-4 py-4 px-5 text-start hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150 ease-in-out group">
                                    <div class="shrink-0 p-2 bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 rounded-xl group-hover:scale-110 transition-transform">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                        </svg>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-gray-900 dark:text-white text-sm">Archivar</span>
                                        <span class="text-[10px] text-gray-500 font-medium tracking-normal mt-0.5">Mover a archivo histórico</span>
                                    </div>
                                </button>
                            </form>
                        @endif
                    @endcan

                    <!-- Exportar JSON -->
                    <div class="flex flex-col border-t border-gray-50 dark:border-gray-800 pt-1 mt-1">
                        <div class="px-5 py-2 text-[9px] font-black uppercase tracking-widest text-gray-400">Portabilidad (Outbound)</div>
                        <x-dropdown-link :href="route('teams.activities.export-json', [$team, $activity])" class="flex items-center gap-4 py-3 px-5 group">
                            <div class="shrink-0 p-2 bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-xl group-hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-bold text-gray-900 dark:text-white text-sm">Exportar Actividad (.json)</span>
                                <span class="text-[10px] text-gray-500 font-medium tracking-normal mt-0.5">Descargar backup portátil</span>
                            </div>
                        </x-dropdown-link>

                        <button type="button" onclick="copyActivityJson()" class="w-full flex items-center gap-4 py-3 px-5 text-start hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150 ease-in-out group">
                            <div class="shrink-0 p-2 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-xl group-hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-bold text-gray-900 dark:text-white text-sm">Copiar JSON</span>
                                <span class="text-[10px] text-gray-500 font-medium tracking-normal mt-0.5">Copia para transferir a otro equipo</span>
                            </div>
                        </button>

                        <div class="px-5 py-2 text-[9px] font-black uppercase tracking-widest text-gray-400 mt-2 border-t border-gray-50 dark:border-gray-800 pt-3">Portabilidad (Inbound)</div>
                        <!-- Cargar Archivo -->
                        <button type="button" onclick="openImportActivityModal('file')" class="w-full flex items-center gap-4 py-3 px-5 text-start hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150 ease-in-out group">
                            <div class="shrink-0 p-2 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 rounded-xl group-hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-bold text-gray-900 dark:text-white text-sm">Cargar Archivo (.json)</span>
                                <span class="text-[10px] text-gray-500 font-medium tracking-normal mt-0.5">Crear actividad desde un archivo local</span>
                            </div>
                        </button>

                        <!-- Pegar JSON -->
                        <button type="button" onclick="openImportActivityModal('paste')" class="w-full flex items-center gap-4 py-3 px-5 text-start hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150 ease-in-out group">
                            <div class="shrink-0 p-2 bg-teal-50 dark:bg-teal-900/20 text-teal-600 dark:text-teal-400 rounded-xl group-hover:scale-110 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m.5 4l3 3m0 0l3-3m-3 3V10" />
                                </svg>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-bold text-gray-900 dark:text-white text-sm">Pegar JSON</span>
                                <span class="text-[10px] text-gray-500 font-medium tracking-normal mt-0.5">Crear actividad desde el portapapeles</span>
                            </div>
                        </button>
                    </div>
                </x-slot>
            </x-dropdown>

            <!-- Hub de Integraciones (Integraciones) -->
            @php
                $isGoogleConnected = auth()->user()->teams()->where('team_id', $team->id)->wherePivotNotNull('google_token')->exists();
            @endphp
            @if($isGoogleConnected || $team->isCoordinator(auth()->user()) || auth()->user()->is_admin)
                <x-dropdown align="center" width="80">
                    <x-slot name="trigger">
                        <button type="button" class="shrink-0 flex items-center gap-1.5 text-xs bg-white dark:bg-white/5 border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300 px-4 py-2.5 rounded-xl transition-all font-bold hover:bg-gray-50 dark:hover:bg-white/10 active:scale-95 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 4a2 2 0 114 0v1a2 2 0 01-2 2h-1a2 2 0 01-2-2V4zm-5 6a2 2 0 114 0v1a2 2 0 01-2 2H7a2 2 0 01-2-2v-1zm10 0a2 2 0 114 0v1a2 2 0 01-2 2h-1a2 2 0 01-2-2v-1zM6 20a2 2 0 114 0v1a2 2 0 01-2 2H7a2 2 0 01-2-2v-1zm10 0a2 2 0 114 0v1a2 2 0 01-2 2h-1a2 2 0 01-2-2v-1z" />
                            </svg>
                            <span class="hidden sm:inline">Integraciones</span>
                            <svg class="h-3 w-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-3 py-2 text-[10px] font-black uppercase tracking-widest text-gray-400 border-b border-gray-50 dark:border-gray-700/50 mb-1">
                            Google Workspace
                        </div>
                        
                        @can('update', $activity)
                            <!-- Sincronización Google Tasks -->
                            <form action="{{ route('google.sync_activity', [$team, $activity]) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-4 py-4 px-5 text-start hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150 ease-in-out group">
                                    <div class="shrink-0 p-2 {{ $activity->google_task_id ? 'bg-violet-50 text-violet-600' : 'bg-amber-50 text-amber-600' }} rounded-xl group-hover:scale-110 transition-transform">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-gray-900 dark:text-white text-sm">Sincronizar Google Tasks</span>
                                        <span class="text-[10px] text-gray-500 font-medium tracking-normal mt-0.5">Vínculo bidireccional con Google</span>
                                    </div>
                                </button>
                            </form>
                            @if ($activity->google_task_id)
                                <form action="{{ route('google.disconnect_activity', [$team, $activity]) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center gap-4 py-4 px-5 text-start hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150 ease-in-out group">
                                        <div class="shrink-0 p-2 bg-gray-50 text-gray-400 rounded-xl group-hover:scale-110 transition-transform">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-bold text-gray-900 dark:text-white text-sm">Desconectar de Google Tasks</span>
                                            <span class="text-[10px] text-gray-500 font-medium tracking-normal mt-0.5">Romper el vínculo con Google Tasks</span>
                                        </div>
                                    </button>
                                </form>
                            @endif

                            <!-- Google Calendar -->
                            <form action="{{ route('google.export_calendar_activity', [$team, $activity]) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-4 py-4 px-5 text-start hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150 ease-in-out group">
                                    <div class="shrink-0 p-2 {{ $activity->google_calendar_event_id ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600' }} rounded-xl group-hover:scale-110 transition-transform">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-gray-900 dark:text-white text-sm">Calendario de Google</span>
                                        <span class="text-[10px] text-gray-500 font-medium tracking-normal mt-0.5">Gestionar evento en calendario</span>
                                    </div>
                                </button>
                            </form>
                            @if ($activity->google_calendar_event_id)
                                <form action="{{ route('google.disconnect_activity', [$team, $activity]) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center gap-4 py-4 px-5 text-start hover:bg-gray-50 dark:hover:bg-white/5 transition duration-150 ease-in-out group">
                                        <div class="shrink-0 p-2 bg-gray-50 text-gray-400 rounded-xl group-hover:scale-110 transition-transform">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-bold text-gray-900 dark:text-white text-sm">Desconectar Calendario</span>
                                            <span class="text-[10px] text-gray-500 font-medium tracking-normal mt-0.5">Romper el vínculo con Google Calendar</span>
                                        </div>
                                    </button>
                                </form>
                            @endif
                        @endcan
                    </x-slot>
                </x-dropdown>
            @endif

            <!-- Cloning Button -->
            <form id="clone-activity-form-{{ $activity->id }}" action="{{ route('teams.activities.clone', [$team, $activity]) }}" method="POST" class="inline">
                @csrf
                <button type="button" onclick="confirmCloneActivity('clone-activity-form-{{ $activity->id }}')" class="shrink-0 flex items-center gap-1.5 text-xs bg-violet-50 hover:bg-violet-100 dark:bg-violet-900/30 dark:hover:bg-violet-900/40 text-violet-600 dark:text-violet-400 px-4 py-2.5 rounded-xl transition-all font-bold active:scale-95 border border-violet-100 dark:border-violet-900/50 shadow-sm ml-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                    </svg>
                    <span class="hidden sm:inline">Clonar</span>
                </button>
            </form>

            @php
                $canConvert = auth()->user()->is_admin || $team->isCoordinator(auth()->user()) || auth()->id() === $activity->created_by_id || auth()->id() === $activity->assigned_user_id || $activity->assignedTo->contains(auth()->id());
            @endphp
            @if($canConvert && !$activity->isDeprecatedByConversion())
                <button type="button" @click="$dispatch('open-convert-activity-modal')" class="shrink-0 flex items-center gap-1.5 text-xs bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white px-4 py-2.5 rounded-xl transition-all font-bold active:scale-95 shadow-lg shadow-violet-500/25 ml-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                    <span class="hidden sm:inline">Convertir Actividad</span>
                </button>
            @endif

            @can('delete', $activity)
                <form id="delete-activity-form" action="{{ route('teams.activities.destroy', [$team, $activity]) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="button" onclick="confirmDeleteActivity()" class="shrink-0 flex items-center gap-1.5 text-xs bg-red-50 hover:bg-red-100 dark:bg-red-900/30 dark:hover:bg-red-900/40 text-red-600 dark:text-red-400 px-4 py-2.5 rounded-xl transition-all font-bold active:scale-95 border border-red-100 dark:border-red-900/50 shadow-sm ml-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <span class="hidden sm:inline">Eliminar</span>
                    </button>
                </form>
            @endcan
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 pb-24 lg:pb-0">

        <!-- COLUMNA PRINCIPAL (Detalles e Información) -->
        <div class="lg:col-span-2 space-y-5">

            @if ($activity->isDeprecatedByConversion())
                <div class="bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent border-2 border-amber-500/30 dark:border-amber-500/20 rounded-3xl p-6 shadow-sm relative overflow-hidden flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="absolute top-0 left-0 w-2 h-full bg-gradient-to-b from-amber-500 to-orange-500"></div>
                    <div class="flex items-start sm:items-center gap-4 pl-2">
                        <div class="w-12 h-12 rounded-2xl bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 border border-amber-500/30 shadow-inner">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="text-sm font-black text-amber-800 dark:text-amber-400 uppercase tracking-wider">Actividad Deprecada (Archivada por Conversión)</h3>
                                <span class="px-2.5 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 text-[10px] font-black uppercase tracking-widest border border-amber-200 dark:border-amber-800/50 shadow-sm">
                                    LEGACY
                                </span>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-300 font-medium leading-relaxed">
                                Esta actividad fue convertida a un nuevo formato. Queda oculta del flujo diario para preservar el rastro de auditoría. Puedes gestionarla o restaurarla.
                            </p>
                            @if($activity->convertedToActivity)
                                <a href="{{ route('teams.activities.show', [$team, $activity->convertedToActivity]) }}" class="inline-flex items-center gap-1 text-[11px] font-bold text-violet-600 dark:text-violet-400 hover:underline mt-2">
                                    👉 Ver nueva actividad resultante ({{ $activity->convertedToActivity->type_label ?? $activity->convertedToActivity->type }})
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 shrink-0 pl-2 sm:pl-0">
                        <!-- Restaurar -->
                        <form action="{{ route('teams.activities.restore-deprecated', [$team, $activity]) }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit" onclick="return confirm('¿Restaurar esta actividad al flujo activo?')" class="flex items-center gap-1.5 text-xs bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 hover:text-emerald-600 dark:hover:text-emerald-400 px-3.5 py-2 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-emerald-200 dark:hover:border-emerald-800 font-bold transition-all shadow-sm active:scale-95">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                Restaurar
                            </button>
                        </form>
                        <!-- Clonar Deprecada -->
                        <form action="{{ route('teams.activities.clone-deprecated', [$team, $activity]) }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit" onclick="return confirm('¿Generar un nuevo clon activo a partir de esta actividad deprecada?')" class="flex items-center gap-1.5 text-xs bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 hover:text-violet-600 dark:hover:text-violet-400 px-3.5 py-2 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-violet-200 dark:hover:border-violet-800 font-bold transition-all shadow-sm active:scale-95">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                                </svg>
                                Clonar
                            </button>
                        </form>
                        <!-- Fusionar (Merge) -->
                        <button type="button" @click="$dispatch('open-merge-deprecated-modal')" class="flex items-center gap-1.5 text-xs bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3.5 py-2 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-blue-200 dark:hover:border-blue-800 font-bold transition-all shadow-sm active:scale-95">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            Fusionar
                        </button>
                    </div>
                </div>
            @endif

            <!-- Activity Name Card -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm transition-colors">
                <div class="flex flex-wrap items-center gap-2 mb-3">
                    <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                        Nombre de la Actividad
                    </h3>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-{{ $activity->type_badge_color }}-100 text-{{ $activity->type_badge_color }}-700 dark:bg-{{ $activity->type_badge_color }}-900/40 dark:text-{{ $activity->type_badge_color }}-300 border border-{{ $activity->type_badge_color }}-200 dark:border-{{ $activity->type_badge_color }}-700/50 shadow-sm">
                        {{ $activity->type_label }}
                    </span>
                    @if ($activity->is_template)
                        <span class="px-2 py-0.5 rounded-full bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300 text-[9px] font-black uppercase tracking-widest border border-violet-200 dark:border-violet-700/50 shadow-sm flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            PLAN MAESTRO
                        </span>
                    @endif
                </div>
                <p class="text-2xl font-black text-gray-900 dark:text-white heading leading-tight tracking-tight">
                    {{ $activity->title }}
                </p>
            </div>

            <!-- Descripción y Observaciones -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm transition-colors">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Descripción</h3>
                    <div class="flex items-center gap-2">
                        @if($team->isCoordinator(auth()->user()) || auth()->id() === $activity->assigned_user_id || $activity->assignedTo->contains(auth()->id()))
                        <button @click="$dispatch('ai:analyze-task', { taskId: {{ $activity->id }}, teamId: {{ $team->id }}, taskTitle: '{{ addslashes($activity->title) }}', section: 'description', isActivity: true })" 
                                class="p-2 bg-violet-50 dark:bg-violet-900/40 text-violet-600 dark:text-violet-400 hover:text-violet-700 dark:hover:text-violet-300 rounded-xl transition-all shadow-sm flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest border border-violet-100 dark:border-violet-800/50"
                                title="Mejorar Resumen con IA">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Ax.ia
                        </button>
                        @endif
                        <button onclick="printSection('Descripción', 'description-content')" 
                                class="p-1.5 bg-gray-50 dark:bg-gray-800 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-xl transition-all border border-transparent hover:border-violet-100 dark:hover:border-violet-800 shadow-sm flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest"
                                title="Imprimir descripción">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Imprimir
                        </button>
                    </div>
                </div>
                @if ($activity->description)
                    <div id="description-content" style="height: 350px; max-height: none; overflow-y: auto;" class="prose dark:prose-invert prose-sm max-w-none text-gray-700 dark:text-gray-300 resize-y min-h-[150px] custom-scrollbar pr-4 py-2">
                        {!! str($activity->description)->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                    </div>
                @else
                    <p class="text-sm text-gray-400 italic">Sin descripción proporcionada.</p>
                @endif
            </div>

            @if ($activity->type === 'document')
                @php
                    $chapters = $activity->metadata['chapters'] ?? [];
                    $docVersion = $activity->metadata['version'] ?? '1.0.0';
                    $canEditDocument = auth()->user()->is_admin || $team->isCoordinator(auth()->user()) || auth()->id() === $activity->created_by_id || auth()->id() === $activity->assigned_user_id || $activity->assignedTo->contains(auth()->id()) || auth()->user()->can('update', $activity);
                @endphp
                <div id="chapters-section" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm transition-colors space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-violet-50 dark:bg-violet-950/40 text-violet-600 dark:text-violet-400 flex items-center justify-center shrink-0 border border-violet-100 dark:border-violet-800/50 shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-bold text-gray-800 dark:text-white">Estructura del Documento (Modo Libro)</h3>
                                    <span class="px-2.5 py-0.5 rounded-full bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300 text-[10px] font-black uppercase tracking-widest border border-violet-200 dark:border-violet-700/50 shadow-sm">
                                        v{{ $docVersion }}
                                    </span>
                                </div>
                                <p class="text-[10px] text-gray-400 dark:text-gray-500 font-medium uppercase tracking-wide">Añade secciones sin interferir en la descripción principal</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button" onclick="printDocumentBook()" class="flex items-center gap-1.5 text-xs bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:text-violet-600 dark:hover:text-violet-400 px-3.5 py-2 rounded-xl border border-gray-200 dark:border-gray-700 font-bold transition-all shadow-sm active:scale-95">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                📖 Imprimir Libro
                            </button>
                            @if($canEditDocument)
                                <button type="button" @click="$dispatch('open-add-chapter-modal')" class="flex items-center gap-1.5 text-xs bg-violet-600 hover:bg-violet-500 text-white px-4 py-2 rounded-xl font-bold transition-all shadow-lg shadow-violet-500/25 active:scale-95">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Añadir Capítulo
                                </button>
                            @endif
                        </div>
                    </div>

                    @if (empty($chapters))
                        <div class="text-center py-8">
                            <p class="text-xs text-gray-400 italic">No hay capítulos registrados en este documento. Haz clic en "Añadir Capítulo" para comenzar.</p>
                        </div>
                    @else
                        <div class="space-y-6">
                            @foreach ($chapters as $idx => $chapter)
                                <div x-data="{ editing: false }" class="bg-gray-50/40 dark:bg-gray-800/20 border border-gray-100 dark:border-gray-800/60 rounded-2xl p-5 space-y-4">
                                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800/50">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="w-7 h-7 rounded-xl bg-violet-100 dark:bg-violet-950 text-violet-700 dark:text-violet-300 font-black text-xs flex items-center justify-center border border-violet-200 dark:border-violet-800 shadow-sm shrink-0">
                                                {{ $idx + 1 }}
                                            </span>
                                            <div class="min-w-0">
                                                <h4 class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ $chapter['title'] }}</h4>
                                                <p class="text-[10px] text-gray-400">Por {{ $chapter['author_name'] ?? 'Autor' }} • {{ \Carbon\Carbon::parse($chapter['updated_at'])->diffForHumans() }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1 shrink-0">
                                            @if($canEditDocument)
                                                <button type="button" @click="editing = !editing" class="p-1.5 text-gray-400 hover:text-blue-500 rounded-xl hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors" title="Editar capítulo">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                    </svg>
                                                </button>
                                                <form action="{{ route('teams.activities.chapters.destroy', [$team, $activity, $chapter['id']]) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar este capítulo?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-all" title="Eliminar capítulo">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>

                                    <div x-show="!editing" id="chapter-content-{{ $chapter['id'] }}" style="height: 200px; max-height: none; overflow-y: auto;" class="prose dark:prose-invert prose-sm max-w-none text-xs text-gray-700 dark:text-gray-300 leading-relaxed resize-y min-h-[120px] custom-scrollbar pr-4 p-4 bg-white dark:bg-gray-900/50 border border-gray-100 dark:border-gray-800/80 rounded-2xl shadow-sm">
                                        {!! str($chapter['content'])->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                                    </div>

                                    <form x-show="editing" x-cloak action="{{ route('teams.activities.chapters.update', [$team, $activity, $chapter['id']]) }}" method="POST" class="space-y-4 pt-2">
                                        @csrf
                                        @method('PATCH')
                                        <div>
                                            <label class="block text-[10px] uppercase font-bold text-gray-400 mb-1">Título del Capítulo</label>
                                            <input type="text" name="chapter_title" value="{{ $chapter['title'] }}" required class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2 text-xs text-gray-800 dark:text-white outline-none shadow-sm">
                                        </div>
                                        <div style="height: 220px; max-height: none; overflow-y: auto;" class="resize-y min-h-[150px] overflow-y-auto custom-scrollbar border border-gray-100 dark:border-gray-800 rounded-2xl p-2 bg-white dark:bg-gray-900 shadow-sm">
                                            <label class="block text-[10px] uppercase font-bold text-gray-400 mb-1">Contenido (Markdown)</label>
                                            <x-markdown-editor 
                                                name="chapter_content" 
                                                id="edit-chap-{{ $chapter['id'] }}"
                                                :value="$chapter['content']"
                                                :label="null"
                                                rows="4"
                                                placeholder="Contenido del capítulo..."
                                                :upload-url="route('teams.forum.upload_image', $team)"
                                            />
                                        </div>
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button" @click="editing = false" class="bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-xl transition-all active:scale-95">
                                                Cancelar
                                            </button>
                                            <button type="submit" class="bg-violet-600 hover:bg-violet-500 text-white text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-xl shadow-lg shadow-violet-500/20 transition-all active:scale-95">
                                                Guardar Capítulo
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            <!-- Subactividades / Desglose -->
            @if ($activity->children && $activity->children->isNotEmpty())
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm transition-colors">
                    <span class="block text-[10px] uppercase font-black text-gray-400 dark:text-gray-500 tracking-widest mb-3">Desglose / Subactividades</span>
                    <div class="space-y-2 max-h-48 overflow-y-auto custom-scrollbar pr-2">
                        @foreach ($activity->children as $child)
                            <div class="flex items-center justify-between gap-2 p-3 bg-gray-50 dark:bg-gray-800/40 rounded-2xl border border-gray-100 dark:border-gray-800/60">
                                <a href="{{ route('teams.activities.show', [$team, $child]) }}" class="text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline truncate flex-1" title="{{ $child->title }}">
                                    🔹 {{ $child->title }}
                                </a>
                                @php
                                    $childStatus = $child->status_value;
                                    $childStatusColor = match ($childStatus) {
                                        'completed', 'approved', 'published', 'active' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400',
                                        'in_progress', 'review' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-400',
                                        'cancelled', 'rejected', 'broken' => 'bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-400',
                                        'blocked' => 'bg-rose-600 text-white animate-pulse',
                                        default => 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400',
                                    };
                                @endphp
                                <span class="px-2.5 py-1 rounded-xl text-[9px] font-bold shrink-0 {{ $childStatusColor }} border border-current/20">
                                    {{ strtoupper($childStatus) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Miembros y Grupos Asignados -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm transition-colors">
                <div style="max-height: 320px; overflow-y: auto;" class="grid grid-cols-1 md:grid-cols-2 gap-6 max-h-[320px] overflow-y-auto custom-scrollbar pr-2">
                    <div>
                        <span class="block text-[10px] uppercase font-black text-gray-400 dark:text-gray-500 tracking-widest mb-3">Miembros Asignados</span>
                        <div class="space-y-2.5">
                            @forelse ($activity->assignedTo as $user)
                                <div class="flex items-center gap-3 p-2 bg-gray-50 dark:bg-gray-800/30 rounded-2xl border border-gray-100 dark:border-gray-800/50">
                                    <div class="w-8 h-8 rounded-xl bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 flex items-center justify-center font-bold text-xs shadow-sm border border-gray-300 dark:border-gray-600 shrink-0">
                                        {{ substr($user->name, 0, 1) }}
                                    </div>
                                    <span class="text-xs font-bold text-gray-800 dark:text-white truncate">{{ $user->name }}</span>
                                </div>
                            @empty
                                <span class="text-xs text-gray-400 italic block py-2">Ningún miembro asignado.</span>
                            @endforelse
                        </div>
                    </div>

                    <div>
                        <span class="block text-[10px] uppercase font-black text-gray-400 dark:text-gray-500 tracking-widest mb-3">Grupos Asignados</span>
                        <div class="space-y-2.5">
                            @forelse ($activity->assignedGroups as $group)
                                <div class="flex items-center gap-3 p-2 bg-gray-50 dark:bg-gray-800/30 rounded-2xl border border-gray-100 dark:border-gray-800/50">
                                    <div class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-xs shadow-sm border border-blue-200 dark:border-blue-800 shrink-0">
                                        👥
                                    </div>
                                    <span class="text-xs font-bold text-gray-800 dark:text-white truncate">{{ $group->name }}</span>
                                </div>
                            @empty
                                <span class="text-xs text-gray-400 italic block py-2">Ningún grupo asignado.</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección de Notas y Comentarios (NORMA VISUAL DE TAREAS ANTIGUAS) -->
            <div id="notes" class="bg-white dark:bg-gray-900 border border-amber-100 dark:border-amber-900/30 rounded-3xl p-6 shadow-sm mt-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/40 flex items-center justify-center text-amber-600 border border-amber-100 dark:border-amber-800/50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <h3 class="text-xs font-black text-amber-700 dark:text-amber-400 uppercase tracking-widest">
                            {{ __('Notas y Comentarios') }} ({{ $notes->count() }})
                        </h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="printSection('Notas y Comentarios', 'notes-list-container')" class="p-1.5 bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300 rounded-xl transition-all border border-amber-100 dark:border-amber-800/50 shadow-sm flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest" title="Imprimir notas">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            Imprimir
                        </button>
                    </div>
                </div>
                
                <form action="{{ route('teams.activities.notes.store', [$team, $activity]) }}" method="POST" id="activity-notes-form">
                    @csrf
                    <div style="max-height: 400px; overflow-y: auto;" class="max-h-[500px] overflow-y-auto custom-scrollbar">
                        <x-markdown-editor 
                            name="content" 
                            id="reply-content-activity"
                            :value="''"
                            :label="null"
                            rows="4"
                            placeholder="Escribe aquí tus notas o comentarios sobre esta actividad..."
                            :upload-url="route('teams.forum.upload_image', $team)"
                        />
                    </div>
                    <div class="mt-3 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Visibilidad:</span>
                            <select name="visibility" class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl pl-3 pr-10 py-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer outline-none shadow-sm">
                                <option value="internal">👥 Interno (Equipo)</option>
                                <option value="private">🔒 Privado (Solo tú)</option>
                            </select>
                        </div>
                        <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white text-[10px] font-black uppercase tracking-widest px-5 py-2.5 rounded-xl shadow-lg shadow-amber-500/20 transition-all active:scale-95">
                            {{ __('Guardar Nota') }}
                        </button>
                    </div>
                </form>

                <!-- Lista de notas -->
                <div id="notes-list-container" style="max-height: 400px; overflow-y: auto;" class="divide-y divide-gray-100 dark:divide-gray-800 pt-6 mt-6 border-t border-gray-100 dark:border-gray-800 max-h-[400px] overflow-y-auto custom-scrollbar pr-2">
                    @forelse ($notes as $note)
                        <div x-data="{ editing: false }" class="py-4 first:pt-0 last:pb-0 space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center font-bold text-xs text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700">
                                        {{ substr($note->user->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-gray-850 dark:text-white">{{ $note->user->name }}</span>
                                        <span class="text-[10px] text-gray-400 ml-1">{{ $note->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if ($note->visibility === 'private')
                                        <span class="px-2.5 py-1 rounded-xl bg-red-50 text-red-600 dark:bg-red-950/20 dark:text-red-400 text-[10px] font-bold border border-red-100 dark:border-red-900/50 shadow-sm">
                                            🔒 Privado
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-xl bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-400 text-[10px] font-bold border border-gray-100 dark:border-gray-700 shadow-sm">
                                            👥 Interno
                                        </span>
                                    @endif

                                    @if ($note->user_id === auth()->id() || auth()->user()->can('delete', $activity))
                                        <button type="button" @click="editing = !editing" class="p-1.5 text-gray-400 hover:text-blue-500 transition-colors rounded-xl hover:bg-blue-50 dark:hover:bg-blue-900/20" title="Editar nota">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                        <form action="{{ route('teams.activities.notes.destroy', [$team, $activity, $note]) }}" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta nota?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-all" title="Eliminar nota">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            <div x-show="!editing" class="text-xs text-gray-700 dark:text-gray-300 leading-relaxed prose dark:prose-invert prose-sm max-w-none">
                                {!! str($note->content)->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                            </div>
                            
                            <form x-show="editing" x-cloak action="{{ route('teams.activities.notes.update', [$team, $activity, $note]) }}" method="POST" class="space-y-3 pt-2">
                                @csrf
                                @method('PATCH')
                                <div style="max-height: 300px; overflow-y: auto;" class="max-h-[300px] overflow-y-auto custom-scrollbar">
                                    <x-markdown-editor 
                                        name="content" 
                                        id="edit-note-{{ $note->id }}"
                                        :value="$note->content"
                                        :label="null"
                                        rows="3"
                                        placeholder="Edita tu nota o comentario..."
                                        :upload-url="route('teams.forum.upload_image', $team)"
                                    />
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Visibilidad:</span>
                                        <select name="visibility" class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl pl-3 pr-10 py-1 text-xs text-gray-700 dark:text-gray-300 cursor-pointer outline-none shadow-sm">
                                            <option value="internal" {{ $note->visibility === 'internal' ? 'selected' : '' }}>👥 Interno (Equipo)</option>
                                            <option value="private" {{ $note->visibility === 'private' ? 'selected' : '' }}>🔒 Privado (Solo tú)</option>
                                        </select>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="editing = false" class="bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400 text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-xl transition-all active:scale-95">
                                            Cancelar
                                        </button>
                                        <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-xl shadow-lg shadow-amber-500/20 transition-all active:scale-95">
                                            Guardar Cambios
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 italic text-center py-6">Aún no hay notas ni comentarios registrados.</p>
                    @endforelse
                </div>
            </div>

            <!-- Sección de Archivos Adjuntos (NORMA VISUAL DE TAREAS ANTIGUAS) -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm mt-5 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-violet-50 dark:bg-violet-900/20 text-violet-600 dark:text-violet-400 flex items-center justify-center shrink-0 border border-violet-100 dark:border-violet-800/50 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-800 dark:text-white">{{ __('tasks.attachments') }}</h3>
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 font-medium uppercase tracking-wide">{{ __('Máx. :size por archivo', ['size' => ini_get('upload_max_filesize')]) }}</p>
                        </div>
                    </div>

                    <!-- Barra de acciones premium -->
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <!-- Botón: Subir archivo -->
                        <form id="activity-attachment-form" action="{{ route('teams.activities.attachments.upload', [$team, $activity]) }}" method="POST" enctype="multipart/form-data" class="m-0 p-0 inline-block">
                            @csrf
                            <label class="group inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold bg-violet-50 dark:bg-violet-500/10 text-violet-600 dark:text-violet-400 border border-violet-200 dark:border-violet-500/20 hover:bg-violet-600 hover:text-white hover:border-violet-600 dark:hover:bg-violet-500 dark:hover:text-white dark:hover:border-violet-500 shadow-sm hover:shadow-violet-500/25 hover:shadow-md transition-all duration-200 active:scale-95 cursor-pointer mb-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform group-hover:-translate-y-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                {{ __('tasks.add_attachment') }}
                                <input type="file" id="activity-attachment-input" name="attachments[]" multiple onchange="this.form.submit()" class="hidden">
                            </label>
                        </form>

                        <!-- Botón: Nuevo documento OnlyOffice -->
                        @if($canEditActivity)
                        <div x-data="{ open: false }" class="relative" @click.outside="open = false">
                            <button type="button" @click="open = !open" class="group inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold bg-teal-50 dark:bg-teal-500/10 text-teal-600 dark:text-teal-400 border border-teal-200 dark:border-teal-500/20 hover:bg-teal-600 hover:text-white hover:border-teal-600 dark:hover:bg-teal-500 dark:hover:text-white dark:hover:border-teal-500 shadow-sm hover:shadow-teal-500/25 hover:shadow-md transition-all duration-200 active:scale-95">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                {{ __('Nuevo documento') }}
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <form id="create-act-docx-form" method="POST" action="{{ route('onlyoffice.activity.create', [$team, $activity]) }}" target="_blank">
                                @csrf <input type="hidden" name="type" value="docx">
                            </form>
                            <form id="create-act-xlsx-form" method="POST" action="{{ route('onlyoffice.activity.create', [$team, $activity]) }}" target="_blank">
                                @csrf <input type="hidden" name="type" value="xlsx">
                            </form>
                            <form id="create-act-pptx-form" method="POST" action="{{ route('onlyoffice.activity.create', [$team, $activity]) }}" target="_blank">
                                @csrf <input type="hidden" name="type" value="pptx">
                            </form>

                            <div x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95 -translate-y-1" x-transition:enter-end="opacity-100 scale-100 translate-y-0" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" x-cloak class="absolute right-0 top-full mt-2 w-56 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-2xl z-[200] overflow-hidden ring-1 ring-black/5 dark:ring-white/5">
                                <div class="px-3 pt-3 pb-1.5">
                                    <p class="text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Crear con OnlyOffice</p>
                                </div>
                                <button type="button" onclick="sessionStorage.setItem('needs_office_reload', '1'); document.getElementById('create-act-docx-form').submit()" class="w-full flex items-center gap-3 px-3 py-2.5 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors group/item">
                                    <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0 group-hover/item:scale-110 transition-transform">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 7V3.5L18.5 9H13zM9 13h6v1H9v-1zm0 2h6v1H9v-1zm0 2h4v1H9v-1z"/></svg>
                                    </div>
                                    <div class="text-left">
                                        <div class="text-xs font-bold text-gray-800 dark:text-white">Documento de texto</div>
                                        <div class="text-[10px] text-gray-400 font-medium">.docx · Word / Writer</div>
                                    </div>
                                </button>
                                <button type="button" onclick="sessionStorage.setItem('needs_office_reload', '1'); document.getElementById('create-act-xlsx-form').submit()" class="w-full flex items-center gap-3 px-3 py-2.5 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors group/item">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0 group-hover/item:scale-110 transition-transform">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 7V3.5L18.5 9H13zM8 12h2v1H8v-1zm0 2h2v1H8v-1zm0 2h2v1H8v-1zm3-4h5v1h-5v-1zm0 2h5v1h-5v-1zm0 2h5v1h-5v-1z"/></svg>
                                    </div>
                                    <div class="text-left">
                                        <div class="text-xs font-bold text-gray-800 dark:text-white">Hoja de cálculo</div>
                                        <div class="text-[10px] text-gray-400 font-medium">.xlsx · Excel / Calc</div>
                                    </div>
                                </button>
                                <button type="button" onclick="sessionStorage.setItem('needs_office_reload', '1'); document.getElementById('create-act-pptx-form').submit()" class="w-full flex items-center gap-3 px-3 py-2.5 hover:bg-orange-50 dark:hover:bg-orange-900/20 transition-colors group/item">
                                    <div class="w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center text-orange-600 dark:text-orange-400 shrink-0 group-hover/item:scale-110 transition-transform">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 7V3.5L18.5 9H13zm-2 3l-2 3h4l-2-3zm2.5 3.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/></svg>
                                    </div>
                                    <div class="text-left">
                                        <div class="text-xs font-bold text-gray-800 dark:text-white">Presentación</div>
                                        <div class="text-[10px] text-gray-400 font-medium">.pptx · PowerPoint / Impress</div>
                                    </div>
                                </button>
                                <div class="px-3 py-2 border-t border-gray-100 dark:border-gray-800 mt-1">
                                    <p class="text-[9px] text-gray-400 dark:text-gray-500 text-center">Se abre en una nueva pestaña ↗</p>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Botón: Google Drive -->
                        @php 
                            $isTeamLinked = auth()->user()->teams()->where('team_id', $team->id)->wherePivotNotNull('google_token')->exists();
                        @endphp
                        @if($isTeamLinked)
                            <button type="button" @click="$dispatch('open-drive-picker', { id: {{ $activity->id }}, type: 'App\\Models\\Activity' })" class="group inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-500/20 hover:bg-blue-600 hover:text-white hover:border-blue-600 dark:hover:bg-blue-500 dark:hover:text-white dark:hover:border-blue-500 shadow-sm hover:shadow-blue-500/25 hover:shadow-md transition-all duration-200 active:scale-95">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 48 48">
                                    <path fill="currentColor" opacity=".8" d="M17 6H11L2 22l3 5h6l9-16z"/>
                                    <path fill="currentColor" opacity=".5" d="M37 42H11l-9-15 4-7h26l9 16z"/>
                                    <path fill="currentColor" d="M15 6l9 16 9-16H15z"/>
                                </svg>
                                Google Drive
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            </button>
                        @else
                            <a href="{{ route('profile.edit', ['tab' => 'integrations']) }}" class="group inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold bg-gray-50 dark:bg-gray-800 text-gray-400 dark:text-gray-500 border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-gray-300 transition-all duration-200 active:scale-95">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101M10.172 13.828a4 4 0 015.656 0l4 4a4 4 0 01-5.656 5.656l-1.102-1.101"/>
                                </svg>
                                Vincular Drive
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Lista de adjuntos -->
                @if ($activity->attachments->isEmpty())
                    <p class="text-xs text-gray-400 italic">{{ __('tasks.no_attachments') }}</p>
                @else
                    <div style="max-height: 400px; overflow-y: auto;" class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[400px] overflow-y-auto custom-scrollbar pr-2">
                        @foreach ($activity->attachments as $attach)
                            <div class="group flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700/50 rounded-xl hover:border-violet-200 dark:hover:border-violet-800 transition-all">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center shadow-sm border shrink-0 {{ $attach->disk === 'google_drive' ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-100 dark:border-blue-800' : 'bg-white dark:bg-gray-800 text-violet-600 dark:text-violet-400 border-gray-100 dark:border-gray-700' }}">
                                        @if($attach->disk === 'google_drive')
                                            <svg class="w-6 h-6" viewBox="0 0 48 48">
                                                <path fill="#FFC107" d="M17 6H11L2 22l3 5h6l9-16z"/>
                                                <path fill="#2196F3" d="M37 42H11l-9-15 4-7h26l9 16z"/>
                                                <path fill="#4CAF50" d="M15 6l9 16 9-16H15z"/>
                                            </svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[12px] font-bold text-gray-800 dark:text-white truncate" title="{{ $attach->file_name }}">
                                            <a href="{{ route('teams.activities.attachments.download', [$team, $activity, $attach]) }}" target="_blank" class="hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                                {{ $attach->file_name }}
                                            </a>
                                        </p>
                                        <p class="text-[10px] text-gray-400 flex items-center gap-1.5">
                                            @if($attach->disk === 'google_drive')
                                                <span class="bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-1.5 rounded font-black uppercase text-[8px]">Google Drive</span>
                                            @else
                                                {{ $attach->file_size_human ?? number_format($attach->file_size / 1024 / 1024, 2) . ' MB' }}
                                            @endif
                                            • {{ $attach->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-0.5 opacity-60 group-hover:opacity-100 transition-all duration-200">
                                    <!-- Botón de Inyección IA -->
                                    <button type="button" @click="$dispatch('ai:analyze-file', { fileName: '{{ addslashes($attach->file_name) }}', fileId: {{ $attach->id }}, fileUrl: '{{ route('teams.activities.attachments.download', [$team, $activity, $attach]) }}', fileType: '{{ $attach->file_type ?? '' }}', taskId: {{ $activity->id }}, teamId: {{ $team->id }}, autoSubmit: false })" class="p-1.5 text-violet-500 hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-300 transition-colors" title="Preguntar a la IA sobre este archivo">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </button>

                                    <a href="{{ route('teams.activities.attachments.download', [$team, $activity, $attach]) }}" target="_blank" rel="noopener noreferrer" class="p-1.5 text-gray-500 hover:text-violet-600 dark:hover:text-violet-400 transition-colors" title="{{ __('tasks.view_or_download') ?? 'Ver o descargar' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>

                                    @can('update', $activity)
                                        <form action="{{ route('teams.activities.attachments.destroy', [$team, $activity, $attach]) }}" method="POST" class="inline" id="delete-activity-attachment-{{ $attach->id }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all" title="{{ __('tasks.delete') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>

        <!-- BARRA LATERAL (Estado, Prioridad, Fechas, Asignados, Subtipo y Pestañas) -->
        <div class="space-y-4">

            <!-- Quality Rating Widget -->
            @php
                $canRate = $activity->assignedTo()->where('users.id', auth()->id())->exists() || $activity->assigned_user_id === auth()->id() || $team->isManager(auth()->user());
                $ratings = $activity->ratings()->with('user')->get();
                $userRating = $ratings->where('user_id', auth()->id())->first();
                $currentVal = $userRating ? $userRating->score : 0;
                $ratingsCount = $ratings->count();
            @endphp

            @if($canRate || $activity->avg_quality_score > 0)
            <div x-data="{ showRatingsModal: false }" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm transition-all hover:shadow-md relative overflow-hidden group/rating">
                <div class="absolute top-0 right-0 w-24 h-24 bg-amber-500/5 dark:bg-amber-400/5 rounded-full -mr-10 -mt-10 blur-2xl transition-all group-hover/rating:scale-150 duration-700 pointer-events-none"></div>
                
                <div class="flex items-center justify-between mb-4 relative cursor-pointer" @click="showRatingsModal = true">
                    <div>
                        <h3 class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-0.5 flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-amber-500" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                            </svg>
                            {{ __('Calidad de Gestión') }}
                        </h3>
                        <p class="text-xs text-gray-500 font-medium">{{ __('¿Es relevante y clara?') }}</p>
                    </div>
                    <div class="text-right">
                        <div class="flex items-baseline justify-end gap-1">
                            <span class="text-lg font-black text-gray-900 dark:text-white leading-none" id="avg-rating-display">{{ $activity->avg_quality_score > 0 ? number_format($activity->avg_quality_score, 1) : '0.0' }}</span>
                            <span class="text-[10px] text-gray-400 font-bold">/ 5</span>
                        </div>
                        <span class="text-[9px] text-amber-500 dark:text-amber-400 font-bold hover:underline">
                            {{ trans_choice('{0} Sin votos|{1} 1 voto|[2,*] :count votos', $ratingsCount, ['count' => $ratingsCount]) }}
                        </span>
                    </div>
                </div>

                @if($canRate)
                <div x-data="{ 
                    rating: {{ $currentVal }}, 
                    hover: 0,
                    submitting: false,
                    async submitRating(val) {
                        if(this.submitting) return;
                        this.rating = val;
                        this.submitting = true;
                        try {
                            const res = await fetch('{{ route('teams.tasks.rate', [$team, $activity]) }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({ score: val })
                            });
                            const data = await res.json();
                            if(data.success) {
                                const el = document.getElementById('avg-rating-display');
                                if(el) el.innerText = parseFloat(data.avg_score).toFixed(1);
                                if(window.toastr) window.toastr.success(data.message);
                                setTimeout(() => window.location.reload(), 800);
                            } else {
                                if(window.toastr) window.toastr.warning(data.message);
                            }
                        } catch(e) {
                            if(window.toastr) window.toastr.error('Error de red al guardar valoración');
                        } finally {
                            this.submitting = false;
                        }
                    }
                }" class="flex items-center justify-center gap-2 py-2.5 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-700/50 relative z-10">
                    <template x-for="i in [1,2,3,4,5]">
                        <button type="button" 
                            @mouseenter="hover = i" 
                            @mouseleave="hover = 0"
                            @click="submitRating(i)"
                            class="focus:outline-none transition-all transform hover:scale-125 active:scale-95"
                            :class="submitting ? 'opacity-50 cursor-wait' : 'cursor-pointer'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 transition-colors duration-150" 
                                :class="(hover || rating) >= i ? 'text-amber-400 fill-current' : 'text-gray-300 dark:text-gray-600 fill-none stroke-current stroke-2'"
                                viewBox="0 0 24 24">
                                <path stroke-linejoin="round" stroke-linecap="round" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                            </svg>
                        </button>
                    </template>
                </div>
                @else
                <div class="flex items-center gap-1 justify-center py-2 opacity-70">
                    @for($i = 1; $i <= 5; $i++)
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 {{ $i <= round($activity->avg_quality_score) ? 'text-amber-400 fill-current' : 'text-gray-300 dark:text-gray-600 fill-current' }}" viewBox="0 0 24 24">
                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                        </svg>
                    @endfor
                </div>
                @endif

                <!-- Modal de Desglose de Votos -->
                <div x-show="showRatingsModal" 
                    class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    x-cloak
                    @click.self="showRatingsModal = false">
                    
                    <div class="bg-white dark:bg-gray-900 rounded-3xl w-full max-w-md overflow-hidden shadow-2xl border border-gray-100 dark:border-gray-800 transform transition-all text-left"
                        x-transition:enter="transition ease-out duration-300 transform"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-200 transform"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95">
                        
                        <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-500 fill-current" viewBox="0 0 24 24">
                                        <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                    </svg>
                                    {{ __('Desglose de Calidad') }}
                                </h3>
                                <p class="text-[11px] text-gray-500 font-medium">{{ __('Votos de los miembros de este equipo') }}</p>
                            </div>
                            <button @click="showRatingsModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div style="max-height: 350px; overflow-y: auto;" class="p-6 max-h-[350px] overflow-y-auto space-y-4">
                            @forelse($ratings as $rating)
                                <div class="flex items-center justify-between p-3.5 bg-gray-50 dark:bg-gray-800/40 rounded-2xl border border-gray-100/50 dark:border-gray-800/50">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $rating->user->profile_photo_url }}" 
                                            alt="{{ $rating->user->name }}" 
                                            class="w-9 h-9 rounded-full object-cover ring-2 ring-amber-500/10 shrink-0">
                                        <div>
                                            <div class="text-xs font-bold text-gray-800 dark:text-gray-200">
                                                {{ $rating->user->name }}
                                            </div>
                                            <div class="text-[10px] text-gray-400 font-semibold">
                                                {{ $rating->created_at->diffForHumans() }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-0.5 shrink-0 bg-white dark:bg-gray-900 px-3 py-1.5 rounded-xl border border-gray-100 dark:border-gray-800 shadow-sm">
                                        @for($i = 1; $i <= 5; $i++)
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 {{ $i <= $rating->score ? 'text-amber-400 fill-current' : 'text-gray-200 dark:text-gray-700 fill-current' }}" viewBox="0 0 24 24">
                                                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                            </svg>
                                        @endfor
                                        <span class="text-[11px] font-black text-gray-700 dark:text-gray-300 ml-1.5 leading-none">{{ number_format($rating->score, 1) }}</span>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-8">
                                    <div class="w-12 h-12 bg-amber-50 dark:bg-amber-950/20 rounded-full flex items-center justify-center mx-auto mb-3 text-amber-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.907c.961 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.373-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                        </svg>
                                    </div>
                                    <p class="text-xs text-gray-400 font-medium">{{ __('Aún no hay valoraciones para esta actividad.') }}</p>
                                </div>
                            @endforelse
                        </div>

                        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/40 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-xs text-gray-500 font-medium">
                            <span>{{ __('Puntuación media:') }}</span>
                            <div class="flex items-center gap-1.5">
                                <span class="text-sm font-black text-gray-900 dark:text-white">{{ $activity->avg_quality_score > 0 ? number_format($activity->avg_quality_score, 1) : '0.0' }}</span>
                                <span class="text-gray-400">/ 5</span>
                                <div class="flex items-center gap-0.5 ml-1">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 {{ $i <= round($activity->avg_quality_score) ? 'text-amber-400 fill-current' : 'text-gray-200 dark:text-gray-700 fill-current' }}" viewBox="0 0 24 24">
                                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                        </svg>
                                    @endfor
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- TIEMPO DEDICADO Card -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm transition-colors">
                <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-widest font-black mb-4">{{ __('TIEMPO DEDICADO') }}</p>
                <div class="flex items-center justify-between">
                    <div x-data="{ 
                        active: {{ auth()->user()->isTrackingTask($activity->id) ? 'true' : 'false' }},
                        seconds: {{ auth()->user()->getTaskTrackingSeconds($activity->id) }},
                        totalToday: '{{ method_exists($activity, 'totalTrackedTimeTodayHuman') ? $activity->totalTrackedTimeTodayHuman() : $activity->totalTrackedTimeHuman() }}',
                        
                        get formatted() {
                            const h = Math.floor(this.seconds / 3600);
                            const m = Math.floor((this.seconds % 3600) / 60);
                            const s = this.seconds % 60;
                            return [h,m,s].map(v => v.toString().padStart(2, '0')).join(':');
                        },
                        init() {
                            if (this.active) {
                                setInterval(() => { this.seconds++ }, 1000);
                            }
                        }
                    }" class="flex-1">
                        <div class="text-3xl font-black text-gray-900 dark:text-white tabular-nums tracking-tight mb-0.5" x-text="formatted">00:00:00</div>
                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wide">
                            Total hoy: <span class="text-gray-600 dark:text-gray-300" x-text="totalToday">0m</span>
                        </div>
                    </div>
                    
                    <div class="shrink-0">
                        @include('tasks.partials.task-timer-button', ['task' => $activity])
                    </div>
                </div>
            </div>

            <!-- Propietario Card (IDÉNTICO A TAREAS ANTIGUAS) -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm dark:shadow-none">
                <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-widest font-bold mb-3">
                    {{ __('tasks.owner') }}
                </p>
                <div class="flex items-center gap-3">
                    <img src="{{ $activity->creator ? $activity->creator->profile_photo_url : 'https://ui-avatars.com/api/?name=?&color=7F9CF5&background=EBF4FF' }}" 
                        alt="{{ $activity->creator?->name ?? '?' }}"
                        class="w-10 h-10 rounded-xl object-cover shadow-sm border border-gray-100 dark:border-gray-800 shrink-0">
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-gray-700 dark:text-gray-300 truncate">{{ $activity->creator?->name ?? '—' }}</p>
                        <p class="text-[10px] text-gray-500 dark:text-gray-600 uppercase font-black tracking-tighter">{{ $activity->created_at->format('d M Y') }}</p>
                    </div>
                </div>
            </div>

            <!-- Bloque de Estado Card (IDÉNTICO A TAREAS ANTIGUAS) -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 space-y-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide font-bold">{{ __('tasks.status') }}</span>
                    <span class="text-[11px] font-bold px-3 py-1 rounded-full border {{ $statusColor }} uppercase tracking-wider shadow-sm">
                        {{ $statusTranslations[$currentStatus] ?? ucfirst($currentStatus) }}
                    </span>
                </div>

                <!-- Nivel de Privacidad / Visibilidad -->
                <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-800">
                    <span class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wide">{{ __('tasks.visibility') }}</span>
                    <div class="flex items-center gap-1.5">
                        @if($activity->visibility === 'private')
                            <div class="w-2 h-2 rounded-full bg-amber-500"></div>
                            <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">{{ __('tasks.private') }}</span>
                        @elseif($activity->visibility === 'semi-private')
                            <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
                            <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">{{ __('Semiprivada') }}</span>
                        @else
                            <div class="w-2 h-2 rounded-full bg-violet-500"></div>
                            <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">{{ __('tasks.public') }}</span>
                        @endif
                    </div>
                </div>

                @can('changeStatus', $activity)
                    <div class="border-t border-gray-100 dark:border-gray-800 pt-3">
                        <label class="block text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-wider mb-2">Actualizar Estado:</label>
                        <form action="{{ route('teams.activities.change-status', [$team, $activity]) }}" method="POST" class="flex gap-2">
                            @csrf
                            @method('PATCH')
                            <select name="status" class="flex-1 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-xs text-gray-800 dark:text-white outline-none cursor-pointer">
                                @foreach ($validStatuses as $st)
                                    <option value="{{ $st }}" {{ $currentStatus === $st ? 'selected' : '' }}>
                                        {{ $statusTranslations[$st] ?? ucfirst($st) }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="bg-violet-600 hover:bg-violet-500 text-white text-xs font-bold px-4 py-2 rounded-xl transition-all shadow">
                                Cambiar
                            </button>
                        </form>
                    </div>
                @endcan
            </div>

            <!-- Prioridad y Fechas Card (IDÉNTICO A TAREAS ANTIGUAS) -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 space-y-3 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wide">{{ __('tasks.priority') }}</span>
                    <span class="text-xs font-semibold text-gray-800 dark:text-gray-200 capitalize">
                        @switch($activity->priority)
                            @case('low') 🟢 Baja @break
                            @case('medium') 🟡 Media @break
                            @case('high') 🟠 Alta @break
                            @case('critical') 🔴 Crítica @break
                            @default {{ $activity->priority }}
                        @endswitch
                    </span>
                </div>

                @if ($activity->scheduled_date)
                    <div class="flex items-center justify-between pt-2 border-t border-gray-50 dark:border-gray-800/50">
                        <span class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wide">{{ __('tasks.scheduled_date') ?? 'Fecha Programada' }}</span>
                        <span class="text-[11px] text-gray-700 dark:text-gray-300 font-bold tabular-nums">{{ $activity->scheduled_date->format('d M Y, H:i') }}</span>
                    </div>
                @endif
                @if ($activity->due_date)
                    <div class="flex items-center justify-between pt-1">
                        <span class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wide">{{ __('tasks.due_date') }}</span>
                        <span class="text-[11px] text-gray-700 dark:text-gray-300 font-bold tabular-nums {{ $activity->due_date->isPast() && !$activity->isCompleted() ? 'text-red-500 font-black' : '' }}">{{ $activity->due_date->format('d M Y, H:i') }}</span>
                    </div>
                @endif
            </div>

            <!-- Expediente Card (IDÉNTICO A TAREAS ANTIGUAS) -->
            @if ($activity->expediente)
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm relative overflow-hidden">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 rounded-lg bg-violet-50 dark:bg-violet-900/20 text-violet-600 dark:text-violet-400 flex items-center justify-center shrink-0 border border-violet-100 dark:border-violet-500/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('Expediente') }}</h4>
                            <p class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ $activity->expediente->code }}</p>
                        </div>
                    </div>
                    <a href="{{ route('teams.expedientes.show', [$team, $activity->expediente]) }}" class="w-full flex items-center justify-center gap-2 text-[10px] font-black uppercase tracking-widest text-violet-700 dark:text-violet-300 hover:bg-violet-50 dark:hover:bg-violet-900/30 py-2 border border-violet-100 dark:border-violet-800/50 rounded-xl transition-all shadow-sm">
                        {{ __('Ver Expediente') }}
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            @endif

            <!-- Padre/Dependencia Card -->
            @if ($activity->parent)
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm relative overflow-hidden">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0 border border-indigo-100 dark:border-indigo-500/10">
                            🔗
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('Actividad Padre') }}</h4>
                            <p class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ $activity->parent->title }}</p>
                        </div>
                    </div>
                    <a href="{{ route('teams.activities.show', [$team, $activity->parent]) }}" class="w-full flex items-center justify-center gap-2 text-[10px] font-black uppercase tracking-widest text-indigo-700 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 py-2 border border-indigo-100 dark:border-indigo-800/50 rounded-xl transition-all shadow-sm">
                        {{ __('Ver Actividad Padre') }}
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            @endif

            <!-- Quota de disco Card -->
            @php
                $attachCount = $activity->attachments->count();
                $attachSize = $activity->attachments->sum('file_size');
                $sizeHuman = number_format($attachSize / 1024 / 1024, 2) . ' MB';
            @endphp
            @if($attachCount > 0)
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm space-y-3">
                    <div class="flex items-center justify-between text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">
                        <span>{{ __('tasks.disk_quota') }}</span>
                        <span>{{ $sizeHuman }}</span>
                    </div>
                    <div class="w-full h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                        <div class="h-full bg-violet-500 rounded-full shadow-sm" style="width: {{ min(100, max(5, ($attachSize / (50 * 1024 * 1024)) * 100)) }}%"></div>
                    </div>
                    <p class="text-[10px] text-gray-400 font-medium">
                        {{ trans_choice('{1} 1 archivo adjunto|[2,*] :count archivos adjuntos', $attachCount, ['count' => $attachCount]) }} en esta actividad.
                    </p>
                </div>
            @endif

            <!-- Etiquetas / Skills (IDÉNTICO A TAREAS ANTIGUAS) -->
            @if($activity->skill || ($activity->tags && $activity->tags->isNotEmpty()))
                <div class="flex flex-wrap gap-2 mb-4">
                    @if($activity->skill)
                        <div class="group inline-flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-gray-900 border border-amber-100 dark:border-amber-900/40 rounded-xl shadow-sm hover:shadow-md hover:border-amber-300 dark:hover:border-amber-700 transition-all duration-300 cursor-default">
                            <div class="w-1.5 h-1.5 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 shadow-sm shadow-amber-500/20 group-hover:scale-125 transition-transform"></div>
                            <div class="flex flex-col min-w-0">
                                <span class="text-[9px] font-black text-amber-700 dark:text-amber-400 uppercase tracking-widest truncate leading-tight">{{ $activity->skill->name }}</span>
                                <span class="text-[7px] text-amber-600/40 dark:text-amber-500/20 font-bold uppercase tracking-tighter truncate leading-none">{{ __('tasks.skill') }}</span>
                            </div>
                        </div>
                    @endif
                    @if($activity->tags)
                        @foreach($activity->tags as $tag)
                            <div class="group inline-flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm hover:shadow-md hover:border-gray-300 dark:hover:border-gray-700 transition-all duration-300 cursor-default">
                                <div class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-600 shadow-sm group-hover:scale-125 transition-transform"></div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-[9px] font-black text-gray-700 dark:text-gray-300 uppercase tracking-widest truncate leading-tight">{{ $tag->name }}</span>
                                    <span class="text-[7px] text-gray-400 font-bold uppercase tracking-tighter truncate leading-none">{{ __('tasks.tag') ?? 'ETIQUETA' }}</span>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            @endif

            <!-- Bloque Específico de Metadatos del Subtipo -->
            <div class="bg-violet-50/30 dark:bg-violet-950/10 border border-violet-100 dark:border-violet-900/40 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center gap-2 mb-4">
                    <span class="text-lg">✨</span>
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200">Campos y Configuración del Subtipo</h3>
                </div>

                <div style="max-height: 300px; overflow-y: auto;" class="max-h-[300px] overflow-y-auto custom-scrollbar pr-2">
                    @switch($activity->type)
                        @case('task')
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="bg-white dark:bg-gray-900 p-4 rounded-2xl border border-gray-150 dark:border-gray-850 shadow-sm">
                                    <span class="block text-[10px] uppercase font-black text-gray-400 tracking-widest mb-1">Urgencia</span>
                                    <span class="text-sm font-bold text-gray-850 dark:text-white capitalize">
                                        @switch($activity->metadata['urgency'] ?? 'medium')
                                            @case('low') 🟢 Baja @break
                                            @case('medium') 🟡 Media @break
                                            @case('high') 🟠 Alta @break
                                            @case('critical') 🔴 Crítica @break
                                            @default {{ $activity->metadata['urgency'] ?? 'Media' }}
                                        @endswitch
                                    </span>
                                </div>
                                <div class="bg-white dark:bg-gray-900 p-4 rounded-2xl border border-gray-150 dark:border-gray-850 shadow-sm">
                                    <span class="block text-[10px] uppercase font-black text-gray-400 tracking-widest mb-1">Carga Cognitiva</span>
                                    <span class="text-sm font-bold text-gray-850 dark:text-white">
                                        🧠 {{ $activity->metadata['cognitive_load'] ?? 1 }} / 10
                                    </span>
                                </div>
                                <div class="bg-white dark:bg-gray-900 p-4 rounded-2xl border border-gray-150 dark:border-gray-850 sm:col-span-2 flex items-center justify-between shadow-sm">
                                    <div>
                                        <span class="block text-[10px] uppercase font-black text-gray-400 tracking-widest mb-0.5">Habilidades requeridas</span>
                                        <span class="text-xs text-gray-500">¿Fuera del Skill Tree de formación principal?</span>
                                    </div>
                                    <span class="px-3 py-1 rounded-xl text-xs font-bold shadow-sm {{ ($activity->metadata['is_out_of_skill_tree'] ?? false) ? 'bg-red-50 text-red-600 border border-red-200' : 'bg-green-50 text-green-600 border border-green-200' }}">
                                        {{ ($activity->metadata['is_out_of_skill_tree'] ?? false) ? 'Sí (Especial/Externo)' : 'No' }}
                                    </span>
                                </div>
                            </div>
                            @break

                        @case('document')
                            <div class="space-y-4">
                                <p class="text-xs text-gray-500">Este documento puede editarse de manera conjunta por el equipo.</p>
                                <div class="flex items-center justify-between bg-white dark:bg-gray-900 p-4 rounded-2xl border border-gray-150 dark:border-gray-850 shadow-sm">
                                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Versión actual</span>
                                    <span class="px-3 py-1 bg-violet-100 dark:bg-violet-900/50 text-violet-750 dark:text-violet-300 rounded-xl text-xs font-bold shadow-sm">
                                        v{{ $activity->metadata['version'] ?? '1.0.0' }}
                                    </span>
                                </div>
                            </div>
                            @break

                        @case('link')
                            <div class="space-y-3">
                                <span class="block text-[10px] uppercase font-black text-gray-400 tracking-widest mb-1">Dirección URL</span>
                                <a href="{{ $activity->metadata['url'] ?? '#' }}" target="_blank" class="flex items-center gap-2 text-violet-650 dark:text-violet-400 hover:underline font-bold break-all text-sm">
                                    🔗 {{ $activity->metadata['url'] ?? 'No especificado' }}
                                </a>
                                @if (isset($activity->metadata['og_title']))
                                    <div class="mt-4 p-4 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm">
                                        <p class="text-xs font-bold text-gray-850 dark:text-white mb-1">{{ $activity->metadata['og_title'] }}</p>
                                        <p class="text-xs text-gray-500 leading-normal">{{ $activity->metadata['og_description'] ?? '' }}</p>
                                    </div>
                                @endif
                            </div>
                            @break

                        @case('meeting')
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="bg-white dark:bg-gray-900 p-4 rounded-2xl border border-gray-150 dark:border-gray-850 shadow-sm">
                                    <span class="block text-[10px] uppercase font-black text-gray-400 tracking-widest mb-1">Modalidad</span>
                                    <span class="text-sm font-bold text-gray-800 dark:text-white capitalize">
                                        @switch($activity->metadata['modality'] ?? 'remote')
                                            @case('presential') 🏢 Presencial @break
                                            @case('remote') 💻 En Remoto @break
                                            @case('hybrid') 🤝 Híbrido @break
                                            @default {{ $activity->metadata['modality'] ?? 'Remoto' }}
                                        @endswitch
                                    </span>
                                </div>
                                <div class="bg-white dark:bg-gray-900 p-4 rounded-2xl border border-gray-150 dark:border-gray-850 shadow-sm">
                                    <span class="block text-[10px] uppercase font-black text-gray-400 tracking-widest mb-1">Duración</span>
                                    <span class="text-sm font-bold text-gray-800 dark:text-white">
                                        ⏱️ {{ $activity->metadata['duration_minutes'] ?? 60 }} Minutos
                                    </span>
                                </div>
                                @if (isset($activity->metadata['location']))
                                    <div class="bg-white dark:bg-gray-900 p-4 rounded-2xl border border-gray-150 dark:border-gray-850 sm:col-span-2 shadow-sm">
                                        <span class="block text-[10px] uppercase font-black text-gray-400 tracking-widest mb-1">Lugar / Enlace</span>
                                        @if (filter_var($activity->metadata['location'], FILTER_VALIDATE_URL))
                                            <a href="{{ $activity->metadata['location'] }}" target="_blank" class="text-xs text-violet-600 dark:text-violet-400 hover:underline font-bold break-all">
                                                {{ $activity->metadata['location'] }}
                                            </a>
                                        @else
                                            <span class="text-xs text-gray-700 dark:text-gray-300 font-semibold">{{ $activity->metadata['location'] }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            @break

                        @case('reminder')
                            <div class="space-y-4">
                                <span class="block text-[10px] uppercase font-black text-gray-400 tracking-widest mb-2">Vías de Notificación</span>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($activity->metadata['channels'] ?? ['email', 'push'] as $ch)
                                        <span class="px-3 py-1.5 bg-white dark:bg-gray-900 border border-gray-150 dark:border-gray-850 text-xs font-bold rounded-xl text-gray-700 dark:text-gray-300 shadow-sm">
                                            @if($ch === 'email') 📧 Email @elseif($ch === 'push') 🔔 Notificación App @else {{ ucfirst($ch) }} @endif
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                            @break

                        @default
                            <p class="text-xs text-gray-500 italic">No hay campos específicos para este tipo de actividad.</p>
                    @endswitch
                </div>
            </div>

            <!-- Historial de cambios como Timeline (IDÉNTICO A TAREAS ANTIGUAS) -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl overflow-hidden shadow-sm">
                <div class="bg-gray-50/50 dark:bg-gray-800/50 px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-widest flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ __('tasks.activity_history') ?? 'Historial de Actividad' }}
                    </h3>
                </div>
                <div class="p-6 custom-scrollbar" style="max-height: 280px; overflow-y: auto;">
                    <div class="relative ml-4 border-l-2 border-gray-100 dark:border-gray-800 pl-8 space-y-8">
                        @forelse ($histories as $history)
                            <div class="relative group">
                                <!-- Dot -->
                                <div class="absolute -left-[41px] top-1 w-5 h-5 rounded-full border-4 border-white dark:border-gray-900 bg-violet-500 shadow-sm ring-4 ring-violet-50 dark:ring-violet-900/20 group-hover:scale-125 transition-transform"></div>
                                
                                <div class="bg-gray-50/50 dark:bg-gray-800/30 rounded-2xl p-4 border border-transparent group-hover:border-violet-100 dark:group-hover:border-violet-900/30 transition-all">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-tight">{{ $history->user->name }}</span>
                                            <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-lg bg-violet-100 dark:bg-violet-900/60 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-800/50 shadow-sm">
                                                ACTUALIZACIÓN
                                            </span>
                                        </div>
                                        <span class="text-[10px] text-gray-400 font-bold tabular-nums">{{ $history->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="text-xs text-gray-600 dark:text-gray-300 mt-2 font-medium">{{ $history->action }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400 italic py-4 text-center">No hay registros en el historial.</p>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- Script de Confirmación de Borrado y Utilidades de Impresión/Clonación -->
    <script>
        function confirmDeleteActivity() {
            Swal.fire({
                title: '¿Eliminar esta actividad?',
                text: 'Esta acción es irreversible y enviará la actividad a la papelera.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#f1f5f9' : '#1e293b',
                customClass: {
                    popup: 'rounded-[2rem]',
                    confirmButton: 'rounded-xl font-black uppercase text-xs tracking-widest',
                    cancelButton: 'rounded-xl font-black uppercase text-xs tracking-widest'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-activity-form').submit();
                }
            });
        }

        function confirmCloneActivity(formId) {
            Swal.fire({
                title: '¿Clonar actividad?',
                text: 'Se creará una copia exacta de esta actividad en este mismo equipo.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#7c3aed',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, clonar',
                cancelButtonText: 'Cancelar',
                background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#f1f5f9' : '#1e293b',
                customClass: {
                    popup: 'rounded-[2rem]',
                    confirmButton: 'rounded-xl font-black uppercase text-xs tracking-widest',
                    cancelButton: 'rounded-xl font-black uppercase text-xs tracking-widest'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(formId).submit();
                }
            });
        }

        function printSection(sectionLabel, contentId) {
            const content = document.getElementById(contentId)?.innerHTML ?? '';
            const activityTitle = @json($activity->title);
            SientiaPrint.print(activityTitle, content, { brand: 'Sientia MTX • ' + sectionLabel });
        }

        async function printFullActivity() {
            const isDark = document.documentElement.classList.contains('dark');
            
            const result = await Swal.fire({
                title: '<span class="text-xs font-black uppercase tracking-widest text-indigo-600">Imprimir Ficha Técnica</span>',
                background: isDark ? '#0f172a' : '#ffffff',
                color: isDark ? '#f3f4f6' : '#1f2937',
                showConfirmButton: false,
                showCloseButton: true,
                customClass: {
                    popup: 'rounded-[2.5rem] shadow-2xl border border-gray-200 dark:border-gray-800 p-6',
                },
                html: `
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-6 text-center px-4">
                        ¿Deseas imprimir la ficha técnica completa incluyendo el membrete e identidad de Sientia MTX?
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-2">
                        <button type="button" id="print-full-btn-with" class="flex flex-col items-center gap-3 p-5 rounded-[2rem] border-2 border-indigo-100 dark:border-indigo-950 bg-indigo-50/50 dark:bg-indigo-950/30 hover:border-indigo-600 transition-all text-center group">
                            <div class="w-12 h-12 rounded-2xl bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 group-hover:scale-110 transition-transform shadow-sm">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div class="font-black text-[10px] uppercase tracking-widest text-indigo-700 dark:text-indigo-300">Con Cabeceras</div>
                            <div class="text-[9px] font-bold text-gray-400 uppercase tracking-tight">Estilo oficial</div>
                        </button>
                        <button type="button" id="print-full-btn-without" class="flex flex-col items-center gap-3 p-5 rounded-[2rem] border-2 border-gray-100 dark:border-gray-800 bg-white dark:bg-slate-900 hover:border-gray-600 transition-all text-center group">
                            <div class="w-12 h-12 rounded-2xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-600 dark:text-gray-400 group-hover:scale-110 transition-transform shadow-sm">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"/></svg>
                            </div>
                            <div class="font-black text-[10px] uppercase tracking-widest text-gray-700 dark:text-gray-300">Sin Cabeceras</div>
                            <div class="text-[9px] font-bold text-gray-400 uppercase tracking-tight">Ficha limpia</div>
                        </button>
                    </div>
                `,
                didOpen: (el) => {
                    el.querySelector('#print-full-btn-with').onclick = () => Swal.close({ value: 'with' });
                    el.querySelector('#print-full-btn-without').onclick = () => Swal.close({ value: 'without' });
                }
            });

            if (!result || !result.value) return;
            const withHeaders = result.value === 'with';

            const activityTitle = @json($activity->title);
            const activityId = @json($activity->id);
            const status = @json($statusTranslations[$currentStatus] ?? ucfirst($currentStatus));
            const progress = @json($activity->progress_percentage ?? 0);
            const priorityLabel = @json(__('tasks.priorities.' . ($activity->priority ?? 'medium')));
            const scheduled = @json($activity->scheduled_date?->format('d/m/y H:i') ?? '—');
            const due = @json($activity->due_date?->format('d/m/y H:i') ?? '—');
            const teamName = @json($team->name);
            const creator = @json($activity->creator?->name ?? '—');
            
            const description = document.getElementById('description-content')?.innerHTML ?? '—';
            
            const members = @json($activity->assignedTo->pluck('name')->toArray());
            const skills = @json($activity->skills->map(fn($s) => $s->name)->toArray());

            const printWin = window.open('', '_blank', 'width=950,height=1100');
            printWin.document.write(`
                <!DOCTYPE html>
                <html lang="es">
                    <head>
                        <meta charset="UTF-8">
                        <title>Ficha Técnica - ${activityTitle}</title>
                        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
                        <style>
                            ${!withHeaders ? '.header, .side-accent { display: none !important; }' : ''}
                            @page { size: A4; margin: 0; }
                            body { 
                                font-family: 'Outfit', sans-serif; 
                                color: #1e293b; 
                                line-height: 1.2; 
                                margin: 0; padding: 0; background: #fff; 
                                -webkit-print-color-adjust: exact; print-color-adjust: exact;
                            }
                            
                            .sheet {
                                position: relative;
                                max-width: 210mm;
                                width: 100%;
                                margin: 0 auto;
                                padding: 18mm 22mm;
                                box-sizing: border-box;
                                page-break-after: avoid;
                            }

                            .side-accent {
                                position: absolute;
                                top: 100px; left: 0; height: 60%; width: 3px;
                                background: linear-gradient(to bottom, #ef4444, #f87171);
                                border-radius: 0 3px 3px 0;
                                opacity: 0.5;
                            }

                            .header {
                                display: flex; justify-content: space-between; align-items: flex-end;
                                margin-bottom: 12px; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;
                            }

                            .logo-text { 
                                font-family: 'Outfit', sans-serif; 
                                font-weight: 900; 
                                font-size: 22px; 
                                color: #0f172a;
                                letter-spacing: -0.04em;
                                line-height: 1;
                            }

                            .brand-tag { font-size: 9px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.1em; }
                            .doc-meta { text-align: right; font-size: 11px; color: #64748b; font-weight: 600; }

                            .title-area { margin-top: 20px; margin-bottom: 24px; }
                            .title-label { font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.15em; margin-bottom: 6px; }
                            h1 { font-size: 28px; font-weight: 900; color: #0f172a; margin: 0; line-height: 1.1; letter-spacing: -0.02em; }

                            .status-badge {
                                display: inline-block; padding: 4px 10px; border-radius: 100px; font-size: 11px; font-weight: 800; text-transform: uppercase;
                                background: #f1f5f9; color: #334155; margin-top: 10px; letter-spacing: 0.05em;
                            }

                            .meta-grid {
                                display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; 
                                margin-top: 24px; margin-bottom: 28px; padding: 16px; background: #f8fafc; border-radius: 16px;
                                border: 1px solid #f1f5f9;
                            }

                            .meta-item { display: flex; flex-direction: column; gap: 4px; }
                            .meta-label { font-size: 9px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.1em; }
                            .meta-val { font-size: 13px; font-weight: 700; color: #0f172a; }

                            .section-card {
                                border: 1px solid #e2e8f0; border-radius: 20px; padding: 22px; margin-bottom: 24px; background: #fff;
                            }
                            .section-title { font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.15em; margin: 0 0 16px 0; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }
                            
                            .content-body { font-size: 13px; line-height: 1.6; color: #334155; }
                            .content-body h1, .content-body h2, .content-body h3 { color: #0f172a; margin-top: 1em; margin-bottom: 0.5em; font-weight: 800; }
                            .content-body p { margin-top: 0; margin-bottom: 1em; }
                            .content-body ul, .content-body ol { margin-top: 0; padding-left: 20px; }

                            .tag-pills { display: flex; flex-wrap: wrap; gap: 6px; }
                            .pill { font-size: 11px; font-weight: 600; background: #f1f5f9; color: #475569; padding: 3px 10px; border-radius: 8px; }

                            .footer { margin-top: 40px; border-top: 1px solid #f1f5f9; padding-top: 12px; display: flex; justify-content: space-between; font-size: 10px; color: #94a3b8; font-weight: 600; }
                        </style>
                    </head>
                    <body>
                        <div class="sheet">
                            <div class="side-accent"></div>
                            
                            <div class="header">
                                <div>
                                    <div class="logo-text">Sientia MTX</div>
                                    <div class="brand-tag">Ecosistema Integrado de Gestión</div>
                                </div>
                                <div class="doc-meta">
                                    <div>Ficha Técnica de Actividad</div>
                                    <div>Equipo: ${teamName}</div>
                                    <div>ID: #${activityId}</div>
                                </div>
                            </div>

                            <div class="title-area">
                                <div class="title-label">Nombre de la Actividad</div>
                                <h1>${activityTitle}</h1>
                                <div class="status-badge">${status} • ${progress}% Completado</div>
                            </div>

                            <div class="meta-grid">
                                <div class="meta-item">
                                    <span class="meta-label">Prioridad</span>
                                    <span class="meta-val">${priorityLabel}</span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Programación</span>
                                    <span class="meta-val">${scheduled}</span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Vencimiento</span>
                                    <span class="meta-val">${due}</span>
                                </div>
                                <div class="meta-item">
                                    <span class="meta-label">Creador</span>
                                    <span class="meta-val">${creator}</span>
                                </div>
                            </div>

                            <div class="section-card">
                                <div class="section-title">Descripción</div>
                                <div class="content-body">${description}</div>
                            </div>

                            ${members.length > 0 ? `
                                <div class="section-card">
                                    <div class="section-title">Miembros Asignados</div>
                                    <div class="tag-pills">
                                        ${members.map(m => `<span class="pill">${m}</span>`).join('')}
                                    </div>
                                </div>
                            ` : ''}

                            ${skills.length > 0 ? `
                                <div class="section-card">
                                    <div class="section-title">Habilidades Requeridas</div>
                                    <div class="tag-pills">
                                        ${skills.map(s => `<span class="pill">${s}</span>`).join('')}
                                    </div>
                                </div>
                            ` : ''}

                            <div class="footer">
                                <span>Sientia MTX • Exportado el ${new Date().toLocaleDateString('es-ES', {day: '2-digit', month: '2-digit', year: 'numeric'})}</span>
                                <span>Espacio de Trabajo Seguro</span>
                            </div>
                        </div>
                        <script>
                            window.onload = () => {
                                setTimeout(() => window.print(), 300);
                            };
                        <\/script>
                    </body>
                </html>
            `);
            printWin.document.close();
        }

        function copyActivityJson() {
            const data = {
                title: @json($activity->title),
                description: @json($activity->description),
                status: @json($currentStatus),
                priority: @json($activity->priority),
                metadata: @json($activity->metadata),
                scheduled_date: @json($activity->scheduled_date),
                due_date: @json($activity->due_date)
            };
            navigator.clipboard.writeText(JSON.stringify(data, null, 2));
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'JSON copiado al portapapeles',
                showConfirmButton: false,
                timer: 2000
            });
        }

        function openImportActivityModal(type) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'info',
                title: type === 'file' ? 'Función de carga en desarrollo' : 'Función de pegado en desarrollo',
                showConfirmButton: false,
                timer: 2000
            });
        }

        function reproduceInTeam() {
            Swal.fire({
                title: 'Reproducir en Equipo',
                text: 'Selecciona el equipo al que deseas clonar esta actividad',
                icon: 'info',
                background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#f1f5f9' : '#1e293b',
                customClass: { popup: 'rounded-[2rem]' }
            });
        }

        function printDocumentBook() {
            const printWin = window.open('', '_blank');
            const title = @json($activity->title);
            const teamName = @json($team->name);
            const docVersion = @json($activity->metadata['version'] ?? '1.0.0');
            const chapters = @json($activity->metadata['chapters'] ?? []);
            
            let chaptersHtml = '';
            let tocHtml = '';
            
            chapters.forEach((chap, idx) => {
                tocHtml += `
                    <div class="toc-item">
                        <span class="toc-title">${idx + 1}. ${chap.title}</span>
                        <span class="toc-dots"></span>
                        <span class="toc-page">Capítulo ${idx + 1}</span>
                    </div>
                `;
                
                chaptersHtml += `
                    <div class="chapter-page">
                        <div class="chapter-header">
                            <span class="chapter-num">CAPÍTULO ${idx + 1}</span>
                            <h2 class="chapter-title">${chap.title}</h2>
                            <div class="chapter-meta">Por ${chap.author_name || 'Autor'} • ${chap.updated_at}</div>
                        </div>
                        <div class="chapter-body">${marked.parse ? marked.parse(chap.content) : chap.content}</div>
                    </div>
                `;
            });

            printWin.document.write(`
                <!DOCTYPE html>
                <html>
                    <head>
                        <title>${title} - Libro Digital</title>
                        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&family=Merriweather:wght@300;400;700&display=swap" rel="stylesheet">
                        <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"><\/script>
                        <style>
                            @page { size: A4; margin: 2.5cm 2cm; }
                            body { font-family: 'Merriweather', serif; color: #1e293b; line-height: 1.8; margin: 0; padding: 0; font-size: 14px; }
                            h1, h2, h3, h4, h5, h6, .outfit { font-family: 'Outfit', sans-serif; }
                            
                            /* Portada */
                            .cover-page { height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; page-break-after: always; padding: 2rem; box-sizing: border-box; }
                            .cover-team { font-size: 16px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 4px; margin-bottom: 2rem; font-family: 'Outfit', sans-serif; }
                            .cover-title { font-size: 42px; font-weight: 900; color: #0f172a; line-height: 1.2; margin-bottom: 2rem; font-family: 'Outfit', sans-serif; }
                            .cover-badge { display: inline-block; background: #f1f5f9; color: #475569; padding: 8px 24px; border-radius: 50px; font-size: 14px; font-weight: 700; margin-bottom: 4rem; font-family: 'Outfit', sans-serif; border: 1px solid #e2e8f0; }
                            .cover-footer { margin-top: auto; font-size: 14px; color: #64748b; font-family: 'Outfit', sans-serif; }
                            
                            /* Índice */
                            .toc-page { page-break-after: always; padding: 2rem 0; }
                            .toc-main-title { font-size: 28px; font-weight: 800; color: #0f172a; margin-bottom: 3rem; font-family: 'Outfit', sans-serif; border-bottom: 2px solid #e2e8f0; padding-bottom: 1rem; }
                            .toc-item { display: flex; align-items: baseline; margin-bottom: 1.5rem; font-family: 'Outfit', sans-serif; font-size: 16px; }
                            .toc-title { font-weight: 600; color: #334155; }
                            .toc-dots { flex: 1; border-bottom: 1px dotted #cbd5e1; margin: 0 12px; }
                            .toc-page { font-weight: 700; color: #64748b; font-size: 14px; }
                            
                            /* Capítulos */
                            .chapter-page { page-break-before: always; padding: 2rem 0; }
                            .chapter-header { margin-bottom: 3rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 2rem; }
                            .chapter-num { font-size: 14px; font-weight: 800; color: #8b5cf6; text-transform: uppercase; letter-spacing: 3px; font-family: 'Outfit', sans-serif; display: block; margin-bottom: 0.5rem; }
                            .chapter-title { font-size: 32px; font-weight: 800; color: #0f172a; margin: 0 0 1rem 0; font-family: 'Outfit', sans-serif; line-height: 1.2; }
                            .chapter-meta { font-size: 13px; color: #64748b; font-family: 'Outfit', sans-serif; }
                            .chapter-body { color: #334155; }
                            .chapter-body p { margin-bottom: 1.5rem; }
                            .chapter-body h1, .chapter-body h2, .chapter-body h3 { font-family: 'Outfit', sans-serif; color: #0f172a; margin-top: 2.5rem; margin-bottom: 1rem; font-weight: 700; }
                        </style>
                    </head>
                    <body>
                        <div class="cover-page">
                            <div class="cover-team">${teamName}</div>
                            <h1 class="cover-title">${title}</h1>
                            <div class="cover-badge">DOCUMENTO VERSIÓN ${docVersion}</div>
                            <div class="cover-footer">Sientia MTX • Exportado el ${new Date().toLocaleDateString('es-ES')}</div>
                        </div>
                        
                        <div class="toc-page">
                            <h2 class="toc-main-title">Índice General</h2>
                            ${tocHtml}
                        </div>

                        ${chaptersHtml}
                        
                        <script>
                            window.onload = () => {
                                setTimeout(() => window.print(), 500);
                            };
                        <\/script>
                    </body>
                </html>
            `);
            printWin.document.close();
        }
    </script>

    <!-- MODAL DE CONVERSIÓN DE ACTIVIDAD -->
    <div x-data="{ 
        show: false, 
        targetType: 'task',
        types: [
            { id: 'task', label: 'Tarea General', icon: '📝', desc: 'Actividad estándar con seguimiento de urgencia, carga cognitiva y gestión de progreso.' },
            { id: 'document', label: 'Documento / Base de Conocimiento', icon: '📄', desc: 'Registro centrado en la documentación colaborativa y control de versiones.' },
            { id: 'link', label: 'Enlace / Recurso Externo', icon: '🔗', desc: 'Referencia a un sitio web, herramienta externa o repositorio de información.' },
            { id: 'meeting', label: 'Reunión / Encuentro', icon: '🤝', desc: 'Evento programado con modalidad (remota/presencial), duración y ubicación.' },
            { id: 'reminder', label: 'Recordatorio / Notificación', icon: '🔔', desc: 'Aviso puntual con canales de distribución (Email, Push, etc.).' }
        ]
    }"
        @open-convert-activity-modal.window="show = true"
        x-show="show"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        @click.self="show = false">
        
        <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] w-full max-w-2xl overflow-hidden shadow-2xl border border-gray-100 dark:border-gray-800 transform transition-all text-left flex flex-col max-h-[90vh]"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95">
            
            <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-violet-50/50 dark:bg-violet-950/20">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xl">✨</span>
                        <h3 class="text-lg font-black text-gray-900 dark:text-white uppercase tracking-tight">
                            {{ __('Convertir Actividad') }}
                        </h3>
                    </div>
                    <p class="text-xs text-gray-500 font-medium">
                        {{ __('Transforma esta actividad a un nuevo tipo. La original quedará archivada como deprecada para mantener el rastro de auditoría.') }}
                    </p>
                </div>
                <button @click="show = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors p-2 rounded-2xl hover:bg-white dark:hover:bg-gray-800 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form action="{{ route('teams.activities.convert', [$team, $activity]) }}" method="POST" class="flex flex-col flex-1 overflow-hidden m-0">
                @csrf
                <div class="p-8 overflow-y-auto custom-scrollbar space-y-4 flex-1">
                    <label class="block text-xs font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2">
                        Selecciona el Tipo Destino
                    </label>
                    <div class="grid grid-cols-1 gap-3">
                        <template x-for="t in types" :key="t.id">
                            <label :class="targetType === t.id ? 'border-violet-600 bg-violet-50/20 dark:bg-violet-950/10 ring-2 ring-violet-600/20' : 'border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 hover:border-gray-300 dark:hover:border-gray-700'"
                                class="flex items-start gap-4 p-5 rounded-3xl border-2 cursor-pointer transition-all shadow-sm">
                                <input type="radio" name="type" :value="t.id" x-model="targetType" class="mt-1 text-violet-600 focus:ring-violet-500 dark:bg-gray-800 border-gray-300 dark:border-gray-700">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-xl" x-text="t.icon"></span>
                                        <span class="text-sm font-bold text-gray-900 dark:text-white" x-text="t.label"></span>
                                    </div>
                                    <p class="text-xs text-gray-500 leading-normal" x-text="t.desc"></p>
                                </div>
                            </label>
                        </template>
                    </div>

                    @if($activity->children && $activity->children->isNotEmpty())
                        <div class="mt-6 p-5 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/50 rounded-3xl flex items-center gap-4">
                            <span class="text-2xl shrink-0">⚠️</span>
                            <div class="text-xs text-amber-800 dark:text-amber-300 leading-relaxed font-medium">
                                <span class="font-bold uppercase tracking-wider block mb-0.5">Atención: Plan Maestro Detectado</span>
                                Esta actividad tiene <b>{{ $activity->children->count() }} subactividades</b> asociadas. Según la política del sistema, todas las subactividades se convertirán y re-enlazarán automáticamente al nuevo registro padre.
                            </div>
                        </div>
                    @endif
                </div>

                <div class="px-8 py-5 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-800 flex items-center justify-end gap-3 shrink-0">
                    <button type="button" @click="show = false" class="px-6 py-3 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-xs font-black uppercase tracking-widest hover:bg-gray-50 dark:hover:bg-gray-800 transition-all shadow-sm active:scale-95">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-3 rounded-2xl bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white text-xs font-black uppercase tracking-widest transition-all shadow-lg shadow-violet-500/25 active:scale-95">
                        Iniciar Conversión
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL DE FUSIÓN DE ACTIVIDAD DEPRECADA (MERGE) -->
    <div x-data="{ show: false }"
        @open-merge-deprecated-modal.window="show = true"
        x-show="show"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        @click.self="show = false">
        
        <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] w-full max-w-lg overflow-hidden shadow-2xl border border-gray-100 dark:border-gray-800 transform transition-all text-left flex flex-col max-h-[90vh]"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95">
            
            <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-blue-50/50 dark:bg-blue-950/20">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xl">🔄</span>
                        <h3 class="text-lg font-black text-gray-900 dark:text-white uppercase tracking-tight">
                            {{ __('Fusionar Actividad') }}
                        </h3>
                    </div>
                    <p class="text-xs text-gray-500 font-medium">
                        {{ __('Transfiere todos los adjuntos, notas y relaciones de esta actividad deprecada a una actividad activa existente.') }}
                    </p>
                </div>
                <button @click="show = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors p-2 rounded-2xl hover:bg-white dark:hover:bg-gray-800 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            @php
                $mergeCandidates = $team->activities()->where('activities.id', '!=', $activity->id)->where('is_archived', false)->get();
            @endphp

            <form action="{{ route('teams.activities.merge-deprecated', [$team, $activity]) }}" method="POST" class="flex flex-col flex-1 overflow-hidden m-0">
                @csrf
                <div class="p-8 overflow-y-auto custom-scrollbar space-y-5 flex-1">
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2">
                            Actividad Destino (Activa)
                        </label>
                        <select name="target_activity_id" required class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl px-4 py-3 text-xs text-gray-800 dark:text-white outline-none cursor-pointer shadow-sm">
                            <option value="">Selecciona la actividad destino...</option>
                            @foreach($mergeCandidates as $cand)
                                <option value="{{ $cand->id }}">[{{ strtoupper($cand->type_label ?? $cand->type) }}] {{ $cand->title }}</option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-gray-400 font-medium mt-2">
                            La actividad elegida recibirá todo el historial y elementos vinculados de la actividad actual.
                        </p>
                    </div>
                </div>

                <div class="px-8 py-5 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-800 flex items-center justify-end gap-3 shrink-0">
                    <button type="button" @click="show = false" class="px-6 py-3 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-xs font-black uppercase tracking-widest hover:bg-gray-50 dark:hover:bg-gray-800 transition-all shadow-sm active:scale-95">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-3 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-black uppercase tracking-widest transition-all shadow-lg shadow-blue-500/25 active:scale-95">
                        Confirmar Fusión
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL DE AÑADIR CAPÍTULO A DOCUMENTO -->
    <div x-data="{ show: false }"
        @open-add-chapter-modal.window="show = true"
        x-show="show"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        @click.self="show = false">
        
        <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] w-full max-w-2xl overflow-hidden shadow-2xl border border-gray-100 dark:border-gray-800 transform transition-all text-left flex flex-col max-h-[90vh]"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95">
            
            <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-violet-50/50 dark:bg-violet-950/20">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xl">✍️</span>
                        <h3 class="text-lg font-black text-gray-900 dark:text-white uppercase tracking-tight">
                            {{ __('Añadir Nuevo Capítulo') }}
                        </h3>
                    </div>
                    <p class="text-xs text-gray-500 font-medium">
                        {{ __('Añade una nueva sección estructurada al documento. Podrás editarla o eliminarla de forma independiente.') }}
                    </p>
                </div>
                <button @click="show = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors p-2 rounded-2xl hover:bg-white dark:hover:bg-gray-800 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form action="{{ route('teams.activities.chapters.store', [$team, $activity]) }}" method="POST" class="flex flex-col flex-1 overflow-hidden m-0">
                @csrf
                <div class="p-8 overflow-y-auto custom-scrollbar space-y-5 flex-1">
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2">
                            Título del Capítulo
                        </label>
                        <input type="text" name="chapter_title" required placeholder="Ej. 1. Introducción y Objetivos..." class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl px-4 py-3 text-xs text-gray-800 dark:text-white outline-none shadow-sm">
                    </div>

                    <div style="height: 250px; max-height: none; overflow-y: auto;" class="resize-y min-h-[150px] overflow-y-auto custom-scrollbar border border-gray-200 dark:border-gray-700 rounded-2xl p-3 bg-white dark:bg-gray-900 shadow-sm">
                        <label class="block text-xs font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-2">
                            Contenido (Markdown)
                        </label>
                        <x-markdown-editor 
                            name="chapter_content" 
                            id="new-chap-content"
                            :value="''"
                            :label="null"
                            rows="5"
                            placeholder="Escribe el contenido del capítulo utilizando sintaxis Markdown..."
                            :upload-url="route('teams.forum.upload_image', $team)"
                        />
                    </div>
                </div>

                <div class="px-8 py-5 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-800 flex items-center justify-end gap-3 shrink-0">
                    <button type="button" @click="show = false" class="px-6 py-3 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-xs font-black uppercase tracking-widest hover:bg-gray-50 dark:hover:bg-gray-800 transition-all shadow-sm active:scale-95">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-3 rounded-2xl bg-violet-600 hover:bg-violet-500 text-white text-xs font-black uppercase tracking-widest transition-all shadow-lg shadow-violet-500/25 active:scale-95">
                        Guardar Capítulo
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
