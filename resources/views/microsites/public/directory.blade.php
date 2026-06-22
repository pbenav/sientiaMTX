@extends('layouts.public_microsites')

@section('title', 'Directorio de Micrositios')

@section('styles')
<style>
    #map {
        height: 100%;
        width: 100%;
    }
    footer {
        display: none !important;
    }
</style>
@endsection

@section('content')
<div x-data="{ viewMode: 'list' }" 
     x-on:show-map.window="viewMode = 'map'"
     x-effect="if (viewMode === 'map') { $nextTick(() => window.dispatchEvent(new CustomEvent('update-map-size'))); }" 
     class="flex-grow grid grid-cols-1 lg:grid-cols-4 overflow-hidden h-[calc(100vh-4rem)] relative">
    
    <!-- Sidebar de Micrositios -->
    <div :class="viewMode === 'list' ? 'flex' : 'hidden lg:flex'" class="lg:col-span-1 min-h-0 bg-white dark:bg-gray-900 border-r border-gray-150 dark:border-gray-800 flex-col h-full z-10 shadow-lg">
        <div class="p-5 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
            <h2 class="text-lg font-black tracking-tight text-gray-900 dark:text-white heading-font">{{ __('Directorio Público') }}</h2>
            <p class="text-xs text-gray-400 dark:text-gray-500 font-medium mt-1">{{ __('Explora las páginas web y recursos publicados por nuestros equipos.') }}</p>
            
            <form action="{{ route('public.microsites.directory') }}" method="GET" class="relative mt-4">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" name="q" id="search-input" placeholder="{{ __('Buscar micrositio o población...') }}" value="{{ request('q') }}"
                       class="w-full pl-9 pr-4 py-2.5 bg-gray-100/80 dark:bg-gray-800/80 border border-transparent focus:border-pink-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-pink-500/20 rounded-xl text-xs font-semibold text-gray-900 dark:text-white outline-none transition-all placeholder-gray-400 dark:placeholder-gray-500 shadow-sm">
            </form>
        </div>
        
        <div id="microsites-list" class="flex-grow min-h-0 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-850">
            @forelse($microsites as $m)
                <div onclick="if(!event.target.closest('a') && !event.target.closest('button')) window.open('{{ route('public.microsites.show', $m->slug) }}', '_blank')" 
                   class="microsite-item block p-4 hover:bg-pink-50/30 dark:hover:bg-pink-950/15 cursor-pointer transition-colors">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-pink-400 to-rose-500 flex items-center justify-center text-white shrink-0 shadow-sm">
                            <span class="text-xs font-black uppercase">{{ substr($m->title, 0, 2) }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-sm font-black text-gray-900 dark:text-white truncate">
                                <a href="{{ route('public.microsites.show', $m->slug) }}" target="_blank" class="hover:underline">{{ $m->title }}</a>
                            </h3>
                            <p class="text-[10px] font-bold text-pink-600 dark:text-pink-400 uppercase tracking-wider mt-0.5">{{ $m->team->name }}</p>
                            
                            @if($m->city)
                                <div class="flex items-center gap-1 mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    <span class="truncate">{{ $m->city }}</span>
                                </div>
                            @endif

                            <div class="flex items-center justify-between gap-2 mt-2">
                                <span class="flex items-center gap-1 text-[9px] text-gray-400 dark:text-gray-500 font-semibold uppercase tracking-wider">
                                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    {{ number_format($m->views) }} visitas
                                </span>
                                <div class="flex items-center gap-1.5">
                                    @php
                                        $qrUrl = route('public.microsites.show', $m->slug);
                                        $qrCodeSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(300)->margin(1)->color(219, 39, 119)->generate($qrUrl);
                                        $qrCodeSmall = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(28)->margin(0)->color(219, 39, 119)->generate($qrUrl);
                                    @endphp
                                    <a href="data:image/svg+xml;base64,{{ base64_encode($qrCodeSvg) }}" download="qr-micrositio-{{ $m->slug }}.svg" 
                                       class="p-0.5 bg-white border border-gray-200 dark:border-gray-700 hover:border-pink-500 rounded-lg shadow-sm transition-all hover:scale-110"
                                       title="{{ __('Descargar código QR') }}"
                                       onclick="event.stopPropagation();">
                                        {!! preg_replace('/<\?xml.*?\?>\n?/', '', $qrCodeSmall) !!}
                                    </a>
                                @if($m->latitude && $m->longitude)
                                        <button type="button" class="locate-map-btn p-1 text-gray-400 hover:text-pink-600 dark:hover:text-pink-400 rounded-lg transition-colors" title="Ver en el mapa" data-lat="{{ $m->latitude }}" data-lng="{{ $m->longitude }}" data-slug="{{ $m->slug }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                                        </button>
                                @endif
                                </div>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 mt-1 self-center" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center">
                    <p class="text-3xl mb-2">🌐</p>
                    <p class="text-sm font-bold text-gray-500 dark:text-gray-400">{{ __('Sin micrositios') }}</p>
                    <p class="text-xs text-gray-450 dark:text-gray-550 mt-1">{{ __('No hay micrositios públicos disponibles actualmente.') }}</p>
                </div>
            @endforelse
            
            @if($microsites->hasPages())
                <div class="p-4 border-t border-gray-100 dark:border-gray-800">
                    {{ $microsites->links('pagination::simple-tailwind') }}
                </div>
            @endif
        </div>
    </div>

    <!-- Mapa Leaflet -->
    <div :class="viewMode === 'map' ? 'block' : 'hidden lg:block'" class="lg:col-span-3 relative h-full w-full">
        <div id="map" class="w-full h-full"></div>
    </div>

    <!-- Botón flotante para alternar vista en móvil -->
    <div class="lg:hidden fixed bottom-6 left-1/2 -translate-x-1/2 z-[2000]">
        <button @click="viewMode = (viewMode === 'list' ? 'map' : 'list')" 
                class="flex items-center gap-2 px-5 py-3 bg-pink-600 dark:bg-pink-500 hover:bg-pink-500 dark:hover:bg-pink-400 text-white text-xs font-black uppercase tracking-wider rounded-full shadow-2xl shadow-pink-500/30 transition-all active:scale-95 select-none">
            <span x-show="viewMode === 'list'" class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                {{ __('Ver Mapa') }}
            </span>
            <span x-show="viewMode === 'map'" class="flex items-center gap-2" x-cloak>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                {{ __('Ver Lista') }}
            </span>
        </button>
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

        // Icono premium de PIN de mapa para micrositios (Rosa)
        const pinIcon = L.divIcon({
            html: `
                <div class="relative w-8 h-8 flex items-center justify-center">
                    <span class="absolute w-6 h-6 bg-pink-500/35 rounded-full animate-ping opacity-75"></span>
                    <div class="w-7.5 h-7.5 bg-gradient-to-tr from-pink-500 to-rose-600 rounded-full border-2 border-white dark:border-gray-950 flex items-center justify-center text-white shadow-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" /></svg>
                    </div>
                </div>`,
            className: 'custom-pin',
            iconSize: [32, 32],
            iconAnchor: [16, 32],
            popupAnchor: [0, -32]
        });

        // Parse map data injected from controller (only those with coords)
        const mapMicrosites = @json($mapMicrosites);
        const markersGroup = L.featureGroup();
        const markersMap = new Map();

        // Añadir marcadores
        mapMicrosites.forEach(m => {
            if (m.latitude && m.longitude) {
                const marker = L.marker([parseFloat(m.latitude), parseFloat(m.longitude)], { icon: pinIcon });
                
                const popupContent = `
                    <div class="p-3 text-gray-900 dark:text-white font-sans max-w-xs">
                        <h4 class="font-black text-sm heading-font line-clamp-2">${m.title}</h4>
                        <p class="text-[10px] font-bold text-pink-600 dark:text-pink-400 uppercase mt-0.5 tracking-wider">${m.team.name}</p>
                        ${m.city ? `<p class="text-[11px] text-gray-500 dark:text-gray-400 mt-2 font-medium">${m.city}</p>` : ''}
                        <a href="/p/${m.slug}" target="_blank" class="block text-center mt-3 px-4 py-2 bg-pink-600 hover:bg-pink-500 text-white !text-white text-[10px] font-black uppercase tracking-widest rounded-lg shadow-sm transition-all hover:scale-102 select-none">
                            {{ __('Ver Micrositio') }}
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
        if (mapMicrosites.length > 0) {
            map.fitBounds(markersGroup.getBounds(), { padding: [50, 50], maxZoom: 14 });
        }

        // Interacción al hacer clic en el botón de localizar en el mapa
        const locateButtons = document.querySelectorAll('.locate-map-btn');
        locateButtons.forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const lat = parseFloat(this.getAttribute('data-lat'));
                const lng = parseFloat(this.getAttribute('data-lng'));
                const slug = this.getAttribute('data-slug');

                const isMobile = window.innerWidth < 1024;
                if (isMobile) {
                    window.dispatchEvent(new CustomEvent('show-map'));
                }

                if (!isNaN(lat) && !isNaN(lng)) {
                    setTimeout(() => {
                        map.setView([lat, lng], 14, { animate: true, duration: 1 });
                        
                        const marker = markersMap.get(slug);
                        if (marker && markersGroup.hasLayer(marker)) {
                            marker.openPopup();
                        }
                    }, isMobile ? 150 : 0);
                }
            });
        });

        // Escuchar evento para recalcular tamaño del mapa (por ejemplo al cambiar de vista en móvil)
        window.addEventListener('update-map-size', () => {
            if (map) {
                map.invalidateSize();
            }
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
