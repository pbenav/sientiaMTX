<x-app-layout maxWidth="[1600px]">
@section('title', 'Editar Persona — ' . $visitor->full_name)

<x-slot name="header">
    <div class="flex items-start gap-4 min-w-0 flex-1">
        <a href="{{ route('appointments.visitors.index', $team) }}"
           class="mt-1 p-2.5 bg-gray-50 dark:bg-gray-800/50 text-gray-400 hover:text-cyan-600 dark:hover:text-cyan-400 rounded-2xl transition-all shadow-sm border border-gray-100 dark:border-gray-700/50 shrink-0">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <div class="min-w-0 flex-1">
            <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight flex items-center gap-3">
                <svg class="h-7 w-7 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Editar: {{ $visitor->full_name }}
            </h1>
        </div>
    </div>
    @include('appointments.partials.nav')
</x-slot>

<div class="py-8">
    <div class="max-w-full mx-auto sm:px-6 lg:px-8">
        <form id="visitor-form" method="POST" action="{{ route('appointments.visitors.update', [$team, $visitor]) }}" class="space-y-6">
            @csrf
            @method('PATCH')

            <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                    <p class="text-xs font-black uppercase tracking-widest text-gray-400">Datos Personales</p>
                </div>
                <div class="p-6 space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2" for="first_name">Nombre *</label>
                            <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $visitor->first_name) }}" required
                                   class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 focus:ring focus:ring-cyan-500/20 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all">
                            @error('first_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2" for="last_name">Apellidos *</label>
                            <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $visitor->last_name) }}" required
                                   class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 focus:ring focus:ring-cyan-500/20 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all">
                            @error('last_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2" for="dni">DNI/NIE</label>
                            <input type="text" id="dni" name="dni" value="{{ old('dni', $visitor->dni) }}"
                                   class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 focus:ring focus:ring-cyan-500/20 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all">
                            @error('dni') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2" for="email">Correo Electrónico *</label>
                            <input type="email" id="email" name="email" value="{{ old('email', $visitor->email) }}" required
                                   class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 focus:ring focus:ring-cyan-500/20 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all">
                            @error('email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2" for="phone">Teléfono</label>
                            <input type="text" id="phone" name="phone" value="{{ old('phone', $visitor->phone) }}"
                                   class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 focus:ring focus:ring-cyan-500/20 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all">
                            @error('phone') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2" for="city">Localidad / Municipio</label>
                            <input type="text" id="city" name="city" value="{{ old('city', $visitor->city) }}"
                                   class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 focus:ring focus:ring-cyan-500/20 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all">
                            @error('city') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2" for="postal_code">Código Postal</label>
                            <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $visitor->postal_code) }}"
                                   class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 focus:ring focus:ring-cyan-500/20 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all">
                            @error('postal_code') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2" for="observations">Observaciones</label>
                        <textarea id="observations" name="observations" rows="3"
                                  class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-cyan-500 focus:ring focus:ring-cyan-500/20 rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white outline-none transition-all resize-y"
                                  placeholder="Detalles adicionales o notas internas sobre la persona...">{{ old('observations', $visitor->observations) }}</textarea>
                        @error('observations') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 justify-end">
                <a href="{{ route('appointments.visitors.index', $team) }}"
                   class="px-5 py-2.5 text-xs font-black uppercase tracking-widest text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-xl transition-all">
                    Cancelar
                </a>
                <button type="submit"
                        class="px-7 py-2.5 text-xs font-black uppercase tracking-widest bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl shadow-lg shadow-cyan-500/20 transition-all active:scale-95">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>
</x-app-layout>

{{-- BARRA FLOTANTE DE ACCIONES RÁPIDAS (EDICIÓN) --}}
<div id="visitor-edit-floating-bar"
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
    <a href="{{ route('appointments.visitors.index', $team) }}"
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
        Editar: {{ $visitor->full_name }}
    </span>

    <div style="width:1px;height:1.25rem;background:#e5e7eb;flex-shrink:0"></div>

    {{-- Guardar --}}
    <button type="button"
            onclick="document.getElementById('visitor-form').submit()"
       style="display:flex;align-items:center;gap:0.375rem;font-size:0.75rem;font-weight:700;color:#fff;background:#0891b2;padding:0.375rem 0.75rem;border-radius:0.625rem;text-decoration:none;transition:background 0.15s ease;border:none;cursor:pointer;"
       onmouseover="this.style.background='#0e7490'"
       onmouseout="this.style.background='#0891b2'">
        <svg style="width:1rem;height:1rem;flex-shrink:0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
        </svg>
        <span>Guardar Cambios</span>
    </button>
</div>

<script>
    (function() {
        const bar = document.getElementById('visitor-edit-floating-bar');
        
        // Función para mostrar/ocultar según scroll
        function handleScroll() {
            if (window.scrollY > 100) {
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
        if (!Alpine.data('floatingDraggable')) {
            Alpine.data('floatingDraggable', () => ({
                isDragging: false,
                startX: 0,
                startY: 0,
                initialLeft: 0,
                initialBottom: 0,
                
                startDrag(e) {
                    if (e.target.closest('button') || e.target.closest('a')) return;
                    
                    this.isDragging = true;
                    const touch = e.type.includes('touch') ? e.touches[0] : e;
                    this.startX = touch.clientX;
                    this.startY = touch.clientY;
                    
                    const rect = this.$el.getBoundingClientRect();
                    this.initialLeft = rect.left;
                    this.initialBottom = window.innerHeight - rect.bottom;
                    
                    this.$el.style.transform = 'none';
                    this.$el.style.left = this.initialLeft + 'px';
                    this.$el.style.bottom = this.initialBottom + 'px';
                },
                
                drag(e) {
                    if (!this.isDragging) return;
                    
                    const touch = e.type.includes('touch') ? e.touches[0] : e;
                    const deltaX = touch.clientX - this.startX;
                    const deltaY = touch.clientY - this.startY;
                    
                    const newLeft = this.initialLeft + deltaX;
                    const newBottom = this.initialBottom - deltaY;
                    
                    const maxX = window.innerWidth - this.$el.offsetWidth;
                    const maxBottom = window.innerHeight - this.$el.offsetHeight;
                    
                    this.$el.style.left = Math.max(0, Math.min(newLeft, maxX)) + 'px';
                    this.$el.style.bottom = Math.max(0, Math.min(newBottom, maxBottom)) + 'px';
                },
                
                stopDrag() {
                    this.isDragging = false;
                }
            }));
        }
    });
</script>
