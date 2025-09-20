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
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Register & Pay</title></head>
<body>
<?php if (!empty($errors)): ?>
	<?php foreach($errors as $e): ?>
		<div style="color:red"><?php echo htmlspecialchars($e) ?></div>
	<?php endforeach; ?>
<?php endif; ?>
<?php if ($success): ?>
	<div style="color:green"><?php echo htmlspecialchars($success) ?></div>
<?php endif; ?>
<form method="post">
	<input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf) ?>">
	<label>Name</label><input name="name" required>
	<label>Email</label><input name="email" type="email" required>
	<label>Password</label><input name="password" type="password" required>
	<label>Amount</label><input name="amount" type="number" step="0.01" value="1000" required>
	<label>Method</label>
	<select name="method"><option value="bank">Bank Transfer</option><option value="paystack">Card (Paystack)</option></select>
	<button type="submit">Register & Pay</button>
	<p>Already registered? <a href="login.php">Login</a></p>
</form>
</body>
</html>

