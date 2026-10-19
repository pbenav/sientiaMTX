<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
    <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100 dark:border-gray-700">
        <div>
            <h2 class="text-lg font-black text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                Circuito de Tramitación y Aprobación
            </h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                Progreso secuencial del documento por las distintas etapas de validación
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-gray-500 dark:text-gray-400">Progreso:</span>
            <div class="w-28 h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden shadow-inner">
                <div class="h-full bg-gradient-to-r from-violet-500 to-emerald-500 transition-all duration-500" style="width: {{ $circuit->progress_percentage }}%"></div>
            </div>
            <span class="text-xs font-black text-violet-600 dark:text-violet-400">{{ $circuit->progress_percentage }}%</span>
        </div>
    </div>

    <!-- Stepper Timeline -->
    <div class="relative">
        <div class="space-y-6">
            @foreach($circuit->steps as $index => $step)
                @php
                    $isCurrent = $circuit->is_active && $circuit->current_step_id === $step->id;
                    $isApproved = $step->status === \App\Models\CircuitExecutionStep::STATUS_APPROVED;
                    $isReturned = $step->status === \App\Models\CircuitExecutionStep::STATUS_RETURNED;
                    $isRejected = $step->status === \App\Models\CircuitExecutionStep::STATUS_REJECTED;
                    $isPending = $step->status === \App\Models\CircuitExecutionStep::STATUS_PENDING;

                    $stateColor = match(true) {
                        $isApproved => 'emerald',
                        $isReturned => 'amber',
                        $isRejected => 'rose',
                        $isCurrent  => 'violet',
                        default     => 'gray',
                    };
                @endphp

                <div class="flex items-start gap-4 relative group">
                    <!-- Vertical Line between steps -->
                    @if(!$loop->last)
                        <div class="absolute left-5 top-10 bottom-0 w-0.5 -ml-px {{ $isApproved ? 'bg-emerald-300 dark:bg-emerald-800' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                    @endif

                    <!-- Step Icon / Indicator -->
                    <div class="relative z-10 flex items-center justify-center w-10 h-10 rounded-2xl shrink-0 font-black text-sm shadow-sm transition-all
                        @if($isApproved)
                            bg-emerald-500 text-white shadow-emerald-500/20
                        @elseif($isCurrent)
                            bg-violet-600 text-white ring-4 ring-violet-100 dark:ring-violet-900/40 animate-pulse
                        @elseif($isReturned)
                            bg-amber-500 text-white ring-4 ring-amber-100 dark:ring-amber-900/40
                        @elseif($isRejected)
                            bg-rose-500 text-white
                        @else
                            bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500
                        @endif">
                        @if($isApproved)
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                        @elseif($isReturned)
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h10a5 5 0 015 5v2m-15-7l4-4m-4 4l4 4" /></svg>
                        @elseif($isRejected)
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                        @else
                            {{ $step->order }}
                        @endif
                    </div>

                    <!-- Step Content Card -->
                    <div class="flex-1 min-w-0 bg-gray-50 dark:bg-gray-750/50 rounded-2xl p-4 border border-gray-100 dark:border-gray-700/60
                        @if($isCurrent) ring-2 ring-violet-500/30 bg-violet-50/20 dark:bg-violet-900/10 @endif">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs font-black uppercase tracking-wider px-2 py-0.5 rounded-md
                                        @if($step->action_type === 'endorsement') bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300
                                        @elseif($step->action_type === 'justification') bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300
                                        @elseif($step->action_type === 'approval') bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300
                                        @elseif($step->action_type === 'publication') bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300
                                        @else bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 @endif">
                                        {{ $step->action_type_label }}
                                    </span>
                                    <h3 class="text-sm font-black text-gray-900 dark:text-white">
                                        {{ $step->name }}
                                    </h3>
                                    @if($step->requires_signature)
                                        <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800/50 px-2 py-0.5 rounded-md">
                                            <svg class="w-3 h-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                            Firma Digital Requerida
                                        </span>
                                    @endif
                                    @if($isCurrent)
                                        <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-widest rounded-full bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300 animate-pulse">
                                            Paso Actual
                                        </span>
                                    @endif
                                </div>
                                @if($step->description)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $step->description }}
                                    </p>
                                @endif
                            </div>

                            <!-- Assignment Badge -->
                            <div class="text-xs text-right shrink-0">
                                @if($step->assignedUser)
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-white dark:bg-gray-800 border border-gray-200/70 dark:border-gray-700 text-gray-700 dark:text-gray-300 shadow-2xs font-semibold">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                        {{ $step->assignedUser->name }}
                                    </div>
                                @elseif($step->assignedRole)
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-white dark:bg-gray-800 border border-gray-200/70 dark:border-gray-700 text-gray-700 dark:text-gray-300 shadow-2xs font-semibold">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                        Rol: {{ ucfirst($step->assignedRole->name) }}
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs italic">Cualquier responsable</span>
                                @endif
                            </div>
                        </div>

                        <!-- Step Result / Observations if resolved -->
                        @if($step->action_taken_by_id)
                            <div class="mt-3 pt-3 border-t border-gray-200/60 dark:border-gray-700/60 text-xs">
                                <div class="flex items-center justify-between text-gray-500 dark:text-gray-400 mb-1">
                                    <span class="font-bold flex items-center gap-1.5">
                                        @if($isApproved)
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                            Resuelto por: <strong class="text-gray-900 dark:text-white">{{ $step->actionUser?->name }}</strong>
                                        @elseif($isReturned)
                                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                            Devuelto por: <strong class="text-gray-900 dark:text-white">{{ $step->actionUser?->name }}</strong>
                                        @elseif($isRejected)
                                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                            Rechazado por: <strong class="text-gray-900 dark:text-white">{{ $step->actionUser?->name }}</strong>
                                        @endif
                                    </span>
                                    <span class="text-[11px] font-mono">
                                        {{ $step->completed_at?->format('d/m/Y H:i') }}
                                    </span>
                                </div>
                                @if($step->comments)
                                    <div class="mt-1 p-2.5 rounded-xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 text-gray-700 dark:text-gray-300 italic">
                                        “{{ $step->comments }}”
                                    </div>
                                @endif

                                @if($step->signed_at)
                                    <div class="mt-2.5 flex items-center justify-between p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-xs">
                                        <div class="flex items-center gap-2 text-emerald-800 dark:text-emerald-300">
                                            <div class="w-6 h-6 rounded-lg bg-emerald-500 text-white flex items-center justify-center shrink-0">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            </div>
                                            <div>
                                                <div class="font-bold flex items-center gap-1.5">
                                                    <span>Firmado Electrónicamente</span>
                                                    <span class="text-[10px] font-mono px-1.5 py-0.2 rounded bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 font-semibold">{{ strtoupper($step->signature_data['method'] ?? 'Certificado PAdES') }}</span>
                                                </div>
                                                <div class="text-[11px] text-emerald-700 dark:text-emerald-400">
                                                    Por {{ $step->signature_data['signer_name'] ?? $step->actionUser?->name }} ({{ $step->signature_data['signer_email'] ?? $step->actionUser?->email }})
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-[10px] font-mono text-emerald-600 dark:text-emerald-400 shrink-0">
                                            {{ $step->signed_at->format('d/m/Y H:i:s') }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

