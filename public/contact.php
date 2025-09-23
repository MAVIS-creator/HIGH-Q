<?php
// public/contact.php - contact form that emails using sendEmail()
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/functions.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$token = $_POST['_csrf_token'] ?? '';
		if (!verifyToken('contact_form', $token)) { $errors[] = 'Invalid CSRF token.'; }

		$first_name = trim($_POST['first_name'] ?? '');
		$last_name = trim($_POST['last_name'] ?? '');
		$email = trim($_POST['email'] ?? '');
		$phone = trim($_POST['phone'] ?? '');
		$program = trim($_POST['program'] ?? '');
		$message = trim($_POST['message'] ?? '');

		if (!$first_name || !$email || !$message) { $errors[] = 'Please provide your first name, email and message.'; }
		if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Invalid email address.'; }

		if (empty($errors)) {
				$to = 'highqsolidacademy@gmail.com';
				$subject = 'Website Contact: ' . ($program ? $program : 'General Inquiry');
				$html = "<h3>Contact form submission</h3>";
				$html .= "<p><strong>Name:</strong> " . htmlspecialchars($first_name . ' ' . $last_name) . "</p>";
				$html .= "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
				$html .= "<p><strong>Phone:</strong> " . htmlspecialchars($phone) . "</p>";
				$html .= "<p><strong>Program of interest:</strong> " . htmlspecialchars($program) . "</p>";
				$html .= "<p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>";

				$sent = sendEmail($to, $subject, $html);
				if ($sent) {
						$success = 'Thanks! Your message has been sent. We will get back to you within 24 hours.';
				} else {
						$errors[] = 'Failed to send your message. Please try again later.';
				}
		}
}

$csrf = generateToken('contact_form');
include __DIR__ . '/includes/header.php';
?>

<section class="about-hero">
	<div class="about-hero-overlay"></div>
	<div class="container about-hero-inner">
		<h1>Contact Us</h1>
		<p class="lead">Get in touch with our team. We're here to help you start your journey towards academic excellence.</p>
	</div>
</section>

<div class="container register-layout contact-layout" style="margin-top:28px;">
	<main class="register-main">
		<div class="card">
			<h3>Send Us a <span style="color:var(--hq-yellow);">Message</span></h3>
			<p class="card-desc">Fill out the form below and we'll get back to you within 24 hours.</p>

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
				<div class="form-row form-inline">
					<div style="flex:1"><label>First Name</label><input name="first_name" placeholder="Your first name" required value="<?= htmlspecialchars($first_name ?? '') ?>"></div>
					<div style="flex:1"><label>Last Name</label><input name="last_name" placeholder="Your last name" value="<?= htmlspecialchars($last_name ?? '') ?>"></div>
				</div>

				<div class="form-row"><label>Email Address</label><input type="email" name="email" placeholder="your.email@example.com" required value="<?= htmlspecialchars($email ?? '') ?>"></div>
				<div class="form-row"><label>Phone Number</label><input name="phone" placeholder="+234 XXX XXX XXXX" value="<?= htmlspecialchars($phone ?? '') ?>"></div>

				<div class="form-row"><label>Program of Interest</label>
					<select name="program">
						<option value="">Select a program</option>
						<?php
						try { $courses = $pdo->query("SELECT id,title FROM courses WHERE is_active=1 ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC); }
						catch(Throwable $e) { $courses = []; }
						foreach($courses as $c) {
							$sel = (isset($program) && $program == $c['title']) ? 'selected' : '';
							echo '<option value="' . htmlspecialchars($c['title']) . '" ' . $sel . '>' . htmlspecialchars($c['title']) . '</option>';
						}
						?>
					</select>
				</div>

				<div class="form-row"><label>Message</label><textarea name="message" placeholder="Tell us about your educational goals and any questions you have..." required><?= htmlspecialchars($message ?? '') ?></textarea></div>

				<div style="margin-top:12px;"><button class="btn-primary" type="submit"><i class="bx bx-send"></i> Send Message</button></div>
			</form>
		</div>
	</main>

	<aside class="register-sidebar">
				<div class="sidebar-card" data-icon="tutor">
					<img class="card-icon" src="assets/images/icons/book-open.svg" alt="Tutorial Center icon">
					<h4>Tutorial Center</h4>
					<p style="color:var(--hq-gray);">8 Pineapple Avenue, Aiyetoro<br>Ikorodu North LCDA,<br>Maya, Ikorodu</p>
				</div>

				<div class="sidebar-card" data-icon="office">
					<img class="card-icon" src="assets/images/icons/target.svg" alt="Area Office icon">
					<h4>Area Office</h4>
					<p style="color:var(--hq-gray);">Shop 18, World Star Complex<br>Opposite London Street,<br>Aiyetoro Maya, Ikorodu, Lagos State</p>
				</div>

				<div class="sidebar-card" data-icon="contact">
					<img class="card-icon" src="assets/images/icons/phone.svg" alt="Contact icon">
					<h4>Contact Information</h4>
					<p style="color:var(--hq-gray);"><strong>Phone</strong><br>0807 208 8794</p>
					<p style="color:var(--hq-gray);"><strong>Email</strong><br>info@hqacademy.com</p>
					<p style="color:var(--hq-gray);"><strong>Office Hours</strong><br>Mon - Fri: 8:00 AM - 6:00 PM<br>Sat: 9:00 AM - 4:00 PM</p>
				</div>
	</aside>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
