            <!-- Activity Name Card -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm dark:shadow-none transition-colors">
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                        {{ __('activities.name') }}</h3>
                    @if ($activity->is_template)
                        <span class="px-2 py-0.5 rounded-full bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300 text-[9px] font-black uppercase tracking-widest border border-violet-200 dark:border-violet-700/50 shadow-sm flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            {{ __('activities.plan_master') }}
                        </span>
                    @endif
                    @if ($activity->assigned_user_id === auth()->id() && $activity->parent_id)
                        <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 text-[9px] font-black uppercase tracking-widest border border-emerald-200 dark:border-emerald-700/50 shadow-sm flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            {{ __('activities.your_execution') }}
                        </span>
                    @endif
                </div>
                <p class="text-xl font-bold text-gray-900 dark:text-white heading leading-tight flex items-center gap-2">
                    @php
                        $typeColors = [
                            'task'     => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                            'document' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
                            'note'     => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                            'link'     => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
                            'decision' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                            'meeting'  => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                            'reminder' => 'bg-pink-100 text-pink-700 dark:bg-pink-900/40 dark:text-pink-300',
                        ];
                        $typeCls = $typeColors[$activity->type] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
                    @endphp
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider {{ $typeCls }} shadow-sm shrink-0">
                        {!! $activity->type_icon !!} {{ $activity->type_label }}
                    </span>
                    {{ $activity->title }}
                </p>
            </div>

            @php
                $displayDescription = $activity->description ?: ($activity->parent?->description ?? null);
                $displayObservations = $activity->observations ?: ($activity->parent?->observations ?? null);
            @endphp

            @if ($displayDescription)
                <div
                    class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm dark:shadow-none transition-colors">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                            {{ __('activities.description') }}
                        </h3>
                        <div class="flex items-center gap-2">
                            @if($team->isCoordinator(auth()->user()) || auth()->id() === $activity->assigned_user_id)
                            <button @click="$dispatch('ai:analyze-activity', { taskId: {{ $activity->id }}, teamId: {{ $team->id }}, taskTitle: '{{ addslashes($activity->title) }}', section: 'description' })" 
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
                    <div id="description-content" style="height: 350px; max-height: none; overflow-y: auto;"
                        class="text-sm text-gray-700 dark:text-gray-300 prose dark:prose-invert max-w-none prose-sm leading-relaxed resize-y min-h-[250px] custom-scrollbar pr-4 py-2">
                        {!! str($displayDescription)->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                    </div>
                </div>
            @endif

            @if ($displayObservations)
                <div
                    class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm dark:shadow-none transition-colors">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                            {{ __('activities.observations') }}
                        </h3>
                        <div class="flex items-center gap-2">
                            @if($team->isCoordinator(auth()->user()) || auth()->id() === $activity->assigned_user_id)
                            <button @click="$dispatch('ai:analyze-activity', { taskId: {{ $activity->id }}, teamId: {{ $team->id }}, taskTitle: '{{ addslashes($activity->title) }}', section: 'observaciones' })" 
                                    class="p-2 bg-violet-50 dark:bg-violet-900/40 text-violet-600 dark:text-violet-400 hover:text-violet-700 dark:hover:text-violet-300 rounded-xl transition-all shadow-sm flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest border border-violet-100 dark:border-violet-800/50"
                                    title="Desarrollar contenido con IA">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                Ax.ia
                            </button>
                            @endif
                            <button onclick="printSection('Observaciones', 'observations-content')" 
                                    class="p-1.5 bg-gray-50 dark:bg-gray-800 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-xl transition-all border border-transparent hover:border-violet-100 dark:hover:border-violet-800 shadow-sm flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest"
                                    title="Imprimir observaciones">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                Imprimir
                            </button>
                        </div>
                    </div>
                    <div id="observations-content" style="height: 350px; max-height: none; overflow-y: auto;"
                        class="text-sm text-gray-700 dark:text-gray-300 prose dark:prose-invert max-w-none prose-sm leading-relaxed resize-y min-h-[250px] custom-scrollbar pr-4 py-2">
                        {!! str($displayObservations)->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                    </div>
                </div>
            @endif

            @if ($activity->is_template || $activity->children()->exists() || $activity->assignedTo->isNotEmpty() || $activity->assigned_user_id)
                @php
                    $isRoadmap = $activity->is_template || $activity->children()->exists();
                    $currentUser = auth()->user();
                    $isUserMgr = $team->isManager($currentUser);

                    if ($isRoadmap) {
                        $instancesQuery = $activity->is_template ? $activity->instances() : $activity->children();
                        $withRelation = $activity->is_template ? 'assignedUser' : 'assignedTo';
                        
                        $instances = $instancesQuery->getQuery()
                            ->visibleTo($currentUser, $isUserMgr)
                            ->with($withRelation)
                            ->get()
                            ->sortBy(function($inst) use ($activity) {
                                if ($activity->is_template) {
                                    return mb_strtolower(($inst->assignedUser?->name ?? '') . ' ' . $inst->title);
                                }
                                return mb_strtolower(($inst->assignedTo->first()?->name ?? '') . ' ' . $inst->title);
                            });
                    } else {
                        // For regular activities, we "simulate" instances using the assigned users
                        $instances = $activity->assignedTo->map(function($user) use ($activity) {
                            return (object)[
                                'id' => $activity->id, // Reference to same activity
                                'name' => $activity->title,
                                'status_value' => $activity->status_value,
                                'progress' => $activity->progress_percentage,
                                'assignedUser' => $user,
                                'timeLogs' => $activity->timeLogs()->where('user_id', $user->id)->get(),
                                'is_simulated' => true,
                                'user_id' => $user->id
                            ];
                        });
                    }

                    $totalInst = $instances->count();
                    $sumProg = $isRoadmap ? $instances->sum('progress_percentage') : ($totalInst > 0 ? $activity->progress_percentage * $totalInst : 0);
                    $prog = $totalInst > 0 ? $sumProg / $totalInst : 0;
                    $doneInst = $isRoadmap ? $instances->where('status', 'completed')->count() : ($activity->status_value === 'completed' ? $totalInst : 0);
                    $hasBlocker = $isRoadmap ? $instances->where('status', 'blocked')->isNotEmpty() : ($activity->status_value === 'blocked');
                @endphp

                <!-- Progress Dashboard -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm dark:shadow-none transition-colors">
                    
                    <!-- HEADER: Members & Progress Text -->
                    <div class="flex items-end justify-between mb-4">
                        <div>
                            <h3 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">
                                {{ $isRoadmap ? __('activities.roadmap_progress') : __('teams.members') }}
                            </h3>
                            <div class="flex flex-wrap items-center gap-3">
                                <p class="text-2xl font-bold text-gray-900 dark:text-white heading leading-none">
                                    {{ $totalInst }} <span class="text-sm font-medium text-gray-400">{{ $totalInst == 1 ? __('activities.assigned_to_one') : __('activities.assigned_to_many') }}</span>
                                </p>
                                @if($isRoadmap)
                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-1 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 text-[10px] font-black uppercase tracking-widest rounded-lg border border-emerald-100 dark:border-emerald-800/50 shadow-sm">
                                            {{ $doneInst }} Completados
                                        </span>
                                        <span class="px-2.5 py-1 bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 text-[10px] font-black uppercase tracking-widest rounded-lg border border-amber-100 dark:border-amber-800/50 shadow-sm">
                                            {{ $totalInst - $doneInst }} Pendientes
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Progress Percentage -->
                        <div class="text-right">
                            <div class="flex items-baseline justify-end gap-2">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ __('Progreso') }}</span>
                                <span class="text-3xl font-black text-violet-600 dark:text-violet-400 tabular-nums js-global-progress-val leading-none">{{ round($prog) }}%</span>
                            </div>
                            @if(!$isRoadmap && $totalInst > 1)
                                <div class="mt-2">
                                    <span class="text-[9px] font-bold text-violet-500/70 border border-violet-200 dark:border-violet-800 rounded-lg px-2 py-0.5 bg-violet-50/50 dark:bg-violet-900/10 uppercase tracking-wider">
                                        {{ __('activities.collaborative_hint') }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- BIG PROGRESS BAR -->
                    <div class="w-full h-3 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden mb-6 border border-gray-200 dark:border-gray-700">
                        <div class="h-full bg-gradient-to-r from-violet-500 to-violet-600 shadow-lg shadow-violet-500/20 js-global-progress-bar"
                             style="width: {{ $prog }}%; transition: none !important;"></div>
                    </div>

                    <!-- FILTERS ROW -->
                    <div class="flex flex-col sm:flex-row items-center gap-3 bg-gray-50/50 dark:bg-gray-800/30 p-3 rounded-xl border border-gray-100 dark:border-gray-800/50 mb-6">
                        <div class="relative flex-1 w-full" x-data="{ rSearch: '' }" x-init="$watch('rSearch', v => $dispatch('roadmap-filter', v))">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input type="text" x-model="rSearch" 
                                placeholder="{{ __('Buscar por nombre del miembro o tarea...') }}" 
                                class="w-full pl-10 pr-4 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-xs outline-none focus:border-violet-500 focus:ring-4 focus:ring-violet-500/5 transition-all font-sans shadow-sm">
                        </div>
                        <div class="relative w-full sm:w-48" x-data="{ rStatus: '' }" x-init="$watch('rStatus', v => $dispatch('roadmap-status-filter', v))">
                            <select x-model="rStatus" class="w-full pl-4 pr-8 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-300 outline-none focus:border-violet-500 focus:ring-4 focus:ring-violet-500/5 transition-all font-sans appearance-none shadow-sm cursor-pointer">
                                <option value="">{{ __('Todos los estados') }}</option>
                                <option value="completed">{{ __('Solo Completados') }}</option>
                                <option value="pending">{{ __('Solo Pendientes') }}</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
                            </div>
                        </div>
                    </div>
                    @if ($hasBlocker)
                        <div
                            class="mb-6 p-4 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900/50 rounded-xl flex items-center gap-3 animate-pulse">
                            <div
                                class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center text-red-600 dark:text-red-400 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-red-700 dark:text-red-400">
                                    {{ __('activities.blocker_detected') }}</p>
                                <p class="text-xs text-red-600/80 dark:text-red-400/70">
                                    {{ __('activities.blocker_description') }}</p>
                            </div>
                        </div>
                    @endif
                    <div style="max-height: 500px; overflow-y: auto;" class="overflow-y-auto max-h-[600px] scrollbar-thin scrollbar-thumb-gray-200 dark:scrollbar-thumb-gray-800 border border-gray-100 dark:border-gray-800 rounded-xl custom-scrollbar"
                        x-data="{ 
                            selectedMembers: [],
                            sortKey: 'name', 
                            sortDir: 'asc',
                            sort(key) {
                                if (this.sortKey === key) {
                                    this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
                                } else {
                                    this.sortKey = key;
                                    this.sortDir = 'asc';
                                }
                                const tbody = this.$refs.roadmapBody;
                                const rows = Array.from(tbody.querySelectorAll('tr'));
                                rows.sort((a, b) => {
                                    let va = a.dataset[this.sortKey];
                                    let vb = b.dataset[this.sortKey];
                                    if (!isNaN(va) && !isNaN(vb)) {
                                        va = parseFloat(va);
                                        vb = parseFloat(vb);
                                    }
                                    if (va < vb) return this.sortDir === 'asc' ? -1 : 1;
                                    if (va > vb) return this.sortDir === 'asc' ? 1 : -1;
                                    return 0;
                                });
                                rows.forEach(row => tbody.appendChild(row));
                            },
                            toggleAll() {
                                const checkboxes = Array.from(document.querySelectorAll('.member-checkbox:not(:disabled)'));
                                if (this.selectedMembers.length === checkboxes.length) {
                                    this.selectedMembers = [];
                                } else {
                                    this.selectedMembers = checkboxes.map(c => c.value);
                                }
                            },
                            roadmapQuery: '',
                            roadmapStatus: ''
                        }"
                        @roadmap-filter.window="roadmapQuery = $event.detail"
                        @roadmap-status-filter.window="roadmapStatus = $event.detail">
                        <!-- Bulk Actions Bar -->
                        <div x-show="selectedMembers.length > 0" 
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 -translate-y-4"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="m-4 p-3 bg-violet-50 dark:bg-violet-900/20 border border-violet-100 dark:border-violet-800/50 rounded-2xl flex items-center justify-between shadow-sm sticky top-2 z-20">
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-black text-violet-700 dark:text-violet-400 uppercase tracking-widest">
                                    <span x-text="selectedMembers.length"></span> {{ __('seleccionados') }}
                                </span>
                            </div>
                            <button type="button" @click.prevent.stop="nudgeUser(selectedMembers)" 
                                    x-show="selectedMembers.length > 0"
                                    class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-[10px] font-black uppercase tracking-widest rounded-xl transition-all shadow-md flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                {{ __('Recordatorio Masivo') }}
                            </button>
                        </div>

                        <table class="w-full text-left text-sm">
                            <thead
                                class="sticky top-0 z-10 bg-gray-50/95 dark:bg-gray-800/95 backdrop-blur-sm text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    @if($team->isCoordinator(auth()->user()) || (isset($instances) && count($instances) > 1))
                                    <th class="px-4 py-3 w-10">
                                        <input type="checkbox" 
                                               @click="toggleAll()" 
                                               :checked="selectedMembers.length > 0 && selectedMembers.length === document.querySelectorAll('.member-checkbox:not(:disabled)').length"
                                               class="rounded border-gray-300 dark:border-gray-700 text-violet-600 focus:ring-violet-500 bg-white dark:bg-gray-900 cursor-pointer">
                                    </th>
                                    @endif
                                    <th class="px-4 py-3 cursor-pointer hover:text-violet-500 transition-colors group" @click="sort('name')">
                                        <div class="flex items-center gap-2">
                                            {{ __('teams.members') }}
                                            <svg class="h-3 w-3 opacity-0 group-hover:opacity-100 transition-opacity" :class="sortKey === 'name' ? 'opacity-100' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path x-show="sortKey !== 'name' || sortDir === 'asc'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                                <path x-show="sortKey === 'name' && sortDir === 'desc'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" />
                                            </svg>
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 cursor-pointer hover:text-violet-500 transition-colors group" @click="sort('status')">
                                        <div class="flex items-center gap-2">
                                            {{ __('activities.status') }}
                                            <svg class="h-3 w-3 opacity-0 group-hover:opacity-100 transition-opacity" :class="sortKey === 'status' ? 'opacity-100' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path x-show="sortKey !== 'status' || sortDir === 'asc'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                                <path x-show="sortKey === 'status' && sortDir === 'desc'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" />
                                            </svg>
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-right cursor-pointer hover:text-violet-500 transition-colors group" @click="sort('time')">
                                        <div class="flex items-center justify-end gap-2">
                                            {{ __('activities.time_spent') ?? 'Tiempo' }}
                                            <svg class="h-3 w-3 opacity-0 group-hover:opacity-100 transition-opacity" :class="sortKey === 'time' ? 'opacity-100' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path x-show="sortKey !== 'time' || sortDir === 'asc'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                                <path x-show="sortKey === 'time' && sortDir === 'desc'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7" />
                                            </svg>
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-right">{{ __('activities.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody x-ref="roadmapBody" class="divide-y divide-gray-100 dark:divide-gray-800/60">

                                @foreach ($instances as $inst)
                                    @php
                                        $isSimulated = isset($inst->is_simulated) && $inst->is_simulated;
                                        $instMember = $isSimulated ? $inst->assignedUser : ($inst->assignedUser ?? $inst->assignedTo->first());
                                        $instMemberName = $instMember?->name ?? 'Múltiples asignados';
                                        $instSeconds = (int) $inst->timeLogs->sum(fn($l) => $l->start_at->diffInSeconds($l->end_at ?: now()));
                                        $instFormatted = (floor($instSeconds / 3600) > 0 ? floor($instSeconds / 3600) . "h " : "") . floor(($instSeconds % 3600) / 60) . "m";
                                        $isInstActive = $inst->timeLogs->whereNull('end_at')->isNotEmpty();
                                        
                                        // Team membership date
                                        $teamMember = $instMember ? $team->members()->where('users.id', $instMember->id)->first() : null;
                                        $joinedAt = $teamMember?->pivot?->joined_at;
                                        $joinedDate = ($joinedAt instanceof \Carbon\Carbon) ? $joinedAt->format('d/m/Y') : null;

                                        $subtasksCount = $isSimulated ? 0 : $inst->children()->count();
                                        $subtasksDone = $isSimulated ? 0 : $inst->children()->where('status', 'completed')->count();
                                    @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors cursor-pointer group" 
                                        data-name="{{ strtolower($instMemberName) }}"
                                        data-taskname="{{ strtolower($inst->name) }}"
                                        data-status="{{ $inst->status_value }}"
                                        data-time="{{ $instSeconds }}"
                                        x-show="(roadmapQuery === '' || $el.dataset.name.includes(roadmapQuery.toLowerCase()) || $el.dataset.taskname.includes(roadmapQuery.toLowerCase())) && (roadmapStatus === '' || (roadmapStatus === 'completed' && $el.dataset.status === 'completed') || (roadmapStatus === 'pending' && $el.dataset.status !== 'completed'))"
                                        x-transition
                                        @if(!$isSimulated) onclick="if(!event.target.closest('button, select, a, input')) window.location='{{ route('teams.activities.show', [$team->id, $inst->id]) }}'" @endif>
                                        
                                        @if($team->isCoordinator(auth()->user()) || (isset($instances) && count($instances) > 1))
                                        <td class="px-4 py-4" onclick="event.stopPropagation()">
                                            <input type="checkbox" 
                                                   value="{{ ($isSimulated ? $activity->id : $inst->id) . ':' . ($isSimulated ? $inst->user_id : ($inst->assigned_user_id ?? '')) }}" 
                                                   x-model="selectedMembers" 
                                                   class="member-checkbox rounded border-gray-300 dark:border-gray-700 text-violet-600 focus:ring-violet-500 bg-white dark:bg-gray-900 cursor-pointer"
                                                   {{ $inst->status_value === 'completed' ? 'disabled' : '' }}>
                                        </td>
                                        @endif

                                        <td class="px-4 py-4 group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors" onclick="event.stopPropagation()">
                                            <div class="flex items-center gap-4">
                                                    <div class="relative">
                                                        <img src="{{ $instMember ? $instMember->profile_photo_url : 'https://ui-avatars.com/api/?name=?&color=7F9CF5&background=EBF4FF' }}" 
                                                            alt="{{ $instMemberName }}"
                                                            class="w-10 h-10 rounded-2xl object-cover shadow-inner border border-white dark:border-gray-800 {{ $isInstActive ? 'ring-2 ring-red-500 ring-offset-2 dark:ring-offset-gray-900 animate-pulse' : '' }}">
                                                        @if($isInstActive)
                                                            <span class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 border-2 border-white dark:border-gray-900 rounded-full"></span>
                                                        @endif
                                                    </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-sm font-black text-gray-900 dark:text-white truncate">{{ $isSimulated ? $instMemberName : $inst->name }}</span>
                                                        @if($isInstActive)
                                                            <span class="text-[7px] font-black bg-red-500 text-white px-1 rounded animate-pulse">LIVE</span>
                                                        @endif
                                                    </div>
                                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-0.5">
                                                        <span class="text-[10px] font-medium text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                                            {{ $isSimulated ? __('activities.assigned') : ($instMemberName ?: (__('activities.unassigned') ?? '?')) }}
                                                        </span>

                                                        @if($joinedDate)
                                                            <span class="text-[9px] text-gray-400 flex items-center gap-1">
                                                                <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                                {{ __('activities.member_since', ['date' => $joinedDate]) ?? "Desde $joinedDate" }}
                                                            </span>
                                                        @endif

                                                        @if($subtasksCount > 0)
                                                            <span class="text-[9px] font-bold text-violet-500 bg-violet-50 dark:bg-violet-900/30 px-1.5 py-0.5 rounded flex items-center gap-1 shrink-0">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                                                                {{ $subtasksDone }}/{{ $subtasksCount }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4">
                                            @php
                                                $instStatusColor = match ($inst->status_value) {
                                                    'completed' => 'text-emerald-500 dark:text-emerald-400',
                                                    'in_progress' => 'text-blue-500 dark:text-blue-400',
                                                    'blocked' => 'text-red-600 dark:text-red-400 font-bold',
                                                    default => 'text-gray-500 dark:text-gray-400',
                                                };
                                            @endphp
                                            <div class="flex flex-col gap-1.5">
                                                <div class="flex items-center gap-1.5 {{ $instStatusColor }}">
                                                    <div
                                                        class="w-1.5 h-1.5 rounded-full {{ str_contains($instStatusColor, 'text-') ? str_replace('text-', 'bg-', explode(' ', $instStatusColor)[0]) : 'bg-gray-400' }}">
                                                    </div>
                                                    <span
                                                        class="text-xs font-bold uppercase tracking-tight">{{ __('activities.statuses.' . $inst->status_value) }}</span>
                                                </div>
                                                <div class="flex items-center gap-2 w-28">
                                                    <div class="flex-1 h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                                        <div id="inst-progress-bar-{{ $isSimulated ? $activity->id . '-' . $inst->user_id : $inst->id }}" class="h-full bg-gradient-to-r from-violet-500 to-violet-500 transition-all duration-300 js-member-progress-bar" style="width: {{ $inst->progress }}%"></div>
                                                    </div>
                                                    <span id="inst-progress-val-{{ $isSimulated ? $activity->id . '-' . $inst->user_id : $inst->id }}" class="text-[9px] text-gray-400 font-bold w-5 tabular-nums js-member-progress-val">{{ $inst->progress }}%</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-right tabular-nums">
                                            <div class="flex flex-col items-end">
                                                <span class="text-xs font-black text-gray-900 dark:text-white tabular-nums flex items-center gap-1.5">
                                                    @if($isInstActive)
                                                        <span class="relative flex h-1.5 w-1.5">
                                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-red-500"></span>
                                                        </span>
                                                    @endif
                                                    {{ $instSeconds > 0 ? $instFormatted : '—' }}
                                                </span>
                                                @if($isInstActive)
                                                    <span class="text-[7px] font-bold text-red-500 uppercase tracking-widest mt-0.5 animate-pulse">{{ __('En curso') }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-right">
                                            @if ($inst->status_value !== 'completed' && ($team->isCoordinator(auth()->user()) || auth()->id() !== ($isSimulated ? $inst->user_id : $inst->assigned_user_id)))
                                                <button onclick="event.stopPropagation(); nudgeUser('{{ $isSimulated ? $activity->id : $inst->id }}', '{{ $isSimulated ? $inst->user_id : ($inst->assigned_user_id ?? '') }}')"
                                                    class="p-2 text-violet-600 hover:bg-violet-50 dark:text-violet-400 dark:hover:bg-violet-400/10 rounded-lg transition-all"
                                                    title="{{ __('activities.nudge_user') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block"
                                                        fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                        stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                                    </svg>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if (!$isRoadmap && $activity->assignedGroups->isNotEmpty())
                        <div class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-800/60">
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-widest font-bold mb-3">
                                {{ __('activities.groups') }}
                            </p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($activity->assignedGroups as $g)
                                    <span class="bg-violet-50 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 text-[9px] px-2 py-1 rounded-lg font-bold uppercase tracking-wider border border-violet-100 dark:border-violet-800/50 shadow-sm">
                                        {{ $g->name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Sección de Notas Privadas: Disponible para cualquier usuario con acceso a la tarea --}}
            @if($personalInstance)
                <div class="bg-white dark:bg-gray-900 border border-amber-100 dark:border-amber-900/30 rounded-2xl p-5 shadow-sm mt-5">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/40 flex items-center justify-center text-amber-600 border border-amber-100 dark:border-amber-800/50">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </div>
                            <h3 class="text-xs font-black text-amber-700 dark:text-amber-400 uppercase tracking-widest">
                                {{ __('activities.private_notes') }}
                            </h3>
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="printPrivateNotes()" class="p-1.5 bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300 rounded-xl transition-all border border-amber-100 dark:border-amber-800/50 shadow-sm flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest" title="Imprimir notas privadas">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                Imprimir
                            </button>
                        </div>
                    </div>
                    
                    <form action="{{ route('teams.activities.private-notes.update', [$team, $personalInstance]) }}" method="POST" id="private-notes-form">
                        @csrf
                        <div style="max-height: 400px; overflow-y: auto;" class="max-h-[500px] overflow-y-auto custom-scrollbar">
                            <x-markdown-editor 
                                name="content" 
                                id="reply-content-private"
                                :value="old('content', $personalInstance->currentPrivateNote?->content)"
                                :label="null"
                                rows="6"
                                placeholder="Escribe aquí tus notas personales sobre esta tarea... Nadie más podrá verlas."
                                :upload-url="route('teams.forum.upload_image', $team)"
                            />
                        </div>
                        <div class="mt-3 flex justify-end">
                            <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-xl shadow-lg shadow-amber-500/20 transition-all active:scale-95">
                                {{ __('activities.save_notes') }}
                            </button>
                        </div>
                    </form>
                </div>
            @endif








            <!-- Attachments Section -->
            <div x-data="{}"
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm dark:shadow-none transition-colors">

                {{-- ── Header premium de la sección ── --}}
                <div class="flex items-start justify-between mb-5 gap-3">
                    {{-- Título con icono --}}
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-violet-100 dark:bg-violet-500/10 flex items-center justify-center text-violet-600 dark:text-violet-400 shrink-0 shadow-sm border border-violet-200/50 dark:border-violet-500/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-800 dark:text-white">{{ __('activities.attachments') }}</h3>
                            <p class="text-[10px] text-gray-400 dark:text-gray-500 font-medium uppercase tracking-wide">{{ __('Máx. :size por archivo', ['size' => ini_get('upload_max_filesize')]) }}</p>
                        </div>
                    </div>

                    {{-- Barra de acciones premium --}}
                    <div class="flex flex-wrap items-center justify-end gap-2">

                        {{-- Botón: Subir archivo --}}
                        <form id="attachment-form" action="{{ route('teams.activities.attachments.upload', [$team, $activity]) }}" method="POST" enctype="multipart/form-data" class="m-0 p-0 inline-block">
                            @csrf
                            <label class="group inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold
                                   bg-violet-50 dark:bg-violet-500/10 text-violet-600 dark:text-violet-400
                                   border border-violet-200 dark:border-violet-500/20
                                   hover:bg-violet-600 hover:text-white hover:border-violet-600
                                   dark:hover:bg-violet-500 dark:hover:text-white dark:hover:border-violet-500
                                   shadow-sm hover:shadow-violet-500/25 hover:shadow-md
                                   transition-all duration-200 active:scale-95 cursor-pointer mb-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform group-hover:-translate-y-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                {{ __('activities.add_attachment') }}
                                <input type="file" id="attachment-input" name="files[]" multiple
                                    onchange="handleAttachmentUpload(this)" class="hidden">
                            </label>
                        </form>

                        {{-- Botón: Nuevo documento (dropdown) --}}
                        @can('update', $activity)
                        <div x-data="{ open: false }" class="relative" @click.outside="open = false">
                            <button type="button" @click="open = !open"
                                class="group inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold
                                       bg-teal-50 dark:bg-teal-500/10 text-teal-600 dark:text-teal-400
                                       border border-teal-200 dark:border-teal-500/20
                                       hover:bg-teal-600 hover:text-white hover:border-teal-600
                                       dark:hover:bg-teal-500 dark:hover:text-white dark:hover:border-teal-500
                                       shadow-sm hover:shadow-teal-500/25 hover:shadow-md
                                       transition-all duration-200 active:scale-95">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                {{ __('Nuevo documento') }}
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            {{-- Formularios ocultos para cada tipo --}}
                            <form id="create-docx-form" method="POST" action="{{ route('onlyoffice.activity.create', [$team, $activity]) }}" target="_blank">
                                @csrf <input type="hidden" name="type" value="docx">
                            </form>
                            <form id="create-xlsx-form" method="POST" action="{{ route('onlyoffice.activity.create', [$team, $activity]) }}" target="_blank">
                                @csrf <input type="hidden" name="type" value="xlsx">
                            </form>
                            <form id="create-pptx-form" method="POST" action="{{ route('onlyoffice.activity.create', [$team, $activity]) }}" target="_blank">
                                @csrf <input type="hidden" name="type" value="pptx">
                            </form>

                            <div x-show="open"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                x-cloak
                                class="absolute right-0 top-full mt-2 w-56 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-2xl z-50 overflow-hidden ring-1 ring-black/5 dark:ring-white/5">
                                <div class="px-3 pt-3 pb-1.5">
                                    <p class="text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Crear con OnlyOffice</p>
                                </div>
                                <button type="button" onclick="sessionStorage.setItem('needs_office_reload', '1'); document.getElementById('create-docx-form').submit()"
                                    class="w-full flex items-center gap-3 px-3 py-2.5 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors group/item">
                                    <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0 group-hover/item:scale-110 transition-transform">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 7V3.5L18.5 9H13zM9 13h6v1H9v-1zm0 2h6v1H9v-1zm0 2h4v1H9v-1z"/></svg>
                                    </div>
                                    <div class="text-left">
                                        <div class="text-xs font-bold text-gray-800 dark:text-white">Documento de texto</div>
                                        <div class="text-[10px] text-gray-400 font-medium">.docx · Word / Writer</div>
                                    </div>
                                </button>
                                <button type="button" onclick="sessionStorage.setItem('needs_office_reload', '1'); document.getElementById('create-xlsx-form').submit()"
                                    class="w-full flex items-center gap-3 px-3 py-2.5 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors group/item">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0 group-hover/item:scale-110 transition-transform">
                                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 7V3.5L18.5 9H13zM8 12h2v1H8v-1zm0 2h2v1H8v-1zm0 2h2v1H8v-1zm3-4h5v1h-5v-1zm0 2h5v1h-5v-1zm0 2h5v1h-5v-1z"/></svg>
                                    </div>
                                    <div class="text-left">
                                        <div class="text-xs font-bold text-gray-800 dark:text-white">Hoja de cálculo</div>
                                        <div class="text-[10px] text-gray-400 font-medium">.xlsx · Excel / Calc</div>
                                    </div>
                                </button>
                                <button type="button" onclick="sessionStorage.setItem('needs_office_reload', '1'); document.getElementById('create-pptx-form').submit()"
                                    class="w-full flex items-center gap-3 px-3 py-2.5 hover:bg-orange-50 dark:hover:bg-orange-900/20 transition-colors group/item">
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
                        @endcan

                        {{-- Botón: Google Drive --}}
                        @php 
                            $isTeamLinked = auth()->user()->teams()->where('team_id', $team->id)->wherePivotNotNull('google_token')->exists();
                        @endphp
                        @if($isTeamLinked)
                            <button type="button" @click="$dispatch('open-drive-picker', { id: {{ $activity->id }}, type: 'App\\Models\\Activity' })"
                                class="group inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold
                                       bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400
                                       border border-blue-200 dark:border-blue-500/20
                                       hover:bg-blue-600 hover:text-white hover:border-blue-600
                                       dark:hover:bg-blue-500 dark:hover:text-white dark:hover:border-blue-500
                                       shadow-sm hover:shadow-blue-500/25 hover:shadow-md
                                       transition-all duration-200 active:scale-95">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 48 48">
                                    <path fill="currentColor" opacity=".8" d="M17 6H11L2 22l3 5h6l9-16z"/>
                                    <path fill="currentColor" opacity=".5" d="M37 42H11l-9-15 4-7h26l9 16z"/>
                                    <path fill="currentColor" d="M15 6l9 16 9-16H15z"/>
                                </svg>
                                Google Drive
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            </button>
                        @else
                            <a href="{{ route('profile.edit', ['tab' => 'integrations']) }}"
                                class="group inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold
                                       bg-gray-50 dark:bg-gray-800 text-gray-400 dark:text-gray-500
                                       border border-gray-200 dark:border-gray-700
                                       hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-gray-300
                                       transition-all duration-200 active:scale-95">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101M10.172 13.828a4 4 0 015.656 0l4 4a4 4 0 01-5.656 5.656l-1.102-1.101"/>
                                </svg>
                                Vincular Drive
                            </a>
                        @endif
                    </div>
                </div>



                @if ($allAttachments->isEmpty())
                    <p class="text-xs text-gray-400 italic">{{ __('activities.no_attachments') }}</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach ($allAttachments as $attachment)
                            @php 
                                $isFromMe = $attachment->user_id === auth()->id();
                                $isTaskType = $attachment->attachable_type === 'App\Models\Activity';
                                $isFromParent = $isTaskType && $attachment->attachable_id === $activity->parent_id;
                                $isFromChild = $isTaskType && $attachment->attachable_id !== $activity->id && $attachment->attachable_id !== $activity->parent_id;
                            @endphp
                            <div
                                class="group flex items-center justify-between p-3 {{ $isFromParent ? 'bg-violet-50/30 dark:bg-violet-900/10 border-violet-100/50' : 'bg-gray-50 dark:bg-gray-800/50 border-gray-100 dark:border-gray-700/50' }} border rounded-xl hover:border-violet-200 dark:hover:border-violet-800 transition-all">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div
                                        class="w-10 h-10 rounded-lg flex items-center justify-center shadow-sm border shrink-0 {{ $attachment->storage_provider === 'google' ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-100 dark:border-blue-800' : ($isFromParent ? 'bg-violet-50 dark:bg-gray-800 text-violet-500 border-gray-100 dark:border-gray-700' : 'bg-white dark:bg-gray-800 text-violet-600 dark:text-violet-400 border-gray-100 dark:border-gray-700') }}">
                                        @if(!$attachment->exists)
                                            <div class="text-red-500/50">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                            </div>
                                        @elseif($attachment->storage_provider === 'google')
                                            <svg class="w-6 h-6" viewBox="0 0 48 48">
                                                <path fill="#FFC107" d="M17 6H11L2 22l3 5h6l9-16z"/>
                                                <path fill="#2196F3" d="M37 42H11l-9-15 4-7h26l9 16z"/>
                                                <path fill="#4CAF50" d="M15 6l9 16 9-16H15z"/>
                                            </svg>
                                        @elseif(str_starts_with($attachment->mime_type ?? '', 'image/'))
                                            <img src="{{ route('teams.attachments.view', [$team, $attachment]) }}" alt="Preview" class="w-full h-full object-cover rounded-lg" />
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[12px] font-bold text-gray-800 dark:text-white truncate"
                                            title="{{ $attachment->file_name }}">
                                            @if(!$attachment->exists)
                                                <span class="text-gray-400 line-through decoration-red-500/30">{{ $attachment->file_name }}</span>
                                            @elseif($attachment->storage_provider === 'google' && $attachment->web_view_link)
                                                <a href="{{ $attachment->web_view_link }}" 
                                                   target="_blank" 
                                                   class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors flex items-center gap-1">
                                                    {{ $attachment->file_name }}
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                </a>
                                            @else
                                                <a href="{{ route('teams.attachments.download', [$team, $attachment]) }}" 
                                                   target="_blank" 
                                                   class="hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                                    {{ $attachment->file_name }}
                                                </a>
                                            @endif
                                        </p>
                                        <p class="text-[10px] text-gray-400 flex items-center gap-1.5">
                                            @if(!$attachment->exists)
                                                <span class="text-red-500/70 font-bold uppercase tracking-tighter">{{ __('Archivo Purgado') }}</span>
                                            @elseif($attachment->storage_provider === 'google')
                                                <span class="bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-1.5 rounded font-black uppercase text-[8px]">Google Drive</span>
                                            @else
                                                {{ number_format($attachment->file_size / 1024 / 1024, 2) }} MB
                                            @endif
                                            •
                                            @if($isFromParent) 
                                                <span class="text-violet-500 font-bold uppercase tracking-tighter">{{ __('activities.shared') ?? 'Plan' }}</span>
                                            @elseif($isFromChild)
                                                <span class="text-amber-500 font-bold uppercase tracking-tighter">{{ $attachment->attachable->assignedUser?->name ?? 'Equipo' }}</span>
                                            @else
                                                {{ $attachment->created_at->diffForHumans() }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div
                                    class="flex items-center gap-0.5 opacity-60 group-hover:opacity-100 transition-all duration-200">
                                    @if($attachment->storage_provider === 'local' && auth()->user()->google_token)
                                        <form action="{{ route('teams.attachments.to-drive', [$team, $attachment]) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" 
                                                class="p-1.5 text-gray-500 hover:text-blue-600 transition-colors"
                                                title="Subir a Google Drive">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                    
                                    {{-- Botón de Historial --}}
                                    <button type="button" 
                                        onclick="showAttachmentHistory({{ $attachment->id }})"
                                        class="p-1.5 text-amber-500 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 transition-colors"
                                        title="{{ __('activities.history') ?? 'Ver histórico' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>

                                    {{-- Botón de Inyección IA --}}
                                    <button type="button" 
                                        @click="$dispatch('ai:analyze-file', { 
                                            fileName: '{{ addslashes($attachment->file_name) }}', 
                                            fileId: {{ $attachment->id }},
                                            fileUrl: '{{ $attachment->storage_provider === 'google' ? $attachment->web_view_link : route('teams.attachments.view', [$team, $attachment]) }}',
                                            fileType: '{{ $attachment->mime_type }}',
                                            taskId: {{ $activity->id }},
                                            teamId: {{ $team->id }},
                                            autoSubmit: false 
                                        })"
                                        class="p-1.5 text-violet-500 hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-300 transition-colors"
                                        title="Preguntar a la IA sobre este archivo">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </button>

                                    @if($attachment->is_office_compatible)
                                        <a href="{{ route('onlyoffice.edit', $attachment) }}"
                                            target="_blank" rel="noopener noreferrer"
                                            onclick="sessionStorage.setItem('needs_office_reload', '1')"
                                            class="p-1.5 text-teal-600 hover:text-teal-700 dark:text-teal-400 dark:hover:text-teal-300 transition-colors"
                                            title="{{ __('Editar con Office') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                              <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                    @endif

                                    <a href="{{ route('teams.attachments.download', [$team, $attachment]) }}"
                                        target="_blank" rel="noopener noreferrer"
                                        class="p-1.5 text-gray-500 hover:text-violet-600 dark:hover:text-violet-400 transition-colors"
                                        title="{{ __('activities.view_or_download') ?? 'Ver o descargar' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                    @can('delete', $attachment)
                                        @if($attachment->storage_provider === 'local' && str_starts_with($attachment->mime_type, 'image/'))
                                            <button type="button"
                                                onclick="editAttachmentImage({{ $attachment->id }}, '{{ route('teams.attachments.view', [$team, $attachment]) }}')"
                                                class="p-1.5 text-gray-500 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/20 rounded-lg transition-all"
                                                title="{{ __('Editar Imagen') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
</svg>
                                            </button>
                                        @endif
                                        <button type="button"
                                            onclick="renameAttachment({{ $attachment->id }}, '{{ addslashes($attachment->file_name) }}')"
                                            class="p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-all"
                                            title="{{ __('activities.edit') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                        <form
                                            action="{{ route('teams.attachments.destroy', [$team, $attachment]) }}"
                                            method="POST" class="inline"
                                            id="delete-attachment-{{ $attachment->id }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                onclick="confirmAttachmentDelete({{ $attachment->id }}, '{{ $attachment->storage_provider }}')"
                                                class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all"
                                                title="{{ __('activities.delete') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    @else
                                        @if(!$isTaskType || $attachment->attachable_id !== $activity->id)
                                            {{-- Informativo para compartidos si el usuario normal no tiene permisos --}}
                                            <span class="p-1.5 text-gray-300 dark:text-gray-600 cursor-help"
                                                title="{{ $isFromParent ? 'Este archivo es del Plan Maestro y debe eliminarse desde allí.' : 'Este archivo pertenece a una subtarea.' }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-40"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </span>
                                        @endif
                                    @endcan
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
