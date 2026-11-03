{{--
    SPDX-License-Identifier: AGPL-3.0-or-later
    Copyright (c) 2022-2026 pbenav <info@sientia.com>

    Banner de Circuito Activo para vistas principales
--}}

@if(isset($circuitable) && $circuitable->activeCircuitExecution)
    @php
        $activeCircuit = $circuitable->activeCircuitExecution;
        $currentStep = $activeCircuit->currentStep;
        $canAct = $activeCircuit->canUserAct(auth()->user());
    @endphp

    <div class="mb-6 p-4 rounded-3xl bg-gradient-to-r from-violet-900/10 via-indigo-900/10 to-transparent border border-violet-200 dark:border-violet-800/60 shadow-sm relative overflow-hidden">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-start sm:items-center gap-3.5">
                <div class="w-10 h-10 rounded-2xl bg-violet-600 text-white flex items-center justify-center shrink-0 shadow-md shadow-violet-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-violet-100 dark:bg-violet-900/40 text-violet-700 dark:text-violet-300">
                            <span class="w-1.5 h-1.5 rounded-full bg-violet-500 animate-pulse"></span>
                            Circuito en Tramitación
                        </span>
                        @if($currentStep)
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider
                                @if($currentStep->action_type === 'endorsement') bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300
                                @elseif($currentStep->action_type === 'justification') bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300
                                @elseif($currentStep->action_type === 'approval') bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300
                                @else bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300 @endif">
                                {{ __('circuits.action_types.' . $currentStep->action_type) ?? ucfirst($currentStep->action_type) }}
                            </span>
                        @endif
                        @if($canAct)
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300 animate-bounce">
                                ¡Requiere tu firma!
                            </span>
                        @endif
                    </div>
                    <h4 class="text-sm font-black text-gray-900 dark:text-white">
                        {{ $activeCircuit->title }}
                    </h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        @if($currentStep)
                            Paso {{ $currentStep->order }}: <strong>{{ $currentStep->name }}</strong>
                            @if($currentStep->assignedUser)
                                &bull; Asignado a: {{ $currentStep->assignedUser->name }}
                            @elseif($currentStep->assignedRole)
                                &bull; Rol: {{ $currentStep->assignedRole->name }}
                            @endif
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3 w-full sm:w-auto justify-end shrink-0">
                <div class="hidden md:flex flex-col items-end">
                    <span class="text-xs font-black text-violet-600 dark:text-violet-400">{{ $activeCircuit->progress_percentage }}% completado</span>
                    <div class="w-28 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden mt-1">
                        <div class="h-full bg-violet-600 rounded-full" style="width: {{ $activeCircuit->progress_percentage }}%"></div>
                    </div>
                </div>

                <a href="{{ route('teams.circuits.show', [$team, $activeCircuit]) }}"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider text-white bg-violet-600 hover:bg-violet-700 shadow-sm transition active:scale-95">
                    @if($canAct)
                        <span>✍️ Resolver Trámite</span>
                    @else
                        <span>Ver Tramitación</span>
                    @endif
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>
    </div>
@endif

