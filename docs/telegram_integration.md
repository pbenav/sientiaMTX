# Guía de Instalación: Integración de Telegram Chat en Sientia MTX

Esta guía detalla los pasos necesarios para habilitar la funcionalidad de Chat de Telegram por Equipos en cualquier instalación nueva o existente de **Sientia MTX**.

---

## 1. Requisitos Previos

1.  **Bot de Telegram:** Debes haber creado un bot en Telegram usando `@BotFather`.
2.  **Token:** Necesitas el `HTTP API Token` proporcionado por BotFather.
3.  **Certificado SSL / HTTPS:** Telegram exige que tu servidor reciba los Webhooks a través de una conexión segura (HTTPS). No funcionará en local sin túneles como Ngrok.

## 2. Configuración del Servidor (.env)

En el directorio raíz de tu proyecto, edita el archivo `.env` y añade la siguiente variable con el token de tu bot:

```env
TELEGRAM_BOT_TOKEN="123456789:ABCDefghIJKLmnopQRSTuvwxyz"
```

*Después de hacer esto, recuerda limpiar la caché de configuración de Laravel si estás en producción:*
```bash
php artisan config:clear
```

## 3. Base de Datos

Asegúrate de ejecutar las migraciones anexas a la funcionalidad del chat de Telegram. Esto creará la columna  `telegram_chat_id` en la tabla de equipos, y la nueva tabla `telegram_messages` para el histórico persistente de los chats.

```bash
php artisan migrate
```

## 4. Registrar el Webhook de Telegram

Telegram necesita saber a qué URL enviar los mensajes entrantes. Debes registrar el Webhook ejecutando lo siguiente en el terminal de tu servidor (`php artisan tinker`):

```php
Illuminate\Support\Facades\Http::post('https://api.telegram.org/bot'.env('TELEGRAM_BOT_TOKEN').'/setWebhook', [
    'url' => 'https://tudominio.com/telegram/webhook'
]);
```
*Si la respuesta tiene `"ok": true`, ¡el webhook está configurado!*

## 5. Instrucciones para Equipos

Usuarios **Propietarios o Administradores** de un equipo:

1.  Crear un Grupo en Telegram para la comunicación de su equipo.
2.  Añadir el bot (ej. `@SientiaBot`) al nuevo grupo y promoverlo a **Administrador**.
3.  Escribir `/vincular` dentro del grupo de Telegram. El bot responderá devolviendo un número ID (-100...).
4.  Ir a la plataforma web de Sientia MTX, entrar a las **Opciones (Editar Equipo)**.
5.  Pegar el número ID en la casilla *Telegram Chat ID* y Guardar.
