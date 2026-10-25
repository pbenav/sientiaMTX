@props(['name', 'value' => '', 'id' => null, 'label' => null, 'rows' => 6, 'placeholder' => '', 'uploadUrl' => null, 'mentionsUrl' => null, 'required' => false])

<div x-data="{ 
    id: 'md-editor-' + Math.random().toString(36).substr(2, 9),
    content: @js($value), 
    tab: 'write',
    showMermaidModal: false,
    uploading: false,
    uploadUrl: @js($uploadUrl),
    mentionsUrl: @js($mentionsUrl),
    mentions: [],
    mentioning: false,
    mentionQuery: '',
    mentionIndex: 0,
    mentionPos: { top: 0, left: 0 },
    cancelledAt: -1,
    hasJustTypedAt: false,
    init() {
        this.$watch('content', val => {
            this.$el.dispatchEvent(new CustomEvent('input', { detail: val, bubbles: true }));
        });
        this.fetchMentions();
        if (this.content) {
            this.cancelledAt = this.content.lastIndexOf('@');
        }
        this.$watch('mentionQuery', value => {
            this.mentionIndex = 0;
        });
        this.$watch('tab', val => {
            if (val === 'preview') {
                this.$nextTick(() => {
                    const container = document.getElementById(this.id + '-preview');
                    if (container && window.renderMermaidDiagrams) {
                        window.renderMermaidDiagrams(container);
                    }
                });
            }
        });
    },

    get preview() {
        if (typeof marked === 'undefined') return '{{ __('Loading...') }}';
        marked.use({ breaks: true, gfm: true });
        return typeof DOMPurify !== 'undefined' ? DOMPurify.sanitize(marked.parse(this.content || '')) : marked.parse(this.content || '').replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '').replace(/on[a-z]+=/gi, '');
    },

    async fetchMentions() {
        if (!this.mentionsUrl) return;
        if (window.sientiaMentionsCache && window.sientiaMentionsCache.length > 0) {
            this.mentions = window.sientiaMentionsCache;
            return;
        }
        try {
            const res = await fetch(this.mentionsUrl);
            const data = await res.json();
            window.sientiaMentionsCache = data;
            this.mentions = data;
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
        const textBefore = (this.content || '').substring(0, cursor);
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
            top: offsetTop - scrollTop + 60,
            left: offsetLeft - scrollLeft + 15
        };
        
        document.body.removeChild(div);
    },

    handleInput(e) {
        if (!this.mentioning) return;
        const textarea = this.$refs.textarea;
        if (!textarea) return;
        
        const cursor = textarea.selectionStart;
        const textBefore = (this.content || '').substring(0, cursor);
        const lastAt = textBefore.lastIndexOf('@');
        
        if (lastAt === -1) {
            this.mentioning = false;
            this.hasJustTypedAt = false;
            return;
        }
        
        const query = textBefore.substring(lastAt + 1);
        if (query.length > 15 || /\s/.test(query)) {
            this.mentioning = false;
            this.hasJustTypedAt = false;
            return;
        }
        
        this.mentionQuery = query;
        this.updateMentionPosition();
    },

    handleKeyDown(e) {
        if (e.key === '@') {
            this.hasJustTypedAt = true;
            this.mentioning = true;
            this.mentionQuery = '';
            this.mentionIndex = 0;
            this.$nextTick(() => {
                this.updateMentionPosition();
                if (this.mentions.length === 0) this.fetchMentions();
            });
            return;
        }

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
                this.cancelMention();
            }
        } else if (e.key === 'Escape') {
            this.mentioning = false;
            this.hasJustTypedAt = false;
        }
    },

    cancelMention() {
        this.mentioning = false;
        this.hasJustTypedAt = false;
        const textarea = this.$refs.textarea;
        if (textarea) {
            const cursor = textarea.selectionStart;
            const textBefore = (this.content || '').substring(0, cursor);
            this.cancelledAt = textBefore.lastIndexOf('@');
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
        this.hasJustTypedAt = false;
        
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
                e.preventDefault();
                const blob = items[i].getAsFile();
                
                if (typeof window.openGlobalImageEditor === 'function') {
                    window.openGlobalImageEditor(blob, (editedFile) => {
                        this.uploadFile(editedFile);
                    });
                } else {
                    this.uploadFile(blob);
                }
            }
        }
    },

    handleDrop(e) {
        if (!this.uploadUrl) return;
        
        const items = e.dataTransfer.items;
        if (!items) return;
        
        for (let i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image') !== -1) {
                const blob = items[i].getAsFile();
                
                if (typeof window.openGlobalImageEditor === 'function') {
                    window.openGlobalImageEditor(blob, (editedFile) => {
                        this.uploadFile(editedFile);
                    });
                } else {
                    this.uploadFile(blob);
                }
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

    <div @click.outside="cancelMention()"
         @keyup.escape.window="cancelMention()"
         class="relative flex flex-col w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-[2rem] shadow-sm transition-all focus-within:ring-2 focus-within:ring-violet-500/20 focus-within:border-violet-500/50"
         :class="uploading ? 'opacity-70 pointer-events-none' : ''">
        
        <!-- Mention Dropdown (Now placed here to be relative to the entire component but absolute positioned) -->
        <template x-if="mentioning && hasJustTypedAt && filteredMentions.length > 0">
            <div x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 :style="`top: ${mentionPos.top}px; left: ${mentionPos.left}px;`"
                 @wheel.stop=""
                 class="absolute z-[1000] w-72 bg-white dark:bg-gray-800 border border-violet-100 dark:border-violet-900/50 rounded-2xl shadow-2xl overflow-hidden">
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
        </template>

        <!-- Uploading overlay -->
        <template x-if="uploading">
            <div class="absolute inset-0 z-20 bg-white/50 dark:bg-gray-900/50 flex items-center justify-center backdrop-blur-[1px] rounded-[2rem]">
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
        <div class="relative z-10 flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-gray-800 border-b border-gray-100 dark:border-gray-800 rounded-t-[2rem]">
            <div class="flex p-1 bg-gray-200/50 dark:bg-gray-950/50 rounded-xl">
                <button type="button" 
                    @click="tab = 'write'"
                    :class="tab === 'write' ? 'bg-white dark:bg-gray-800 text-violet-600 dark:text-violet-400 shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="px-4 py-1.5 text-[10px] font-black uppercase tracking-widest rounded-lg transition-all border border-transparent"
                    :class="tab === 'write' ? 'border-gray-100 dark:border-gray-700' : ''">
                    {{ __('Edit') }}
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
                <button type="button" @click="if(typeof SientiaPrint !== 'undefined') SientiaPrint.print('Impresión de Texto', preview); else { const w = window.open('', '_blank'); w.document.write('<html><head><title>Imprimir</title><style>@media print{/* -- Print Mode Code Block Fix -- */pre,code,pre *,code *,.prose pre,.prose code{background-color:transparent!important;color:#000!important}div[class*=bg-gray-8],div[class*=bg-gray-9],div[class*=bg-slate-8],div[class*=bg-slate-9],div[style*=background],.prose div,.markdown-body div,.bg-gray-800,.bg-gray-900,.dark\\:bg-gray-800,.dark\\:bg-gray-900{background-color:transparent!important}pre,.prose pre{border:1px solid #cbd5e1!important;border-radius:0.25rem!important;white-space:pre-wrap!important;word-break:break-all!important;padding:0.5rem!important}code,.prose code{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace!important}}body{font-family:sans-serif;padding:2rem;line-height:1.6} img{max-width:100%} pre{background:#f1f5f9;padding:1rem;border-radius:0.5rem;overflow-x:auto}</style></head><body>' + preview + '</body></html>'); w.document.close(); setTimeout(() => w.print(), 500); }"
                    class="flex items-center gap-1 text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-orange-500 transition-colors" title="{{ __('Print content') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    {{ __('Print') }}
                </button>
                <div class="h-6 w-px bg-gray-200 dark:bg-gray-800"></div>
                <a href="https://www.markdownguide.org/cheat-sheet/" target="_blank" 
                   class="text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-violet-600 transition-colors" title="{{ __('Chuleta oficial de Markdown') }}">
                    Markdown
                </a>
                <div class="h-6 w-px bg-gray-200 dark:bg-gray-800"></div>
                <button type="button" @click="showMermaidModal = true" 
                   class="flex items-center gap-1 text-[10px] font-black uppercase tracking-widest text-violet-600 dark:text-violet-400 hover:text-violet-700 dark:hover:text-violet-300 transition-colors" title="{{ __('Chuleta y ejemplos de diagramas Mermaid') }}">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                    <span>Mermaid</span>
                </button>
            </div>
        </div>

        <!-- Editor Container -->
        <div class="relative min-h-[150px] rounded-b-[2rem]">
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
                    @drop.prevent="handleDrop($event)"
                    @dragover.prevent=""
                    @scroll="updateMentionPosition()"
                    {{ $required ? 'required' : '' }}
                ></textarea>
            </div>
            <div :id="id + '-preview'" x-show="tab === 'preview'" 
                class="prose prose-sm dark:prose-invert max-w-none break-words leading-relaxed py-5 px-6 bg-gray-50/30 dark:bg-gray-950/20 resize-y overflow-y-auto max-h-[650px] min-h-[250px] custom-scrollbar"
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

    <!-- Modal: Chuleta Interactiva de Diagramas Mermaid -->
    <template x-teleport="body">
        <div x-show="showMermaidModal" 
             x-cloak 
             class="fixed inset-0 z-[99999] flex items-center justify-center p-4 sm:p-6"
             @keydown.escape.window="showMermaidModal = false">
            
            <!-- Backdrop -->
            <div x-show="showMermaidModal" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100" 
                 x-transition:leave-end="opacity-0" 
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" 
                 @click="showMermaidModal = false"></div>

            <!-- Modal Panel -->
            <div x-show="showMermaidModal" 
                 x-data="{ 
                     activeTab: 'flowchart',
                     copiedKey: null,
                     copyCode(code, key) {
                         navigator.clipboard.writeText(code);
                         this.copiedKey = key;
                         setTimeout(() => this.copiedKey = null, 2000);
                     },
                     insertSnippet(snippet) {
                         insertAtCursor('\n```mermaid\n' + snippet.trim() + '\n```\n');
                         showMermaidModal = false;
                     }
                 }"
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95" 
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95" 
                 class="relative w-full max-w-4xl max-h-[90vh] bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col overflow-hidden text-left font-sans">
                
                <!-- Cabecera del Modal -->
                <div class="px-8 py-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-gradient-to-r from-violet-500/10 via-fuchsia-500/5 to-transparent">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-violet-600 flex items-center justify-center text-white shadow-lg shadow-violet-500/25">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-black text-slate-900 dark:text-white uppercase tracking-wider">Chuleta Rápida de Diagramas Mermaid</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Inserta plantillas listas para usar en tus notas, actividades y circuitos.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('docs', 'markdown-mermaid') }}" target="_blank" 
                           class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-violet-50 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300 hover:bg-violet-100 text-[11px] font-bold rounded-xl transition-colors">
                            <span>📖 Manual Completo</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                        <button type="button" @click="showMermaidModal = false" 
                                class="w-9 h-9 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Tabs de Tipos de Diagrama -->
                <div class="px-8 pt-4 pb-2 border-b border-slate-100 dark:border-slate-800 flex gap-2 overflow-x-auto custom-scrollbar bg-slate-50/50 dark:bg-slate-900/50">
                    <button type="button" @click="activeTab = 'flowchart'" 
                            :class="activeTab === 'flowchart' ? 'bg-violet-600 text-white shadow-md shadow-violet-600/20' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'"
                            class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all flex items-center gap-1.5 whitespace-nowrap">
                        <span>🔄 Flujograma</span>
                    </button>
                    <button type="button" @click="activeTab = 'sequence'" 
                            :class="activeTab === 'sequence' ? 'bg-violet-600 text-white shadow-md shadow-violet-600/20' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'"
                            class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all flex items-center gap-1.5 whitespace-nowrap">
                        <span>⚡ Secuencia</span>
                    </button>
                    <button type="button" @click="activeTab = 'state'" 
                            :class="activeTab === 'state' ? 'bg-violet-600 text-white shadow-md shadow-violet-600/20' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'"
                            class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all flex items-center gap-1.5 whitespace-nowrap">
                        <span>📌 Estados</span>
                    </button>
                    <button type="button" @click="activeTab = 'gantt'" 
                            :class="activeTab === 'gantt' ? 'bg-violet-600 text-white shadow-md shadow-violet-600/20' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'"
                            class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all flex items-center gap-1.5 whitespace-nowrap">
                        <span>📊 Cronograma (Gantt)</span>
                    </button>
                    <button type="button" @click="activeTab = 'pie'" 
                            :class="activeTab === 'pie' ? 'bg-violet-600 text-white shadow-md shadow-violet-600/20' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'"
                            class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all flex items-center gap-1.5 whitespace-nowrap">
                        <span>🥧 Gráfico Circular (Pie)</span>
                    </button>
                    <button type="button" @click="activeTab = 'er'" 
                            :class="activeTab === 'er' ? 'bg-violet-600 text-white shadow-md shadow-violet-600/20' : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700'"
                            class="px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all flex items-center gap-1.5 whitespace-nowrap">
                        <span>🗂️ Entidad-Relación</span>
                    </button>
                </div>

                <!-- Contenido de las pestañas -->
                <div class="p-8 overflow-y-auto flex-1 custom-scrollbar space-y-6">
                    
                    {{-- TAB 1: Flujograma --}}
                    <div x-show="activeTab === 'flowchart'" class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white">Flujograma de Tramitación y Decisión (TD: Top-Down)</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Ideal para describir procesos administrativos, bifurcaciones de validación o itinerarios de firma.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="copyCode(
`graph TD
    A([Inicio del Trámite]) --> B[Recepción de Solicitud]
    B --> C{¿Cumple requisitos?}
    C -- Sí --> D[Revisión por Coordinador]
    C -- No --> E[Requerimiento de Subsanación]
    E --> F{¿Aporta documentación?}
    F -- Sí --> D
    F -- No --> G([Archivo / Desistimiento])
    D --> H[Firma y Resolución]
    H --> I([Fin del Procedimiento])`, 'flow1')" 
                                        class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl transition-colors flex items-center gap-1.5">
                                    <span x-text="copiedKey === 'flow1' ? '✓ Copiado' : 'Copiar'"></span>
                                </button>
                                <button type="button" @click="insertSnippet(
`graph TD
    A([Inicio del Trámite]) --> B[Recepción de Solicitud]
    B --> C{¿Cumple requisitos?}
    C -- Sí --> D[Revisión por Coordinador]
    C -- No --> E[Requerimiento de Subsanación]
    E --> F{¿Aporta documentación?}
    F -- Sí --> D
    F -- No --> G([Archivo / Desistimiento])
    D --> H[Firma y Resolución]
    H --> I([Fin del Procedimiento])`)" 
                                        class="px-4 py-1.5 bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold rounded-xl transition-all shadow-md shadow-violet-600/20 flex items-center gap-1.5">
                                    <span>⚡ Insertar en Documento</span>
                                </button>
                            </div>
                        </div>
                        <pre class="bg-slate-950 text-emerald-400 p-4 rounded-2xl text-xs font-mono overflow-x-auto shadow-inner leading-relaxed border border-slate-800"><code>graph TD
    A([Inicio del Trámite]) --&gt; B[Recepción de Solicitud]
    B --&gt; C{¿Cumple requisitos?}
    C -- Sí --&gt; D[Revisión por Coordinador]
    C -- No --&gt; E[Requerimiento de Subsanación]
    E --&gt; F{¿Aporta documentación?}
    F -- Sí --&gt; D
    F -- No --&gt; G([Archivo / Desistimiento])
    D --&gt; H[Firma y Resolución]
    H --&gt; I([Fin del Procedimiento])</code></pre>
                    </div>

                    {{-- TAB 2: Secuencia --}}
                    <div x-show="activeTab === 'sequence'" class="space-y-4" style="display: none;">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white">Diagrama de Secuencia e Intercambio de Mensajes</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Muestra la interacción paso a paso entre actores, Sede Electrónica y AutoFirma.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="copyCode(
`sequenceDiagram
    autonumber
    actor U as Usuario Firmante
    participant S as Sede Electrónica MTX
    participant A as AutoFirma Local
    participant T as Servidor de Sellado

    U->>S: Solicita firmar documento
    S->>S: Calcula hash SHA-256 y prepara PDF
    S->>A: Invoca AutoScript con hash y coords
    A->>U: Pide selección de certificado FNMT/DNIe
    U-->>A: Selecciona certificado
    A->>A: Firma digital PKCS#7 / PAdES
    A-->>S: Devuelve paquete criptográfico
    S->>T: Registra diligencia y CSV
    T-->>S: Validación y sello de tiempo
    S-->>U: Muestra documento firmado con QR` , 'seq1')" 
                                        class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl transition-colors">
                                    <span x-text="copiedKey === 'seq1' ? '✓ Copiado' : 'Copiar'"></span>
                                </button>
                                <button type="button" @click="insertSnippet(
`sequenceDiagram
    autonumber
    actor U as Usuario Firmante
    participant S as Sede Electrónica MTX
    participant A as AutoFirma Local
    participant T as Servidor de Sellado

    U->>S: Solicita firmar documento
    S->>S: Calcula hash SHA-256 y prepara PDF
    S->>A: Invoca AutoScript con hash y coords
    A->>U: Pide selección de certificado FNMT/DNIe
    U-->>A: Selecciona certificado
    A->>A: Firma digital PKCS#7 / PAdES
    A-->>S: Devuelve paquete criptográfico
    S->>T: Registra diligencia y CSV
    T-->>S: Validación y sello de tiempo
    S-->>U: Muestra documento firmado con QR`)" 
                                        class="px-4 py-1.5 bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold rounded-xl transition-all shadow-md shadow-violet-600/20 flex items-center gap-1.5">
                                    <span>⚡ Insertar en Documento</span>
                                </button>
                            </div>
                        </div>
                        <pre class="bg-slate-950 text-cyan-400 p-4 rounded-2xl text-xs font-mono overflow-x-auto shadow-inner leading-relaxed border border-slate-800"><code>sequenceDiagram
    autonumber
    actor U as Usuario Firmante
    participant S as Sede Electrónica MTX
    participant A as AutoFirma Local
    participant T as Servidor de Sellado

    U-&gt;&gt;S: Solicita firmar documento
    S-&gt;&gt;S: Calcula hash SHA-256 y prepara PDF
    S-&gt;&gt;A: Invoca AutoScript con hash y coords
    A-&gt;&gt;U: Pide selección de certificado FNMT/DNIe
    U--&gt;&gt;A: Selecciona certificado
    A-&gt;&gt;A: Firma digital PKCS#7 / PAdES
    A--&gt;&gt;S: Devuelve paquete criptográfico
    S-&gt;&gt;T: Registra diligencia y CSV
    T--&gt;&gt;S: Validación y sello de tiempo
    S--&gt;&gt;U: Muestra documento firmado con QR</code></pre>
                    </div>

                    {{-- TAB 3: Estados --}}
                    <div x-show="activeTab === 'state'" class="space-y-4" style="display: none;">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white">Máquina de Estados de un Circuito o Expediente</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Visualiza el ciclo de vida de un trámite desde Borrador hasta Finalizado o Rechazado.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="copyCode(
`stateDiagram-v2
    [*] --> Borrador
    Borrador --> EnTramite : Iniciar Circuito
    EnTramite --> EnFirma : Etapa en curso
    EnFirma --> EnTramite : Firma aprobada (quedan pasos)
    EnFirma --> Aprobado : Última firma completada
    EnFirma --> Rechazado : Voto negativo / Reprobación
    Aprobado --> Publicado : Generar Recibo y CSV
    Publicado --> [*]
    Rechazado --> [*]`, 'state1')" 
                                        class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl transition-colors">
                                    <span x-text="copiedKey === 'state1' ? '✓ Copiado' : 'Copiar'"></span>
                                </button>
                                <button type="button" @click="insertSnippet(
`stateDiagram-v2
    [*] --> Borrador
    Borrador --> EnTramite : Iniciar Circuito
    EnTramite --> EnFirma : Etapa en curso
    EnFirma --> EnTramite : Firma aprobada (quedan pasos)
    EnFirma --> Aprobado : Última firma completada
    EnFirma --> Rechazado : Voto negativo / Reprobación
    Aprobado --> Publicado : Generar Recibo y CSV
    Publicado --> [*]
    Rechazado --> [*]`)" 
                                        class="px-4 py-1.5 bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold rounded-xl transition-all shadow-md shadow-violet-600/20 flex items-center gap-1.5">
                                    <span>⚡ Insertar en Documento</span>
                                </button>
                            </div>
                        </div>
                        <pre class="bg-slate-950 text-amber-400 p-4 rounded-2xl text-xs font-mono overflow-x-auto shadow-inner leading-relaxed border border-slate-800"><code>stateDiagram-v2
    [*] --&gt; Borrador
    Borrador --&gt; EnTramite : Iniciar Circuito
    EnTramite --&gt; EnFirma : Etapa en curso
    EnFirma --&gt; EnTramite : Firma aprobada (quedan pasos)
    EnFirma --&gt; Aprobado : Última firma completada
    EnFirma --&gt; Rechazado : Voto negativo / Reprobación
    Aprobado --&gt; Publicado : Generar Recibo y CSV
    Publicado --&gt; [*]
    Rechazado --&gt; [*]</code></pre>
                    </div>

                    {{-- TAB 4: Gantt --}}
                    <div x-show="activeTab === 'gantt'" class="space-y-4" style="display: none;">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white">Cronograma de Proyecto o Plan de Actuación (Gantt)</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Representa fases temporales, dependencias y plazos de entrega.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="copyCode(
`gantt
    title Cronograma de Implantación Sientia.MTX
    dateFormat  YYYY-MM-DD
    section Fase 1: Análisis
    Auditoría de Procesos       :done, a1, 2026-11-01, 7d
    Parametrización Circuitos   :active, a2, after a1, 10d
    section Fase 2: Despliegue
    Configuración AutoFirma     :b1, 2026-11-18, 5d
    Pruebas Piloto y Sellado    :b2, after b1, 7d
    section Fase 3: Producción
    Formación de Usuarios       :c1, after b2, 5d
    Puesta en Marcha            :crit, after c1, 3d`, 'gantt1')" 
                                        class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl transition-colors">
                                    <span x-text="copiedKey === 'gantt1' ? '✓ Copiado' : 'Copiar'"></span>
                                </button>
                                <button type="button" @click="insertSnippet(
`gantt
    title Cronograma de Implantación Sientia.MTX
    dateFormat  YYYY-MM-DD
    section Fase 1: Análisis
    Auditoría de Procesos       :done, a1, 2026-11-01, 7d
    Parametrización Circuitos   :active, a2, after a1, 10d
    section Fase 2: Despliegue
    Configuración AutoFirma     :b1, 2026-11-18, 5d
    Pruebas Piloto y Sellado    :b2, after b1, 7d
    section Fase 3: Producción
    Formación de Usuarios       :c1, after b2, 5d
    Puesta en Marcha            :crit, after c1, 3d`)" 
                                        class="px-4 py-1.5 bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold rounded-xl transition-all shadow-md shadow-violet-600/20 flex items-center gap-1.5">
                                    <span>⚡ Insertar en Documento</span>
                                </button>
                            </div>
                        </div>
                        <pre class="bg-slate-950 text-fuchsia-400 p-4 rounded-2xl text-xs font-mono overflow-x-auto shadow-inner leading-relaxed border border-slate-800"><code>gantt
    title Cronograma de Implantación Sientia.MTX
    dateFormat  YYYY-MM-DD
    section Fase 1: Análisis
    Auditoría de Procesos       :done, a1, 2026-11-01, 7d
    Parametrización Circuitos   :active, a2, after a1, 10d
    section Fase 2: Despliegue
    Configuración AutoFirma     :b1, 2026-11-18, 5d
    Pruebas Piloto y Sellado    :b2, after b1, 7d
    section Fase 3: Producción
    Formación de Usuarios       :c1, after b2, 5d
    Puesta en Marcha            :crit, after c1, 3d</code></pre>
                    </div>

                    {{-- TAB 5: Pie --}}
                    <div x-show="activeTab === 'pie'" class="space-y-4" style="display: none;">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white">Gráfico Circular de Distribución (Pie Chart)</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Para resumir porcentajes o cuotas de participación en informes ejecutivos.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="copyCode(
`pie title Estado de los Expedientes del Trimestre
    \"Completados y Firmados\" : 62
    \"En Trámite de Subsanación\" : 23
    \"Pendientes de Informe\" : 10
    \"Desestimados\" : 5`, 'pie1')" 
                                        class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl transition-colors">
                                    <span x-text="copiedKey === 'pie1' ? '✓ Copiado' : 'Copiar'"></span>
                                </button>
                                <button type="button" @click="insertSnippet(
`pie title Estado de los Expedientes del Trimestre
    \"Completados y Firmados\" : 62
    \"En Trámite de Subsanación\" : 23
    \"Pendientes de Informe\" : 10
    \"Desestimados\" : 5`)" 
                                        class="px-4 py-1.5 bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold rounded-xl transition-all shadow-md shadow-violet-600/20 flex items-center gap-1.5">
                                    <span>⚡ Insertar en Documento</span>
                                </button>
                            </div>
                        </div>
                        <pre class="bg-slate-950 text-pink-400 p-4 rounded-2xl text-xs font-mono overflow-x-auto shadow-inner leading-relaxed border border-slate-800"><code>pie title Estado de los Expedientes del Trimestre
    "Completados y Firmados" : 62
    "En Trámite de Subsanación" : 23
    "Pendientes de Informe" : 10
    "Desestimados" : 5</code></pre>
                    </div>

                    {{-- TAB 6: ER --}}
                    <div x-show="activeTab === 'er'" class="space-y-4" style="display: none;">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white">Diagrama de Entidad-Relación de Datos</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Para documentar la estructura de datos entre Expedientes, Circuitos y Firmantes.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="copyCode(
`erDiagram
    EXPEDIENTE ||--o{ CIRCUITO : contiene
    CIRCUITO ||--|{ ETAPA : estructura
    ETAPA }|--|| USUARIO : asignada_a
    ETAPA ||--o| FIRMA : produce
    FIRMA {
        string csv PK
        string sha256
        datetime signed_at
        string certificado
    }`, 'er1')" 
                                        class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl transition-colors">
                                    <span x-text="copiedKey === 'er1' ? '✓ Copiado' : 'Copiar'"></span>
                                </button>
                                <button type="button" @click="insertSnippet(
`erDiagram
    EXPEDIENTE ||--o{ CIRCUITO : contiene
    CIRCUITO ||--|{ ETAPA : estructura
    ETAPA }|--|| USUARIO : asignada_a
    ETAPA ||--o| FIRMA : produce
    FIRMA {
        string csv PK
        string sha256
        datetime signed_at
        string certificado
    }`)" 
                                        class="px-4 py-1.5 bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold rounded-xl transition-all shadow-md shadow-violet-600/20 flex items-center gap-1.5">
                                    <span>⚡ Insertar en Documento</span>
                                </button>
                            </div>
                        </div>
                        <pre class="bg-slate-950 text-blue-400 p-4 rounded-2xl text-xs font-mono overflow-x-auto shadow-inner leading-relaxed border border-slate-800"><code>erDiagram
    EXPEDIENTE ||--o{ CIRCUITO : contiene
    CIRCUITO ||--|{ ETAPA : estructura
    ETAPA }|--|| USUARIO : asignada_a
    ETAPA ||--o| FIRMA : produce
    FIRMA {
        string csv PK
        string sha256
        datetime signed_at
        string certificado
    }</code></pre>
                    </div>

                </div>

                <!-- Pie del Modal con Consejo Rápido -->
                <div class="px-8 py-3.5 bg-slate-50 dark:bg-slate-950/60 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500">
                    <div class="flex items-center gap-2">
                        <span class="text-amber-500">💡</span>
                        <span>Escribe <code>```mermaid</code> al principio y <code>```</code> al final en cualquier documento o tarea para generar diagramas automáticos.</span>
                    </div>
                    <button type="button" @click="showMermaidModal = false" class="text-xs font-bold text-slate-600 dark:text-slate-400 hover:underline">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
