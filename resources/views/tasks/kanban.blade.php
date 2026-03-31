<x-app-layout maxWidth="max-w-full">
    <style>
        .kanban-column {
            background-color: var(--col-bg);
            transition: all 0.5s ease;
        }
        .dark .kanban-column {
            background-color: rgba(17, 24, 39, 0.4) !important;
            backdrop-filter: blur(8px);
            border-color: rgba(255, 255, 255, 0.05) !important;
        }
        .dark .kanban-column-accent {
            background-color: var(--col-bg);
            opacity: 0.3;
        }
    </style>

    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex items-start gap-3 min-w-0 flex-1">
                <a href="{{ route('teams.index') }}"
                    class="mt-1 p-2 bg-gray-50 dark:bg-gray-800 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-xl transition-all shadow-sm shrink-0"
                    title="{{ __('navigation.back') ?? 'Volver' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div class="min-w-0 flex-1">
                    @include('teams.partials.breadcrumb')
                    <h1 class="text-2xl font-black text-gray-900 dark:text-white heading truncate select-none">
                        {{ __('navigation.kanban') }}
                    </h1>
                </div>
            </div>

            @include('teams.partials.header-actions')
        </div>
    </x-slot>

    @php
        $quadrantConfig = $team->getQuadrantConfig();
    @endphp

    <div class="flex flex-col min-h-[calc(100vh-180px)] h-full">
        <!-- Kanban Board Container -->
        <div class="flex-1 overflow-x-auto pb-6 pt-4 custom-scrollbar overflow-y-hidden">
            <div class="flex h-full gap-6 px-6 min-w-full items-stretch pb-4" id="kanban-board">
                @foreach($columns as $column)
                    <div class="flex-1 flex flex-col min-h-[700px] h-full rounded-[2.5rem] border-2 border-black/10 dark:border-white/10 transition-all duration-500 shadow-xl hover:shadow-2xl animate-fade-in group relative overflow-hidden kanban-column" 
                         style="--col-bg: {{ $column->color ?? '#f9fafb' }}; border-color: {{ ($column->color ?? '#f9fafb') }}40; flex: 1 1 0%; min-width: 350px; max-width: 600px;"
                         data-column-id="{{ $column->id }}">
                        <!-- Accent Top Bar -->
                        <div class="absolute top-0 left-0 right-0 h-2 kanban-column-accent" style="background-color: {{ $column->color ?? '#f9fafb' }};"></div>
                        
                        <!-- Column Header -->
                        <div class="p-4 flex flex-col gap-2 cursor-grab active:cursor-grabbing column-handle">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 group/title">
                                    <h3 class="font-black text-gray-900 dark:text-white uppercase tracking-[0.15em] text-[13px] column-title" 
                                        contenteditable="true" 
                                        onblur="updateColumnTitle({{ $column->id }}, this.innerText)"
                                        onclick="event.stopPropagation()">
                                        {{ $column->title }}
                                    </h3>
                                    <span class="px-2.5 py-1 rounded-xl bg-black/5 dark:bg-white/10 text-[11px] font-black text-gray-700 dark:text-gray-300 border border-black/5 dark:border-white/5 shadow-sm">
                                        {{ count($column->tasks->filter(fn($t) => !$t->is_archived)) }}
                                    </span>
                                </div>
                                        <div class="flex items-center gap-2" onclick="event.stopPropagation()">
                                            <div class="flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-all duration-300">
                                                <!-- Trash Icon for Custom Columns -->
                                                @if($column->type === 'custom')
                                                    <button onclick="deleteColumn({{ $column->id }})" 
                                                            class="p-1 px-1.5 rounded-lg hover:bg-red-500 hover:text-white text-red-500/50 transition-all mr-1"
                                                            title="Eliminar columna">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                @endif

                                                <!-- Palette Icon -->
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-gray-400 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-3" />
                                        </svg>
                                        @foreach(['#fee2e2', '#dbeafe', '#dcfce7', '#fef3c7', '#f5f3ff', '#e0f2fe'] as $hex)
                                            <button onclick="updateColumnColor({{ $column->id }}, '{{ $hex }}')" 
                                                    class="w-3.5 h-3.5 rounded-full border border-gray-300/50 dark:border-gray-600/50 hover:scale-125 transition-transform shadow-sm"
                                                    style="background-color: {{ $hex }};"
                                                    title="Cambiar color"></button>
                                        @endforeach
                                        <!-- Custom Color Picker -->
                                        <div class="relative flex items-center justify-center">
                                            <button onclick="this.nextElementSibling.click()" 
                                                    class="w-3.5 h-3.5 rounded-full border-2 border-dashed border-gray-400 dark:border-gray-500 hover:scale-125 hover:border-violet-500 transition-all flex items-center justify-center bg-transparent"
                                                    title="Color personalizado">
                                                <span class="text-[8px] font-bold text-gray-500">+</span>
                                            </button>
                                            <input type="color" class="absolute inset-0 opacity-0 w-full h-full cursor-pointer" 
                                                   onchange="updateColumnColor({{ $column->id }}, this.value)" 
                                                   value="{{ $column->color ?? '#f9fafb' }}">
                                        </div>
                                    </div>
                                    <div class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tasks List -->
                        <div class="flex-1 overflow-y-auto px-2 pb-4 space-y-3 task-list custom-scrollbar" data-column-id="{{ $column->id }}">
                            @foreach($column->tasks->filter(fn($t) => !$t->is_archived) as $task)
                                @php
                                    $quadrant = $task->getQuadrant($task);
                                    $qCfg = $quadrantConfig[$quadrant] ?? null;
                                @endphp
                                <div class="bg-white dark:bg-gray-900 backdrop-blur-sm rounded-2xl shadow-md border-l-[6px] p-4 cursor-grab active:cursor-grabbing hover:shadow-2xl hover:-translate-y-1.5 transition-all duration-300 group relative animate-card-appear border-t border-r border-b border-gray-100/50 dark:border-gray-800/50"
                                     data-task-id="{{ $task->id }}"
                                     style="border-left-color: {{ $qCfg['color'] ?? '#d1d5db' }}; animation-delay: {{ $loop->index * 50 }}ms">
                                    
                                    <!-- Card Content -->
                                    <div class="flex items-start justify-between gap-2 mb-2">
                                        <a href="{{ route('teams.tasks.show', [$team, $task]) }}" class="text-sm font-black text-gray-900 dark:text-gray-50 leading-tight hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                            {{ $task->title }}
                                        </a>
                                        <div class="shrink-0 flex items-center gap-1.5">
                                            @if(!$task->is_archived)
                                                <button onclick="archiveTask({{ $task->id }})" 
                                                        class="p-1 rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/40 text-gray-400 hover:text-emerald-600 transition-colors"
                                                        title="{{ __('tasks.mark_as_completed_and_archive') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </button>
                                            @endif
                                            <a href="{{ route('teams.dashboard', $team) }}" 
                                               onclick="event.stopPropagation()"
                                               class="text-[9px] font-black px-1.5 py-0.5 rounded uppercase tracking-tighter hover:ring-2 hover:ring-violet-500 transition-all cursor-pointer shadow-sm"
                                               style="background-color: {{ $qCfg['color'] ?? '#d1d5db' }}40; color: {{ $qCfg['color'] ?? '#374151' }};"
                                               title="{{ __('teams.view_dashboard') ?? 'Ver Eisenhower' }}">
                                                Q{{ $quadrant }}
                                            </a>
                                        </div>
                                    </div>

                                    @if($task->description)
                                        <p class="text-[11px] text-gray-600 dark:text-gray-300 line-clamp-2 mb-3">
                                            {{ $task->description }}
                                        </p>
                                    @endif

                                    <!-- Progress Slider -->
                                    <div class="mt-4 space-y-1.5">
                                        <div class="flex items-center justify-between text-[10px] font-bold">
                                            <span class="text-gray-400 uppercase tracking-widest">{{ __('tasks.progress') }}</span>
                                            <span class="text-violet-600 dark:text-violet-400 progress-label">{{ $task->progress_percentage }}%</span>
                                        </div>
                                        <input type="range" min="0" max="100" value="{{ $task->progress_percentage }}" 
                                               class="w-full h-1.5 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-violet-600 progress-slider"
                                               data-task-id="{{ $task->id }}"
                                               onmousedown="event.stopPropagation()">
                                    </div>

                                    <!-- Card Footer -->
                                    <div class="mt-4 flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-700/50">
                                        <div class="flex -space-x-2">
                                            @if($task->assignedUser)
                                                <div class="w-6 h-6 rounded-full bg-violet-500 flex items-center justify-center text-[10px] text-white font-bold border-2 border-white dark:border-gray-800 shadow-sm" title="{{ $task->assignedUser->name }}">
                                                    {{ strtoupper(substr($task->assignedUser->name, 0, 1)) }}
                                                </div>
                                            @elseif($task->assignedTo->count() > 0)
                                                @foreach($task->assignedTo->take(3) as $user)
                                                    <div class="w-6 h-6 rounded-full bg-indigo-500 flex items-center justify-center text-[10px] text-white font-bold border-2 border-white dark:border-gray-800 shadow-sm" title="{{ $user->name }}">
                                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                                    </div>
                                                @endforeach
                                                @if($task->assignedTo->count() > 3)
                                                    <div class="w-6 h-6 rounded-full bg-gray-400 flex items-center justify-center text-[8px] text-white font-bold border-2 border-white dark:border-gray-800" title="...">
                                                        +{{ $task->assignedTo->count() - 3 }}
                                                    </div>
                                                @endif
                                            @else
                                                <span class="text-[10px] text-gray-400 italic font-medium">{{ __('tasks.unassigned') }}</span>
                                            @endif
                                        </div>
                                        
                                        @if($task->due_date)
                                            <div class="flex items-center gap-1 text-[10px] font-bold {{ $task->due_date->isPast() && $task->status !== 'completed' ? 'text-red-500' : 'text-gray-400' }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2v12a2 2 0 002 2z" />
                                                </svg>
                                                {{ $task->due_date->format('d M') }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <!-- Add Column Area -->
                <div class="min-w-[60px] flex items-center justify-center h-full mr-10" id="add-column-area">
                    <button onclick="createNewColumn()" class="w-12 h-12 rounded-2xl bg-white dark:bg-gray-950 shadow-sm flex items-center justify-center border-2 border-dashed border-gray-200 dark:border-gray-800 text-gray-400 hover:text-violet-500 hover:border-violet-500 hover:scale-110 hover:shadow-lg transition-all duration-300" title="{{ __('Añadir Columna') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Completed Tasks Zone -->
        <div class="mt-16 w-full px-4 pb-16">
            <div class="bg-gray-50/50 dark:bg-gray-950/20 border border-gray-200 dark:border-gray-800/40 rounded-[2.5rem] overflow-hidden shadow-sm dark:shadow-none transition-colors">
                <div class="px-8 py-5 border-b border-gray-100 dark:border-white/5 bg-gray-50 dark:bg-gray-900/10 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="p-1.5 rounded-full border border-emerald-500/20 bg-emerald-500/5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-500/60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h3 class="text-[12px] font-black uppercase tracking-[0.25em] text-gray-900 dark:text-gray-100">
                            {{ __('teams.completed_tasks') }}
                        </h3>
                    </div>
                    <span class="text-xs font-bold text-gray-400 dark:text-gray-600">{{ count($completedTasks) }}</span>
                </div>

                <div class="min-h-[140px] p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" id="completed-tasks-zone" data-column-type="done">
                    @forelse($completedTasks as $task)
                        <div class="px-4 py-3 flex items-center gap-4 bg-white dark:bg-gray-900/20 hover:bg-gray-100 dark:hover:bg-white/10 group transition-all rounded-2xl border border-gray-100 dark:border-white/5 shadow-sm dark:shadow-none relative overflow-hidden">
                            <div class="w-1.5 h-1.5 rounded-full shrink-0 bg-emerald-500/20 z-10 relative"></div>
                            <span class="flex-1 text-[12px] text-gray-400 dark:text-gray-600 line-through truncate group-hover:text-gray-600 dark:group-hover:text-gray-400 transition-colors cursor-pointer"
                                  onclick="window.location.href='{{ route('teams.tasks.show', [$team, $task]) }}'">
                                {{ $task->title }}
                            </span>
                            <button onclick="unarchiveTask({{ $task->id }})" 
                                    class="p-1 rounded-lg opacity-0 group-hover:opacity-100 hover:bg-indigo-100 dark:hover:bg-indigo-900/40 text-gray-400 hover:text-indigo-600 transition-all border border-transparent hover:border-indigo-200"
                                    title="{{ __('tasks.restore') ?? 'Desarchivar' }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    @empty
                        <div class="col-span-full py-20 text-center text-xs text-gray-500 italic">
                            {{ __('No hay tareas completadas archivadas.') }}
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Task Sorting
            const taskLists = document.querySelectorAll('.task-list');
            taskLists.forEach(el => {
                new Sortable(el, {
                    group: 'tasks',
                    animation: 200,
                    ghostClass: 'bg-violet-100/50',
                    chosenClass: 'scale-105',
                    dragClass: 'shadow-2xl',
                    filter: '.progress-slider',
                    preventOnFilter: false,
                    onEnd: function(evt) {
                        const taskId = evt.item.dataset.taskId;
                        const newColumnId = evt.to.dataset.columnId;
                        const isCompletedZone = evt.to.id === 'completed-tasks-zone';
                        const newIndex = evt.newIndex;

                        if (isCompletedZone) {
                            archiveTask(taskId);
                        } else {
                            updateTaskPosition(taskId, newColumnId, newIndex);
                        }
                    }
                });
            });

            // Column Sorting
            const board = document.getElementById('kanban-board');
            new Sortable(board, {
                animation: 150,
                handle: '.column-handle',
                ghostClass: 'opacity-50',
                onEnd: function() {
                    updateColumnsOrder();
                }
            });

            // Progress Slider Integration
            document.querySelectorAll('.progress-slider').forEach(slider => {
                slider.addEventListener('input', function() {
                    const label = this.parentElement.querySelector('.progress-label');
                    label.textContent = this.value + '%';
                });

                slider.addEventListener('change', function() {
                    const taskId = this.dataset.taskId;
                    const progress = this.value;
                    
                    updateTaskProgress(taskId, progress);
                });
            });

            function updateTaskPosition(taskId, columnId, order) {
                fetch(`{{ route('teams.tasks.kanban.update', [$team, ':taskId']) }}`.replace(':taskId', taskId), {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        kanban_column_id: columnId,
                        kanban_order: order
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // If bidirectional update triggered a move in backend (e.g. status change)
                        // we might need to refresh or update the card UI
                        // For now, let's just show a toast if needed
                        console.log('Task moved successfully');
                        
                        // If progress was updated automatically by backend due to move
                        // Update the slider on the card
                        const card = document.querySelector(`[data-task-id="${taskId}"]`);
                        if (card) {
                            const slider = card.querySelector('.progress-slider');
                            const label = card.querySelector('.progress-label');
                            if (slider) slider.value = data.progress;
                            if (label) label.textContent = data.progress + '%';
                            
                            // Possibly move to another column if it was changed by backend?
                            // Actually the user MOVED it, so we just update the card's state.
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
            }

            window.archiveTask = function(taskId) {
                fetch(`{{ route('teams.tasks.move', [$team, ':taskId']) }}`.replace(':taskId', taskId), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        status: 'completed',
                        progress_percentage: 100,
                        is_archived: true
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload(); 
                    }
                })
                .catch(error => console.error('Error:', error));
            }

            window.unarchiveTask = function(taskId) {
                fetch(`{{ route('teams.tasks.move', [$team, ':taskId']) }}`.replace(':taskId', taskId), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        is_archived: false
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload(); 
                    }
                })
                .catch(error => console.error('Error:', error));
            }

            // Completed tasks drop zone
            new Sortable(document.getElementById('completed-tasks-zone'), {
                group: 'tasks',
                animation: 200,
                onAdd: function(evt) {
                    const taskId = evt.item.dataset.taskId;
                    archiveTask(taskId);
                }
            });

            function updateTaskProgress(taskId, progress) {
                fetch(`{{ route('teams.tasks.move', [$team, ':taskId']) }}`.replace(':taskId', taskId), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        progress_percentage: progress
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Refresh page if progress move triggered a column change
                        // Alternatively, we could move the DOM element to the new column
                        // To keep it simple and reactive as requested:
                        location.reload(); 
                    }
                })
                .catch(error => console.error('Error:', error));
            }

            function updateColumnsOrder() {
                const columns = Array.from(document.querySelectorAll('[data-column-id]')).map((el, index) => {
                    return {
                        id: el.dataset.columnId,
                        order_index: index + 1
                    };
                });

                fetch(`{{ route('teams.kanban.columns.order', $team) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ columns: columns })
                })
                .catch(error => console.error('Error:', error));
            }

            window.updateColumnTitle = function(columnId, title) {
                updateColumn(columnId, { title: title });
            };

            window.updateColumnColor = function(columnId, color) {
                updateColumn(columnId, { color: color });
                // Update UI immediately
                const col = document.querySelector(`[data-column-id="${columnId}"]`);
                if (col) col.style.backgroundColor = color;
            };

            function updateColumn(columnId, data) {
                fetch(`{{ route('teams.kanban.columns.update', [$team, ':columnId']) }}`.replace(':columnId', columnId), {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(data)
                })
                .catch(error => console.error('Error:', error));
            }

            window.createNewColumn = function() {
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
                        if (!title) {
                            Swal.showValidationMessage('El título es obligatorio');
                        }
                        return title;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`{{ route('teams.kanban.columns.store', $team) }}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ title: result.value })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                location.reload();
                            }
                        })
                        .catch(error => console.error('Error:', error));
                    }
                });
            };

            window.deleteColumn = function(columnId) {
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
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                location.reload();
                            }
                        })
                        .catch(error => console.error('Error:', error));
                    }
                });
            };
        });
    </script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes cardAppear {
            from { opacity: 0; transform: scale(0.95) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }
        .animate-card-appear {
            animation: cardAppear 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 10px;
        }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #1f2937;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #cbd5e1;
        }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #374151;
        }
    </style>
    @endpush
</x-app-layout>
