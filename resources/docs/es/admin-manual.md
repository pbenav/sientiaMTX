# 🛡️ Manual del Administrador — SientiaMTX

Como **Coordinador** o **Administrador** de SientiaMTX, tienes herramientas avanzadas para gestionar equipos, supervisar el progreso global y mantener la infraestructura del sistema en perfecto estado.

---

## 👥 1. Gestión de Usuarios

### Crear Nuevos Usuarios
Desde **Configuración → Usuarios → Crear Usuario**:
1. Introduce el nombre, correo y contraseña inicial.
2. Activa la opción **"Es Administrador"** si debe tener acceso a la configuración global.
3. El usuario recibirá un correo de bienvenida (si el servidor de correo está configurado).

### Roles del Sistema
| Rol | Permisos |
|---|---|
| **Administrador** | Acceso total: configuración, usuarios, notificaciones, Telegram |
| **Coordinador** | Gestiona su equipo: crea, edita y reasigna tareas de otros |
| **Miembro** | Crea sus propias tareas y colabora en las públicas del equipo |

### Desactivar o Eliminar Usuarios
En la lista de usuarios, usa los botones de acción de cada fila. Eliminar un usuario no borra sus tareas; las reasigna automáticamente al administrador del equipo.

---

## 🏢 2. Gestión de Equipos

Cada equipo en SientiaMTX es un **espacio de trabajo independiente** con sus propias tareas, tableros y miembros.

### Crear un Equipo
Desde el dashboard principal → **"Crear Nuevo Equipo"**:
- Nombre, descripción e imagen de portada.
- El creador del equipo se convierte automáticamente en su **Coordinador**.

### Añadir Miembros
Dentro del equipo → **Miembros → Invitar**:
- Busca al usuario por su email (debe estar registrado en el sistema).
- Asigna su rol: Miembro o Coordinador.

### Grupos dentro del Equipo
Crea grupos (departamentos, áreas) para poder asignar tareas masivas:
1. Dentro del equipo → **Miembros → Crear Grupo**.
2. Cuando crees una tarea, en el selector de "Asignados" podrás elegir un grupo completo.

---

## 📊 3. Supervisión de Tareas

Como coordinador, tienes visibilidad completa sobre:

- **Dashboard del Equipo**: Gráfica de progreso global, tareas por estado y rendimiento por miembro.
- **Reasignar Tareas**: Si un miembro abandona el equipo o está de baja, puedes reasignar sus tareas desde la vista de lista.
- **Enviar "Empujón" (Nudge)**: Si una tarea está bloqueada o llevas tiempo sin actualizaciones, el botón 🔔 envía un recordatorio al responsable.

### Ver Tareas Privadas
> [!WARNING]
> Por diseño de seguridad, **los coordinadores NO pueden ver las tareas privadas** de otros miembros. Solo el propietario de la tarea puede verla. Esta restricción es intencional y garantiza la privacidad del trabajador.

---

## ⚙️ 4. Configuración Global (Solo Administradores)

### Correo Electrónico (SMTP)
En **Configuración → Correo**:
- Configura el servidor SMTP (host, puerto, credenciales).
- Usa el botón **"Enviar Email de Prueba"** para verificar la configuración antes de guardar.

### Notificaciones y Telegram
En la misma sección de Configuración:
1. Introduce el **Token del Bot** (obtenido de @BotFather).
2. Introduce el **Nombre del Bot** (sin el @).
3. Guarda y pulsa **"Registrar Webhook"**.
4. Usa **"Info del Webhook"** para verificar que Telegram confirma la conexión.

> [!IMPORTANT]
> El webhook SOLO funciona con HTTPS. Asegúrate de que tu dominio tiene un certificado SSL válido.

---

## ☁️ 5. Gestión del Almacenamiento

Cada usuario tiene una **cuota de espacio en disco** para archivos adjuntos en tareas.

- Visualiza el consumo de cada usuario en **Configuración → Usuarios** (barra de progreso de disco).
- Los archivos se almacenan en `storage/app/task-attachments/` y están protegidos de acceso directo desde el navegador.
- Puedes eliminar archivos huérfanos ejecutando:

```bash
php artisan media:clean-orphans
```

---

## 🔧 6. Mantenimiento del Servidor

### Verificar estado de las colas

```bash
supervisorctl status
php artisan queue:monitor default
```

### Reiniciar trabajadores tras un despliegue

```bash
php artisan queue:restart
sudo supervisorctl restart sientiamtx-worker:*
```

### Limpiar caché tras actualización

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
npm run build
php artisan queue:restart
```

### Monitoreo del Log de Errores

```bash
tail -f storage/logs/laravel.log
```

---

## 🛡️ 7. Seguridad y Buenas Prácticas

- **Claves API**: El token de Telegram se guarda en `.env`, nunca lo incluyas en el código fuente ni en el control de versiones.
- **Actualizaciones**: Ejecuta `git pull` regularmente para recibir parches de seguridad.
- **Backups**: Programa un volcado diario de la base de datos:

```bash
mysqldump -u usuario -p sientia_mtx > backup_$(date +%Y%m%d).sql
```

- **HTTPS**: Todos los webhooks de Telegram requieren HTTPS. Usa Let's Encrypt si no tienes certificado:

```bash
certbot --nginx -d tu-dominio.com
```
