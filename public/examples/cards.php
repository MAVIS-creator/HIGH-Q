<?php
$pageTitle = 'HQ Animations Cards Example';
include __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
  <h1>Card Examples</h1>
  <div class="row g-4 mt-3">
    <?php for ($i=1;$i<=6;$i++): ?>
    <div class="col-sm-6 col-lg-4">
      <div class="card hq-glass hq-slide-in hq-up hq-tilt hq-hover-glow">
        <div class="card-body hq-stagger">
          <h5 class="card-title">Demo <?php echo $i; ?></h5>
          <p class="card-text">Short copy to show layout.</p>
          <a class="btn hq-cta" href="#">Explore</a>
        </div>
      </div>
    </div>
    <?php endfor; ?>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
