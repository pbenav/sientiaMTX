# 📄 Integración de OnlyOffice — Servidor Laravel (sientiaMTX)

Esta guía documenta la integración completa de OnlyOffice Document Server con SientiaMTX, incluyendo la arquitectura, la configuración necesaria y los puntos clave para replicar la instalación.

---

## 🏗️ Arquitectura General

```
[Navegador del Usuario]
        │  HTTPS
        ▼
[Apache Proxy Reverso]  192.168.1.10  →  mtx.sientia.com / office.sientia.com
        │  HTTP interno
        ├──────────────────────────────────────────┐
        ▼                                          ▼
[Laravel — sientiaMTX]                  [OnlyOffice Document Server]
  192.168.10.151                           192.168.10.152
  (LXC Proxmox)                            (LXC Proxmox)
```

> **Clave arquitectónica:** OnlyOffice y Laravel se comunican **directamente por la red interna** (192.168.10.x), sin pasar por el proxy Apache. Esto evita el problema de hairpin NAT y los fallos de firma de URL.

---

## ⚙️ Variables de Entorno (`.env`)

Añade o verifica estas variables en el archivo `.env` del servidor Laravel:

```env
# URL pública de la aplicación (usada por el navegador del usuario)
APP_URL=https://mtx.sientia.com

# ── OnlyOffice ──────────────────────────────────────────────────────────────

# URL pública del servidor de OnlyOffice (usada por el navegador para cargar el editor)
ONLYOFFICE_URL=https://office.sientia.com/

# Secreto JWT compartido entre Laravel y OnlyOffice (debe ser igual en ambos)
ONLYOFFICE_SECRET=tu_secreto_aqui

# IP INTERNA de Laravel (para que OnlyOffice descargue archivos directamente por LAN)
ONLYOFFICE_INTERNAL_APP_URL=http://192.168.10.151

# IP INTERNA de OnlyOffice (para que Laravel se conecte a él directamente por LAN)
ONLYOFFICE_INTERNAL_SERVER_URL=http://192.168.10.152
```

> **¡IMPORTANTE!** Después de modificar el `.env`, ejecuta siempre:
> ```bash
> php artisan config:cache
> php artisan cache:clear
> ```
> Si no cacheas la config, Laravel puede ignorar las variables en entornos con opcache activo.

---

## 📦 Configuración (`config/onlyoffice.php`)

El archivo `config/onlyoffice.php` define los parámetros clave y los lee desde el `.env`. Este archivo es **imprescindible** para que `config:cache` funcione correctamente:

```php
<?php
return [
    'url'    => env('ONLYOFFICE_URL', 'https://office.sientia.com/'),
    'secret' => env('ONLYOFFICE_SECRET'),
    'extensions' => [
        'word'  => ['docx', 'doc', 'odt', 'rtf', 'txt'],
        'cell'  => ['xlsx', 'xls', 'ods', 'csv'],
        'slide' => ['pptx', 'ppt', 'odp'],
    ],
    // IPs internas para comunicación directa LAN (bypass del proxy Apache)
    'internal_app_url'    => env('ONLYOFFICE_INTERNAL_APP_URL'),
    'internal_server_url' => env('ONLYOFFICE_INTERNAL_SERVER_URL'),
];
```

---

## 🔑 Lógica de Seguridad de las URLs

### URL de Descarga (`/onlyoffice/download/{attachment}`)

OnlyOffice necesita descargar el archivo desde Laravel. El endpoint acepta la petición si:
1. **Viene de la IP interna de OnlyOffice** (`192.168.10.152`), ó
2. **La URL tiene una firma válida** de Laravel (para accesos eventuales externos).

La URL que se envía a OnlyOffice apunta siempre a la **IP interna** (`http://192.168.10.151/...`), asegurando que la conexión sea directa.

### URL de Callback (`/onlyoffice/callback/{attachment}`)

OnlyOffice llama a este endpoint para guardar los cambios. Está excluido del middleware CSRF en `bootstrap/app.php`:

```php
$middleware->validateCsrfTokens(except: [
    '/onlyoffice/callback/*',
]);
```

La seguridad se garantiza mediante validación del **token JWT** en la cabecera `Authorization` (configurada en el `local.json` de OnlyOffice).

---

## 🛣️ Rutas Registradas

```php
// Requiere autenticación — carga el editor en el navegador
Route::middleware(['auth'])->group(function () {
    Route::get('/attachments/{attachment}/edit', [OnlyOfficeController::class, 'edit'])
        ->name('onlyoffice.edit');
});

// Sin autenticación de sesión — accedida directamente por el servidor de OnlyOffice
Route::get('/onlyoffice/download/{attachment}', [OnlyOfficeController::class, 'downloadFile'])
    ->name('onlyoffice.download');
Route::post('/onlyoffice/callback/{attachment}', [OnlyOfficeController::class, 'callback'])
    ->name('onlyoffice.callback');
```

---

## 🚀 Procedimiento de Despliegue / Actualización

```bash
# 1. Obtener cambios del repositorio
git pull origin main

# 2. Instalar dependencias (si hay nuevas)
composer install --no-dev --optimize-autoloader

# 3. Rebuild de caches (SIEMPRE después de cambios en .env o config/)
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan cache:clear
```

---

## 🛠️ Solución de Problemas

| Síntoma | Causa probable | Solución |
|---|---|---|
| "Error al cargar el documento" | `ONLYOFFICE_INTERNAL_APP_URL` vacío o incorrecto | Verificar `.env` y ejecutar `php artisan config:cache` |
| "Firma no válida" al descargar | URL pública en vez de IP interna | Verificar que `config('onlyoffice.internal_app_url')` devuelve la IP interna |
| "No se ha podido guardar" | JWT incorrecto o cabecera no leída | Verificar que `ONLYOFFICE_SECRET` es idéntico en ambos servidores |
| `DB table "task_result" does not exist` (en OnlyOffice) | BD no inicializada | Ejecutar `createdb.sql` en el servidor de OnlyOffice (ver doc de OnlyOffice) |
| Servicios `ds-docservice` en bucle | Error de sintaxis en `local.json` | Validar JSON con `python3 -m json.tool /etc/onlyoffice/documentserver/local.json` |
