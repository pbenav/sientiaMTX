<x-app-layout maxWidth="[1600px]">
@section('title', 'Cita '.$appointment->localizador)

@php
    $nextAppointment = App\Models\Appointment::whereHas('service', function($q) use ($team) {
            $q->where('team_id', $team->id);
        })
        ->where('appointment_date', $appointment->appointment_date)
        ->where(function ($query) use ($appointment) {
            $query->where('appointment_time', '>', $appointment->appointment_time)
                  ->orWhere(function ($q) use ($appointment) {
                      $q->where('appointment_time', '=', $appointment->appointment_time)
                        ->where('id', '>', $appointment->id);
                  });
        })
        ->where('status', 'confirmed')
        ->orderBy('appointment_time', 'asc')
        ->orderBy('id', 'asc')
        ->first();
@endphp

<x-slot name="header">
    <div class="flex items-center gap-3 flex-wrap">
        <div class="flex items-center gap-2 min-w-0 flex-1">
            <a href="{{ route('appointments.list', $team) }}"
                class="p-1.5 text-gray-400 hover:text-cyan-600 dark:hover:text-cyan-400 rounded-lg transition-all shrink-0">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <p class="text-[10px] font-mono font-bold text-gray-400 shrink-0">{{ $appointment->localizador }}</p>
            <span class="text-gray-300 dark:text-gray-700 mx-1">/</span>
            <h1 class="text-base font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight">
                <span class="truncate">{{ $appointment->service->name }} — {{ $appointment->visitor->full_name }}</span>
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

        {{-- Si es videoconferencia, banner de acceso rápido --}}
        @if(in_array($appointment->modality, ['jitsi', 'meet']))
            <div class="bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-150 dark:border-indigo-900/50 rounded-3xl p-5 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/60 rounded-xl flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0">
                        <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-indigo-950 dark:text-indigo-200">Esta cita es una Videoconferencia ({{ ucfirst($appointment->modality) }})</h3>
                        <p class="text-xs text-indigo-600 dark:text-indigo-400 font-medium">Puedes unirte a la sala directamente. El ciudadano necesitará su localizador para acceder.</p>
                    </div>
                </div>
                <a href="{{ route('public.appointments.video.auth', $appointment) }}?localizador={{ $appointment->localizador }}" target="_blank"
                   class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-black uppercase tracking-widest rounded-xl transition-all shadow-md shadow-indigo-600/10 shrink-0 active:scale-95">
                    💻 Iniciar Videoconferencia
                </a>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Info principal --}}
            <div class="lg:col-span-2 space-y-5">

                {{-- Datos de la cita --}}
                <div x-data="{ editing: false, appointment_date: '{{ $appointment->appointment_date->toDateString() }}', appointment_time: '{{ substr($appointment->appointment_time, 0, 5) }}' }"
                     class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 flex items-center justify-between">
                        <p class="text-xs font-black uppercase tracking-widest text-gray-400">📅 Datos de la Cita</p>
                        <div class="flex items-center gap-2">
                            @if($appointment->activity || $appointment->task)
                                <div class="mr-2 border-r border-gray-200 dark:border-gray-700 pr-4">
                                    @include('tasks.partials.task-timer-button', ['task' => $appointment->activity ?? $appointment->task])
                                </div>
                            @endif

                            <button @click="editing = !editing" class="px-3 py-1.5 text-[10px] font-black uppercase tracking-widest bg-cyan-50 dark:bg-cyan-950/20 text-cyan-600 dark:text-cyan-400 border border-cyan-150 dark:border-cyan-900/50 rounded-lg transition-all active:scale-95">
                                <span x-show="!editing">✏️ Editar Cita</span>
                                <span x-show="editing" x-cloak>✕ Cancelar</span>
                            </button>
                            <span class="text-[9px] font-black uppercase px-2.5 py-1 rounded-lg
                                @if($appointment->status === 'confirmed') bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400
                                @elseif($appointment->status === 'cancelled') bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400
                                @elseif($appointment->status === 'completed') bg-violet-50 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400
                                @elseif($appointment->status === 'blocked') bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400
                                @else bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 @endif">
                                {{ $appointment->status_label }}
                            </span>

                            @if($nextAppointment)
                                <a href="{{ route('appointments.show', [$team, $nextAppointment]) }}" 
                                   class="ml-2 px-3 py-1.5 text-[10px] font-black uppercase tracking-widest bg-violet-50 hover:bg-violet-100 dark:bg-violet-900/20 dark:hover:bg-violet-900/30 text-violet-600 dark:text-violet-400 border border-violet-150 dark:border-violet-800/50 rounded-lg transition-all flex items-center gap-1 shadow-sm">
                                    Siguiente
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"></path></svg>
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Vista de lectura -->
                    <div x-show="!editing" class="p-6 grid grid-cols-2 gap-5">
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
                        @if($appointment->activity || $appointment->task)
                        @php
                            $task = $appointment->activity ?? $appointment->task;
                        @endphp
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Duración real (contador)</p>
                            <p class="text-sm font-bold text-cyan-600 dark:text-cyan-400">
                                {{ $task->totalTrackedTimeHuman() }}
                            </p>
                        </div>
                        @endif
                    </div>

                    <!-- Formulario de Edición -->
                    <form x-show="editing" x-cloak method="POST" action="{{ route('appointments.update', [$team, $appointment]) }}" class="p-6 space-y-4">
                        @csrf @method('PATCH')
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-450 dark:text-gray-500 mb-1">Fecha *</label>
                                <input type="date" name="appointment_date" x-model="appointment_date" required
                                       class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 dark:text-white outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-450 dark:text-gray-500 mb-1">Hora *</label>
                                <input type="text" name="appointment_time" x-model="appointment_time" placeholder="HH:MM" required
                                       class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 dark:text-white outline-none transition-all">
                            </div>
                        </div>

                        @if($appointment->activity || $appointment->task)
                        @php
                            $task = $appointment->activity ?? $appointment->task;
                            $trackedSeconds = $task->totalTrackedSeconds();
                            $trackedMinutes = (int) floor($trackedSeconds / 60);
                        @endphp
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-450 dark:text-gray-500 mb-1">Duración real de la cita (minutos del contador)</label>
                            <input type="number" name="tracked_minutes" value="{{ $trackedMinutes }}" min="0"
                                   class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 dark:text-white outline-none transition-all"
                                   placeholder="Introduce la duración en minutos...">
                            <p class="mt-1 text-[10px] text-gray-450 dark:text-gray-500">Si se dejó el contador encendido por error, puedes corregir aquí el total de minutos acumulados.</p>
                        </div>
                        @endif
                        
                        <div class="flex justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                            <button type="button" @click="editing = false" class="px-4 py-2 text-xs font-black uppercase tracking-widest bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl transition-all">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 text-xs font-black uppercase tracking-widest bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl transition-all shadow-md">
                                Guardar cambios
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Datos del ciudadano --}}
                <div x-data="{ editingVisitor: false }" class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 flex items-center justify-between">
                        <p class="text-xs font-black uppercase tracking-widest text-gray-400">👤 Datos del Ciudadano</p>
                        <button @click="editingVisitor = !editingVisitor" class="px-3 py-1.5 text-[10px] font-black uppercase tracking-widest bg-cyan-50 dark:bg-cyan-950/20 text-cyan-600 dark:text-cyan-400 border border-cyan-150 dark:border-cyan-900/50 rounded-lg transition-all active:scale-95">
                            <span x-show="!editingVisitor">✏️ Editar Ciudadano</span>
                            <span x-show="editingVisitor" x-cloak>✕ Cancelar</span>
                        </button>
                    </div>

                    <!-- Vista de lectura -->
                    <div x-show="!editingVisitor" class="p-6 grid grid-cols-2 gap-4">
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

                    <!-- Formulario de Edición -->
                    <form x-show="editingVisitor" x-cloak method="POST" action="{{ route('appointments.update', [$team, $appointment]) }}" class="p-6 space-y-4">
                        @csrf @method('PATCH')
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-450 dark:text-gray-500 mb-1">Nombre Completo *</label>
                                <input type="text" name="visitor_full_name" value="{{ $appointment->visitor->full_name }}" required
                                       class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 dark:text-white outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-450 dark:text-gray-500 mb-1">DNI / NIE</label>
                                <input type="text" name="visitor_dni" value="{{ $appointment->visitor->dni }}"
                                       class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 dark:text-white outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-450 dark:text-gray-500 mb-1">Email</label>
                                <input type="email" name="visitor_email" value="{{ $appointment->visitor->email }}"
                                       class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 dark:text-white outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-450 dark:text-gray-500 mb-1">Teléfono</label>
                                <input type="text" name="visitor_phone" value="{{ $appointment->visitor->phone }}"
                                       class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 dark:text-white outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-450 dark:text-gray-500 mb-1">Localidad / Ciudad</label>
                                <input type="text" name="visitor_city" value="{{ $appointment->visitor->city }}"
                                       class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 dark:text-white outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-450 dark:text-gray-500 mb-1">Código Postal</label>
                                <input type="text" name="visitor_postal_code" value="{{ $appointment->visitor->postal_code }}"
                                       class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 dark:text-white outline-none transition-all">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-450 dark:text-gray-500 mb-1">Observaciones</label>
                                <textarea name="visitor_observations" rows="3"
                                          class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 dark:text-white outline-none transition-all resize-y">{{ $appointment->visitor->observations }}</textarea>
                            </div>
                        </div>
                        
                        <div class="flex justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                            <button type="button" @click="editingVisitor = false" class="px-4 py-2 text-xs font-black uppercase tracking-widest bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl transition-all">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 text-xs font-black uppercase tracking-widest bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl transition-all shadow-md">
                                Guardar ciudadano
                            </button>
                        </div>
                    </form>
                </div>
                {{-- Campos Personalizados --}}
                @if(!empty($appointment->custom_fields_values) && !empty($appointment->service->custom_fields))
                <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                        <p class="text-xs font-black uppercase tracking-widest text-gray-400">📝 Información Adicional</p>
                    </div>
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">
                        @foreach($appointment->service->custom_fields as $field)
                            @if(isset($appointment->custom_fields_values[$field['id']]) && $appointment->custom_fields_values[$field['id']] !== '')
                                <div class="{{ $field['type'] === 'textarea' ? 'sm:col-span-2' : '' }}">
                                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">{{ $field['name'] }}</p>
                                    @if($field['type'] === 'textarea')
                                        <p class="text-sm text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800 p-3 rounded-xl whitespace-pre-wrap">{{ $appointment->custom_fields_values[$field['id']] }}</p>
                                    @elseif($field['type'] === 'date')
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($appointment->custom_fields_values[$field['id']])->format('d/m/Y') }}</p>
                                    @else
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $appointment->custom_fields_values[$field['id']] }}</p>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Notas del miembro --}}
                <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                        <p class="text-xs font-black uppercase tracking-widest text-gray-400">📝 Notas Internas</p>
                    </div>
                    <form method="POST" action="{{ route('appointments.update', [$team, $appointment]) }}" class="p-6">
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
                        {{-- Marcar Completada --}}
                        @if($appointment->status !== 'completed')
                            <form method="POST" action="{{ route('appointments.update', [$team, $appointment]) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="completed">
                                <button type="submit" class="w-full py-2.5 text-xs font-black uppercase tracking-widest bg-emerald-50 hover:bg-emerald-100 text-emerald-600 dark:bg-emerald-900/20 dark:hover:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900 rounded-xl transition-all shadow-sm active:scale-95">
                                    ✓ Marcar Completada
                                </button>
                            </form>
                        @endif

                        {{-- Cambiar estado --}}
                        <form method="POST" action="{{ route('appointments.update', [$team, $appointment]) }}">
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
                            <form method="POST" id="cancel-appointment-form" action="{{ route('appointments.destroy', [$team, $appointment]) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="w-full py-2.5 text-xs font-black uppercase tracking-widest bg-amber-50 hover:bg-amber-100 text-amber-600 dark:bg-amber-900/20 dark:hover:bg-amber-900/30 dark:text-amber-400 border border-amber-200 dark:border-amber-900 rounded-xl transition-all">
                                    ❌ Cancelar Cita
                                </button>
                            </form>
                        @endif

                        <form method="POST" id="delete-appointment-form" action="{{ route('appointments.forceDestroy', [$team, $appointment]) }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="w-full py-2.5 text-xs font-black uppercase tracking-widest bg-red-50 hover:bg-red-100 text-red-600 dark:bg-red-900/20 dark:hover:bg-red-900/30 dark:text-red-400 border border-red-200 dark:border-red-900 rounded-xl transition-all">
                                🗑️ Borrar Permanente
                            </button>
                        </form>

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
                        @if($appointment->activity)
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1.5">Actividad Asociada</p>
                                <a href="{{ route('teams.activities.show', [$team, $appointment->activity]) }}" 
                                   class="text-xs font-bold text-cyan-600 dark:text-cyan-400 hover:underline hover:text-cyan-700 dark:hover:text-cyan-300 transition-colors truncate block">
                                    {{ $appointment->activity->title }}
                                </a>
                            </div>
                        @elseif($appointment->task)
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1.5">Tarea Asociada</p>
                                <a href="{{ route('teams.activities.show', [$team, $appointment->task]) }}" 
                                   class="text-xs font-bold text-cyan-600 dark:text-cyan-400 hover:underline hover:text-cyan-700 dark:hover:text-cyan-300 transition-colors truncate block">
                                    {{ $appointment->task->title }}
                                </a>
                            </div>
                        @endif
                        @if($appointment->expediente)
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1.5">Expediente</p>
                                <a href="{{ route('teams.expedientes.show', [$team, $appointment->expediente]) }}" 
                                   class="text-xs font-bold text-cyan-600 dark:text-cyan-400 hover:underline hover:text-cyan-700 dark:hover:text-cyan-300 transition-colors truncate block">
                                    [{{ $appointment->expediente->code }}] {{ $appointment->expediente->title }}
                                </a>
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

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const cancelForm = document.getElementById('cancel-appointment-form');
        if (cancelForm) {
            cancelForm.addEventListener('submit', function (e) {
                e.preventDefault();
                if (typeof Swal === 'undefined') {
                    if (confirm('¿Cancelar cita previa?')) {
                        HTMLFormElement.prototype.submit.call(cancelForm);
                    }
                    return;
                }
                Swal.fire({
                    title: '¿Cancelar cita previa?',
                    text: 'Indique a continuación el motivo de la cancelación (opcional). El ciudadano recibirá esta información por email si prestó su consentimiento.',
                    input: 'textarea',
                    inputPlaceholder: 'Escriba el motivo de la cancelación aquí...',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, cancelar cita',
                    cancelButtonText: 'No, mantener',
                    customClass: {
                        popup: 'rounded-[2rem] dark:bg-gray-900 border border-gray-150 dark:border-gray-800 p-8',
                        confirmButton: 'px-5 py-3 text-xs font-black uppercase tracking-widest bg-amber-500 hover:bg-amber-600 text-white rounded-xl transition-all shadow-md',
                        cancelButton: 'px-5 py-3 text-xs font-black uppercase tracking-widest bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-all ml-3',
                        input: 'bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 rounded-2xl text-xs font-bold text-gray-900 dark:text-white outline-none focus:ring-2 focus:ring-cyan-500/20 !mx-auto !w-[90%] !my-4 h-24 p-3'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        const reasonInput = document.createElement('input');
                        reasonInput.type = 'hidden';
                        reasonInput.name = 'cancellation_reason';
                        reasonInput.value = result.value || '';
                        cancelForm.appendChild(reasonInput);
                        HTMLFormElement.prototype.submit.call(cancelForm);
                    }
                });
            });
        }

        const deleteForm = document.getElementById('delete-appointment-form');
        if (deleteForm) {
            deleteForm.addEventListener('submit', function (e) {
                e.preventDefault();
                if (typeof Swal === 'undefined') {
                    if (confirm('¿Eliminar permanentemente esta cita de la base de datos?')) {
                        HTMLFormElement.prototype.submit.call(deleteForm);
                    }
                    return;
                }
                Swal.fire({
                    title: '¿Eliminar permanentemente?',
                    text: '⚠️ Esta acción no se puede deshacer y borrará la cita de la base de datos por completo. Indique a continuación el motivo de la anulación definitiva para el correo del ciudadano (opcional).',
                    input: 'textarea',
                    inputPlaceholder: 'Escriba el motivo del borrado definitivo aquí...',
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, borrar del todo',
                    cancelButtonText: 'Cancelar',
                    customClass: {
                        popup: 'rounded-[2rem] dark:bg-gray-900 border border-gray-150 dark:border-gray-800 p-8',
                        confirmButton: 'px-5 py-3 text-xs font-black uppercase tracking-widest bg-red-600 hover:bg-red-700 text-white rounded-xl transition-all shadow-md',
                        cancelButton: 'px-5 py-3 text-xs font-black uppercase tracking-widest bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-all ml-3',
                        input: 'bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 rounded-2xl text-xs font-bold text-gray-900 dark:text-white outline-none focus:ring-2 focus:ring-cyan-500/20 !mx-auto !w-[90%] !my-4 h-24 p-3'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        const reasonInput = document.createElement('input');
                        reasonInput.type = 'hidden';
                        reasonInput.name = 'cancellation_reason';
                        reasonInput.value = result.value || '';
                        deleteForm.appendChild(reasonInput);
                        HTMLFormElement.prototype.submit.call(deleteForm);
                    }
                });
            });
        }
    });
</script>
</x-app-layout>
