<!-- Active Services Panel (Sentinel) -->
<div class="mt-6 mb-12" id="sentinel-services-panel">
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
                    <div data-id="{{ $service->id }}" class="service-card bg-gray-50/50 dark:bg-white/5 border border-gray-100 dark:border-gray-800/50 rounded-2xl p-5 hover:border-violet-500/30 transition-all duration-300 {{ $team->isCoordinator(auth()->user()) ? 'cursor-move' : '' }}">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-white dark:bg-gray-800 flex items-center justify-center text-lg border border-gray-100 dark:border-gray-700 shadow-sm pointer-events-none">
                                    {{ $service->icon ?: '🌐' }}
                                </div>
                                <div>
                                    <button type="button" 
                                        data-id="{{ $service->id }}" 
                                        data-name="{{ $service->name }}"
                                        @click="$dispatch('open-service-incidents', { id: $el.dataset.id, name: $el.dataset.name }); $dispatch('open-modal', 'service-incidents-modal')" 
                                        class="text-left hover:text-violet-600 transition-colors group/name">
                                        <h5 class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-tight flex items-center gap-1.5">
                                            {{ $service->name }}
                                            <svg class="w-2.5 h-2.5 opacity-0 group-hover/name:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </h5>
                                    </button>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <div data-service-dot="{{ $service->id }}" class="w-1.5 h-1.5 rounded-full bg-{{ $color }}-500 {{ $service->status === 'down' ? 'animate-ping' : '' }}"></div>
                                        <span data-service-label="{{ $service->id }}" class="text-[8px] font-black uppercase tracking-wider text-{{ $color }}-600">{{ $service->getStatusLabel() }}</span>
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
                            <div class="flex justify-between mt-1 text-[8px] font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 border-t border-gray-100 dark:border-gray-800/50 pt-1">
                                <span>Hace 3h</span>
                                <span>Ahora</span>
                            </div>
                        </div>

                        @php
                            $userReportedUp = $service->hasUserReportedRecently(auth()->id(), 'up');
                            $userReportedDown = $service->hasUserReportedRecently(auth()->id(), 'down');
                            $upCount = $service->getRecentUpReportsCount();
                            $downCount = $service->getRecentDownReportsCount();
                        @endphp
                        <div class="grid grid-cols-2 gap-2 mt-auto pt-2 border-t border-gray-100/50 dark:border-gray-800/50 z-10 relative">
                            <!-- Botón OK (Recuperación/Activo) -->
                            <form action="{{ route('teams.services.report', [$team, $service]) }}" method="POST" class="flex-1 report-service-form">
                                @csrf
                                <input type="hidden" name="type" value="up">
                                <button type="submit" 
                                    @if($userReportedUp) disabled title="{{ __('Ya has reportado que funciona recientemente') }}" @endif
                                    class="group w-full py-2 bg-white dark:bg-white/5 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 text-[9px] font-black uppercase tracking-widest rounded-xl transition-all border border-gray-200 dark:border-gray-700 hover:border-emerald-300 dark:hover:border-emerald-500/50 text-gray-600 dark:text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-300 disabled:opacity-30 disabled:cursor-not-allowed">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <div class="w-1.5 h-1.5 rounded-full {{ $service->status === 'up' ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-600' }} {{ !$userReportedUp ? 'group-hover:animate-pulse' : '' }}"></div>
                                        <span>OK</span>
                                        
                                        <span class="report-count px-1.5 py-0.5 bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-[7px] font-black rounded-md {{ $upCount > 0 ? '' : 'hidden' }}">
                                            {{ $upCount }}
                                        </span>
                                    </div>
                                </button>
                            </form>

                            <!-- Botón KO (Caída/Inestable) -->
                            <form action="{{ route('teams.services.report', [$team, $service]) }}" method="POST" class="flex-1 report-service-form">
                                @csrf
                                <input type="hidden" name="type" value="down">
                                <button type="submit" 
                                    @if($userReportedDown) disabled title="{{ __('Ya has reportado una incidencia recientemente') }}" @endif
                                    class="group w-full py-2 bg-white dark:bg-white/5 hover:bg-red-50 dark:hover:bg-red-900/20 text-[9px] font-black uppercase tracking-widest rounded-xl transition-all border border-gray-200 dark:border-gray-700 hover:border-red-300 dark:hover:border-red-500/50 text-gray-600 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-300 disabled:opacity-30 disabled:cursor-not-allowed">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <div class="w-1.5 h-1.5 rounded-full {{ $service->status !== 'up' ? 'bg-red-500 animate-ping' : 'bg-gray-300 dark:bg-gray-600' }} {{ !$userReportedDown ? 'group-hover:animate-bounce' : '' }}"></div>
                                        <span>KO</span>
                                        
                                        <span class="report-count px-1.5 py-0.5 bg-red-100 dark:bg-red-500/20 text-red-600 dark:text-red-400 text-[7px] font-black rounded-md {{ $downCount > 0 ? '' : 'hidden' }}">
                                            {{ $downCount }}
                                        </span>
                                    </div>
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
        
        <div x-data="{ 
            editingId: null, 
            editName: '', 
            editIcon: '', 
            editUrl: '', 
            editDescription: '', 
            editAction: '',
            startEdit(s) {
                this.editingId = s.id;
                this.editName = s.name;
                this.editIcon = s.icon || '🌐';
                this.editUrl = s.url || '';
                this.editDescription = s.description || '';
                this.editAction = `{{ url('/teams/' . $team->id . '/services') }}/${s.id}`;
            }
        }">
            <form action="{{ route('teams.services.store', $team) }}" method="POST" class="space-y-4 mb-8" x-show="!editingId">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-input-label class="tracking-widest mb-1.5 ml-1">{{ __('Nombre del Servicio') }}</x-input-label>
                        <x-text-input name="name" required class="py-4" :emoji="true" />
                    </div>
                    <div>
                        <x-input-label class="tracking-widest mb-1.5 ml-1">{{ __('Emoji/Icono') }}</x-input-label>
                        <x-text-input name="icon" placeholder="💡, 📞, 📧..." class="py-4" :emoji="true" />
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

            <!-- Edit Form (Visible only when editing) -->
            <form :action="editAction" method="POST" class="space-y-4 mb-8 bg-violet-50/50 dark:bg-violet-900/10 p-6 rounded-3xl border border-violet-100 dark:border-violet-800/50" x-show="editingId" x-cloak>
                @csrf
                @method('PATCH')
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xs font-black uppercase tracking-widest text-violet-600 dark:text-violet-400">Editando Recurso</h3>
                    <button type="button" @click="editingId = null" class="text-[10px] font-black uppercase text-gray-400 hover:text-gray-600 transition-colors tracking-widest">Cancelar</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-input-label class="tracking-widest mb-1.5 ml-1">{{ __('Nombre') }}</x-input-label>
                        <x-text-input name="name" x-model="editName" required class="py-4" :emoji="true" />
                    </div>
                    <div>
                        <x-input-label class="tracking-widest mb-1.5 ml-1">{{ __('Icono') }}</x-input-label>
                        <x-text-input name="icon" x-model="editIcon" class="py-4" :emoji="true" />
                    </div>
                </div>
                <div>
                    <x-input-label class="tracking-widest mb-1.5 ml-1">{{ __('URL') }}</x-input-label>
                    <x-text-input type="url" name="url" x-model="editUrl" class="py-4" :emoji="false" />
                </div>
                <div>
                    <x-input-label class="tracking-widest mb-1.5 ml-1">{{ __('Descripción') }}</x-input-label>
                    <textarea name="description" x-model="editDescription" rows="2" class="w-full bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring-violet-500 rounded-xl shadow-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 transition-all font-bold text-sm px-5 py-4"></textarea>
                </div>
                
                <div class="mt-6 flex justify-end">
                    <button type="submit" class="px-6 py-4 bg-violet-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-violet-700 transition-all shadow-xl shadow-violet-500/25">
                        {{ __('Guardar Cambios') }}
                    </button>
                </div>
            </form>

            <div class="border-t border-gray-100 dark:border-gray-800 pt-6">
                <h3 class="text-[9px] font-black uppercase tracking-widest text-gray-400 mb-4 ml-1">{{ __('Herramientas Monitorizadas') }}</h3>
                <div class="space-y-2">
                    @foreach($services as $s)
                        <div class="flex items-center justify-between p-3 pl-4 bg-gray-50/50 dark:bg-white/5 rounded-2xl border border-gray-100 dark:border-gray-800 hover:border-violet-500/20 transition-all group/s-item">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-xl bg-white dark:bg-gray-800 flex items-center justify-center text-lg border border-gray-100 dark:border-gray-700 shadow-sm transition-transform group-hover/s-item:scale-110">
                                    {{ $s->icon ?: '🌐' }}
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[11px] font-black text-gray-900 dark:text-white uppercase tracking-tight">{{ $s->name }}</span>
                                    <span class="text-[8px] font-bold text-gray-400 uppercase tracking-widest">{{ $s->url ? 'URL Configurada' : 'Sin URL' }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-1 pr-1">
                                <button type="button" 
                                    data-service="{{ json_encode($s) }}"
                                    @click="startEdit(JSON.parse($el.dataset.service))" 
                                    class="p-2.5 text-violet-600 hover:bg-violet-100 dark:hover:bg-violet-900/40 rounded-xl transition-all"
                                    title="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </button>
                                <form action="{{ route('teams.services.destroy', [$team, $s]) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2.5 text-rose-500 hover:bg-rose-100 dark:hover:bg-rose-900/40 rounded-xl transition-all" 
                                        onclick="return confirm('¿Eliminar este servicio monitorizado? Esta acción no se puede deshacer.')"
                                        title="Eliminar">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
            </div>
        </div>
    </div>
</x-modal>

@push('modals')
<!-- Modal Historial Sentinel -->
<x-modal name="service-incidents-modal" focusable>
    <div class="p-6" x-data="{ 
        loading: true, 
        serviceId: null,
        serviceName: '', 
        incidents: [],
        async loadIncidents(id, name = null) {
            if (!id) return;
            this.serviceId = id;
            if (name) this.serviceName = name;
            
            console.log('Sentinel: Loading incidents for ID:', this.serviceId);
            this.loading = true;
            try {
                const response = await fetch(`/teams/{{ $team->id }}/services/${this.serviceId}/incidents`);
                const data = await response.json();
                this.incidents = data.incidents || [];
                console.log('Sentinel: Data loaded', this.incidents.length);
            } catch (e) {
                console.error('Sentinel: Error loading incidents:', e);
            } finally {
                this.loading = false;
            }
        }
    }" 
    x-on:open-service-incidents.window="loadIncidents($event.detail.id, $event.detail.name); $dispatch('open-modal', 'service-incidents-modal')"
    x-on:refresh-service-incidents.window="if ($event.detail.id == serviceId) loadIncidents(serviceId)">
        
        <div class="flex items-center justify-between mb-6 border-b border-gray-50 dark:border-gray-800 pb-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-amber-500/10 rounded-xl text-amber-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h2 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest">
                    Historial: <span x-text="serviceName" class="text-violet-600"></span>
                </h2>
            </div>
            <button @click="$dispatch('close-modal', 'service-incidents-modal')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <div class="space-y-3 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar">
            <template x-if="loading">
                <div class="py-12 flex flex-col items-center justify-center space-y-4">
                    <svg class="animate-spin h-8 w-8 text-violet-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <p class="text-[10px] font-black uppercase text-gray-400 tracking-widest animate-pulse">Consultando Sentinel...</p>
                </div>
            </template>

            <div x-show="!loading && incidents.length === 0" class="py-12 text-center text-gray-500 italic text-xs bg-gray-50/50 dark:bg-gray-800/30 rounded-3xl border border-dashed border-gray-200 dark:border-gray-700">
                No se han registrado incidencias recientemente.
            </div>

            <template x-for="(incident, index) in incidents" :key="'inc-' + index">
                <div class="p-4 bg-white dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700 rounded-2xl flex items-start justify-between gap-4 hover:shadow-lg hover:shadow-violet-500/5 transition-all">
                    <div class="flex items-start gap-4">
                        <div :class="incident.type === 'down' ? 'bg-red-500/10 text-red-500' : 'bg-emerald-500/10 text-emerald-500'" class="p-2 rounded-xl shrink-0">
                            <template x-if="incident.type === 'down'">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            </template>
                            <template x-if="incident.type === 'up'">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            </template>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-[10px] font-black uppercase tracking-tight" :class="incident.type === 'down' ? 'text-red-600' : 'text-emerald-600'" x-text="incident.type_label"></span>
                                <span class="text-[9px] text-gray-400 font-bold" x-text="incident.date"></span>
                            </div>
                            <p class="text-xs font-bold text-gray-700 dark:text-gray-300 mt-1 break-words whitespace-normal" :title="incident.details" x-text="incident.details || 'Verificación rutinaria de estado'"></p>
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <div class="flex items-center justify-end gap-1.5">
                            <span class="text-[9px] font-black uppercase tracking-widest" :class="incident.reporter_type === 'system' ? 'text-violet-600' : 'text-gray-500'" x-text="incident.reporter"></span>
                            <template x-if="incident.reporter_type === 'human'">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            </template>
                            <template x-if="incident.reporter_type === 'system'">
                                <svg class="w-3.5 h-3.5 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                            </template>
                        </div>
                        <span class="text-[8px] text-gray-400 font-medium italic block mt-0.5" x-text="incident.diff"></span>
                    </div>
                </div>
            </template>
        </div>
    </div>
</x-modal>
@endpush

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
        
        // Handle async report forms for OK/KO buttons
        document.querySelectorAll('.report-service-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const card = this.closest('.service-card');
                const serviceId = card.getAttribute('data-id');
                const button = this.querySelector('button[type="submit"]');
                
                if (!button || button.disabled) return;
                
                // Disable interaction immediately to prevent double click
                button.disabled = true;
                button.classList.add('opacity-50');
                
                const formData = new FormData(this);
                
                fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(async response => {
                    const data = await response.json();
                    
                    if (response.ok && data.success) {
                        // 1. Update the count badge inside the current form
                        const badge = this.querySelector('.report-count');
                        if (badge) {
                            let count = parseInt(badge.innerText.trim()) || 0;
                            count++;
                            badge.innerText = count;
                            badge.classList.remove('hidden');
                        }

                        // 1.b Refresh the incidents modal if it's open or about to be
                        window.dispatchEvent(new CustomEvent('refresh-service-incidents', { 
                            detail: { id: serviceId } 
                        }));
                        
                        // 2. Provide visual Success feedback on button
                        button.title = 'Reportado con éxito';
                        
                        // 3. CRITICAL UX: Re-enable the OPPOSITE button so the user can immediately correct if needed!
                        // Simpler: just find the other button in the same grid parent
                        const siblingForms = card.querySelectorAll('.report-service-form');
                        siblingForms.forEach(siblingForm => {
                            if (siblingForm !== this) {
                                const siblingBtn = siblingForm.querySelector('button[type="submit"]');
                                if (siblingBtn) {
                                    siblingBtn.disabled = false;
                                    siblingBtn.classList.remove('opacity-50', 'opacity-30');
                                    siblingBtn.title = '';
                                }
                            }
                        });

                        // 4. Update dynamic visual elements for service status across the card
                        if (data.new_status_color && data.new_status_label) {
                            const dot = document.querySelector(`[data-service-dot="${serviceId}"]`);
                            const label = document.querySelector(`[data-service-label="${serviceId}"]`);
                            
                            if (dot) {
                                dot.className = `w-1.5 h-1.5 rounded-full bg-${data.new_status_color}-500 ${data.new_status === 'down' ? 'animate-ping' : ''}`;
                            }
                            if (label) {
                                label.innerText = data.new_status_label;
                                label.className = `text-[8px] font-black uppercase tracking-wider text-${data.new_status_color}-600`;
                            }
                        }
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: data.message || 'Reportado con éxito',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true,
                                background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                                color: document.documentElement.classList.contains('dark') ? '#ffffff' : '#1f2937',
                            });
                        }
                    } else {
                        // If the response is error, inform using standard Swals
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'warning',
                                title: data.message || 'Límite de tiempo',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true,
                                background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
                                color: document.documentElement.classList.contains('dark') ? '#ffffff' : '#1f2937',
                            });
                        } else {
                            alert(data.message || 'Límite de tiempo.');
                        }
                        button.disabled = false;
                        button.classList.remove('opacity-50');
                    }
                })
                .catch(error => {
                    console.error('Sentinel error:', error);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'error', title: 'Error de conexión', toast: true, position: 'top-end', timer: 3000 });
                    }
                    button.disabled = false;
                    button.classList.remove('opacity-50');
                });
            });
        });
    });
</script>
@endpush
