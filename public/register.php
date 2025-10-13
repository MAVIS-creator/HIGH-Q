<?php
// public/register.php
// Use public-side config/includes (avoid pulling admin internals)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/functions.php';
$cfg = require __DIR__ . '/../config/payments.php';

$errors = [];
$success = '';

// Fixed additional processing fees applied to any registration
$form_fee = 1000; // ₦1,000 form processing
$card_fee = 1500; // ₦1,500 card fee

// Load site settings to respect registration toggle (structured site_settings preferred)
$siteSettings = [];
try {
	$stmt = $pdo->query("SELECT * FROM site_settings ORDER BY id ASC LIMIT 1");
	$siteSettings = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
	try {
		$st = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
		$st->execute(['system_settings']);
		$val = $st->fetchColumn();
		$j = $val ? json_decode($val, true) : [];
		if (is_array($j)) $siteSettings = $j;
	} catch (Throwable $e2) { /* ignore */ }
}

// If registration is disabled, do not allow registrations
$registrationEnabled = true;
if (!empty($siteSettings)) {
	if (isset($siteSettings['registration'])) $registrationEnabled = (bool)$siteSettings['registration'];
	elseif (isset($siteSettings['security']['registration'])) $registrationEnabled = (bool)$siteSettings['security']['registration'];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$registrationEnabled) {
	$errors[] = 'Registrations are temporarily closed by the site administrator.';
}

function generatePaymentReference($prefix='PAY') {
	return $prefix . '-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)),0,6);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$token = $_POST['_csrf_token'] ?? '';
	if (!verifyToken('signup_form', $token)) { $errors[] = 'Invalid CSRF token.'; }

	// Registration inputs (no site account required here)
	$programs = $_POST['programs'] ?? []; // array of course_id

	// compute amount server-side from selected programs to prevent tampering
	$amount = 0.0;
	if (!empty($programs) && is_array($programs)) {
		$placeholders = implode(',', array_fill(0, count($programs), '?'));
		$stmt = $pdo->prepare("SELECT id,price FROM courses WHERE id IN ($placeholders)");
		foreach ($programs as $i => $pid) { $stmt->bindValue($i+1, $pid, PDO::PARAM_INT); }
		$stmt->execute();
				$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
				$selectedHasVaries = false;
				foreach ($rows as $r) {
					// if price is null or empty treat as 'Varies' and require admin verification before payment
					if (!isset($r['price']) || $r['price'] === null || $r['price'] === '') {
						$selectedHasVaries = true;
					}
					$amount += floatval($r['price']);
				}
	}
	$amount = round($amount, 2);

	// Add fixed form/card fees to the amount server-side if programs selected and payment is being created
	// Note: If verification before payment is required the registration will be pending and no payment created
	if (!empty($programs) && !$selectedHasVaries) {
		// Add form & card fees to the payable amount
		$amount += floatval($form_fee) + floatval($card_fee);
		$amount = round($amount,2);
	}

	// Server-side: re-check client-submitted total (if provided) to prevent tampering
	if (isset($_POST['client_total']) && $_POST['client_total'] !== '') {
		$posted_client_total = (float) str_replace(',', '', $_POST['client_total']);
		if (abs($posted_client_total - $amount) > 0.01) {
			$errors[] = 'Payment total does not match server calculation. Please refresh the page and try again.';
			error_log('Registration payment mismatch: posted=' . $posted_client_total . ' computed=' . $amount . ' programs=' . json_encode($programs));
		}
	}

	$method = $_POST['method'] ?? 'bank'; // 'bank' or 'paystack'

	// If any selected program has variable pricing, disable online methods server-side and force bank transfer
	$varies_notice = '';
	if (!empty($selectedHasVaries)) {
		$varies_notice = 'Note: One or more selected programs have variable pricing. Online payment methods are disabled for these selections; an administrator will contact you with the final amount.';
		// Force bank as the payment method to avoid online payment attempts
		$method = 'bank';
	}

	// Registration form fields
	$first_name = trim($_POST['first_name'] ?? '');
	$last_name = trim($_POST['last_name'] ?? '');
	$email_contact = trim($_POST['email_contact'] ?? '');
	$date_of_birth = trim($_POST['date_of_birth'] ?? '') ?: null;
	$home_address = trim($_POST['home_address'] ?? '') ?: null;
	$previous_education = trim($_POST['previous_education'] ?? '') ?: null;
	$academic_goals = trim($_POST['academic_goals'] ?? '') ?: null;
	$emergency_name = trim($_POST['emergency_name'] ?? '') ?: null;
	$emergency_phone = trim($_POST['emergency_phone'] ?? '') ?: null;
	$emergency_relationship = trim($_POST['emergency_relationship'] ?? '') ?: null;
	// $programs already read above
	$agreed_terms = isset($_POST['agreed_terms']) ? 1 : 0;

	// Terms must be accepted
	if (!$agreed_terms) { $errors[] = 'You must accept the terms and conditions to proceed.'; }

	// Validate contact email if provided
	if ($email_contact !== '' && !filter_var($email_contact, FILTER_VALIDATE_EMAIL)) {
		$errors[] = 'Provide a valid contact email address.';
	}

	if (empty($errors)) {
		// create registration record without creating a site user account
		try {
			$pdo->beginTransaction();

			$reg = $pdo->prepare('INSERT INTO student_registrations (user_id, first_name, last_name, email, date_of_birth, home_address, previous_education, academic_goals, emergency_contact_name, emergency_contact_phone, emergency_relationship, agreed_terms, status, created_at) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
			$reg->execute([
				$first_name ?: null,
				$last_name ?: null,
				$email_contact ?: null,
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

			// create a payment placeholder (student_id left NULL since no user)
			// Decide whether to auto-create payment or wait for admin verification
			$verifyBeforePayment = false;
			try {
				$st = $pdo->prepare("SELECT value FROM settings WHERE `key` = ? LIMIT 1");
				$st->execute(['system_settings']);
				$val = $st->fetchColumn();
				if ($val) {
					$j = json_decode($val, true);
					if (is_array($j) && isset($j['security']) && isset($j['security']['verify_registration_before_payment'])) {
						$verifyBeforePayment = (bool)$j['security']['verify_registration_before_payment'];
					}
				}
			} catch (Throwable $e) { /* ignore */ }
			// If any selected program is 'Varies' (no fixed price), require verification before payment
			if (!empty($selectedHasVaries)) $verifyBeforePayment = true;

			$reference = null; $paymentId = null;
			if (!$verifyBeforePayment) {
				$reference = generatePaymentReference('REG');
				$stmt = $pdo->prepare('INSERT INTO payments (student_id, amount, payment_method, reference, status, created_at) VALUES (NULL, ?, ?, ?, "pending", NOW())');
				$stmt->execute([$amount, $method, $reference]);
				$paymentId = $pdo->lastInsertId();
			}

			$pdo->commit();

			// Create an admin notification and send email to admins about new registration
			try {
				// Fetch admin email from site_settings (fallback to settings table)
				$adminEmail = null;
				$r = $pdo->query("SELECT contact_email FROM site_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
				if (!empty($r['contact_email'])) { $adminEmail = $r['contact_email']; }
				else {
					$s = $pdo->query("SELECT system_settings FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
					if (!empty($s['system_settings'])) {
						$json = json_decode($s['system_settings'], true);
						$adminEmail = $json['contact_email'] ?? $json['site']['contact_email'] ?? null;
					}
				}

				// Insert notification
				$insNotif = $pdo->prepare('INSERT INTO notifications (user_id, title, body, type, metadata, is_read, created_at) VALUES (NULL, ?, ?, ?, ?, 0, NOW())');
				$title = 'New student registration';
				$body = "$first_name $last_name registered for programs." . ($reference ? " Reference: $reference" : "");
				$meta = json_encode(['registration_id'=>$registrationId,'email'=>$email_contact,'programs'=>$programs], JSON_UNESCAPED_SLASHES);
				$insNotif->execute([$title, $body, 'registration', $meta]);

				// Send email if admin email exists and email notifications enabled
				if (!empty($adminEmail)) {
					$subject = 'New registration: ' . ($first_name . ' ' . $last_name);
					$html = "<p>A new student has registered.</p><p><strong>Name:</strong> " . htmlspecialchars($first_name . ' ' . $last_name) . "</p>";
					$html .= "<p><strong>Email:</strong> " . htmlspecialchars($email_contact ?: '') . "</p>";
					$html .= "<p><strong>Reference:</strong> " . htmlspecialchars($reference) . "</p>";
					// Use helper sendEmail (declared in public/config/functions.php)
					@sendEmail($adminEmail, $subject, $html);
				}
			} catch (Throwable $e) {
				// don't block user on notification/email errors
				error_log('Registration notification error: ' . $e->getMessage());
			}

			// bank transfer: redirect to dedicated waiting page only if a payment reference was created.
			// If verify-before-payment is enabled, no payment/reference was created and we should show an awaiting-verification message.
			if ($method === 'bank') {
				if ($reference) {
					$_SESSION['last_payment_id'] = $paymentId;
					$_SESSION['last_payment_reference'] = $reference;
					header('Location: payments_wait.php?ref=' . urlencode($reference));
					exit;
				} else {
					// mark in session and redirect back to registration with pending flag so UI shows awaiting verification
					$_SESSION['registration_pending_id'] = $registrationId;
					header('Location: register.php?pending=1');
					exit;
				}
			}

		} catch (Exception $e) {
			$pdo->rollBack();
			$errors[] = 'Failed to register: ' . $e->getMessage();
		}
	}
}

// Render a simple form when GET or on errors
$csrf = generateToken('signup_form');
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<link rel="stylesheet" href="./assets/css/main-fixed.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

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
													<div class="admin-notice admin-warning">
														<?php foreach($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
													</div>
												<?php endif; ?>
												<?php if (!empty($varies_notice)): ?>
													<div class="admin-notice admin-error">
														<?= htmlspecialchars($varies_notice) ?>
													</div>
												<?php endif; ?>
												<?php if ($success): ?>
													<div class="admin-notice admin-success">
														<?= htmlspecialchars($success) ?>
													</div>
												<?php endif; ?>

												<?php if (!empty($_GET['pending']) || !empty($_SESSION['registration_pending_id'])): ?>
													<script>
													document.addEventListener('DOMContentLoaded', () => {
														Swal.fire({
															icon: 'info',
															title: 'Registration Submitted',
															html: 'Your registration was received and is pending review by an administrator. You will receive an email and/or phone call when your registration is verified.<br><strong>No payment is required until verification is complete.</strong>',
															showCancelButton: true,
															confirmButtonText: 'Go to Dashboard',
															cancelButtonText: 'Stay on this page',
															footer: '<a href="/public/terms.php" target="_blank" class="swal-footer-link">Terms & Privacy</a>',
															didClose: () => {
																// optional: focus return
															}
														}).then(result => {
															if (result.isConfirmed) {
																// Redirect to a sensible place (home or student list)
																window.location = 'index.php';
															}
														});
													});
													</script>
													<?php unset($_SESSION['registration_pending_id']); ?>
												<?php else: ?>
													<form method="post">
													<input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf) ?>">
													<!-- client_total is set by JS to allow server-side re-check of the UI-calculated total -->
													<input type="hidden" name="client_total" id="client_total_input" value="">
													<h4 class="section-title"><i class="bx bxs-user"></i> Personal Information</h4>
													<div class="section-body">
																									<div class="form-row form-inline"><div><label>First Name *</label><input type="text" name="first_name" placeholder="Enter your first name" required value="<?= htmlspecialchars($first_name ?? '') ?>"></div><div><label>Last Name *</label><input type="text" name="last_name" placeholder="Enter your last name" required value="<?= htmlspecialchars($last_name ?? '') ?>"></div></div>
																									<div class="form-row form-inline"><div class="form-col"><label>Contact Email</label><input name="email_contact" type="email" placeholder="your.email@example.com" value="<?= htmlspecialchars($email_contact ?? '') ?>"></div><div class="form-col"><label>Phone Number</label><input name="phone" placeholder="+234 XXX XXX XXXX" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"></div></div>
																									<div class="form-row"><label>Date of Birth</label><input name="date_of_birth" type="date" placeholder="dd/mm/yyyy" value="<?= htmlspecialchars($date_of_birth ?? '') ?>"></div>
																									<div class="form-row"><label>Home Address</label><textarea name="home_address" placeholder="Enter your complete home address"><?= htmlspecialchars($home_address ?? '') ?></textarea></div>

													<h4 class="section-title"><i class="bx bx-collection"></i> Program Selection</h4>
													<div class="programs-grid">
														<?php if (empty($courses)): ?><p>No programs available currently.</p><?php endif; ?>
														<?php foreach ($courses as $c): ?>
																<label class="program-label">
																		<input type="checkbox" name="programs[]" value="<?= $c['id'] ?>"> <?= htmlspecialchars($c['title']) ?> <small class="program-price">(<?= ($c['price'] === null || $c['price'] === '') ? 'Varies' : '₦' . number_format($c['price'],2) ?>)</small>
																		<div class="program-duration"><?= htmlspecialchars($c['duration'] ?? '') ?></div>
																	</label>
															<?php endforeach; ?>
													</div>

													<div class="form-row"><label>Previous Education</label><textarea name="previous_education" placeholder="Tell us about your educational background (schools attended, certificates obtained, etc.)"><?= htmlspecialchars($previous_education ?? '') ?></textarea></div>
													<div class="form-row"><label>Academic Goals</label><textarea name="academic_goals" placeholder="What are your academic and career aspirations? How can we help you achieve them?"><?= htmlspecialchars($academic_goals ?? '') ?></textarea></div>

													<h4 class="section-title"><i class="bx bxs-phone"></i> Emergency Contact</h4>
													<div class="section-body">
													<div class="form-row"><label>Parent/Guardian Name</label><input type="text" name="emergency_name" placeholder="Full name of parent/guardian" value="<?= htmlspecialchars($emergency_name ?? '') ?>"></div>
													<div class="form-row"><label>Parent/Guardian Phone</label><input type="tel" name="emergency_phone" placeholder="+234 XXX XXX XXXX" value="<?= htmlspecialchars($emergency_phone ?? '') ?>"></div>
													<div class="form-row"><label>Relationship to student</label><input type="text" name="emergency_relationship" placeholder="e.g. Father, Mother, Guardian" value="<?= htmlspecialchars($emergency_relationship ?? '') ?>"></div>
													</div>

													<div class="form-row terms-row">
														<div class="checkbox-wrapper">
															<input 
																type="checkbox" 
																name="agreed_terms" 
																id="agreed_terms" 
																<?= !empty($agreed_terms) ? 'checked' : '' ?> 
																required
															>
															<label for="agreed_terms" class="terms-label">
																<span>I agree to the <a href="/terms.php" target="_blank">terms and conditions</a></span>
															</label>
														</div>
													</div>
													<div class="submit-row"><button class="btn-primary btn-submit" type="submit">Submit Registration</button></div>
												</form>
											</div>
										</main>

										<aside class="register-sidebar hq-aside-target">
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
								<!-- Visual toggle for testing mobile payment panel behavior -->
								<button id="mps-toggle" type="button" class="mps-toggle" aria-pressed="false">Show mobile payment panel</button>
						<div class="payment-method" data-method="bank">
							<strong>Bank Transfer</strong>
									<p>Account Name: <?= htmlspecialchars($siteSettings['bank_account_name'] ?? 'High Q Solid Academy Limited') ?><br>
									Bank: <?= htmlspecialchars($siteSettings['bank_name'] ?? '[Bank Name]') ?><br>
									Account Number: <?= htmlspecialchars($siteSettings['bank_account_number'] ?? '[Account Number]') ?></p>
						</div>
						<div class="payment-method" data-method="cash">
							<strong>Cash Payment</strong>
							<p>Visit our office locations<br>8 Pineapple Avenue, Aiyetoro, Maya<br>Shop 18, World Star Complex, Aiyetoro</p>
						</div>
						<div class="payment-method" data-method="online" id="payment-method-online">
							<strong>Online Payment</strong>
							<p>Secure online payment portal. Credit/Debit card accepted.</p>
						</div>

						<!-- Payment summary: subtotal of selected programs + fixed form/card fees -->
						<div class="payment-summary">
							<h5 class="payment-summary-title">Payment Summary</h5>
							<div class="payment-summary-body">
								<div>Programs subtotal: <strong id="ps-subtotal">₦0.00</strong></div>
								<div>Form fee: <strong id="ps-form">₦<?= number_format($form_fee,2) ?></strong></div>
								<div>Card fee: <strong id="ps-card">₦<?= number_format($card_fee,2) ?></strong></div>
								<hr class="ps-divider">
								<div>Total payable: <strong id="ps-total">₦0.00</strong></div>
							</div>
							<p class="payment-note">Note: A processing Form fee (₦1,000) and Card fee (₦1,500) apply once you select any program. These fees are included in the total amount shown and are required at checkout.</p>
						</div>
					</div>

							<div class="sidebar-card help-box">
						<h4>Need Help?</h4>
								<p><strong>Call Us</strong><br><?= htmlspecialchars($siteSettings['contact_phone'] ?? '0807 208 8794') ?></p>
								<p><strong>Email Us</strong><br><?= htmlspecialchars($siteSettings['contact_email'] ?? 'info@hqacademy.com') ?></p>
								<p><strong>Visit Us</strong><br><?= nl2br(htmlspecialchars($siteSettings['contact_address'] ?? "8 Pineapple Avenue, Aiyetoro\nMaya, Ikorodu")) ?></p>
					</div>

					<div class="sidebar-card why-box" id="whyChooseUs">
						<h4>Why Choose Us?</h4>
						<div class="why-stats">
							<div class="stat">
								<div class="icon">
									<i class="bx bx-trophy"></i>
								</div>
								<div class="stat-body">
									<strong>305</strong>
									<span>Highest JAMB Score 2025</span>
								</div>
							</div>
							<div class="stat">
								<div class="icon">
									<i class="bx bx-group"></i>
								</div>
								<div class="stat-body">
									<strong>1000+</strong>
									<span>Students Trained</span>
								</div>
							</div>
							<div class="stat">
								<div class="icon">
									<i class="bx bx-bar-chart"></i>
								</div>
								<div class="stat-body">
									<strong>99%</strong>
									<span>Success Rate</span>
								</div>
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
	<div class="ceo-heading ceo-heading--center">
			<h2>What Happens <span class="highlight">Next?</span></h2>
			<p class="ceo-subtext">After submitting your registration, here's what you can expect from us.</p>
		</div>

		<div class="achievements-grid">
				<div class="next-stat yellow">
					<div class="next-icon"><i class="bx bx-check-circle"></i></div>
				<strong>1. Confirmation</strong>
				<span>You'll receive an email confirmation within 1 hour and a call from our team within 24 hours.</span>
			</div>

				<div class="next-stat yellow">
					<div class="next-icon"><i class="bx bx-book-open"></i></div>
				<strong>2. Assessment</strong>
				<span>We'll schedule a brief assessment to understand your current level and customize your learning path.</span>
			</div>

				<div class="next-stat red">
					<div class="next-icon"><i class="bx bx-rocket"></i></div>
				<strong>3. Start Learning</strong>
				<span>Begin your journey with our expert tutors and join the ranks of our successful students.</span>
			</div>
		</div>
	</div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

<?php endif; ?>

<!-- Strong debug overrides to ensure site CSS doesn't win during tests -->
<style id="hq-register-override">
/* Force layout and widths for debugging — high specificity + !important */
body .container.register-layout {
	display: grid !important;
	grid-template-columns: 2.2fr 0.8fr !important; /* wider form, narrower sidebar */
	gap: 28px !important;
	max-width: 1200px !important;
	margin: 3rem auto !important;
	padding: 0 1rem !important;
}

/* Ensure main area stretches — override any max-width from other files */
.register-layout .register-main {
	width: auto !important;
	max-width: none !important;
	flex: none !important;
}

/* Narrow the sidebar and fix its basis */
.register-layout .register-sidebar {
	flex: none !important;
	width: 320px !important;
	max-width: 320px !important;
	min-width: 240px !important;
}

/* As a visual aid during debugging, outline the areas (remove later) */
.register-layout .register-main { outline: 2px dashed rgba(0,0,0,0.06) !important; }
.register-layout .register-sidebar { outline: 2px dashed rgba(0,0,0,0.06) !important; }
</style>

<style id="hq-register-controls">
/* Checkbox visual fixes */
.program-label input[type="checkbox"],
.checkbox-wrapper input[type="checkbox"] {
	width: 18px !important;
	height: 18px !important;
	accent-color: var(--hq-yellow, #f5b904) !important; /* modern browsers */
	vertical-align: middle !important;
	margin-right: 8px !important;
}

/* Terms row: align checkbox and label on one line for desktop */
@media (min-width: 768px) {
	.terms-row .checkbox-wrapper {
		display: flex !important;
		align-items: center !important;
		gap: 12px !important;
	}
	.terms-row .terms-label { margin: 0 !important; }
}

/* Why Choose Us: stack stats into one column (vertical) */
.why-box .why-stats {
	display: flex !important;
	flex-direction: column !important;
	gap: 14px !important;
}
.why-box .why-stats .stat {
	display:flex !important;
	align-items:center !important;
	gap:16px !important;
	padding: 12px 10px !important;
	background: transparent !important;
	border-radius: 8px !important;
}
.why-box .why-stats .stat .icon {
	width:48px !important;
	height:48px !important;
	display:flex !important;
	align-items:center !important;
	justify-content:center !important;
	background: rgba(0,0,0,0.03) !important;
	border-radius: 10px !important;
	color: #d9534f !important;
	font-size: 20px !important;
}
.why-box .why-stats .stat .stat-body {
	display:flex !important;
	flex-direction:column !important;
}
.why-box .why-stats .stat .stat-body strong {
	font-size: 20px !important;
	color: var(--hq-black) !important;
	margin-bottom:6px !important;
}
.why-box .why-stats .stat .stat-body span { font-size: 13px !important; color: var(--hq-gray) !important; }

</style>

<script>
// Live payment summary for registration page
document.addEventListener('DOMContentLoaded', function(){
	try{
		const formatN = (n) => '₦' + Number(n).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
		const checkboxes = Array.from(document.querySelectorAll('input[name="programs[]"]'));
		const subtotalEl = document.getElementById('ps-subtotal');
		const formEl = document.getElementById('ps-form');
		const cardEl = document.getElementById('ps-card');
		const totalEl = document.getElementById('ps-total');
		const formFee = <?= intval($form_fee) ?>;
		const cardFee = <?= intval($card_fee) ?>;

		function compute(){
			let sub = 0;
			let hasVaries = false;
			checkboxes.forEach(cb => {
				if (!cb.checked) return;
				const label = cb.closest('label');
				const priceText = label ? label.querySelector('small') : null;
				if (priceText) {
					const txt = priceText.textContent || '';
					if (/Varies/i.test(txt)) { hasVaries = true; }
					const m = txt.match(/₦([0-9,\.]+)/);
					if (m) { sub += parseFloat(m[1].replace(/,/g,'')); }
				}
			});

			// if any program selected, include fixed fees (even if subtotal 0 for safety)
			let total = sub;
			if (checkboxes.some(cb=>cb.checked)) {
				total += formFee + cardFee;
			}

			subtotalEl.textContent = formatN(sub);
			formEl.textContent = formatN(formFee);
			cardEl.textContent = formatN(cardFee);
			totalEl.textContent = formatN(total);

			// persist client-side total to hidden input for server-side recheck
			try {
				var clientInput = document.getElementById('client_total_input');
				if (clientInput) clientInput.value = total.toFixed(2);
			} catch(e) {}

			// Enable/disable online payment UI when variable-priced programs are selected
			try {
				var onlineBlock = document.getElementById('payment-method-online');
				var paymentSummary = document.querySelector('.payment-summary');
				// add/remove disabled class
				if (onlineBlock) {
					if (hasVaries) onlineBlock.classList.add('disabled'); else onlineBlock.classList.remove('disabled');
				}
				// show a small inline note in payment summary if varies
				if (paymentSummary) {
					var existing = paymentSummary.querySelector('.varies-note');
					if (hasVaries) {
						if (!existing) {
							var n = document.createElement('div');
							n.className = 'varies-note';
							n.style.color = '#a33';
							n.style.marginTop = '8px';
							n.textContent = 'Online payment disabled for variable-priced programs. An admin will confirm final pricing.';
							paymentSummary.appendChild(n);
						}
					} else {
						if (existing) existing.remove();
					}
				}
			} catch(e) {}

			// if any selected program is 'Varies', inform the user via toast
			if (hasVaries) {
				if (typeof Swal !== 'undefined') {
					Swal.fire({
						icon: 'info',
						title: 'Price varies',
						text: 'One or more selected programs have variable pricing. An administrator will contact you to confirm pricing before payment.',
						toast: true,
						position: 'top-end',
						timer: 6000,
						showConfirmButton: false
					});
				} else {
					// fallback alert
					console.log('Selected program(s) have variable pricing; admin will contact the student.');
					// avoid annoying alert popup; console log only when Swal not available
				}
			}
		}

		// Expose compute globally so other scripts (mobile panel, debug helpers) can call or wrap it
		try { window.compute = compute; } catch(e) { /* ignore */ }

		// Attach listeners
		checkboxes.forEach(cb => cb.addEventListener('change', compute));
		// init
		compute();
	}catch(e){/* ignore */}
});
</script>
<!-- Mobile payment summary and computed-style logger -->
<style>
/* Mobile payment summary panel */
#mobilePaymentSummary {
	display: none;
	position: fixed;
	left: 12px;
	right: 12px;
	bottom: 12px;
	background: #fff;
	border-radius: 10px;
	box-shadow: 0 10px 30px rgba(0,0,0,0.15);
	z-index: 1500;
	padding: 12px;
	max-height: 60vh;
	overflow: auto;
}
#mobilePaymentSummary .mps-close { position:absolute; right:12px; top:8px; background:transparent; border:none; font-size:18px; cursor:pointer }
@media (min-width: 901px) {
	#mobilePaymentSummary { display:none !important; }
}
</style>

<div id="mobilePaymentSummary" aria-hidden="true">
	<button class="mps-close" aria-label="Close">✕</button>
	<div class="mps-content"></div>
</div>

<script>
// Computed-style logger for debugging: logs computed values and attempts to find matching stylesheet rules
function hqLogComputedStyles() {
	const selectors = ['.container.register-layout', '.register-layout .register-main', '.register-layout .register-sidebar', '.payment-summary', '.why-box .why-stats'];
	selectors.forEach(sel => {
		const el = document.querySelector(sel);
		console.groupCollapsed('Computed style for ' + sel);
		if (!el) { console.log('Element not found: ' + sel); console.groupEnd(); return; }
		const cs = getComputedStyle(el);
		console.log('inline style:', el.style && el.style.cssText);
		console.log('display:', cs.getPropertyValue('display'));
		console.log('width:', cs.getPropertyValue('width'));
		console.log('max-width:', cs.getPropertyValue('max-width'));
		console.log('grid-template-columns:', cs.getPropertyValue('grid-template-columns'));
		console.log('flex:', cs.getPropertyValue('flex'));
		console.log('box-sizing:', cs.getPropertyValue('box-sizing'));
		// Try to find rules in document stylesheets that mention the selector or class
		try {
			const rulesFound = [];
			Array.from(document.styleSheets).forEach(sheet => {
				try {
					Array.from(sheet.cssRules || []).forEach(r => {
						if (r.selectorText && r.selectorText.indexOf(sel.replace(/\s+/g,' ')) !== -1) rulesFound.push(r.cssText);
						// also match class fragments
						if (r.selectorText && sel.split(' ').some(s => s.startsWith('.') && r.selectorText.indexOf(s) !== -1)) rulesFound.push(r.cssText);
					});
				} catch(e) { /* cross-origin or inaccessible stylesheet */ }
			});
			if (rulesFound.length) { console.log('Matching rules (first 20):', rulesFound.slice(0,20)); }
			else { console.log('No matching stylesheet rules found (or CORS blocked).'); }
		} catch(e) { console.log('Error scanning stylesheets:', e); }
		console.groupEnd();
	});
}

// Add a console-friendly helper
window.hqLogComputedStyles = hqLogComputedStyles;

// Mobile payment summary: clone payment-summary content into the mobile panel and show when programs are tapped (mobile only)
function initMobilePaymentSummary() {
	const mobile = document.getElementById('mobilePaymentSummary');
	const mpsContent = mobile.querySelector('.mps-content');
	const paymentSummary = document.querySelector('.payment-summary');
	const closeBtn = mobile.querySelector('.mps-close');
	function updateMobileContent() {
		if (!paymentSummary) return;
		mpsContent.innerHTML = paymentSummary.innerHTML;
	}
	// show panel
	function showMobilePanel() {
		updateMobileContent();
		mobile.style.display = 'block';
		mobile.setAttribute('aria-hidden','false');
	}
	function hideMobilePanel() {
		mobile.style.display = 'none';
		mobile.setAttribute('aria-hidden','true');
	}
	closeBtn.addEventListener('click', hideMobilePanel);

	// show when any program checkbox or label clicked on small screens
	function attachShowHandlers() {
		const programInputs = Array.from(document.querySelectorAll('input[name="programs[]"]'));
		const programLabels = Array.from(document.querySelectorAll('.program-label'));
		const showIfMobile = (e) => {
			if (window.innerWidth <= 900) {
				// small delay to let compute update values
				setTimeout(showMobilePanel, 120);
			}
		};
		programInputs.forEach(i => i.addEventListener('change', showIfMobile));
		programLabels.forEach(l => l.addEventListener('click', showIfMobile));
	}

	// keep mobile content in sync when compute() runs: override compute to also update mobile panel if visible
	const originalCompute = window.compute;
	if (typeof originalCompute === 'function') {
		window.compute = function(){
			try { originalCompute(); } catch(e){}
			try { if (mobile.style.display !== 'none') updateMobileContent(); } catch(e){}
		};
	}

	attachShowHandlers();
}

// Initialize mobile summary after DOM ready
document.addEventListener('DOMContentLoaded', function(){
	try { initMobilePaymentSummary(); } catch(e) { console.warn('Mobile payment summary init failed', e); }
});
</script>
<!-- FINAL DEBUG OVERRIDE: force registration layout widths (remove when done) -->
<style id="hq-register-override-final">
html body.hq-public .container.register-layout,
html body[class] .container.register-layout,
.container.register-layout {
	display: grid !important;
	grid-template-columns: 2.2fr 0.8fr !important;
	gap: 28px !important;
	max-width: 1200px !important;
	margin: 3rem auto !important;
	padding: 0 1rem !important;
}

.container.register-layout .register-main {
	width: auto !important;
	max-width: none !important;
	flex: none !important;
}

.container.register-layout .register-sidebar {
	flex: none !important;
	width: 320px !important;
	max-width: 320px !important;
	min-width: 240px !important;
}

/* Visual outlines for debugging - remove later */
.container.register-layout .register-main { outline: 2px dashed rgba(0,0,0,0.06) !important; }
.container.register-layout .register-sidebar { outline: 2px dashed rgba(0,0,0,0.06) !important; }
</style>
<style id="hq-register-override-why-final">
.container.register-layout .why-box .why-stats {
	display: flex !important;
	flex-direction: column !important;
	gap: 14px !important;
}
.container.register-layout .why-box .why-stats .stat {
	display:flex !important;
	align-items:center !important;
	gap:16px !important;
	padding: 12px 10px !important;
}
.container.register-layout .why-box .why-stats .stat .icon {
	width:48px !important;
	height:48px !important;
	display:flex !important;
	align-items:center !important;
	justify-content:center !important;
	color: #d9534f !important;
	font-size: 20px !important;
}
.container.register-layout .why-box .why-stats .stat .stat-body strong { font-size:20px !important; margin-bottom:6px !important; }
.container.register-layout .why-box .why-stats .stat .stat-body span { font-size:13px !important; color: var(--hq-gray) !important; }
</style>
<style id="hq-register-override-final-extra">
/* Final override for checkbox and why-box */
.container.register-layout .program-label input[type="checkbox"],
.container.register-layout .checkbox-wrapper input[type="checkbox"] {
	width: 18px !important;
	height: 18px !important;
	accent-color: var(--hq-yellow, #f5b904) !important;
}

@media (min-width: 768px) {
	.container.register-layout .terms-row .checkbox-wrapper {
		display: flex !important;
		align-items: center !important;
		gap: 12px !important;
	}
}

.container.register-layout .why-box .why-stats {
	display: flex !important;
	flex-direction: column !important;
}

</style>