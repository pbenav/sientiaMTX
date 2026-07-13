@php
    $meta = $activity->metadata ?? [];
    $links = $meta['links'] ?? [];
    if (empty($links) && !empty($meta['url'])) {
        $links = [['title' => 'Enlace Adjunto', 'url' => $meta['url']]];
    }
    $status = $meta['status'] ?? 'active';
    $statusColors = [
        'active' => ['bg' => 'green-50', 'dark_bg' => 'green-900/10', 'border' => 'green-100', 'dark_border' => 'green-800/30', 'text' => 'green-600', 'dark_text' => 'green-400', 'label' => 'Activo'],
        'broken' => ['bg' => 'red-50', 'dark_bg' => 'red-900/10', 'border' => 'red-100', 'dark_border' => 'red-800/30', 'text' => 'red-600', 'dark_text' => 'red-400', 'label' => 'Roto'],
        'archived' => ['bg' => 'gray-50', 'dark_bg' => 'gray-900/10', 'border' => 'gray-100', 'dark_border' => 'gray-800/30', 'text' => 'gray-600', 'dark_text' => 'gray-400', 'label' => 'Archivado'],
    ][$status] ?? ['bg' => 'blue-50', 'dark_bg' => 'blue-900/10', 'border' => 'blue-100', 'dark_border' => 'blue-800/30', 'text' => 'blue-600', 'dark_text' => 'blue-400', 'label' => ucfirst($status)];
@endphp

{{-- Links Card --}}
<div class="bg-purple-50 dark:bg-purple-900/10 border border-purple-100 dark:border-purple-800/30 rounded-2xl p-5 shadow-sm">
    <h3 class="text-xs font-black text-purple-600 dark:text-purple-400 uppercase tracking-widest mb-3 flex items-center gap-1.5">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
        Enlaces Coleccionados
    </h3>
    <div class="space-y-3 mt-4">
        @forelse($links as $link)
            <a href="{{ $link['url'] }}" target="_blank"
               class="flex items-center justify-between p-3 bg-white dark:bg-gray-900 rounded-xl border border-purple-100 dark:border-purple-800/20 hover:border-purple-400 transition-colors group shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center text-purple-500 shrink-0 border border-purple-100 dark:border-purple-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors truncate">{{ $link['title'] ?? 'Enlace' }}</span>
                        <span class="text-[10px] text-gray-500 truncate w-48 sm:w-64">{{ $link['url'] }}</span>
                    </div>
                </div>
                <div class="text-purple-400 group-hover:text-purple-600 dark:group-hover:text-purple-300 transition-colors opacity-0 group-hover:opacity-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                </div>
            </a>
        @empty
            <p class="text-xs text-gray-500 italic">No hay enlaces asociados a esta actividad.</p>
        @endforelse
    </div>
</div>

{{-- Status & Description --}}
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Estado</p>
            <span class="px-3 py-1 rounded-lg text-xs font-black bg-{{ $statusColors['text'] }}-100 text-{{ $statusColors['text'] }}-700 dark:bg-{{ $statusColors['text'] }}-900/30 dark:text-{{ $statusColors['text'] }}-300 border border-{{ $statusColors['text'] }}-200 dark:border-{{ $statusColors['text'] }}-800/30">
                {{ $statusColors['label'] }}
            </span>
        </div>
        @if(!empty($meta['verified_at']))
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Última verificación</p>
            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($meta['verified_at'])->diffForHumans() }}</p>
        </div>
        @endif
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
