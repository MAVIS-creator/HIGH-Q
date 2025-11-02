# Deployment Guide

This document expands the deployment guidance for the HIGH-Q app. It covers hosting at domain root (recommended) and hosting inside a subfolder (e.g. `/HIGH-Q`). Follow these steps carefully and test in a staging environment before making changes in production.

## 1) Set APP_URL in `.env` (required)

- If hosting at domain root, set:

```env
APP_URL=https://example.com
```

- If hosting in a subfolder (example):

```env
APP_URL=https://example.com/HIGH-Q
```

- Also update `admin/.env` if present. `APP_URL` is used by server helpers (`app_url()` and `admin_url()`) for absolute links used in emails, generated ZIPs/exports, and some file path resolution.

## 2) Backup `.htaccess` and other critical files

- Make backups before editing server configuration or auth files:

```powershell
Copy-Item .htaccess .htaccess.bak -Force
Copy-Item admin\.htaccess admin\.htaccess.bak -Force
Copy-Item admin\.htpasswd admin\.htpasswd.bak -Force
```

## 3) `.htaccess` examples (manual review required)

- Domain root example (recommended):

```apache
# Root .htaccess for domain root installs
RewriteEngine On
RewriteBase /

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L,QSA]

ErrorDocument 400 /public/errors/400.php
ErrorDocument 401 /public/errors/401.php
ErrorDocument 403 /public/errors/403.php
ErrorDocument 404 /public/errors/404.php
ErrorDocument 500 /public/errors/500.php
```

- Subfolder example (example `/HIGH-Q`):

```apache
# Root .htaccess for subfolder installs (example /HIGH-Q)
RewriteEngine On
RewriteBase /HIGH-Q/

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L,QSA]

ErrorDocument 400 /HIGH-Q/public/errors/400.php
ErrorDocument 401 /HIGH-Q/public/errors/401.php
ErrorDocument 403 /HIGH-Q/public/errors/403.php
ErrorDocument 404 /HIGH-Q/public/errors/404.php
ErrorDocument 500 /HIGH-Q/public/errors/500.php
```

- Admin area example (adjust `RewriteBase` and `AuthUserFile`):

```apache
AuthType Basic
AuthName "Admin Area"
AuthUserFile "C:/xampp/htdocs/HIGH-Q/admin/.htpasswd"
Require valid-user

RewriteEngine On
RewriteBase /HIGH-Q/admin/

ErrorDocument 400 /HIGH-Q/admin/errors/400.php
ErrorDocument 401 /HIGH-Q/admin/errors/401.php
ErrorDocument 403 /HIGH-Q/admin/errors/403.php
ErrorDocument 404 /HIGH-Q/admin/errors/404.php
ErrorDocument 500 /HIGH-Q/admin/errors/500.php
```

Notes:
- Adapt `AuthUserFile`, `RewriteBase`, and ErrorDocument paths to your host environment.
- If using domain root, set `RewriteBase /` and remove subfolder prefixes from paths.

## 4) File permissions and services

- Ensure the webserver user (Apache, nginx, etc.) can write to:
  - `public/uploads/`
  - `storage/`

- Restart your webserver after changes. For XAMPP, restart Apache in the control panel.

## 5) Smoke tests

- Domain root (example):
  - `https://example.com/index.php`
  - `https://example.com/assets/images/hq-logo.jpeg`
  - `https://example.com/admin/login.php`

- Subfolder (example `/HIGH-Q`):
  - `https://example.com/HIGH-Q/index.php`
  - `https://example.com/HIGH-Q/assets/images/hq-logo.jpeg`
  - `https://example.com/HIGH-Q/admin/login.php`

- Use browser devtools to confirm that admin AJAX endpoints return 200/JSON and that static assets load.

## 6) Troubleshooting & rollback

- If something breaks, restore backups:

```powershell
Copy-Item .htaccess.bak .htaccess -Force
Copy-Item admin\.htaccess.bak admin\.htaccess -Force
```

- Common issues:
  - 404 errors for assets: check `RewriteBase` and `APP_URL` values.
  - Auth problems: ensure the `AuthUserFile` path is correct and readable by Apache.
  - File uploads failing: verify `public/uploads/` permissions.

## 7) Best practices & notes

- Prefer setting a correct `APP_URL` rather than changing many files; the app uses `app_url()` and `admin_url()` server helpers that read `APP_URL`.
- For client-side scripts (admin area), the app injects `window.HQ_ADMIN_BASE` and `window.HQ_APP_BASE` so JS can build API and resource URLs dynamically.
- Do not edit logs, historical mail dumps, or tools HTML automatically; those are archival.

## 8) Optional: apply `.htaccess` preview

If you'd like, I can prepare a preview diff for your `.htaccess` and `admin/.htaccess` files (with backups). I will not apply changes without your explicit approval.

---

If you want any of the examples adapted to a specific hostname or file-location on your server, tell me the details (domain, subfolder if any, and the absolute path to your webroot) and I can produce ready-to-apply diffs.
