            <!-- Task Name Card -->
            <div
                class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm dark:shadow-none transition-colors">
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                        {{ __('tasks.name') }}</h3>
                    @if ($task->is_template)
                        <span class="px-2 py-0.5 rounded-full bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300 text-[9px] font-black uppercase tracking-widest border border-violet-200 dark:border-violet-700/50 shadow-sm flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            {{ __('tasks.plan_master') }}
                        </span>
                    @endif
                    @if ($task->assigned_user_id === auth()->id() && $task->parent_id)
                        <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 text-[9px] font-black uppercase tracking-widest border border-emerald-200 dark:border-emerald-700/50 shadow-sm flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            {{ __('tasks.your_execution') }}
                        </span>
                    @endif
                </div>
                <p class="text-xl font-bold text-gray-900 dark:text-white heading leading-tight flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 shadow-sm shrink-0">
                        {{ __('Tarea') }}
                    </span>
                    {{ $task->title }}
                </p>
            </div>






            @php
                $displayDescription = $task->description ?: ($task->parent?->description ?? null);
                $displayObservations = $task->observations ?: ($task->parent?->observations ?? null);
            @endphp

            @if ($displayDescription)
                <div
                    class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm dark:shadow-none transition-colors">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                            {{ __('tasks.description') }}
                        </h3>
                        <div class="flex items-center gap-2">
                            @if($team->isCoordinator(auth()->user()) || auth()->id() === $task->assigned_user_id)
                            <button @click="$dispatch('ai:analyze-task', { taskId: {{ $task->id }}, teamId: {{ $team->id }}, taskTitle: '{{ addslashes($task->title) }}', section: 'description' })" 
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
                            {{ __('tasks.observations') }}
                        </h3>
                        <div class="flex items-center gap-2">
                            @if($team->isCoordinator(auth()->user()) || auth()->id() === $task->assigned_user_id)
                            <button @click="$dispatch('ai:analyze-task', { taskId: {{ $task->id }}, teamId: {{ $team->id }}, taskTitle: '{{ addslashes($task->title) }}', section: 'observaciones' })" 
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

            @if ($task->is_template || $task->children()->exists() || $task->assignedTo->isNotEmpty() || $task->assigned_user_id)
                @php
                    $isRoadmap = $task->is_template || $task->children()->exists();
                    $currentUser = auth()->user();
                    $isUserMgr = $team->isManager($currentUser);

                    if ($isRoadmap) {
                        $instancesQuery = $task->is_template ? $task->instances() : $task->children();
                        $withRelation = $task->is_template ? 'assignedUser' : 'assignedTo';
                        
                        // Coordinators/Managers and members see all instances in a roadmap to calculate global progress
                        $instances = $instancesQuery->getQuery()
                            ->with($withRelation)
                            ->get()
                            ->sortBy(function($inst) use ($task) {
                                if ($task->is_template) {
                                    return mb_strtolower(($inst->assignedUser?->name ?? '') . ' ' . $inst->title);
                                }
                                return mb_strtolower(($inst->assignedTo->first()?->name ?? '') . ' ' . $inst->title);
                            });
                    } else {
                        // For regular tasks, we "simulate" instances using the assigned users
                        $instances = $task->assignedTo->map(function($user) use ($task) {
                            return (object)[
                                'id' => $task->id, // Reference to same task
                                'name' => $task->title,
                                'status' => $task->status,
                                'progress' => $task->progress_percentage,
                                'assignedUser' => $user,
                                'timeLogs' => $task->timeLogs()->where('user_id', $user->id)->get(),
                                'is_simulated' => true,
                                'user_id' => $user->id
                            ];
                        });
                    }

                    $totalInst = $instances->count();
                    $sumProg = $isRoadmap ? $instances->sum('progress_percentage') : ($totalInst > 0 ? $task->progress_percentage * $totalInst : 0);
                    $prog = $totalInst > 0 ? $sumProg / $totalInst : 0;
                    $doneInst = $isRoadmap ? $instances->where('status', 'completed')->count() : ($task->status === 'completed' ? $totalInst : 0);
                    $hasBlocker = $isRoadmap ? $instances->where('status', 'blocked')->isNotEmpty() : ($task->status === 'blocked');
                @endphp

