@if($isDemoMode ?? false)
<div class="mt-3 mb-2 px-3 py-2.5 rounded-xl bg-violet-50/50 dark:bg-violet-900/10 border border-violet-100 dark:border-violet-800/30 flex items-start gap-3" x-data="{ showHint: true }" x-show="showHint" x-transition>
    <div class="shrink-0 w-6 h-6 rounded-lg bg-violet-100 dark:bg-violet-800/50 text-violet-500 dark:text-violet-400 flex items-center justify-center mt-0.5">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </div>
    <div class="flex-1 min-w-0 pt-0.5">
        <div class="text-[11px] text-violet-800 dark:text-violet-300 leading-relaxed font-medium">
            <strong class="font-black uppercase tracking-widest text-[9px] text-violet-600 dark:text-violet-400 mr-1 border-b border-violet-200 dark:border-violet-700/50">Guía de Demo:</strong> 
            {{ $slot }}
        </div>
    </div>
    <button type="button" @click="showHint = false" class="shrink-0 p-1 text-violet-300 hover:text-violet-600 dark:hover:text-violet-300 transition-colors rounded-md hover:bg-violet-100 dark:hover:bg-violet-800/50">
        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
</div>
@endif
