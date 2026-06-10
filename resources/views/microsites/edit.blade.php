<x-app-layout>
    @section('title', 'Editar Micrositio')

    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('teams.microsites.index', $team) }}" class="p-2 text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 transition-colors shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white heading">Editar: {{ $microsite->title }}</h1>
                    <div class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
                        <span>{{ $team->name }}</span>
                        <span class="text-gray-300 dark:text-gray-600">&bull;</span>
                        <a href="{{ route('public.microsites.show', $microsite->slug) }}" target="_blank" class="hover:text-pink-600 dark:hover:text-pink-400 transition-colors font-mono text-xs bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-md">
                            /p/{{ $microsite->slug }}
                        </a>
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('public.microsites.show', $microsite->slug) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 text-sm font-bold rounded-xl transition-all shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Ver Página
                </a>
            </div>
        </div>
    </x-slot>

    <div class="pt-12 pb-32 px-4">
        <div class="max-w-5xl mx-auto">
            
            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
                    <div class="flex items-center gap-2 text-red-600 dark:text-red-400 font-bold mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        Revisa los siguientes errores:
                    </div>
                    <ul class="list-disc list-inside text-sm text-red-500 dark:text-red-400 space-y-1 ml-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="microsite-form" action="{{ route('teams.microsites.update', [$team, $microsite]) }}" method="POST" class="space-y-6">
                @csrf
                @method('PATCH')

                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 md:p-8 shadow-sm">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-pink-100 dark:bg-pink-900/30 text-pink-600 flex items-center justify-center">1</span>
                        Información Básica
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="title" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                                Título del Micrositio <span class="text-pink-500">*</span>
                            </label>
                            <input type="text" name="title" id="title" value="{{ old('title', $microsite->title) }}" required
                                class="w-full bg-gray-50/50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500 transition-colors">
                        </div>

                        <div>
                            <label for="slug" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                                URL Amigable (Slug) <span class="text-gray-400 font-normal text-xs ml-1">(Opcional, se genera solo si lo dejas en blanco)</span>
                            </label>
                            <div class="flex items-stretch shadow-sm rounded-xl overflow-hidden">
                                <span class="px-3 bg-gray-100 dark:bg-gray-800 border-y border-l border-gray-200 dark:border-gray-700 text-gray-500 flex items-center text-sm font-mono">
                                    /p/
                                </span>
                                <input type="text" name="slug" id="slug" value="{{ old('slug', $microsite->slug) }}"
                                    class="flex-1 bg-gray-50/50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 px-4 py-2.5 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500 transition-colors font-mono text-sm">
                            </div>
                        </div>

                        <div>
                            <label class="relative inline-flex items-center cursor-pointer mt-6">
                                <input type="hidden" name="is_published" value="0">
                                <input type="checkbox" name="is_published" value="1" class="sr-only peer" {{ old('is_published', $microsite->is_published) ? 'checked' : '' }}>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-emerald-500"></div>
                                <span class="ms-3 text-sm font-bold text-gray-700 dark:text-gray-300">Publicar Micrositio</span>
                            </label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 ml-[52px]">Si está desmarcado, el micrositio dejará de ser accesible para el público.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 md:p-8 shadow-sm">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-pink-100 dark:bg-pink-900/30 text-pink-600 flex items-center justify-center">2</span>
                        Geolocalización (Mapa)
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="city" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                                Población / Ciudad
                            </label>
                            <input type="text" name="city" id="city" value="{{ old('city', $microsite->city) }}"
                                class="w-full bg-gray-50/50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500 transition-colors">
                        </div>

                        <div>
                            <label for="latitude" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                                Latitud
                            </label>
                            <input type="text" name="latitude" id="latitude" value="{{ old('latitude', $microsite->latitude) }}" placeholder="Ej: 37.3891"
                                class="w-full bg-gray-50/50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500 transition-colors font-mono">
                        </div>

                        <div>
                            <label for="longitude" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                                Longitud
                            </label>
                            <input type="text" name="longitude" id="longitude" value="{{ old('longitude', $microsite->longitude) }}" placeholder="Ej: -5.9845"
                                class="w-full bg-gray-50/50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500 transition-colors font-mono">
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 md:p-8 shadow-sm">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-pink-100 dark:bg-pink-900/30 text-pink-600 flex items-center justify-center">3</span>
                        Contenido y Diseño
                    </h2>

                    <div class="space-y-6">
                        <div>
                            <label for="css_content" class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                                Estilos CSS Personalizados
                                <span class="px-2 py-0.5 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded text-[10px] uppercase font-black">CSS</span>
                            </label>
                            <textarea name="css_content" id="css_content" rows="8" placeholder="body { background-color: #f3f4f6; }&#10;.mi-clase { color: pink; }"
                                class="w-full bg-[#1e1e1e] border border-gray-700 rounded-xl px-4 py-3 text-gray-300 focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500 transition-colors font-mono text-sm shadow-inner resize-y">{{ old('css_content', $microsite->css_content) }}</textarea>
                            <p class="text-xs text-gray-500 mt-1">Se envolverá automáticamente en etiquetas &lt;style&gt;. No uses Javascript, será eliminado.</p>
                        </div>

                        <div>
                            <label for="html_content" class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                                Contenido de la Página <span class="text-pink-500">*</span>
                                <span class="px-2 py-0.5 bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 rounded text-[10px] uppercase font-black">HTML</span>
                            </label>
                            <textarea name="html_content" id="html_content" rows="20" required placeholder="&lt;div class=&quot;max-w-2xl mx-auto p-4&quot;&gt;&#10;  &lt;h1 class=&quot;text-3xl font-bold&quot;&gt;¡Hola Mundo!&lt;/h1&gt;&#10;  &lt;p&gt;Este es mi nuevo micrositio.&lt;/p&gt;&#10;&lt;/div&gt;"
                                class="w-full bg-[#1e1e1e] border border-gray-700 rounded-xl px-4 py-3 text-gray-300 focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500 transition-colors font-mono text-sm shadow-inner resize-y">{{ old('html_content', $microsite->html_content) }}</textarea>
                            <p class="text-xs text-gray-500 mt-1">Puedes usar HTML estándar y las clases CSS de Tailwind que necesites. El código se renderizará tal cual lo escribas.</p>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <!-- Floating Action Bar (Pill) -->
    <div id="microsite-edit-floating-bar"
         x-data="floatingDraggable"
         @mousedown="startDrag"
         @touchstart.passive="startDrag"
         @window:mousemove="drag"
         @window:touchmove.passive="drag"
         @window:mouseup="stopDrag"
         @window:touchend="stopDrag"
         class="fixed bottom-6 left-1/2 -translate-x-1/2 translate-y-4 z-[800] flex items-center gap-2 px-4 py-2.5 bg-white/90 dark:bg-gray-800/95 backdrop-blur-xl border border-gray-100 dark:border-gray-600 rounded-2xl shadow-2xl dark:shadow-[0_0_15px_rgba(0,0,0,0.5)] opacity-0 pointer-events-none transition-all duration-300 whitespace-nowrap cursor-move"
         :class="isDragging ? 'scale-105 shadow-[0_20px_50px_rgba(0,0,0,0.2)] dark:shadow-[0_20px_50px_rgba(0,0,0,0.6)]' : ''">

        {{-- Volver --}}
        <a href="{{ route('teams.microsites.index', $team) }}"
           class="flex items-center gap-1.5 text-xs font-bold text-gray-500 dark:text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 hover:bg-pink-50 dark:hover:bg-pink-900/30 px-3 py-1.5 rounded-xl transition-all">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            <span>Volver</span>
        </a>

        <div class="w-px h-5 bg-gray-200 dark:bg-gray-700 shrink-0"></div>

        {{-- Título truncado --}}
        <span class="text-xs font-black text-gray-800 dark:text-gray-200 max-w-[200px] truncate">
            {{ $microsite->title }}
        </span>

        <div class="w-px h-5 bg-gray-200 dark:bg-gray-700 shrink-0"></div>

        {{-- Guardar --}}
        <button type="button"
                onclick="document.getElementById('microsite-form').submit()"
                class="flex items-center gap-1.5 text-xs font-bold text-white bg-pink-500 hover:bg-pink-600 px-3 py-1.5 rounded-xl transition-all shadow-sm">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            <span>Guardar</span>
        </button>
    </div>

    <script>
        (function() {
            const bar = document.getElementById('microsite-edit-floating-bar');

            const checkScroll = (e) => {
                const target = e.target === document ? document.documentElement : e.target;
                const scrollY = target.scrollTop || 0;
                const finalScroll = scrollY || window.scrollY || 0;
                
                if (finalScroll > 150) {
                    bar.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none');
                    bar.classList.add('opacity-100', 'translate-y-0', 'pointer-events-auto');
                } else {
                    bar.classList.add('opacity-0', 'translate-y-4', 'pointer-events-none');
                    bar.classList.remove('opacity-100', 'translate-y-0', 'pointer-events-auto');
                }
            };

            window.addEventListener('scroll', checkScroll, { passive: true, capture: true });
        })();
    </script>
</x-app-layout>
