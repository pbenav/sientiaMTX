@props(['column', 'team', 'quadrantConfig' => []])

<div class="shrink-0 flex flex-col rounded-[2.5rem] border-2 border-black/10 dark:border-white/10 transition-all duration-500 shadow-xl hover:shadow-2xl animate-fade-in group relative overflow-hidden kanban-column" 
     style="--col-bg: {{ $column->color ?? '#f9fafb' }}; border-color: {{ ($column->color ?? '#f9fafb') }}40;"
     data-column-id="{{ $column->id }}">
    <!-- Accent Top Bar -->
    <div class="absolute top-0 left-0 right-0 h-2 kanban-column-accent" style="background-color: {{ $column->color ?? '#f9fafb' }};"></div>
    
    <!-- Column Header -->
    <div class="p-3 sm:p-3.5 md:p-4 flex flex-col gap-1.5 sm:gap-2 cursor-grab active:cursor-grabbing column-handle">
        <div class="flex items-center justify-between gap-1">
            <div class="flex items-center gap-1.5 sm:gap-2 min-w-0 flex-1">
                <h3 class="font-black text-gray-900 dark:text-white uppercase tracking-[0.15em] text-[13px] column-title truncate" 
                    contenteditable="true" 
                    onblur="updateColumnTitle({{ $column->id }}, this.innerText)"
                    onclick="event.stopPropagation()">
                    {{ $column->title }}
                </h3>
                <span class="px-1.5 sm:px-2.5 py-0.5 sm:py-1 rounded-lg sm:rounded-xl bg-black/5 dark:bg-white/10 text-[10px] sm:text-[11px] font-black text-gray-700 dark:text-gray-300 border border-black/5 dark:border-white/5 shadow-sm shrink-0">
                    {{ count($column->activities->filter(fn($t) => !$t->is_archived)) }}
                </span>

                <!-- Trash Icon for Custom Columns -->
                @if($column->type === 'custom')
                    <button onclick="deleteColumn({{ $column->id }})" 
                            class="p-1 sm:p-1.5 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-500 hover:bg-red-500 hover:text-white transition-all shadow-sm border border-red-200/50 dark:border-red-500/20 shrink-0"
                            title="Eliminar columna">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 sm:h-3.5 w-3 sm:w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                @endif

                <!-- Archive All Completed Button -->
                @if(count($column->activities->filter(fn($t) => !$t->is_archived && ($t->isCompleted() || $column->type === 'done'))) > 0)
                    <button onclick="archiveAllCompleted({{ $column->id }})" 
                            class="p-1 sm:p-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 hover:bg-emerald-600 hover:text-white transition-all shadow-sm border border-emerald-200/50 dark:border-emerald-500/20 shrink-0 group/btn"
                            title="{{ __('Archivar/ocultar todas las tareas completadas de la columna') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 sm:h-3.5 w-3 sm:w-3.5 group-hover/btn:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                @endif
            </div>
            <div class="flex items-center gap-1.5 sm:gap-2 transition-all duration-300" onclick="event.stopPropagation()">
                <!-- Palette Icon -->
                <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 sm:h-3 w-2.5 sm:w-3 text-gray-400 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-3" />
                </svg>
                @foreach(['#fee2e2', '#dbeafe', '#dcfce7', '#fef3c7'] as $hex)
                    <button onclick="updateColumnColor({{ $column->id }}, '{{ $hex }}')" 
                            class="w-3 sm:w-3.5 md:w-4 h-3 sm:h-3.5 md:h-4 rounded-full border border-gray-300/30 dark:border-white/10 hover:scale-110 active:scale-95 transition-all shadow-sm"
                            style="background-color: {{ $hex }};"
                            title="Cambiar color"></button>
                @endforeach
                <!-- Custom Color Picker -->
                <div class="relative flex items-center justify-center">
                    <button onclick="this.nextElementSibling.click()" 
                            class="w-2.5 sm:w-3 md:w-3.5 h-2.5 sm:h-3 md:h-3.5 rounded-full border-2 border-dashed border-gray-400 dark:border-gray-500 hover:scale-125 hover:border-violet-500 transition-all flex items-center justify-center bg-transparent"
                            title="Color personalizado">
                        <span class="text-[6px] sm:text-[7px] md:text-[8px] font-bold text-gray-500">+</span>
                    </button>
                    <input type="color" class="absolute inset-0 opacity-0 w-full h-full cursor-pointer" 
                           onchange="updateColumnColor({{ $column->id }}, this.value)" 
                           value="{{ $column->color ?? '#f9fafb' }}">
                </div>
            </div>
            <div class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 sm:h-3.5 md:h-4 w-3 sm:w-3.5 md:w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Tasks List -->
    <div class="flex-1 overflow-y-auto px-1.5 sm:px-2 pb-2.5 sm:pb-3.5 md:pb-4 space-y-2 sm:space-y-2.5 md:space-y-3 task-list custom-scrollbar" data-column-id="{{ $column->id }}">
        @foreach($column->activities as $index => $task)
            <x-kanban.task-card :task="$task" :team="$team" :column="$column" :quadrantConfig="$quadrantConfig" :cardIndex="$index" />
        @endforeach
    </div>
</div>
