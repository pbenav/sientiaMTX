<x-app-layout>
    @section('title', 'Crear ' . ucfirst($type) . ' — ' . $team->name)

    <x-slot name="header">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2 min-w-0">
                <a href="{{ route('teams.activities.index', $team) }}"
                    class="p-1.5 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-lg transition-all shrink-0"
                    title="{{ __('navigation.back') ?? 'Volver' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                @include('teams.partials.breadcrumb')
                <span class="text-gray-300 dark:text-gray-700 mx-1">/</span>
                <h1 class="text-base font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight flex items-center gap-1.5">
                    <span class="truncate">Crear nueva actividad: 
                        @switch($type)
                            @case('task') 📋 Tarea @break
                            @case('document') 📄 Documento @break
                            @case('note') 📝 Nota @break
                            @case('link') 🔗 Enlace @break
                            @case('decision') ⚖️ Acuerdo @break
                            @case('meeting') 🎥 Reunión @break
                            @case('reminder') 🔔 Recordatorio @break
                            @default {{ ucfirst($type) }}
                        @endswitch
                    </span>
                </h1>
            </div>
            
            <div class="flex items-center gap-2 shrink-0">
                @if($type === 'task')
                    <button type="button" onclick="importFromClipboard()" class="text-[10px] font-black uppercase tracking-widest text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 transition-colors flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl border border-emerald-100 dark:border-emerald-500/20 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        {{ __('Pegar JSON') }}
                    </button>
                    <button type="button" onclick="importTask()" class="text-[10px] font-black uppercase tracking-widest text-violet-600 dark:text-violet-400 hover:text-violet-700 dark:hover:text-violet-300 transition-colors flex items-center gap-1.5 px-3 py-1.5 bg-violet-50 dark:bg-violet-900/30 rounded-xl border border-violet-100 dark:border-violet-500/20 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0l-4 4m4-4v12" />
                        </svg>
                        {{ __('Cargar Archivo (.json)') }}
                    </button>
                @endif
                @include('teams.partials.header-toolbar', ['toolsOnly' => true])
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-6">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm transition-all duration-300">
            <form id="create-activity-form" method="POST" action="{{ route('teams.activities.store', $team) }}" class="space-y-6" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="type" value="{{ $type }}">

                <!-- Title -->
                <div>
                    <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-2">Título de la Actividad</label>
                    <input type="text" name="title" value="{{ old('title') }}" required
                        class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-2xl px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 outline-none transition-all"
                        placeholder="Ej. Redactar el acta de inicio, Revisar propuesta comercial...">
                    @error('title')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <x-markdown-editor 
                        name="description" 
                        id="description"
                        :value="old('description')"
                        :label="__('Descripción o Contenido')"
                        rows="4"
                        :upload-url="route('teams.forum.upload_image', $team)"
                        :mentions-url="route('teams.mentions', $team)"
                    />
                </div>

                <!-- Campos Específicos según el Tipo -->
                <div class="bg-gray-50/50 dark:bg-gray-800/20 border border-gray-150 dark:border-gray-800 rounded-3xl p-6 space-y-6">
                    <div class="flex items-center gap-2 border-b border-gray-200/50 dark:border-gray-800 pb-3">
                        <div class="w-8 h-8 rounded-xl bg-violet-50 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 flex items-center justify-center font-bold">
                            ✨
                        </div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Información Específica del Tipo</h3>
                    </div>

                    @if ($type === 'task')
                        <!-- TAREA ESPECÍFICO -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400 mb-2">Urgencia</label>
                                <select name="urgency" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none cursor-pointer">
                                    <option value="low" {{ old('urgency') == 'low' ? 'selected' : '' }}>Baja</option>
                                    <option value="medium" {{ old('urgency', 'medium') == 'medium' ? 'selected' : '' }}>Media</option>
                                    <option value="high" {{ old('urgency') == 'high' ? 'selected' : '' }}>Alta</option>
                                    <option value="critical" {{ old('urgency') == 'critical' ? 'selected' : '' }}>Crítica</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400 mb-2">Carga Cognitiva</label>
                                <select name="cognitive_load" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none cursor-pointer">
                                    @for ($i = 1; $i <= 10; $i++)
                                        <option value="{{ $i }}" {{ old('cognitive_load', 1) == $i ? 'selected' : '' }}>{{ $i }} ({{ $i == 1 ? 'Mínima' : ($i == 5 ? 'Media' : ($i == 10 ? 'Extrema' : '')) }})</option>
                                    @endfor
                                </select>
                            </div>

                            <div class="md:col-span-2">
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input type="checkbox" name="is_out_of_skill_tree" value="1" {{ old('is_out_of_skill_tree') ? 'checked' : '' }}
                                        class="accent-violet-600 w-5 h-5 rounded-lg border-gray-300 dark:border-gray-600 focus:ring-violet-500/20">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-gray-700 dark:text-gray-300">Fuera de mi Skill Tree</span>
                                        <span class="text-xs text-gray-500">¿Esta actividad requiere habilidades que no forman parte de tu formación principal?</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Autoprogramable (Recurrencia) -->
                        <div x-data="{ 
                            isAutoprogrammable: {{ old('is_autoprogrammable', 0) ? 'true' : 'false' }},
                            frequency: '{{ old('autoprogram_settings.frequency', 'daily') }}',
                            monthlyType: '{{ old('autoprogram_settings.monthly_type', 'date') }}',
                            labels: {
                                'daily': 'días',
                                'weekly': 'semanas',
                                'monthly': 'meses',
                                'yearly': 'años'
                            }
                        }" class="bg-violet-50/30 dark:bg-gray-900/40 backdrop-blur-md border border-violet-100 dark:border-violet-500/20 rounded-2xl p-6 shadow-sm transition-all mt-6">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-violet-100 dark:bg-violet-500/10 flex items-center justify-center text-violet-600 dark:text-violet-400 shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">Autoprogramable (Recurrencia)</span>
                                        <span class="text-[11px] text-gray-500 dark:text-gray-400">Permite que esta actividad se duplique automáticamente según el patrón definido.</span>
                                    </div>
                                </div>
                                
                                <div class="flex p-1 bg-gray-200 dark:bg-gray-950/50 rounded-xl w-fit self-start sm:self-center border border-transparent dark:border-gray-800">
                                    <button type="button" @click="isAutoprogrammable = false" 
                                        :class="!isAutoprogrammable ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                        class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all duration-200">
                                        Desactivado
                                    </button>
                                    <button type="button" @click="isAutoprogrammable = true" 
                                        :class="isAutoprogrammable ? 'bg-violet-600 text-white shadow-lg shadow-violet-500/20' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                        class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all duration-200">
                                        Activo
                                    </button>
                                </div>
                                <input type="hidden" name="is_autoprogrammable" :value="isAutoprogrammable ? 1 : 0">
                            </div>

                            <div x-show="isAutoprogrammable" x-transition class="space-y-6 pt-6 border-t border-violet-100/50 dark:border-violet-500/10">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="flex items-center gap-2 text-xs font-bold text-violet-600 dark:text-violet-400">
                                            Frecuencia
                                        </label>
                                        <select name="autoprogram_settings[frequency]" x-model="frequency" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none cursor-pointer">
                                            <option value="daily">Diaria</option>
                                            <option value="weekly">Semanal</option>
                                            <option value="monthly">Mensual</option>
                                            <option value="yearly">Anual</option>
                                        </select>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="flex items-center gap-2 text-xs font-bold text-violet-600 dark:text-violet-400">
                                            Repetir cada
                                        </label>
                                        <div class="flex items-center gap-2">
                                            <input type="number" name="autoprogram_settings[interval]" value="1" min="1" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none">
                                            <span class="text-xs font-medium text-gray-500 w-16" x-text="labels[frequency]">días</span>
                                        </div>
                                    </div>

                                    <div x-show="frequency === 'weekly'" class="col-span-2 space-y-3 pb-2" x-transition>
                                        <label class="flex items-center gap-2 text-xs font-bold text-violet-600 dark:text-violet-400">
                                            Días de la semana
                                        </label>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach(['1' => 'L', '2' => 'M', '3' => 'X', '4' => 'J', '5' => 'V', '6' => 'S', '7' => 'D'] as $val => $label)
                                                <label class="relative cursor-pointer">
                                                    <input type="checkbox" name="autoprogram_settings[days][]" value="{{ $val }}" 
                                                        {{ in_array($val, old('autoprogram_settings.days', [])) ? 'checked' : '' }}
                                                        class="peer sr-only">
                                                    <div class="w-9 h-9 rounded-xl border-2 border-gray-100 dark:border-gray-800 flex items-center justify-center text-xs font-black text-gray-400 peer-checked:border-violet-500 peer-checked:bg-violet-50 dark:peer-checked:bg-violet-900/30 peer-checked:text-violet-600 transition-all hover:border-violet-200 shadow-sm">
                                                        {{ $label }}
                                                    </div>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div x-show="frequency === 'monthly'" class="col-span-2 space-y-3 pb-2" x-transition>
                                        <label class="flex items-center gap-2 text-xs font-bold text-violet-600 dark:text-violet-400">
                                            Patrón Mensual
                                        </label>
                                        <div class="flex flex-col sm:flex-row gap-4">
                                            <label class="relative flex items-center gap-3 cursor-pointer group">
                                                <input type="radio" name="autoprogram_settings[monthly_type]" value="date" x-model="monthlyType" class="peer sr-only">
                                                <div class="w-4 h-4 rounded-full border-2 border-gray-300 dark:border-gray-600 peer-checked:border-violet-500 flex items-center justify-center transition-all">
                                                    <div class="w-2 h-2 rounded-full bg-violet-500 hidden peer-checked:block"></div>
                                                </div>
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">El mismo día del mes</span>
                                            </label>
                                            <label class="relative flex items-center gap-3 cursor-pointer group">
                                                <input type="radio" name="autoprogram_settings[monthly_type]" value="ordinal" x-model="monthlyType" class="peer sr-only">
                                                <div class="w-4 h-4 rounded-full border-2 border-gray-300 dark:border-gray-600 peer-checked:border-violet-500 flex items-center justify-center transition-all">
                                                    <div class="w-2 h-2 rounded-full bg-violet-500 hidden peer-checked:block"></div>
                                                </div>
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Un día específico de la semana</span>
                                            </label>
                                        </div>
                                        
                                        <div x-show="monthlyType === 'ordinal'" class="flex items-center gap-2 mt-3" x-transition>
                                            <span class="text-sm text-gray-500">El</span>
                                            <select name="autoprogram_settings[monthly_ordinal]" class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-3 py-1.5 text-sm text-gray-900 dark:text-white outline-none">
                                                <option value="first">Primer</option>
                                                <option value="second">Segundo</option>
                                                <option value="third">Tercer</option>
                                                <option value="fourth">Cuarto</option>
                                                <option value="last">Último</option>
                                            </select>
                                            <select name="autoprogram_settings[monthly_day]" class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-3 py-1.5 text-sm text-gray-900 dark:text-white outline-none">
                                                <option value="monday">Lunes</option>
                                                <option value="tuesday">Martes</option>
                                                <option value="wednesday">Miércoles</option>
                                                <option value="thursday">Jueves</option>
                                                <option value="friday">Viernes</option>
                                                <option value="saturday">Sábado</option>
                                                <option value="sunday">Domingo</option>
                                            </select>
                                            <span class="text-sm text-gray-500">del mes</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <label class="flex items-center gap-2 text-xs font-bold text-violet-600 dark:text-violet-400">
                                        Antelación de creación (despertar)
                                    </label>
                                    <div class="flex items-center gap-3">
                                        <input type="number" name="autoprogram_settings[lead_value]" value="7" min="1" class="w-24 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none">
                                        <select name="autoprogram_settings[lead_unit]" class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none">
                                            <option value="hours">Horas</option>
                                            <option value="days" selected>Días</option>
                                            <option value="weeks">Semanas</option>
                                            <option value="months">Meses</option>
                                        </select>
                                        <span class="text-[10px] text-gray-400 italic">antes de la fecha señalada</span>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <label class="flex items-center gap-2 text-xs font-bold text-violet-600 dark:text-violet-400">
                                        Terminar
                                    </label>
                                    <div class="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-8">
                                        <label class="relative flex items-center gap-3 cursor-pointer group">
                                            <input type="radio" name="autoprogram_settings[limit_type]" value="count" checked class="peer sr-only">
                                            <div class="w-4 h-4 rounded-full border-2 border-gray-300 dark:border-gray-600 peer-checked:border-violet-500 flex items-center justify-center transition-all">
                                                <div class="w-2 h-2 rounded-full bg-violet-500 hidden peer-checked:block"></div>
                                            </div>
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Después de</span>
                                            <input type="number" name="autoprogram_settings[limit_value_count]" value="5" min="1" class="w-16 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-lg px-2 py-1 text-xs text-gray-900 dark:text-white outline-none">
                                            <span class="text-xs text-gray-500">veces</span>
                                        </label>
                                        <label class="relative flex items-center gap-3 cursor-pointer group">
                                            <input type="radio" name="autoprogram_settings[limit_type]" value="date" class="peer sr-only">
                                            <div class="w-4 h-4 rounded-full border-2 border-gray-300 dark:border-gray-600 peer-checked:border-violet-500 flex items-center justify-center transition-all">
                                                <div class="w-2 h-2 rounded-full bg-violet-500 hidden peer-checked:block"></div>
                                            </div>
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">El día</span>
                                            <input type="date" name="autoprogram_settings[limit_value_date]" class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-lg px-2 py-1 text-xs text-gray-900 dark:text-white outline-none">
                                        </label>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-4 pt-2">
                                    <label class="flex items-center gap-2 cursor-pointer group">
                                        <input type="checkbox" name="autoprogram_settings[skip_weekends]" value="1" checked class="accent-violet-600 rounded">
                                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Saltar fines de semana</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer group">
                                        <input type="checkbox" name="autoprogram_settings[sequential]" value="1" checked class="accent-violet-600 rounded">
                                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Dependencias secuenciales (Gantt)</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Gamification Features (Resiliencia Colectiva) -->
                        <div class="bg-amber-50/20 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-900/30 rounded-2xl p-6 space-y-6 mt-6">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center text-amber-600 shadow-sm border border-amber-200/20">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <h4 class="text-sm font-bold uppercase tracking-wide text-amber-700 dark:text-amber-400">Impacto y Bienestar</h4>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                                <!-- Skill Selection -->
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                                        Árbol de Capacidades
                                    </label>
                                    <select name="skill_id" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm focus:border-amber-500 outline-none text-gray-900 dark:text-white cursor-pointer">
                                        <option value="">Seleccionar habilidad principal...</option>
                                        @foreach($skills as $skill)
                                            <option value="{{ $skill->id }}" {{ old('skill_id') == $skill->id ? 'selected' : '' }}>
                                                {{ $skill->name }} ({{ $skill->category }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Impact Human Metric -->
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                                        Impacto Social / Humano (Puntos)
                                    </label>
                                    <input type="number" name="impact_human_metric" value="{{ old('impact_human_metric', 0) }}" min="0" max="100" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none">
                                </div>

                                <!-- Backstage Checkbox -->
                                <div class="flex items-center">
                                    <label class="relative flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 cursor-pointer hover:border-emerald-300 transition-all group w-full">
                                        <input type="checkbox" name="is_backstage" value="1" {{ old('is_backstage') ? 'checked' : '' }} class="accent-emerald-600 rounded w-4 h-4">
                                        <div class="flex flex-col">
                                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Backstage / Preparación</span>
                                            <span class="text-[9px] text-gray-400 uppercase font-black">Visibiliza el esfuerzo invisible</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    @elseif ($type === 'document')
                        <!-- DOCUMENTO ESPECÍFICO -->
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                                Esta actividad creará un documento de texto colaborativo. Los miembros del equipo podrán editarlo simultáneamente usando OnlyOffice.
                            </p>
                            <div class="mt-4">
                                <label class="block text-xs font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400 mb-2">Versión Inicial</label>
                                <input type="text" name="version" value="{{ old('version', '1.0.0') }}" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2 text-sm text-gray-900 dark:text-white outline-none">
                            </div>
                        </div>
                    @elseif ($type === 'link')
                        <!-- ENLACE ESPECÍFICO -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400 mb-2">Dirección URL (Enlace)</label>
                            <input type="url" name="url" value="{{ old('url') }}" required class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none" placeholder="https://example.com/recurso">
                            @error('url')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    @elseif ($type === 'decision')
                        <!-- DECISIÓN ESPECÍFICO -->
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed mb-4">
                                Registra un acuerdo tomado por el equipo o la dirección. Esto mantendrá la trazabilidad histórica de las decisiones clave.
                            </p>
                        </div>
                    @elseif ($type === 'meeting')
                        <!-- REUNIÓN ESPECÍFICO -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400 mb-2">Modalidad</label>
                                <select name="modality" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none cursor-pointer">
                                    <option value="remote" {{ old('modality') == 'remote' ? 'selected' : '' }}>💻 En remoto / Online</option>
                                    <option value="presential" {{ old('modality') == 'presential' ? 'selected' : '' }}>🏢 Presencial</option>
                                    <option value="hybrid" {{ old('modality') == 'hybrid' ? 'selected' : '' }}>🤝 Híbrido</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400 mb-2">Duración (Minutos)</label>
                                <input type="number" name="duration_minutes" value="{{ old('duration_minutes', 60) }}" min="1" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none">
                            </div>

                            <div class="md:col-span-2" x-data="{
                                link: '{{ old('location') }}',
                                generateJitsi() {
                                    this.link = 'https://meet.jit.si/SientiaMTX-' + Math.random().toString(36).substring(2, 12);
                                },
                                async generateMeet() {
                                    Swal.fire({
                                        title: '🌐 Creando sala Meet...',
                                        text: 'Conectando con Google Meet',
                                        allowOutsideClick: false,
                                        showConfirmButton: false,
                                        didOpen: () => Swal.showLoading(),
                                    });

                                    try {
                                        let response = await fetch('{{ route('meet.generate') }}', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                                'Accept': 'application/json',
                                            },
                                            body: JSON.stringify({ team_id: {{ $team->id ?? 'null' }} })
                                        });
                                        let data = await response.json();
                                        if (data.success && data.meet_url) {
                                            this.link = data.meet_url;
                                            Swal.close();
                                        } else {
                                            Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'No se pudo iniciar la llamada.', toast: true, position: 'top-end', timer: 4000, showConfirmButton: false });
                                        }
                                    } catch (err) {
                                        Swal.fire({ icon: 'error', title: 'Error de red', toast: true, position: 'top-end', timer: 3000, showConfirmButton: false });
                                    }
                                }
                            }">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-xs font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400">Lugar / Enlace Videollamada</label>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="generateJitsi()" class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50 px-2.5 py-1 rounded-lg transition-colors flex items-center gap-1.5 border border-emerald-100 dark:border-emerald-800/50">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/></svg>
                                            Generar Jitsi
                                        </button>
                                        <button type="button" @click="generateMeet()" class="text-[10px] font-bold text-blue-600 dark:text-blue-400 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 px-2.5 py-1 rounded-lg transition-colors flex items-center gap-1.5 border border-blue-100 dark:border-blue-800/50">
                                            <svg class="w-3.5 h-3.5" viewBox="0 0 48 48">
                                                <path fill="#FFC107" d="M17 6H11L2 22l3 5h6l9-16z"/>
                                                <path fill="#2196F3" d="M37 42H11l-9-15 4-7h26l9 16z"/>
                                                <path fill="#4CAF50" d="M15 6l9 16 9-16H15z"/>
                                            </svg>
                                            Generar Meet
                                        </button>
                                    </div>
                                </div>
                                <input type="text" name="location" x-model="link" class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none" placeholder="Ej. Sala de juntas principal o Enlace de Google Meet/Teams">
                            </div>
                        </div>
                    @elseif ($type === 'reminder')
                        <!-- RECORDATORIO ESPECÍFICO -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400 mb-2">Canales de Notificación</label>
                                <div class="flex flex-wrap gap-4 mt-2">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" name="channels[]" value="email" checked class="accent-violet-600 rounded">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">📧 Correo Electrónico</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" name="channels[]" value="push" checked class="accent-violet-600 rounded">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">🔔 Notificación en la App (Push/Nudge)</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Contexto y Vinculaciones -->
                <div class="bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 space-y-6 shadow-sm">
                    <div class="flex items-center gap-3 mb-1">
                        <div class="w-8 h-8 rounded-xl bg-violet-100 dark:bg-violet-900/40 text-violet-600 dark:text-violet-400 flex items-center justify-center shrink-0 border border-violet-200 dark:border-violet-500/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xs font-black uppercase tracking-widest text-violet-700 dark:text-violet-400">Contexto y Vinculaciones</h3>
                            <p class="text-[10px] text-gray-500 dark:text-gray-400">Asocia esta actividad a un expediente, dependencias o servicios.</p>
                        </div>
                    </div>

                    <!-- Expediente Vinculado -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400 mb-2">Expediente Vinculado</label>
                        <select name="expediente_id" id="expediente_id_select" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white transition-all cursor-pointer">
                            <option value="">(Ningún expediente)</option>
                            @foreach ($expedientes as $exp)
                                <option value="{{ $exp->id }}" {{ (old('expediente_id') ?: request('expediente_id')) == $exp->id ? 'selected' : '' }}>
                                    {{ $exp->code }} — {{ $exp->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Secondary Grid: Actividad Padre y Dependencia de Servicio -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                        <!-- Actividad Padre -->
                        <div>
                            <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-2">Actividad Padre (Dependencia)</label>
                            <select name="parent_id" id="parent_id_select" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer">
                                <option value="">(Ninguna)</option>
                                @foreach ($parentActivities as $parent)
                                    <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}
                                        data-assignee="{{ $parent->creator ? $parent->creator->name : 'Sin asignar' }}">
                                        {{ $parent->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Dependencia de Servicio (Solo si es tarea o unificado) -->
                        <div>
                            <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-2">Dependencia de Servicio</label>
                            <select name="service_id" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer">
                                <option value="">Sin dependencia externa</option>
                                @foreach ($services as $service)
                                    <option value="{{ $service->id }}" {{ old('service_id') == $service->id ? 'selected' : '' }}>
                                        {{ $service->icon }} {{ $service->name }} ({{ $service->getStatusLabel() }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Visibilidad en vistas (Efímera) -->
                <div class="bg-violet-50/50 dark:bg-violet-900/10 border border-violet-100/50 dark:border-violet-800/50 rounded-2xl p-4 transition-all">
                    <label class="relative flex items-center gap-3 cursor-pointer group w-full">
                        <input type="hidden" name="metadata[is_ephemeral]" value="0">
                        <input type="checkbox" name="metadata[is_ephemeral]" value="1" {{ old('metadata.is_ephemeral') ? 'checked' : '' }} class="accent-violet-600 rounded w-5 h-5 border-gray-300 dark:border-gray-600 focus:ring-violet-500/20">
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300">Actividad Efímera (Ocultar visualmente)</span>
                            <span class="text-[11px] text-gray-500">No aparecerá en el Kanban, Diagrama de Gantt ni en la Matriz de Eisenhower. Útil para tareas de sistema o reuniones rutinarias que no requieren seguimiento visual.</span>
                        </div>
                    </label>
                </div>

                <!-- Prioridad, Privacidad y Fechas -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-2">Prioridad</label>
                        <select name="priority" id="priority_select" required class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none cursor-pointer">
                            <option value="low" {{ old('priority', 'medium') == 'low' ? 'selected' : '' }}>Baja</option>
                            <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>Media</option>
                            <option value="high" {{ old('priority', 'medium') == 'high' ? 'selected' : '' }}>Alta</option>
                            <option value="critical" {{ old('priority', 'medium') == 'critical' ? 'selected' : '' }}>Crítica</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-2">Nivel de Privacidad</label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="relative flex cursor-pointer">
                                <input type="radio" name="visibility" value="public" class="peer sr-only" {{ old('visibility', 'public') === 'public' ? 'checked' : '' }}>
                                <div class="w-full p-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 rounded-xl peer-checked:border-violet-500 peer-checked:bg-violet-50 dark:peer-checked:bg-violet-950/30 transition-all flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-white dark:bg-gray-900 flex items-center justify-center text-violet-600 shadow-sm border border-gray-100 dark:border-gray-800">
                                        👥
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">Pública</span>
                                        <span class="text-[10px] text-gray-500">Todo el equipo</span>
                                    </div>
                                </div>
                            </label>
                            <label class="relative flex cursor-pointer">
                                <input type="radio" name="visibility" value="private" class="peer sr-only" {{ old('visibility') === 'private' ? 'checked' : '' }}>
                                <div class="w-full p-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 rounded-xl peer-checked:border-amber-500 peer-checked:bg-amber-50 dark:peer-checked:bg-amber-950/30 transition-all flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-white dark:bg-gray-900 flex items-center justify-center text-amber-600 shadow-sm border border-gray-100 dark:border-gray-800">
                                        🔒
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">Privada</span>
                                        <span class="text-[10px] text-gray-500">Solo yo</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    @if($type === 'task')
                        <!-- Eisenhower Matrix Preview -->
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold uppercase tracking-wide text-gray-600 dark:text-gray-400 mb-2">Vista Previa Eisenhower</label>
                            <div id="quadrant-preview" class="rounded-xl border p-3 text-xs hidden transition-all">
                                <span class="font-bold uppercase tracking-wider" id="qp-label"></span>
                                <span class="text-gray-500 dark:text-gray-400 ml-1 italic font-medium" id="qp-desc"></span>
                            </div>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-2">Fecha Programada</label>
                        <input type="datetime-local" name="scheduled_date" value="{{ old('scheduled_date', now()->format('Y-m-d\TH:i')) }}"
                            class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-2">Fecha de Vencimiento</label>
                        <input type="datetime-local" name="due_date" value="{{ old('due_date') }}"
                            class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 outline-none">
                    </div>

                    <!-- Timeline Lock -->
                    <div class="md:col-span-2 bg-gray-50 dark:bg-gray-800/50 p-4 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center text-red-600 dark:text-red-400 shrink-0">
                                🔒
                            </div>
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-gray-900 dark:text-white">Bloquear programación (Inamovible)</span>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400">Evita que la actividad sea desplazada o redimensionada en el Gantt de forma accidental.</span>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="is_timeline_locked" value="1" {{ old('is_timeline_locked') ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 rounded-full peer peer-focus:ring-4 peer-focus:ring-red-500/20 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-red-600"></div>
                        </label>
                    </div>
                </div>

                <!-- Asignaciones de Miembros y Grupos (Con selección avanzada de Tareas) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-4 border-t border-gray-100 dark:border-gray-800" x-data="{
                    selectAll(status) {
                        document.querySelectorAll('.user-checkbox').forEach(cb => {
                            cb.checked = status;
                            cb.dispatchEvent(new Event('change', { bubbles: true }));
                        });
                    },
                    syncGroup(groupCb) {
                        try {
                            const memberIds = JSON.parse(groupCb.dataset.members);
                            const isChecked = groupCb.checked;
                            memberIds.forEach(id => {
                                const userCb = document.getElementById('user_checkbox_' + id);
                                if (userCb) {
                                    userCb.checked = isChecked;
                                    userCb.dispatchEvent(new Event('change', { bubbles: true }));
                                }
                            });
                        } catch (err) {
                            console.error('Group sync error:', err);
                        }
                    }
                }">
                    @if ($members->count() > 0)
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    Miembros Asignados
                                </label>
                                <div class="flex gap-2">
                                    <button type="button" @click="selectAll(true)" class="text-[10px] font-black uppercase tracking-widest text-violet-600 hover:text-violet-700 dark:text-violet-400 transition-colors">
                                        Todos
                                    </button>
                                    <span class="text-gray-300 dark:text-gray-700 text-[10px]">|</span>
                                    <button type="button" @click="selectAll(false)" class="text-[10px] font-black uppercase tracking-widest text-gray-500 hover:text-gray-700 dark:text-gray-400 transition-colors">
                                        Ninguno
                                    </button>
                                </div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4 space-y-2.5 max-h-80 overflow-y-auto">
                                @foreach ($members as $member)
                                    <label class="flex items-center gap-3 p-2 rounded-xl hover:bg-white dark:hover:bg-gray-800 cursor-pointer group transition-all border border-transparent hover:border-gray-150 shadow-sm">
                                        <input type="checkbox" name="assigned_to[]" value="{{ $member->id }}"
                                            id="user_checkbox_{{ $member->id }}"
                                            {{ in_array($member->id, old('assigned_to', [])) ? 'checked' : '' }}
                                            class="user-checkbox accent-violet-600 w-5 h-5 rounded-lg border-gray-300 dark:border-gray-600 focus:ring-violet-500/20">
                                        <div class="flex flex-col min-w-0">
                                            <span class="text-sm font-bold text-gray-700 dark:text-gray-200 truncate group-hover:text-gray-900 transition-colors leading-tight">{{ $member->name }}</span>
                                            <span class="text-[10px] text-gray-500 truncate">{{ $member->email }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($groups->count() > 0)
                        <div class="space-y-3">
                            <label class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                Grupos Asignados
                            </label>
                            <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4 space-y-2.5 max-h-80 overflow-y-auto">
                                @foreach ($groups as $group)
                                    <label class="flex items-center gap-3 p-3 rounded-xl hover:bg-white dark:hover:bg-gray-800 cursor-pointer group transition-all border border-transparent hover:border-gray-150 shadow-sm">
                                        <input type="checkbox" name="assigned_groups[]" value="{{ $group->id }}"
                                            data-members="{{ json_encode($group->users->pluck('id')) }}"
                                            @change="syncGroup($el)"
                                            {{ in_array($group->id, old('assigned_groups', [])) ? 'checked' : '' }}
                                            class="group-checkbox accent-violet-600 w-5 h-5 rounded-lg border-gray-300 dark:border-gray-600 focus:ring-violet-500/20">
                                        <div class="flex flex-col min-w-0">
                                            <span class="text-sm font-bold text-gray-700 dark:text-gray-200 truncate group-hover:text-gray-900 transition-colors leading-tight">{{ $group->name }}</span>
                                            <span class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">{{ $group->users->count() }} Miembros</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Archivos Adjuntos (con Google Drive) -->
                <div class="pt-8 border-t border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-violet-100 dark:bg-violet-500/10 flex items-center justify-center text-violet-600 dark:text-violet-400">
                                📎
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Archivos Adjuntos</h3>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">Sube archivos relevantes para esta actividad.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @php
                                $isGoogleLinked = auth()->user()->teams()->where('team_id', $team->id)->wherePivotNotNull('google_token')->exists();
                            @endphp
                            @if($isGoogleLinked)
                                <button type="button" onclick="openDrivePicker()"
                                    class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-blue-600 dark:text-blue-400 text-xs font-bold px-4 py-2 rounded-xl border border-blue-200 dark:border-blue-800 transition-all shadow-sm flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M7.71 3.5L1.15 15l3.43 6 6.55-11.5H7.71zM9.73 15L6.3 21h13.12l3.43-6H9.73zM18.74 3.5l-6.55 11.5 3.43 6L22.18 9.5l-3.44-6z"/>
                                    </svg>
                                    Google Drive
                                </button>
                            @endif
                            <label class="cursor-pointer bg-violet-50 dark:bg-violet-900/20 hover:bg-violet-100 dark:hover:bg-violet-900/40 text-violet-600 dark:text-violet-400 px-4 py-2 rounded-xl text-xs font-bold transition-all border border-violet-200 dark:border-violet-500/20 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                </svg>
                                Añadir Archivo
                                <input type="file" name="attachments[]" multiple class="hidden" onchange="updateFileList(this)">
                            </label>
                        </div>
                        <input type="hidden" name="drive_attachments" id="drive_attachments_input">
                    </div>

                    <div id="file-list-preview" class="grid grid-cols-1 sm:grid-cols-2 gap-3 pb-4">
                        <!-- Lista temporal de archivos seleccionados -->
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="flex justify-end gap-3 pt-6 border-t border-gray-100 dark:border-gray-800">
                    <a href="{{ route('teams.activities.index', $team) }}"
                        class="text-sm text-gray-500 hover:text-gray-900 px-5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 transition-all font-medium">Atrás</a>
                    <button type="submit"
                        class="text-sm bg-violet-600 hover:bg-violet-500 text-white px-8 py-2.5 rounded-xl font-bold transition-all shadow-lg hover:shadow-violet-500/25">
                        Crear Actividad
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <style>
        .ts-wrapper {
            border: none !important;
            background: transparent !important;
            padding: 0 !important;
            box-shadow: none !important;
        }
        .ts-control {
            border-radius: 0.75rem !important;
            border-width: 1px !important;
            background-color: #f9fafb !important;
            border-color: #e5e7eb !important;
            padding: 0.625rem 1rem !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
            min-height: 44px !important;
            display: flex !important;
            align-items: center !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
        }
        .ts-control input { 
            font-size: 14px !important; 
            padding: 0 !important; 
            margin: 0 !important; 
            background: transparent !important; 
            border: none !important; 
            outline: none !important; 
            box-shadow: none !important;
            line-height: 1 !important;
            height: auto !important;
        }
        .ts-control input::placeholder { color: #9ca3af !important; font-weight: 500 !important; }
        
        .dark .ts-control {
            background-color: #1f2937 !important;
            border-color: #374151 !important;
            color: #f3f4f6 !important;
        }
        
        .ts-wrapper.focus .ts-control {
            border-color: #7c3aed !important;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.2) !important;
        }
        
        .ts-wrapper .clear-button { 
            right: 1rem !important; 
            top: 50% !important; 
            transform: translateY(-50%) !important; 
            font-size: 1.25rem !important;
            color: #9ca3af !important;
            opacity: 0.7 !important;
            transition: all 0.2s ease !important;
        }
        .ts-wrapper .clear-button:hover { opacity: 1 !important; color: #ef4444 !important; }
        .ts-wrapper .ts-control { padding-right: 2.5rem !important; }
        
        .ts-dropdown { 
            border-radius: 1rem !important; 
            border: 1px solid #e5e7eb !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important; 
            margin-top: 6px !important; 
            padding: 0.5rem !important; 
            z-index: 9999 !important;
        }
        .dark .ts-dropdown { background-color: #111827 !important; border-color: #374151 !important; }
        
        .ts-dropdown .option { 
            padding: 0.625rem 0.75rem !important; 
            border-radius: 0.6rem !important; 
            margin-bottom: 2px !important; 
            transition: all 0.15s ease !important;
            color: #374151 !important;
        }
        .dark .ts-dropdown .option { color: #e5e7eb !important; }
        
        .ts-dropdown .active { 
            background-color: #f5f3ff !important; 
            color: #4f46e5 !important; 
        }
        .dark .ts-dropdown .active { background-color: #4f46e5 !important; color: #ffffff !important; }
        
        #parent_id_select, #expediente_id_select { display: none; }
    </style>

    <script>
        window.applyImportData = function(data) {
            try {
                if (!data.type || !data.type.startsWith('sientia_task')) {
                    throw new Error('El formato no es un JSON de Sientia MTX válido.');
                }
                const task = data.task;
                
                document.querySelector('[name="title"]').value = task.title || '';
                
                const descEl = document.getElementById('description');
                if (descEl) {
                    if (window.EasyMDEInstances && window.EasyMDEInstances['description']) {
                        window.EasyMDEInstances['description'].value(task.description || '');
                    } else {
                        descEl.value = task.description || '';
                    }
                }
                
                if (task.priority) document.querySelector('[name="priority"]').value = task.priority;
                if (task.urgency && document.querySelector('[name="urgency"]')) {
                    document.querySelector('[name="urgency"]').value = task.urgency;
                    document.querySelector('[name="urgency"]').dispatchEvent(new Event('change'));
                }
                
                const visRadio = document.querySelector(`input[name="visibility"][value="${task.visibility}"]`);
                if (visRadio) visRadio.checked = true;

                const cogLoad = document.querySelector('[name="cognitive_load"]');
                if (cogLoad) {
                    cogLoad.value = task.cognitive_load || 1;
                    cogLoad.dispatchEvent(new Event('input'));
                }

                if (document.querySelector('[name="is_out_of_skill_tree"]'))
                    document.querySelector('[name="is_out_of_skill_tree"]').checked = !!task.is_out_of_skill_tree;
                
                if (document.querySelector('[name="is_backstage"]'))
                    document.querySelector('[name="is_backstage"]').checked = !!task.is_backstage;

                document.querySelector('[name="priority"]').dispatchEvent(new Event('change'));

                Swal.fire({
                    icon: 'success',
                    title: '¡Datos inyectados!',
                    text: 'El formulario se ha rellenado con éxito.',
                    toast: true,
                    position: 'top-end',
                    timer: 3000,
                    showConfirmButton: false
                });
            } catch (err) {
                Swal.fire('Error de Importación', err.message, 'error');
            }
        };

        window.importFromClipboard = async function() {
            try {
                const text = await navigator.clipboard.readText();
                if (!text) throw new Error('El portapapeles está vacío.');
                const data = JSON.parse(text);
                window.applyImportData(data);
            } catch (err) {
                Swal.fire('Error al pegar', 'Asegúrate de haber copiado el JSON correcto. ' + err.message, 'error');
            }
        };

        window.importTask = function() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'application/json';
            input.onchange = e => {
                const file = e.target.files[0];
                const reader = new FileReader();
                reader.onload = event => {
                    try {
                        const data = JSON.parse(event.target.result);
                        window.applyImportData(data);
                    } catch (err) {
                        Swal.fire('Error', 'Archivo JSON corrupto.', 'error');
                    }
                };
                reader.readAsText(file);
            };
            input.click();
        };

        document.addEventListener('DOMContentLoaded', function() {
            // --- Eisenhower Matrix Preview ---
            const quadrantData = {
                1: { label: 'Urgente e Importante', description: 'Hacer de inmediato' },
                2: { label: 'Importante pero No Urgente', description: 'Planificar/Programar' },
                3: { label: 'Urgente pero No Importante', description: 'Delegar' },
                4: { label: 'Ni Urgente ni Importante', description: 'Eliminar/Posponer' }
            };
            const priorityEl = document.querySelector('[name="priority"]');
            const urgencyEl = document.querySelector('[name="urgency"]');
            const preview = document.getElementById('quadrant-preview');
            const highLevels = ['high', 'critical'];

            const qColors = {
                1: { border: 'border-red-200 dark:border-red-700', bg: 'bg-red-50 dark:bg-red-950/30', text: 'text-red-600 dark:text-red-300' },
                2: { border: 'border-blue-200 dark:border-blue-700', bg: 'bg-blue-50 dark:bg-blue-950/30', text: 'text-blue-600 dark:text-blue-300' },
                3: { border: 'border-amber-200 dark:border-amber-700', bg: 'bg-amber-50 dark:bg-amber-950/30', text: 'text-amber-600 dark:text-amber-300' },
                4: { border: 'border-gray-200 dark:border-gray-700', bg: 'bg-gray-50 dark:bg-gray-800', text: 'text-gray-600 dark:text-gray-300' },
            };

            function updatePreview() {
                if (!priorityEl || !urgencyEl || !preview) return;
                const imp = highLevels.includes(priorityEl.value);
                const urg = highLevels.includes(urgencyEl.value);
                let q = 4;
                if (imp && urg) q = 1;
                else if (imp) q = 2;
                else if (urg) q = 3;

                const cfg = qColors[q];
                preview.className = `rounded-xl border p-3 text-xs transition-all shadow-sm dark:shadow-none ${cfg.border} ${cfg.bg}`;
                preview.classList.remove('hidden');
                document.getElementById('qp-label').className = `font-bold uppercase tracking-wider ${cfg.text}`;
                document.getElementById('qp-label').textContent = `Q${q}: ${quadrantData[q].label}`;
                document.getElementById('qp-desc').className = `text-gray-500 dark:text-gray-400 ml-1 italic font-medium`;
                document.getElementById('qp-desc').textContent = `— ${quadrantData[q].description}`;
            }

            if (priorityEl && urgencyEl) {
                priorityEl.addEventListener('change', updatePreview);
                urgencyEl.addEventListener('change', updatePreview);
                updatePreview();
            }

            // --- TomSelect for Expedientes ---
            const expedSelectEl = document.getElementById('expediente_id_select');
            if (expedSelectEl) {
                new TomSelect("#expediente_id_select", {
                    plugins: ['clear_button'],
                    create: false,
                    sortField: { field: "text", direction: "asc" },
                    placeholder: 'Buscar expediente...',
                    allowEmptyOption: true,
                    render: {
                        option: function(data, escape) {
                            if (!data.value) return '<div class="text-gray-400 italic py-1 px-2 text-sm">' + escape(data.text) + '</div>';
                            const p = data.text.split('—');
                            const code = p[0].trim();
                            const title = p.length > 1 ? p.slice(1).join('—').trim() : code;
                            return '<div style="display:flex; align-items:center; gap:12px; padding:2px 4px;">' +
                                '<div style="flex-shrink:0; min-width:85px; height:24px; display:flex; align-items:center; justify-content:center; background:rgba(79,70,229,0.08); border:1px solid rgba(79,70,229,0.15); border-radius:6px; font-size:9px; font-weight:900; font-family:monospace; color:#4f46e5;">' + escape(code) + '</div>' +
                                '<div style="font-size:13px; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">' + escape(title) + '</div>' +
                            '</div>';
                        },
                        item: function(data, escape) {
                            if (!data.value) return '<div class="text-gray-500 font-bold text-sm">' + escape(data.text) + '</div>';
                            const p = data.text.split('—');
                            const code = p[0].trim();
                            const title = p.length > 1 ? p.slice(1).join('—').trim() : code;
                            return '<div style="display:flex; align-items:center; gap:8px;">' +
                                '<span style="flex-shrink:0; display:inline-flex; align-items:center; height:18px; padding:0 6px; background:rgba(79,70,229,0.1); color:#4f46e5; border-radius:4px; font-size:9px; font-weight:900; font-family:monospace;">' + escape(code) + '</span>' +
                                '<span style="font-size:13px; font-weight:700;">' + escape(title) + '</span>' +
                            '</div>';
                        }
                    }
                });
            }

            // --- TomSelect for Searchable Dependencies ---
            const parenSelectEl = document.getElementById('parent_id_select');
            if (parenSelectEl) {
                new TomSelect("#parent_id_select", {
                    create: false,
                    sortField: { field: "text", direction: "asc" },
                    placeholder: 'Buscar actividad padre...',
                    render: {
                        option: function(data, escape) {
                            return '<div class="flex items-center gap-3">' +
                                '<div class="w-9 h-9 rounded-xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400 shrink-0 border border-gray-200/50 dark:border-gray-700/50">' +
                                    '<span class="text-[9px] font-mono font-black">#' + escape(data.value) + '</span>' +
                                '</div>' +
                                '<div class="flex flex-col min-w-0">' +
                                    '<span class="font-bold text-gray-900 dark:text-white truncate text-xs">' + escape(data.text) + '</span>' +
                                    '<span class="text-[10px] text-gray-700 dark:text-gray-200 font-black uppercase tracking-widest mt-0.5 flex items-center gap-1.5">' + 
                                        '<span class="w-1.5 h-1.5 rounded-full bg-violet-400"></span>' +
                                        escape(data.assignee) + 
                                    '</span>' +
                                '</div>' +
                            '</div>';
                        },
                        item: function(data, escape) {
                            return '<div class="flex items-center gap-2">' + 
                                '<span class="text-[10px] font-mono font-bold text-violet-500 bg-violet-50 dark:bg-violet-900/30 px-1.5 py-0.5 rounded">#' + escape(data.value) + '</span>' +
                                '<span class="font-medium text-gray-900 dark:text-white">' + escape(data.text) + '</span>' +
                                '<span class="text-[9px] text-gray-500 bg-gray-100 dark:bg-gray-800 dark:text-gray-400 px-1.5 py-0.5 rounded border border-gray-200 dark:border-gray-700 font-black uppercase tracking-tighter">@' + escape(data.assignee) + '</span>' +
                            '</div>';
                        }
                    }
                });
            }
        });

        let selectedDriveFiles = [];

        function openDrivePicker(folderId = 'root') {
            Swal.fire({
                title: 'Google Drive',
                html: `
                    <div class="flex flex-col gap-4">
                        <div id="drive-contents" class="max-h-64 overflow-y-auto flex flex-col gap-1 text-left">
                            <div class="flex items-center justify-center py-8">
                                <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                `,
                width: '32rem',
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText: 'Cerrar',
                didOpen: () => {
                    loadDriveFolder(folderId);
                },
                background: document.documentElement.classList.contains('dark') ? '#111827' : '#fff',
                color: document.documentElement.classList.contains('dark') ? '#fff' : '#111827'
            });
        }

        function loadDriveFolder(folderId) {
            const container = document.getElementById('drive-contents');
            const teamId = '{{ $team->id }}';
            
            fetch(`{{ route('google.drive.list') }}?team_id=${teamId}&folderId=${folderId}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.files) {
                        container.innerHTML = '<p class="text-center py-4 text-gray-500">No se pudieron cargar los archivos.</p>';
                        return;
                    }

                    container.innerHTML = '';
                    
                    if (folderId !== 'root') {
                        const backBtn = document.createElement('button');
                        backBtn.className = 'p-2 text-blue-600 font-bold text-sm mb-2';
                        backBtn.innerHTML = '⬅️ Volver';
                        backBtn.onclick = () => loadDriveFolder('root');
                        container.appendChild(backBtn);
                    }

                    data.files.forEach(file => {
                        const isFolder = file.mimeType === 'application/vnd.google-apps.folder';
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'flex items-center justify-between p-3 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-xl w-full text-left';
                        btn.innerHTML = `
                            <div class="flex items-center gap-3">
                                <span>${isFolder ? '📁' : '📄'}</span>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-xs font-bold truncate">${file.name}</span>
                                    <span class="text-[9px] text-gray-400">${file.mimeType.split('.').pop()}</span>
                                </div>
                            </div>
                        `;
                        btn.onclick = () => {
                            if (isFolder) {
                                loadDriveFolder(file.id);
                            } else {
                                selectDriveFile(file);
                            }
                        };
                        container.appendChild(btn);
                    });
                });
        }

        function selectDriveFile(file) {
            if (!selectedDriveFiles.some(f => f.id === file.id)) {
                selectedDriveFiles.push(file);
                updateFileListDisplays();
                Swal.close();
            } else {
                Swal.fire('Info', 'Este archivo ya está seleccionado', 'info');
            }
        }

        function updateFileListDisplays() {
            const driveInput = document.getElementById('drive_attachments_input');
            driveInput.value = JSON.stringify(selectedDriveFiles);
            renderFilesUI(document.querySelector('input[name="attachments[]"]'));
        }

        window.updateFileList = function(input) {
            renderFilesUI(input);
        }

        function renderFilesUI(fileInput) {
            const list = document.getElementById('file-list-preview');
            list.innerHTML = '';

            // Drive Files
            selectedDriveFiles.forEach((file, index) => {
                const div = document.createElement('div');
                div.className = 'flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-800';
                div.innerHTML = `
                    <div class="flex items-center gap-3 overflow-hidden">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-800 flex items-center justify-center text-blue-600">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M7.71 3.5L1.15 15l3.43 6 6.55-11.5H7.71zM9.73 15L6.3 21h13.12l3.43-6H9.73zM18.74 3.5l-6.55 11.5 3.43 6L22.18 9.5l-3.44-6z"/>
                            </svg>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="text-xs font-bold text-blue-700 dark:text-blue-300 truncate">${file.name}</span>
                            <span class="text-[9px] text-blue-400 uppercase font-bold">Google Drive</span>
                        </div>
                    </div>
                    <button type="button" onclick="removeDriveFile(${index})" class="text-red-500 hover:text-red-700 p-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                `;
                list.appendChild(div);
            });

            // Local Files
            if (fileInput && fileInput.files.length > 0) {
                Array.from(fileInput.files).forEach((file, fileIndex) => {
                    const isImage = file.type.startsWith('image/');
                    const div = document.createElement('div');
                    div.className = 'flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-700/50';
                    
                    let imagePreview = '';
                    if (isImage) {
                        const objectUrl = URL.createObjectURL(file);
                        imagePreview = `<div class="w-8 h-8 rounded-lg overflow-hidden shrink-0"><img src="${objectUrl}" class="w-full h-full object-cover"></div>`;
                    } else {
                        imagePreview = `<div class="w-8 h-8 rounded-lg bg-white dark:bg-gray-900 flex items-center justify-center text-gray-400 font-mono text-[9px] shrink-0">${file.name.split('.').pop().toUpperCase()}</div>`;
                    }

                    div.innerHTML = `
                        <div class="flex items-center gap-3 overflow-hidden">
                            ${imagePreview}
                            <div class="flex flex-col min-w-0">
                                <span class="text-xs font-bold text-gray-700 dark:text-gray-200 truncate">${file.name}</span>
                                <span class="text-[10px] text-gray-400">${(file.size / 1024).toFixed(1)} KB</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            ${isImage ? `
                            <button type="button" onclick="editLocalFile(${fileIndex})" class="text-violet-500 hover:text-violet-700 p-1 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 transition-all hover:bg-violet-50" title="Editar Imagen">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </button>
                            ` : ''}
                            <button type="button" onclick="removeLocalFile(${fileIndex})" class="text-red-500 hover:text-red-700 p-1 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 transition-all hover:bg-red-50" title="Eliminar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    `;
                    list.appendChild(div);
                });
            }
        }

        window.removeDriveFile = function(index) {
            selectedDriveFiles.splice(index, 1);
            document.getElementById('drive_attachments_input').value = JSON.stringify(selectedDriveFiles);
            renderFilesUI(document.querySelector('input[name="attachments[]"]'));
        }

        window.removeLocalFile = function(index) {
            const input = document.querySelector('input[name="attachments[]"]');
            const dataTransfer = new DataTransfer();
            Array.from(input.files).forEach((file, i) => {
                if (i !== index) dataTransfer.items.add(file);
            });
            input.files = dataTransfer.files;
            renderFilesUI(input);
        }

        window.editLocalFile = function(index) {
            const input = document.querySelector('input[name="attachments[]"]');
            const file = input.files[index];
            if (typeof window.openGlobalImageEditor === 'function') {
                window.openGlobalImageEditor(file, (editedFile) => {
                    const dataTransfer = new DataTransfer();
                    Array.from(input.files).forEach((f, i) => {
                        if (i === index) dataTransfer.items.add(editedFile);
                        else dataTransfer.items.add(f);
                    });
                    input.files = dataTransfer.files;
                    renderFilesUI(input);
                });
            }
        }
    </script>
    @endpush

    {{-- BARRA FLOTANTE DE ACCIONES RÁPIDAS --}}
    <div id="activity-create-floating-bar"
         x-data="floatingDraggable"
         @mousedown="startDrag"
         @touchstart.passive="startDrag"
         @window:mousemove="drag"
         @window:touchmove.passive="drag"
         @window:mouseup="stopDrag"
         @window:touchend="stopDrag"
         class="fixed bottom-6 left-1/2 -translate-x-1/2 translate-y-4 z-[800] flex items-center gap-2 px-4 py-2.5 bg-white/93 dark:bg-gray-900/93 backdrop-blur-xl border border-gray-100 dark:border-gray-800 rounded-2xl shadow-2xl opacity-0 pointer-events-none transition-all duration-300 whitespace-nowrap cursor-move"
         :class="isDragging ? 'scale-105 shadow-[0_20px_50px_rgba(0,0,0,0.2)]' : ''">

        <a href="{{ route('teams.activities.index', $team) }}"
           style="display:flex;align-items:center;gap:0.375rem;font-size:0.75rem;font-weight:700;color:#6b7280;padding:0.375rem 0.75rem;border-radius:0.625rem;text-decoration:none;transition:all 0.15s ease;"
           onmouseover="this.style.color='#7c3aed';this.style.background='#f5f3ff'"
           onmouseout="this.style.color='#6b7280';this.style.background='transparent'">
            <svg style="width:1rem;height:1rem;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            <span>Volver</span>
        </a>

        <div style="width:1px;height:1.25rem;background:#e5e7eb;flex-shrink:0"></div>

        <span style="font-size:0.75rem;font-weight:900;color:#1f2937;max-width:200px;overflow:hidden;text-overflow:ellipsis;" class="dark:text-gray-300">
            Crear Actividad
        </span>

        <div style="width:1px;height:1.25rem;background:#e5e7eb;flex-shrink:0"></div>

        <button type="button"
                onclick="document.getElementById('create-activity-form').submit()"
           style="display:flex;align-items:center;gap:0.375rem;font-size:0.75rem;font-weight:700;color:#fff;background:#7c3aed;padding:0.375rem 0.75rem;border-radius:0.625rem;text-decoration:none;transition:background 0.15s ease;border:none;cursor:pointer;"
           onmouseover="this.style.background='#6d28d9'"
           onmouseout="this.style.background='#7c3aed'">
            <svg style="width:1rem;height:1rem;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            <span>Guardar</span>
        </button>
    </div>

    <script>
        (function() {
            const bar = document.getElementById('activity-create-floating-bar');
            if (bar) {
                const checkScroll = (e) => {
                    const target = e.target === document ? document.documentElement : e.target;
                    const scrollY = target.scrollTop || 0;
                    const finalScroll = scrollY || window.scrollY || 0;
                    
                    if (finalScroll > 150) {
                        bar.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none');
                        bar.classList.add('opacity-100', 'translate-y-0', 'pointer-events-auto');
                    } else {
                        bar.classList.add('opacity-0', 'translate-y-4', 'pointer-events-none');
                        bar.classList.remove('opacity-100', 'translate-y-0', 'pointer-events-auto');
                    }
                };
                window.addEventListener('scroll', checkScroll, { passive: true, capture: true });
            }
        })();
    </script>
</x-app-layout>
