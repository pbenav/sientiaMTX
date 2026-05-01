<x-app-layout>
    @section('title', 'Ajustes de Apariencia')

    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 rounded-xl shadow-sm border border-violet-200 dark:border-violet-800/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.828 2.828a2 2 0 010 2.828l-1.657 1.657M7 7.343l-1.657-1.657a2 2 0 010-2.828l2.828-2.828a2 2 0 012.828 0l1.657 1.657" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white heading">Ajustes de Apariencia</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Personaliza la estética visual de la plataforma y el renderizado de contenidos.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-12 px-4">
        <div class="max-w-7xl mx-auto">
            @include('settings.partials.tabs')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2">
                    <form action="{{ route('settings.appearance.update') }}" method="POST" class="space-y-6">
                        @csrf
                        
                        <!-- Markdown Typography Card -->
                        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl overflow-hidden shadow-sm">
                            <div class="p-6 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30 flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-black uppercase tracking-widest text-violet-600 dark:text-violet-400">Tipografía Markdown (Global)</h3>
                                    <p class="text-xs text-gray-500 mt-1">Configura cómo se renderizan los contenidos enriquecidos en todo el sistema.</p>
                                </div>
                                <div class="px-3 py-1 bg-violet-100 dark:bg-violet-900/30 rounded-lg">
                                    <span class="text-[10px] font-bold text-violet-600 dark:text-violet-400 uppercase tracking-tight">Preferencias Base</span>
                                </div>
                            </div>
                            
                            <div class="p-8 space-y-10">
                                <!-- Headings Grid -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <!-- H1 -->
                                    <div class="space-y-4 p-5 bg-gray-50 dark:bg-gray-800/20 rounded-2xl border border-gray-100 dark:border-gray-800">
                                        <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400">Título H1 (#)</label>
                                        <div class="space-y-3">
                                            <div>
                                                <label class="block text-[9px] font-bold text-gray-500 mb-1 uppercase">Tamaño</label>
                                                <input type="text" name="markdown_h1_size" value="{{ $markdown['h1_size'] }}" class="w-full bg-white dark:bg-gray-900 border-gray-100 dark:border-gray-800 rounded-xl text-sm focus:ring-violet-500/20 focus:border-violet-500">
                                            </div>
                                            <div>
                                                <label class="block text-[9px] font-bold text-gray-500 mb-1 uppercase">Peso</label>
                                                <input type="text" name="markdown_h1_weight" value="{{ $markdown['h1_weight'] }}" class="w-full bg-white dark:bg-gray-900 border-gray-100 dark:border-gray-800 rounded-xl text-sm focus:ring-violet-500/20 focus:border-violet-500">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- H2 -->
                                    <div class="space-y-4 p-5 bg-gray-50 dark:bg-gray-800/20 rounded-2xl border border-gray-100 dark:border-gray-800">
                                        <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400">Título H2 (##)</label>
                                        <div class="space-y-3">
                                            <div>
                                                <label class="block text-[9px] font-bold text-gray-500 mb-1 uppercase">Tamaño</label>
                                                <input type="text" name="markdown_h2_size" value="{{ $markdown['h2_size'] }}" class="w-full bg-white dark:bg-gray-900 border-gray-100 dark:border-gray-800 rounded-xl text-sm focus:ring-violet-500/20 focus:border-violet-500">
                                            </div>
                                            <div>
                                                <label class="block text-[9px] font-bold text-gray-500 mb-1 uppercase">Peso</label>
                                                <input type="text" name="markdown_h2_weight" value="{{ $markdown['h2_weight'] }}" class="w-full bg-white dark:bg-gray-900 border-gray-100 dark:border-gray-800 rounded-xl text-sm focus:ring-violet-500/20 focus:border-violet-500">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- H3 -->
                                    <div class="space-y-4 p-5 bg-gray-50 dark:bg-gray-800/20 rounded-2xl border border-gray-100 dark:border-gray-800">
                                        <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400">Título H3 (###)</label>
                                        <div class="space-y-3">
                                            <div>
                                                <label class="block text-[9px] font-bold text-gray-500 mb-1 uppercase">Tamaño</label>
                                                <input type="text" name="markdown_h3_size" value="{{ $markdown['h3_size'] }}" class="w-full bg-white dark:bg-gray-900 border-gray-100 dark:border-gray-800 rounded-xl text-sm focus:ring-violet-500/20 focus:border-violet-500">
                                            </div>
                                            <div>
                                                <label class="block text-[9px] font-bold text-gray-500 mb-1 uppercase">Peso</label>
                                                <input type="text" name="markdown_h3_weight" value="{{ $markdown['h3_weight'] }}" class="w-full bg-white dark:bg-gray-900 border-gray-100 dark:border-gray-800 rounded-xl text-sm focus:ring-violet-500/20 focus:border-violet-500">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Colors & More -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-4">
                                    <div class="space-y-6">
                                        <div class="p-5 bg-gray-50 dark:bg-gray-800/20 rounded-2xl border border-gray-100 dark:border-gray-800">
                                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-4">Colores de Acento</label>
                                            <div class="space-y-4">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Color Primario (Enlaces)</span>
                                                    <input type="color" name="markdown_accent_color" value="{{ $markdown['accent_color'] }}" class="h-8 w-8 rounded-lg overflow-hidden border-none cursor-pointer">
                                                </div>
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Color Viñetas</span>
                                                    <input type="color" name="markdown_bullet_color" value="{{ $markdown['bullet_color'] }}" class="h-8 w-8 rounded-lg overflow-hidden border-none cursor-pointer">
                                                </div>
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Borde Citas (Quotes)</span>
                                                    <input type="color" name="markdown_bq_color" value="{{ $markdown['bq_color'] }}" class="h-8 w-8 rounded-lg overflow-hidden border-none cursor-pointer">
                                                </div>
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400">Ancho Borde Citas</span>
                                                    <input type="text" name="markdown_bq_width" value="{{ $markdown['bq_width'] ?? '4px' }}" class="w-16 bg-white dark:bg-gray-900 border-gray-100 dark:border-gray-800 rounded-xl text-xs text-center focus:ring-violet-500/20 focus:border-violet-500">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-6">
                                        <div class="p-5 bg-gray-50 dark:bg-gray-800/20 rounded-2xl border border-gray-100 dark:border-gray-800">
                                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-4">Cuerpo de Texto</label>
                                            <div class="space-y-4">
                                                <div>
                                                    <label class="block text-[9px] font-bold text-gray-500 mb-1 uppercase">Tamaño Base</label>
                                                    <input type="text" name="markdown_text_size" value="{{ $markdown['text_size'] }}" class="w-full bg-white dark:bg-gray-900 border-gray-100 dark:border-gray-800 rounded-xl text-sm focus:ring-violet-500/20 focus:border-violet-500">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="p-6 bg-gray-50/50 dark:bg-gray-800/30 border-t border-gray-100 dark:border-gray-800 flex justify-end">
                                <button type="submit" class="px-8 py-3 bg-violet-600 hover:bg-violet-700 text-white text-xs font-black uppercase tracking-widest rounded-2xl transition-all shadow-lg shadow-violet-500/20 active:scale-95">
                                    Guardar Cambios Globales
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Preview Panel -->
                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm sticky top-8">
                        <h3 class="text-xs font-black uppercase tracking-widest text-gray-400 mb-6">Previsualización en tiempo real</h3>
                        
                        <div class="p-8 bg-gray-50/50 dark:bg-gray-950/20 rounded-2xl border border-dashed border-gray-200 dark:border-gray-800 overflow-hidden" id="markdown-preview">
                            <h1 style="font-size: {{ $markdown['h1_size'] }}; font-weight: {{ $markdown['h1_weight'] }}; color: {{ $markdown['accent_color'] }}; margin-top:0;" class="mb-4">Título H1</h1>
                            <h2 style="font-size: {{ $markdown['h2_size'] }}; font-weight: {{ $markdown['h2_weight'] }};" class="mb-3">Subtítulo H2</h2>
                            <h3 style="font-size: {{ $markdown['h3_size'] }}; font-weight: {{ $markdown['h3_weight'] }};" class="mb-2">Sección H3</h3>
                            <p style="font-size: {{ $markdown['text_size'] }};" class="text-gray-600 dark:text-gray-400 leading-relaxed mb-4">
                                Este es el cuerpo de texto. Incluye <a href="#" style="color: {{ $markdown['accent_color'] }}; text-decoration: underline;">enlaces</a> y otros elementos.
                            </p>
                            <ul class="mb-4 space-y-1">
                                <li class="flex items-center gap-2">
                                    <span style="color: {{ $markdown['bullet_color'] }}">•</span>
                                    <span class="text-xs text-gray-600 dark:text-gray-400">Elemento de lista 1</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span style="color: {{ $markdown['bullet_color'] }}">•</span>
                                    <span class="text-xs text-gray-600 dark:text-gray-400">Elemento de lista 2</span>
                                </li>
                            </ul>
                            <div style="border-left: 3px solid {{ $markdown['bq_color'] }};" class="pl-4 py-1 bg-gray-100 dark:bg-gray-800/40 rounded-r-lg italic text-[11px] text-gray-500">
                                "Esta es una cita de ejemplo para verificar el estilo del bloque."
                            </div>
                        </div>

                        <div class="mt-8 p-4 bg-indigo-50 dark:bg-indigo-900/10 rounded-2xl border border-indigo-100 dark:border-indigo-800 flex gap-3 items-start">
                            <svg class="h-5 w-5 text-indigo-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <p class="text-[10px] text-indigo-700 dark:text-indigo-400 font-medium leading-relaxed">Nota: Los estilos de equipo tienen prioridad sobre estos ajustes globales.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
