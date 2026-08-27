@props(['task', 'team', 'column' => null, 'quadrantConfig' => [], 'showProgress' => true, 'showBadges' => true, 'showFooter' => true, 'isTodayCard' => false, 'cardIndex' => 0])

@php
    $quadrant = $task->getQuadrant($task);
    $qCfg = $quadrantConfig[$quadrant] ?? null;
@endphp

<div x-data="{ 
         taskId: {{ $task->id }},
         get isWorking() { return Alpine.store('timer').activeTaskId == this.taskId }
     }"
     :class="isWorking ? 'ring-2 ring-violet-500 shadow-xl shadow-violet-500/20 bg-violet-50/30 dark:bg-violet-900/10' : ({{ ($isTodayCard || !$task->is_archived) && ($task->isCompleted() || $column?->type === 'done') ? 'true' : 'false' }} ? 'bg-gray-50/50 dark:bg-gray-900/50 grayscale-[0.3]' : 'bg-white dark:bg-gray-900')"
     class="backdrop-blur-sm rounded-xl sm:rounded-2xl shadow-md border-l-[5px] sm:border-l-[6px] p-2.5 sm:p-3.5 md:p-4 cursor-grab active:cursor-grabbing hover:shadow-2xl hover:-translate-y-1.5 transition-all duration-300 group relative animate-card-appear border-t border-r border-b border-gray-100/50 dark:border-gray-800/50"
     data-task-id="{{ $task->id }}"
     {{ ($isTodayCard || !$task->is_archived) && ($task->isCompleted() || $column?->type === 'done') ? 'data-completed=1' : '' }}
     style="border-left-color: {{ $qCfg['color'] ?? '#d1d5db' }}; animation-delay: {{ $cardIndex * 50 }}ms; will-change: transform, opacity;">
    
    <!-- Card Content -->
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
                @if($showBadges && $task->priority)
                    <span class="text-[9px] font-bold uppercase tracking-tighter px-1.5 py-0.5 rounded border shadow-sm {{ match($task->priority) {
                        'critical' => 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 border-red-200 dark:border-red-700/50',
                        'high' => 'bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300 border-orange-200 dark:border-orange-700/50',
                        'medium' => 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-700/50',
                        default => 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700'
                    } }}">
                        {{ __("tasks.priorities.{$task->priority}") }}
                    </span>
                @endif
            </div>
            <a href="{{ route('teams.activities.show', [$team, $task]) }}" class="text-sm font-black text-gray-900 dark:text-gray-50 leading-tight hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                {{ $task->title }}
            </a>
            <div class="flex flex-wrap gap-1 mt-0.5">
                @if($showBadges)
                    @if ($task->is_template)
                        @php
                            $isCollabKanban = isset($task->metadata['assignment_mode']) && $task->metadata['assignment_mode'] === 'shared';
                        @endphp
                        <span class="px-1.5 py-0.5 rounded-md {{ $isCollabKanban ? 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-700/50' : 'bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300 border-violet-200 dark:border-violet-700/50' }} text-[8px] font-black uppercase tracking-tighter border shadow-sm">
                            {{ $isCollabKanban ? (__('activities.collaborative_task') ?? 'Tarea Colaborativa') : __('tasks.plan_master') }}
                        </span>
                    @endif
                    @if ($task->assigned_user_id === auth()->id() && $task->parent_id)
                        <span class="px-1.5 py-0.5 rounded-md bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 text-[8px] font-black uppercase tracking-tighter border border-emerald-200 dark:border-emerald-700/50 shadow-sm">
                            {{ __('tasks.your_execution') }}
                        </span>
                    @endif
                    @if ($task->expediente)
                        <a href="{{ route('teams.expedientes.show', [$team, $task->expediente]) }}" onclick="event.stopPropagation()" class="px-1.5 py-0.5 rounded-md bg-violet-100/60 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300 text-[8px] font-black uppercase tracking-tighter border border-violet-200/50 dark:border-violet-800/50 shadow-sm hover:ring-1 hover:ring-violet-500 transition-all">
                            {{ $task->expediente->code }}
                        </a>
                    @endif
                    @if ($task->google_task_id)
                        <span class="px-1.5 py-0.5 rounded-md bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 border border-blue-200/50 dark:border-blue-700/50 shadow-sm" title="Sincronizada con Google Tasks">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                        </span>
                    @endif
                    @if ($task->google_calendar_event_id)
                        <span class="px-1.5 py-0.5 rounded-md bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 border border-amber-200/50 dark:border-amber-700/50 shadow-sm" title="Sincronizada con Google Calendar">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </span>
                    @endif
                    @if ($task->is_autoprogrammable)
                        <span class="px-1.5 py-0.5 rounded-md bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300 border border-violet-200/50 dark:border-violet-700/50 shadow-sm" title="Plantilla de Autoprogramación">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        </span>
                    @elseif (!$task->is_autoprogrammable && $task->parent && $task->parent->is_autoprogrammable)
                        <a href="{{ route('teams.activities.show', [$team, $task->parent_id]) }}" class="px-1.5 py-0.5 rounded-md bg-violet-100 dark:bg-violet-900/40 text-violet-600 dark:text-violet-400 border border-violet-200/50 dark:border-violet-700/50 shadow-sm hover:bg-violet-200 dark:hover:bg-violet-900/60 transition-colors" title="Tarea autoprogramada (Ir a plantilla maestra)" onclick="event.stopPropagation();">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        </a>
                    @endif
                    @if($task->urgency)
                        <span class="px-1.5 py-0.5 rounded-md text-[8px] font-black uppercase tracking-tighter border shadow-sm {{ match($task->urgency) {
                            'very_high' => 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 border-red-200 dark:border-red-700/50',
                            'high' => 'bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-300 border-orange-200 dark:border-orange-700/50',
                            'medium' => 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300 border-yellow-200 dark:border-yellow-700/50',
                            'low' => 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 border-green-200 dark:border-green-700/50',
                            'very_low' => 'bg-teal-100 dark:bg-teal-900/40 text-teal-700 dark:text-teal-300 border-teal-200 dark:border-teal-700/50',
                            default => 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700'
                        } }}" title="{{ __("tasks.urgencies.{$task->urgency}") }}">
                            ⚡ {{ __("tasks.urgencies.{$task->urgency}") }}
                        </span>
                    @endif
                    @if($task->is_blocked || $task->status_value === 'blocked')
                        <span class="px-1.5 py-0.5 rounded-md bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300 border border-red-200/50 dark:border-red-700/50 shadow-sm text-[8px] font-black uppercase tracking-tighter" title="Bloqueada">
                            🔒 {{ __('tasks.blocked') }}
                        </span>
                    @endif
                    @if($task->instances_count > 0)
                        <span class="px-1.5 py-0.5 rounded-md bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200/50 dark:border-indigo-700/50 shadow-sm text-[8px] font-black uppercase tracking-tighter" title="{{ $task->instances_count }} instanc{{ $task->instances_count === 1 ? 'ia' : 'ias' }}">
                            📋 {{ $task->instances_count }}
                        </span>
                    @endif
                    @if($task->has_private_notes)
                        <span class="px-1.5 py-0.5 rounded-md bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 border border-amber-200/50 dark:border-amber-700/50 shadow-sm" title="Notas privadas">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        </span>
                    @endif
                    @if($task->has_attachments)
                        <span class="px-1.5 py-0.5 rounded-md bg-sky-100 dark:bg-sky-900/40 text-sky-700 dark:text-sky-300 border border-sky-200/50 dark:border-sky-700/50 shadow-sm" title="{{ $task->attachments->count() }} adjunto{{ $task->attachments->count() !== 1 ? 's' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                        </span>
                    @endif
                @endif
            </div>
        </div>
        <div class="shrink-0 flex flex-col items-end gap-1.5">
            <div class="flex items-center gap-1.5">
                @include('tasks.partials.task-timer-button')
                @if($showBadges && !$task->is_archived && ($task->isCompleted() || $column?->type === 'done'))
                    <button onclick="archiveTask({{ $task->id }})" 
                            class="p-1 rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/40 text-emerald-500 hover:text-emerald-600 transition-colors"
                            title="{{ __('tasks.mark_as_completed_and_archive') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                @endif
            </div>
            @if(!$isTodayCard)
            <a href="{{ route('teams.dashboard', $team) }}" 
               onclick="event.stopPropagation()"
               class="text-[9px] font-black px-1.5 py-0.5 rounded uppercase tracking-tighter hover:ring-2 hover:ring-violet-500 transition-all cursor-pointer shadow-sm"
               style="background-color: {{ $qCfg['color'] ?? '#d1d5db' }}40; color: {{ $qCfg['color'] ?? '#374151' }};"
               title="{{ __('teams.view_dashboard') ?? 'Ver Eisenhower' }}">
                Q{{ $quadrant }}
            </a>
            @endif
        </div>
    </div>

    @if($task->description)
        <div class="text-[11px] text-gray-600 dark:text-gray-300 line-clamp-2 mb-3 opacity-80 leading-relaxed">
            {{ \Illuminate\Support\Str::limit(html_entity_decode(strip_tags(\Illuminate\Support\Str::markdown($task->description))), 120) }}
        </div>
    @endif

    <!-- Progress Slider -->
    @if($showProgress)
    <div class="mt-4 space-y-1.5">
        <div class="flex items-center justify-between text-[10px] font-bold">
            <span class="text-gray-400 uppercase tracking-widest">{{ __('tasks.progress') }}</span>
            <span class="text-violet-600 dark:text-violet-400 progress-label">{{ $task->progress }}%</span>
        </div>
        <div class="relative w-full h-8 flex items-center group">
            <!-- Visual Track -->
            <div class="absolute w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg pointer-events-none"></div>
            <!-- Visual Fill -->
            <div class="absolute h-2 bg-violet-500 rounded-lg transition-all duration-300 pointer-events-none progress-fill" style="width: {{ $task->progress }}%;"></div>
            <!-- Visual Thumb -->
            <div class="absolute w-4 h-4 bg-white border-2 border-violet-500 rounded-full shadow-sm transition-all duration-300 pointer-events-none progress-thumb transform -translate-x-1/2" style="left: {{ $task->progress }}%; {{ $task->progress == 0 ? 'margin-left: 8px;' : ($task->progress == 100 ? 'margin-left: -8px;' : '') }}"></div>
            
            <!-- Interactive Invisible Slider (Touch Target) -->
            <input type="range" min="0" max="100" value="{{ $task->progress }}" autocomplete="off"
                class="absolute w-full h-full opacity-0 cursor-pointer z-30 progress-slider"
                data-task-id="{{ $task->id }}"
                {{ $task->children()->count() > 0 ? 'disabled' : '' }}>
        </div>
    </div>
    @endif

    @if($showFooter)
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
        
        <div class="flex items-center gap-1.5">
            @if($task->today_time_minutes > 0)
                <span class="text-[9px] font-bold text-violet-600 dark:text-violet-400 flex items-center gap-0.5" title="Tiempo hoy">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ floor($task->today_time_minutes / 60) }}h{{ $task->today_time_minutes % 60 }}m
                </span>
            @endif
            @if(!$isTodayCard && $task->avg_quality_score !== null)
                <span class="text-[9px] font-bold {{ $task->avg_quality_score >= 4 ? 'text-green-600' : ($task->avg_quality_score >= 3 ? 'text-yellow-600' : 'text-red-600') }}" title="Calificación promedio">
                    ⭐ {{ number_format($task->avg_quality_score, 1) }}
                </span>
            @endif
            @if(!$isTodayCard && $task->skills->isNotEmpty())
                <span class="text-[9px] font-bold text-sky-600 dark:text-sky-400 flex items-center gap-0.5" title="Habilidades">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" /></svg>
                    {{ $task->skills->count() }}
                </span>
            @endif
            @if(!$isTodayCard && $task->tags->isNotEmpty())
                <span class="text-[9px] font-bold text-gray-500 dark:text-gray-400 flex items-center gap-0.5" title="Etiquetas">
                    #{{ $task->tags->count() }}
                </span>
            @endif
            @if($task->days_remaining !== null)
                <div class="flex items-center gap-1 text-[10px] font-bold {{ $task->due_date->isPast() && !$task->isCompleted() ? 'text-red-500' : ($task->days_remaining === 0 ? 'text-orange-500' : 'text-gray-400') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2v12a2 2 0 002 2z" />
                    </svg>
                    @if($task->days_remaining < 0)
                        {{ abs($task->days_remaining) }}d atrasada
                    @elseif($task->days_remaining === 0)
                        Hoy
                    @else
                        {{ $task->days_remaining }}d
                    @endif
                </div>
            @elseif($task->due_date)
                <div class="flex items-center gap-1 text-[10px] font-bold text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2v12a2 2 0 002 2z" />
                    </svg>
                    {{ $task->due_date->format('d M') }}
                </div>
            @endif
        </div>
    </div>
    @endif
</div>
