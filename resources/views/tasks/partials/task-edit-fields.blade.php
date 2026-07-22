            <!-- 1. Plan Maestro Related (Only if template/child) -->
            @if ($task->is_template)
                @php
                    $isCollabShow = isset($task->metadata['assignment_mode']) && $task->metadata['assignment_mode'] === 'shared';
                @endphp
                <div class="{{ $isCollabShow ? 'bg-blue-50/30 dark:bg-blue-900/10 border-blue-100 dark:border-blue-900/30' : 'bg-violet-50/30 dark:bg-violet-900/10 border-violet-100 dark:border-violet-900/30' }} border rounded-2xl p-4 shadow-sm space-y-4">
                    <p class="text-[10px] font-bold {{ $isCollabShow ? 'text-blue-600 dark:text-blue-400' : 'text-violet-600 dark:text-violet-400' }} uppercase tracking-widest">
                        {{ $isCollabShow ? (__('activities.collaborative_task_actions') ?? 'ACCIONES DE TAREA COLABORATIVA') : __('ACCIONES DEL PLAN MAESTRO') }}
                    </p>
                    
                    <div class="space-y-2">
                        @if ($task->status !== 'completed')
                            <button onclick="updateTaskStatus('completed')"
                                class="w-full flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold py-3 rounded-xl transition-all shadow-md shadow-emerald-600/20">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ $isCollabShow ? (__('activities.close_collaborative_task') ?? 'Cerrar Tarea Colaborativa') : __('Cerrar Plan Maestro') }}
                            </button>
                        @else
                            <button onclick="updateTaskStatus('in_progress')"
                                class="w-full flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-400 text-white text-xs font-bold py-3 rounded-xl transition-all shadow-md shadow-amber-500/20">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                {{ $isCollabShow ? (__('activities.reopen_collaborative_task') ?? 'Reabrir Tarea Colaborativa') : __('Reabrir Plan Maestro') }}
                            </button>
                        @endif

                        @if ($task->status !== 'blocked')
                            <button onclick="updateTaskStatus('blocked')"
                                class="w-full flex items-center justify-center gap-2 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-xs font-bold py-3 rounded-xl transition-all border border-red-100 dark:border-red-900/30">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                {{ __('Informar bloqueo') }}
                            </button>
                        @else
                            <button onclick="updateTaskStatus('in_progress')"
                                class="w-full flex items-center justify-center gap-2 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 text-xs font-bold py-3 rounded-xl transition-all border border-emerald-100 dark:border-emerald-900/30 shadow-sm active:scale-[0.98]">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                </svg>
                                {{ __('Quitar bloqueo') }}
                            </button>
                        @endif
                    </div>

                    <div class="pt-2 border-t border-violet-100 dark:border-violet-900/20">
                        <div class="flex items-center justify-between text-[9px] font-black uppercase tracking-widest text-violet-400 mb-1">
                            <span>{{ __('tasks.roadmap_progress') }}</span>
                            <span class="js-global-progress-val">{{ $task->progress }}%</span>
                        </div>
                        <div class="w-full h-1 bg-violet-100 dark:bg-violet-900/30 rounded-full overflow-hidden">
                            <div class="h-full bg-violet-500 transition-all duration-1000 js-global-progress-bar" style="width: {{ $task->progress }}%"></div>
                        </div>
                    </div>
                </div>
            @elseif ($task->isInstance())
                <div class="bg-violet-50/50 dark:bg-violet-500/5 border border-violet-100 dark:border-violet-500/10 rounded-2xl p-4 space-y-3 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white dark:bg-violet-500/10 flex items-center justify-center text-violet-600 dark:text-violet-400 shrink-0 shadow-sm border border-violet-50 dark:border-violet-500/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-black text-violet-700 dark:text-violet-400 uppercase tracking-widest">{{ __('Plan Maestro Relacionado') }}</p>
                            @if ($team->isCoordinator(auth()->user()))
                                <div class="mt-1">
                                    <select onchange="reassignTask({{ $task->id }}, this.value)" class="w-full text-[10px] bg-white dark:bg-violet-900 border border-violet-100 dark:border-violet-800 rounded-lg px-2 py-1 shadow-sm font-bold text-violet-700 dark:text-violet-300 cursor-pointer">
                                        <option value="" disabled {{ !$task->assigned_user_id ? 'selected' : '' }}>{{ __('Reasignar a...') }}</option>
                                        <option value="unassign">-- {{ __('Pendiente') }} --</option>
                                        @foreach($team->members()->orderBy('name')->get() as $member)
                                            <option value="{{ $member->id }}" {{ $task->assigned_user_id === $member->id ? 'selected' : '' }}>{{ $member->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @else
                                <p class="text-[11px] font-bold text-violet-900 dark:text-violet-200 truncate">{{ $task->assignedUser?->name ?? __('Sin asignar') }}</p>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('teams.tasks.show', [$team, $task->parent_id]) }}" class="block w-full text-center text-[10px] font-black uppercase tracking-widest text-violet-600 dark:text-violet-300 hover:bg-violet-600 hover:text-white dark:hover:bg-violet-500 py-2 bg-white dark:bg-violet-500/10 rounded-xl border border-violet-100 dark:border-violet-500/20 transition-all">
                        {{ __('VER PLAN MAESTRO') }}
                    </a>
                </div>
            @endif

            <!-- 2. TU EJECUCIÓN Card -->
            @if ($personalInstance)
                <div class="bg-violet-50/40 dark:bg-violet-900/10 border border-violet-100/50 dark:border-violet-800/50 rounded-2xl p-5 space-y-5 shadow-sm transition-colors relative overflow-hidden">
                    <p class="text-[10px] text-violet-600 dark:text-violet-400 uppercase tracking-widest font-black flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        {{ __('TU EJECUCIÓN') }}
                    </p>

                    <div class="space-y-2.5">
                        @if ($personalInstance->status !== 'completed')
                            <button onclick="updateTaskStatus('completed', {{ $personalInstance->id }})"
                                class="w-full flex items-center justify-center gap-2 bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold py-3.5 rounded-xl transition-all shadow-md shadow-violet-600/20 active:scale-[0.98]">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ __('Marcar como completada') }}
                            </button>
                        @else
                            <button onclick="updateTaskStatus('pending', {{ $personalInstance->id }})"
                                class="w-full flex items-center justify-center gap-2 bg-white dark:bg-gray-800 text-violet-700 dark:text-violet-300 hover:bg-violet-50 dark:hover:bg-violet-900/50 text-xs font-bold py-3.5 rounded-xl transition-all border border-violet-200 dark:border-violet-700 shadow-sm active:scale-[0.98]">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                {{ __('Reabrir tarea') }}
                            </button>
                        @endif

                        @if ($personalInstance->status !== 'blocked')
                            <button onclick="updateTaskStatus('blocked', {{ $personalInstance->id }})"
                                class="w-full flex items-center justify-center gap-2 bg-red-50/80 hover:bg-red-100 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-xs font-bold py-3.5 rounded-xl transition-all border border-red-100/50 dark:border-red-900/30 active:scale-[0.98]">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                {{ __('Informar un bloqueo') }}
                            </button>
                        @else
                            <button onclick="updateTaskStatus('in_progress', {{ $personalInstance->id }})"
                                class="w-full flex items-center justify-center gap-2 bg-emerald-50/80 hover:bg-emerald-100 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 text-xs font-bold py-3.5 rounded-xl transition-all border border-emerald-100/50 dark:border-emerald-900/30 active:scale-[0.98] shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                </svg>
                                {{ __('Quitar bloqueo') }}
                            </button>
                        @endif
                    </div>

                    <div class="relative pt-4 border-t border-violet-100/30 dark:border-violet-800/30">
                        <label class="flex items-center justify-between text-[9px] text-violet-400/80 dark:text-violet-500/50 uppercase tracking-widest font-black mb-3">
                            <span>{{ __('TU PROGRESO') }}</span>
                            <div class="flex items-center gap-1 min-w-[3rem] justify-end font-bold">
                                <span id="personal-progress-val" class="text-violet-600 dark:text-violet-400 tabular-nums text-sm">{{ $personalInstance->progress_percentage }}</span>
                                <span class="text-violet-400 text-[10px]">%</span>
                            </div>
                        </label>
                        <div class="flex items-center gap-3">
                            <input type="range" min="0" max="100" value="{{ $personalInstance->progress_percentage }}"
                                class="flex-1 h-1 bg-violet-100 dark:bg-violet-900/50 rounded-full appearance-none cursor-pointer accent-violet-600 js-member-progress-slider"
                                oninput="document.getElementById('personal-progress-val').innerText = this.value"
                                onchange="updateTaskProgress(this.value, {{ $personalInstance->id }}, '{{ $personalInstance->status }}')">
                        </div>
                    </div>
                </div>
            @endif

            <!-- 3. TIEMPO DEDICADO Card -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm transition-colors">
                <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-widest font-black mb-4">{{ __('TIEMPO DEDICADO') }}</p>
                <div class="flex items-center justify-between">
                    <div x-data="{ 
                        active: {{ auth()->user()->isTrackingTask($personalInstance->id ?? $task->id) ? 'true' : 'false' }},
                        seconds: {{ auth()->user()->getTaskTrackingSeconds($personalInstance->id ?? $task->id) }},
                        totalToday: '{{ $task->totalTrackedTimeTodayHuman() }}',
                        
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
                        @include('tasks.partials.task-timer-button', ['task' => $personalInstance ?? $task])
                    </div>
                </div>
            </div>

            <!-- 5. Propietario Card -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm dark:shadow-none">
                <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase tracking-widest font-bold mb-3">
                    {{ __('tasks.owner') }}
                </p>
                <div class="flex items-center gap-3">
                    <img src="{{ $task->creator ? $task->creator->profile_photo_url : 'https://ui-avatars.com/api/?name=?&color=7F9CF5&background=EBF4FF' }}" 
                        alt="{{ $task->creator?->name ?? '?' }}"
                        class="w-10 h-10 rounded-xl object-cover shadow-sm border border-gray-100 dark:border-gray-800 shrink-0">
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-gray-700 dark:text-gray-300 truncate">{{ $task->creator?->name ?? '—' }}</p>
                        <p class="text-[10px] text-gray-500 dark:text-gray-600 uppercase font-black tracking-tighter">{{ $task->created_at->format('d M Y') }}</p>
                    </div>
                </div>
            </div>

            <!-- 6. Estado Card -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 space-y-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide font-bold">{{ __('tasks.status') }}</span>
                    <span class="text-[11px] font-bold px-3 py-1 rounded-full border {{ $statusColor }} uppercase tracking-wider">
                        {{ __('tasks.statuses.' . $task->status) }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide font-bold">{{ __('tasks.quadrant') }}</span>
                    <span class="text-[11px] font-bold {{ $qCfg['color'] }} uppercase tracking-wider">
                        Q{{ $q }}: {{ __('tasks.quadrants.' . $q . '.label') }}
                    </span>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-800">
                    <span class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wide">{{ __('tasks.visibility') }}</span>
                    <div class="flex items-center gap-1.5">
                        @if($task->privacy_level === 'private')
                            <div class="w-2 h-2 rounded-full bg-amber-500"></div>
                            <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">
                                {{ __('tasks.private') }}
                            </span>
                        @elseif($task->privacy_level === 'semi-private')
                            <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
                            <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">
                                {{ __('Semiprivada') }}
                            </span>
                        @else
                            <div class="w-2 h-2 rounded-full bg-violet-500"></div>
                            <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">
                                {{ __('tasks.public') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 7. Prioridad Card -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 space-y-3 shadow-sm">
                @foreach ([['tasks.priority', $task->priority, 'tasks.priorities'], ['tasks.urgency', $task->urgency, 'tasks.urgencies']] as [$lbl, $val, $map])
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wide">{{ __($lbl) }}</span>
                        <span class="text-xs font-semibold text-gray-800 dark:text-gray-200 {{ $map === 'tasks.priorities' ? 'js-priority-label' : '' }}">{{ __($map . '.' . $val) }}</span>
                    </div>
                @endforeach

                <div class="pt-2 border-t border-gray-50 dark:border-gray-800/50 mt-2">
                    <button id="btn-auto-priority" onclick="toggleAutoPriority()" 
                        class="w-full flex items-center justify-between px-3 py-2 rounded-xl transition-all duration-300 {{ $task->auto_priority ? 'bg-violet-50 dark:bg-violet-900/20 text-violet-600 dark:text-violet-400 border border-violet-100 dark:border-violet-800' : 'bg-gray-50 dark:bg-gray-800/50 text-gray-500 dark:text-gray-400 border border-transparent hover:border-gray-200 dark:hover:border-gray-700' }}">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 {{ $task->auto_priority ? 'animate-pulse' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <span class="text-[10px] font-bold uppercase tracking-wider">{{ __('Prioridad Automática') }}</span>
                        </div>
                        <div class="relative inline-flex h-4 w-7 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $task->auto_priority ? 'bg-violet-500' : 'bg-gray-200 dark:bg-gray-700' }}">
                            <span class="pointer-events-none inline-block h-3 w-3 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $task->auto_priority ? 'translate-x-3' : 'translate-x-0' }}"></span>
                        </div>
                    </button>
                    @if($task->due_date)
                        <p class="text-[9px] text-gray-400 mt-1.5 px-1 italic">
                            {{ __('La prioridad escalará según el tiempo restante hasta la entrega.') }}
                        </p>
                    @endif
                </div>
            </div>

            <!-- Expediente Card -->
            @if ($task->expediente)
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm relative overflow-hidden">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 rounded-lg bg-violet-50 dark:bg-violet-900/20 text-violet-600 dark:text-violet-400 flex items-center justify-center shrink-0 border border-violet-100 dark:border-violet-500/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('Expediente') }}</h4>
                            <p class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ $task->expediente->code }}</p>
                        </div>
                    </div>
                    <a href="{{ route('teams.expedientes.show', [$team, $task->expediente]) }}" class="w-full flex items-center justify-center gap-2 text-[10px] font-black uppercase tracking-widest text-violet-700 dark:text-violet-300 hover:bg-violet-50 dark:hover:bg-violet-900/30 py-2 border border-violet-100 dark:border-violet-800/50 rounded-xl transition-all shadow-sm">
                        {{ __('Ver Expediente') }}
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            @elseif (auth()->user()->can('update', $task))
                <div class="bg-gray-50 dark:bg-gray-900/50 border border-dashed border-gray-200 dark:border-gray-800 rounded-2xl p-4 text-center shadow-sm">
                    <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold mb-2">{{ __('Sin Expediente') }}</p>
                    <a href="{{ route('teams.tasks.edit', [$team, $task]) }}" class="text-[9px] font-black uppercase tracking-wider text-violet-600 dark:text-violet-400 hover:underline">
                        {{ __('Vincular uno ahora') }}
                    </a>
                </div>
            @endif

            <!-- Cita Previa Card -->
            @if ($task->appointment)
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm relative overflow-hidden">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 rounded-lg bg-cyan-50 dark:bg-cyan-900/20 text-cyan-600 dark:text-cyan-400 flex items-center justify-center shrink-0 border border-cyan-100 dark:border-cyan-500/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('Cita Previa') }}</h4>
                            <p class="text-xs font-bold text-gray-900 dark:text-white truncate">Loc: {{ $task->appointment->localizador }}</p>
                        </div>
                    </div>
                    @if(in_array($task->appointment->modality, ['jitsi', 'meet']))
                        <a href="{{ route('public.appointments.video.auth', $task->appointment) }}?localizador={{ $task->appointment->localizador }}" target="_blank" class="w-full flex items-center justify-center gap-2 text-[10px] font-black uppercase tracking-widest text-cyan-700 dark:text-cyan-300 hover:bg-cyan-50 dark:hover:bg-cyan-900/30 py-2 border border-cyan-100 dark:border-cyan-800/50 rounded-xl transition-all shadow-sm">
                            💻 {{ __('Iniciar Videoconferencia') }}
                        </a>
                    @else
                        <p class="w-full text-center text-[10px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 py-2 border border-gray-100 dark:border-gray-800/50 rounded-xl bg-gray-50 dark:bg-gray-800/50">
                            🏢 {{ __('Modalidad Presencial') }}
                        </p>
                    @endif
                </div>
            @endif

            <!-- 8. Fechas Card -->
            @if ($task->due_date || $task->scheduled_date)
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 space-y-3 shadow-sm">
                    @if ($task->scheduled_date)
                        <div class="flex items-center justify-between pb-3 border-b border-gray-50 dark:border-gray-800/50">
                            <span class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wide">{{ $task->is_autoprogrammable ? 'Inicio del Ciclo' : (__('tasks.scheduled_date') ?? 'Fecha de Inicio') }}</span>
                            <span class="text-[11px] text-gray-700 dark:text-gray-300 font-bold tabular-nums">{{ $task->scheduled_date->format('d M Y, H:i') }}</span>
                        </div>
                    @endif
                    @if ($task->due_date)
                        <div class="flex items-center justify-between pt-1">
                            <span class="text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wide">{{ __('tasks.due_date') }}</span>
                            <span class="text-[11px] text-gray-700 dark:text-gray-300 font-bold tabular-nums">{{ $task->due_date->format('d M Y, H:i') }}</span>
                        </div>
                    @endif
                </div>
            @endif

            <!-- 9. Autoprogramación Card -->
            @if ($task->is_autoprogrammable)
                <div class="bg-white dark:bg-gray-900 border border-violet-100 dark:border-violet-900/30 rounded-2xl p-4 space-y-3 shadow-sm border-l-4 border-l-violet-500">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] text-violet-600 dark:text-violet-400 uppercase tracking-widest font-bold">{{ __('tasks.autoprogram_active') ?? 'Autoprogramación JIT' }}</p>
                        <div class="w-2 h-2 rounded-full bg-violet-500 animate-pulse"></div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between text-[11px]">
                            <span class="text-gray-400 font-medium">{{ __('tasks.frequency') }}:</span>
                            <span class="font-bold text-gray-700 dark:text-gray-300">{{ __('tasks.' . ($task->autoprogram_settings['frequency'] ?? 'daily')) }} (x{{ $task->autoprogram_settings['interval'] ?? 1 }})</span>
                        </div>
                        @if(isset($task->autoprogram_settings['next_occurrence_at']))
                            <div class="flex justify-between text-[11px] pt-1 border-t border-gray-50 dark:border-gray-800">
                                <span class="text-gray-400 font-medium">Próxima ocurrencia:</span>
                                <span class="text-violet-600 dark:text-violet-400 font-black">{{ \Carbon\Carbon::parse($task->autoprogram_settings['next_occurrence_at'])->format('d M Y') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- 10. Quota de disco Card -->
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ __('tasks.disk_quota') }}</h3>
                    <span class="text-[10px] text-gray-400 font-black tabular-nums">{{ number_format(auth()->user()->disk_used / 1024 / 1024, 1) }}MB / {{ number_format(auth()->user()->disk_quota / 1024 / 1024, 0) }}MB</span>
                </div>
                @php
                    $perc = auth()->user()->disk_quota > 0 ? (auth()->user()->disk_used / auth()->user()->disk_quota) * 100 : 0;
                    $barColor = $perc > 90 ? 'bg-red-500' : ($perc > 70 ? 'bg-amber-500' : 'bg-blue-500');
                @endphp
                <div class="w-full h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                    <div class="h-full {{ $barColor }} transition-all duration-1000 shadow-sm" style="width: {{ $perc }}%"></div>
                </div>
            </div>


            <!-- 11. Etiquetas (Capacidades) -->
            @php $taskSkills = $task->skills; @endphp
            @if($taskSkills->isNotEmpty())
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach($taskSkills as $skill)
                        <div class="group inline-flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-gray-900 border border-amber-100 dark:border-amber-900/40 rounded-xl shadow-sm hover:shadow-md hover:border-amber-300 dark:hover:border-amber-700 transition-all duration-300 cursor-default">
                            <div class="w-1.5 h-1.5 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 shadow-sm shadow-amber-500/20 group-hover:scale-125 transition-transform"></div>
                            <div class="flex flex-col min-w-0">
                                <span class="text-[9px] font-black text-amber-700 dark:text-amber-400 uppercase tracking-widest truncate leading-tight">{{ $skill->name }}</span>
                                <span class="text-[7px] text-amber-600/40 dark:text-amber-500/20 font-bold uppercase tracking-tighter truncate leading-none">{{ $skill->category }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

