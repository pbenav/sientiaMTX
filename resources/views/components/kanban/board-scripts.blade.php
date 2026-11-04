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
                const val = this.value;
                const taskId = this.dataset.taskId;
                document.querySelectorAll(`[data-task-id="${taskId}"]`).forEach(c => {
                    const s = c.querySelector('input.progress-slider');
                    if (s && s !== this) s.value = val;
                    const lbl = c.querySelector('.progress-label');
                    if (lbl) lbl.textContent = val + '%';
                    const fl = c.querySelector('.progress-fill');
                    if (fl) {
                        fl.style.width = val + '%';
                        if (val == 100) {
                            fl.classList.remove('bg-violet-500');
                            fl.classList.add('bg-emerald-500');
                        } else {
                            fl.classList.add('bg-violet-500');
                            fl.classList.remove('bg-emerald-500');
                        }
                    }
                    const th = c.querySelector('.progress-thumb');
                    if (th) {
                        th.style.left = val + '%';
                        th.style.marginLeft = val == 0 ? '8px' : (val == 100 ? '-8px' : '0');
                    }
                });
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
            const cards = document.querySelectorAll(`[data-task-id="${taskId}"]`);
            cards.forEach(card => {
                card.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                card.style.transform = 'scale(0.9)';
                card.style.opacity = '0';
                setTimeout(() => card.remove(), 300);
            });
            fetch(`{{ route('teams.activities.move', [$team, ':taskId']) }}`.replace(':taskId', taskId), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ status: 'completed', progress_percentage: 100, is_archived: true })
            }).catch(error => console.error('Error:', error));
        };

        window.archiveAllCompleted = window.kanbanArchiveAllCompleted = function(columnId) {
            Swal.fire({
                title: '{{ __('Archive all tasks?') }}',
                text: '{{ __('All completed tasks in this column will be hidden from the main board.') }}',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '{{ __('Yes, archive all') }}',
                cancelButtonText: '{{ __('Cancel') }}',
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
                    const taskCards = columnEl.querySelectorAll('[data-task-id]');
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
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify({ status: 'completed', progress_percentage: 100, is_archived: true })
                        }).then(() => {
                            completedCount++;
                            if (completedCount === totalTasks) setTimeout(() => window.location.reload(), 500);
                        }).catch(err => console.error(err));
                    });
                }
            });
        };
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
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ column_id: columnId, tasks: tasks, moved_task_id: taskId })
            }).then(response => response.json()).then(data => {
                if (data.success && data.progress !== null) {
                    document.querySelectorAll(`[data-task-id="${taskId}"]`).forEach(card => {
                        const slider = card.querySelector('input.progress-slider');
                        if (slider) {
                            slider.value = data.progress;
                            const fl = slider.parentElement.querySelector('.progress-fill');
                            if (fl) {
                                fl.style.width = data.progress + '%';
                                if (data.progress == 100) {
                                    fl.classList.remove('bg-violet-500');
                                    fl.classList.add('bg-emerald-500');
                                } else {
                                    fl.classList.add('bg-violet-500');
                                    fl.classList.remove('bg-emerald-500');
                                }
                            }
                            const th = slider.parentElement.querySelector('.progress-thumb');
                            if (th) {
                                th.style.left = data.progress + '%';
                                th.style.marginLeft = data.progress == 0 ? '8px' : (data.progress == 100 ? '-8px' : '0');
                            }
                        }
                        const archiveBtn = card.querySelector('.archive-task-btn');
                        if (archiveBtn) {
                            if (data.progress == 100) {
                                archiveBtn.classList.remove('opacity-0', 'group-hover:opacity-100', 'text-gray-400');
                                archiveBtn.classList.add('bg-emerald-50', 'dark:bg-emerald-900/30', 'text-emerald-600', 'shadow-sm', 'border', 'border-emerald-200/50');
                            }
                        }
                        const label = card.querySelector('.progress-text') || card.querySelector('.progress-label');
                        if (label) label.innerText = `${data.progress}%`;
                    });
                }
            }).catch(error => console.error('Error:', error));
        };

        window.updateTaskProgress = window.kanbanUpdateTaskProgress = function(taskId, progress) {
            console.log(`[Kanban] Sending progress update for Task ${taskId} -> ${progress}%`);
            fetch(`{{ route('teams.activities.move', [$team, ':taskId']) }}`.replace(':taskId', taskId), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ progress_percentage: progress })
            }).then(response => response.json()).then(data => {
                console.log(`[Kanban] Received response for Task ${taskId}:`, data);
                if (data.success && data.kanban_column_id) {
                    const cards = document.querySelectorAll(`[data-task-id="${taskId}"]`);
                    cards.forEach(card => {
                        const currentList = card.closest('.task-list');
                        const targetColId = String(data.kanban_column_id);
                        if (currentList && currentList.dataset.columnId !== targetColId && currentList.dataset.columnId !== 'today') {
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
                            }
                        }
                    });
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
                    Swal.fire({ icon: 'error', title: '{{ __('Error updating') }}', text: data.error || data.message || '{{ __('Unknown error.') }}', toast: true, position: 'bottom-end', showConfirmButton: false, timer: 3000 });
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
            if (col) {
                col.style.setProperty('--col-accent', color);
                const accent = col.querySelector('.kanban-column-accent');
                if (accent) accent.style.backgroundColor = color;
            }
        };

        window.createNewColumn = window.kanbanCreateNewColumn = function() {
            Swal.fire({
                title: '{{ __('New Column') }}',
                input: 'text',
                inputLabel: '{{ __('Column Title') }}',
                inputPlaceholder: '{{ __('e.g., In Review, Testing...') }}',
                showCancelButton: true,
                confirmButtonText: '{{ __('Create') }}',
                cancelButtonText: '{{ __('Cancel') }}',
                confirmButtonColor: '#7c3aed',
                background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#ffffff' : '#111827',
                preConfirm: (title) => {
                    if (!title) { Swal.showValidationMessage('{{ __('The title is required') }}'); }
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
                title: '{{ __('Delete column?') }}',
                text: '{{ __('Tasks in this column will automatically be moved to the Pending column.') }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '{{ __('Yes, delete') }}',
                cancelButtonText: '{{ __('Cancel') }}',
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
