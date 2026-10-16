<x-app-layout maxWidth="max-w-none">
    @section('title', __('Create Microsite'))

    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('teams.microsites.index', $team) }}" class="p-2 text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 transition-colors shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white heading">{{ __('New Microsite') }}</h1>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $team->name }}
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-6 pb-32">
        <div class="w-full space-y-6">

            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
                    <div class="flex items-center gap-2 text-red-600 dark:text-red-400 font-bold mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        {{ __('Please review the following errors:') }}
                    </div>
                    <ul class="list-disc list-inside text-sm text-red-500 dark:text-red-400 space-y-1 ml-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="microsite-form" action="{{ route('teams.microsites.store', $team) }}" method="POST" class="space-y-6">
                @csrf

                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 md:p-8 shadow-sm">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-pink-100 dark:bg-pink-900/30 text-pink-600 flex items-center justify-center">1</span>
                        {{ __('Basic Information') }}
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="title" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ __('Microsite Title') }} <span class="text-pink-500">*</span>
                            </label>
                            <input type="text" name="title" id="title" value="{{ old('title') }}" required
                                class="w-full bg-gray-50/50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500 transition-colors">
                        </div>

                        <div>
                            <label for="slug" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ __('Friendly URL (Slug)') }} <span class="text-gray-400 font-normal text-xs ml-1">({{ __('Optional, auto-generated if left blank') }})</span>
                            </label>
                            <div class="flex items-stretch shadow-sm rounded-xl overflow-hidden">
                                <span class="px-3 bg-gray-100 dark:bg-gray-800 border-y border-l border-gray-200 dark:border-gray-700 text-gray-500 flex items-center text-sm font-mono">
                                    /p/
                                </span>
                                <input type="text" name="slug" id="slug" value="{{ old('slug') }}"
                                    class="flex-1 bg-gray-50/50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 px-4 py-2.5 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500 transition-colors font-mono text-sm"
                                    placeholder="{{ __('my-awesome-page') }}">
                            </div>
                        </div>

                        <div>
                            <label class="relative inline-flex items-center cursor-pointer mt-6">
                                <input type="hidden" name="is_published" value="0">
                                <input type="checkbox" name="is_published" value="1" class="sr-only peer" {{ old('is_published', true) ? 'checked' : '' }}>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-emerald-500"></div>
                                <span class="ms-3 text-sm font-bold text-gray-700 dark:text-gray-300">{{ __('Publish Microsite') }}</span>
                            </label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 ml-[52px]">{{ __('If unchecked, nobody except you will be able to see it.') }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 md:p-8 shadow-sm">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-pink-100 dark:bg-pink-900/30 text-pink-600 flex items-center justify-center">2</span>
                        {{ __('Geolocation (Map)') }}
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="city" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ __('City / Town') }}
                            </label>
                            <input type="text" name="city" id="city" value="{{ old('city', auth()->user()->working_area_name) }}"
                                class="w-full bg-gray-50/50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500 transition-colors">
                        </div>

                        <div>
                            <label for="latitude" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ __('Latitude') }}
                            </label>
                            <input type="text" name="latitude" id="latitude" value="{{ old('latitude', auth()->user()->location_lat) }}" placeholder="{{ __('e.g., 37.3891') }}"
                                class="w-full bg-gray-50/50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500 transition-colors font-mono">
                        </div>

                        <div>
                            <label for="longitude" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ __('Longitude') }}
                            </label>
                            <input type="text" name="longitude" id="longitude" value="{{ old('longitude', auth()->user()->location_lng) }}" placeholder="{{ __('e.g., -5.9845') }}"
                                class="w-full bg-gray-50/50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500 transition-colors font-mono">
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 md:p-8 shadow-sm">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-pink-100 dark:bg-pink-900/30 text-pink-600 flex items-center justify-center">3</span>
                        {{ __('Content & Design') }}
                    </h2>

                    <div class="flex items-center gap-3 text-[11px] text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/40 px-3.5 py-2 rounded-xl border border-gray-100 dark:border-gray-800/60 mb-6 overflow-x-auto">
                        <span class="font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-[10px]">{{ __('Atajos de edición') }}:</span>
                        <span><kbd class="px-1.5 py-0.5 rounded bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 font-mono text-[10px] text-gray-700 dark:text-gray-300">Ctrl+F</kbd> {{ __('Buscar') }}</span>
                        <span><kbd class="px-1.5 py-0.5 rounded bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 font-mono text-[10px] text-gray-700 dark:text-gray-300">Ctrl+H</kbd> {{ __('Reemplazar') }}</span>
                        <span><kbd class="px-1.5 py-0.5 rounded bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 font-mono text-[10px] text-gray-700 dark:text-gray-300">Ctrl+/</kbd> {{ __('Comentar') }}</span>
                        <span><kbd class="px-1.5 py-0.5 rounded bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 font-mono text-[10px] text-gray-700 dark:text-gray-300">Ctrl+Espacio</kbd> {{ __('Autocompletar') }}</span>
                        <span><kbd class="px-1.5 py-0.5 rounded bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 font-mono text-[10px] text-gray-700 dark:text-gray-300">F11</kbd> {{ __('Pantalla completa') }}</span>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <div class="flex items-center justify-between mb-1.5 flex-wrap gap-2">
                                <label for="css_content" class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300">
                                    {{ __('Custom CSS Styles') }}
                                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded text-[10px] uppercase font-black">CSS</span>
                                </label>
                                <div class="flex items-center gap-1.5">
                                    <button type="button"
                                            onclick="formatCodeMirrorEditor('css')"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:text-pink-600 dark:hover:text-pink-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors cursor-pointer"
                                            title="{{ __('Auto-indentar código CSS') }}">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h16"/></svg>
                                        <span>{{ __('Auto-indentar') }}</span>
                                    </button>
                                    <button type="button"
                                            onclick="toggleCodeMirrorFullscreen('css')"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:text-pink-600 dark:hover:text-pink-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors cursor-pointer"
                                            title="{{ __('Pantalla completa (F11)') }}">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                                        <span>{{ __('Pantalla completa') }}</span>
                                    </button>
                                </div>
                            </div>
                            <textarea name="css_content" id="css_content" rows="6" placeholder="body { background-color: #f3f4f6; }&#10;.mi-clase { color: pink; }"
                                class="w-full bg-[#1e1e1e] border border-gray-700 rounded-xl px-4 py-3 text-gray-300 focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500 transition-colors font-mono text-sm shadow-inner resize-y">{{ old('css_content') }}</textarea>
                            <p class="text-xs text-gray-500 mt-1">{{ __('It will be automatically wrapped in <style> tags. Do not use Javascript, it will be removed.') }}</p>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5 flex-wrap gap-2">
                                <label for="html_content" class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300">
                                    {{ __('Page Content') }} <span class="text-pink-500">*</span>
                                    <span class="px-2 py-0.5 bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 rounded text-[10px] uppercase font-black">HTML</span>
                                </label>
                                <div class="flex items-center gap-1.5">
                                    <button type="button"
                                            onclick="formatCodeMirrorEditor('html')"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:text-pink-600 dark:hover:text-pink-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors cursor-pointer"
                                            title="{{ __('Auto-indentar código HTML') }}">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h16"/></svg>
                                        <span>{{ __('Auto-indentar') }}</span>
                                    </button>
                                    <button type="button"
                                            onclick="toggleCodeMirrorFullscreen('html')"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:text-pink-600 dark:hover:text-pink-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors cursor-pointer"
                                            title="{{ __('Pantalla completa (F11)') }}">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                                        <span>{{ __('Pantalla completa') }}</span>
                                    </button>
                                </div>
                            </div>
                            <textarea name="html_content" id="html_content" rows="15" required placeholder="&lt;div class=&quot;max-w-2xl mx-auto p-4&quot;&gt;&#10;  &lt;h1 class=&quot;text-3xl font-bold&quot;&gt;¡Hola Mundo!&lt;/h1&gt;&#10;  &lt;p&gt;Este es mi nuevo micrositio.&lt;/p&gt;&#10;&lt;/div&gt;"
                                class="w-full bg-[#1e1e1e] border border-gray-700 rounded-xl px-4 py-3 text-gray-300 focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500 transition-colors font-mono text-sm shadow-inner resize-y">{{ old('html_content') }}</textarea>
                            <p class="text-xs text-gray-500 mt-1">{{ __('You can use standard HTML and Tailwind CSS classes as needed. Code will be rendered exactly as written.') }}</p>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <!-- Floating Action Bar (Pill) -->
    <div id="microsite-create-floating-bar"
         x-data="floatingDraggable"
         @mousedown="startDrag"
         @touchstart.passive="startDrag"
         @mousemove.window="drag"
         @touchmove.window.passive="drag"
         @mouseup.window="stopDrag"
         @touchend.window="stopDrag"
         class="fixed bottom-6 left-1/2 -translate-x-1/2 translate-y-4 z-[800] flex items-center gap-2 px-4 py-2.5 bg-white/90 dark:bg-gray-800/95 backdrop-blur-xl border border-gray-100 dark:border-gray-600 rounded-2xl shadow-2xl dark:shadow-[0_0_15px_rgba(0,0,0,0.5)] opacity-0 pointer-events-none transition-all duration-300 whitespace-nowrap cursor-move"
         :class="isDragging ? 'scale-105 shadow-[0_20px_50px_rgba(0,0,0,0.2)] dark:shadow-[0_20px_50px_rgba(0,0,0,0.6)]' : ''">

        {{-- Volver --}}
        <a href="{{ route('teams.microsites.index', $team) }}"
           class="flex items-center gap-1.5 text-xs font-bold text-gray-500 dark:text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 hover:bg-pink-50 dark:hover:bg-pink-900/30 px-3 py-1.5 rounded-xl transition-all">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            <span>{{ __('Back') }}</span>
        </a>

        <div class="w-px h-5 bg-gray-200 dark:bg-gray-700 shrink-0"></div>

        {{-- Texto identificador --}}
        <span class="text-xs font-black text-gray-800 dark:text-gray-200 max-w-[200px] truncate">
            {{ __("Create Microsite") }}
        </span>

        <div class="w-px h-5 bg-gray-200 dark:bg-gray-700 shrink-0"></div>

        {{-- Guardar --}}
        <button type="button"
                onclick="window.submitMicrositeForm ? window.submitMicrositeForm() : document.getElementById('microsite-form').submit()"
                class="flex items-center gap-1.5 text-xs font-bold text-white bg-pink-500 hover:bg-pink-600 px-3 py-1.5 rounded-xl transition-all shadow-sm">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            <span>{{ __('Save') }}</span>
        </button>
    </div>

    @include('microsites.partials.code-editor-assets')
</x-app-layout>
