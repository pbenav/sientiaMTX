<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-violet-100 dark:bg-violet-500/20 rounded-lg text-violet-600 dark:text-violet-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18 18.247 18.477 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white leading-tight">
                {{ __('Documentación') }} — {{ $menu[$slug] ?? $slug }}
            </h2>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Sidebar Navigation -->
            <div class="lg:col-span-1">
                <div class="sticky top-24 space-y-2">
                    <div class="bg-white/50 dark:bg-gray-900/50 backdrop-blur-xl border border-gray-200 dark:border-gray-800 p-4 rounded-2xl shadow-sm">
                        <h3 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-4 px-2">
                            {{ __('Manuales Disponibles') }}
                        </h3>
                        <nav class="space-y-1">
                            @foreach($menu as $itemSlug => $itemTitle)
                                <a href="{{ route('docs', $itemSlug) }}" 
                                   class="flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-300 {{ $slug === $itemSlug ? 'bg-violet-600 text-white shadow-lg shadow-violet-500/30' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-violet-600 dark:hover:text-violet-400' }}">
                                    <span class="truncate">{{ $itemTitle }}</span>
                                    @if($slug === $itemSlug)
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-auto" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                </a>
                            @endforeach
                        </nav>
                    </div>

                    <!-- Language Info Card -->
                    <div class="p-4 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-2xl text-white shadow-xl">
                        <p class="text-xs font-bold opacity-80 uppercase tracking-widest mb-1">{{ __('Idioma Actual') }}</p>
                        <p class="text-sm font-medium flex items-center gap-2">
                            <span class="text-lg">{{ app()->getLocale() === 'es' ? '🇪🇸' : '🇬🇧' }}</span>
                            {{ app()->getLocale() === 'es' ? 'Español' : 'English' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="lg:col-span-3">
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl shadow-sm overflow-hidden prose prose-slate dark:prose-invert max-w-none">
                    <div class="p-8 lg:p-12">
                        {!! $content !!}
                    </div>

                    <!-- Footer Note -->
                    <div class="px-8 py-6 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-700 flex justify-between items-center">
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            © {{ date('Y') }} Sientia Documentation Engine
                        </span>
                        <div class="flex gap-4">
                            <button onclick="window.print()" class="text-xs text-violet-600 dark:text-violet-400 hover:underline font-medium">
                                {{ __('Descargar PDF') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Style for Markdown Content -->
    <style>
        .prose h1 { margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 2px solid #f1f5f9; }
        .dark .prose h1 { border-color: #1e293b; }
        .prose h2 { margin-top: 3rem; color: #7c3aed; }
        .dark .prose h2 { color: #a78bfa; }
        .prose pre { background-color: #0f172a; border-radius: 1rem; padding: 1.5rem; margin: 1.5rem 0; border: 1px solid #334155; }
        .prose code { color: #db2777; font-weight: 600; padding: 0.2rem 0.4rem; background: #fdf2f8; border-radius: 0.4rem; }
        .dark .prose code { color: #f472b6; background: #312e81; }
        .prose pre code { background: transparent; padding: 0; color: #e2e8f0; font-weight: 400; }
        .prose blockquote { border-left-color: #7c3aed; background: #f5f3ff; padding: 1.5rem; border-radius: 0 1rem 1rem 0; font-style: italic; }
        .dark .prose blockquote { background: #1e1b4b; border-left-color: #a78bfa; }
        .prose ul li::marker { color: #7c3aed; }
        .prose table { border-radius: 1rem; overflow: hidden; border: 1px solid #e2e8f0; }
        .dark .prose table { border-color: #334155; }
        .prose thead { background: #f8fafc; }
        .dark .prose thead { background: #1e293b; }
    </style>
</x-app-layout>
