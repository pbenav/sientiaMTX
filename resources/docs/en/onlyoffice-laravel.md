# 📄 OnlyOffice Integration — Laravel Server (sientiaMTX)

This guide documents the end-to-end integration of OnlyOffice Document Server with SientiaMTX, including network architecture, required configuration settings, and deployment steps.

---

## 🏗️ General Architecture

```
[User Browser]
        │  HTTPS
        ▼
[Apache Reverse Proxy]   192.168.1.10  →  mtx.sientia.com / office.sientia.com
        │  Internal HTTP
        ├──────────────────────────────────────────┐
        ▼                                          ▼
[Laravel — sientiaMTX]                  [OnlyOffice Document Server]
  192.168.10.151                           192.168.10.152
  (LXC Proxmox)                            (LXC Proxmox)
```

> **Architectural Key:** OnlyOffice and Laravel communicate **directly over the local LAN** (192.168.10.x), bypassing the Apache reverse proxy. This avoids hairpin NAT loops and signature verification failures.

---

## ⚙️ Environment Variables (`.env`)

Add or verify these environment variables inside Laravel's `.env` file:

```env
# Public application URL (used by client browsers)
APP_URL=https://mtx.sientia.com

# ── OnlyOffice ──────────────────────────────────────────────────────────────

# Public OnlyOffice URL (used by browser to load document editor)
ONLYOFFICE_URL=https://office.sientia.com/

# Shared JWT secret between Laravel and OnlyOffice (must match on both servers)
ONLYOFFICE_SECRET=your_jwt_secret_here

# Internal LAN IP for Laravel (OnlyOffice downloads files directly from here)
ONLYOFFICE_INTERNAL_APP_URL=http://192.168.10.151

# Internal LAN IP for OnlyOffice (Laravel contacts OnlyOffice directly via LAN)
ONLYOFFICE_INTERNAL_SERVER_URL=http://192.168.10.152
```

> [!IMPORTANT]
> After modifying `.env`, always clear and rebuild configuration caches:
> ```bash
> php artisan config:cache && php artisan cache:clear
> ```

---

## 📦 Configuration File (`config/onlyoffice.php`)

The `config/onlyoffice.php` file defines core parameters and reads values from `.env`:

```php
<?php
return [
    'url'    => env('ONLYOFFICE_URL', 'https://office.sientia.com/'),
    'secret' => env('ONLYOFFICE_SECRET'),
    'extensions' => [
        'word'  => ['docx', 'doc', 'odt', 'rtf', 'txt'],
        'cell'  => ['xlsx', 'xls', 'ods', 'csv'],
        'slide' => ['pptx', 'ppt', 'odp'],
    ],
    // Internal IPs for direct LAN bypass
    'internal_app_url'    => env('ONLYOFFICE_INTERNAL_APP_URL'),
    'internal_server_url' => env('ONLYOFFICE_INTERNAL_SERVER_URL'),
];
```

---

## 🔑 Security & URL Architecture

### File Download Endpoint (`/onlyoffice/download/{attachment}`)

OnlyOffice requests files directly from Laravel. The download endpoint accepts requests if:
1. **The request originates from OnlyOffice's internal IP** (`192.168.10.152`), or
2. **The URL contains a valid signed signature** from Laravel.

### Callback Endpoint (`/onlyoffice/callback/{attachment}`)

OnlyOffice triggers this endpoint to save document revisions. It is excluded from CSRF middleware in `bootstrap/app.php`:

```php
$middleware->validateCsrfTokens(except: [
    '/onlyoffice/callback/*',
]);
```

Security is verified via the **JWT Bearer Token** in the `Authorization` header.

---

## 🛣️ Registered Routes

```php
// Requires authentication — loads editor in user browser
Route::middleware(['auth'])->group(function () {
    Route::get('/attachments/{attachment}/edit', [OnlyOfficeController::class, 'edit'])
        ->name('onlyoffice.edit');
});

// Non-session endpoint — contacted directly by OnlyOffice server
Route::get('/onlyoffice/download/{attachment}', [OnlyOfficeController::class, 'downloadFile'])
    ->name('onlyoffice.download');
Route::post('/onlyoffice/callback/{attachment}', [OnlyOfficeController::class, 'callback'])
    ->name('onlyoffice.callback');
```

---

## 🛠️ Troubleshooting

| Issue | Likely Cause | Resolution |
|---|---|---|
| "Error loading document" | `ONLYOFFICE_INTERNAL_APP_URL` missing or invalid | Check `.env` and execute `php artisan config:cache` |
| "Invalid Signature" on download | Public domain used instead of internal IP | Confirm `config('onlyoffice.internal_app_url')` resolves to LAN IP |
| "Failed to save document" | JWT secret mismatch | Verify `ONLYOFFICE_SECRET` is identical on both servers |
| Table `task_result` does not exist | OnlyOffice DB uninitialized | Run `createdb.sql` on the OnlyOffice server |

