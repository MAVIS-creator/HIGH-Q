# Admin Notification System - Setup & Verification Guide

## Overview
Your HIGH-Q system now sends automatic email notifications to ALL admin users for all updates from the public side:

### 📋 Events That Trigger Notifications

**Public User Actions:**
- ✅ **New Registrations** (via public registration forms)
- ✅ **Chat Messages from Visitors** (public chat widget)
- ✅ **Payment Confirmations** (Paystack webhook callback)

These keep admins informed of all user activity in real-time without needing to log in.

---

## System Components

### 1. **Core Functions** (`public/config/functions.php`)
Added three new functions available to both public and admin code:

- **`hqAdminEmailNotificationsEnabled($pdo)`**
  - Checks if email notifications are enabled in system settings
  - Returns: `boolean`

- **`hqAdminNotificationRecipients($pdo, $actorUserId)`**
  - Collects emails from all users with admin role or settings permission
  - Returns: `array` of email addresses
  - Automatically deduplicates and validates emails

- **`sendAdminChangeNotification($title, $details, $actorUserId)`**
  - Sends styled HTML email to all admin recipients
  - Includes event details in formatted table
  - Shows who triggered the action and when

- **`notifyAdminChange($pdo, $title, $details, $actorUserId)`**
  - Fire-and-forget wrapper (won't block execution if email fails)

### 2. **Public Registration Updates** (`public/process-registration.php`)
When a new registration is submitted through the universal registration wizard:
- Registration is saved to database
- `notifyAdminChange()` automatically sends notification to all admins
- Includes: Registration ID, Program Type, Student Name, Email, Phone, Amount, Payment Reference
- Notification is sent but doesn't block the registration process

### 3. **Public Chat Messages** (`public/chatbox.php`) - ⭐ NEW
When a visitor sends a chat message from the public chatbox:
- Message is saved to database
- `notifyAdminChange()` automatically sends notification to all admins  
- Includes: Thread ID, Visitor Name, Email, Message Preview, Attachments
- Notifies admins immediately so they can respond quickly
- Works for both new conversations and existing threads

### 4. **Public Payment Confirmations** (`public/api/payments_webhook.php`) - ⭐ NEW
When Paystack confirms a payment from a public user:
- Payment status updated from pending to confirmed
- `notifyAdminChange()` automatically sends notification to all admins
- Includes: Payment ID, Reference, Amount, Gateway, Confirmation Time
- Notifies admins when payment is successfully processed
- User account is automatically activated upon confirmation

### 5. **Legacy Registration** (`public/register.php`)
Updated old registration flow to use same `notifyAdminChange()` system:
- Replaced single admin email notification with multi-admin system
- Maintains internal notification record in database
- Sends same comprehensive details to all admins

### 6. **Admin-Side Payment Actions** (`admin/pages/payments.php`)
Existing admin actions also notify all admins:
- ✅ Payment confirmed by admin → notifies all admins
- ✅ Payment rejected by admin → notifies all admins
- With payment reference and reason included

### 7. **Admin-Side Chat Actions** (`admin/pages/chat.php`)
Existing admin actions also notify all admins:
- ✅ Chat thread claimed → notifies all admins
- ✅ Admin reply sent → notifies all admins
- ✅ Chat thread closed → notifies all admins

---

## How It Works

### Admin Email Collection Process
The system automatically identifies admin users by checking:
1. User role slug = 'admin' OR role name = 'admin'
2. User has 'settings' permission (role_permissions table)
3. User email is valid and not blank
4. Actor user (who triggered action) is included

### Notification Flow
```
Event occurs (registration/payment/chat)
    ↓
notifyAdminChange() called
    ↓
Check if notifications enabled
    ↓
Collect all admin emails
    ↓
Build styled HTML email
    ↓
Send via PHPMailer (SMTP)
    ↓
Log error if sending fails
    ↓
Continue execution (non-blocking)
```

### Email Format
Each notification includes:
- **Subject**: "HIGH-Q Admin Update: [Event Title]"
- **Who**: Admin name and email who triggered it
- **When**: Exact timestamp
- **Details**: Event-specific information in formatted table
- **Action Button**: Quick link to admin panel

---

## Testing the System

### Access Test Dashboard
1. **Log in to admin panel**
2. **Navigate to**: `admin/api/test-notifications.php?action=test_all`

### Available Tests
| Action | Purpose |
|--------|---------|
| `test_all` | Run complete test suite |
| `info` | Display system information |
| `test_admin_list` | Verify admin email collection |
| `test_registration` | Send test registration notification |
| `test_payment` | Send test payment notification |
| `test_chat` | Send test chat notification |
| `test_settings` | Check notification settings |

### Example Test URLs
```
http://yoursite/admin/api/test-notifications.php?action=info
http://yoursite/admin/api/test-notifications.php?action=test_all
http://yoursite/admin/api/test-notifications.php?action=test_admin_list
```

---

## Configuration

### Enable/Disable Notifications
Notifications are **enabled by default**. To disable:

1. Go to **Admin Panel → Settings**
2. Find "Email Notifications" toggle
3. Set to OFF to disable all admin notifications

### Email Configuration (`.env` file)
Ensure these are set in your `.env` file:
```bash
MAIL_HOST=smtp.gmail.com          # Your SMTP server
MAIL_PORT=587                      # Usually 587 for TLS
MAIL_USERNAME=your-email@example.com
MAIL_PASSWORD=your-app-password   # Use app-specific password for Gmail
MAIL_ENCRYPTION=tls               # or 'ssl'
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME=HIGH-Q Solid Academy
MAIL_DEBUG=false                   # Set to 'true' for debugging
```

### Verify Email Settings
Check that your SMTP credentials are correct:
1. Run test dashboard: `?action=test_settings`
2. Look for "mail_configured": should be `true`
3. Check MAIL_HOST and MAIL_FROM address

---

## Verification Checklist

### ✅ Pre-Deployment
- [ ] Email configuration verified in `.env`
- [ ] SMTP credentials are correct
- [ ] Admin users have valid email addresses
- [ ] At least one user has 'admin' role
- [ ] Test notifications sent successfully

### ✅ Registration Notifications
- [ ] Submit a test registration via `register-new.php`
- [ ] Check admin email inbox for notification
- [ ] Email contains student name, email, and payment reference
- [ ] Multiple admins receive the email

### ✅ Payment Notifications
- [ ] Create/confirm a test payment in admin
- [ ] All admins receive payment confirmation email
- [ ] Email includes payment reference and amount

### ✅ Chat Notifications
- [ ] Send a test chat message from public
- [ ] Admin replies to chat
- [ ] All admins receive notification email

---

## Troubleshooting

### Issue: No emails received
**Check:**
1. Run `?action=test_admin_list` - Are admins detected?
2. Check `.env` - Is MAIL_HOST configured?
3. Check `storage/logs/mailer_debug.log` - Any errors?
4. Verify SMTP credentials work with an email client
5. Check spam/junk folder

### Issue: Only partial admins getting emails
**Check:**
1. Run `?action=test_admin_list` - Compare to expected admin count
2. Verify admin users have 'admin' role
3. Verify admin emails are valid and not blank
4. Check role_permissions - do admins have 'settings' permission?

### Issue: Emails sent but no "From" name
**Check:**
1. `MAIL_FROM_NAME` in `.env` is set
2. Some email providers ignore this - check with provider

### Issue: Email test says "warnings"
**Meaning:**
- Admins were found but notifications may be disabled
- Check notification settings toggle

### Enable Debug Logging
Set in `.env`:
```bash
MAIL_DEBUG=true
```
Then check: `storage/logs/mailer_debug.log`

---

## Files Modified

1. **`public/config/functions.php`**
   - Added: `hqAdminEmailNotificationsEnabled()`
   - Added: `hqAdminNotificationRecipients()`
   - Added: `sendAdminChangeNotification()`
   - Added: `notifyAdminChange()`

2. **`public/process-registration.php`** (New Universal Wizard)
   - Added: Admin notification after registration insert

3. **`public/register.php`** (Legacy Registration)
   - Updated: Use `notifyAdminChange()` instead of single email

4. **`admin/api/test-notifications.php`** (NEW)
   - Test dashboard for notification verification

---

## What Admins Will See

### Email Design Features ✨

All admin notification emails feature:
- **Professional Header**: HIGH-Q branding with gradient background (Navy Blue to Slate)
- **Gold Accent**: ₦ Symbol and branding in premium gold (#ffd600)
- **Clean Typography**: System UI fonts with optimized spacing and hierarchy
- **Color-coded Sections**: Actor info box with soft blue gradient
- **Details Table**: Clean tabular format with alternating row backgrounds
- **Call-to-Action Button**: Golden button linking to admin panel
- **Professional Footer**: Auto-notification disclaimer

### 1. Registration Email (Public Submission)
**Subject**: `HIGH-Q Admin Update: New Registration Submitted`

**Visual Layout**:
- Header: "HIGH-Q" logo + "Admin Notification" title
- Actor Info Box: Shows who triggered it, their email, timestamp
- Details Table with:
  - Registration ID
  - Program Type
  - Student Name & Email
  - Phone Number
  - Amount & Payment Reference
  - Status

---

### 2. Chat Message Email (Visitor Message) - ⭐
**Subject**: `HIGH-Q Admin Update: New Chat Message from Visitor`

**Visual Layout**:
- Same professional header as registration
- Actor Info Box: Visitor name/email and timestamp
- Details Table with:
  - Thread ID
  - Visitor Contact Info
  - Message Preview (first 100 chars)
  - Attachment Count
  - Status: "Awaiting Admin Response"

---

### 3. Payment Confirmation Email (Admin Confirms) - ⭐ BANK TRANSFER
**Subject**: `HIGH-Q Admin Update: Payment Confirmed by Admin`

**Visual Layout**:
- Professional header with navy/slate gradient
- Actor Info Box: Which admin confirmed it
- Details Table with:
  - Payment ID
  - Reference Number
  - Amount (₦ formatted)
  - Gateway: "Bank Transfer"
  - Status: "Successfully Confirmed"

---

## Email Styling Showcase

**Header Section**:
```
═══════════════════════════════════════════
        HIGH-Q
   Admin Notification
    SYSTEM EVENT ALERT
═══════════════════════════════════════════
```

**Actor Info Section**:
```
┌─────────────────────────────────────────┐
│ Triggered By:   John Admin              │
│ Email:          john@highq.com          │
│ Timestamp:      2026-05-03 14:30:45     │
└─────────────────────────────────────────┘
```

**Details Table**:
```
┌──────────────────────┬──────────────────┐
│ Field               │ Value             │
├──────────────────────┼──────────────────┤
│ Registration ID     │ REG-12345         │
│ Program Type        │ Post-UTME         │
│ Student Name        │ John Doe          │
└──────────────────────┴──────────────────┘
```

**Call-to-Action**:
```
    [ACCESS ADMIN PANEL] ← Golden button
```

---

### Registration Email Subject
`HIGH-Q Admin Update: New Registration Submitted`

### Registration Email Content
```
Event: New Registration Submitted

Triggered By: [Admin Name]
Actor Email: [Admin Email]
Time: [Timestamp]

Registration ID: [ID]
Program Type: Post-UTME
Student Name: [Full Name]
Email: [Student Email]
Phone: [Phone Number]
Amount: ₦10,000.00
Payment Reference: [Reference]
Status: Pending Admin Review

[Button: Open Admin Panel]
```

---

## Next Steps

1. **Test the system:**
   - Visit: `admin/api/test-notifications.php?action=test_all`
   - Verify all tests pass

2. **Do a live test:**
   - Submit a registration via `register-new.php`
   - Confirm email received by all admins

3. **Monitor first few transactions:**
   - Check that payments send notifications
   - Verify chat messages notify admins

4. **Feedback:**
   - If emails not arriving, check troubleshooting section
   - Verify SMTP settings in `.env`

---

## Important Notes

- **Non-blocking**: If email sending fails, it won't break the registration/payment/chat flow
- **Deduplication**: Same admin won't receive duplicate emails
- **Validation**: Only valid emails are used
- **Security**: Test script requires admin authentication
- **Logging**: All errors logged to `storage/logs/`

---

## Support Endpoints

- **Test Dashboard**: `admin/api/test-notifications.php`
- **Error Logs**: `storage/logs/mailer_debug.log` (when MAIL_DEBUG=true)
- **Function Calls**: All in `public/config/functions.php`

---

**Last Updated**: May 3, 2026
**Status**: ✅ Complete and Ready for Testing
