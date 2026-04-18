<div x-data="{ 
    working: false,
    elapsed: 0,
    timer: null,
    loading: false,
    activeTask: null,
    activeTaskTitle: null,
    compact: {{ isset($compact) && $compact ? 'true' : 'false' }},
    
    init() {
        this.fetchStatus();
        setInterval(() => {
            if (this.working) this.elapsed++;
        }, 1000);

        // SYNC: If a task is started, ensure workday reflects it
        window.addEventListener('task-started', () => {
             this.working = true;
             this.fetchStatus(); 
        });
    },
    
    fetchStatus() {
        fetch('{{ route('time-logs.status') }}')
            .then(res => res.json())
            .then(data => {
                this.working = data.is_working;
                this.elapsed = Math.floor(data.workday_elapsed);
                this.activeTask = data.active_task_id;
                this.activeTaskTitle = data.active_task_title;
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
        });
    },
    
    formatTime(seconds) {
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;
        return [h, m, s].map(v => v < 10 ? '0' + v : v).join(':');
    }
}" class="flex items-center gap-3" :class="compact ? 'w-full justify-start' : ''">
    
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
        <div class="flex items-center gap-1.5 shrink-0">
            <div class="w-1 h-1 sm:w-1.5 sm:h-1.5 rounded-full bg-red-500 animate-pulse"></div>
            <span class="text-[10px] sm:text-xs font-mono font-black text-violet-700 dark:text-violet-300" x-text="formatTime(elapsed)"></span>
        </div>
        
        <template x-if="activeTaskTitle">
            <div class="flex items-center gap-1.5 pl-1.5 ml-1.5 border-l border-violet-200 dark:border-violet-700 min-w-0 overflow-hidden">
                <!-- Icono en lugar de texto largo en tablets -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-violet-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                <span class="text-[10px] font-bold text-violet-700 dark:text-violet-300 truncate max-w-[60px] sm:max-w-[100px] lg:max-w-[150px]" 
                      :title="activeTaskTitle" 
                      x-text="activeTaskTitle"></span>
            </div>
        </template>
    </div>
</div>
