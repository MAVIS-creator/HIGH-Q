<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/functions.php';

$csrf = generateToken('registration_form');

// Fixed fees for POST UTME
$post_utme_form_fee = 1000;  // ₦1,000 compulsory form fee
$post_utme_tutor_fee = 8000; // ₦8,000 optional tutorial fee
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<!-- Hero Section -->
<section class="about-hero">
	<div class="about-hero-overlay"></div>
	<div class="container about-hero-inner">
		<h1>POST UTME Registration</h1>
		<p class="lead">Register for our comprehensive POST UTME preparation program</p>
	</div>
</section>

<!-- Registration Type Toggle -->
<div class="registration-toggle">
    <button class="toggle-btn" onclick="window.location.href='register.php?type=regular'">
        Regular Registration
    </button>
    <button class="toggle-btn active">
        POST UTME Registration
    </button>
</div>

<style>
    .registration-toggle {
        display: flex;
        justify-content: center;
        gap: 16px;
        margin: 32px auto;
        max-width: 500px;
        padding: 0 20px;
    }

    .toggle-btn {
        flex: 1;
        padding: 12px 24px;
        border: 2px solid var(--hq-primary);
        background: none;
        color: var(--hq-primary);
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s;
        font-size: 15px;
        white-space: nowrap;
    }

    .toggle-btn.active {
        background: var(--hq-primary);
        color: white;
    }

    .toggle-btn:hover:not(.active) {
        background: rgba(0, 102, 255, 0.1);
    }

    @media (max-width: 576px) {
        .registration-toggle {
            flex-direction: column;
            gap: 8px;
        }
    }

    /* Additional styles specific to POST UTME form */
    [Rest of the POST UTME specific styles from register_new.php]
</style>

<main class="public-main">
    <div class="container">
        <div class="card">
            [Rest of the POST UTME form content from register_new.php]
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
