<?php

// SPDX-License-Identifier: AGPL-3.0-or-later
// Copyright (c) 2022-2026 pbenav <info@sientia.com>


namespace App\Http\Controllers;

use App\Models\Setting;

class LegalController extends Controller
{
    /**
     * Display the privacy policy.
     */
    public function privacy()
    {
        return view('legal.privacy', [
            'content' => Setting::get('legal_privacy')
        ]);
    }

    /**
     * Display the terms of service.
     */
    public function terms()
    {
        return view('legal.terms', [
            'content' => Setting::get('legal_terms')
        ]);
    }

    /**
     * Display the cookie policy.
     */
    public function cookies()
    {
        return view('legal.cookies', [
            'content' => Setting::get('legal_cookies')
        ]);
    }

    /**
     * Show the mandatory re-consent form.
     */
    /**
     * Show the mandatory re-consent form.
     */
    public function reconsent()
    {
        $privacy = Setting::get('legal_privacy') ?: $this->getDefaultLegalContent('privacy');
        $terms   = Setting::get('legal_terms')   ?: $this->getDefaultLegalContent('terms');
        $cookies = Setting::get('legal_cookies') ?: $this->getDefaultLegalContent('cookies');

        return view('legal.reconsent', [
            'privacy' => $privacy,
            'terms'   => $terms,
            'cookies' => $cookies,
        ]);
    }

    /**
     * Return the default HTML content for a legal document type.
     * Used by the settings editor "Load default" button.
     *
     * @param string $type  privacy | terms | cookies
     */
    public function defaultContent(string $type): \Illuminate\Http\JsonResponse
    {
        $content = $this->getDefaultLegalContent($type);

        if ($content === null) {
            return response()->json(['error' => 'Tipo no válido'], 422);
        }

        return response()->json(['html' => $content]);
    }

    /**
     * Get the professional default HTML content for a legal document type.
     *
     * @param string $type
     * @return string|null
     */
    private function getDefaultLegalContent(string $type): ?string
    {
        $app   = config('app.name', 'Sientia Open Source Lab');
        $email = config('mail.from.address', '[correo de contacto]');
        $field = fn(string $label) => '<span style="background:#fef9c3;border-radius:2px;padding:0 3px;font-style:italic;color:#92400e;font-size:0.85em;">[' . $label . ']</span>';

        return match ($type) {
            /* ─────────────────────────── TERMS ─────────────────────────── */
            'terms' => implode('', [
                "<h1>Términos de Servicio de {$app}</h1>",
                "<p><em>Última actualización: " . date('d/m/Y') . "</em></p>",
                "<p>Los presentes Términos de Servicio (en adelante, el «Acuerdo» o los «Términos») rigen el acceso y uso de la plataforma de gestión de proyectos, comunicación y productividad <strong>{$app}</strong> (en adelante, el «Servicio»), proporcionada por <strong>{$field('Nombre o razón social de la Entidad')}</strong> (en adelante, la «Empresa»).</p>",
                "<p>Al acceder, registrarse o utilizar el Servicio, usted (el «Usuario» o el «Cliente») acepta quedar vinculado por estos Términos en su totalidad. Si no acepta estos Términos, no podrá acceder ni utilizar el Servicio. Si utiliza el Servicio en representación de una entidad jurídica, declara y garantiza que tiene la autoridad necesaria para vincular a dicha entidad a este Acuerdo.</p>",

                "<h2>1. Definiciones</h2>",
                "<ul>",
                "<li><strong>Servicio:</strong> La plataforma de software como servicio (SaaS) {$app}, incluyendo sus interfaces web, aplicaciones móviles, API e integraciones.</li>",
                "<li><strong>Cliente:</strong> La entidad física o jurídica que contrata el Servicio.</li>",
                "<li><strong>Usuario Final:</strong> Cualquier individuo autorizado por el Cliente para acceder al Servicio.</li>",
                "<li><strong>Datos del Cliente:</strong> Todo contenido, información o material que el Cliente o sus Usuarios Finales carguen o procesen en el Servicio.</li>",
                "<li><strong>Propiedad Intelectual:</strong> Patentes, derechos de autor, marcas, secretos comerciales y cualquier otro derecho de propiedad intelectual o industrial.</li>",
                "</ul>",

                "<h2>2. Cuentas y Registro</h2>",
                "<h3>2.1 Registro de cuenta</h3>",
                "<p>Para utilizar las funcionalidades completas del Servicio, es necesario crear una cuenta. El Usuario se compromete a proporcionar información veraz, precisa y completa durante el proceso de registro y a mantener dicha información actualizada.</p>",
                "<h3>2.2 Responsabilidad de la cuenta</h3>",
                "<p>El Usuario es el único responsable de mantener la confidencialidad de sus credenciales de acceso y de todas las actividades que ocurran bajo su cuenta. La Empresa no será responsable de ninguna pérdida o daño derivado del incumplimiento de esta obligación de seguridad. El Usuario debe notificar inmediatamente a <strong>{$email}</strong> ante cualquier sospecha de uso no autorizado de su cuenta.</p>",

                "<h2>3. Licencia y Uso del Servicio</h2>",
                "<h3>3.1 Concesión de licencia</h3>",
                "<p>Sujeto al cumplimiento de este Acuerdo, la Empresa otorga al Cliente una licencia limitada, no exclusiva, intransferible y revocable para acceder y utilizar el Servicio durante la vigencia del Acuerdo, exclusivamente para fines comerciales internos.</p>",
                "<h3>3.2 Restricciones de uso</h3>",
                "<p>El Cliente se compromete a no:</p>",
                "<ul>",
                "<li>Ingeniería inversa, descompilar o intentar extraer el código fuente del Servicio.</li>",
                "<li>Utilizar el Servicio para desarrollar un producto o servicio competitivo.</li>",
                "<li>Acceder al Servicio mediante métodos automatizados (bots, scrapers) sin consentimiento expreso por escrito.</li>",
                "<li>Interferir con la integridad o el rendimiento del Servicio o los datos contenidos en él.</li>",
                "<li>Utilizar el Servicio para almacenar o transmitir material ilícito, difamatorio o que infrinja derechos de terceros.</li>",
                "</ul>",

                "<h2>4. Propiedad Intelectual y Datos</h2>",
                "<h3>4.1 Propiedad de la Empresa</h3>",
                "<p>La Empresa y sus licenciantes conservan todos los derechos, títulos e intereses, incluidos todos los derechos de Propiedad Intelectual, sobre el Servicio, el software subyacente, el diseño, las marcas y cualquier mejora o modificación de los mismos.</p>",
                "<h3>4.2 Propiedad de los Datos del Cliente</h3>",
                "<p>El Cliente conserva todos los derechos de propiedad sobre los Datos del Cliente. El Cliente otorga a la Empresa una licencia mundial, limitada y libre de regalías para alojar, copiar, transmitir y mostrar los Datos del Cliente únicamente en la medida necesaria para prestar, mantener y mejorar el Servicio.</p>",
                "<h3>4.3 Feedback</h3>",
                "<p>Cualquier sugerencia, idea o comentario (Feedback) proporcionado por el Cliente podrá ser utilizado por la Empresa de forma gratuita y sin restricciones.</p>",

                "<h2>5. Inteligencia Artificial (IA)</h2>",
                "<h3>5.1 Funcionalidades de IA</h3>",
                "<p>El Servicio incluye herramientas basadas en modelos de inteligencia artificial para la generación de contenido, análisis de datos y asistencia en la gestión de proyectos.</p>",
                "<h3>5.2 Responsabilidad de los resultados</h3>",
                "<p>El Usuario reconoce que los resultados generados por la IA pueden contener errores, imprecisiones o sesgos. Es responsabilidad exclusiva del Usuario revisar y validar cualquier resultado de la IA antes de basarse en él para decisiones críticas. La Empresa no garantiza la exactitud ni la idoneidad de los resultados generados por la IA para fines específicos.</p>",

                "<h2>6. Integraciones de Terceros</h2>",
                "<h3>6.1 Conectividad</h3>",
                "<p>El Servicio permite la integración con aplicaciones de terceros, incluyendo, de forma no limitativa, Google Workspace (Calendar, Drive, Meet), Telegram y WhatsApp.</p>",
                "<h3>6.2 Responsabilidad de terceros</h3>",
                "<p>La Empresa no se hace responsable del rendimiento, la disponibilidad o las prácticas de privacidad de dichas aplicaciones de terceros. El uso de las integraciones está sujeto a los términos y condiciones de los proveedores de dichos servicios. El Cliente asume toda la responsabilidad por los datos transmitidos a través de dichas integraciones.</p>",

                "<h2>7. Confidencialidad</h2>",
                "<p>Cada parte se compromete a proteger la información confidencial de la otra parte con el mismo grado de cuidado que utiliza para proteger su propia información confidencial, y a no utilizarla para fines ajenos al cumplimiento de este Acuerdo.</p>",

                "<h2>8. Indemnización</h2>",
                "<p>El Cliente defenderá, indemnizará y mantendrá indemne a la Empresa frente a cualquier reclamación, coste, daño o gasto derivado de: (i) el uso del Servicio por parte del Cliente o sus Usuarios Finales; (ii) el incumplimiento de este Acuerdo; o (iii) la infracción de derechos de terceros por parte de los Datos del Cliente.</p>",

                "<h2>9. Limitación de Responsabilidad</h2>",
                "<h3>9.1 Exclusión de daños</h3>",
                "<p>En la medida máxima permitida por la ley, en ningún caso la Empresa será responsable por daños indirectos, incidentales, punitivos o consecuentes, incluyendo la pérdida de beneficios, ingresos o datos.</p>",
                "<h3>9.2 Límite máximo</h3>",
                "<p>La responsabilidad total agregada de la Empresa bajo este Acuerdo no superará en ningún caso el importe total abonado por el Cliente a la Empresa durante los {$field('doce (12)')} meses inmediatamente anteriores al evento que dio lugar a la reclamación.</p>",

                "<h2>10. Vigencia y Rescisión</h2>",
                "<h3>10.1 Plazo</h3>",
                "<p>Este Acuerdo permanecerá vigente mientras el Cliente mantenga una suscripción activa o acceda al Servicio.</p>",
                "<h3>10.2 Rescisión</h3>",
                "<p>Cualquiera de las partes puede rescindir este Acuerdo en caso de incumplimiento material por la otra parte que no sea subsanado en un plazo de 30 días tras la notificación por escrito. Al rescindirse el Acuerdo, el acceso al Servicio cesará inmediatamente y el Cliente deberá destruir todas las copias de cualquier documentación confidencial de la Empresa.</p>",

                "<h2>11. Modificaciones de los Términos</h2>",
                "<p>La Empresa se reserva el derecho de modificar estos Términos en cualquier momento. Si realizamos cambios materiales, notificaremos al Cliente a través del Servicio o por correo electrónico. El uso continuado del Servicio tras la entrada en vigor de los cambios constituye la aceptación de los nuevos Términos.</p>",

                "<h2>12. Ley Aplicable y Jurisdicción</h2>",
                "<p>Este Acuerdo se rige por las leyes de {$field('España / Región correspondiente')}. Para cualquier disputa derivada de este Acuerdo, las partes se someten a la jurisdicción exclusiva de los tribunales de {$field('Ciudad de la Jurisdicción')}, renunciando expresamente a cualquier otro fuero que pudiera corresponderles.</p>",

                "<h2>13. Disposiciones Generales</h2>",
                "<p><strong>13.1 Independencia de las cláusulas:</strong> Si alguna disposición de este Acuerdo se considera inválida o inaplicable, el resto de las disposiciones permanecerán en pleno vigor.</p>",
                "<p><strong>13.2 Divisibilidad:</strong> La falta de ejercicio de cualquier derecho por parte de la Empresa no constituirá una renuncia a dicho derecho.</p>",
                "<p><strong>13.3 Acuerdo íntegro:</strong> Este Acuerdo constituye el entendimiento completo entre las partes con respecto al uso del Servicio y sustituye cualquier comunicación previa.</p>",

                "<h2>14. Contacto</h2>",
                "<p>Para cualquier consulta legal relacionada con estos Términos, póngase en contacto con nosotros en:<br>",
                "<strong>{$field('Nombre de la Empresa')}</strong><br>",
                "<strong>{$field('Dirección física')}</strong><br>",
                "<a href=\"mailto:{$email}\">{$email}</a></p>",
            ]),

            /* ─────────────────────────── PRIVACY ─────────────────────────── */
            'privacy' => implode('', [
                "<h1>Política de Privacidad de {$app}</h1>",
                "<p><em>Última actualización: " . date('d/m/Y') . "</em></p>",
                "<p>En <strong>{$app}</strong> (operado por <strong>{$field('Nombre o razón social')}</strong>), valoramos su privacidad y nos comprometemos a proteger sus datos personales de acuerdo con el Reglamento General de Protección de Datos (RGPD) y la normativa local vigente.</p>",

                "<h2>1. Responsable del Tratamiento</h2>",
                "<p>El responsable del tratamiento de sus datos personales es:</p>",
                "<ul>",
                "<li><strong>Identidad:</strong> {$field('Nombre o razón social')}</li>",
                "<li><strong>NIF/CIF:</strong> {$field('NIF/CIF')}</li>",
                "<li><strong>Dirección:</strong> {$field('Dirección postal completa')}</li>",
                "<li><strong>Correo electrónico:</strong> <a href=\"mailto:{$email}\">{$email}</a></li>",
                "<li><strong>DPO (si aplica):</strong> {$field('Datos de contacto del Delegado de Protección de Datos')}</li>",
                "</ul>",

                "<h2>2. Datos que Recopilamos</h2>",
                "<h3>2.1 Datos proporcionados por usted</h3>",
                "<ul>",
                "<li><strong>Datos de cuenta:</strong> Nombre, apellidos, dirección de correo electrónico y contraseña (cifrada).</li>",
                "<li><strong>Datos de perfil:</strong> Fotografía, cargo profesional y zona horaria.</li>",
                "<li><strong>Contenido del usuario:</strong> Tareas, proyectos, mensajes, archivos y cualquier otra información introducida en la plataforma.</li>",
                "</ul>",
                "<h3>2.2 Datos recopilados automáticamente</h3>",
                "<ul>",
                "<li><strong>Información del dispositivo:</strong> Dirección IP, tipo de navegador, sistema operativo e identificadores únicos de dispositivo.</li>",
                "<li><strong>Datos de uso:</strong> Páginas visitadas, tiempo de permanencia y acciones realizadas en el Servicio.</li>",
                "</ul>",
                "<h3>2.3 Datos de integraciones de terceros</h3>",
                "<p>Si vincula su cuenta con servicios como Google Workspace, recopilamos los tokens de acceso y la información necesaria (por ejemplo, eventos de calendario o archivos de Drive) para habilitar dichas funciones.</p>",

                "<h2>3. Finalidades del Tratamiento</h2>",
                "<p>Tratamos sus datos para:</p>",
                "<ul>",
                "<li>Proporcionar, mantener y mejorar el Servicio.</li>",
                "<li>Gestionar su cuenta y las comunicaciones de soporte.</li>",
                "<li>Enviar notificaciones transaccionales y actualizaciones del Servicio.</li>",
                "<li>Garantizar la seguridad del Servicio y prevenir actividades fraudulentas.</li>",
                "<li>Analizar el uso del Servicio para optimizar su rendimiento y usabilidad.</li>",
                "</ul>",

                "<h2>4. Base Jurídica del Tratamiento</h2>",
                "<p>Tratamos sus datos bajo las siguientes bases legales:</p>",
                "<ul>",
                "<li><strong>Ejecución de un contrato:</strong> Para prestarle el Servicio que ha solicitado.</li>",
                "<li><strong>Interés legítimo:</strong> Para mejorar nuestro producto, garantizar la seguridad y realizar marketing directo limitado (cuando sea permitido).</li>",
                "<li><strong>Consentimiento:</strong> Para el uso de cookies no esenciales o comunicaciones comerciales si así lo ha aceptado.</li>",
                "<li><strong>Obligación legal:</strong> Cuando sea necesario para cumplir con leyes aplicables.</li>",
                "</ul>",

                "<h2>5. Conservación de los Datos</h2>",
                "<p>Conservaremos sus datos personales mientras se mantenga la relación contractual y, posteriormente, durante los plazos de prescripción legales aplicables para atender posibles responsabilidades.</p>",

                "<h2>6. Destinatarios de los Datos</h2>",
                "<p>No vendemos sus datos personales. Podemos compartir datos con:</p>",
                "<ul>",
                "<li>Proveedores de servicios (hosting, análisis, soporte) que actúan como encargados del tratamiento bajo contrato.</li>",
                "<li>Terceros integrados por el Usuario (Google, Telegram, etc.) bajo su dirección.</li>",
                "<li>Autoridades legales cuando exista una obligación imperativa.</li>",
                "<li>Transferencias empresariales en caso de fusión o adquisición.</li>",
                "</ul>",

                "<h2>7. Transferencias Internacionales</h2>",
                "<p>Utilizamos proveedores de servicios globales. Si transferimos datos fuera del Espacio Económico Europeo (EEE), lo hacemos bajo mecanismos legales adecuados como las Cláusulas Contractuales Tipo de la Comisión Europea.</p>",

                "<h2>8. Sus Derechos</h2>",
                "<p>Usted tiene derecho a:</p>",
                "<ul>",
                "<li><strong>Acceso:</strong> Saber qué datos tratamos sobre usted.</li>",
                "<li><strong>Rectificación:</strong> Corregir datos inexactos.</li>",
                "<li><strong>Supresión («derecho al olvido»):</strong> Solicitar la eliminación de sus datos.</li>",
                "<li><strong>Oposición:</strong> Oponerse al tratamiento por interés legítimo.</li>",
                "<li><strong>Limitación:</strong> Solicitar que suspendamos el tratamiento temporalmente.</li>",
                "<li><strong>Portabilidad:</strong> Recibir sus datos en un formato estructurado.</li>",
                "</ul>",
                "<p>Para ejercer estos derechos, envíe un correo a <a href=\"mailto:{$email}\">{$email}</a> adjuntando copia de su documento de identidad. También tiene derecho a presentar una reclamación ante la Agencia Española de Protección de Datos (www.aepd.es).</p>",

                "<h2>9. Seguridad de la Información</h2>",
                "<p>Implementamos medidas técnicas y organizativas de última generación, incluyendo cifrado SSL/TLS, firewalls y controles de acceso restringido, para proteger sus datos contra el acceso no autorizado o la pérdida accidental.</p>",

                "<h2>10. Cambios en la Política</h2>",
                "<p>Podemos actualizar esta política periódicamente. Le notificaremos cualquier cambio sustancial a través de nuestra plataforma o por correo electrónico.</p>",
            ]),

            /* ─────────────────────────── COOKIES ─────────────────────────── */
            'cookies' => implode('', [
                "<h1>Política de Cookies de {$app}</h1>",
                "<p><em>Última actualización: " . date('d/m/Y') . "</em></p>",
                "<p>Esta Política de Cookies explica cómo <strong>{$app}</strong> utiliza cookies y tecnologías similares para reconocerle cuando visita nuestra plataforma. Explica qué son estas tecnologías y por qué las utilizamos, así como sus derechos para controlar el uso que hacemos de ellas.</p>",

                "<h2>1. ¿Qué son las cookies?</h2>",
                "<p>Las cookies son pequeños archivos de datos que se guardan en su ordenador o dispositivo móvil cuando visita un sitio web. Los propietarios de sitios web utilizan ampliamente las cookies para que sus sitios funcionen, o para que funcionen de forma más eficiente, así como para proporcionar información de informes.</p>",

                "<h2>2. ¿Por qué utilizamos cookies?</h2>",
                "<p>Utilizamos cookies propias y de terceros por varias razones. Algunas cookies son necesarias por motivos técnicos para que nuestro Servicio funcione, y las denominamos cookies «esenciales» o «estrictamente necesarias». Otras cookies nos permiten rastrear y dirigir los intereses de nuestros usuarios para mejorar la experiencia en nuestro Servicio.</p>",

                "<h2>3. Tipos de cookies que utilizamos</h2>",
                "<h3>3.1 Cookies esenciales</h3>",
                "<p>Estas cookies son estrictamente necesarias para proporcionarle los servicios disponibles a través de nuestra plataforma y para utilizar algunas de sus funciones, como el acceso a áreas seguras.</p>",
                "<h3>3.2 Cookies de personalización</h3>",
                "<p>Estas cookies se utilizan para mejorar el rendimiento y la funcionalidad de nuestro Servicio, pero no son esenciales para su uso. Sin embargo, sin estas cookies, algunas funciones pueden dejar de estar disponibles.</p>",
                "<h3>3.3 Cookies de análisis y personalización</h3>",
                "<p>Estas cookies recopilan información que se utiliza de forma agregada para ayudarnos a comprender cómo se utiliza nuestro Servicio o la eficacia de nuestras campañas de marketing.</p>",

                "<h2>4. Cookies específicas utilizadas</h2>",
                "<p>A continuación se detallan las principales cookies utilizadas en el Servicio:</p>",
                "<table border=\"1\" style=\"width:100%; border-collapse: collapse; text-align: left;\">",
                "<thead><tr style=\"background-color: #f2f2f2;\"><th>Nombre</th><th>Propósito</th><th>Duración</th></tr></thead>",
                "<tbody>",
                "<tr><td><strong>" . config('session.cookie', 'sientia_session') . "</strong></td><td>Identifica la sesión del usuario para mantener la autenticación.</td><td>" . config('session.lifetime', 120) . " min</td></tr>",
                "<tr><td><strong>XSRF-TOKEN</strong></td><td>Protección contra ataques de falsificación de solicitudes entre sitios (CSRF).</td><td>Sesión</td></tr>",
                "<tr><td><strong>theme / locale</strong></td><td>Almacena las preferencias de idioma y tema visual del usuario.</td><td>1 año</td></tr>",
                "<tr><td><strong>{$field('Otras cookies')}</strong></td><td>{$field('Descripción')}</td><td>{$field('Duración')}</td></tr>",
                "</tbody></table>",

                "<h2>5. Control de las cookies</h2>",
                "<p>Usted tiene el derecho de decidir si acepta o rechaza las cookies. Puede ejercer sus preferencias de cookies configurando los controles de su navegador web para aceptar o rechazar las cookies. Si decide rechazar las cookies, podrá seguir utilizando nuestro sitio web, aunque su acceso a algunas funciones y áreas de nuestro sitio web puede estar restringido.</p>",

                "<h2>6. Gestión a través del navegador</h2>",
                "<p>Los medios por los que puede rechazar las cookies a través de los controles de su navegador web varían de un navegador a otro. Debe visitar el menú de ayuda de su navegador para obtener más información:</p>",
                "<ul>",
                "<li><a href=\"https://support.google.com/chrome/answer/95647\">Google Chrome</a></li>",
                "<li><a href=\"https://support.mozilla.org/es/kb/habilitar-y-deshabilitar-cookies-sitios-web-rastrear-preferencias\">Mozilla Firefox</a></li>",
                "<li><a href=\"https://support.apple.com/es-es/guide/safari/sfri11471/mac\">Apple Safari</a></li>",
                "<li><a href=\"https://support.microsoft.com/es-es/windows/eliminar-y-administrar-cookies-168dab11-0753-043d-7c16-ede5947fc64d\">Microsoft Edge</a></li>",
                "</ul>",

                "<h2>7. Cambios en la Política de Cookies</h2>",
                "<p>Podemos actualizar esta Política de Cookies de vez en cuando para reflejar, por ejemplo, cambios en las cookies que utilizamos o por otras razones operativas, legales o reglamentarias. Por lo tanto, le rogamos que vuelva a consultar esta Política de Cookies periódicamente para mantenerse informado sobre el uso que hacemos de las cookies y tecnologías relacionadas.</p>",

                "<h2>8. Más información</h2>",
                "<p>Si tiene alguna pregunta sobre el uso que hacemos de las cookies u otras tecnologías, envíenos un correo electrónico a <a href=\"mailto:{$email}\">{$email}</a>.</p>",
            ]),

            default => null,
        };
    }

    /**
     * Handle the legal re-consent submission.nerse informado sobre el uso que hacemos de las cookies y tecnologías relacionadas.</p>",

                "<h2>8. Más información</h2>",
                "<p>Si tiene alguna pregunta sobre el uso que hacemos de las cookies u otras tecnologías, envíenos un correo electrónico a <a href=\"mailto:{$email}\">{$email}</a>.</p>",
            ]),

            default => null,
        };

        if ($content === null) {
            return response()->json(['error' => 'Tipo no válido'], 422);
        }

        return response()->json(['html' => $content]);
    }

    /**
     * Handle the legal re-consent submission.
     */
    public function acceptConsent(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'accept' => 'required|accepted',
        ], [
            'accept.accepted' => __('Debes aceptar los términos legales para continuar.'),
        ]);

        $user = auth()->user();
        $user->update([
            'privacy_policy_accepted_at' => now(),
            'terms_accepted_at' => now(),
            'marketing_accepted_at' => $request->has('marketing') ? now() : null,
        ]);

        return redirect()->route('dashboard')->with('success', __('¡Gracias por aceptar los nuevos términos!'));
    }
}
