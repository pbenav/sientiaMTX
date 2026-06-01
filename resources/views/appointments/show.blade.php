<x-app-layout maxWidth="[1600px]">
@section('title', 'Cita '.$appointment->localizador)

<x-slot name="header">
    <div class="flex items-start gap-4 min-w-0 flex-1">
        <a href="{{ route('appointments.list') }}"
           class="mt-1 p-2.5 bg-gray-50 dark:bg-gray-800/50 text-gray-400 hover:text-cyan-600 dark:hover:text-cyan-400 rounded-2xl transition-all shadow-sm border border-gray-100 dark:border-gray-700/50 shrink-0">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <div class="min-w-0 flex-1">
            <p class="text-[10px] font-mono font-bold text-gray-400 mb-1">{{ $appointment->localizador }}</p>
            <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight">
                {{ $appointment->service->name }} — {{ $appointment->visitor->full_name }}
            </h1>
        </div>
    </div>
    @include('appointments.partials.nav')
</x-slot>

<div class="py-8">
    <div class="max-w-full mx-auto sm:px-6 lg:px-8 space-y-6">

        @if(session('success'))
            <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-2xl p-4 text-sm font-bold flex items-center gap-3">
                <svg class="w-5 h-5 shrink-0 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Info principal --}}
            <div class="lg:col-span-2 space-y-5">

                {{-- Datos de la cita --}}
                <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 flex items-center justify-between">
                        <p class="text-xs font-black uppercase tracking-widest text-gray-400">📅 Datos de la Cita</p>
                        <span class="text-[9px] font-black uppercase px-2.5 py-1 rounded-lg
                            @if($appointment->status === 'confirmed') bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400
                            @elseif($appointment->status === 'cancelled') bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400
                            @elseif($appointment->status === 'completed') bg-violet-50 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400
                            @elseif($appointment->status === 'blocked') bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400
                            @else bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 @endif">
                            {{ $appointment->status_label }}
                        </span>
                    </div>
                    <div class="p-6 grid grid-cols-2 gap-5">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Fecha</p>
                            <p class="text-lg font-black text-gray-900 dark:text-white">{{ $appointment->appointment_date->format('d/m/Y') }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Hora</p>
                            <p class="text-lg font-black text-cyan-600 dark:text-cyan-400">{{ substr($appointment->appointment_time, 0, 5) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Servicio</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $appointment->service->name }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Duración</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $appointment->slot_duration_minutes }} minutos</p>
                        </div>
                    </div>
                </div>

                {{-- Datos del ciudadano --}}
                <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                        <p class="text-xs font-black uppercase tracking-widest text-gray-400">👤 Datos del Ciudadano</p>
                    </div>
                    <div class="p-6 grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Nombre completo</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $appointment->visitor->full_name }}</p>
                        </div>
                        @if($appointment->visitor->dni)
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">DNI/NIE</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $appointment->visitor->dni }}</p>
                        </div>
                        @endif
                        @if($appointment->visitor->email)
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Email</p>
                            <a href="mailto:{{ $appointment->visitor->email }}" class="text-sm font-bold text-cyan-600 dark:text-cyan-400 hover:underline">
                                {{ $appointment->visitor->email }}
                            </a>
                        </div>
                        @endif
                        @if($appointment->visitor->phone)
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Teléfono</p>
                            <a href="tel:{{ $appointment->visitor->phone }}" class="text-sm font-bold text-cyan-600 dark:text-cyan-400 hover:underline">
                                {{ $appointment->visitor->phone }}
                            </a>
                        </div>
                        @endif
                        @if($appointment->visitor->city || $appointment->visitor->postal_code)
                        <div class="col-span-2">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Localidad</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ $appointment->visitor->city }}{{ $appointment->visitor->postal_code ? ' ('.$appointment->visitor->postal_code.')' : '' }}
                            </p>
                        </div>
                        @endif
                        @if($appointment->visitor->observations)
                        <div class="col-span-2">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Observaciones del Ciudadano</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 rounded-xl p-3">
                                {{ $appointment->visitor->observations }}
                            </p>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Notas del miembro --}}
                <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                        <p class="text-xs font-black uppercase tracking-widest text-gray-400">📝 Notas Internas</p>
                    </div>
                    <form method="POST" action="{{ route('appointments.update', $appointment) }}" class="p-6">
                        @csrf @method('PATCH')
                        <textarea name="member_notes" rows="3"
                                  class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 focus:ring focus:ring-cyan-500/20 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all resize-y"
                                  placeholder="Notas privadas sobre esta cita (no visibles para el ciudadano)...">{{ $appointment->member_notes }}</textarea>
                        <div class="mt-3 flex justify-end">
                            <button type="submit" class="px-4 py-2 text-xs font-black uppercase tracking-widest bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl transition-all active:scale-95">
                                Guardar notas
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Panel lateral: acciones --}}
            <div class="space-y-5">
                {{-- Acciones rápidas --}}
                <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                        <p class="text-xs font-black uppercase tracking-widest text-gray-400">⚡ Acciones</p>
                    </div>
                    <div class="p-5 space-y-3">
                        {{-- Cambiar estado --}}
                        <form method="POST" action="{{ route('appointments.update', $appointment) }}">
                            @csrf @method('PATCH')
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1.5">Estado</label>
                            <div class="flex gap-2">
                                <select name="status" class="flex-1 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 dark:text-white outline-none transition-all">
                                    @foreach(\App\Models\Appointment::STATUSES as $val => $label)
                                        <option value="{{ $val }}" {{ $appointment->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="px-3 py-2 bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-black rounded-xl transition-all">OK</button>
                            </div>
                        </form>

                        {{-- Cancelar --}}
                        @if(!in_array($appointment->status, ['cancelled', 'blocked']))
                            <form method="POST" action="{{ route('appointments.destroy', $appointment) }}"
                                  onsubmit="return confirm('¿Cancelar esta cita? El ciudadano recibirá un email si consintió.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="w-full py-2.5 text-xs font-black uppercase tracking-widest bg-red-50 hover:bg-red-100 text-red-600 dark:bg-red-900/20 dark:hover:bg-red-900/30 dark:text-red-400 border border-red-200 dark:border-red-900 rounded-xl transition-all">
                                    ❌ Cancelar Cita
                                </button>
                            </form>
                        @endif

                        {{-- Google Calendar --}}
                        @php
                            $gcalUrl = 'https://calendar.google.com/calendar/render?action=TEMPLATE'
                                . '&text=' . urlencode('[CITA] '.$appointment->service->name)
                                . '&dates=' . $appointment->appointment_datetime->format('Ymd\THis')
                                . '/' . $appointment->end_datetime->format('Ymd\THis')
                                . '&details=' . urlencode('Localizador: '.$appointment->localizador."\nCiudadano: ".$appointment->visitor->full_name);
                        @endphp
                        <a href="{{ $gcalUrl }}" target="_blank"
                           class="flex items-center justify-center gap-2 w-full py-2.5 text-xs font-black uppercase tracking-widest bg-blue-50 hover:bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:hover:bg-blue-900/30 dark:text-blue-400 border border-blue-200 dark:border-blue-900 rounded-xl transition-all">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M19.5 3h-2V1.5h-1.5V3h-9V1.5H5.5V3h-2C2.67 3 2 3.67 2 4.5v15C2 20.33 2.67 21 3.5 21h16c.83 0 1.5-.67 1.5-1.5v-15c0-.83-.67-1.5-1.5-1.5zm0 16.5h-16V9h16v10.5zM3.5 7.5h16V4.5h-16V7.5z"/></svg>
                            Añadir a Google Calendar
                        </a>
                    </div>
                </div>

                {{-- Vínculos --}}
                <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                        <p class="text-xs font-black uppercase tracking-widest text-gray-400">🔗 Vínculos</p>
                    </div>
                    <div class="p-5 space-y-3">
                        @if($appointment->task)
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1.5">Tarea Asociada</p>
                                <p class="text-xs font-bold text-gray-700 dark:text-gray-300 truncate">{{ $appointment->task->title }}</p>
                            </div>
                        @endif
                        @if($appointment->expediente)
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1.5">Expediente</p>
                                <p class="text-xs font-bold text-gray-700 dark:text-gray-300 truncate">
                                    [{{ $appointment->expediente->code }}] {{ $appointment->expediente->title }}
                                </p>
                            </div>
                        @endif

                        <div class="pt-3 border-t border-gray-100 dark:border-gray-800">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1.5">Consentimientos GDPR</p>
                            <div class="space-y-1.5">
                                <div class="flex items-center gap-2 text-[10px] font-bold {{ $appointment->visitor->consent_email ? 'text-emerald-600' : 'text-gray-400' }}">
                                    <span>{{ $appointment->visitor->consent_email ? '✓' : '✗' }}</span>
                                    Email de comunicación
                                </div>
                                <div class="flex items-center gap-2 text-[10px] font-bold {{ $appointment->visitor->consent_data ? 'text-emerald-600' : 'text-gray-400' }}">
                                    <span>{{ $appointment->visitor->consent_data ? '✓' : '✗' }}</span>
                                    Tratamiento de datos
                                </div>
                                <div class="flex items-center gap-2 text-[10px] font-bold {{ $appointment->visitor->consent_legal ? 'text-emerald-600' : 'text-gray-400' }}">
                                    <span>{{ $appointment->visitor->consent_legal ? '✓' : '✗' }}</span>
                                    Condiciones legales
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
