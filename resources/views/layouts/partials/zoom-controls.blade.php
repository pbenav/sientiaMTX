<div class="flex items-center gap-3 bg-gray-100/50 dark:bg-gray-800/50 px-3 py-1.5 rounded-xl border border-gray-200/50 dark:border-gray-700/50 shadow-sm transition-all" 
     x-data="{ 
        zoom: parseFloat(localStorage.getItem('global_zoom') || '1.0'),
        updateZoom(val) {
            this.zoom = parseFloat(val);
            localStorage.setItem('global_zoom', this.zoom.toFixed(2));
            if (typeof applyGlobalZoom === 'function') {
                applyGlobalZoom(this.zoom);
            }
        }
     }">
    <!-- Icono Alejar -->
    <button @click="updateZoom(Math.max(0.5, zoom - 0.05))" class="text-gray-400 hover:text-violet-500 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10H7" />
        </svg>
    </button>

    <!-- Slider -->
    <div class="flex items-center group relative">
        <input type="range" min="0.5" max="1.5" step="0.05" x-model="zoom" @input="updateZoom($event.target.value)"
            class="w-20 md:w-28 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-violet-600 dark:accent-violet-500 transition-all hover:h-2"
            title="{{ __('Adjust Zoom') }}">
        
        <!-- Tooltip con el porcentaje -->
        <span class="absolute -top-6 left-1/2 -translate-x-1/2 px-1.5 py-0.5 rounded-md bg-gray-900 text-white text-[9px] font-bold opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
            <span x-text="Math.round(zoom * 100) + '%'"></span>
        </span>
    </div>

    <!-- Icono Acercar -->
    <button @click="updateZoom(Math.min(1.5, zoom + 0.05))" class="text-gray-400 hover:text-violet-500 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 7v3m0 0v3m0-3h3m-3 0H7" />
        </svg>
    </button>

    <!-- Label / Reset -->
    <button @click="updateZoom(1.0)" 
            class="text-[9px] font-black text-violet-600 dark:text-violet-400 bg-white dark:bg-gray-700 px-1.5 py-0.5 rounded-md shadow-sm border border-gray-200/50 dark:border-gray-600/50 hover:scale-110 transition-transform active:scale-95"
            title="{{ __('Reset Zoom') }}">
        <span x-text="Math.round(zoom * 100) + '%'"></span>
    </button>
</div>
