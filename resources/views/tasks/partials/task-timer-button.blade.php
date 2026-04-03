<div x-data="{ 
    taskId: {{ $task->id }},
    loading: false,
    
    get timer() { return Alpine.store('timer').activeTaskId == this.taskId ? Alpine.store('timer').timer : null },
    get elapsed() { return Alpine.store('timer').activeTaskId == this.taskId ? Alpine.store('timer').elapsed : 0 },
    humanTime: '{{ $task->totalTrackedTimeHuman() }}',

    toggle() {
        this.loading = true;
        fetch('{{ route('time-logs.toggle-task', $task) }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'started') {
                window.dispatchEvent(new CustomEvent('task-started', { detail: { taskId: this.taskId } }));
            } else {
                if (Alpine.store('timer')) Alpine.store('timer').stop();
                window.location.reload();
            }
            this.loading = false;
        });
    },

    formatTime(seconds) {
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;
        return (h > 0 ? h + 'h ' : '') + m + 'm ' + s + 's';
    }
}" 
@task-started.window="if($event.detail.taskId !== taskId) stopTimer()"
class="flex flex-col items-end gap-1">
    
    <div class="flex items-center gap-1.5">
        <!-- Human Time Accumulator (Static or Dynamic) -->
        <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500" x-show="!timer" x-text="humanTime"></span>
        <span class="text-[10px] font-bold text-violet-500 animate-pulse" x-show="timer" x-cloak x-text="formatTime(elapsed)"></span>

        <!-- Toggle Button -->
        <button @click.stop="toggle()" :disabled="loading"
                class="p-1.5 rounded-lg transition-all duration-300 shadow-sm border group relative overflow-hidden"
                :title="timer ? '{{ __('tasks.stop_task_tracking') }}' : '{{ __('tasks.start_task_tracking') }}'"
                :class="timer 
                    ? 'bg-red-600 border-red-500 text-white hover:bg-red-700 animate-pulse-subtle' 
                    : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-400 hover:border-violet-500 hover:text-violet-500'">
            
            <template x-if="!loading">
                <div class="flex items-center justify-center gap-1.5 relative z-10">
                    <svg x-show="!timer" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    
                    <svg x-show="timer" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        <rect x="9" y="9" width="6" height="6" rx="1" stroke-width="2.5" />
                    </svg>

                    <!-- Recording Dot inside button when running -->
                    <div x-show="timer" class="w-1 h-1 rounded-full bg-white animate-ping ml-0.5" x-cloak></div>
                </div>
            </template>

            <template x-if="loading">
                <svg class="animate-spin h-3.5 w-3.5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </template>
        </button>
    </div>
</div>
