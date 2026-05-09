<?php
$pageTitle = 'Terms of Service - High Q Solid Academy';
include __DIR__ . '/includes/header.php';
?>

<style>
  :root {
    --policy-ink: #0f172a;
    --policy-muted: #475569;
    --policy-soft: #e2e8f0;
    --policy-warm: #ffbf00;
    --policy-warm-dark: #b45309;
    --policy-surface: #ffffff;
  }

  .policy-page {
    font-family: "Space Grotesk", "Segoe UI", sans-serif;
    color: var(--policy-ink);
    background: radial-gradient(circle at top right, #fff7d6 0%, #ffffff 48%, #f8fafc 100%);
    padding: 56px 0 80px;
  }

  .policy-shell {
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 20px;
  }

  .policy-hero {
    display: grid;
    grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr);
    gap: 28px;
    align-items: stretch;
    margin-bottom: 36px;
  }

  .policy-hero .eyebrow {
    text-transform: uppercase;
    letter-spacing: 0.24em;
    font-size: 12px;
    font-weight: 700;
    color: var(--policy-warm-dark);
  }

  .policy-hero h1 {
    font-size: clamp(32px, 4vw, 46px);
    line-height: 1.05;
    margin: 10px 0 14px;
  }

  .policy-hero .lead {
    font-size: 17px;
    line-height: 1.6;
    color: var(--policy-muted);
  }

  .policy-meta {
    margin-top: 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
  }

  .policy-chip {
    background: #111827;
    color: #f8fafc;
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 12px;
    letter-spacing: 0.04em;
  }

  .policy-ghost {
    text-decoration: none;
    border: 1px solid var(--policy-soft);
    padding: 8px 12px;
    border-radius: 10px;
    color: var(--policy-ink);
    font-weight: 600;
  }

  .policy-card {
    background: var(--policy-surface);
    border-radius: 18px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 18px 50px rgba(15, 23, 42, 0.08);
    padding: 20px 22px;
  }

  .policy-card h3 {
    font-size: 15px;
    text-transform: uppercase;
    letter-spacing: 0.18em;
    color: var(--policy-muted);
    margin: 0 0 12px;
  }

  .policy-card ul {
    margin: 0;
    padding: 0;
    list-style: none;
    display: grid;
    gap: 10px;
  }

  .policy-card li {
    display: grid;
    grid-template-columns: 12px 1fr;
    gap: 10px;
    font-size: 14px;
    color: var(--policy-muted);
  }

  .policy-card li span {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--policy-warm);
    margin-top: 6px;
  }

  .policy-grid {
    display: grid;
    grid-template-columns: minmax(0, 240px) minmax(0, 1fr);
    gap: 32px;
  }

  .policy-nav {
    position: sticky;
    top: 24px;
    align-self: start;
    padding: 18px;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    background: #ffffff;
  }

  .policy-nav h4 {
    margin: 0 0 12px;
    font-size: 14px;
    color: var(--policy-muted);
    text-transform: uppercase;
    letter-spacing: 0.18em;
  }

  .policy-nav a {
    display: block;
    text-decoration: none;
    color: var(--policy-ink);
    font-weight: 600;
    font-size: 14px;
    padding: 8px 0;
    border-bottom: 1px dashed #e2e8f0;
  }

  .policy-content {
    display: grid;
    gap: 26px;
  }

  .policy-section {
    background: #ffffff;
    border-radius: 20px;
    padding: 22px 24px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.04);
  }

  .policy-section h2 {
    margin: 0 0 12px;
    font-size: 20px;
  }

  .policy-section p,
  .policy-section li {
    color: var(--policy-muted);
    line-height: 1.7;
    font-size: 15px;
  }

  .policy-section ul {
    padding-left: 18px;
  }

  .policy-highlight {
    background: #111827;
    color: #f8fafc;
    border-radius: 18px;
    padding: 22px 24px;
    display: grid;
    gap: 10px;
  }

  .policy-highlight a {
    color: var(--policy-warm);
    font-weight: 700;
  }

  @media (max-width: 960px) {
    .policy-hero,
    .policy-grid {
      grid-template-columns: 1fr;
    }

    .policy-nav {
      position: static;
    }
  }
</style>

<section class="policy-page">
  <div class="policy-shell">
    <div class="policy-hero">
      <div>
        <p class="eyebrow">Terms of Service</p>
        <h1>Clear rules for a trusted learning experience.</h1>
        <p class="lead">These Terms explain how you can use our services, how payments work, and how we protect students, instructors, and the academy.</p>
        <div class="policy-meta">
          <span class="policy-chip">Last updated: <?= date('F j, Y') ?></span>
          <a class="policy-ghost" href="/">Back to home</a>
        </div>
      </div>
      <div class="policy-card">
        <h3>Highlights</h3>
        <ul>
          <li><span></span>Use our services responsibly and lawfully.</li>
          <li><span></span>Program access depends on successful payment.</li>
          <li><span></span>Course content is protected by copyright.</li>
          <li><span></span>We communicate changes on this page.</li>
        </ul>
      </div>
    </div>

    <div class="policy-grid">
      <aside class="policy-nav">
        <h4>On this page</h4>
        <a href="#use">Acceptable use</a>
        <a href="#accounts">Accounts</a>
        <a href="#payments">Payments & refunds</a>
        <a href="#content">Content rights</a>
        <a href="#conduct">Student conduct</a>
        <a href="#liability">Liability</a>
        <a href="#changes">Changes</a>
        <a href="#contact">Contact</a>
      </aside>

      <div class="policy-content">
        <div class="policy-section" id="use">
          <h2>1. Acceptable use</h2>
          <p>You may use our services only for lawful, educational purposes. Do not misuse or attempt to disrupt our systems, content, or community.</p>
        </div>

        <div class="policy-section" id="accounts">
          <h2>2. Accounts and registration</h2>
          <ul>
            <li>Provide accurate information during registration.</li>
            <li>Keep your login credentials secure.</li>
            <li>You are responsible for activity under your account.</li>
          </ul>
        </div>

        <div class="policy-section" id="payments">
          <h2>3. Payments and refunds</h2>
          <p>Program fees are listed on each registration page. Payments must be completed to confirm enrollment. Refunds are assessed on a case-by-case basis. Email <a href="mailto:info@hqacademy.com">info@hqacademy.com</a> with your request and supporting details.</p>
        </div>

        <div class="policy-section" id="content">
          <h2>4. Content and intellectual property</h2>
          <p>All course materials, assessments, and website content are owned by High Q Solid Academy or our licensors. You may not reproduce, distribute, or sell content without written permission.</p>
        </div>

        <div class="policy-section" id="conduct">
          <h2>5. Student conduct</h2>
          <p>We expect respectful conduct in all learning channels. Harassment, abuse, or academic dishonesty may result in suspension or removal from programs.</p>
        </div>

        <div class="policy-section" id="liability">
          <h2>6. Limitation of liability</h2>
          <p>To the maximum extent permitted by law, we are not liable for indirect, incidental, or consequential damages arising from use of our services.</p>
        </div>

        <div class="policy-section" id="changes">
          <h2>7. Changes to these Terms</h2>
          <p>We may update these Terms periodically. Material changes will be posted on this page with a new effective date.</p>
        </div>

        <div class="policy-highlight" id="contact">
          <strong>Contact</strong>
          <p>Questions about these Terms? Email <a href="mailto:info@hqacademy.com">info@hqacademy.com</a>.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
