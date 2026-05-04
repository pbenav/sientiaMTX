@props(['name', 'value' => '', 'id' => null, 'label' => null, 'rows' => 6, 'placeholder' => '', 'uploadUrl' => null, 'mentionsUrl' => null, 'required' => false])

<div x-data="{ 
    content: @js($value), 
    tab: 'write',
    uploading: false,
    uploadUrl: @js($uploadUrl),
    mentionsUrl: @js($mentionsUrl),
    mentions: [],
    mentioning: false,
    mentionQuery: '',
    mentionIndex: 0,
    mentionPos: { top: 0, left: 0 },
    
    init() {
        this.$watch('mentionQuery', value => {
            this.mentionIndex = 0;
        });
    },

    get preview() {
        return typeof marked !== 'undefined' ? marked.parse(this.content || '') : '{{ __("Cargando...") }}';
    },

    async fetchMentions() {
        if (!this.mentionsUrl || this.mentions.length > 0) return;
        try {
            const res = await fetch(this.mentionsUrl);
            this.mentions = await res.json();
        } catch (e) {
            console.error('Error fetching mentions:', e);
        }
    },

    get filteredMentions() {
        if (!this.mentionQuery) return this.mentions.slice(0, 10);
        return this.mentions
            .filter(m => m.name.toLowerCase().includes(this.mentionQuery.toLowerCase()))
            .slice(0, 10);
    },

    updateMentionPosition() {
        const textarea = this.$refs.textarea;
        const cursor = textarea.selectionStart;
        const textBefore = this.content.substring(0, cursor);
        const lastAt = textBefore.lastIndexOf('@');
        
        if (lastAt === -1) return;

        // Create a mirror div to calculate position
        const div = document.createElement('div');
        const style = window.getComputedStyle(textarea);
        
        // Copy relevant styles
        const properties = [
            'direction', 'boxSizing', 'width', 'height', 'overflowX', 'overflowY',
            'borderTopWidth', 'borderRightWidth', 'borderBottomWidth', 'borderLeftWidth', 'borderStyle',
            'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft',
            'fontStyle', 'fontVariant', 'fontWeight', 'fontStretch', 'fontSize', 'fontSizeAdjust', 'lineHeight', 'fontFamily',
            'textAlign', 'textTransform', 'textIndent', 'textDecoration', 'letterSpacing', 'wordSpacing', 'tabSize', 'MozTabSize'
        ];
        
        properties.forEach(prop => div.style[prop] = style[prop]);
        
        div.style.position = 'absolute';
        div.style.visibility = 'hidden';
        div.style.whiteSpace = 'pre-wrap';
        div.style.wordBreak = 'break-word';
        div.style.top = '0';
        div.style.left = '-9999px';
        
        // Content up to the @
        div.textContent = textBefore.substring(0, lastAt);
        const span = document.createElement('span');
        span.textContent = '@';
        div.appendChild(span);
        
        document.body.appendChild(div);
        
        const { offsetTop, offsetLeft } = span;
        const { scrollTop, scrollLeft } = textarea;
        
        // Adjust for scroll and add small offset
        this.mentionPos = {
            top: offsetTop - scrollTop + 25,
            left: offsetLeft - scrollLeft
        };
        
        document.body.removeChild(div);
    },

    handleInput(e) {
        const textarea = this.$refs.textarea;
        const cursor = textarea.selectionStart;
        const textBefore = this.content.substring(0, cursor);
        
        const lastAt = textBefore.lastIndexOf('@');
        if (lastAt !== -1 && (lastAt === 0 || /\s/.test(textBefore[lastAt - 1]))) {
            const query = textBefore.substring(lastAt + 1);
            if (!/\s/.test(query)) {
                this.mentioning = true;
                this.mentionQuery = query;
                this.mentionIndex = 0;
                this.updateMentionPosition();
                if (this.mentions.length === 0) this.fetchMentions();
                return;
            }
        }
        this.mentioning = false;
    },

    handleKeyDown(e) {
        if (this.mentioning && this.filteredMentions.length > 0) {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.mentionIndex = (this.mentionIndex + 1) % this.filteredMentions.length;
                this.$nextTick(() => this.scrollToSelectedMention());
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.mentionIndex = (this.mentionIndex - 1 + this.filteredMentions.length) % this.filteredMentions.length;
                this.$nextTick(() => this.scrollToSelectedMention());
            } else if (e.key === 'Enter' || e.key === 'Tab') {
                e.preventDefault();
                this.selectMention(this.filteredMentions[this.mentionIndex]);
            } else if (e.key === 'Escape') {
                this.mentioning = false;
            }
        }
    },

    scrollToSelectedMention() {
        const container = this.$refs.mentionsList;
        const selected = container.children[this.mentionIndex];
        if (selected) {
            selected.scrollIntoView({ block: 'nearest', behavior: 'auto' });
        }
    },

    selectMention(user) {
        const textarea = this.$refs.textarea;
        const cursor = textarea.selectionStart;
        const textBeforeAt = this.content.substring(0, cursor).lastIndexOf('@');
        
        const before = this.content.substring(0, textBeforeAt);
        const after = this.content.substring(cursor);
        const mentionText = `**@${user.name}**<!--mention:${user.id}--> `;
        
        this.content = before + mentionText + after;
        this.mentioning = false;
        
        this.$nextTick(() => {
            const newPos = before.length + mentionText.length;
            textarea.setSelectionRange(newPos, newPos);
            textarea.focus();
        });
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
        <label class="block text-sm font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ $label }}</label>
    @endif

    <div class="relative flex flex-col w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-[2rem] shadow-sm transition-all focus-within:ring-2 focus-within:ring-violet-500/20 focus-within:border-violet-500/50"
         :class="uploading ? 'opacity-70 pointer-events-none' : ''">
        
        <!-- Mention Dropdown (Now placed here to be relative to the entire component but absolute positioned) -->
        <div x-show="mentioning && filteredMentions.length > 0" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             :style="`top: ${mentionPos.top}px; left: ${mentionPos.left}px;`"
             @wheel.stop=""
             class="absolute z-[1000] w-72 bg-white dark:bg-gray-800 border border-violet-100 dark:border-violet-900/50 rounded-2xl shadow-2xl overflow-hidden"
             x-cloak>
            <div class="px-4 py-2 bg-violet-50 dark:bg-violet-900/20 border-b border-violet-100 dark:border-violet-900/30 flex items-center justify-between">
                <span class="text-[10px] font-black text-violet-600 dark:text-violet-400 uppercase tracking-widest">{{ __('Mencionar') }}</span>
                <span class="text-[9px] text-violet-400 font-bold uppercase" x-text="'@' + mentionQuery"></span>
            </div>
            <div class="max-h-64 overflow-y-auto custom-scrollbar p-1" x-ref="mentionsList">
                <template x-for="(user, index) in filteredMentions" :key="user.id">
                    <button type="button" 
                        @click="selectMention(user)"
                        @mouseenter="mentionIndex = index"
                        :class="mentionIndex === index ? 'bg-violet-600 text-white shadow-lg' : 'text-gray-700 dark:text-gray-300 hover:bg-violet-50 dark:hover:bg-violet-900/20'"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-all duration-200 text-left group">
                        <img :src="user.avatar" class="w-8 h-8 rounded-full border-2 border-white dark:border-gray-700 shadow-sm" :class="mentionIndex === index ? 'border-violet-400' : ''">
                        <div class="flex flex-col">
                            <span class="font-bold" x-text="user.name"></span>
                            <span class="text-[10px] opacity-60 font-medium tracking-tight" :class="mentionIndex === index ? 'text-violet-100' : 'text-gray-500'">{{ __('Miembro del equipo') }}</span>
                        </div>
                    </button>
                </template>
            </div>
        </div>

        <!-- Uploading overlay -->
        <template x-if="uploading">
            <div class="absolute inset-0 z-[1100] bg-white/50 dark:bg-gray-900/50 flex items-center justify-center backdrop-blur-[1px] rounded-[2rem]">
                <div class="flex items-center gap-3 bg-white dark:bg-gray-800 px-6 py-3 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700">
                    <svg class="animate-spin h-5 w-5 text-violet-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-xs font-black uppercase tracking-widest text-gray-700 dark:text-gray-300">{{ __('Subiendo...') }}</span>
                </div>
            </div>
        </template>
        
        <!-- Header / Toolbar -->
        <div class="relative z-50 flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-800 rounded-t-[2rem]">
            <div class="flex p-1 bg-gray-200/50 dark:bg-gray-950/50 rounded-xl">
                <button type="button" 
                    @click="tab = 'write'"
                    :class="tab === 'write' ? 'bg-white dark:bg-gray-800 text-violet-600 dark:text-violet-400 shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="px-4 py-1.5 text-[10px] font-black uppercase tracking-widest rounded-lg transition-all border border-transparent"
                    :class="tab === 'write' ? 'border-gray-100 dark:border-gray-700' : ''">
                    {{ __('Editar') }}
                </button>
                <button type="button" 
                    @click="tab = 'preview'"
                    :class="tab === 'preview' ? 'bg-white dark:bg-gray-800 text-violet-600 dark:text-violet-400 shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="px-4 py-1.5 text-[10px] font-black uppercase tracking-widest rounded-lg transition-all border border-transparent"
                    :class="tab === 'preview' ? 'border-gray-100 dark:border-gray-700' : ''">
                    {{ __('Vista Previa') }}
                </button>
            </div>

            <div class="flex items-center gap-4">
                <div class="relative" x-data="{ open: false }">
                    <button type="button" @click="open = !open" 
                        class="flex items-center justify-center w-9 h-9 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-500 hover:text-violet-600 hover:border-violet-300 transition-all shadow-sm"
                        title="{{ __('Insertar icono') }}">
                        😊
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition x-cloak
                        class="absolute right-0 mt-3 z-[100] w-72 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-[2rem] shadow-2xl p-4 overflow-hidden">
                        <div class="grid grid-cols-8 gap-1 max-h-64 overflow-y-auto custom-scrollbar">
                            @foreach(['😀','😃','😄','😁','😆','😅','😂','🤣','😊','😇','🙂','🙃','😉','😌','😍','🥰','😘','😗','😙','😚','😋','😛','😝','😜','🤪','🤨','🧐','🤓','😎','🤩','🥳','😏','😒','😞','😔','😟','😕','🙁','☹️','😣','😖','😫','😩','🥺','😢','😭','😤','😠','😡','🤬','🤯','😳','🥵','🥶','😱','😨','😰','😥','😓','🤗','🤔','🤭','🤫','🤥','😶','😐','😑','😬','🙄','😯','😦','😧','😮','😲','🥱','😴','🤤','😪','😵','🤐','🥴','🤢','🤮','🤧','😇','🤠','🤡','👋','👌','👍','👎','👏','🙌','🙏','💪','🤝','❤️','🔥','✨','⭐','⚡','✅','❌','⚠️','💡','💰','🚀'] as $icon)
                                <button type="button" @click="insertAtCursor('{{ $icon }}'); open = false" 
                                        class="text-xl hover:bg-violet-50 dark:hover:bg-violet-900/30 p-1.5 rounded-xl transition-all hover:scale-125">
                                    {{ $icon }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="h-6 w-px bg-gray-200 dark:bg-gray-800"></div>
                <a href="https://www.markdownguide.org/cheat-sheet/" target="_blank" 
                   class="text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-violet-600 transition-colors">
                    Markdown
                </a>
            </div>
        </div>

        <!-- Editor Container -->
        <div class="relative min-h-[150px]">
            <div x-show="tab === 'write'" class="h-full">
                <textarea 
                    x-ref="textarea"
                    name="{{ $name }}" 
                    id="{{ $id ?? $name }}"
                    x-model="content"
                    rows="{{ $rows }}"
                    placeholder="{{ $placeholder }}"
                    class="w-full bg-transparent border-0 focus:ring-0 text-sm py-5 px-6 text-gray-800 dark:text-gray-200 placeholder-gray-400 dark:placeholder-gray-600 resize-y min-h-[150px] font-mono leading-relaxed"
                    @input="handleInput($event)"
                    @keydown="handleKeyDown($event)"
                    @paste="handlePaste($event)"
                    @scroll="updateMentionPosition()"
                    {{ $required ? 'required' : '' }}
                ></textarea>
            </div>
            <div x-show="tab === 'preview'" 
                class="prose prose-sm dark:prose-invert max-w-none break-words leading-relaxed py-5 px-6 bg-gray-50/30 dark:bg-gray-950/20"
                x-html="preview"
                x-cloak>
            </div>
        </div>
        
        <!-- Footer Info -->
        <div class="px-6 py-2 bg-gray-50/50 dark:bg-gray-800/30 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between rounded-b-[2rem]">
            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">{{ __('Escribe @ para mencionar') }}</span>
            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest" x-text="(content || '').length + ' caracteres'"></span>
        </div>
    </div>
</div>
