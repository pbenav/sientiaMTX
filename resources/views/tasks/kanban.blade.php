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
                <x-kanban.today-column 
                    :activities="$todayActivities" 
                    :$quadrantConfig 
                    :$team 
                />


                <!-- Add Column Area -->
                <x-kanban.board-footer :$team />
            </div>
        </div>

        <!-- Completed Tasks Zone (Archive) -->
        <x-kanban.completed-zone 
            :$completedTasks 
            :$hideCompleted 
            :$team 
        />
    </div>

    @include('components.kanban.board-scripts')

</x-app-layout>
