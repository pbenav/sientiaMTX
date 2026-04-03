<div class="relative" x-data="{ 
    open: false,
    zoomVal: 100,
    tempZoomVal: 100,
    
    init() {
        const saved = localStorage.getItem('global_zoom') || '1.0';
        this.zoomVal = Math.round(parseFloat(saved) * 100);
        this.tempZoomVal = this.zoomVal;
    },

    apply() {
        this.zoomVal = parseInt(this.tempZoomVal);
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

    <!-- Desplegable -->
    <div x-show="open" 
         @click.outside="open = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 translate-y-[-10px]"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-cloak style="display: none"
         class="absolute right-0 sm:right-0 mt-3 w-72 bg-white/95 dark:bg-gray-900/95 backdrop-blur-xl border border-gray-200 dark:border-gray-800 rounded-2xl shadow-2xl z-[70] p-5 text-gray-900 dark:text-white ring-1 ring-black/5 dark:ring-white/5">
        
        <div class="flex flex-col gap-4">
            <!-- Header con Porcentaje ACTUAL -->
            <div class="flex items-center justify-between">
                <div class="flex flex-col">
                   <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">{{ __('navigation.zoom') }}</span>
                   <span class="text-[9px] text-gray-500 font-medium">{{ __('navigation.adjust_view') ?? 'Ajustar visualización' }}</span>
                </div>
                <button @click="tempZoomVal = 100; apply()" 
                        class="text-[10px] font-black text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-900/30 px-2.5 py-1.5 rounded-lg hover:scale-105 transition-transform flex items-center gap-1.5 border border-violet-100 dark:border-violet-500/20"
                        title="{{ __('navigation.reset_zoom') }}">
                    <span id="global-zoom-label" x-text="tempZoomVal + '%'"></span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
            </div>

            <!-- Area del Slider Mejorada -->
            <div class="flex items-center gap-2 bg-gray-50 dark:bg-gray-950/50 p-2.5 rounded-xl border border-gray-100 dark:border-gray-800 shadow-inner">
                <!-- Botón Menos (-) -->
                <button @click="if(tempZoomVal > 50) { tempZoomVal -= 5; apply(); }" 
                        class="w-8 h-8 flex items-center justify-center rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-500 hover:text-violet-600 dark:hover:text-violet-400 hover:border-violet-200 dark:hover:border-violet-700 transition-all shadow-sm shrink-0 active:scale-95">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6" />
                    </svg>
                </button>

                <!-- Input Range Absoluto con Live Feedback -->
                <input type="range" min="50" max="150" step="5" 
                    x-model.number="tempZoomVal" 
                    @input="apply()"
                    class="flex-1 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-violet-600 dark:accent-violet-500">

                <!-- Botón Más (+) -->
                <button @click="if(tempZoomVal < 150) { tempZoomVal += 5; apply(); }" 
                        class="w-8 h-8 flex items-center justify-center rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-500 hover:text-violet-600 dark:hover:text-violet-400 hover:border-violet-200 dark:hover:border-violet-700 transition-all shadow-sm shrink-0 active:scale-95">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                    </svg>
                </button>
            </div>

            <!-- Ayuda Visual -->
            <div class="flex justify-between px-1 text-[9px] font-bold text-gray-400 uppercase tracking-tighter">
                <span>50%</span>
                <span class="text-violet-500/80 font-black" x-show="tempZoomVal == 100">{{ __('navigation.normal') }}</span>
                <span class="text-gray-400/80" x-show="tempZoomVal != 100">{{ __('navigation.custom') ?? 'Personalizado' }}</span>
                <span>150%</span>
            </div>
        </div>
    </div>
</div>
