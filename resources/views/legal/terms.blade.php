<x-legal-layout title="Términos de Servicio" updatedAt="{{ date('d \d\e F \d\e Y') }}">
@if($content)
    {!! $content !!}
@else

<h1>Términos de Servicio</h1>
<p class="meta">Última actualización: <strong>{{ date('d/m/Y') }}</strong> · Vigencia: <strong>{{ date('d/m/Y') }}</strong></p>

<p>Bienvenido a <strong>{{ config('app.name', 'Sientia') }}</strong>. Al registrarte y utilizar nuestra plataforma, aceptas en su totalidad los presentes Términos de Servicio. Por favor, léelos detenidamente antes de usar el servicio.</p>

<div class="callout">
    Si no aceptas estos Términos, no accedas ni utilices el servicio ni los sitios web.
</div>

<h2>1. Introducción</h2>
<p><strong class="field">[Nombre de la empresa o del responsable]</strong> ("<strong>{{ config('app.name') }}</strong>", "nosotros", "nos") ofrece una plataforma de productividad y gestión de trabajo en equipo, que incluye la Matriz de Eisenhower, Diagramas de Gantt, tableros Kanban, foros, expedientes, comunicación interna y videoconferencia, así como integraciones con servicios de terceros (Google Workspace, Telegram, WhatsApp). Al acceder al servicio reconoces haber leído y aceptado estos Términos, así como nuestra <a href="{{ route('privacy') }}">Política de Privacidad</a>.</p>

<p>Podemos revisar estos Términos periódicamente. Si los cambios son sustanciales, te notificaremos por correo electrónico o mediante un aviso dentro del servicio. El uso continuado tras la publicación de los cambios implica su aceptación.</p>

<h2>2. Elegibilidad y Alcance</h2>
<p>Para utilizar el servicio debes:</p>
<ul>
    <li>Tener al menos <strong>16 años</strong> de edad, o contar con el consentimiento de tus tutores legales si la legislación de tu país exige una edad mayor.</li>
    <li>No haber sido previamente suspendido o bloqueado de la plataforma.</li>
    <li>Actuar en tu propio nombre o estar debidamente autorizado a actuar en nombre de una organización.</li>
</ul>

<h2>3. Registro y Uso de la Cuenta</h2>
<h3>3.1 Registro</h3>
<p>Para acceder a las funciones del servicio debes crear una cuenta proporcionando información veraz, completa y actualizada. Eres el único responsable de mantener la confidencialidad de tus credenciales y de toda la actividad que se realice bajo tu cuenta.</p>

<h3>3.2 Uso no autorizado</h3>
<p>Si detectas un acceso no autorizado a tu cuenta, notifícanos inmediatamente a <a href="mailto:{{ config('mail.from.address', '[correo de soporte]') }}">{{ config('mail.from.address', '[correo de soporte]') }}</a>. No asumiremos responsabilidad por pérdidas derivadas del uso no autorizado de tu cuenta, ya sea con o sin tu conocimiento.</p>

<h2>4. Licencia de Uso</h2>
<p>Sujeto al cumplimiento de estos Términos, te otorgamos una licencia limitada, no exclusiva, intransferible y revocable para acceder y utilizar el servicio únicamente para tu uso interno o para el uso interno autorizado dentro de tu organización. Nos reservamos el derecho de revocar esta licencia en cualquier momento.</p>

<h2>5. Política de Uso Aceptable</h2>
<h3>5.1 Prohibiciones generales</h3>
<p>Queda terminantemente prohibido:</p>
<ul>
    <li>Acceder, alterar o utilizar áreas no públicas del servicio o de sus sistemas informáticos.</li>
    <li>Sondear, escanear o explotar vulnerabilidades de cualquier sistema o red.</li>
    <li>Acceder al servicio mediante métodos automatizados (scraping, bots) sin autorización expresa.</li>
    <li>Interferir con la infraestructura del servicio o con el acceso de otros usuarios (envío de virus, ataques de denegación de servicio, spam, etc.).</li>
    <li>Utilizar modelos de inteligencia artificial para eludir los filtros de seguridad del servicio.</li>
</ul>

<h3>5.2 Uso indebido</h3>
<p>El servicio no puede emplearse para:</p>
<ul>
    <li>Actividades ilícitas, fraudulentas o engañosas.</li>
    <li>Suplantación de identidad o phishing.</li>
    <li>Discurso de odio, acoso, difamación o amenazas.</li>
    <li>Distribución de contenido malicioso (virus, malware, ransomware).</li>
    <li>Vulnerar la privacidad de terceros o compartir datos personales sin autorización.</li>
    <li>Daño o explotación de menores.</li>
    <li>Desarrollar servicios que compitan directamente con <strong>{{ config('app.name') }}</strong> usando acceso privilegiado a la plataforma.</li>
</ul>

<h3>5.3 Contenido del usuario</h3>
<p>Eres el único responsable del contenido que publiques o almacenes en el servicio. No está permitido publicar contenido que sea ilegal, obsceno, difamatorio, discriminatorio o que infrinja derechos de propiedad intelectual de terceros.</p>

<h3>5.4 Inteligencia Artificial</h3>
<p>Si utilizas las funciones de IA del servicio, te comprometes a: (i) supervisar los resultados generados y verificar su exactitud; (ii) asumir la responsabilidad de las decisiones tomadas basándose en dichos resultados; y (iii) no utilizar la IA para generar contenido engañoso o para eludir restricciones del sistema.</p>

<h2>6. Propiedad Intelectual</h2>
<p>El software, diseño, logotipos, interfaces, documentación y demás contenidos del servicio son propiedad exclusiva de <strong class="field">[Nombre de la empresa]</strong> y están protegidos por las leyes de propiedad intelectual aplicables. Queda prohibida su reproducción, distribución o modificación sin autorización previa por escrito.</p>

<p>El contenido que tú o tu organización generéis en el servicio (tareas, proyectos, archivos, mensajes) es de vuestra propiedad. Nos otorgas una licencia limitada para procesarlo únicamente con el fin de prestarte el servicio.</p>

<h2>7. Garantías y Limitación de Responsabilidad</h2>
<p>El servicio se proporciona <strong>"tal cual"</strong> y <strong>"según disponibilidad"</strong>, sin garantías de ningún tipo, ya sean expresas o implícitas, incluyendo garantías de comerciabilidad, idoneidad para un fin particular o ausencia de infracciones.</p>

<p>En ningún caso <strong class="field">[Nombre de la empresa]</strong>, sus directivos, empleados o colaboradores serán responsables por daños indirectos, incidentales, especiales, consecuentes o punitivos, incluyendo pérdida de datos, pérdida de beneficios o interrupción del negocio, aunque se haya advertido de la posibilidad de tales daños.</p>

<p>La responsabilidad total máxima de <strong>{{ config('app.name') }}</strong> hacia ti, por cualquier causa, no superará el importe abonado por ti en los <strong class="field">[X]</strong> meses anteriores a la reclamación, o <strong class="field">[importe mínimo legal aplicable]</strong> en caso de no haber pago.</p>

<h2>8. Indemnización</h2>
<p>Aceptas indemnizar y eximir de responsabilidad a <strong class="field">[Nombre de la empresa]</strong> y a sus representantes frente a cualquier reclamación, daño o gasto, incluidos honorarios legales razonables, que se deriven de: (i) tu uso del servicio; (ii) tu incumplimiento de estos Términos; (iii) la violación de derechos de terceros.</p>

<h2>9. Servicios de Terceros</h2>
<p>El servicio puede incluir integraciones con herramientas de terceros (Google Workspace, Telegram, WhatsApp, Jitsi, servicios de almacenamiento en la nube, entre otros). No somos responsables del funcionamiento, disponibilidad ni las prácticas de privacidad de dichos servicios. Su uso se rige por los propios términos y políticas de los respectivos proveedores.</p>

<h2>10. Modificaciones del Servicio</h2>
<p>Nos reservamos el derecho de modificar, suspender o interrumpir, total o parcialmente, el servicio en cualquier momento, con o sin previo aviso. No seremos responsables ante ti ni ante terceros por tales modificaciones, suspensiones o interrupciones.</p>

<h2>11. Legislación Aplicable y Resolución de Disputas</h2>
<p>Estos Términos se regirán por las leyes de <strong class="field">[País / Comunidad Autónoma]</strong>, sin perjuicio de las normas de conflicto de leyes. Para los usuarios ubicados en la Unión Europea, se aplicarán las leyes del país de residencia del consumidor en la medida en que la ley aplicable así lo exija.</p>

<p>Cualquier disputa derivada de estos Términos deberá intentar resolverse primero de forma amistosa, contactando con nosotros en <strong class="field">[correo de contacto legal]</strong>. Si no se alcanza un acuerdo en un plazo razonable, las partes se someterán a los tribunales competentes de <strong class="field">[ciudad / jurisdicción]</strong>.</p>

<h2>12. Disposiciones Generales</h2>
<ul>
    <li><strong>Acuerdo completo:</strong> Estos Términos constituyen el acuerdo íntegro entre tú y <strong>{{ config('app.name') }}</strong> sobre el uso del servicio.</li>
    <li><strong>Divisibilidad:</strong> Si alguna disposición se declara inválida, el resto del acuerdo permanecerá en vigor.</li>
    <li><strong>Sin renuncia:</strong> La no exigencia de algún derecho en un momento dado no implica renuncia al mismo.</li>
    <li><strong>Cesión:</strong> No puedes ceder tus derechos bajo estos Términos sin nuestro consentimiento previo. Nosotros podemos ceder los nuestros libremente.</li>
    <li><strong>Avisos:</strong> Los avisos legales se enviarán al correo electrónico asociado a tu cuenta, o se publicarán en el servicio.</li>
</ul>

<h2>13. Contacto</h2>
<p>Para cualquier consulta sobre estos Términos, puedes contactarnos en:<br>
<strong class="field">[Nombre de la empresa / Responsable]</strong><br>
<strong class="field">[Dirección postal completa]</strong><br>
<a href="mailto:[correo de contacto legal]" class="field">[correo de contacto legal]</a></p>

@endif
</x-legal-layout>
