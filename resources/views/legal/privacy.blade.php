<x-legal-layout title="Política de Privacidad">
@if($content)
    {!! $content !!}
@else

<h1>Política de Privacidad</h1>
<p class="meta">Última actualización: <strong>{{ date('d/m/Y') }}</strong></p>

<p>En <strong>{{ config('app.name', 'Sientia') }}</strong>, nos comprometemos a proteger tu privacidad. Esta Política de Privacidad explica qué información recogemos, cómo la utilizamos, con quién la compartimos y qué derechos tienes sobre ella.</p>

<p>Si tienes preguntas, escríbenos a <a href="mailto:{{ config('mail.from.address', '[correo de privacidad]') }}">{{ config('mail.from.address', '[correo de privacidad]') }}</a>.</p>

<h2>1. Responsable del Tratamiento</h2>
<p>El responsable del tratamiento de tus datos personales es:</p>
<ul>
    <li><strong>Nombre o razón social:</strong> <span class="field">[Nombre de la empresa / responsable]</span></li>
    <li><strong>NIF / CIF:</strong> <span class="field">[NIF o CIF]</span></li>
    <li><strong>Dirección:</strong> <span class="field">[Dirección postal completa]</span></li>
    <li><strong>Correo electrónico:</strong> <span class="field">[correo de privacidad]</span></li>
    <li><strong>Delegado de Protección de Datos (DPO):</strong> <span class="field">[nombre del DPO o "No aplica"]</span></li>
</ul>

<h2>2. Información que Recopilamos</h2>

<h3>2.1 Información que tú nos proporcionas</h3>
<ul>
    <li>Datos de registro: nombre, apellidos, dirección de correo electrónico y contraseña (almacenada de forma cifrada).</li>
    <li>Información de perfil: foto, cargo, zona horaria y preferencias de notificación.</li>
    <li>Contenido que creas en el servicio: tareas, proyectos, mensajes de chat, publicaciones en foros, archivos adjuntos y expedientes.</li>
    <li>Datos de autenticación de terceros: si vinculas tu cuenta de Google, almacenamos los tokens de acceso necesarios para habilitar las integraciones (Google Drive, Google Calendar, Google Meet).</li>
    <li>Credenciales de integraciones opcionales: identificadores de Telegram o WhatsApp si activas dichas integraciones.</li>
</ul>

<h3>2.2 Información recopilada automáticamente</h3>
<ul>
    <li>Datos de uso: acciones realizadas en el servicio, páginas visitadas, funciones utilizadas y duración de las sesiones.</li>
    <li>Datos técnicos: dirección IP, tipo y versión del navegador, sistema operativo, idioma preferido y páginas de referencia.</li>
    <li>Cookies y tecnologías de seguimiento similares: consulta nuestra <a href="{{ route('cookies') }}">Política de Cookies</a> para más información.</li>
    <li>Información del dispositivo móvil: si utilizas la app móvil, podemos recopilar el identificador del dispositivo, la versión del sistema operativo y datos de red.</li>
</ul>

<h3>2.3 Información procedente de terceros</h3>
<ul>
    <li>Datos de integraciones de terceros configuradas por ti (Google Workspace, Telegram, WhatsApp), en la medida necesaria para prestar el servicio.</li>
    <li>Información facilitada por otros usuarios del mismo equipo o espacio de trabajo.</li>
</ul>

<h2>3. Finalidades y Bases Jurídicas del Tratamiento</h2>

<table>
    <thead>
        <tr>
            <th>Finalidad</th>
            <th>Base jurídica (RGPD)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Prestación del servicio y gestión de la cuenta</td>
            <td>Ejecución del contrato (art. 6.1.b)</td>
        </tr>
        <tr>
            <td>Comunicaciones transaccionales (alertas, cambios de servicio)</td>
            <td>Ejecución del contrato (art. 6.1.b)</td>
        </tr>
        <tr>
            <td>Seguridad, prevención del fraude y resolución de incidencias</td>
            <td>Interés legítimo (art. 6.1.f)</td>
        </tr>
        <tr>
            <td>Mejora del servicio y análisis estadístico anonimizado</td>
            <td>Interés legítimo (art. 6.1.f)</td>
        </tr>
        <tr>
            <td>Cumplimiento de obligaciones legales</td>
            <td>Obligación legal (art. 6.1.c)</td>
        </tr>
        <tr>
            <td>Comunicaciones de marketing (si aplica)</td>
            <td>Consentimiento (art. 6.1.a)</td>
        </tr>
    </tbody>
</table>

<h2>4. Cómo Compartimos tu Información</h2>
<p>No vendemos tus datos personales a terceros. Podemos compartirlos únicamente en los siguientes supuestos:</p>
<ul>
    <li><strong>Proveedores de servicios:</strong> empresas que nos prestan servicios en nuestro nombre (alojamiento, análisis, seguridad, procesamiento de pagos). Todos ellos están vinculados por acuerdos de tratamiento de datos.</li>
    <li><strong>Integraciones solicitadas por ti:</strong> cuando activas integraciones de terceros (Google, Telegram, WhatsApp), compartimos los datos mínimos necesarios con dichos proveedores.</li>
    <li><strong>Dentro de tu organización:</strong> si usas el servicio a través de una empresa, el administrador de tu cuenta puede acceder a ciertos datos asociados al dominio o espacio de trabajo.</li>
    <li><strong>Transmisión empresarial:</strong> en caso de fusión, adquisición o venta de activos, tus datos podrían transferirse al nuevo titular, siempre sujetos a esta Política.</li>
    <li><strong>Obligaciones legales:</strong> podemos divulgar información cuando lo exija la ley, una orden judicial u otra autoridad competente.</li>
</ul>

<h2>5. Transferencias Internacionales de Datos</h2>
<p>Si tus datos personales se transfieren fuera del Espacio Económico Europeo (EEE), nos aseguraremos de que dicha transferencia se realice con las garantías adecuadas, como Cláusulas Contractuales Tipo aprobadas por la Comisión Europea u otros mecanismos reconocidos.</p>
<p>Los principales subencargados de tratamiento que pueden implicar transferencias fuera del EEE son: <span class="field">[lista de subencargados relevantes]</span>.</p>

<h2>6. Seguridad</h2>
<p>Aplicamos medidas técnicas y organizativas apropiadas para proteger tus datos contra el acceso no autorizado, la pérdida accidental, la destrucción o la divulgación. Entre ellas: cifrado en tránsito (TLS), contraseñas cifradas con hash seguro, acceso restringido al personal autorizado y auditorías periódicas de seguridad.</p>
<p>Sin embargo, ningún método de transmisión por Internet es completamente seguro. En caso de brecha de seguridad que afecte a tus derechos y libertades, te notificaremos conforme a lo exigido por la normativa aplicable.</p>

<h2>7. Conservación de los Datos</h2>
<p>Conservamos tus datos durante el tiempo necesario para cumplir las finalidades descritas en esta Política y, en todo caso, durante los plazos mínimos exigidos por la legislación vigente. Cuando tu cuenta sea eliminada, procederemos a borrar o anonimizar tus datos en un plazo máximo de <strong class="field">[X días/meses]</strong>, salvo que la ley exija su conservación por un período mayor.</p>

<h2>8. Menores de Edad</h2>
<p>El servicio no está dirigido a menores de 16 años. No recopilamos conscientemente datos personales de menores. Si eres padre, madre o tutor legal y crees que tu hijo/a nos ha proporcionado datos sin tu consentimiento, contáctanos para proceder a su eliminación.</p>

<h2>9. Tus Derechos</h2>
<p>De conformidad con el RGPD y la normativa de protección de datos aplicable, tienes derecho a:</p>
<ul>
    <li><strong>Acceso:</strong> obtener confirmación sobre si tratamos tus datos y recibir una copia de los mismos.</li>
    <li><strong>Rectificación:</strong> solicitar la corrección de datos inexactos o incompletos.</li>
    <li><strong>Supresión:</strong> solicitar la eliminación de tus datos cuando ya no sean necesarios o revoques tu consentimiento.</li>
    <li><strong>Limitación:</strong> solicitar la restricción del tratamiento en determinadas circunstancias.</li>
    <li><strong>Portabilidad:</strong> recibir tus datos en un formato estructurado y de uso común.</li>
    <li><strong>Oposición:</strong> oponerte al tratamiento basado en interés legítimo.</li>
    <li><strong>Retirada del consentimiento:</strong> en cualquier momento, sin que ello afecte a la licitud del tratamiento previo.</li>
</ul>
<p>Para ejercer tus derechos, escríbenos a <a href="mailto:{{ config('mail.from.address', '[correo de privacidad]') }}">{{ config('mail.from.address', '[correo de privacidad]') }}</a>. Responderemos en el plazo máximo de <strong>30 días</strong>. Si consideras que tus derechos han sido vulnerados, puedes reclamar ante la autoridad de control competente (<strong class="field">[p. ej., Agencia Española de Protección de Datos — www.aepd.es]</strong>).</p>

<h2>10. Cambios en esta Política</h2>
<p>Actualizaremos esta Política cuando sea necesario para reflejar cambios en nuestras prácticas, en el servicio o en la legislación aplicable. Si los cambios son relevantes, te notificaremos por correo electrónico o mediante un aviso destacado en el servicio antes de que entren en vigor.</p>

<h2>11. Contacto</h2>
<p>
    <strong class="field">[Nombre de la empresa / responsable]</strong><br>
    <strong class="field">[Dirección postal completa]</strong><br>
    <a href="mailto:[correo de privacidad]" class="field">[correo de privacidad]</a>
</p>

@endif
</x-legal-layout>
