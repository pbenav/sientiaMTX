@component('mail::message')
# Solicitud de Firma de Acuerdo

Hola **{{ $guestName }}**,

Has sido invitado/a por **{{ $inviter->name }}** para revisar y firmar digitalmente un acuerdo en **SientiaMTX**.

@if($customMessage)
@component('mail::panel')
{!! nl2br(e($customMessage)) !!}
@endcomponent
@endif

**Asunto / Título:** {{ $activity->title }}

Este documento requiere tu firma digital (utilizando Autofirma) para constancia y validez del acuerdo. Puedes revisar el documento y proceder a su firma haciendo clic en el siguiente botón:

@component('mail::button', ['url' => $signatureUrl, 'color' => 'success'])
Revisar y Firmar Documento
@endcomponent

<br>
*Nota: Necesitarás tener instalada la aplicación Autofirma (Gobierno de España) y un certificado digital válido en tu dispositivo.*

Gracias,<br>
{{ config('app.name') }}
@endcomponent
