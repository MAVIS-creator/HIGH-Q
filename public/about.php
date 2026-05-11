<?php
// SEO Meta Tags
$pageTitle = 'About Us | Leading Educational Excellence Since 2018';
$pageDescription = 'High Q Tutorial - Nigeria\'s premier exam coaching center. Expert tutors, proven results, comprehensive JAMB, WAEC, and Post-UTME preparation.';
$pageKeywords = 'about High Q Tutorial, exam coaching Nigeria, JAMB tutors, educational excellence';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<style>
  /* Mobile-only: center history logo and add small gap under it without affecting desktop */
  .history-copy {
    position: relative;
    overflow: hidden;
    max-height: 25rem;
    transition: max-height 0.35s ease;
  }

  .history-copy::after {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 5.5rem;
    background: linear-gradient(180deg, rgba(255,255,255,0), #ffffff 88%);
    pointer-events: none;
    transition: opacity 0.25s ease;
  }

  .history-copy.is-expanded {
    max-height: 120rem;
  }

  .history-copy.is-expanded::after,
  .history-copy.is-static::after {
    opacity: 0;
    display: none;
  }

  .history-toggle {
    display: none;
    align-items: center;
    gap: 0.35rem;
    margin-top: 1rem;
    padding: 0;
    border: none;
    background: transparent;
    color: var(--hq-yellow);
    font-weight: 700;
    font-size: 0.98rem;
    cursor: pointer;
  }

  .history-toggle .less-text {
    display: none;
  }

  .history-toggle.expanded .read-text {
    display: none;
  }

  .history-toggle.expanded .less-text {
    display: inline;
  }

  @media (max-width: 767.98px) {
    .history-copy {
      max-height: 18rem;
    }

    .history-toggle {
      font-size: 0.95rem;
    }

    .history-logo {
      display: flex !important;
      justify-content: center !important; /* center horizontally */
      align-items: center !important;
      text-align: center;
      margin-bottom: 0.75rem; /* small gap similar to a <br> */
    }

    /* make the logo-card slightly smaller on very small screens if needed */
    .history-logo .logo-card img {
      max-width: 140px;
      height: auto;
    }
  }
</style>

<section class="about-hero">
  <div class="about-hero-overlay"></div>
  <div class="container about-hero-inner">
    <h1>About HQ Academy</h1>
    <p class="lead">Building Excellence in Education Through Dedication and Innovation</p>
  </div>
</section>

<!-- Core Values -->

<section class="history-section">
  <div class="container">
    <div class="row align-items-start">
      <div class="col-12 col-md-8 order-2 order-md-1 history-content">
      <h3>History About High Q Solid Academy</h3>
      <div class="history-copy" id="historyCopy">
        <p>High Q tutorial founded in 2018/2019 by Mr. Adebule Quam Okikiola and Mr. Adebule Ibrahim has left an enduring legacy since its inception. Named after its visionary founders, the tutorial symbolizes a commitment to education empowerment that resonates within the community.</p>

        <p>Following Mr. Ibrahim's departure for overseas opportunities, Mr. Adebule Quam assumed sole leadership, steering the tutorial toward remarkable success. Under Mr. Adebule Quam's guidance, High Q Tutorial has blossomed into a hub of academic excellence and technological proficiency.</p>

        <p>Beyond conventional tutorial work, it serves as a catalyst for holistic development. Through meticulous instruction, students have sharpened their academic prowess, achieving commendable results in examinations such as JAMB, WAEC, and NECO since 2018.</p>

        <p>High Q Tutorial’s impact transcends the classroom, enriching lives and fostering digital literacy essential for navigating the complexities of the modern world. By equipping learners with practical skills in Microsoft Word, Excel, graphic design, and programming, the tutorial prepares students for both academic success and real-world challenges.</p>

        <p>The tutorial’s unwavering dedication to educational enrichment has solidified its position as a cornerstone of the community.</p>
      </div>
      <button type="button" class="history-toggle" id="historyToggle" aria-expanded="false">
        <span class="read-text">Show more</span>
        <span class="less-text">Show less</span>
      </button>
      </div>

      <div class="col-12 col-md-4 order-1 order-md-2 history-logo d-flex justify-content-md-center justify-content-end">
        <div class="logo-card">
          <img src="<?= app_url('assets/images/hq-logo.jpeg') ?>" alt="HQ Logo" class="img-fluid">
        </div>
      </div>
    </div>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var historyCopy = document.getElementById('historyCopy');
  var historyToggle = document.getElementById('historyToggle');

  if (!historyCopy || !historyToggle) {
    return;
  }

  if (historyCopy.scrollHeight <= historyCopy.clientHeight + 8) {
    historyCopy.classList.add('is-static');
    return;
  }

  historyToggle.style.display = 'inline-flex';
  historyToggle.addEventListener('click', function () {
    var expanded = historyToggle.getAttribute('aria-expanded') === 'true';
    historyCopy.classList.toggle('is-expanded', !expanded);
    historyToggle.classList.toggle('expanded', !expanded);
    historyToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');

    if (expanded) {
      historyCopy.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
});
</script>


<!-- Vision & Mission -->
<section class="values-section">
  <div class="container">
    <div class="values-grid">
      <article class="value-card">
        <div class="value-icon"><i class='bx bxs-bullseye'></i></div>
        <h4>Our Vision</h4>
        <p>To be the leading tutorial academy that transforms students into confident, well-prepared individuals ready to excel in their academic pursuits and achieve their educational goals with excellence.</p>
      </article>

      <article class="value-card">
        <div class="value-icon"><i class='bx bxs-rocket'></i></div>
        <h4>Our Mission</h4>
        <p>To provide high-quality, personalized education that empowers students to achieve academic excellence through innovative teaching methods, experienced tutors, and comprehensive exam preparation programs.</p>
      </article>
    </div>
  </div><br><br>
  <div class="container">
    <div class="ceo-heading">
      <h2>Our Core <span class="highlight">Values</span></h2>
    </div>

    <div class="core-grid">
      <div class="core-value-card">
        <div class="core-icon"><i class='bx bxs-award'></i></div>
        <h5>Excellence</h5>
        <p>We strive for the highest standards in everything we do</p>
      </div>

      <div class="core-value-card">
        <div class="core-icon"><i class='bx bxs-shield'></i></div>
        <h5>Integrity</h5>
        <p>Building trust through honesty and transparent communication</p>
      </div>

      <div class="core-value-card">
        <div class="core-icon"><i class='bx bxs-rocket'></i></div>
        <h5>Innovation</h5>
        <p>Embracing new methods and technologies for better learning</p>
      </div>

      <div class="core-value-card">
        <div class="core-icon"><i class='bx bxs-hand'></i></div>
        <h5>Dedication</h5>
        <p>Committed to our students' success and growth</p>
      </div>

      <div class="core-value-card">
        <div class="core-icon"><i class='bx bxs-star'></i></div>
        <h5>Quality</h5>
        <p>Delivering superior educational experiences consistently</p>
      </div>

      <div class="core-value-card">
        <div class="core-icon"><i class='bx bxs-heart'></i></div>
        <h5>Care</h5>
        <p>Nurturing every student with personalized attention</p>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/tutors.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>


