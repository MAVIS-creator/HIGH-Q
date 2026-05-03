# PUBLIC-SIDE NOTIFICATION SYSTEM REFERENCE

## System Overview

The notification system automatically sends emails to **ALL ADMIN USERS** whenever the public side generates important events. This ensures all admins stay informed in real-time about user activity.

---

## What Triggers Notifications

### 1. **PUBLIC REGISTRATION** ✅
- **When**: A new visitor submits a registration form
- **Files**: 
  - `public/register.php` (legacy form)
  - `public/process-registration.php` (registration wizard)
- **Admin Notification Includes**:
  - Registration ID
  - Program Type
  - Student Name & Email
  - Phone Number
  - Amount & Payment Reference
  - Registration Status

### 2. **PUBLIC CHAT MESSAGE** ✅
- **When**: A visitor sends a message through the chat widget
- **File**: `public/chatbox.php`
- **Admin Notification Includes**:
  - Thread ID
  - Visitor Name & Email
  - Message Preview (first 100 characters)
  - Attachment Count
  - Status: "Awaiting Admin Response"

### 3. **PAYMENT CONFIRMATION** ✅
- **When**: Admin confirms a payment from the admin panel
- **File**: `admin/pages/payments.php` (when admin clicks confirm)
- **Payment System**: Bank Transfer (Not Paystack)
- **Admin Notification Includes**:
  - Payment ID & Reference
  - Amount (₦ formatted)
  - Payment Gateway (Bank Transfer)
  - Status: "Successfully Confirmed"

---

## Testing the System

### Quick Test Dashboard
Visit: **`/admin/api/test-notifications.php`**

Available test actions:
- `?action=test_all` - Run all tests at once
- `?action=test_admin_list` - Check if admins are found
- `?action=test_registration` - Simulate registration notification
- `?action=test_payment` - Simulate payment notification
- `?action=test_chat` - Simulate admin chat notification
- `?action=test_public_chat` - **Simulate PUBLIC chat notification** ⭐ NEW
- `?action=test_public_payment` - **Simulate PUBLIC payment notification** ⭐ NEW
- `?action=test_settings` - Check system configuration
- `?action=info` - Get full system information

### Step-by-Step Testing

**1. Verify Configuration**
```
Visit: /admin/api/test-notifications.php?action=test_settings
Expected: notifications_enabled = true, mail_configured = true
```

**2. Check Admin Collection**
```
Visit: /admin/api/test-notifications.php?action=test_admin_list
Expected: See list of admin emails
```

**3. Test Public Notifications**
```
Visit: /admin/api/test-notifications.php?action=test_public_chat
Visit: /admin/api/test-notifications.php?action=test_public_payment
Expected: Test emails sent to all admin addresses
```

**4. Live Testing**
- Submit a real registration form from public side
- Check admin email addresses for notification
- Send a chat message from the public chat widget
- Verify email received by all admins

---

## Core Functions

All notification functions are in: **`public/config/functions.php`**

### `hqAdminEmailNotificationsEnabled($pdo)`
Returns: `true/false` - Whether notifications are enabled in system settings

### `hqAdminNotificationRecipients($pdo, $exclude_user_id = 0)`
Returns: Array of all admin email addresses
Features:
- Queries users with 'admin' role
- Includes users with 'settings' permission
- Deduplicates emails
- Validates email format
- Excludes specified user if needed

### `sendAdminChangeNotification($pdo, $title, $details, $actor_user_id = 0)`
Sends formatted email to all admins
Parameters:
- `$title` - Email subject line
- `$details` - Associative array of key/value pairs (displayed as table in email)
- `$actor_user_id` - User ID triggering the action (optional)

### `notifyAdminChange($pdo, $title, $details, $actor_user_id = 0)`
Fire-and-forget wrapper - non-blocking notification
Won't interrupt user workflow if email fails

---

## Email Format Example

### Subject Line
```
HIGH-Q Admin Update: [Event Type]
```

### Email Content
- **Header**: "An important update from HIGH-Q"
- **Event Title**: The action that occurred
- **Details Table**: Key-value pairs formatted as HTML table
- **Timestamp**: When the notification was sent
- **Footer**: Admin portal link

### Example Email Subject Lines
- `HIGH-Q Admin Update: New Registration Submitted`
- `HIGH-Q Admin Update: New Chat Message from Visitor`
- `HIGH-Q Admin Update: Payment Confirmed from Public User`

---

## Configuration Requirements

### Email Settings (in .env file)
```
MAIL_HOST=smtp.gmail.com          # SMTP server
MAIL_PORT=587                     # SMTP port (587 for TLS, 465 for SSL)
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password   # Use app-specific password for Gmail
MAIL_ENCRYPTION=tls               # TLS or SSL
MAIL_FROM_ADDRESS=noreply@highq.com
MAIL_FROM_NAME=HIGH-Q Admin System
```

### System Settings (in database settings table)
- Key: `notifications_enabled` 
- Value: `1` (enabled) or `0` (disabled)

---

## Troubleshooting

### No Emails Received
1. Check `/admin/api/test-notifications.php?action=test_settings`
2. Verify MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD are correct
3. Verify .env file exists and is readable
4. Check spam/junk folders

### Admins Not Found
1. Visit `/admin/api/test-notifications.php?action=test_admin_list`
2. Verify users exist with 'admin' role or 'settings' permission
3. Verify email addresses are valid format

### Notifications Not Triggering
1. Check event occurs on public side (not admin side for public events)
2. Verify `notifyAdminChange()` call is in correct file
3. Check error logs in `/tmp/` or PHP error log
4. Verify notifications are enabled in system settings

---

## Implementation Files Modified

1. **`public/config/functions.php`** - Core notification functions
2. **`public/register.php`** - Calls notifyAdminChange() after registration
3. **`public/process-registration.php`** - Calls notifyAdminChange() after registration
4. **`public/chatbox.php`** - Calls notifyAdminChange() when visitor sends message
5. **`public/api/payments_webhook.php`** - Calls notifyAdminChange() when Paystack confirms payment
6. **`admin/api/test-notifications.php`** - Test dashboard

---

## Key Features

✅ **Non-blocking** - Notifications never interrupt user workflows  
✅ **Deduplication** - Each admin gets exactly one email per event  
✅ **Multi-role support** - Admins identified by role or permission  
✅ **Email validation** - Invalid addresses automatically filtered  
✅ **Comprehensive data** - All relevant event details included in emails  
✅ **Public-first** - Designed for public-side events (visitor activity)  
✅ **Testable** - Full test suite available for verification  

---

## Quick Links

- Test Dashboard: `/admin/api/test-notifications.php`
- Setup Guide: `docs/ADMIN_NOTIFICATION_SETUP.md`
- Functions File: `public/config/functions.php`
- Main Documentation: `docs/ADMIN_NOTIFICATION_SETUP.md`

---

**Last Updated**: Implementation Complete
**System Status**: ✅ Ready for Testing
