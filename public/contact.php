<?php
// public/contact.php - contact form that emails using sendEmail()
// SEO Meta Tags
$pageTitle = 'Contact Us | Get in Touch with High Q Tutorial';
$pageDescription = 'Contact High Q Tutorial for inquiries about JAMB, WAEC, Post-UTME registration and exam preparation. We\'re here to help you succeed.';
$pageKeywords = 'contact High Q, exam inquiries, tutorial support, registration help Nigeria';

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/functions.php';
// recaptcha config
$recfg = file_exists(__DIR__ . '/config/recaptcha.php') ? require __DIR__ . '/config/recaptcha.php' : ['site_key'=>'','secret'=>''];

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
			// Temporarily disabled reCAPTCHA
			// if (!empty($recfg['secret'])) {
			//     $rc = $_POST['g-recaptcha-response'] ?? '';
			//     if (!$rc) { $errors[] = 'Please complete the I am not a robot check.'; }
			//     // ... recaptcha verification code ...
			// }
				$to = 'highqsolidacademy@gmail.com';
				$subject = 'Website Contact: ' . ($program ? $program : 'General Inquiry');

				// Styled HTML email for better readability
				$fullName = htmlspecialchars(trim($first_name . ' ' . $last_name));
				$safeEmail = htmlspecialchars($email);
				$safePhone = htmlspecialchars($phone);
				$safeProgram = htmlspecialchars($program ?: 'Not specified');
				$safeMessage = nl2br(htmlspecialchars($message));

				$html = <<<HTML
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#f5f7fb;padding:24px;font-family:'Segoe UI',Arial,sans-serif;">
	<tr>
		<td align="center">
			<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="640" style="background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;box-shadow:0 10px 30px rgba(0,0,0,0.06);">
				<tr>
					<td style="background:linear-gradient(135deg,#0f172a,#1e293b);padding:18px 24px;color:#f8fafc;">
						<div style="font-size:18px;font-weight:700;">High Q Solid Academy</div>
						<div style="font-size:14px;color:#cbd5e1;margin-top:4px;">New website contact message</div>
					</td>
				</tr>
				<tr>
					<td style="padding:24px 24px 12px 24px;color:#0f172a;">
						<div style="font-size:18px;font-weight:700;margin-bottom:4px;">Contact Form Submission</div>
						<div style="font-size:14px;color:#475569;">Submitted via highqsolidacademy.com</div>
					</td>
				</tr>
				<tr>
					<td style="padding:0 24px 24px 24px;">
						<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
							<tr style="background:#f8fafc;">
								<td style="padding:12px 16px;font-size:14px;color:#475569;width:32%;">Name</td>
								<td style="padding:12px 16px;font-size:15px;font-weight:600;color:#0f172a;">{$fullName}</td>
							</tr>
							<tr>
								<td style="padding:12px 16px;font-size:14px;color:#475569;width:32%;">Email</td>
								<td style="padding:12px 16px;font-size:15px;font-weight:600;color:#0f172a;">{$safeEmail}</td>
							</tr>
							<tr style="background:#f8fafc;">
								<td style="padding:12px 16px;font-size:14px;color:#475569;width:32%;">Phone</td>
								<td style="padding:12px 16px;font-size:15px;font-weight:600;color:#0f172a;">{$safePhone}</td>
							</tr>
							<tr>
								<td style="padding:12px 16px;font-size:14px;color:#475569;width:32%;">Program of Interest</td>
								<td style="padding:12px 16px;font-size:15px;font-weight:600;color:#0f172a;">{$safeProgram}</td>
							</tr>
						</table>

						<div style="margin-top:16px;padding:16px;border:1px solid #e5e7eb;border-radius:10px;background:#0f172a;color:#e2e8f0;">
							<div style="font-size:14px;letter-spacing:0.02em;text-transform:uppercase;color:#a5b4fc;margin-bottom:6px;">Message</div>
							<div style="font-size:15px;line-height:1.6;">{$safeMessage}</div>
						</div>

						<div style="margin-top:16px;font-size:13px;color:#94a3b8;">This email was sent automatically from the website contact form.</div>
					</td>
				</tr>
				<tr>
					<td style="background:#f8fafc;padding:14px 24px;color:#475569;font-size:13px;text-align:center;">
						High Q Solid Academy &bull; Always Ahead of Others
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
HTML;

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
				<script>
				document.addEventListener('DOMContentLoaded', function(){
					try {
						var html = <?= json_encode(implode('<br>', array_map('htmlspecialchars', $errors))) ?>;
						if (typeof Swal !== 'undefined') {
							Swal.fire({ icon: 'error', title: 'Please fix these issues', html: html, confirmButtonText: 'OK', customClass: { popup: 'hq-swal' } });
						} else { alert(html.replace(/<br\s*\/?\>/g,'\n')); }
					} catch(e){ console.error(e); }
				});
				</script>
			<?php endif; ?>
			<?php if ($success): ?>
				<script>
				document.addEventListener('DOMContentLoaded', function(){
					try {
						var html = <?= json_encode($success) ?>;
						if (typeof Swal !== 'undefined') {
							Swal.fire({ icon: 'success', title: 'Message sent', html: html, confirmButtonText: 'OK', customClass: { popup: 'hq-swal' } });
						} else { alert(html); }
					} catch(e){ console.error(e); }
				});
				</script>
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
				<?php if (!empty($recfg['site_key'])): ?>
					<script src="https://www.google.com/recaptcha/api.js" async defer></script>
					<script>
						(function(){
							var f = document.querySelector('form'); if(!f) return;
							var w = document.createElement('div'); w.className='g-recaptcha'; w.setAttribute('data-sitekey','<?= htmlspecialchars($recfg['site_key']) ?>'); w.style.marginTop='12px';
							// Try to insert before the button's parent div if possible
							var btn = f.querySelector('button');
							if (btn && btn.parentNode && btn.parentNode.parentNode === f) {
								f.insertBefore(w, btn.parentNode);
							} else {
								f.appendChild(w);
							}
						})();
					</script>
				<?php endif; ?>
		</div>
	</main>

	<aside class="register-sidebar hq-aside-target">
				<div class="sidebar-card" data-icon="tutor">
					<div class="card-icon"><i class="bx bx-book-open" style="font-size:28px;color:var(--hq-yellow);"></i></div>
					<h4>Tutorial Center</h4>
					<p class="sidebar-text">8 Pineapple Avenue, Aiyetoro<br>Ikorodu North LCDA,<br>Maya, Ikorodu</p>
				</div>

				<div class="sidebar-card" data-icon="office">
					<div class="card-icon"><i class="bx bx-map" style="font-size:28px;color:var(--hq-yellow);"></i></div>
					<h4>Area Office</h4>
					<p class="sidebar-text">Shop 18, World Star Complex<br>Opposite London Street,<br>Aiyetoro Maya, Ikorodu, Lagos State</p>
				</div>

				<div class="sidebar-card" data-icon="contact">
					<div class="card-icon"><i class="bx bx-phone" style="font-size:28px;color:var(--hq-yellow);"></i></div>
					<h4>Contact Information</h4>
					<p class="sidebar-text"><strong>Phone</strong><br>0807 208 8794</p>
					<p class="sidebar-text"><strong>Email</strong><br>info@hqacademy.com</p>
					<p class="sidebar-text"><strong>Office Hours</strong><br>Mon - Fri: 8:00 AM - 6:00 PM<br>Sat: 9:00 AM - 4:00 PM</p>
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
				<p>Program and tuition fees vary by track and duration. Academic prep (WAEC/NECO/JAMB) typically ranges ₦25,000-₦40,000 per cycle, while tech/digital skills and professional tracks are priced by module. Contact us for a full fee sheet or payment plans.</p>
			</div>
		</div>
	</div>
</section>

<section class="faq-section alt">
	<div class="container">
		<div class="faq-grid" style="margin-top:8px;">
			<div class="faq-card">
				<h4>What is your success rate?</h4>
				<p>We maintain a 99% pass rate in WAEC/NECO, a 95% JAMB/Post-UTME success rate, and a 75% placement rate for tech/digital skills graduates. Our top JAMB score was 305 and alumni study at LAUTECH and other leading universities.</p>
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
		<button class="modal-close" id="modalClose" aria-label="Close">&times;</button>
		<h3>Schedule a Visit</h3>
		<p style="color:#666;font-size:14px;margin-bottom:20px;">Fill out the form below and we'll contact you to confirm your appointment.</p>
		
		<form id="scheduleForm">
			<input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf) ?>">
			
			<div class="field">
				<label>Your Name *</label>
				<input type="text" id="visit_name" name="name" placeholder="Enter your full name" required>
			</div>
			
			<div class="field">
				<label>Email Address *</label>
				<input type="email" id="visit_email" name="email" placeholder="your.email@example.com" required>
			</div>
			
			<div class="field">
				<label>Phone Number</label>
				<input type="tel" id="visit_phone" name="phone" placeholder="+234 XXX XXX XXXX">
			</div>
			
			<div class="field">
				<label>Preferred Date *</label>
				<input type="date" id="visit_date" name="visit_date" required min="<?= date('Y-m-d') ?>">
			</div>
			
			<div class="field">
				<label>Preferred Time *</label>
				<select id="visit_time" name="visit_time" required>
					<option value="">Select a time</option>
					<option value="09:00 AM">09:00 AM</option>
					<option value="10:00 AM">10:00 AM</option>
					<option value="11:00 AM">11:00 AM</option>
					<option value="12:00 PM">12:00 PM</option>
					<option value="01:00 PM">01:00 PM</option>
					<option value="02:00 PM">02:00 PM</option>
					<option value="03:00 PM">03:00 PM</option>
					<option value="04:00 PM">04:00 PM</option>
					<option value="05:00 PM">05:00 PM</option>
				</select>
			</div>
			
			<div class="field">
				<label>Additional Message (Optional)</label>
				<textarea id="visit_message" name="message" placeholder="Let us know what you'd like to discuss..." rows="3"></textarea>
			</div>
			
			<div class="actions">
				<button type="button" class="btn-ghost" id="cancelSchedule">Cancel</button>
				<button type="submit" class="btn-primary" id="confirmSchedule">
					<i class="bx bx-calendar-check"></i> Schedule Visit
				</button>
			</div>
		</form>
	</div>
</div>

<!-- Chat widget positioned bottom-right near floating button (no overlay) -->
<div id="chatIframeModal" style="display:none;position:fixed;bottom:90px;right:20px;z-index:9998;width:400px;max-width:calc(100vw - 40px);height:600px;max-height:calc(100vh - 110px);box-shadow:0 12px 48px rgba(0,0,0,0.25);border-radius:16px;overflow:hidden;background:#fff;">
	<div style="width:100%;height:100%;position:relative;">
		<button id="closeChatModal" aria-label="Close chat" style="position:absolute;right:8px;top:8px;border:none;background:rgba(255,255,255,0.95);padding:8px 10px;border-radius:50%;cursor:pointer;z-index:3;box-shadow:0 4px 12px rgba(0,0,0,0.15);font-size:18px;line-height:1;width:32px;height:32px;display:flex;align-items:center;justify-content:center;font-weight:bold;color:#333;"><i class="bx bx-x"></i></button>
		<iframe id="chatIframe" src="chatbox.php" style="width:100%;height:100%;border:0;display:block;" title="Live Chat"></iframe>
	</div>
</div>

<style>
/* Mobile chat positioning */
@media (max-width: 700px) {
	#chatIframeModal {
		bottom: 75px !important;
		right: 12px !important;
		width: calc(100vw - 24px) !important;
		height: calc(100vh - 95px) !important;
		max-height: 550px !important;
	}
}
</style>

<!-- Inline mini chat removed to avoid duplicate chat widget; iframe modal remains -->

<script>
// If user came via the floating chat link (contact.php#livechat), focus the message field and scroll into view
document.addEventListener('DOMContentLoaded', function(){
	try{
				if(window.location.hash === '#livechat'){
						var ta = document.getElementById('contact_message');
						if(ta){ ta.focus(); ta.scrollIntoView({behavior:'smooth', block:'center'}); }
						// iframe modal will be used for live chat
				}
	}catch(e){/* ignore */}
});
</script>

<script>
// Quick Actions: modal calendar and mini chat
document.addEventListener('DOMContentLoaded', function(){
	function setCookie(name,value,days){ var d=new Date(); d.setTime(d.getTime()+(days*24*60*60*1000)); document.cookie = name+"="+encodeURIComponent(value)+";path=/;expires="+d.toUTCString(); }
	function getCookie(name){ var m=document.cookie.match(new RegExp('(^| )'+name+'=([^;]+)')); return m? decodeURIComponent(m[2]) : null; }

	var openSchedule = document.getElementById('openSchedule');
	var modal = document.getElementById('modalBackdrop');
	var modalClose = document.getElementById('modalClose');
	var cancel = document.getElementById('cancelSchedule');
	var scheduleForm = document.getElementById('scheduleForm');
	
	// Open modal
	if(openSchedule && modal){ 
		openSchedule.addEventListener('click', function(){ 
			modal.classList.add('open'); 
			modal.setAttribute('aria-hidden','false');
			// Prevent body scroll when modal is open
			document.body.style.overflow = 'hidden';
		}); 
		openSchedule.addEventListener('keypress', function(e){ 
			if(e.key==='Enter') openSchedule.click(); 
		}); 
	}
	
	// Close modal functions
	function closeModal() {
		if (modal) {
			modal.classList.remove('open'); 
			modal.setAttribute('aria-hidden','true');
			// Restore body scroll
			document.body.style.overflow = '';
			// Reset form
			if (scheduleForm) scheduleForm.reset();
		}
	}
	
	if(cancel) cancel.addEventListener('click', closeModal);
	if(modalClose) modalClose.addEventListener('click', closeModal);
	
	// Close on backdrop click
	if(modal) {
		modal.addEventListener('click', function(e) {
			if (e.target === modal) closeModal();
		});
	}
	
	// Handle form submission
	if(scheduleForm) {
		scheduleForm.addEventListener('submit', function(e) {
			e.preventDefault();
			
			var submitBtn = document.getElementById('confirmSchedule');
			var originalBtnText = submitBtn.innerHTML;
			submitBtn.disabled = true;
			submitBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Scheduling...';
			
			// Get form data
			var formData = {
				_csrf_token: document.querySelector('input[name="_csrf_token"]').value,
				name: document.getElementById('visit_name').value,
				email: document.getElementById('visit_email').value,
				phone: document.getElementById('visit_phone').value,
				visit_date: document.getElementById('visit_date').value,
				visit_time: document.getElementById('visit_time').value,
				message: document.getElementById('visit_message').value
			};
			
			// Get API base URL
			var apiBase = window.HQ_APP_BASE || '';
			if (apiBase && apiBase.slice(-1) === '/') apiBase = apiBase.slice(0, -1);
			
			fetch((apiBase ? apiBase + '/api/schedule_appointment.php' : 'api/schedule_appointment.php'), {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify(formData)
			})
			.then(function(response) { return response.json(); })
			.then(function(data) {
				submitBtn.disabled = false;
				submitBtn.innerHTML = originalBtnText;
				
				if (data.success) {
					closeModal();
					
					var message = data.message;
					var calendarData = data.calendar_data;
					
					if (typeof Swal !== 'undefined') {
						Swal.fire({
							icon: 'success',
							title: 'Appointment Requested!',
							html: message + '<br><br><small>Would you like to add this to your calendar?</small>',
							showCancelButton: true,
							confirmButtonText: 'Add to Calendar',
							cancelButtonText: 'Close',
							customClass: { popup: 'hq-swal' }
						}).then(function(result) {
							if (result.isConfirmed && calendarData) {
								// Create calendar event
								addToCalendar(calendarData);
							}
						});
					} else {
						alert(message);
					}
				} else {
					if (typeof Swal !== 'undefined') {
						Swal.fire({
							icon: 'error',
							title: 'Error',
							text: data.message,
							confirmButtonText: 'OK',
							customClass: { popup: 'hq-swal' }
						});
					} else {
						alert('Error: ' + data.message);
					}
				}
			})
			.catch(function(error) {
				submitBtn.disabled = false;
				submitBtn.innerHTML = originalBtnText;
				
				if (typeof Swal !== 'undefined') {
					Swal.fire({
						icon: 'error',
						title: 'Connection Error',
						text: 'Failed to schedule appointment. Please try again or call us at 0807 208 8794',
						confirmButtonText: 'OK',
						customClass: { popup: 'hq-swal' }
					});
				} else {
					alert('Failed to schedule appointment. Please try again.');
				}
			});
		});
	}
	
	// Function to add event to calendar
	function addToCalendar(data) {
		var startDateTime = new Date(data.start_date + ' ' + data.start_time);
		var endDateTime = new Date(startDateTime.getTime() + (60 * 60 * 1000)); // Add 1 hour
		
		// Format dates for iCal
		function formatICalDate(date) {
			return date.toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z';
		}
		
		var icsContent = [
			'BEGIN:VCALENDAR',
			'VERSION:2.0',
			'PRODID:-//High Q Academy//Appointment//EN',
			'BEGIN:VEVENT',
			'UID:' + Date.now() + '@hqacademy.com',
			'DTSTAMP:' + formatICalDate(new Date()),
			'DTSTART:' + formatICalDate(startDateTime),
			'DTEND:' + formatICalDate(endDateTime),
			'SUMMARY:' + data.title,
			'DESCRIPTION:' + data.description,
			'LOCATION:' + data.location,
			'STATUS:CONFIRMED',
			'BEGIN:VALARM',
			'TRIGGER:-PT30M',
			'ACTION:DISPLAY',
			'DESCRIPTION:Reminder: Visit to High Q Academy in 30 minutes',
			'END:VALARM',
			'END:VEVENT',
			'END:VCALENDAR'
		].join('\r\n');
		
		// Create download link
		var blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8' });
		var link = document.createElement('a');
		link.href = window.URL.createObjectURL(blob);
		link.download = 'high-q-academy-visit.ics';
		document.body.appendChild(link);
		link.click();
		document.body.removeChild(link);
	}

	// Live Chat: open iframe modal only. Keep cookie helpers and badge update.
	var openLive = document.getElementById('openLiveChat');

	function updateFloatingBadge(){ try{ var badge = document.querySelector('.floating-chat .badge'); var thread = getCookie('hq_thread_id'); if(thread){ if(!badge){ var n=document.createElement('span'); n.className='badge'; n.textContent='1'; var fc = document.querySelector('.floating-chat'); if(fc) fc.appendChild(n); } } else { if(badge) badge.remove(); } }catch(e){}
	}

	// call once on load to sync badge state
	updateFloatingBadge();

	// if there's an existing thread for this visitor, keep polling in background (uses iframe API when modal open)
	try{ var existingThread = getCookie('hq_thread_id'); if(existingThread){ /* polling may be handled by iframe/chatbox */ } }catch(e){}

	function openChatModal() {
		var chatModal = document.getElementById('chatIframeModal');
		if(chatModal){
			chatModal.style.display = 'block';
			chatModal.setAttribute('aria-hidden','false');
			var iframe = document.getElementById('chatIframe'); 
			if(iframe && iframe.contentWindow) iframe.contentWindow.postMessage({hq_chat_action:'focus'}, '*');
		}
	}

	if(openLive){
		openLive.addEventListener('click', openChatModal);
		openLive.addEventListener('keypress', function(e){ if(e.key==='Enter') openLive.click(); });
	}

	// Auto-open chat modal if page loaded with #livechat hash
	if(window.location.hash === '#livechat') {
		openChatModal();
	}

	// chat iframe modal close button
	var closeChatModal = document.getElementById('closeChatModal');
	if(closeChatModal){ 
		closeChatModal.addEventListener('click', function(){ 
			var chatModal = document.getElementById('chatIframeModal'); 
			if(chatModal){ 
				chatModal.style.display='none'; 
				chatModal.setAttribute('aria-hidden','true'); 
				var iframe = document.getElementById('chatIframe'); 
				if(iframe && iframe.contentWindow) iframe.contentWindow.postMessage({hq_chat_action:'close'}, '*'); 
			} 
		}); 
	}

	// listen for messages from iframe (chatbox) to allow it to request close
	window.addEventListener('message', function(ev){ try{ if(ev.data && ev.data.hq_chat_action === 'close'){ var m = document.getElementById('chatIframeModal'); if(m) m.style.display='none'; } }catch(e){} });

	// clear chat form placeholder removed with mini chat; no-op kept to avoid errors

	// clicking floating chat also opens contact page or mini chat
	var floatBtn = document.querySelector('.floating-chat');
	if(floatBtn){ floatBtn.addEventListener('click', function(e){ e.preventDefault(); // open contact in same tab, but with hash to open mini chat
		var base = (window.HQ_APP_BASE || '');
		// Ensure base is empty or does not end with '/'
		try { if (base && base.slice(-1) === '/') base = base.slice(0, -1); } catch(e){}
		location.href = (base ? (base + '/contact.php#livechat') : 'contact.php#livechat'); }); }

});
</script>

<!-- contact helpers moved to public/assets/js/contact-helpers.js -->

<?php include __DIR__ . '/includes/footer.php'; ?>
