<?php
/**
 * HIGH Q - UI Testing Dashboard
 * Comprehensive test suite for all major functionality
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../public/config/db.php';
require_once __DIR__ . '/../public/config/functions.php';

// Get latest registration for testing
$latestReg = null;
try {
    $stmt = $pdo->query("SELECT * FROM student_registrations ORDER BY id DESC LIMIT 1");
    $latestReg = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$baseUrl = 'http://localhost/HIGH-Q';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HIGH Q - UI Testing Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
        }
        body {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
            min-height: 100vh;
            color: #fff;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
        }
        .card-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            border-radius: 20px 20px 0 0 !important;
            border: none;
        }
        .test-item {
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        .test-item:hover {
            background: rgba(255,255,255,0.1);
            transform: translateY(-2px);
        }
        .btn-test {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            border: none;
            border-radius: 10px;
            padding: 0.5rem 1.5rem;
            color: #fff;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-test:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 40px rgba(79, 70, 229, 0.3);
            color: #fff;
        }
        .btn-success-test {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        }
        .btn-warning-test {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-pending { background: rgba(245,158,11,0.2); color: #fcd34d; }
        .status-success { background: rgba(34,197,94,0.2); color: #86efac; }
        .status-error { background: rgba(239,68,68,0.2); color: #fca5a5; }
        
        #output {
            background: rgba(0,0,0,0.3);
            border-radius: 12px;
            padding: 1rem;
            font-family: 'Consolas', monospace;
            font-size: 0.9rem;
            max-height: 400px;
            overflow-y: auto;
        }
        #output .success { color: #86efac; }
        #output .error { color: #fca5a5; }
        #output .info { color: #93c5fd; }
        
        .section-title {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255,255,255,0.6);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        iframe {
            border-radius: 12px;
            border: 2px solid rgba(255,255,255,0.1);
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="text-center mb-5">
            <h1 class="display-4 fw-bold mb-2">
                <i class="bi bi-gear-fill me-2" style="color: #ffd600;"></i>
                HIGH Q Testing Dashboard
            </h1>
            <p class="text-white-50">Comprehensive UI and functionality testing suite</p>
        </div>
        
        <div class="row g-4">
            <!-- PDF Tests -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header py-3">
                        <h5 class="mb-0"><i class="bi bi-file-pdf me-2"></i>PDF Generation Tests</h5>
                    </div>
                    <div class="card-body">
                        <div class="section-title">Admission Letter</div>
                        
                        <div class="test-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>Generate Test PDF</strong>
                                    <div class="text-white-50 small">Creates a sample admission letter</div>
                                </div>
                                <a href="<?= $baseUrl ?>/public/admission_letter.php?rid=<?= $latestReg['id'] ?? 34 ?>&format=pdf" 
                                   target="_blank" class="btn btn-test">
                                    <i class="bi bi-download me-1"></i> Download
                                </a>
                            </div>
                        </div>
                        
                        <div class="test-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>View Letter (HTML Preview)</strong>
                                    <div class="text-white-50 small">Preview before downloading</div>
                                </div>
                                <a href="<?= $baseUrl ?>/public/admission_letter.php?rid=<?= $latestReg['id'] ?? 34 ?>&format=html" 
                                   target="_blank" class="btn btn-test btn-success-test">
                                    <i class="bi bi-eye me-1"></i> Preview
                                </a>
                            </div>
                        </div>
                        
                        <div class="section-title mt-4">Welcome Kit</div>
                        
                        <div class="test-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>Generate Welcome Kit PDF</strong>
                                    <div class="text-white-50 small">Creates a welcome kit for JAMB program</div>
                                </div>
                                <button onclick="testWelcomeKit()" class="btn btn-test">
                                    <i class="bi bi-file-earmark-pdf me-1"></i> Generate
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Email Tests -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header py-3">
                        <h5 class="mb-0"><i class="bi bi-envelope me-2"></i>Email Tests</h5>
                    </div>
                    <div class="card-body">
                        <div class="section-title">Test Emails</div>
                        
                        <div class="test-item">
                            <div class="row align-items-center">
                                <div class="col-12 mb-2">
                                    <strong>Send Test Email</strong>
                                    <div class="text-white-50 small">Sends a simple test email</div>
                                </div>
                                <div class="col-12">
                                    <div class="input-group">
                                        <input type="email" id="testEmail" class="form-control" 
                                               placeholder="Enter email address" 
                                               value="<?= htmlspecialchars($latestReg['email'] ?? 'akintunde.dolapo1@gmail.com') ?>">
                                        <button onclick="sendTestEmail()" class="btn btn-test">
                                            <i class="bi bi-send me-1"></i> Send
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="test-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>Send Welcome Kit Email</strong>
                                    <div class="text-white-50 small">Email with PDF attachment</div>
                                </div>
                                <button onclick="sendWelcomeKitEmail()" class="btn btn-test btn-warning-test">
                                    <i class="bi bi-envelope-paper me-1"></i> Send
                                </button>
                            </div>
                        </div>
                        
                        <div class="section-title mt-4">Output Log</div>
                        <div id="output">
                            <div class="info">Ready for testing...</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Page Tests -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header py-3">
                        <h5 class="mb-0"><i class="bi bi-window me-2"></i>Page Tests</h5>
                    </div>
                    <div class="card-body">
                        <div class="section-title">Public Pages</div>
                        
                        <div class="row g-2">
                            <div class="col-6">
                                <a href="<?= $baseUrl ?>/public/home.php" target="_blank" class="btn btn-test w-100 mb-2">
                                    <i class="bi bi-house me-1"></i> Home
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="<?= $baseUrl ?>/public/contact.php" target="_blank" class="btn btn-test w-100 mb-2">
                                    <i class="bi bi-chat me-1"></i> Contact (FAQ)
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="<?= $baseUrl ?>/public/register.php" target="_blank" class="btn btn-test w-100 mb-2">
                                    <i class="bi bi-pencil-square me-1"></i> Register
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="<?= $baseUrl ?>/public/about.php" target="_blank" class="btn btn-test w-100 mb-2">
                                    <i class="bi bi-info-circle me-1"></i> About
                                </a>
                            </div>
                        </div>
                        
                        <div class="section-title mt-4">Admin Pages</div>
                        
                        <div class="row g-2">
                            <div class="col-6">
                                <a href="<?= $baseUrl ?>/admin/login.php" target="_blank" class="btn btn-test w-100 mb-2">
                                    <i class="bi bi-box-arrow-in-right me-1"></i> Login
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="<?= $baseUrl ?>/admin/signup.php" target="_blank" class="btn btn-test w-100 mb-2">
                                    <i class="bi bi-person-plus me-1"></i> Sign Up
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="<?= $baseUrl ?>/admin/forgot_password.php" target="_blank" class="btn btn-test w-100 mb-2">
                                    <i class="bi bi-key me-1"></i> Forgot Pass
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="<?= $baseUrl ?>/admin/" target="_blank" class="btn btn-test w-100 mb-2">
                                    <i class="bi bi-speedometer2 me-1"></i> Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Receipt & WhatsApp Tests -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header py-3">
                        <h5 class="mb-0"><i class="bi bi-receipt me-2"></i>Receipt & WhatsApp</h5>
                    </div>
                    <div class="card-body">
                        <div class="section-title">Receipt Page Test</div>
                        
                        <div class="test-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>View Receipt Page</strong>
                                    <div class="text-white-50 small">Includes WhatsApp channel link</div>
                                </div>
                                <a href="<?= $baseUrl ?>/public/receipt.php?ref=TEST-<?= date('YmdHis') ?>" 
                                   target="_blank" class="btn btn-test btn-success-test">
                                    <i class="bi bi-receipt me-1"></i> View
                                </a>
                            </div>
                        </div>
                        
                        <div class="section-title mt-4">FAQ Mobile Test</div>
                        
                        <p class="text-white-50 small">Test FAQ expansion on mobile by resizing the iframe below:</p>
                        
                        <div class="d-flex gap-2 mb-3">
                            <button onclick="setFrameSize(375, 667)" class="btn btn-test btn-sm">iPhone SE</button>
                            <button onclick="setFrameSize(414, 896)" class="btn btn-test btn-sm">iPhone XR</button>
                            <button onclick="setFrameSize(768, 1024)" class="btn btn-test btn-sm">iPad</button>
                            <button onclick="setFrameSize('100%', 500)" class="btn btn-test btn-sm">Desktop</button>
                        </div>
                        
                        <div style="overflow: auto; max-width: 100%;">
                            <iframe id="faqFrame" 
                                    src="<?= $baseUrl ?>/public/contact.php#faq" 
                                    width="375" height="500"
                                    style="background: #fff;"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Database Stats -->
        <div class="card mt-4">
            <div class="card-header py-3">
                <h5 class="mb-0"><i class="bi bi-database me-2"></i>Database Statistics</h5>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <?php
                    $stats = [
                        'student_registrations' => 'Registrations',
                        'payments' => 'Payments',
                        'admin_users' => 'Admin Users',
                        'appointments' => 'Appointments'
                    ];
                    foreach ($stats as $table => $label):
                        try {
                            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                        } catch (Exception $e) {
                            $count = 'N/A';
                        }
                    ?>
                    <div class="col-6 col-md-3">
                        <div class="text-center">
                            <div class="display-4 fw-bold" style="color: #ffd600;"><?= $count ?></div>
                            <div class="text-white-50"><?= $label ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const output = document.getElementById('output');
        
        function log(msg, type = 'info') {
            const div = document.createElement('div');
            div.className = type;
            div.innerHTML = `[${new Date().toLocaleTimeString()}] ${msg}`;
            output.appendChild(div);
            output.scrollTop = output.scrollHeight;
        }
        
        function setFrameSize(w, h) {
            const frame = document.getElementById('faqFrame');
            frame.style.width = typeof w === 'number' ? w + 'px' : w;
            frame.style.height = h + 'px';
            log(`Frame resized to ${w} × ${h}`);
        }
        
        async function sendTestEmail() {
            const email = document.getElementById('testEmail').value;
            if (!email) { log('Please enter an email address', 'error'); return; }
            
            log(`Sending test email to ${email}...`);
            
            try {
                const response = await fetch('test_api.php?action=sendEmail&email=' + encodeURIComponent(email));
                const data = await response.json();
                
                if (data.success) {
                    log('✓ Email sent successfully!', 'success');
                } else {
                    log('✗ Email failed: ' + (data.error || 'Unknown error'), 'error');
                }
            } catch (e) {
                log('✗ Request error: ' + e.message, 'error');
            }
        }
        
        async function testWelcomeKit() {
            log('Generating welcome kit PDF...');
            
            try {
                const response = await fetch('test_api.php?action=generateWelcomeKit');
                const data = await response.json();
                
                if (data.success) {
                    log('✓ Welcome kit generated: ' + data.filepath, 'success');
                    if (data.downloadUrl) {
                        log(`<a href="${data.downloadUrl}" target="_blank" style="color: #86efac;">Download PDF</a>`, 'success');
                    }
                } else {
                    log('✗ Generation failed: ' + (data.error || 'Unknown error'), 'error');
                }
            } catch (e) {
                log('✗ Request error: ' + e.message, 'error');
            }
        }
        
        async function sendWelcomeKitEmail() {
            const email = document.getElementById('testEmail').value;
            log(`Sending welcome kit email to ${email}...`);
            
            try {
                const response = await fetch('test_api.php?action=sendWelcomeKitEmail&email=' + encodeURIComponent(email));
                const data = await response.json();
                
                if (data.success) {
                    log('✓ Welcome kit email sent with PDF attachment!', 'success');
                } else {
                    log('✗ Email failed: ' + (data.error || 'Unknown error'), 'error');
                }
            } catch (e) {
                log('✗ Request error: ' + e.message, 'error');
            }
        }
    </script>
</body>
</html>
