<?php
$pageTitle = 'Exams - HIGH Q';
require_once __DIR__ . '/includes/header.php';
?>
<section class="py-5">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-lg-8 text-center">
        <h1 class="display-4 fw-bold mb-3">Exams</h1>
        <p class="lead text-muted mb-5">Upcoming exams and resources</p>
        
        <div class="card border-0 bg-gradient shadow-lg mx-auto" style="max-width: 600px; background: linear-gradient(90deg, #ffd600, #ffb347);">
          <div class="card-body p-5">
            <h2 class="h3 fw-bold text-white mb-3">Coming Soon</h2>
            <p class="text-white mb-4 opacity-90">We're preparing exam materials and resources. Check back soon.</p>
            <div class="pulse bg-white rounded-circle mx-auto" style="width: 14px; height: 14px; animation: pulse 1.6s infinite;"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<style>
@keyframes pulse { 
  0% { transform: scale(1); opacity: 1 } 
  50% { transform: scale(1.6); opacity: .5 } 
  100% { transform: scale(1); opacity: 1 } 
}
</style>
<?php require_once __DIR__ . '/includes/footer.php';
