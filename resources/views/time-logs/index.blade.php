<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-6">
            <div class="flex items-start gap-4 min-w-0 flex-1">
                <a href="{{ route('teams.index') }}"
                    class="mt-1 p-2.5 bg-gray-50 dark:bg-gray-800/50 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-2xl transition-all shadow-sm border border-gray-100 dark:border-gray-700/50 shrink-0"
                    title="{{ __('navigation.back') ?? 'Volver' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div class="min-w-0 flex-1">
                    @include('teams.partials.breadcrumb')
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight">
                        Escritorio: Resiliencia Colectiva
                    </h1>
                </div>
            </div>

            <div class="flex items-center gap-3 shrink-0">
                <!-- User Level / XP Badge -->
                <div class="flex items-center gap-2 px-4 py-2 bg-amber-50 dark:bg-amber-900/30 border border-amber-100 dark:border-amber-800 rounded-2xl shadow-sm">
                    <span class="text-amber-600 dark:text-amber-400 font-black text-xs uppercase tracking-widest">Nivel 1</span>
                    <div class="w-20 h-1.5 bg-amber-200 dark:bg-amber-800 rounded-full overflow-hidden">
                        <div class="h-full bg-amber-500" style="width: 25%"></div>
                    </div>
                </div>
                @include('teams.partials.header-actions')
            </div>
        </div>

        <!-- View Switcher Sub-Header -->
        <div class="mt-8 mb-4 flex w-full">
            @include('teams.partials.view-switcher')
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Gamification Summary Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Energy Bar (Semáforo de Bienestar) -->
                <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-3xl p-6 border border-gray-100 dark:border-gray-800 relative group">
                    <div class="absolute top-4 right-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-emerald-500 opacity-20 group-hover:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <p class="text-xs font-black uppercase tracking-widest text-gray-400 mb-4">Estado de Energía</p>
                    <div class="relative pt-1">
                        <div class="flex mb-2 items-center justify-between">
                            <div>
                                <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-emerald-600 bg-emerald-200">
                                    {{ auth()->user()->energy_level }}% - Óptimo
                                </span>
                            </div>
                        </div>
                        <div class="overflow-hidden h-4 mb-4 text-xs flex rounded-full bg-emerald-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                            <div style="width:{{ auth()->user()->energy_level }}%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-emerald-500 shadow-md shadow-emerald-500/20 transition-all duration-1000"></div>
                        </div>
                    </div>
                    <p class="text-[10px] text-gray-500">Recarga completando tareas de baja carga cognitiva.</p>
                </div>

                <!-- Kudos Received -->
                <div x-data="{ open: false }" class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-3xl p-6 border border-gray-100 dark:border-gray-800 relative group cursor-pointer hover:shadow-violet-500/5 transition-all" @click="open = true">
                    <div class="absolute top-4 right-4 animate-bounce">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-violet-500 opacity-20 group-hover:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </div>
                    <p class="text-xs font-black uppercase tracking-widest text-gray-400 mb-2">Kudos Recibidos</p>
                    <div class="flex items-baseline gap-2">
                        <h3 class="text-4xl font-black text-violet-600 dark:text-violet-400 tabular-nums">{{ auth()->user()->receivedKudos()->count() }}</h3>
                        <span class="text-xs font-bold text-gray-500 uppercase">Reconocimientos</span>
                    </div>
                    <div class="mt-4">
                        <button type="button" class="w-full py-2 px-4 bg-violet-600 hover:bg-violet-500 text-white text-[10px] font-black uppercase tracking-widest rounded-xl transition-all shadow-lg hover:shadow-violet-500/25">
                            Enviar Apoyo / Kudo
                        </button>
                    </div>

                    <!-- Kudo Modal -->
                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm"
                         style="display: none;"
                         @click.away="open = false">
                        <div class="bg-white dark:bg-gray-900 rounded-3xl overflow-hidden shadow-2xl transform transition-all sm:max-w-md w-full border border-gray-200 dark:border-gray-800" @click.stop>
                            <div class="p-6 border-b border-gray-100 dark:border-gray-800 bg-violet-50/50 dark:bg-violet-950/30">
                                <h3 class="text-lg font-black text-gray-900 dark:text-white uppercase tracking-widest flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                    Enviar Kudo
                                </h3>
                                <p class="text-xs text-gray-500 mt-1">Reconoce el esfuerzo horizontal de un compañero.</p>
                            </div>
                            <form action="{{ route('kudos.store', $team) }}" method="POST" class="p-6 space-y-4">
                                @csrf
                                <div>
                                    <label class="block text-[10px] font-black uppercase text-gray-400 mb-2">¿Para quién?</label>
                                    <select name="receiver_id" required class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-violet-500 transition-all text-gray-900 dark:text-white">
                                        <option value="">Selecciona compañero...</option>
                                        @foreach($teamMembers as $member)
                                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black uppercase text-gray-400 mb-2">Categoría del Reconocimiento</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        @foreach(['Ayuda Técnica', 'Apoyo Moral', 'Innovación', 'Resiliencia', 'Gestión Caos', 'Compañerismo'] as $cat)
                                            <label class="cursor-pointer">
                                                <input type="radio" name="type" value="{{ $cat }}" class="peer sr-only" required @if($loop->first) checked @endif>
                                                <div class="p-2 border border-gray-100 dark:border-gray-700 rounded-lg text-center text-[10px] font-bold text-gray-600 dark:text-gray-400 peer-checked:bg-violet-600 peer-checked:text-white peer-checked:border-violet-600 transition-all uppercase tracking-tighter">
                                                    {{ $cat }}
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black uppercase text-gray-400 mb-2">Mensaje (Opcional)</label>
                                    <textarea name="message" rows="2" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-violet-500 transition-all text-gray-900 dark:text-white" placeholder="Gracias por echarme un cable con..."></textarea>
                                </div>
                                <div class="flex gap-3 pt-2">
                                    <button type="button" @click="open = false" class="flex-1 px-4 py-3 bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white text-xs font-black uppercase rounded-xl hover:bg-gray-200 transition-all">Cancelar</button>
                                    <button type="submit" class="flex-2 px-8 py-3 bg-violet-600 hover:bg-violet-500 text-white text-xs font-black uppercase rounded-xl transition-all shadow-lg shadow-violet-500/25">Lanzar Kudo ✨</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Resilience Points -->
                <div class="lg:col-span-1 bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-3xl p-6 border border-gray-100 dark:border-gray-800 relative group">
                    <div class="absolute top-4 right-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-violet-500 opacity-20 group-hover:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                        </svg>
                    </div>
                    <p class="text-xs font-black uppercase tracking-widest text-gray-400 mb-2">Puntos de Resiliencia</p>
                    <h3 class="text-4xl font-black text-violet-600 dark:text-violet-400 tabular-nums">
                        {{ auth()->user()->resilience_points }}
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">Has superado {{ auth()->user()->resilience_points / 10 }} retos fuera de tu Skill Tree.</p>
                </div>

                <!-- Total Experience / Citizens Empoderados -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-3xl p-6 border border-gray-100 dark:border-gray-800 relative group flex items-center justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-widest text-gray-400 mb-2">Impacto en Ciudadanía</p>
                        <h3 class="text-4xl font-black text-emerald-600 dark:text-emerald-400 tabular-nums">
                            {{ auth()->user()->experience_points }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">Ciudadanos empoderados a través de tu acción tecnológica.</p>
                    </div>
                    <div class="p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl border border-emerald-100 dark:border-emerald-800/50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                </div>
            </div>
            <!-- Dynamic Skill Tree section -->
            <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-3xl border border-gray-100 dark:border-gray-800 flex flex-col mb-6">
                <div class="p-4 border-b border-gray-50 dark:border-gray-800 flex items-center justify-between bg-violet-50/10 dark:bg-violet-950/10">
                    <div class="flex flex-col">
                        <h4 class="font-black text-gray-900 dark:text-gray-100 flex items-center gap-2 uppercase tracking-widest text-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            Especialización (Skill Tree)
                        </h4>
                    </div>
                </div>
                <div class="p-6">
                    <div class="flex flex-wrap justify-between gap-y-6 gap-x-2">
                        @php
                            $allSkills = \App\Models\Skill::all();
                            $userSkills = auth()->user()->skills->keyBy('id');
                            $levelThresholds = [1 => 0, 2 => 50, 3 => 150, 4 => 350, 5 => 750];
                        @endphp

                        @foreach($allSkills as $skill)
                            @php
                                $uSkill = $userSkills->get($skill->id);
                                $level = $uSkill ? $uSkill->pivot->level : 1;
                                $xp = $uSkill ? $uSkill->pivot->total_xp : 0;
                                $nextThreshold = $levelThresholds[min(5, $level + 1)];
                                $prevThreshold = $levelThresholds[$level];
                                $progress = $level >= 5 ? 100 : (($xp - $prevThreshold) / max(1, $nextThreshold - $prevThreshold)) * 100;
                            @endphp
                            <div class="flex flex-col items-center group w-[120px] shrink-0">
                                <div class="relative w-16 h-16 flex items-center justify-center mb-2">
                                    <svg class="w-full h-full transform -rotate-90">
                                        <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="4" fill="transparent" class="text-gray-100 dark:text-gray-800" />
                                        <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="4" fill="transparent" 
                                            stroke-dasharray="176" 
                                            stroke-dashoffset="{{ 176 - (176 * $progress / 100) }}"
                                            class="text-violet-500 transition-all duration-1000 ease-out" />
                                    </svg>
                                    <div class="absolute inset-0 flex items-center justify-center flex-col">
                                        <span class="text-sm font-black text-gray-900 dark:text-white">{{ $level }}</span>
                                    </div>
                                </div>
                                <h5 class="text-[9px] font-black text-gray-900 dark:text-gray-100 uppercase tracking-tighter text-center leading-tight h-5 flex items-center">{{ $skill->name }}</h5>
                                <div class="mt-1 text-[8px] font-black text-violet-500 bg-violet-50 dark:bg-violet-900/30 px-2 py-0.5 rounded-full border border-violet-100 dark:border-violet-800">
                                    {{ $xp }} XP
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Full Width Territorial Map -->
            <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-3xl border border-gray-200 dark:border-gray-800 flex flex-col mb-6">
                <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-emerald-50/5 dark:bg-emerald-950/5">
                    <div class="flex flex-col">
                        <h4 class="font-black text-gray-900 dark:text-gray-100 flex items-center gap-2 uppercase tracking-widest text-xs">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Mapa de Impacto (Resiliencia)
                        </h4>
                    </div>
                    
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = true" class="px-3 py-1.5 bg-white dark:bg-gray-800 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 text-[10px] font-black uppercase rounded-xl border border-emerald-200 dark:border-emerald-700 transition-all flex items-center gap-2 shadow-sm">
                            <span class="animate-pulse">📍</span> Mi Zona
                        </button>

                        <!-- User Zone Settings Modal -->
                        <div x-show="open" class="fixed inset-0 z-[100] overflow-y-auto" style="display: none;">
                            <div class="flex items-center justify-center min-h-screen px-4 py-6 text-center sm:block sm:p-0">
                                <div class="fixed inset-0 transition-opacity bg-gray-900/60 backdrop-blur-sm" @click="open = false"></div>
                                <div class="inline-block w-full max-w-lg p-8 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-900 rounded-3xl shadow-2xl border border-gray-200 dark:border-gray-800 relative z-[101]">
                                    <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-widest mb-2 flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        </svg>
                                        Tu Área de Dinamización
                                    </h3>
                                    <p class="text-xs text-gray-500 mb-6">Define tu zona de acción para que el mapa de calor del equipo refleje tu presencia.</p>
                                    
                                    <form action="{{ route('user.update-zone') }}" method="POST" class="space-y-6">
                                        @csrf @method('PATCH')
                                        <div>
                                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-2">Nombre del Área</label>
                                            <input type="text" name="working_area_name" value="{{ auth()->user()->working_area_name }}" placeholder="Ej: Llano de Zafarraya" 
                                                   class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl px-4 py-3 text-sm focus:border-emerald-500 outline-none transition-all text-gray-900 dark:text-white" required>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-[10px] font-black uppercase text-gray-400 mb-2">Latitud</label>
                                                <input type="number" name="location_lat" step="0.00000001" value="{{ auth()->user()->location_lat }}" placeholder="36.987..." 
                                                       class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl px-4 py-3 text-sm focus:border-emerald-500 outline-none transition-all text-gray-900 dark:text-white" required>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-black uppercase text-gray-400 mb-2">Longitud</label>
                                                <input type="number" name="location_lng" step="0.00000001" value="{{ auth()->user()->location_lng }}" placeholder="-4.123..." 
                                                       class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl px-4 py-3 text-sm focus:border-emerald-500 outline-none transition-all text-gray-900 dark:text-white" required>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-2 flex justify-between">
                                                Radio de Influencia (km)
                                                <span class="text-emerald-500 font-bold" x-text="$refs.radius.value + ' km'"></span>
                                            </label>
                                            <input type="range" name="impact_radius" x-ref="radius" min="1" max="50" value="{{ auth()->user()->impact_radius ?? 10 }}" 
                                                   class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-emerald-500">
                                        </div>
                                        <div class="flex gap-4 pt-4">
                                            <button type="button" @click="open = false" class="flex-1 px-6 py-4 bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white text-xs font-black uppercase rounded-2xl hover:bg-gray-200 transition-all">Cancelar</button>
                                            <button type="submit" class="flex-2 px-8 py-4 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black uppercase rounded-2xl transition-all shadow-lg shadow-emerald-500/25">Guardar Zona 📍</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="relative h-[400px] w-full z-0">
                    <!-- Interactive Heatmap Container -->
                    <div id="resilience-heatmap" class="absolute inset-0 z-0"></div>
                    
                    <!-- Heatmap Overlay Info (Floating) -->
                    <div class="absolute bottom-6 left-6 z-10 bg-white/80 dark:bg-gray-900/80 backdrop-blur-md px-6 py-4 rounded-3xl border border-white/20 shadow-2xl">
                             <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1 italic">Intensidad de Resiliencia</p>
                             <div class="flex items-center gap-2">
                                <div class="w-24 h-2 rounded-full bg-gradient-to-r from-blue-500 via-emerald-500 to-red-500"></div>
                                <span class="text-[8px] font-black text-gray-500 uppercase">Baja → Extrema</span>
                             </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Kudos Received Detail -->
                <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-3xl border border-gray-100 dark:border-gray-800">
                    <div class="p-6 border-b border-gray-50 dark:border-gray-800 flex items-center justify-between bg-rose-50/30 dark:bg-rose-950/20">
                        <h4 class="font-black text-gray-900 dark:text-gray-100 flex items-center gap-2">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                             </svg>
                             Apoyo de la Red (Kudos)
                        </h4>
                        <span class="text-xs font-black text-rose-600 dark:text-rose-400 uppercase tracking-widest">{{ auth()->user()->receivedKudos()->count() }} Recibidos</span>
                    </div>
                    <div class="p-6 space-y-4 max-h-[500px] overflow-y-auto no-scrollbar">
                        @forelse(auth()->user()->receivedKudos()->with('sender')->orderBy('created_at', 'desc')->get() as $kudo)
                            <div class="flex items-start gap-4 p-4 rounded-2xl bg-gray-50 dark:bg-gray-800/50 border border-transparent hover:border-rose-200 dark:hover:border-rose-800 transition-all">
                                <div class="w-10 h-10 rounded-full bg-rose-100 dark:bg-rose-900/30 text-rose-600 flex items-center justify-center shrink-0 text-xs font-black">
                                    {{ substr($kudo->sender->name, 0, 2) }}
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $kudo->sender->name }}</span>
                                        <span class="px-2 py-0.5 bg-white dark:bg-gray-700 text-rose-600 text-[8px] font-black uppercase rounded-full border border-rose-100 dark:border-rose-800">{{ $kudo->type }}</span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1 italic">"{{ $kudo->message ?? 'Sin mensaje' }}"</p>
                                    <span class="text-[9px] text-gray-400 mt-2 uppercase font-black">{{ $kudo->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-12">
                                <p class="text-sm text-gray-500 italic">Tu red social está madurando... ¡lanza tú un kudo para empezar!</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Recent Achievements (Logs) -->
                <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-3xl border border-gray-100 dark:border-gray-800">
                    <div class="p-6 border-b border-gray-50 dark:border-gray-800 flex items-center justify-between bg-violet-50/30 dark:bg-violet-950/20">
                        <h4 class="font-black text-gray-900 dark:text-gray-100 flex items-center gap-2">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                             </svg>
                             Retos Alcanzados
                        </h4>
                    </div>
                    <div class="p-6 space-y-4 max-h-[500px] overflow-y-auto no-scrollbar">
                        @forelse(auth()->user()->gamificationLogs()->orderBy('created_at', 'desc')->limit(10)->get() as $log)
                            <div class="flex items-start gap-3 p-3 rounded-2xl bg-gray-50 dark:bg-gray-800/50 border border-transparent hover:border-violet-200 dark:hover:border-violet-800 transition-all">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 {{ $log->type === 'resilience' ? 'bg-violet-100 text-violet-600' : 'bg-emerald-100 text-emerald-600' }}">
                                    <span class="text-[10px] font-black">+{{ $log->points }}</span>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-xs font-bold text-gray-900 dark:text-gray-100 truncate">{{ $log->description }}</span>
                                    <span class="text-[10px] text-gray-500 uppercase font-black">{{ $log->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <p class="text-sm text-gray-500 italic">Completando tu primera misión...</p>
                            </div>
                        @endforelse
                    </div>
                </div>
                                <div class="w-24 h-2 rounded-full bg-gradient-to-r from-blue-500 via-emerald-500 to-red-500"></div>
                                <span class="text-[8px] font-black text-gray-500 uppercase">Baja → Extrema</span>
                             </div>
                        </div>
                    </div>
                    
                    <div class="p-6 bg-gray-50/50 dark:bg-gray-800/30 border-t border-gray-100 dark:border-gray-800">
                        <div class="flex items-center justify-around text-center">
                            @foreach($team->members->whereNotNull('location_lat')->take(4) as $member)
                                <div class="group cursor-pointer">
                                    <div class="w-10 h-10 rounded-full border-2 border-emerald-500 p-0.5 mx-auto mb-2 group-hover:scale-110 transition-transform">
                                        <div class="w-full h-full rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-[10px] font-black">
                                            {{ substr($member->name, 0, 2) }}
                                        </div>
                                    </div>
                                    <p class="text-[9px] font-black text-gray-900 dark:text-white uppercase truncate w-20">{{ $member->name }}</p>
                                    <p class="text-[8px] text-emerald-500 font-bold uppercase truncate w-20">{{ $member->working_area_name }}</p>
                                </div>
                            @endforeach
                            @if($team->members->whereNotNull('location_lat')->count() > 4)
                                <div class="flex flex-col items-center">
                                    <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400 text-xs font-black">
                                        +{{ $team->members->whereNotNull('location_lat')->count() - 4 }}
                                    </div>
                                    <p class="text-[9px] font-black text-gray-500 uppercase mt-2">Más áreas</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pre-existing statistics relocated -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pt-4">
                <!-- Tasks Accounting Table -->
                <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-3xl border border-gray-100 dark:border-gray-800">
                    <div class="p-6 border-b border-gray-50 dark:border-gray-800 flex items-center justify-between">
                        <h4 class="font-black text-gray-900 dark:text-gray-100">Contabilidad de Esfuerzo</h4>
                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Tiempos Registrados</div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 dark:bg-gray-950/50">
                                <tr>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">{{ __('navigation.task_list') }}</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-right">{{ __('tasks.total_time') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                @forelse($tasks as $task)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $task->title }}</span>
                                                    @if($task->is_out_of_skill_tree)
                                                        <span class="px-1.5 py-0.5 bg-violet-100 dark:bg-violet-900/30 text-violet-600 text-[8px] font-black uppercase rounded shadow-sm border border-violet-200">Resiliencia</span>
                                                    @endif
                                                </div>
                                                <span class="text-[10px] text-gray-500 uppercase font-black">
                                                    Q{{ $task->getQuadrant($task) }} 
                                                    {{ $task->is_backstage ? '• Preparación' : '' }}
                                                    {{ $task->skill ? '• ' . $task->skill->name : '' }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <span class="inline-flex px-3 py-1 bg-violet-50 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300 font-mono font-bold rounded-full text-sm">
                                                {{ $task->totalTrackedTimeHuman() }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="px-6 py-8 text-center text-gray-500 italic">{{ __('tasks.no_task_logs') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Workday History Table -->
                <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-3xl border border-gray-100 dark:border-gray-800">
                    <div class="p-6 border-b border-gray-50 dark:border-gray-800 flex items-center justify-between">
                        <h4 class="font-black text-gray-900 dark:text-gray-100">Registro de Presencia</h4>
                         <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Últimos 30 días</div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 dark:bg-gray-950/50">
                                <tr>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">{{ __('tasks.date') }}</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">{{ __('tasks.entrance_exit') }}</th>
                                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-right">{{ __('tasks.total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                @forelse($workdayLogs as $log)
                                    @php
                                        $duration = $log->end_at ? $log->start_at->diffInSeconds($log->end_at) : $log->start_at->diffInSeconds(now());
                                        $hours = floor($duration / 3600);
                                        $minutes = floor(($duration % 3600) / 60);
                                    @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                        <td class="px-6 py-4">
                                            <span class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $log->start_at->translatedFormat('d M Y') }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-xs font-mono text-gray-500">
                                            {{ $log->start_at->format('H:i') }} - {{ $log->end_at ? $log->end_at->format('H:i') : '--:--' }}
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <span class="font-mono font-black {{ $log->end_at ? 'text-gray-900 dark:text-gray-100' : 'text-red-500 animate-pulse' }}">
                                                {{ $hours }}h {{ $minutes }}m
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-8 text-center text-gray-500 italic">{{ __('tasks.no_workday_logs') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

    </div>
    
    <!-- Heatmap Scripts (Leaflet de CDNJS para mayor estabilidad) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mapContainer = document.getElementById('resilience-heatmap');
            if (!mapContainer) return;

            const heatmapPoints = {!! $heatmapData->toJson() !!};
            console.log("Team Map Data:", heatmapPoints);
            
            // Default center: España
            let center = [40.4168, -3.7038]; 
            let zoom = 12;

            // Prioridad al centro del usuario actual si tiene coordenadas
            @if(auth()->user()->location_lat)
                center = [{{ auth()->user()->location_lat }}, {{ auth()->user()->location_lng }}];
                zoom = 13;
            @elseif(count($heatmapData) > 0)
                const avgLat = heatmapPoints.reduce((sum, p) => sum + p.lat, 0) / heatmapPoints.length;
                const avgLng = heatmapPoints.reduce((sum, p) => sum + p.lng, 0) / heatmapPoints.length;
                center = [avgLat, avgLng];
            @else
                zoom = 6; 
            @endif

            const map = L.map('resilience-heatmap', {
                center: center,
                zoom: zoom,
                zoomControl: true, // Habilitar controles para navegar mejor en rural
                attributionControl: true
            });

            // OpenStreetMap Estándar (Más legible para el territorio real)
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            if (heatmapPoints.length > 0) {
                // Layer for Heatmap
                const heatData = heatmapPoints.map(p => [p.lat, p.lng, parseFloat(p.count) || 10]);
                L.heatLayer(heatData, {
                    radius: 40,
                    blur: 30,
                    maxZoom: 16,
                    gradient: {0.4: '#3b82f6', 0.65: '#10b981', 1: '#ef4444'}
                }).addTo(map);

                // Marcadores y círculos
                const markers = [];
                heatmapPoints.forEach(p => {
                    // Círculo de influencia
                    const circle = L.circle([p.lat, p.lng], {
                        color: '#10b981',
                        fillColor: '#10b981',
                        fillOpacity: 0.15,
                        radius: p.radius || 5000,
                        weight: 2,
                        dashArray: '8, 8'
                    }).addTo(map);
                    
                    circle.bindPopup(`
                        <div class="p-3 dark:text-gray-100 min-w-[150px]">
                            <p class="text-[10px] font-black uppercase text-emerald-600 mb-1 tracking-widest">${p.name}</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-gray-100 mb-2">${p.area || 'Sin nombre de área'}</p>
                            <div class="pt-2 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                                <span class="text-[8px] font-black uppercase text-gray-400">Impacto</span>
                                <span class="text-xs font-black text-emerald-500">${Math.round(p.count)} XP</span>
                            </div>
                        </div>
                    `, { className: 'custom-popup rounded-3xl overflow-hidden' });
                    
                    const marker = L.marker([p.lat, p.lng], {
                        opacity: 0.8
                    });
                    markers.push(marker);
                });

                // Auto-fit bounds solo si hay varios puntos y no es el zoom manual inicial
                if (heatmapPoints.length > 1) {
                    const group = L.featureGroup(markers);
                    map.fitBounds(group.getBounds().pad(0.3));
                }
            }

            // Forzar actualización de tamaño por si acaso el parpadeo del flexbox afectó a Leaflet
            setTimeout(() => {
                map.invalidateSize();
            }, 500);
        });
    </script>

    <style>
        .custom-popup .leaflet-popup-content-wrapper {
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(4px);
        }
        .dark .custom-popup .leaflet-popup-content-wrapper {
            background: rgba(17, 24, 39, 0.9);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .custom-popup .leaflet-popup-tip {
            background: rgba(255, 255, 255, 0.9);
        }
        .dark .custom-popup .leaflet-popup-tip {
            background: rgba(17, 24, 39, 0.9);
        }
        #resilience-heatmap {
            background: #111827; /* Fallback color matching Dark Matter tile */
        }
    </style>
</x-app-layout>
