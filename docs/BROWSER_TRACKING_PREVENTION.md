# Browser Tracking Prevention Issues

## Problem
When accessing the admin panel, you may see browser console warnings:
```
Tracking Prevention blocked access to storage for <URL>
```

And API calls may fail with HTML responses instead of JSON.

## Root Cause
Modern browsers (Edge, Safari, Firefox) have "Tracking Prevention" or "Enhanced Tracking Protection" that blocks cross-site cookies and storage when:
1. Accessing via different origins (e.g., `localhost` vs `127.0.0.1`)
2. Accessing via HTTP on localhost in certain configurations
3. Third-party cookie restrictions are enabled

## Solutions

### Solution 1: Use Consistent URL (Recommended)
**Always use the same host** when accessing the admin panel:
- ✅ Use `http://localhost/HIGH-Q/public/admin` everywhere
- ❌ Don't mix `http://127.0.0.1/HIGH-Q/public/admin` and `http://localhost/...`

### Solution 2: Configure Browser Settings

**Microsoft Edge / Chrome:**
1. Go to `edge://settings/content/cookies` (or `chrome://settings/content/cookies`)
2. Add `http://localhost` to "Sites that can always use cookies"
3. Or disable "Block third-party cookies" (not recommended for general browsing)

**Firefox:**
1. Go to `about:preferences#privacy`
2. Under "Enhanced Tracking Protection", select "Custom"
3. Uncheck "Cookies" or add exception for localhost

**Safari:**
1. Preferences → Privacy
2. Uncheck "Prevent cross-site tracking" (temporarily)
3. Or add localhost to exceptions

### Solution 3: Use HTTPS with Valid Certificate
For production deployments, always use HTTPS with a valid SSL certificate. This eliminates most tracking prevention issues.

## Technical Fix Applied
The following improvements were made to handle these scenarios gracefully:

1. **API Endpoints** now:
   - Always return JSON (even on auth failures)
   - Set `Cache-Control: no-cache` headers
   - Return 401 status codes for unauthenticated requests
   - Start sessions before checking auth

2. **JavaScript** now:
   - Handles 401 responses gracefully
   - Logs truncated errors (not full HTML dumps)
   - Hides notification badge when unauthenticated
   - Uses same-origin credentials mode

3. **Admin Base Normalization**:
   - `HQ_ADMIN_BASE` is normalized to current origin
   - Prevents cross-origin API calls
   - See `admin/includes/header.php`

## Prevention
To avoid these issues:
1. Set `APP_URL` in `.env` to match your access URL
2. Always access via the same hostname
3. For local dev, use `http://localhost` consistently
4. For production, use HTTPS with valid SSL
