# 🛡️ Admin Manual — SientiaMTX (v1.0.0-GA)

As a **Coordinator** or **Administrator** of SientiaMTX, you have advanced tools to manage teams, monitor global progress, and maintain the system infrastructure in perfect condition.

---

## 👥 1. User Management and Security

### Roles and Hierarchy
SientiaMTX uses a strict role hierarchy to protect data integrity:
- **Global Administrator**: Access to all system configurations and user management.
- **Team Owner**: The creator of the team. Their role is protected and cannot be demoted by coordinators.
- **Coordinator**: Can manage members and tasks within their team, but **cannot** edit the global profiles of other users (email/name).
- **Member**: Collaborates on team tasks.

### Recent Security Audit
Enterprise-level security standards have been implemented:
- **Profile Protection**: Coordinators can no longer modify member emails to prevent spoofing risks.
- **File Integrity**: File uploads in Forums and Tasks are validated by team membership and disk quotas.

---

## 🏢 2. Team Management

### Disk Quotas
Each team has a disk quota configurable by the administrator:
1. Go to **Settings → Teams**.
2. Adjust the GB limit allowed for that team.
3. The system will block new uploads if the limit is reached.

### Work Groups
Create groups for bulk assignments. When assigning a task to a group, SientiaMTX automatically creates an instance for each group member.

---

## 📊 3. Monitoring and Dashboard

### Active Network
As a coordinator, you can see in real-time who is working, their geographical location (if enabled), and their current workload. This facilitates intelligent delegation based on actual availability.

### Nudges
If you detect a stalled Q1 (Critical) task, use the 🔔 button to send an immediate reminder via Telegram/Email to the person responsible.

---

## ☁️ 4. Storage Management (Purge)

To keep the server optimized, you can purge old files:
1. Go to **Settings → Storage**.
2. Choose the time period (e.g., older than 30 days).
3. Select what to purge: Telegram files, obsolete attachments, or AI logs.
4. The system will immediately free up physical disk space.

---

## 🤖 5. AI Configuration (Ax.ia)

SientiaMTX uses **Gemini** models (Google AI). As an administrator:
- Configure the global **API Key** in the `.env` file or allow each team to use its own key from its settings panel.
- We recommend the `gemini-1.5-flash` model for its balance between speed and cost.

---

## 🔧 6. Server Maintenance

### Essential Commands (CLI)

**Clean orphaned files:**
```bash
php artisan media:clean-orphans
```

**Sync disk quotas:**
```bash
php artisan disk:sync-all
```

**System Update:**
```bash
git pull origin main
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan optimize:clear
npm run build
```

---

## 🛡️ 7. Best Practices
- **Mandatory HTTPS**: Required for Telegram and Google integration.
- **Backups**: Perform a weekly dump of the database and the `storage/app/public` folder.
- **API Keys**: Never share the `.env` or Gemini keys.

---
**Sientia MTX: Security and total control for high-performance teams.**
