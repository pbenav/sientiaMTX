@props(['team' => null])

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let isDragging = false;

        // Task Sorting
        const taskLists = document.querySelectorAll('.task-list');
        taskLists.forEach(el => {
            new Sortable(el, {
                group: 'tasks',
                animation: 200,
                ghostClass: 'bg-violet-100/50',
                chosenClass: 'scale-105',
                dragClass: 'shadow-2xl',
                delay: 150,
                delayOnTouchOnly: true,
                touchStartThreshold: 10,
                filter: 'button, input, select, .progress-slider',
                preventOnFilter: false,
                onStart: function() {
                    isDragging = true;
                },
                onEnd: function(evt) {
                    setTimeout(() => { isDragging = false; }, 200);
                    const taskId = evt.item.dataset.taskId;
                    const newColumnId = evt.to.dataset.columnId;
                    const isCompletedZone = evt.to.id === 'completed-tasks-zone';
                    if (isCompletedZone) {
                        kanbanArchiveTask(taskId);
                    } else {
                        kanbanUpdateTaskPosition(taskId, newColumnId, evt.newIndex);
                    }
                }
            });
        });

        // Global click interceptor
        document.addEventListener('click', function(e) {
            if (isDragging) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }, true);

        // Column Sorting
        const board = document.getElementById('kanban-board');
        if (board) {
            new Sortable(board, {
                animation: 150,
                handle: '.column-handle',
                ghostClass: 'opacity-50',
                delay: 150,
                delayOnTouchOnly: true,
                touchStartThreshold: 10,
                onStart: function() { isDragging = true; },
                onEnd: function() {
                    setTimeout(() => { isDragging = false; }, 200);
                    kanbanUpdateColumnsOrder();
                }
            });
        }

        // Progress Slider Integration
        document.querySelectorAll('.progress-slider').forEach(slider => {
            if (slider.disabled) return;
            const fill = slider.parentElement.querySelector('.progress-fill');
            const thumb = slider.parentElement.querySelector('.progress-thumb');
            const label = slider.parentElement.parentElement.querySelector('.progress-label');
            if (fill) fill.style.width = slider.value + '%';
            if (thumb) {
                thumb.style.left = slider.value + '%';
                thumb.style.marginLeft = slider.value == 0 ? '8px' : (slider.value == 100 ? '-8px' : '0');
            }
            if (label) label.textContent = slider.value + '%';

            slider.addEventListener('input', function() {
                const lbl = this.parentElement.parentElement.querySelector('.progress-label');
                if (lbl) lbl.textContent = this.value + '%';
                const fl = this.parentElement.querySelector('.progress-fill');
                if (fl) fl.style.width = this.value + '%';
                const th = this.parentElement.querySelector('.progress-thumb');
                if (th) {
                    th.style.left = this.value + '%';
                    th.style.marginLeft = this.value == 0 ? '8px' : (this.value == 100 ? '-8px' : '0');
                }
            });

            slider.addEventListener('change', function() {
                kanbanUpdateTaskProgress(this.dataset.taskId, this.value);
            });
        });

        // Completed tasks drop zone
        const completedZone = document.getElementById('completed-tasks-zone');
        if (completedZone) {
            new Sortable(completedZone, {
                group: 'tasks',
                animation: 200,
                delay: 200,
                delayOnTouchOnly: true,
                touchStartThreshold: 5,
                onAdd: function(evt) {
                    kanbanArchiveTask(evt.item.dataset.taskId);
                }
            });
        }

        // --- API Functions ---
        window.archiveTask = window.kanbanArchiveTask = function(taskId) {
            const card = document.querySelector(`[data-task-id="${taskId}"]`);
            if (card) { card.style.transform = 'scale(0.9)'; card.style.opacity = '0'; setTimeout(() => card.remove(), 300); }
            fetch(`{{ route('teams.activities.move', [$team, ':taskId']) }}`.replace(':taskId', taskId), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ status: 'completed', progress_percentage: 100, is_archived: true })
            }).catch(error => console.error('Error:', error));
        };

        window.archiveAllCompleted = window.kanbanArchiveAllCompleted = function(columnId) {
            Swal.fire({
                title: '¿Archivar todas las tareas?',
                text: 'Se ocultarán del tablón principal todas las tareas completadas de esta columna.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, archivar todas',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#10b981',
                background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#f3f4f6' : '#111827',
                customClass: {
                    popup: 'rounded-[2rem] border-0 shadow-2xl',
                    confirmButton: 'rounded-xl px-6 py-2.5 text-[11px] font-black uppercase tracking-widest',
                    cancelButton: 'rounded-xl px-6 py-2.5 text-[11px] font-black uppercase tracking-widest'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const columnEl = document.querySelector(`.task-list[data-column-id="${columnId}"]`);
                    if (!columnEl) return;
                    const taskCards = columnEl.querySelectorAll('[data-task-id][data-completed="1"]');
                    const totalTasks = taskCards.length;
                    if (totalTasks === 0) return;
                    let completedCount = 0;
                    taskCards.forEach((card) => {
                        const taskId = card.dataset.taskId;
                        card.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                        card.style.transform = 'scale(0.9)';
                        card.style.opacity = '0';
                        setTimeout(() => card.remove(), 300);
                        fetch(`{{ route('teams.activities.move', [$team, ':taskId']) }}`.replace(':taskId', taskId), {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify({ status: 'completed', progress_percentage: 100, is_archived: true })
                        }).then(() => {
                            completedCount++;
                            if (completedCount === totalTasks) setTimeout(() => window.location.reload(), 500);
                        }).catch(err => console.error(err));
                    });
                }
            });
        };

        window.unarchiveTask = window.kanbanUnarchiveTask = function(taskId) {
            const row = document.querySelector(`button[onclick*="unarchiveTask(${taskId})"]`)?.closest('div');
            if (row) { row.style.opacity = '0'; setTimeout(() => row.remove(), 300); }
            fetch(`{{ route('teams.activities.move', [$team, ':taskId']) }}`.replace(':taskId', taskId), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ is_archived: false })
            }).then(response => response.json()).then(data => {
                if (data.success) location.reload();
            }).catch(error => console.error('Error:', error));
        };

        window.updateTaskPosition = window.kanbanUpdateTaskPosition = function(taskId, columnId, order) {
            const columnEl = document.querySelector(`.task-list[data-column-id="${columnId}"]`);
            if (!columnEl) return;
            const tasks = Array.from(columnEl.querySelectorAll('[data-task-id]')).map((el, index) => ({
                id: el.dataset.taskId,
                kanban_order: index
            }));
            fetch(`{{ route('teams.kanban.tasks.order', $team) }}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ column_id: columnId, tasks: tasks, moved_task_id: taskId })
            }).then(response => response.json()).then(data => {
                if (data.success && data.progress !== null) {
                    const card = document.querySelector(`[data-task-id="${taskId}"]`);
                    if (card) {
                        const slider = card.querySelector('input[type="range"]');
                        if (slider) {
                            slider.value = data.progress;
                            const fl = slider.parentElement.querySelector('.progress-fill');
                            if (fl) fl.style.width = data.progress + '%';
                            const th = slider.parentElement.querySelector('.progress-thumb');
                            if (th) {
                                th.style.left = data.progress + '%';
                                th.style.marginLeft = data.progress == 0 ? '8px' : (data.progress == 100 ? '-8px' : '0');
                            }
                        }
                        const label = card.querySelector('.progress-text') || card.querySelector('.progress-label');
                        if (label) label.innerText = `${data.progress}%`;
                    }
                }
            }).catch(error => console.error('Error:', error));
        };

        window.updateTaskProgress = window.kanbanUpdateTaskProgress = function(taskId, progress) {
            console.log(`[Kanban] Sending progress update for Task ${taskId} -> ${progress}%`);
            fetch(`{{ route('teams.activities.move', [$team, ':taskId']) }}`.replace(':taskId', taskId), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ progress_percentage: progress })
            }).then(response => response.json()).then(data => {
                console.log(`[Kanban] Received response for Task ${taskId}:`, data);
                if (data.success && data.kanban_column_id) {
                    const card = document.querySelector(`[data-task-id="${taskId}"]`);
                    if (card) {
                        const currentList = card.closest('.task-list');
                        const targetColId = String(data.kanban_column_id);
                        if (currentList && currentList.dataset.columnId !== targetColId) {
                            const targetList = document.querySelector(`.task-list[data-column-id="${targetColId}"]`);
                            if (targetList) {
                                card.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                                card.style.opacity = '0';
                                card.style.transform = 'scale(0.95)';
                                setTimeout(() => {
                                    targetList.appendChild(card);
                                    const ccEl = currentList.parentElement.querySelector('.column-title').nextElementSibling;
                                    if (ccEl) ccEl.textContent = Math.max(0, parseInt(ccEl.textContent || '0') - 1);
                                    const tcEl = targetList.parentElement.querySelector('.column-title').nextElementSibling;
                                    if (tcEl) tcEl.textContent = parseInt(tcEl.textContent || '0') + 1;
                                    void card.offsetWidth;
                                    card.style.opacity = '1';
                                    card.style.transform = 'scale(1)';
                                }, 300);
                            } else {
                                console.warn(`[Kanban] Target list ${targetColId} not found, reloading page...`);
                                window.location.reload();
                            }
                        }
                    }
                } else if (!data.success) {
                    console.error('[Kanban] Server returned failure:', data.error || data.message);
                    Swal.fire({ icon: 'error', title: 'Error al actualizar', text: data.error || data.message || 'Error desconocido.', toast: true, position: 'bottom-end', showConfirmButton: false, timer: 3000 });
                    setTimeout(() => window.location.reload(), 2000);
                }
            }).catch(error => console.error('[Kanban] Fetch Error:', error));
        };

        window.updateColumnsOrder = window.kanbanUpdateColumnsOrder = function() {
            const columns = Array.from(document.querySelectorAll('[data-column-id]')).map((el, index) => ({
                id: el.dataset.columnId,
                order_index: index + 1
            }));
            fetch(`{{ route('teams.kanban.columns.order', $team) }}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ columns: columns })
            }).catch(error => console.error('Error:', error));
        };

        window.updateColumn = window.kanbanUpdateColumn = function(columnId, data) {
            fetch(`{{ route('teams.kanban.columns.update', [$team, ':columnId']) }}`.replace(':columnId', columnId), {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify(data)
            }).catch(error => console.error('Error:', error));
        };

        window.updateColumnTitle = window.kanbanUpdateColumnTitle = function(columnId, title) {
            kanbanUpdateColumn(columnId, { title: title });
        };

        window.updateColumnColor = window.kanbanUpdateColumnColor = function(columnId, color) {
            kanbanUpdateColumn(columnId, { color: color });
            const col = document.querySelector(`[data-column-id="${columnId}"]`);
            if (col) col.style.backgroundColor = color;
        };

        window.createNewColumn = window.kanbanCreateNewColumn = function() {
            Swal.fire({
                title: '{{ __('Nueva Columna') }}',
                input: 'text',
                inputLabel: '{{ __('Título de la columna') }}',
                inputPlaceholder: '{{ __('Ej: En revisión, Testing...') }}',
                showCancelButton: true,
                confirmButtonText: '{{ __('Crear') }}',
                cancelButtonText: '{{ __('Cancelar') }}',
                confirmButtonColor: '#7c3aed',
                background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#ffffff' : '#111827',
                preConfirm: (title) => {
                    if (!title) { Swal.showValidationMessage('El título es obligatorio'); }
                    return title;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`{{ route('teams.kanban.columns.store', $team) }}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ title: result.value })
                    }).then(response => response.json()).then(data => {
                        if (data.success) location.reload();
                    }).catch(error => console.error('Error:', error));
                }
            });
        };

        window.deleteColumn = window.kanbanDeleteColumn = function(columnId) {
            Swal.fire({
                title: '¿Eliminar columna?',
                text: 'Las tareas de esta columna se moverán automáticamente a la columna "Pendiente".',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#ef4444',
                background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#ffffff' : '#111827',
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`{{ route('teams.kanban.columns.destroy', [$team, ':columnId']) }}`.replace(':columnId', columnId), {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    }).then(response => response.json()).then(data => {
                        if (data.success) location.reload();
                    }).catch(error => console.error('Error:', error));
                }
            });
        };
    });
</script>
