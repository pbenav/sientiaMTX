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
    public function reconsent()
    {
        $privacy = Setting::get('legal_privacy');
        $terms = Setting::get('legal_terms');
        $cookies = Setting::get('legal_cookies');

        // Fallbacks if empty
        if (empty($privacy)) {
            $privacy = '<h1>Política de Privacidad</h1><p>En <strong>' . config('app.name', 'Sientia') . '</strong>, nos tomamos muy en serio la privacidad de tus datos...</p><h2>1. Identificación del Responsable</h2><p>El responsable es <strong>[Nombre del Responsable / Empresa]</strong>.</p>';
        }

        if (empty($terms)) {
            $terms = '<h1>Términos de Servicio</h1><p>Bienvenido. Al usar <strong>' . config('app.name', 'Sientia') . '</strong> aceptas estos términos.</p>';
        }

        if (empty($cookies)) {
            $cookies = '<h1>Política de Cookies</h1><p>Usamos cookies para mejorar tu experiencia.</p>';
        }

        return view('legal.reconsent', [
            'privacy' => $privacy,
            'terms' => $terms,
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
        $app   = config('app.name', 'Sientia');
        $email = config('mail.from.address', '[correo de contacto]');
        $field = fn(string $label) => '<span style="background:#fef9c3;border-radius:2px;padding:0 3px;font-style:italic;color:#92400e;font-size:0.85em;">[' . $label . ']</span>';

        $content = match ($type) {

            /* ─────────────────────────── TERMS ─────────────────────────── */
            'terms' => implode('', [
                "<h1>Términos de Servicio</h1>",
                "<p><em>Última actualización: " . date('d/m/Y') . "</em></p>",
                "<p>Bienvenido a <strong>{$app}</strong>. Al registrarte y utilizar nuestra plataforma aceptas en su totalidad los presentes Términos de Servicio.</p>",
                "<h2>1. Introducción</h2>",
                "<p><strong>{$field('Nombre o razón social')}</strong> ofrece una plataforma de productividad y gestión de trabajo en equipo que incluye gestión de tareas, Matriz de Eisenhower, Diagramas de Gantt, tableros Kanban, foros, chat, videoconferencia e integraciones con servicios de terceros.</p>",
                "<p>Podemos revisar estos Términos periódicamente. Si los cambios son sustanciales, te notificaremos por correo electrónico o mediante un aviso en el servicio.</p>",
                "<h2>2. Elegibilidad</h2>",
                "<p>Para usar el servicio debes tener al menos <strong>16 años</strong> de edad y no haber sido previamente suspendido de la plataforma.</p>",
                "<h2>3. Registro y Cuenta</h2>",
                "<p>Debes proporcionar información veraz y actualizada. Eres responsable de la confidencialidad de tus credenciales y de toda la actividad realizada bajo tu cuenta. Ante cualquier acceso no autorizado, notifícanos inmediatamente a <a href=\"mailto:{$email}\">{$email}</a>.</p>",
                "<h2>4. Licencia de Uso</h2>",
                "<p>Te otorgamos una licencia limitada, no exclusiva, intransferible y revocable para acceder y usar el servicio exclusivamente para tu uso interno o el de tu organización.</p>",
                "<h2>5. Uso Aceptable</h2>",
                "<p>Queda prohibido: acceder a áreas no autorizadas del sistema, realizar scraping o ataques automatizados, distribuir contenido malicioso, suplantar identidades, vulnerar la privacidad de terceros o usar la plataforma para actividades ilícitas.</p>",
                "<h2>6. Propiedad Intelectual</h2>",
                "<p>El software, diseño, logotipos e interfaces de <strong>{$app}</strong> son propiedad de <strong>{$field('Nombre de la empresa')}</strong> y están protegidos por la legislación de propiedad intelectual. El contenido que creas en el servicio es tuyo; nos otorgas una licencia limitada para procesarlo exclusivamente con el fin de prestarte el servicio.</p>",
                "<h2>7. Limitación de Responsabilidad</h2>",
                "<p>El servicio se proporciona «tal cual». No garantizamos disponibilidad ininterrumpida ni ausencia de errores. En ningún caso nuestra responsabilidad total superará el importe abonado en los <strong>{$field('X')}</strong> meses anteriores a la reclamación.</p>",
                "<h2>8. Legislación y Disputas</h2>",
                "<p>Estos Términos se rigen por las leyes de <strong>{$field('País/Comunidad Autónoma')}</strong>. Cualquier disputa se intentará resolver amistosamente antes de acudir a los tribunales competentes de <strong>{$field('ciudad/jurisdicción')}</strong>.</p>",
                "<h2>9. Contacto</h2>",
                "<p>{$field('Nombre de la empresa')}<br>{$field('Dirección postal')}<br><a href=\"mailto:{$email}\">{$email}</a></p>",
            ]),

            /* ─────────────────────────── PRIVACY ─────────────────────────── */
            'privacy' => implode('', [
                "<h1>Política de Privacidad</h1>",
                "<p><em>Última actualización: " . date('d/m/Y') . "</em></p>",
                "<p>En <strong>{$app}</strong> nos comprometemos a proteger tu privacidad. Esta política explica qué datos recopilamos, cómo los usamos y qué derechos tienes.</p>",
                "<h2>1. Responsable del Tratamiento</h2>",
                "<ul><li><strong>Nombre:</strong> {$field('Nombre o razón social')}</li>",
                "<li><strong>NIF/CIF:</strong> {$field('NIF o CIF')}</li>",
                "<li><strong>Dirección:</strong> {$field('Dirección postal completa')}</li>",
                "<li><strong>Contacto:</strong> <a href=\"mailto:{$email}\">{$email}</a></li>",
                "<li><strong>DPO:</strong> {$field('Nombre del DPO o «No aplica»')}</li></ul>",
                "<h2>2. Datos que Recopilamos</h2>",
                "<p><strong>Datos que tú nos proporcionas:</strong> nombre, correo electrónico, contraseña (cifrada), foto de perfil, contenido de tareas, proyectos y mensajes, y tokens de integraciones de terceros (Google, Telegram, WhatsApp) si las activas.</p>",
                "<p><strong>Datos recopilados automáticamente:</strong> dirección IP, tipo de navegador, sistema operativo, páginas visitadas y duración de la sesión.</p>",
                "<h2>3. Bases Jurídicas (RGPD)</h2>",
                "<p>Tratamos tus datos basándonos en: (i) ejecución del contrato de servicio, (ii) interés legítimo para seguridad y mejora del servicio, (iii) obligación legal cuando aplique, y (iv) tu consentimiento para comunicaciones de marketing.</p>",
                "<h2>4. Compartición de Datos</h2>",
                "<p>No vendemos tus datos. Podemos compartirlos con: proveedores de servicios técnicos bajo acuerdos de tratamiento, servicios de terceros que hayas activado voluntariamente, y autoridades competentes cuando lo exija la ley.</p>",
                "<h2>5. Transferencias Internacionales</h2>",
                "<p>Si tus datos se transfieren fuera del EEE, aplicamos las garantías adecuadas (Cláusulas Contractuales Tipo u otros mecanismos reconocidos).</p>",
                "<h2>6. Conservación</h2>",
                "<p>Conservamos tus datos durante el tiempo necesario para la prestación del servicio y los plazos legales aplicables. Tras eliminar tu cuenta, borramos o anonimizamos tus datos en un plazo máximo de <strong>{$field('X días/meses')}</strong>.</p>",
                "<h2>7. Tus Derechos</h2>",
                "<p>Tienes derecho a acceder, rectificar, suprimir, limitar el tratamiento, portar tus datos y oponerte al tratamiento. Escríbenos a <a href=\"mailto:{$email}\">{$email}</a>. Si consideras vulnerados tus derechos, puedes reclamar ante la <strong>{$field('autoridad de control — p. ej. AEPD')}</strong>.</p>",
                "<h2>8. Cambios</h2>",
                "<p>Notificaremos los cambios relevantes por correo o mediante aviso en el servicio.</p>",
                "<h2>9. Contacto</h2>",
                "<p>{$field('Nombre de la empresa')}<br>{$field('Dirección postal')}<br><a href=\"mailto:{$email}\">{$email}</a></p>",
            ]),

            /* ─────────────────────────── COOKIES ─────────────────────────── */
            'cookies' => implode('', [
                "<h1>Política de Cookies</h1>",
                "<p><em>Última actualización: " . date('d/m/Y') . "</em></p>",
                "<p>Esta política explica qué cookies utiliza <strong>{$app}</strong>, para qué sirven y cómo puedes controlarlas.</p>",
                "<h2>1. ¿Qué son las Cookies?</h2>",
                "<p>Las cookies son pequeños archivos de texto que se almacenan en tu dispositivo cuando visitas un sitio web. Permiten recordar información sobre tu visita y mejorar tu experiencia de usuario.</p>",
                "<h2>2. Cookies que Utilizamos</h2>",
                "<p><strong>Estrictamente necesarias:</strong> imprescindibles para el funcionamiento del servicio (gestión de sesión, protección CSRF). No pueden desactivarse.</p>",
                "<p><strong>Funcionales:</strong> recuerdan tus preferencias (tema visual, idioma). Pueden desactivarse aunque afectará a la experiencia.</p>",
                "<p><strong>Analíticas:</strong> nos ayudan a entender cómo se usa el servicio. Los datos se agregan y anonimizan en la medida de lo posible.</p>",
                "<p><strong>De terceros:</strong> establecidas por proveedores externos cuando activas integraciones (Google, etc.). Su uso se rige por las políticas de dichos proveedores.</p>",
                "<h2>3. Cookies Específicas</h2>",
                "<p><strong>" . config('session.cookie', 'sientia_session') . "</strong> — Sesión de usuario autenticado (" . config('session.lifetime', 120) . " min).</p>",
                "<p><strong>XSRF-TOKEN</strong> — Protección CSRF (sesión).</p>",
                "<p><strong>theme / locale</strong> — Preferencias visuales y de idioma (1 año).</p>",
                "<p>{$field('Añade aquí otras cookies específicas que utilices')}</p>",
                "<h2>4. Gestión de Cookies</h2>",
                "<p>Puedes eliminar o bloquear cookies desde la configuración de tu navegador (Chrome, Firefox, Safari, Edge). Ten en cuenta que desactivar las cookies necesarias impedirá el uso del servicio.</p>",
                "<p>Para las cookies de integraciones de terceros, desactiva la integración correspondiente desde tu perfil en la sección <strong>Integraciones</strong>.</p>",
                "<h2>5. Cambios</h2>",
                "<p>Actualizaremos esta política cuando sea necesario. La fecha de la última revisión siempre estará visible al inicio del documento.</p>",
                "<h2>6. Contacto</h2>",
                "<p>Dudas sobre cookies: <a href=\"mailto:{$email}\">{$email}</a></p>",
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
