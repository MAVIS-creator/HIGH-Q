# HIGH-Q Split-Domain Deployment Checklist

This project is now prepared for a split deployment where:

- public site lives on `https://www.example.com`
- admin site lives on `https://admin.example.com`

The important rule is:

- public-facing files must be served from `public/`
- admin-facing files must be served from `admin/`
- shared uploaded files must be stored on disk under `public/uploads/...`
- shared uploaded file paths stored in the database should usually look like `uploads/...`

That keeps the same file readable from:

- `app_url('uploads/...')` on the public site
- admin previews that intentionally point back to the public host

## 1. Environment Values

Production:

```env
APP_URL=https://www.example.com
ADMIN_URL=https://admin.example.com
```

Localhost example:

```env
APP_URL=http://localhost/HIGH-Q
ADMIN_URL=http://localhost/HIGH-Q/admin
```

## 2. Recommended Host Mapping

### Public host

- hostname: `www.example.com`
- document root: project root, for example `.../HIGH-Q/`
- root `.htaccess` rewrites requests into `public/`

### Admin host

- hostname: `admin.example.com`
- document root: `.../HIGH-Q/admin/`
- `admin/.htaccess` handles admin-only errors and protections

If the admin subdomain points directly to the `admin/` folder, the public root rewrite will not interfere with admin routes.

## 3. Shared Upload Locations

These should remain on disk inside `public/uploads/...`:

- tutor photos: `public/uploads/tutors/`
- post images: `public/uploads/posts/`
- avatars: `public/uploads/avatars/`
- receipts: `public/uploads/receipts/`
- chat uploads: `public/uploads/chat/`
- testimonials: `public/uploads/testimonials/`
- passports: `public/uploads/passports/`

Preferred database path shape:

```txt
uploads/tutors/file.jpg
uploads/posts/file.jpg
uploads/avatars/file.jpg
uploads/receipts/file.pdf
```

Avoid storing:

```txt
public/uploads/...
../public/...
/admin/...
```

## 4. URL Rules

Use:

- `app_url(...)` for public-facing pages, shared uploads, public assets, payment pages
- `admin_url(...)` for admin assets, admin pages, admin APIs, admin error pages

Do not hardcode:

- `/admin/...`
- `/public/...`
- `http://localhost/...`

## 5. Payment and Notification Expectations

Verified current behavior:

- admin-created payment links use `app_url('pay/...')`
- confirm-with-price links use `app_url('pay/...')`
- public-side notifications link back with `admin_url('index.php?pages=...')`
- bank-transfer receipts are written under `public/uploads/receipts/`

## 6. SSL / HTTPS Note

Root `.htaccess` currently sends HSTS with `includeSubDomains`:

```txt
Strict-Transport-Security: max-age=31536000; includeSubDomains
```

That means:

- if `www.example.com` sends this header,
- then browsers will also expect `admin.example.com` to have valid HTTPS

So the admin subdomain must have a valid certificate before production launch.

## 7. Apache vhost shape

Example only; adapt to your host:

```apache
<VirtualHost *:80>
    ServerName www.example.com
    DocumentRoot "C:/path/to/HIGH-Q"
    <Directory "C:/path/to/HIGH-Q">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

<VirtualHost *:80>
    ServerName admin.example.com
    DocumentRoot "C:/path/to/HIGH-Q/admin"
    <Directory "C:/path/to/HIGH-Q/admin">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## 8. Go-Live Test List

After deployment, test these exact items:

1. Open public homepage.
2. Open admin login on the admin subdomain.
3. Submit a new `register-new.php` registration.
4. Open the generated payment wait page.
5. Upload/mark bank transfer sent.
6. Confirm payment in admin.
7. Download or open receipt from confirmation email.
8. Confirm admission package email still arrives.
9. Create a tutor with photo from admin and verify it appears publicly.
10. Create a post with featured image and verify it appears in public news and post detail.
11. Update admin profile avatar and verify it still renders in admin.
12. Trigger admin test notifications and verify email CTA links open on the admin subdomain.

## 9. Current Readiness Summary

Ready:

- split admin/public URL helper behavior
- public-facing uploads for tutors, posts, avatars, receipts
- admin JS base URLs no longer assuming `/admin`
- payment link generation using `APP_URL`
- admin notification deep links using `ADMIN_URL`

Still worth checking during deployment:

- final vhost/docroot wiring on the hosting panel
- actual SSL issuance on both domains
- file permissions for `public/uploads/`
- whether any old database rows still contain legacy bad paths from earlier versions

