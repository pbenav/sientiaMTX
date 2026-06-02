@php $isEdit = isset($service); $action = $isEdit ? route('appointments.services.update', $service) : route('appointments.services.store'); @endphp

<x-app-layout maxWidth="[1600px]">
@section('title', $isEdit ? 'Editar Servicio — '.$service->name : 'Nuevo Servicio de Cita')

<x-slot name="header">
    <div class="flex items-start gap-4 min-w-0 flex-1">
        <a href="{{ route('appointments.services.index') }}"
           class="mt-1 p-2.5 bg-gray-50 dark:bg-gray-800/50 text-gray-400 hover:text-cyan-600 dark:hover:text-cyan-400 rounded-2xl transition-all shadow-sm border border-gray-100 dark:border-gray-700/50 shrink-0">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <div class="min-w-0 flex-1">
            <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight flex items-center gap-3">
                <svg class="h-7 w-7 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    @if($isEdit)
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    @else
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    @endif
                </svg>
                {{ $isEdit ? 'Editar: '.$service->name : 'Nuevo Servicio' }}
            </h1>
        </div>
    </div>
    @include('appointments.partials.nav')
</x-slot>

<div class="py-8">
    <div class="max-w-full mx-auto sm:px-6 lg:px-8">
        <form id="service-form" method="POST" action="{{ $action }}" class="space-y-6">
            @csrf
            @if($isEdit) @method('PATCH') @endif

            <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                    <p class="text-xs font-black uppercase tracking-widest text-gray-400">Información del Servicio</p>
                </div>
                <div class="p-6 space-y-5">

                        {{-- Nombre --}}
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2" for="name">Nombre del Servicio *</label>
                            <input type="text" id="name" name="name" value="{{ old('name', $service->name ?? '') }}" required
                                   class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 focus:ring focus:ring-cyan-500/20 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all"
                                   placeholder="ej. Consulta General, Asesoramiento Técnico...">
                            @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Modalidades --}}
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">Modalidades soportadas *</label>
                            @php
                                $selectedModalities = old('modality', $service->modality ?? ['presencial']);
                                if (!is_array($selectedModalities)) $selectedModalities = [$selectedModalities];
                            @endphp
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                @foreach(\App\Models\AppointmentService::MODALITIES as $key => $label)
                                    <label class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer hover:border-cyan-500 transition-colors group">
                                        <input type="checkbox" name="modality[]" value="{{ $key }}"
                                               {{ in_array($key, $selectedModalities) ? 'checked' : '' }}
                                               class="w-5 h-5 rounded border-gray-300 text-cyan-600 focus:ring-cyan-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 transition-colors">
                                        <span class="text-sm font-bold text-gray-700 dark:text-gray-300 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('modality') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                    {{-- Descripción Markdown --}}
                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2" for="description">
                            Descripción <span class="text-[9px] font-bold text-cyan-500 normal-case tracking-normal ml-1">Markdown</span>
                        </label>
                        <textarea id="description" name="description" rows="5"
                                  class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 focus:ring focus:ring-cyan-500/20 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all font-mono resize-y"
                                  placeholder="Describe el servicio. Puedes usar **negrita**, *cursiva*, listas, etc.">{{ old('description', $service->description ?? '') }}</textarea>
                        @error('description') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Duración y tramo --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2" for="duration_minutes">Duración de la cita (min) *</label>
                            <input type="number" id="duration_minutes" name="duration_minutes" min="5" max="480" step="5"
                                   value="{{ old('duration_minutes', $service->duration_minutes ?? 30) }}" required
                                   class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 focus:ring focus:ring-cyan-500/20 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all">
                            @error('duration_minutes') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2" for="slot_duration_minutes">
                                Tramo mínimo (min) <span class="text-[9px] normal-case tracking-normal text-gray-400">opcional</span>
                            </label>
                            <input type="number" id="slot_duration_minutes" name="slot_duration_minutes" min="5" max="120" step="5"
                                   value="{{ old('slot_duration_minutes', $service->slot_duration_minutes ?? '') }}"
                                   placeholder="Hereda config."
                                   class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 focus:ring focus:ring-cyan-500/20 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2" for="max_per_slot">
                                Máx. por tramo <span class="text-[9px] normal-case tracking-normal text-gray-400">opcional</span>
                            </label>
                            <input type="number" id="max_per_slot" name="max_per_slot" min="1" max="100"
                                   value="{{ old('max_per_slot', $service->max_per_slot ?? '') }}"
                                   placeholder="Hereda config."
                                   class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 focus:ring focus:ring-cyan-500/20 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all">
                        </div>
                    </div>

                    {{-- Precio --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2" for="price">Precio (€)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-black text-sm">€</span>
                                <input type="number" id="price" name="price" min="0" step="0.01"
                                       value="{{ old('price', $service->price ?? '') }}"
                                       placeholder="0 = gratuito"
                                       class="w-full pl-8 pr-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 focus:ring focus:ring-cyan-500/20 rounded-xl py-3 text-sm text-gray-900 dark:text-white outline-none transition-all">
                            </div>
                        </div>
                        <div class="flex flex-col gap-4 justify-end">
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <div class="relative">
                                    <input type="hidden" name="price_visible" value="0">
                                    <input type="checkbox" name="price_visible" value="1" {{ old('price_visible', $service->price_visible ?? false) ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="w-10 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-cyan-500"></div>
                                </div>
                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Mostrar precio en portal público</span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <div class="relative">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $service->is_active ?? true) ? 'checked' : '' }}
                                           class="sr-only peer">
                                    <div class="w-10 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-cyan-500"></div>
                                </div>
                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Servicio activo y visible</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Horario y Disponibilidad Semanal --}}
            @php
                $days = [
                    1 => 'Lunes',
                    2 => 'Martes',
                    3 => 'Miércoles',
                    4 => 'Jueves',
                    5 => 'Viernes',
                    6 => 'Sábado',
                    0 => 'Domingo',
                ];
                // Si es edición, obtenemos todos los schedules agrupados por day_of_week
                $schedulesMap = $isEdit ? $service->schedules->groupBy('day_of_week') : collect();

                $initialSchedulesJS = [];
                foreach ($days as $num => $name) {
                    $daySchedules = $schedulesMap->get($num) ?: collect();
                    $isActive = $isEdit ? $daySchedules->isNotEmpty() : in_array($num, [1, 2, 3, 4, 5]); // Por defecto de Lunes a Viernes
                    
                    $tramos = [];
                    if ($daySchedules->isNotEmpty()) {
                        foreach ($daySchedules as $ds) {
                            $tramos[] = [
                                'start_time' => substr($ds->start_time, 0, 5),
                                'end_time' => substr($ds->end_time, 0, 5),
                            ];
                        }
                    } else {
                        $tramos[] = [
                            'start_time' => '09:00',
                            'end_time' => '14:00',
                        ];
                    }
                    
                    $initialSchedulesJS[$num] = [
                        'active' => $isActive,
                        'tramos' => $tramos,
                    ];
                }
            @endphp

            <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden"
                 x-data="{
                    schedules: {{ json_encode($initialSchedulesJS) }},
                    addTramo(day) {
                        this.schedules[day].tramos.push({ start_time: '09:00', end_time: '14:00' });
                        this.schedules[day].active = true;
                    },
                    removeTramo(day, index) {
                        if (this.schedules[day].tramos.length > 1) {
                            this.schedules[day].tramos.splice(index, 1);
                        } else {
                            this.schedules[day].active = false;
                        }
                    },
                    copiarLunes() {
                        const lunes = this.schedules[1];
                        const copia = JSON.parse(JSON.stringify(lunes.tramos));
                        const estado = lunes.active;
                        
                        // Copiar el estado y tramos del lunes a todos los días de martes a domingo
                        [2, 3, 4, 5, 6, 0].forEach(day => {
                            this.schedules[day].tramos = JSON.parse(JSON.stringify(copia));
                            this.schedules[day].active = estado;
                        });
                    }
                 }">
                <div class="p-5 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <p class="text-xs font-black uppercase tracking-widest text-gray-400">📅 Horario y Disponibilidad Semanal</p>
                    <button type="button" @click="copiarLunes()"
                            class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-wider text-cyan-600 dark:text-cyan-400 hover:text-cyan-500 bg-cyan-50 dark:bg-cyan-500/10 hover:bg-cyan-100/50 px-3.5 py-2 rounded-xl transition-all active:scale-95 border border-cyan-150 dark:border-cyan-900/30">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                        Copiar Lunes al resto
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($days as $num => $name)
                            <div class="flex flex-col gap-4 py-6 first:pt-0 last:pb-0">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-3">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="hidden" name="schedules[{{ $num }}][is_active]" value="0">
                                            <input type="checkbox" name="schedules[{{ $num }}][is_active]" value="1" x-model="schedules[{{ $num }}].active" class="sr-only peer">
                                            <div class="w-10 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-cyan-500"></div>
                                        </label>
                                        <span class="text-sm font-black text-gray-900 dark:text-white" :class="schedules[{{ $num }}].active ? '' : 'opacity-50'">{{ $name }}</span>
                                    </div>

                                    <button type="button" @click="addTramo({{ $num }})" x-show="schedules[{{ $num }}].active"
                                            class="flex items-center gap-1 text-[10px] font-black uppercase tracking-wider text-cyan-600 dark:text-cyan-400 hover:text-cyan-500 bg-cyan-50 dark:bg-cyan-500/10 px-2.5 py-1.5 rounded-lg transition-all active:scale-95">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                        Añadir Tramo
                                    </button>

                                    <div class="text-xs text-gray-400 italic" x-show="!schedules[{{ $num }}].active" x-transition>
                                        No disponible
                                    </div>
                                </div>

                                <!-- Listado de tramos horarias para este día -->
                                <div class="pl-0 sm:pl-13 space-y-3" x-show="schedules[{{ $num }}].active" x-transition x-cloak>
                                    <div class="flex flex-wrap items-center gap-3">
                                        <template x-for="(tramo, index) in schedules[{{ $num }}].tramos" :key="index">
                                            <div class="flex items-center gap-3 bg-gray-50/50 dark:bg-gray-800/35 p-3 rounded-2xl border border-gray-150 dark:border-gray-800/80 shadow-sm shrink-0">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[10px] text-gray-400 font-black uppercase tracking-wider">De:</span>
                                                    <input type="time" :name="`schedules[{{ $num }}][tramos][${index}][start_time]`" x-model="tramo.start_time"
                                                           class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 rounded-xl px-2.5 py-1.5 text-xs text-gray-900 dark:text-white outline-none transition-all font-bold">
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[10px] text-gray-400 font-black uppercase tracking-wider">A:</span>
                                                    <input type="time" :name="`schedules[{{ $num }}][tramos][${index}][end_time]`" x-model="tramo.end_time"
                                                           class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 rounded-xl px-2.5 py-1.5 text-xs text-gray-900 dark:text-white outline-none transition-all font-bold">
                                                </div>
                                                <button type="button" @click="removeTramo({{ $num }}, index)"
                                                        class="p-1.5 text-red-500 hover:text-red-600 bg-red-50 dark:bg-red-500/10 hover:bg-red-100 dark:hover:bg-red-500/25 rounded-lg transition-all active:scale-90"
                                                        title="Eliminar tramo">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Botones --}}
            <div class="flex items-center gap-3 justify-end">
                <a href="{{ route('appointments.services.index') }}"
                   class="px-5 py-2.5 text-xs font-black uppercase tracking-widest text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-xl transition-all">
                    Cancelar
                </a>
                <button type="submit"
                        class="px-7 py-2.5 text-xs font-black uppercase tracking-widest bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl shadow-lg shadow-cyan-500/20 transition-all active:scale-95">
                    {{ $isEdit ? 'Guardar Cambios' : 'Crear Servicio' }}
                </button>
            </div>
        </form>
    </div>
</div>
</x-app-layout>

{{-- ============================================================
     BARRA FLOTANTE DE ACCIONES RÁPIDAS (EDICIÓN)
     ============================================================ --}}
<div id="service-edit-floating-bar"
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
    <a href="{{ route('appointments.services.index') }}"
       style="display:flex;align-items:center;gap:0.375rem;font-size:0.75rem;font-weight:700;color:#6b7280;padding:0.375rem 0.75rem;border-radius:0.625rem;text-decoration:none;transition:all 0.15s ease;"
       onmouseover="this.style.color='#0891b2';this.style.background='#ecfeff'"
       onmouseout="this.style.color='#6b7280';this.style.background='transparent'">
        <svg style="width:1rem;height:1rem;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        <span>Volver</span>
    </a>

    <div style="width:1px;height:1.25rem;background:#e5e7eb;flex-shrink:0"></div>

    {{-- Título truncado --}}
    <span style="font-size:0.75rem;font-weight:900;color:#1f2937;max-width:200px;overflow:hidden;text-overflow:ellipsis;" class="dark:text-gray-300">
        {{ $isEdit ? 'Editar: ' . $service->name : 'Nuevo Servicio' }}
    </span>

    <div style="width:1px;height:1.25rem;background:#e5e7eb;flex-shrink:0"></div>

    {{-- Guardar --}}
    <button type="button"
            onclick="document.getElementById('service-form').submit()"
       style="display:flex;align-items:center;gap:0.375rem;font-size:0.75rem;font-weight:700;color:#fff;background:#0891b2;padding:0.375rem 0.75rem;border-radius:0.625rem;text-decoration:none;transition:background 0.15s ease;border:none;cursor:pointer;"
       onmouseover="this.style.background='#0e7490'"
       onmouseout="this.style.background='#0891b2'">
        <svg style="width:1rem;height:1rem;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
        </svg>
        <span>{{ $isEdit ? 'Guardar Cambios' : 'Crear Servicio' }}</span>
    </button>
</div>

<script>
    (function() {
        const bar = document.getElementById('service-edit-floating-bar');
        
        // Función para mostrar/ocultar según scroll
        function handleScroll() {
            if (window.scrollY > 150) {
                bar.style.opacity = '1';
                bar.style.pointerEvents = 'auto';
                bar.style.transform = 'translate(-50%, 0)';
            } else {
                bar.style.opacity = '0';
                bar.style.pointerEvents = 'none';
                bar.style.transform = 'translate(-50%, 1rem)';
            }
        }

        window.addEventListener('scroll', handleScroll, { passive: true });
        handleScroll();
    })();

    document.addEventListener('alpine:init', () => {
        Alpine.data('floatingDraggable', () => ({
            isDragging: false,
            startX: 0,
            startY: 0,
            initialLeft: 0,
            initialBottom: 0,
            
            startDrag(e) {
                // Ignore clicks on buttons/links inside the bar
                if (e.target.closest('button') || e.target.closest('a')) return;
                
                this.isDragging = true;
                const touch = e.type.includes('touch') ? e.touches[0] : e;
                this.startX = touch.clientX;
                this.startY = touch.clientY;
                
                const rect = this.$el.getBoundingClientRect();
                this.initialLeft = rect.left;
                this.initialBottom = window.innerHeight - rect.bottom;
                
                // Remove center transform during drag for absolute positioning
                this.$el.style.transform = 'none';
                this.$el.style.left = this.initialLeft + 'px';
                this.$el.style.bottom = this.initialBottom + 'px';
            },
            
            drag(e) {
                if (!this.isDragging) return;
                
                const touch = e.type.includes('touch') ? e.touches[0] : e;
                const deltaX = touch.clientX - this.startX;
                const deltaY = touch.clientY - this.startY;
                
                // Update position (inverted Y since we use bottom)
                const newLeft = this.initialLeft + deltaX;
                const newBottom = this.initialBottom - deltaY;
                
                // Simple bounds checking
                const maxX = window.innerWidth - this.$el.offsetWidth;
                const maxBottom = window.innerHeight - this.$el.offsetHeight;
                
                this.$el.style.left = Math.max(0, Math.min(newLeft, maxX)) + 'px';
                this.$el.style.bottom = Math.max(0, Math.min(newBottom, maxBottom)) + 'px';
            },
            
            stopDrag() {
                this.isDragging = false;
            }
        }));
    });
</script>
