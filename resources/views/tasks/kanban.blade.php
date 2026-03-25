<x-app-layout>
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

    <div class="flex flex-col min-h-[calc(100vh-180px)] h-full">
        <!-- Kanban Board Container -->
        <div class="flex-1 overflow-x-auto pb-10 pt-4 custom-scrollbar">
            <div class="flex h-full gap-5 px-4 min-w-full items-stretch" id="kanban-board">
                @foreach($columns as $column)
                    <div class="flex-1 min-w-[320px] max-w-[480px] flex flex-col min-h-[700px] h-full rounded-[2.5rem] border border-gray-200/40 dark:border-gray-800/40 transition-all duration-500 shadow-sm hover:shadow-md animate-fade-in group" 
                         style="background-color: {{ $column->color ?? 'rgba(249, 250, 251, 0.5)' }};"
                         data-column-id="{{ $column->id }}">
                        <!-- Column Header -->
                        <div class="p-4 flex flex-col gap-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 group/title">
                                    <h3 class="font-bold text-gray-900 dark:text-white uppercase tracking-wider text-xs column-title" 
                                        contenteditable="true" 
                                        onblur="updateColumnTitle({{ $column->id }}, this.innerText)">
                                        {{ $column->title }}
                                    </h3>
                                    <span class="px-2 py-0.5 rounded-full bg-white/50 dark:bg-black/20 text-[10px] font-bold text-gray-500 dark:text-gray-400 border border-gray-200/30 dark:border-gray-700/30">
                                        {{ $column->tasks->count() }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-all duration-300">
                                        <!-- Palette Icon -->
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-gray-400 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-3" />
                                        </svg>
                                        @foreach(['#fef2f2', '#eff6ff', '#f0fdf4', '#fefce8', '#faf5ff', '#f0f9ff'] as $hex)
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
                                    <div class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-grab active:cursor-grabbing column-handle">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tasks List -->
                        <div class="flex-1 overflow-y-auto px-2 pb-4 space-y-3 task-list custom-scrollbar" data-column-id="{{ $column->id }}">
                            @foreach($column->tasks as $task)
                                @php
                                    $quadrant = $task->getQuadrant($task);
                                    $meta = $task->getQuadrantMetadata($quadrant);
                                    $colorClass = match($quadrant) {
                                        1 => 'border-red-500 bg-red-50/30 dark:bg-red-500/5',
                                        2 => 'border-blue-500 bg-blue-50/30 dark:bg-blue-500/5',
                                        3 => 'border-amber-500 bg-amber-50/30 dark:bg-amber-500/5',
                                        4 => 'border-gray-400 bg-gray-100/30 dark:bg-gray-400/5',
                                        default => 'border-gray-200 dark:border-gray-800'
                                    };
                                    $badgeClass = match($quadrant) {
                                        1 => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                        2 => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                        3 => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                        4 => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400',
                                        default => 'bg-gray-100 text-gray-700'
                                    };
                                @endphp
                                <div class="bg-white dark:bg-gray-800/90 backdrop-blur-sm rounded-2xl shadow-sm border-l-4 {{ $colorClass }} p-4 cursor-grab active:cursor-grabbing hover:shadow-lg hover:-translate-y-1 transition-all duration-300 group relative animate-card-appear"
                                     data-task-id="{{ $task->id }}"
                                     style="animation-delay: {{ $loop->index * 50 }}ms">
                                    
                                    <!-- Card Content -->
                                    <div class="flex items-start justify-between gap-2 mb-2">
                                        <a href="{{ route('teams.tasks.show', [$team, $task]) }}" class="text-sm font-bold text-gray-900 dark:text-white leading-tight hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                            {{ $task->title }}
                                        </a>
                                        <div class="shrink-0 flex items-center gap-1">
                                            <span class="text-[9px] font-black px-1.5 py-0.5 rounded {{ $badgeClass }} uppercase tracking-tighter">
                                                Q{{ $quadrant }}
                                            </span>
                                        </div>
                                    </div>

                                    @if($task->description)
                                        <p class="text-[11px] text-gray-500 dark:text-gray-400 line-clamp-2 mb-3">
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
                                               data-task-id="{{ $task->id }}">
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
                    onEnd: function(evt) {
                        const taskId = evt.item.dataset.taskId;
                        const newColumnId = evt.to.dataset.columnId;
                        const newIndex = evt.newIndex;

                        updateTaskPosition(taskId, newColumnId, newIndex);
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
