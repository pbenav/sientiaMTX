# 🔐 Cumplimiento del Esquema Nacional de Seguridad (ENS)

El **Esquema Nacional de Seguridad (ENS)** regula las condiciones de seguridad necesarias para garantizar la confianza en el uso de los medios electrónicos en el ámbito de la Administración Pública en España y su cadena de proveedores.

**SientiaMTX** ha sido concebido desde su diseño (*Security by Design*) para ofrecer el más alto nivel de soberanía tecnológica y confidencialidad, preparándose arquitectónicamente para cumplir con los principios y requisitos del ENS.

---

## 🛡️ Pilares Fundamentales del Cumplimiento

La arquitectura de SientiaMTX satisface las exigencias del ENS en múltiples dimensiones clave, permitiendo su despliegue en infraestructuras que traten datos de nivel Medio o Alto.

### 1. Soberanía del Dato y Despliegue On-Premise
Frente a soluciones SaaS alojadas en nubes de terceros, SientiaMTX favorece el despliegue en contenedores autohospedados (*Self-Hosted*).
*   **Localización física:** Los datos residen en servidores controlados por el cliente (ej: Proxmox LXC local).
*   **Aislamiento de Red:** Preparado para operar detrás de NAT sin necesidad de exponer puertos entrantes, gracias al canal de despliegue saliente seguro.

### 2. Autenticación y Control de Acceso (MFA)
Cumpliendo rigurosamente con el principio de prevención y control de accesos:
*   **MFA Robusto:** Implementa autenticación de doble factor (2FA/TOTP) obligatoria.
*   **Privacidad Offline:** A diferencia de otros sistemas, la generación de los códigos QR para el emparejamiento se realiza **localmente en el navegador**, eliminando dependencias de APIs de terceros (como Google Charts), evitando fugas de secretos criptográficos por la red.

### 3. Criptografía y Comunicaciones
*   **Cifrado en Reposo:** Las credenciales sensibles, como claves API de Inteligencia Artificial, Tokens de Google o secretos TOTP, se almacenan cifradas simétricamente mediante algoritmos robustos (AES-256-CBC) gobernados por la clave maestra `APP_KEY`.
*   **Canales Seguros:** Soporte nativo y forzado para TLS 1.3 en todas las interacciones web y notificaciones API.

### 4. Trazabilidad y Auditoría
SientiaMTX mantiene un registro pormenorizado de las acciones que impactan la seguridad del sistema:
*   Log detallado de inicios de sesión e IPs de acceso.
*   Historial inmutable de cambios de estado en tareas y asignaciones.
*   Aislamiento de sesiones en integraciones de terceros (Multi-sesión aislada en WhatsApp Bridge).

### 5. Aislamiento Jerárquico (Deep Privacy)
Garantizando el principio de **Mínimo Privilegio**:
*   **Privacidad Estricta**: Los expedientes y tareas definidos como privados por sus creadores son completamente invisibles para Administradores, Propietarios (Owners) o Coordinadores que no estén explícitamente asignados al recurso. 
*   **Blindaje contra Escalada**: Los permisos administrativos operan de forma separada al acceso a la información confidencial de los usuarios durante su ejecución laboral.

---

## ⚖️ Matriz de Adecuación ENS

| Medida ENS | Implementación SientiaMTX | Nivel de Garantía |
|---|---|---|
| **[op.acc.1]** Identificación y Autenticación | Integración con 2FA (Local-Offline) y contraseñas hasheadas con Bcrypt (work-factor 12+). | ALTO |
| **[op.pl.1]** Planificación de Capacidad | Monitorización activa de cuota de disco asignada por equipo para prevenir ataques de agotamiento de recursos. | MEDIO |
| **[mp.info.1]** Protección de la Información | Los backups de la base de datos SQL y almacenamiento pueden gestionarse localmente sin conexión a internet. | ALTO |
| **[mp.com.1]** Protección de Comunicaciones | Integración API basada en Webhooks seguros y soporte SSL/TLS end-to-end. | ALTO |

---

## 💼 Adecuación para Entornos Confidenciales

Gracias a este enfoque paranoico con la seguridad, SientiaMTX es idóneo para:
1.  **Empresas Estratégicas:** Gestión de tareas sensibles sin exponer datos a la nube comercial.
2.  **Administraciones Locales:** Cumplimiento inmediato de normativas RGPD y esquemas locales de seguridad.
3.  **Investigación y Desarrollo:** Entornos donde la fuga de información competitiva supone un riesgo existencial.

---

## 🌍 Cumplimiento Normativo Internacional (OWASP, GDPR, HIPAA, SOC 2, ISO 27001)

Tras superar con éxito rigurosos escaneos y auditorías de seguridad en 2026, la arquitectura de SientiaMTX incorpora soporte integrado para cumplir con los marcos regulatorios y de seguridad internacionales más exigentes:

### 1. 🌐 OWASP Top 10 (2021) — Blindaje de Aplicación
SientiaMTX implementa controles defensivos frente a los 10 principales riesgos de seguridad web:
*   **Inyección (A03) y Cabeceras (A05)**: Configuración estricta de `Content-Security-Policy` (CSP), `X-Frame-Options` (bloqueo de Clickjacking), `nosniff` y `HSTS` forzado de 1 año mediante `SecurityHeadersMiddleware`.
*   **Defensa SSRF (A10)**: Validación de resoluciones DNS e IPs externas para bloquear automáticamente llamadas a rangos privados o de metadatos cloud.
*   **Protección contra Asignación Masiva**: Modelos de datos protegidos explícitamente mediante `$guarded = ['id', 'is_admin']` para evitar cualquier escalada de privilegios indebida.

### 2. 🇪🇺 GDPR (General Data Protection Regulation) — Privacidad Absoluta
*   **Art. 20 (Portabilidad de Datos)**: Generación automática de descargas estructuradas en formato JSON/CSV con toda la información del perfil del usuario, estadísticas de gamificación, tiempos y actividades.
*   **Art. 17 (Derecho al Olvido)**: Endpoints dedicados para el borrado irreversible de la huella digital. El proceso elimina o anonimiza notas, historiales de estado de ánimo, citas previas y chats antes de revocar la sesión y purgar la cuenta.

### 3. 🏥 HIPAA & 🛡️ SOC 2 — Controles de Auditoría y Vigilancia
*   **Trazabilidad Inmutable**: Inyección de un UUID único en la cabecera `X-Request-ID` para rastreo cruzado en cada petición y correlación forense.
*   **Saneamiento de Logs (*Log Sanitization*)**: Toda información confidencial (contraseñas, tokens de Google, secretos TOTP, claves API) es enmascarada y eliminada en memoria antes de registrarse en el historial de auditoría inmutable (`AuditTrailMiddleware`).

### 4. 📋 ISO/IEC 27001:2022 — Gestión de Seguridad Integral
*   **Control de Accesos (A.9)**: Limitación de velocidad (*Rate Limiting*) progresiva implementada en todas las rutas públicas de autenticación (`throttle:5,1` en login/registro, `throttle:3,1` en recuperación de credenciales) para neutralizar intentos de fuerza bruta y adivinación de cuentas.
*   **Criptografía Segura (A.10)**: Generación de códigos 2FA y secretos TOTP basada en funciones aleatorias del núcleo del sistema operativo (`random_int`), impidiendo su predicción.

> [!TIP]
> Para maximizar el cumplimiento del ENS y de las normativas internacionales, se recomienda mantener SientiaMTX actualizado regularmente a través de la canalización de CI/CD autorizada y alojarlo en sistemas operativos con soporte de seguridad activo (LTS).

