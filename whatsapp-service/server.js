const express = require('express');
const cors = require('cors');
const axios = require('axios');
const qrcode = require('qrcode');
const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');

const app = express();
const PORT = process.env.PORT || 3001; // Usamos 3001 para no pisar el 3000 si está ocupado
let currentWebhookUrl = process.env.LARAVEL_WEBHOOK_URL || 'http://localhost:8000/whatsapp/webhook';

// Middlewares
app.use(cors());
app.use(express.json());

// Middleware de autodetección dinámico de la dirección del webhook
app.use((req, res, next) => {
    const webhookUrl = req.query.webhook_url || req.body.webhook_url;
    if (webhookUrl) {
        currentWebhookUrl = webhookUrl;
    }
    next();
});

// Estado de la conexión
let currentQR = null;
let isClientReady = false;

console.log('Iniciando cliente de WhatsApp...');

// Inicializar cliente de WhatsApp con almacenamiento local de sesión y caché de versión web compatible
const client = new Client({
    authStrategy: new LocalAuth({
        clientId: "sientia-mtx"
    }),
    webVersionCache: {
        type: 'remote',
        remotePath: 'https://raw.githubusercontent.com/wppconnect-team/wa-version/main/html/2.2412.54.html'
    },
    puppeteer: {
        headless: true,
        args: [
            '--no-sandbox', 
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-accelerated-2d-canvas',
            '--no-first-run',
            '--no-zygote',
            '--disable-gpu',
            '--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36'
        ],
        timeout: 60000,
        protocolTimeout: 300000 // 5 minutos de timeout
    }
});

// Evento: Se genera un nuevo código QR
client.on('qr', async (qr) => {
    console.log('¡Nuevo código QR generado! Escanéalo para iniciar sesión.');
    try {
        // Convertimos el QR a formato imagen base64 para enviarlo fácil a la vista de Laravel
        currentQR = await qrcode.toDataURL(qr);
        isClientReady = false;
    } catch (err) {
        console.error('Error generando imagen QR:', err);
    }
});

// Evento: Cliente listo y conectado
client.on('ready', () => {
    console.log('¡Cliente de WhatsApp Web está LISTO!');
    isClientReady = true;
    currentQR = null; // Borramos el QR porque ya estamos conectados
});

// Evento: Desconexión o cierre de sesión
client.on('disconnected', (reason) => {
    console.log('Cliente desconectado:', reason);
    isClientReady = false;
    currentQR = null;
});

// Evento: Mensaje creado o recibido (Sincronización total bidireccional)
client.on('message_create', async (message) => {
    if (!message.from) return;
    
    console.log(`[Mensaje WA] De: ${message.from} (Por mí: ${message.fromMe}): ${message.body}`);
    
    try {
        let authorName = 'Usuario';
        try {
            const contact = await message.getContact();
            authorName = contact.pushname || contact.name || message.author || message.from;
            if (authorName && authorName.includes('@')) {
                authorName = authorName.split('@')[0];
            }
        } catch (err) {
            console.error('Error obteniendo contacto en Node:', err.message);
        }

        let mediaData = null;
        let mediaMimetype = null;
        if (message.hasMedia) {
            try {
                const media = await message.downloadMedia();
                if (media) {
                    mediaData = media.data;
                    mediaMimetype = media.mimetype;
                }
            } catch (err) {
                console.error('Error descargando media en Node:', err.message);
            }
        }

        // Enviamos el mensaje al webhook de Laravel
        await axios.post(currentWebhookUrl, {
            id: message.id.id,
            from: message.from,
            to: message.to,
            body: message.body,
            type: message.type,
            timestamp: message.timestamp,
            fromMe: message.fromMe,
            author: authorName,
            mediaData: mediaData,
            mediaMimetype: mediaMimetype
        });
        console.log('=> Webhook enviado a Laravel en: ' + currentWebhookUrl);
    } catch (error) {
        console.error('Error enviando webhook a Laravel:', error.message);
    }
});

// --- API REST PARA LARAVEL ---

// 1. Obtener el estado actual (y el QR si es necesario)
app.get('/api/status', (req, res) => {
    res.json({
        ready: isClientReady,
        qr: currentQR
    });
});

// 2. Enviar un mensaje desde Laravel hacia WhatsApp
app.post('/api/send', async (req, res) => {
    if (!isClientReady) {
        return res.status(503).json({ success: false, error: 'El cliente de WhatsApp no está conectado todavía.' });
    }

    const { phone, message, mediaBase64, mediaMimetype, mediaFilename } = req.body;

    if (!phone) {
        return res.status(400).json({ success: false, error: 'Se requiere el teléfono (phone).' });
    }

    try {
        // WhatsApp requiere el formato de número con el sufijo @c.us o @g.us
        let chatId = phone;
        if (!chatId.includes('@c.us') && !chatId.includes('@g.us') && !chatId.includes('@s.whatsapp.net')) {
            chatId = `${phone}@c.us`;
        }
        
        let response;
        if (mediaBase64 && mediaMimetype) {
            const media = new MessageMedia(mediaMimetype, mediaBase64, mediaFilename || 'file');
            response = await client.sendMessage(chatId, media, { caption: message || '' });
        } else {
            response = await client.sendMessage(chatId, message || '');
        }
        
        res.json({ success: true, messageId: response.id.id });
    } catch (error) {
        console.error('Error enviando mensaje:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

// 3. Reiniciar cliente (limpia sesión y genera nuevo QR)
app.post('/api/restart', async (req, res) => {
    console.log('Reiniciando cliente de WhatsApp...');
    try {
        currentQR = null;
        isClientReady = false;
        
        try {
            await client.destroy();
        } catch(e) {
            console.log('Error al destruir cliente (puede que no estuviera iniciado):', e.message);
        }
        
        // Volvemos a inicializarlo
        client.initialize();
        
        res.json({ success: true, message: 'Cliente reiniciando, esperando nuevo QR...' });
    } catch (error) {
        console.error('Error reiniciando:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});

// Arrancamos el cliente de WhatsApp
client.initialize();

// Arrancamos el servidor Express
app.listen(PORT, () => {
    console.log(`Servidor puente de WhatsApp corriendo en el puerto ${PORT}`);
    console.log(`Webhook dinámico (autodetectado), por defecto: ${currentWebhookUrl}`);
});
