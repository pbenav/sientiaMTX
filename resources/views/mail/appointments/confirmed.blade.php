<x-mail::message>
# ¡Su cita ha sido confirmada!

Hola {{ $appointment->visitor->full_name }},

Le confirmamos que su cita previa ha sido registrada correctamente en nuestro sistema.

**Detalles de su Cita:**
- **Localizador:** `{{ $appointment->localizador }}`
- **Fecha:** {{ $appointment->appointment_datetime->format('d/m/Y') }}
- **Hora:** {{ $appointment->appointment_datetime->format('H:i') }}
- **Servicio:** {{ $appointment->service->name ?? 'Cita General' }}
- **Modalidad:** {{ in_array($appointment->modality ?? 'presencial', ['jitsi', 'meet']) ? 'Videoconferencia' : 'Presencial' }}

@if(in_array($appointment->modality ?? 'presencial', ['jitsi', 'meet']))
Puede acceder a la sala de videoconferencia en el momento de la cita utilizando su localizador y documento de identidad en nuestro portal.

<x-mail::button :url="route('public.appointments.video.auth', $appointment->localizador)" color="primary">
Acceder al Portal de Videocita
</x-mail::button>
@else
Le esperamos en nuestras oficinas. Por favor, acuda con 5 minutos de antelación.
@endif

Si necesita cancelar o modificar su cita, por favor, póngase en contacto con nosotros.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
