<div class="relative" x-data="{ 
    open: false,
    zoom: parseFloat(localStorage.getItem('global_zoom') || '1.0'),
    updateZoom(val) {
        this.zoom = parseFloat(val);
        localStorage.setItem('global_zoom', this.zoom.toFixed(2));
        if (typeof applyGlobalZoom === 'function') {
            applyGlobalZoom(this.zoom);
        }
    }
}">
    <!-- Botón Lupa (Compacto) -->
    <button @click="open = !open" 
            class="flex items-center justify-center text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 w-9 h-9 rounded-lg transition-all shadow-sm bg-white dark:bg-gray-800"
            title="{{ __('Zoom Controls') }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" :class="zoom != 1.0 ? 'text-violet-500' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
        </svg>
    </button>

    <!-- Desplegable con Slider -->
    <div x-show="open" 
         @click.outside="open = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 translate-y-[-10px]"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         class="absolute right-0 mt-2 w-64 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-2xl z-[60] p-4">
        
        <div class="flex flex-col gap-4">
            <!-- Header del Desplegable -->
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Zoom Global</span>
                <button @click="updateZoom(1.0)" 
                        class="text-[10px] font-black text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-900/30 px-2 py-0.5 rounded-lg hover:scale-105 transition-transform"
                        title="{{ __('Reset al 100%') }}">
                    <span x-text="Math.round(zoom * 100) + '%'"></span>
                </button>
            </div>

            <!-- Area del Slider -->
            <div class="flex items-center gap-3 bg-gray-50 dark:bg-gray-900/50 p-3 rounded-xl border border-gray-100 dark:border-gray-800">
                <!-- Alejar -->
                <button @click="updateZoom(Math.max(0.5, zoom - 0.05))" class="text-gray-400 hover:text-violet-500 transition-colors shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10H7" />
                    </svg>
                </button>

                <!-- Input Range -->
                <input type="range" min="0.5" max="1.5" step="0.05" x-model="zoom" @input="updateZoom($event.target.value)"
                    class="flex-1 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-violet-600 dark:accent-violet-500">

                <!-- Acercar -->
                <button @click="updateZoom(Math.min(1.5, zoom + 0.05))" class="text-gray-400 hover:text-violet-500 transition-colors shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 7v3m0 0v3m0-3h3m-3 0H7" />
                    </svg>
                </button>
            </div>

            <!-- Info Adicional -->
            <div class="flex justify-between px-1">
                <span class="text-[9px] font-bold text-gray-500">50%</span>
                <span class="text-[9px] font-bold text-gray-500">100%</span>
                <span class="text-[9px] font-bold text-gray-500">150%</span>
            </div>
        </div>
    </div>
</div>
