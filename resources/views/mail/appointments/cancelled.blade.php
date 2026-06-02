<x-mail::message>
# Cita Cancelada

Hola {{ $appointment->visitor->full_name }},

Lamentamos informarle que su cita previa ha sido cancelada.

**Detalles de la Cita:**
- **Localizador:** `{{ $appointment->localizador }}`
- **Fecha:** {{ $appointment->appointment_datetime->format('d/m/Y') }}
- **Hora:** {{ $appointment->appointment_datetime->format('H:i') }}
- **Servicio:** {{ $appointment->service->name ?? 'Cita General' }}

@if($reason)
**Motivo de la cancelación:**
{{ $reason }}
@endif

Si desea solicitar una nueva cita, por favor visite nuestro portal.

<x-mail::button :url="route('public.appointments.map')" color="primary">
Solicitar Nueva Cita
</x-mail::button>

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
