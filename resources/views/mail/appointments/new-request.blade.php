<x-mail::message>
# {{ __('Nueva Solicitud de Cita') }}

{{ __('Hola') }} {{ $appointment->member->name }},

{{ __('Se ha registrado una nueva solicitud de cita previa en su calendario.') }}

**{{ __('Detalles de la Solicitud:') }}**
- **{{ __('Ciudadano:') }}** {{ $appointment->visitor->full_name }}
- **{{ __('Localizador:') }}** `{{ $appointment->localizador }}`
- **{{ __('Fecha:') }}** {{ $appointment->appointment_datetime->format('d/m/Y') }}
- **{{ __('Hora:') }}** {{ $appointment->appointment_datetime->format('H:i') }}
- **{{ __('Servicio:') }}** {{ $appointment->service->name ?? __('Cita General') }}
- **{{ __('Modalidad:') }}** {{ in_array($appointment->modality ?? 'presencial', ['jitsi', 'meet']) ? __('Videoconferencia') : __('Presencial') }}

{{ __('Puede gestionar esta y otras citas desde su panel de control.') }}

<x-mail::button :url="route('appointments.list', $appointment->service->team_id)" color="primary">
{{ __('Ver Mis Citas') }}
</x-mail::button>

{{ __('Gracias') }},<br>
{{ config('app.name') }}
</x-mail::message>
