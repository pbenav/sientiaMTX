<x-mail::message>
# Nueva Solicitud de Cita

Hola {{ $appointment->member->name }},

Se ha registrado una nueva solicitud de cita previa en su calendario.

**Detalles de la Solicitud:**
- **Ciudadano:** {{ $appointment->visitor->full_name }}
- **Localizador:** `{{ $appointment->localizador }}`
- **Fecha:** {{ $appointment->appointment_datetime->format('d/m/Y') }}
- **Hora:** {{ $appointment->appointment_datetime->format('H:i') }}
- **Servicio:** {{ $appointment->service->name ?? 'Cita General' }}
- **Modalidad:** {{ in_array($appointment->modality ?? 'presencial', ['jitsi', 'meet']) ? 'Videoconferencia' : 'Presencial' }}

Puede gestionar esta y otras citas desde su panel de control.

<x-mail::button :url="route('appointments.list')" color="primary">
Ver Mis Citas
</x-mail::button>

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
