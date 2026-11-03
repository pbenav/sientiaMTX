<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 rounded-xl shadow-xs border border-violet-200 dark:border-violet-800/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18 18.247 18.477 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white heading">{{ __('Centro de Documentación') }}</h1>
                    <x-demo-hint>
                        Acceso estructurado a los manuales de usuario, gobernanza colegiada, integraciones y administración técnica de sientia.MTX.
                    </x-demo-hint>
                </div>
            </div>
            
            <div class="flex items-center gap-2.5 flex-wrap">
                {{-- Botón 1: Imprimir Documento Actual --}}
                <button type="button" onclick="choosePrintMode()" class="flex items-center gap-2 px-3.5 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xs hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-xs font-bold text-gray-700 dark:text-gray-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>{{ __('Imprimir Página') }}</span>
                </button>

                {{-- Botón 2: Compilar Libro / Manual Completo --}}
                <button type="button" @click="$dispatch('open-book-modal')" class="flex items-center gap-2 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-xl shadow-md shadow-violet-600/20 transition-all active:scale-95 text-xs font-black uppercase tracking-wider">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18 18.247 18.477 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span>{{ __('Compilar Libro / Manual') }}</span>
                </button>
            </div>
        </div>
    </x-slot>

    <div x-data="{ mobileMenuOpen: false, bookModalOpen: false }" @open-book-modal.window="bookModalOpen = true" class="w-full">

        {{-- Mobile: Toggle índice desplegable --}}
        <div class="mb-4" id="docs-mobile-toggle" style="display:none">
            <button @click="mobileMenuOpen = !mobileMenuOpen"
                class="flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 shadow-xs w-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <span>{{ __('Índice Temático de Manuales') }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-auto transition-transform" :class="mobileMenuOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            {{-- Mobile menu dropdown con categorías --}}
            <div x-show="mobileMenuOpen" x-transition x-cloak
                class="mt-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg overflow-hidden p-2 space-y-3">
                @foreach($categories as $catKey => $catData)
                    <div class="border-b border-gray-100 dark:border-gray-800 last:border-0 pb-2">
                        <div class="text-[10px] font-black uppercase tracking-wider text-violet-600 dark:text-violet-400 px-3 py-1">
                            {{ $catData['label'] }}
                        </div>
                        <div class="space-y-0.5">
                            @foreach($catData['items'] as $itemSlug => $itemTitle)
                                <a href="{{ route('docs', $itemSlug) }}" @click="mobileMenuOpen = false"
                                   class="flex items-center px-3 py-2 text-xs font-semibold rounded-lg transition-colors {{ $slug === $itemSlug ? 'bg-violet-50 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300 font-bold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                                    @if($slug === $itemSlug)
                                        <span class="w-1.5 h-1.5 rounded-full bg-violet-600 mr-2 shrink-0"></span>
                                    @endif
                                    <span>{{ $itemTitle }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Main layout con sidebar y contenido --}}
        <div class="flex gap-6 items-start">

            {{-- Desktop Sidebar Reorganizado por Categorías --}}
            <aside id="docs-sidebar" style="width:260px;flex-shrink:0">
                <div class="sticky top-24 space-y-3">
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-3.5 shadow-xs">
                        <div class="flex items-center justify-between mb-3 px-1">
                            <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">
                                {{ __('Línea Editorial') }}
                            </p>
                            <span class="text-[9px] font-bold text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-900/30 px-1.5 py-0.2 rounded">v{{ config('app.version', '1.3.0') }}</span>
                        </div>

                        <nav class="space-y-4">
                            @foreach($categories as $catKey => $catData)
                                <div>
                                    <div class="text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-gray-500 px-2.5 py-1 mb-1 border-b border-gray-100 dark:border-gray-800 flex items-center gap-1.5">
                                        @if($catKey === 'users')
                                            <span>👤</span>
                                        @elseif($catKey === 'coordination')
                                            <span>🛡️</span>
                                        @elseif($catKey === 'integrations')
                                            <span>🔌</span>
                                        @else
                                            <span>⚙️</span>
                                        @endif
                                        <span>{{ $catData['label'] }}</span>
                                    </div>

                                    <div class="space-y-0.5">
                                        @foreach($catData['items'] as $itemSlug => $itemTitle)
                                            <a href="{{ route('docs', $itemSlug) }}"
                                               class="flex items-center gap-2 px-2.5 py-1.5 text-xs font-semibold rounded-lg transition-all {{ $slug === $itemSlug ? 'bg-violet-600 text-white shadow-xs font-bold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-violet-600 dark:hover:text-violet-400' }}">
                                                <span class="truncate">{{ $itemTitle }}</span>
                                                @if($slug === $itemSlug)
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-auto shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                                    </svg>
                                                @endif
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </nav>
                    </div>

                    {{-- Tarjeta Idioma --}}
                    <div class="p-3.5 bg-gradient-to-br from-violet-600 to-indigo-700 rounded-2xl text-white shadow-md">
                        <p class="text-[9px] font-bold opacity-75 uppercase tracking-widest mb-0.5">{{ __('Idioma Documental') }}</p>
                        <p class="text-xs font-bold flex items-center gap-1.5">
                            <span class="text-sm">{{ app()->getLocale() === 'es' ? '🇪🇸' : '🇬🇧' }}</span>
                            {{ app()->getLocale() === 'es' ? 'Español (Oficial)' : 'English' }}
                        </p>
                    </div>
                </div>
            </aside>

            {{-- Content Area --}}
            <div class="flex-1 min-w-0">
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl shadow-xs overflow-hidden">
                    <div class="px-6 py-8 sm:px-10 sm:py-10 prose prose-slate dark:prose-invert max-w-none
                        prose-headings:font-bold
                        prose-h1:text-2xl prose-h1:sm:text-3xl prose-h1:pb-4 prose-h1:border-b prose-h1:border-gray-200 dark:prose-h1:border-gray-700
                        prose-h2:text-xl prose-h2:sm:text-2xl prose-h2:text-violet-600 dark:prose-h2:text-violet-400 prose-h2:mt-10
                        prose-h3:text-base prose-h3:sm:text-lg
                        prose-code:text-pink-600 dark:prose-code:text-pink-400 prose-code:font-semibold prose-code:bg-pink-50 dark:prose-code:bg-pink-900/20 prose-code:rounded prose-code:px-1.5 prose-code:py-0.5 prose-code:text-sm prose-code:before:content-none prose-code:after:content-none
                        prose-pre:bg-gray-950 prose-pre:rounded-xl prose-pre:border prose-pre:border-gray-800
                        prose-pre:overflow-x-auto
                        prose-table:w-full prose-table:text-sm
                        prose-th:bg-gray-50 dark:prose-th:bg-gray-800 prose-th:font-semibold
                        prose-td:align-top
                        prose-blockquote:border-l-violet-500 prose-blockquote:bg-violet-50 dark:prose-blockquote:bg-violet-900/20 prose-blockquote:rounded-r-xl prose-blockquote:py-1 prose-blockquote:not-italic
                        prose-a:text-violet-600 dark:prose-a:text-violet-400
                        prose-img:rounded-xl">
                        <div id="docs-content-container">
                            {!! $content !!}
                        </div>
                    </div>

                    {{-- Footer de página --}}
                    <div class="px-6 sm:px-10 py-5 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-700 flex flex-wrap gap-3 justify-between items-center">
                        <span class="text-xs text-gray-400 dark:text-gray-500 font-medium">
                            © {{ date('Y') }} sientia.MTX Open Source Ecosystem &bull; Licencia GNU AGPL v3
                        </span>
                        <div class="flex items-center gap-3">
                            <button onclick="choosePrintMode()" class="text-xs font-semibold text-gray-600 dark:text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 flex items-center gap-1.5 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                {{ __('Imprimir Página') }}
                            </button>
                            <button @click="$dispatch('open-book-modal')" class="text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18 18.247 18.477 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                                {{ __('Compilar Libro') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- ── Modal Interactivo: Compilar Libro / Manual Completo ── --}}
        <div x-show="bookModalOpen" x-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="bookModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" @click="bookModalOpen = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="bookModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block align-bottom bg-white dark:bg-gray-900 rounded-[2rem] text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full border border-gray-200 dark:border-gray-800">
                    
                    <form action="{{ route('docs.book.print') }}" method="POST" target="_blank">
                        @csrf
                        <div class="p-6 sm:p-8">
                            <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800 mb-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-2xl bg-violet-100 dark:bg-violet-900/40 text-violet-600 dark:text-violet-400 flex items-center justify-center">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18 18.247 18.477 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-black text-gray-900 dark:text-white" id="modal-title">
                                            Compilar Manual Oficial en Formato Libro
                                        </h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Genera un compendio encuadernable con portada oficial, índice paginado y capítulos seleccionados.
                                        </p>
                                    </div>
                                </div>
                                <button type="button" @click="bookModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            <!-- Opciones y Título del Libro -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Título del Manual / Libro:</label>
                                    <input type="text" name="title" value="Manual Oficial SientiaMTX" required
                                        class="w-full text-xs font-bold rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Versión / Edición:</label>
                                    <input type="text" name="version" value="v{{ config('app.version', '1.3.0') }}" required
                                        class="w-full text-xs font-bold rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-gray-900 dark:text-white">
                                </div>
                            </div>

                            <!-- Opciones estructurales -->
                            <div class="flex items-center gap-6 mb-6 p-3 bg-gray-50 dark:bg-gray-800/60 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-300">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="cover" value="1" checked class="rounded text-violet-600 focus:ring-violet-500">
                                    <span>Incluir Portada Institucional</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="toc" value="1" checked class="rounded text-violet-600 focus:ring-violet-500">
                                    <span>Generar Índice de Contenidos (TOC)</span>
                                </label>
                            </div>

                            <!-- Botones de selección rápida -->
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <span class="text-xs font-black uppercase tracking-wider text-gray-500 dark:text-gray-400">Capítulos a Incluir:</span>
                                <div class="flex items-center gap-2 text-[11px] font-bold">
                                    <button type="button" onclick="setBookSelection('all')" class="text-violet-600 hover:underline">Todos</button>
                                    <span>&bull;</span>
                                    <button type="button" onclick="setBookSelection('users')" class="text-violet-600 hover:underline">Manual Usuario</button>
                                    <span>&bull;</span>
                                    <button type="button" onclick="setBookSelection('admin')" class="text-violet-600 hover:underline">Manual Técnico</button>
                                    <span>&bull;</span>
                                    <button type="button" onclick="setBookSelection('none')" class="text-gray-500 hover:underline">Ninguno</button>
                                </div>
                            </div>

                            <!-- Lista de Documentos por Categorías -->
                            <div class="max-h-72 overflow-y-auto space-y-4 pr-1 border border-gray-100 dark:border-gray-800 rounded-2xl p-4 bg-gray-50/50 dark:bg-gray-900/50">
                                @foreach($categories as $catKey => $catData)
                                    <div>
                                        <div class="text-[10px] font-black uppercase tracking-wider text-violet-700 dark:text-violet-400 mb-2 flex items-center gap-1.5">
                                            <span>{{ $catData['label'] }}</span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            @foreach($catData['items'] as $itemSlug => $itemTitle)
                                                <label class="flex items-center gap-2.5 p-2 bg-white dark:bg-gray-800/80 rounded-xl border border-gray-200 dark:border-gray-700/60 hover:border-violet-400 cursor-pointer text-xs transition-colors">
                                                    <input type="checkbox" name="docs[]" value="{{ $itemSlug }}" checked data-cat="{{ $catKey }}"
                                                        class="book-doc-checkbox rounded text-violet-600 focus:ring-violet-500">
                                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ $itemTitle }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="px-6 sm:px-8 py-4 bg-gray-50 dark:bg-gray-800/60 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-3 items-center">
                            <button type="button" @click="bookModalOpen = false"
                                class="px-4 py-2 text-xs font-bold rounded-xl text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="px-5 py-2.5 bg-violet-600 hover:bg-violet-700 text-white font-black text-xs uppercase tracking-wider rounded-xl shadow-md shadow-violet-600/20 transition-all active:scale-95 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18 18.247 18.477 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                <span>Compilar e Imprimir Libro</span>
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>

    </div>

@push('scripts')
    <style>
        @media print {
            pre, code, pre *, code *, .prose pre, .prose code { background-color: transparent !important; color: #000 !important; }
            div[class*=bg-gray-8], div[class*=bg-gray-9], div[class*=bg-slate-8], div[class*=bg-slate-9], div[style*=background], .prose div, .markdown-body div { background-color: transparent !important; }
            pre, .prose pre { border: 1px solid #cbd5e1 !important; border-radius: 0.25rem !important; white-space: pre-wrap !important; word-break: break-all !important; padding: 0.5rem !important; }
        }
        @media (max-width: 767px) {
            #docs-sidebar { display: none !important; }
            #docs-mobile-toggle { display: block !important; }
        }
        .prose table { display: block; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .prose pre { overflow-x: auto; }
    </style>
    <script>
        function choosePrintMode() {
            const slug = @json($slug);
            const baseUrl = @json(route('docs.print', ['slug' => '__SLUG__']));
            const printUrl = baseUrl.replace('__SLUG__', slug);

            const isDark = document.documentElement.classList.contains('dark');
            Swal.fire({
                title: '<span class="text-xs font-black uppercase tracking-widest text-indigo-600">{{ __('docs.print_format') }}</span>',
                background: isDark ? '#0f172a' : '#ffffff',
                color: isDark ? '#f3f4f6' : '#1f2937',
                showConfirmButton: false,
                showCloseButton: true,
                customClass: {
                    popup: 'rounded-[2.5rem] shadow-2xl border border-gray-200 dark:border-gray-800 p-6',
                },
                html: `
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-6 text-center px-4">
                        {{ __("docs.watermark_question") }}
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-2">
                        <button type="button" id="swal-btn-header" class="flex flex-col items-center gap-3 p-5 rounded-[2rem] border-2 border-indigo-100 dark:border-indigo-950 bg-indigo-50/50 dark:bg-indigo-950/30 hover:border-indigo-600 transition-all text-center group">
                            <div class="w-12 h-12 rounded-2xl bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 group-hover:scale-110 transition-transform shadow-sm">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div class="font-black text-[10px] uppercase tracking-widest text-indigo-700 dark:text-indigo-300">Con Cabeceras</div>
                            <div class="text-[9px] font-bold text-gray-400 uppercase tracking-tight">Estilo oficial MTX</div>
                        </button>
                        <button type="button" id="swal-btn-noheader" class="flex flex-col items-center gap-3 p-5 rounded-[2rem] border-2 border-gray-100 dark:border-gray-800 bg-white dark:bg-slate-900 hover:border-gray-600 transition-all text-center group">
                            <div class="w-12 h-12 rounded-2xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-600 dark:text-gray-400 group-hover:scale-110 transition-transform shadow-sm">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"/></svg>
                            </div>
                            <div class="font-black text-[10px] uppercase tracking-widest text-gray-700 dark:text-gray-300">Sin Cabeceras</div>
                            <div class="text-[9px] font-bold text-gray-400 uppercase tracking-tight">{{ __('Contenido limpio') }}</div>
                        </button>
                    </div>
                `,
                didOpen: () => {
                    document.getElementById('swal-btn-header').addEventListener('click', () => {
                        Swal.close();
                        window.open(printUrl + '?header=1', '_blank');
                    });
                    document.getElementById('swal-btn-noheader').addEventListener('click', () => {
                        Swal.close();
                        window.open(printUrl + '?header=0', '_blank');
                    });
                }
            });
        }

        function setBookSelection(mode) {
            const checkboxes = document.querySelectorAll('.book-doc-checkbox');
            checkboxes.forEach(cb => {
                const cat = cb.getAttribute('data-cat');
                if (mode === 'all') {
                    cb.checked = true;
                } else if (mode === 'none') {
                    cb.checked = false;
                } else if (mode === 'users') {
                    cb.checked = (cat === 'users');
                } else if (mode === 'admin') {
                    cb.checked = (cat === 'coordination' || cat === 'sysadmin' || cat === 'integrations');
                }
            });
        }
    </script>
@endpush
</x-app-layout>
