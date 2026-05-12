# 🖥️ Servidor OnlyOffice Document Server — Guía de Instalación y Configuración

Esta guía documenta la instalación y configuración del servidor de OnlyOffice Document Server sobre Ubuntu/Debian en un contenedor LXC de Proxmox, integrado con SientiaMTX.

---

## 🏗️ Infraestructura

| Parámetro | Valor |
|---|---|
| **IP del contenedor** | `192.168.10.152` |
| **Sistema Operativo** | Ubuntu 22.04 LTS (LXC no privilegiado en Proxmox) |
| **Hostname** | `office` |
| **Dominio público** | `office.sientia.com` (proxy inverso Apache en `192.168.1.10`) |
| **Versión OnlyOffice** | Document Server 9.x Community Edition |

---

## 📦 Instalación de OnlyOffice Document Server

```bash
# Instalar dependencias
apt update && apt install -y curl gnupg2

# Añadir repositorio oficial de OnlyOffice
curl -fsSL https://download.onlyoffice.com/GPG-KEY-ONLYOFFICE | gpg --dearmor -o /usr/share/keyrings/onlyoffice.gpg
echo "deb [signed-by=/usr/share/keyrings/onlyoffice.gpg] https://download.onlyoffice.com/repo/debian squeeze main" \
    | tee /etc/apt/sources.list.d/onlyoffice.list

# Instalar (responder las preguntas del instalador con los valores de BD PostgreSQL)
apt update && apt install -y onlyoffice-documentserver
```

Durante la instalación se configurarán automáticamente:
- **PostgreSQL** con base de datos `onlyoffice` y usuario `onlyoffice`
- **RabbitMQ** como broker de mensajería
- **nginx** como servidor web interno (puerto 80)

---

## ⚙️ Configuración Principal (`/etc/onlyoffice/documentserver/local.json`)

Este archivo es el núcleo de la configuración. Cualquier error de sintaxis JSON impide que arranquen los servicios.

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
          "string": "TU_SECRETO_AQUI"
        },
        "inbox": {
          "string": "TU_SECRETO_AQUI"
        },
        "outbox": {
          "string": "TU_SECRETO_AQUI"
        },
        "session": {
          "string": "TU_SECRETO_AQUI"
        }
      }
    }
  }
}
```

> **¡CRÍTICO!** El valor de `secret.*.string` debe ser **exactamente igual** a `ONLYOFFICE_SECRET` en el `.env` de Laravel. Cualquier diferencia hace fallar la autenticación JWT.

> **`allowPrivateIPAddress: true`** es **obligatorio** para que OnlyOffice pueda descargar archivos desde una IP privada (192.168.10.151). Sin esto, el servidor bloquea las peticiones a IPs internas por seguridad anti-SSRF.

### Validar la sintaxis del JSON antes de reiniciar:
```bash
python3 -m json.tool /etc/onlyoffice/documentserver/local.json
```

---

## 🗄️ Inicialización de la Base de Datos PostgreSQL

Si los logs muestran `DB table "task_result" does not exist`, la base de datos no está inicializada:

```bash
# Inicializar el esquema
sudo -u postgres psql -d onlyoffice -f /var/www/onlyoffice/documentserver/server/schema/postgresql/createdb.sql

# Otorgar permisos al usuario de la aplicación
sudo -u postgres psql -d onlyoffice -c "ALTER SCHEMA public OWNER TO onlyoffice;"
sudo -u postgres psql -d onlyoffice -c "GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO onlyoffice;"
sudo -u postgres psql -d onlyoffice -c "GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO onlyoffice;"
```

---

## 🔧 Gestión de Servicios

OnlyOffice se divide en varios servicios systemd:

```bash
# Ver estado de todos los servicios
systemctl list-units --type=service | grep ds-

# Reiniciar todos los servicios de OnlyOffice
systemctl restart nginx rabbitmq-server postgresql ds-converter ds-docservice ds-metrics

# Ver logs en tiempo real
journalctl -u ds-docservice -f
journalctl -u ds-converter -f
```

| Servicio | Función |
|---|---|
| `ds-docservice` | Motor principal del editor de documentos |
| `ds-converter` | Conversión entre formatos (docx ↔ odt, etc.) |
| `ds-metrics` | Métricas y monitorización interna |
| `rabbitmq-server` | Cola de mensajes entre servicios |
| `postgresql` | Base de datos de sesiones y cambios |

---

## 🌐 Configuración del Proxy Inverso Apache (`office.sientia.com`)

El servidor Apache en `192.168.1.10` debe tener el VirtualHost configurado con soporte WebSocket:

```apache
<VirtualHost *:443>
    ServerName office.sientia.com

    # SSL (Let's Encrypt o certificado propio)
    SSLEngine on
    SSLCertificateFile    /etc/letsencrypt/live/office.sientia.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/office.sientia.com/privkey.pem

    ProxyPreserveHost On

    # ── WebSocket (imprescindible para el editor en tiempo real) ──
    RewriteEngine On
    RewriteCond %{HTTP:Upgrade} websocket [NC]
    RewriteCond %{HTTP:Connection} upgrade [NC]
    RewriteRule ^/?(.*) "ws://192.168.10.152/$1" [P,L]

    # ── HTTP normal ──
    ProxyPass        / http://192.168.10.152/
    ProxyPassReverse / http://192.168.10.152/
</VirtualHost>
```

> **`RewriteCond %{HTTP:Upgrade} websocket`** es **obligatorio**. Sin esto, la conexión WebSocket del editor falla y el documento no carga (error 502).

---

## 🔒 Generación del Secreto JWT

Para generar un secreto seguro:

```bash
# Opción 1: openssl
openssl rand -base64 32

# Opción 2: /dev/urandom
cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1
```

El secreto generado debe ir en **los dos sitios**:
1. `/etc/onlyoffice/documentserver/local.json` → campos `secret.*.string`
2. `.env` de Laravel → variable `ONLYOFFICE_SECRET`

---

## 🛠️ Solución de Problemas

| Síntoma en logs | Causa | Solución |
|---|---|---|
| `SyntaxError: JSON5: invalid character` | Error de sintaxis en `local.json` | Validar con `python3 -m json.tool local.json` y corregir |
| `[AMQP] AggregateError [ECONNREFUSED]` | RabbitMQ no está arrancado | `systemctl start rabbitmq-server` |
| `DB table "task_result" does not exist` | BD no inicializada | Ejecutar `createdb.sql` y otorgar permisos |
| Editor carga pero no guarda | Secreto JWT incorrecto o cabecera incorrecta | Verificar que `secret` y `inbox.header`/`outbox.header` coincidan con Laravel |
| WebSocket falla (502 en polling) | Apache sin rewrite de WebSocket | Añadir las líneas `RewriteCond`/`RewriteRule` de WebSocket al VirtualHost |
| `allowPrivateIPAddress` no configurado | OnlyOffice bloquea IPs privadas | Añadir `"allowPrivateIPAddress": true` en `local.json` |

---

## 📋 Checklist de Verificación Post-Instalación

- [ ] `systemctl status ds-docservice` → `active (running)`
- [ ] `systemctl status ds-converter` → `active (running)`
- [ ] `systemctl status rabbitmq-server` → `active (running)`
- [ ] `systemctl status postgresql` → `active (running)`
- [ ] `curl -I http://192.168.10.151/onlyoffice/download/{id}` desde el servidor → `200 OK`
- [ ] `curl -X POST -I http://192.168.10.151/onlyoffice/callback/{id}` desde el servidor → `200 OK`
- [ ] El editor abre el documento sin alertas de error
- [ ] Los cambios guardados se reflejan en el archivo de almacenamiento de Laravel
