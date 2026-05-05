# 🤖 Telegram & Notifications Setup

SientiaMTX uses a Telegram Bot to send you daily summaries, urgent task alerts, and forum thread notifications. This guide explains how to activate it in a few simple steps.

---

## 📲 1. Activation for Members (Recommended)

If your team already has a bot configured, you only need to link your personal account to start receiving alerts. **This is all most users need to do.**

### Steps to link your account:
1. **Search for your team's bot** on Telegram (ask your coordinator for the bot's name).
2. Click **START** or send the `/start` command.
3. The bot will reply with your **Chat ID** (a long number, e.g., `123456789`).
4. In SientiaMTX, go to your **Profile → Notification Settings**.
5. Paste that number into the **"Telegram Chat ID"** field.
6. Enable the **"Receive alerts via Telegram"** option and save changes.

> [!TIP]
> You can configure how many hours in advance you prefer for your task reminders (default: 24h).

---

## 🛡️ 2. Configuration for Administrators (Advanced)

If you are the system administrator or want to set up a new bot for the global server, follow these steps:

### A. Create the Bot on Telegram
1. Message **`@BotFather`** on Telegram.
2. Use `/newbot` and follow the instructions to get your **API Token**.
3. Keep the token in a safe place.

### B. Link the Bot to SientiaMTX
1. Go to **Settings → Notifications & Telegram**.
2. Enter the **Bot Name** (without the @) and the **Token**.
3. Click **"Save"** and then **"Register Webhook"**.
4. Verify with **"Webhook Info"** that the connection is successful (requires HTTPS).

---

## 🔔 What notifications will I receive?

| Notification | When it happens |
|---|---|
| **Morning Summary** | Every morning with your tasks for the day. |
| **Q1 Alert (Critical)** | When an urgent task is near its deadline. |
| **Mentions** | When someone tags you in the forum or a comment. |
| **New Tasks** | When a public or group task is assigned to you. |

---

## 🛠️ Troubleshooting
- **The bot doesn't respond**: Make sure you are talking to the correct bot and that the administrator has registered the Webhook.
- **Messages are not arriving**: Verify that your Chat ID is correct and that the queue service (Supervisor) is active on the server.
