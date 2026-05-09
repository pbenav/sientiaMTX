# 🟢 Integración de WhatsApp Web

SientiaMTX permite una integración bidireccional completa con WhatsApp a través de un puente (bridge) basado en `whatsapp-web.js`. Puedes enviar y recibir mensajes, fotos, audios y stickers directamente desde el widget del equipo.

---

## 📲 1. ¿Cómo funciona la participación en WhatsApp? (Flujo de Usuarios)

En SientiaMTX existen dos formas de participar en los chats de WhatsApp según el tipo de cuenta y configuración del equipo:

### 🟢 A. Participación Estándar (Sin configurar nada)
Si tu equipo de trabajo ya está conectado a un grupo de WhatsApp (configurado previamente por el Administrador):
* **No necesitas hacer nada para participar.** No tienes que escanear ningún código QR ni vincular tu teléfono personal.
* Simplemente abre el widget del chat del equipo en SientiaMTX para leer los mensajes entrantes y escribir respuestas en tiempo real. 
* El servidor de SientiaMTX canalizará todos tus mensajes a través de la cuenta principal de WhatsApp del equipo de forma totalmente transparente.

### 👑 B. Configuración Personalizada (Cuentas Premium)
Si dispones de una **cuenta Premium** y deseas conectar tu propio número de WhatsApp particular o de empresa para integrarlo con tus tareas y equipos independientes, sí deberás realizar la vinculación directa de tu dispositivo:

#### Pasos para conectar tu cuenta Premium/Particular:
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

## 🛡️ 3. Guía de Despliegue para Administradores (Instalación desde Cero)

El subsistema de WhatsApp requiere que el servicio puente (Node.js) esté corriendo de forma ininterrumpida en el servidor. Sigue esta guía paso a paso para desplegarlo en producción de forma robusta.

### A. Requisitos de Memoria (Súper Importante)
El puente de WhatsApp levanta un navegador Chromium completo a través de Puppeteer, el cual consume entre **300 MB y 500 MB de RAM**.

> [!IMPORTANT]
> Si vas a desplegar en un VPS pequeño o en un **contenedor LXC de Proxmox** (de 1 GB o 2 GB de RAM), es altamente recomendado aumentar la memoria a **2 GB de RAM** o, en su defecto, crear un archivo **SWAP (Memoria de Intercambio) de 2 GB** en tu Linux para evitar caídas del servidor SSH o del proceso de base de datos por falta de memoria (OOM Killer):
> ```bash
> sudo fallocate -l 2G /swapfile
> sudo chmod 600 /swapfile
> sudo mkswap /swapfile
> sudo swapon /swapfile
> echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
> ```

### B. Instalación de Dependencias Gráficas de Chromium
En servidores Linux sin entorno gráfico (headless) modernos (como **Ubuntu 22.04 / 24.04**), debes instalar las bibliotecas nativas que requiere Chromium para poder abrirse.

Ejecuta el siguiente comando simplificado y compatible con sistemas modernos (incluyendo paquetes de transición `t64`):
```bash
sudo apt update
sudo apt install -y libgbm1 libnss3 libatk1.0-0 libatk-bridge2.0-0 libcups2 libdrm2 libxkbcommon0 libxcomposite1 libxdamage1 libxrandr2 libasound2t64 ca-certificates fonts-liberation xdg-utils wget libnspr4
```

> [!TIP]
> **Asistente Automático de Puppeteer:**  
> También puedes entrar a la carpeta del servicio en tu servidor y dejar que Puppeteer instale de forma automática todas las dependencias del sistema operativo actual ejecutando:
> ```bash
> npx puppeteer system-deps
> ```

### C. Puesta en Marcha del Bridge con PM2
Para asegurarte de que el puente corra permanentemente en segundo plano, se reinicie si hay fallos y arranque solo con el sistema, utiliza **PM2**:

1. **Instala PM2 de forma global en el servidor**:
   ```bash
   sudo npm install -g pm2
   ```

2. **Accede a la carpeta del servicio e instala dependencias de Node**:
   ```bash
   cd /var/www/sientiaMTX/whatsapp-service
   npm install
   ```

3. **Inicia el bridge de WhatsApp**:
   ```bash
   pm2 start server.js --name "whatsapp-bridge"
   ```

4. **Configura el arranque automático con el inicio del sistema**:
   ```bash
   pm2 startup
   # (Copia y pega la línea de comando sudo que te arroje PM2 para habilitar el servicio systemd)
   pm2 save
   ```

### D. Autodetección Dinámica del Webhook (Sin Configuración)
No necesitas configurar ninguna variable de entorno ni IP fija para el webhook de Laravel.  
El bridge de Node cuenta con un **sistema de autodetección en caliente**: cada vez que Laravel interactúa con el bridge en el puerto `3001` (por ejemplo, al cargar el perfil para ver el código QR o enviar un mensaje), Laravel le chiva dinámicamente su URL actual (`route('whatsapp.webhook')`). El bridge se actualiza en memoria al instante y redirige todos los mensajes recibidos a esa dirección exacta de forma automática.


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
