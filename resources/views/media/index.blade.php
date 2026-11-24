<x-app-layout>
    @section('title', __('tasks.disk_quota'))

    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 rounded-xl shadow-sm border border-violet-200 dark:border-violet-800/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white heading">
                        {{ __('tasks.disk_quota') }}
                    </h1>
                    <x-demo-hint>
                        {{ __('media_demo_hint') }}
                    </x-demo-hint>
                </div>
            </div>

            <div class="flex items-center gap-2">
                @if(auth()->user()->is_admin)
                    {{-- Botón de Purga Global de Archivos Huérfanos y Obsoletos --}}
                    <button type="button" onclick="confirmGlobalPurge()" 
                            class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-500 text-white text-xs font-black uppercase tracking-widest rounded-xl transition-all shadow-lg shadow-red-600/20 active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        {{ __('Purgar obsoletos') }}
                        @if(!empty($orphanStats['total_files']) && $orphanStats['total_files'] > 0)
                            <span class="bg-white/20 text-white text-[10px] px-1.5 py-0.5 rounded-full font-black">
                                {{ $orphanStats['total_files'] }}
                            </span>
                        @endif
                    </button>

                    <form id="global-purge-form" action="{{ route('media.purge-orphans') }}" method="POST" class="hidden">
                        @csrf
                    </form>
                @endif

                @if(auth()->user()->is_admin || $teams->isNotEmpty())
                    @if($teamId && ($currentTeam = $teams->find($teamId)))
                        @if(auth()->user()->is_admin || auth()->user()->getRole($currentTeam) === 'coordinator')
                            <a href="{{ route('teams.storage.index', $currentTeam) }}" 
                                class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-500 text-white text-xs font-black uppercase tracking-widest rounded-xl transition-all shadow-lg shadow-amber-600/20 active:scale-95">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                {{ __('Mantenimiento del Equipo') }}
                            </a>
                        @endif
                    @elseif(auth()->user()->is_admin || $teams->isNotEmpty())
                        {{-- Selector rápido de mantenimiento si no hay equipo filtrado --}}
                        <x-dropdown align="right" width="64">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-black uppercase tracking-widest rounded-xl transition-all active:scale-95 border border-gray-200 dark:border-gray-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    {{ __('Mantenimiento por Equipo') }}
                                    <svg class="ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <div class="px-4 py-2 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100 dark:border-gray-800 mb-1">
                                    {{ __('Seleccionar Equipo') }}
                                </div>
                                <div class="max-h-64 overflow-y-auto">
                                    @foreach($teams as $team)
                                        @if(auth()->user()->is_admin || auth()->user()->getRole($team) === 'coordinator')
                                            <x-dropdown-link :href="route('teams.storage.index', $team)">
                                                <div class="flex items-center justify-between gap-2">
                                                    <span class="font-bold">{{ $team->name }}</span>
                                                    <span class="text-[9px] bg-amber-100 dark:bg-amber-900/30 text-amber-600 px-1.5 py-0.5 rounded uppercase font-black tracking-tighter">{{ __('Limpieza') }}</span>
                                                </div>
                                            </x-dropdown-link>
                                        @endif
                                    @endforeach
                                </div>
                            </x-slot>
                        </x-dropdown>
                    @endif
                @endif
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Usage Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm dark:shadow-none transition-colors col-span-1 md:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">
                        {{ __('tasks.disk_quota') }}</h3>
                    <div class="flex items-center gap-2">
                        <span class="text-lg font-black text-gray-900 dark:text-white">
                            {{ number_format($user->disk_used / 1024 / 1024, 2) }}
                        </span>
                        <span class="text-sm font-medium text-gray-400">/
                            {{ number_format($user->disk_quota / 1024 / 1024, 0) }} MB</span>
                    </div>
                </div>

                @php
                    $perc = $user->disk_quota > 0 ? ($user->disk_used / $user->disk_quota) * 100 : 0;
                    $barColor = $perc > 90 ? 'bg-red-500' : ($perc > 70 ? 'bg-amber-500' : 'bg-violet-500');
                @endphp

                <div
                    class="w-full h-4 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden border border-gray-200 dark:border-gray-700 shadow-inner">
                    <div class="h-full {{ $barColor }} shadow-lg"
                        style="width: {{ $perc }}%; transition: none !important;"></div>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between text-xs text-gray-500 gap-2">
                    <span>{{ number_format($attachments->count()) }} {{ __('archivos registrados') }}</span>
                    <span>{{ number_format($perc, 1) }}% {{ __('utilizado') }}</span>
                </div>
            </div>

            <!-- Card de archivos obsoletos / huérfanos -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm dark:shadow-none transition-colors flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-400">{{ __('Archivos obsoletos') }}</span>
                        <span class="p-1.5 bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </span>
                    </div>
                    <div class="text-2xl font-black text-gray-900 dark:text-white">
                        {{ $orphanStats['total_mb'] }} <span class="text-xs font-normal text-gray-400">{{ __('MB recuperables') }}</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ $orphanStats['obsolete_records'] }} {{ __('registros huérfanos y') }} {{ $orphanStats['ghost_files'] }} {{ __('archivos sin vincular en disco.') }}
                    </p>
                </div>

                @if(auth()->user()->is_admin)
                    <button type="button" onclick="confirmGlobalPurge()" 
                            class="mt-4 w-full py-2 px-3 bg-red-50 hover:bg-red-100 dark:bg-red-950/20 dark:hover:bg-red-950/40 text-red-600 dark:text-red-400 rounded-xl text-xs font-bold transition-colors flex items-center justify-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        {{ __('Limpiar almacenamiento') }}
                    </button>
                @endif
            </div>
        </div>

        <!-- File List -->
        <div
            class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm dark:shadow-none transition-colors">
            <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <h3 class="text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">
                    {{ __('tasks.files_management') }}</h3>
                
                <form action="{{ route('media.index') }}" method="GET" class="flex items-center gap-2">
                    <label for="team_filter" class="text-[10px] font-black text-gray-400 uppercase tracking-tighter">{{ __('Filtrar por Equipo') }}:</label>
                    <select name="team_id" id="team_filter" onchange="this.form.submit()" 
                        class="text-xs font-bold bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 rounded-xl px-3 py-1.5 focus:ring-violet-500 transition-all text-gray-700 dark:text-gray-300">
                        <option value="">{{ __('Todos los equipos') }}</option>
                        @foreach($teams as $team)
                            <option value="{{ $team->id }}" {{ $teamId == $team->id ? 'selected' : '' }}>
                                {{ $team->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
            @if ($attachments->isEmpty())
                <div class="py-16 text-center">
                    <div
                        class="w-16 h-16 bg-gray-50 dark:bg-gray-800 text-gray-400 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-gray-100 dark:border-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <p class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ __('tasks.no_files') }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ __('No hay archivos en este filtro') }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                        <thead
                            class="bg-gray-50/50 dark:bg-gray-800/50 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-gray-800">
                            <tr>
                                <th class="px-6 py-4">{{ __('tasks.file') }}</th>
                                <th class="px-6 py-4">{{ __('Actividad / Tarea') }}</th>
                                <th class="px-6 py-4">{{ __('tasks.size') }}</th>
                                <th class="px-6 py-4">{{ __('tasks.date') }}</th>
                                <th class="px-6 py-4 text-right">{{ __('tasks.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($attachments as $file)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-10 h-10 rounded-xl bg-violet-50 dark:bg-violet-950/30 text-violet-600 dark:text-violet-400 flex items-center justify-center shrink-0 border border-violet-100 dark:border-violet-900/40">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="1.5"
                                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-bold text-gray-800 dark:text-gray-200 truncate max-w-[200px]"
                                                    title="{{ $file->file_name }}">
                                                    {{ $file->file_name }}
                                                </p>
                                                <p class="text-[10px] text-gray-400 uppercase tracking-tighter">
                                                    {{ $file->mime_type }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($file->activity_id && $file->team_id)
                                            <a href="{{ route('teams.activities.show', [$file->team_id, $file->activity_id]) }}"
                                                class="text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline">
                                                {{ $file->activity_title ?: __('Actividad #:id', ['id' => $file->activity_id]) }}
                                            </a>
                                            @if($file->is_deleted)
                                                <span class="ms-1.5 px-1.5 py-0.5 bg-red-100 text-red-600 dark:bg-red-950/30 dark:text-red-400 text-[9px] rounded font-bold">
                                                    {{ __('Eliminada') }}
                                                </span>
                                            @endif
                                            <p class="text-[10px] text-gray-500 font-medium">
                                                {{ $file->team_name }}
                                            </p>
                                        @elseif ($file->activity_title)
                                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                                {{ $file->activity_title }}
                                            </span>
                                            @if($file->is_deleted)
                                                <span class="ms-1.5 px-1.5 py-0.5 bg-red-100 text-red-600 dark:bg-red-950/30 dark:text-red-400 text-[9px] rounded font-bold">
                                                    {{ __('Eliminada') }}
                                                </span>
                                            @endif
                                            <p class="text-[10px] text-gray-500 font-medium">
                                                {{ $file->team_name ?: __('Sin equipo') }}
                                            </p>
                                        @else
                                            <span class="text-[10px] italic text-red-400 font-medium">
                                                {{ __('Obsoleto / Sin actividad vinculada') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 font-mono text-xs">
                                        {{ number_format($file->file_size / 1024 / 1024, 2) }} MB
                                    </td>
                                    <td class="px-6 py-4 text-[11px] text-gray-500">
                                        {{ $file->created_at ? $file->created_at->format('d M Y') : '—' }}
                                        <span class="block text-[10px] opacity-70">
                                            {{ $file->created_at ? $file->created_at->format('H:i') : '' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity">
                                            {{-- Botón de Inyección IA --}}
                                            <button type="button" 
                                                @click="$dispatch('ai:analyze-file', { 
                                                    fileName: '{{ addslashes($file->file_name) }}', 
                                                    fileId: {{ $file->id }},
                                                    fileUrl: '{{ route('media.download', ['attachment' => $file->id, 'type' => $file->source_type]) }}',
                                                    fileType: '{{ $file->mime_type }}',
                                                    taskId: {{ $file->activity_id ?? 'null' }},
                                                    autoSubmit: false 
                                                })"
                                                class="p-2 text-indigo-500 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors"
                                                title="{{ __('html.ai_on_file') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                </svg>
                                            </button>

                                            <a href="{{ route('media.download', ['attachment' => $file->id, 'type' => $file->source_type]) }}"
                                                class="p-2 text-gray-400 hover:text-violet-600 dark:text-gray-500/70 dark:hover:text-violet-400 transition-colors"
                                                title="{{ __('tasks.download') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4 16v1a2 2 0 002-2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                </svg>
                                            </a>

                                            <button type="button" onclick="confirmFileDelete({{ $file->id }}, '{{ $file->source_type }}')"
                                                class="p-2 text-gray-400 hover:text-red-500 transition-colors"
                                                title="{{ __('tasks.delete') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>

                                            <form id="delete-form-{{ $file->source_type }}-{{ $file->id }}"
                                                action="{{ route('media.destroy', $file->id) }}" method="POST"
                                                class="hidden">
                                                @csrf @method('DELETE')
                                                <input type="hidden" name="source_type" value="{{ $file->source_type }}">
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <script>
        function confirmFileDelete(id, sourceType) {
            Swal.fire({
                title: '{{ __('tasks.delete_confirm') }}',
                text: '{{ __('tasks.delete_file_warning') }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: '{{ __('Yes, delete') }}',
                cancelButtonText: '{{ __('Cancel') }}',
                background: document.documentElement.classList.contains('dark') ? '#111827' : '#fff',
                color: document.documentElement.classList.contains('dark') ? '#fff' : '#111827'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + sourceType + '-' + id).submit();
                }
            });
        }

        function confirmGlobalPurge() {
            Swal.fire({
                title: '{{ __('¿Purgar archivos obsoletos y huérfanos?') }}',
                html: '{{ __('Esta acción eliminará de forma irreversible los archivos de tareas y actividades eliminadas, así como los archivos fantasma sin registro en el servidor.') }}<br><br><strong>{{ __('Se liberarán aproximadamente :mb MB.', ['mb' => $orphanStats['total_mb']]) }}</strong>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: '{{ __('Sí, purgar todo') }}',
                cancelButtonText: '{{ __('Cancel') }}',
                background: document.documentElement.classList.contains('dark') ? '#111827' : '#fff',
                color: document.documentElement.classList.contains('dark') ? '#fff' : '#111827'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('global-purge-form').submit();
                }
            });
        }
    </script>
</x-app-layout>
