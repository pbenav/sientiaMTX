# 🛡️ Manual del Administrador — SientiaMTX (v1.0.0-GA)

Como **Coordinador** o **Administrador** de SientiaMTX, tienes herramientas avanzadas para gestionar equipos, supervisar el progreso global y mantener la infraestructura del sistema en perfecto estado.

---

## 👥 1. Gestión de Usuarios y Seguridad

### Roles y Jerarquía
SientiaMTX utiliza una jerarquía de roles estricta para proteger la integridad de los datos:
- **Administrador Global**: Acceso a toda la configuración del sistema y gestión de usuarios.
- **Propietario de Equipo**: Creador del equipo. Su rol está protegido y no puede ser degradado por coordinadores.
- **Coordinador**: Puede gestionar miembros y tareas dentro de su equipo, pero **no puede** editar perfiles globales de otros usuarios (email/nombre).
- **Miembro**: Colabora en las tareas del equipo.

### Auditoría de Seguridad Reciente
Se han implementado estándares de seguridad de nivel empresarial:
- **Protección de Perfiles**: Los coordinadores ya no pueden modificar el email de los miembros para evitar riesgos de suplantación.
- **Integridad de Archivos**: Las subidas de archivos en Foros y Tareas están validadas por membresía de equipo y cuotas de disco.

---

## 🏢 2. Gestión de Equipos

### Cuotas de Disco
Cada equipo tiene una cuota de disco configurable por el administrador:
1. Ve a **Configuración → Equipos**.
2. Ajusta el límite de GB permitidos para ese equipo.
3. El sistema bloqueará nuevas subidas si se alcanza el límite.

### Grupos de Trabajo
Crea grupos para asignaciones masivas. Al asignar una tarea a un grupo, SientiaMTX crea automáticamente una instancia para cada miembro del grupo.

---

## 📊 3. Supervisión y Dashboard

### Red Activa (Active Network)
Como coordinador, puedes ver en tiempo real quién está trabajando, su ubicación geográfica (si está habilitada) y su carga de trabajo actual. Esto facilita la delegación inteligente basada en la disponibilidad real.

### Empujones (Nudges)
Si detectas una tarea Q1 (Crítica) estancada, usa el botón 🔔 para enviar un recordatorio inmediato por Telegram/Email al responsable.

---

## ☁️ 4. Gestión de Almacenamiento (Purga)

Para mantener el servidor optimizado, puedes purgar archivos antiguos:
1. Ve a **Configuración → Almacenamiento**.
2. Elige el periodo de tiempo (ej. más de 30 días).
3. Selecciona qué purgar: Archivos de Telegram, Adjuntos obsoletos o Logs de IA.
4. El sistema liberará espacio físico en el disco inmediatamente.

---

## 🤖 5. Configuración de IA (Ax.ia)

SientiaMTX utiliza modelos **Gemini** (Google AI). Como administrador:
- Configura la **API Key** global en el archivo `.env` o permite que cada equipo use su propia clave desde su panel de ajustes.
- Recomendamos el modelo `gemini-1.5-flash` por su equilibrio entre velocidad y coste.

---

## 🔧 6. Mantenimiento del Servidor

### Comandos Esenciales (CLI)

**Limpiar archivos huérfanos:**
```bash
php artisan media:clean-orphans
```

**Sincronizar cuotas de disco:**
```bash
php artisan disk:sync-all
```

**Actualización del Sistema:**
```bash
git pull origin main
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan optimize:clear
npm run build
```

---

## 🛡️ 7. Buenas Prácticas
- **HTTPS Obligatorio**: Necesario para la integración con Telegram y Google.
- **Backups**: Realiza un dump semanal de la base de datos y de la carpeta `storage/app/public`.
- **API Keys**: Nunca compartas el `.env` ni las claves de Gemini.

---
**Sientia MTX: Seguridad y control total para equipos de alto rendimiento.**
