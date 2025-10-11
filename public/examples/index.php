<?php
// Simple demo page to preview HQ animations
$pageTitle = 'HQ Animations Demo';
include __DIR__ . '/../includes/header.php';
?>
<main class="container py-5">
  <h1 class="mb-4">HQ Animations — Demo</h1>

  <section class="mb-5">
    <h2>Glass cards (stagger + slide-in)</h2>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card hq-glass hq-slide-in hq-up hq-tilt hq-hover-glow hq-delay-1">
          <div class="card-body hq-stagger">
            <h5 class="card-title">Card One</h5>
            <p class="card-text">Example card with glassmorphism and staggered content.</p>
            <a class="btn hq-cta hq-neon" href="#">Action</a>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card hq-glass hq-slide-in hq-up hq-tilt hq-hover-glow hq-delay-2">
          <div class="card-body hq-stagger">
            <h5 class="card-title">Card Two</h5>
            <p class="card-text">Another demo card.</p>
            <a class="btn hq-cta" href="#">Action</a>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card hq-glass hq-slide-in hq-up hq-tilt hq-hover-glow hq-delay-3">
          <div class="card-body hq-stagger">
            <h5 class="card-title">Card Three</h5>
            <p class="card-text">A third card to showcase stagger.</p>
            <a class="btn hq-cta" href="#">Action</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="mb-5">
    <h2>Hero CTA</h2>
    <div class="py-4 text-center">
      <a class="hq-cta hq-shimmer hq-neon hq-delay-2">Try it now</a>
    </div>
  </section>

  <section class="mb-5">
    <h2>Reveal left/right</h2>
    <div class="row">
      <div class="col-md-6">
        <div class="p-4 hq-reveal-left">Left reveal content — scroll to reveal</div>
      </div>
      <div class="col-md-6">
        <div class="p-4 hq-reveal-right">Right reveal content — scroll to reveal</div>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . './includes/footer.php'; ?>
