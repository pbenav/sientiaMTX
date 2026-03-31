# 🚀 Guía de Instalación SientiaMTX

Bienvenido a la guía oficial de instalación de **SientiaMTX**, el sistema inteligente de gestión de tareas basado en la Matriz de Eisenhower con integración de Inteligencia Artificial y Notificaciones en Tiempo Real.

## 📋 Requisitos Previos

Antes de comenzar, asegúrate de que tu servidor cumple con los siguientes requisitos:

| Componente | Versión Mínima | Notas |
|---|---|---|
| PHP | 8.2+ | Recomendado 8.3/8.4 |
| MySQL / MariaDB | 8.0 / 10.4+ | Base de datos principal |
| Composer | 2.x | Gestor de dependencias PHP |
| Node.js + NPM | 18+ | Compilación de frontend |
| Servidor Web | Apache / Nginx | Con módulo rewrite activo |
| Supervisor | 4.x | Para colas de notificaciones |

---

## 🛠️ Pasos de Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/pbenav/sientiaMTX.git
cd sientiaMTX
```

### 2. Instalar dependencias PHP y Node

```bash
composer install --optimize-autoloader --no-dev
npm install
```

### 3. Configurar el entorno

Copia el archivo de ejemplo y genera la clave de la aplicación:

```bash
cp .env.example .env
php artisan key:generate
```

Edita el archivo `.env` con tus credenciales:

```env
APP_NAME="SientiaMTX"
APP_URL=https://tu-dominio.com

DB_DATABASE=sientia_mtx
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña

QUEUE_CONNECTION=database

TELEGRAM_BOT_TOKEN=tu_token_de_telegram
TELEGRAM_BOT_NAME=nombre_del_bot
```

### 4. Ejecutar migraciones

```bash
php artisan migrate --force
php artisan db:seed --class=RolesSeeder   # Si existe
```

### 5. Compilar activos del frontend

```bash
npm run build
```

### 6. Configurar permisos de carpetas

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

---

## ⚙️ Configuración de Producción

### Colas de Notificaciones (Supervisor)

SientiaMTX usa colas para que el envío a Telegram no bloquee la app. Crea el archivo `/etc/supervisor/conf.d/sientiamtx.conf`:

```ini
[program:sientiamtx-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/sientiaMTX/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/sientiaMTX/storage/logs/worker.log
stopwaitsecs=3600
```

Activa y recarga Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start sientiamtx-worker:*
```

### Tarea Programada (Cron)

Para que las notificaciones automáticas se ejecuten, añade esta línea al crontab de `www-data`:

```bash
crontab -u www-data -e
```

```cron
* * * * * cd /var/www/sientiaMTX && php artisan schedule:run >> /dev/null 2>&1
```

### Configuración de Nginx

Ejemplo de bloque de servidor (reemplaza `tu-dominio.com` por tu dominio real):

```nginx
server {
    listen 443 ssl;
    server_name tu-dominio.com;
    root /var/www/sientiaMTX/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## ✅ Verificación Final

Comprueba que todo está en orden:

```bash
php artisan about
php artisan queue:monitor default
supervisorctl status
```

> [!TIP]
> Una vez completada la instalación, sigue con la **Guía de Configuración de Telegram** para activar el sistema de alertas inteligentes.
