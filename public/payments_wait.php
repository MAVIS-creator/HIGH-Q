<?php
// public/payments_wait.php - Modern React-like UI Payment Waiting Page
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/csrf.php';
$siteSettings = [];
require_once __DIR__ . '/config/functions.php';
$ref = $_GET['ref'] ?? (isset($_SESSION['last_payment_reference']) ? $_SESSION['last_payment_reference'] : '');
$payment = null;
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) session_start();
$HQ_SUBPATH = '';
// Compute an effective base for API calls
if (function_exists('app_url')) {
  $HQ_BASE = rtrim(app_url(''), '/');
  $HQ_EFFECTIVE_BASE = $HQ_BASE;
} else {
  $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\');
  $HQ_EFFECTIVE_BASE = rtrim($proto . '://' . $host, '/') . ($scriptDir ? $scriptDir : '');
  $HQ_BASE = rtrim($proto . '://' . $host, '/');
}

// Fallback: if we didn't find payment by reference, try session-stored payment id
if (!$payment && empty($ref) && !empty($_SESSION['last_payment_id'])) {
  try {
    $stmt = $pdo->prepare('SELECT p.*, u.email, u.name FROM payments p LEFT JOIN users u ON u.id = p.student_id WHERE p.id = ? LIMIT 1');
    $stmt->execute([ (int) $_SESSION['last_payment_id'] ]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($payment && !empty($payment['reference'])) {
      $ref = $payment['reference'];
    }
  } catch (Throwable $e) { /* ignore */ }
}

// load site settings
try {
  $stmtS = $pdo->query("SELECT * FROM site_settings ORDER BY id ASC LIMIT 1");
  $siteSettings = $stmtS->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) { $siteSettings = []; }
$payment = null;
if ($ref) {
  $stmt = $pdo->prepare('SELECT p.*, u.email, u.name FROM payments p LEFT JOIN users u ON u.id = p.student_id WHERE p.reference = ? LIMIT 1');
  $stmt->execute([$ref]);
  $payment = $stmt->fetch(PDO::FETCH_ASSOC);

  // enforce 2-day expiry for unpaid pending links
  try {
    if ($payment && !empty($payment['created_at'])) {
      $createdTs = strtotime($payment['created_at']);
      $expirySeconds = 2 * 24 * 60 * 60; // 2 days
      if (time() - $createdTs > $expirySeconds && in_array($payment['status'], ['pending'])) {
        $upd = $pdo->prepare('UPDATE payments SET status = "expired", updated_at = NOW() WHERE id = ?');
        $upd->execute([$payment['id']]);
        $payment['status'] = 'expired';
      }
    }
  } catch (Throwable $e) { /* ignore */ }
}

$csrf = generateToken('signup_form');
$bankName = $siteSettings['bank_name'] ?? '[Bank Name]';
$bankAccount = $siteSettings['bank_account_number'] ?? '[Account Number]';
$bankAccountName = $siteSettings['bank_account_name'] ?? 'High Q Solid Academy Limited';
$logoUrl = app_url('assets/images/hq-logo.jpeg');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Complete Your Payment - HIGH Q SOLID ACADEMY</title>
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    
    :root {
      --hq-navy: #0b1a2c;
      --hq-navy-light: #1e3a5f;
      --hq-gold: #ffd600;
      --hq-gold-dark: #e6c200;
      --hq-success: #22c55e;
      --hq-warning: #f59e0b;
      --hq-danger: #ef4444;
      --hq-text: #1e293b;
      --hq-text-muted: #64748b;
      --hq-bg: #f8fafc;
      --hq-white: #ffffff;
      --hq-border: #e2e8f0;
      --radius-sm: 8px;
      --radius-md: 12px;
      --radius-lg: 16px;
      --radius-xl: 24px;
      --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
      --shadow-md: 0 4px 12px rgba(0,0,0,0.1);
      --shadow-lg: 0 12px 40px rgba(0,0,0,0.12);
      --shadow-glow: 0 0 30px rgba(255, 214, 0, 0.3);
    }

    body {
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
      background: linear-gradient(135deg, var(--hq-navy) 0%, var(--hq-navy-light) 50%, var(--hq-navy) 100%);
      min-height: 100vh;
      color: var(--hq-text);
      overflow-x: hidden;
    }

    /* Animated background */
    .bg-pattern {
      position: fixed;
      inset: 0;
      z-index: 0;
      overflow: hidden;
      pointer-events: none;
    }
    .bg-pattern::before,
    .bg-pattern::after {
      content: '';
      position: absolute;
      width: 600px;
      height: 600px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(255, 214, 0, 0.1) 0%, transparent 70%);
      animation: float 20s infinite ease-in-out;
    }
    .bg-pattern::before {
      top: -200px;
      left: -200px;
    }
    .bg-pattern::after {
      bottom: -200px;
      right: -200px;
      animation-delay: -10s;
    }
    @keyframes float {
      0%, 100% { transform: translate(0, 0) scale(1); }
      50% { transform: translate(50px, 50px) scale(1.1); }
    }

    /* Header */
    .header {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      padding: 16px 24px;
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: var(--shadow-md);
    }
    .header-inner {
      max-width: 1000px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .header img {
      width: 48px;
      height: 48px;
      border-radius: var(--radius-sm);
      object-fit: contain;
    }
    .header-text h1 {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--hq-navy);
    }
    .header-text p {
      font-size: 0.8rem;
      color: var(--hq-text-muted);
    }
    .header-badge {
      margin-left: auto;
      display: flex;
      align-items: center;
      gap: 8px;
      background: linear-gradient(135deg, var(--hq-success) 0%, #16a34a 100%);
      color: white;
      padding: 8px 16px;
      border-radius: 999px;
      font-size: 0.8rem;
      font-weight: 600;
    }
    .header-badge i { font-size: 1rem; }

    /* Progress Steps */
    .progress-container {
      max-width: 600px;
      margin: 30px auto 0;
      padding: 0 20px;
    }
    .progress-steps {
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .step {
      display: flex;
      flex-direction: column;
      align-items: center;
      flex: 1;
      position: relative;
    }
    .step-circle {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 1rem;
      transition: all 0.3s ease;
      position: relative;
      z-index: 2;
    }
    .step.completed .step-circle {
      background: var(--hq-success);
      color: white;
      box-shadow: 0 4px 12px rgba(34, 197, 94, 0.4);
    }
    .step.active .step-circle {
      background: linear-gradient(135deg, var(--hq-gold) 0%, var(--hq-gold-dark) 100%);
      color: var(--hq-navy);
      box-shadow: var(--shadow-glow);
      animation: pulse 2s infinite;
    }
    .step.pending .step-circle {
      background: rgba(255, 255, 255, 0.2);
      color: rgba(255, 255, 255, 0.5);
      border: 2px dashed rgba(255, 255, 255, 0.3);
    }
    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }
    .step-label {
      margin-top: 10px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: rgba(255, 255, 255, 0.7);
    }
    .step.active .step-label { color: var(--hq-gold); }
    .step.completed .step-label { color: var(--hq-success); }
    .step-line {
      flex: 1;
      height: 3px;
      background: rgba(255, 255, 255, 0.2);
      margin: 0 -10px;
      margin-bottom: 30px;
      position: relative;
    }
    .step-line.completed { background: var(--hq-success); }
    .step-line.active { 
      background: linear-gradient(90deg, var(--hq-success) 0%, var(--hq-gold) 100%); 
    }

    /* Main Content */
    .main-content {
      max-width: 700px;
      margin: 30px auto;
      padding: 0 20px 40px;
      position: relative;
      z-index: 1;
    }

    /* Payment Card */
    .payment-card {
      background: var(--hq-white);
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-lg);
      overflow: hidden;
    }

    .card-header {
      background: linear-gradient(135deg, var(--hq-navy) 0%, var(--hq-navy-light) 100%);
      padding: 24px;
      text-align: center;
      color: white;
    }
    .card-header h2 {
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 8px;
    }
    .card-header p {
      font-size: 0.9rem;
      opacity: 0.85;
    }
    .amount-display {
      margin-top: 16px;
      font-size: 2.5rem;
      font-weight: 800;
      color: var(--hq-gold);
      text-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }

    .card-body {
      padding: 30px;
    }

    /* Bank Details Section */
    .bank-details {
      background: linear-gradient(135deg, #fefce8 0%, #fef9c3 100%);
      border: 2px solid var(--hq-gold);
      border-radius: var(--radius-lg);
      padding: 24px;
      margin-bottom: 24px;
      text-align: center;
    }
    .bank-label {
      font-size: 0.75rem;
      font-weight: 600;
      color: var(--hq-text-muted);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 8px;
    }
    .bank-name {
      font-size: 1.25rem;
      font-weight: 700;
      color: var(--hq-navy);
      margin-bottom: 12px;
    }
    .account-number {
      font-size: 2rem;
      font-weight: 800;
      color: var(--hq-navy);
      font-family: 'Courier New', monospace;
      letter-spacing: 3px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
    }
    .copy-btn {
      background: var(--hq-navy);
      color: var(--hq-gold);
      border: none;
      width: 44px;
      height: 44px;
      border-radius: var(--radius-md);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
      transition: all 0.3s ease;
    }
    .copy-btn:hover {
      transform: scale(1.1);
      box-shadow: var(--shadow-md);
    }
    .copy-btn.copied {
      background: var(--hq-success);
      color: white;
    }
    .account-name {
      margin-top: 12px;
      font-size: 0.9rem;
      color: var(--hq-text-muted);
    }

    /* Timer */
    .timer-section {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      padding: 16px;
      background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
      border-radius: var(--radius-md);
      margin-bottom: 24px;
    }
    .timer-icon {
      font-size: 1.5rem;
      color: var(--hq-danger);
      animation: tick 1s infinite;
    }
    @keyframes tick {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.5; }
    }
    .timer-text {
      font-weight: 600;
      color: var(--hq-danger);
    }
    .timer-value {
      font-size: 1.5rem;
      font-weight: 800;
      color: var(--hq-danger);
      font-family: monospace;
    }

    /* Reference Box */
    .reference-box {
      background: var(--hq-bg);
      border: 1px solid var(--hq-border);
      border-radius: var(--radius-md);
      padding: 16px;
      margin-bottom: 24px;
      text-align: center;
    }
    .reference-box .label {
      font-size: 0.75rem;
      color: var(--hq-text-muted);
      text-transform: uppercase;
      margin-bottom: 4px;
    }
    .reference-box .value {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--hq-navy);
      font-family: monospace;
    }

    /* Form Section */
    .form-section {
      background: var(--hq-bg);
      border-radius: var(--radius-lg);
      padding: 24px;
      margin-bottom: 24px;
    }
    .form-title {
      font-size: 1rem;
      font-weight: 700;
      color: var(--hq-navy);
      margin-bottom: 4px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .form-title i { color: var(--hq-gold); }
    .form-subtitle {
      font-size: 0.85rem;
      color: var(--hq-text-muted);
      margin-bottom: 20px;
    }
    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }
    .form-group {
      display: flex;
      flex-direction: column;
    }
    .form-group.full-width { grid-column: 1 / -1; }
    .form-group label {
      font-size: 0.8rem;
      font-weight: 600;
      color: var(--hq-text);
      margin-bottom: 6px;
    }
    .form-group input {
      padding: 14px 16px;
      border: 2px solid var(--hq-border);
      border-radius: var(--radius-md);
      font-size: 1rem;
      transition: all 0.3s ease;
      background: white;
    }
    .form-group input:focus {
      outline: none;
      border-color: var(--hq-gold);
      box-shadow: 0 0 0 4px rgba(255, 214, 0, 0.15);
    }
    .form-group input::placeholder {
      color: #94a3b8;
    }

    /* Submit Button */
    .submit-btn {
      width: 100%;
      padding: 18px;
      background: linear-gradient(135deg, var(--hq-gold) 0%, var(--hq-gold-dark) 100%);
      color: var(--hq-navy);
      border: none;
      border-radius: var(--radius-lg);
      font-size: 1.1rem;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      transition: all 0.3s ease;
      box-shadow: var(--shadow-md);
    }
    .submit-btn:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-glow);
    }
    .submit-btn:disabled {
      opacity: 0.7;
      cursor: not-allowed;
      transform: none;
    }
    .submit-btn i { font-size: 1.3rem; }

    /* Loading Spinner */
    .spinner {
      width: 24px;
      height: 24px;
      border: 3px solid rgba(11, 26, 44, 0.2);
      border-top-color: var(--hq-navy);
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    /* Status Message */
    .status-message {
      padding: 16px;
      border-radius: var(--radius-md);
      margin-top: 16px;
      display: none;
    }
    .status-message.success {
      background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
      color: #166534;
      display: block;
    }
    .status-message.waiting {
      background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
      color: #92400e;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    /* Recorded Info */
    .recorded-info {
      background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
      border: 2px solid #86efac;
      border-radius: var(--radius-lg);
      padding: 20px;
      margin-top: 20px;
      display: none;
    }
    .recorded-info h4 {
      color: #166534;
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .recorded-info h4 i { color: var(--hq-success); }
    .recorded-row {
      display: flex;
      justify-content: space-between;
      padding: 8px 0;
      border-bottom: 1px dashed #86efac;
    }
    .recorded-row:last-child { border-bottom: none; }
    .recorded-label { color: #64748b; font-size: 0.9rem; }
    .recorded-value { font-weight: 600; color: #166534; }

    /* Error state */
    .error-state {
      text-align: center;
      padding: 60px 20px;
    }
    .error-state i {
      font-size: 4rem;
      color: var(--hq-warning);
      margin-bottom: 20px;
    }
    .error-state h2 {
      color: var(--hq-navy);
      margin-bottom: 12px;
    }
    .error-state p {
      color: var(--hq-text-muted);
      margin-bottom: 24px;
    }
    .back-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 14px 28px;
      background: linear-gradient(135deg, var(--hq-navy) 0%, var(--hq-navy-light) 100%);
      color: var(--hq-gold);
      border-radius: var(--radius-md);
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
    }
    .back-btn:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }

    /* Footer */
    .footer {
      text-align: center;
      padding: 20px;
      color: rgba(255, 255, 255, 0.6);
      font-size: 0.85rem;
    }

    /* Responsive */
    @media (max-width: 600px) {
      .header-badge { display: none; }
      .account-number { font-size: 1.4rem; letter-spacing: 1px; }
      .amount-display { font-size: 2rem; }
      .form-grid { grid-template-columns: 1fr; }
      .step-label { font-size: 0.65rem; }
      .step-circle { width: 40px; height: 40px; font-size: 0.9rem; }
    }
  </style>
</head>
<body>
  <div class="bg-pattern"></div>

  <header class="header">
    <div class="header-inner">
      <img src="<?= htmlspecialchars($logoUrl) ?>" alt="HQ Logo">
      <div class="header-text">
        <h1>HIGH Q SOLID ACADEMY</h1>
        <p>Secure Payment Portal</p>
      </div>
      <div class="header-badge">
        <i class='bx bx-lock-alt'></i>
        256-bit Encrypted
      </div>
    </div>
  </header>

  <!-- Progress Steps -->
  <div class="progress-container">
    <div class="progress-steps">
      <div class="step completed">
        <div class="step-circle"><i class='bx bx-check'></i></div>
        <div class="step-label">Program</div>
      </div>
      <div class="step-line completed"></div>
      <div class="step completed">
        <div class="step-circle"><i class='bx bx-check'></i></div>
        <div class="step-label">Details</div>
      </div>
      <div class="step-line active"></div>
      <div class="step active">
        <div class="step-circle">3</div>
        <div class="step-label">Payment</div>
      </div>
    </div>
  </div>

  <main class="main-content">
    <?php if (!$payment): ?>
      <div class="payment-card">
        <div class="error-state">
          <i class='bx bx-error-circle'></i>
          <h2>Payment Not Found</h2>
          <p>We couldn't find your payment reference. If you just registered, please try again or contact support.</p>
          <a href="register-new.php" class="back-btn">
            <i class='bx bx-arrow-back'></i>
            Back to Registration
          </a>
        </div>
      </div>
    <?php else: ?>
      <div class="payment-card">
        <div class="card-header">
          <h2>Complete Your Payment</h2>
          <p>Transfer the exact amount to the account below</p>
          <div class="amount-display">₦<?= number_format($payment['amount'], 2) ?></div>
        </div>

        <div class="card-body">
          <!-- Bank Details -->
          <div class="bank-details">
            <div class="bank-label">Transfer to</div>
            <div class="bank-name"><?= htmlspecialchars($bankName) ?></div>
            <div class="account-number">
              <span id="accountNum"><?= htmlspecialchars($bankAccount) ?></span>
              <button class="copy-btn" id="copyBtn" title="Copy account number">
                <i class='bx bx-copy'></i>
              </button>
            </div>
            <div class="account-name"><?= htmlspecialchars($bankAccountName) ?></div>
          </div>

          <!-- Timer -->
          <div class="timer-section">
            <i class='bx bx-time-five timer-icon'></i>
            <span class="timer-text">Payment window expires in</span>
            <span class="timer-value" id="timerDisplay">30:00</span>
          </div>

          <!-- Reference -->
          <div class="reference-box">
            <div class="label">Your Payment Reference</div>
            <div class="value"><?= htmlspecialchars($payment['reference']) ?></div>
          </div>

          <!-- Payer Details Form -->
          <div class="form-section" id="payerFormWrap">
            <div class="form-title">
              <i class='bx bx-info-circle'></i>
              Payment Verification Details
            </div>
            <div class="form-subtitle">
              After making the transfer, provide your bank details below for verification.
            </div>
            <form id="payerForm">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="payment_id" value="<?= intval($payment['id'] ?? 0) ?>">
              <div class="form-grid">
                <div class="form-group full-width">
                  <label for="payer_name">Name on Your Bank Account</label>
                  <input type="text" id="payer_name" name="payer_name" required placeholder="e.g., John Doe">
                </div>
                <div class="form-group">
                  <label for="payer_number">Your Account Number</label>
                  <input type="text" id="payer_number" name="payer_number" required placeholder="e.g., 0012345678">
                </div>
                <div class="form-group">
                  <label for="payer_bank">Your Bank Name</label>
                  <input type="text" id="payer_bank" name="payer_bank" required placeholder="e.g., Zenith Bank">
                </div>
              </div>
            </form>
          </div>

          <!-- Submit Button -->
          <button type="button" class="submit-btn" id="submitBtn">
            <i class='bx bx-check-circle'></i>
            I've Sent the Money
          </button>

          <!-- Status Messages -->
          <div class="status-message waiting" id="waitingStatus">
            <div class="spinner"></div>
            <span>Verifying your payment... Please wait.</span>
          </div>

          <!-- Recorded Info -->
          <div class="recorded-info" id="recordedInfo">
            <h4><i class='bx bx-check-circle'></i> Transfer Details Recorded</h4>
            <div class="recorded-row">
              <span class="recorded-label">Account Name</span>
              <span class="recorded-value" id="recName">-</span>
            </div>
            <div class="recorded-row">
              <span class="recorded-label">Account Number</span>
              <span class="recorded-value" id="recNumber">-</span>
            </div>
            <div class="recorded-row">
              <span class="recorded-label">Bank</span>
              <span class="recorded-value" id="recBank">-</span>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </main>

  <footer class="footer">
    &copy; <?= date('Y') ?> HIGH Q SOLID ACADEMY. All rights reserved.
  </footer>

  <script>
    window.HQ_APP_BASE = <?= json_encode(rtrim($HQ_EFFECTIVE_BASE, '/')) ?>;
    if (!window.HQ_BASE) window.HQ_BASE = window.HQ_APP_BASE;

    <?php if ($payment): ?>
    (function(){
      const ref = <?= json_encode($payment['reference'] ?? '') ?>;
      const paymentId = <?= intval($payment['id'] ?? 0) ?>;
      const accountNum = document.getElementById('accountNum');
      const copyBtn = document.getElementById('copyBtn');
      const timerDisplay = document.getElementById('timerDisplay');
      const form = document.getElementById('payerForm');
      const submitBtn = document.getElementById('submitBtn');
      const waitingStatus = document.getElementById('waitingStatus');
      const recordedInfo = document.getElementById('recordedInfo');
      const payerFormWrap = document.getElementById('payerFormWrap');

      // 30-minute timer
      const timerKey = 'hq_timer_' + ref;
      let startTime = localStorage.getItem(timerKey);
      if (!startTime) {
        startTime = Math.floor(Date.now() / 1000);
        localStorage.setItem(timerKey, startTime);
      } else {
        startTime = parseInt(startTime, 10);
      }
      const duration = 30 * 60; // 30 minutes

      function updateTimer() {
        const now = Math.floor(Date.now() / 1000);
        const elapsed = now - startTime;
        const remaining = Math.max(0, duration - elapsed);
        const mins = Math.floor(remaining / 60);
        const secs = remaining % 60;
        timerDisplay.textContent = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        
        if (remaining <= 0) {
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<i class="bx bx-x-circle"></i> Payment Window Expired';
        }
      }
      updateTimer();
      setInterval(updateTimer, 1000);

      // Copy to clipboard
      copyBtn.addEventListener('click', function() {
        const text = accountNum.textContent;
        navigator.clipboard.writeText(text).then(() => {
          copyBtn.classList.add('copied');
          copyBtn.innerHTML = '<i class="bx bx-check"></i>';
          Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Account number copied!',
            showConfirmButton: false,
            timer: 2000,
            background: '#ffd600',
            color: '#0b1a2c'
          });
          setTimeout(() => {
            copyBtn.classList.remove('copied');
            copyBtn.innerHTML = '<i class="bx bx-copy"></i>';
          }, 2000);
        });
      });

      // Form submission
      submitBtn.addEventListener('click', function() {
        if (!form.checkValidity()) {
          form.reportValidity();
          return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<div class="spinner"></div> Recording...';

        const fd = new FormData(form);
        const apiBase = (window.HQ_APP_BASE || '').replace(/\/$/, '');

        fetch(apiBase + '/api/mark_sent.php', {
          method: 'POST',
          body: fd,
          credentials: 'same-origin',
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
          if (data.status === 'ok') {
            // Show success state
            payerFormWrap.style.display = 'none';
            recordedInfo.style.display = 'block';
            document.getElementById('recName').textContent = data.payment?.payer_name || fd.get('payer_name');
            document.getElementById('recNumber').textContent = data.payment?.payer_number || fd.get('payer_number');
            document.getElementById('recBank').textContent = data.payment?.payer_bank || fd.get('payer_bank');
            
            submitBtn.innerHTML = '<i class="bx bx-check-circle"></i> Recorded - Awaiting Verification';
            waitingStatus.style.display = 'flex';
            
            // Clear timer storage
            localStorage.removeItem(timerKey);
            
            // Start polling immediately
            checkStatus();
          } else {
            throw new Error(data.message || 'Failed to record transfer');
          }
        })
        .catch(err => {
          Swal.fire('Error', err.message, 'error');
          submitBtn.disabled = false;
          submitBtn.innerHTML = '<i class="bx bx-check-circle"></i> I\'ve Sent the Money';
        });
      });

      // Poll for payment confirmation
      function checkStatus() {
        const apiBase = (window.HQ_APP_BASE || '').replace(/\/$/, '');
        fetch(apiBase + '/api/payment_status.php?ref=' + encodeURIComponent(ref) + '&t=' + Date.now())
          .then(r => r.json())
          .then(data => {
            if (data.status === 'ok' && data.payment) {
              const st = data.payment.status || '';
              if (st === 'confirmed' || st === 'paid') {
                Swal.fire({
                  icon: 'success',
                  title: 'Payment Confirmed!',
                  html: 'Your payment has been verified.<br>Redirecting to your receipt...',
                  showConfirmButton: false,
                  timer: 3000,
                  background: '#ffd600',
                  color: '#0b1a2c'
                }).then(() => {
                  window.location = apiBase + '/receipt.php?ref=' + encodeURIComponent(ref);
                });
                return;
              }
            }
            // Continue polling
            setTimeout(checkStatus, 5000);
          })
          .catch(() => {
            setTimeout(checkStatus, 5000);
          });
      }

      // Initial status check
      setTimeout(checkStatus, 3000);

      // Activate payment on page load
      (function activatePayment() {
        const token = form.querySelector('input[name="_csrf"]')?.value || '';
        const fd = new FormData();
        fd.append('ref', ref);
        fd.append('payment_id', paymentId);
        fd.append('_csrf', token);
        const apiBase = (window.HQ_APP_BASE || '').replace(/\/$/, '');
        fetch(apiBase + '/api/activate_payment.php', {
          method: 'POST',
          body: fd,
          credentials: 'same-origin',
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).catch(() => {});
      })();
    })();
    <?php endif; ?>
  </script>
</body>
</html>
