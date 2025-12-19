# Environment Configuration Guide

## Overview
HIGH-Q SOLID ACADEMY uses environment variables to configure database connections, mail settings, payment gateways, and other sensitive configuration data. This keeps credentials secure and allows easy configuration across different environments (localhost, ngrok, production).

## Setup Instructions

### 1. Create Environment Files

Both the root directory and admin directory need `.env` files:

```bash
# From the project root
cp .env.example .env
cp admin/.env.example admin/.env
```

### 2. Configure Database
Edit both `.env` files and set your database credentials:

```env
DB_HOST=localhost
DB_NAME=highq_db
DB_USER=root
DB_PASS=your_password
DB_CHARSET=utf8mb4
```

### 3. Configure Application URLs

#### For Localhost Development
```env
APP_URL=http://localhost/HIGH-Q
```

#### For Ngrok Tunneling
```env
APP_URL=https://your-subdomain.ngrok-free.dev/HIGH-Q
```

#### For Production
```env
APP_URL=https://yourdomain.com
```

**IMPORTANT:** The application automatically detects HTTPS from proxy headers (`X-Forwarded-Proto`), so ngrok and other reverse proxies work correctly.

### 4. Configure Mail (SMTP)

For Gmail:
```env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@highqacademy.com
MAIL_FROM_NAME="HIGH Q SOLID ACADEMY"
```

**Note:** For Gmail, you must use an [App Password](https://support.google.com/accounts/answer/185833), not your regular password.

### 5. Configure Payment Gateway (Paystack)

Sign up for Paystack at https://dashboard.paystack.com and get your API keys:

```env
PAYSTACK_PUBLIC=pk_test_xxxxxxxxxxxxxxxxxxxxx
PAYSTACK_SECRET=sk_test_xxxxxxxxxxxxxxxxxxxxx
PAYSTACK_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxx
```

### 6. Configure Bank Details

For displaying bank transfer payment options:

```env
SITE_BANK_ACCOUNT=0123456789
SITE_BANK_NAME=Your Bank Name
SITE_ACCOUNT_NAME=HIGH Q SOLID ACADEMY
```

## Keeping .env Files in Sync

**CRITICAL:** Changes to `.env` MUST be replicated to `admin/.env`

The application loads environment variables in two places:
- `/public/config/functions.php` (for public-facing pages)
- `/admin/includes/functions.php` (for admin panel)

Both load from their respective `.env` files, so they must contain identical values for:
- Database credentials
- APP_URL
- Mail configuration
- Payment gateway keys
- Bank details

### Sync Script (Optional)
You can create a simple sync script:

```bash
# sync-env.sh
#!/bin/bash
cp .env admin/.env
echo ".env synced to admin/.env"
```

## Environment-Specific Notes

### Localhost
- Use `http://localhost/HIGH-Q` as APP_URL
- No special configuration needed
- Mixed content warnings won't occur

### Ngrok
- Get your ngrok URL: `ngrok http 80`
- Use the HTTPS URL: `https://abc123.ngrok-free.dev/HIGH-Q`
- The application automatically detects HTTPS via `X-Forwarded-Proto` header
- No code changes needed!

### Production
- Use your actual domain: `https://yourdomain.com`
- Ensure HTTPS is properly configured on your server
- Use production Paystack keys (starting with `pk_live_` and `sk_live_`)
- Disable debug mode: `DEBUG_MODE=false`

## Environment Variables Reference

### Required Variables
These MUST be set for the application to work:
- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
- `APP_URL`
- `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`
- `PAYSTACK_PUBLIC`, `PAYSTACK_SECRET`

### Optional Variables
These have sensible defaults:
- `DB_CHARSET` (default: `utf8mb4`)
- `MAIL_PORT` (default: `587`)
- `MAIL_ENCRYPTION` (default: `tls`)
- `MAIL_DEBUG` (default: `0`)
- `SESSION_TIMEOUT` (default: `7200` seconds)
- `MAX_UPLOAD_SIZE` (default: `100` MB)

## Troubleshooting

### Mixed Content Warnings (HTTP/HTTPS)
If you see "Mixed Content" errors in the browser console:
- Ensure `APP_URL` uses HTTPS in your `.env` when accessing via HTTPS
- The application detects HTTPS automatically from headers, but static resources use APP_URL

### 404 Errors for Admin API
If `/admin/api/user_profile.php` returns 404:
- Check that `admin/.env` has the same `APP_URL` as root `.env`
- Clear browser cache
- Verify files exist in `/admin/api/` folder

### Mail Not Sending
- Enable debug mode: `MAIL_DEBUG=1` in `.env`
- Check logs in `/storage/logs/mailer_debug.log`
- Verify Gmail App Password is correct
- Check firewall isn't blocking SMTP port 587

### Database Connection Failed
- Verify credentials in both `.env` files match
- Ensure MySQL/MariaDB is running
- Check database exists and user has permissions

## Security Best Practices

1. **Never commit `.env` files** to version control (already in `.gitignore`)
2. **Use strong passwords** for database and mail accounts
3. **Use production keys in production** - never use test keys in production
4. **Restrict file permissions:**
   ```bash
   chmod 600 .env
   chmod 600 admin/.env
   ```
5. **Rotate credentials regularly**, especially after team member changes

## Additional Resources

- [Paystack API Documentation](https://paystack.com/docs/api/)
- [Gmail App Passwords Guide](https://support.google.com/accounts/answer/185833)
- [Ngrok Documentation](https://ngrok.com/docs)
- [PHPMailer Documentation](https://github.com/PHPMailer/PHPMailer)
