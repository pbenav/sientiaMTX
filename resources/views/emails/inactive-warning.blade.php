<x-mail::message>
# 👋 ¡Hola {{ $user->name }}!

Hemos notado que hace bastante tiempo que no nos visitas en **{{ config('app.name') }}**.

Para mantener nuestra base de datos optimizada y segura, realizamos una limpieza automática de cuentas inactivas. Tu cuenta ha sido seleccionada para su eliminación debido a un periodo prolongado de inactividad.

<x-mail::panel>
🚨 Tu cuenta y todos tus datos asociados se eliminarán por completo en **{{ $gracePeriodDays }} días** a menos que inicies sesión.
</x-mail::panel>

### ¿Quieres conservar tu cuenta?
Es muy sencillo: solo tienes que acceder a la plataforma pulsando el siguiente botón. ¡Y listo! El proceso de eliminación se cancelará al instante de forma automática.

<x-mail::button :url="url('/')" color="success">
Iniciar Sesión Ahora
</x-mail::button>

Si ya no deseas utilizar nuestros servicios, no tienes que hacer nada; transcurrido el plazo tu cuenta se eliminará automáticamente respetando tu privacidad.

Gracias,<br>
El equipo de {{ config('app.name') }}
</x-mail::message>
