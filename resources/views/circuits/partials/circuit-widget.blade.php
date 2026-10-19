{{--
    SPDX-License-Identifier: AGPL-3.0-or-later
    Copyright (c) 2022-2026 pbenav <info@sientia.com>

    Widget de Circuito de Tramitación y Aprobación
    Utilizado en fichas de Expedientes y Actividades
--}}

@php
    $activeCircuit = $circuitable->activeCircuitExecution;
    $availableTemplates = $team->circuitTemplates()->active()->forTarget($type ?? 'both')->get();
    $pastCircuits = $circuitable->circuitExecutions()->where('id', '!=', $activeCircuit?->id ?? 0)->latest()->take(3)->get();
@endphp

<div x-data="{
    openStartModal: false,
    selectedTemplateId: '{{ $availableTemplates->first()?->id ?? '' }}',
    title: '{{ addslashes($circuitable->title) }}',
    notes: ''
}" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm">

    {{-- Widget Header --}}
    <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <div>
                <h3 class="text-xs font-black uppercase tracking-wider text-gray-900 dark:text-white">
                    {{ __('circuits.circuit') }}
                </h3>
                <span class="text-[10px] text-gray-400 font-medium">Visto Bueno • Justificación • Aprobación</span>
            </div>
        </div>

        @if($activeCircuit)
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800/40 animate-pulse">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                {{ __('circuits.statuses.in_progress') }}
            </span>
        @else
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400">
                {{ __('circuits.idle') }}
            </span>
        @endif
    </div>

    {{-- Widget Body --}}
    <div class="mt-4">
        @if($activeCircuit)
            {{-- Active Circuit Details --}}
            <div class="space-y-4">
                <div>
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white line-clamp-1">
                        {{ $activeCircuit->title }}
                    </h4>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                        {{ $activeCircuit->template ? $activeCircuit->template->name : 'Flujo Estándar de 3 Pasos' }}
                    </p>
                </div>

                {{-- Progress Bar --}}
                <div>
                    <div class="flex items-center justify-between text-xs font-bold mb-1.5">
                        <span class="text-gray-500 dark:text-gray-400">
                            Paso {{ ($activeCircuit->steps->where('status', 'approved')->count()) + 1 }} de {{ $activeCircuit->steps->count() }}
                        </span>
                        <span class="text-violet-600 dark:text-violet-400 font-black">
                            {{ $activeCircuit->progress_percentage }}%
                        </span>
                    </div>
                    <div class="w-full h-2 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-violet-600 to-indigo-600 transition-all duration-500" style="width: {{ $activeCircuit->progress_percentage }}%"></div>
                    </div>
                </div>

                {{-- Current Step Info Card --}}
                @if($activeCircuit->currentStep)
                    @php $step = $activeCircuit->currentStep; @endphp
                    <div class="p-3.5 bg-gray-50 dark:bg-gray-800/60 rounded-2xl border border-gray-100 dark:border-gray-800 space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">
                                Paso Actual
                            </span>
                            <span class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider
                                @if($step->action_type === 'endorsement') bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300
                                @elseif($step->action_type === 'justification') bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300
                                @elseif($step->action_type === 'approval') bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300
                                @elseif($step->action_type === 'publication') bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300
                                @else bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 @endif">
                                {{ __('circuits.action_types.' . $step->action_type) ?? ucfirst($step->action_type) }}
                            </span>
                        </div>
                        <p class="text-xs font-bold text-gray-800 dark:text-gray-200">
                            {{ $step->name }}
                        </p>
                        @if($step->assignedUser)
                            <div class="flex items-center gap-1.5 text-[11px] text-gray-500 dark:text-gray-400">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <span>Asignado a: <strong class="text-gray-700 dark:text-gray-300">{{ $step->assignedUser->name }}</strong></span>
                            </div>
                        @elseif($step->assignedRole)
                            <div class="flex items-center gap-1.5 text-[11px] text-gray-500 dark:text-gray-400">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                <span>Rol: <strong class="text-gray-700 dark:text-gray-300">{{ $step->assignedRole->name }}</strong></span>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Alert if user must act --}}
                @if($activeCircuit->canUserAct(auth()->user()))
                    <div class="p-3 bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-800/40 rounded-2xl flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="text-violet-600 dark:text-violet-400 text-sm">✍️</span>
                            <span class="text-xs font-bold text-violet-900 dark:text-violet-200">
                                ¡Se requiere tu visto bueno o firma!
                            </span>
                        </div>
                        <a href="{{ route('teams.circuits.show', [$team, $activeCircuit]) }}"
                            class="text-xs font-black uppercase tracking-wider bg-violet-600 text-white px-3 py-1.5 rounded-xl hover:bg-violet-700 transition shadow-sm shrink-0">
                            Resolver
                        </a>
                    </div>
                @endif

                {{-- Action Link --}}
                <a href="{{ route('teams.circuits.show', [$team, $activeCircuit]) }}"
                    class="w-full flex items-center justify-center gap-2 text-xs font-bold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-750 px-4 py-2.5 rounded-xl transition shadow-sm">
                    <svg class="w-4 h-4 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <span>Ver Expediente de Tramitación</span>
                </a>
            </div>
        @else
            {{-- Idle: Option to start circuit --}}
            <div class="text-center py-2 space-y-3">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Somete este elemento al circuito de validación, justificación y visto bueno colegiado antes de su resolución o publicación.
                </p>
                <button @click="openStartModal = true" type="button"
                    class="w-full inline-flex items-center justify-center gap-2 text-xs font-black uppercase tracking-wider bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white px-4 py-2.5 rounded-xl transition-all shadow-md shadow-violet-500/20 active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span>Iniciar Tramitación</span>
                </button>
            </div>
        @endif

        {{-- Past tramitations expandable history --}}
        @if($pastCircuits->isNotEmpty())
            <div x-data="{ openHistory: false }" class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                <button @click="openHistory = !openHistory" type="button" class="w-full flex items-center justify-between text-xs font-bold text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <span>Historial de tramitaciones ({{ $pastCircuits->count() }})</span>
                    <svg class="w-3.5 h-3.5 transition-transform" :class="openHistory ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="openHistory" x-collapse x-cloak class="mt-3 space-y-2">
                    @foreach($pastCircuits as $past)
                        <a href="{{ route('teams.circuits.show', [$team, $past]) }}" class="block p-2 rounded-xl bg-gray-50 dark:bg-gray-800/40 hover:bg-violet-50/50 dark:hover:bg-violet-900/20 transition text-left">
                            <div class="flex items-center justify-between text-[11px] font-bold">
                                <span class="text-gray-700 dark:text-gray-300 truncate max-w-[160px]">{{ $past->title }}</span>
                                <span class="capitalize text-[10px]
                                    @if($past->status === 'completed') text-emerald-600 dark:text-emerald-400
                                    @elseif($past->status === 'rejected') text-rose-600 dark:text-rose-400
                                    @else text-gray-500 @endif">
                                    {{ __('circuits.statuses.' . $past->status) ?? $past->status }}
                                </span>
                            </div>
                            <span class="text-[9px] text-gray-400">{{ $past->created_at->format('d/m/Y H:i') }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- MODAL INICIAR TRAMITACIÓN --}}
    <div x-show="openStartModal"
        x-cloak
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">

        <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] w-full max-w-lg overflow-hidden shadow-2xl border border-gray-100 dark:border-gray-800 transform transition-all text-left"
            @click.away="openStartModal = false"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95">

            <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-violet-50/50 dark:bg-violet-950/20">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-violet-600 text-white flex items-center justify-center font-black">
                        ⚡
                    </div>
                    <div>
                        <h3 class="text-base font-black text-gray-900 dark:text-white uppercase tracking-tight">
                            Iniciar Tramitación
                        </h3>
                        <p class="text-xs text-gray-500 font-medium">
                            {{ $circuitable->title }}
                        </p>
                    </div>
                </div>
                <button @click="openStartModal = false" type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form action="{{ route('teams.circuits.start', $team) }}" method="POST" class="p-6 space-y-4">
                @csrf
                <input type="hidden" name="circuitable_type" value="{{ $type }}">
                <input type="hidden" name="circuitable_id" value="{{ $circuitable->id }}">

                {{-- Template Selection --}}
                <div>
                    <label class="block text-xs font-black uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1.5">
                        Plantilla de Circuito
                    </label>
                    @if($availableTemplates->isNotEmpty())
                        <select name="circuit_template_id" x-model="selectedTemplateId" class="w-full text-sm bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-violet-500 focus:border-violet-500">
                            @foreach($availableTemplates as $tmpl)
                                <option value="{{ $tmpl->id }}">{{ $tmpl->name }} ({{ $tmpl->steps->count() }} pasos)</option>
                            @endforeach
                            <option value="">Circuito Estándar (Visto Bueno → Justificación → Aprobación)</option>
                        </select>
                    @else
                        <div class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/30 rounded-xl text-xs text-amber-800 dark:text-amber-300">
                            No hay plantillas personalizadas creadas. Se aplicará el <strong>Circuito Estándar de 3 Pasos</strong> (Visto Bueno → Justificación → Aprobación).
                        </div>
                    @endif
                </div>

                {{-- Tramitation Title --}}
                <div>
                    <label class="block text-xs font-black uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1.5">
                        Título de la Tramitación
                    </label>
                    <input type="text" name="title" x-model="title" required
                        class="w-full text-sm bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-violet-500 focus:border-violet-500"
                        placeholder="Ej. Aprobación y Justificación: {{ $circuitable->title }}">
                </div>

                {{-- Initial Notes --}}
                <div>
                    <label class="block text-xs font-black uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1.5">
                        Observaciones / Justificación Inicial (Opcional)
                    </label>
                    <textarea name="notes" x-model="notes" rows="3"
                        class="w-full text-sm bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-violet-500 focus:border-violet-500"
                        placeholder="Explica brevemente el motivo o indicaciones para los revisores del circuito..."></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                    <button @click="openStartModal = false" type="button" class="text-xs font-bold text-gray-500 dark:text-gray-400 hover:text-gray-700 px-4 py-2">
                        Cancelar
                    </button>
                    <button type="submit" class="text-xs font-black uppercase tracking-wider bg-violet-600 hover:bg-violet-700 text-white px-5 py-2.5 rounded-xl shadow-md transition-all active:scale-95">
                        Lanzar Tramitación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

