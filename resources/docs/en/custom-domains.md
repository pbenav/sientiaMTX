# Custom Domain Mapping (CNAME / White-label)

To allow Sientia MTX to run under a different domain or subdomain than the primary one (e.g., maintaining an old URL like `decitas.zafarraya.es` while the service is hosted on `mtx.sientia.com`), the best technical practice is to configure **Domain Mapping** via DNS CNAME records and ServerAliases.

This approach (known as White-label) keeps SEO intact, maintains the URL visible to the citizen, and most importantly, **avoids using Iframes**, which are actively blocked by the privacy policies of modern browsers (Safari, Firefox, Chrome), preventing the use of sessions, language translations, and causing security errors (Error 419 XSRF).

Below is the step-by-step process:

## Step 1: Configure DNS Records (At your domain provider)

The owner of the source domain (e.g., `zafarraya.es`) must modify their DNS records to point to the server where Sientia MTX is installed.

1. Access your domain provider's control panel (GoDaddy, Cloudflare, etc.).
2. Go to the **DNS Zone** management.
3. Create or edit the subdomain record (e.g., `decitas`).
4. Configure the record as a **CNAME Type**.
5. In the **Value/Destination** field, enter the Sientia MTX domain (e.g., `mtx.sientia.com`).

*Note: If you are configuring a main domain (e.g., `mydomain.com`) and your provider does not allow CNAMEs at the root (Apex), you must use an **A** record pointing to the public IP address of the Sientia server.*

## Step 2: Configure the Web Server (On Sientia MTX)

The Sientia server (usually Nginx or Apache) must be configured to "listen" and accept requests coming with this new domain name.

### If using Nginx:
Edit the server block (VirtualHost) configuration file for Sientia MTX (usually in `/etc/nginx/sites-available/sientiamtx`):

```nginx
server {
    listen 80;
    listen 443 ssl;
    
    # Add the new domain separated by spaces
    server_name mtx.sientia.com decitas.zafarraya.es;
    
    # ... rest of the Laravel configuration (root, index, etc) ...
}
```
After saving, reload Nginx:
```bash
sudo systemctl reload nginx
```

### If using Apache:
Edit the VirtualHost file (usually in `/etc/apache2/sites-available/sientiamtx.conf`):

```apache
<VirtualHost *:80>
    ServerName mtx.sientia.com
    # Add the ServerAlias directive
    ServerAlias decitas.zafarraya.es
    
    # ... rest of the configuration ...
</VirtualHost>
```
After saving, reload Apache:
```bash
sudo systemctl reload apache2
```

## Step 3: Generate SSL Certificate (HTTPS)

To prevent browsers from blocking the page for being insecure, you must generate a Let's Encrypt certificate for the new domain.

If you use **Certbot**, run the following command on the Sientia server:

**Nginx:**
```bash
sudo certbot --nginx -d mtx.sientia.com -d decitas.zafarraya.es
```

**Apache:**
```bash
sudo certbot --apache -d mtx.sientia.com -d decitas.zafarraya.es
```

## Final Result

Once the DNS propagates (it can take from a few minutes to a couple of hours), any citizen visiting `https://decitas.zafarraya.es` will see exactly the same application and appointment portal as in Sientia MTX, but their browser will show their original domain. The system will work natively (First-Party), without cookie blocks and with all functionalities enabled.
