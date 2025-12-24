# 🕐 Automated Security Scan Scheduler Setup

Complete guide to setting up automatic daily security scans with email reports.

---

## 📋 Overview

The security scan scheduler automatically runs security scans at 2:00 AM every day and sends email reports when critical issues are found.

**Features:**
- ✅ Automatic daily scanning
- ✅ Multiple scan types (Quick/Full/Malware)
- ✅ Professional email reports
- ✅ Audit logging
- ✅ No manual intervention needed

---

## 🪟 Windows Setup (Recommended for your system)

### Quick Setup (Automated)

**Step 1:** Open Command Prompt as Administrator
```
1. Press Windows Key
2. Type "cmd"
3. Right-click "Command Prompt"
4. Click "Run as administrator"
```

**Step 2:** Run the setup script
```bash
C:\xampp\htdocs\HIGH-Q\setup-task-scheduler.bat
```

The script will:
- ✓ Verify PHP installation
- ✓ Locate scan-scheduler.php
- ✓ Create Windows Task Scheduler job
- ✓ Test the scheduler
- ✓ Display confirmation

**That's it!** Scans will run automatically at 2:00 AM daily.

---

### Manual Setup (If script doesn't work)

**Step 1:** Open Task Scheduler
```
1. Press Windows Key
2. Type "Task Scheduler"
3. Click "Task Scheduler"
```

**Step 2:** Create Basic Task
```
1. Right-click "Task Scheduler (Local)"
2. Click "Create Basic Task"
3. Name: "HIGH-Q Security Scan"
4. Click "Next"
```

**Step 3:** Set Schedule
```
1. Select "Daily"
2. Click "Next"
3. Set time to 2:00 AM
4. Click "Next"
```

**Step 4:** Set Action
```
1. Select "Start a program"
2. Click "Next"
3. Program/script: C:\xampp\php\php.exe
4. Add arguments: C:\xampp\htdocs\HIGH-Q\bin\scan-scheduler.php
5. Click "Next"
```

**Step 5:** Finish
```
1. Review settings
2. Click "Finish"
3. Task is created!
```

### Verify Windows Setup

**Method 1: Task Scheduler**
```
1. Open Task Scheduler
2. Look for "HIGH-Q Security Scan" in task list
3. Click to view details
4. Status should show "Ready" or "Running"
```

**Method 2: Check Logs**
```
Open: C:\xampp\htdocs\HIGH-Q\storage\logs\php-error.log
Look for: "scan-scheduler started at..."
```

---

## 🐧 Linux Setup

### Quick Setup (Automated)

**Step 1:** Make script executable
```bash
chmod +x /path/to/HIGH-Q/setup-cron.sh
```

**Step 2:** Run setup script
```bash
/path/to/HIGH-Q/setup-cron.sh
```

The script will:
- ✓ Detect PHP installation
- ✓ Verify scheduler location
- ✓ Add cron job
- ✓ Verify setup

**That's it!** Scans will run automatically at 2:00 AM daily.

---

### Manual Setup (If script doesn't work)

**Step 1:** Open crontab editor
```bash
crontab -e
```

**Step 2:** Add this line (replace /path/to with actual path)
```
0 2 * * * /usr/bin/php /path/to/HIGH-Q/bin/scan-scheduler.php >> /dev/null 2>&1
```

**Step 3:** Save and exit
```
Press Ctrl+X (or your editor's save command)
Confirm to save
```

### Verify Linux Setup

**View all cron jobs:**
```bash
crontab -l
```

You should see:
```
0 2 * * * /usr/bin/php /path/to/HIGH-Q/bin/scan-scheduler.php >> /dev/null 2>&1
```

**Check logs:**
```bash
tail -f /path/to/HIGH-Q/storage/logs/php-error.log
```

---

## 🍎 macOS Setup

Same as Linux:

**Quick Setup:**
```bash
chmod +x /path/to/HIGH-Q/setup-cron.sh
/path/to/HIGH-Q/setup-cron.sh
```

**Manual Setup:**
```bash
crontab -e
```

Add:
```
0 2 * * * /usr/local/bin/php /path/to/HIGH-Q/bin/scan-scheduler.php >> /dev/null 2>&1
```

**Note:** On macOS, use `/usr/local/bin/php` (or `which php` to find correct path)

---

## ⚙️ Configuration

### Schedule Types

The scheduler reads from your database settings and supports:

| Schedule | Frequency | Typical Use |
|----------|-----------|------------|
| `daily` | Every day | Regular monitoring |
| `weekly` | Once per week | Comprehensive audits |
| `monthly` | Once per month | Deep security review |

### Change Schedule

**Via Admin Panel:**
1. Go to Admin → Settings
2. Find "Security Scan Schedule"
3. Select: Daily / Weekly / Monthly
4. Save

**Via Database (SQL):**
```sql
UPDATE settings 
SET value = JSON_SET(
    value, 
    '$.security.scan_schedule', 
    'daily'
)
WHERE key = 'system_settings';
```

### Change Scan Type

**Default:** Full Scan (comprehensive)

**Options:**
- `quick` - Fast surface-level checks (2-5 min)
- `full` - Complete system audit (10-15 min)
- `malware` - Threat detection focus (5-10 min)

**Change via Database:**
```sql
UPDATE settings 
SET value = JSON_SET(
    value, 
    '$.security.scan_type', 
    'full'
)
WHERE key = 'system_settings';
```

---

## 📧 Email Configuration

Reports are sent to company email on critical findings.

### Company Email

**Current:** akintunde.dolapo1@gmail.com

**Location:** `.env` file
```env
MAIL_FROM_ADDRESS=akintunde.dolapo1@gmail.com
MAIL_FROM_NAME="HIGH Q SOLID ACADEMY"
```

### Change Email

Edit `.env`:
```env
MAIL_FROM_ADDRESS=your-email@example.com
MAIL_FROM_NAME="Your Company Name"
```

### Email Credentials

**Current SMTP:**
```env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=akintunde.dolapo1@gmail.com
MAIL_PASSWORD=trqz edje gfow pzfd
MAIL_ENCRYPTION=tls
```

---

## 📊 What Happens When Scheduler Runs

```
2:00 AM → Scheduler starts
  ↓
Read settings (schedule, scan_type, last_scan)
  ↓
Check if scan is due (daily/weekly/monthly)
  ↓
If NOT due → Exit (try again tomorrow)
  ↓
If DUE → Run security scan
  ↓
Execute scan type (Quick/Full/Malware)
  ↓
Generate report
  ↓
If critical issues found → Send email
  ↓
Save report to storage/scan_reports/
  ↓
Log action in audit_logs table
  ↓
Update last_scan_at timestamp
  ↓
Scheduler ends
```

---

## 📝 Logs & Monitoring

### Error Log
```
Location: C:\xampp\htdocs\HIGH-Q\storage\logs\php-error.log
Contains: Scan errors, email sending issues, database problems
```

### Email Debug Log
```
Location: C:\xampp\htdocs\HIGH-Q\storage\logs\mailer_debug.log
Contains: SMTP communication, email sending details
```

### Report Storage
```
Location: C:\xampp\htdocs\HIGH-Q\storage\scan_reports\
Files: report_*.json (one per scan)
```

### Audit Log
```
Table: audit_logs (database)
Action: security_scan_scheduled
Contains: Scan type, findings, timestamp
```

### View Recent Scans
```sql
SELECT * FROM audit_logs 
WHERE action = 'security_scan_scheduled' 
ORDER BY created_at DESC 
LIMIT 10;
```

---

## 🐛 Troubleshooting

### Scan Not Running

**Windows:**
1. Open Task Scheduler
2. Find "HIGH-Q Security Scan"
3. Right-click → Run
4. Check if it executes
5. Review error log if it fails

**Linux/Mac:**
```bash
# Run manually to test
php /path/to/bin/scan-scheduler.php

# Check for errors
tail -f /path/to/storage/logs/php-error.log
```

### Email Not Sending

**Check:**
1. Verify SMTP settings in `.env`
2. Check `storage/logs/mailer_debug.log`
3. Ensure recipient email is valid
4. Check email spam folder
5. Verify database connection

**Test email:**
```bash
php -r "
require 'vendor/autoload.php';
require 'admin/includes/db.php';
require 'admin/includes/functions.php';
sendEmail('test@example.com', 'Test', '<h1>Test</h1>');
"
```

### Scheduler Not Starting (Windows)

**Check:**
1. Open Task Scheduler → View All Tasks
2. Find "HIGH-Q Security Scan"
3. Check if "Enabled" is checked
4. Check "Last Run Time" and "Last Run Result"
5. If result is error code, review error log

**Re-run setup:**
```
Admin Cmd → setup-task-scheduler.bat
```

### Scheduler Not Running (Linux)

**Check cron:**
```bash
# List all cron jobs
crontab -l

# Check if entry exists
crontab -l | grep scan-scheduler

# Check cron logs (if available)
grep CRON /var/log/syslog  # Ubuntu/Debian
log show --predicate 'process == "cron"'  # macOS
```

**Verify PHP path:**
```bash
which php
# Use this path in crontab
```

---

## 🔧 Manual Testing

### Test Scheduler Now (Windows)

**Via Command Prompt:**
```bash
C:\xampp\php\php.exe C:\xampp\htdocs\HIGH-Q\bin\scan-scheduler.php
```

**Should output:**
```
Scan completed successfully. Report: ...
```

### Test Scheduler Now (Linux)

**Via Terminal:**
```bash
php /path/to/HIGH-Q/bin/scan-scheduler.php
```

**Should output:**
```
Scan completed successfully. Report: ...
```

### Manual Scan via Admin Panel

Instead of waiting for 2 AM:
1. Go to Admin → Security Scan
2. Select scan type
3. Click "Start Scan"
4. Click "Email Report"
5. Get report immediately

---

## 📋 Management Commands

### Windows Task Scheduler

```bash
# View all tasks
tasklist /v

# View specific task
schtasks /query /tn "HIGH-Q Security Scan" /v

# Disable task
schtasks /change /tn "HIGH-Q Security Scan" /disable

# Enable task
schtasks /change /tn "HIGH-Q Security Scan" /enable

# Run immediately
schtasks /run /tn "HIGH-Q Security Scan"

# Delete task
schtasks /delete /tn "HIGH-Q Security Scan" /f
```

### Linux Cron

```bash
# List all cron jobs
crontab -l

# Edit cron jobs
crontab -e

# Remove all cron jobs
crontab -r

# View cron logs
tail -f /var/log/cron  # Some systems
```

---

## 📊 Verifying Success

**Checklist after setup:**

- [ ] Task/Cron created successfully
- [ ] Test run executes without errors
- [ ] Log shows "scan-scheduler started"
- [ ] Report file created in `storage/scan_reports/`
- [ ] Email sent (check inbox/spam)
- [ ] Audit log updated with scan record
- [ ] No errors in error logs

---

## 🎯 Next Steps

1. **Run setup script** (Windows or Linux)
2. **Test manually** to verify it works
3. **Check logs** to confirm execution
4. **Adjust schedule** in Admin panel if needed
5. **Monitor** for first automatic run tomorrow

---

## 💡 Pro Tips

**Schedule Multiple Scans:**
```
Windows: Create additional tasks at different times
Linux: Add multiple cron entries
Example: 0 2 * * * ... (Quick Scan)
         0 10 * * 0 ... (Full Scan on Sunday)
```

**Get Notified:**
- Emails sent automatically on critical findings
- Check Admin → Security Scan page for history
- Review storage/scan_reports/ for archives

**Backup Reports:**
```bash
# Archive old reports
tar -czf scan-reports-backup-$(date +%Y%m%d).tar.gz storage/scan_reports/
```

---

## ✅ You're All Set!

Your security scan scheduler is now configured to:
- ✅ Run automatically every day at 2:00 AM
- ✅ Check for threats automatically
- ✅ Send email alerts on critical findings
- ✅ Store reports for compliance
- ✅ Maintain audit trail

**Sit back and let it run!** 🎉

---

*Automated Security Scan Setup Guide - 2025-12-24*  
*For: HIGH-Q Examination Platform*
