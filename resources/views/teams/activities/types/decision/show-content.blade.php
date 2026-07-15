@php
    $meta = $activity->metadata ?? [];
    $decisionType = $meta['decision_type'] ?? '';
    $decisionTypeLabels = [
        'aprobar'     => 'Aprobada',
        'rechazar'    => 'Rechazada',
        'implementar' => 'Implementada',
        'proponer'    => 'Propuesta',
    ];

    $displayDescription  = $activity->description  ?: ($activity->parent?->description ?? null);
    $displayObservations = $activity->observations ?: ($activity->parent?->observations ?? null);
@endphp

{{-- ① Descripción (compacta, con scroll) --}}
@if ($displayDescription)
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
            {{ __('activities.description') }}
        </h3>
        <button onclick="printSection('Descripción', 'description-content')"
                class="p-1.5 bg-gray-50 dark:bg-gray-800 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-xl transition-all border border-transparent hover:border-violet-100 dark:hover:border-violet-800 shadow-sm flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest"
                title="Imprimir descripción">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Imprimir
        </button>
    </div>
    {{-- Altura fija y pequeña con scroll --}}
    <div id="description-content"
         class="text-sm text-gray-700 dark:text-gray-300 prose dark:prose-invert max-w-none prose-sm leading-relaxed custom-scrollbar pr-2 py-1 overflow-y-auto"
         style="max-height: 140px;">
        {!! str($displayDescription)->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
    </div>
</div>
@endif

{{-- ② Datos del Acuerdo --}}
@if(!empty($meta['agreement_date']) || !empty($decisionType) || !empty($meta['justification']))
<div class="bg-violet-50 dark:bg-violet-900/10 border border-violet-100 dark:border-violet-800/30 rounded-2xl p-5 shadow-sm">
    <h3 class="text-xs font-black text-violet-600 dark:text-violet-400 uppercase tracking-widest mb-4 flex items-center gap-1.5">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        Datos del Acuerdo
    </h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @if(!empty($meta['agreement_date']))
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Fecha del Acuerdo</p>
            <p class="text-sm font-bold text-gray-800 dark:text-gray-200">
                {{ \Carbon\Carbon::parse($meta['agreement_date'])->translatedFormat('d \d\e F \d\e Y') }}
            </p>
        </div>
        @endif

        @if(!empty($decisionType))
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Tipo de Decisión</p>
            <span class="px-3 py-1 rounded-lg text-xs font-black bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300 border border-violet-200 dark:border-violet-800/30">
                {{ $decisionTypeLabels[$decisionType] ?? ucfirst($decisionType) }}
            </span>
        </div>
        @endif

        @if(!empty($meta['justification']))
        <div class="sm:col-span-2">
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Justificación</p>
            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $meta['justification'] }}</p>
        </div>
        @endif
    </div>
</div>
@endif

{{-- ③ Términos del Acuerdo (documento a firmar) --}}
@if(!empty($meta['terms']))
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Términos del Acuerdo
        </h3>
        <button onclick="printSection('Términos del Acuerdo', 'terms-content')"
                class="p-1.5 bg-gray-50 dark:bg-gray-800 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-xl transition-all border border-transparent hover:border-violet-100 dark:hover:border-violet-800 shadow-sm flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest"
                title="Imprimir términos">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Imprimir
        </button>
    </div>
    <div id="terms-content"
         class="text-sm text-gray-700 dark:text-gray-300 prose dark:prose-invert max-w-none prose-sm leading-relaxed overflow-y-auto resize-y custom-scrollbar pr-4 py-2"
         style="height: 320px; min-height: 200px;">
        {!! str($meta['terms'])->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
    </div>
</div>
@endif

{{-- ④a Firmantes Internos (miembros del equipo asignados) --}}
@php
    $memberSignatures = $meta['member_signatures'] ?? [];
    $currentUserId    = auth()->id();
@endphp
@if(!empty($memberSignatures))
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm">
    <h3 class="text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4 flex items-center gap-1.5">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
        </svg>
        Firmantes Internos
    </h3>
    <div class="space-y-3">
        @foreach($memberSignatures as $sig)
        @php
            $isSigned  = !empty($sig['signed_at']);
            $isMe      = (int)($sig['user_id'] ?? 0) === $currentUserId;
        @endphp
        <div class="flex items-center justify-between gap-3 p-3 rounded-xl border
            {{ $isSigned ? 'bg-emerald-50 dark:bg-emerald-900/10 border-emerald-100 dark:border-emerald-800/30' : 'bg-indigo-50/40 dark:bg-indigo-900/10 border-indigo-100 dark:border-indigo-800/30' }}">

            {{-- Avatar + datos --}}
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 text-sm font-black
                    {{ $isSigned ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' }}">
                    {{ strtoupper(substr($sig['name'] ?? '?', 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-gray-800 dark:text-gray-200 truncate">
                        {{ $sig['name'] ?? '—' }}
                        @if($isMe) <span class="text-[9px] font-black uppercase tracking-widest text-indigo-500 ml-1">Tú</span> @endif
                    </p>
                    @if($isSigned)
                        <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold">
                            Firmado el {{ \Carbon\Carbon::parse($sig['signed_at'])->translatedFormat('d M Y, H:i') }}
                        </p>
                    @else
                        <p class="text-[10px] text-indigo-500 dark:text-indigo-400">Firma pendiente</p>
                    @endif
                </div>
            </div>

            {{-- Estado / Acción --}}
            <div class="flex items-center gap-2 shrink-0">
                @if($isSigned)
                    <span class="flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-black bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/30">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        Firmado
                    </span>
                @elseif($isMe)
                    <button
                        id="btn-sign-internal"
                        type="button"
                        onclick="signAsInternalMember()"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] font-black bg-indigo-600 text-white hover:bg-indigo-700 active:scale-95 transition-all shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        Firmar ahora
                    </button>
                @else
                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 border border-amber-200 dark:border-amber-800/30">
                        Pendiente
                    </span>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- ④b Partes / Invitados Externos --}}
@if(!empty($meta['guests']))
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm">
    <h3 class="text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4 flex items-center gap-1.5">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        Partes / Firmantes Externos
    </h3>
    <div class="space-y-3">
        @foreach($meta['guests'] as $guest)
        @php
            $signed = !empty($guest['signed_at']);
        @endphp
        <div class="flex items-center justify-between gap-3 p-3 rounded-xl border
            {{ $signed ? 'bg-emerald-50 dark:bg-emerald-900/10 border-emerald-100 dark:border-emerald-800/30' : 'bg-gray-50 dark:bg-gray-800/50 border-gray-100 dark:border-gray-700' }}">

            {{-- Avatar + datos --}}
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 text-sm font-black
                    {{ $signed ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                    {{ strtoupper(substr($guest['name'] ?? '?', 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-gray-800 dark:text-gray-200 truncate">{{ $guest['name'] ?? '—' }}</p>
                    <p class="text-[10px] text-gray-500 truncate">{{ $guest['email'] ?? '' }}</p>
                    @if($signed)
                        <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold">
                            Firmado el {{ \Carbon\Carbon::parse($guest['signed_at'])->translatedFormat('d M Y, H:i') }}
                        </p>
                    @endif
                </div>
            </div>

            {{-- Estado + botón reenvío --}}
            <div class="flex items-center gap-2 shrink-0">
                @if($signed)
                    <span class="flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-black bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/30">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        Firmado
                    </span>
                @else
                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 border border-amber-200 dark:border-amber-800/30">
                        Pendiente
                    </span>
                    @if($team->isCoordinator(auth()->user()) || auth()->id() === $activity->created_by_id)
                        <button
                            type="button"
                            onclick="resendAgreementInvitation('{{ $guest['email'] }}')"
                            title="Reenviar invitación de firma"
                            class="flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-black bg-violet-50 text-violet-600 hover:bg-violet-100 dark:bg-violet-900/20 dark:text-violet-400 dark:hover:bg-violet-900/40 border border-violet-200 dark:border-violet-800/30 transition-all active:scale-95">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            Reenviar
                        </button>
                    @endif
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- ⑤ Notas de Implementación --}}
@if(!empty($meta['implementation_notes']))
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm">
    <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Notas de Implementación</h3>
    <div class="text-sm text-gray-700 dark:text-gray-300 prose dark:prose-invert max-w-none prose-sm leading-relaxed">
        {!! str($meta['implementation_notes'])->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
    </div>
</div>
@endif

{{-- ⑥ Participantes internos --}}
@if(!empty($meta['participants']))
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm">
    <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Participantes</h3>
    <div class="flex gap-2 flex-wrap">
        @foreach((array)$meta['participants'] as $participant)
        <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300 border border-red-200 dark:border-red-800/30">{{ $participant }}</span>
        @endforeach
    </div>
</div>
@endif

{{-- ⑦ Observaciones --}}
@if ($displayObservations)
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
            {{ __('activities.observations') }}
        </h3>
        <button onclick="printSection('Observaciones', 'observations-content')"
                class="p-1.5 bg-gray-50 dark:bg-gray-800 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-xl transition-all border border-transparent hover:border-violet-100 dark:hover:border-violet-800 shadow-sm flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest"
                title="Imprimir observaciones">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Imprimir
        </button>
    </div>
    <div id="observations-content"
         class="text-sm text-gray-700 dark:text-gray-300 prose dark:prose-invert max-w-none prose-sm leading-relaxed overflow-y-auto resize-y custom-scrollbar pr-4 py-2"
         style="height: 200px; min-height: 100px;">
        {!! str($displayObservations)->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
    </div>
</div>
@endif

{{-- Scripts: firma interna y reenvío de invitación --}}
<script>
const SHOW_INTERNAL_URL = '{{ route("agreements.signature.show-internal", [$team, $activity]) }}';
const RESEND_URL        = '{{ route("agreements.signature.resend", [$team, $activity]) }}';
const CSRF_TOKEN        = '{{ csrf_token() }}';

function signAsInternalMember() {
    Swal.fire({
        title: '¿Iniciar firma digital?',
        text: 'Se abrirá el portal de firma electrónica para firmar con AutoFirma y su certificado digital.',
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#4f46e5',
    }).then(result => {
        if (result.isConfirmed) {
            window.location.href = SHOW_INTERNAL_URL;
        }
    });
}

function resendAgreementInvitation(email) {
    const btn = event.currentTarget;
    const origHtml = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<svg class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';

    fetch(RESEND_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify({ email })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                toast: true, position: 'top-end', icon: 'success',
                title: data.message ?? 'Invitación reenviada correctamente.',
                showConfirmButton: false, timer: 4000, timerProgressBar: true
            });
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.error ?? 'No se pudo reenviar.' });
        }
    })
    .catch(() => {
        Swal.fire({ icon: 'error', title: 'Error de red', text: 'No se pudo contactar con el servidor.' });
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = origHtml;
    });
}
</script>
