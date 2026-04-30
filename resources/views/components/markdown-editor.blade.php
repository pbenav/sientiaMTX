@props(['name', 'value' => '', 'id' => null, 'label' => null, 'rows' => 6, 'placeholder' => '', 'uploadUrl' => null])

<div x-data="{ 
    content: @js($value), 
    tab: 'write',
    uploading: false,
    uploadUrl: @js($uploadUrl),
    get preview() {
        return marked.parse(this.content || '');
    },
    handlePaste(e) {
        if (!this.uploadUrl) return;
        
        const items = (e.clipboardData || e.originalEvent.clipboardData).items;
        for (let i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image') !== -1) {
                const blob = items[i].getAsFile();
                this.uploadFile(blob);
            }
        }
    },
    uploadFile(file) {
        const formData = new FormData();
        formData.append('image', file);
        
        this.uploading = true;
        
        fetch(this.uploadUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            this.uploading = false;
            if (data.url) {
                const text = `\n![Image](${data.url})\n`;
                this.insertAtCursor(text);
            }
        })
        .catch(err => {
            this.uploading = false;
            console.error('Upload error:', err);
        });
    },
    insertAtCursor(text) {
        const textarea = this.$refs.textarea;
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const currentContent = this.content || '';
        
        this.content = currentContent.substring(0, start) + text + currentContent.substring(end);
        
        this.$nextTick(() => {
            const newPos = start + text.length;
            textarea.setSelectionRange(newPos, newPos);
            textarea.focus();
        });
    }
}" class="w-full space-y-2">
    @if($label)
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $label }}</label>
    @endif

    <div class="relative flex flex-col w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm transition-all focus-within:ring-2 focus-within:ring-violet-500/20 focus-within:border-violet-500/50"
         :class="uploading ? 'opacity-70 pointer-events-none' : ''">
        
        <!-- Uploading overlay (Highest level z-index) -->
        <template x-if="uploading">
            <div class="absolute inset-0 z-[1000] bg-white/50 dark:bg-gray-900/50 flex items-center justify-center backdrop-blur-[1px]">
                <div class="flex items-center gap-2 bg-white dark:bg-gray-800 px-4 py-2 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700">
                    <svg class="animate-spin h-4 w-4 text-violet-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ __('Subiendo imagen...') }}</span>
                </div>
            </div>
        </template>
        
        <!-- Header (High level z-index to allow picker to float over content) -->
        <div class="relative z-[50] flex items-center justify-between px-3 py-2 bg-gray-50 dark:bg-gray-800 border-b border-gray-100 dark:border-gray-800 rounded-t-2xl shadow-sm">
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

                <!-- Emoji Picker Trigger -->
                <div class="relative" x-data="{ open: false }">
                    <button type="button" @click="open = !open" 
                        class="flex items-center justify-center w-8 h-8 rounded-xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-700 text-gray-500 hover:text-violet-600 hover:border-violet-300 transition-all shadow-sm"
                        title="{{ __('Insertar icono') }}">
                        😊
                    </button>
                    <!-- Emoji Panel (Opaque and high z-index) -->
                    <div x-show="open" @click.away="open = false" x-transition x-cloak
                        class="absolute right-0 mt-2 z-[999] w-72 sm:w-80 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-3xl shadow-2xl p-3 overflow-hidden">
                        <div class="grid grid-cols-10 gap-0 max-h-64 overflow-y-auto custom-scrollbar pr-1">
                            @foreach([
                                '😀','😃','😄','😁','😆','😅','😂','🤣','😊','😇','🙂','🙃','😉','😌','😍','🥰','😘','😗','😙','😚','😋','😛','😝','😜','🤪','🤨','🧐','🤓','😎','🤩','🥳','😏','😒','😞','😔','😟','😕','🙁','☹️','😣','😖','😫','😩','🥺','😢','😭','😤','😠','😡','🤬','🤯','😳','🥵','🥶','😱','😨','😰','😥','😓','🤗','🤔','🤭','🤫','🤥','😶','😐','😑','😬','🙄','😯','😦','😧','😮','😲','🥱','😴','🤤','😪','😵','🤐','🥴','🤢','🤮','🤧','😇','🤠','🤡','🥳','🥸','🤓','🧐','👋','👌','👍','👎','👏','🙌','🙏','💪','🤝','🤞','✌️','🤘','🤙','👈','👉','👆','👇','☝️','✋','🤚','🖐️','🖖','✍️','💅','🤳','❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❤️‍🔥','❤️‍🩹','❣️','💕','💞','💓','💗','💖','💘','💝','💟','💌','💍','💎','🎯','📈','📉','📊','📋','📁','📂','📑','📓','📔','📕','📖','🔖','🔗','📎','📏','📐','✂️','📌','📍','🔨','🛠️','🔧','🔩','⚙️','🧱','⚖️','🧰','🧲','💻','🖥️','⌨️','🖱️','🖨️','📱','☎️','📞','🔋','🔌','💡','🔦','🕯️','💰','💵','💸','💳','💹','🏧','🏦','🏢','✅','✔️','☑️','❌','✖️','❎','➕','➖','➗','♾️','❓','❔','❕','❗️','⚠️','🔔','🔕','📣','📢','💬','💭','🗯️','🔥','✨','⭐','🌟','⚡','🌈','☀️','🌤️','☁️','🌧️','❄️','⛄','🌀','🌊','💧','💨','🔴','🔵','🟢','🟡','🟠','🟣','🟤','⚪','⚫','🟥','🟦','🟩','🟨','🟧','🟪','🟫','⬜','⬛','🔶','🔷','🔸','🔹','🔺','🔻','💠','🔘','🔳','🔲','⏰','🕰️','⏱️','⏲️','⏳','⌛','📅','📆','🗓️','⌚','🌍','🌎','🌏','🗺️','🚀','🛸','🚁','✈️','🚂','🚲','🚗','🏠','🏘️','🏨','🏰','🗼','🗽','⛱️','🏙️','🌇','🌃','🌉','🛣️','🏁','🚩','🥇','🥈','🥉','🏆','🏅','🎖️','🎨','🎬','🎧','🎮','🎹','🎻','🎺','🎸','🥁'
                            ] as $icon)
                                <button type="button" @click="insertAtCursor('{{ $icon }}')" 
                                        class="text-xl hover:bg-gray-100 dark:hover:bg-gray-800 p-1 rounded-lg transition-all hover:scale-125 active:scale-90 flex items-center justify-center">
                                    {{ $icon }}
                                </button>
                            @endforeach
                        </div>
                        <div class="mt-3 pt-2 border-t border-gray-100 dark:border-gray-800 text-center bg-white dark:bg-gray-900">
                            <a href="https://emojicopy.com/" target="_blank" class="text-[10px] font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors flex items-center justify-center gap-1.5 group/link">
                                <span>Buscar más emojis</span>
                                <svg class="w-2.5 h-2.5 group-hover/link:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panels Container (Lower z-index) -->
        <div class="relative z-0">
            <!-- Write Panel -->
            <div x-show="tab === 'write'" class="relative">
                <textarea 
                    x-ref="textarea"
                    name="{{ $name }}" 
                    id="{{ $id ?? $name }}"
                    x-model="content"
                    rows="{{ $rows }}"
                    placeholder="{{ $placeholder }}"
                    class="w-full bg-transparent border-0 focus:ring-0 text-sm py-4 px-5 text-gray-700 dark:text-gray-300 placeholder-gray-400 dark:placeholder-gray-600 resize-y min-h-[120px] font-mono leading-relaxed"
                    @input="$el.dispatchEvent(new CustomEvent('change', { bubbles: true }))"
                    @paste="handlePaste($event)"
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
                class="min-h-[120px] max-h-[600px] overflow-y-auto custom-scrollbar bg-white dark:bg-gray-950/20 py-4 px-5 rounded-b-2xl"
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
</div>
