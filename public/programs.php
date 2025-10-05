<?php
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<section class="about-hero">
	<div class="about-hero-overlay"></div>
	<div class="container about-hero-inner">
		<h1>Our Programs</h1>
		<p class="lead">Comprehensive educational programs designed to help you excel in your studies</p>
	</div>
</section>

<!-- Programs listing (placeholder) -->
<section class="programs-content">
	<div class="container">
		<div class="ceo-heading">
			<h2>Explore Our <span class="high">Programs</span></h2>
			<p class="lead">Choose a course tailored to your needs — from exam prep to digital skills.</p>
		</div>
			<!-- Static program cards (not pulled from SQL) -->
			<div class="programs-grid">
				<article class="program-card">
					<div class="program-card-head">
						<div class="program-icon"><i class="bx bx-target-lock"></i></div>
					</div>
					<div class="program-card-body">
						<h4>JAMB Preparation</h4>
						<p class="muted">Comprehensive preparation for Joint Admissions and Matriculation Board examinations</p>
						<div class="subjects">
							<span class="tag">English Language</span>
							<span class="tag">Mathematics</span>
							<span class="tag">Sciences</span>
							<span class="tag">Arts</span>
						</div>
						<p class="duration">Duration: 4-6 months</p>
						<a href="program.php?slug=jamb-preparation" class="btn-primary">Learn More</a>
					</div>
				</article>

				<article class="program-card">
					<div class="program-card-head">
						<div class="program-icon"><i class="bx bx-book"></i></div>
					</div>
					<div class="program-card-body">
						<h4>WAEC Preparation</h4>
						<p class="muted">Complete preparation for West African Senior School Certificate Examination</p>
						<div class="subjects">
							<span class="tag">Core Subjects</span>
							<span class="tag">Electives</span>
							<span class="tag">Practicals</span>
						</div>
						<p class="duration">Duration: 6-12 months</p>
						<a href="program.php?slug=waec-preparation" class="btn-primary">Learn More</a>
					</div>
				</article>

				<article class="program-card">
					<div class="program-card-head">
						<div class="program-icon"><i class="bx bx-book-open"></i></div>
					</div>
					<div class="program-card-body">
						<h4>NECO Preparation</h4>
						<p class="muted">National Examination Council preparation with experienced tutors</p>
						<div class="subjects">
							<span class="tag">All Subjects</span>
							<span class="tag">Mock Exams</span>
							<span class="tag">Study Materials</span>
						</div>
						<p class="duration">Duration: 6-12 months</p>
						<a href="program.php?slug=neco-preparation" class="btn-primary">Learn More</a>
					</div>
				</article>

				<article class="program-card">
					<div class="program-card-head">
						<div class="program-icon"><i class="bx bx-award"></i></div>
					</div>
					<div class="program-card-body">
						<h4>Post-UTME</h4>
						<p class="muted">University-specific entrance examination preparation</p>
						<div class="subjects">
							<span class="tag">University Focus</span>
							<span class="tag">Practice Tests</span>
							<span class="tag">Interview Prep</span>
						</div>
						<p class="duration">Duration: 2-4 months</p>
						<a href="program.php?slug=post-utme" class="btn-primary">Learn More</a>
					</div>
				</article>

				<article class="program-card">
					<div class="program-card-head">
						<div class="program-icon"><i class="bx bx-star"></i></div>
					</div>
					<div class="program-card-body">
						<h4>Special Tutorials</h4>
						<p class="muted">Intensive one-on-one and small group tutorial sessions</p>
						<div class="subjects">
							<span class="tag">Personalized</span>
							<span class="tag">Flexible Schedule</span>
							<span class="tag">Subject Focus</span>
						</div>
						<p class="duration">Duration: Flexible</p>
						<a href="program.php?slug=special-tutorials" class="btn-primary">Learn More</a>
					</div>
				</article>

				<article class="program-card">
					<div class="program-card-head">
						<div class="program-icon"><i class="bx bx-laptop"></i></div>
					</div>
					<div class="program-card-body">
						<h4>Computer Training</h4>
						<p class="muted">Modern computer skills and digital literacy training</p>
						<div class="subjects">
							<span class="tag">MS Office</span>
							<span class="tag">Internet Skills</span>
							<span class="tag">Programming</span>
						</div>
						<p class="duration">Duration: 3-6 months</p>
						<a href="program.php?slug=computer-training" class="btn-primary">Learn More</a>
					</div>
				</article>

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
