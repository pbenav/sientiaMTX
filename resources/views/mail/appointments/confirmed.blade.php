<x-mail::message>
# {{ __('¡Su cita ha sido confirmada!') }}

{{ __('Hola') }} {{ $appointment->visitor->full_name }},

{{ __('Le confirmamos que su cita previa ha sido registrada correctamente en nuestro sistema.') }}

**{{ __('Detalles de su Cita:') }}**
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
@if(in_array($appointment->modality ?? 'presencial', ['jitsi', 'meet']))
{{ __('Puede acceder a la sala de videoconferencia en el momento de la cita utilizando su localizador y documento de identidad en nuestro portal.') }}

<x-mail::button :url="route('public.appointments.video.auth', $appointment->localizador)" color="primary">
{{ __('Acceder al Portal de Videocita') }}
</x-mail::button>
@else
{{ __('Le esperamos en nuestras oficinas. Por favor, acuda con 5 minutos de antelación.') }}
@endif

{{ __('Si necesita cancelar o modificar su cita, por favor, póngase en contacto con nosotros.') }}

{{ __('Gracias') }},<br>
{{ config('app.name') }}
</x-mail::message>
