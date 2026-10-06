# 🛡️ Administrator Manual — SientiaMTX (v1.2.0)

This manual is intended for **Team Coordinators** and **Global System Administrators**. It covers advanced system configuration, infrastructure requirements, enterprise security policies, and recurring CLI maintenance operations.

---

## 👥 1. User Management & Security Architecture

### Role Hierarchy & Privilege Separation
SientiaMTX enforces strict role-based access control (RBAC):
- **Global Administrator (`is_admin`)**: Full access to system settings, team provisioning, custom domains, ENS security policies, and global audit trails.
- **Team Owner**: The team creator. Rank is protected against unauthorized removal or demotion by subordinate coordinators.
- **Coordinator**: Manages members, groups, and storage quotas within their assigned team. By design, coordinators **cannot** alter other users' global credentials or violate Deep Privacy on private files they do not belong to.
- **Member**: Standard operational collaborator.

### Strict Deep Privacy Enforcement
The security model guarantees that items marked private remain strictly confidential:
- Neither Global Administrators nor Team Coordinators possess default visibility into private dossiers or tasks unless explicitly invited as participants or creators.
- Protects human resources deliberations, legal audits, and sensitive executive decisions from unauthorized inspection.

### Multi-Factor Authentication (MFA) & ENS Compliance
SientiaMTX complies with national and European security schemes (ENS):
- Global enforcement option available under **System Settings ➔ Security**.
- Supports **TOTP (RFC 6238)** via client-side QR generation (no external API calls) and **Email Verification Codes**.
- Audit events recorded to `SecurityLog` with distributed trace headers (`X-Request-ID`).

---

## ☁️ 2. Document Server: OnlyOffice & Filerobot

### Internal LAN Architecture
To prevent hairpin NAT failures and ensure real-time collaborative editing, SientiaMTX and OnlyOffice Document Server communicate directly over the local network:

```
[User Browser] ──────HTTPS──────▶ [Reverse Proxy Apache/Nginx]
                                            │
                                            ├──▶ mtx.sientia.com (Laravel)
                                            └──▶ office.sientia.com (OnlyOffice)
                                                   ▲
     Direct Internal LAN (HTTP 192.168.10.x)       │
     Laravel 192.168.10.151 ───────────────────────┘
```

### Environment Variables (`.env`)
```env
APP_URL=https://mtx.sientia.com

# OnlyOffice Document Server
ONLYOFFICE_URL=https://office.sientia.com/
ONLYOFFICE_SECRET="your_shared_jwt_secret"
ONLYOFFICE_INTERNAL_APP_URL=http://192.168.10.151
ONLYOFFICE_INTERNAL_SERVER_URL=http://192.168.10.152
```

> [!IMPORTANT]
> Whenever modifying environment variables on production servers, always refresh Laravel caches:
> ```bash
> php artisan config:cache && php artisan cache:clear
> ```

---

## 📆 3. Citizen Appointments Module Management

Administrators maintain complete operational supervision over appointment books:
1. **Service Provisioning (`AppointmentService`)**: Set up in-person or video modalities (Sientia Meet / Google Meet), assign team members, and define slot capacities.
2. **Weekly Schedules & Calendar Blocks**: Establish baseline shifts (`AppointmentSchedule`) and define blocks (`AppointmentBlock`) for official holidays or maintenance downtime.
3. **Extraordinary Administrative Controls**:
   - Ability to backdate bookings to record unscheduled walk-in or telephone consultations.
   - Authorize extraordinary time slots with live frontend slot count calculation.
4. **GDPR Right to Erasure (Art. 17)**: From visitor management (`AppointmentVisitor`), trigger definitive anonymization of citizen records while preserving numerical KPIs for auditing.

---

## 🤖 4. Ax.ia Artificial Intelligence Engine

SientiaMTX leverages Google Gemini models with layered API key management:
- **Global Environment Key**:
  ```env
  GEMINI_API_KEY="your_google_ai_studio_api_key"
  ```
- **Team-Specific Encrypted Keys**: Workspaces can configure their own encrypted API key within team settings to isolate quota consumption.
- **Model Fallback Chain**: Defaults to `gemini-1.5-flash` or `gemini-2.0-flash`, gracefully falling back to `gemini-1.5-pro` under quota constraints.

---

## 📲 5. External Channels & Notifications

- **WhatsApp Web Bridge**: Requires administrative enablement on the user's profile (`whatsapp_enabled`). Guarded by the `EnsureWhatsappIsEnabled` middleware.
- **Telegram Bot**: Configure bot credentials in `.env` and run `php artisan telegram:setup-webhook` to initialize webhook routing.
- **Google Workspace API**: Enable Calendar and Tasks synchronization following the [Google API Setup Guide](google-setup.md).

---

## 🔧 6. Maintenance CLI Command Catalog

SientiaMTX provides specialized Artisan commands for automated administration and server maintenance:

### Workday & Time Tracking Management
```bash
# Detect and normalize workdays exceeding schedule or 10-hour hard cap
php artisan timelogs:mark-anomalous

# Dry-run mode (audit only, no changes written)
php artisan timelogs:mark-anomalous --dry-run

# Audit a specific user
php artisan timelogs:mark-anomalous --user=42
```

### Reminders & Meetings Automation
```bash
# Trigger scheduled reminders across channels (Telegram, WhatsApp, Mail, Push)
php artisan reminders:trigger

# Force trigger for a single activity during testing
php artisan reminders:trigger --activity=108

# Automatically autocomplete past meetings past grace duration
php artisan app:autocomplete-meetings

# Wake up recurring auto-programmed tasks and activities
php artisan sientia:activities-autoprogram-wakeup
```

### Storage Optimization & Pruning
```bash
# Clean up physical orphan files not referenced in database
php artisan media:clean-orphans

# Recalculate and synchronize team disk space usage
php artisan disk:sync-all

# Purge legacy chat messages according to retention policy
php artisan chat:purge-old-messages

# Empty items in recycle bin exceeding retention window
php artisan tasks:cleanup-trash
```

### Sentinel Health Monitoring
```bash
# Run automated health checks across all registered services
php artisan app:check-sentinel

# Force immediate execution bypassing intervals
php artisan app:check-sentinel --force
```

### Standard Production Deployment Workflow
```bash
cd /var/www/sientiaMTX
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build
```

---

**SientiaMTX: Uncompromising control, technical reliability, and enterprise-grade security.**
