<!-- Active Services Panel (Sentinel) -->
<div class="mt-6 mb-12">
    <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm rounded-3xl border border-gray-100 dark:border-gray-800 px-6 py-6 transition-all duration-300">
        <div class="flex items-center justify-between mb-8 border-b border-gray-50 dark:border-gray-800 pb-6">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-violet-600/10 rounded-xl text-violet-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <h4 class="font-black text-gray-900 dark:text-gray-100 uppercase tracking-widest text-[10px]">Portal de Disponibilidad (Sentinel)</h4>
            </div>
            @if($team->isCoordinator(auth()->user()))
                <button x-data @click="$dispatch('open-modal', 'manage-services')" class="px-3 py-1.5 bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-gray-800 rounded-xl text-[10px] font-black uppercase text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/10 transition-all">
                    {{ __('Gestionar') }}
                </button>
            @endif
        </div>

        @if($services->isEmpty())
            <div class="py-12 text-center">
                <p class="text-[11px] text-gray-500 italic">{{ __('No hay servicios configurados. Los coordinadores pueden añadir herramientas como Telegram, Google, etc.') }}</p>
            </div>
        @else            <div id="services-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" data-url="{{ route('teams.services.reorder', $team) }}">
                @foreach($services as $service)
                    @php
                        $color = $service->getStatusColor();
                    @endphp
                    <div data-id="{{ $service->id }}" class="service-card bg-gray-50/50 dark:bg-white/5 border border-gray-100 dark:border-gray-800/50 rounded-2xl p-5 hover:border-violet-500/30 transition-all duration-300 transform-gpu {{ $team->isCoordinator(auth()->user()) ? 'cursor-move' : '' }}">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-white dark:bg-gray-800 flex items-center justify-center text-lg border border-gray-100 dark:border-gray-700 shadow-sm pointer-events-none">
                                    {{ $service->icon ?: '🌐' }}
                                </div>
                                <div class="pointer-events-none">
                                    <h5 class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-tight">{{ $service->name }}</h5>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <div class="w-1.5 h-1.5 rounded-full bg-{{ $color }}-500 {{ $service->status === 'down' ? 'animate-ping' : '' }}"></div>
                                        <span class="text-[8px] font-black uppercase tracking-wider text-{{ $color }}-600">{{ $service->getStatusLabel() }}</span>
                                    </div>
                                </div>
                            </div>
                            @if($service->url)
                                <a href="{{ $service->url }}" target="_blank" class="p-2 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-colors z-10">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                            @endif
                        </div>

                        <!-- Mini History Chart -->
                        @php
                            $history = $service->getIncidentHistory();
                        @endphp
                        <div class="mt-3 mb-3 px-1">
                            <div class="flex items-end gap-[2px] h-4 w-full relative">
                                @foreach($history as $index => $count)
                                    @php
                                        $barColor = $count > 0 ? ($count > 1 ? 'bg-red-500' : 'bg-amber-500') : 'bg-emerald-500';
                                        $barOpacity = $count > 0 ? 'opacity-100' : 'opacity-20';
                                        $barHeight = $count > 0 ? min(100, 30 + ($count * 20)) : 20;
                                    @endphp
                                    <div class="flex-1 {{ $barColor }} {{ $barOpacity }} rounded-[1px] transition-all duration-700 hover:opacity-100 cursor-help group/bar" 
                                         style="height: {{ $barHeight }}%;"
                                         title="{{ $count }} incidencias">
                                         <!-- Tooltip on hover could go here, but keep it light -->
                                    </div>
                                @endforeach
                            </div>
                            <div class="flex justify-between mt-1 text-[6px] font-black uppercase tracking-tighter text-gray-300 dark:text-gray-600 border-t border-gray-50 dark:border-gray-800/30 pt-0.5">
                                <span>-10d</span>
                                <span>Hoy</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 mt-auto pt-2 border-t border-gray-100/50 dark:border-gray-800/50 z-10 relative">
                            <form action="{{ route('teams.services.report', [$team, $service]) }}" method="POST" class="flex-1">
                                @csrf
                                <input type="hidden" name="type" value="{{ $service->status === 'up' ? 'down' : 'up' }}">
                                <button type="submit" class="w-full py-2 bg-white dark:bg-white/5 hover:bg-violet-50 dark:hover:bg-violet-900/20 text-[9px] font-black uppercase tracking-widest rounded-xl transition-all border border-gray-200 dark:border-gray-700 hover:border-violet-300 dark:hover:border-violet-500/50 text-gray-600 dark:text-gray-400 hover:text-violet-600 dark:hover:text-violet-300">
                                    {{ $service->status === 'up' ? __('Reportar Caída') : __('Confirmar Recuperación') }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<!-- Modal stays largely the same as it uses standard Sientia components -->
<x-modal name="manage-services" focusable>
    <div class="p-6">
        <h2 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest mb-6 border-b border-gray-50 dark:border-gray-800 pb-4">{{ __('Configuración de Sentinel') }}</h2>
        
        <form action="{{ route('teams.services.store', $team) }}" method="POST" class="space-y-4 mb-8">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-input-label class="tracking-widest mb-1.5 ml-1">{{ __('Nombre del Servicio') }}</x-input-label>
                    <x-text-input name="name" required class="py-4" :emoji="false" />
                </div>
                <div>
                    <x-input-label class="tracking-widest mb-1.5 ml-1">{{ __('Emoji/Icono') }}</x-input-label>
                    <x-text-input name="icon" placeholder="💡, 📞, 📧..." class="py-4" />
                </div>
            </div>
            <div>
                <x-input-label class="tracking-widest mb-1.5 ml-1">{{ __('URL del Portal') }}</x-input-label>
                <x-text-input type="url" name="url" placeholder="https://..." class="py-4" :emoji="false" />
            </div>
            <div>
                <x-input-label class="tracking-widest mb-1.5 ml-1">{{ __('Descripción') }}</x-input-label>
                <textarea name="description" rows="2" class="w-full bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring-violet-500 rounded-xl shadow-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 transition-all font-bold text-sm px-5 py-4"></textarea>
            </div>
            
            <div class="mt-6 flex justify-end">
                <button type="submit" class="px-6 py-4 bg-violet-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-violet-700 transition-all shadow-xl shadow-violet-500/25">
                    {{ __('Añadir Herramienta') }}
                </button>
            </div>
        </form>

        <div class="border-t border-gray-100 dark:border-gray-800 pt-6">
            <h3 class="text-[9px] font-black uppercase tracking-widest text-gray-400 mb-4 ml-1">{{ __('Herramientas Monitorizadas') }}</h3>
            <div class="space-y-2">
                @foreach($services as $s)
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-white/5 rounded-2xl border border-gray-100 dark:border-gray-800">
                        <div class="flex items-center gap-3">
                            <span class="text-lg">{{ $s->icon ?: '🌐' }}</span>
                            <span class="text-xs font-black text-gray-900 dark:text-white uppercase">{{ $s->name }}</span>
                        </div>
                        <form action="{{ route('teams.services.destroy', [$team, $s]) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-950/40 rounded-xl transition-all" onclick="return confirm('¿Eliminar este servicio?')">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-modal>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const grid = document.getElementById('services-grid');
        if (grid && typeof Sortable !== 'undefined' && @json($team->isCoordinator(auth()->user()))) {
            new Sortable(grid, {
                animation: 150,
                ghostClass: 'opacity-30',
                onEnd: function() {
                    const ids = Array.from(grid.querySelectorAll('.service-card')).map(el => el.getAttribute('data-id'));
                    const url = grid.getAttribute('data-url');
                    
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ ids: ids })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (!data.success) {
                            console.error('Error updating order');
                        }
                    });
                }
            });
        }
    });
</script>
@endpush
