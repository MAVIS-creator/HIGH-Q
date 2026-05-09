# 🚀 Quick Start Guide - Security Scan System

Get up and running with the new security scan and reporting system in 5 minutes!

---

## ⚡ 30-Second Overview

**What:** Professional security scanning system with beautiful email reports  
**Who:** Admins with `sentinel` permission  
**Where:** Admin Panel → Security Scan page  
**How:** 1. Click "Start Scan" 2. Wait for results 3. Click "Email Report"

---

## 🎯 Test It Now (2 minutes)

### Step 1: Open the Test Page
```
http://localhost/HIGH-Q/tmp_test_scan.html
```

### Step 2: Click a Scan Button
- ⚡ **Quick Scan** (Fast: 2-5 min, limited coverage)
- 🔍 **Full Scan** (Complete: 10-15 min, everything)
- 🦠 **Malware Scan** (Focused: 5-10 min, threats only)

### Step 3: Watch Results
- See threat counts in real-time
- View critical issues found
- Review warnings and info

### Step 4: Send Report (optional)
- Reports automatically saved to `storage/scan_reports/`
- Look for beautiful HTML email report

---

## 🛠️ Production Setup (5 minutes)

### 1. Access Admin Panel
```
http://localhost/HIGH-Q/admin/
```

### 2. Navigate to Security Scan
```
Admin → Security Scan
```

### 3. Run Your First Scan
1. Choose scan type from radio buttons
2. Optionally enter recipient email (or leave blank for default)
3. Click **"Start Scan"** button
4. Monitor progress bar
5. View threat summary when complete

### 4. Send Report Email (NEW!)
1. After scan completes, results display
2. Recipient field shows default company email
3. Click **"Email Report"** button
4. Check email for styled report!

---

## 📧 What the Email Report Contains

When you click "Email Report," you get a professional HTML email with:

```
┌─────────────────────────────────┐
│ 🔒 Security Scan Report         │
│ HIGH Q SOLID ACADEMY            │
├─────────────────────────────────┤
│                                 │
│ Risk Level: CRITICAL            │ ← Color-coded badge
│                                 │
│ 📊 Threat Summary:              │
│ ┌────────────┐ ┌────────────┐  │
│ │ 2 Critical │ │ 5 Warnings │  │
│ └────────────┘ └────────────┘  │
│                                 │
│ 🚨 Critical Issues:             │
│ • eval() detected (test.php)    │
│ • exec() usage (admin.php)      │
│                                 │
│ 💡 Recommendations:             │
│ ✓ Review and patch vulnerable   │
│ ✓ Run regular scans             │
│ ✓ Maintain backups              │
│                                 │
└─────────────────────────────────┘
```

---

## ⏰ Set Up Automatic Scans (5 minutes)

### **Windows Task Scheduler:**
1. Open Task Scheduler
2. Create Basic Task
3. Name: "Daily Security Scan"
4. Trigger: Daily at 2:00 AM
5. Action: 
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `C:\xampp\htdocs\HIGH-Q\bin\scan-scheduler.php`
6. Click OK

### **Linux/Mac Cron:**
```bash
# Edit crontab
crontab -e

# Add this line (runs daily at 2 AM):
0 2 * * * php /var/www/HIGH-Q/bin/scan-scheduler.php
```

**Result:** Scans run automatically, email reports sent on critical findings!

---

## 📊 Understanding Scan Types

### **⚡ Quick Scan** - Best For: Regular Checks
- ✓ Fast (2-5 minutes)
- ✓ Detects obvious threats
- ✓ Good for daily use
- Limited to 1,000 files
- **Use Case:** Daily automated scans, quick security checks

### **🔍 Full Scan** - Best For: Comprehensive Audit
- ✓ Complete coverage
- ✓ Checks everything (integrity, dependencies, permissions)
- ✓ Best security
- Takes longer (10-15 min)
- **Use Case:** Weekly scheduled scans, before major deployments

### **🦠 Malware Scan** - Best For: Threat Detection
- ✓ Focused on malware
- ✓ Webshell detection
- ✓ Suspicious code patterns
- ✓ File integrity baseline
- **Use Case:** Monthly deep scans, incident response

---

## 🔍 Reading the Reports

### **Risk Level**
- 🔴 **CRITICAL** → Immediate action required
- 🟠 **WARNING** → Should be addressed
- 🟢 **SAFE** → No major issues

### **Threat Numbers**
- **Critical Issues** → Security vulnerabilities requiring immediate attention
- **Warnings** → Suspicious patterns or configurations
- **Info** → Informational messages

### **Findings**
Each finding shows:
- Message describing the issue
- Filename/path affected
- Type of finding (webshell, syntax error, etc.)

---

## 💾 Where Are Reports Stored?

### **Automatic Storage**
Reports automatically saved to:
```
storage/scan_reports/
└── report_quick_20251224_093000.json
└── report_full_20251224_103000.json
└── report_malware_20251224_113000.json
```

Each file contains:
- Full scan data
- Timestamp
- Recipient email
- Whether it was sent

### **Email Reports**
- Sent to: Company email (configurable per report)
- Format: Beautiful HTML + plain text fallback
- Subject: `[HIGH Q] Security Scan Report - [Scan Type] Scan`

### **History & Auditing**
Reports also logged in database:
- Table: `audit_logs`
- User: System (user_id = 0)
- Action: `security_report_sent`
- Details: Critical/warning counts, recipient

---

## ⚙️ Configuration

### **Default Company Email**
Located in `.env`:
```env
MAIL_FROM_ADDRESS=akintunde.dolapo1@gmail.com
MAIL_FROM_NAME="HIGH Q SOLID ACADEMY"
```

To change: Edit `.env` and update `MAIL_FROM_ADDRESS`

### **Custom Recipient per Report**
When sending report, specify different email:
1. In Admin panel: Enter email in "Email Report To" field
2. Via API: Pass `recipient_email` parameter
3. Leave blank to use company email

### **Scheduled Scan Settings**
Located in database `settings` table:
```json
{
  "security": {
    "scan_schedule": "daily",      // daily|weekly|monthly
    "scan_type": "full",           // quick|full|malware
    "last_scan_at": "2025-12-24..."
  }
}
```

---

## 🐛 Troubleshooting

### **Scan Isn't Running?**
1. Go to Admin panel
2. Click "Start Scan"
3. Make sure browser doesn't block popups
4. Check browser console for errors (F12)
5. Check server logs: `storage/logs/php-error.log`

### **Email Not Received?**
1. Check spam/junk folder
2. Verify recipient email is correct
3. Check mail logs: `storage/logs/mailer_debug.log`
4. Verify SMTP settings in `.env`

### **Email Looks Bad?**
- Try different email client (Gmail, Outlook, Apple)
- Check email client settings (HTML vs plain text)
- May need to enable images from sender

### **Report Not Sent?**
1. Run manual test:
   ```bash
   php bin/scan-scheduler.php
   ```
2. Check for output/errors
3. Review logs

---

## 📚 Full Documentation

For more details, see:
- [SCAN_SYSTEM_COMPLETE.md](SCAN_SYSTEM_COMPLETE.md) - Comprehensive guide
- [API_REFERENCE.md](API_REFERENCE.md) - Technical API docs
- [SCAN_CONSOLIDATION_SUMMARY.md](SCAN_CONSOLIDATION_SUMMARY.md) - Architecture

---

## 🎓 Common Tasks

### **Generate & Send Report Now**
1. Admin → Security Scan
2. Select scan type
3. Click "Start Scan"
4. Once complete, click "Email Report"
5. Enter recipient (optional)
6. Done! Check email

### **View Past Reports**
```
storage/scan_reports/
```
Each JSON file contains complete scan data

### **Change Scan Schedule**
Via Admin panel or directly in database:
```sql
UPDATE settings 
SET value = JSON_SET(value, '$.security.scan_schedule', 'weekly')
WHERE key = 'system_settings';
```

### **Send Report to Multiple People**
1. Send first report via UI
2. For additional recipients, use email forward functionality
3. Or configure scheduled scans to send to multiple addresses

---

## 🎯 Best Practices

1. **Regular Scans**
   - Run Quick Scan weekly
   - Run Full Scan monthly
   - Run Malware Scan bi-weekly

2. **Email Distribution**
   - Send reports to security team
   - Archive reports for compliance
   - Act on critical findings within 24 hours

3. **Threat Response**
   - Review all critical issues immediately
   - Document remediation steps
   - Run scan again after fixes

4. **Documentation**
   - Save important reports
   - Track scan history
   - Use for security audits

---

## ✅ Verification Checklist

Before considering setup complete:

- [ ] Can access Admin → Security Scan page
- [ ] Can run Quick Scan successfully
- [ ] Can view threat results
- [ ] Can click "Email Report" button
- [ ] Email received with styled report
- [ ] Report displays correctly in email client
- [ ] Optional: Set up scheduled scans
- [ ] Optional: Customize recipient email

---

## 🎉 You're Done!

Your security scan system is now ready to use. 

**Next steps:**
1. Run a scan to test: Admin → Security Scan
2. Review the threat report
3. Set up scheduled scans (optional but recommended)
4. Act on any critical findings

**Questions?** See full documentation in project root.

---

*Quick Start Guide - 2025-12-24*  
*Security Scan System v1.0 - Production Ready*
