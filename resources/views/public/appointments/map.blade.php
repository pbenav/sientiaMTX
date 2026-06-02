@extends('layouts.public_appointments')

@section('title', 'Red de Citas Previas')

@section('styles')
<style>
    #map {
        height: calc(100vh - 4rem - 3.5rem);
        min-height: 450px;
    }
</style>
@endsection

@section('content')
<div class="flex-grow grid grid-cols-1 lg:grid-cols-4 overflow-hidden h-[calc(100vh-4rem-3.5rem)]">
    
    <!-- Sidebar de Miembros -->
    <div class="lg:col-span-1 bg-white dark:bg-gray-900 border-r border-gray-150 dark:border-gray-800 flex flex-col h-full z-10 shadow-lg">
        <div class="p-5 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
            <h2 class="text-lg font-black tracking-tight text-gray-900 dark:text-white heading-font">{{ __('Puntos de Atención') }}</h2>
            <p class="text-xs text-gray-400 dark:text-gray-500 font-medium mt-1">{{ __('Selecciona un miembro en el mapa o en la lista para solicitar cita.') }}</p>
            
            <div class="relative mt-4">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" id="search-input" placeholder="{{ __('Buscar por nombre, área o equipo...') }}" 
                       class="w-full pl-9 pr-4 py-2.5 bg-gray-100/80 dark:bg-gray-800/80 border border-transparent focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl text-xs font-semibold text-gray-900 dark:text-white outline-none transition-all placeholder-gray-400 dark:placeholder-gray-500 shadow-sm">
            </div>

            @if(!empty($allTeams))
            <div class="mt-2.5">
                <select id="team-filter" class="w-full px-3 py-2 bg-gray-100/80 dark:bg-gray-800/80 border border-transparent focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl text-[10px] font-black uppercase tracking-wider text-gray-500 dark:text-gray-400 outline-none transition-all cursor-pointer shadow-sm">
                    <option value="">🔍 {{ __('Todos los equipos') }}</option>
                    @foreach($allTeams as $teamName)
                        <option value="{{ $teamName }}">👥 {{ $teamName }}</option>
                    @endforeach
                </select>
            </div>
            @endif
        </div>
        
        <div id="members-list" class="flex-grow overflow-y-auto divide-y divide-gray-100 dark:divide-gray-850">
            @forelse($members as $m)
                <div class="member-item p-4 hover:bg-cyan-50/30 dark:hover:bg-cyan-950/15 cursor-pointer transition-colors" 
                     data-lat="{{ $m['lat'] }}" 
                     data-lng="{{ $m['lng'] }}"
                     data-slug="{{ $m['slug'] }}"
                     data-name="{{ $m['display_name'] }}"
                     data-area="{{ $m['area'] ?? '' }}"
                     data-teams="{{ json_encode($m['teams'] ?? []) }}">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-cyan-400 to-blue-500 flex items-center justify-center text-white shrink-0 shadow-sm">
                            <span class="text-xs font-black uppercase">{{ substr($m['display_name'], 0, 2) }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-sm font-black text-gray-900 dark:text-white truncate">{{ $m['display_name'] }}</h3>
                            @if(!empty($m['area']))
                                <p class="text-[10px] font-bold text-cyan-600 dark:text-cyan-400 uppercase tracking-wider mt-0.5">{{ $m['area'] }}</p>
                            @endif
                            @if(!empty($m['teams']))
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach($m['teams'] as $t)
                                        <span class="px-1.5 py-0.5 bg-cyan-50 dark:bg-cyan-950/45 text-cyan-600 dark:text-cyan-400 text-[8px] font-black uppercase rounded border border-cyan-100/70 dark:border-cyan-900/40 select-none tracking-wide">
                                            {{ $t }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                            <div class="flex items-center gap-3 mt-2 text-[10px] text-gray-400 dark:text-gray-500 font-semibold">
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-cyan-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg>
                                    {{ $m['services'] }} {{ $m['services'] == 1 ? __('servicio') : __('servicios') }}
                                </span>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 mt-1 self-center" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center">
                    <p class="text-3xl mb-2">🗺️</p>
                    <p class="text-sm font-bold text-gray-500 dark:text-gray-400">{{ __('Sin puntos activos') }}</p>
                    <p class="text-xs text-gray-450 dark:text-gray-550 mt-1">{{ __('No hay miembros disponibles con coordenadas GPS en la red.') }}</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Mapa Leaflet -->
    <div class="lg:col-span-3 relative">
        <div id="map" class="w-full h-full"></div>
        
        <!-- Acceso rápido a Videoconferencia -->
        <div x-data="{ open: false }" 
             class="absolute bottom-4 left-4 lg:top-4 lg:bottom-auto lg:left-4 z-[1000] bg-white/95 dark:bg-gray-900/95 rounded-3xl shadow-2xl border border-gray-100 dark:border-gray-800 w-[calc(100%-2rem)] max-w-sm lg:w-80 backdrop-blur-sm transition-all overflow-hidden">
            
            <!-- Cabecera (Siempre visible, actúa como botón de toggle) -->
            <div @click="open = !open" class="p-5 flex items-center justify-between cursor-pointer hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 bg-cyan-50 dark:bg-cyan-950/40 rounded-lg flex items-center justify-center text-cyan-500 border border-cyan-100 dark:border-cyan-900/50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="text-xs font-black uppercase tracking-wider text-gray-900 dark:text-white">{{ __('Acceso a Videocita') }}</h3>
                </div>
                <button type="button" class="text-gray-400 hover:text-cyan-500 transition-colors">
                    <svg x-show="!open" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    <svg x-show="open" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" x-cloak><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                </button>
            </div>
            
            <!-- Contenido (Formulario) -->
            <div x-show="open" x-collapse x-cloak>
                <div class="p-5 pt-0 border-t border-gray-100 dark:border-gray-800/50 mt-1">
                    <p class="text-[10px] text-gray-400 dark:text-gray-500 font-semibold mb-4 leading-relaxed mt-4">{{ __('Si tienes una cita de videoconferencia hoy, introduce tu localizador para acceder.') }}</p>

                    <form method="POST" action="{{ route('public.appointments.video.find') }}" class="space-y-3">
                        @csrf
                        <div>
                            <input type="text" name="localizador" required autocomplete="off"
                                   class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-850 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl text-xs font-mono font-bold uppercase tracking-wide text-gray-950 dark:text-white outline-none transition-all placeholder-gray-400"
                                   placeholder="MTXCITA-XXXXXXXX">
                            @error('localizador_search')
                                <p class="mt-1.5 text-[9px] text-red-500 font-bold leading-tight">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit"
                                class="w-full py-2 bg-cyan-600 hover:bg-cyan-500 text-white text-[10px] font-black uppercase tracking-widest rounded-xl shadow-md shadow-cyan-500/10 transition-all select-none">
                            {{ __('Acceder a la videocita') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Coordenadas iniciales por defecto (España / Centro)
        const defaultLat = 40.416775;
        const defaultLng = -3.703790;
        
        // Crear mapa
        const map = L.map('map', {
            zoomControl: false
        }).setView([defaultLat, defaultLng], 6);

        // Control de zoom en la esquina superior derecha
        L.control.zoom({ position: 'topright' }).addTo(map);

        // Tile layer elegante y adaptativo (OpenStreetMap Carto DB)
        const isDark = document.documentElement.classList.contains('dark');
        const cartoUrl = isDark 
            ? 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png'
            : 'https://mt1.google.com/vt/lyrs=m&hl=es&x={x}&y={y}&z={z}';

        const tiles = L.tileLayer(cartoUrl, {
            attribution: isDark ? '© OpenStreetMap contributors, CartoDB' : '© Google Maps',
            maxZoom: 20
        }).addTo(map);

        // Icono premium de PIN de mapa
        const pinIcon = L.divIcon({
            html: `
                <div class="relative w-8 h-8 flex items-center justify-center">
                    <span class="absolute w-6 h-6 bg-cyan-500/35 rounded-full animate-ping opacity-75"></span>
                    <div class="w-7.5 h-7.5 bg-gradient-to-tr from-cyan-500 to-blue-600 rounded-full border-2 border-white dark:border-gray-950 flex items-center justify-center text-white shadow-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                    </div>
                </div>`,
            className: 'custom-pin',
            iconSize: [32, 32],
            iconAnchor: [16, 32],
            popupAnchor: [0, -32]
        });

        const members = @json($members);
        const markersGroup = L.featureGroup();

        const markersMap = new Map();

        // Añadir marcadores
        members.forEach(m => {
            if (m.lat && m.lng) {
                const marker = L.marker([m.lat, m.lng], { icon: pinIcon });
                
                const popupContent = `
                    <div class="p-3 text-gray-900 dark:text-white font-sans max-w-xs">
                        <h4 class="font-black text-sm heading-font">${m.display_name}</h4>
                        ${m.area ? `<p class="text-[10px] font-bold text-cyan-600 dark:text-cyan-400 uppercase mt-0.5 tracking-wider">${m.area}</p>` : ''}
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-2 font-medium">${m.services} {{ __('servicios disponibles') }}</p>
                        <a href="/citas/${m.slug}" class="block text-center mt-3 px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white !text-white text-[10px] font-black uppercase tracking-widest rounded-lg shadow-sm transition-all hover:scale-102 select-none">
                            {{ __('Pedir Cita Previa') }}
                        </a>
                    </div>
                `;

                marker.bindPopup(popupContent);
                markersMap.set(m.slug, marker);
                marker.addTo(markersGroup);
            }
        });

        markersGroup.addTo(map);

        // Si hay marcadores, ajustar la vista inicial
        if (members.length > 0) {
            map.fitBounds(markersGroup.getBounds(), { padding: [50, 50] });
        }

        // Filtro y Búsqueda Avanzada
        const searchInput = document.getElementById('search-input');
        const teamFilter = document.getElementById('team-filter');
        const items = document.querySelectorAll('.member-item');

        function applyFilters() {
            const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const selectedTeam = teamFilter ? teamFilter.value : '';

            const visibleBounds = L.latLngBounds();
            let visibleCount = 0;

            items.forEach(item => {
                const name = item.getAttribute('data-name').toLowerCase();
                const area = item.getAttribute('data-area').toLowerCase();
                const teams = JSON.parse(item.getAttribute('data-teams') || '[]');
                const slug = item.getAttribute('data-slug');
                const lat = parseFloat(item.getAttribute('data-lat'));
                const lng = parseFloat(item.getAttribute('data-lng'));

                const matchesSearch = name.includes(query) || area.includes(query) || teams.some(t => t.toLowerCase().includes(query));
                const matchesTeam = !selectedTeam || teams.includes(selectedTeam);

                const marker = markersMap.get(slug);

                if (matchesSearch && matchesTeam) {
                    item.style.display = 'block';
                    if (marker) {
                        if (!markersGroup.hasLayer(marker)) {
                            markersGroup.addLayer(marker);
                        }
                        if (!isNaN(lat) && !isNaN(lng)) {
                            visibleBounds.extend([lat, lng]);
                            visibleCount++;
                        }
                    }
                } else {
                    item.style.display = 'none';
                    if (marker && markersGroup.hasLayer(marker)) {
                        markersGroup.removeLayer(marker);
                    }
                }
            });

            // Ajustar vista del mapa de forma suave si cambian los marcadores visibles
            if (visibleCount > 0) {
                map.fitBounds(visibleBounds, { padding: [50, 50], maxZoom: 14 });
            }
        }

        if (searchInput) searchInput.addEventListener('input', applyFilters);
        if (teamFilter) teamFilter.addEventListener('change', applyFilters);

        // Interacción al hacer clic en un elemento de la lista lateral
        items.forEach(item => {
            item.addEventListener('click', function () {
                const lat = parseFloat(this.getAttribute('data-lat'));
                const lng = parseFloat(this.getAttribute('data-lng'));
                const slug = this.getAttribute('data-slug');

                if (!isNaN(lat) && !isNaN(lng)) {
                    map.setView([lat, lng], 14, { animate: true, duration: 1 });
                    
                    const marker = markersMap.get(slug);
                    if (marker && markersGroup.hasLayer(marker)) {
                        marker.openPopup();
                    }
                }
            });
        });

        // Escuchar el cambio de tema para cambiar los estilos del mapa
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === "class") {
                    const isDark = document.documentElement.classList.contains('dark');
                    const newCartoUrl = isDark 
                        ? 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png'
                        : 'https://mt1.google.com/vt/lyrs=m&hl=es&x={x}&y={y}&z={z}';
                    tiles.setUrl(newCartoUrl);
                }
            });
        });

        observer.observe(document.documentElement, { attributes: true });
    });
</script>
@endsection
