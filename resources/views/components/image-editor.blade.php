<div id="global-image-editor-modal" x-data="draggableImageEditor()" class="fixed inset-0 z-[60] hidden pointer-events-none">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm pointer-events-auto" onclick="closeGlobalImageEditor()"></div>
    
    <!-- Draggable & Resizable Modal -->
    <div x-ref="modal"
         class="absolute bg-white dark:bg-gray-900 rounded-xl shadow-2xl overflow-hidden block pointer-events-auto border border-gray-200 dark:border-gray-700"
         style="width: 80vw; height: 80vh; min-width: 400px; min-height: 400px; top: 10vh; left: 10vw; resize: both;">
         
         <!-- Drag Handle / Header -->
         <div @mousedown="startDrag" class="h-12 shrink-0 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 cursor-move flex items-center justify-between px-4 select-none">
             <div class="flex items-center space-x-2">
                 <svg class="w-5 h-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                 </svg>
                 <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Editor de Imagen</span>
             </div>
             <button onclick="closeGlobalImageEditor()" class="text-gray-400 hover:text-red-500 transition-colors p-1.5 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700">
                 <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                     <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                 </svg>
             </button>
         </div>

        <!-- Filerobot Container -->
        <div id="filerobot-editor-container" style="height: calc(100% - 3rem); width: 100%;" class="bg-gray-900"></div>
    </div>
</div>

@push('scripts')
<script src="https://scaleflex.cloudimg.io/v7/plugins/filerobot-image-editor/latest/filerobot-image-editor.min.js"></script>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('draggableImageEditor', () => ({
            isDragging: false,
            startX: 0,
            startY: 0,
            initialX: 0,
            initialY: 0,
            
            init() {
                // Force recalculation when the user resizes the modal using CSS resize
                const observer = new ResizeObserver(() => {
                    window.dispatchEvent(new Event('resize'));
                });
                observer.observe(this.$refs.modal);
            },
            startDrag(e) {
                if (e.target.closest('button')) return; // Ignore buttons
                
                this.isDragging = true;
                this.startX = e.clientX;
                this.startY = e.clientY;
                
                const rect = this.$refs.modal.getBoundingClientRect();
                this.initialX = rect.left;
                this.initialY = rect.top;
                
                const onMouseMove = (e) => {
                    if (!this.isDragging) return;
                    const dx = e.clientX - this.startX;
                    const dy = e.clientY - this.startY;
                    
                    this.$refs.modal.style.left = `${this.initialX + dx}px`;
                    this.$refs.modal.style.top = `${this.initialY + dy}px`;
                };
                
                const onMouseUp = () => {
                    this.isDragging = false;
                    document.removeEventListener('mousemove', onMouseMove);
                    document.removeEventListener('mouseup', onMouseUp);
                    // Force Filerobot to recalculate canvas coordinates after dragging
                    window.dispatchEvent(new Event('resize'));
                };
                
                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
            }
        }));
    });

    let currentFilerobotEditor = null;

    window.openGlobalImageEditor = function(fileOrUrl, onSaveCallback) {
        document.getElementById('global-image-editor-modal').classList.remove('hidden');
        
        let sourceUrl = fileOrUrl;
        if (fileOrUrl instanceof File || fileOrUrl instanceof Blob) {
            sourceUrl = URL.createObjectURL(fileOrUrl);
        }

        const config = {
            source: sourceUrl,
            defaultSavedImageQuality: 0.95, // High quality, much better than 0.85
            defaultSavedImageType: 'webp', 
            isLowQualityPreview: false, 
            previewPixelRatio: window.devicePixelRatio || 2, // Ensure crisp preview on high-DPI displays
            observePluginContainerSize: true,
            reduceBeforeEdit: {
                mode: 'manual', 
                widthLimit: 4000,
                heightLimit: 4000
            },
            onSave: (editedImageObject, designState) => {
                fetch(editedImageObject.imageBase64)
                    .then(res => res.blob())
                    .then(blob => {
                        let originalName = 'edited-image.jpg';
                        if (fileOrUrl instanceof File) {
                            originalName = fileOrUrl.name;
                        } else if (typeof fileOrUrl === 'string') {
                            originalName = fileOrUrl.substring(fileOrUrl.lastIndexOf('/') + 1) || originalName;
                            // strip query parameters if any
                            originalName = originalName.split('?')[0];
                        }
                        
                        const mimeType = editedImageObject.mimeType || blob.type || 'image/webp';
                        let ext = mimeType.split('/')[1] || 'webp';
                        if (ext === 'jpeg') ext = 'jpg';
                        
                        const nameWithoutExt = originalName.substring(0, originalName.lastIndexOf('.')) || originalName;
                        const finalName = `${nameWithoutExt}-edited.${ext}`;

                        const newFile = new File([blob], finalName, { type: mimeType });
                        
                        if (onSaveCallback) {
                            onSaveCallback(newFile, editedImageObject.imageBase64);
                        }
                        
                        setTimeout(() => {
                            closeGlobalImageEditor();
                        }, 50);
                    });
            },
            onClose: () => {
                closeGlobalImageEditor();
            },
            language: 'es',
            theme: {
                colors: {
                    primaryBg: '#111827',
                    primaryFg: '#f9fafb',
                    secondaryBg: '#1f2937',
                    secondaryFg: '#d1d5db',
                    accent: '#8b5cf6',
                }
            }
        };

        document.getElementById('filerobot-editor-container').innerHTML = ''; // Force clean state
        currentFilerobotEditor = new FilerobotImageEditor(
            document.getElementById('filerobot-editor-container'),
            config
        );

        currentFilerobotEditor.render({
            onClose: (closingReason) => {
                closeGlobalImageEditor();
            }
        });
        
        // Filerobot often caches its canvas bounding box before the browser finishes painting the modal layout.
        // This causes mouse wheel zoom to calculate from an offset origin (the top-left of the screen instead of the canvas).
        // Dispatching a resize event a moment after render forces it to grab the true X/Y coordinates.
        setTimeout(() => {
            window.dispatchEvent(new Event('resize'));
        }, 150);
    };

    window.closeGlobalImageEditor = function() {
        document.getElementById('global-image-editor-modal').classList.add('hidden');
        if (currentFilerobotEditor) {
            currentFilerobotEditor.terminate();
            currentFilerobotEditor = null;
        }
    };
</script>
@endpush

@php
    $currentTeamForEditor = request()->route('team');
    $currentTeamIdForEditor = is_object($currentTeamForEditor) ? $currentTeamForEditor->id : $currentTeamForEditor;
@endphp

@if($currentTeamIdForEditor)
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Run periodically or on DOM mutations to catch newly rendered markdown?
        // Simple approach: run every 2 seconds to catch any loaded markdown content
        setInterval(() => {
            document.querySelectorAll('.markdown-content img:not(.filerobot-injected), .prose img:not(.filerobot-injected)').forEach(img => {
                img.classList.add('filerobot-injected');
                const src = img.getAttribute('src');
                if (src && src.includes('/storage/forum/')) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'relative inline-block group/inline-img max-w-full';
                    img.parentNode.insertBefore(wrapper, img);
                    wrapper.appendChild(img);
                    
                    const btn = document.createElement('button');
                    btn.className = 'absolute top-2 right-2 p-1.5 bg-white/90 dark:bg-gray-800/90 text-gray-700 dark:text-gray-300 hover:text-violet-600 dark:hover:text-violet-400 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 opacity-0 group-hover/inline-img:opacity-100 transition-opacity z-10';
                    btn.title = 'Editar Imagen en línea';
                    btn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>`;
                    
                    btn.onclick = (e) => {
                        e.preventDefault();
                        if (typeof window.openGlobalImageEditor === 'function') {
                            window.openGlobalImageEditor(src, (editedFile) => {
                                const formData = new FormData();
                                formData.append('image', editedFile);
                                formData.append('path', src.split('?')[0]);
                                
                                Swal.fire({
                                    title: 'Guardando...',
                                    text: 'Actualizando la imagen en línea',
                                    allowOutsideClick: false,
                                    didOpen: () => {
                                        Swal.showLoading();
                                    }
                                });

                                fetch(`{{ route('teams.forum.replace_inline_image', $currentTeamIdForEditor) }}`, {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                        'Accept': 'application/json'
                                    },
                                    body: formData
                                })
                                .then(response => {
                                    if (!response.ok) throw new Error(response.statusText);
                                    return response.json();
                                })
                                .then(data => {
                                    if(data.success) {
                                        img.src = data.url;
                                        Swal.fire({
                                            title: '¡Actualizada!',
                                            text: 'La imagen ha sido sustituida correctamente.',
                                            icon: 'success',
                                            timer: 2000,
                                            showConfirmButton: false
                                        });
                                    } else {
                                        throw new Error(data.message || 'Error al guardar la imagen');
                                    }
                                })
                                .catch(error => {
                                    Swal.fire('Error', error.message, 'error');
                                });
                            });
                        }
                    };
                    wrapper.appendChild(btn);
                }
            });
        }, 1000);
    });
</script>
@endif
