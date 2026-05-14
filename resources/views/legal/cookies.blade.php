<x-legal-layout title="Política de Cookies">
@if($content)
    {!! $content !!}
@else

<h1>Política de Cookies</h1>
<p class="meta">Última actualización: <strong>{{ date('d/m/Y') }}</strong></p>

<p>Esta Política de Cookies explica qué son las cookies y tecnologías similares, cómo las utilizamos en <strong>{{ config('app.name', 'Sientia') }}</strong>, y qué opciones tienes para controlarlas.</p>

<h2>1. ¿Qué son las Cookies?</h2>
<p>Las cookies son pequeños archivos de texto que se descargan en tu dispositivo cuando visitas un sitio web. Sirven para que el sitio recuerde información sobre tu visita (como tu sesión iniciada o tus preferencias de idioma), lo que facilita tu experiencia y nos ayuda a mejorar el servicio.</p>
<p>Además de las cookies en sentido estricto, utilizamos tecnologías similares como las <em>web beacons</em> (píxeles de seguimiento) o el <em>almacenamiento local del navegador</em>. En esta política nos referimos a todas ellas colectivamente como "cookies".</p>

<h2>2. ¿Cómo Utilizamos las Cookies?</h2>
<p>Utilizamos cookies para:</p>
<ul>
    <li>Mantener tu sesión activa y permitirte acceder a áreas protegidas del servicio.</li>
    <li>Recordar tus preferencias (idioma, modo oscuro, disposición de la interfaz).</li>
    <li>Analizar el uso del servicio y mejorar su rendimiento y usabilidad.</li>
    <li>Garantizar la seguridad de la plataforma y prevenir el fraude.</li>
    <li>Integrarnos con servicios de terceros que hayas activado (Google Workspace, etc.).</li>
</ul>

<h2>3. Tipos de Cookies que Utilizamos</h2>

<table>
    <thead>
        <tr>
            <th>Categoría</th>
            <th>Descripción</th>
            <th>¿Desactivables?</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Estrictamente necesarias</strong></td>
            <td>Imprescindibles para el funcionamiento del servicio: gestión de sesión, tokens CSRF, preferencias de consentimiento.</td>
            <td>No</td>
        </tr>
        <tr>
            <td><strong>Funcionales</strong></td>
            <td>Mejoran la experiencia recordando preferencias (idioma, tema visual, configuración de notificaciones).</td>
            <td>Sí</td>
        </tr>
        <tr>
            <td><strong>Analíticas / de rendimiento</strong></td>
            <td>Nos permiten medir cómo se utiliza el servicio para mejorarlo. Los datos se agregan y anonomizan en la medida de lo posible.</td>
            <td>Sí</td>
        </tr>
        <tr>
            <td><strong>De terceros</strong></td>
            <td>Establecidas por proveedores externos cuando activas integraciones (ej. Google). Su uso se rige por las políticas de dichos proveedores.</td>
            <td>Sí (desactivando la integración)</td>
        </tr>
    </tbody>
</table>

<h2>4. Duración de las Cookies</h2>
<ul>
    <li><strong>Cookies de sesión:</strong> se eliminan automáticamente al cerrar el navegador. Las usamos para gestionar tu sesión de inicio de sesión.</li>
    <li><strong>Cookies persistentes:</strong> permanecen en tu dispositivo durante un período determinado (desde unas horas hasta varios meses, según su función). Nos permiten recordar tus preferencias entre sesiones.</li>
</ul>

<h2>5. Cookies Específicas que Utilizamos</h2>

<table>
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Tipo</th>
            <th>Duración</th>
            <th>Finalidad</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><code>{{ config('session.cookie', 'sientia_session') }}</code></td>
            <td>Sesión / Necesaria</td>
            <td>{{ config('session.lifetime', 120) }} minutos</td>
            <td>Identificador de sesión de usuario autenticado.</td>
        </tr>
        <tr>
            <td><code>XSRF-TOKEN</code></td>
            <td>Sesión / Necesaria</td>
            <td>Sesión</td>
            <td>Protección CSRF (falsificación de solicitudes entre sitios).</td>
        </tr>
        <tr>
            <td><code>theme</code></td>
            <td>Funcional</td>
            <td>1 año</td>
            <td>Preferencia de tema visual (claro/oscuro).</td>
        </tr>
        <tr>
            <td><code>locale</code></td>
            <td>Funcional</td>
            <td>1 año</td>
            <td>Preferencia de idioma del usuario.</td>
        </tr>
        <tr>
            <td><span class="field">[nombre cookie analítica]</span></td>
            <td>Analítica</td>
            <td><span class="field">[duración]</span></td>
            <td><span class="field">[descripción]</span></td>
        </tr>
    </tbody>
</table>

<div class="callout">
    Si utilizas integraciones de Google (Drive, Calendar, Meet), Google puede establecer sus propias cookies en tu navegador. Consulta la <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Política de Privacidad de Google</a> para más información.
</div>

<h2>6. Gestión y Control de Cookies</h2>
<p>Puedes controlar y gestionar las cookies de varias formas:</p>

<h3>6.1 Configuración del navegador</h3>
<p>La mayoría de los navegadores te permiten ver, bloquear o eliminar cookies desde sus ajustes. Ten en cuenta que desactivar ciertas cookies puede afectar al funcionamiento del servicio:</p>
<ul>
    <li><a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener">Google Chrome</a></li>
    <li><a href="https://support.mozilla.org/es/kb/cookies-informacion-que-los-sitios-web-guardan-en-" target="_blank" rel="noopener">Mozilla Firefox</a></li>
    <li><a href="https://support.apple.com/es-es/guide/safari/sfri11471/mac" target="_blank" rel="noopener">Apple Safari</a></li>
    <li><a href="https://support.microsoft.com/es-es/microsoft-edge/eliminar-las-cookies-en-microsoft-edge-63947406-40ac-c3b8-57b9-2a946a29ae09" target="_blank" rel="noopener">Microsoft Edge</a></li>
</ul>

<h3>6.2 Desactivación de cookies de terceros</h3>
<p>Para las cookies establecidas por integraciones de terceros, puedes desactivar la integración correspondiente desde tu perfil en la sección <strong>Integraciones</strong>.</p>

<h3>6.3 Do Not Track</h3>
<p>Actualmente no respondemos de forma automática a las señales "Do Not Track" del navegador, ya que no existe un estándar universal aceptado. Sin embargo, sí respetamos el <strong>Global Privacy Control (GPC)</strong> en los territorios donde sea requerido.</p>

<h2>7. Cambios en esta Política</h2>
<p>Podemos actualizar esta Política cuando sea necesario para reflejar cambios en nuestras prácticas o en la normativa aplicable. Te recomendamos revisarla periódicamente. La fecha de última actualización siempre estará visible al inicio del documento.</p>

<h2>8. Contacto</h2>
<p>Si tienes preguntas sobre cómo utilizamos las cookies, contáctanos en:<br>
<a href="mailto:{{ config('mail.from.address', '[correo de privacidad]') }}">{{ config('mail.from.address', '[correo de privacidad]') }}</a><br>
<strong class="field">[Nombre de la empresa]</strong> · <strong class="field">[Dirección postal]</strong>
</p>

@endif
</x-legal-layout>
