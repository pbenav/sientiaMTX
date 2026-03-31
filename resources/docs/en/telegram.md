# 🤖 Telegram & Notifications Setup

SientiaMTX integrates a powerful notification system that uses a Telegram Bot to send you daily summaries, urgent task alerts, and important milestone notifications — all in real time and tailored to your schedule.

---

## 1. Create Your Bot on Telegram

### Step-by-Step with BotFather

1. Open Telegram and search for **`@BotFather`** (the official bot manager).
2. Send the command `/newbot`.
3. Choose a **display name** for your bot (e.g., *My Company Notifier*).
4. Choose a **username** ending in `bot` (e.g., *mycompany_mtx_bot*).
5. BotFather will reply with your **API Token**:
   ```
   123456789:ABCDefGhIJKlmNoPqRsTuVwXyZ
   ```
   **Keep this token secret and never share it publicly.**

---

## 2. Connect the Bot to SientiaMTX

As an administrator:

1. Go to **Settings → Notifications & Telegram**.
2. Enter the **Bot Name** (without the `@` symbol).
3. Paste the **Token** from BotFather.
4. Click **"Save Settings"**.
5. Click **"Register Webhook in Telegram"**.
   - You'll see a confirmation message if everything is connected.
   - Use **"Webhook Info"** to verify that Telegram has received your webhook URL.

> [!IMPORTANT]
> Your site **must have HTTPS** enabled. Telegram will reject webhook registrations on plain HTTP domains. Use Let's Encrypt if needed: `certbot --nginx -d your-domain.com`

---

## 3. Individual User Activation

Each user must link their own Telegram account:

1. Find your company's bot in Telegram (by its username) and click **START** or send `/start`.
2. The bot will reply with your **Chat ID** (a numeric code, e.g. `987654321`).
3. In SientiaMTX, go to **Profile → Notification Settings**.
4. Paste the Chat ID in the **"Telegram Chat ID"** field.
5. Enable **"Receive alerts via Telegram"**.
6. Set your preferred **advance notice hours** (default: 24h before deadline).

---

## 4. Types of Notifications

| Notification | Trigger | Audience |
|---|---|---|
| **Morning Summary** | Daily schedule | All opted-in users |
| **Urgent Alert** | Q1 task near deadline | Task owner + assignees |
| **Milestone Reached** | 50%, 75%, 100% completion | Team coordinator |
| **Task Blocked** | Member marks task as blocked | Team coordinator |

---

## 5. How the Queue System Works

SientiaMTX uses **Laravel Queues** to send notifications asynchronously. This means:
- Notifications are stored in the database first.
- **Supervisor** processes them in the background without slowing down the app.
- If a notification fails (e.g., bad Telegram token), it retries up to 3 times.

To check the queue health:

```bash
supervisorctl status
php artisan queue:monitor default
```

---

## 🛠️ Troubleshooting

### Bot doesn't respond to `/start`
- Verify the bot exists by searching its username in Telegram.
- Confirm the token in Settings matches the one from BotFather.

### Webhook registration fails
- Ensure your domain is accessible via HTTPS on port 443.
- Check `storage/logs/laravel.log` for error details.
- Run `php artisan optimize:clear` and try registering again.

### Notifications not arriving
1. Verify the user has a valid Chat ID set in their profile.
2. Confirm Supervisor is running: `supervisorctl status`
3. Check the worker log: `tail -f storage/logs/worker.log`
4. Manually trigger the check: `php artisan tasks:check-urgent`
