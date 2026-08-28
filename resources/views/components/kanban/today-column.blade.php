@props(['activities' => [], 'quadrantConfig' => [], 'team' => null, 'column' => null, 'showProgress' => true, 'showBadges' => true, 'showFooter' => true])

@if(is_array($activities) ? !empty($activities) : $activities->isNotEmpty())
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
                    {{ is_array($activities) ? count($activities) : $activities->count() }}
                </span>
            </div>
        </div>
    </div>

    <!-- Tasks List -->
    <div class="flex-1 overflow-y-auto px-1.5 sm:px-2 pb-2.5 sm:pb-3.5 md:pb-4 space-y-2 sm:space-y-2.5 md:space-y-3 task-list custom-scrollbar" data-column-id="today">
        @foreach($activities as $task)
            <x-kanban.task-card 
                :$task 
                :$team 
                :$column 
                :quadrantConfig="$quadrantConfig"
                :$showProgress 
                :$showBadges 
                :$showFooter 
                isTodayCard
                :cardIndex="$loop->index"
            />
        @endforeach
    </div>
</div>
@endif
