<?php
// public/register.php
// Use public-side config/includes (avoid pulling admin internals)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/functions.php';
$cfg = require __DIR__ . '/../config/payments.php';

$errors = [];
$success = '';

function generatePaymentReference($prefix='PAY') {
	return $prefix . '-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)),0,6);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$token = $_POST['_csrf_token'] ?? '';
	if (!verifyToken('signup_form', $token)) { $errors[] = 'Invalid CSRF token.'; }

	$name = trim($_POST['name'] ?? '');
	$email = trim($_POST['email'] ?? '');
	$password = $_POST['password'] ?? '';
	$amount = floatval($_POST['amount'] ?? 0);
	$method = $_POST['method'] ?? 'bank'; // 'bank' or 'paystack'

	if (!$name || !$email || !$password) { $errors[] = 'Name, email and password are required.'; }
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Invalid email.'; }

	if (empty($errors)) {
		// create pending user and payment record (transaction)
		try {
			$pdo->beginTransaction();

			$hashed = password_hash($password, PASSWORD_DEFAULT);
			// check email exists
			$s = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
			$s->execute([$email]);
			if ($s->fetch()) { throw new Exception('Email already exists'); }

			$role_id = null; $is_active = 0;
			$ins = $pdo->prepare('INSERT INTO users (role_id,name,phone,email,password,avatar,is_active,created_at) VALUES (?, ?, ?, ?, ?, NULL, ?, NOW())');
			$ins->execute([$role_id, $name, $_POST['phone'] ?? null, $email, $hashed, $is_active]);
			$userId = $pdo->lastInsertId();

			$reference = generatePaymentReference('REG');
			$stmt = $pdo->prepare('INSERT INTO payments (student_id, amount, payment_method, reference, status, gateway, created_at) VALUES (?, ?, ?, ?, "pending", ?, NOW())');
			$gateway = $method === 'paystack' ? 'paystack' : 'bank_transfer';
			$stmt->execute([$userId, $amount, $method, $reference, $gateway]);
			$paymentId = $pdo->lastInsertId();

			$pdo->commit();
		} catch (Exception $e) {
			$pdo->rollBack();
			$errors[] = 'Failed to register: ' . $e->getMessage();
		}

		if (empty($errors)) {
			if ($method === 'paystack') {
				try {
					require_once __DIR__ . '/../vendor/autoload.php';
					$cfgGlobal = require __DIR__ . '/../config/payments.php';
					$paymentsHelper = new \Src\Helpers\Payments($cfgGlobal);
					$init = $paymentsHelper->initializePaystack([
						'email' => $email,
						'amount' => intval($amount * 100),
						'reference' => $reference,
						'callback_url' => (getenv('APP_URL') ?: '') . '/public/payments_callback.php'
					]);
					if (!empty($init['status']) && $init['status'] && !empty($init['data']['authorization_url'])) {
						header('Location: ' . $init['data']['authorization_url']); exit;
					}
					$errors[] = 'Failed to initialize payment gateway. Please try bank transfer.';
				} catch (Exception $e) {
					$errors[] = 'Payment gateway error: ' . $e->getMessage();
				}
			}

			// bank transfer fallback: show instructions and reference
			if ($method === 'bank' || !empty($errors)) {
				$bank = $cfg['bank'];
				// display instructions (render minimal HTML below)
				$success = "Please transfer " . number_format($amount,2) . " to account {$bank['account_number']} ({$bank['bank_name']}). Use reference: $reference. After payment, upload your receipt on your profile or contact support.";
			}
		}
	}
}

// Render a simple form when GET or on errors
$csrf = generateToken('signup_form');
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<!-- Hero (reuse about-hero styling used across the site) -->
<section class="about-hero">
	<div class="about-hero-overlay"></div>
	<div class="container about-hero-inner">
		<h1>Register with HIGH Q Academy</h1>
		<p class="lead">Start your journey towards academic excellence. Register for our programs and join thousands of successful students.</p>
	</div>
</section>

<section class="programs-content">
	<div class="container">
		<?php if (!empty($errors)): ?>
			<div class="admin-notice" style="background:#fff7e6;border-left:4px solid var(--hq-yellow);padding:12px;margin-bottom:12px;color:#b33;">
				<?php foreach($errors as $e): ?>
					<div><?php echo htmlspecialchars($e) ?></div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ($success): ?>
			<div class="admin-notice" style="background:#e6fff0;border-left:4px solid #3cb371;padding:12px;margin-bottom:12px;color:#094;">
				<?php echo htmlspecialchars($success) ?>
			</div>
		<?php endif; ?>

			<div class="register-layout">
				<div class="register-main">
					<div class="card">
						<form method="post">
							<input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf) ?>">
							<div class="form-row">
								<input name="name" required placeholder="Full name">
							</div>
							<div class="form-row">
								<input name="phone" placeholder="Phone (optional)">
							</div>
							<div class="form-row">
								<input name="email" type="email" required placeholder="Email address">
							</div>
							<div class="form-row">
								<input name="password" type="password" required placeholder="Password">
							</div>
							<div class="form-row form-inline">
								<input name="amount" type="number" step="0.01" value="1000" required>
								<select name="method">
									<option value="bank">Bank Transfer</option>
									<option value="paystack">Card (Paystack)</option>
								</select>
								<button type="submit" class="btn-primary">Register & Pay</button>
							</div>
							<p class="muted" style="margin-top:10px">Already registered? <a href="login.php">Login</a></p>
						</form>
					</div>
				</div>

					<?php
						// load site settings (structured table) for contact and bank details
						$siteSettings = [];
						try {
							$s = $pdo->query("SELECT * FROM site_settings ORDER BY id ASC LIMIT 1");
							$siteSettings = $s->fetch(PDO::FETCH_ASSOC) ?: [];
						} catch (Throwable $e) { $siteSettings = []; }
					?>

					<aside class="register-sidebar">
					<div class="sidebar-card admission-box">
						<h4>Admission Requirements</h4>
						<ul>
							<li>Completed O'Level certificate (for JAMB/Post-UTME)</li>
							<li>Valid identification document</li>
							<li>Passport photograph (2 copies)</li>
							<li>Registration fee payment</li>
							<li>Commitment to academic excellence</li>
						</ul>
					</div>

							<div class="sidebar-card payment-box">
						<h4>Payment Options</h4>
						<div class="payment-method">
							<strong>Bank Transfer</strong>
									<p>Account Name: <?= htmlspecialchars($siteSettings['bank_account_name'] ?? 'High Q Solid Academy Limited') ?><br>
									Bank: <?= htmlspecialchars($siteSettings['bank_name'] ?? '[Bank Name]') ?><br>
									Account Number: <?= htmlspecialchars($siteSettings['bank_account_number'] ?? '[Account Number]') ?></p>
						</div>
						<div class="payment-method">
							<strong>Cash Payment</strong>
							<p>Visit our office locations<br>8 Pineapple Avenue, Aiyetoro, Maya<br>Shop 18, World Star Complex, Aiyetoro</p>
						</div>
						<div class="payment-method">
							<strong>Online Payment</strong>
							<p>Secure online payment portal. Credit/Debit card accepted.</p>
						</div>
					</div>

							<div class="sidebar-card help-box">
						<h4>Need Help?</h4>
								<p><strong>Call Us</strong><br><?= htmlspecialchars($siteSettings['contact_phone'] ?? '0807 208 8794') ?></p>
								<p><strong>Email Us</strong><br><?= htmlspecialchars($siteSettings['contact_email'] ?? 'info@hqacademy.com') ?></p>
								<p><strong>Visit Us</strong><br><?= nl2br(htmlspecialchars($siteSettings['contact_address'] ?? "8 Pineapple Avenue, Aiyetoro\nMaya, Ikorodu")) ?></p>
					</div>

					<div class="sidebar-card why-box">
						<h4>Why Choose Us?</h4>
						<div class="why-stats">
							<div class="stat"><strong>292</strong><span>Highest JAMB Score 2024</span></div>
							<div class="stat"><strong>1000+</strong><span>Students Trained</span></div>
							<div class="stat"><strong>99%</strong><span>Success Rate</span></div>
						</div>
					</div>
				</aside>
			</div>
	</div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

