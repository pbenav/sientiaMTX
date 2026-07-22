                    <!-- Diff will be injected here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Attachment History Modal -->
    <div id="attachment-history-modal" class="hidden fixed inset-0 z-[110] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-950/80 backdrop-blur-sm transition-opacity" onclick="closeAttachmentHistory()"></div>
            <div class="relative bg-white dark:bg-gray-900 rounded-3xl shadow-2xl max-w-2xl w-full overflow-hidden border border-gray-200 dark:border-gray-800">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gray-50 dark:bg-gray-900/50">
                    <div>
                        <h3 class="text-lg font-black text-gray-900 dark:text-white heading uppercase tracking-tight">Historial del Archivo</h3>
                        <p id="history-filename" class="text-xs text-gray-500 dark:text-gray-400 font-medium truncate max-w-sm"></p>
                    </div>
                    <button onclick="closeAttachmentHistory()" class="text-gray-400 hover:text-gray-500 p-2">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
                <div class="px-6 py-6 max-h-[60vh] overflow-y-auto" id="history-content">
                    <!-- Logs will be injected here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        function showHistoryDiff(id) {
            const histories = @json($task->histories->sortByDesc('created_at')->take(15)->values());
            const log = histories.find(h => h.id == id);
            
            if (!log || !log.old_values || !log.new_values) {
                // If it's a simple action without values (like 'cloned' or 'blocked'), we might just show notes
                if (log && log.notes) {
                    Swal.fire({
                        title: log.action.toUpperCase(),
                        text: log.notes,
                        icon: 'info',
                        background: document.documentElement.classList.contains('dark') ? '#111827' : '#fff',
                        color: document.documentElement.classList.contains('dark') ? '#fff' : '#111827'
                    });
                }
                return;
            }

            document.getElementById('history-diff-action').innerText = log.action.toUpperCase();
            document.getElementById('history-diff-date').innerText = new Date(log.created_at).toLocaleString();
            const content = document.getElementById('history-diff-content');
            content.innerHTML = '';

            const fieldLabels = {
                'title': 'Título',
                'description': 'Descripción',
                'status': 'Estado',
                'priority': 'Prioridad',
                'urgency': 'Urgencia',
                'progress_percentage': 'Progreso',
                'due_date': 'Fecha de entrega',
                'scheduled_date': 'Fecha de inicio',
                'visibility': 'Visibilidad',
                'observations': 'Observaciones',
                'cognitive_load': 'Carga cognitiva',
                'is_backstage': 'Backstage',
                'skill_id': 'Capacidad principal',
                'service_id': 'Servicio asociado'
            };

            const valueFormatters = {
                'status': (v) => {
                    const map = { 'pending': 'Pendiente', 'in_progress': 'En Progreso', 'completed': 'Completada', 'cancelled': 'Cancelada', 'blocked': 'Bloqueada' };
                    return map[v] || v;
                },
                'priority': (v) => {
                    const map = { 'low': 'Baja', 'medium': 'Media', 'high': 'Alta', 'critical': 'Crítica' };
                    return map[v] || v;
                },
                'urgency': (v) => {
                    const map = { 'low': 'Baja', 'medium': 'Media', 'high': 'Alta', 'critical': 'Crítica' };
                    return map[v] || v;
                },
                'progress_percentage': (v) => v + '%',
                'visibility': (v) => v === 'public' ? 'Público' : 'Privado',
                'is_backstage': (v) => v ? 'Sí' : 'No',
                'due_date': (v) => v ? new Date(v).toLocaleString() : '—',
                'scheduled_date': (v) => v ? new Date(v).toLocaleString() : '—',
                'autoprogram_settings': (v) => {
                    if (!v) return '—';
                    try {
                        const obj = typeof v === 'string' ? JSON.parse(v) : v;
                        return '<pre class="whitespace-pre-wrap font-mono text-[9px]">' + JSON.stringify(obj, null, 2) + '</pre>';
                    } catch (e) { return v; }
                }
            };

            const ignoredFields = ['updated_at', 'created_at', 'id', 'uuid', 'google_synced_at', 'matrix_order', 'kanban_order', 'kanban_column_id'];
            
            let hasChanges = false;
            let html = '<div class="space-y-4">';

            for (const key in log.new_values) {
                if (ignoredFields.includes(key)) continue;
                
                const oldVal = log.old_values[key];
                const newVal = log.new_values[key];

                if (JSON.stringify(oldVal) !== JSON.stringify(newVal)) {
                    hasChanges = true;
                    const label = fieldLabels[key] || key;
                    const formatter = valueFormatters[key] || ((v) => {
                        if (v === null || v === undefined) return '—';
                        if (typeof v === 'boolean') return v ? 'Sí' : 'No';
                        return v;
                    });
                    
                    html += `
                        <div class="bg-gray-50 dark:bg-gray-800/40 rounded-xl p-4 border border-gray-100 dark:border-gray-800">
                            <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">${label}</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-stretch">
                                <div class="bg-red-50 dark:bg-red-900/10 text-red-700 dark:text-red-400 p-2 rounded-lg text-xs border border-red-100 dark:border-red-900/20 line-through opacity-60 break-all overflow-hidden">
                                    ${formatter(oldVal)}
                                </div>
                                <div class="bg-emerald-50 dark:bg-emerald-900/10 text-emerald-700 dark:text-emerald-400 p-2 rounded-lg text-xs border border-emerald-100 dark:border-emerald-900/20 font-bold break-all overflow-hidden">
                                    ${formatter(newVal)}
                                </div>
                            </div>
                        </div>
                    `;
                }
            }

            if (!hasChanges) {
                html += '<p class="text-center text-gray-500 italic py-4">No hay cambios detallados registrados para esta acción.</p>';
            }

            if (log.notes) {
                html += `
                    <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-900/20 rounded-xl">
                        <p class="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-1">NOTAS</p>
                        <p class="text-xs text-blue-700 dark:text-blue-300 font-medium">${log.notes}</p>
                    </div>
                `;
            }

            html += '</div>';
            content.innerHTML = html;

            document.getElementById('task-history-diff-modal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeHistoryDiff() {
            document.getElementById('task-history-diff-modal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Close on ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeHistoryDiff();
        });

        function showAttachmentHistory(id) {
            const attachments = @json($allAttachments);
            const attachment = attachments.find(a => a.id == id);
            
            if (!attachment) return;

            document.getElementById('history-filename').innerText = attachment.file_name;
            const content = document.getElementById('history-content');
            content.innerHTML = '';

            if (attachment.logs && attachment.logs.length > 0) {
                const logs = attachment.logs.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                
                let html = '<div class="space-y-6 relative ml-4 border-l-2 border-gray-100 dark:border-gray-800 pl-8">';
                
                logs.forEach(log => {
                    const date = new Date(log.created_at).toLocaleString();
                    const actionColors = {
                        'upload': 'bg-emerald-500',
                        'download': 'bg-blue-500',
                        'view': 'bg-violet-500',
                        'rename': 'bg-amber-500',
                        'move_to_drive': 'bg-violet-500',
                        'delete': 'bg-red-500'
                    };
                    
                    const actionIcons = {
                        'upload': '<path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v8" />',
                        'download': '<path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />',
                        'view': '<path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />',
                        'rename': '<path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />',
                        'move_to_drive': '<path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" /><path d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />'
                    };

                    const actionLabel = {
                        'upload': 'Subida de archivo',
                        'download': 'Descarga realizada',
                        'view': 'Visualización online',
                        'rename': 'Cambio de nombre',
                        'move_to_drive': 'Traspaso a Google Drive',
                        'delete': 'Eliminación'
                    };

                    let metaHtml = '';
                    if (log.metadata) {
                        if (log.metadata.original_name) metaHtml = `<p class="mt-1 text-gray-400">Origen: <span class="font-bold text-gray-600 dark:text-gray-300 italic">${log.metadata.original_name}</span></p>`;
                        if (log.metadata.old_name) metaHtml = `<p class="mt-1 text-gray-400">De <span class="line-through">${log.metadata.old_name}</span> a <span class="font-bold text-gray-600 dark:text-gray-300">${log.metadata.new_name}</span></p>`;
                    }

                    html += `
                        <div class="relative">
                            <div class="absolute -left-[45px] top-1 w-8 h-8 rounded-full border-4 border-white dark:border-gray-900 ${actionColors[log.action] || 'bg-gray-400'} flex items-center justify-center text-white shadow-sm ring-4 ring-gray-100 dark:ring-gray-800/30">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    ${actionIcons[log.action] || '<circle cx="12" cy="12" r="10" />'}
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-tight">${actionLabel[log.action]}</span>
                                    <span class="text-[10px] bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 px-2 py-0.5 rounded-full font-bold tabular-nums">${date}</span>
                                </div>
                                <div class="flex items-center gap-2 group">
                                    <img src="${log.user ? (log.user.profile_photo_path ? '/storage/' + log.user.profile_photo_path : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(log.user.name) + '&color=7F9CF5&background=EBF4FF') : 'https://ui-avatars.com/api/?name=?&color=7F9CF5&background=EBF4FF'}" 
                                        class="w-5 h-5 rounded-full object-cover shadow-sm" alt="${log.user?.name || '?'}">
                                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-tighter">${log.user?.name || 'Sistema'}</span>
                                    ${log.ip_address ? `<span class="text-[9px] text-gray-400 font-mono bg-gray-50 dark:bg-gray-800/50 px-1.5 py-0.5 rounded">IP: ${log.ip_address}</span>` : ''}
                                </div>
                                ${metaHtml}
                            </div>
                        </div>
                    `;
                });
                
                html += '</div>';
                content.innerHTML = html;
            } else {
                content.innerHTML = '<div class="text-center py-10"><p class="text-gray-500 italic">No hay movimientos registrados para este archivo todavía.</p></div>';
            }

            document.getElementById('attachment-history-modal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeAttachmentHistory() {
            document.getElementById('attachment-history-modal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Close on ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeAttachmentHistory();
        });

        function copyTaskJson() {
            const btn = event.currentTarget;
            
            btn.disabled = true;
            btn.style.opacity = '0.5';

            fetch("{{ route('teams.activities.export-json', [$team, $task]) }}", {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => r.json())
            .then(data => {
                const jsonStr = JSON.stringify(data, null, 4);
                navigator.clipboard.writeText(jsonStr).then(() => {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Copiado!',
                        text: 'El JSON de la tarea está en tu portapapeles.',
                        timer: 2000,
                        showConfirmButton: false,
                        background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                        color: document.documentElement.classList.contains('dark') ? '#f1f5f9' : '#1e293b',
                    });
                });
            })
            .catch(e => {
                console.error(e);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo obtener el JSON de la tarea.'
                });
            })
            .finally(() => {
                btn.disabled = false;
                btn.style.opacity = '1';
            });
        }
    </script>
        @include('tasks.partials.import-modal-script')
    @endpush

    @push('scripts')
{{-- ============================================================
     BARRA FLOTANTE DE ACCIONES RÁPIDAS
     ============================================================ --}}
<div id="task-floating-bar"
     x-data="floatingDraggable"
     @mousedown="startDrag"
     @touchstart.passive="startDrag"
     @window:mousemove="drag"
     @window:touchmove.passive="drag"
     @window:mouseup="stopDrag"
     @window:touchend="stopDrag"
     class="fixed bottom-6 left-1/2 -translate-x-1/2 translate-y-4 z-[800] flex items-center gap-2 px-4 py-2.5 bg-white/93 dark:bg-gray-900/93 backdrop-blur-xl border border-gray-100 dark:border-gray-800 rounded-2xl shadow-2xl opacity-0 pointer-events-none transition-all duration-300 whitespace-nowrap cursor-move"
     :class="isDragging ? 'scale-105 shadow-[0_20px_50px_rgba(0,0,0,0.2)]' : ''">

    {{-- Volver --}}
    <a href="{{ $backUrl ?? route('teams.dashboard', $team) }}"
       style="display:flex;align-items:center;gap:0.375rem;font-size:0.75rem;font-weight:700;color:#6b7280;padding:0.375rem 0.75rem;border-radius:0.625rem;text-decoration:none;transition:all 0.15s ease;"
       onmouseover="this.style.color='#7c3aed';this.style.background='#f5f3ff'"
       onmouseout="this.style.color='#6b7280';this.style.background='transparent'">
        <svg style="width:1rem;height:1rem;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        <span>{{ __('navigation.back') ?? 'Volver' }}</span>
    </a>

    <div style="width:1px;height:1.25rem;background:#e5e7eb;flex-shrink:0"></div>

    {{-- Título truncado --}}
    <span style="font-size:0.75rem;font-weight:900;color:#1f2937;max-width:200px;overflow:hidden;text-overflow:ellipsis;">
        {{ $task->title }}
    </span>

    @can('update', $task)
        <div style="width:1px;height:1.25rem;background:#e5e7eb;flex-shrink:0"></div>
        <a href="{{ route('teams.tasks.edit', [$team, $task]) }}"
           style="display:flex;align-items:center;gap:0.375rem;font-size:0.75rem;font-weight:700;color:#fff;background:#7c3aed;padding:0.375rem 0.75rem;border-radius:0.625rem;text-decoration:none;transition:background 0.15s ease;"
           onmouseover="this.style.background='#6d28d9'"
           onmouseout="this.style.background='#7c3aed'">
            <svg style="width:1rem;height:1rem;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            <span>{{ __('tasks.edit') }}</span>
        </a>
    @endcan
</div>

<script>
    function savePrivateNotes() {
        const content = document.getElementById('reply-content-private').value;
        const button = event.currentTarget;
        const originalText = button.innerHTML;
        
        button.disabled = true;
        button.innerHTML = '<svg class="animate-spin h-3 w-3 mr-2 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> GUARDANDO...';

        fetch("{{ route('teams.activities.private-notes.update', [$team, $personalInstance]) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ content: content })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: '{{ __("Notas guardadas correctamente") }}',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '{{ __("No se pudieron guardar las notas") }}'
            });
        })
        .finally(() => {
            button.disabled = false;
            button.innerHTML = originalText;
        });
    }
</script>

    <script>
        (function() {
            const bar = document.getElementById('task-floating-bar');
            if (!bar) return;

            const checkScroll = (e) => {
                let scrollY = 0;
                if (e && e.target && e.target !== document) {
                    scrollY = e.target.scrollTop;
                } else {
                    scrollY = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
                }
                
                if (scrollY > 150) {
                    bar.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none');
                    bar.classList.add('opacity-100', 'translate-y-0', 'pointer-events-auto');
                } else {
                    bar.classList.add('opacity-0', 'translate-y-4', 'pointer-events-none');
                    bar.classList.remove('opacity-100', 'translate-y-0', 'pointer-events-auto');
                }
            };

            window.addEventListener('scroll', checkScroll, { passive: true, capture: true });
            
            setTimeout(() => checkScroll(), 100);
        })();
    </script>
@endpush

    @if(isset($mappedActivity) && $mappedActivity)
    <!-- MODAL DE CONVERSIÓN DE ACTIVIDAD -->
    <div x-data="{ 
        show: false, 
        targetType: 'task',
        types: [
            { id: 'task', label: 'Tarea General', icon: '📝', desc: 'Actividad estándar con seguimiento de urgencia, carga cognitiva y gestión de progreso.' },
            { id: 'document', label: 'Documento / Base de Conocimiento', icon: '📄', desc: 'Registro centrado en la documentación colaborativa y control de versiones.' },
            { id: 'link', label: 'Enlace / Recurso Externo', icon: '🔗', desc: 'Referencia a un sitio web, herramienta externa o repositorio de información.' },
            { id: 'meeting', label: 'Reunión / Encuentro', icon: '🤝', desc: 'Evento programado con modalidad (remota/presencial), duración y ubicación.' },
            { id: 'reminder', label: 'Recordatorio / Notificación', icon: '🔔', desc: 'Aviso puntual con canales de distribución (Email, Push, etc.).' }
        ]
    }"
        @open-convert-activity-modal.window="show = true"
        x-show="show"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        @click.self="show = false">
        
        <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] w-full max-w-2xl overflow-hidden shadow-2xl border border-gray-100 dark:border-gray-800 transform transition-all text-left flex flex-col max-h-[90vh]"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95">
            
            <div class="px-8 py-6 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-violet-50/50 dark:bg-violet-950/20">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xl">✨</span>
                        <h3 class="text-lg font-black text-gray-900 dark:text-white uppercase tracking-tight">
                            {{ __('Convertir Actividad') }}
                        </h3>
                    </div>
                    <p class="text-xs text-gray-500 font-medium">
                        {{ __('Transforma esta actividad a un nuevo tipo. La original quedará archivada como deprecada para mantener el rastro de auditoría.') }}
                    </p>
                </div>
                <button @click="show = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors p-2 rounded-2xl hover:bg-white dark:hover:bg-gray-800 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form action="{{ route('teams.activities.convert', [$team, $mappedActivity]) }}" method="POST" class="flex flex-col flex-1 overflow-hidden m-0">
                @csrf
                <div class="p-8 overflow-y-auto custom-scrollbar flex-1">
                    
                    <div class="mb-6 p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/50">
                        <div class="flex gap-3">
                            <div class="text-amber-500 mt-0.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-amber-800 dark:text-amber-400 uppercase tracking-widest mb-1">Aviso de Integridad</h4>
                                <p class="text-xs text-amber-700/80 dark:text-amber-500/80 leading-relaxed font-medium">
                                    Solo se conservarán los metadatos y atributos compatibles con el nuevo tipo seleccionado. Los campos exclusivos de "{{ $mappedActivity->type_label ?? 'Tarea' }}" que no existan en el nuevo esquema se descartarán en la nueva versión.
                                </p>
                            </div>
                        </div>
                    </div>

                    <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-3 ml-1">
                        Selecciona el nuevo tipo de actividad
                    </label>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <template x-for="type in types" :key="type.id">
                            <label class="relative flex cursor-pointer rounded-2xl border bg-white dark:bg-gray-800/50 p-4 shadow-sm focus:outline-none transition-all group hover:border-violet-300 dark:hover:border-violet-700"
                                :class="targetType === type.id ? 'border-violet-500 ring-2 ring-violet-500/20 bg-violet-50/30 dark:bg-violet-900/10' : 'border-gray-200 dark:border-gray-700'">
                                
                                <input type="radio" name="type" :value="type.id" x-model="targetType" class="sr-only">
                                
                                <div class="flex w-full items-start justify-between gap-4">
                                    <div class="flex items-start gap-4">
                                        <div class="text-2xl mt-1 p-2 rounded-xl bg-gray-50 dark:bg-gray-800 group-hover:scale-110 transition-transform" 
                                             :class="targetType === type.id ? 'bg-violet-100 dark:bg-violet-900/50 text-violet-600 dark:text-violet-400' : ''"
                                             x-text="type.icon">
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-gray-900 dark:text-white mb-1" x-text="type.label"></span>
                                            <span class="text-[10px] text-gray-500 dark:text-gray-400 font-medium leading-relaxed" x-text="type.desc"></span>
                                        </div>
                                    </div>
                                    
                                    <div class="shrink-0 text-violet-500" x-show="targetType === type.id">
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                            <circle cx="12" cy="12" r="10" stroke-opacity="0.2" fill="currentColor" fill-opacity="0.1"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4" />
                                        </svg>
                                    </div>
                                </div>
                            </label>
                        </template>
                    </div>

                </div>

                <div class="px-8 py-5 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/20 flex items-center justify-between gap-3 shrink-0">
                    <button type="button" @click="show = false" class="px-6 py-2.5 text-xs font-bold text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm active:scale-95">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-2.5 text-xs font-bold text-white bg-violet-600 hover:bg-violet-500 rounded-xl shadow-lg shadow-violet-500/25 transition-all active:scale-95 flex items-center gap-2">
                        <span>Proceder a la Conversión</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
