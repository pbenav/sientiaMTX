@php
    $meta = $activity->metadata ?? [];
    $location = $meta['location'] ?? '';
    $joinUrl = filter_var($location, FILTER_VALIDATE_URL) ? $location : ($meta['join_url'] ?? ($meta['google_meet_url'] ?? null));
    $duration = $meta['duration_minutes'] ?? null;
    $displayObservations = $activity->observations ?: ($activity->parent?->observations ?? null);
@endphp

{{-- Meeting Details Card --}}
<div class="bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-100 dark:border-emerald-800/30 rounded-2xl p-5 shadow-sm">
    <h3 class="text-xs font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-3 flex items-center gap-1.5">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
        Detalles de la Reunión
    </h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Fecha Programada</p>
            <p class="text-sm font-bold text-gray-900 dark:text-white">
                {{ $activity->scheduled_date ? $activity->scheduled_date->format('d/m/Y H:i') : 'Por definir' }}
            </p>
        </div>

        @if(!empty($meta['modality']))
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Modalidad</p>
            <p class="text-sm font-bold text-gray-900 dark:text-white">
                @if($meta['modality'] === 'remote') 💻 En remoto / Online
                @elseif($meta['modality'] === 'presential') 🏢 Presencial
                @elseif($meta['modality'] === 'hybrid') 🤝 Híbrido
                @else {{ ucfirst($meta['modality']) }}
                @endif
            </p>
        </div>
        @endif

        @if(!empty($location))
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Ubicación / Enlace</p>
            @if($joinUrl)
                <a href="{{ $joinUrl }}" target="_blank" class="text-sm font-bold text-emerald-600 dark:text-emerald-400 hover:underline flex items-center gap-1 truncate">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                    <span class="truncate">{{ $location }}</span>
                </a>
            @else
                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $location }}</p>
            @endif
        </div>
        @endif

        @if(!empty($duration))
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Duración</p>
            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $duration }} min</p>
        </div>
        @endif
    </div>
    @if($joinUrl)
    <div class="mt-4 flex items-center gap-3 flex-wrap">
        @if(str_contains($joinUrl, 'meet.google.com'))
            <a href="{{ $joinUrl }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-black uppercase tracking-widest rounded-xl transition-all shadow-md active:scale-95">
                <svg class="w-4 h-4 shrink-0" viewBox="0 0 48 48">
                    <path fill="#FFC107" d="M17 6H11L2 22l3 5h6l9-16z"/>
                    <path fill="#2196F3" d="M37 42H11l-9-15 4-7h26l9 16z"/>
                    <path fill="#4CAF50" d="M15 6l9 16 9-16H15z"/>
                </svg>
                Unirse con Google Meet
            </a>
        @else
            <a href="{{ $joinUrl }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black uppercase tracking-widest rounded-xl transition-all shadow-md active:scale-95">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Unirse a la reunión
            </a>
        @endif

        @if(!empty($meta['google_html_link']))
            <a href="{{ $meta['google_html_link'] }}" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 hover:border-violet-300 dark:hover:border-violet-600 text-xs font-bold rounded-xl transition-all shadow-sm">
                <svg class="w-3.5 h-3.5 text-blue-500" viewBox="0 0 24 24" fill="currentColor"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20a2 2 0 0 0 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/></svg>
                Google Calendar
            </a>
        @endif
    </div>
    @endif
</div>

{{-- Participantes y Asistentes (Reunión Mixta / Interna) --}}
@php
    $internalAttendees = $activity->assignedTo;
    $externalGuests = $meta['guests'] ?? [];
    $hasInternals = $internalAttendees->isNotEmpty();
    $hasExternals = !empty($externalGuests);
    $isMixed = $hasInternals && $hasExternals;
@endphp

@if($hasInternals || $hasExternals || !empty($meta['attendees']))
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5 pb-4 border-b border-gray-100 dark:border-gray-800">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-violet-50 dark:bg-violet-900/30 flex items-center justify-center text-violet-600 dark:text-violet-400 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                    Participantes de la Reunión
                </h3>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">
                    {{ $internalAttendees->count() }} miembros internos &bull; {{ count($externalGuests) }} invitados externos
                </p>
            </div>
        </div>

        @if($isMixed)
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-gradient-to-r from-emerald-500/10 via-violet-500/10 to-blue-500/10 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-800/50">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Reunión Mixta (Interna + Externa)
            </span>
        @elseif($hasExternals)
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border border-blue-200 dark:border-blue-800/40">
                Invitados Externos
            </span>
        @elseif($hasInternals)
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40">
                Equipo Interno
            </span>
        @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Bloque: Miembros del Equipo (Internos) --}}
        <div>
            <h4 class="text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-3 flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                Equipo Interno MTX ({{ $internalAttendees->count() }})
            </h4>

            @if($internalAttendees->isNotEmpty())
                <div class="space-y-2">
                    @foreach($internalAttendees as $member)
                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50 dark:bg-gray-800/40 border border-gray-150 dark:border-gray-800">
                            <div class="flex items-center gap-3 min-w-0">
                                <img src="{{ $member->profile_photo_url }}" alt="{{ $member->name }}" class="w-8 h-8 rounded-lg object-cover shrink-0 border border-gray-200 dark:border-gray-700">
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-gray-900 dark:text-white truncate flex items-center gap-1.5">
                                        {{ $member->name }}
                                        @if($member->id === $activity->created_by_id)
                                            <span class="text-[9px] px-1.5 py-0.2 rounded bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300 font-normal">Organizador</span>
                                        @endif
                                    </p>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate">{{ $member->email }}</p>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded-md text-[9px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/30 shrink-0">
                                Interno
                            </span>
                        </div>
                    @endforeach
                </div>
            @elseif(!empty($meta['attendees']))
                <div class="flex flex-wrap gap-1.5">
                    @foreach((array)$meta['attendees'] as $att)
                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/30">{{ $att }}</span>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-gray-400 italic">No hay miembros internos asignados.</p>
            @endif
        </div>

        {{-- Bloque: Invitados Externos --}}
        <div>
            <h4 class="text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-3 flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                Invitados Externos ({{ count($externalGuests) }})
            </h4>

            @if(!empty($externalGuests))
                <div class="space-y-2">
                    @foreach($externalGuests as $guest)
                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50 dark:bg-gray-800/40 border border-gray-150 dark:border-gray-800">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800/40 flex items-center justify-center text-blue-600 dark:text-blue-400 text-xs font-bold shrink-0">
                                    {{ strtoupper(substr($guest['name'] ?? $guest['email'] ?? '?', 0, 2)) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-gray-900 dark:text-white truncate">
                                        {{ $guest['name'] ?? 'Invitado Externo' }}
                                    </p>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate">{{ $guest['email'] ?? '' }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                @if(!empty($guest['response_status']))
                                    @php
                                        $respMap = [
                                            'accepted' => ['label' => 'Aceptada', 'cls' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/30'],
                                            'declined' => ['label' => 'Rechazada', 'cls' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300 border-red-200 dark:border-red-800/30'],
                                            'tentative' => ['label' => 'Quizás', 'cls' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 border-amber-200 dark:border-amber-800/30'],
                                            'needsAction' => ['label' => 'Pendiente', 'cls' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 border-gray-200 dark:border-gray-700'],
                                        ];
                                        $statusCfg = $respMap[$guest['response_status']] ?? ['label' => ucfirst($guest['response_status']), 'cls' => 'bg-gray-100 text-gray-600 border-gray-200'];
                                    @endphp
                                    <span class="px-2 py-0.5 rounded-md text-[9px] font-bold border {{ $statusCfg['cls'] }}" title="Respuesta en Google Calendar">
                                        {{ $statusCfg['label'] }}
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-md text-[9px] font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border border-blue-200 dark:border-blue-800/30">
                                        Externo
                                    </span>
                                @endif

                                @can('update', $activity)
                                    @if(!empty($guest['email']))
                                        <button type="button" 
                                                onclick="resendMeetingInvitation('{{ $guest['email'] }}', '{{ addslashes($guest['name'] ?? 'Invitado') }}')"
                                                class="p-1 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 hover:bg-violet-50 dark:hover:bg-violet-900/30 rounded-lg transition-colors" 
                                                title="Reenviar invitación por correo">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </button>
                                    @endif
                                @endcan
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-gray-400 italic">No hay invitados externos especificados.</p>
            @endif
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
function resendMeetingInvitation(email, name) {
    Swal.fire({
        title: '¿Reenviar invitación?',
        text: `Se enviará un correo con los detalles de la reunión a ${name} (${email}).`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, enviar invitación',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#7c3aed',
        cancelButtonColor: '#6b7280',
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Enviando invitación...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('{{ route('teams.activities.resend_meeting_invitation', [$team, $activity]) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') || '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ email: email })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Invitación enviada!',
                        text: data.message,
                        timer: 3000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.error || 'No se pudo enviar la invitación.'
                    });
                }
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de red',
                    text: 'No se pudo contactar con el servidor.'
                });
            });
        }
    });
}
</script>
@endpush

{{-- Agenda --}}
@if(!empty($meta['agenda']))
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm">
    <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Agenda</h3>
    <div class="text-sm text-gray-700 dark:text-gray-300 prose dark:prose-invert max-w-none prose-sm leading-relaxed">
        {!! str($meta['agenda'])->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
    </div>
</div>
@endif

{{-- Post-meeting Acta --}}
@if(!empty($meta['post_meeting_acta']) || $displayObservations)
@php
    $acta = $meta['post_meeting_acta'] ?? null;
@endphp
@if($acta || $displayObservations)
<div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm">
    <h3 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">Acta Post-Reunión</h3>
    @if($acta)
    <div class="text-sm text-gray-700 dark:text-gray-300 prose dark:prose-invert max-w-none prose-sm leading-relaxed mb-4">
        {!! str($acta)->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
    </div>
    @endif
    @if($displayObservations)
    <div class="border-t border-gray-100 dark:border-gray-800 pt-4">
        <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">Observaciones</p>
        <div class="text-sm text-gray-700 dark:text-gray-300 prose dark:prose-invert max-w-none prose-sm leading-relaxed">
            {!! str($displayObservations)->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
        </div>
    </div>
    @endif
</div>
@endif
@endif

{{-- Description --}}
@php
    $displayDescription = $activity->description ?: ($activity->parent?->description ?? null);
@endphp

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
        <div id="description-content" style="height: 350px; max-height: none; overflow-y: auto;"
            class="text-sm text-gray-700 dark:text-gray-300 prose dark:prose-invert max-w-none prose-sm leading-relaxed resize-y min-h-[250px] custom-scrollbar pr-4 py-2">
            {!! str($displayDescription)->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
        </div>
    </div>
@endif
