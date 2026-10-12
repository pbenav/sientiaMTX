<x-app-layout maxWidth="max-w-none">
@section('title', __('Settings — Appointments'))

<x-slot name="header">
    <div class="flex items-center justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-2 min-w-0">
            <a href="{{ route('appointments.index', $team) }}"
                class="p-1.5 text-gray-400 hover:text-cyan-600 dark:hover:text-cyan-400 rounded-lg transition-all shrink-0">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            @include('teams.partials.breadcrumb')
            <span class="text-gray-300 dark:text-gray-700 mx-1">/</span>
            <h1 class="text-base font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight flex items-center gap-1.5">
                <svg class="h-4 w-4 text-cyan-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="truncate">{{ __('Appointment Portal Settings') }}</span>
            </h1>
        </div>
    </div>
    <div class="mt-2 border-t border-gray-100 dark:border-gray-800 pt-3">
        <x-demo-hint>
            {!! __('Here you configure the global settings for the team\'s appointment portal: the public link to the citizen channel, interactive GPS location, notification automations, and automatic file creation linked to bookings.') !!}
        </x-demo-hint>
    </div>
    @include('appointments.partials.nav')
</x-slot>

<div class="py-8">
    <div class="max-w-full mx-auto sm:px-6 lg:px-8">

        @if(session('success'))
            <div class="mb-6 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-2xl p-4 text-sm font-bold flex items-center gap-3">
                <svg class="w-5 h-5 shrink-0 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                {{ session('success') }}
            </div>
        @endif

        <form id="settings-form" method="POST" action="{{ route('appointments.settings.update', $team) }}" class="space-y-6">
            @csrf @method('PATCH')

            {{-- Identidad pública --}}
            <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                    <p class="text-xs font-black uppercase tracking-widest text-gray-400">🌐 {{ __('Public Identity') }}</p>
                    <p class="text-[10px] text-gray-400 mt-0.5">{{ __('How citizens will see you on the portal') }}</p>
                </div>
                <div class="p-6 space-y-5">
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">{{ __('Public Portal URL') }}</label>
                        <div class="flex items-center gap-0 rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 focus-within:border-cyan-500 focus-within:ring-2 focus-within:ring-cyan-500/20">
                            <span class="bg-gray-100 dark:bg-gray-800 px-4 py-3 text-xs font-mono text-gray-400 whitespace-nowrap border-r border-gray-200 dark:border-gray-700">
                                {{ url('/citas/') }}/
                            </span>
                            <input type="text" name="public_slug" value="{{ old('public_slug', $settings->public_slug ?? '') }}"
                                   placeholder="{{ __('Leave blank to auto-generate') }}"
                                   class="flex-1 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-sm font-mono text-gray-900 dark:text-white outline-none">
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1.5">{{ __('Only lowercase letters, numbers, and hyphens. If left blank, one will be generated automatically. E.g.:') }} <span class="font-mono text-cyan-600">maria-garcia</span></p>
                        @error('public_slug') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">{{ __('Display Name on Portal') }}</label>
                        <input type="text" name="display_name" value="{{ old('display_name', $settings->display_name ?? '') }}"
                               placeholder="{{ __('e.g. Maria Garcia — Information Desk') }}"
                               class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 focus:ring focus:ring-cyan-500/20 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all">
                        <p class="text-[10px] text-gray-400 mt-1.5">{{ __('If empty, your registered username will be used') }}</p>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800/50 rounded-2xl">
                        <div>
                            <p class="text-xs font-black text-gray-700 dark:text-gray-300">{{ __('Appear on Public Portal') }}</p>
                            <p class="text-[10px] text-gray-400 mt-0.5">{{ __('Enable so citizens can find you and book an appointment') }}</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="hidden" name="is_public" value="0">
                            <input type="checkbox" name="is_public" value="1" {{ old('is_public', $settings->is_public ?? false) ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-12 h-7 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-cyan-500"></div>
                        </label>
                    </div>

                    <!-- Sección interactiva premium de Geolocalización (Coordenadas GPS) -->
                    <div class="pt-4 border-t border-gray-100 dark:border-gray-800 space-y-4"
                         x-data="appointmentSettingsGPS()"
                         x-init="initComponent()">
                        <label class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">📍 {{ __('Geographic Location (GPS Coordinates)') }}</label>

                        <!-- Buscador por dirección Nominatim -->
                        <div class="relative">
                            <label class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5 ml-1">{{ __('GPS / Address Search') }}</label>
                            <div class="relative">
                                <input type="text" x-model="searchQuery" @keydown.enter.prevent="searchLocation()" placeholder="{{ __('City, street, landmark...') }}" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-xs text-gray-900 dark:text-white focus:border-cyan-500 focus:ring focus:ring-cyan-500/20 outline-none transition-all">
                                <button type="button" @click="searchLocation()" class="absolute right-3 top-1/2 -translate-y-1/2 p-1.5 text-cyan-600 dark:text-cyan-400 hover:bg-cyan-50 dark:hover:bg-cyan-900/30 rounded-lg transition-all border border-transparent">
                                    <svg x-show="!isSearching" xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <svg x-show="isSearching" style="display:none;" class="animate-spin h-4.5 w-4.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </button>
                            </div>

                            <!-- Search Results Dropdown -->
                            <div x-show="searchResults.length > 0" class="absolute z-[1000] w-full mt-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-2xl overflow-hidden max-h-48 overflow-y-auto">
                                <template x-for="res in searchResults" :key="res.place_id">
                                    <button type="button" @click="selectLocation(res)" class="w-full text-left px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors border-b border-gray-100 dark:border-gray-800 last:border-0">
                                        <p class="text-xs font-bold text-gray-900 dark:text-white truncate" x-text="res.display_name"></p>
                                        <p class="text-[9px] text-gray-400 font-mono" x-text="`${parseFloat(res.lat).toFixed(6)}, ${parseFloat(res.lon).toFixed(6)}`"></p>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Botón de detección por Geolocation del Navegador -->
                        <button type="button" @click="getUserLocation()" class="w-full flex items-center justify-center gap-2 py-2.5 border border-dashed border-cyan-200 dark:border-cyan-800/50 rounded-xl text-cyan-600 dark:text-cyan-400 hover:bg-cyan-50 dark:hover:bg-cyan-900/10 hover:border-cyan-300 transition-all text-xs font-black uppercase tracking-wider">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ __('Auto-detect My GPS Location 📡') }}
                        </button>

                        <!-- Mapa interactivo Leaflet para arrastrar el pin -->
                        <div class="space-y-1">
                            <label class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest ml-1">{{ __('Or fine-tune location by dragging the pin 🗺️') }}</label>
                            <div id="settings-picker-map" class="w-full h-44 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700 shadow-inner overflow-hidden relative z-10"></div>
                        </div>

                        <!-- Inputs de coordenadas -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="location_lat" class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">{{ __('Latitude') }}</label>
                                <input type="number" name="location_lat" id="location_lat" step="any" min="-90" max="90"
                                       x-model="latVal"
                                       @input="syncMap()"
                                       placeholder="Ej: 37.38283"
                                       class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 focus:ring focus:ring-cyan-500/20 rounded-xl px-4 py-2.5 text-xs text-gray-900 dark:text-white outline-none transition-all font-mono">
                                @error('location_lat') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="location_lng" class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">{{ __('Longitude') }}</label>
                                <input type="number" name="location_lng" id="location_lng" step="any" min="-180" max="180"
                                       x-model="lngVal"
                                       @input="syncMap()"
                                       placeholder="Ej: -5.97312"
                                       class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 focus:ring focus:ring-cyan-500/20 rounded-xl px-4 py-2.5 text-xs text-gray-900 dark:text-white outline-none transition-all font-mono">
                                @error('location_lng') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <p class="text-[10px] text-gray-400 leading-relaxed">{{ __('These coordinates position your service point on the public map. If left empty or incomplete, you will not appear on the Citizen Channel map.') }}</p>
                    </div>
                </div>
            </div>

            {{-- Textos del portal --}}
            <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                    <p class="text-xs font-black uppercase tracking-widest text-gray-400">✍️ {{ __('Portal Texts') }}</p>
                </div>
                <div class="p-6 space-y-5">
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">
                            {{ __('Welcome Text') }} <span class="text-[9px] font-bold text-cyan-500 normal-case tracking-normal ml-1">Markdown</span>
                        </label>
                        <textarea name="welcome_text" rows="4"
                                  class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 focus:ring focus:ring-cyan-500/20 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all font-mono resize-y"
                                  placeholder="{{ __('Welcome message that citizens will see when entering your appointment page...') }}">{{ old('welcome_text', $settings->welcome_text ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Parámetros de disponibilidad --}}
            <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                    <p class="text-xs font-black uppercase tracking-widest text-gray-400">⏱ {{ __('Default Parameters') }}</p>
                    <p class="text-[10px] text-gray-400 mt-0.5">{{ __('Applies to services that do not have their own configuration') }}</p>
                </div>
                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">{{ __('Default minimum slot (min)') }}</label>
                        <input type="number" name="default_slot_duration" min="5" max="120" step="5"
                               value="{{ old('default_slot_duration', $settings->default_slot_duration ?? 15) }}"
                               class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 focus:ring focus:ring-cyan-500/20 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">{{ __('Default max appointments per slot') }}</label>
                        <input type="number" name="default_max_per_slot" min="1" max="100"
                               value="{{ old('default_max_per_slot', $settings->default_max_per_slot ?? 1) }}"
                               class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 focus:ring focus:ring-cyan-500/20 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all">
                    </div>
                </div>
            </div>

            {{-- Integraciones y automatismos --}}
            <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                    <p class="text-xs font-black uppercase tracking-widest text-gray-400">⚙️ {{ __('Automations') }}</p>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800/50 rounded-2xl">
                        <div>
                            <p class="text-xs font-black text-gray-700 dark:text-gray-300">{{ __('Automatically create task') }}</p>
                            <p class="text-[10px] text-gray-400 mt-0.5">{{ __('Generates a task in your manager for each confirmed appointment') }}</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="hidden" name="auto_create_task" value="0">
                            <input type="checkbox" name="auto_create_task" value="1" {{ old('auto_create_task', $settings->auto_create_task ?? true) ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-12 h-7 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-cyan-500"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800/50 rounded-2xl">
                        <div>
                            <p class="text-xs font-black text-gray-700 dark:text-gray-300">{{ __('Confirmation email to citizen') }}</p>
                            <p class="text-[10px] text-gray-400 mt-0.5">{{ __('Send email with booking code if citizen consented') }}</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="hidden" name="email_confirmation" value="0">
                            <input type="checkbox" name="email_confirmation" value="1" {{ old('email_confirmation', $settings->email_confirmation ?? true) ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-12 h-7 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-cyan-500"></div>
                        </label>
                    </div>

                    <div class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-2xl">
                        <label class="block text-xs font-black uppercase tracking-widest text-gray-700 dark:text-gray-300 mb-2">{{ __('Preferred Jitsi Server (Video Calls)') }}</label>
                        <select name="jitsi_domain" class="w-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 rounded-xl px-4 py-3 text-sm font-bold text-gray-900 dark:text-white outline-none transition-all">
                            <option value="meet.jit.si" {{ old('jitsi_domain', $settings->jitsi_domain ?? 'meet.jit.si') === 'meet.jit.si' ? 'selected' : '' }}>meet.jit.si ({{ __('Official - 5 min embedded limit') }})</option>
                            <option value="meet.ffmuc.net" {{ old('jitsi_domain', $settings->jitsi_domain ?? 'meet.jit.si') === 'meet.ffmuc.net' ? 'selected' : '' }}>meet.ffmuc.net ({{ __('Open - No embedded limit - May fail in Firefox') }})</option>
                        </select>
                        <p class="text-[10px] text-gray-400 mt-2">
                            {{ __('The meet.jit.si server limits embedded calls to 5 min, but works well when opened externally. The meet.ffmuc.net server allows embedding with no time limit, but Firefox blocks the pop-up window for security. Choose the one that best suits your needs.') }}
                        </p>
                    </div>

                    {{-- Expediente por defecto --}}
                    @if($expedientes->isNotEmpty())
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">{{ __('Default file for appointments') }}</label>
                            <select name="default_expediente_id"
                                    class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 focus:ring focus:ring-cyan-500/20 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all">
                                <option value="">{{ __('No file (task only)') }}</option>
                                @foreach($expedientes as $exp)
                                    <option value="{{ $exp->id }}" {{ old('default_expediente_id', $settings->default_expediente_id ?? '') == $exp->id ? 'selected' : '' }}>
                                        [{{ $exp->code }}] {{ $exp->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="px-8 py-3 text-sm font-black uppercase tracking-widest bg-cyan-600 hover:bg-cyan-500 text-white rounded-2xl shadow-lg shadow-cyan-500/20 transition-all active:scale-95">
                    {{ __('Save Settings') }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function appointmentSettingsGPS() {
        return {
            searchQuery: '',
            isSearching: false,
            searchResults: [],
            mapInstance: null,
            markerInstance: null,
            latVal: @json(old('location_lat', auth()->user()->location_lat ?? 37.17)),
            lngVal: @json(old('location_lng', auth()->user()->location_lng ?? -3.60)),

            async searchLocation() {
                if (!this.searchQuery.trim()) return;
                this.isSearching = true;
                this.searchResults = [];
                try {
                    const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(this.searchQuery)}&limit=5`);
                    this.searchResults = await response.json();
                } catch (e) {
                    console.error('Error searching location:', e);
                } finally {
                    this.isSearching = false;
                }
            },
            selectLocation(res) {
                this.latVal = parseFloat(res.lat);
                this.lngVal = parseFloat(res.lon);
                this.searchResults = [];
                this.searchQuery = res.display_name;
                this.syncMap();
            },
            getUserLocation() {
                if (!navigator.geolocation) {
                    alert('{{ __('swal.alert_geolocation_not_supported') }}');
                    return;
                }
                this.isSearching = true;
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        this.latVal = pos.coords.latitude;
                        this.lngVal = pos.coords.longitude;
                        this.isSearching = false;
                        this.searchQuery = '{{ __('Current location detected 🛰️') }}';
                        this.syncMap();
                    },
                    (err) => {
                        this.isSearching = false;
                        alert('{{ __('swal.alert_location_unavailable') }}');
                    },
                    { enableHighAccuracy: true }
                );
            },
            initMap() {
                if (this.mapInstance) return;

                const lat = parseFloat(this.latVal) || 37.17;
                const lng = parseFloat(this.lngVal) || -3.60;

                if (typeof L === 'undefined') {
                    setTimeout(() => this.initMap(), 300);
                    return;
                }

                this.mapInstance = L.map('settings-picker-map', { zoomControl: true }).setView([lat, lng], 14);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(this.mapInstance);

                this.markerInstance = L.marker([lat, lng], { draggable: true }).addTo(this.mapInstance);

                const updateInputs = (ll) => {
                    this.latVal = parseFloat(ll.lat).toFixed(7);
                    this.lngVal = parseFloat(ll.lng).toFixed(7);
                };

                this.markerInstance.on('dragend', (e) => updateInputs(e.target.getLatLng()));
                this.mapInstance.on('click', (e) => {
                    this.markerInstance.setLatLng(e.latlng);
                    updateInputs(e.latlng);
                });

                setTimeout(() => this.mapInstance.invalidateSize(), 200);
            },
            syncMap() {
                if (this.mapInstance && this.markerInstance && !isNaN(this.latVal) && !isNaN(this.lngVal)) {
                    const lat = parseFloat(this.latVal);
                    const lng = parseFloat(this.lngVal);
                    this.markerInstance.setLatLng([lat, lng]);
                    this.mapInstance.flyTo([lat, lng], 15);
                }
            },
            initComponent() {
                // Cargar Leaflet dinámicamente si no está en el documento
                if (!document.getElementById('leaflet-css')) {
                    const link = document.createElement('link');
                    link.id = 'leaflet-css';
                    link.rel = 'stylesheet';
                    link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                    document.head.appendChild(link);
                }
                if (!document.getElementById('leaflet-js')) {
                    const script = document.createElement('script');
                    script.id = 'leaflet-js';
                    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                    script.onload = () => this.initMap();
                    document.head.appendChild(script);
                } else {
                    setTimeout(() => this.initMap(), 100);
                }
            }
        };
    }
</script>
</x-app-layout>

{{-- ============================================================
     BARRA FLOTANTE DE ACCIONES RÁPIDAS (EDICIÓN)
     ============================================================ --}}
<div id="settings-edit-floating-bar"
     x-data="floatingDraggable"
     @mousedown="startDrag"
     @touchstart.passive="startDrag"
     @window:mousemove="drag"
     @window:touchmove.passive="drag"
     @window:mouseup="stopDrag"
     @window:touchend="stopDrag"
     class="fixed bottom-6 left-1/2 -translate-x-1/2 translate-y-4 z-[800] flex items-center gap-2 px-4 py-2.5 bg-white/93 dark:bg-gray-900/93 backdrop-blur-xl border border-gray-100 dark:border-gray-800 rounded-2xl shadow-2xl opacity-0 pointer-events-none transition-all duration-300 whitespace-nowrap cursor-move"
     :class="isDragging ? 'scale-105 shadow-[0_20px_50px_rgba(0,0,0,0.2)]' : ''">

    {{-- Volver --}}
    <a href="{{ route('appointments.index', $team) }}"
       style="display:flex;align-items:center;gap:0.375rem;font-size:0.75rem;font-weight:700;color:#6b7280;padding:0.375rem 0.75rem;border-radius:0.625rem;text-decoration:none;transition:all 0.15s ease;"
       onmouseover="this.style.color='#0891b2';this.style.background='#ecfeff'"
       onmouseout="this.style.color='#6b7280';this.style.background='transparent'">
        <svg style="width:1rem;height:1rem;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        <span>{{ __('Back') }}</span>
    </a>

    <div style="width:1px;height:1.25rem;background:#e5e7eb;flex-shrink:0"></div>

    {{-- Título truncado --}}
    <span style="font-size:0.75rem;font-weight:900;color:#1f2937;max-width:200px;overflow:hidden;text-overflow:ellipsis;" class="dark:text-gray-300">
        {{ __('Portal Settings') }}
    </span>

    <div style="width:1px;height:1.25rem;background:#e5e7eb;flex-shrink:0"></div>

    {{-- Guardar --}}
    <button type="button"
            onclick="document.getElementById('settings-form').submit()"
       style="display:flex;align-items:center;gap:0.375rem;font-size:0.75rem;font-weight:700;color:#fff;background:#0891b2;padding:0.375rem 0.75rem;border-radius:0.625rem;text-decoration:none;transition:background 0.15s ease;border:none;cursor:pointer;"
       onmouseover="this.style.background='#0e7490'"
       onmouseout="this.style.background='#0891b2'">
        <svg style="width:1rem;height:1rem;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
        </svg>
        <span>{{ __('Save Settings') }}</span>
    </button>
</div>

