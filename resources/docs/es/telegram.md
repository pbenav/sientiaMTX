# 🤖 Configuración de Telegram y Notificaciones

SientiaMTX integra un potente sistema de notificaciones que utiliza un Bot de Telegram para enviarte resúmenes diarios, alertas de tareas urgentes y avisos de hitos importantes.

---

## 1. Crear tu Bot en Telegram

Para empezar, necesitas un **Token de API** oficial proporcionado por Telegram:

1. Busca al usuario `@BotFather` en tu aplicación de Telegram.
2. Envía el comando `/newbot`.
3. Sigue las instrucciones para darle un **nombre** (ej: *SientiaMTX Notificador*) y un **nombre de usuario** (ej: *SientiaMTX_Bot*).
4. BotFather te entregará un **Token** (ej: `123456789:ABCDefGhIJKlmNoPqRsTuVwXyZ`). **¡Guárdalo a buen recaudo!**

---

## 2. Vincular el Bot con SientiaMTX

Accede a tu panel de administración en SientiaMTX y sigue estos pasos:

1. Ve a **Configuración > Notificaciones y Telegram**.
2. Introduce el **Nombre del Bot** (sin el @) y el **Token** que te dio BotFather.
3. Haz clic en **Guardar Configuración**.
4. Verás una sección llamada **Gestión del Webhook**. Haz clic en el botón **"Registrar Webhook en Telegram"**.
   - *Nota: Asegúrate de que tu sitio tiene HTTPS activo, ya que Telegram lo requiere para enviar datos.*

---

## 3. Activación para Usuarios (Paso Individual)

Cada usuario debe vincular su propia cuenta de Telegram para recibir alertas:

1. Haz clic en el enlace a tu bot (o únete manualmente por su nombre de usuario).
2. Pulsa el botón **"INICIAR"** o envía el comando `/start`.
3. El bot te responderá con tu **ID de Chat Numérico** (ej: `987654321`).
4. Vuelve a SientiaMTX, ve a tu **Perfil > Ajustes de Notificación** y pega ese ID en el campo correspondiente.
5. Marca la casilla **"Recibir avisos por Telegram"**.

---

## 4. Tipos de Notificaciones

SientiaMTX enviará automáticamente:

- **Resumen Matutino**: Todas las mañanas con tus tareas del día y una frase motivacional de la IA.
- **Alertas de Urgencia**: Cuando una tarea del **Cuadrante 1 (Importante y Urgente)** se acerca a su plazo (vencimiento < 2 horas).
- **Hitos de Proyecto**: Cuando se completa un porcentaje clave (50%, 75%, 100%) de una tarea colaborativa.
- **Bloqueos**: Si un colaborador marca una tarea como "Bloqueada", el coordinador recibirá un aviso inmediato.

---

## 🛡️ Solución de Problemas (Troubleshooting)

### El comando de alertas no se ejecuta
Si las notificaciones automáticas no se envían, verifica que tienes la **Tarea Programada (Cron)** activa en tu servidor:
```bash
* * * * * cd /var/www/sientiaMTX && php artisan schedule:run >> /dev/null 2>&1
```

### El bot no responde
1. Verifica que el Webhook esté registrado (puedes verlo en el botón "Info Webhook").
2. Asegúrate de que el puerto 443 del servidor está abierto para las IPs de Telegram.
