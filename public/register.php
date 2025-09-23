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

	// Basic account fields
	$name = trim($_POST['name'] ?? '');
	$email = trim($_POST['email'] ?? '');
	$password = $_POST['password'] ?? '';
	$programs = $_POST['programs'] ?? []; // array of course_id

	// compute amount server-side from selected programs to prevent tampering
	$amount = 0.0;
	if (!empty($programs) && is_array($programs)) {
		$placeholders = implode(',', array_fill(0, count($programs), '?'));
		$stmt = $pdo->prepare("SELECT id,price FROM courses WHERE id IN ($placeholders)");
		foreach ($programs as $i => $pid) { $stmt->bindValue($i+1, $pid, PDO::PARAM_INT); }
		$stmt->execute();
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		foreach ($rows as $r) { $amount += floatval($r['price']); }
	}
	$amount = round($amount, 2);
	$method = $_POST['method'] ?? 'bank'; // 'bank' or 'paystack'

	// Registration form fields
	$first_name = trim($_POST['first_name'] ?? '');
	$last_name = trim($_POST['last_name'] ?? '');
	// derive display name if simple name field not provided
	if (empty($name)) { $name = trim($first_name . ' ' . $last_name); }
	$date_of_birth = trim($_POST['date_of_birth'] ?? '') ?: null;
	$home_address = trim($_POST['home_address'] ?? '') ?: null;
	$previous_education = trim($_POST['previous_education'] ?? '') ?: null;
	$academic_goals = trim($_POST['academic_goals'] ?? '') ?: null;
	$emergency_name = trim($_POST['emergency_name'] ?? '') ?: null;
	$emergency_phone = trim($_POST['emergency_phone'] ?? '') ?: null;
	$emergency_relationship = trim($_POST['emergency_relationship'] ?? '') ?: null;
	// $programs already read above
	$agreed_terms = isset($_POST['agreed_terms']) ? 1 : 0;

	if (!$name || !$email || !$password) { $errors[] = 'Name, email and password are required.'; }
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Invalid email.'; }

	if (empty($errors)) {
		// create pending user, registration and payment record
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

			// insert registration
			$reg = $pdo->prepare('INSERT INTO student_registrations (user_id, first_name, last_name, date_of_birth, home_address, previous_education, academic_goals, emergency_contact_name, emergency_contact_phone, emergency_relationship, agreed_terms, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
			$reg->execute([
				$userId,
				$first_name ?: $name,
				$last_name,
				$date_of_birth,
				$home_address,
				$previous_education,
				$academic_goals,
				$emergency_name,
				$emergency_phone,
				$emergency_relationship,
				$agreed_terms ? '1' : '0',
				'pending'
			]);
			$registrationId = $pdo->lastInsertId();

			// associate selected programs
			if (!empty($programs) && is_array($programs)) {
				$sp = $pdo->prepare('INSERT INTO student_programs (registration_id, course_id) VALUES (?, ?)');
				foreach ($programs as $cid) {
					$sp->execute([$registrationId, (int)$cid]);
				}
			}

			$reference = generatePaymentReference('REG');
			$stmt = $pdo->prepare('INSERT INTO payments (student_id, amount, payment_method, reference, status, created_at) VALUES (?, ?, ?, ?, "pending", NOW())');
			$gateway = $method === 'paystack' ? 'paystack' : 'bank_transfer';
			$stmt->execute([$userId, $amount, $method, $reference]);
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
				$success = "Please transfer " . number_format($amount,2) . " to account {$bank['account_number']} ({$bank['bank_name']}). Use reference: $reference. After payment, click the \"I have sent the money\" button and provide your transfer details.";
				// Keep payment id & reference in session for follow-up (short-lived)
				$_SESSION['last_payment_id'] = $paymentId;
				$_SESSION['last_payment_reference'] = $reference;
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
										<?php
										// load available programs from courses table
										try {
												$courses = $pdo->query("SELECT id,title,price,duration FROM courses WHERE is_active=1 ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
										} catch (Throwable $e) { $courses = []; }
										?>

										<div class="container register-layout">
										<main class="register-main">
											<div class="card">
												<h3>Student Registration Form</h3>
												<p class="card-desc">Fill out this form to begin your registration process. Our team will contact you within 24 hours to complete your enrollment.</p>
												<?php if (!empty($errors)): ?>
													<div class="admin-notice" style="background:#fff7e6;border-left:4px solid var(--hq-yellow);padding:12px;margin-bottom:12px;color:#b33;">
														<?php foreach($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
													</div>
												<?php endif; ?>
												<?php if ($success): ?>
													<div class="admin-notice" style="background:#e6fff0;border-left:4px solid #3cb371;padding:12px;margin-bottom:12px;color:#094;">
														<?= htmlspecialchars($success) ?>
													</div>
												<?php endif; ?>

												<form method="post">
													<input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf) ?>">
													<h4 class="section-title"><i class="bx bxs-user"></i> Personal Information</h4>
													<div class="section-body">
																									<div class="form-row"><label>First Name *</label><input name="first_name" placeholder="Enter your first name" required value="<?= htmlspecialchars($first_name ?? '') ?>"></div>
																									<div class="form-row"><label>Last Name *</label><input name="last_name" placeholder="Enter your last name" required value="<?= htmlspecialchars($last_name ?? '') ?>"></div>
																									<div class="form-row"><label>Email Address *</label><input name="email" type="email" placeholder="your.email@example.com" required value="<?= htmlspecialchars($email ?? '') ?>"></div>
																									<div class="form-row"><label>Phone Number</label><input name="phone" placeholder="+234 XXX XXX XXXX" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"></div>
																									<div class="form-row"><label>Date of Birth</label><input name="date_of_birth" type="date" placeholder="dd/mm/yyyy" value="<?= htmlspecialchars($date_of_birth ?? '') ?>"></div>
																									<div class="form-row"><label>Home Address</label><textarea name="home_address" placeholder="Enter your complete home address"><?= htmlspecialchars($home_address ?? '') ?></textarea></div>

													<h4 class="section-title"><i class="bx bx-collection"></i> Program Selection</h4>
													<div class="programs-grid">
														<?php if (empty($courses)): ?><p>No programs available currently.</p><?php endif; ?>
														<?php foreach ($courses as $c): ?>
																<label style="display:block;padding:10px;border:1px solid #eee;border-radius:6px;margin-bottom:8px;">
																	<input type="checkbox" name="programs[]" value="<?= $c['id'] ?>"> <?= htmlspecialchars($c['title']) ?> <small style="color:#666">(₦<?= number_format($c['price'],2) ?>)</small>
																	<div style="font-size:12px;color:#777;"><?= htmlspecialchars($c['duration'] ?? '') ?></div>
																</label>
															<?php endforeach; ?>
													</div>

													<div class="form-row"><label>Previous Education</label><textarea name="previous_education" placeholder="Tell us about your educational background (schools attended, certificates obtained, etc.)"><?= htmlspecialchars($previous_education ?? '') ?></textarea></div>
													<div class="form-row"><label>Academic Goals</label><textarea name="academic_goals" placeholder="What are your academic and career aspirations? How can we help you achieve them?"><?= htmlspecialchars($academic_goals ?? '') ?></textarea></div>

													<h4 class="section-title"><i class="bx bxs-phone"></i> Emergency Contact</h4>
													<div class="section-body">
													<div class="form-row"><label>Parent/Guardian Name</label><input name="emergency_name" placeholder="Full name of parent/guardian" value="<?= htmlspecialchars($emergency_name ?? '') ?>"></div>
													<div class="form-row"><label>Parent/Guardian Phone</label><input name="emergency_phone" placeholder="+234 XXX XXX XXXX" value="<?= htmlspecialchars($emergency_phone ?? '') ?>"></div>
													<div class="form-row"><label>Relationship</label><input name="emergency_relationship" placeholder="Relationship to student" value="<?= htmlspecialchars($emergency_relationship ?? '') ?>"></div>
													</div>

													<div class="form-row"><label><input type="checkbox" name="agreed_terms" <?= !empty($agreed_terms) ? 'checked' : '' ?>> I agree to the terms and conditions</label></div>
													<div style="margin-top:12px;"><button class="btn-primary" type="submit">Submit Registration</button></div>
												</form>
											</div>
										</main>

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
							<div class="stat">
								<div class="icon"><img src="assets/images/icons/trophy.svg" alt="trophy"></div>
								<div class="stat-body"><strong>292</strong><span>Highest JAMB Score 2024</span></div>
							</div>
							<div class="stat">
								<div class="icon"><img src="assets/images/icons/teacher.svg" alt="students"></div>
								<div class="stat-body"><strong>1000+</strong><span>Students Trained</span></div>
							</div>
							<div class="stat">
								<div class="icon"><img src="assets/images/icons/results.svg" alt="results"></div>
								<div class="stat-body"><strong>99%</strong><span>Success Rate</span></div>
							</div>
						</div>
					</div>
				</aside>
			</div>
	</div>
</section>

<!-- What Happens Next? -->
<section class="next-section">
	<div class="container">
		<div class="ceo-heading" style="text-align:center;">
			<h2>What Happens <span class="highlight">Next?</span></h2>
			<p style="color:var(--hq-gray); margin-top:8px;">After submitting your registration, here's what you can expect from us.</p>
		</div>

		<div class="achievements-grid">
			<div class="next-stat yellow">
				<div class="next-icon"><img src="assets/images/icons/payment.svg" alt="confirmation"></div>
				<strong>1. Confirmation</strong>
				<span>You'll receive an email confirmation within 1 hour and a call from our team within 24 hours.</span>
			</div>

			<div class="next-stat yellow">
				<div class="next-icon"><img src="assets/images/icons/book-open.svg" alt="assessment"></div>
				<strong>2. Assessment</strong>
				<span>We'll schedule a brief assessment to understand your current level and customize your learning path.</span>
			</div>

			<div class="next-stat red">
				<div class="next-icon"><img src="assets/images/icons/trophy.svg" alt="learning"></div>
				<strong>3. Start Learning</strong>
				<span>Begin your journey with our expert tutors and join the ranks of our successful students.</span>
			</div>
		</div>
	</div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
// If the user clicked "I have sent the money" on the bank instructions, handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_sent']) && !empty($_POST['payment_id'])) {
	$payId = (int)$_POST['payment_id'];
	$payer_name = trim($_POST['payer_name'] ?? '');
	$payer_number = trim($_POST['payer_number'] ?? '');
	$payer_bank = trim($_POST['payer_bank'] ?? '');
	// basic CSRF
	$token2 = $_POST['_csrf_token'] ?? '';
	if (!verifyToken('signup_form', $token2)) { /* ignore silently */ }
	else {
		$upd = $pdo->prepare('UPDATE payments SET payer_account_name = ?, payer_account_number = ?, payer_bank_name = ?, status = ?, updated_at = NOW() WHERE id = ?');
		$upd->execute([$payer_name, $payer_number, $payer_bank, 'sent', $payId]);
		// redirect to a waiting page where the user can poll or be informed
		header('Location: payments_wait.php?ref=' . urlencode($_SESSION['last_payment_reference'] ?? '')); exit;
	}
}
?>

