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
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2 min-w-0">
                <a href="{{ route('teams.index') }}"
                    class="p-1.5 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-lg transition-all shrink-0"
                    title="{{ __('navigation.back') ?? 'Volver' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                @include('teams.partials.breadcrumb')
                <span class="text-gray-300 dark:text-gray-700 mx-1">/</span>
                <h1 class="text-base font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                    </svg>
                    <span class="truncate">{{ __('navigation.kanban') }}</span>
                </h1>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @include('teams.partials.header-toolbar')
            </div>
        </div>

        @include('teams.partials.team-view-nav', ['showCreateActions' => true])

        <div class="mt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <x-demo-hint>
                El tablero Kanban organiza las tareas en columnas personalizables, permitiendo un seguimiento visual del flujo de trabajo (Workflow). Puedes arrastrar tarjetas entre estados y actualizar su progreso rápidamente.
            </x-demo-hint>
        </div>
    </x-slot>

    <div class="py-4 space-y-4">
        <!-- Filters -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm mx-4 xl:mx-8">
            <form action="{{ route('teams.kanban', $team) }}" method="GET" class="flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[200px] relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400 group-focus-within:text-violet-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ $filters['search'] }}" 
                           placeholder="{{ __('tasks.search') }}..." 
                           enterkeyhint="search"
                           class="w-full pl-10 pr-12 py-2.5 {{ $filters['search'] ? 'bg-violet-50/50 dark:bg-violet-900/10 border-violet-300 dark:border-violet-800 ring-2 ring-violet-500/20' : 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700' }} border rounded-xl text-sm outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 dark:text-white transition-all shadow-sm">
                    
                    <div class="absolute inset-y-0 right-2 flex items-center gap-1">
                        @if(!empty($filters['search']))
                            <a href="{{ route('teams.kanban', [$team, 'reset_filters' => 1]) }}" class="p-1.5 text-gray-400 hover:text-red-500 transition-colors" title="{{ __('tasks.clear_search') ?? 'Limpiar búsqueda' }}">
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </a>
                        @endif
                        <button type="submit" class="p-1.5 text-gray-400 hover:text-violet-600 transition-colors" title="{{ __('tasks.search') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </div>
                </div>
                <x-kanban.filter-select
                    name="status"
                    :selected="$filters['status']"
                    placeholder="{{ __('tasks.status') }}"
                    width="w-40"
                >
                    @foreach (['pending', 'in_progress', 'completed', 'cancelled', 'blocked'] as $status)
                        <option value="{{ $status }}" {{ $filters['status'] === $status ? 'selected' : '' }}>{{ __("tasks.statuses.{$status}") }}</option>
                    @endforeach
                </x-kanban.filter-select>

                <x-kanban.filter-select
                    name="priority"
                    :selected="$filters['priority']"
                    placeholder="{{ __('tasks.priority') }}"
                    width="w-40"
                >
                    @foreach(['low','medium','high','critical'] as $p)
                        <option value="{{$p}}" {{$filters['priority']==$p?'selected':''}}>{{__("tasks.priorities.{$p}")}}</option>
                    @endforeach
                </x-kanban.filter-select>

                <x-kanban.filter-select
                    name="assigned_to"
                    :selected="$filters['assigned_to']"
                    placeholder="{{ __('tasks.assigned_to') }}"
                    width="w-40"
                >
                    @foreach ($members as $member)
                        <option value="{{ $member->id }}" {{ $filters['assigned_to'] == $member->id ? 'selected' : '' }}>{{ $member->name }}</option>
                    @endforeach
                </x-kanban.filter-select>

                <x-kanban.filter-select
                    name="expediente_id"
                    :selected="$filters['expediente_id'] ?? null"
                    placeholder="{{ __('Expediente') }}"
                    width="w-40"
                >
                    @foreach($expedientes as $exp)
                        <option value="{{ $exp->id }}" {{ ($filters['expediente_id'] ?? null) == $exp->id ? 'selected' : '' }}>{{ $exp->code }}</option>
                    @endforeach
                </x-kanban.filter-select>

                <x-kanban.filter-select
                    name="skill_id"
                    :selected="$filters['skill_id']"
                    placeholder="{{ __('tasks.skill') ?? 'Especialidad' }}"
                    width="w-40"
                >
                    @foreach($skills as $skill)
                        <option value="{{ $skill->id }}" {{ $filters['skill_id'] == $skill->id ? 'selected' : '' }}>{{ $skill->name }}</option>
                    @endforeach
                </x-kanban.filter-select>

                <x-kanban.filter-select
                    name="type"
                    :selected="$filters['type'] ?? null"
                    placeholder="{{ __('tasks.type') }}"
                    width="w-40"
                >
                    <option value="template" {{ ($filters['type'] ?? null) === 'template' ? 'selected' : '' }}>{{ __('tasks.template') }}</option>
                    <option value="instance" {{ ($filters['type'] ?? null) === 'instance' ? 'selected' : '' }}>{{ __('tasks.subtask') }}</option>
                    <option value="plain" {{ ($filters['type'] ?? null) === 'plain' ? 'selected' : '' }}>{{ __('tasks.task') }}</option>
                </x-kanban.filter-select>

                <x-kanban.filter-select
                    name="urgency"
                    :selected="$filters['urgency'] ?? null"
                    placeholder="{{ __('tasks.urgency') }}"
                    width="w-36"
                >
                    @foreach(['very_low','low','medium','high','very_high'] as $u)
                        <option value="{{ $u }}" {{ ($filters['urgency'] ?? null) === $u ? 'selected' : '' }}>{{ __("tasks.urgencies.{$u}") }}</option>
                    @endforeach
                </x-kanban.filter-select>

                <x-kanban.filter-select
                    name="assignment_mode"
                    :selected="$filters['assignment_mode'] ?? null"
                    placeholder="{{ __('tasks.assignment_mode') }}"
                    width="w-40"
                >
                    <option value="individual" {{ ($filters['assignment_mode'] ?? null) === 'individual' ? 'selected' : '' }}>{{ __('tasks.assignment_individual') }}</option>
                    <option value="shared" {{ ($filters['assignment_mode'] ?? null) === 'shared' ? 'selected' : '' }}>{{ __('tasks.assignment_collaborative') }}</option>
                </x-kanban.filter-select>

                <x-kanban.filter-select
                    name="blocked"
                    :selected="$filters['blocked'] ?? null"
                    placeholder="{{ __('tasks.blocked') }}"
                    width="w-32"
                >
                    <option value="1" {{ ($filters['blocked'] ?? null) === '1' ? 'selected' : '' }}>{{ __('tasks.blocked_yes') }}</option>
                    <option value="0" {{ ($filters['blocked'] ?? null) === '0' ? 'selected' : '' }}>{{ __('tasks.blocked_no') }}</option>
                </x-kanban.filter-select>

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
                    <x-kanban.column :column="$column" :team="$team" :quadrantConfig="$quadrantConfig" />
                @endforeach

                <!-- Today Column -->
                @if($todayActivities->isNotEmpty())
                <div class="shrink-0 flex flex-col rounded-[2.5rem] border-2 border-violet-200 dark:border-violet-800 transition-all duration-500 shadow-xl hover:shadow-2xl animate-fade-in group relative overflow-hidden kanban-column" 
                     style="--col-bg: #f5f3ff; border-color: #8b5cf640;"
                     data-column-id="today">
                    <div class="absolute top-0 left-0 right-0 h-2 kanban-column-accent" style="background-color: #8b5cf6;"></div>
                    
                    <!-- Column Header -->
                    <div class="p-3 sm:p-3.5 md:p-4 flex flex-col gap-1.5 sm:gap-2 cursor-grab active:cursor-grabbing column-handle">
                        <div class="flex items-center justify-between gap-1">
                            <div class="flex items-center gap-1.5 sm:gap-2 min-w-0 flex-1">
                                <h3 class="font-black text-gray-900 dark:text-white uppercase tracking-[0.15em] text-[13px] column-title truncate">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-violet-500 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Hoy
                                </h3>
                                <span class="px-1.5 sm:px-2.5 py-0.5 sm:py-1 rounded-lg sm:rounded-xl bg-violet-500/10 text-[10px] sm:text-[11px] font-black text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-700/50 shadow-sm shrink-0">
                                    {{ $todayActivities->count() }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Tasks List -->
                    <div class="flex-1 overflow-y-auto px-1.5 sm:px-2 pb-2.5 sm:pb-3.5 md:pb-4 space-y-2 sm:space-y-2.5 md:space-y-3 task-list custom-scrollbar" data-column-id="today">
                        @foreach($todayActivities as $task)
                            @php
                                $quadrant = $task->getQuadrant($task);
                                $qCfg = $quadrantConfig[$quadrant] ?? null;
                            @endphp
                            <div x-data="{ 
                                     taskId: {{ $task->id }},
                                     get isWorking() { return Alpine.store('timer').activeTaskId == this.taskId }
                                 }"
                                 :class="isWorking ? 'ring-2 ring-violet-500 shadow-xl shadow-violet-500/20 bg-violet-50/30 dark:bg-violet-900/10' : 'bg-white dark:bg-gray-900'"
                                 class="backdrop-blur-sm rounded-xl sm:rounded-2xl shadow-md border-l-[5px] sm:border-l-[6px] p-2.5 sm:p-3.5 md:p-4 cursor-grab active:cursor-grabbing hover:shadow-2xl hover:-translate-y-1.5 transition-all duration-300 group relative animate-card-appear border-t border-r border-b border-gray-100/50 dark:border-gray-800/50"
                                 data-task-id="{{ $task->id }}"
                                 style="border-left-color: {{ $qCfg['color'] ?? '#d1d5db' }}; animation-delay: {{ $loop->index * 50 }}ms; will-change: transform, opacity;">
                                 
                                 <!-- Card Content (simplified for Today column) -->
                                 <div class="flex items-start justify-between gap-2 mb-2">
                                     <div class="flex flex-col gap-1 flex-1">
                                         <div class="flex items-center gap-1.5 mb-0.5">
                                             <span class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider shadow-sm border flex items-center gap-1"
                                                   style="background-color: {{ $task->type_badge_color }}15; color: {{ $task->type_badge_color }}; border-color: {{ $task->type_badge_color }}30;">
                                                 @if($task->type_icon)
                                                     {!! $task->type_icon !!}
                                                 @endif
                                                 {{ $task->type_label }}
                                             </span>
                                         </div>
                                         <a href="{{ route('teams.activities.show', [$team, $task]) }}" class="text-sm font-black text-gray-900 dark:text-gray-50 leading-tight hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                             {{ $task->title }}
                                         </a>
                                     </div>
                                 </div>
                                 
                                 @if($task->description)
                                     <div class="text-[11px] text-gray-600 dark:text-gray-300 line-clamp-2 mb-3 opacity-80 leading-relaxed">
                                         {{ \Illuminate\Support\Str::limit(html_entity_decode(strip_tags(\Illuminate\Support\Str::markdown($task->description))), 120) }}
                                     </div>
                                 @endif
                                 
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
                                     </div>
                                     
                                     <div class="flex items-center gap-1.5">
                                         @if($task->today_time_minutes > 0)
                                             <span class="text-[9px] font-bold text-violet-600 dark:text-violet-400 flex items-center gap-0.5" title="Tiempo hoy">
                                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                 {{ floor($task->today_time_minutes / 60) }}h{{ $task->today_time_minutes % 60 }}m
                                             </span>
                                         @endif
                                         @if($task->days_remaining !== null)
                                             <div class="flex items-center gap-1 text-[10px] font-bold {{ $task->days_remaining === 0 ? 'text-orange-500' : 'text-gray-400' }}">
                                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2v12a2 2 0 002 2z" />
                                                 </svg>
                                                 @if($task->days_remaining === 0)
                                                     Hoy
                                                 @else
                                                     {{ $task->days_remaining }}d
                                                 @endif
                                             </div>
                                         @endif
                                     </div>
                                 </div>
                             </div>
                         @endforeach
                     </div>
                 </div>
                 @endif

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
                                    onclick="window.location.href='{{ route('teams.activities.show', [$team, $task]) }}'">
                                    {{ $task->title }}
                                </span>
                                <button onclick="unarchiveTask({{ $task->id }})" 
                                        class="p-1 rounded-lg opacity-60 group-hover:opacity-100 hover:bg-violet-100 dark:hover:bg-violet-900/40 text-gray-400 hover:text-violet-600 transition-all border border-transparent hover:border-violet-200"
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
                if(slider.disabled) return;
                
                // Inicializar estado visual por si el navegador restaura 'value' desde caché
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
                    const label = this.parentElement.parentElement.querySelector('.progress-label');
                    if (label) label.textContent = this.value + '%';
                    const fill = this.parentElement.querySelector('.progress-fill');
                    if (fill) fill.style.width = this.value + '%';
                    const thumb = this.parentElement.querySelector('.progress-thumb');
                    if (thumb) {
                        thumb.style.left = this.value + '%';
                        thumb.style.marginLeft = this.value == 0 ? '8px' : (this.value == 100 ? '-8px' : '0');
                    }
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
                            if (slider) {
                                slider.value = data.progress;
                                const fill = slider.parentElement.querySelector('.progress-fill');
                                if (fill) fill.style.width = data.progress + '%';
                                const thumb = slider.parentElement.querySelector('.progress-thumb');
                                if (thumb) {
                                    thumb.style.left = data.progress + '%';
                                    thumb.style.marginLeft = data.progress == 0 ? '8px' : (data.progress == 100 ? '-8px' : '0');
                                }
                            }
                            const label = card.querySelector('.progress-text') || card.querySelector('.progress-label');
                            if (label) label.innerText = `${data.progress}%`;
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
            }



            window.archiveTask = function(taskId) {
                const card = document.querySelector(`[data-task-id="${taskId}"]`);
                if (card) {
                    card.style.transform = 'scale(0.9)';
                    card.style.opacity = '0';
                    setTimeout(() => card.remove(), 300);
                }

                fetch(`{{ route('teams.activities.move', [$team, ':taskId']) }}`.replace(':taskId', taskId), {
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
                .catch(error => console.error('Error:', error));
            }

            window.archiveAllCompleted = function(columnId) {
                Swal.fire({
                    title: '¿Archivar todas las tareas?',
                    text: 'Se ocultarán del tablón principal todas las tareas completadas de esta columna.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, archivar todas',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#10b981',
                    background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                    color: document.documentElement.classList.contains('dark') ? '#f3f4f6' : '#1f2937',
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
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    status: 'completed',
                                    progress_percentage: 100,
                                    is_archived: true
                                })
                            }).then(() => {
                                completedCount++;
                                if (completedCount === totalTasks) {
                                    setTimeout(() => window.location.reload(), 500);
                                }
                            }).catch(err => console.error(err));
                        });
                    }
                });
            };

            window.unarchiveTask = function(taskId) {
                const row = document.querySelector(`button[onclick="unarchiveTask(${taskId})"]`)?.closest('div');
                if (row) {
                    row.style.opacity = '0';
                    setTimeout(() => row.remove(), 300);
                }

                fetch(`{{ route('teams.activities.move', [$team, ':taskId']) }}`.replace(':taskId', taskId), {
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
                        location.reload(); // Reload needed to place it in the correct column since we don't have all column data readily available here
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
                console.log(`[Kanban] Sending progress update for Task ${taskId} -> ${progress}%`);
                fetch(`{{ route('teams.activities.move', [$team, ':taskId']) }}`.replace(':taskId', taskId), {
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
                    console.log(`[Kanban] Received response for Task ${taskId}:`, data);
                    if (data.success && data.kanban_column_id) {
                        const card = document.querySelector(`[data-task-id="${taskId}"]`);
                        if (card) {
                            const currentList = card.closest('.task-list');
                            const targetColId = String(data.kanban_column_id);
                            
                            if (currentList && currentList.dataset.columnId !== targetColId) {
                                console.log(`[Kanban] Task ${taskId} needs to move from col ${currentList.dataset.columnId} to ${targetColId}`);
                                const targetList = document.querySelector(`.task-list[data-column-id="${targetColId}"]`);
                                
                                if (targetList) {
                                    card.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                                    card.style.opacity = '0';
                                    card.style.transform = 'scale(0.95)';
                                    
                                    setTimeout(() => {
                                        targetList.appendChild(card);
                                        
                                        // Update counts
                                        const currentCountEl = currentList.parentElement.querySelector('.column-title').nextElementSibling;
                                        if (currentCountEl) currentCountEl.textContent = Math.max(0, parseInt(currentCountEl.textContent || '0') - 1);
                                        const targetCountEl = targetList.parentElement.querySelector('.column-title').nextElementSibling;
                                        if (targetCountEl) targetCountEl.textContent = parseInt(targetCountEl.textContent || '0') + 1;

                                        // Force reflow
                                        void card.offsetWidth;
                                        card.style.opacity = '1';
                                        card.style.transform = 'scale(1)';
                                        console.log(`[Kanban] Task ${taskId} moved successfully.`);
                                    }, 300);
                                } else {
                                    console.warn(`[Kanban] Target list ${targetColId} not found, reloading page...`);
                                    window.location.reload();
                                }
                            } else {
                                console.log(`[Kanban] Task ${taskId} stays in the same column.`);
                            }
                        } else {
                            console.warn(`[Kanban] Card for task ${taskId} not found in DOM.`);
                        }
                    } else if (!data.success) {
                        console.error('[Kanban] Server returned failure:', data.error || data.message);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al actualizar',
                            text: data.error || data.message || 'Error desconocido al actualizar el progreso.',
                            toast: true,
                            position: 'bottom-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                        // Revert slider visually
                        setTimeout(() => window.location.reload(), 2000);
                    }
                })
                .catch(error => console.error('[Kanban] Fetch Error:', error));
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
