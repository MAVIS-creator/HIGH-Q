<?php include __DIR__ . '/includes/header.php'; ?>

<!-- Reuse the about-hero structure for a consistent hero on the contact page -->
<section class="about-hero">
	<div class="about-hero-overlay"></div>
	<div class="container about-hero-inner">
		<h1>Contact Us</h1>
		<p class="lead">Get in touch with our team. We're here to help you start your journey towards academic excellence.</p>
	</div>
</section>

<div class="container" style="padding:40px 0;">
	<div style="display:grid; grid-template-columns:1fr 360px; gap:28px; align-items:start;">
		<main>
			<h2>Send a message</h2>
			<p style="color:var(--hq-gray);">Use the form below to reach us and we will get back within 24 hours.</p>
			<!-- minimal placeholder form; you can expand as needed -->
			<form style="margin-top:18px;">
				<div class="form-row"><label>Name</label><input type="text" class="input" style="width:100%; padding:10px; border-radius:8px; border:1px solid #e6e6e6;"></div>
				<div class="form-row"><label>Email</label><input type="email" class="input" style="width:100%; padding:10px; border-radius:8px; border:1px solid #e6e6e6;"></div>
				<div class="form-row"><label>Message</label><textarea class="input" style="width:100%; padding:10px; border-radius:8px; border:1px solid #e6e6e6; min-height:120px;"></textarea></div>
				<div style="margin-top:8px;"><button class="btn" type="submit">Send Message</button></div>
			</form>
		</main>

		<aside>
			<div class="sidebar-card help-box">
				<h4>Need Help?</h4>
				<p><strong>Call Us</strong><br>0807 208 8794</p>
				<p><strong>Email Us</strong><br>info@hqacademy.com</p>
				<p><strong>Visit Us</strong><br>8 Pineapple Avenue, Aiyetoro<br>Maya, Ikorodu</p>
			</div>
		</aside>
	</div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
