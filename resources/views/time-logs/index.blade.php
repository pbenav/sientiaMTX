@php
    // Usamos las variables pasadas desde el controlador: $tasks, $workdayLogs, $team, $teamMembers, $heatmapData
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-6">
            <div class="flex items-start gap-4 min-w-0 flex-1">
                <a href="{{ route('teams.index') }}"
                    class="mt-1 p-2.5 bg-gray-50 dark:bg-gray-800/50 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-2xl transition-all shadow-sm border border-gray-100 dark:border-gray-700/50 shrink-0"
                    title="{{ __('navigation.back') ?? 'Volver' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
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
                <div class="flex items-center gap-2 px-4 py-2 bg-amber-50 dark:bg-amber-900/30 border border-amber-100 dark:border-amber-800 rounded-2xl shadow-sm">
                    <span class="text-amber-600 dark:text-amber-400 font-black text-xs uppercase tracking-widest">Nivel 1</span>
                    <div class="w-20 h-1.5 bg-amber-200 dark:bg-amber-800 rounded-full overflow-hidden">
                        <div class="h-full bg-amber-500" style="width: 25%"></div>
                    </div>
                </div>
                @include('teams.partials.header-actions')
            </div>
        </div>

        <div class="mt-8 mb-4 flex w-full">
            @include('teams.partials.view-switcher')
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-3xl p-6 border border-gray-100 dark:border-gray-800 relative group">
                    <div class="absolute top-4 right-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-emerald-500 opacity-20 group-hover:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <p class="text-xs font-black uppercase tracking-widest text-gray-400 mb-4">Estado de Energía</p>
                    @php
                        $energy = auth()->user()->energy_level ?? 0;
                        $label = 'Óptimo';
                        $advice = '¡Energía a tope! Momento ideal para tareas de alta carga cognitiva.';
                        $colorClass = 'emeral-500';
                        $bgClass = 'emerald-500';
                        $pillClass = 'text-emerald-600 bg-emerald-200';
                        
                        if ($energy >= 80) {
                            $label = 'Excelente';
                            $advice = '¡A tope! Momento ideal para tareas complejas y retos.';
                        } elseif ($energy >= 50) {
                            $label = 'Estable';
                            $advice = 'Mantén el ritmo. Vas por buen camino.';
                            $pillClass = 'text-blue-600 bg-blue-100';
                            $bgClass = 'blue-500';
                        } elseif ($energy >= 30) {
                            $label = 'Cansancio';
                            $advice = 'Batería media. Considera una tarea ligera o un café.';
                            $pillClass = 'text-amber-600 bg-amber-100';
                            $bgClass = 'amber-500';
                        } else {
                            $label = 'Agotado';
                            $advice = '¡Para un momento! Prioriza tu bienestar ahora mismo.';
                            $pillClass = 'text-rose-600 bg-rose-100';
                            $bgClass = 'rose-500';
                        }
                    @endphp
                    <div class="relative pt-1">
                        <div class="flex mb-2 items-center justify-between">
                            <div>
                                <span class="text-[10px] font-black inline-block py-1 px-3 uppercase rounded-full {{ $pillClass }} transition-colors duration-500">
                                    {{ $energy }}% - {{ $label }}
                                </span>
                            </div>
                        </div>
                        <div class="overflow-hidden h-4 mb-4 text-xs flex rounded-full bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                            <div style="width:{{ $energy }}%" class="flex flex-col text-center whitespace-nowrap text-white justify-center {{ $bgClass }} transition-all duration-1000 shadow-sm shadow-{{ $bgClass }}/20"></div>
                        </div>
                    </div>
                    <p class="text-[10px] font-medium text-gray-500 dark:text-gray-400 leading-tight">{{ $advice }}</p>
                </div>

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

                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="fixed inset-0 z-[10000] overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm"
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
                            <form action="{{ route('teams.kudos.store', $team) }}" method="POST" class="p-6 space-y-4">
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
                                <div>
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

                <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-3xl p-6 border border-gray-100 dark:border-gray-800 relative group flex items-center justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-widest text-gray-400 mb-2">Impacto Colectivo (Red)</p>
                        <h3 class="text-4xl font-black text-emerald-600 dark:text-emerald-400 tabular-nums">
                            {{ number_format($team->members->sum('experience_points')) }}
                        </h3>
                        <p class="text-[10px] text-gray-500 mt-1">Suma global de labor de todo el equipo.</p>
                    </div>
                    <div class="p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl border border-emerald-100 dark:border-emerald-800/50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-3xl border border-gray-100 dark:border-gray-800 flex flex-col mb-6">
                <div class="p-4 border-b border-gray-50 dark:border-gray-800 flex items-center justify-between bg-violet-50/10 dark:bg-violet-950/10">
                    <h4 class="font-black text-gray-900 dark:text-gray-100 flex items-center gap-2 uppercase tracking-widest text-xs">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        Capacidad del Equipo (Especialización Colectiva)
                    </h4>
                    <span class="text-[9px] font-bold text-violet-400 uppercase">XP Realizada vs Planificada</span>
                </div>
                <div class="p-6">
                    <div class="flex flex-wrap justify-between gap-y-6 gap-x-2">
                        @php
                            $allSkills = \App\Models\Skill::forTeamOrGlobal($team->id)->orderBy('name')->get();
                            $memberIds = $team->members->pluck('id');
                            
                            // Collective XP: Summing from all members, grouping by name to handle shadowing
                            $collectiveXp = \DB::table('user_skills')
                                ->join('skills', 'user_skills.skill_id', '=', 'skills.id')
                                ->whereIn('user_skills.user_id', $memberIds)
                                ->select('skills.name', \DB::raw('SUM(user_skills.total_xp) as aggregate_xp'))
                                ->groupBy('skills.name')
                                ->pluck('aggregate_xp', 'name');

                            // Potential XP: Summing cognitive load of pending/active tasks per skill name
                            $potentialXp = \DB::table('tasks')
                                ->join('skill_task', 'tasks.id', '=', 'skill_task.task_id')
                                ->join('skills', 'skill_task.skill_id', '=', 'skills.id')
                                ->where('tasks.team_id', $team->id)
                                ->whereNotIn('tasks.status', ['completed', 'cancelled', 'blocked'])
                                ->select('skills.name', \DB::raw('SUM(tasks.cognitive_load * 10) as potential'), \DB::raw('COUNT(tasks.id) as count'))
                                ->groupBy('skills.name')
                                ->get()
                                ->keyBy('name');

                            // Real Task Count: Count of completed tasks per skill
                            $completedTaskCount = \DB::table('tasks')
                                ->join('skill_task', 'tasks.id', '=', 'skill_task.task_id')
                                ->join('skills', 'skill_task.skill_id', '=', 'skills.id')
                                ->where('tasks.team_id', $team->id)
                                ->where('tasks.status', 'completed')
                                ->select('skills.name', \DB::raw('COUNT(tasks.id) as count'))
                                ->groupBy('skills.name')
                                ->pluck('count', 'name');

                            $levelThresholds = [1 => 0, 2 => 30, 3 => 100, 4 => 300, 5 => 1000]; // Adjusted for better early reward
                        @endphp
                        @foreach($allSkills as $skill)
                            @php
                                $xp = $collectiveXp->get($skill->name, 0);
                                $planData = $potentialXp->get($skill->name);
                                $plan = $planData ? $planData->potential : 0;
                                $pendingCount = $planData ? $planData->count : 0;
                                $completedCount = $completedTaskCount->get($skill->name, 0);
                                $totalActions = $completedCount + $pendingCount;
                                
                                $level = 1;
                                foreach($levelThresholds as $lvl => $thr) {
                                    if ($xp >= $thr) $level = $lvl;
                                }

                                $nextThreshold = $levelThresholds[min(5, $level + 1)];
                                $prevThreshold = $levelThresholds[$level];
                                $range = max(1, $nextThreshold - $prevThreshold);
                                
                                $progress = $level >= 5 ? 100 : (($xp - $prevThreshold) / $range) * 100;
                                $planProgress = $level >= 5 ? 0 : ($plan / $range) * 100;
                            @endphp
                            <div class="flex flex-col items-center w-[120px] shrink-0 group/skill">
                                <div class="relative w-16 h-16 flex items-center justify-center mb-2">
                                    <svg class="w-full h-full transform -rotate-90">
                                        <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="5" fill="transparent" class="text-gray-100 dark:text-gray-800" />
                                        
                                        <!-- Ghost Bar (Potential) -->
                                        @if($planProgress > 0)
                                        <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="5" fill="transparent" 
                                            stroke-dasharray="176" stroke-dashoffset="{{ 176 - (176 * min(100, $progress + $planProgress) / 100) }}"
                                            class="text-violet-200 dark:text-violet-900 transition-all duration-1000" />
                                        @endif

                                        <!-- Real Bar (Mastery) -->
                                        <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="5" fill="transparent" 
                                            stroke-dasharray="176" stroke-dashoffset="{{ 176 - (176 * $progress / 100) }}"
                                            class="text-violet-500 transition-all duration-1000" />
                                    </svg>
                                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                                        <span class="text-[8px] font-black text-violet-400 uppercase leading-none">Nivel</span>
                                        <span class="text-xl font-black text-gray-900 dark:text-white leading-none">{{ $level }}</span>
                                    </div>
                                    
                                    <!-- Badge for Total Actions (Tasks) -->
                                    <div class="absolute -bottom-1 -right-1 bg-white dark:bg-gray-800 px-1.5 py-0.5 rounded-md border border-gray-200 dark:border-gray-700 shadow-sm flex items-center gap-1">
                                        <span class="text-[8px] font-black text-gray-600 dark:text-gray-400">{{ $totalActions }}</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                                        </svg>
                                    </div>

                                    @if($plan > 0)
                                        <div class="absolute top-0 right-0 flex h-4 w-4">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-violet-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-4 w-4 bg-violet-600 text-[8px] text-white font-black items-center justify-center shadow-lg border border-white dark:border-gray-800">+</span>
                                        </div>
                                    @endif
                                </div>
                                <h5 class="text-[10px] font-black text-gray-900 dark:text-gray-100 uppercase tracking-tight text-center leading-tight h-6 flex items-center">{{ $skill->name }}</h5>
                                <div class="mt-1 flex flex-col items-center gap-1">
                                    <div class="text-[9px] font-black text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-900/30 px-2 py-0.5 rounded-full border border-violet-100 dark:border-violet-800/50">{{ number_format($xp) }} XP</div>
                                    @if($plan > 0)
                                        <div class="text-[8px] font-black text-violet-400 uppercase italic tracking-tighter">{{ number_format($plan) }} XP en curso</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- Map Column -->
                <div class="md:col-span-2 bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-3xl border border-gray-200 dark:border-gray-800 flex flex-col">
                    <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-emerald-50/5 dark:bg-emerald-950/5">
                        <h4 class="font-black text-gray-900 dark:text-gray-100 flex items-center gap-2 uppercase tracking-widest text-[10px]">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Impacto Territorial
                        </h4>
                        <button type="button" @click="$dispatch('open-zone-modal')" class="px-2.5 py-1.5 bg-white dark:bg-gray-800 text-emerald-600 border border-emerald-200 hover:bg-emerald-50 text-[9px] font-black uppercase rounded-lg transition-all flex items-center gap-1.5 shadow-sm cursor-pointer relative z-10">
                            📍 Mi Zona
                        </button>
                    </div>
                        <div class="relative w-full group h-[350px]">
                            <div id="resilience-heatmap" class="h-full w-full rounded-b-3xl"></div>
                            <div class="absolute top-3 right-3 z-[5] bg-white/70 dark:bg-gray-900/70 backdrop-blur-sm px-2 py-1 rounded-lg border border-gray-100 pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity">
                                <p class="text-[8px] font-black text-gray-500 uppercase tracking-tighter">Ctrl + 🖱️ Zoom</p>
                            </div>
                            <div class="absolute bottom-3 left-3 z-[5] bg-white/90 dark:bg-gray-900/90 backdrop-blur-md px-3 py-2 rounded-xl border border-gray-100 shadow-sm">
                            <div class="flex items-center gap-2">
                                <div class="w-16 h-1 rounded-full bg-gradient-to-r from-blue-400 via-emerald-400 to-rose-400"></div>
                                <span class="text-[7px] font-black text-gray-400 uppercase">Resiliencia</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Network Column -->
                <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-3xl border border-gray-200 dark:border-gray-800 flex flex-col">
                    <div class="px-5 py-4 border-b border-gray-50 dark:border-gray-800 bg-gray-50/30 dark:bg-transparent">
                        <h4 class="font-black text-gray-900 dark:text-gray-100 uppercase tracking-widest text-[10px] flex items-center gap-2">
                            <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
                            Red Activa
                        </h4>
                    </div>
                    <div class="p-5 flex-1 overflow-y-auto max-h-[350px] space-y-4 no-scrollbar">
                        @foreach($team->members->whereNotNull('location_lat')->take(12) as $member)
                            <div class="flex items-center justify-between group">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-400 to-teal-600 p-0.5 shadow-sm transition-transform group-hover:scale-105">
                                        <div class="w-full h-full rounded-[10px] bg-white dark:bg-gray-800 flex items-center justify-center text-[10px] font-black text-emerald-600 uppercase">
                                            {{ substr($member->name, 0, 2) }}
                                        </div>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[11px] font-black text-gray-900 dark:text-white uppercase truncate">{{ $member->name }}</p>
                                        <p class="text-[9px] text-emerald-500 font-bold truncate tracking-tight">{{ $member->working_area_name ?? 'Zona Sin Nombre' }}</p>
                                    </div>
                                </div>
                                <div class="h-1.5 w-1.5 rounded-full bg-emerald-500/20 group-hover:bg-emerald-500 transition-colors"></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="p-4 bg-gray-50/50 dark:bg-transparent border-t border-gray-50 dark:border-gray-800 mt-auto">
                        <p class="text-[8px] font-black text-gray-400 uppercase text-center tracking-widest">Distribución de Impacto Colectivo</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-3xl border border-gray-100 flex flex-col">
                    <div class="p-6 border-b border-gray-50 bg-rose-50/30 flex items-center justify-between">
                        <h4 class="font-black text-gray-900 flex items-center gap-2 uppercase tracking-widest text-[10px]">Kudos Recibidos</h4>
                        <span class="text-xs font-black text-rose-600 uppercase tracking-widest">{{ auth()->user()->receivedKudos()->count() }}</span>
                    </div>
                    <div class="p-6 space-y-4 max-h-[400px] overflow-y-auto no-scrollbar">
                        @forelse(auth()->user()->receivedKudos()->with('sender')->orderBy('created_at', 'desc')->limit(5)->get() as $kudo)
                            <div class="flex items-start gap-4 p-4 rounded-2xl bg-gray-50 dark:bg-gray-800/50">
                                <div class="w-10 h-10 rounded-full bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0 text-xs font-black">{{ substr($kudo->sender->name ?? '?', 0, 2) }}</div>
                                <div class="min-w-0">
                                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100 truncate block">{{ $kudo->sender->name ?? 'Anónimo' }}</span>
                                    <p class="text-xs text-gray-500 italic mt-1 break-words">"{{ $kudo->message }}"</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-gray-500 py-8 italic text-xs">Sin reconocimientos aún.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-3xl border border-gray-100 flex flex-col">
                    <div class="p-6 border-b border-gray-50 bg-amber-50/30 flex items-center justify-between">
                        <h4 class="font-black text-gray-900 flex items-center gap-2 uppercase tracking-widest text-[10px]">Kudos Enviados</h4>
                        <span class="text-xs font-black text-amber-600 uppercase tracking-widest">{{ auth()->user()->givenKudos()->count() }}</span>
                    </div>
                    <div class="p-6 space-y-4 max-h-[400px] overflow-y-auto no-scrollbar">
                        @forelse(auth()->user()->givenKudos()->with('receiver')->orderBy('created_at', 'desc')->limit(5)->get() as $kudo)
                            <div class="flex items-start gap-4 p-4 rounded-2xl bg-gray-50 dark:bg-gray-800/50">
                                <div class="w-10 h-10 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 text-xs font-black">{{ substr($kudo->receiver->name ?? '?', 0, 2) }}</div>
                                <div class="min-w-0">
                                    <span class="text-sm font-bold text-gray-900 dark:text-gray-100 truncate block">{{ $kudo->receiver->name ?? 'Anónimo' }}</span>
                                    <p class="text-xs text-gray-500 italic mt-1 break-words">"{{ $kudo->message }}"</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-gray-500 py-8 italic text-xs">No has enviado kudos aún.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-3xl border border-gray-100 flex flex-col">
                    <div class="p-6 border-b border-gray-50 bg-violet-50/30 flex items-center justify-between">
                        <h4 class="font-black text-gray-900 flex items-center gap-2 uppercase tracking-widest text-[10px]">Logros Recientes</h4>
                    </div>
                    <div class="p-6 space-y-4 max-h-[400px] overflow-y-auto no-scrollbar">
                        @forelse(auth()->user()->gamificationLogs()->orderBy('created_at', 'desc')->limit(10)->get() as $log)
                            <div class="flex items-start gap-3 p-3 rounded-2xl bg-gray-50 dark:bg-gray-800/50">
                                <div class="w-8 h-8 rounded-full flex items-center font-black justify-center text-[10px] {{ $log->type === 'resilience' ? 'bg-violet-100 text-violet-600 dark:bg-violet-900/30 dark:text-violet-400' : 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400' }} shrink-0">+{{ $log->points }}</div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-xs font-bold text-gray-900 dark:text-gray-100 truncate">{{ $log->description }}</span>
                                    <span class="text-[9px] text-gray-400 uppercase">{{ $log->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-gray-500 py-8 italic text-xs">¡Empieza tu aventura!</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pt-4">
                <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-3xl border border-gray-100 flex flex-col">
                    <div class="p-6 border-b border-gray-50 flex items-center justify-between">
                        <h4 class="font-black text-gray-900">Contabilidad de Esfuerzo</h4>
                        <form action="" method="GET" class="flex items-center gap-2">
                            @if(request('presence_limit'))
                                <input type="hidden" name="presence_limit" value="{{ request('presence_limit') }}">
                            @endif
                            <label class="text-[9px] font-black uppercase text-gray-400">Ver:</label>
                            <select name="effort_limit" onchange="this.form.submit()" class="text-[10px] font-bold border-gray-200 dark:border-gray-700 rounded-lg py-0.5 pl-2 pr-8 bg-gray-50 dark:bg-gray-800 focus:ring-0 focus:border-violet-500 transition-all">
                                <option value="5" {{ request('effort_limit') == 5 ? 'selected' : '' }}>5</option>
                                <option value="10" {{ (!request('effort_limit') || request('effort_limit') == 10) ? 'selected' : '' }}>10</option>
                                <option value="20" {{ request('effort_limit') == 20 ? 'selected' : '' }}>20</option>
                                <option value="50" {{ request('effort_limit') == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ request('effort_limit') == 100 ? 'selected' : '' }}>100</option>
                            </select>
                        </form>
                    </div>
                    <div class="overflow-x-auto overflow-y-auto max-h-[400px] no-scrollbar">
                        <table class="w-full text-left">
                        <thead class="bg-gray-50"><tr><th class="px-6 py-4 text-[10px] font-black uppercase text-gray-400">Tarea</th><th class="px-6 py-4 text-[10px] font-black uppercase text-gray-400 text-right">Tiempo</th></tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($tasks as $task)
                                <tr>
                                    <td class="px-6 py-4"><span class="text-sm font-bold text-gray-900">{{ $task->title }}</span><br><span class="text-[8px] uppercase font-black text-gray-400">Q{{ $task->getQuadrant($task) }} {{ $task->skill ? '• '.$task->skill->name : '' }}</span></td>
                                    <td class="px-6 py-4 text-right"><span class="bg-violet-50 text-violet-700 px-3 py-1 rounded-full text-xs font-bold">{{ $task->totalTrackedTimeHuman() }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table></div>
                </div>

                <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-3xl border border-gray-100 flex flex-col">
                    <div class="p-6 border-b border-gray-50 flex items-center justify-between">
                        <h4 class="font-black text-gray-900">Registro de Presencia</h4>
                        <form action="" method="GET" class="flex items-center gap-2">
                            @if(request('effort_limit'))
                                <input type="hidden" name="effort_limit" value="{{ request('effort_limit') }}">
                            @endif
                            <label class="text-[9px] font-black uppercase text-gray-400">Ver:</label>
                            <select name="presence_limit" onchange="this.form.submit()" class="text-[10px] font-bold border-gray-200 dark:border-gray-700 rounded-lg py-0.5 pl-2 pr-8 bg-gray-50 dark:bg-gray-800 focus:ring-0 focus:border-emerald-500 transition-all">
                                <option value="5" {{ request('presence_limit') == 5 ? 'selected' : '' }}>5</option>
                                <option value="10" {{ (!request('presence_limit') || request('presence_limit') == 10) ? 'selected' : '' }}>10</option>
                                <option value="20" {{ request('presence_limit') == 20 ? 'selected' : '' }}>20</option>
                                <option value="50" {{ request('presence_limit') == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ request('presence_limit') == 100 ? 'selected' : '' }}>100</option>
                            </select>
                        </form>
                    </div>
                    <div class="overflow-x-auto overflow-y-auto max-h-[400px] no-scrollbar">
                        <table class="w-full text-left">
                        <thead class="bg-gray-50"><tr><th class="px-6 py-4 text-[10px] font-black uppercase text-gray-400">Fecha</th><th class="px-6 py-4 text-[10px] font-black uppercase text-gray-400 text-right">Total</th></tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($workdayLogs as $log)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-bold text-gray-900">{{ $log->start_at->translatedFormat('d M Y') }}</td>
                                    <td class="px-6 py-4 text-right text-xs font-black">{{ $log->end_at ? floor($log->start_at->diffInMinutes($log->end_at) / 60).'h '.($log->start_at->diffInMinutes($log->end_at) % 60).'m' : 'Activa' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table></div>
                </div>
            </div>

        </div>
    </div>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
    <style>
        #resilience-heatmap { z-index: 0 !important; }
        .leaflet-pane { z-index: 1 !important; }
        .leaflet-top, .leaflet-bottom { z-index: 2 !important; }
        .custom-div-icon { z-index: 10 !important; }
        .leaflet-popup { z-index: 20 !important; }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const heatmapPoints = @json($heatmapData ?? []);
            
            // Validamos que los puntos tengan coordenadas válidas y sean números para evitar errores de renderizado
            const validPoints = heatmapPoints.filter(p => {
                const lat = parseFloat(p.lat);
                const lng = parseFloat(p.lng);
                return !isNaN(lat) && !isNaN(lng) && Math.abs(lat) <= 90 && Math.abs(lng) <= 180;
            });
            
            let center = [37.17, -3.60]; // Default Andalucía
            let zoom = 8;
            
            const userLat = @json(auth()->user()->location_lat);
            const userLng = @json(auth()->user()->location_lng);
            
            if (userLat && userLng) {
                center = [parseFloat(userLat), parseFloat(userLng)];
                zoom = 13;
            }
            
            const map = L.map('resilience-heatmap', { 
                center: center, 
                zoom: zoom,
                scrollWheelZoom: false // Bloqueado por defecto
            });

            // Solo permitir zoom con la rueda si se pulsa Ctrl o Meta (Cmd)
            window.addEventListener('keydown', (e) => {
                if (e.ctrlKey || e.metaKey) map.scrollWheelZoom.enable();
            });
            window.addEventListener('keyup', (e) => {
                if (!e.ctrlKey && !e.metaKey) map.scrollWheelZoom.disable();
            });
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
            
            // Forzamos el orden de las capas para que los popups nunca se queden detrás
            map.getPane('markerPane').style.zIndex = 650;
            map.getPane('popupPane').style.zIndex = 750;
            
            if (validPoints.length > 0) {
                // Obtenemos la intensidad máxima para normalizar y evitar valores extremos que rompan el canvas
                const maxVal = Math.max(...validPoints.map(p => parseFloat(p.count) || 10));
                
                const heatData = validPoints.map(p => [
                    parseFloat(p.lat), 
                    parseFloat(p.lng), 
                    (parseFloat(p.count) || 10) / maxVal // Normalizamos a escala 0-1
                ]);
                
                L.heatLayer(heatData, { 
                    radius: 35, 
                    blur: 20, 
                    maxZoom: 16,
                    max: 1.0
                }).addTo(map);
                
                validPoints.forEach(p => {
                    // Marcador 'Hito' elegante
                    const initial = p.name ? p.name.substring(0, 2).toUpperCase() : '📍';
                    const customIcon = L.divIcon({
                        className: 'custom-div-icon',
                        html: `<div style="background-color: #059669; color: white; border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-weight: 900; border: 2px solid white; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06); font-size: 10px; font-family: ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji';">${initial}</div>`,
                        iconSize: [28, 28],
                        iconAnchor: [14, 14],
                        popupAnchor: [0, -14]
                    });

                    L.marker([p.lat, p.lng], { icon: customIcon }).addTo(map)
                     .bindPopup(`<div class="text-center font-sans tracking-tight leading-tight"><span class="text-[10px] font-black uppercase text-emerald-600">${p.area || 'Zona Activa'}</span><br><b class="text-xs text-gray-900">${p.name}</b></div>`);

                    // Círculo de impacto territorial
                    if (p.radius) {
                        L.circle([p.lat, p.lng], {
                            radius: p.radius,
                            color: '#10b981',
                            fillColor: '#10b981',
                            fillOpacity: 0.05,
                            weight: 1,
                            dashArray: '5, 5'
                        }).addTo(map);
                    }
                });
                
                // Si hay más de un punto, ajustar el mapa para que se vean todos
                if (validPoints.length > 1) {
                    const bounds = L.latLngBounds(validPoints.map(p => [p.lat, p.lng]));
                    map.fitBounds(bounds, { padding: [50, 50] });
                }
            }
        });
    </script>
    <!-- Modal Global Mi Zona -->
    <div x-data="{ open: false }" @open-zone-modal.window="open = true" x-cloak>
        <div x-show="open" 
             class="fixed inset-0 z-[10000] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"
             style="display: none;">
            
            <div @click.away="open = false" 
                 class="w-full max-w-lg bg-white dark:bg-gray-900 rounded-3xl shadow-2xl border border-gray-200 dark:border-gray-800 p-8 transform transition-all overflow-hidden">
                
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase flex items-center gap-2">
                        <span class="p-2 bg-emerald-500/10 text-emerald-500 rounded-xl">📍</span>
                        Tu Área de Acción
                    </h3>
                    <button @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form action="{{ route('user.update-zone') }}" method="POST" class="space-y-5">
                    @csrf @method('PATCH')
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1.5 ml-1">Nombre de tu territorio</label>
                        <input type="text" name="working_area_name" value="{{ auth()->user()->working_area_name }}" placeholder="Ej: Zafarraya Central" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl px-5 py-4 text-sm font-bold text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500/20 outline-none" required>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-1.5 ml-1">Latitud</label>
                            <input type="number" name="location_lat" step="any" value="{{ auth()->user()->location_lat }}" placeholder="37.0..." class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl px-5 py-4 text-sm font-bold text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500/20 outline-none" required>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-1.5 ml-1">Longitud</label>
                            <input type="number" name="location_lng" step="any" value="{{ auth()->user()->location_lng }}" placeholder="-4.1..." class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl px-5 py-4 text-sm font-bold text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500/20 outline-none" required>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-1.5 ml-1">
                            <label class="text-[10px] font-black text-gray-400 uppercase">Radio de Impacto</label>
                            <span class="text-[10px] font-black text-emerald-500"><span id="impact-val-display">{{ auth()->user()->impact_radius ?? 10 }}</span> km</span>
                        </div>
                        <input type="range" name="impact_radius" min="1" max="50" value="{{ auth()->user()->impact_radius ?? 10 }}" 
                               class="w-full h-2 bg-gray-100 dark:bg-gray-800 rounded-lg appearance-none cursor-pointer accent-emerald-500"
                               oninput="document.getElementById('impact-val-display').innerText = this.value">
                    </div>

                    <div class="flex gap-4 pt-4">
                        <button type="submit" class="w-full py-5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl font-black uppercase text-xs shadow-xl shadow-emerald-500/25 transition-all active:scale-[0.98]">
                            Guardar Mi Perímetro 📍
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
