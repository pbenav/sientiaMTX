const express = require('express');
const cors = require('cors');
const axios = require('axios');
const qrcode = require('qrcode');
const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');

const app = express();
const PORT = process.env.PORT || 3001;
let currentWebhookUrl = process.env.LARAVEL_WEBHOOK_URL || 'http://localhost:8000/whatsapp/webhook';

// Middlewares
app.use(cors());
app.use(express.json());

// Middleware de autodetección dinámico de la dirección del webhook
app.use((req, res, next) => {
    const webhookUrl = req.query?.webhook_url || req.body?.webhook_url;
    if (webhookUrl) {
        currentWebhookUrl = webhookUrl;
    }
    next();
});

// Mapa de clientes en memoria (Multi-sesión dinámica)
const sessions = {};

// Obtiene o inicializa la sesión de un cliente específico con optimización de RAM estricta
function getSession(sessionId = 'default') {
    if (sessions[sessionId]) {
        return sessions[sessionId];
    }

    console.log(`[Multi-Sesión] Inicializando cliente de WhatsApp para sesión: ${sessionId}...`);
    
    const client = new Client({
        authStrategy: new LocalAuth({
            clientId: `sientia-mtx-${sessionId}`
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
                '--disable-gpu',
                '--disable-extensions',
                '--no-first-run',
                '--no-zygote',
                '--single-process',
                '--js-flags="--max-old-space-size=128"', // Limita la RAM interna de V8 en cada Chromium
                '--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36'
            ],
            timeout: 60000,
            protocolTimeout: 300000
        }
    });

    const sessionState = {
        client: client,
        qr: null,
        ready: false
    };

    client.on('qr', async (qr) => {
        console.log(`[Multi-Sesión QR - ${sessionId}] Generado nuevo código QR.`);
        try {
            sessionState.qr = await qrcode.toDataURL(qr);
            sessionState.ready = false;
        } catch (err) {
            console.error(`[Multi-Sesión Error - ${sessionId}] Error generando QR:`, err.message);
        }
    });

    client.on('ready', () => {
        console.log(`[Multi-Sesión - ${sessionId}] ¡Cliente listo y conectado!`);
        sessionState.ready = true;
        sessionState.qr = null;
    });

    client.on('disconnected', (reason) => {
        console.log(`[Multi-Sesión - ${sessionId}] Cliente desconectado:`, reason);
        sessionState.ready = false;
        sessionState.qr = null;
    });

    client.on('message_create', async (message) => {
        if (!message.from) return;
        
        console.log(`[Multi-Sesión Msg - ${sessionId}] De: ${message.from} (Mío: ${message.fromMe}): ${message.body}`);
        
        try {
            let authorName = 'Usuario';
            try {
                const contact = await message.getContact();
                authorName = contact.pushname || contact.name || message.author || message.from;
                if (authorName && authorName.includes('@')) {
                    authorName = authorName.split('@')[0];
                }
            } catch (err) {
                console.error('Error obteniendo contacto:', err.message);
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
                    console.error('Error descargando media:', err.message);
                }
            }

            // Enviamos el mensaje al webhook de Laravel incluyendo el sessionId
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
                mediaMimetype: mediaMimetype,
                session: sessionId
            });
            console.log(`=> Webhook enviado a Laravel para sesión ${sessionId}`);
        } catch (error) {
            console.error('Error enviando webhook a Laravel:', error.message);
        }
    });

    client.initialize().catch(err => {
        console.error(`[Multi-Sesión Error - ${sessionId}] Fallo al inicializar:`, err.message);
    });

    sessions[sessionId] = sessionState;
    return sessionState;
}

// Inicializar la sesión por defecto (retrocompatibilidad con el equipo de siempre)
getSession('default');

// --- API REST PARA LARAVEL ---

// 1. Obtener el estado actual (y el QR si es necesario) de una sesión específica
app.get('/api/status', (req, res) => {
    const sessionId = req.query.session || 'default';
    const forceInit = req.query.init === 'true';

    // OPTIMIZACIÓN PASIVA: Si la sesión no existe en memoria y no forzamos el arranque (carga pasiva del perfil),
    // devolvemos desconectado de inmediato sin inicializar Puppeteer para no gastar RAM.
    if (!sessions[sessionId] && sessionId !== 'default' && !forceInit) {
        return res.json({
            ready: false,
            qr: null
        });
    }

    const session = getSession(sessionId);
    res.json({
        ready: session.ready,
        qr: session.qr
    });
});

// 2. Enviar un mensaje desde Laravel hacia WhatsApp usando una sesión específica
app.post('/api/send', async (req, res) => {
    const sessionId = req.body.session || req.query.session || 'default';
    const session = getSession(sessionId);

    if (!session.ready) {
        return res.status(503).json({ success: false, error: 'El cliente de WhatsApp no está conectado todavía.' });
    }

    const { phone, message, mediaBase64, mediaMimetype, mediaFilename } = req.body;

    if (!phone) {
        return res.status(400).json({ success: false, error: 'Se requiere el teléfono (phone).' });
    }

    try {
        let chatId = phone;
        if (!chatId.includes('@c.us') && !chatId.includes('@g.us') && !chatId.includes('@s.whatsapp.net')) {
            chatId = `${phone}@c.us`;
        }
        
        let response;
        if (mediaBase64 && mediaMimetype) {
            const media = new MessageMedia(mediaMimetype, mediaBase64, mediaFilename || 'file');
            response = await session.client.sendMessage(chatId, media, { caption: message || '' });
        } else {
            response = await session.client.sendMessage(chatId, message || '');
        }
        
        res.json({ success: true, messageId: response.id.id });
    } catch (error) {
        console.error(`Error enviando mensaje para la sesión ${sessionId}:`, error);
        res.status(500).json({ success: false, error: error.message });
    }
});

// 3. Reiniciar cliente de una sesión específica (limpia sesión y genera nuevo QR)
app.post('/api/restart', async (req, res) => {
    const sessionId = req.body.session || req.query.session || 'default';
    const session = getSession(sessionId);
    
    console.log(`Reiniciando cliente de WhatsApp para sesión ${sessionId}...`);
    try {
        session.qr = null;
        session.ready = false;
        
        try {
            await session.client.destroy();
        } catch(e) {
            console.log('Error al destruir cliente:', e.message);
        }
        
        // Volvemos a inicializarlo de inmediato
        delete sessions[sessionId];
        getSession(sessionId);
        
        res.json({ success: true, message: 'Cliente reiniciando, esperando nuevo QR...' });
    } catch (error) {
        console.error(`Error reiniciando sesión ${sessionId}:`, error);
        res.status(500).json({ success: false, error: error.message });
    }
});

// Arrancamos el servidor Express
app.listen(PORT, () => {
    console.log(`Servidor puente de WhatsApp MULTI-SESIÓN OPTIMIZADO corriendo en el puerto ${PORT}`);
    console.log(`Webhook dinámico (autodetectado), por defecto: ${currentWebhookUrl}`);
});
