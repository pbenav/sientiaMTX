@props(['initialLinks' => []])

<div x-data="linkCrud({{ json_encode($initialLinks) }})" class="space-y-4">
    <!-- Lista de Enlaces -->
    <div class="space-y-3" x-show="links.length > 0" x-cloak>
        <template x-for="(link, index) in links" :key="index">
            <div class="group flex items-center justify-between p-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:border-violet-300 dark:hover:border-violet-600 transition-all shadow-sm">
                
                <div class="flex items-center gap-4 flex-1 min-w-0">
                    <div class="w-10 h-10 rounded-lg bg-violet-50 dark:bg-violet-900/30 flex items-center justify-center text-violet-600 dark:text-violet-400 shrink-0 border border-violet-100 dark:border-violet-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-900 dark:text-white truncate" x-text="link.title"></p>
                        <a :href="link.url" target="_blank" class="text-xs text-blue-600 dark:text-blue-400 truncate hover:underline flex items-center gap-1" x-text="link.url"></a>
                    </div>
                </div>

                <button type="button" @click="removeLink(index)" class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors opacity-0 group-hover:opacity-100 focus:opacity-100 shrink-0" title="Eliminar Enlace">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>

                <!-- Hidden inputs for Laravel to capture the array -->
                <input type="hidden" :name="'metadata[links][' + index + '][title]'" :value="link.title">
                <input type="hidden" :name="'metadata[links][' + index + '][url]'" :value="link.url">
            </div>
        </template>
    </div>
    
    <div x-show="links.length === 0" class="text-center p-6 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-2xl bg-gray-50/50 dark:bg-gray-800/30" x-cloak>
        <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">No hay enlaces añadidos</p>
    </div>

    <!-- Formulario para añadir -->
    <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4 flex flex-col md:flex-row gap-3 items-start md:items-end">
        <div class="flex-1 w-full">
            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Título del Enlace</label>
            <input type="text" x-model="newTitle" @keydown.enter.prevent="addLink" placeholder="Ej. Documentación del Proyecto" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-3 py-2 text-sm outline-none transition-all dark:text-white">
        </div>
        <div class="flex-1 w-full">
            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">URL</label>
            <input type="url" x-model="newUrl" @keydown.enter.prevent="addLink" placeholder="https://..." class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-3 py-2 text-sm outline-none transition-all dark:text-white">
        </div>
        <button type="button" @click="addLink" :disabled="!isValid" class="w-full md:w-auto px-4 py-2 bg-violet-600 hover:bg-violet-700 disabled:bg-violet-300 disabled:cursor-not-allowed text-white text-xs font-bold uppercase tracking-widest rounded-xl transition-all shadow-md">
            Añadir
        </button>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('linkCrud', (initial) => ({
            links: initial || [],
            newTitle: '',
            newUrl: '',
            
            get isValid() {
                return this.newTitle.trim().length > 0 && this.isValidUrl(this.newUrl);
            },
            
            isValidUrl(string) {
                try {
                    new URL(string);
                    return true;
                } catch (_) {
                    return false;  
                }
            },
            
            addLink() {
                if (this.isValid) {
                    this.links.push({
                        title: this.newTitle.trim(),
                        url: this.newUrl.trim()
                    });
                    this.newTitle = '';
                    this.newUrl = '';
                }
            },
            
            removeLink(index) {
                this.links.splice(index, 1);
            }
        }));
    });
</script>
