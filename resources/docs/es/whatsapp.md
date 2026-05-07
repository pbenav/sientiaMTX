# 🟢 Integración de WhatsApp Web

SientiaMTX permite una integración bidireccional completa con WhatsApp a través de un puente (bridge) basado en `whatsapp-web.js`. Puedes enviar y recibir mensajes, fotos, audios y stickers directamente desde el widget del equipo.

---

## 📲 1. Vinculación del Dispositivo (Usuarios)

Para empezar a usar WhatsApp, primero debes vincular tu cuenta personal (o una de empresa) con el servidor de SientiaMTX.

### Pasos para conectar:
1. En SientiaMTX, ve a tu **Perfil → Integraciones de Chat**.
2. Desplázate hasta la sección **Puente de WhatsApp**.
3. Verás un **Código QR** que se genera automáticamente (polling en tiempo real).
4. Abre WhatsApp en tu móvil, ve a **Dispositivos vinculados → Vincular un dispositivo** y escanea el código de la pantalla.
5. El estado cambiará a **"¡WhatsApp Conectado!"** y verás tu foto de perfil.

> [!IMPORTANT]
> El sistema utiliza una sesión persistente. Solo tendrás que volver a escanearlo si cierras la sesión manualmente o el token de WhatsApp caduca.

---

## 👥 2. Vincular un Equipo a un Chat

Una vez conectado, debes indicar a qué chat de WhatsApp debe "escuchar" cada equipo de SientiaMTX.

1. Entra en la edición de un equipo: **Equipos → Editar**.
2. Busca el campo **"ID DE CHAT/NÚMERO DE WHATSAPP"**.
3. **Para números personales**: Pon el número en formato internacional (ej: `34600123456`).
4. **Para grupos**: Debes poner el ID técnico del grupo (ej: `1234567890-1415919161@g.us`).
5. Guarda los cambios. El widget de WhatsApp aparecerá automáticamente en el panel de ese equipo.

---

## 🛡️ 3. Configuración para Administradores

El subsistema de WhatsApp requiere que el servicio puente (Node.js) esté corriendo en el servidor.

### A. Puesta en marcha del Bridge
1. Accede a la carpeta del servicio: `cd whatsapp-service`.
2. Instala las dependencias: `npm install`.
3. Levanta el servidor: `node server.js` (se recomienda usar **PM2** para que siempre esté activo).
4. El servicio corre por defecto en el **puerto 3001**.

### B. Seguridad y Webhooks
El bridge de Node.js envía los mensajes entrantes a Laravel mediante un Webhook a la dirección `http://localhost:8000/whatsapp/webhook`. Asegúrate de que esta URL sea accesible desde el servicio de Node.

---

## 📸 Soporte Multimedia y Cuotas

| Tipo de Contenido | Funcionamiento |
|---|---|
| **Texto** | Sincronización instantánea bidireccional. |
| **Imágenes** | Soporta JPG, PNG y WebP. Se pueden pegar directamente (`Ctrl+V`). |
| **Notas de Voz** | Grabación directa desde el navegador (formato WebM/OGG). |
| **Stickers** | Recepción de stickers y visualización en el chat. |

> [!WARNING]
> Los archivos multimedia consumen **cuota de disco** del equipo. Si el equipo se queda sin espacio, los archivos no se descargarán localmente y solo se mostrará el texto del mensaje.

---

## 🛠️ Solución de Problemas
- **El QR no aparece**: Verifica que el servicio de Node está corriendo (`ps aux | grep node`) y que el puerto 3001 está libre.
- **Los mensajes no llegan al widget**: Comprueba que el `whatsapp_chat_id` en los ajustes del equipo sea exactamente el mismo que aparece en los logs de `server.js` cuando recibes un mensaje.
- **Error "Execution context destroyed"**: Es un error común de Puppeteer al navegar. El bridge se reinicia solo y suele recuperarse en un par de segundos.
