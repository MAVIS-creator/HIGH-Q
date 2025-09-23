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
		<p class="lead">Start your journey towards academic excellence. Create an account and secure your spot in our programs.</p>
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

		<div class="register-grid" style="max-width:720px;margin:0 auto;padding:24px;background:#fff;border-radius:10px;box-shadow:0 10px 30px rgba(11,37,64,0.06);">
			<form method="post">
				<input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf) ?>">
				<div style="display:flex;gap:12px;margin-bottom:12px;">
					<input name="name" required placeholder="Full name" style="flex:1;padding:10px;border:1px solid #ddd;border-radius:6px;">
					<input name="phone" placeholder="Phone (optional)" style="width:180px;padding:10px;border:1px solid #ddd;border-radius:6px;">
				</div>
				<div style="display:flex;gap:12px;margin-bottom:12px;">
					<input name="email" type="email" required placeholder="Email address" style="flex:1;padding:10px;border:1px solid #ddd;border-radius:6px;">
					<input name="password" type="password" required placeholder="Password" style="width:220px;padding:10px;border:1px solid #ddd;border-radius:6px;">
				</div>
				<div style="display:flex;gap:12px;margin-bottom:12px;align-items:center;">
					<input name="amount" type="number" step="0.01" value="1000" required style="width:160px;padding:10px;border:1px solid #ddd;border-radius:6px;">
					<select name="method" style="padding:10px;border:1px solid #ddd;border-radius:6px;">
						<option value="bank">Bank Transfer</option>
						<option value="paystack">Card (Paystack)</option>
					</select>
					<button type="submit" class="btn-primary" style="margin-left:auto;">Register & Pay</button>
				</div>
				<p style="margin-top:8px">Already registered? <a href="login.php">Login</a></p>
			</form>
		</div>
	</div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

