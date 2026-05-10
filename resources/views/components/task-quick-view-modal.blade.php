<div x-data="{
    isOpen: false,
    taskId: null,
    teamId: null,
    embedUrl: '',
    loading: true,
    
    open(data) {
        this.taskId = data.taskId;
        this.teamId = data.teamId;
        this.embedUrl = `/teams/${this.teamId}/tasks/${this.taskId}?embed=1`;
        this.isOpen = true;
        this.loading = true;
        document.body.classList.add('overflow-hidden');
    },
    
    close() {
        this.isOpen = false;
        setTimeout(() => {
            this.embedUrl = '';
            this.taskId = null;
        }, 300);
        document.body.classList.remove('overflow-hidden');
    }
}"
x-on:open-task-modal.window="open($event.detail)"
x-on:keydown.escape.window="close()"
class="fixed inset-0 z-[9999] overflow-y-auto"
x-show="isOpen"
x-cloak>
    
    <!-- Overlay -->
    <div x-show="isOpen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="close()"
         class="fixed inset-0 bg-gray-950/80 backdrop-blur-md transition-opacity"></div>

    <!-- Modal Content Wrapper -->
    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div x-show="isOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative transform overflow-hidden rounded-[2.5rem] bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 text-left shadow-2xl transition-all w-full max-w-5xl h-[90vh] flex flex-col">
            
            <!-- Sticky Close Bar -->
            <div class="absolute top-4 right-4 z-[100]">
                <button @click="close()" class="p-2 bg-gray-100/80 dark:bg-gray-800/80 hover:bg-red-50 dark:hover:bg-red-900/50 text-gray-500 hover:text-red-600 rounded-xl backdrop-blur-md transition-all shadow-sm border border-gray-200/50 dark:border-gray-700/50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Loading Spinner -->
            <div x-show="loading" class="absolute inset-0 flex items-center justify-center bg-white dark:bg-gray-950 z-10">
                <div class="flex flex-col items-center gap-3">
                    <svg class="animate-spin h-10 w-10 text-violet-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-xs font-bold text-gray-400 tracking-widest uppercase">Cargando detalles...</span>
                </div>
            </div>

            <!-- The Iframe container -->
            <template x-if="embedUrl">
                <iframe :src="embedUrl" 
                        @load="loading = false"
                        class="w-full h-full border-none flex-1"
                        frameborder="0"></iframe>
            </template>
        </div>
    </div>
</div>
