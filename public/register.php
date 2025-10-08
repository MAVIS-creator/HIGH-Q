<?php
// public/register.php
// Use public-side config/includes (avoid pulling admin internals)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/functions.php';
$cfg = require __DIR__ . '/../config/payments.php';

$errors = [];
$success = '';

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
	$method = $_POST['method'] ?? 'bank'; // 'bank' or 'paystack'

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

										<div class="container py-4">
										<div class="row g-4">
											<main class="col-12 col-lg-8">
												<div class="card border-0 shadow-sm">
													<div class="card-body p-4">
														<h3 class="h4 fw-bold mb-2">Student Registration Form</h3>
														<p class="text-secondary mb-4">Fill out this form to begin your registration process. Our team will contact you within 24 hours to complete your enrollment.</p>
														<?php if (!empty($errors)): ?>
															<div class="alert alert-warning border-start border-warning border-4">
																<?php foreach($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
															</div>
														<?php endif; ?>
												<?php if ($success): ?>
													<div class="admin-notice" style="background:#e6fff0;border-left:4px solid #3cb371;padding:12px;margin-bottom:12px;color:#094;">
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
															footer: '<a href="/public/terms.php" target="_blank" style="color:#444">Terms & Privacy</a>',
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
													<div class="mb-4">
														<h4 class="h5 fw-bold d-flex align-items-center gap-2 mb-3">
															<i class="bx bxs-user fs-4"></i> Personal Information
														</h4>
														<div class="row g-3">
															<div class="col-12 col-sm-6">
																<div class="form-floating">
																	<input type="text" class="form-control" id="first_name" name="first_name" placeholder="Enter your first name" value="<?= htmlspecialchars($first_name ?? '') ?>" required>
																	<label for="first_name">First Name *</label>
																</div>
															</div>
															<div class="col-12 col-sm-6">
																<div class="form-floating">
																	<input type="text" class="form-control" id="last_name" name="last_name" placeholder="Enter your last name" value="<?= htmlspecialchars($last_name ?? '') ?>" required>
																	<label for="last_name">Last Name *</label>
																</div>
															</div>
															<div class="col-12 col-sm-6">
																<div class="form-floating">
																	<input type="email" class="form-control" id="email_contact" name="email_contact" placeholder="your.email@example.com" value="<?= htmlspecialchars($email_contact ?? '') ?>">
																	<label for="email_contact">Contact Email</label>
																</div>
															</div>
															<div class="col-12 col-sm-6">
																<div class="form-floating">
																	<input type="tel" class="form-control" id="phone" name="phone" placeholder="+234 XXX XXX XXXX" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
																	<label for="phone">Phone Number</label>
																</div>
															</div>
															<div class="col-12">
																<div class="form-floating">
																	<input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="<?= htmlspecialchars($date_of_birth ?? '') ?>">
																	<label for="date_of_birth">Date of Birth</label>
																</div>
															</div>
															<div class="col-12">
																<div class="form-floating">
																	<textarea class="form-control" id="home_address" name="home_address" placeholder="Enter your complete home address" style="height:100px"><?= htmlspecialchars($home_address ?? '') ?></textarea>
																	<label for="home_address">Home Address</label>
																</div>
															</div>
														</div>
													</div>

													<div class="mb-4">
														<h4 class="h5 fw-bold d-flex align-items-center gap-2 mb-3">
															<i class="bx bx-collection fs-4"></i> Program Selection
														</h4>
														<div class="row g-3">
															<?php if (empty($courses)): ?>
																<div class="col-12">
																	<p class="text-secondary">No programs available currently.</p>
																</div>
															<?php else: ?>
																<?php foreach ($courses as $c): ?>
																	<div class="col-12">
																		<div class="form-check card">
																			<div class="card-body">
																				<input type="checkbox" class="form-check-input me-2" name="programs[]" id="program_<?= $c['id'] ?>" value="<?= $c['id'] ?>">
																				<label class="form-check-label w-100" for="program_<?= $c['id'] ?>">
																					<div class="fw-bold"><?= htmlspecialchars($c['title']) ?></div>
																					<div class="small text-secondary">
																						<span class="badge bg-warning text-dark"><?= ($c['price'] === null || $c['price'] === '') ? 'Varies' : '₦' . number_format($c['price'],2) ?></span>
																						<?php if (!empty($c['duration'])): ?>
																							<span class="ms-2"><?= htmlspecialchars($c['duration']) ?></span>
																						<?php endif; ?>
																					</div>
																				</label>
																			</div>
																		</div>
																	</div>
																<?php endforeach; ?>
															<?php endif; ?>
														</div>
													</div>

													<div class="mb-4">
														<div class="row g-3">
															<div class="col-12">
																<div class="form-floating">
																	<textarea class="form-control" id="previous_education" name="previous_education" placeholder="Tell us about your educational background" style="height:120px"><?= htmlspecialchars($previous_education ?? '') ?></textarea>
																	<label for="previous_education">Previous Education</label>
																	<div class="form-text">Schools attended, certificates obtained, etc.</div>
																</div>
															</div>
															<div class="col-12">
																<div class="form-floating">
																	<textarea class="form-control" id="academic_goals" name="academic_goals" placeholder="What are your academic and career aspirations?" style="height:120px"><?= htmlspecialchars($academic_goals ?? '') ?></textarea>
																	<label for="academic_goals">Academic Goals</label>
																	<div class="form-text">Tell us how we can help you achieve your goals</div>
																</div>
															</div>
														</div>
													</div>

													<div class="mb-4">
														<h4 class="h5 fw-bold d-flex align-items-center gap-2 mb-3">
															<i class="bx bxs-phone fs-4"></i> Emergency Contact
														</h4>
														<div class="row g-3">
															<div class="col-12">
																<div class="form-floating">
																	<input type="text" class="form-control" id="emergency_name" name="emergency_name" placeholder="Full name of parent/guardian" value="<?= htmlspecialchars($emergency_name ?? '') ?>">
																	<label for="emergency_name">Parent/Guardian Name</label>
																</div>
															</div>
															<div class="col-12 col-sm-6">
																<div class="form-floating">
																	<input type="tel" class="form-control" id="emergency_phone" name="emergency_phone" placeholder="+234 XXX XXX XXXX" value="<?= htmlspecialchars($emergency_phone ?? '') ?>">
																	<label for="emergency_phone">Parent/Guardian Phone</label>
																</div>
															</div>
															<div class="col-12 col-sm-6">
																<div class="form-floating">
																	<input type="text" class="form-control" id="emergency_relationship" name="emergency_relationship" placeholder="e.g. Father, Mother, Guardian" value="<?= htmlspecialchars($emergency_relationship ?? '') ?>">
																	<label for="emergency_relationship">Relationship to Student</label>
																</div>
															</div>
														</div>
													</div>

													<div class="mb-4">
														<div class="form-check">
															<input class="form-check-input" type="checkbox" name="agreed_terms" id="agreed_terms" <?= !empty($agreed_terms) ? 'checked' : '' ?> required>
															<label class="form-check-label" for="agreed_terms">
																I agree to the <a href="/terms.php" class="text-decoration-underline" target="_blank">terms and conditions</a>
															</label>
														</div>
													</div>
													<div class="d-grid">
														<button class="btn btn-primary btn-lg" type="submit">Submit Registration</button>
													</div>
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
		<div class="ceo-heading" style="text-align:center;">
			<h2>What Happens <span class="highlight">Next?</span></h2>
			<p style="color:var(--hq-gray); margin-top:8px;">After submitting your registration, here's what you can expect from us.</p>
		</div>

		<div class="achievements-grid">
				<div class="next-stat yellow">
					<div class="next-icon"><i class="bx bx-check-circle" style="font-size:26px;color:#d99a00"></i></div>
				<strong>1. Confirmation</strong>
				<span>You'll receive an email confirmation within 1 hour and a call from our team within 24 hours.</span>
			</div>

				<div class="next-stat yellow">
					<div class="next-icon"><i class="bx bx-book-open" style="font-size:26px;color:#d99a00"></i></div>
				<strong>2. Assessment</strong>
				<span>We'll schedule a brief assessment to understand your current level and customize your learning path.</span>
			</div>

				<div class="next-stat red">
					<div class="next-icon"><i class="bx bx-rocket" style="font-size:26px;color:#d9534f"></i></div>
				<strong>3. Start Learning</strong>
				<span>Begin your journey with our expert tutors and join the ranks of our successful students.</span>
			</div>
		</div>
	</div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

<?php endif; ?>