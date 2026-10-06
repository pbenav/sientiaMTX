# 🖥️ OnlyOffice Document Server — Installation & Configuration Guide

This guide documents the installation and configuration of OnlyOffice Document Server on Ubuntu/Debian within a Proxmox LXC container, integrated with SientiaMTX.

---

## 🏗️ Infrastructure Parameters

| Parameter | Value |
|---|---|
| **Container IP** | `192.168.10.152` |
| **Operating System** | Ubuntu 22.04 LTS (Unprivileged Proxmox LXC) |
| **Hostname** | `office` |
| **Public Domain** | `office.sientia.com` (Apache reverse proxy at `192.168.1.10`) |
| **OnlyOffice Version** | Document Server 9.x Community Edition |

---

## 📦 Installing OnlyOffice Document Server

```bash
# Install system dependencies
apt update && apt install -y curl gnupg2

# Add official OnlyOffice GPG signing key and repository
curl -fsSL https://download.onlyoffice.com/GPG-KEY-ONLYOFFICE | gpg --dearmor -o /usr/share/keyrings/onlyoffice.gpg
echo "deb [signed-by=/usr/share/keyrings/onlyoffice.gpg] https://download.onlyoffice.com/repo/debian squeeze main" \
    | tee /etc/apt/sources.list.d/onlyoffice.list

# Install packages
apt update && apt install -y onlyoffice-documentserver
```

The package manager configures:
- **PostgreSQL** with `onlyoffice` database and user
- **RabbitMQ** messaging broker
- **nginx** internal web server on port 80

---

## ⚙️ Main Configuration (`/etc/onlyoffice/documentserver/local.json`)

```json
{
  "services": {
    "CoAuthoring": {
      "request-filtering-agent": {
        "allowPrivateIPAddress": true,
        "allowSelfSignedCertificates": true
      },
      "sql": {
        "type": "postgres",
        "dbHost": "localhost",
        "dbPort": "5432",
        "dbName": "onlyoffice",
        "dbUser": "onlyoffice",
        "dbPass": "onlyoffice"
      },
      "token": {
        "enable": {
          "request": {
            "inbox": true,
            "outbox": true
          },
          "browser": true
        },
        "inbox": {
          "header": "Authorization"
        },
        "outbox": {
          "header": "Authorization"
        }
      },
      "secret": {
        "browser": {
          "string": "YOUR_SHARED_SECRET_HERE"
        },
        "inbox": {
          "string": "YOUR_SHARED_SECRET_HERE"
        },
        "outbox": {
          "string": "YOUR_SHARED_SECRET_HERE"
        },
        "session": {
          "string": "YOUR_SHARED_SECRET_HERE"
        }
      }
    }
  }
}
```

> [!CRITICAL]
> The `secret.*.string` token must match **`ONLYOFFICE_SECRET`** in Laravel's `.env`.
> 
> **`allowPrivateIPAddress: true`** is **mandatory** to allow downloading documents directly from Laravel's LAN IP (`192.168.10.151`) without SSRF security blocks.

Validate JSON syntax prior to restarting:
```bash
python3 -m json.tool /etc/onlyoffice/documentserver/local.json
```

---

## 🗄️ PostgreSQL Database Schema Initialization

If logs report `DB table "task_result" does not exist`:

```bash
# Initialize database schema
sudo -u postgres psql -d onlyoffice -f /var/www/onlyoffice/documentserver/server/schema/postgresql/createdb.sql

# Grant required permissions to the onlyoffice user
sudo -u postgres psql -d onlyoffice -c "ALTER SCHEMA public OWNER TO onlyoffice;"
sudo -u postgres psql -d onlyoffice -c "GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO onlyoffice;"
sudo -u postgres psql -d onlyoffice -c "GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO onlyoffice;"
```

---

## 🔧 Service Management

```bash
# Restart all OnlyOffice services
systemctl restart nginx rabbitmq-server postgresql ds-converter ds-docservice ds-metrics

# Monitor logs in real time
journalctl -u ds-docservice -f
journalctl -u ds-converter -f
```

---

## 🌐 Apache Reverse Proxy Configuration (`office.sientia.com`)

Ensure WebSocket upgrades are proxied properly:

```apache
<VirtualHost *:443>
    ServerName office.sientia.com

    SSLEngine on
    SSLCertificateFile    /etc/letsencrypt/live/office.sientia.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/office.sientia.com/privkey.pem

    ProxyPreserveHost On

    # ── WebSocket Proxying (Required for live real-time editing) ──
    RewriteEngine On
    RewriteCond %{HTTP:Upgrade} websocket [NC]
    RewriteCond %{HTTP:Connection} upgrade [NC]
    RewriteRule ^/?(.*) "ws://192.168.10.152/$1" [P,L]

    # ── Standard HTTP ──
    ProxyPass        / http://192.168.10.152/
    ProxyPassReverse / http://192.168.10.152/
</VirtualHost>
```

---

## 📋 Post-Installation Verification Checklist

- [ ] `systemctl status ds-docservice` is active (running)
- [ ] `systemctl status ds-converter` is active (running)
- [ ] `systemctl status rabbitmq-server` is active (running)
- [ ] `systemctl status postgresql` is active (running)
- [ ] Document opens in SientiaMTX with live collaborative editing working as expected
