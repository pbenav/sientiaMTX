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

> [!TIP]
> Para maximizar el cumplimiento del ENS, se recomienda mantener SientiaMTX actualizado regularmente a través de la canalización de CI/CD autorizada y alojarlo en sistemas operativos con soporte de seguridad activo (LTS).
