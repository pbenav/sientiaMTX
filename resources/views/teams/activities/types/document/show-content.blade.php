@php
    $meta = $activity->metadata ?? [];
    $chapters = $meta['chapters'] ?? [];
@endphp


{{-- Document Metadata Card --}}
<div class="bg-orange-50 dark:bg-orange-900/10 border border-orange-100 dark:border-orange-800/30 rounded-2xl p-5 shadow-sm">
    <h3 class="text-xs font-black text-orange-500 dark:text-orange-400 uppercase tracking-widest mb-3 flex items-center gap-1.5">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
        Propiedades del Documento
    </h3>
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
        @if(!empty($meta['version']))
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Versión</p>
            <p class="text-sm font-bold text-gray-900 dark:text-white font-mono">{{ $meta['version'] }}</p>
        </div>
        @endif
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Capítulos</p>
            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ count($chapters) }}</p>
        </div>
        @if(!empty($meta['is_ephemeral']))
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Efímero</p>
            <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">Sí</span>
        </div>
        @endif
    </div>
</div>

{{-- Document Status & Collaborators --}}
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm">
    <div class="flex flex-col xl:flex-row gap-6 justify-between">
        <div class="flex-1">
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Estado del Documento</p>
            <form action="{{ route('teams.activities.change-status', [$team, $activity]) }}" method="POST" class="w-full">
                @csrf
                @method('PATCH')
                <div class="flex items-center p-1 bg-gray-100 dark:bg-gray-800 rounded-xl w-full sm:w-auto sm:inline-flex overflow-x-auto no-scrollbar shadow-inner">
                    @php
                        $docStatuses = ['draft', 'under_review', 'approved', 'completed', 'archived'];
                    @endphp
                    @foreach($docStatuses as $val)
                        <label class="relative cursor-pointer shrink-0 px-3 sm:px-5 py-2 text-[10px] sm:text-xs font-bold uppercase tracking-wider rounded-lg transition-all text-center flex-1 sm:flex-none {{ $activity->status_value == $val ? 'bg-white dark:bg-gray-700 text-violet-600 dark:text-violet-400 shadow-sm border border-gray-200/50 dark:border-gray-600/50' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-200/50 dark:hover:bg-gray-700/50' }}">
                            <input type="radio" name="status" value="{{ $val }}" class="hidden" onchange="this.form.submit()" {{ $activity->status_value == $val ? 'checked' : '' }}>
                            {{ __("activities.statuses.{$val}") }}
                        </label>
                    @endforeach
                </div>
            </form>
        </div>
        @if(!empty($meta['collaborators']))
        <div class="xl:w-1/3 shrink-0">
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Colaboradores</p>
            <div class="flex gap-2 flex-wrap">
                @foreach((array)$meta['collaborators'] as $collab)
                <span class="px-2.5 py-1 rounded-lg text-[10px] sm:text-xs font-bold bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300 border border-orange-200 dark:border-orange-800/30 shadow-sm">{{ $collab }}</span>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Description --}}
@php
    $displayDescription = $activity->description ?: ($activity->parent?->description ?? null);
    $displayObservations = $activity->observations ?: ($activity->parent?->observations ?? null);
@endphp

@if ($displayDescription)
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                {{ __('activities.description') }}
            </h3>
            <button onclick="printSection('Descripción', 'description-content')" 
                    class="p-1.5 bg-gray-50 dark:bg-gray-800 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-xl transition-all border border-transparent hover:border-violet-100 dark:hover:border-violet-800 shadow-sm flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest"
                    title="Imprimir descripción">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Imprimir
            </button>
        </div>
        <div id="description-content" style="height: 350px; max-height: none; overflow-y: auto;"
            class="text-sm text-gray-700 dark:text-gray-300 prose dark:prose-invert max-w-none prose-sm leading-relaxed resize-y min-h-[250px] custom-scrollbar pr-4 py-2"
            x-data="{ content: `{{ base64_encode($displayDescription) }}` }"
            x-init="$nextTick(() => { 
               const decoded = decodeURIComponent(escape(window.atob(content)));
               $el.innerHTML = typeof marked !== 'undefined' ? marked.parse(decoded, {breaks: true, gfm: true}) : decoded; 
            })">
            <div class="flex items-center justify-center p-4">
                <svg class="animate-spin h-5 w-5 text-violet-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            </div>
        </div>
    </div>
@endif

@if ($displayObservations)
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                {{ __('activities.observations') }}
            </h3>
            <button onclick="printSection('Observaciones', 'observations-content')" 
                    class="p-1.5 bg-gray-50 dark:bg-gray-800 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-xl transition-all border border-transparent hover:border-violet-100 dark:hover:border-violet-800 shadow-sm flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest"
                    title="Imprimir observaciones">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Imprimir
            </button>
        </div>
        <div id="observations-content" style="height: 350px; max-height: none; overflow-y: auto;"
            class="text-sm text-gray-700 dark:text-gray-300 prose dark:prose-invert max-w-none prose-sm leading-relaxed resize-y min-h-[250px] custom-scrollbar pr-4 py-2"
            x-data="{ content: `{{ base64_encode($displayObservations) }}` }"
            x-init="$nextTick(() => { 
               const decoded = decodeURIComponent(escape(window.atob(content)));
               $el.innerHTML = typeof marked !== 'undefined' ? marked.parse(decoded, {breaks: true, gfm: true}) : decoded; 
            })">
            <div class="flex items-center justify-center p-4">
                <svg class="animate-spin h-5 w-5 text-violet-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            </div>
        </div>
    </div>
@endif

@if(count($chapters) > 0)
    <div id="chapters-section" x-data="{ 
            search: '',
            currentIndex: 0,
            totalMatches: 0,
            init() {
                $watch('search', () => {
                    this.currentIndex = 0;
                    this.updateMatches();
                });
            },
            updateMatches() {
                setTimeout(() => {
                    const marks = document.querySelectorAll('mark.search-highlight-mark');
                    this.totalMatches = marks.length;
                    if (this.totalMatches === 0) {
                        this.currentIndex = 0;
                        return;
                    }
                    if (this.currentIndex >= this.totalMatches || this.currentIndex < 0) {
                        this.currentIndex = 0;
                    }
                    this.highlightCurrent(marks);
                }, 100);
            },
            highlightCurrent(marks) {
                const list = marks || document.querySelectorAll('mark.search-highlight-mark');
                if (list.length === 0) return;
                list.forEach((m, idx) => {
                    if (idx === this.currentIndex) {
                        m.className = 'bg-violet-600 text-white dark:bg-violet-500 font-extrabold rounded px-2 py-0.5 shadow-md ring-2 ring-violet-400 transition-all search-highlight-mark search-highlight-active';
                        m.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    } else {
                        m.className = 'bg-yellow-300 dark:bg-yellow-900 text-gray-900 dark:text-yellow-100 rounded px-1.5 py-0.5 font-bold shadow-sm search-highlight-mark';
                    }
                });
            },
            nextMatch() {
                if (this.totalMatches <= 0) return;
                this.currentIndex = (this.currentIndex + 1) % this.totalMatches;
                this.highlightCurrent();
            },
            prevMatch() {
                if (this.totalMatches <= 0) return;
                this.currentIndex = (this.currentIndex - 1 + this.totalMatches) % this.totalMatches;
                this.highlightCurrent();
            }
        }" 
        @matches-updated.window="updateMatches()"
        class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm transition-colors space-y-6 mt-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gray-100 dark:border-gray-800">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-violet-50 dark:bg-violet-950/40 text-violet-600 dark:text-violet-400 flex items-center justify-center shrink-0 border border-violet-100 dark:border-violet-800/50 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-800 dark:text-white">Estructura del Documento</h3>
                    <p class="text-[10px] text-gray-400 dark:text-gray-500 font-medium uppercase tracking-wide">{{ count($chapters) }} Capítulos integrados</p>
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
                <div class="relative w-full sm:w-64">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" /></svg>
                    </div>
                    <input type="text" x-model="search" @keydown.enter.prevent="nextMatch()" @keydown.shift.enter.prevent="prevMatch()" class="block w-full pl-9 pr-9 py-2 border border-gray-200 dark:border-gray-700 rounded-xl leading-5 bg-gray-50 dark:bg-gray-800/50 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500 sm:text-xs transition-colors" placeholder="Buscar en los capítulos...">
                    <button type="button" x-show="search.length > 0" @click="search = ''; totalMatches = 0;" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" style="display: none;" title="Limpiar búsqueda">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <!-- Navegador de ocurrencias -->
                <div class="flex items-center gap-1.5 shrink-0 bg-gray-50 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-xl px-2.5 py-1.5 shadow-sm" x-show="search.trim().length > 0">
                    <span class="text-xs font-bold text-gray-500 dark:text-gray-400 font-mono mr-1 select-none flex items-center gap-1">
                        <template x-if="totalMatches > 0">
                            <span><span x-text="currentIndex + 1" class="text-violet-600 dark:text-violet-400 font-extrabold"></span>/<span x-text="totalMatches"></span></span>
                        </template>
                        <template x-if="totalMatches === 0">
                            <span class="text-rose-500 font-semibold">0/0</span>
                        </template>
                    </span>
                    <button type="button" @click="prevMatch()" :disabled="totalMatches <= 1" class="p-1 text-gray-500 hover:text-violet-600 dark:hover:text-violet-400 disabled:opacity-30 disabled:hover:text-gray-500 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors" title="Ocurrencia anterior (Shift+Enter)">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                        </svg>
                    </button>
                    <button type="button" @click="nextMatch()" :disabled="totalMatches <= 1" class="p-1 text-gray-500 hover:text-violet-600 dark:hover:text-violet-400 disabled:opacity-30 disabled:hover:text-gray-500 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors" title="Siguiente ocurrencia (Enter)">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>

                <button type="button" onclick="printDocumentBook()" class="flex items-center justify-center gap-1.5 text-xs bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:text-violet-600 dark:hover:text-violet-400 px-3.5 py-2 rounded-xl border border-gray-200 dark:border-gray-700 font-bold transition-all shadow-sm active:scale-95 w-full sm:w-auto shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span class="whitespace-nowrap">Imprimir Libro</span>
                </button>
            </div>
        </div>
        
        <div class="space-y-4">
            @foreach($chapters as $idx => $chapter)
            <div x-data="{ 
                    open: false,
                    rawContent: `{{ base64_encode($chapter['content'] ?? '') }}`,
                    decodedContent: '',
                    init() {
                        this.decodedContent = decodeURIComponent(escape(window.atob(this.rawContent)));
                        if (search && search.trim() !== '' && this.isMatch) {
                            this.open = true;
                        }
                        $watch('search', value => {
                            if (value && value.trim() !== '' && this.isMatch) {
                                this.open = true;
                            } else if (!value || value.trim() === '') {
                                this.open = false;
                            }
                            this.updateContent();
                        });
                    },
                    get isMatch() {
                        if (!search || search.trim() === '') return true;
                        const s = search.trim().toLowerCase();
                        return '{{ addslashes(strtolower($chapter['title'] ?? '')) }}'.includes(s) || this.decodedContent.toLowerCase().includes(s);
                    },
                    updateContent() {
                        if (!this.$refs.mdContainer) return;
                        let html = typeof marked !== 'undefined' ? marked.parse(this.decodedContent, {breaks: true, gfm: true}) : this.decodedContent;
                        this.$refs.mdContainer.innerHTML = html;
                        
                        if (search && search.trim() !== '') {
                            let s = search.trim();
                            let escaped = s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                            let regex = new RegExp('(' + escaped + ')', 'gi');
                            let walker = document.createTreeWalker(this.$refs.mdContainer, NodeFilter.SHOW_TEXT, null, false);
                            let nodesToReplace = [];
                            let node;
                            
                            while (node = walker.nextNode()) {
                                if (node.parentNode && ['SCRIPT', 'STYLE', 'MARK'].includes(node.parentNode.nodeName)) continue;
                                if (node.nodeValue.match(regex)) {
                                    nodesToReplace.push(node);
                                }
                            }
                            
                            nodesToReplace.forEach(n => {
                                let span = document.createElement('span');
                                span.innerHTML = n.nodeValue.replace(regex, '<mark class=\'bg-yellow-300 dark:bg-yellow-900 text-gray-900 dark:text-yellow-100 rounded px-1.5 py-0.5 font-bold shadow-sm search-highlight-mark\'>$1</mark>');
                                n.parentNode.replaceChild(span, n);
                            });
                        }
                        this.$dispatch('matches-updated');
                    }
                 }" 
                 x-show="isMatch"
                 class="bg-gray-50/40 dark:bg-gray-800/20 border border-gray-100 dark:border-gray-800/60 rounded-2xl p-5 space-y-4 transition-all duration-300">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800/50 cursor-pointer group" @click="open = !open">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-7 h-7 rounded-xl bg-violet-100 dark:bg-violet-950 text-violet-700 dark:text-violet-300 font-black text-xs flex items-center justify-center border border-violet-200 dark:border-violet-800 shadow-sm shrink-0 group-hover:scale-110 transition-transform">
                            {{ $idx + 1 }}
                        </span>
                        <div class="min-w-0">
                            <h4 class="text-xs font-bold text-gray-900 dark:text-white truncate group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors">
                                {{ $chapter['title'] ?? 'Capítulo sin título' }}
                            </h4>
                            <p class="text-[10px] text-gray-400">Por {{ $chapter['author_name'] ?? 'Autor' }} • {{ isset($chapter['updated_at']) ? \Carbon\Carbon::parse($chapter['updated_at'])->diffForHumans() : '' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button type="button" @click.stop="$dispatch('ai:analyze-task', { taskId: {{ $activity->id }}, teamId: {{ $team->id }}, taskTitle: '{{ addslashes($activity->title) }}', section: 'chapter-{{ $idx }}' })" class="p-1.5 text-gray-400 hover:text-indigo-500 rounded-xl hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors" title="Enviar a Ax.ia">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </button>
                        <button type="button" @click.stop="printSection('{{ addslashes($chapter['title'] ?? 'Capítulo ' . ($idx + 1)) }}', 'chapter-content-{{ $idx }}')" class="p-1.5 text-gray-400 hover:text-orange-500 rounded-xl hover:bg-orange-50 dark:hover:bg-orange-900/20 transition-colors" title="Imprimir Capítulo">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                        </button>
                        <div class="text-gray-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                </div>
                <div x-show="open" x-collapse style="display: none;"
                     x-init="$nextTick(() => { updateContent(); })">
                    <div x-ref="mdContainer" id="chapter-content-{{ $idx }}" style="height: 200px; max-height: none; overflow-y: auto;" class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 resize-y min-h-[120px] custom-scrollbar pr-4 p-4 bg-white dark:bg-gray-900/50 border border-gray-100 dark:border-gray-800/80 rounded-2xl shadow-sm mt-2 markdown-body">
                        <div class="flex items-center justify-center p-4">
                            <svg class="animate-spin h-5 w-5 text-violet-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
@endif
