<x-mail::message>
# Hola {{ $guestName }},

Has sido invitado/a a una reunión en **SientiaMTX** por **{{ $inviter->name }}** ({{ $inviter->email }}).

@if(!empty($customMessage))
<x-mail::panel>
{!! nl2br(e($customMessage)) !!}
</x-mail::panel>
@endif

**Asunto / Título:** {{ $activity->title }}

@if($activity->description)
**Descripción:**
{{ strip_tags(str()->markdown($activity->description)) }}
@endif

**Detalles de la Convocatoria:**
@php
    $meta = $activity->metadata ?? [];
    $location = $meta['location'] ?? 'No especificada';
    $joinUrl = $meta['join_url'] ?? $meta['google_meet_url'] ?? (filter_var($location, FILTER_VALIDATE_URL) ? $location : null);
    $duration = $meta['duration_minutes'] ?? 'No especificada';
    $scheduled = $activity->scheduled_date ? $activity->scheduled_date->format('d/m/Y H:i') : 'No especificada';
    $agenda = $meta['agenda'] ?? null;
@endphp
- **Fecha y Hora:** {{ $scheduled }}
@if(!empty($meta['modality']))
- **Modalidad:** {{ $meta['modality'] === 'remote' ? 'En remoto / Online' : ($meta['modality'] === 'presential' ? 'Presencial' : ($meta['modality'] === 'hybrid' ? 'Híbrida' : ucfirst($meta['modality']))) }}
@endif
- **Lugar / Enlace:** {{ $location }}
- **Duración estimada:** {{ $duration }} {{ is_numeric($duration) ? 'minutos' : '' }}

@if($agenda)
**Agenda:**
{{ strip_tags(str()->markdown($agenda)) }}
@endif

@if($joinUrl)
<x-mail::button :url="$joinUrl">
{{ str_contains($joinUrl, 'meet.google.com') ? 'Unirse con Google Meet' : 'Unirse a la Reunión' }}
</x-mail::button>
@endif

Un saludo,<br>
{{ config('app.name') }}
</x-mail::message>
