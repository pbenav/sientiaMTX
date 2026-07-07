@php
    $meta = $activity->metadata ?? [];
    $chapters = $meta['chapters'] ?? [];
@endphp

{{-- Document Metadata Card --}}
<div class="bg-orange-50 dark:bg-orange-900/10 border border-orange-100 dark:border-orange-800/30 rounded-2xl p-5 shadow-sm">
    <h3 class="text-xs font-black text-orange-500 dark:text-orange-400 uppercase tracking-widest mb-3 flex items-center gap-1.5">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
        Propiedades del Documento
    </h3>
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
        @if(!empty($meta['version']))
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Versión</p>
            <p class="text-sm font-bold text-gray-900 dark:text-white font-mono">{{ $meta['version'] }}</p>
        </div>
        @endif
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Capítulos</p>
            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ count($chapters) }}</p>
        </div>
        @if(!empty($meta['is_ephemeral']))
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Efímero</p>
            <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">Sí</span>
        </div>
        @endif
    </div>
    @if(count($chapters) > 0)
    <div class="mt-4 space-y-2" id="chapters-section">
        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Índice de Capítulos</p>
        @foreach($chapters as $idx => $chapter)
        <div x-data="{ open: false }" class="bg-white dark:bg-gray-900 rounded-xl border border-orange-100 dark:border-orange-800/20 overflow-hidden">
            <div @click="open = !open" class="flex items-start gap-3 p-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                <span class="text-[10px] font-black text-orange-500 shrink-0 mt-0.5">{{ $idx + 1 }}.</span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $chapter['title'] ?? 'Capítulo sin título' }}</p>
                    <p class="text-[10px] text-gray-400 mt-0.5">{{ $chapter['author_name'] ?? '' }} &middot; {{ isset($chapter['updated_at']) ? \Carbon\Carbon::parse($chapter['updated_at'])->diffForHumans() : '' }}</p>
                </div>
                <div class="shrink-0 text-gray-400">
                    <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    <svg x-show="open" style="display: none;" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" /></svg>
                </div>
            </div>
            <div x-show="open" x-collapse style="display: none;" class="p-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                <div class="flex justify-end mb-3">
                    <button type="button" onclick="printSection('{{ addslashes($chapter['title'] ?? 'Capítulo ' . ($idx + 1)) }}', 'chapter-content-{{ $idx }}')" class="p-1.5 bg-white dark:bg-gray-800 text-gray-500 hover:text-orange-600 dark:hover:text-orange-400 rounded-lg transition-all shadow-sm border border-gray-200 dark:border-gray-700 flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                        Imprimir Capítulo
                    </button>
                </div>
                <div id="chapter-content-{{ $idx }}" class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300">
                    {!! str($chapter['content'] ?? '')->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

{{-- Document Status & Collaborators --}}
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Estado</p>
            <span class="px-3 py-1 rounded-lg text-xs font-black bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border border-blue-200 dark:border-blue-800/30">
                {{ $activity->status_value ?? 'borrador' }}
            </span>
        </div>
        @if(!empty($meta['collaborators']))
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Colaboradores</p>
            <div class="flex gap-2 flex-wrap">
                @foreach((array)$meta['collaborators'] as $collab)
                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300 border border-orange-200 dark:border-orange-800/30">{{ $collab }}</span>
                @endforeach
            </div>
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
