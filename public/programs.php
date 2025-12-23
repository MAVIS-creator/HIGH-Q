<?php
// Fetch programs from database
require_once __DIR__ . '/config/db.php';

$programs = [];
try {
    $stmt = $pdo->prepare("SELECT id, title, slug, description, duration, icon, highlight_badge FROM courses WHERE is_active = 1 ORDER BY title ASC");
    $stmt->execute();
    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback to empty array
    $programs = [];
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<section class="about-hero">
	<div class="about-hero-overlay"></div>
	<div class="container about-hero-inner">
		<h1>Our Programs</h1>
		<p class="lead">Comprehensive educational programs designed to help you excel in your studies</p>
	</div>
</section>

<!-- Programs listing (from database) -->
<section class="programs-content">
	<div class="container">
		<div class="ceo-heading">
			<h2>Explore Our <span class="high">Programs</span></h2>
			<p class="lead">Choose a course tailored to your needs — from exam prep to digital skills.</p>
		</div>
        
        <?php if (empty($programs)): ?>
            <div style="text-align: center; padding: 64px 24px; color: var(--hq-gray);">
                <i class='bx bx-folder-open' style="font-size: 48px; color: #d1d5db; margin-bottom: 16px;"></i>
                <p>No programs available at the moment. Check back soon!</p>
            </div>
        <?php else: ?>
            <div class="programs-grid">
                <?php foreach ($programs as $program): ?>
                    <?php
                    $icon = htmlspecialchars($program['icon'] ?? 'bx bx-book');
                    $title = htmlspecialchars($program['title']);
                    $slug = htmlspecialchars($program['slug']);
                    $description = htmlspecialchars($program['description'] ?? '');
                    $duration = htmlspecialchars($program['duration'] ?? 'Flexible');
                    $highlight = htmlspecialchars($program['highlight_badge'] ?? '');
                    ?>
                    <article class="program-card">
                        <div class="program-card-head">
                            <div class="program-icon"><i class="<?= $icon ?>"></i></div>
                        </div>
                        <div class="program-card-body">
                            <h4><?= $title ?></h4>
                            <p class="muted"><?= $description ?></p>
                            <?php if ($highlight): ?>
                                <div class="subjects">
                                    <span class="tag highlight-badge"><?= $highlight ?></span>
                                </div>
                            <?php endif; ?>
                            <p class="duration" style="color: #536387; font-weight: 600;">Duration: <?= $duration ?></p>
                            <a href="program.php?slug=<?= $slug ?>" class="btn-primary">Learn More</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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
