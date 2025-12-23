<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

// Redirect slug to single page
if (!empty($_GET['slug'])) {
	header('Location: program-single.php?slug=' . urlencode($_GET['slug']));
	exit;
}

// Static program containers (slugs still power single pages)
$programs = [
  [
    'title' => 'SSCE & GCE Exams',
    'slug' => 'ssce-gce-exams',
    'subtitle' => "Registration and intensive coaching for O'Level success.",
    'duration' => '8-24 weeks (exam-calendar aligned)',
    'features' => [
      'WAEC & NECO (School Candidates - May/June)',
      'WAEC GCE & NECO GCE (Private Candidates)',
      'NABTEB (Optional, fits same track)',
      'Intensive drills, past questions, timed practice'
    ],
  ],
  [
    'title' => 'JAMB & University Admission',
    'slug' => 'jamb-university-admission',
    'subtitle' => 'Comprehensive JAMB CBT simulations and admission processing.',
    'duration' => '6-12 weeks (CBT-focused)',
    'features' => [
      'JAMB / UTME (Registration & Training)',
      'CBT Training (core tool for JAMB success)',
      'Post-UTME (University screening prep)',
      'Admission guidance and application support'
    ],
  ],
  [
    'title' => 'Advanced & International Studies',
    'slug' => 'advanced-international-studies',
    'subtitle' => 'Global certifications and Direct Entry programs for university admission.',
    'duration' => '10-24 weeks (modular)',
    'features' => [
      "Study Abroad: SAT, TOEFL, IELTS, GMAT, GRE",
      'Direct Entry: JUPEB, A-Levels (Cambridge/IJMB)',
      "International O'Level: IGCSE",
      'Application strategy and test readiness'
    ],
  ],
  [
    'title' => 'Remedial & Foundation Tutorials',
    'slug' => 'remedial-foundation-tutorials',
    'subtitle' => 'Structured academic grooming from Primary 1 to SSS 3.',
    'duration' => '4-16 weeks (modular by class)',
    'features' => [
      'Junior Secondary: BECE (Junior WAEC) Prep',
      'Primary School: Common Entrance & Foundation',
      'Senior School: After-school subject tutorials (SSS 1-3)',
      'Small-group coaching and continuous assessment'
    ],
  ],
  [
    'title' => 'Digital Skills & Tech',
    'slug' => 'digital-skills-tech',
    'subtitle' => 'Practical tech skills for the modern workplace.',
    'duration' => '4-10 weeks (hands-on)',
    'features' => [
      'Computer Literacy (Word/Excel)',
      'Graphic Design & Programming',
      'Data Analysis fundamentals',
      'Portfolio-driven, project-based learning'
    ],
  ],
  [
    'title' => 'Professional Services',
    'slug' => 'professional-services',
    'subtitle' => 'Expert guidance on admissions and career paths.',
    'duration' => 'On-demand (book a slot)',
    'features' => [
      'Educational Consulting & Career Counseling',
      'University/Visa Application Assistance',
      'Data Entry & Document Processing',
      'Personalized advisory sessions'
    ],
  ],
];
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<section class="about-hero">
	<div class="about-hero-overlay"></div>
	<div class="container about-hero-inner">
		<h1>Our Programs</h1>
		<p class="lead">Comprehensive educational programs designed to help you excel in your studies</p>
	</div>
</section>

<section class="programs-content">
	<div class="container">
		<div class="ceo-heading">
			<h2>Explore Our <span class="high">Programs</span></h2>
			<p class="lead">Choose a course tailored to your needs — from exam prep to digital skills.</p>
		</div>

		<div class="programs-grid">
		  <?php if (empty($programs)): ?>
		    <div class="program-card" style="grid-column: span 3; text-align:center; padding:32px;">
		      <h4 style="margin-bottom:8px;">No programs published yet</h4>
		      <p class="muted" style="margin-bottom:16px;">Please check back soon or contact us for custom tutorials.</p>
		      <a class="btn-primary" href="contact.php">Contact Us</a>
		    </div>
		  <?php else: ?>
		    <?php foreach ($programs as $p): ?>
		      <?php
		        $title = htmlspecialchars($p['title']);
		        $slug  = htmlspecialchars($p['slug']);
		        $desc  = trim($p['description'] ?? '');
		        $duration = trim($p['duration'] ?? '');
		        $icon = trim($p['icon'] ?? '');
		        $badge = trim($p['highlight_badge'] ?? '');
		        $features = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $p['features_list'] ?? ''))));
		      ?>
		      <article class="program-card">
		        <div class="program-card-head">
		          <div class="program-icon <?php echo ($slug === 'waec-preparation' ? 'program-waec' : ($slug === 'neco-preparation' ? 'program-neco' : ($slug === 'computer-training' ? 'program-computer' : ''))); ?>">
		            <?php
		              if ($icon !== '') {
		                if (strpos($icon, 'bx') !== false) {
		                  echo "<i class='" . htmlspecialchars($icon) . "'></i>";
		                } else {
		                  $iconPath = __DIR__ . '/assets/images/icons/' . $icon;
		                  if (is_readable($iconPath)) {
		                    echo "<img src='" . app_url('assets/images/icons/' . rawurlencode($icon)) . "' alt='" . $title . " icon'>";
		                  } else {
		                    echo "<i class='bx bxs-book-open'></i>";
		                  }
		                }
		              } else {
		                echo "<i class='bx bxs-book-open'></i>";
		              }
		            ?>
		          </div>
		        </div>
		        <div class="program-card-body">
		          <h4><?= $title ?></h4>
		          <?php if ($desc !== ''): ?>
		            <p class="muted"><?= htmlspecialchars(strlen($desc) > 180 ? substr($desc, 0, 177) . '…' : $desc) ?></p>
		          <?php endif; ?>
		          <?php if (!empty($features)): ?>
		            <div class="subjects">
		              <?php foreach (array_slice($features, 0, 4) as $ft): ?>
		                <span class="tag"><?= htmlspecialchars($ft) ?></span>
		              <?php endforeach; ?>
		            </div>
		          <?php endif; ?>
		          <p class="duration" style="color: #536387; font-weight: 600;">Duration: <?= htmlspecialchars($duration ?: 'Flexible') ?></p>
		          <a href="program.php?slug=<?= $slug ?>" class="btn-primary">Learn More</a>
		        </div>
		      </article>
		    <?php endforeach; ?>
		  <?php endif; ?>
		</div>
	</div>
</section>

<!-- Why Choose Our Programs -->
<section class="why-programs">
	<div class="container">
		<div class="ceo-heading">
			<h2>Why Choose <span class="highlight">Our Programs</span></h2>
		</div>

		<div class="core-grid">
			<article class="value-card">
				<div class="value-icon"><i class="bx bx-user" style="color:var(--hq-yellow);font-size:20px"></i></div>
				<h4>Expert Tutors</h4>
				<p>Experienced and qualified tutors with proven track records</p>
			</article>

			<article class="value-card">
				<div class="value-icon"><i class="bx bx-bar-chart" style="color:var(--hq-yellow);font-size:20px"></i></div>
				<h4>Proven Results</h4>
				<p>Consistent high performance and success rates across all programs</p>
			</article>

			<article class="value-card">
				<div class="value-icon"><i class="bx bx-target-lock" style="color:var(--hq-yellow);font-size:20px"></i></div>
				<h4>Personalized Learning</h4>
				<p>Tailored approach to meet individual student needs and goals</p>
			</article>

			<article class="value-card">
				<div class="value-icon"><i class="bx bx-laptop" style="color:var(--hq-yellow);font-size:20px"></i></div>
				<h4>Modern Facilities</h4>
				<p>State-of-the-art equipment and learning resources</p>
			</article>
		</div>
	</div>
</section>

<!-- Track record / stats -->
<section class="track-record">
	<div class="container">
		<div class="ceo-heading">
			<h2>Our <span class="highlight">Track Record</span></h2>
		</div>

		<div class="ceo-stats">
			<div class="stat yellow">
				<i class="bx bx-bar-chart"></i>
				<strong>305</strong>
				<span>Highest JAMB Score 2025</span>
			</div>

			<div class="stat red">
				<i class="bx bx-award"></i>
				<strong>98%</strong>
				<span>WAEC Success Rate</span>
			</div>

			<div class="stat gray">
				<i class="bx bx-users"></i>
				<strong>500+</strong>
				<span>Students Trained</span>
			</div>

			<div class="stat">
				<i class="bx bx-calendar"></i>
				<strong>6+</strong>
				<span>Years of Excellence</span>
			</div>
		</div>
	</div>
</section>


<section class="cta-section">
	<div class="container">
		<h2>Ready to Excel?</h2>
		<p>Choose your program and start your journey to academic success</p>
		<a href="register.php" class="btn-enroll">Enroll Now</a>
	</div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

<!-- Call to Action: Ready to Excel -->

<style>
	/* Base card tweaks: avoid space-between stretching content */
	.program-card {
		justify-content: flex-start;
		gap: 12px;
	}

	/* Reduce spacing between icon and title */
	.program-card-head {
		margin-bottom: 6px;
	}

	.program-card-body {
		display: flex;
		flex-direction: column;
		gap: 12px;
	}

	.program-card-body h4 {
		margin: 0;
		font-size: 17px;
		font-weight: 700;
	}

	/* Tighten only WAEC, NECO, Computer Training via helper class */
	.program-card.tight-gap {
		gap: 8px;
	}
	.program-card.tight-gap .program-card-head {
		margin-bottom: 2px;
	}
	.program-card.tight-gap .program-icon {
		margin-bottom: 0;
	}

	/* Mobile-only: small vertical gap between icon and body to mimic a <br> without affecting desktop */
	@media (max-width: 575.98px) {
		.program-card .program-card-head { display: block; text-align: center; }
		.program-card .program-icon { margin-bottom: 0.3rem; }
		.program-card-head {
			margin-bottom: 6px;
		}
		.program-card.tight-gap .program-card-head {
			margin-bottom: 1px;
		}
	}

	/* Desktop-only adjustments: reduce excessive space for specific program cards */
	@media (min-width: 992px) {
		/* Target WAEC, NECO and Computer Training specifically */
		.program-card .program-icon.program-waec,
		.program-card .program-icon.program-neco,
		.program-card .program-icon.program-computer {
			margin-bottom: 0; /* reduce the gap on desktop for these cards only */
		}

		/* If icons have large padding, reduce it just on these cards */
		.program-card .program-icon.program-waec i,
		.program-card .program-icon.program-neco i,
		.program-card .program-icon.program-computer i {
			font-size: 20px; /* keep icon visually balanced */
		}
	}
</style>
