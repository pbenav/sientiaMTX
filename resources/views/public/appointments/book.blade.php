@extends('layouts.public_appointments')

@section('title', 'Reservar Cita — ' . $service->name)

@section('content')
<div class="py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-8">
    
    <!-- Breadcrumb / Volver -->
    <a href="{{ route('public.appointments.member', $settings->public_slug) }}" class="inline-flex items-center gap-2 text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 hover:text-cyan-600 dark:hover:text-cyan-400 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        {{ __('Volver a Servicios') }}
    </a>

    <!-- Información del servicio -->
    <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-150 dark:border-gray-800 p-6 shadow-sm flex items-center justify-between gap-6 flex-wrap">
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-cyan-600 dark:text-cyan-400 mb-1">{{ __('Servicio Seleccionado') }}</p>
            <h1 class="text-xl font-black text-gray-900 dark:text-white heading-font tracking-tight">{{ $service->name }}</h1>
            <p class="text-xs text-gray-400 dark:text-gray-500 font-semibold mt-0.5">{{ __('con') }} {{ $settings->display_name ?: $service->user->name }}</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <span class="text-xs font-black text-cyan-700 dark:text-cyan-400 bg-cyan-50 dark:bg-cyan-900/30 px-3 py-1.5 rounded-xl">
                ⏱ {{ $service->duration_minutes }} min
            </span>
            @if($service->price !== null && $service->price_visible)
                <span class="text-xs font-black text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 px-3 py-1.5 rounded-xl">
                    {{ $service->price > 0 ? '€' . number_format($service->price, 2) : __('Gratuito') }}
                </span>
            @endif
        </div>
    </div>

    @if($errors->any())
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 rounded-2xl p-4 text-sm font-bold flex items-start gap-3">
            <svg class="w-5 h-5 shrink-0 text-red-500 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <div class="flex-1">
                <p class="font-black">{{ __('Se han producido errores al procesar tu solicitud:') }}</p>
                <ul class="list-disc list-inside mt-1 font-semibold text-xs text-red-700 dark:text-red-400">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('public.appointments.store', $service) }}" class="space-y-8">
        @csrf

        <!-- Fila 1: Selección de Fecha y Hora (Dos columnas simétricas) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Calendario -->
            <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-150 dark:border-gray-800 shadow-sm p-6 overflow-hidden">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-sm font-black uppercase tracking-wider text-gray-900 dark:text-white heading-font">{{ __('1. Elige la fecha') }}</h3>
                    <div class="flex items-center gap-1">
                        <button type="button" id="prev-month" class="p-2 text-gray-400 hover:text-cyan-600 dark:hover:text-cyan-400 hover:bg-cyan-50 dark:hover:bg-cyan-900/20 rounded-xl transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <span id="calendar-month-year" class="text-xs font-black uppercase tracking-widest text-gray-700 dark:text-gray-300 px-2 select-none"></span>
                        <button type="button" id="next-month" class="p-2 text-gray-400 hover:text-cyan-600 dark:hover:text-cyan-400 hover:bg-cyan-50 dark:hover:bg-cyan-900/20 rounded-xl transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-7 gap-1 text-center mb-2">
                    @foreach(['L', 'M', 'X', 'J', 'V', 'S', 'D'] as $d)
                        <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">{{ $d }}</span>
                    @endforeach
                </div>

                <div id="calendar-days" class="grid grid-cols-7 gap-1 text-center font-bold text-xs">
                    <!-- Rellenado dinámicamente por JS -->
                </div>

                <!-- Inputs ocultos para enviar la fecha -->
                <input type="hidden" id="selected-date-input" name="appointment_date" value="{{ old('appointment_date') }}">
            </div>

            <!-- Horas disponibles -->
            <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-150 dark:border-gray-800 shadow-sm p-6 overflow-hidden">
                <h3 class="text-sm font-black uppercase tracking-wider text-gray-900 dark:text-white heading-font mb-4">{{ __('2. Selecciona la hora') }}</h3>
                
                <div id="no-date-selected" class="p-6 text-center text-gray-450 dark:text-gray-550">
                    <p class="text-3xl mb-2">📅</p>
                    <p class="text-xs font-semibold">{{ __('Selecciona un día en el calendario de la izquierda para ver las horas disponibles.') }}</p>
                </div>

                <div id="slots-loading" class="hidden p-6 text-center">
                    <div class="w-8 h-8 border-4 border-cyan-500 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
                    <p class="text-xs font-semibold text-gray-400 dark:text-gray-500">{{ __('Buscando tramos libres...') }}</p>
                </div>

                <div id="slots-container" class="hidden grid grid-cols-3 sm:grid-cols-4 gap-2.5 max-h-60 overflow-y-auto pr-1">
                    <!-- Rellenado dinámicamente -->
                </div>

                <input type="hidden" id="selected-time-input" name="appointment_time" value="{{ old('appointment_time') }}">
            </div>

        </div>

        <!-- Fila 2: Formulario del Ciudadano (Ancho Completo con Grid Multi-columna) -->
        <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-150 dark:border-gray-800 shadow-sm p-6 space-y-6">
            <div class="border-b border-gray-100 dark:border-gray-800 pb-3">
                <h3 class="text-sm font-black uppercase tracking-wider text-gray-900 dark:text-white heading-font">{{ __('3. Tus Datos') }}</h3>
                <p class="text-[10px] text-gray-400 mt-0.5">{{ __('Por favor, rellena tu información de contacto para confirmar la cita previa') }}</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                @php
                    $rawModality = $service->modality;
                    if (empty($rawModality)) {
                        $modalities = ['presencial'];
                    } else {
                        $modalities = is_array($rawModality) ? $rawModality : [$rawModality];
                        $order = array_keys(\App\Models\AppointmentService::MODALITIES);
                        usort($modalities, function($a, $b) use ($order) {
                            $posA = array_search($a, $order);
                            $posB = array_search($b, $order);
                            if ($posA === false) $posA = 999;
                            if ($posB === false) $posB = 999;
                            return $posA <=> $posB;
                        });
                    }
                @endphp
                @if(count($modalities) > 1)
                    <div class="md:col-span-12">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-gray-450 dark:text-gray-500 mb-2.5">{{ __('Modalidad de la Cita') }} *</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                            @foreach($modalities as $mod)
                                <label class="relative flex items-center justify-center p-4 border border-gray-200 dark:border-gray-700 rounded-2xl cursor-pointer hover:bg-cyan-50/50 dark:hover:bg-cyan-950/20 transition-all group has-[:checked]:bg-cyan-50 has-[:checked]:dark:bg-cyan-900/30 has-[:checked]:border-cyan-500 has-[:checked]:dark:border-cyan-500">
                                    <input type="radio" name="modality" value="{{ $mod }}" required class="peer sr-only">
                                    <div class="text-center">
                                        <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-800 peer-checked:bg-cyan-100 dark:peer-checked:bg-cyan-900 flex items-center justify-center mx-auto mb-2 text-gray-500 peer-checked:text-cyan-600 transition-colors">
                                            @if($mod === 'presencial')
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                            @elseif($mod === 'meet')
                                                <div class="p-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                                    <svg class="w-5 h-5" viewBox="0 0 512 512" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <g transform="translate(0, 45.4)">
                                                            <path d="m289.6 256 49.9 57 67.1 42.9 11.7-99.6-11.7-97.3-68.4 37.7z" fill="#00832d"/>
                                                            <path d="M0 346.7v84.8c0 19.4 15.7 35.1 35.1 35.1h84.8l17.6-64.1-17.6-55.8-58.2-17.6z" fill="#0066da"/>
                                                            <path d="M119.9 45.4 0 165.3l61.7 17.6 58.2-17.6 17.3-55.1z" fill="#e94235"/>
                                                            <path d="M119.9 165.3H0v181.4h119.9z" fill="#2684fc"/>
                                                            <path d="M483.3 96.2 406.6 159v196.9l77 63.1c11.5 9 28.4.8 28.4-13.9V109.7c0-14.8-17.2-22.9-28.7-13.5M289.6 256v90.7H119.9v119.9h251.6c19.4 0 35.1-15.7 35.1-35.1v-75.6z" fill="#00ac47"/>
                                                            <path d="M371.5 45.4H119.9v119.9h169.7V256l117-96.9V80.5c0-19.4-15.7-35.1-35.1-35.1" fill="#ffba00"/>
                                                        </g>
                                                    </svg>
                                                </div>
                                            @elseif($mod === 'jitsi')
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                            @else
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                            @endif
                                        </div>
                                        <p class="text-[11px] font-black text-gray-700 dark:text-gray-300 peer-checked:text-cyan-700 dark:peer-checked:text-cyan-400">
                                            {{ __(\App\Models\AppointmentService::MODALITIES[$mod] ?? ucfirst($mod)) }}
                                        </p>
                                    </div>
                                    <div class="absolute right-3 top-3 opacity-0 peer-checked:opacity-100 transition-opacity text-cyan-500">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @else
                    <input type="hidden" name="modality" value="{{ $modalities[0] }}">
                @endif

                <div class="md:col-span-6">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-450 dark:text-gray-500 mb-1.5">{{ __('Nombre') }} *</label>
                    <input type="text" name="first_name" required value="{{ old('first_name') }}"
                           class="w-full bg-gray-50 dark:bg-gray-850 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-4 py-3 text-xs font-bold outline-none transition-all">
                </div>
                
                <div class="md:col-span-6">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-450 dark:text-gray-500 mb-1.5">{{ __('Apellidos') }} *</label>
                    <input type="text" name="last_name" required value="{{ old('last_name') }}"
                           class="w-full bg-gray-50 dark:bg-gray-850 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-4 py-3 text-xs font-bold outline-none transition-all">
                </div>

                <div class="md:col-span-4">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-450 dark:text-gray-500 mb-1.5">{{ __('DNI / NIE / Pasaporte') }}</label>
                    <input type="text" id="input-dni" name="dni" value="{{ old('dni') }}" autocomplete="off"
                           class="w-full bg-gray-50 dark:bg-gray-850 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-4 py-3 text-xs font-bold outline-none transition-all"
                           placeholder="12345678A, X1234567A...">
                    <p id="hint-dni" class="mt-1 text-[10px] font-semibold hidden"></p>
                    @error('dni') <p class="mt-1 text-[10px] text-red-500 font-bold">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-4">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-450 dark:text-gray-500 mb-1.5">{{ __('Correo Electrónico') }}</label>
                    <input type="email" id="input-email" name="email" value="{{ old('email') }}"
                           class="w-full bg-gray-50 dark:bg-gray-850 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-4 py-3 text-xs font-bold outline-none transition-all"
                           placeholder="nombre@ejemplo.com">
                    <p id="hint-email" class="mt-1 text-[10px] font-semibold hidden"></p>
                    @error('email') <p class="mt-1 text-[10px] text-red-500 font-bold">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-4">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-450 dark:text-gray-500 mb-1.5">{{ __('Teléfono Móvil') }}</label>
                    <input type="tel" id="input-phone" name="phone" value="{{ old('phone') }}"
                           class="w-full bg-gray-50 dark:bg-gray-850 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-4 py-3 text-xs font-bold outline-none transition-all"
                           placeholder="+34 600 000 000">
                    <p id="hint-phone" class="mt-1 text-[10px] font-semibold hidden"></p>
                    @error('phone') <p class="mt-1 text-[10px] text-red-500 font-bold">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-8">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-450 dark:text-gray-500 mb-1.5">
                        {{ __('Municipio') }}
                        <span id="geo-loading" class="hidden ml-2 text-cyan-500 normal-case tracking-normal font-semibold">⟳ {{ __('Detectando ubicación...') }}</span>
                        <span id="geo-detected" class="hidden ml-2 text-emerald-500 normal-case tracking-normal font-semibold">📍 {{ __('Rellenado por ubicación del servicio') }}</span>
                    </label>
                    <input type="text" id="input-city" name="city" value="{{ old('city') }}"
                           class="w-full bg-gray-50 dark:bg-gray-850 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-4 py-3 text-xs font-bold outline-none transition-all">
                </div>

                <div class="md:col-span-4">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-450 dark:text-gray-500 mb-1.5">{{ __('Código Postal') }}</label>
                    <input type="text" id="input-postal" name="postal_code" value="{{ old('postal_code') }}"
                           class="w-full bg-gray-50 dark:bg-gray-850 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-4 py-3 text-xs font-bold outline-none transition-all">
                </div>

                <div class="md:col-span-12">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-450 dark:text-gray-500 mb-1.5">{{ __('Observaciones') }}</label>
                    <textarea name="observations" rows="3"
                              class="w-full bg-gray-50 dark:bg-gray-850 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-4 py-3 text-xs font-bold outline-none transition-all resize-none"
                              placeholder="{{ __('Indica de forma breve el motivo de tu consulta...') }}">{{ old('observations') }}</textarea>
                </div>
            </div>
        </div>

        <!-- Fila 2.5: Información Adicional (Campos Personalizados) -->
        @if(!empty($service->custom_fields))
        <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-150 dark:border-gray-800 shadow-sm p-6 space-y-6">
            <div class="border-b border-gray-100 dark:border-gray-800 pb-3">
                <h3 class="text-sm font-black uppercase tracking-wider text-gray-900 dark:text-white heading-font">{{ __('Información Adicional') }}</h3>
                <p class="text-[10px] text-gray-400 mt-0.5">{{ __('Por favor, completa los siguientes campos requeridos por el servicio') }}</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                @foreach($service->custom_fields as $field)
                    <div class="md:col-span-12 lg:col-span-6">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-gray-450 dark:text-gray-500 mb-1.5">{{ $field['name'] }} @if($field['is_required']) * @endif</label>
                        @if($field['type'] === 'textarea')
                            <textarea name="custom_fields_values[{{ $field['id'] }}]" {{ $field['is_required'] ? 'required' : '' }} rows="3"
                                      class="w-full bg-gray-50 dark:bg-gray-850 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-4 py-3 text-xs font-bold outline-none transition-all resize-none"
                                      >{{ old('custom_fields_values.' . $field['id']) }}</textarea>
                        @else
                            <input type="{{ $field['type'] === 'number' ? 'number' : ($field['type'] === 'date' ? 'date' : 'text') }}" 
                                   name="custom_fields_values[{{ $field['id'] }}]" 
                                   value="{{ old('custom_fields_values.' . $field['id']) }}" 
                                   {{ $field['is_required'] ? 'required' : '' }}
                                   class="w-full bg-gray-50 dark:bg-gray-850 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-4 py-3 text-xs font-bold outline-none transition-all">
                        @endif
                        @error('custom_fields_values.' . $field['id']) <p class="mt-1 text-[10px] text-red-500 font-bold">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Fila 3: Consentimientos GDPR y Envío (Ancho Completo de 12 Columnas para Simetría) -->
        <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-150 dark:border-gray-800 shadow-sm p-6">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                <div class="lg:col-span-8 space-y-4">
                    <div class="border-b border-gray-100 dark:border-gray-800 pb-2 mb-2">
                        <h3 class="text-sm font-black uppercase tracking-wider text-gray-900 dark:text-white heading-font">{{ __('4. Consentimiento y GDPR') }}</h3>
                    </div>
                    
                    <div class="space-y-3">
                        <label class="flex items-start gap-3 cursor-pointer group">
                            <input type="checkbox" name="consent_data" value="1" required class="mt-1 rounded border-gray-300 text-cyan-600 focus:ring-cyan-500">
                            <span class="text-[11px] font-bold text-gray-600 dark:text-gray-400 leading-tight">
                                {{ __('Acepto el tratamiento de mis datos personales únicamente con la finalidad de gestionar la reserva de cita previa conforme al RGPD.') }} *
                            </span>
                        </label>

                        <label class="flex items-start gap-3 cursor-pointer group">
                            <input type="checkbox" name="consent_legal" value="1" required class="mt-1 rounded border-gray-300 text-cyan-600 focus:ring-cyan-500">
                            <span class="text-[11px] font-bold text-gray-600 dark:text-gray-400 leading-tight">
                                {{ __('He leído y acepto el aviso legal y las condiciones de uso de este portal de citas públicas.') }} *
                            </span>
                        </label>

                        <label class="flex items-start gap-3 cursor-pointer group">
                            <input type="checkbox" name="consent_email" value="1" checked class="mt-1 rounded border-gray-300 text-cyan-600 focus:ring-cyan-500">
                            <span class="text-[11px] font-bold text-gray-600 dark:text-gray-400 leading-tight">
                                {{ __('Deseo recibir una confirmación de cita en mi dirección de correo electrónico con los detalles y el localizador único.') }}
                            </span>
                        </label>
                    </div>
                </div>

                <div class="lg:col-span-4 flex justify-center items-center">
                    <button type="submit" id="submit-btn" disabled
                            class="w-full py-4 text-xs font-black uppercase tracking-widest text-white bg-gray-300 dark:bg-gray-800 cursor-not-allowed rounded-2xl shadow-lg shadow-gray-400/10 transition-all select-none">
                        {{ __('Confirmar Reserva') }}
                    </button>
                </div>
            </div>
        </div>

    </form>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const serviceId = "{{ $service->id }}";
        const selectedDateInput = document.getElementById('selected-date-input');
        const selectedTimeInput = document.getElementById('selected-time-input');

        const noDateSelected = document.getElementById('no-date-selected');
        const slotsLoading = document.getElementById('slots-loading');
        const slotsContainer = document.getElementById('slots-container');
        const submitBtn = document.getElementById('submit-btn');

        let currentYear = new Date().getFullYear();
        let currentMonth = new Date().getMonth(); // 0-11
        let availableDays = [];

        // Inicializar calendario
        renderCalendar(currentYear, currentMonth);

        // Cambiar mes
        document.getElementById('prev-month').addEventListener('click', function () {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            renderCalendar(currentYear, currentMonth);
        });

        document.getElementById('next-month').addEventListener('click', function () {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            renderCalendar(currentYear, currentMonth);
        });

        // Cargar días disponibles para el mes
        function loadAvailableDays(year, month) {
            fetch(`/citas/service/${serviceId}/available-days/${year}/${month + 1}`)
                .then(res => res.json())
                .then(data => {
                    availableDays = data.available_days || [];
                    highlightAvailableDays();
                });
        }

        // Renderizar el calendario de un mes
        function renderCalendar(year, month) {
            const container = document.getElementById('calendar-days');
            container.innerHTML = '';

            const monthNames = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
            document.getElementById('calendar-month-year').textContent = `${monthNames[month]} ${year}`;

            const firstDayIndex = new Date(year, month, 1).getDay(); // 0: Dom, 1: Lun...
            // Convertir de Domingo=0 a Lunes=0 para grid español
            const startOffset = firstDayIndex === 0 ? 6 : firstDayIndex - 1;

            const daysInMonth = new Date(year, month + 1, 0).getDate();

            // Rellenar días vacíos al inicio del mes
            for (let i = 0; i < startOffset; i++) {
                const span = document.createElement('span');
                container.appendChild(span);
            }

            // Rellenar días del mes
            for (let day = 1; day <= daysInMonth; day++) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = day;
                
                // Formatear fecha
                const dStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                btn.setAttribute('data-date', dStr);
                
                const dayDate = new Date(year, month, day);
                const today = new Date();
                today.setHours(0,0,0,0);

                if (dayDate < today) {
                    btn.disabled = true;
                    btn.className = "py-2.5 text-gray-300 dark:text-gray-700 cursor-not-allowed select-none rounded-xl";
                } else {
                    btn.className = "calendar-day py-2.5 hover:bg-cyan-50 dark:hover:bg-cyan-950/20 text-gray-500 dark:text-gray-400 rounded-xl transition-all cursor-not-allowed opacity-40";
                    btn.addEventListener('click', function () {
                        selectDate(dStr, btn);
                    });
                }

                container.appendChild(btn);
            }

            loadAvailableDays(year, month);
        }

        // Marcar visualmente los días que tienen slots disponibles
        function highlightAvailableDays() {
            document.querySelectorAll('.calendar-day').forEach(btn => {
                const dateStr = btn.getAttribute('data-date');
                if (availableDays.includes(dateStr)) {
                    btn.className = "calendar-day py-2.5 hover:bg-cyan-50 dark:hover:bg-cyan-950/20 text-gray-800 dark:text-gray-200 font-black rounded-xl transition-all cursor-pointer border border-cyan-100 dark:border-cyan-900/30 bg-cyan-50/10";
                }
            });

            // Si hay una fecha seleccionada previamente en este mes, volver a marcarla
            if (selectedDateInput.value) {
                const activeBtn = document.querySelector(`.calendar-day[data-date="${selectedDateInput.value}"]`);
                if (activeBtn) {
                    activeBtn.className = "calendar-day py-2.5 bg-cyan-600 text-white font-black rounded-xl transition-all cursor-pointer shadow-md";
                }
            }
        }

        // Selección de Fecha
        function selectDate(dateStr, btn) {
            if (!availableDays.includes(dateStr)) return;

            // Limpiar selección previa
            document.querySelectorAll('.calendar-day').forEach(b => {
                const bDate = b.getAttribute('data-date');
                if (availableDays.includes(bDate)) {
                    b.className = "calendar-day py-2.5 hover:bg-cyan-50 dark:hover:bg-cyan-950/20 text-gray-800 dark:text-gray-200 font-black rounded-xl transition-all cursor-pointer border border-cyan-100 dark:border-cyan-900/30 bg-cyan-50/10";
                }
            });

            btn.className = "calendar-day py-2.5 bg-cyan-600 text-white font-black rounded-xl transition-all cursor-pointer shadow-md shadow-cyan-500/20";
            selectedDateInput.value = dateStr;
            selectedTimeInput.value = ''; // Limpiar hora previa

            checkSubmitState();

            // Cargar horas
            loadSlots(dateStr);
        }

        // Cargar slots de hora
        function loadSlots(dateStr) {
            noDateSelected.classList.add('hidden');
            slotsLoading.classList.remove('hidden');
            slotsContainer.classList.add('hidden');
            slotsContainer.innerHTML = '';

            fetch(`/citas/service/${serviceId}/slots/${dateStr}`)
                .then(res => res.json())
                .then(data => {
                    slotsLoading.classList.add('hidden');
                    const slots = data.slots || [];

                    if (slots.length === 0) {
                        noDateSelected.classList.remove('hidden');
                        noDateSelected.querySelector('p:last-child').textContent = 'No hay tramos horarios libres para esta fecha.';
                        return;
                    }

                    slotsContainer.classList.remove('hidden');
                    slots.forEach(slot => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.textContent = slot.time;
                        btn.className = slot.full 
                            ? "py-3 border border-gray-100 dark:border-gray-800 text-gray-300 dark:text-gray-700 bg-gray-50 dark:bg-gray-900 cursor-not-allowed select-none rounded-xl text-xs font-bold"
                            : "slot-btn py-3 border border-gray-200 dark:border-gray-800 hover:border-cyan-500 dark:hover:border-cyan-500 text-gray-800 dark:text-gray-300 font-black rounded-xl transition-all hover:bg-cyan-50/25 dark:hover:bg-cyan-950/10 text-xs";

                        if (!slot.full) {
                            btn.addEventListener('click', function () {
                                selectTime(slot.time, btn);
                            });
                        }

                        slotsContainer.appendChild(btn);
                    });
                });
        }

        // Selección de Hora
        function selectTime(timeStr, btn) {
            document.querySelectorAll('.slot-btn').forEach(b => {
                b.className = "slot-btn py-3 border border-gray-200 dark:border-gray-800 hover:border-cyan-500 dark:hover:border-cyan-500 text-gray-800 dark:text-gray-300 font-black rounded-xl transition-all hover:bg-cyan-50/25 dark:hover:bg-cyan-950/10 text-xs";
            });

            btn.className = "slot-btn py-3 bg-cyan-600 text-white font-black rounded-xl transition-all shadow-md shadow-cyan-500/20 text-xs";
            selectedTimeInput.value = timeStr;
            checkSubmitState();
        }

        // Controlar estado activo del botón de Confirmar
        function checkSubmitState() {
            if (selectedDateInput.value && selectedTimeInput.value) {
                submitBtn.disabled = false;
                submitBtn.className = "w-full py-3.5 text-xs font-black uppercase tracking-widest text-white bg-cyan-600 hover:bg-cyan-500 rounded-2xl shadow-lg shadow-cyan-500/20 active:scale-98 transition-all cursor-pointer";
            } else {
                submitBtn.disabled = true;
                submitBtn.className = "w-full py-3.5 text-xs font-black uppercase tracking-widest text-white bg-gray-300 dark:bg-gray-800 cursor-not-allowed rounded-2xl shadow-lg shadow-gray-400/10 transition-all select-none";
            }
        }
    });
</script>

<script>
    // ---- Validación en tiempo real: DNI/NIE, Email, Teléfono ----
    (function () {
        const DNI_LETTERS = 'TRWAGMYFPDXBNJZSQVHLCKE';

        function setHint(hintEl, inputEl, msg, isError) {
            hintEl.textContent = msg;
            hintEl.className = 'mt-1 text-[10px] font-semibold ' + (isError ? 'text-red-500' : 'text-emerald-500');
            hintEl.classList.remove('hidden');
            inputEl.classList.toggle('border-red-400', isError);
            inputEl.classList.toggle('dark:border-red-600', isError);
            inputEl.classList.toggle('border-emerald-400', !isError);
            inputEl.classList.toggle('dark:border-emerald-600', !isError);
        }

        function clearHint(hintEl, inputEl) {
            hintEl.classList.add('hidden');
            inputEl.classList.remove('border-red-400','dark:border-red-600','border-emerald-400','dark:border-emerald-600');
        }

        function isPassport(v) {
            // Comienza por 2+ letras → pasaporte
            return /^[A-Z]{2,}/i.test(v) && !/^[XYZ]\d{7}[A-Z]$/i.test(v);
        }

        function validateDni(v) {
            v = v.toUpperCase().trim();
            if (!/^\d{8}[A-Z]$/.test(v)) return false;
            return v[8] === DNI_LETTERS[parseInt(v.slice(0, 8)) % 23];
        }

        function validateNie(v) {
            v = v.toUpperCase().trim();
            if (!/^[XYZ]\d{7}[A-Z]$/.test(v)) return false;
            const map = { X: '0', Y: '1', Z: '2' };
            const normalized = map[v[0]] + v.slice(1, 8);
            return v[8] === DNI_LETTERS[parseInt(normalized) % 23];
        }

        // --- DNI/NIE ---
        const dniInput = document.getElementById('input-dni');
        const dniHint  = document.getElementById('hint-dni');
        if (dniInput && dniHint) {
            dniInput.addEventListener('input', function () {
                const v = this.value.trim();
                if (!v) { clearHint(dniHint, dniInput); return; }

                if (isPassport(v) || v.length < 6) {
                    // Parece pasaporte o demasiado corto → no validamos dígito de control
                    setHint(dniHint, dniInput, '🛂 Detectado como pasaporte u otro documento', false);
                    return;
                }

                const upper = v.toUpperCase();
                if (/^[XYZ]/i.test(upper)) {
                    validateNie(upper)
                        ? setHint(dniHint, dniInput, '✓ NIE válido', false)
                        : setHint(dniHint, dniInput, '✗ NIE no válido — revisa el dígito de control', true);
                } else if (/^\d/.test(upper)) {
                    validateDni(upper)
                        ? setHint(dniHint, dniInput, '✓ DNI válido', false)
                        : setHint(dniHint, dniInput, '✗ DNI no válido — revisa el número o la letra', true);
                } else {
                    setHint(dniHint, dniInput, '🛂 Detectado como pasaporte u otro documento', false);
                }
            });
        }

        // --- Email ---
        const emailInput = document.getElementById('input-email');
        const emailHint  = document.getElementById('hint-email');
        if (emailInput && emailHint) {
            emailInput.addEventListener('blur', function () {
                const v = this.value.trim();
                if (!v) { clearHint(emailHint, emailInput); return; }
                const valid = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v);
                valid
                    ? setHint(emailHint, emailInput, '✓ Correo válido', false)
                    : setHint(emailHint, emailInput, '✗ Formato de correo incorrecto', true);
            });
            emailInput.addEventListener('input', function () {
                if (!this.value.trim()) clearHint(emailHint, emailInput);
            });
        }

        // --- Teléfono ---
        const phoneInput = document.getElementById('input-phone');
        const phoneHint  = document.getElementById('hint-phone');
        if (phoneInput && phoneHint) {
            phoneInput.addEventListener('blur', function () {
                const v = this.value.trim();
                if (!v) { clearHint(phoneHint, phoneInput); return; }
                const valid = /^(\+?[\d\s\-\.\(\)]{6,20})$/.test(v);
                valid
                    ? setHint(phoneHint, phoneInput, '✓ Teléfono válido', false)
                    : setHint(phoneHint, phoneInput, '✗ Formato de teléfono incorrecto', true);
            });
            phoneInput.addEventListener('input', function () {
                if (!this.value.trim()) clearHint(phoneHint, phoneInput);
            });
        }
    })();
</script>

<script>
    // ---- Geocodificación inversa: pre-rellenar municipio y CP con coords del servicio ----
    (function () {
        @php
            $memberLat = $service->user->location_lat;
            $memberLng = $service->user->location_lng;
        @endphp

        const lat = {{ $memberLat ? json_encode((float)$memberLat) : 'null' }};
        const lng = {{ $memberLng ? json_encode((float)$memberLng) : 'null' }};

        const cityInput   = document.getElementById('input-city');
        const postalInput = document.getElementById('input-postal');
        const geoLoading  = document.getElementById('geo-loading');
        const geoDetected = document.getElementById('geo-detected');

        // Solo actuar si tenemos coords y los campos están vacíos (sin old())
        if (lat && lng && cityInput && postalInput && !cityInput.value && !postalInput.value) {
            geoLoading.classList.remove('hidden');

            fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json&accept-language=es`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                geoLoading.classList.add('hidden');

                const addr = data.address || {};
                // Municipio: en España puede estar en 'city', 'town', 'village', 'municipality'
                const city       = addr.city || addr.town || addr.village || addr.municipality || addr.county || '';
                const postalCode = addr.postcode || '';

                if (city && !cityInput.value) {
                    cityInput.value = city;
                    cityInput.classList.add('border-emerald-300', 'dark:border-emerald-700');
                }
                if (postalCode && !postalInput.value) {
                    postalInput.value = postalCode;
                    postalInput.classList.add('border-emerald-300', 'dark:border-emerald-700');
                }

                if (city || postalCode) {
                    geoDetected.classList.remove('hidden');
                    // Quitar estilo verde suave al editar manualmente
                    [cityInput, postalInput].forEach(el => {
                        el.addEventListener('input', () => {
                            el.classList.remove('border-emerald-300', 'dark:border-emerald-700');
                            geoDetected.classList.add('hidden');
                        }, { once: true });
                    });
                }
            })
            .catch(() => {
                geoLoading.classList.add('hidden');
            });
        }
    })();
</script>
@endsection

