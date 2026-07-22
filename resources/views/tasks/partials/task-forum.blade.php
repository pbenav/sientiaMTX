            <!-- Foro / Discusión -->
            <div class="mt-0">
                @include('teams.forum.partials.thread-widget')
            </div>

        </div>
    </div>

    @push('scripts')
        <script>
            function nudgeUser(taskIds, userId = null) {
                const isBulk = Array.isArray(taskIds) || (taskIds && typeof taskIds === 'object' && taskIds.length !== undefined);
                const ids = isBulk ? Array.from(taskIds) : [taskIds];
                
                Swal.fire({
                    title: isBulk ? '¿Enviar recordatorio masivo?' : '¿Enviar recordatorio?',
                    html: `
                        <p class="text-sm text-gray-500 mb-4">${isBulk ? 'Se enviará un recordatorio a todos los miembros seleccionados.' : 'Se enviará un recordatorio al miembro responsable.'}</p>
                        <textarea id="nudge-message" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-sm focus:ring-violet-500 min-h-[100px] p-3 shadow-inner" placeholder="Escribe un mensaje personalizado del coordinador (opcional)..."></textarea>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#7c3aed',
                    cancelButtonColor: '#94a3b8',
                    confirmButtonText: 'Enviar',
                    cancelButtonText: 'Cancelar',
                    background: document.documentElement.classList.contains('dark') ? '#111827' : '#fff',
                    color: document.documentElement.classList.contains('dark') ? '#fff' : '#111827',
                    preConfirm: () => {
                        return document.getElementById('nudge-message').value;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const customMessage = result.value;
                        const url = isBulk ? `{{ route('teams.activities.bulk-nudge', $team) }}` : `{{ route('teams.activities.nudge', [$team, 'TASK_ID']) }}`.replace('TASK_ID', taskIds);
                        const cleanTaskIds = ids.map(target => target.toString().split(':')[0]);
                        const payload = isBulk ? { targets: ids, task_ids: cleanTaskIds, custom_message: customMessage } : { custom_message: customMessage };
                        if (userId) payload.user_id = userId;

                        fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify(payload)
                        })
                        .then(async response => {
                            const contentType = response.headers.get('content-type');
                            if (!contentType || !contentType.includes('application/json')) {
                                const text = await response.text();
                                console.error('Non-JSON response:', text);
                                throw new Error('El servidor no devolvió una respuesta JSON válida. Verifica la conexión o la URL del dominio.');
                            }
                            const data = await response.json();
                            if (!response.ok) {
                                throw new Error(data.message || data.error || 'Error en la petición al servidor.');
                            }
                            return data;
                        })
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    title: '¡Listo!',
                                    text: data.message,
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false,
                                    background: document.documentElement.classList.contains('dark') ? '#111827' : '#fff',
                                    color: document.documentElement.classList.contains('dark') ? '#fff' : '#111827'
                                }).then(() => {
                                    if (isBulk) {
                                        window.location.reload();
                                    }
                                });
                            } else {
                                Swal.fire('Error', data.message || 'No se pudo enviar el recordatorio.', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Nudge Error:', error);
                            Swal.fire('Error', error.message || 'Ocurrió un error en la conexión.', 'error');
                        });
                    }
                });
            }

            function reassignTask(taskId, userId) {
                if (!userId) return;
                
                const payloadValue = userId === 'unassign' ? null : userId;
                
                fetch(`/teams/{{ $team->id }}/tasks/${taskId}/move`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        assigned_user_id: payloadValue
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'bottom-end',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        });
                        Toast.fire({
                            icon: 'success',
                            title: 'Asignación actualizada'
                        }).then(() => location.reload());
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'No se ha podido cambiar la asignación.',
                        icon: 'error'
                    });
                });
            }

            function updateTaskStatus(status, taskId = {{ $task->id }}) {
                const messages = {
                    'completed': '¿Marcar como completada?',
                    'blocked': '¿Informar un bloqueo en esta tarea?',
                    'pending': '¿Reabrir esta tarea?',
                    'in_progress': '¿Quitar el bloqueo de esta tarea?'
                };

                Swal.fire({
                    title: messages[status] || '¿Cambiar estado?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: status === 'blocked' ? '#ef4444' : '#6366f1',
                    cancelButtonColor: '#94a3b8',
                    confirmButtonText: 'Confirmar',
                    cancelButtonText: 'Cancelar',
                    background: document.documentElement.classList.contains('dark') ? '#111827' : '#fff',
                    color: document.documentElement.classList.contains('dark') ? '#fff' : '#111827'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/teams/{{ $team->id }}/tasks/${taskId}/move`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                        'content')
                                },
                                body: JSON.stringify({
                                    status: status
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        title: '¡Actualizado!',
                                        text: 'El estado se ha actualizado correctamente.',
                                        icon: 'success',
                                        timer: 1500,
                                        showConfirmButton: false,
                                        background: document.documentElement.classList.contains('dark') ?
                                            '#111827' : '#fff',
                                        color: document.documentElement.classList.contains('dark') ?
                                            '#fff' : '#111827'
                                    }).then(() => {
                                        window.location.reload();
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                Swal.fire({
                                    title: 'Error',
                                    text: 'No se pudo actualizar el estado',
                                    icon: 'error',
                                    background: document.documentElement.classList.contains('dark') ?
                                        '#111827' : '#fff',
                                    color: document.documentElement.classList.contains('dark') ? '#fff' :
                                        '#111827'
                                });
                            });
                    }
                });
            }

            function updateTaskProgress(progress, taskId = {{ $task->id }}, currentStatus = '{{ $task->status }}') {

                fetch(`/teams/{{ $team->id }}/tasks/${taskId}/move`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content')
                        },
                        body: JSON.stringify({
                            progress_percentage: progress
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // If status has changed (e.g. from completed back to in_progress), reload
                            if (data.task_status !== currentStatus || progress == 100) {
                                window.location.reload();
                            } else {
                                // Subtle label update without animations that feel like glitches
                                // Actualización masiva de todos los elementos de progreso
                                const finalProgress = data.parent_progress !== null ? data.parent_progress : data.task_progress;
                                const finalProgressRounded = Math.round(finalProgress);

                                document.querySelectorAll('.js-global-progress-val').forEach(el => el.innerText = finalProgressRounded + '%');
                                document.querySelectorAll('.js-global-progress-bar').forEach(el => el.style.width = finalProgress + '%');

                                // Sincronizar todos los miembros (tareas colaborativas/maestras)
                                document.querySelectorAll('.js-member-progress-bar').forEach(bar => {
                                    bar.style.width = finalProgress + '%';
                                });
                                document.querySelectorAll('.js-member-progress-val').forEach(val => {
                                    val.innerText = finalProgressRounded + '%';
                                });
                                document.querySelectorAll('.js-member-progress-slider').forEach(slider => {
                                    slider.value = finalProgressRounded;
                                });

                                // Elemento específico del sidebar si existe
                                const valSpan = document.getElementById('progress-val');
                                if (valSpan) valSpan.innerText = finalProgressRounded;

                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }

            function toggleAutoPriority() {
                const btn = document.getElementById('btn-auto-priority');
                if (!btn) return;

                fetch(`/teams/{{ $team->id }}/tasks/{{ $task->id }}/toggle-auto-priority`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.text().then(text => {
                                console.error('Server Error:', text);
                                throw new Error('Error del servidor: ' + response.status);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Update UI state
                            const isOn = data.auto_priority;
                            
                            // Update button styles
                            btn.classList.toggle('bg-violet-50', isOn);
                            btn.classList.toggle('dark:bg-violet-900/20', isOn);
                            btn.classList.toggle('text-violet-600', isOn);
                            btn.classList.toggle('dark:text-violet-400', isOn);
                            btn.classList.toggle('border-violet-100', isOn);
                            btn.classList.toggle('dark:border-violet-800', isOn);
                            
                            btn.classList.toggle('bg-gray-50', !isOn);
                            btn.classList.toggle('dark:bg-gray-800/50', !isOn);
                            btn.classList.toggle('text-gray-500', !isOn);
                            btn.classList.toggle('dark:text-gray-400', !isOn);
                            btn.classList.toggle('border-transparent', !isOn);
                            
                            const svg = btn.querySelector('svg');
                            if (svg) svg.classList.toggle('animate-pulse', isOn);
                            
                            const dot = btn.querySelector('span.pointer-events-none');
                            const bg = btn.querySelector('div.relative.inline-flex');
                            if (dot) {
                                dot.style.transform = isOn ? 'translateX(0.75rem)' : 'translateX(0)';
                                bg.classList.toggle('bg-violet-500', isOn);
                                bg.classList.toggle('bg-gray-200', !isOn);
                                bg.classList.toggle('dark:bg-gray-700', !isOn);
                            }

                            // Update priority label if it changed
                            document.querySelectorAll('.js-priority-label').forEach(el => {
                                el.innerText = data.priority_label;
                            });

                            if (typeof Toast !== 'undefined') {
                                Toast.fire({
                                    icon: 'success',
                                    title: isOn ? 'Prioridad automática activada' : 'Prioridad automática desactivada'
                                });
                            }
                        }
                    })
                    .catch(error => {
                        console.error('AutoPriority Error:', error);
                        let errorMsg = 'No se pudo actualizar la prioridad automática';
                        
                        // Si tenemos un objeto de error con mensaje específico
                        if (error.message && error.message.includes('Error del servidor')) {
                            // Intentamos no hacer nada especial, pero el throw de arriba ya tiene el status
                        }

                        if (typeof Toast !== 'undefined') {
                            Toast.fire({
                                icon: 'error',
                                title: errorMsg
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: errorMsg,
                                icon: 'error',
                                background: document.documentElement.classList.contains('dark') ? '#111827' : '#fff',
                                color: document.documentElement.classList.contains('dark') ? '#fff' : '#111827'
                            });
                        }
                    });
            }

            function editAttachmentImage(id, url) {
                if (typeof window.openGlobalImageEditor === 'function') {
                    window.openGlobalImageEditor(url, (editedFile) => {
                        const formData = new FormData();
                        formData.append('file', editedFile);
                        
                        Swal.fire({
                            title: 'Guardando...',
                            text: 'Actualizando la imagen en el servidor',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        fetch(`/teams/{{ $team->id }}/attachments/${id}/replace`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: formData
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Actualizada!',
                                    showConfirmButton: false,
                                    timer: 1500
                                }).then(() => {
                                    window.location.reload();
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
            }

            function renameAttachment(id, currentName) {
                Swal.fire({
                    title: "{{ __('tasks.rename_attachment') }}",
                    input: 'text',
                    inputLabel: "{{ __('tasks.new_name') }}",
                    inputValue: currentName,
                    showCancelButton: true,
                    confirmButtonText: "{{ __('Save Changes') }}",
                    cancelButtonText: "{{ __('Cancel') }}",
                    inputValidator: (value) => {
                        if (!value) {
                            return '¡El nombre no puede estar vacío!'
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = `/teams/{{ $team->id }}/attachments/${id}`;
                        
                        // Add CSRF token safely
                        const csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden';
                        csrfInput.name = '_token';
                        csrfInput.value = '{{ csrf_token() }}';
                        form.appendChild(csrfInput);

                        // Add Method override safely
                        const methodInput = document.createElement('input');
                        methodInput.type = 'hidden';
                        methodInput.name = '_method';
                        methodInput.value = 'PATCH';
                        form.appendChild(methodInput);

                        // Add File Name safely
                        const fileInput = document.createElement('input');
                        fileInput.type = 'hidden';
                        fileInput.name = 'file_name';
                        fileInput.value = result.value;
                        form.appendChild(fileInput);

                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            }

            function confirmAttachmentDelete(id, provider = 'local') {
                if (provider === 'google') {
                    Swal.fire({
                        title: '¿Qué deseas hacer?',
                        text: "Este archivo está en Google Drive. ¿Quieres eliminarlo de la nube o solo desvincularlo de esta tarea?",
                        icon: 'question',
                        showDenyButton: true,
                        showCancelButton: true,
                        confirmButtonText: 'Eliminar de Drive y MTX',
                        denyButtonText: 'Solo desvincular de MTX',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#ef4444',
                        denyButtonColor: '#6b7280',
                        customClass: {
                            popup: 'rounded-[2.5rem] border-0 shadow-2xl dark:bg-gray-900 dark:text-white',
                            confirmButton: 'rounded-2xl px-6 py-3 uppercase tracking-widest font-black text-[10px]',
                            denyButton: 'rounded-2xl px-6 py-3 uppercase tracking-widest font-black text-[10px]',
                            cancelButton: 'rounded-2xl px-6 py-3 uppercase tracking-widest font-black text-[10px]'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Delete from both
                            const form = document.getElementById(`delete-attachment-${id}`);
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'delete_from_drive';
                            input.value = '1';
                            form.appendChild(input);
                            form.submit();
                        } else if (result.isDenied) {
                            // Only unlink
                            document.getElementById(`delete-attachment-${id}`).submit();
                        }
                    });
                } else {
                    Swal.fire({
                        title: "{{ __('tasks.delete_attachment_confirm') }}",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: '{{ __('Sí, eliminar') }}',
                        cancelButtonText: '{{ __('Cancelar') }}',
                        customClass: {
                            popup: 'rounded-[2.5rem] border-0 shadow-2xl dark:bg-gray-900 dark:text-white',
                            confirmButton: 'rounded-2xl px-6 py-3 uppercase tracking-widest font-black text-[10px]',
                            cancelButton: 'rounded-2xl px-6 py-3 uppercase tracking-widest font-black text-[10px]'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById(`delete-attachment-${id}`).submit();
                        }
                    });
                }
            }

            async function handleAttachmentUpload(input) {
                const files = input.files;
                if (!files.length) return;

                const limit = "{{ ini_get('upload_max_filesize') }}";
                const limitBytes = parsePHPSize(limit);

                let totalSize = 0;

                // 1. Check PHP upload limit
                for (let i = 0; i < files.length; i++) {
                    totalSize += files[i].size;
                    if (files[i].size > limitBytes) {
                        Swal.fire({
                            title: '{{ __('Archivo demasiado grande') }}',
                            text: `El archivo ${files[i].name} excede el límite de ${limit} configurado en el servidor.`,
                            icon: 'error',
                            background: document.documentElement.classList.contains('dark') ? '#111827' : '#fff',
                            color: document.documentElement.classList.contains('dark') ? '#fff' : '#111827'
                        });
                        input.value = '';
                        return;
                    }
                }

                // 2. Check team quota BEFORE uploading
                try {
                    const res = await fetch('{{ route("teams.quota-status", $team) }}', {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (res.ok) {
                        const quota = await res.json();
                        if (totalSize > quota.available_bytes) {
                            const usedMB = (quota.disk_used / 1024 / 1024).toFixed(1);
                            const totalMB = (quota.disk_quota / 1024 / 1024).toFixed(1);
                            Swal.fire({
                                title: '⚠️ Almacenamiento lleno',
                                html: `El equipo ha alcanzado su límite de almacenamiento o los archivos seleccionados exceden el espacio disponible.<br><small style="opacity:.7">${usedMB} MB / ${totalMB} MB usados</small><br><br>Un coordinador debe liberar espacio antes de poder subir más archivos.`,
                                icon: 'warning',
                                background: document.documentElement.classList.contains('dark') ? '#111827' : '#fff',
                                color: document.documentElement.classList.contains('dark') ? '#fff' : '#111827',
                                confirmButtonColor: '#7c3aed'
                            });
                            input.value = '';
                            return;
                        }
                    }
                } catch (e) {
                    console.warn('Quota pre-check failed, proceeding with upload.', e);
                }

                sessionStorage.setItem('task_show_scrollpos', window.scrollY);
                document.getElementById('attachment-form').submit();
            }

            function parsePHPSize(size) {
                const unit = size.slice(-1).toUpperCase();
                const value = parseFloat(size);
                switch (unit) {
                    case 'G': return value * 1024 * 1024 * 1024;
                    case 'M': return value * 1024 * 1024;
                    case 'K': return value * 1024;
                    default: return value;
                }
            }

            // Restore scroll position after attachment upload
            const scrollpos = sessionStorage.getItem('task_show_scrollpos');
            if (scrollpos) {
                setTimeout(() => {
                    window.scrollTo({ top: parseInt(scrollpos), behavior: 'instant' });
                }, 50);
                sessionStorage.removeItem('task_show_scrollpos');
            }

            // Inteligencia Premium: Recarga automática al volver de editar un documento en OnlyOffice
            window.addEventListener('focus', function() {
                if (sessionStorage.getItem('needs_office_reload')) {
                    sessionStorage.removeItem('needs_office_reload');
                    window.location.reload();
                }
            });
        </script>


    @endpush

    @push('modals')
        <x-google-drive-picker :team="$team" />
    <div id="task-history-diff-modal" class="hidden fixed inset-0 z-[110] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-950/80 backdrop-blur-sm transition-opacity" onclick="closeHistoryDiff()"></div>
            <div class="relative bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-2xl w-full overflow-hidden border border-gray-200 dark:border-gray-800">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gray-50 dark:bg-gray-900/50">
                    <div>
                        <h3 class="text-lg font-black text-gray-900 dark:text-white heading uppercase tracking-tight" id="history-diff-action">Cambios Realizados</h3>
                        <p id="history-diff-date" class="text-xs text-gray-500 dark:text-gray-400 font-medium"></p>
                    </div>
                    <button onclick="closeHistoryDiff()" class="text-gray-400 hover:text-gray-500 p-2">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
                <div class="px-6 py-6 max-h-[70vh] overflow-y-auto custom-scrollbar" id="history-diff-content">
