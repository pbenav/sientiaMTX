# 🛡️ Administrator Manual — SientiaMTX

As a **Coordinator** or **Administrator** of SientiaMTX, you have advanced tools to manage teams, oversee global progress, and keep the system infrastructure running smoothly.

---

## 👥 1. User Management

### Creating New Users
Go to **Settings → Users → Create User**:
1. Enter name, email, and initial password.
2. Enable **"Is Administrator"** if they need access to global settings.
3. The user will receive a welcome email (if the mail server is configured).

### System Roles

| Role | Permissions |
|---|---|
| **Administrator** | Full access: settings, users, notifications, Telegram |
| **Coordinator** | Manages their team: creates, edits, and reassigns others' tasks |
| **Member** | Creates their own tasks and collaborates on team's public tasks |

### Deactivating or Deleting Users
In the user list, use the action buttons on each row. Deleting a user does not delete their tasks; they are automatically reassigned to the team administrator.

---

## 🏢 2. Team Management

Each team in SientiaMTX is an **independent workspace** with its own tasks, boards, and members.

### Creating a Team
From the main dashboard → **"Create New Team"**:
- Name, description, and cover image.
- The team creator automatically becomes its **Coordinator**.

### Adding Members
Inside the team → **Members → Invite**:
- Search for the user by email (must already be registered).
- Assign their role: Member or Coordinator.

### Groups Within a Team
Create groups (departments, areas) to enable bulk task assignment:
1. Inside the team → **Members → Create Group**.
2. When creating a task, select an entire group in the "Assignees" selector.

---

## 📊 3. Task Oversight

As a coordinator, you have full visibility over:
- **Team Dashboard**: Global progress chart, tasks by status, performance per member.
- **Reassign Tasks**: If a member leaves or is unavailable, reassign their tasks from the list view.
- **Send a Nudge**: If a task is blocked or hasn't been updated, the 🔔 button sends a reminder to the responsible person.

### Viewing Private Tasks

> [!WARNING]
> By design, **coordinators CANNOT see private tasks** belonging to other members. Only the task owner can see them. This restriction is intentional and protects worker privacy.

---

## ⚙️ 4. Global Settings (Administrators Only)

### Email (SMTP)
In **Settings → Mail**:
- Configure SMTP server (host, port, credentials).
- Use **"Send Test Email"** to verify the configuration before saving.

### Notifications & Telegram
In the same Settings section:
1. Enter the **Bot Token** (from @BotFather).
2. Enter the **Bot Name** (without the `@` symbol).
3. Save and click **"Register Webhook"**.
4. Use **"Webhook Info"** to verify Telegram confirms the connection.

> [!IMPORTANT]
> The webhook ONLY works with HTTPS. Make sure your domain has a valid SSL certificate.

---

## ☁️ 5. Storage Management

Each user has a **disk space quota** for task attachments.
- View each user's consumption in **Settings → Users** (disk usage bar).
- Files are stored in `storage/app/task-attachments/` and are protected from direct browser access.
- Remove orphaned files with:

```bash
php artisan media:clean-orphans
```

---

## 🔧 6. Server Maintenance

### Check Queue Status

```bash
supervisorctl status
php artisan queue:monitor default
```

### Restart Workers After Deployment

```bash
php artisan queue:restart
sudo supervisorctl restart sientiamtx-worker:*
```

### Full Deployment Process

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
npm run build
php artisan queue:restart
```

### Monitor Error Logs

```bash
tail -f storage/logs/laravel.log
```

---

## 🛡️ 7. Security Best Practices

- **API Keys**: The Telegram token is stored in `.env` — never commit it to version control.
- **Updates**: Run `git pull` regularly to receive security patches.
- **Backups**: Schedule a daily database dump:

```bash
mysqldump -u user -p sientia_mtx > backup_$(date +%Y%m%d).sql
```

- **HTTPS**: All Telegram webhooks require HTTPS. Use Let's Encrypt:

```bash
certbot --nginx -d your-domain.com
```

- **Permissions**: Keep `storage/` owned by `www-data`:

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```
