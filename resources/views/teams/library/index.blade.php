<x-app-layout>
    @section('title', 'Biblioteca de Documentos — ' . $team->name)

    <x-slot name="header">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2 min-w-0">
                <a href="{{ route('teams.dashboard', $team) }}"
                    class="p-1.5 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-lg transition-all shrink-0"
                    title="Volver al escritorio">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                @include('teams.partials.breadcrumb')
                <span class="text-gray-300 dark:text-gray-700 mx-1">/</span>
                <h1 class="text-base font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span class="truncate">Biblioteca</span>
                </h1>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @include('teams.partials.header-toolbar')
            </div>
        </div>

        @include('teams.partials.team-view-nav', ['showCreateActions' => false])
    </x-slot>

    <div class="flex flex-col md:flex-row gap-6">
        <!-- Sidebar de Wiki -->
        <div class="w-full md:w-1/4 xl:w-1/5 flex flex-col gap-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden flex flex-col h-[calc(100vh-14rem)] sticky top-24">
                <div class="p-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/20">
                    <h3 class="text-xs font-black uppercase tracking-widest text-gray-900 dark:text-white flex items-center gap-2 mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                        Índice de Documentos
                    </h3>
                    <form action="{{ route('teams.library', $team) }}" method="GET" class="relative">
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar en la wiki..." 
                               class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs py-2 pl-8 pr-3 focus:ring-2 focus:ring-violet-500/50 outline-none transition-all placeholder-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 absolute left-2.5 top-2.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        @if(request('doc'))
                            <input type="hidden" name="doc" value="{{ request('doc') }}">
                        @endif
                    </form>
                </div>
                
                <div class="flex-1 overflow-y-auto no-scrollbar p-2">
                    @forelse($documents as $doc)
                        <a href="{{ route('teams.library', [$team, 'doc' => $doc->id]) }}" 
                           class="flex items-start gap-2.5 p-2.5 rounded-xl transition-all mb-1 {{ $activeDocument && $activeDocument->id == $doc->id ? 'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-300' : 'hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-400' }}">
                           <div class="mt-0.5 shrink-0">
                               <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 {{ $doc->status_value == 'completed' ? 'text-emerald-500' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                               </svg>
                           </div>
                           <div class="flex flex-col min-w-0">
                               <span class="text-sm font-semibold truncate">{{ $doc->title }}</span>
                               <span class="text-[10px] uppercase font-bold {{ $doc->status_value == 'completed' ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">{{ __("activities.statuses.{$doc->status_value}") }}</span>
                           </div>
                        </a>
                    @empty
                        <div class="text-center p-6">
                            <span class="text-xs text-gray-400">No hay documentos en la biblioteca.</span>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Contenido principal -->
        <div class="flex-1">
            @if($activeDocument)
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden min-h-[calc(100vh-14rem)] flex flex-col">
                    <div class="p-6 border-b border-gray-100 dark:border-gray-800 flex justify-between items-start gap-4">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-2 py-1 text-[10px] font-bold rounded bg-violet-50 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300 uppercase tracking-wider border border-violet-200 dark:border-violet-800">DOCUMENTO WIKI</span>
                                <span class="px-2 py-1 text-[10px] font-bold rounded {{ $activeDocument->status_value == 'completed' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-300 dark:border-emerald-800' : 'bg-gray-50 text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-400' }} uppercase tracking-wider border">
                                    {{ __("activities.statuses.{$activeDocument->status_value}") }}
                                </span>
                            </div>
                            <h2 class="text-2xl font-black text-gray-900 dark:text-white">{{ $activeDocument->title }}</h2>
                            @if($activeDocument->description)
                                <div x-data="{ content: `{{ base64_encode($activeDocument->description) }}` }"
                                     x-init="$nextTick(() => { 
                                        const decoded = decodeURIComponent(escape(window.atob(content)));
                                        $refs.descContainer.innerHTML = typeof marked !== 'undefined' ? marked.parse(decoded, {breaks: true, gfm: true}) : decoded; 
                                     })">
                                    <div x-ref="descContainer" class="prose prose-sm dark:prose-invert max-w-none text-gray-500 dark:text-gray-400 mt-2 markdown-body">
                                        <div class="flex items-center p-2">
                                            <svg class="animate-spin h-4 w-4 text-violet-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <a href="{{ route('teams.activities.show', [$team, $activeDocument]) }}" class="shrink-0 px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-xs font-bold uppercase rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                            Ver Actividad
                        </a>
                    </div>
                    
                    <div class="p-6 flex-1 flex flex-col">
                        @php
                            $notesStr = is_string($activeDocument->notes) ? trim($activeDocument->notes) : '';
                            $hasNotes = $notesStr !== '' && $notesStr !== '[]' && strip_tags($notesStr) !== '';
                        @endphp
                        @if($hasNotes)
                            <div class="prose dark:prose-invert max-w-none text-sm w-full mb-6">
                                {!! str($activeDocument->notes)->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                            </div>
                        @endif

                        @php
                            $chapters = $activeDocument->metadata['chapters'] ?? [];
                        @endphp
                        
                        @if(count($chapters) > 0)
                            <div id="chapters-section" class="bg-gray-50/50 dark:bg-gray-800/30 border border-gray-100 dark:border-gray-800 rounded-3xl p-6 shadow-sm transition-colors space-y-6">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-2xl bg-violet-100 dark:bg-violet-900/40 text-violet-600 dark:text-violet-400 flex items-center justify-center shrink-0 border border-violet-200 dark:border-violet-800/50 shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-bold text-gray-800 dark:text-white">Libro: Estructura del Documento</h3>
                                            <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wide">{{ count($chapters) }} Capítulos</p>
                                        </div>
                                    </div>
                                    <button type="button" onclick="printDocumentBook()" class="flex items-center gap-1.5 text-xs bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 hover:text-violet-600 dark:hover:text-violet-400 px-3.5 py-2 rounded-xl border border-gray-200 dark:border-gray-700 font-bold transition-all shadow-sm active:scale-95">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                        </svg>
                                        Imprimir
                                    </button>
                                </div>
                                
                                <div class="space-y-4">
                                    @foreach($chapters as $idx => $chapter)
                                    <div x-data="{ open: false }" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 space-y-4 shadow-sm">
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
                                            <div class="flex items-center gap-2 shrink-0">
                                                <div class="text-gray-400 transition-transform duration-200" :class="open ? 'rotate-180' : ''">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>
                                        <div x-show="open" x-collapse style="display: none;" 
                                             x-data="{ content: `{{ base64_encode($chapter['content'] ?? '') }}` }"
                                             x-init="$nextTick(() => { 
                                                const decoded = decodeURIComponent(escape(window.atob(content)));
                                                $refs.mdContainer.innerHTML = typeof marked !== 'undefined' ? marked.parse(decoded, {breaks: true, gfm: true}) : decoded; 
                                             })">
                                            <div x-ref="mdContainer" id="chapter-content-{{ $idx }}" class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 resize-y min-h-[120px] pr-4 p-4 bg-gray-50 dark:bg-gray-800/30 border border-gray-100 dark:border-gray-800/80 rounded-2xl mt-2 markdown-body">
                                                <div class="flex items-center justify-center p-4">
                                                    <svg class="animate-spin h-5 w-5 text-violet-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            @if(!$hasNotes)
                                <p class="text-gray-400 italic">No hay notas directas ni capítulos en la wiki para este documento. Revisa los archivos adjuntos.</p>
                            @endif
                        @endif
                        
                        @if($activeDocument->attachments->isNotEmpty())
                            <div class="mt-8 pt-8 border-t border-gray-100 dark:border-gray-800">
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4">Archivos Adjuntos (OnlyOffice)</h3>
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
                                            <a href="{{ route('onlyoffice.activity.edit', $attachment) }}" target="_blank" class="p-2 text-violet-600 dark:text-violet-400 hover:bg-violet-100 dark:hover:bg-violet-900/40 rounded-lg transition-colors">
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
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm h-[calc(100vh-14rem)] flex flex-col items-center justify-center p-8 text-center">
                    <div class="w-20 h-20 bg-gray-50 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-black text-gray-900 dark:text-white mb-2">Librería Vacía</h2>
                    <p class="text-sm text-gray-500 max-w-md">No tienes documentos registrados en este equipo. Crea actividades de tipo "Documento" y aparecerán organizadas aquí a modo de Wiki interna.</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

@if(isset($activeDocument))
<script>
function printDocumentBook() {
    const printWin = window.open('', '_blank');
    const title = @json($activeDocument->title);
    const teamName = @json($team->name);
    const docVersion = @json($activeDocument->metadata['version'] ?? '1.0.0');
    const chapters = @json($activeDocument->metadata['chapters'] ?? []);
    
    let chaptersHtml = '';
    let tocHtml = '';
    
    chapters.forEach((chap, idx) => {
        let rawContent = chap.content || '';
        let parsedHtml = typeof marked !== 'undefined' ? marked.parse(rawContent, {breaks: true, gfm: true}) : rawContent;
        
        tocHtml += `
            <div class="toc-item">
                <span class="toc-title">${idx + 1}. ${chap.title}</span>
                <span class="toc-dots"></span>
                <span class="toc-page">Capítulo ${idx + 1}</span>
            </div>
        `;
        
        chaptersHtml += `
            <div class="chapter-page">
                <div class="chapter-header">
                    <span class="chapter-num">CAPÍTULO ${idx + 1}</span>
                    <h2 class="chapter-title">${chap.title}</h2>
                    <div class="chapter-meta">Por ${chap.author_name || 'Autor'} • ${chap.updated_at || ''}</div>
                </div>
                <div class="chapter-body markdown-body">${parsedHtml}</div>
            </div>
        `;
    });

    printWin.document.write(`
        <!DOCTYPE html>
        <html>
            <head>
                <title>${title} - Libro Digital</title>
                <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&family=Merriweather:wght@300;400;700&display=swap" rel="stylesheet">
                <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"><\/script>
                <style>
                    @page { size: A4; margin: 2.5cm 2cm; }
                    body { font-family: 'Merriweather', serif; color: #1e293b; line-height: 1.8; margin: 0; padding: 0; font-size: 14px; }
                    h1, h2, h3, h4, h5, h6, .outfit { font-family: 'Outfit', sans-serif; }
                    
                    /* Portada */
                    .cover-page { height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; page-break-after: always; padding: 2rem; box-sizing: border-box; }
                    .cover-team { font-size: 16px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 4px; margin-bottom: 2rem; font-family: 'Outfit', sans-serif; }
                    .cover-title { font-size: 42px; font-weight: 900; color: #0f172a; line-height: 1.2; margin-bottom: 2rem; font-family: 'Outfit', sans-serif; }
                    .cover-badge { display: inline-block; background: #f1f5f9; color: #475569; padding: 8px 24px; border-radius: 50px; font-size: 14px; font-weight: 700; margin-bottom: 4rem; font-family: 'Outfit', sans-serif; border: 1px solid #e2e8f0; }
                    .cover-footer { margin-top: auto; font-size: 14px; color: #64748b; font-family: 'Outfit', sans-serif; }
                    
                    /* Índice */
                    .toc-page { page-break-after: always; padding: 2rem 0; }
                    .toc-main-title { font-size: 28px; font-weight: 800; color: #0f172a; margin-bottom: 3rem; font-family: 'Outfit', sans-serif; border-bottom: 2px solid #e2e8f0; padding-bottom: 1rem; }
                    .toc-item { display: flex; align-items: baseline; margin-bottom: 1.5rem; font-family: 'Outfit', sans-serif; font-size: 16px; }
                    .toc-title { font-weight: 600; color: #334155; }
                    .toc-dots { flex: 1; border-bottom: 1px dotted #cbd5e1; margin: 0 12px; }
                    .toc-page { font-weight: 700; color: #64748b; font-size: 14px; }
                    
                    /* Capítulos */
                    .chapter-page { page-break-before: always; padding: 2rem 0; }
                    .chapter-header { margin-bottom: 3rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 2rem; }
                    .chapter-num { font-size: 14px; font-weight: 800; color: #8b5cf6; text-transform: uppercase; letter-spacing: 3px; font-family: 'Outfit', sans-serif; display: block; margin-bottom: 0.5rem; }
                    .chapter-title { font-size: 32px; font-weight: 800; color: #0f172a; margin: 0 0 1rem 0; font-family: 'Outfit', sans-serif; line-height: 1.2; }
                    .chapter-meta { font-size: 13px; color: #64748b; font-family: 'Outfit', sans-serif; }
                    .chapter-body { color: #334155; }
                    .chapter-body p { margin-bottom: 1.5rem; }
                    .chapter-body h1, .chapter-body h2, .chapter-body h3 { font-family: 'Outfit', sans-serif; color: #0f172a; margin-top: 2.5rem; margin-bottom: 1rem; font-weight: 700; }
                </style>
            </head>
            <body>
                <div class="cover-page">
                    <div class="cover-team">${teamName}</div>
                    <h1 class="cover-title">${title}</h1>
                    <div class="cover-badge">DOCUMENTO VERSIÓN ${docVersion}</div>
                    <div class="cover-footer">Sientia MTX • Exportado el ${new Date().toLocaleDateString('es-ES')}</div>
                </div>
                
                <div class="toc-page">
                    <h2 class="toc-main-title">Índice General</h2>
                    ${tocHtml}
                </div>

                ${chaptersHtml}
                
                <script>
                    window.onload = () => {
                        setTimeout(() => window.print(), 500);
                    };
                <\/script>
            </body>
        </html>
    `);
    printWin.document.close();
}
</script>
@endif
