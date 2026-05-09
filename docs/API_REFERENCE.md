# 🔒 Security Scan & Report API Reference

Complete API documentation for the security scanning and reporting system.

---

## 📊 API Endpoints

### 1. **SecurityScanEngine** - `admin/api/scan-engine.php`

Runs security scans and returns threat reports.

#### **Endpoint**
```
POST /HIGH-Q/admin/api/scan-engine.php
```

#### **Parameters**
```php
scan_type: string  // 'quick', 'full', or 'malware'
```

#### **Response** (JSON)
```json
{
  "status": "ok",
  "report": {
    "scan_type": "quick",
    "started_at": "2025-12-24T10:30:00+00:00",
    "finished_at": "2025-12-24T10:35:00+00:00",
    "totals": {
      "files_scanned": 450,
      "critical_issues": 2,
      "warnings": 5,
      "info_messages": 12
    },
    "critical": [
      {
        "type": "eval_shell",
        "file": "admin/pages/test.php",
        "message": "Suspicious pattern detected: eval_shell"
      }
    ],
    "warnings": [...],
    "info": [...]
  }
}
```

#### **Permissions**
- Requires: `sentinel` permission (admin only)

#### **Example Usage**
```javascript
// JavaScript
fetch('/HIGH-Q/admin/api/scan-engine.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/x-www-form-urlencoded'},
  body: 'scan_type=quick'
})
.then(r => r.json())
.then(data => console.log(data.report));

// Bash/cURL
curl -X POST http://localhost/HIGH-Q/admin/api/scan-engine.php \
  -d "scan_type=full"
```

#### **Scan Types**

| Type | Duration | Coverage | Use Case |
|------|----------|----------|----------|
| `quick` | 2-5 min | 1,000 files | Fast surface check |
| `full` | 10-15 min | All files | Comprehensive audit |
| `malware` | 5-10 min | 2,000 files | Threat detection |

---

### 2. **ReportGenerator** - `admin/includes/report-generator.php`

Generates professional HTML and plain text reports.

#### **Class**
```php
class ReportGenerator {
    public function __construct($scanData)
    public function generateHtmlEmail(): string
    public function generatePlainText(): string
}
```

#### **Usage**
```php
require 'admin/includes/report-generator.php';

$scanData = [...results from scan-engine...];
$generator = new ReportGenerator($scanData);

// HTML for email
$html = $generator->generateHtmlEmail();

// Plain text for fallback
$text = $generator->generatePlainText();
```

#### **Output Features**
- Professional gradient header
- Color-coded threat summary
- Risk level badge (CRITICAL/WARNING/SAFE)
- Detailed findings with file names
- Smart recommendations
- Responsive email design
- Timestamp and company branding

---

### 3. **SendReport API** - `admin/api/send-report.php`

Generates and sends scan reports via email.

#### **Endpoint**
```
POST /HIGH-Q/admin/api/send-report.php
Content-Type: application/json
```

#### **Parameters** (JSON)
```json
{
  "scan_data": {
    "status": "completed",
    "report": {...scan result from engine...}
  },
  "recipient_email": "admin@example.com",
  "send_email": true
}
```

#### **Response** (JSON)
```json
{
  "status": "success",
  "message": "Report generated and sent successfully",
  "report_html": "<!DOCTYPE html>...",
  "report_text": "SECURITY SCAN REPORT...",
  "recipient": "admin@example.com",
  "sent": true
}
```

#### **Permissions**
- Requires: `sentinel` permission (admin only)

#### **Example Usage**
```javascript
// Fetch API
const scanData = await fetch('/HIGH-Q/admin/api/scan-engine.php', {
  method: 'POST',
  body: 'scan_type=quick'
}).then(r => r.json());

// Send report
const result = await fetch('/HIGH-Q/admin/api/send-report.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({
    scan_data: scanData,
    recipient_email: 'admin@company.com',
    send_email: true
  })
}).then(r => r.json());

console.log(result.message);
```

#### **Report Storage**
- Location: `storage/scan_reports/report_*.json`
- Format: JSON with scan data, timestamp, recipient, sent status
- Retention: Historical records (no auto-delete)

---

### 4. **Scheduler** - `bin/scan-scheduler.php`

Automatic scheduled scanning with email reports.

#### **Command**
```bash
php /path/to/bin/scan-scheduler.php
```

#### **Configuration** (from settings table)
```json
{
  "security": {
    "scan_schedule": "daily|weekly|monthly",
    "scan_type": "quick|full|malware",
    "last_scan_at": "2025-12-24 10:00:00"
  }
}
```

#### **Behavior**
- Reads schedule from database settings
- Runs scan if schedule is due
- Automatically sends report via email
- Updates last_scan_at timestamp
- Logs to error_log

#### **Setup (Windows Task Scheduler)**
```
Program: C:\xampp\php\php.exe
Arguments: C:\xampp\htdocs\HIGH-Q\bin\scan-scheduler.php
Schedule: Daily 2:00 AM
```

#### **Setup (Linux/Mac Cron)**
```bash
0 2 * * * php /var/www/HIGH-Q/bin/scan-scheduler.php
```

---

## 🎨 Email Report Template

### **Structure**
```
┌─────────────────────────────────────┐
│  [Gradient Header with Logo]        │
│  HIGH Q SOLID ACADEMY               │
│  Security Scan Report               │
├─────────────────────────────────────┤
│                                     │
│  Risk Level: [CRITICAL/WARNING/SAFE]│
│                                     │
│  ┌──────┬────────┬──────┬────────┐ │
│  │Crit. │Warnings│Files │Scan Type│ │
│  │  2   │   5    │ 450  │  Full  │ │
│  └──────┴────────┴──────┴────────┘ │
│                                     │
│  CRITICAL ISSUES                    │
│  ───────────────────────────────    │
│  • Issue 1 (file.php)               │
│  • Issue 2 (config.php)             │
│                                     │
│  WARNINGS                           │
│  ───────────────────────────────    │
│  • Warning 1 (admin.php)            │
│                                     │
│  RECOMMENDATIONS                    │
│  ───────────────────────────────    │
│  ✓ Review and Patch                 │
│  ✓ Regular Monitoring               │
│  ✓ Backup & Recovery                │
│                                     │
├─────────────────────────────────────┤
│ Generated: 2025-12-24 10:30:00      │
│ Report ID: report_full_20251224...  │
└─────────────────────────────────────┘
```

---

## 📧 Email Configuration

### **.env Settings**
```env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=akintunde.dolapo1@gmail.com
MAIL_PASSWORD=trqz edje gfow pzfd
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=akintunde.dolapo1@gmail.com
MAIL_FROM_NAME="HIGH Q SOLID ACADEMY"
```

### **Email Features**
- TLS encryption
- Company branding
- HTML + plain text
- File attachments (optional)
- Audit logging

---

## 🔍 Scan Type Comparison

### **Quick Scan** (2-5 min)
```
✓ PHP files syntax check
✓ Suspicious patterns (eval, exec, base64_decode)
✓ Exposed sensitive files
✓ 1,000 file limit
✗ No integrity check
✗ No dependency audit
```

### **Full Scan** (10-15 min)
```
✓ All Quick Scan features +
✓ File integrity (MD5 hashing)
✓ Composer dependency audit
✓ PHPStan static analysis
✓ Environment checks
✓ Permission audit
✗ Slower (complete system)
```

### **Malware Scan** (5-10 min)
```
✓ File integrity baseline
✓ Webshell signatures
✓ Suspicious code patterns
✓ Large file detection
✓ Obfuscation detection
✓ 2,000 file limit
✗ Focused on malware only
```

---

## 🗄️ Database Schema (Optional)

### **security_scans Table**
```sql
CREATE TABLE security_scans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    scan_type VARCHAR(20) NOT NULL,
    status VARCHAR(20) NOT NULL,
    critical_count INT,
    warning_count INT,
    info_count INT,
    files_scanned INT,
    started_at DATETIME,
    finished_at DATETIME,
    report_file VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_scan_type (scan_type),
    INDEX idx_created (created_at)
);
```

### **Report Storage** (File-based)
```
storage/
└── scan_reports/
    ├── report_quick_20251224_093000.json
    ├── report_full_20251224_103000.json
    └── report_malware_20251224_113000.json
```

---

## 🔐 Security Considerations

### **Access Control**
```php
requirePermission('sentinel');  // Admin only
```

### **CSRF Protection**
- API endpoints require valid session
- Use X-Requested-With header for AJAX

### **Data Protection**
- Reports stored in `storage/` (not web-accessible)
- Email transmitted via TLS
- Logged in audit_logs table
- No sensitive data in plain text

### **Rate Limiting** (optional)
Consider adding rate limiting to prevent scan abuse:
```php
// Run one scan at a time (check for running processes)
$running = exec('ps aux | grep scan-engine');
if (strpos($running, 'scan-engine.php') !== false) {
    throw new Exception('Scan already running');
}
```

---

## 📝 Logging & Monitoring

### **Error Log** (`storage/logs/php-error.log`)
```
[24-Dec-2025 10:30:00 UTC] SecurityScanEngine: Quick scan started
[24-Dec-2025 10:35:00 UTC] SecurityScanEngine: Quick scan completed (450 files)
[24-Dec-2025 10:35:15 UTC] ReportGenerator: HTML report generated
[24-Dec-2025 10:35:20 UTC] sendEmail: Report sent to admin@company.com
```

### **Audit Log** (database `audit_logs` table)
```
user_id | action | meta | created_at
--------|--------|------|------------
0 | security_scan_scheduled | {...} | 2025-12-24 10:35:00
1 | security_report_sent | {...} | 2025-12-24 10:35:20
```

### **Email Debug Log** (`storage/logs/mailer_debug.log`)
```
[2025-12-24T10:35:20] [level=0] Connection -> EHLO smtp.gmail.com
[2025-12-24T10:35:21] [level=0] ...authentication successful
[2025-12-24T10:35:22] [level=0] Message sent!
```

---

## 🚀 Advanced Usage

### **Custom Report Recipient**
```javascript
const result = await fetch('/HIGH-Q/admin/api/send-report.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({
    scan_data: scanData,
    recipient_email: 'custom@example.com',  // Override default
    send_email: true
  })
});
```

### **Generate Report Without Sending**
```javascript
const result = await fetch('/HIGH-Q/admin/api/send-report.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({
    scan_data: scanData,
    send_email: false  // Get HTML without sending
  })
});

// HTML report in result.report_html
const html = result.report_html;
```

### **Bulk Email Reports**
```php
// Send same report to multiple recipients
$recipients = ['admin@company.com', 'security@company.com', 'cto@company.com'];
foreach ($recipients as $to) {
    sendEmail(
        $to,
        'Security Scan Report',
        $reportData['report_html'],
        []
    );
}
```

---

## 🐛 Troubleshooting

### **Scan Not Running**
1. Check PHP execution permissions: `php -v`
2. Verify database connection: Check `admin/includes/db.php`
3. Check logs: `storage/logs/php-error.log`

### **Email Not Sending**
1. Verify SMTP settings in `.env`
2. Check debug log: `storage/logs/mailer_debug.log`
3. Test email configuration separately
4. Ensure recipient email is valid
5. Check spam folder

### **Report HTML Not Rendering**
1. Try plain text fallback
2. Test in different email client
3. Check for CSS support (inline styles used)
4. Validate HTML structure

### **Scheduler Not Triggering**
1. Verify Task Scheduler / Cron setup
2. Check system logs for errors
3. Test manual execution: `php bin/scan-scheduler.php`
4. Verify database settings table exists

---

## 📞 Support

- **Documentation:** See [SCAN_SYSTEM_COMPLETE.md](SCAN_SYSTEM_COMPLETE.md)
- **API Tests:** Use [tmp_test_scan.html](tmp_test_scan.html)
- **Logs:** Check `storage/logs/` directory
- **Errors:** View Apache error log or PHP error log

---

*Last Updated: 2025-12-24*  
*Status: Production Ready ✨*
