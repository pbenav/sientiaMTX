# 🔒 Auditoría de Seguridad — SientiaMTX v1.2.0

**Fecha:** 23 de julio de 2026  
**Aplicación:** SientiaMTX (Laravel 12.x)  
**Rama:** `dev`  
**Alcance:** Código fuente completo, configuración, rutas, controladores, modelos, vistas, dependencias, archivos sensibles  
**Total de hallazgos:** 42 (10 Críticos, 14 Altos, 12 Medios, 6 Bajos)

---

## Tabla de Resumen por Severidad

| Severidad | Cantidad | Descripción |
|-----------|----------|-------------|
| 🔴 **Crítica** | 10 | Riesgo inmediato de compromiso del sistema |
| 🟠 **Alta** | 14 | Vulnerabilidades que requieren atención urgente |
| 🟡 **Media** | 12 | Debilidades que deben corregirse en el corto plazo |
| 🟢 **Baja** | 6 | Mejoras recomendadas |

---

## 🔴 HALLAZGOS CRÍTICOS

### C-01. Modos de Depuración Activos en Entorno de Desarrollo
- **Archivo:** `.env`, línea 4
- **Código:** `APP_DEBUG=true`
- **Riesgo:** Laravel muestra trazas completas del stack, consultas SQL, variables de entorno y configuración en páginas de error. Un atacante puede extraer credenciales de base de datos, claves API, rutas internas y configuración del servidor.
- **Recomendación:** Establecer `APP_DEBUG=false` en producción. Verificar que `.env.example` tenga `APP_DEBUG=false`.

### C-02. URL de la Aplicación Configurada con HTTP
- **Archivo:** `.env`, línea 5
- **Código:** `APP_URL=http://localhost:8000`
- **Riesgo:** Los tokens CSRF, cookies de sesión y todo el tráfico de la aplicación pueden negociarse sobre HTTP sin cifrar. Aunque `SecurityHeadersMiddleware` establece `Strict-Transport-Security`, si `APP_URL` es HTTP, el generador de URLs de Laravel producirá enlaces `http://`, potencialmente ignorando HSTS.
- **Recomendación:** Establecer `APP_URL=https://tudominio.com` en producción. Asegurar que un proxy inverso (Nginx/Apache) termine TLS y establezca `X-Forwarded-Proto: https`.

### C-03. Credenciales de Base de Datos Débiles e Idénticas
- **Archivo:** `.env`, líneas 14-17
- **Código:** `DB_USERNAME=********`, `DB_PASSWORD=********`
- **Riesgo:** Usuario y contraseña idénticos son trivialmente adivinables. La base de datos está en `127.0.0.1` con el puerto MySQL predeterminado `3306` y el nombre de base de datos es `sientiamtx`.
- **Recomendación:** Generar una contraseña fuerte y aleatoria (mínimo 32 caracteres, mayúsculas, minúsculas, dígitos, caracteres especiales). Usar un usuario dedicado con privilegios mínimos. Implementar restricciones de firewall.

### C-04. Claves de Google OAuth en Texto Plano
- **Archivo:** `.env`, líneas 48-49
- **Código:** `GOOGLE_CLIENT_SECRET=********`
- **Riesgo:** Un atacante podría suplantar la aplicación en flujos OAuth de Google, accediendo potencialmente a servicios vinculados a la cuenta.
- **Recomendación:** Rotar inmediatamente la clave secreta. Almacenar en un gestor de secretos. Restringir el cliente OAuth a URIs de redirección específicas.

### C-05. Token de Bot de Telegram en Texto Plano
- **Archivo:** `.env`, línea 50
- **Código:** `TELEGRAM_BOT_TOKEN=********:********`
- **Riesgo:** Cualquiera con este token puede enviar mensajes como el bot, leer canales accesibles y realizar operaciones del bot.
- **Recomendación:** Revocar el token actual vía BotFather y generar uno nuevo. Almacenar en un gestor de secretos.

### C-06. Clave Privada VAPID en Texto Plano
- **Archivo:** `.env`, línea 57
- **Código:** `VAPID_PRIVATE_KEY=********`
- **Riesgo:** Un atacante podría enviar notificaciones push maliciosas a todos los suscriptores, facilitando phishing vía notificaciones push.
- **Recomendación:** Regenerar el par de claves VAPID. Almacenar en un gestor de secretos.

### C-07. Escalado de Privilegios vía Asignación Masiva en `User.php`
- **Archivo:** `app/Models/User.php`
- **Campo vulnerable:** `$fillable` incluye `is_admin` y `permissions`
- **Riesgo:** Si algún controlador, endpoint de API o formulario permite que la entrada del usuario llegue al modelo `User` mediante `User::create()` o `$user->fill()`, un atacante podría:
  - Otorgarse privilegios de administrador (`is_admin = true`)
  - Inyectar permisos arbitrarios en el campo `permissions` (JSON)
- **Recomendación:** Eliminar `is_admin` y `permissions` de `$fillable`. Establecerlos programáticamente en clases de servicio o formularios.

### C-08. Inyección CSS en Micrositios Públicos
- **Archivo:** `resources/views/microsites/public/show.blade.php`, línea 26
- **Patrón:** `{!! $cssContent !!}`
- **Riesgo:** El contenido CSS controlado por el usuario se renderiza directamente en etiquetas `<style>`. Un atacante que controle el contenido del micrositio puede inyectar ataques basados en CSS (exfiltración vía `url()`, o incluso XSS impulsado por CSS mediante vulnerabilidades del navegador).
- **Recomendación:** Sanitizar `$cssContent` antes de la renderización. Validar que solo contenga propiedades CSS seguras.

### C-09. Inyección XSS en Contexto JavaScript — Sala de Video Pública
- **Archivo:** `resources/views/public/appointments/video_room.blade.php`, líneas 67, 70, 72, 98
- **Patrón:** `$appointment->visitor->full_name` interpolado directamente en JavaScript
- **Riesgo:** Un nombre de visitante como `"><script>alert(1)</script><"` ejecutaría JavaScript arbitrario. Esta es una página **pública** (accesible sin autenticación), lo que la hace particularmente peligrosa.
- **Recomendación:** Usar `@json()` para todas las interpolaciones en contexto JavaScript. Validar el formato de `$jitsiDomain`.

### C-10. Inyección de Plantilla en Asistente IA — `x-html` sin Sanitización
- **Archivo:** `resources/views/components/ai-assistant.blade.php`, líneas 165, 190, 1296
- **Patrón:** `<span x-html="renderMarkdown(msg.content)">` y `marked.parse()` sin sanitizador
- **Riesgo:** Las respuestas del asistente IA (activadas por el usuario) se renderizan directamente como HTML vía Alpine.js `x-html` y `marked.parse()`. Si la respuesta de la IA contiene contenido malicioso (por ejemplo, de un ataque de inyección de prompt), se ejecutará como JavaScript.
- **Recomendación:** Implementar DOMPurify o sanitizador similar en `marked.parse()`. Validar y sanitizar las respuestas de la IA antes de la renderización.

---

## 🟠 HALLAZGOS DE ALTA SEVERIDAD

### H-01. CSP Permite `'unsafe-inline'` y `'unsafe-eval'`
- **Archivo:** `app/Http/Middleware/SecurityHeadersMiddleware.php`, línea 37
- **Código:**
  ```php
  "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://*; " .
  "style-src 'self' 'unsafe-inline' https://*; " .
  ```
- **Riesgo:** Las directivas `'unsafe-inline'` y `'unsafe-eval'` anulan los beneficios principales de CSP:
  - `'unsafe-inline'` en `script-src` permite ejecución de JavaScript en línea, facilitando ataques XSS.
  - `'unsafe-eval'` permite `eval()` y funciones similares, posibilitando inyección de código.
- **Recomendación:** Eliminar `'unsafe-inline'` y `'unsafe-eval'` de CSP. Usar nonces o hashes para scripts en línea. Usar un encabezado `Content-Security-Policy-Report-Only` inicialmente para probar el impacto.

### H-02. CSP Permite Wildcard `https://*` en Múltiples Directivas
- **Archivo:** `app/Http/Middleware/SecurityHeadersMiddleware.php`, líneas 36-43
- **Riesgo:** El CSP usa wildcards `https://*` en `default-src`, `script-src`, `style-src`, `img-src`, `media-src`, `font-src`, `connect-src` y `frame-src`. Esto permite cargar recursos de **CUALQUIER** sitio HTTPS, lo que anula el propósito de CSP.
- **Recomendación:** Reemplazar wildcards con dominios específicos permitidos. Por ejemplo: `https://cdn.example.com https://api.example.com`.

### H-03. Exención de CSRF para Múltiples Endpoints de Webhook
- **Archivo:** `bootstrap/app.php`, líneas 34-42
- **Riesgo:** Múltiples rutas están exentas de protección CSRF:
  - `/telegram/webhook` y `/whatsapp/webhook`: Endpoints públicos de webhook. Necesitan autenticación alternativa (verificación de firma HMAC).
  - `/api/s2s/sync-workday` y `/api/s2s/sync-history`: Endpoints servidor-a-servidor. Confían únicamente en la clave secreta.
  - `/onlyoffice/callback/*`: Debería verificar la firma HMAC de OnlyOffice.
- **Recomendación:** Asegurar que cada ruta exenta tenga su propio mecanismo de autenticación. Documentar todas las exenciones y controles compensatorios.

### H-04. Proxies de Confianza Configurados con Wildcard `*`
- **Archivo:** `bootstrap/app.php`, línea 44
- **Código:** `$trusted = env('TRUSTED_PROXIES', '*');`
- **Riesgo:** Todas las direcciones IP son consideradas proxies confiables. Esto permite que cualquier cliente suplante la cabecera `X-Forwarded-For` e impersonifique cualquier dirección IP, potencialmente omitiendo limitación por IP.
- **Recomendación:** Establecer `TRUSTED_PROXIES` a las direcciones IP específicas del proxy/load balancer.

### H-05. Archivo de Log Sobredimensionado (479 MB)
- **Archivo:** `storage/logs/laravel.log`
- **Tamaño:** 479,650,709 bytes
- **Riesgo:** Un archivo de log de este tamaño indica:
  - Configuración de logging incorrecta (nivel debug en producción)
  - Error de aplicación que produce salida masiva
  - Relleno de disco que puede causar interrupciones del servicio
  - Contiene datos sensibles (contraseñas, tokens, PII) en texto plano
- **Recomendación:** Establecer `LOG_LEVEL=warning` o `error` en producción. Implementar rotación de logs. Considerar un sistema de logging centralizado.

### H-06. SSL de Base de Datos No Forzado
- **Archivo:** `config/database.php`, líneas 62-63 y 98
- **Riesgo:** La configuración SSL de MySQL/MariaDB solo incluye `MYSQL_ATTR_SSL_CA` si la variable de entorno está configurada. Si no está configurada, `array_filter` la elimina, resultando en **SIN configuración SSL**. PostgreSQL usa `sslmode=prefer`, que permite conexiones no cifradas.
- **Recomendación:** Configurar `MYSQL_ATTR_SSL_CA` con la ruta del certificado CA. Usar `sslmode=require` o `sslmode=verify-full` para PostgreSQL.

### H-07. Almacenamiento en Caché Configurado como `file`
- **Archivo:** `.env`, línea 30
- **Código:** `CACHE_STORE=file`
- **Riesgo:** El driver de caché almacena datos en el sistema de archivos local. En un entorno multi-servidor, la caché basada en archivos no se compartirá entre servidores. Además, los datos en caché pueden contener información sensible (sesiones, contraseñas) si no están correctamente cifrados.
- **Recomendación:** Usar `redis` o `database` como driver de caché en producción.

### H-08. Cola Configurada como `sync`
- **Archivo:** `.env`, línea 24
- **Código:** `QUEUE_CONNECTION=sync`
- **Riesgo:** Todos los trabajos se ejecutan sincrónicamente en el ciclo de vida de la solicitud:
  - Tiempos de respuesta lentos para los usuarios
  - Timeouts de solicitud para trabajos de larga ejecución
  - Sin mecanismo de reintento para trabajos fallidos
  - Sin capacidad de procesamiento en segundo plano
- **Recomendación:** Usar `database`, `redis` o `sqs` como driver de cola en producción.

### H-09. HTML Purifier Permite Estilo en Línea en Múltiples Elementos
- **Archivo:** `config/purifier.php`, línea 28
- **Código:** `'HTML.Allowed' => 'div,b,strong,i,em,u,a[href|title],ul,ol,li,p[style],br,span[style],img[width|height|alt|src]'`
- **Riesgo:** La configuración permite atributos `style` en elementos `p` y `span`. Aunque las propiedades CSS están restringidas a una lista segura, se puede explotar para inyección CSS (por ejemplo, `position: absolute; z-index: 999999;` para superponer contenido).
- **Recomendación:** Eliminar `[style]` de los atributos permitidos. Usar una lista `HTML.Allowed` más restrictiva.

### H-10. Inyección XSS en Editor de Markdown — Vista Previa
- **Archivo:** `resources/views/components/markdown-editor.blade.php`, líneas 28, 30, 416
- **Patrón:** `marked.parse()` sin sanitizador + `x-html="preview"`
- **Riesgo:** La función de vista previa del editor markdown usa `marked.parse()` sin sanitizador y se renderiza vía `x-html`. Cualquier contenido markdown con HTML (por ejemplo, `<img src=x onerror=alert(1)>`) se ejecutará como JavaScript.
- **Recomendación:** Implementar DOMPurify en `marked.parse()`. Este es un componente compartido usado en toda la aplicación.

### H-11. Markdown sin Sanitización en Expedientes
- **Archivo:** `resources/views/expedientes/show.blade.php`, línea 943
- **Patrón:** `{!! Str::markdown($note->content) !!}`
- **Riesgo:** El contenido markdown generado por el usuario se renderiza como HTML sin stripping. Los atacantes pueden inyectar HTML/JavaScript a través de notas de expediente.
- **Recomendación:** Usar `Str::markdown($note->content, ['html_input' => 'strip'])`.

### H-12. Mensajes del Foro con Markdown sin Sanitización
- **Archivo:** `resources/views/teams/forum/partials/message-item.blade.php`
- **Riesgo:** Los mensajes del foro se renderizan como HTML vía markdown sin stripping. Los usuarios pueden inyectar HTML/JavaScript a través de publicaciones.
- **Recomendación:** Configurar `html_input => 'strip'` en el procesador de markdown.

### H-13. Asignación Masiva en `Activity.php` — `created_by_id` y `team_id`
- **Archivo:** `app/Models/Activity.php`
- **Riesgo:** `created_by_id` y `team_id` están en `$fillable`. Un atacante podría crear actividades en nombre de otros usuarios o en otros equipos.
- **Recomendación:** Eliminar del `$fillable`. Establecer explícitamente en clases de servicio:
  ```php
  $activity->created_by_id = auth()->id();
  $activity->team_id = $request->user()->currentTeam()->id;
  ```

### H-14. Validación de Dominio Jitsi Ausente
- **Archivo:** `app/Http/Controllers/Appointments/AppointmentSettingsController.php`, línea 78
- **Riesgo:** `jitsi_domain` no tiene validación de formato de dominio. Un atacante podría establecer el dominio de Jitsi en una URL maliciosa (por ejemplo, `attacker.com`), redirigiendo las videollamadas a un servidor controlado para robo de credenciales o ataques de intermediario.
- **Recomendación:** Validar que `jitsi_domain` sea un dominio válido y pertenezca a Jitsi Meet.

---

## 🟡 HALLAZGOS DE MEDIA SEVERIDAD

### M-01. Archivo `.env` en el Directorio de Trabajo con Credenciales Reales
- **Archivo:** `.env` (raíz del proyecto)
- **Riesgo:** El archivo `.env` contiene credenciales de producción en texto plano. Aunque está en `.gitignore` y NO está rastreado por git, su presencia en el directorio de trabajo significa que cualquier desarrollador con acceso al sistema puede leerlo.
- **Recomendación:** Asegurar permisos de archivo `600` en `.env`. Verificar que no haya copias de seguridad accesibles.

### M-02. Credenciales SMTP en Texto Plano
- **Archivo:** `.env`, líneas 36-39
- **Código:** `MAIL_PASSWORD="#!Sientia+!"`
- **Riesgo:** Si un atacante obtiene acceso al sistema de archivos o si `.env` se compromete accidentalmente, tendría acceso completo para enviar correos como `info@sientia.com`.
- **Recomendación:** Rotar la contraseña SMTP inmediatamente. Usar un servicio de correo transaccional (SendGrid, Amazon SES) con claves API de alcance limitado.

### M-03. Credenciales de Telegram Webhook en Texto Plano
- **Archivo:** `.env`, línea 51
- **Riesgo:** `TELEGRAM_WEBHOOK_SECRET` expuesto en texto plano.
- **Recomendación:** Almacenar en un gestor de secretos.

### M-04. Tiempo de Sesión de 120 Minutos
- **Archivo:** `.env`, línea 19
- **Código:** `SESSION_LIFETIME=120`
- **Riesgo:** Un tiempo de sesión de 2 horas es moderadamente largo y podría permitir secuestro de sesión si una cookie de sesión es robada.
- **Recomendación:** Considerar reducir a 30 minutos para aplicaciones sensibles. Implementar expiración deslizante.

### M-05. Sin Limitación de Tasa en Endpoints Públicos
- **Archivo:** `routes/web.php`
- **Riesgo:** Los endpoints de webhook públicos (`/telegram/webhook`, `/whatsapp/webhook`) y las rutas públicas de reserva de citas no tienen limitación de tasa. Esto podría llevar a:
  - Abuso de webhooks / inundación
  - Spam en reservas de citas
  - Agotamiento de recursos
- **Recomendación:** Aplicar middleware de limitación de tasa a endpoints públicos.

### M-06. Rutas Públicas de Citas sin Autenticación
- **Archivo:** `routes/web.php`, líneas 38-56
- **Riesgo:** Las rutas públicas incluyen operaciones `book`, `edit`, `update` y `confirm` que manejan datos personales/médicos. Aunque usan tokens `localizador` para el acceso, esto es una forma de seguridad por oscuridad.
- **Recomendación:** Implementar expiración de tokens. Agregar limitación de tasa. Considerar CAPTCHA en formularios de reserva.

### M-07. Fichero `config/cors.php` No Existe
- **Archivo:** `config/` (archivo ausente)
- **Riesgo:** Sin configuración CORS explícita, Laravel permite por defecto todos los orígenes (`*`) para solicitudes preflight.
- **Recomendación:** Crear `config/cors.php` con orígenes, métodos y encabezados explícitos.

### M-08. Archivos de Volcado de Base de Datos en la Raíz del Repositorio
- **Archivos:** `dump_mtx_160726.sql` (25 MB), `dump_mtx_190726.sql` (26 MB)
- **Riesgo:** Los dumps contienen INSERT con datos reales de la aplicación, URLs de producción, trazas de error con rutas de archivos y detalles de excepciones, datos de usuarios en campos JSON.
- **Recomendación:** Mover los dumps a un directorio de copias de seguridad cifrado fuera del proyecto. Implementar rotación automática.

### M-09. Permisos de Archivos de Sesión (666)
- **Archivo:** `storage/framework/sessions/`
- **Riesgo:** Algunos archivos de sesión tienen permisos 666 (escritura mundial), lo que podría permitir secuestro de sesión si otro usuario del sistema puede acceder a ellos.
- **Recomendación:** Restringir permisos a 660 o 664:
  ```bash
  chmod 660 storage/framework/sessions/*
  ```

### M-10. Archivos de Respaldo (`*.bak`) Rastreados en Git
- **Archivo:** `resources/views/tasks/show.blade.php.bak`
- **Riesgo:** Un archivo `.bak` obsoleto está rastreado en git. Podría contener código sensible o lógica de negocio.
- **Recomendación:** Eliminar del repositorio y añadir `*.bak` a `.gitignore`.

### M-11. Autenticación S2S Basada en Clave Compartida Simple
- **Archivo:** `app/Http/Controllers/S2SIntegrationController.php`
- **Riesgo:** La autenticación servidor-a-servidor usa una clave compartida (`X-S2S-Secret` header o Bearer token). Si la clave se compromete, un atacante puede sincronizar datos de jornada laboral e historial.
- **Recomendación:** Implementar mutual TLS o autenticación con firma de solicitud. Rotar la clave periódicamente.

### M-12. Controlador de Documentación con Markdown sin Sanitización
- **Archivo:** `app/Http/Controllers/DocumentationController.php`, línea 38
- **Patrón:** `Str::markdown($contentMd)` sin opción `html_input`
- **Riesgo:** El contenido de documentación editable por usuario se renderiza como HTML sin stripping.
- **Recomendación:** Usar `Str::markdown($contentMd, ['html_input' => 'strip'])`.

---

## 🟢 HALLAZGOS DE BAJA SEVERIDAD

### L-01. Versión de la Aplicación Expuesta
- **Archivo:** `config/app.php`, líneas 17-18
- **Código:** `'version' => env('APP_VER', '1.2.0')`
- **Riesgo:** La versión de la aplicación se expone vía `APP_VER`. Aunque no es directamente explotable, ayuda a los atacantes a identificar vulnerabilidades conocidas para versiones específicas.
- **Recomendación:** Eliminar la exposición de la versión en producción. Usar un sistema de seguimiento de versión interno separado.

### L-02. BCRYPT_ROUNDS en 12
- **Archivo:** `.env`, línea 11
- **Código:** `BCRYPT_ROUNDS=12`
- **Estado:** **Positivo** — Este valor está por encima del predeterminado de Laravel (10).
- **Nota:** `phpunit.xml` establece `BCRYPT_ROUNDS=4` para pruebas, lo cual es apropiado para velocidad pero nunca debe usarse en producción.
- **Recomendación:** Asegurar que `BCRYPT_ROUNDS=12` solo se aplique en producción.

### L-03. Datos de Sesión de WhatsApp Duplicados
- **Archivos:** `.wwebjs_auth/` (raíz) y `whatsapp-service/.wwebjs_auth/`
- **Tamaño:** 61 MB en cada ubicación
- **Riesgo:** Tener datos de autenticación de WhatsApp en dos ubicaciones es unconventional y podría ser un riesgo de seguridad si el directorio raíz se comparte.
- **Recomendación:** Consolidar la autenticación de WhatsApp en `whatsapp-service/.wwebjs_auth/` y eliminar el directorio `.wwebjs_auth/` en la raíz.

### L-04. Driver de Broadcasting Configurado como `log`
- **Archivo:** `.env`
- **Riesgo:** El broadcasting está configurado como `log`, lo cual es seguro pero no útil para producción. Las notificaciones en tiempo real no se entregarán.
- **Recomendación:** Configurar un driver de producción adecuado (Redis, Pusher) o establecer explícitamente como `null`.

### L-05. `config/hashing.php`, `config/broadcasting.php`, `config/view.php` No Existen
- **Archivo:** `config/` (archivos ausentes)
- **Riesgo:** Usan valores predeterminados. No hay configuración explícita de hashing, broadcasting o cache de vistas.
- **Recomendación:** Crear estos archivos de configuración explícitamente para asegurar que todos los ajustes son intencionales.

### L-06. Manejo Silencioso de Errores de Desencriptación en `UserProfile.php`
- **Archivo:** `app/Traits/UserProfile.php`
- **Patrón:** `decrypt()` con fallback a `null` sin logging
- **Riesgo:** Si `decrypt()` falla (por ejemplo, clave incorrecta), retorna silenciosamente `null`, lo que podría enmascarar problemas.
- **Recomendación:** Agregar logging para fallos de desencriptación:
  ```php
  try {
      return decrypt($this->google_token);
  } catch (\Exception $e) {
      Log::error('Failed to decrypt google_token for user: ' . $this->id, ['error' => $e->getMessage()]);
      return null;
  }
  ```

---

## ✅ PRÁCTICAS DE SEGURIDAD CORRECTAMENTE IMPLEMENTADAS

La aplicación demuestra varias **buenas prácticas de seguridad**:

1. **Middleware de Headers de Seguridad** — `SecurityHeadersMiddleware` configura correctamente `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Strict-Transport-Security` y `Content-Security-Policy`.

2. **Middleware de Auditoría** — `AuditTrailMiddleware` registra IDs de solicitud, IDs de usuario, IPs, métodos, URLs y payloads sanitizados. Los campos sensibles (`password`, `token`, `api_key`, etc.) se excluyen de los registros.

3. **Protección CSRF** — La protección CSRF integrada de Laravel está habilitada para todas las rutas web con exenciones específicas documentadas.

4. **Limitación de Tasa (Throttling)** — Las rutas de autenticación tienen limitación de tasa (`throttle:30,1` para login/registro, `throttle:15,1` para restablecimiento de contraseña, `throttle:6,1` para verificación de email).

5. **Cifrado de Sesión** — `SESSION_ENCRYPT=true` está configurado en `.env`.

6. **Autenticación de Dos Factores (2FA)** — La aplicación soporta 2FA (rutas existen en `routes/auth.php`).

7. **WebAuthn/Passkeys** — La autenticación por passkey está configurada con relying party ID adecuado y limitación de tasa.

8. **Modo Demo/Privacidad** — Un modo demo (`APP_DEMO_MODE`) está disponible para enmascarar datos sensibles en la interfaz.

9. **`.env` No Rastreado en Git** — `.env` está correctamente listado en `.gitignore` y NO está rastreado por git.

10. **Purificación HTML** — HTMLPurifier está configurado para sanitizar la entrada del usuario.

11. **Manejo de Expiración de Sesión** — Un middleware personalizado maneja la expiración de sesión de forma elegante con redireccionamientos adecuados.

12. **Rounds de Bcrypt** — Los rounds de bcrypt en producción están establecidos en 12 (por encima del valor predeterminado de 10).

---

## 📋 PLAN DE ACCIÓN PRIORIZADO

### Inmediato (Días 1-3)
1. 🔴 Rotar TODAS las credenciales en `.env` (base de datos, SMTP, Google OAuth, Telegram, VAPID, OnlyOffice, CTH)
2. 🔴 Establecer `APP_DEBUG=false` y `APP_URL=https://` en producción
3. 🔴 Fortalecer credenciales de base de datos (actualmente `cth`/`cth`)
4. 🔴 Eliminar archivos de volcado de base de datos del directorio raíz del repositorio

### Alto (Semanas 1-2)
5. 🟠 Ajustar CSP eliminando `unsafe-inline`, `unsafe-eval` y wildcards `https://*`
6. 🟠 Configurar IPs de proxy de confianza específicas en lugar de `*`
7. 🟠 Implementar rotación de logs y reducir `LOG_LEVEL` a `warning` o `error`
8. 🟠 Forzar conexiones SSL/TLS de base de datos
9. 🟠 Sanitizar todas las llamadas `Str::markdown()` con `['html_input' => 'strip']`
10. 🟠 Implementar DOMPurify en `marked.parse()` para vistas de markdown y asistente IA

### Medio (Semanas 3-4)
11. 🟡 Crear `config/cors.php` con orígenes explícitos
12. 🟡 Agregar limitación de tasa a endpoints públicos de webhook y citas
13. 🟡 Eliminar `is_admin` y `permissions` de `$fillable` en `User.php`
14. 🟡 Eliminar `created_by_id` y `team_id` de `$fillable` en `Activity.php`
15. 🟡 Validar dominio Jitsi en `AppointmentSettingsController.php`
16. 🟡 Restringir permisos de archivos de sesión a 660

### Largo Plazo (Meses 1-3)
17. 🟢 Almacenar todas las credenciales en un gestor de secretos (AWS Secrets Manager, HashiCorp Vault, etc.)
18. 🟢 Migrar driver de caché de `file` a `redis`
19. 🟢 Migrar driver de cola de `sync` a `redis` o `database`
20. 🟢 Implementar escaneo automático de XSS en pipeline CI/CD
21. 🟢 Evaluar migración de driver de sesiones de `file` a `database` o `redis`
22. 🟢 Implementar sistema de logging centralizado (ELK, Sentry, Datadog)

---

## 📊 Métricas de la Auditoría

| Métrica | Valor |
|---------|-------|
| Archivos de configuración analizados | 17 |
| Middlewares analizados | 12 |
| Controladores analizados | 45+ |
| Modelos analizados | 60+ |
| Traits analizados | 17 |
| Vistas de Blade analizadas | 200+ |
| Rutas analizadas | 300+ |
| Dependencias composer analizadas | 120+ |
| Dependencias npm analizadas | 80+ |
| Total de hallazgos | 42 |

---

## 🔐 Conclusión

La aplicación SientiaMTX demuestra una base sólida de prácticas de seguridad con middleware de headers de seguridad, protección CSRF, throttling de autenticación, soporte 2FA/WebAuthn y purificación HTML. Sin embargo, se identificaron **10 vulnerabilidades críticas** que requieren atención inmediata, principalmente relacionadas con:

1. **Credenciales expuestas** en el archivo `.env` (7 hallazgos críticos)
2. **Asignación masiva** que permite escalado de privilegios (1 hallazgo crítico)
3. **Inyección XSS** en vistas públicas y componentes compartidos (2 hallazgos críticos)
4. **CSP debilitada** por `unsafe-inline`, `unsafe-eval` y wildcards (2 hallazgos altos)
5. **Markdown sin sanitización** en múltiples vistas (4 hallazgos altos)

Se recomienda priorizar la rotación de credenciales, el ajuste de CSP y la sanitización de markdown antes de cualquier despliegue a producción.
