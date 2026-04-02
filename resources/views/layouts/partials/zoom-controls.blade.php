<div class="relative" x-data="{ 
    open: false,
    zoomVal: 100,
    dragging: false,
    
    init() {
        const saved = localStorage.getItem('global_zoom') || '1.0';
        this.zoomVal = Math.round(parseFloat(saved) * 100);
    },

    saveAndApply() {
        const floatZoom = (this.zoomVal / 100).toFixed(2);
        localStorage.setItem('global_zoom', floatZoom);
        if (typeof applyGlobalZoom === 'function') {
            applyGlobalZoom(parseFloat(floatZoom));
        }
    }
}">
    <!-- Botón Lupa -->
    <button @click="open = !open" 
            class="flex items-center justify-center text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white border border-gray-200 dark:border-gray-700 hover:border-violet-300 dark:hover:border-violet-700 w-9 h-9 rounded-lg transition-all shadow-sm bg-white dark:bg-gray-800"
            title="{{ __('navigation.zoom_controls') }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" :class="zoomVal != 100 ? 'text-violet-500' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
        </svg>
    </button>

    <!-- Desplegable con Slider (w-72 para evitar desbordamientos) -->
    <div x-show="open" 
         @click.outside="open = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 translate-y-[-10px]"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         class="absolute right-0 mt-2 w-72 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-2xl z-[60] p-4 text-gray-900 dark:text-white">
        
        <div class="flex flex-col gap-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                   <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">{{ __('navigation.zoom_controls') }}</span>
                </div>
                <button @click="zoomVal = 100; saveAndApply()" 
                        class="text-[10px] font-black text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-900/30 px-2.5 py-1 rounded-lg hover:scale-105 transition-transform"
                        title="{{ __('navigation.reset_zoom') }}">
                    <span x-text="zoomVal + '%'"></span>
                </button>
            </div>

            <!-- Area del Slider -->
            <div class="flex items-center gap-3 bg-gray-50 dark:bg-gray-900/50 p-3 rounded-xl border border-gray-100 dark:border-gray-800">
                <!-- Alejar (-) -->
                <button @click="zoomVal = Math.max(50, zoomVal - 5); saveAndApply()" 
                        class="text-gray-400 hover:text-violet-500 transition-colors shrink-0 p-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6" />
                    </svg>
                </button>

                <!-- Input Range (Vinculación pura .number) -->
                <input type="range" min="50" max="150" step="5" 
                    x-model.number="zoomVal" 
                    @input="saveAndApply()"
                    class="flex-1 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-violet-600 dark:accent-violet-500">

                <!-- Acercar (+) -->
                <button @click="zoomVal = Math.min(150, zoomVal + 5); saveAndApply()" 
                        class="text-gray-400 hover:text-violet-500 transition-colors shrink-0 p-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                    </svg>
                </button>
            </div>

            <!-- Etiquetas de apoyo -->
            <div class="flex justify-between px-1">
                <span class="text-[9px] font-bold text-gray-500">50%</span>
                <div class="flex gap-1 items-center">
                    <div class="w-1.5 h-1.5 rounded-full" :class="zoomVal == 100 ? 'bg-violet-500' : 'bg-gray-300'"></div>
                    <span class="text-[9px] font-bold" :class="zoomVal == 100 ? 'text-violet-500' : 'text-gray-500'">100% (Normal)</span>
                </div>
                <span class="text-[9px] font-bold text-gray-500">150%</span>
            </div>
        </div>
    </div>
</div>
