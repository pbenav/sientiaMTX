@php
    $meta = $activity->metadata ?? [];
    $channels = $meta['channels'] ?? [];
    $firingDate = $meta['firing_date'] ?? null;
    $countdownText = '';
    if ($firingDate) {
        $now = \Carbon\Carbon::now();
        $firing = \Carbon\Carbon::parse($firingDate);
        if ($firing->isPast()) {
            $countdownText = 'Vencido hace ' . $firing->diffForHumans(now, false);
        } elseif ($firing->isFuture()) {
            $countdownText = 'Faltan ' . $firing->diffForHumans(now, false);
        } else {
            $countdownText = 'Hora de activación: ' . $firing->format('H:i');
        }
    }
@endphp

{{-- Reminder Configuration Card --}}
<div class="bg-pink-50 dark:bg-pink-900/10 border border-pink-100 dark:border-pink-800/30 rounded-2xl p-5 shadow-sm">
    <h3 class="text-xs font-black text-pink-600 dark:text-pink-400 uppercase tracking-widest mb-3 flex items-center gap-1.5">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
        Configuración del Recordatorio
    </h3>
    
    @if(!empty($firingDate))
    <div class="mb-4">
        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Fecha de activación</p>
        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($firingDate)->format('d/m/Y H:i') }}</p>
        <div id="reminder-countdown" class="mt-2 text-xs font-bold text-pink-600 dark:text-pink-400" data-firing="{{ $firingDate }}">
            {{ $countdownText }}
        </div>
    </div>
    @endif

    @if(!empty($channels))
    <div>
        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Canales de notificación</p>
        <div class="flex gap-2 flex-wrap">
            @foreach((array)$channels as $channel)
            <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-300 border border-pink-200 dark:border-pink-800/30">{{ ucfirst($channel) }}</span>
            @endforeach
        </div>
    </div>
    @endif
</div>

{{-- State --}}
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Estado</p>
            @php
                $isActive = $firingDate && \Carbon\Carbon::parse($firingDate)->isFuture();
            @endphp
            <span class="px-3 py-1 rounded-lg text-xs font-black {{ $isActive ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-300' }} border {{ $isActive ? 'border-green-200 dark:border-green-800/30' : 'border-gray-200 dark:border-gray-800/30' }}">
                {{ $isActive ? 'Activo' : 'Inactivo' }}
            </span>
        </div>
    </div>
</div>

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
