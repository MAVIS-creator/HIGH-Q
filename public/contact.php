<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';
$siteSettings = require __DIR__ . '/../config/app.php';
$csrf = '';
include __DIR__ . '/includes/header.php';
?>

<section class="contact-hero">
  <div class="container">
    <h1>Contact <span class="highlight">Us</span></h1>
    <p>Get in touch with our team. We're here to help you start your journey towards academic excellence.</p>
  </div>
</section>

<section class="container" style="padding:36px 20px 80px;">
  <div style="max-width:900px;margin:0 auto;text-align:center;">
    <h2 style="margin-top:0;">Reach out</h2>
    <p style="color:var(--hq-gray);">Use the contact methods below or send us a message using the form.</p>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php';
