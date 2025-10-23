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
				$selectedHasAnyVaries = false;
				$selectedHasAnyFixed = false;
				$selectedFixedIds = [];
				$selectedVariesIds = [];
				foreach ($rows as $r) {
					// if price is null or empty treat as 'Varies'
					if (!isset($r['price']) || $r['price'] === null || $r['price'] === '') {
						$selectedHasAnyVaries = true;
						$selectedVariesIds[] = (int)$r['id'];
					} else {
						$selectedHasAnyFixed = true;
						$selectedFixedIds[] = (int)$r['id'];
						$amount += floatval($r['price']);
					}
				}
				// if all selected are 'Varies' then we have no fixed-priced items
				$selectedAllVaries = ($selectedHasAnyVaries && !$selectedHasAnyFixed);
	}
	$amount = round($amount, 2);

	// Add fixed form/card fees to the amount server-side if there are any fixed-priced selections
	// Note: If all selected programs are variable-priced the registration will require admin verification before payment
	if (!empty($programs) && $selectedHasAnyFixed) {
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

	$method = 'bank'; // Force bank transfer for now

	// If all selected programs are variable-priced, disable online methods server-side and force bank transfer
	$varies_notice = '';
	if (!empty($selectedAllVaries)) {
		$varies_notice = 'Note: One or more selected programs have variable pricing. Online payment methods are disabled for these selections; an administrator will contact you with the final amount.';
		// Force bank as the payment method to avoid online payment attempts when no fixed-price items exist
		$method = 'bank';
	} elseif (!empty($selectedHasAnyVaries)) {
		// Mixed selection: warn the user but allow online payment for fixed-price items
		$varies_notice = 'Note: Some selected programs have variable pricing. Online payment will proceed for fixed-priced items; an administrator will confirm pricing for the variable items.';
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

			// handle passport upload if present
			if (!empty($_FILES['passport']) && $_FILES['passport']['error'] === UPLOAD_ERR_OK) {
				$u = $_FILES['passport'];
				$ext = pathinfo($u['name'], PATHINFO_EXTENSION);
				$allowed = ['jpg','jpeg','png'];
				if (!in_array(strtolower($ext), $allowed)) {
					// ignore invalid types but log
					error_log('Passport upload rejected: invalid type ' . $ext);
				} else {
					$dstDir = __DIR__ . '/uploads/passports';
					if (!is_dir($dstDir)) @mkdir($dstDir, 0755, true);
					$fname = 'passport_' . $registrationId . '_' . time() . '.' . $ext;
					$dst = $dstDir . '/' . $fname;
					if (move_uploaded_file($u['tmp_name'], $dst)) {
							// store a full absolute URL so admin views will render correctly when hosted
							$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
							$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
							$baseUrl = rtrim($proto . '://' . $host, '/');
							$publicRel = '/HIGH-Q/public/uploads/passports/' . $fname;
							$fullUrl = $baseUrl . $publicRel;
							$upd = $pdo->prepare('UPDATE student_registrations SET passport_path = ? WHERE id = ?');
							$upd->execute([$fullUrl, $registrationId]);
					}
				}
			}

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
			// If all selected programs are 'Varies' (no fixed price), require verification before payment
			if (!empty($selectedAllVaries)) $verifyBeforePayment = true;

			$reference = null; $paymentId = null;
			if (!$verifyBeforePayment && $selectedHasAnyFixed) {
				// create a payment record for fixed-priced items only (partial payment)
				$reference = generatePaymentReference('REG');
				$metadata = json_encode(['fixed_programs' => $selectedFixedIds, 'varies_programs' => $selectedVariesIds]);
				$stmt = $pdo->prepare('INSERT INTO payments (student_id, amount, payment_method, reference, status, created_at, metadata) VALUES (NULL, ?, ?, ?, "pending", NOW(), ?)');
				$stmt->execute([$amount, $method, $reference, $metadata]);
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

			// For now, always use bank transfer: skip online payment logic

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
													<script>
													document.addEventListener('DOMContentLoaded', function(){
														try {
															var errHtml = <?= json_encode(implode('<br>', array_map('htmlspecialchars', $errors))) ?>;
															if (typeof Swal !== 'undefined') {
																Swal.fire({
																	icon: 'error',
																	title: 'There was a problem',
																	html: errHtml,
																	confirmButtonText: 'OK',
																	customClass: { popup: 'hq-swal' }
																});
															} else {
																// fallback: alert with joined errors
																alert(errHtml.replace(/<br\s*\/?>/g, "\n"));
															}
														} catch(e) { console.error(e); }
													});
													</script>
												<?php endif; ?>
												<?php if (!empty($varies_notice)): ?>
													<script>
													document.addEventListener('DOMContentLoaded', function(){
														try {
															var html = <?= json_encode($varies_notice) ?>;
															if (typeof Swal !== 'undefined') {
																Swal.fire({
																	icon: 'info',
																	title: 'Note',
																	html: html,
																	confirmButtonText: 'OK',
																	customClass: { popup: 'hq-swal' }
																});
															} else {
																alert(html.replace(/<br\s*\/?>/g,'\n'));
															}
														} catch(e) { console.error(e); }
													});
													</script>
												<?php endif; ?>
												<?php if ($success): ?>
													<script>
													document.addEventListener('DOMContentLoaded', function(){
														try {
															var html = <?= json_encode($success) ?>;
															if (typeof Swal !== 'undefined') {
																Swal.fire({
																	icon: 'success',
																	title: 'Success',
																	html: html,
																	confirmButtonText: 'OK',
																	customClass: { popup: 'hq-swal' }
																});
															} else {
																alert(html);
															}
														} catch(e) { console.error(e); }
													});
													</script>
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
													<form method="post" enctype="multipart/form-data">
													<input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf) ?>">
													<!-- client_total is set by JS to allow server-side re-check of the UI-calculated total -->
													<input type="hidden" name="client_total" id="client_total_input" value="">
													<input type="hidden" name="method" id="method_input" value="bank">
													<h4 class="section-title"><i class="bx bxs-user"></i> Personal Information</h4>
													<div class="section-body">
																									<div class="form-row form-inline"><div><label>First Name *</label><input type="text" name="first_name" placeholder="Enter your first name" required value="<?= htmlspecialchars($first_name ?? '') ?>"></div><div><label>Last Name *</label><input type="text" name="last_name" placeholder="Enter your last name" required value="<?= htmlspecialchars($last_name ?? '') ?>"></div></div>
																									<div class="form-row">
																										<label>Passport Photo (passport-size, face visible)</label>
																										<div class="hq-file-input">
																											<button type="button" class="btn">Choose file</button>
																											<input type="file" name="passport" id="passport_input" accept="image/*" style="display:none">
																											<span id="passport_chosen" style="margin-left:10px;color:#444;font-size:0.95rem">No file chosen</span>
																										</div>
																									</div>
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
													<!-- Move payment summary here (desktop will show it; mobile panel clones this content) -->
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
														<!-- inline summary hidden; floating panel is used for visible summary -->
													</div>
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

						<!-- payment summary moved to main form for desktop; mobile clones it into floating panel -->
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

<!-- Debug overrides removed; consolidated styles are in public/css/register.css -->

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
			let subtotalFixed = 0;
			let anyVaries = false;
			let anyFixed = false;
			checkboxes.forEach(cb => {
				if (!cb.checked) return;
				const label = cb.closest('label');
				const priceText = label ? label.querySelector('small') : null;
				if (priceText) {
					const txt = priceText.textContent || '';
					if (/Varies/i.test(txt)) { anyVaries = true; }
					const m = txt.match(/₦([0-9,\.]+)/);
					if (m) { subtotalFixed += parseFloat(m[1].replace(/,/g,'')); anyFixed = true; }
				}
			});

			// total payable on the client: only fixed-priced items + fees (if any fixed items selected)
			let total = subtotalFixed;
			if (anyFixed) {
				total += formFee + cardFee;
			}

					// Set the payment method for the server: if there are any fixed-priced items, prefer online (Paystack)
					try { var methodInput = document.getElementById('method_input'); if (methodInput) { methodInput.value = anyFixed ? 'paystack' : 'bank'; } } catch(e) {}

			subtotalEl.textContent = formatN(subtotalFixed);
			formEl.textContent = formatN(formFee);
			cardEl.textContent = formatN(cardFee);
			totalEl.textContent = formatN(total);

			// persist client-side total to hidden input for server-side recheck
			try {
				var clientInput = document.getElementById('client_total_input');
				if (clientInput) clientInput.value = total.toFixed(2);
			} catch(e) {}

			// Enable/disable online payment UI: only disable when ALL selected are variable-priced
			try {
				var onlineBlock = document.getElementById('payment-method-online');
				var paymentSummary = document.querySelector('.payment-summary');
				// add/remove disabled class
				if (onlineBlock) {
					if (anyVaries && !anyFixed) onlineBlock.classList.add('disabled'); else onlineBlock.classList.remove('disabled');
				}
				// show a small inline note in payment summary if there are variable-priced items
				if (paymentSummary) {
					var existing = paymentSummary.querySelector('.varies-note');
					if (anyVaries) {
						if (!existing) {
							var n = document.createElement('div');
							n.className = 'varies-note';
							n.style.color = '#a33';
							n.style.marginTop = '8px';
							if (anyVaries && !anyFixed) {
								n.textContent = 'Online payment disabled for variable-priced programs. An admin will confirm final pricing.';
							} else {
								n.textContent = 'Some selected programs have variable pricing. Online payment will proceed for fixed-priced items; an admin will confirm the variable items.';
							}
							paymentSummary.appendChild(n);
						}
					} else {
						if (existing) existing.remove();
					}
				}
			} catch(e) {}

			// Inform the user appropriately
			if (anyVaries) {
				if (typeof Swal !== 'undefined') {
					if (anyVaries && !anyFixed) {
						Swal.fire({
							icon: 'info',
							title: 'Price varies',
							text: 'All selected programs have variable pricing. An administrator will contact you to confirm pricing before payment.',
							toast: true,
							position: 'top-end',
							timer: 6000,
							showConfirmButton: false
						});
					} else {
						Swal.fire({
							icon: 'info',
							title: 'Mixed selection',
							text: 'Some selected programs have variable pricing. Online payment will proceed for fixed-priced items.',
							toast: true,
							position: 'top-end',
							timer: 6000,
							showConfirmButton: false
						});
					}
				} else {
					console.log('Selected program(s) include variable-priced items; admin will contact the student.');
				}
			}
		}

			// Expose compute globally so other scripts (mobile panel, debug helpers) can call or wrap it
			try { window.compute = compute; } catch(e) { /* ignore */ }

			// expose last computed state for use on submit
			try { window.hqPaymentState = { anyVaries: anyVaries, anyFixed: anyFixed, subtotalFixed: subtotalFixed, total: total }; } catch(e) {}

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
	#mobilePaymentSummary { display:none; }
}
</style>

<script>
// Passport file input wiring
document.addEventListener('DOMContentLoaded', function(){
	var btn = document.querySelector('.hq-file-input button');
	var input = document.getElementById('passport_input');
	var chosen = document.getElementById('passport_chosen');
	if (!btn || !input) return;
	btn.addEventListener('click', function(){ input.click(); });
	input.addEventListener('change', function(){
		if (input.files && input.files.length) chosen.textContent = input.files[0].name; else chosen.textContent = 'No file chosen';
	});
});
</script>

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
<script>
// Ensure the registration form sets the correct payment method before submit
(function(){
	var form = document.querySelector('form[method="post"][enctype]');
	if (!form) return;
	form.id = form.id || 'registrationForm';
	form.addEventListener('submit', function(e){
		try { if (typeof window.compute === 'function') window.compute(); } catch(e){}
		var state = window.hqPaymentState || {};
		var methodInput = document.getElementById('method_input');
		if (methodInput) {
			// prefer online when there are fixed-priced items
			if (state.anyFixed) methodInput.value = 'paystack'; else methodInput.value = 'bank';
		}
		// If we're about to go to online payment, show a brief confirm so the user knows they'll be redirected
		if (methodInput && methodInput.value === 'paystack') {
			// allow the form to submit but intercept to show confirmation
			e.preventDefault();
			if (typeof Swal !== 'undefined') {
				Swal.fire({ title: 'Proceed to payment', text: 'You will be redirected to a secure payment page to complete payment for the fixed-priced programs. Continue?', icon: 'question', showCancelButton: true }).then(function(res){ if (res.isConfirmed) form.submit(); });
			} else {
				if (confirm('You will be redirected to a secure payment page. Continue?')) form.submit();
			}
		}
	});
})();
</script>
<script>
// Program label selection visuals
document.addEventListener('DOMContentLoaded', function(){
	try{
		document.querySelectorAll('.program-label').forEach(label => {
			const input = label.querySelector('input[type="checkbox"]');
			if (!input) return;
			// sync initial state
			if (input.checked) label.classList.add('selected');
			input.addEventListener('change', function(){
				if (this.checked) label.classList.add('selected'); else label.classList.remove('selected');
			});
			// also toggle when label clicked
			label.addEventListener('click', function(e){
				// let input handle it normally
				setTimeout(()=>{ if (input.checked) label.classList.add('selected'); else label.classList.remove('selected'); }, 30);
			});
		});
	}catch(e){}
});

// Desktop/Mobile: show the floating payment panel when a program with a fixed price is selected
function programClickShowsPanel() {
	try{
		const mobile = document.getElementById('mobilePaymentSummary');
		const paymentSummary = document.querySelector('.payment-summary');
		if (!mobile || !paymentSummary) return;

		function maybeShow(e) {
			const target = e.target.closest('.program-label') || e.target.closest('label');
			if (!target) return;
			// detect if program has a fixed price (not 'Varies')
			const priceEl = target.querySelector('.program-price');
			const txt = priceEl ? priceEl.textContent || '' : '';
			if (/Varies/i.test(txt)) return; // don't show for 'Varies'
			// update content and show
			const mpsContent = mobile.querySelector('.mps-content');
			mpsContent.innerHTML = paymentSummary.innerHTML;
			mobile.style.display = 'block';
			mobile.setAttribute('aria-hidden','false');
		}

		document.querySelectorAll('.program-label').forEach(l => {
			l.addEventListener('click', maybeShow);
		});
		document.querySelectorAll('input[name="programs[]"]').forEach(i => i.addEventListener('change', maybeShow));
	}catch(e){ console.warn(e); }
}
document.addEventListener('DOMContentLoaded', programClickShowsPanel);
</script>
<script>
// FAQ read-more toggle: attach to buttons with .faq-readmore
document.addEventListener('DOMContentLoaded', function(){
	try{
		Array.from(document.querySelectorAll('.faq-readmore')).forEach(btn => {
			btn.addEventListener('click', function(e){
				const target = this.closest('.faq-card') || document.querySelector(this.getAttribute('data-target'));
				if (!target) return;
				const clamped = target.querySelector('.faq-clamped');
				if (!clamped) return;
				const expanded = clamped.classList.toggle('faq-clamped--expanded');
				this.textContent = expanded ? 'Show less' : 'Read more';
			});
		});
	}catch(e){/* ignore */}
});
</script>
<!-- Debug overrides removed: styles are consolidated into public/css/register.css -->