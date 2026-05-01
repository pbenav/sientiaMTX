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

        /* Responsive Kanban column widths */
        @media (max-width: 640px) {
            .kanban-column {
                width: 320px !important;
                flex: 0 0 320px !important;
                max-height: calc(100vh - 80px) !important;
                min-height: 800px;
            }
            .kanban-column .column-title {
                font-size: 11px;
                letter-spacing: 0.05em;
            }
        }

        @media (min-width: 641px) and (max-width: 1024px) {
            /* iPad/Tablet: smaller columns to fit 2-3 without horizontal scroll */
            .kanban-column {
                width: 300px !important;
                flex: 0 0 300px !important;
                max-height: calc(100vh - 100px) !important;
                min-height: 950px;
            }
            .kanban-column .column-title {
                font-size: 10px;
                letter-spacing: 0.05em;
            }
        }

        @media (min-width: 1025px) {
            /* Desktop: standard 380px columns */
            .kanban-column {
                width: 380px !important;
                flex: 0 0 380px !important;
                max-height: calc(100vh - 120px) !important;
                min-height: 1200px;
            }
            .kanban-column .column-title {
                font-size: 13px;
            }
        }

        /* Shadow indicator for scrolling */
        .task-list {
            position: relative;
            scrollbar-gutter: stable;
        }

        .kanban-column::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 40px;
            background: linear-gradient(to top, var(--col-bg), transparent);
            pointer-events: none;
            opacity: 0.8;
            border-bottom-left-radius: 2.5rem;
            border-bottom-right-radius: 2.5rem;
            z-index: 10;
        }
    </style>

    <x-slot name="header">
        <div class="flex flex-col xl:flex-row xl:items-start justify-between gap-6">
            <div class="flex items-start gap-4 min-w-0 flex-1">
                <a href="{{ route('teams.index') }}"
                    class="mt-1 p-2.5 bg-gray-50 dark:bg-gray-800/50 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-2xl transition-all shadow-sm border border-gray-100 dark:border-gray-700/50 shrink-0"
                    title="{{ __('navigation.back') ?? 'Volver' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div class="min-w-0 flex-1">
                    @include('teams.partials.breadcrumb')
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight">
                        {{ __('navigation.kanban') }}
                    </h1>
                </div>
            </div>
        </div>

        <!-- View Switcher Sub-Header -->
        <div class="mt-4 mb-2 flex w-full">
            @include('teams.partials.view-switcher')
        </div>

        <!-- Action Buttons Row -->
        <div class="flex items-center gap-3 shrink-0 mt-2 border-t border-gray-100 dark:border-gray-800 pt-3">
            @include('teams.partials.header-actions')
        </div>
    </x-slot>

    <div class="py-4 space-y-4">
        <!-- Filters -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm mx-4 xl:mx-8">
            <form action="{{ route('teams.kanban', $team) }}" method="GET" class="flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[200px] relative">
                    <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="{{ __('tasks.search') }}..." class="w-full pl-4 pr-4 py-2 {{ $filters['search'] ? 'bg-violet-50 dark:bg-violet-900/20 ring-2 ring-violet-500/30' : 'bg-gray-50 dark:bg-gray-800' }} border-none rounded-xl text-sm focus:ring-2 focus:ring-violet-500/50 dark:text-white transition-all">
                </div>
                <select name="status" onchange="this.form.submit()" class="w-40 {{ $filters['status'] ? 'bg-violet-50 dark:bg-violet-900/20 ring-2 ring-violet-500/30 text-violet-700 dark:text-violet-300' : 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400' }} border-none rounded-xl text-xs font-bold uppercase py-2 cursor-pointer">
                    <option value="">{{ __('tasks.status') }}</option>
                    @foreach (['pending', 'in_progress', 'completed', 'cancelled', 'blocked'] as $status)
                        <option value="{{ $status }}" {{ $filters['status'] === $status ? 'selected' : '' }}>{{ __("tasks.statuses.{$status}") }}</option>
                    @endforeach
                </select>
                <select name="priority" onchange="this.form.submit()" class="w-40 {{ $filters['priority'] ? 'bg-violet-50 dark:bg-violet-900/20 ring-2 ring-violet-500/30 text-violet-700 dark:text-violet-300' : 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400' }} border-none rounded-xl text-xs font-bold uppercase py-2 cursor-pointer">
                    <option value="">{{ __('tasks.priority') }}</option>
                    @foreach(['low','medium','high','critical'] as $p)
                        <option value="{{$p}}" {{$filters['priority']==$p?'selected':''}}>{{__("tasks.priorities.{$p}")}}</option>
                    @endforeach
                </select>

                <!-- Assigned To -->
                <select name="assigned_to" onchange="this.form.submit()" class="w-40 {{ $filters['assigned_to'] ? 'bg-violet-50 dark:bg-violet-900/20 ring-2 ring-violet-500/30 text-violet-700 dark:text-violet-300' : 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400' }} border-none rounded-xl text-xs font-bold uppercase py-2 cursor-pointer">
                    <option value="">{{ __('tasks.assigned_to') }}</option>
                    @foreach ($members as $member)
                        <option value="{{ $member->id }}" {{ $filters['assigned_to'] == $member->id ? 'selected' : '' }}>{{ $member->name }}</option>
                    @endforeach
                </select>

                <!-- Skill Filter -->
                <select name="skill_id" onchange="this.form.submit()" class="w-40 {{ $filters['skill_id'] ? 'bg-violet-50 dark:bg-violet-900/20 ring-2 ring-violet-500/30 text-violet-700 dark:text-violet-300' : 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400' }} border-none rounded-xl text-xs font-bold uppercase py-2 cursor-pointer">
                    <option value="">{{ __('tasks.skill') ?? 'Especialidad' }}</option>
                    @foreach($skills as $skill)
                        <option value="{{ $skill->id }}" {{ $filters['skill_id'] == $skill->id ? 'selected' : '' }}>{{ $skill->name }}</option>
                    @endforeach
                </select>

                <!-- Type Filter -->
                <select name="type" onchange="this.form.submit()" class="w-40 {{ ($filters['type'] ?? null) ? 'bg-violet-50 dark:bg-violet-900/20 ring-2 ring-violet-500/30 text-violet-700 dark:text-violet-300' : 'bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400' }} border-none rounded-xl text-xs font-bold uppercase py-2 cursor-pointer">
                    <option value="">{{ __('tasks.type') }}</option>
                    <option value="template" {{ ($filters['type'] ?? null) === 'template' ? 'selected' : '' }}>{{ __('tasks.template') }}</option>
                    <option value="instance" {{ ($filters['type'] ?? null) === 'instance' ? 'selected' : '' }}>{{ __('tasks.subtask') }}</option>
                    <option value="plain" {{ ($filters['type'] ?? null) === 'plain' ? 'selected' : '' }}>{{ __('tasks.task') }}</option>
                </select>

                @if(collect($filters ?? [])->filter()->isNotEmpty())
                    <a href="{{ route('teams.kanban', [$team, 'reset_filters' => 1]) }}" class="text-xs font-bold text-red-500 uppercase tracking-widest">{{ __('tasks.clear_filters') }}</a>
                @endif
            </form>
        </div>

    @php
        $quadrantConfig = $team->getQuadrantConfig();
    @endphp

    <div class="flex flex-col min-h-[calc(100vh-180px)] w-full pb-10">
        <!-- Kanban Board Container -->
        <div class="flex-1 overflow-x-auto pb-6 pt-2 no-scrollbar">
            <div class="flex h-full gap-2 sm:gap-3 md:gap-4 px-2 sm:px-3 md:px-6 w-max min-w-full items-start pb-4" id="kanban-board">
                @foreach($columns as $column)
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
                                        {{ count($column->tasks->filter(fn($t) => !$t->is_archived)) }}
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
                            @foreach($column->tasks->filter(fn($t) => !$t->is_archived) as $task)
                                @php
                                    $quadrant = $task->getQuadrant($task);
                                    $qCfg = $quadrantConfig[$quadrant] ?? null;
                                @endphp
                                <div x-data="{ 
                                         taskId: {{ $task->id }},
                                         get isWorking() { return Alpine.store('timer').activeTaskId == this.taskId }
                                     }"
                                     :class="isWorking ? 'ring-2 ring-violet-500 shadow-xl shadow-violet-500/20 bg-violet-50/30 dark:bg-violet-900/10' : ({{ $task->status === 'completed' ? 'true' : 'false' }} ? 'bg-gray-50/50 dark:bg-gray-900/50 grayscale-[0.3]' : 'bg-white dark:bg-gray-900')"
                                     class="backdrop-blur-sm rounded-xl sm:rounded-2xl shadow-md border-l-[5px] sm:border-l-[6px] p-2.5 sm:p-3.5 md:p-4 cursor-grab active:cursor-grabbing hover:shadow-2xl hover:-translate-y-1.5 transition-all duration-300 group relative animate-card-appear border-t border-r border-b border-gray-100/50 dark:border-gray-800/50"
                                     data-task-id="{{ $task->id }}"
                                     style="border-left-color: {{ $qCfg['color'] ?? '#d1d5db' }}; animation-delay: {{ $loop->index * 50 }}ms; will-change: transform, opacity;">
                                    
                                    <!-- Card Content -->
                                    <div class="flex items-start justify-between gap-2 mb-2">
                                        <div class="flex flex-col gap-1 flex-1">
                                            <a href="{{ route('teams.tasks.show', [$team, $task]) }}" class="text-sm font-black text-gray-900 dark:text-gray-50 leading-tight hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                                {{ $task->title }}
                                            </a>
                                            <div class="flex flex-wrap gap-1 mt-0.5">
                                                @if ($task->is_template)
                                                    <span class="px-1.5 py-0.5 rounded-md bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300 text-[8px] font-black uppercase tracking-tighter border border-violet-200 dark:border-violet-700/50 shadow-sm">
                                                        {{ __('tasks.plan_master') }}
                                                    </span>
                                                @endif
                                                @if ($task->assigned_user_id === auth()->id() && $task->parent_id)
                                                    <span class="px-1.5 py-0.5 rounded-md bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 text-[8px] font-black uppercase tracking-tighter border border-emerald-200 dark:border-emerald-700/50 shadow-sm">
                                                        {{ __('tasks.your_execution') }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="shrink-0 flex flex-col items-end gap-1.5">
                                            <div class="flex items-center gap-1.5">
                                                @include('tasks.partials.task-timer-button')
                                                @if(!$task->is_archived)
                                                    <button onclick="archiveTask({{ $task->id }})" 
                                                            class="p-1 rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/40 text-gray-400 hover:text-emerald-600 transition-colors"
                                                            title="{{ __('tasks.mark_as_completed_and_archive') }}">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </button>
                                                @endif
                                            </div>
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
                                        <div class="text-[11px] text-gray-600 dark:text-gray-300 line-clamp-2 mb-3 opacity-80 leading-relaxed">
                                            {{ \Illuminate\Support\Str::limit(html_entity_decode(strip_tags(\Illuminate\Support\Str::markdown($task->description))), 120) }}
                                        </div>
                                    @endif

                                    <!-- Progress Slider -->
                                    <div class="mt-4 space-y-1.5">
                                        <div class="flex items-center justify-between text-[10px] font-bold">
                                            <span class="text-gray-400 uppercase tracking-widest">{{ __('tasks.progress') }}</span>
                                            <span class="text-violet-600 dark:text-violet-400 progress-label">{{ $task->progress_percentage }}%</span>
                                        </div>
                                        <input type="range" min="0" max="100" value="{{ $task->progress_percentage }}" 
                                               class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-violet-600 progress-slider z-30 relative"
                                               data-task-id="{{ $task->id }}">
                                    </div>

                                    <!-- Card Footer -->
                                    <div class="mt-4 flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-700/50">
                                        <div class="flex items-center gap-2">
                                            <div class="flex -space-x-2">
                                                @if($task->assignedUser)
                                                    <img src="{{ $task->assignedUser->profile_photo_url }}" alt="{{ $task->assignedUser->name }}" 
                                                        class="w-6 h-6 rounded-full object-cover border-2 border-white dark:border-gray-800 shadow-sm" title="{{ $task->assignedUser->name }}">
                                                @elseif($task->assignedTo->count() > 0)
                                                    @foreach($task->assignedTo->take(3) as $user)
                                                        <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" 
                                                            class="w-6 h-6 rounded-full object-cover border-2 border-white dark:border-gray-800 shadow-sm" title="{{ $user->name }}">
                                                    @endforeach
                                                @endif
                                            </div>
                                            @if($task->assignedUser)
                                                <span class="text-[9px] font-bold text-gray-500 dark:text-gray-400 truncate max-w-[80px]">
                                                    {{ $task->assignedUser->id === auth()->id() ? __('Tú') : explode(' ', $task->assignedUser->name)[0] }}
                                                </span>
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

        <!-- Completed Tasks Zone (Archive) -->
        <div class="mt-16 w-full px-4 pb-16" x-show="{{ $hideCompleted ? 'false' : 'true' }}" x-transition>
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
                                        class="p-1 rounded-lg opacity-60 group-hover:opacity-100 hover:bg-indigo-100 dark:hover:bg-indigo-900/40 text-gray-400 hover:text-indigo-600 transition-all border border-transparent hover:border-indigo-200"
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
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Task Sorting
            let isDragging = false;
            const taskLists = document.querySelectorAll('.task-list');
            taskLists.forEach(el => {
                    new Sortable(el, {
                        group: 'tasks',
                        animation: 200,
                        ghostClass: 'bg-violet-100/50',
                        chosenClass: 'scale-105',
                        dragClass: 'shadow-2xl',
                        delay: 150, // Slightly faster for better feel
                        delayOnTouchOnly: true,
                        touchStartThreshold: 10, // More forgiving for scroll
                        filter: 'button, input, select, .progress-slider', // Removed 'a' so we can drag by title
                        preventOnFilter: false, // CRITICAL: This allows the slider to be dragged normally
                        onStart: function() {
                            isDragging = true;
                        },
                    onEnd: function(evt) {
                        setTimeout(() => { isDragging = false; }, 200);

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

            // Global click interceptor for Kanban
            document.addEventListener('click', function(e) {
                if (isDragging) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }, true);

            // Column Sorting
            const board = document.getElementById('kanban-board');
            new Sortable(board, {
                animation: 150,
                handle: '.column-handle',
                ghostClass: 'opacity-50',
                delay: 150,
                delayOnTouchOnly: true,
                touchStartThreshold: 10,
                onStart: function() {
                    isDragging = true;
                },
                onEnd: function() {
                    setTimeout(() => { isDragging = false; }, 200);
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
                // Get all tasks in the target column to sync the entire order
                const columnEl = document.querySelector(`.task-list[data-column-id="${columnId}"]`);
                if (!columnEl) return;

                const tasks = Array.from(columnEl.querySelectorAll('[data-task-id]')).map((el, index) => {
                    return {
                        id: el.dataset.taskId,
                        kanban_order: index
                    };
                });

                fetch(`{{ route('teams.kanban.tasks.order', $team) }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        column_id: columnId,
                        tasks: tasks,
                        moved_task_id: taskId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.progress !== null) {
                        const card = document.querySelector(`[data-task-id="${taskId}"]`);
                        if (card) {
                            const slider = card.querySelector('input[type="range"]');
                            if (slider) slider.value = data.progress;
                            const label = card.querySelector('.progress-text') || card.querySelector('.progress-label');
                            if (label) label.innerText = `${data.progress}%`;
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
            const completedZone = document.getElementById('completed-tasks-zone');
            if (completedZone) {
                new Sortable(completedZone, {
                    group: 'tasks',
                    animation: 200,
                    delay: 200,
                    delayOnTouchOnly: true,
                    touchStartThreshold: 5,
                    onAdd: function(evt) {
                        const taskId = evt.item.dataset.taskId;
                        archiveTask(taskId);
                    }
                });
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
                    if (data.success && data.kanban_column_id) {
                        const card = document.querySelector(`[data-task-id="${taskId}"]`);
                        if (card) {
                            const currentList = card.closest('.task-list');
                            if (currentList && currentList.dataset.columnId != data.kanban_column_id) {
                                const targetList = document.querySelector(`.task-list[data-column-id="${data.kanban_column_id}"]`);
                                if (targetList) {
                                    card.style.opacity = '0';
                                    setTimeout(() => {
                                        targetList.appendChild(card);
                                        card.style.opacity = '1';
                                    }, 150);
                                }
                            }
                        }
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
        .task-list {
            position: relative;
            scrollbar-gutter: stable;
            scroll-behavior: smooth;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
        }

        .kanban-column::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: linear-gradient(to top, var(--col-bg), transparent);
            pointer-events: none;
            opacity: 0.9;
            border-bottom-left-radius: 2.5rem;
            border-bottom-right-radius: 2.5rem;
            z-index: 10;
        }
    </style>
    @endpush
</x-app-layout>
