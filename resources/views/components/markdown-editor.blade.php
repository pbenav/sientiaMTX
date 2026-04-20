@props(['name', 'value' => '', 'id' => null, 'label' => null, 'rows' => 6, 'placeholder' => ''])

<div x-data="{ 
    content: @js($value), 
    tab: 'write',
    get preview() {
        return marked.parse(this.content || '');
    }
}" class="w-full space-y-2">
    @if($label)
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $label }}</label>
    @endif

    <div class="relative flex flex-col w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm transition-all focus-within:ring-2 focus-within:ring-violet-500/20 focus-within:border-violet-500/50">
        
        <!-- Editor Tabs -->
        <div class="flex items-center justify-between px-3 py-2 bg-gray-50/80 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-800 backdrop-blur-md">
            <div class="flex p-0.5 bg-gray-200/50 dark:bg-gray-950/50 rounded-xl">
                <button type="button" 
                    @click="tab = 'write'"
                    :class="tab === 'write' ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="px-3 py-1.5 text-[10px] font-black uppercase tracking-widest rounded-lg transition-all">
                    {{ __('Editar') }}
                </button>
                <button type="button" 
                    @click="tab = 'preview'"
                    :class="tab === 'preview' ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="px-3 py-1.5 text-[10px] font-black uppercase tracking-widest rounded-lg transition-all flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    {{ __('Vista Previa') }}
                </button>
            </div>

            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-[9px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-tighter">{{ __('Markdown habilitado') }}</span>
                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-500/50 animate-pulse"></div>
                </div>
                
                <a href="{{ app()->getLocale() === 'es' ? 'https://markdown.es/sintaxis-markdown/' : 'https://www.markdownguide.org/cheat-sheet/' }}" 
                   target="_blank" 
                   class="flex items-center justify-center w-5 h-5 rounded-full text-gray-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/30 transition-all" 
                   title="{{ __('Ayuda de Markdown') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </a>
            </div>
        </div>

        <!-- Write Panel -->
        <div x-show="tab === 'write'" class="relative">
            <textarea 
                name="{{ $name }}" 
                id="{{ $id ?? $name }}"
                x-model="content"
                rows="{{ $rows }}"
                placeholder="{{ $placeholder }}"
                class="w-full bg-transparent border-0 focus:ring-0 text-sm py-4 px-5 text-gray-700 dark:text-gray-300 placeholder-gray-400 dark:placeholder-gray-600 resize-y min-h-[120px] font-mono leading-relaxed"
                @input="$el.dispatchEvent(new CustomEvent('change', { bubbles: true }))"
            ></textarea>

            <!-- Markdown Hints Tooltip (Floating) -->
            <div class="absolute bottom-2 right-4 flex gap-3 text-[10px] text-gray-400 pointer-events-none opacity-50">
                <span>**bold**</span>
                <span>*italic*</span>
                <span># heading</span>
                <span>- list</span>
            </div>
        </div>

        <!-- Preview Panel -->
        <div x-show="tab === 'preview'" 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            class="min-h-[120px] bg-white dark:bg-gray-950/20 py-4 px-5"
            x-cloak>
            <div class="prose prose-sm dark:prose-invert max-w-none break-words leading-relaxed" x-html="preview"></div>
            <template x-if="!content">
                <div class="flex flex-col items-center justify-center h-full min-h-[100px] text-gray-400 italic text-xs">
                    <p>{{ __('Nada que previsualizar...') }}</p>
                </div>
            </template>
        </div>
    </div>
</div>
