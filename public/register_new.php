<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/functions.php';
$csrf = generateToken('registration_form');

// Get registration type from query param, default to regular
$registrationType = $_GET['type'] ?? 'regular';

// Fixed fees for POST UTME
$post_utme_form_fee = 1000;  // ₦1,000 compulsory form fee
$post_utme_tutor_fee = 8000; // ₦8,000 optional tutorial fee

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - HIGH Q SOLID ACADEMY</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="./assets/css/public.css">
    <?php
    require_once __DIR__ . '/config/db.php';
    require_once __DIR__ . '/config/csrf.php';
    require_once __DIR__ . '/config/functions.php';
    $csrf = generateToken('registration_form');

    // default fees
    $post_utme_form_fee = 1000;
    $post_utme_tutor_fee = 8000;

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Register — HIGH Q</title>
        <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
        <link rel="stylesheet" href="./assets/css/public.css">
    </head>
    <body>
    <?php include __DIR__ . '/includes/header.php'; ?>

    <div class="container" style="max-width:980px;margin:28px auto;">
        <div class="registration-toggle" style="margin-bottom:18px;">
            <button class="toggle-pill <?= ($_GET['type'] ?? '') !== 'post-utme' ? 'active-toggle' : '' ?>" data-target="#regularForm">Regular Registration</button>
            <button class="toggle-pill <?= ($_GET['type'] ?? '') === 'post-utme' ? 'active-toggle' : '' ?>" data-target="#postUtmeForm">POST UTME Registration</button>
        </div>

        <div id="regularForm" class="form-section <?= ($_GET['type'] ?? '') !== 'post-utme' ? 'active' : '' ?>">
            <div class="card">
                <h3>Regular Registration</h3>
                <p class="card-desc">Use the regular registration form on the main registration page.</p>
                <p><a href="./register.php">Open full registration page</a></p>
            </div>
        </div>

        <?php
        // Include the POST-UTME form include — variables passed for the include to use
        $post_registration_action = './api/register_post_utme.php';
        $post_utme_form_fee = $post_utme_form_fee;
        $post_utme_tutor_fee = $post_utme_tutor_fee;
        $post_csrf = $csrf;
        include __DIR__ . '/includes/post_utme_form.php';
        ?>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script>
    // Simple toggle to show/hide sections without navigation
    document.addEventListener('DOMContentLoaded', function(){
        document.querySelectorAll('.toggle-pill').forEach(btn => {
            btn.addEventListener('click', function(){
                document.querySelectorAll('.toggle-pill').forEach(b => b.classList.remove('active-toggle'));
                this.classList.add('active-toggle');
                const target = this.getAttribute('data-target');
                if (!target) return;
                document.querySelectorAll('.form-section').forEach(s => s.classList.remove('active'));
                const el = document.querySelector(target);
                if (el) el.classList.add('active');
                // if target contains a form, focus first input
                const f = el && el.querySelector('input,select,textarea'); if (f) f.focus();
            });
        });
    });
    </script>
    </body>
    </html>