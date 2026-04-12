<div class="relative" x-data="{ 
    open: false,
    zoomVal: 100,
    tempZoomVal: 100,
    isApplying: false,
    
    init() {
        // Carga inicial
        const saved = localStorage.getItem('global_zoom') || '1.0';
        const initialZoom = Math.round(parseFloat(saved) * 100);
        this.zoomVal = initialZoom;
        this.tempZoomVal = initialZoom;

        // Escuchar cambios externos y sincronizar
        window.addEventListener('global-zoom-changed', (e) => {
            if (this.isApplying) return;
            
            // Asegurar que el valor sea numérico y esté en escala 50-150
            let val = parseFloat(e.detail);
            if (isNaN(val)) return;
            
            const newZoom = Math.round(val * 100);
            this.zoomVal = newZoom;
            this.tempZoomVal = newZoom;
        });
    },

    apply() {
        this.isApplying = true;
        
        // El porcentaje real que queremos aplicar
        const targetZoom = parseInt(this.tempZoomVal);
        this.zoomVal = targetZoom;
        
        const floatZoom = (targetZoom / 100).toFixed(2);
        localStorage.setItem('global_zoom', floatZoom);
        
        if (typeof window.applyGlobalZoom === 'function') {
            window.applyGlobalZoom(parseFloat(floatZoom));
        }
        
        // Bloqueo de 200ms para que el navegador se asiente tras el escalado
        setTimeout(() => { this.isApplying = false; }, 200);
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
         @click.stop
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 translate-y-[-10px]"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-cloak style="display: none"
         class="absolute right-0 mt-3 w-80 bg-white/95 dark:bg-gray-900/95 backdrop-blur-xl border border-gray-200 dark:border-gray-800 rounded-3xl shadow-2xl z-[90] p-6 text-gray-900 dark:text-white ring-1 ring-black/5 dark:ring-white/5">
        
        <div class="flex flex-col gap-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex flex-col gap-0.5">
                   <span class="text-[11px] font-black uppercase tracking-widest text-gray-400/80 leading-none">{{ __('navigation.zoom') }}</span>
                   <span class="text-[10px] text-gray-500 font-medium leading-none">{{ __('navigation.adjust_view') ?? 'Visualización' }}</span>
                </div>
                <button @click="tempZoomVal = 100; apply()" 
                        class="text-[11px] font-black text-violet-600 dark:text-violet-400 bg-violet-100/50 dark:bg-violet-900/30 px-3 py-2 rounded-xl hover:scale-105 transition-all flex items-center gap-2 border border-violet-200/50 dark:border-violet-500/20 active:scale-95">
                    <span x-text="tempZoomVal + '%'"></span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
            </div>

            <!-- Area del Slider -->
            <div class="relative group">
                <div class="flex items-center gap-4 bg-gray-50 dark:bg-gray-950/30 p-3.5 rounded-2xl border border-gray-100/60 dark:border-gray-800/40 shadow-inner overflow-hidden">
                    <button @click="if(tempZoomVal > 50) { tempZoomVal -= 5; apply(); }" 
                            class="w-9 h-9 flex items-center justify-center rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-500 hover:text-violet-600 dark:hover:text-violet-400 border-violet-200/50 transition-all shadow-sm shrink-0 active:scale-90">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6" />
                        </svg>
                    </button>

                    <div class="flex-1 flex items-center px-1">
                        <input type="range" min="50" max="150" step="5" 
                            x-model.number="tempZoomVal" 
                            @change="apply()"
                            class="w-full h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full appearance-none cursor-pointer accent-violet-600 dark:accent-violet-500 transition-all hover:h-2"
                            style="min-width: 0;">
                    </div>

                    <button @click="if(tempZoomVal < 150) { tempZoomVal += 5; apply(); }" 
                            class="w-9 h-9 flex items-center justify-center rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-500 hover:text-violet-600 dark:hover:text-violet-400 border-violet-200/50 transition-all shadow-sm shrink-0 active:scale-90">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Labels -->
            <div class="flex justify-between items-center px-1">
                <span class="text-[10px] font-black text-gray-300 dark:text-gray-600 tracking-tighter">50%</span>
                <div class="flex items-center gap-1.5">
                    <span class="text-[10px] font-black uppercase tracking-widest transition-all"
                          :class="tempZoomVal == 100 ? 'text-violet-500/80' : 'text-gray-400/60'">
                        <span x-show="tempZoomVal == 100">{{ __('navigation.normal') }}</span>
                        <span x-show="tempZoomVal != 100">{{ __('navigation.custom') ?? 'Personalizado' }}</span>
                    </span>
                </div>
                <span class="text-[10px] font-black text-gray-300 dark:text-gray-600 tracking-tighter">150%</span>
            </div>
        </div>
    </div>
</div>
