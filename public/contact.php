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

				<div class="form-row"><label>Message</label><textarea id="contact_message" name="message" placeholder="Tell us about your educational goals and any questions you have..." required><?= htmlspecialchars($message ?? '') ?></textarea></div>

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

				<div class="quick-actions">
					<div class="quick-action schedule" role="button" tabindex="0" id="openSchedule" aria-label="Schedule Visit">
						<i class="bx bx-calendar"></i>
						<div>Schedule Visit</div>
					</div>
					<div class="quick-action livechat" role="button" tabindex="0" id="openLiveChat" aria-label="Live Chat">
						<i class="bx bx-chat"></i>
						<div>Live Chat</div>
					</div>
				</div>
	</aside>
</div>

<!-- Frequently Asked Questions (row 1) -->
<section class="faq-section" style="margin-top:48px;background:#fbf9f7;">
	<div class="container">
		<h2 class="section-title">Frequently Asked <span style="color:var(--hq-yellow);">Questions</span></h2>
		<p class="lead">Find answers to common questions about our programs and services.</p>

		<div class="faq-grid">
			<div class="faq-card">
				<h4>What programs do you offer?</h4>
				<p>We offer comprehensive JAMB/Post-UTME preparation, WAEC/NECO preparation, digital skills training, CBT preparation, tutorial classes, and educational consultancy services.</p>
			</div>

			<div class="faq-card">
				<h4>How much do your programs cost?</h4>
				<p>Program fees vary based on duration and type. JAMB preparation ranges from ₦25,000-₦40,000, while other programs are competitively priced. Contact us for detailed pricing.</p>
			</div>
		</div>
	</div>
</section>

<section class="faq-section alt">
	<div class="container">
		<div class="faq-grid" style="margin-top:8px;">
			<div class="faq-card">
				<h4>What is your success rate?</h4>
				<p>We maintain a 99% pass rate in WAEC/NECO examinations and our highest JAMB score in 2024 was 292, with the student now studying Medicine at LAUTECH.</p>
			</div>

			<div class="faq-card">
				<h4>Do you offer online classes?</h4>
				<p>Yes, we offer both in-person and online classes to accommodate different learning preferences and schedules. Our CBT training is particularly effective online.</p>
			</div>

			<div class="faq-card">
				<h4>How can I register for a program?</h4>
				<p>You can register by visiting our offices, calling 0807 208 8794, or filling out our online registration form. We also offer consultation to help you choose the right program.</p>
			</div>

			<div class="faq-card">
				<h4>What makes High Q Academy different?</h4>
				<p>Our experienced tutors, proven track record, personalized attention, and comprehensive approach to both academic and digital skills development set us apart.</p>
			</div>
		</div>
	</div>
</section>

<!-- Dark CTA -->
<section class="site-cta dark-cta">
	<div class="container">
		<h2>Ready to Start Your Success Journey?</h2>
		<p>Don't wait any longer. Contact us today and take the first step towards achieving your academic goals.</p>

		<div class="cta-actions">
			<a class="cta-call" href="tel:+2348072088794"><i class="bx bx-phone"></i>&nbsp; Call Now: 0807 208 8794</a>
			<a class="cta-visit" href="about.php"><i class="bx bx-map"></i>&nbsp; Visit Our Center</a>
		</div>
	</div>
</section>


<!-- Calendar modal for scheduling visits -->
<div class="modal-backdrop" id="modalBackdrop" role="dialog" aria-hidden="true">
	<div class="modal" role="document" aria-modal="true">
		<h3>Schedule a Visit</h3>
		<div class="field">
			<label>Date</label>
			<input type="date" id="visit_date">
		</div>
		<div class="field">
			<label>Preferred Time</label>
			<select id="visit_time">
				<option>09:00 AM</option>
				<option>10:00 AM</option>
				<option>11:00 AM</option>
				<option>01:00 PM</option>
				<option>02:00 PM</option>
				<option>03:00 PM</option>
			</select>
		</div>
		<div class="actions">
			<button class="btn-ghost" id="cancelSchedule">Cancel</button>
			<button class="btn-primary" id="confirmSchedule">Confirm</button>
		</div>
	</div>
</div>

<!-- Mini chat panel -->
<div class="mini-chat" id="miniChat" aria-hidden="true">
	<div class="mini-header">
		<div>Live Chat</div>
		<button id="closeMini" aria-label="Close chat" class="btn-ghost">✕</button>
	</div>
	<div class="mini-body" id="miniBody">
		<div style="color:#666;font-size:13px">Start a conversation — our admin will reply shortly.</div>
	</div>
	<div class="mini-footer">
		<input type="text" id="miniName" placeholder="Your name" style="flex:1;padding:8px;border:1px solid #eee;border-radius:6px">
		<button class="btn-primary" id="miniSend">Send</button>
	</div>
</div>

<script>
// If user came via the floating chat link (contact.php#livechat), focus the message field and scroll into view
document.addEventListener('DOMContentLoaded', function(){
	try{
				if(window.location.hash === '#livechat'){
						var ta = document.getElementById('contact_message');
						if(ta){ ta.focus(); ta.scrollIntoView({behavior:'smooth', block:'center'}); }
						// also open mini chat if present
						var mc = document.getElementById('miniChat'); if(mc) mc.classList.add('open');
				}
	}catch(e){/* ignore */}
});
</script>

<script>
// Quick Actions: modal calendar and mini chat
(function(){
	function setCookie(name,value,days){ var d=new Date(); d.setTime(d.getTime()+(days*24*60*60*1000)); document.cookie = name+"="+encodeURIComponent(value)+";path=/;expires="+d.toUTCString(); }
	function getCookie(name){ var m=document.cookie.match(new RegExp('(^| )'+name+'=([^;]+)')); return m? decodeURIComponent(m[2]) : null; }

	var openSchedule = document.getElementById('openSchedule');
	var modal = document.getElementById('modalBackdrop');
	var cancel = document.getElementById('cancelSchedule');
	var confirm = document.getElementById('confirmSchedule');
	if(openSchedule && modal){ openSchedule.addEventListener('click', function(){ modal.style.display='flex'; modal.setAttribute('aria-hidden','false'); }); openSchedule.addEventListener('keypress', function(e){ if(e.key==='Enter') openSchedule.click(); }); }
	if(cancel){ cancel.addEventListener('click', function(){ modal.style.display='none'; modal.setAttribute('aria-hidden','true'); }); }
	if(confirm){ confirm.addEventListener('click', function(){ var date=document.getElementById('visit_date').value; var time=document.getElementById('visit_time').value; if(!date){ alert('Please pick a date'); return; } alert('Thanks — your visit has been requested for ' + date + ' at ' + time + '. Our team will contact you to confirm.'); modal.style.display='none'; }); }

	// Mini chat open
	var openLive = document.getElementById('openLiveChat');
	var mini = document.getElementById('miniChat');
	var closeMini = document.getElementById('closeMini');
	var miniSend = document.getElementById('miniSend');
	var miniBody = document.getElementById('miniBody');
	var miniName = document.getElementById('miniName');

	function updateFloatingBadge(){ try{ var badge = document.querySelector('.floating-chat .badge'); var thread = getCookie('hq_thread_id'); if(thread){ if(!badge){ var n=document.createElement('span'); n.className='badge'; n.textContent='1'; document.querySelector('.floating-chat').appendChild(n); } } else { if(badge) badge.remove(); } }catch(e){}
	updateFloatingBadge();

	if(openLive && mini){ openLive.addEventListener('click', function(){ mini.classList.add('open'); mini.setAttribute('aria-hidden','false'); document.getElementById('miniName').focus(); }); openLive.addEventListener('keypress', function(e){ if(e.key==='Enter') openLive.click(); }); }
	if(closeMini && mini){ closeMini.addEventListener('click', function(){ mini.classList.remove('open'); mini.setAttribute('aria-hidden','true'); }); }

	// Send mini-chat message: create thread via API and store thread_id cookie
	if(miniSend){ miniSend.addEventListener('click', async function(){ var name = miniName.value.trim() || 'Guest'; var msg = document.getElementById('contact_message') ? document.getElementById('contact_message').value.trim() : ''; // fallback to main message
		if(!msg){ msg = prompt('Message:'); if(!msg) return; }
		try{
			var fd = new FormData(); fd.append('name', name); fd.append('email', ''); fd.append('message', msg);
			var res = await fetch('/HIGH-Q/public/api/chat.php?action=send_message', { method: 'POST', body: fd });
			var j = await res.json(); if(j.status==='ok'){ setCookie('hq_thread_id', j.thread_id, 7); updateFloatingBadge(); miniBody.innerHTML = '<div style="color:#0a0;font-size:13px">Message sent. An admin will reply soon.</div>'; miniName.value = ''; // clear
				// append to main contact textarea if present
				var ta = document.getElementById('contact_message'); if(ta) ta.value = '';
			} else { alert('Failed to send.'); }
		}catch(e){ alert('Error sending message'); }
	}); }

	// clicking floating chat also opens contact page or mini chat
	var floatBtn = document.querySelector('.floating-chat');
	if(floatBtn){ floatBtn.addEventListener('click', function(e){ e.preventDefault(); // open contact in same tab, but with hash to open mini chat
		location.href = 'contact.php#livechat'; }); }

})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
