# 🛡️ Manual del Administrador — SientiaMTX (v1.2.0)

Este manual está dirigido a **Coordinadores de Equipo** y **Administradores Globales de Sistemas**. Describe la configuración avanzada de la plataforma, la gestión de infraestructura, la seguridad corporativa y los comandos de mantenimiento periódico de SientiaMTX.

---

## 👥 1. Gestión de Usuarios y Seguridad

### Jerarquía de Roles
SientiaMTX implementa un control de acceso basado en roles con separación estricta de privilegios:
- **Administrador Global (`is_admin`)**: Acceso a la configuración del sistema, creación de equipos, dominios personalizados, configuración ENS y auditorías globales.
- **Propietario de Equipo (*Team Owner*)**: Creador del equipo. Su rango está protegido y no puede ser degradado ni expulsado por coordinadores.
- **Coordinador**: Administra miembros, grupos y cuotas dentro de su propio equipo. Por seguridad, **no puede** modificar credenciales de otros usuarios ni vulnerar la Privacidad Profunda de expedientes privados ajenos.
- **Miembro**: Colaborador operativo dentro del equipo.

### Privacidad Profunda (*Strict Deep Privacy*)
El sistema de seguridad garantiza que los expedientes y tareas marcados como privados son estrictamente confidenciales:
- Ningún administrador ni coordinador tiene visibilidad de expedientes o tareas privadas en los que no figure explícitamente como participante o creador.
- Esta garantía protege datos de recursos humanos, auditorías y asuntos de alta sensibilidad ante inspecciones indebidas.

### Autenticación Multifactor y Cumplimiento ENS
SientiaMTX cumple con las exigencias del Esquema Nacional de Seguridad (ENS):
- Permite forzar o habilitar MFA globalmente desde **Configuración del Sistema ➔ Seguridad**.
- Soporte para **TOTP (RFC 6238)** con generación de QR en el cliente (sin dependencias externas) y verificación por **Código de Correo**.
- Registro de eventos críticos en `SecurityLog` y encabezados de auditoría `X-Request-ID`.

---

## ☁️ 2. Servidor de Documentos: OnlyOffice & Filerobot

### Arquitectura de Red Interna (LAN)
Para evitar bloqueos por *Hairpin NAT* y garantizar una edición en tiempo real fluida, SientiaMTX y OnlyOffice Document Server se comunican directamente a nivel de red interna:

```
[Navegador del Usuario] ──HTTPS──▶ [Proxy Reverso Apache/Nginx]
                                            │
                                            ├──▶ mtx.sientia.com (Laravel)
                                            └──▶ office.sientia.com (OnlyOffice)
                                                   ▲
    LAN Interna Directa (HTTP 192.168.10.x)        │
    Laravel 192.168.10.151 ────────────────────────┘
```

### Variables de Entorno (`.env`)
```env
APP_URL=https://mtx.sientia.com

# OnlyOffice Document Server
ONLYOFFICE_URL=https://office.sientia.com/
ONLYOFFICE_SECRET="clave_secreta_jwt_compartida"
ONLYOFFICE_INTERNAL_APP_URL=http://192.168.10.151
ONLYOFFICE_INTERNAL_SERVER_URL=http://192.168.10.152
```

> [!IMPORTANT]
> Tras modificar variables de entorno en producción, es imprescindible refrescar la caché:
> ```bash
> php artisan config:cache && php artisan cache:clear
> ```

---

## 📆 3. Gestión del Módulo de Cita Previa

Como administrador, dispones de herramientas de supervisión integral de las agendas:
1. **Configuración de Servicios (`AppointmentService`)**: Alta de servicios presenciales, telemáticos (Sientia Meet / Google Meet), asignación de miembros y definición de cuotas por franja horaria.
2. **Plantillas Horarias y Bloqueos**: Configura turnos semanales (`AppointmentSchedule`) y crea bloqueos (`AppointmentBlock`) para festivos o paradas técnicas.
3. **Control Extraordinario**:
   - Capacidad exclusiva para registrar citas en fechas pasadas para dejar constancia de atenciones no programadas.
   - Habilitación de slots extraordinarios con validación en tiempo real.
4. **Cumplimiento RGPD (Art. 17)**: Desde la gestión de visitantes (`AppointmentVisitor`), puedes ejecutar la anonimización definitiva de datos de ciudadanos a petición del interesado, manteniendo el conteo estadístico para auditorías de servicio.

---

## 🤖 4. Motor de Inteligencia Artificial (Ax.ia)

SientiaMTX integra modelos Google Gemini con gestión flexible de claves:
- **Clave Global del Sistema**:
  ```env
  GEMINI_API_KEY="tu_clave_api_de_google_ai_studio"
  ```
- **Claves por Equipo**: Los equipos pueden definir su propia API Key cifrada desde sus ajustes de equipo para no consumir la cuota global del servidor.
- **Cadena de Modelos Fallback**: El servicio utiliza por defecto `gemini-1.5-flash` o `gemini-2.0-flash`, con degradación controlada ante saturación de cuota hacia `gemini-1.5-pro`.

---

## 📲 5. Canales Externos y Notificaciones

- **WhatsApp Web Bridge**: Requiere autorización previa del administrador en la ficha del usuario (`whatsapp_enabled`). El controlador está protegido mediante el middleware `EnsureWhatsappIsEnabled`.
- **Telegram Bot**: Configura el token del bot en el `.env` y ejecuta `php artisan telegram:setup-webhook` para habilitar el canal bidireccional.
- **Google Workspace API**: Habilita sincronización de Google Calendar y Google Tasks siguiendo la [Guía de Configuración Google API](google-setup.md).

---

## 🔧 6. Catálogo de Comandos de Mantenimiento (CLI)

SientiaMTX dispone de comandos de consola especializados para la automatización y mantenimiento del servidor:

### Control Horario y Jornadas
```bash
# Revisa y marca jornadas que superan el horario del usuario o el hard-cap de 10h
php artisan timelogs:mark-anomalous

# Modo simulación (no escribe cambios)
php artisan timelogs:mark-anomalous --dry-run

# Analizar un usuario específico
php artisan timelogs:mark-anomalous --user=42
```

### Recordatorios y Reuniones
```bash
# Dispara los recordatorios pendientes según sus canales (Telegram, WhatsApp, Mail, Push)
php artisan reminders:trigger

# Forzar disparo de una actividad concreta en pruebas
php artisan reminders:trigger --activity=108

# Marca como completadas automáticamente las reuniones pasadas
php artisan app:autocomplete-meetings

# Despierta actividades y tareas autoprogramadas recurrentes
php artisan sientia:activities-autoprogram-wakeup
```

### Almacenamiento y Optimización
```bash
# Elimina archivos huérfanos sin referencias en la base de datos
php artisan media:clean-orphans

# Recalcula y sincroniza el uso de disco de todos los equipos
php artisan disk:sync-all

# Purga mensajes antiguos del chat de equipo según política de retención
php artisan chat:purge-old-messages

# Vacía elementos de la papelera que superan el periodo de retención
php artisan tasks:cleanup-trash
```

### Monitorización de Salud (Sentinel)
```bash
# Ejecuta comprobaciones de salud sobre todos los servicios monitorizados
php artisan app:check-sentinel

# Forzar comprobación inmediata ignorando intervalos
php artisan app:check-sentinel --force
```

### Procedimiento Estándar de Despliegue en Producción
```bash
cd /var/www/sientiaMTX
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build
```

---

**SientiaMTX: Control total, fiabilidad técnica y seguridad para entornos corporativos.**
