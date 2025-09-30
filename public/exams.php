<?php
$pageTitle = 'Exams - HIGH Q';
require_once __DIR__ . '/includes/header.php';
?>
<section style="padding:80px 0;text-align:center;">
  <div class="container">
    <h1>Exams</h1>
    <p class="muted">Upcoming exams and resources</p>
    <div style="margin-top:40px;">
      <div class="coming-soon" style="display:inline-block;padding:40px 60px;background:linear-gradient(90deg,#ffd600,#ffb347);border-radius:12px;box-shadow:0 12px 40px rgba(0,0,0,0.12);">
        <h2 style="margin:0 0 10px;">Coming Soon</h2>
        <p style="margin:0 0 14px;">We're preparing exam materials and resources. Check back soon.</p>
        <div class="pulse" style="width:14px;height:14px;border-radius:50%;background:#fff;margin:0 auto;animation:pulse 1.6s infinite;"></div>
      </div>
    </div>
  </div>
</section>
<style>
@keyframes pulse { 0% { transform: scale(1); opacity: 1 } 50% { transform: scale(1.6); opacity: .5 } 100% { transform: scale(1); opacity: 1 } }
</style>
<?php require_once __DIR__ . '/includes/footer.php';
