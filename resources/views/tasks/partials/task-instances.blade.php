                <!-- Progress Dashboard -->
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm dark:shadow-none transition-colors">
                    
                    <!-- HEADER: Members & Progress Text -->
                    <div class="flex items-end justify-between mb-4">
                        <div>
                            <h3 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">
                                {{ $isRoadmap ? __('tasks.roadmap_progress') : __('teams.members') }}
                            </h3>
                            <div class="flex flex-wrap items-center gap-3">
                                <p class="text-2xl font-bold text-gray-900 dark:text-white heading leading-none">
                                    {{ $totalInst }} <span class="text-sm font-medium text-gray-400">{{ $totalInst == 1 ? __('tasks.assigned_to_one') : __('tasks.assigned_to_many') }}</span>
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
                                        {{ __('tasks.collaborative_hint') }}
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
                                    {{ __('tasks.blocker_detected') }}</p>
                                <p class="text-xs text-red-600/80 dark:text-red-400/70">
                                    {{ __('tasks.blocker_description') }}</p>
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
