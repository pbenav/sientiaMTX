@php
    $meta = $activity->metadata ?? [];
    $location = $meta['location'] ?? '';
    $joinUrl = filter_var($location, FILTER_VALIDATE_URL) ? $location : ($meta['join_url'] ?? null);
    $duration = $meta['duration_minutes'] ?? null;
    $displayObservations = $activity->observations ?: ($activity->parent?->observations ?? null);
@endphp

{{-- Meeting Details Card --}}
<div class="bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-100 dark:border-emerald-800/30 rounded-2xl p-5 shadow-sm">
    <h3 class="text-xs font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-3 flex items-center gap-1.5">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
        Detalles de la Reunión
    </h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Fecha Programada</p>
            <p class="text-sm font-bold text-gray-900 dark:text-white">
                {{ $activity->scheduled_date ? $activity->scheduled_date->format('d/m/Y H:i') : 'Por definir' }}
            </p>
        </div>

        @if(!empty($meta['modality']))
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Modalidad</p>
            <p class="text-sm font-bold text-gray-900 dark:text-white">
                @if($meta['modality'] === 'remote') 💻 En remoto / Online
                @elseif($meta['modality'] === 'presential') 🏢 Presencial
                @elseif($meta['modality'] === 'hybrid') 🤝 Híbrido
                @else {{ ucfirst($meta['modality']) }}
                @endif
            </p>
        </div>
        @endif

        @if(!empty($location))
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Ubicación / Enlace</p>
            @if($joinUrl)
                <a href="{{ $joinUrl }}" target="_blank" class="text-sm font-bold text-emerald-600 dark:text-emerald-400 hover:underline flex items-center gap-1 truncate">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                    <span class="truncate">{{ $location }}</span>
                </a>
            @else
                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $location }}</p>
            @endif
        </div>
        @endif

        @if(!empty($duration))
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Duración</p>
            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $duration }} min</p>
        </div>
        @endif
    </div>
    @if($joinUrl)
    <div class="mt-4">
        <a href="{{ $joinUrl }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black uppercase tracking-widest rounded-xl transition-all shadow-md">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            Unirse a la reunión
        </a>
    </div>
    @endif
</div>

{{-- Attendees --}}
@if(!empty($meta['attendees']) || $activity->assignedTo->isNotEmpty())
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm">
    <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Asistentes</h3>
    <div class="flex gap-2 flex-wrap">
        @php $attendees = !empty($meta['attendees']) ? $meta['attendees'] : $activity->assignedTo->pluck('name')->toArray(); @endphp
        @foreach((array)$attendees as $attendee)
        <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/30">{{ $attendee }}</span>
        @endforeach
    </div>
</div>
@endif

{{-- Invitados Externos --}}
@if(!empty($meta['guests']))
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm mt-4 mb-4">
    <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Invitados Externos</h3>
    <div class="flex gap-2 flex-wrap">
        @foreach($meta['guests'] as $guest)
            <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border border-blue-200 dark:border-blue-800/30" title="{{ $guest['email'] ?? '' }}">
                {{ $guest['name'] ?? 'Invitado' }}
                @if(!empty($guest['email']))
                    <span class="text-[9px] font-normal opacity-75 ml-1">({{ $guest['email'] }})</span>
                @endif
            </span>
        @endforeach
    </div>
</div>
@endif

{{-- Agenda --}}
@if(!empty($meta['agenda']))
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm">
    <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Agenda</h3>
    <div class="text-sm text-gray-700 dark:text-gray-300 prose dark:prose-invert max-w-none prose-sm leading-relaxed">
        {!! str($meta['agenda'])->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
    </div>
</div>
@endif

{{-- Post-meeting Acta --}}
@if(!empty($meta['post_meeting_acta']) || $displayObservations)
@php
    $acta = $meta['post_meeting_acta'] ?? null;
@endphp
@if($acta || $displayObservations)
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm">
    <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Acta Post-Reunión</h3>
    @if($acta)
    <div class="text-sm text-gray-700 dark:text-gray-300 prose dark:prose-invert max-w-none prose-sm leading-relaxed mb-4">
        {!! str($acta)->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
    </div>
    @endif
    @if($displayObservations)
    <div class="border-t border-gray-100 dark:border-gray-800 pt-4">
        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Observaciones</p>
        <div class="text-sm text-gray-700 dark:text-gray-300 prose dark:prose-invert max-w-none prose-sm leading-relaxed">
            {!! str($displayObservations)->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
        </div>
    </div>
    @endif
</div>
@endif
@endif

{{-- Description --}}
@php
    $displayDescription = $activity->description ?: ($activity->parent?->description ?? null);
@endphp

@if ($displayDescription)
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                {{ __('activities.description') }}
            </h3>
            <button onclick="printSection('Descripción', 'description-content')" 
                    class="p-1.5 bg-gray-50 dark:bg-gray-800 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-xl transition-all border border-transparent hover:border-violet-100 dark:hover:border-violet-800 shadow-sm flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest"
                    title="Imprimir descripción">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Imprimir
            </button>
        </div>
        <div id="description-content" style="height: 350px; max-height: none; overflow-y: auto;"
            class="text-sm text-gray-700 dark:text-gray-300 prose dark:prose-invert max-w-none prose-sm leading-relaxed resize-y min-h-[250px] custom-scrollbar pr-4 py-2">
            {!! str($displayDescription)->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
        </div>
    </div>
@endif
