<?php
// public/contact.php
// Clean contact page: single POST handler, Bootstrap form, CSRF and optional reCAPTCHA.

require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/functions.php';

// Load reCAPTCHA config if present
$recfg = file_exists(__DIR__ . '/config/recaptcha.php') ? require __DIR__ . '/config/recaptcha.php' : ['site_key' => '', 'secret' => ''];

$errors = [];
$success = '';
$first_name = $last_name = $email = $phone = $program = $message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_csrf_token'] ?? '';
    if (!verifyToken('contact_form', $token)) {
        $errors[] = 'Invalid CSRF token.';
    }

    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $program = trim($_POST['program'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!$first_name || !$email || !$message) {
        $errors[] = 'Please provide your first name, email and message.';
    }
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }

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
            // clear form values
            $first_name = $last_name = $email = $phone = $program = $message = '';
        } else {
            $errors[] = 'Failed to send your message. Please try again later.';
        }
    }
}

$csrf = generateToken('contact_form');

// Include the site header (navigation, opening HTML)
include __DIR__ . '/includes/header.php';
?>

<section class="about-hero position-relative py-4 py-md-5">
    <div class="about-hero-overlay position-absolute top-0 start-0 w-100 h-100"></div>
    <div class="container about-hero-inner position-relative text-center py-3 py-md-5">
        <h1 class="display-4 fw-bold mb-3">Contact Us</h1>
        <p class="lead mb-0 mx-auto" style="max-width: 700px;">Get in touch with our team. We're here to help you start your journey towards academic excellence.</p>
    </div>
</section>

<style>
@media (max-width: 768px) {
    .contact-card {
        padding: 1.5rem !important;
    }
    .contact-card p {
        margin-bottom: 0;
        font-size: 0.9rem;
    }
    .contact-card .card-body {
        padding: 1rem !important;
    }
    aside .card {
        margin-bottom: 1rem !important;
    }
    .contact-info > div {
        margin-bottom: 0.5rem !important;
    }
    .contact-info > div:last-child {
        margin-bottom: 0 !important;
    }
}
</style>

<div class="container py-4 py-lg-5 mb-4">
    <main class="row gy-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-body p-4 p-lg-5">
                    <h3 class="fw-bold mb-2">Send Us a <span class="text-warning">Message</span></h3>
                    <p class="text-muted mb-4">Fill out the form below and we'll get back to you within 24 hours.</p>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-warning mb-4">
                            <?php foreach ($errors as $e): ?>
                                <div><?= htmlspecialchars($e) ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success mb-4">
                            <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" class="needs-validation" novalidate>
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name</label>
                                <input class="form-control" name="first_name" placeholder="Your first name" required value="<?= htmlspecialchars($first_name ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input class="form-control" name="last_name" placeholder="Your last name" value="<?= htmlspecialchars($last_name ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" name="email" placeholder="your.email@example.com" required value="<?= htmlspecialchars($email ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input class="form-control" name="phone" placeholder="+234 XXX XXX XXXX" value="<?= htmlspecialchars($phone ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Program of Interest</label>
                            <select class="form-select" name="program">
                                <option value="">Select a program</option>
                                <?php
                                // Fetch courses if $pdo is available
                                $courses = [];
                                try {
                                    if (isset($pdo)) {
                                        $courses = $pdo->query("SELECT id, title FROM courses WHERE is_active=1 ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
                                    }
                                } catch (Throwable $e) {
                                    $courses = [];
                                }
                                foreach ($courses as $c) {
                                    $sel = (isset($program) && $program == $c['title']) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($c['title']) . '" ' . $sel . '>' . htmlspecialchars($c['title']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Message</label>
                            <textarea class="form-control" id="contact_message" name="message" rows="5" placeholder="Tell us about your educational goals and any questions you have..." required><?= htmlspecialchars($message ?? '') ?></textarea>
                        </div>

                        <div>
                            <button class="btn btn-primary px-4 py-2" type="submit"><i class="bx bx-send me-2"></i>Send Message</button>
                        </div>
                    </form>

                    <?php if (!empty($recfg['site_key'])): ?>
                        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                        <script>
                        (function(){
                            var f = document.querySelector('form'); if(!f) return;
                            var w = document.createElement('div'); w.className='g-recaptcha'; w.setAttribute('data-sitekey','<?= htmlspecialchars($recfg['site_key']) ?>'); w.style.marginTop='12px'; f.insertBefore(w, f.querySelector('button'));
                        })();
                        </script>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <aside class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bx bx-book-open fs-4 text-warning me-2"></i>
                        <h5 class="mb-0">Tutorial Center</h5>
                    </div>
                    <p class="text-muted mb-0">8 Pineapple Avenue, Aiyetoro<br>Ikorodu North LCDA,<br>Maya, Ikorodu</p>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bx bx-map fs-4 text-warning me-2"></i>
                        <h5 class="mb-0">Area Office</h5>
                    </div>
                    <p class="text-muted mb-0">Shop 18, World Star Complex<br>Opposite London Street,<br>Aiyetoro Maya, Ikorodu, Lagos State</p>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bx bx-phone fs-4 text-warning me-2"></i>
                        <h5 class="mb-0">Contact Information</h5>
                    </div>
                    <div class="contact-info">
                        <div class="mb-2">
                            <strong class="d-block mb-1">Phone</strong>
                            <p class="text-muted mb-0">0807 208 8794</p>
                        </div>
                        <div class="mb-2">
                            <strong class="d-block mb-1">Email</strong>
                            <p class="text-muted mb-0">info@hqacademy.com</p>
                        </div>
                        <div>
                            <strong class="d-block mb-1">Office Hours</strong>
                            <p class="text-muted mb-0">Mon - Fri: 8:00 AM - 6:00 PM<br>Sat: 9:00 AM - 4:00 PM</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mb-4 mb-lg-0">
                <button class="btn btn-outline-primary flex-grow-1 d-flex align-items-center justify-content-center gap-2" id="openSchedule">
                    <i class="bx bx-calendar"></i>
                    <span>Schedule Visit</span>
                </button>
                <button class="btn btn-outline-primary flex-grow-1 d-flex align-items-center justify-content-center gap-2" id="openLiveChat">
                    <i class="bx bx-chat"></i>
                    <span>Live Chat</span>
                </button>
            </div>
        </aside>
    </main>
</div>

<!-- FAQ and CTA sections kept simple to avoid duplication; they can be extracted to partials later -->
<section class="py-4 py-lg-5 bg-light mb-4 mb-lg-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">Frequently Asked <span class="text-warning">Questions</span></h2>
            <p class="lead text-muted mb-0">Find answers to common questions about our programs and services.</p>
        </div>

        <div class="row gy-4 mb-4">
            <div class="col-12 col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="fw-bold mb-3">What programs do you offer?</h4>
                        <p class="text-muted mb-0">We offer comprehensive JAMB/Post-UTME preparation, WAEC/NECO preparation, digital skills training, CBT preparation, tutorial classes, and educational consultancy services.</p>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="fw-bold mb-3">How much do your programs cost?</h4>
                        <p class="text-muted mb-0">Program fees vary based on duration and type. Contact us for detailed pricing.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Small JS for schedule modal and chat iframe modal -->
<script>
document.addEventListener('DOMContentLoaded', function(){
    var openSchedule = document.getElementById('openSchedule');
    var modal = document.getElementById('modalBackdrop');
    var cancel = document.getElementById('cancelSchedule');
    var confirm = document.getElementById('confirmSchedule');
    if(openSchedule && modal){ openSchedule.addEventListener('click', function(){ modal.classList.add('open'); modal.setAttribute('aria-hidden','false'); }); }
    if(cancel){ cancel.addEventListener('click', function(){ modal.classList.remove('open'); modal.setAttribute('aria-hidden','true'); }); }
    if(confirm){ confirm.addEventListener('click', function(){ var date=document.getElementById('visit_date').value; var time=document.getElementById('visit_time').value; if(!date){ alert('Please pick a date'); return; } alert('Thanks — your visit has been requested for ' + date + ' at ' + time + '. Our team will contact you to confirm.'); modal.classList.remove('open'); }); }

    var openLive = document.getElementById('openLiveChat');
    if(openLive){ openLive.addEventListener('click', function(){ var chatModal = document.getElementById('chatIframeModal'); if(chatModal){ chatModal.style.display = 'block'; chatModal.setAttribute('aria-hidden','false'); } }); }
});
</script>

<?php include __DIR__ . '/includes/footer.php';

