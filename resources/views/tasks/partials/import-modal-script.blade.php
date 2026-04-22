    <script>
        function openImportTaskModal(initialMode = 'all') {
            Swal.fire({
                title: 'Importar Tarea',
                html: `
                    <div class="text-left mt-4 border-t border-gray-100 dark:border-gray-800 pt-5">
                        <div class="flex items-center justify-between mb-2 ml-1">
                            <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block">Opción 1: Pegar JSON desde Portapapeles</label>
                            <button type="button" onclick="pasteFromClipboard()" class="flex items-center gap-1 text-[9px] font-bold text-violet-600 dark:text-violet-400 hover:scale-105 transition-transform">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                Pegar ahora
                            </button>
                        </div>
                        <textarea id="import-json-content" class="w-full h-36 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 rounded-2xl px-4 py-3 text-xs font-mono text-gray-600 dark:text-gray-400 focus:ring-2 focus:ring-violet-500/20 outline-none resize-none shadow-inner" placeholder='Pega aquí el contenido JSON...'></textarea>
                        
                        <div class="relative my-6">
                            <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-100 dark:border-gray-800"></div></div>
                            <div class="relative flex justify-center text-[10px] uppercase font-bold text-gray-400 bg-white dark:bg-slate-900 px-4">O bien</div>
                        </div>

                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2 ml-1">Opción 2: Seleccionar Archivo .json</label>
                        <input type="file" id="import-json-file" accept=".json" class="w-full text-xs text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-violet-100 file:text-violet-700 hover:file:bg-violet-200 dark:file:bg-violet-900/40 dark:file:text-violet-400 transition-all cursor-pointer"/>
                        
                        <p class="mt-5 text-[10px] text-gray-500 font-medium leading-relaxed italic border-l-2 border-amber-200 pl-3">
                            * Se creará una nueva tarea con todos los metadatos exportados. Los archivos binarios adjuntos no se transportan en el JSON.
                        </p>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Importar Ahora 🚀',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#7c3aed',
                background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#f1f5f9' : '#1e293b',
                customClass: {
                    popup: 'rounded-[2rem]',
                    confirmButton: 'rounded-xl font-black uppercase text-xs tracking-widest px-8 py-3',
                    cancelButton: 'rounded-xl font-black uppercase text-xs tracking-widest px-8 py-3'
                },
                didOpen: () => {
                    // Definir la función globalmente para que el botón del HTML del modal pueda verla
                    window.pasteFromClipboard = async () => {
                        try {
                            const text = await navigator.clipboard.readText();
                            document.getElementById('import-json-content').value = text;
                        } catch (err) {
                            Swal.showValidationMessage('No se pudo acceder al portapapeles. Pégalo manualmente.');
                        }
                    };

                    // Auto-pegar y auto-enfocar si el modo es 'paste'
                    if (initialMode === 'paste') {
                        window.pasteFromClipboard();
                        document.getElementById('import-json-content').focus();
                    } else if (initialMode === 'file') {
                        document.getElementById('import-json-file').focus();
                    }
                },
                preConfirm: () => {
                    const content = document.getElementById('import-json-content').value;
                    const fileInput = document.getElementById('import-json-file');
                    const file = fileInput.files[0];
                    
                    if (!content && !file) {
                        Swal.showValidationMessage('Debes pegar el JSON o seleccionar un archivo');
                        return false;
                    }
                    
                    const formData = new FormData();
                    if (file) {
                        formData.append('file', file);
                    } else {
                        formData.append('json_content', content);
                    }
                    formData.append('_token', '{{ csrf_token() }}');
                    
                    return fetch("{{ route('teams.tasks.import-json', $team) }}", {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(json => { throw new Error(json.message || 'Error en la importación'); });
                        }
                        return response.json();
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`Error: ${error.message}`);
                    });
                }
            }).then((result) => {
                if (result.isConfirmed && result.value.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Tarea Importada!',
                        text: result.value.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = result.value.url;
                    });
                }
            });
        }
    </script>
