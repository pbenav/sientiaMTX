<x-legal-layout title="Términos de Servicio" updatedAt="{{ date('d \d\e F \d\e Y') }}">
@if($content)
    {!! $content !!}
@else

<h1>Términos de Servicio de {{ config('app.name', 'Sientia Open Source Lab') }}</h1>
<p class="meta">Última actualización: <strong>{{ date('d/m/Y') }}</strong></p>

<p>Los presentes Términos de Servicio (en adelante, el «Acuerdo» o los «Términos») rigen el acceso y uso de la plataforma de gestión de proyectos, comunicación y productividad <strong>{{ config('app.name', 'Sientia Open Source Lab') }}</strong> (en adelante, el «Servicio»), proporcionada por <strong><span class="field">[Nombre o razón social de la Entidad]</span></strong> (en adelante, la «Empresa»).</p>

<p>Al acceder, registrarse o utilizar el Servicio, usted (el «Usuario» o el «Cliente») acepta quedar vinculado por estos Términos en su totalidad. Si no acepta estos Términos, no podrá acceder ni utilizar el Servicio.</p>

<h2>1. Definiciones</h2>
<ul>
    <li><strong>Servicio:</strong> La plataforma de software como servicio (SaaS) {{ config('app.name') }}, incluyendo sus interfaces web, aplicaciones móviles, API e integraciones.</li>
    <li><strong>Cliente:</strong> La entidad física o jurídica que contrata el Servicio.</li>
    <li><strong>Datos del Cliente:</strong> Todo contenido, información o material que el Cliente o sus Usuarios Finales carguen o procesen en el Servicio.</li>
</ul>

<h2>2. Cuentas y Registro</h2>
<p>Para utilizar el Servicio, debe crear una cuenta proporcionando información veraz y completa. Usted es responsable de mantener la confidencialidad de sus credenciales. Cualquier uso no autorizado debe notificarse inmediatamente a <strong>{{ config('mail.from.address', '[correo de soporte]') }}</strong>.</p>

<h2>3. Licencia de Uso</h2>
<p>La Empresa otorga al Cliente una licencia limitada, no exclusiva e intransferible para utilizar el Servicio durante la vigencia de su suscripción, exclusivamente para fines comerciales internos.</p>

<h2>4. Restricciones de Uso</h2>
<p>Queda prohibido: realizar ingeniería inversa del Servicio, utilizarlo para fines ilícitos, interferir con su funcionamiento o intentar acceder a datos de otros usuarios sin autorización.</p>

<h2>5. Propiedad Intelectual</h2>
<p>La Empresa conserva todos los derechos sobre el Servicio, sus marcas y logotipos. El Cliente conserva la propiedad de los datos que cargue, otorgando a la Empresa una licencia limitada para procesarlos con el fin de prestar el Servicio.</p>

<h2>6. Inteligencia Artificial (IA)</h2>
<p>El Servicio incluye herramientas de IA. El Usuario reconoce que los resultados de la IA pueden no ser siempre precisos y es su responsabilidad validarlos. La Empresa no se hace responsable de las decisiones tomadas basadas en resultados de la IA.</p>

<h2>7. Integraciones de Terceros</h2>
<p>El Servicio se integra con aplicaciones como Google Workspace, Telegram y WhatsApp. El uso de estas integraciones está sujeto a los términos de sus respectivos proveedores.</p>

<h2>8. Limitación de Responsabilidad</h2>
<p>En la medida permitida por la ley, la Empresa no será responsable por daños indirectos o pérdida de datos. La responsabilidad total de la Empresa se limitará al importe pagado por el Cliente en los últimos 12 meses.</p>

<h2>9. Ley Aplicable</h2>
<p>Este Acuerdo se rige por las leyes de <strong><span class="field">[España / Región]</span></strong>. Cualquier disputa se someterá a los tribunales de <strong><span class="field">[Ciudad de la Jurisdicción]</span></strong>.</p>

<h2>10. Contacto</h2>
<p>
    <strong><span class="field">[Nombre de la Empresa]</span></strong><br>
    <strong><span class="field">[Dirección física]</span></strong><br>
    <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>
</p>


@endif
</x-legal-layout>
