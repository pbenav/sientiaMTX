# 🟢 WhatsApp Web Integration

SientiaMTX allows full bidirectional integration with WhatsApp through a bridge based on `whatsapp-web.js`. You can send and receive messages, photos, audios, and stickers directly from the team's widget.

---

## 📲 1. How does WhatsApp Participation Work? (User Flow)

In SientiaMTX, there are two ways to participate in WhatsApp chats depending on the account type and team settings:

### 🟢 A. Standard Participation (No Setup Required)
If your work team is already connected to a WhatsApp group (previously configured by the Administrator):
* **You do not need to do anything to participate.** You do not need to scan any QR code or link your personal phone.
* Simply open the team chat widget in SientiaMTX to read incoming messages and write responses in real-time.
* The SientiaMTX server will channel all your messages through the team's primary WhatsApp account in a completely transparent way.

### 👑 B. Custom Configuration (Premium Accounts)
If you have a **Premium account** and want to connect your own private or business WhatsApp number to integrate it with your tasks and independent teams, you must link your device directly:

#### Steps to connect your Premium/Private account:
1. In SientiaMTX, go to your **Profile → Chat Integrations**.
2. Scroll down to the **WhatsApp Bridge** section.
3. You will see a **QR Code** that generates automatically (real-time polling).
4. Open WhatsApp on your mobile phone, go to **Linked Devices → Link a Device** and scan the code on the screen.
5. The status will automatically change to **"WhatsApp Connected!"** and you will see your profile picture.

> [!IMPORTANT]
> The system uses a persistent session. You will only need to scan it again if you log out manually or the WhatsApp token expires.

---

## 👥 2. Link a Team to a Chat

Once connected, you must specify which WhatsApp chat each SientiaMTX team should "listen" to.

1. Go to the team edition: **Teams → Edit**.
2. Find the field **"CHAT ID/WHATSAPP NUMBER"**.
3. **For personal numbers**: Enter the number in international format (e.g., `34600123456`).
4. **For groups**: Enter the technical ID of the group (e.g., `1234567890-1415919161@g.us`).
5. Save the changes. The WhatsApp widget will automatically appear on the panel of that team.

---

## 🛡️ 3. Admin Configuration

The WhatsApp subsystem requires the bridge service (Node.js) to be running on the server.

### A. Running the Bridge
1. Go to the service folder: `cd whatsapp-service`.
2. Install the dependencies: `npm install`.
3. Start the server: `node server.js` (it is recommended to use **PM2** so it is always active).
4. The service runs by default on **port 3001**.

### B. Security and Webhooks
The Node.js bridge sends incoming messages to Laravel via a Webhook to `http://localhost:8000/whatsapp/webhook`. Make sure this URL is accessible from the Node service.

---

## 📸 Media Support and Disk Quota

| Content Type | How it Works |
|---|---|
| **Text** | Instant bidirectional synchronization. |
| **Images** | Supports JPG, PNG, and WebP. You can paste them directly (`Ctrl+V`). |
| **Voice Notes** | Direct recording from the browser (WebM/OGG format). |
| **Stickers** | Receives stickers and displays them inside the chat widget. |

> [!WARNING]
> Media files consume **team disk quota**. If the team runs out of space, the files won't download locally, and only the text of the message will be displayed.

---

## 🛠️ Troubleshooting
- **The QR does not appear**: Make sure that the Node service is running (`ps aux | grep node`) and that port 3001 is free.
- **Messages do not reach the widget**: Check that the `whatsapp_chat_id` in the team settings matches exactly the one shown in the `server.js` logs when you receive a message.
- **"Execution context destroyed" Error**: This is a common Puppeteer navigation error. The bridge automatically restarts and usually recovers within a few seconds.
