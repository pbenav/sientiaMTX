<div x-data="{ 
    working: false,
    elapsed: 0,
    timer: null,
    loading: false,
    activeTask: null,
    activeTaskTeamId: null,
    activeTaskTitle: null,
    cthData: null,
    showInfoModal: false,
    showMoodModal: false,
    selectedMood: null,
    compact: {{ isset($compact) && $compact ? 'true' : 'false' }},
    
    init() {
        this.fetchStatus(true);
        setInterval(() => {
            if (this.working) this.elapsed++;
        }, 1000);

        // SYNC: If a task is started, ensure workday reflects it
        window.addEventListener('task-started', () => {
             this.working = true;
             this.fetchStatus(); 
        });
    },
    
    fetchStatus(isInit = false) {
        let url = '{{ route('time-logs.status') }}';
        if (isInit) url += '?init=1';
        fetch(url)
            .then(res => res.json())
            .then(data => {
                this.working = data.is_working;
                this.elapsed = Math.floor(data.workday_elapsed);
                this.activeTask = data.active_task_id;
                this.activeTaskTeamId = data.active_task_team_id;
                this.activeTaskTitle = data.active_task_title;
                if (data.cth) {
                    this.cthData = data.cth;
                }
            });
    },
    
    toggle() {
        this.loading = true;
        fetch('{{ route('time-logs.toggle-workday') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        })
        .then(res => res.json())
        .then(data => {
            this.working = data.status === 'started';
            if (!this.working) this.elapsed = 0;
            this.loading = false;
            // Dispatch event to update task buttons if any
            window.dispatchEvent(new CustomEvent('workday-toggled', { detail: { working: this.working } }));
            
            const triggerMood = () => {
                this.selectedMood = null;
                setTimeout(() => { this.showMoodModal = true; }, 300);
            };

            if (data.syncing_cth && data.cth_result) {
                this.fetchStatus(); // Refrescar los datos de CTH después de hacer toggle
                if (typeof window.Swal !== 'undefined') {
                    if (data.cth_result.success) {
                        window.Swal.fire({
                            toast: true,
                            position: 'bottom-end',
                            showConfirmButton: false,
                            timer: 4000,
                            timerProgressBar: true,
                            icon: 'success',
                            title: this.working ? 'Jornada Iniciada en CTH' : 'Jornada Detenida en CTH',
                            text: data.cth_result.message,
                            customClass: {
                                popup: 'bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-xl rounded-2xl',
                                title: 'text-sm font-bold text-gray-800 dark:text-white',
                                htmlContainer: 'text-xs text-emerald-600 dark:text-emerald-400 font-bold'
                            }
                        }).then(() => {
                            triggerMood();
                        });
                    } else {
                        if (data.cth_result.grace_closing_available) {
                            window.Swal.fire({
                                title: 'Gestión de Turnos Abiertos en CTH',
                                text: data.cth_result.message,
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#10b981',
                                cancelButtonColor: '#6b7280',
                                confirmButtonText: '⚡ Cerrar turnos antiguos automáticamente',
                                cancelButtonText: 'Cancelar',
                                customClass: {
                                    popup: 'bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-2xl rounded-2xl',
                                    title: 'text-lg font-bold text-gray-800 dark:text-white',
                                    htmlContainer: 'text-sm text-gray-600 dark:text-gray-300 font-medium'
                                }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    this.loading = true;
                                    fetch('{{ route('time-logs.apply-cth-grace-closing') }}', {
                                        method: 'POST',
                                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                                    }).then(r => r.json()).then(res => {
                                        this.loading = false;
                                        if (res.success) {
                                            window.Swal.fire({
                                                icon: 'success',
                                                title: '¡Turnos cerrados con éxito!',
                                                text: res.message,
                                                showConfirmButton: true,
                                                confirmButtonColor: '#10b981',
                                                confirmButtonText: 'Iniciar jornada ahora',
                                                customClass: {
                                                    popup: 'bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-2xl rounded-2xl',
                                                    title: 'text-lg font-bold text-gray-800 dark:text-white'
                                                }
                                            }).then(() => {
                                                this.toggle();
                                            });
                                        } else {
                                            window.Swal.fire('Error', res.message, 'error').then(() => triggerMood());
                                        }
                                    });
                                } else {
                                    triggerMood();
                                }
                            });
                        } else {
                            window.Swal.fire({
                                toast: true,
                                position: 'bottom-end',
                                showConfirmButton: false,
                                timer: 7000,
                                timerProgressBar: true,
                                icon: 'error',
                                title: 'Fallo de Sincronización CTH',
                                text: data.cth_result.message,
                                customClass: {
                                    popup: 'bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-2xl rounded-2xl border-l-4 border-l-red-500',
                                    title: 'text-sm font-black text-red-600 dark:text-red-400 uppercase tracking-wide',
                                    htmlContainer: 'text-xs text-gray-600 dark:text-gray-300 font-medium'
                                }
                            }).then(() => {
                                triggerMood();
                            });
                        }
                    }
                } else {
                    triggerMood();
                }
            } else {
                triggerMood();
            }
        });
    },
    
    formatTime(seconds) {
        if (seconds < 0) seconds = 0;
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;
        return [h, m, s].map(v => v < 10 ? '0' + v : v).join(':');
    },

    submitMood() {
        if (!this.selectedMood) return;
        fetch('{{ route('metrics.wellness.mood.store') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ score: this.selectedMood })
        }).then(() => {
            this.showMoodModal = false;
            if (typeof window.Swal !== 'undefined') {
                window.Swal.fire({ toast: true, position: 'bottom-end', icon: 'success', title: '¡Energía registrada!', showConfirmButton: false, timer: 3000, customClass: { popup: 'bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-xl rounded-2xl' } });
            }
        });
    },

    formatCthTime(timeString) {
        if (!timeString) return '--:--:--';
        const date = new Date(timeString);
        if (isNaN(date.getTime())) return timeString;
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
}" class="flex items-center gap-3 relative" :class="compact ? 'w-full justify-start' : ''">
    
    <!-- Botón de Conmutación (Ancla fija a la izquierda) -->
    <button @click="toggle()" :disabled="loading"
            class="flex items-center justify-center transition-all duration-300 shadow-sm border font-bold"
            :class="[
                compact ? 'w-10 h-10 rounded-xl shrink-0' : 'px-4 py-2 rounded-xl text-xs sm:px-3 sm:py-2',
                working 
                    ? 'bg-red-50 border-red-100 text-red-600 hover:bg-red-100 dark:bg-red-900/30 dark:border-red-800 dark:text-red-400' 
                    : 'bg-white border-gray-200 text-gray-700 hover:border-violet-500 hover:text-violet-600 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300'
            ]"
            :title="working ? '{{ __('tasks.stop_workday') }}' : '{{ __('tasks.start_workday') }}'">
        
        <template x-if="!loading">
            <div class="flex items-center justify-center">
                <svg x-show="!working" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg x-show="working" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <rect x="7" y="7" width="10" height="10" rx="2" stroke-width="2.5" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </template>

        <template x-if="loading">
            <svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </template>
    </button>

    <!-- Contador Digital y Tarea Activa (Compacto y responsivo) -->
    <div x-show="working" x-cloak
         class="flex items-center gap-1.5 px-2 py-1.5 bg-violet-50 dark:bg-violet-900/20 border border-violet-100 dark:border-violet-800 rounded-xl transition-all duration-300 min-w-0">
        <div class="flex items-center gap-1.5 shrink-0 cursor-pointer" @click="showInfoModal = true; if(!cthData) fetchStatus();" title="Ver información del fichaje">
            <div class="w-1 h-1 sm:w-1.5 sm:h-1.5 rounded-full bg-red-500 animate-pulse"></div>
            <span class="text-[10px] sm:text-xs font-mono font-black text-violet-700 dark:text-violet-300" x-text="formatTime(elapsed)"></span>
        </div>
        
        <template x-if="activeTaskTitle">
            <div class="flex items-center gap-1.5 pl-1.5 ml-1.5 border-l border-violet-200 dark:border-violet-700 min-w-0 overflow-hidden">
                <!-- Icono en lugar de texto largo en tablets -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-violet-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                <a :href="'{{ route('teams.activities.edit', ['team' => 'TEAM_ID', 'activity' => 'TASK_ID']) }}'.replace('TEAM_ID', activeTaskTeamId).replace('TASK_ID', activeTask)" 
                   class="text-[10px] font-bold text-violet-700 hover:text-violet-900 dark:text-violet-300 dark:hover:text-violet-100 hover:underline truncate max-w-[60px] sm:max-w-[100px] lg:max-w-[150px] cursor-pointer" 
                   :title="activeTaskTitle" 
                   x-text="activeTaskTitle"></a>
            </div>
        </template>
    </div>

    <!-- Modal Info CTH -->
    <template x-if="cthData && cthData.enabled">
        <div x-show="showInfoModal" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" @click.self="showInfoModal = false" @keydown.escape.window="showInfoModal = false">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-sm p-6 relative mx-4" 
                 x-show="showInfoModal" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <button @click="showInfoModal = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                
                <div class="mb-4">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Sincronización CTH
                    </h3>
                </div>

                <div class="space-y-3 text-sm text-gray-600 dark:text-gray-300">
                    <div class="flex justify-between border-b dark:border-gray-700 pb-2">
                        <span class="font-medium">Servidor:</span>
                        <span class="font-mono text-xs" x-text="cthData.server"></span>
                    </div>
                    <div class="flex justify-between border-b dark:border-gray-700 pb-2">
                        <span class="font-medium">Usuario:</span>
                        <span class="font-mono text-xs" :class="!cthData.user_code ? 'text-red-500 font-bold' : ''" x-text="cthData.user_code || 'No configurado'"></span>
                    </div>
                    <div class="flex justify-between border-b dark:border-gray-700 pb-2">
                        <span class="font-medium">Centro de Trabajo:</span>
                        <span class="font-mono text-xs" :class="!cthData.work_center_code ? 'text-red-500 font-bold' : ''" x-text="cthData.work_center_code || 'No configurado'"></span>
                    </div>
                    
                    <template x-if="cthData.status">
                        <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-3 mt-4">
                            <div class="flex justify-between items-center mb-2">
                                <span class="font-bold text-xs uppercase tracking-wider text-gray-500">Estado en CTH</span>
                                <span :class="cthData.status.is_working ? 'text-emerald-500' : 'text-red-500'" class="font-bold flex items-center gap-1">
                                    <span class="w-2 h-2 rounded-full" :class="cthData.status.is_working ? 'bg-emerald-500 animate-pulse' : 'bg-red-500'"></span>
                                    <span x-text="cthData.status.is_working ? 'TRABAJANDO' : 'DETENIDO'"></span>
                                </span>
                            </div>
                            
                            <template x-if="cthData.status.start_time">
                                <div class="flex justify-between text-xs mt-1">
                                    <span>Inicio del tramo:</span>
                                    <span class="font-mono font-bold" x-text="formatCthTime(cthData.status.start_time)"></span>
                                </div>
                            </template>
                            
                            <template x-if="cthData.status.end_time">
                                <div class="flex justify-between text-xs mt-1">
                                    <span>Fin del tramo:</span>
                                    <span class="font-mono font-bold" x-text="formatCthTime(cthData.status.end_time)"></span>
                                </div>
                            </template>

                            <template x-if="cthData.status.grace_closing_available">
                                <div class="mt-3 p-2 bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-200 text-xs rounded-lg border border-yellow-200 dark:border-yellow-800">
                                    <div class="font-bold flex items-center gap-1 mb-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                        Turno Anterior Abierto
                                    </div>
                                    Tienes un turno anterior sin cerrar. Debes cerrarlo para poder registrar nuevos fichajes.
                                </div>
                            </template>

                            <template x-if="!cthData.status.success && cthData.status.message">
                                <div class="mt-3 p-2 bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-200 text-xs rounded-lg border border-red-200 dark:border-red-800">
                                    <div class="font-bold flex items-center gap-1 mb-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        Aviso del Servidor
                                    </div>
                                    <span x-text="cthData.status.message"></span>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
                
                <div class="mt-5 flex justify-end">
                    <button @click="showInfoModal = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-white text-sm font-medium rounded-xl transition-colors">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- Mood Checkin Modal -->
    <template x-teleport="body">
        <div x-show="showMoodModal" x-cloak class="fixed inset-0 flex items-center justify-center bg-gray-900/60 backdrop-blur-md" style="z-index: 106000;" @click.self="showMoodModal = false" @keydown.escape.window="showMoodModal = false">
            <div class="bg-white dark:bg-gray-800 rounded-[2rem] shadow-2xl w-full max-w-sm p-8 mx-4 transform transition-all border border-gray-100 dark:border-gray-700"
                 x-show="showMoodModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-8 sm:translate-y-0 sm:scale-95">
                
                <h3 class="text-xl font-black text-gray-800 dark:text-white text-center mb-2" x-text="working ? '¡Que tengas un buen turno!' : '¡Buen trabajo hoy!'"></h3>
                <p class="text-sm text-gray-600 dark:text-gray-300 text-center mb-8">¿Cómo definirías tu energía en este momento?</p>
                
                <div class="flex justify-center gap-3 sm:gap-4 mb-8">
                    <template x-for="(emoji, idx) in ['😫', '🙁', '😐', '🙂', '🤩']">
                        <button type="button" 
                                class="text-[2.5rem] leading-none transition-all duration-300 rounded-full hover:scale-125 focus:outline-none focus:ring-0 p-1" 
                                :class="selectedMood === (idx + 1) ? 'ring-4 ring-violet-500/40 scale-125 bg-violet-50 dark:bg-violet-900/30' : 'grayscale hover:grayscale-0 opacity-70 hover:opacity-100'"
                                @click="selectedMood = idx + 1">
                            <span x-text="emoji"></span>
                        </button>
                    </template>
                </div>
                
                <div class="flex gap-3 justify-center">
                    <button @click="showMoodModal = false" class="rounded-xl px-6 py-3 text-[11px] font-black uppercase tracking-widest text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        Omitir
                    </button>
                    <button @click="submitMood()" :disabled="!selectedMood" class="rounded-xl px-6 py-3 text-[11px] font-black uppercase tracking-widest text-white bg-violet-500 hover:bg-violet-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg shadow-violet-500/30">
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
