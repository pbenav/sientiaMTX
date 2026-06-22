# Mapeo de Dominio Personalizado (Custom Domains / CNAME)

Para permitir que Sientia MTX se ejecute bajo un dominio o subdominio distinto al principal (por ejemplo, mantener una URL antigua como `decitas.zafarraya.es` mientras el servicio se aloja en `mtx.sientia.com`), la mejor práctica técnica es configurar un **Mapeo de Dominio** mediante registros DNS CNAME y ServerAliases.

Esta aproximación (conocida como White-label o Marca Blanca) permite mantener intacto el SEO, la URL visible para el ciudadano y, lo más importante, **evita usar Iframes**, los cuales son bloqueados activamente por las políticas de privacidad de los navegadores modernos (Safari, Firefox, Chrome), impidiendo el uso de sesiones, traducciones de idiomas y provocando errores de seguridad (Error 419 XSRF).

A continuación, se detalla el proceso paso a paso:

## Paso 1: Configurar los registros DNS (En el proveedor del dominio)

El propietario del dominio origen (ej. `zafarraya.es`) debe modificar sus registros DNS para que apunten al servidor donde está instalado Sientia MTX.

1. Accede al panel de control de tu proveedor de dominio (GoDaddy, DonDominio, Cloudflare, etc.).
2. Dirígete a la gestión de la **Zona DNS**.
3. Crea o edita el registro del subdominio (ej. `decitas`).
4. Configura el registro como **Tipo CNAME**.
5. En el campo **Valor/Destino**, introduce el dominio de Sientia MTX (ej. `mtx.sientia.com`).

*Nota: Si estás configurando un dominio principal (ej. `midominio.com`) y tu proveedor no permite CNAME en el directorio raíz (Apex), deberás usar un registro tipo **A** apuntando a la dirección IP pública del servidor de Sientia.*

## Paso 2: Configurar el Servidor Web (En Sientia MTX)

El servidor de Sientia (normalmente Nginx o Apache) debe estar configurado para "escuchar" y aceptar las peticiones que lleguen con este nuevo nombre de dominio.

### Si usas Nginx:
Edita el archivo de configuración del bloque de servidor (VirtualHost) de Sientia MTX (usualmente en `/etc/nginx/sites-available/sientiamtx`):

```nginx
server {
    listen 80;
    listen 443 ssl;
    
    # Añade el nuevo dominio separándolo por espacios
    server_name mtx.sientia.com decitas.zafarraya.es;
    
    # ... resto de la configuración de Laravel (root, index, etc) ...
}
```
Tras guardar, recarga Nginx:
```bash
sudo systemctl reload nginx
```

### Si usas Apache:
Edita el archivo del VirtualHost (usualmente en `/etc/apache2/sites-available/sientiamtx.conf`):

```apache
<VirtualHost *:80>
    ServerName mtx.sientia.com
    # Añade la directiva ServerAlias
    ServerAlias decitas.zafarraya.es
    
    # ... resto de la configuración ...
</VirtualHost>
```
Tras guardar, recarga Apache:
```bash
sudo systemctl reload apache2
```

## Paso 3: Generar Certificado SSL (HTTPS)

Para evitar que los navegadores bloqueen la página por no ser segura, debes generar un certificado Let's Encrypt para el nuevo dominio.

Si utilizas **Certbot**, ejecuta el siguiente comando en el servidor de Sientia:

**Nginx:**
```bash
sudo certbot --nginx -d mtx.sientia.com -d decitas.zafarraya.es
```

**Apache:**
```bash
sudo certbot --apache -d mtx.sientia.com -d decitas.zafarraya.es
```

## Resultado Final

Una vez los DNS se propaguen (puede tardar desde unos minutos hasta un par de horas), cualquier ciudadano que visite `https://decitas.zafarraya.es` verá exactamente la misma aplicación y portal de citas que en Sientia MTX, pero su navegador mostrará su dominio original. El sistema funcionará como First-Party, sin bloqueos de cookies y con todas las funcionalidades habilitadas.
