# HIGH-Q Project Structure

```
HIGH-Q/
├── .env
├── .git/
├── .gitignore
├── .htaccess
├── autosave.bat
├── composer.json
├── composer.lock
├── highq.sql
├── phpinfo.php
├── php_lint_report.txt
├── progress.md
├── README.md
├── robots.txt
├── admin/
│   ├── .env
│   ├── .htaccess
│   ├── .htpasswd
│   ├── api/
│   │   ├── create_payment_link.php
│   │   ├── debug-headers.php
│   │   ├── delete_attachment.php
│   │   ├── export_registration.php
│   │   ├── ip_logs.php
│   │   ├── mac_blocklist.php
│   │   ├── maintenance-debug.php
│   │   ├── mark_read.php
│   │   ├── notifications.php
│   │   ├── resend_payment_link.php
│   │   ├── run-scan.php
│   │   ├── save-settings.php
│   │   └── threads.php
│   ├── assets/
│   │   ├── avatars/
│   │   ├── css/
│   │   │   ├── admin-style.css
│   │   │   ├── admin.css
│   │   │   ├── admin1.css
│   │   │   ├── courses.css
│   │   │   ├── dashboard.css
│   │   │   ├── notifications.css
│   │   │   ├── payment.css
│   │   │   ├── posts.css
│   │   │   ├── responsive.css
│   │   │   ├── roles.css
│   │   │   ├── signup.css
│   │   │   ├── style.css
│   │   │   ├── styles.css
│   │   │   ├── tutors.css
│   │   │   └── users.css
│   │   ├── img/
│   │   │   ├── android-chrome-192x192.png
│   │   │   ├── android-chrome-512x512.png
│   │   │   ├── apple-touch-icon.png
│   │   │   ├── favicon-16x16.png
│   │   │   ├── favicon-32x32.png
│   │   │   ├── favicon.ico
│   │   │   ├── hq-logo.jpeg
│   │   │   ├── icons/
│   │   │   │   ├── bank.svg
│   │   │   │   ├── book-open.svg
│   │   │   │   ├── book-stack.svg
│   │   │   │   ├── cash.svg
│   │   │   │   ├── doc.svg
│   │   │   │   ├── laptop.svg
│   │   │   │   ├── online.svg
│   │   │   │   ├── payment.svg
│   │   │   │   ├── phone.svg
│   │   │   │   ├── results.svg
│   │   │   │   ├── star.svg
│   │   │   │   ├── target.svg
│   │   │   │   ├── teacher.svg
│   │   │   │   └── trophy.svg
│   │   │   └── site.webmanifest
│   │   ├── js/
│   │   │   ├── admin-forms.js
│   │   │   ├── admin-security.js
│   │   │   ├── header-notifications.js
│   │   │   ├── notifications.js
│   │   │   ├── settings.js
│   │   │   └── viewport-check.js
│   ├── auth_check.php
│   ├── auth_test.php
│   ├── cli/
│   ├── config/
│   │   └── recaptcha.php
│   ├── create_htpasswd.php
│   ├── create_pass.php
│   ├── download_post_utme.php
│   ├── errors/
│   │   ├── 400.php
│   │   ├── 401.php
│   │   ├── 403.php
│   │   ├── 404.php
│   │   └── 500.php
│   ├── forgot_password.php
│   ├── includes/
│   │   ├── auth.php
│   │   ├── csrf.php
│   │   ├── db.php
│   │   ├── footer.php
│   │   ├── functions.php
│   │   ├── header.php
│   │   ├── scan.php
│   │   └── sidebar.php
│   ├── index.php
│   ├── login.php
│   ├── logout.php
│   ├── pages/
│   │   ├── .htaccess
│   │   ├── audit_logs.php
│   │   ├── chat.php
│   │   ├── chat_view.php
│   │   ├── comments.php
│   │   ├── courses.php
│   │   ├── dashboard.php
│   │   ├── icons.php
│   │   ├── index.php
│   │   ├── payment.php
│   │   ├── payments.php
│   │   ├── post.php
│   │   ├── post_edit.php
│   │   ├── roles.php
│   │   ├── settings.php
│   │   ├── students.php
│   │   ├── tutors.php
│   │   └── users.php
│   ├── pending.php
│   ├── post_utme_registrations.php
│   ├── reset_password_final.php
│   ├── robots.txt
│   ├── signup.php
│   ├── signup.php.new
│   ├── test.php
│   ├── test_auth.php
│   └── verify_email.php
├── bin/
│   ├── scan-runner.php
+│   └── scan-scheduler.php
├── config/
│   ├── payments.php
│   └── recaptcha.php
├── migrations/
│   ├── 2025-09-19-add-created_by-to-posts.sql
│   ├── 2025-09-19-create-site_settings.sql
│   ├── 2025-09-20-add-payments-columns.sql
│   ├── 2025-09-23-add-bank-details-to-site_settings.sql
│   ├── 2025-09-24-alter-payments-add-metadata.sql
│   ├── 2025-09-24-create-student-registrations.sql
│   ├── 2025-09-25-add-course-fields-and-icons.sql
│   ├── 2025-09-25-convert-icons-and-normalize-features.sql
│   ├── 2025-09-25-drop-unused.sql
│   ├── 2025-09-26-create-notifications-table.sql
│   ├── 2025-09-28-add-email-to-student-registrations.php
│   ├── 2025-09-28-add-email-verification-sent-at.sql
│   ├── 2025-09-28-add-email-verification-to-users.sql
│   ├── 2025-09-28-add-maintenance-allowed-ips.sql
│   ├── 2025-09-29-create-ip-logs-and-mac-blocklist.sql
│   ├── 2025-09-30-add-categoryid-and-tags-to-posts.sql
│   ├── 2025-09-30-add-comments-ip.sql
│   ├── 2025-09-30-add-featured-image-to-posts.sql
│   ├── 2025-09-30-create-forum-questions.sql
│   ├── 2025-09-30-create-forum-replies.sql
│   ├── 2025-09-30-create-newsletter-subscribers.sql
│   ├── 2025-09-30-create-post-likes.sql
│   ├── 2025-10-01-create-post-likes-table.sql
│   ├── 2025-10-02-create-comment-likes-table.sql
│   ├── 2025-10-03-create-forum-replies.sql
│   ├── 2025-10-04-make-payments-id-autoinc.sql
│   ├── 2025-10-05-add-contact-tiktok-column.sql
│   ├── 2025-10-05-alter-chat-attachments-add-meta.sql
│   ├── 2025-10-05-create-chat-attachments.sql
│   ├── 2025-10-06-add-allow-admin-public-view-during-maintenance.sql
│   ├── 2025-10-06-add-column-to-site_settings.sql
│   ├── 2025-10-06-add-unsubscribe-token-to-newsletter.sql
│   ├── migrate_course_features.php
│   ├── seed_icons.php
│   ├── test_dotenv_admin.php
│   └── _seed_icons.sql
├── public/
│   ├── .htaccess
│   ├── 404.php
+│   ├── 500.php
│   ├── about.php
│   ├── api/
│   │   ├── activate_payment.php
│   │   ├── chat.php
│   │   ├── comments.php
│   │   ├── comment_like.php
│   │   ├── forum_reply.php
│   │   ├── like_post.php
│   │   ├── mark_sent.php
+│   │   ├── payments_webhook.php
│   │   ├── payment_status.php
│   │   ├── register_post_utme.php
│   │   ├── settings.php
│   │   ├── subscribe_newsletter.php
│   │   ├── unsubscribe_newsletter.php
│   ├── assets/
│   │   ├── css/
│   │   │   ├── admin.css
│   │   │   ├── animations.css
│   │   │   ├── ceo-responsive.css
│   │   │   ├── hero.css
│   │   │   ├── payment.css
│   │   │   ├── post-toc.css
│   │   │   ├── public.css
│   │   │   ├── responsive.css
│   │   │   ├── social-icons.css
│   │   ├── images/
│   │   │   ├── android-chrome-192x192.png
│   │   │   ├── android-chrome-512x512.png
│   │   │   ├── apple-touch-icon.png
│   │   │   ├── favicon-16x16.png
│   │   │   ├── favicon-32x32.png
│   │   │   ├── favicon.ico
│   │   │   ├── hq-logo.jpeg
│   │   │   ├── icons/
│   │   │   │   ├── bank.svg
│   │   │   │   ├── book-open.svg
│   │   │   │   ├── book-stack.svg
│   │   │   │   ├── cash.svg
│   │   │   │   ├── doc.svg
│   │   │   │   ├── laptop.svg
│   │   │   │   ├── online.svg
│   │   │   │   ├── payment.svg
│   │   │   │   ├── phone.svg
│   │   │   │   ├── results.svg
│   │   │   │   ├── star.svg
│   │   │   │   ├── target.svg
│   │   │   │   ├── teacher.svg
│   │   │   │   └── trophy.svg
│   │   │   ├── master.jpg
│   │   │   ├── quam.jpg
│   │   │   ├── quam1.jpg
│   │   │   └── site.webmanifest
│   │   ├── js/
│   │   │   ├── post.js
│   │   │   └── sweetalert2-cdn.html
│   │   ├── vendor/
│   ├── chatbox.php
│   ├── community.php
│   ├── config/
│   ├── contact.php
│   ├── download_attachment.php
│   ├── errors/
│   │   ├── 400.php
│   │   ├── 401.php
│   │   ├── 403.php
│   │   ├── 404.php
│   │   └── 500.php
│   ├── exams.php
│   ├── home.php
│   ├── includes/
│   │   ├── footer.php
│   │   ├── header.php
│   ├── index.php
│   ├── news.php
│   ├── payments_callback.php
│   ├── payments_wait.php
│   ├── post.php
│   ├── privacy.php
│   ├── program.php
│   ├── programs.php
│   ├── receipt.php
│   ├── register.php
│   ├── register_new.php
│   ├── register_post_utme.php
│   ├── terms.php
│   ├── tutors.php
│   ├── tutor_profile.php
│   ├── unsubscribe_newsletter.php
│   ├── uploads/
│   │   ├── passports/
│   │   ├── posts/
│   │   └── tutors/
├── scripts/
│   ├── apply_gender_and_seed_postutme.php
│   ├── apply_notifications_migration.php
│   ├── apply_waec_serial_migration.php
│   ├── check_dompdf.php
│   ├── check_home.php
│   ├── check_migrations_and_schema.php
│   ├── check_postutme_schema.php
│   ├── check_site_settings_cli.php
│   ├── check_smtp_cert.php
│   ├── check_smtp_ssl.php
│   ├── check_smtp_verify_php.php
│   ├── drop_waec_serial_no_and_backup.php
│   ├── dump_public_header.php
│   ├── home_after.html
│   ├── home_dump.html
│   ├── inspect_payments.php
│   ├── inspect_site_settings.php
│   ├── run_db_diagnostics.php
│   ├── run_migrations.php
│   ├── run_specific_migrations.php
│   ├── send_test_mail.php
│   ├── send_test_mail_noverify.php
│   ├── set_settings_bank.php
│   └── set_site_bank.php
├── src/
│   ├── Helpers/
│   │   └── Payments.php
│   ├── Models/
│   │   └── User.php
│   ├── Security/
│   │   ├── auth.php
│   │   └── csrf.php
├── storage/
│   ├── like_post_error.log
│   ├── logs/
│   │   ├── mailer_debug.log
│   │   └── students_confirm_errors.log
│   └── posts-debug.log
├── tools/
│   ├── apply_notifications_migration.php
│   ├── check_dompdf.php
│   ├── check_home.php
│   ├── check_migrations_and_schema.php
│   ├── check_site_settings_cli.php
│   ├── check_smtp_cert.php
│   ├── check_smtp_ssl.php
│   ├── check_smtp_verify_php.php
│   ├── dump_public_header.php
│   ├── home_after.html
│   ├── home_dump.html
│   ├── inspect_payments.php
│   ├── inspect_site_settings.php
│   ├── run_migrations.php
│   ├── run_specific_migrations.php
│   ├── send_test_mail.php
│   ├── send_test_mail_noverify.php
│   ├── set_settings_bank.php
│   ├── set_site_bank.php
└── vendor/
  └── ... (Composer dependencies)
├── docs/
│   └── DEPLOY.md

├── docs/
│   └── DEPLOY.md
```

## Deploying the app (domain root vs subfolder)

This project can be hosted at the domain root (e.g. https://example.com) or inside a subfolder (e.g. https://example.com/HIGH-Q). Below are recommended steps for both cases. Back up files and test in staging before changing production configuration.

1) Set APP_URL in `.env` (required)

   - If hosting at domain root, set:

     ```env
     APP_URL=https://example.com
     ```

   - If hosting in a subfolder, set (example):

     ```env
     APP_URL=https://example.com/HIGH-Q
     ```

   - Also update `admin/.env` if present. `APP_URL` is the canonical source of truth used by server helpers (`app_url()` / `admin_url()`) to build absolute links (emails, exports, etc.).

2) Backup `.htaccess` files

   - Always create backups before changing server config:

     ```powershell
     Copy-Item .htaccess .htaccess.bak -Force
     Copy-Item admin\.htaccess admin\.htaccess.bak -Force
     ```

3) `.htaccess` examples (manual review required)

   - If you host at the domain root (recommended), set `RewriteBase /` and use root-relative ErrorDocument paths. Example root `.htaccess`:

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

   - If you host in a subfolder, set `RewriteBase` to the subfolder and adjust ErrorDocument paths to include the subfolder. Example for `/HIGH-Q`:

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

   - Admin area `admin/.htaccess` (adjust `RewriteBase` similarly). Example (subfolder case shown):

     ```apache
     AuthType Basic
     AuthName "Admin Area"
     AuthUserFile "C:/xampp/htdocs/HIGH-Q/admin/.htpasswd"
     Require valid-user

     ErrorDocument 400 /HIGH-Q/admin/errors/400.php
     ErrorDocument 401 /HIGH-Q/admin/errors/401.php
     ErrorDocument 403 /HIGH-Q/admin/errors/403.php
     ErrorDocument 404 /HIGH-Q/admin/errors/404.php
     ErrorDocument 500 /HIGH-Q/admin/errors/500.php
     ```

   - Note: adapt `AuthUserFile` and `RewriteBase` values to match your host. If hosting at domain root, set `RewriteBase /` and remove the subfolder prefix from ErrorDocument paths.

4) File permissions and services

   - Ensure `public/uploads/` and `storage/` are writable by the webserver user.
   - Restart Apache/PHP-FPM after edits (XAMPP: restart Apache via control panel).

5) Smoke tests

   - If root install:
     - Visit: `https://example.com/index.php`
     - Asset: `https://example.com/assets/images/hq-logo.jpeg`
     - Admin: `https://example.com/admin/login.php`

   - If subfolder install (example `/HIGH-Q`):
     - Visit: `https://example.com/HIGH-Q/index.php`
     - Asset: `https://example.com/HIGH-Q/assets/images/hq-logo.jpeg`
     - Admin: `https://example.com/HIGH-Q/admin/login.php`

   - Use browser devtools to confirm admin AJAX endpoints return 200/JSON.

6) Rollback

   - Restore backups if needed:

     ```powershell
     Copy-Item .htaccess.bak .htaccess -Force
     Copy-Item admin\.htaccess.bak admin\.htaccess -Force
     ```

Notes

- Prefer setting `APP_URL` correctly rather than mass-editing files: server-side helpers (`app_url()`/`admin_url()`) will then generate correct absolute links used in emails and exports.
- Do not edit log files or historical mail dumps — they are archival and may contain past absolute links.

If you'd like, I can add these suggestions as a dedicated `docs/DEPLOY.md` file or prepare preview diffs for your `.htaccess` files (I will back them up first). Let me know which you prefer.

## Admin Panel Guide

The admin panel is the control center for HIGH Q Solid Academy. It is accessed from `admin/login.php` and, after login, routes into `admin/index.php?pages=dashboard`. The panel uses role-based access control, so the navigation you see depends on the permissions assigned to your account.

### How the admin layout works

- The left sidebar is the main navigation.
- The logo at the top of the sidebar returns you to the dashboard.
- The active page is highlighted automatically.
- The logout button is always available at the bottom of the sidebar.
- Some menu items are shown only when your role has permission for that section.
- The `Smart Patcher` item opens in a new tab.

### Navigation and what each section does

| Menu item | Purpose |
| --- | --- |
| Dashboard | Overview of the academy and quick access to admin tasks. |
| Manage Users | Create, review, and manage user accounts. |
| Roles Management | Control role names and access permissions. |
| Site Settings | Update site-wide details such as academy information and configuration. |
| Courses | Manage course records and course content. |
| Tutors | Manage tutor profiles and tutor-related content. |
| Academic Management | Handle academic records, learning data, and related administration. |
| Payments | Review and manage payment records. |
| Create Payment Link | Generate a new payment link for a student or payment request. |
| Icons | View or manage the icon set used across the project. |
| News / Blog | Create, edit, and publish news or blog posts. |
| Comments | Review and moderate user comments. |
| Testimonials | Manage testimonials displayed on the site. |
| Chat Support | Monitor and respond to support chats. |
| Audit Logs | Review recorded administrative actions and system activity. |
| Appointments | Manage scheduled appointments and related requests. |
| Security Scan | Run or review security scans for the admin area. |
| Smart Patcher | Open the patching tool in a separate tab for maintenance tasks. |
| Automator | Run automation-related admin tasks. |
| Canary Trap | Security-trap and monitoring section for suspicious activity. |

### Permission behavior

- Admin accounts automatically get access to newly added menu items.
- Other roles only see the items stored in their `role_permissions` records.
- `Audit Logs` can also appear for users who have `settings` permission.
- `Create Payment Link` can appear for users who have `payments` permission.
- `Appointments` can appear for users who have `settings` or `students` permission.
- `Testimonials` can appear for users who have `settings` permission.

### Typical admin workflow

1. Sign in through `admin/login.php`.
2. Open the dashboard to check the latest overview.
3. Use the sidebar to move between management areas.
4. Update users, roles, courses, payments, posts, and settings from the matching menu item.
5. Review logs or run security tools when you need audit or maintenance checks.
6. Sign out when finished.

### Notes for administrators

- If a menu item is missing, check the role permissions assigned to the account.
- If a page does not load correctly, verify that the admin route still matches the menu URL in `admin/includes/menu.php`.
- The admin panel is designed to be used from desktop browsers for the best experience.

