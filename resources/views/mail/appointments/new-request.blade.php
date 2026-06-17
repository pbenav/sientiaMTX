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

@if(!empty($appointment->custom_fields_values) && !empty($appointment->service->custom_fields))
**{{ __('Información Adicional:') }}**
@foreach($appointment->service->custom_fields as $field)
@if(!empty($appointment->custom_fields_values[$field['id']]))
- **{{ $field['name'] }}:** {{ $field['type'] === 'date' ? \Carbon\Carbon::parse($appointment->custom_fields_values[$field['id']])->format('d/m/Y') : $appointment->custom_fields_values[$field['id']] }}
@endif
@endforeach

@endif
{{ __('Puede gestionar esta y otras citas desde su panel de control.') }}

<x-mail::button :url="route('appointments.list', $appointment->service->team_id)" color="primary">
{{ __('Ver Mis Citas') }}
</x-mail::button>

{{ __('Gracias') }},<br>
{{ config('app.name') }}
</x-mail::message>
