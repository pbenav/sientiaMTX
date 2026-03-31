<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-violet-100 dark:bg-violet-500/20 rounded-lg text-violet-600 dark:text-violet-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18 18.247 18.477 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('Documentación') }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $menu[$slug] ?? $slug }}</p>
            </div>
        </div>
    </x-slot>

    <div x-data="{ mobileMenuOpen: false }" class="w-full">

        {{-- Mobile: Sidebar toggle button --}}
        <div class="md:hidden mb-4">
            <button @click="mobileMenuOpen = !mobileMenuOpen"
                class="flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm w-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <span>{{ __('Índice de Manuales') }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-auto transition-transform" :class="mobileMenuOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            {{-- Mobile menu dropdown --}}
            <div x-show="mobileMenuOpen" x-transition x-cloak
                class="mt-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg overflow-hidden">
                @foreach($menu as $itemSlug => $itemTitle)
                    <a href="{{ route('docs', $itemSlug) }}" @click="mobileMenuOpen = false"
                       class="flex items-center px-4 py-3 text-sm font-medium border-b border-gray-100 dark:border-gray-800 last:border-0 transition-colors {{ $slug === $itemSlug ? 'bg-violet-50 dark:bg-violet-500/10 text-violet-700 dark:text-violet-400' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                        @if($slug === $itemSlug)
                            <span class="w-2 h-2 rounded-full bg-violet-500 mr-3 shrink-0"></span>
                        @else
                            <span class="w-2 h-2 rounded-full bg-gray-300 dark:bg-gray-600 mr-3 shrink-0"></span>
                        @endif
                        {{ $itemTitle }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Main layout --}}
        <div class="flex gap-6 items-start">

            {{-- Desktop Sidebar --}}
            <aside class="hidden md:block w-52 lg:w-60 xl:w-64 shrink-0">
                <div class="sticky top-24 space-y-3">
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm">
                        <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-3 px-1">
                            {{ __('Manuales Disponibles') }}
                        </p>
                        <nav class="space-y-1">
                            @foreach($menu as $itemSlug => $itemTitle)
                                <a href="{{ route('docs', $itemSlug) }}"
                                   class="flex items-center gap-2.5 px-3 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 {{ $slug === $itemSlug ? 'bg-violet-600 text-white shadow-md shadow-violet-500/25' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-violet-600 dark:hover:text-violet-400' }}">
                                    <span>{{ $itemTitle }}</span>
                                    @if($slug === $itemSlug)
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 ml-auto shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                </a>
                            @endforeach
                        </nav>
                    </div>

                    <div class="p-4 bg-gradient-to-br from-violet-500 to-indigo-600 rounded-2xl text-white shadow-lg">
                        <p class="text-[10px] font-bold opacity-75 uppercase tracking-widest mb-1">{{ __('Idioma') }}</p>
                        <p class="text-sm font-semibold flex items-center gap-2">
                            <span class="text-base">{{ app()->getLocale() === 'es' ? '🇪🇸' : '🇬🇧' }}</span>
                            {{ app()->getLocale() === 'es' ? 'Español' : 'English' }}
                        </p>
                    </div>
                </div>
            </aside>

            {{-- Content Area --}}
            <div class="flex-1 min-w-0">
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
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
                        {!! $content !!}
                    </div>

                    {{-- Footer --}}
                    <div class="px-6 sm:px-10 py-5 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-700 flex flex-wrap gap-3 justify-between items-center">
                        <span class="text-xs text-gray-400 dark:text-gray-500">
                            © {{ date('Y') }} Sientia Documentation
                        </span>
                        <button onclick="window.print()"
                            class="text-xs font-medium text-violet-600 dark:text-violet-400 hover:underline flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            {{ __('Imprimir / PDF') }}
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
    <style>
        /* Fix table overflow on small screens */
        .prose table {
            display: block;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        /* Fix pre/code overflow */
        .prose pre {
            overflow-x: auto;
        }
        /* Print styles */
        @media print {
            aside, nav, footer, button { display: none !important; }
            .prose { max-width: 100% !important; }
        }
    </style>
    @endpush
</x-app-layout>
