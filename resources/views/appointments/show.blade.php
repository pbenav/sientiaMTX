<x-app-layout maxWidth="max-w-none">
@section('title', __('Appointment :code', ['code' => $appointment->localizador]))

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
                        <h3 class="text-sm font-black text-indigo-950 dark:text-indigo-200">{{ __('This appointment is a Video Conference (:modality)', ['modality' => ucfirst($appointment->modality)]) }}</h3>
                        <p class="text-xs text-indigo-600 dark:text-indigo-400 font-medium">{{ __('You can join the room directly. The citizen will need their booking code to access.') }}</p>
                    </div>
                </div>
                <a href="{{ route('public.appointments.video.auth', $appointment) }}?localizador={{ $appointment->localizador }}" target="_blank" rel="noopener"
                   class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-black uppercase tracking-widest rounded-xl transition-all shadow-md shadow-indigo-600/10 shrink-0 active:scale-95">
                    💻 {{ __('Start Video Conference') }}
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
                        <p class="text-xs font-black uppercase tracking-widest text-gray-400">📅 {{ __('Appointment Details') }}</p>
                        <div class="flex items-center gap-2">
                            @if($appointment->activity || $appointment->task)
                                <div class="mr-2 border-r border-gray-200 dark:border-gray-700 pr-4">
                                    @include('tasks.partials.task-timer-button', ['task' => $appointment->activity ?? $appointment->task])
                                </div>
                            @else
                                <form action="{{ route('appointments.start-attention', [$team, $appointment]) }}" method="POST" class="mr-2 border-r border-gray-200 dark:border-gray-700 pr-4">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1.5 text-[10px] font-black uppercase tracking-widest bg-violet-50 text-violet-600 rounded-lg hover:bg-violet-100 flex items-center gap-1 transition-all" title="{{ __('Start attention timer') }}">
                                        ▶ {{ __('Start') }}
                                    </button>
                                </form>
                            @endif

                            @if(!in_array($appointment->status, ['completed', 'cancelled', 'blocked']))
                            <button type="button" id="btn-add-capacity-backend-header" class="px-3 py-1.5 text-[10px] font-black uppercase tracking-widest bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition-all active:scale-95 shadow-sm flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                <span>{{ __('Attend Now') }}</span>
                            </button>
                            @endif
                            
                            <button @click="editing = !editing" class="px-3 py-1.5 text-[10px] font-black uppercase tracking-widest bg-cyan-50 dark:bg-cyan-950/20 text-cyan-600 dark:text-cyan-400 border border-cyan-150 dark:border-cyan-900/50 rounded-lg transition-all active:scale-95">
                                <span x-show="!editing">✏️ {{ __('Edit Appointment') }}</span>
                                <span x-show="editing" x-cloak>✕ {{ __("Cancel") }}</span>
                            </button>
                            <span class="text-[9px] font-black uppercase px-2.5 py-1 rounded-lg
                            @if($appointment->status === 'confirmed') bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400
                                @elseif($appointment->status === 'cancelled') bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400
                                @elseif($appointment->status === 'completed') bg-violet-50 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400
                                @elseif($appointment->status === 'no_show') bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400
                                @elseif($appointment->status === 'blocked') bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400
                                @else bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 @endif">
                                {{ $appointment->status_label }}
                            </span>

                            @if($nextAppointment)
                                <a href="{{ route('appointments.show', [$team, $nextAppointment]) }}" 
                                   class="ml-2 px-3 py-1.5 text-[10px] font-black uppercase tracking-widest bg-violet-50 hover:bg-violet-100 dark:bg-violet-900/20 dark:hover:bg-violet-900/30 text-violet-600 dark:text-violet-400 border border-violet-150 dark:border-violet-800/50 rounded-lg transition-all flex items-center gap-1 shadow-sm">
                                    {{ __('Next') }}
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"></path></svg>
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Vista de lectura -->
                    <div x-show="!editing" class="p-6 grid grid-cols-2 gap-5">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">{{ __('Date') }}</p>
                            <p class="text-lg font-black text-gray-900 dark:text-white">{{ $appointment->appointment_date->format('d/m/Y') }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">{{ __('Time') }}</p>
                            <p class="text-lg font-black text-cyan-600 dark:text-cyan-400">{{ substr($appointment->appointment_time, 0, 5) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">{{ __('Service') }}</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $appointment->service->name }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">{{ __('Duration') }}</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $appointment->slot_duration_minutes }} {{ __('minutes') }}</p>
                        </div>
                        @if($appointment->activity || $appointment->task)
                        @php
                            $task = $appointment->activity ?? $appointment->task;
                        @endphp
                        <div x-data="{
                            taskId: {{ $task->id }},
                            baseTimeHuman: '{{ $task->totalTrackedTimeHuman() }}',
                            get isActive() { return Alpine.store('timer') && Alpine.store('timer').activeTaskId == this.taskId },
                            get elapsed() { return this.isActive ? Alpine.store('timer').elapsed : 0 },
                            formatTime(sec) {
                                if (!sec) return '0s';
                                const h = Math.floor(sec / 3600);
                                const m = Math.floor((sec % 3600) / 60);
                                const s = sec % 60;
                                return (h > 0 ? h + 'h ' : '') + (m > 0 || h > 0 ? m + 'm ' : '') + s + 's';
                            },
                            init() {
                                window.addEventListener('time-updated', (e) => {
                                    if (e.detail.taskId == this.taskId && e.detail.humanTime) {
                                        this.baseTimeHuman = e.detail.humanTime;
                                    }
                                });
                            }
                        }">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">{{ __('Actual duration (timer)') }}</p>
                            <p class="text-sm font-bold text-cyan-600 dark:text-cyan-400">
                                <template x-if="!isActive">
                                    <span x-text="baseTimeHuman"></span>
                                </template>
                                <template x-if="isActive">
                                    <span class="text-violet-600 dark:text-violet-400 animate-pulse font-extrabold" x-text="formatTime(elapsed)"></span>
                                </template>
                            </p>
                        </div>
                        @endif
                    </div>

                    <!-- Formulario de Edición -->
                    <form x-show="editing" x-cloak method="POST" action="{{ route('appointments.update', [$team, $appointment]) }}" class="p-6 space-y-4">
                        @csrf @method('PATCH')
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-450 dark:text-gray-500 mb-1">{{ __('Date *') }}</label>
                                <input type="date" name="appointment_date" x-model="appointment_date" required
                                       class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 dark:text-white outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-450 dark:text-gray-500 mb-1">{{ __('Time *') }}</label>
                                <input type="text" name="appointment_time" x-model="appointment_time" placeholder="HH:MM" required
                                       class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 dark:text-white outline-none transition-all">
                            </div>
                        </div>

                        @if($appointment->activity || $appointment->task)
                        @php
                            $task = $appointment->activity ?? $appointment->task;
                            $trackedSeconds = $task->totalTrackedSeconds();
                            $h = floor($trackedSeconds / 3600);
                            $m = floor(($trackedSeconds % 3600) / 60);
                            $s = $trackedSeconds % 60;
                            $timeFormatted = sprintf('%02d:%02d:%02d', $h, $m, $s);
                        @endphp
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-450 dark:text-gray-500 mb-1">{{ __('Actual appointment duration (HH:MM:SS)') }}</label>
                            <input type="text" name="tracked_time_format" value="{{ $timeFormatted }}"
                                   pattern="^\d{1,3}:\d{2}:\d{2}$"
                                   class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 dark:text-white outline-none transition-all font-mono"
                                   placeholder="HH:MM:SS" title="{{ __('Format: hours:minutes:seconds (e.g. 01:30:00)') }}">
                            <p class="mt-1 text-[10px] text-gray-450 dark:text-gray-500">{{ __('If the timer was left running by mistake, you can correct the total accumulated time here using HH:MM:SS format.') }}</p>
                        </div>
                        @endif
                        
                        <div class="flex justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                            <button type="button" @click="editing = false" class="px-4 py-2 text-xs font-black uppercase tracking-widest bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl transition-all">
                                {{ __("Cancel") }}
                            </button>
                            <button type="submit" class="px-4 py-2 text-xs font-black uppercase tracking-widest bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl transition-all shadow-md">
                                {{ __('Save Changes') }}
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Datos del ciudadano --}}
                <div x-data="{ editingVisitor: false }" class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 flex items-center justify-between">
                        <p class="text-xs font-black uppercase tracking-widest text-gray-400">👤 {{ __('Citizen Details') }}</p>
                        <button @click="editingVisitor = !editingVisitor" class="px-3 py-1.5 text-[10px] font-black uppercase tracking-widest bg-cyan-50 dark:bg-cyan-950/20 text-cyan-600 dark:text-cyan-400 border border-cyan-150 dark:border-cyan-900/50 rounded-lg transition-all active:scale-95">
                            <span x-show="!editingVisitor">✏️ {{ __('Edit Citizen') }}</span>
                            <span x-show="editingVisitor" x-cloak>✕ {{ __("Cancel") }}</span>
                        </button>
                    </div>

                    <!-- Vista de lectura -->
                    <div x-show="!editingVisitor" class="p-6 grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">{{ __('Full Name') }}</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $appointment->visitor->full_name }}</p>
                        </div>
                        @if($appointment->visitor->dni)
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">{{ __('ID / NIE') }}</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white" style="text-transform: uppercase">{{ $appointment->visitor->dni }}</p>
                        </div>
                        @endif
                        @if($appointment->visitor->email)
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">{{ __('Email') }}</p>
                            <a href="mailto:{{ $appointment->visitor->email }}" class="text-sm font-bold text-cyan-600 dark:text-cyan-400 hover:underline" style="text-transform: lowercase">
                                {{ $appointment->visitor->email }}
                            </a>
                        </div>
                        @endif
                        @if($appointment->visitor->phone)
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">{{ __('Phone') }}</p>
                            <a href="tel:{{ $appointment->visitor->phone }}" class="text-sm font-bold text-cyan-600 dark:text-cyan-400 hover:underline">
                                {{ $appointment->visitor->phone }}
                            </a>
                        </div>
                        @endif
                        @if($appointment->visitor->city || $appointment->visitor->postal_code)
                        <div class="col-span-2">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">{{ __('City') }}</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ $appointment->visitor->city }}{{ $appointment->visitor->postal_code ? ' ('.$appointment->visitor->postal_code.')' : '' }}
                            </p>
                        </div>
                        @endif
                        @if($appointment->visitor->observations)
                        <div class="col-span-2">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">{{ __('Citizen Observations') }}</p>
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
                                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-450 dark:text-gray-500 mb-1">{{ __('Full Name *') }}</label>
                                <input type="text" name="visitor_full_name" value="{{ $appointment->visitor->full_name }}" required
                                       class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 dark:text-white outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-450 dark:text-gray-500 mb-1">{{ __('ID / NIE') }}</label>
                                <input type="text" name="visitor_dni" value="{{ old('visitor_dni', strtoupper($appointment->visitor->dni ?? '')) }}"
                                       style="text-transform: uppercase"
                                       oninput="this.value = this.value.toUpperCase()"
                                       class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 dark:text-white outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-450 dark:text-gray-500 mb-1">{{ __('Email') }}</label>
                                <input type="email" name="visitor_email" value="{{ old('visitor_email', strtolower($appointment->visitor->email ?? '')) }}"
                                       style="text-transform: lowercase"
                                       oninput="this.value = this.value.toLowerCase()"
                                       class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 dark:text-white outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-450 dark:text-gray-500 mb-1">{{ __('Phone') }}</label>
                                <input type="text" name="visitor_phone" value="{{ $appointment->visitor->phone }}"
                                       class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 dark:text-white outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-450 dark:text-gray-500 mb-1">{{ __('City / Municipality') }}</label>
                                <input type="text" name="visitor_city" value="{{ $appointment->visitor->city }}"
                                       class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 dark:text-white outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-450 dark:text-gray-500 mb-1">{{ __('Postal Code') }}</label>
                                <input type="text" name="visitor_postal_code" value="{{ $appointment->visitor->postal_code }}"
                                       class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 dark:text-white outline-none transition-all">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-450 dark:text-gray-500 mb-1">{{ __('Observations') }}</label>
                                <textarea name="visitor_observations" rows="3"
                                          class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 dark:text-white outline-none transition-all resize-y">{{ $appointment->visitor->observations }}</textarea>
                            </div>
                        </div>

                        {{-- Campos personalizados del servicio --}}
                        @if(!empty($appointment->service->custom_fields))
                        <div class="sm:col-span-2 border-t border-gray-100 dark:border-gray-800 pt-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-3">📝 {{ __('Additional Service Information') }}</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @foreach($appointment->service->custom_fields as $field)
                                    <div class="{{ ($field['type'] ?? 'text') === 'textarea' ? 'sm:col-span-2' : '' }}">
                                        <label class="block text-[10px] font-black uppercase tracking-widest text-gray-450 dark:text-gray-500 mb-1">
                                            {{ $field['name'] }}
                                            @if(!empty($field['required'])) <span class="text-red-400">*</span> @endif
                                        </label>
                                        @php
                                            $cfValue = $appointment->custom_fields_values[$field['id']] ?? '';
                                        @endphp
                                        @if(($field['type'] ?? 'text') === 'textarea')
                                            <textarea name="custom_fields_values[{{ $field['id'] }}]" rows="3"
                                                      class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 dark:text-white outline-none transition-all resize-y">{{ $cfValue }}</textarea>
                                        @elseif(($field['type'] ?? 'text') === 'select' && !empty($field['options']))
                                            <select name="custom_fields_values[{{ $field['id'] }}]"
                                                    class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 dark:text-white outline-none transition-all">
                                                <option value="">— {{ __('Select') }} —</option>
                                                @foreach($field['options'] as $opt)
                                                    <option value="{{ $opt }}" {{ $cfValue === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                                @endforeach
                                            </select>
                                        @elseif(($field['type'] ?? 'text') === 'date')
                                            <input type="date" name="custom_fields_values[{{ $field['id'] }}]" value="{{ $cfValue }}"
                                                   class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 dark:text-white outline-none transition-all">
                                        @elseif(($field['type'] ?? 'text') === 'checkbox')
                                            <label class="flex items-center gap-2 cursor-pointer mt-1">
                                                <input type="hidden" name="custom_fields_values[{{ $field['id'] }}]" value="0">
                                                <input type="checkbox" name="custom_fields_values[{{ $field['id'] }}]" value="1" {{ $cfValue ? 'checked' : '' }}
                                                       class="rounded border-gray-300 dark:border-gray-600 text-cyan-600 focus:ring-cyan-500">
                                                <span class="text-xs text-gray-600 dark:text-gray-400">{{ $field['name'] }}</span>
                                            </label>
                                        @else
                                            <input type="text" name="custom_fields_values[{{ $field['id'] }}]" value="{{ $cfValue }}"
                                                   class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700/80 focus:border-cyan-500 focus:bg-white dark:focus:bg-gray-950 focus:ring-2 focus:ring-cyan-500/20 rounded-xl px-3 py-2 text-xs font-bold text-gray-900 dark:text-white outline-none transition-all">
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        
                        <div class="flex justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                            <button type="button" @click="editingVisitor = false" class="px-4 py-2 text-xs font-black uppercase tracking-widest bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl transition-all">
                                {{ __("Cancel") }}
                            </button>
                            <button type="submit" class="px-4 py-2 text-xs font-black uppercase tracking-widest bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl transition-all shadow-md">
                                {{ __('Save Citizen') }}
                            </button>
                        </div>
                    </form>
                </div>
                {{-- Campos Personalizados --}}
                @if(!empty($appointment->custom_fields_values) && !empty($appointment->service->custom_fields))
                <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                        <p class="text-xs font-black uppercase tracking-widest text-gray-400">📝 {{ __('Additional Information') }}</p>
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
                        <p class="text-xs font-black uppercase tracking-widest text-gray-400">📝 {{ __('Internal Notes') }}</p>
                    </div>
                    <form method="POST" action="{{ route('appointments.update', [$team, $appointment]) }}" class="p-6">
                        @csrf @method('PATCH')
                        <textarea name="member_notes" rows="3"
                                  class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 focus:ring focus:ring-cyan-500/20 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all resize-y"
                                  placeholder="{{ __('Private notes about this appointment (not visible to citizen)...') }}">{{ $appointment->member_notes }}</textarea>
                        <div class="mt-3 flex justify-end">
                            <button type="submit" class="px-4 py-2 text-xs font-black uppercase tracking-widest bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl transition-all active:scale-95">
                                {{ __('Save Notes') }}
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
                        <p class="text-xs font-black uppercase tracking-widest text-gray-400">⚡ {{ __('Actions') }}</p>
                    </div>
                    <div class="p-5 space-y-3">
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400">{{ __('Appointment Status') }}</label>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                {{-- Confirmada --}}
                                <form method="POST" action="{{ route('appointments.update', [$team, $appointment]) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="confirmed">
                                    <button type="submit" title="{{ __('Confirmed') }}" class="w-full py-2 px-1 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all shadow-sm active:scale-95 border
                                        {{ $appointment->status === 'confirmed' 
                                            ? 'bg-cyan-500 text-white border-cyan-600 dark:bg-cyan-600 dark:border-cyan-500' 
                                            : 'bg-cyan-50 hover:bg-cyan-100 text-cyan-600 border-cyan-200 dark:bg-cyan-900/20 dark:hover:bg-cyan-900/30 dark:text-cyan-400 dark:border-cyan-800' }}">
                                        👍 {{ __('Conf.') }}
                                    </button>
                                </form>

                                {{-- Completada --}}
                                <form method="POST" action="{{ route('appointments.update', [$team, $appointment]) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="completed">
                                    <button type="submit" title="{{ __('Completed') }}" class="w-full py-2 px-1 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all shadow-sm active:scale-95 border
                                        {{ $appointment->status === 'completed' 
                                            ? 'bg-emerald-500 text-white border-emerald-600 dark:bg-emerald-600 dark:border-emerald-500' 
                                            : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-600 border-emerald-200 dark:bg-emerald-900/20 dark:hover:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800' }}">
                                        ✓ {{ __('Done') }}
                                    </button>
                                </form>

                                {{-- No Presentado --}}
                                <form method="POST" action="{{ route('appointments.update', [$team, $appointment]) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="no_show">
                                    <button type="submit" title="{{ __('No Show') }}" class="w-full py-2 px-1 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all shadow-sm active:scale-95 border
                                        {{ $appointment->status === 'no_show' 
                                            ? 'bg-rose-500 text-white border-rose-600 dark:bg-rose-600 dark:border-rose-500' 
                                            : 'bg-rose-50 hover:bg-rose-100 text-rose-600 border-rose-200 dark:bg-rose-900/20 dark:hover:bg-rose-900/30 dark:text-rose-400 dark:border-rose-800' }}">
                                        🤷 {{ __('Absent') }}
                                    </button>
                                </form>

                                {{-- Cancelada --}}
                                <form method="POST" id="cancel-appointment-form" action="{{ route('appointments.destroy', [$team, $appointment]) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" title="{{ __('Cancelled') }}" class="w-full py-2 px-1 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all shadow-sm active:scale-95 border
                                        {{ $appointment->status === 'cancelled' 
                                            ? 'bg-amber-500 text-white border-amber-600 dark:bg-amber-600 dark:border-amber-500' 
                                            : 'bg-amber-50 hover:bg-amber-100 text-amber-600 border-amber-200 dark:bg-amber-900/20 dark:hover:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800' }}">
                                        ❌ {{ __('Canc.') }}
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-gray-100 dark:border-gray-800">
                            <form method="POST" id="delete-appointment-form" action="{{ route('appointments.forceDestroy', [$team, $appointment]) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="w-full py-2 text-[10px] sm:text-xs font-black uppercase tracking-widest bg-red-50 hover:bg-red-100 text-red-600 dark:bg-red-900/20 dark:hover:bg-red-900/30 dark:text-red-400 border border-red-200 dark:border-red-900 rounded-xl transition-all">
                                    🗑️ {{ __('Delete Permanently') }}
                                </button>
                            </form>
                        </div>
                        
                        {{-- Ampliar Aforo / Atender Ahora --}}
                        @if(!in_array($appointment->status, ['completed', 'cancelled', 'blocked']))
                        <div class="pt-4 border-t border-gray-100 dark:border-gray-800">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1.5">{{ __('Attend Appointment Now') }}</label>
                            <button type="button" id="btn-add-capacity-backend" class="w-full py-2.5 text-[10px] sm:text-xs font-black uppercase tracking-widest bg-amber-500 hover:bg-amber-600 text-white rounded-xl transition-all shadow-md active:scale-95 flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                {{ __('Attend Now (Move to Today)') }}
                            </button>
                            <p class="text-[9px] text-gray-400 mt-1.5 leading-tight text-center">{{ __('Moves this appointment to the current moment (now) and automatically authorizes extra capacity to attend immediately.') }}</p>
                        </div>
                        @endif

                        {{-- Google Calendar --}}
                        @php
                            $gcalUrl = 'https://calendar.google.com/calendar/render?action=TEMPLATE'
                                . '&text=' . urlencode('[CITA] '.$appointment->service->name)
                                . '&dates=' . $appointment->appointment_datetime->format('Ymd\THis')
                                . '/' . $appointment->end_datetime->format('Ymd\THis')
                                . '&details=' . urlencode('Localizador: '.$appointment->localizador."\nCiudadano: ".$appointment->visitor->full_name);
                        @endphp
                        <a href="{{ $gcalUrl }}" target="_blank" rel="noopener"
                           class="flex items-center justify-center gap-2 w-full py-2.5 text-xs font-black uppercase tracking-widest bg-blue-50 hover:bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:hover:bg-blue-900/30 dark:text-blue-400 border border-blue-200 dark:border-blue-900 rounded-xl transition-all">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M19.5 3h-2V1.5h-1.5V3h-9V1.5H5.5V3h-2C2.67 3 2 3.67 2 4.5v15C2 20.33 2.67 21 3.5 21h16c.83 0 1.5-.67 1.5-1.5v-15c0-.83-.67-1.5-1.5-1.5zm0 16.5h-16V9h16v10.5zM3.5 7.5h16V4.5h-16V7.5z"/></svg>
                            {{ __('Add to Google Calendar') }}
                        </a>
                    </div>
                </div>

                {{-- Vínculos --}}
                <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                        <p class="text-xs font-black uppercase tracking-widest text-gray-400">🔗 {{ __('Links') }}</p>
                    </div>
                    <div class="p-5 space-y-3">
                        @if($appointment->activity)
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1.5">{{ __('Associated Activity') }}</p>
                                <a href="{{ route('teams.activities.show', [$team, $appointment->activity]) }}" 
                                   class="text-xs font-bold text-cyan-600 dark:text-cyan-400 hover:underline hover:text-cyan-700 dark:hover:text-cyan-300 transition-colors truncate block">
                                    {{ $appointment->activity->title }}
                                </a>
                            </div>
                        @elseif($appointment->task)
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1.5">{{ __('Associated Task') }}</p>
                                <a href="{{ route('teams.activities.show', [$team, $appointment->task]) }}" 
                                   class="text-xs font-bold text-cyan-600 dark:text-cyan-400 hover:underline hover:text-cyan-700 dark:hover:text-cyan-300 transition-colors truncate block">
                                    {{ $appointment->task->title }}
                                </a>
                            </div>
                        @endif
                        @if($appointment->expediente)
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1.5">{{ __('File') }}</p>
                                <a href="{{ route('teams.expedientes.show', [$team, $appointment->expediente]) }}" 
                                   class="text-xs font-bold text-cyan-600 dark:text-cyan-400 hover:underline hover:text-cyan-700 dark:hover:text-cyan-300 transition-colors truncate block">
                                    [{{ $appointment->expediente->code }}] {{ $appointment->expediente->title }}
                                </a>
                            </div>
                        @endif
                        <div class="pt-3 border-t border-gray-100 dark:border-gray-800">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1.5">{{ __('GDPR Consents') }}</p>
                            <div class="space-y-1.5">
                                <div class="flex items-center gap-2 text-[10px] font-bold {{ $appointment->visitor->consent_email ? 'text-emerald-600' : 'text-gray-400' }}">
                                    <span>{{ $appointment->visitor->consent_email ? '✓' : '✗' }}</span>
                                    {{ __('Communication email') }}
                                </div>
                                <div class="flex items-center gap-2 text-[10px] font-bold {{ $appointment->visitor->consent_data ? 'text-emerald-600' : 'text-gray-400' }}">
                                    <span>{{ $appointment->visitor->consent_data ? '✓' : '✗' }}</span>
                                    {{ __('Data processing') }}
                                </div>
                                <div class="flex items-center gap-2 text-[10px] font-bold {{ $appointment->visitor->consent_legal ? 'text-emerald-600' : 'text-gray-400' }}">
                                    <span>{{ $appointment->visitor->consent_legal ? '✓' : '✗' }}</span>
                                    {{ __('Legal terms') }}
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
                    if (confirm('{{ __('swal.confirm_cancel_appointment') }}')) {
                        HTMLFormElement.prototype.submit.call(cancelForm);
                    }
                    return;
                }
                Swal.fire({
                    title: '{{ __('Cancel appointment?') }}',
                    text: '{{ __('Enter the reason for cancellation (optional). The citizen will receive this information by email if they consented.') }}',
                    input: 'textarea',
                    inputPlaceholder: '{{ __('Enter cancellation reason here...') }}',
                    showCancelButton: true,
                    confirmButtonText: '{{ __('Yes, cancel appointment') }}',
                    cancelButtonText: '{{ __('No, keep') }}',
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
                    if (confirm('{{ __('swal.confirm_delete_appointment_permanently') }}')) {
                        HTMLFormElement.prototype.submit.call(deleteForm);
                    }
                    return;
                }
                Swal.fire({
                    title: '{{ __('Delete permanently?') }}',
                    text: '{{ __('This action cannot be undone and will delete the appointment from the database completely. Enter the cancellation reason for the citizen email (optional).') }}',
                    input: 'textarea',
                    inputPlaceholder: '{{ __('Enter deletion reason here...') }}',
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonText: '{{ __('Yes, delete completely') }}',
                    cancelButtonText: '{{ __("Cancel") }}',
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
        
        // Manejar el botón de Atender Ahora
        const addCapacityBtn = document.getElementById('btn-add-capacity-backend');
        const addCapacityBtnHeader = document.getElementById('btn-add-capacity-backend-header');
        
        function handleAddCapacityClick() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '{{ __('Attend right now?') }}',
                    text: '{{ __('This appointment will be moved to today, right now. An extra spot will be automatically authorized.') }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '{{ __('Yes, move to now') }}',
                    cancelButtonText: '{{ __("Cancel") }}',
                    customClass: {
                        popup: 'rounded-[2rem] dark:bg-gray-900 border border-gray-150 dark:border-gray-800 p-8',
                        confirmButton: 'px-5 py-3 text-xs font-black uppercase tracking-widest bg-amber-500 hover:bg-amber-600 text-white rounded-xl transition-all shadow-md',
                        cancelButton: 'px-5 py-3 text-xs font-black uppercase tracking-widest bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-all ml-3'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        ejecutarAddCapacity();
                    }
                });
            } else {
                if (confirm('{{ __('swal.confirm_move_and_attach') }}')) {
                    ejecutarAddCapacity();
                }
            }
        }
        
        if (addCapacityBtn) addCapacityBtn.addEventListener('click', handleAddCapacityClick);
        if (addCapacityBtnHeader) addCapacityBtnHeader.addEventListener('click', handleAddCapacityClick);

        function ejecutarAddCapacity() {
            if (addCapacityBtn) {
                addCapacityBtn.disabled = true;
                addCapacityBtn.innerHTML = '{{ __('Processing...') }}';
            }
            if (addCapacityBtnHeader) {
                addCapacityBtnHeader.disabled = true;
                addCapacityBtnHeader.innerHTML = '...';
            }
            
            let formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            
            fetch("{{ route('appointments.attend-now', [$team, $appointment]) }}", {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: '{{ __('Appointment moved!') }}',
                            text: '{{ __('The appointment has been moved to today and capacity authorized.') }}',
                            customClass: {
                                popup: 'rounded-[2rem] dark:bg-gray-900 border border-gray-150 dark:border-gray-800 p-8',
                                confirmButton: 'px-5 py-3 text-xs font-black uppercase tracking-widest bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl transition-all shadow-md'
                            },
                            buttonsStyling: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        alert('{{ __('The appointment has been moved to today and capacity authorized.') }}');
                        window.location.reload();
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Error', data.message || 'No se pudo procesar la solicitud', 'error');
                    } else {
                        alert('Error: ' + (data.message || 'No se pudo procesar la solicitud'));
                    }
                    restaurarBotones();
                }
            })
            .catch(() => restaurarBotones());
        }

        function restaurarBotones() {
            if (addCapacityBtn) {
                addCapacityBtn.disabled = false;
                addCapacityBtn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg> {{ __('Attend Now (Move to Today)') }}';
            }
            if (addCapacityBtnHeader) {
                addCapacityBtnHeader.disabled = false;
                addCapacityBtnHeader.innerHTML = '<svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg><span>{{ __('Attend Now') }}</span>';
            }
        }
    });
</script>
</x-app-layout>
