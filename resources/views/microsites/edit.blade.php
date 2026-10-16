<x-app-layout maxWidth="max-w-none">
    @section('title', __('Edit Microsite'))

    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('teams.microsites.index', $team) }}" class="p-2 text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 transition-colors shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white heading">{{ __('Edit') }}: {{ $microsite->title }}</h1>
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
                    {{ __('View Page') }}
                </a>
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

            <form id="microsite-form" action="{{ route('teams.microsites.update', [$team, $microsite]) }}" method="POST" class="space-y-6">
                @csrf
                @method('PATCH')

                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 md:p-8 shadow-sm">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-pink-100 dark:bg-pink-900/30 text-pink-600 flex items-center justify-center">1</span>
                        {{ __('Basic Information') }}
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div>
                            <label for="title" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ __('Microsite Title') }} <span class="text-pink-500">*</span>
                            </label>
                            <input type="text" name="title" id="title" value="{{ old('title', $microsite->title) }}" required
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
                                <input type="text" name="slug" id="slug" value="{{ old('slug', $microsite->slug) }}"
                                    class="flex-1 bg-gray-50/50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 px-4 py-2.5 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500 transition-colors font-mono text-sm">
                            </div>
                        </div>

                        <div>
                            <label class="relative inline-flex items-center cursor-pointer mt-6">
                                <input type="hidden" name="is_published" value="0">
                                <input type="checkbox" name="is_published" value="1" class="sr-only peer" {{ old('is_published', $microsite->is_published) ? 'checked' : '' }}>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-emerald-500"></div>
                                <span class="ms-3 text-sm font-bold text-gray-700 dark:text-gray-300">{{ __('Publish Microsite') }}</span>
                            </label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 ml-[52px]">{{ __('If unchecked, the microsite will no longer be accessible to the public.') }}</p>
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
                            <input type="text" name="city" id="city" value="{{ old('city', $microsite->city) }}"
                                class="w-full bg-gray-50/50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500 transition-colors">
                        </div>

                        <div>
                            <label for="latitude" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ __('Latitude') }}
                            </label>
                            <input type="text" name="latitude" id="latitude" value="{{ old('latitude', $microsite->latitude) }}" placeholder="{{ __('e.g., 37.3891') }}"
                                class="w-full bg-gray-50/50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500 transition-colors font-mono">
                        </div>

                        <div>
                            <label for="longitude" class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ __('Longitude') }}
                            </label>
                            <input type="text" name="longitude" id="longitude" value="{{ old('longitude', $microsite->longitude) }}" placeholder="{{ __('e.g., -5.9845') }}"
                                class="w-full bg-gray-50/50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500 transition-colors font-mono">
                        </div>
                    </div>
                </div>

    {{-- Datos y función del Gestor de Imágenes del Micrositio --}}
    <script>
        window.__micrositeInitialImages = @json($microsite->images);
        window.__micrositeImageRoutes = {
            store: '{{ route('teams.microsites.images.store', [$team, $microsite]) }}',
            updateUrl: '{{ route('teams.microsites.images.update', [$team, $microsite, 'IMG_ID']) }}',
            destroyUrl: '{{ route('teams.microsites.images.destroy', [$team, $microsite, 'IMG_ID']) }}'
        };
        window.__micrositeCsrfToken = '{{ csrf_token() }}';

        function micrositeImagesManager() {
            return {
                images: (window.__micrositeInitialImages || []).map(img => ({
                    ...img,
                    _editing: false,
                    _editTitle: img.title || '',
                    _justInserted: false,
                    _copied: false,
                })),
                isUploading: false,
                isDraggingOver: false,
                uploadTitle: '',
                notification: null,
                notificationTimeout: null,
                lastCursorPos: null,

                init() {
                    const textarea = document.getElementById('html_content');
                    if (textarea) {
                        const trackCursor = () => {
                            this.lastCursorPos = textarea.selectionStart;
                        };
                        textarea.addEventListener('keyup', trackCursor);
                        textarea.addEventListener('click', trackCursor);
                        textarea.addEventListener('select', trackCursor);
                        textarea.addEventListener('blur', trackCursor);
                    }
                },

                showNotification(message, type = 'success') {
                    if (this.notificationTimeout) clearTimeout(this.notificationTimeout);
                    this.notification = { message, type };
                    this.notificationTimeout = setTimeout(() => {
                        this.notification = null;
                    }, 3500);
                },

                triggerFileInput() {
                    const input = document.getElementById('microsite-image-file-input') || (this.$refs && this.$refs.fileInput);
                    if (input) input.click();
                },

                handleFileSelect(event) {
                    const files = event.target.files;
                    if (files && files.length > 0) {
                        this.uploadFile(files[0]);
                    }
                    event.target.value = '';
                },

                handleDrop(event) {
                    this.isDraggingOver = false;
                    const files = event.dataTransfer ? event.dataTransfer.files : null;
                    if (files && files.length > 0) {
                        const file = files[0];
                        if (!file.type || !file.type.startsWith('image/')) {
                            this.showNotification('{{ __('Por favor, selecciona un archivo de imagen válido.') }}', 'error');
                            return;
                        }
                        this.uploadFile(file);
                    }
                },

                async uploadFile(file) {
                    if (!file) return;

                    if (file.size > 10 * 1024 * 1024) {
                        this.showNotification('{{ __('La imagen supera el límite máximo permitido de 10 MB.') }}', 'error');
                        return;
                    }

                    const formData = new FormData();
                    formData.append('image', file);
                    if (this.uploadTitle && this.uploadTitle.trim()) {
                        formData.append('title', this.uploadTitle.trim());
                    }

                    this.isUploading = true;

                    try {
                        const csrfToken = window.__micrositeCsrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        const response = await fetch(window.__micrositeImageRoutes.store, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                            body: formData,
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            const errorMsg = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || '{{ __('Error al subir la imagen.') }}');
                            throw new Error(errorMsg);
                        }

                        const newImage = {
                            ...data.image,
                            _editing: false,
                            _editTitle: data.image.title || '',
                            _justInserted: false,
                            _copied: false,
                        };
                        this.images.unshift(newImage);
                        this.uploadTitle = '';
                        this.showNotification('{{ __('Imagen subida correctamente.') }}');
                    } catch (err) {
                        console.error('Error uploading image:', err);
                        this.showNotification(err.message || '{{ __('No se pudo subir la imagen.') }}', 'error');
                    } finally {
                        this.isUploading = false;
                    }
                },

                startEdit(image) {
                    image._editTitle = image.title || '';
                    image._editing = true;
                    this.$nextTick(() => {
                        const input = document.getElementById('img-title-input-' + image.id);
                        if (input) {
                            input.focus();
                            input.select();
                        }
                    });
                },

                cancelEdit(image) {
                    image._editing = false;
                    image._editTitle = image.title || '';
                },

                async saveTitle(image) {
                    const newTitle = (image._editTitle || '').trim();
                    if (!newTitle) {
                        this.showNotification('{{ __('El título no puede estar vacío.') }}', 'error');
                        return;
                    }

                    const url = window.__micrositeImageRoutes.updateUrl.replace('IMG_ID', image.id);

                    try {
                        const csrfToken = window.__micrositeCsrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        const response = await fetch(url, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ title: newTitle }),
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.message || '{{ __('Error al actualizar el título.') }}');
                        }

                        image.title = data.image.title;
                        image._editing = false;
                        this.showNotification('{{ __('Título actualizado correctamente.') }}');
                    } catch (err) {
                        console.error('Error updating title:', err);
                        this.showNotification(err.message || '{{ __('Error al guardar el título.') }}', 'error');
                    }
                },

                async deleteImage(image) {
                    if (!confirm('{{ __('¿Eliminar esta imagen? Se borrará definitivamente del servidor.') }}')) {
                        return;
                    }

                    const url = window.__micrositeImageRoutes.destroyUrl.replace('IMG_ID', image.id);

                    try {
                        const csrfToken = window.__micrositeCsrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        const response = await fetch(url, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.message || '{{ __('Error al eliminar la imagen.') }}');
                        }

                        this.images = this.images.filter(img => img.id !== image.id);
                        this.showNotification('{{ __('Imagen eliminada correctamente.') }}');
                    } catch (err) {
                        console.error('Error deleting image:', err);
                        this.showNotification(err.message || '{{ __('Error al eliminar la imagen.') }}', 'error');
                    }
                },

                copyUrl(image) {
                    if (!image.url) return;

                    const done = () => {
                        image._copied = true;
                        setTimeout(() => { image._copied = false; }, 2000);
                        this.showNotification('{{ __('URL copiada al portapapeles.') }}');
                    };

                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(image.url).then(done).catch(() => {
                            this.fallbackCopyText(image.url);
                            done();
                        });
                    } else {
                        this.fallbackCopyText(image.url);
                        done();
                    }
                },

                fallbackCopyText(text) {
                    const el = document.createElement('textarea');
                    el.value = text;
                    el.style.position = 'fixed';
                    el.style.left = '-9999px';
                    document.body.appendChild(el);
                    el.select();
                    try {
                        document.execCommand('copy');
                    } catch (e) {}
                    document.body.removeChild(el);
                },

                insertIntoHtml(image) {
                    const alt = (image.title || image.file_name || 'Imagen').replace(/"/g, '&quot;');
                    let snippet = '';
                    if (image.title) {
                        snippet = `\n<figure class="my-6">\n  <img src="${image.url}" alt="${alt}" class="w-full max-h-[500px] object-cover rounded-2xl shadow-md border border-gray-100 dark:border-gray-800">\n  <figcaption class="text-center text-xs text-gray-500 dark:text-gray-400 mt-2 italic">${alt}</figcaption>\n</figure>\n`;
                    } else {
                        snippet = `\n<img src="${image.url}" alt="${alt}" class="w-full max-h-[500px] object-cover rounded-2xl shadow-md border border-gray-100 dark:border-gray-800 my-6">\n`;
                    }

                    if (window.htmlCodeMirrorEditor) {
                        const editor = window.htmlCodeMirrorEditor;
                        const doc = editor.getDoc();
                        const cursor = doc.getCursor();
                        doc.replaceRange(snippet, cursor);
                        editor.focus();
                        editor.save();
                        editor.getWrapperElement().scrollIntoView({ behavior: 'smooth', block: 'center' });
                    } else {
                        const textarea = document.getElementById('html_content');
                        if (textarea) {
                            let start = textarea.selectionStart;
                            let end = textarea.selectionEnd;

                            if ((start === 0 && end === 0 && this.lastCursorPos !== null) || start === undefined) {
                                start = this.lastCursorPos ?? textarea.value.length;
                                end = start;
                            }

                            const fullText = textarea.value;
                            textarea.value = fullText.substring(0, start) + snippet + fullText.substring(end);
                            textarea.selectionStart = textarea.selectionEnd = start + snippet.length;
                            this.lastCursorPos = textarea.selectionEnd;

                            textarea.dispatchEvent(new Event('input', { bubbles: true }));
                            textarea.dispatchEvent(new Event('change', { bubbles: true }));

                            textarea.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            textarea.focus();
                        }
                    }

                    image._justInserted = true;
                    setTimeout(() => { image._justInserted = false; }, 2000);

                    this.showNotification('{{ __('¡Imagen insertada en el código HTML!') }}');
                }
            };
        }

        window.micrositeImagesManager = micrositeImagesManager;
        if (window.Alpine && window.Alpine.data) {
            window.Alpine.data('micrositeImagesManager', micrositeImagesManager);
        } else {
            document.addEventListener('alpine:init', () => {
                if (window.Alpine && window.Alpine.data) {
                    window.Alpine.data('micrositeImagesManager', micrositeImagesManager);
                }
            });
        }
    </script>

                {{-- Sección 3: Gestor de Imágenes del Micrositio --}}
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 md:p-8 shadow-sm"
                     x-data="micrositeImagesManager()">

                    {{-- Encabezado de la sección --}}
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-pink-100 dark:bg-pink-900/30 text-pink-600 flex items-center justify-center font-bold">3</span>
                            <div>
                                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    {{ __('Imágenes del Micrositio') }}
                                    <span x-show="images.length > 0"
                                          x-cloak
                                          class="text-xs px-2.5 py-0.5 rounded-full font-semibold bg-pink-50 dark:bg-pink-900/40 text-pink-600 dark:text-pink-300 border border-pink-200 dark:border-pink-800"
                                          x-text="images.length + ' ' + (images.length === 1 ? '{{ __('imagen') }}' : '{{ __('imágenes') }}')"></span>
                                </h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ __('Sube imágenes para tu micrositio, edita su título e insértalas con un clic en el código HTML.') }}
                                </p>
                            </div>
                        </div>

                        {{-- Botón rápido para subir en la cabecera --}}
                        <div>
                            <button type="button"
                                    id="microsite-upload-btn-header"
                                    @click="triggerFileInput()"
                                    onclick="document.getElementById('microsite-image-file-input').click()"
                                    :disabled="isUploading"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-pink-500 hover:bg-pink-600 active:scale-95 text-white text-xs font-bold rounded-xl transition-all shadow-sm disabled:opacity-50">
                                <svg x-show="!isUploading" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                <svg x-show="isUploading" x-cloak style="display:none;" class="w-4 h-4 shrink-0 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="isUploading ? '{{ __('Subiendo...') }}' : '{{ __('Subir Imagen') }}'">{{ __('Subir Imagen') }}</span>
                            </button>
                        </div>
                    </div>

                    {{-- Notificación / Feedback --}}
                    <div x-show="notification"
                         x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         :class="notification?.type === 'error' ? 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800 text-red-700 dark:text-red-300' : 'bg-emerald-50 dark:bg-emerald-900/30 border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300'"
                         class="mb-4 p-3 rounded-xl border text-xs font-bold flex items-center justify-between"
                         style="display: none;">
                        <span x-text="notification?.message"></span>
                        <button type="button" @click="notification = null" class="text-current opacity-70 hover:opacity-100 ml-2">&times;</button>
                    </div>

                    {{-- Zona de Drag & Drop y Formulario de subida --}}
                    <div class="mb-6 p-4 md:p-6 border-2 border-dashed rounded-2xl transition-all duration-200 cursor-pointer"
                         :class="isDraggingOver ? 'border-pink-500 bg-pink-50/50 dark:bg-pink-950/20' : 'border-gray-200 dark:border-gray-700 bg-gray-50/40 dark:bg-gray-800/30 hover:border-pink-300 dark:hover:border-pink-600/50'"
                         @dragover.prevent="isDraggingOver = true"
                         @dragleave.prevent="isDraggingOver = false"
                         @drop.prevent="handleDrop($event)"
                         @click="if ($event.target.tagName !== 'INPUT' && $event.target.tagName !== 'BUTTON') triggerFileInput()">

                        <input type="file"
                               id="microsite-image-file-input"
                               x-ref="fileInput"
                               @change="handleFileSelect($event)"
                               accept="image/jpeg,image/png,image/jpg,image/gif,image/webp,image/svg+xml"
                               class="hidden">

                        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                            <div class="flex items-center gap-3.5 text-left w-full md:w-auto">
                                <div class="w-12 h-12 rounded-xl bg-pink-100 dark:bg-pink-900/40 text-pink-600 dark:text-pink-400 flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900 dark:text-white">
                                        {{ __('Arrastra tus imágenes aquí o pulsa para seleccionar') }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('Formatos admitidos: PNG, JPG, WEBP, GIF, SVG (máx. 10 MB)') }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 w-full md:w-auto" @click.stop>
                                <input type="text"
                                       x-model="uploadTitle"
                                       @keydown.enter.prevent="triggerFileInput()"
                                       placeholder="{{ __('Nombre / pie descriptivo (opcional)') }}"
                                       class="flex-1 md:w-64 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-xs text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500 transition-colors">

                                <button type="button"
                                        @click="triggerFileInput()"
                                        onclick="document.getElementById('microsite-image-file-input').click()"
                                        :disabled="isUploading"
                                        class="px-3.5 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-bold rounded-xl transition-all shrink-0">
                                    {{ __('Explorar') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Galería de Imágenes --}}
                    <div>
                        {{-- Estado vacío --}}
                        <div x-show="images.length === 0 && !isUploading"
                             class="text-center py-12 px-4 border border-dashed border-gray-200 dark:border-gray-800 rounded-2xl bg-gray-50/30 dark:bg-gray-800/20">
                            <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-400 flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ __('Sin imágenes en este micrositio') }}</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">
                                {{ __('Sube tus imágenes arriba para tenerlas organizadas e insertarlas directamente en el contenido HTML.') }}
                            </p>
                        </div>

                        {{-- Rejilla de tarjetas --}}
                        <div x-show="images.length > 0"
                             x-cloak
                             class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-4">
                            <template x-for="img in images" :key="img.id">
                                <div class="bg-gray-50/70 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700/80 rounded-2xl overflow-hidden flex flex-col group transition-all duration-200 hover:border-pink-300 dark:hover:border-pink-600/50 hover:shadow-md">

                                    {{-- Previsualización con overlay --}}
                                    <div class="relative h-44 bg-gray-100 dark:bg-gray-900 overflow-hidden flex items-center justify-center">
                                        <img :src="img.url"
                                             :alt="img.title || img.file_name"
                                             loading="lazy"
                                             class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">

                                        {{-- Badge tamaño --}}
                                        <span class="absolute top-2 left-2 px-2 py-0.5 rounded-md bg-black/60 backdrop-blur-sm text-white font-mono text-[10px]"
                                              x-text="img.formatted_size"></span>

                                        {{-- Botón rápido Copiar URL --}}
                                        <button type="button"
                                                @click="copyUrl(img)"
                                                class="absolute top-2 right-2 p-1.5 rounded-lg bg-white/90 dark:bg-gray-900/90 hover:bg-white text-gray-700 dark:text-gray-200 shadow-sm backdrop-blur-sm transition-all"
                                                :title="'{{ __('Copiar URL') }}'">
                                            <svg x-show="!img._copied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                            </svg>
                                            <svg x-show="img._copied" x-cloak style="display:none;" class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </button>
                                    </div>

                                    {{-- Detalles y Edición de Título --}}
                                    <div class="p-4 flex-1 flex flex-col justify-between gap-3">
                                        <div>
                                            {{-- Vista normal de título --}}
                                            <div x-show="!img._editing" class="flex items-start justify-between gap-2">
                                                <div @click="startEdit(img)" class="cursor-pointer group/title flex-1 min-w-0" title="{{ __('Clic para renombrar') }}">
                                                    <h4 class="text-xs font-bold text-gray-900 dark:text-white truncate group-hover/title:text-pink-600 dark:group-hover/title:text-pink-400 flex items-center gap-1.5">
                                                        <span x-text="img.title || img.file_name"></span>
                                                        <svg class="w-3 h-3 text-gray-400 opacity-0 group-hover/title:opacity-100 transition-opacity shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                                        </svg>
                                                    </h4>
                                                    <p class="text-[10px] font-mono text-gray-400 dark:text-gray-500 truncate mt-0.5" x-text="img.file_name"></p>
                                                </div>
                                            </div>

                                            {{-- Formulario edición en línea del título --}}
                                            <div x-show="img._editing" x-cloak class="space-y-2" style="display: none;">
                                                <input type="text"
                                                       :id="'img-title-input-' + img.id"
                                                       x-model="img._editTitle"
                                                       @keydown.enter.prevent="saveTitle(img)"
                                                       @keydown.escape="cancelEdit(img)"
                                                       placeholder="{{ __('Título de la imagen') }}"
                                                       class="w-full text-xs bg-white dark:bg-gray-900 border border-pink-400 rounded-lg px-2.5 py-1.5 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500/20">
                                                <div class="flex items-center gap-1.5">
                                                    <button type="button"
                                                            @click="saveTitle(img)"
                                                            class="px-2.5 py-1 bg-pink-500 hover:bg-pink-600 text-white rounded-md text-[11px] font-bold transition-colors">
                                                        {{ __('Guardar') }}
                                                    </button>
                                                    <button type="button"
                                                            @click="cancelEdit(img)"
                                                            class="px-2 py-1 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-md text-[11px] transition-colors">
                                                        {{ __('Cancelar') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Botones de Acción --}}
                                        <div class="space-y-2 pt-2 border-t border-gray-200/70 dark:border-gray-700/60">
                                            {{-- Botón Principal: Insertar en HTML --}}
                                            <button type="button"
                                                    @click="insertIntoHtml(img)"
                                                    :class="img._justInserted ? 'bg-emerald-500 hover:bg-emerald-600' : 'bg-pink-500 hover:bg-pink-600'"
                                                    class="w-full py-2 px-3 text-white rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5 shadow-sm active:scale-95">
                                                <svg x-show="!img._justInserted" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                </svg>
                                                <svg x-show="img._justInserted" x-cloak style="display:none;" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                <span x-text="img._justInserted ? '{{ __('¡Insertada!') }}' : '{{ __('Insertar en HTML') }}'">{{ __('Insertar en HTML') }}</span>
                                            </button>

                                            {{-- Fila Secundaria: Copiar URL + Eliminar --}}
                                            <div class="flex items-center gap-2">
                                                <button type="button"
                                                        @click="copyUrl(img)"
                                                        class="flex-1 py-1.5 px-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700/60 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-[11px] font-semibold flex items-center justify-center gap-1 transition-colors">
                                                    <svg class="w-3.5 h-3.5 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                                                    </svg>
                                                    <span x-text="img._copied ? '{{ __('¡Copiada!') }}' : '{{ __('Copiar URL') }}'">{{ __('Copiar URL') }}</span>
                                                </button>

                                                <button type="button"
                                                        @click="deleteImage(img)"
                                                        class="py-1.5 px-2.5 text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg text-[11px] font-semibold flex items-center justify-center gap-1 transition-colors"
                                                        title="{{ __('Eliminar imagen') }}">
                                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                    <span>{{ __('Eliminar') }}</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Sección 4: Contenido y Diseño --}}
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 md:p-8 shadow-sm">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-pink-100 dark:bg-pink-900/30 text-pink-600 flex items-center justify-center">4</span>
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
                            <textarea name="css_content" id="css_content" rows="8" placeholder="body { background-color: #f3f4f6; }&#10;.mi-clase { color: pink; }"
                                class="w-full bg-[#1e1e1e] border border-gray-700 rounded-xl px-4 py-3 text-gray-300 focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500 transition-colors font-mono text-sm shadow-inner resize-y">{{ old('css_content', $microsite->css_content) }}</textarea>
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
                            <textarea name="html_content" id="html_content" rows="20" required placeholder="&lt;div class=&quot;max-w-2xl mx-auto p-4&quot;&gt;&#10;  &lt;h1 class=&quot;text-3xl font-bold&quot;&gt;¡Hola Mundo!&lt;/h1&gt;&#10;  &lt;p&gt;Este es mi nuevo micrositio.&lt;/p&gt;&#10;&lt;/div&gt;"
                                class="w-full bg-[#1e1e1e] border border-gray-700 rounded-xl px-4 py-3 text-gray-300 focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500 transition-colors font-mono text-sm shadow-inner resize-y">{{ old('html_content', $microsite->html_content) }}</textarea>
                            <p class="text-xs text-gray-500 mt-1">{{ __('You can use standard HTML and Tailwind CSS classes as needed. Code will be rendered exactly as written.') }}</p>
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

        {{-- Título truncado --}}
        <span class="text-xs font-black text-gray-800 dark:text-gray-200 max-w-[200px] truncate">
            {{ $microsite->title }}
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
