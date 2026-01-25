<?php
// SEO Meta Tags
$pageTitle = 'About Us | Leading Educational Excellence Since 2018';
$pageDescription = 'High Q Tutorial - Nigeria\'s premier exam coaching center. Expert tutors, proven results, comprehensive JAMB, WAEC, and Post-UTME preparation.';
$pageKeywords = 'about High Q Tutorial, exam coaching Nigeria, JAMB tutors, educational excellence';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<style>
  /* Mobile-only: center history logo and add small gap under it without affecting desktop */
  @media (max-width: 767.98px) {
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
      <p>High Q tutorial founded in 2018/2019 by Mr. Adebule Quam Okikiola and Mr. Adebule Ibrahim has left an enduring legacy since its inception. Named after its visionary founders, the tutorial symbolizes a commitment to education empowerment that resonates within the community.</p>

      <p>Following Mr. Ibrahim's departure for overseas opportunities, Mr. Adebule Quam assumed sole leadership, steering the tutorial toward remarkable success. Under Mr. Adebule Quam's guidance, High Q Tutorial has blossomed into a hub of academic excellence and technological proficiency.</p>

      <p>Beyond conventional tutorial work, it serves as a catalyst for holistic development. Through meticulous instruction, students have sharpened their academic prowess, achieving commendable results in examinations such as JAMB, WAEC, and NECO since 2018.</p>

      <p>High Q Tutorial’s impact transcends the classroom, enriching lives and fostering digital literacy essential for navigating the complexities of the modern world. By equipping learners with practical skills in Microsoft Word, Excel, graphic design, and programming, the tutorial prepares students for both academic success and real-world challenges.</p>

      <p>The tutorial’s unwavering dedication to educational enrichment has solidified its position as a cornerstone of the community.</p>
      </div>

      <div class="col-12 col-md-4 order-1 order-md-2 history-logo d-flex justify-content-md-center justify-content-end">
        <div class="logo-card">
          <img src="<?= app_url('assets/images/hq-logo.jpeg') ?>" alt="HQ Logo" class="img-fluid">
        </div>
      </div>
    </div>
  </div>
</section>


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

<!-- Wall of Fame Section -->
<section class="wall-of-fame-section" style="padding:4rem 0;background:linear-gradient(135deg, #0b1a2c 0%, #1e3a5f 100%);">
  <div class="container">
    <div class="ceo-heading" style="text-align:center;margin-bottom:2.5rem;">
      <h2 style="color:#ffd600;font-weight:800;font-size:2.25rem;">Wall of <span style="color:#fff;">Fame</span></h2>
      <p style="color:rgba(255,255,255,0.75);font-size:1rem;max-width:600px;margin:0.75rem auto 0;">Celebrating our outstanding students who achieved remarkable results through dedication and the HQ Academy experience.</p>
    </div>

    <div class="fame-grid" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:1.5rem;">
      
      <!-- Kingsley Oluwapelumi - JAMB 242 -->
      <article class="fame-card" style="background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 10px 40px rgba(0,0,0,0.2);transition:transform 0.3s ease,box-shadow 0.3s ease;">
        <div style="position:relative;">
          <img src="<?= app_url('uploads/HQ Student Feature Submission.csv (1)/IMG_5396 - Kingsley Pelumi.jpeg') ?>" 
               alt="Adedunye Kingsley Oluwapelumi" 
               style="width:100%;height:220px;object-fit:cover;">
          <div style="position:absolute;top:12px;right:12px;background:linear-gradient(135deg,#ffd600 0%,#e6c200 100%);color:#0b1a2c;padding:6px 14px;border-radius:999px;font-weight:700;font-size:0.8rem;box-shadow:0 4px 12px rgba(0,0,0,0.2);">
            JAMB: 242
          </div>
        </div>
        <div style="padding:1.25rem;">
          <h4 style="margin:0 0 0.5rem;font-size:1.1rem;font-weight:700;color:#0b1a2c;">Adedunye Kingsley Oluwapelumi</h4>
          <p style="margin:0 0 0.75rem;font-size:0.85rem;color:#64748b;"><i class='bx bxs-graduation'></i> Ambrose Ali University</p>
          <p style="margin:0;font-size:0.9rem;color:#475569;line-height:1.5;">"I chose HQ because of the passion and zeal toward the success of every student."</p>
        </div>
      </article>
      
      <!-- Fadele Oluwanifemi Abigail - JAMB 235 -->
      <article class="fame-card" style="background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 10px 40px rgba(0,0,0,0.2);transition:transform 0.3s ease,box-shadow 0.3s ease;">
        <div style="position:relative;">
          <img src="<?= app_url('uploads/HQ Student Feature Submission.csv (1)/IMG_20251221_082402_027 - Oluwanifemi Abigail.jpg') ?>" 
               alt="Fadele Oluwanifemi Abigail" 
               style="width:100%;height:220px;object-fit:cover;">
          <div style="position:absolute;top:12px;right:12px;background:linear-gradient(135deg,#ffd600 0%,#e6c200 100%);color:#0b1a2c;padding:6px 14px;border-radius:999px;font-weight:700;font-size:0.8rem;box-shadow:0 4px 12px rgba(0,0,0,0.2);">
            JAMB: 235
          </div>
        </div>
        <div style="padding:1.25rem;">
          <h4 style="margin:0 0 0.5rem;font-size:1.1rem;font-weight:700;color:#0b1a2c;">Fadele Oluwanifemi Abigail</h4>
          <p style="margin:0 0 0.75rem;font-size:0.85rem;color:#64748b;"><i class='bx bxs-graduation'></i> Ladoke Akintola University</p>
          <p style="margin:0;font-size:0.9rem;color:#475569;line-height:1.5;">"At first, it was the only tutorial I heard people talking about. It has been said that the academy is really high quality."</p>
        </div>
      </article>
      
      <!-- Adeyemi Wahab Ayoade - JAMB -->
      <article class="fame-card" style="background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 10px 40px rgba(0,0,0,0.2);transition:transform 0.3s ease,box-shadow 0.3s ease;">
        <div style="position:relative;">
          <img src="<?= app_url('uploads/HQ Student Feature Submission.csv (1)/Screenshot_20231227-204304_1 - Wahab.jpg') ?>" 
               alt="Adeyemi Wahab Ayoade" 
               style="width:100%;height:220px;object-fit:cover;">
          <div style="position:absolute;top:12px;right:12px;background:linear-gradient(135deg,#ffd600 0%,#e6c200 100%);color:#0b1a2c;padding:6px 14px;border-radius:999px;font-weight:700;font-size:0.8rem;box-shadow:0 4px 12px rgba(0,0,0,0.2);">
            JAMB
          </div>
        </div>
        <div style="padding:1.25rem;">
          <h4 style="margin:0 0 0.5rem;font-size:1.1rem;font-weight:700;color:#0b1a2c;">Adeyemi Wahab Ayoade</h4>
          <p style="margin:0 0 0.75rem;font-size:0.85rem;color:#64748b;"><i class='bx bxs-graduation'></i> Adekunle Ajasin University</p>
          <p style="margin:0;font-size:0.9rem;color:#475569;line-height:1.5;">"I chose this tutorial because it is well-structured, engaging, and provides clear explanations."</p>
        </div>
      </article>
      
      <!-- Ogunsanya Zainab Olayinka - JAMB 218 -->
      <article class="fame-card" style="background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 10px 40px rgba(0,0,0,0.2);transition:transform 0.3s ease,box-shadow 0.3s ease;">
        <div style="position:relative;">
          <img src="<?= app_url('uploads/HQ Student Feature Submission.csv (1)/IMG_2129 - Ogunsanya Zainab.JPG') ?>" 
               alt="Ogunsanya Zainab Olayinka" 
               style="width:100%;height:220px;object-fit:cover;">
          <div style="position:absolute;top:12px;right:12px;background:linear-gradient(135deg,#ffd600 0%,#e6c200 100%);color:#0b1a2c;padding:6px 14px;border-radius:999px;font-weight:700;font-size:0.8rem;box-shadow:0 4px 12px rgba(0,0,0,0.2);">
            JAMB: 218
          </div>
        </div>
        <div style="padding:1.25rem;">
          <h4 style="margin:0 0 0.5rem;font-size:1.1rem;font-weight:700;color:#0b1a2c;">Ogunsanya Zainab Olayinka</h4>
          <p style="margin:0 0 0.75rem;font-size:0.85rem;color:#64748b;"><i class='bx bxs-graduation'></i> Olabisi Onabanjo University</p>
          <p style="margin:0;font-size:0.9rem;color:#475569;line-height:1.5;">"HQ stands out from other tutorials."</p>
        </div>
      </article>
      
    </div>
  </div>
</section>

<style>
.fame-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 20px 50px rgba(0,0,0,0.3);
}
</style>

<?php
// Include tutors partial (keeps heavy markup in a separate file)
include __DIR__ . '/tutors.php';

include __DIR__ . '/includes/footer.php'; ?>
