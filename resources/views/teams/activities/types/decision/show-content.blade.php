@php
    $meta = $activity->metadata ?? [];
    $decisionType = $meta['decision_type'] ?? '';
    $decisionTypeLabels = [
        'aprobar' => 'Aprobada',
        'rechazar' => 'Rechazada',
        'implementar' => 'Implementada',
        'proponer' => 'Propuesta',
    ];
    $stateColors = match($decisionType) {
        'aprobar' => ['bg' => 'green-50', 'dark_bg' => 'green-900/10', 'text' => 'green-600', 'dark_text' => 'green-400', 'border' => 'green-200', 'dark_border' => 'green-800/30'],
        'rechazar' => ['bg' => 'red-50', 'dark_bg' => 'red-900/10', 'text' => 'red-600', 'dark_text' => 'red-400', 'border' => 'red-200', 'dark_border' => 'red-800/30'],
        'implementar' => ['bg' => 'blue-50', 'dark_bg' => 'blue-900/10', 'text' => 'blue-600', 'dark_text' => 'blue-400', 'border' => 'blue-200', 'dark_border' => 'blue-800/30'],
        'proponer' => ['bg' => 'amber-50', 'dark_bg' => 'amber-900/10', 'text' => 'amber-600', 'dark_text' => 'amber-400', 'border' => 'amber-200', 'dark_border' => 'amber-800/30'],
        default => ['bg' => 'gray-50', 'dark_bg' => 'gray-900/10', 'text' => 'gray-600', 'dark_text' => 'gray-400', 'border' => 'gray-200', 'dark_border' => 'gray-800/30'],
    };
@endphp

{{-- Decision State Card --}}
<div class="bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-800/30 rounded-2xl p-5 shadow-sm">
    <h3 class="text-xs font-black text-red-600 dark:text-red-400 uppercase tracking-widest mb-3 flex items-center gap-1.5">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" /></svg>
        Estado de la Decisión
    </h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @if(!empty($decisionType))
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Tipo</p>
            <span class="px-3 py-1 rounded-lg text-xs font-black bg-{{ $stateColors['text'] }}-100 text-{{ $stateColors['text'] }}-700 dark:bg-{{ $stateColors['text'] }}-900/30 dark:text-{{ $stateColors['text'] }}-300 border border-{{ $stateColors['border'] }} dark:border-{{ $stateColors['dark_border'] }}">
                {{ $decisionTypeLabels[$decisionType] ?? ucfirst($decisionType) }}
            </span>
        </div>
        @endif
        @if(!empty($meta['justification']))
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Justificación</p>
            <p class="text-sm text-gray-700 dark:text-gray-300 line-clamp-3">{{ $meta['justification'] }}</p>
        </div>
        @endif
    </div>
</div>

{{-- Implementation Notes --}}
@if(!empty($meta['implementation_notes']))
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm">
    <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Notas de Implementación</h3>
    <div class="text-sm text-gray-700 dark:text-gray-300 prose dark:prose-invert max-w-none prose-sm leading-relaxed">
        {!! str($meta['implementation_notes'])->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
    </div>
</div>
@endif

{{-- Participants --}}
@if(!empty($meta['participants']))
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm">
    <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Participantes</h3>
    <div class="flex gap-2 flex-wrap">
        @foreach((array)$meta['participants'] as $participant)
        <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300 border border-red-200 dark:border-red-800/30">{{ $participant }}</span>
        @endforeach
    </div>
</div>
@endif

{{-- Description --}}
@php
    $displayDescription = $activity->description ?: ($activity->parent?->description ?? null);
    $displayObservations = $activity->observations ?: ($activity->parent?->observations ?? null);
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

@if ($displayObservations)
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                {{ __('activities.observations') }}
            </h3>
            <button onclick="printSection('Observaciones', 'observations-content')" 
                    class="p-1.5 bg-gray-50 dark:bg-gray-800 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-xl transition-all border border-transparent hover:border-violet-100 dark:hover:border-violet-800 shadow-sm flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest"
                    title="Imprimir observaciones">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Imprimir
            </button>
        </div>
        <div id="observations-content" style="height: 350px; max-height: none; overflow-y: auto;"
            class="text-sm text-gray-700 dark:text-gray-300 prose dark:prose-invert max-w-none prose-sm leading-relaxed resize-y min-h-[250px] custom-scrollbar pr-4 py-2">
            {!! str($displayObservations)->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
        </div>
    </div>
@endif
