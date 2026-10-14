@props([
    'team',
    'documents',
    'activeDocument' => null,
    'currentFolder' => null,
    'dateFrom' => '1970-01-01',
    'dateTo' => null,
])

<div class="flex flex-col md:flex-row gap-6">
    <!-- Sidebar de Wiki / Lista de Documentos -->
    <div class="w-full md:w-80 xl:w-96 shrink-0 flex flex-col gap-4">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden flex flex-col h-[calc(100vh-16rem)] sticky top-24">
            <div class="p-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/20">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-black uppercase tracking-widest text-gray-900 dark:text-white flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                        {{ __('Índice Documental') }}
                    </h3>
                    <span class="text-[10px] font-bold text-gray-400 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-full">
                        {{ $documents->count() }}
                    </span>
                </div>

                {{-- Filtro rápido de estado en el lector --}}
                <form action="{{ route('teams.library', $team) }}" method="GET" class="space-y-2">
                    <input type="hidden" name="mode" value="reader">
                    @if($currentFolder)
                        <input type="hidden" name="folder" value="{{ $currentFolder->id }}">
                    @endif
                    <div class="relative">
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('Filtrar por texto...') }}"
                               class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs py-1.5 pl-7 pr-7 focus:ring-2 focus:ring-violet-500/50 outline-none transition-all placeholder-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 absolute left-2 top-2.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </form>
            </div>

            <div class="flex-1 overflow-y-auto no-scrollbar p-2">
                @forelse($documents as $doc)
                     <a href="{{ route('teams.library', array_filter(['team' => $team, 'folder' => $currentFolder?->id, 'doc' => $doc->id, 'mode' => 'reader', 'q' => request('q')])) }}"
                        class="flex items-start gap-2.5 p-2.5 rounded-xl transition-all mb-1 {{ $activeDocument && $activeDocument->id == $doc->id ? 'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-300 font-bold border border-violet-200 dark:border-violet-800/40' : 'hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-400' }}">
                        <div class="mt-0.5 shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 {{ $doc->status_value == 'completed' || $doc->status_value == 'approved' ? 'text-emerald-500' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="text-xs truncate font-bold">{{ $doc->title }}</span>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-[9px] uppercase font-bold {{ $doc->status_value == 'completed' || $doc->status_value == 'approved' ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                                    {{ __("activities.statuses.{$doc->status_value}") }}
                                </span>
                                <span class="text-[9px] text-gray-400">{{ $doc->created_at->format('d/m/Y') }}</span>
                            </div>
                        </div>
                     </a>
                @empty
                    <div class="text-center p-6">
                        <span class="text-xs text-gray-400">{{ __('No hay documentos en este nivel.') }}</span>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Contenido del Documento Activo -->
    <div class="flex-1 min-w-0">
        @if($activeDocument)
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden min-h-[calc(100vh-16rem)] flex flex-col">
                <div class="p-6 border-b border-gray-100 dark:border-gray-800 flex justify-between items-start gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-lg bg-violet-50 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300 uppercase tracking-wider border border-violet-200 dark:border-violet-800 shrink-0">
                                {{ __('DOCUMENTO WIKI') }}
                            </span>
                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-lg {{ $activeDocument->status_value == 'completed' || $activeDocument->status_value == 'approved' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 dark:border-emerald-800' : 'bg-gray-50 text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-400' }} uppercase tracking-wider border shrink-0">
                                {{ __("activities.statuses.{$activeDocument->status_value}") }}
                            </span>
                            @if($activeDocument->folder)
                                <span class="text-xs text-gray-400 font-medium flex items-center gap-1">
                                    • 📁 {{ $activeDocument->folder->name }}
                                </span>
                            @endif
                        </div>
                        <h2 class="text-2xl font-black text-gray-900 dark:text-white truncate">{{ $activeDocument->title }}</h2>
                        <div class="flex items-center gap-4 mt-2 text-[11px] text-gray-400 font-medium shrink-0 flex-wrap">
                            <span class="flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                Creado: <strong class="text-gray-600 dark:text-gray-300">{{ $activeDocument->created_at->format('d/m/Y') }}</strong>
                            </span>
                            @if($activeDocument->updated_at && $activeDocument->updated_at->ne($activeDocument->created_at))
                            <span class="flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                Actualizado: <strong class="text-gray-600 dark:text-gray-300">{{ $activeDocument->updated_at->diffForHumans() }}</strong>
                            </span>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <button type="button"
                                @click="$dispatch('open-move-modal', { type: 'document', id: {{ $activeDocument->id }}, name: '{{ addslashes($activeDocument->title) }}', current_folder_id: {{ $activeDocument->folder_id ?? 'null' }} })"
                                class="px-3.5 py-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-bold transition-colors flex items-center gap-1.5"
                                title="{{ __('Mover a otra carpeta') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            {{ __('Mover') }}
                        </button>

                        <a href="{{ route('teams.activities.show', [$team, $activeDocument]) }}"
                           class="px-4 py-2 bg-violet-50 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-800 text-xs font-bold uppercase rounded-xl hover:bg-violet-100 transition-colors flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                            {{ __('Editar') }}
                        </a>
                    </div>
                </div>

                <div class="p-6 flex-1 flex flex-col min-w-0">
                    @php
                        $chapters = $activeDocument->metadata['chapters'] ?? [];
                    @endphp

                    @if(count($chapters) > 0)
                        <div id="chapters-section" x-data="{
                                search: @js(request('q', '')),
                                currentIndex: 0,
                                totalMatches: 0,
                                init() {
                                    $watch('search', () => {
                                        this.currentIndex = 0;
                                        this.updateMatches();
                                    });
                                    setTimeout(() => this.updateMatches(), 400);
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
                            class="bg-gray-50/50 dark:bg-gray-800/30 border border-gray-100 dark:border-gray-800 rounded-3xl p-6 shadow-sm transition-colors space-y-6">

                            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-2xl bg-violet-100 dark:bg-violet-900/40 text-violet-600 dark:text-violet-400 flex items-center justify-center shrink-0 border border-violet-200 dark:border-violet-800/50 shadow-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="text-sm font-bold text-gray-800 dark:text-white truncate">{{ __('Estructura del Documento') }}</h3>
                                        <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wide">{{ count($chapters) }} {{ __('Capítulos') }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <button type="button"
                                            onclick="printDocumentBook()"
                                            class="flex items-center gap-1.5 text-xs bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 hover:text-violet-600 dark:hover:text-violet-400 px-3.5 py-2 rounded-xl border border-gray-200 dark:border-gray-700 font-bold transition-all shadow-sm active:scale-95">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                        </svg>
                                        <span>{{ __('Imprimir Libro') }}</span>
                                    </button>
                                </div>
                            </div>

                            <div class="space-y-4 min-w-0">
                                @foreach($chapters as $idx => $chapter)
                                <div x-data="{
                                        open: false,
                                        rawContent: `{{ base64_encode($chapter['content'] ?? '') }}`,
                                        decodedContent: '',
                                        init() {
                                            const decodeB64 = (s) => { try { return new TextDecoder().decode(Uint8Array.from(atob(s), c => c.charCodeAt(0))); } catch(_) { try { return decodeURIComponent(escape(atob(s))); } catch(e) { return atob(s); } } };
                                            this.decodedContent = decodeB64(this.rawContent);
                                            this.updateContent();
                                        },
                                        updateContent() {
                                            if (!this.$refs.mdContainer) return;
                                            let html = typeof marked !== 'undefined' ? marked.parse(this.decodedContent, {breaks: true, gfm: true}) : this.decodedContent;
                                            this.$refs.mdContainer.innerHTML = html;
                                        }
                                     }"
                                     class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 space-y-4 shadow-sm transition-all duration-300">
                                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800/50 cursor-pointer group" @click="open = !open">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="w-7 h-7 rounded-xl bg-violet-50 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300 font-black text-xs flex items-center justify-center border border-violet-100 dark:border-violet-800/50 shrink-0 group-hover:scale-110 transition-transform">
                                                {{ $idx + 1 }}
                                            </span>
                                            <div class="min-w-0">
                                                <h4 class="text-xs font-bold text-gray-900 dark:text-white truncate group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors">{{ $chapter['title'] ?? 'Capítulo sin título' }}</h4>
                                                <p class="text-[10px] text-gray-400">Por {{ $chapter['author_name'] ?? 'Autor' }} • {{ isset($chapter['updated_at']) ? \Carbon\Carbon::parse($chapter['updated_at'])->diffForHumans() : '' }}</p>
                                            </div>
                                        </div>
                                        <div class="text-gray-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div x-show="open" x-collapse style="display: none;">
                                        <div x-ref="mdContainer" class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 resize-y min-h-[120px] p-4 bg-gray-50 dark:bg-gray-800/30 border border-gray-100 dark:border-gray-800/80 rounded-2xl mt-2 markdown-body overflow-x-hidden"></div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($activeDocument->attachments->isNotEmpty())
                        <div class="mt-8 pt-8 border-t border-gray-100 dark:border-gray-800">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4">{{ __('Archivos Adjuntos') }} (OnlyOffice)</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($activeDocument->attachments as $attachment)
                                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 flex items-center gap-3 bg-gray-50 dark:bg-gray-800/50">
                                        <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-bold text-gray-900 dark:text-white truncate" title="{{ $attachment->file_name }}">{{ $attachment->file_name }}</p>
                                            <p class="text-[10px] text-gray-500">{{ number_format($attachment->file_size / 1024, 2) }} KB</p>
                                        </div>
                                        <a href="{{ route('onlyoffice.activity.edit', $attachment) }}" target="_blank" class="p-2 text-violet-600 dark:text-violet-400 hover:bg-violet-100 dark:hover:bg-violet-900/40 rounded-lg transition-colors" title="Abrir en OnlyOffice">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm h-[calc(100vh-16rem)] flex flex-col items-center justify-center p-8 text-center">
                <div class="w-16 h-16 bg-gray-50 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1">{{ __('Selecciona un documento') }}</h3>
                <p class="text-xs text-gray-400 max-w-sm">{{ __('Elige un documento del índice lateral para visualizar sus capítulos y notas.') }}</p>
            </div>
        @endif
    </div>
</div>

