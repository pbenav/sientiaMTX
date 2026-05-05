# 🤖 Configuración de Telegram y Notificaciones

SientiaMTX utiliza un Bot de Telegram para enviarte resúmenes diarios, alertas de tareas urgentes y avisos de hilos en los foros. Esta guía te explica cómo activarlo en pocos pasos.

---

## 📲 1. Activación para Miembros (Recomendado)

Si tu equipo ya tiene un bot configurado, solo necesitas vincular tu cuenta personal para empezar a recibir avisos. **Esto es lo único que necesitan hacer la mayoría de los usuarios.**

### Pasos para vincular tu cuenta:
1. **Busca el bot de tu equipo** en Telegram (pregunta a tu coordinador por el nombre del bot).
2. Haz clic en **INICIAR** (START) o envía el comando `/start`.
3. El bot te responderá con tu **ID de Chat** (un número largo, ej: `123456789`).
4. En SientiaMTX, ve a tu **Perfil → Configuración de Notificaciones**.
5. Pega ese número en el campo **"Telegram Chat ID"**.
6. Activa la opción **"Recibir avisos por Telegram"** y guarda los cambios.

> [!TIP]
> Puedes configurar cuántas horas de antelación prefieres para tus recordatorios de tareas (por defecto: 24h).

---

## 🛡️ 2. Configuración para Administradores (Avanzado)

Si eres el administrador del sistema o quieres configurar un bot nuevo para el servidor global, sigue estos pasos:

### A. Crear el Bot en Telegram
1. Habla con **`@BotFather`** en Telegram.
2. Usa `/newbot` y sigue las instrucciones para obtener tu **API Token**.
3. Guarda el token en un lugar seguro.

### B. Vincular el Bot con SientiaMTX
1. Entra en **Configuración → Notificaciones y Telegram**.
2. Introduce el **Nombre del Bot** (sin la @) y el **Token**.
3. Pulsa **"Guardar"** y luego **"Registrar Webhook"**.
4. Verifica con **"Info del Webhook"** que la conexión sea exitosa (requiere HTTPS).

---

## 🔔 ¿Qué notificaciones recibiré?

| Notificación | Cuándo ocurre |
|---|---|
| **Resumen Matutino** | Cada mañana con tus tareas del día. |
| **Alerta Q1 (Crítica)** | Cuando una tarea urgente está próxima a vencer. |
| **Menciones** | Cuando alguien te etiqueta en el foro o un comentario. |
| **Nuevas Tareas** | Cuando se te asigna una tarea pública o de grupo. |

---

## 🛠️ Solución de Problemas
- **El bot no responde**: Asegúrate de estar hablando con el bot correcto y que el administrador haya registrado el Webhook.
- **No llegan los mensajes**: Verifica que tu Chat ID sea el correcto y que el servicio de colas (Supervisor) esté activo en el servidor.
