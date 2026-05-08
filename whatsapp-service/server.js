const express = require('express');
const cors = require('cors');
const axios = require('axios');
const qrcode = require('qrcode');
const fs = require('fs');
const path = require('path');
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
            remotePath: 'https://raw.githubusercontent.com/wppconnect-team/wa-version/main/html/2.3000.1017577156-alpha.html'
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
                '--disable-accelerated-2d-canvas',
                '--disable-background-networking',
                '--disable-background-timer-throttling',
                '--disable-backgrounding-occluded-windows',
                '--disable-renderer-backgrounding',
                '--mute-audio',
                '--no-pings',
                '--aggressive-cache-discard',
                '--disable-ipc-flooding-protection',
                '--js-flags="--max-old-space-size=256"', // Reduce a 256MB el límite para cada pestaña, ideal para servidores con poca RAM
                '--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36'
            ],
            timeout: 60000,
            protocolTimeout: 300000
        }
    });

    const sessionState = {
        client: client,
        qr: null,
        ready: false,
        authenticated: false
    };

    // Temporizador de auto-sleep por inactividad
    let inactivityTimeout = null;
    const resetInactivityTimer = () => {
        return; // Desactivar el auto-sleep para mantener el canal conectado 24/7 y evitar desvinculaciones por reconexiones repetitivas
        if (sessionId === 'default') return; // La sesión por defecto nunca se duerme de forma activa
        if (inactivityTimeout) clearTimeout(inactivityTimeout);
        inactivityTimeout = setTimeout(async () => {
            console.log(`[Auto-Sleep] Durmiendo sesión de equipo inactiva: ${sessionId} para liberar CPU/RAM.`);
            try {
                if (sessions[sessionId]) {
                    const clientToDestroy = sessions[sessionId].client;
                    delete sessions[sessionId];
                    await clientToDestroy.destroy();
                }
            } catch (err) {
                console.error(`[Auto-Sleep Error - ${sessionId}] Error al dormir la sesión:`, err.message);
            }
        }, 1200000); // 20 minutos de inactividad
    };

    // Inicializar el temporizador de inactividad
    resetInactivityTimer();

    client.on('qr', async (qr) => {
        resetInactivityTimer();
        console.log(`[Multi-Sesión QR - ${sessionId}] Generado nuevo código QR.`);
        try {
            sessionState.qr = await qrcode.toDataURL(qr);
            sessionState.ready = false;
        } catch (err) {
            console.error(`[Multi-Sesión Error - ${sessionId}] Error generando QR:`, err.message);
        }
    });

    client.on('authenticated', () => {
        resetInactivityTimer();
        console.log(`[Multi-Sesión - ${sessionId}] Autenticado con éxito.`);
        sessionState.authenticated = true;
        sessionState.qr = null;
    });

    client.on('ready', () => {
        resetInactivityTimer();
        console.log(`[Multi-Sesión - ${sessionId}] ¡Cliente listo y conectado!`);
        sessionState.ready = true;
        sessionState.authenticated = true;
        sessionState.qr = null;
    });

    client.on('disconnected', (reason) => {
        console.log(`[Multi-Sesión - ${sessionId}] Cliente desconectado:`, reason);
        sessionState.ready = false;
        sessionState.authenticated = false;
        sessionState.qr = null;
        if (inactivityTimeout) clearTimeout(inactivityTimeout);
    });

    client.on('message_create', async (message) => {
        resetInactivityTimer();
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

    // Comprobar si existe la carpeta física de la sesión en disco
    const sessionDir = path.join(__dirname, '.wwebjs_auth', `session-sientia-mtx-${sessionId}`);
    const hasSavedSession = fs.existsSync(sessionDir);

    if (!sessions[sessionId] && sessionId !== 'default') {
        // Si no está en memoria pero existe en disco, ¡la levantamos automáticamente en segundo plano de forma transparente!
        if (hasSavedSession) {
            console.log(`[Auto-Reconexión] Detectadas credenciales en disco para sesión: ${sessionId}. Reconectando en segundo plano...`);
            getSession(sessionId);
            
            return res.json({
                ready: false,
                authenticated: true,
                qr: null,
                connecting: true
            });
        }

        // Si no hay sesión en memoria ni en disco, y no forzamos, devolvemos desvinculado
        if (!forceInit) {
            return res.json({
                ready: false,
                authenticated: false,
                qr: null
            });
        }
    }

    const session = getSession(sessionId);
    res.json({
        ready: session.ready,
        authenticated: session.authenticated || hasSavedSession || false,
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

        // Borrar la carpeta física de credenciales en disco para forzar un QR limpio
        try {
            const sessionDir = path.join(__dirname, '.wwebjs_auth', `session-sientia-mtx-${sessionId}`);
            if (fs.existsSync(sessionDir)) {
                fs.rmSync(sessionDir, { recursive: true, force: true });
                console.log(`[LocalAuth] Borrada carpeta física de credenciales tras desvinculación explícita: ${sessionId}`);
            }
        } catch (err) {
            console.error('Error borrando sesión física:', err.message);
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

// 4. Sincronizar historial reciente de un chat específico (retroactividad tras desconexión)
app.post('/api/sync', async (req, res) => {
    const sessionId = req.body.session || req.query.session || 'default';
    
    if (!sessions[sessionId]) {
        return res.status(503).json({ success: false, error: 'La sesión de WhatsApp solicitada no está activa o inicializada.' });
    }
    
    const session = sessions[sessionId];

    if (!session.ready) {
        return res.status(503).json({ success: false, error: 'El cliente de WhatsApp no está conectado todavía.' });
    }

    const { phone, limit = 50 } = req.body;

    if (!phone) {
        return res.status(400).json({ success: false, error: 'Se requiere el teléfono o grupo (phone).' });
    }

    try {
        let chatId = phone;
        if (!chatId.includes('@c.us') && !chatId.includes('@g.us') && !chatId.includes('@s.whatsapp.net')) {
            chatId = `${phone}@c.us`;
        }

        console.log(`[Sincronización WhatsApp] Iniciando recuperación de hasta ${limit} mensajes de ${chatId}...`);
        const chat = await session.client.getChatById(chatId);
        const messages = await chat.fetchMessages({ limit: parseInt(limit) });

        let processedCount = 0;
        // Procesamos los mensajes recuperados
        for (const msg of messages) {
            let authorName = 'Usuario';
            try {
                const contact = await msg.getContact();
                authorName = contact.pushname || contact.name || msg.author || msg.from;
                if (authorName && authorName.includes('@')) {
                    authorName = authorName.split('@')[0];
                }
            } catch (e) {}

            let mediaData = null;
            let mediaMimetype = null;
            if (msg.hasMedia) {
                try {
                    const media = await msg.downloadMedia();
                    if (media) {
                        mediaData = media.data;
                        mediaMimetype = media.mimetype;
                    }
                } catch (e) {}
            }

            try {
                await axios.post(currentWebhookUrl, {
                    id: msg.id.id,
                    from: msg.from,
                    to: msg.to,
                    body: msg.body,
                    type: msg.type,
                    timestamp: msg.timestamp,
                    fromMe: msg.fromMe,
                    author: authorName,
                    mediaData: mediaData,
                    mediaMimetype: mediaMimetype,
                    session: sessionId
                });
                processedCount++;
            } catch (err) {
                // Mensajes duplicados o fallos de conexión aislados se ignoran de forma segura
            }
        }

        res.json({ success: true, count: messages.length, processed: processedCount });
    } catch (error) {
        console.error(`Error sincronizando chat para sesión ${sessionId}:`, error);
        res.status(500).json({ success: false, error: error.message });
    }
});

// Arrancamos el servidor Express
app.listen(PORT, () => {
    console.log(`Servidor puente de WhatsApp MULTI-SESIÓN OPTIMIZADO corriendo en el puerto ${PORT}`);
    console.log(`Webhook dinámico (autodetectado), por defecto: ${currentWebhookUrl}`);
});
