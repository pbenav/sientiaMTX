<x-legal-layout title="Política de Cookies">
@if($content)
    {!! $content !!}
@else

<h1>Política de Cookies de {{ config('app.name', 'Sientia') }}</h1>
<p class="meta">Última actualización: <strong>{{ date('d/m/Y') }}</strong></p>

<p>Esta Política de Cookies explica cómo utilizamos las cookies y tecnologías similares en nuestra plataforma.</p>

<h2>1. ¿Qué son las Cookies?</h2>
<p>Las cookies son pequeños archivos de texto que se almacenan en su dispositivo para recordar información sobre su visita y mejorar su experiencia.</p>

<h2>2. Cookies que Utilizamos</h2>
<p>Utilizamos cookies esenciales para el funcionamiento técnico de la plataforma (sesión, seguridad) y cookies funcionales para recordar sus preferencias de idioma y tema.</p>

<h2>3. Tabla de Cookies</h2>
<table border="1" style="width:100%; border-collapse: collapse; text-align: left;">
    <thead><tr style="background-color: #f2f2f2;"><th>Nombre</th><th>Propósito</th><th>Duración</th></tr></thead>
    <tbody>
        <tr><td><strong>{{ config('session.cookie', 'sientia_session') }}</strong></td><td>Gestión de sesión de usuario.</td><td>{{ config('session.lifetime', 120) }} min</td></tr>
        <tr><td><strong>XSRF-TOKEN</strong></td><td>Seguridad contra ataques CSRF.</td><td>Sesión</td></tr>
        <tr><td><strong>theme / locale</strong></td><td>Preferencias visuales e idioma.</td><td>1 año</td></tr>
    </tbody>
</table>

<h2>4. Control de Cookies</h2>
<p>Usted puede bloquear o eliminar las cookies a través de la configuración de su navegador. Tenga en cuenta que esto puede afectar la disponibilidad de ciertas funciones del Servicio.</p>

<h2>5. Contacto</h2>
<p>Para más información, contáctenos en <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>.</p>


@endif
</x-legal-layout>
