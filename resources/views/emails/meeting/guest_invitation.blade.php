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

**Detalles de la Reunión:**
@php
    $meta = $activity->metadata ?? [];
    $location = $meta['location'] ?? 'No especificada';
    $duration = $meta['duration_minutes'] ?? 'No especificada';
    $scheduled = $activity->scheduled_date ? $activity->scheduled_date->format('d/m/Y H:i') : 'No especificada';
@endphp
- **Fecha y Hora:** {{ $scheduled }}
- **Lugar / Enlace:** {{ $location }}
- **Duración:** {{ $duration }} {{ is_numeric($duration) ? 'minutos' : '' }}

@if(filter_var($location, FILTER_VALIDATE_URL))
<x-mail::button :url="$location">
Unirse a la Reunión
</x-mail::button>
@endif

Un saludo,<br>
{{ config('app.name') }}
</x-mail::message>
