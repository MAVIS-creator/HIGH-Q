<?php
$pageTitle = 'Privacy Policy - High Q Solid Academy';
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
    --policy-hero: #0b1120;
  }

  .policy-page {
    font-family: "Space Grotesk", "Segoe UI", sans-serif;
    color: var(--policy-ink);
    background: radial-gradient(circle at top left, #fff7d6 0%, #ffffff 45%, #f8fafc 100%);
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
        <p class="eyebrow">Privacy Policy</p>
        <h1>Your data. Your rights. Our responsibility.</h1>
        <p class="lead">This policy explains how High Q Solid Academy collects, uses, and protects your information across our website, registrations, and support channels.</p>
        <div class="policy-meta">
          <span class="policy-chip">Last updated: <?= date('F j, Y') ?></span>
          <a class="policy-ghost" href="/">Back to home</a>
        </div>
      </div>
      <div class="policy-card">
        <h3>At a glance</h3>
        <ul>
          <li><span></span>We collect only what we need to run programs and support students.</li>
          <li><span></span>We never sell personal information.</li>
          <li><span></span>You can access, correct, or delete your data.</li>
          <li><span></span>Security and retention are built into our processes.</li>
        </ul>
      </div>
    </div>

    <div class="policy-grid">
      <aside class="policy-nav">
        <h4>On this page</h4>
        <a href="#data-we-collect">Data we collect</a>
        <a href="#how-we-use">How we use data</a>
        <a href="#sharing">Sharing</a>
        <a href="#cookies">Cookies</a>
        <a href="#security">Security</a>
        <a href="#retention">Retention</a>
        <a href="#rights">Your rights</a>
        <a href="#contact">Contact</a>
      </aside>

      <div class="policy-content">
        <div class="policy-section" id="data-we-collect">
          <h2>1. Data we collect</h2>
          <ul>
            <li><strong>Identity & contact:</strong> name, email, phone number, and guardian details when required.</li>
            <li><strong>Academic profile:</strong> program interests, exam history, and placement details you choose to share.</li>
            <li><strong>Payments:</strong> transaction references and billing metadata from payment processors.</li>
            <li><strong>Technical data:</strong> device, browser, IP address, and usage logs for security and analytics.</li>
          </ul>
        </div>

        <div class="policy-section" id="how-we-use">
          <h2>2. How we use your information</h2>
          <ul>
            <li>To deliver program enrollment, confirmations, schedules, and progress updates.</li>
            <li>To provide student support, answer inquiries, and resolve issues.</li>
            <li>To improve our learning experience through product and service analytics.</li>
            <li>To meet legal, regulatory, and accounting requirements.</li>
          </ul>
        </div>

        <div class="policy-section" id="sharing">
          <h2>3. Sharing and disclosure</h2>
          <p>We do not sell personal data. We share limited information with trusted partners such as payment processors, email delivery services, and hosting providers only to deliver our services. We may disclose data when required by law or to protect our rights and users.</p>
        </div>

        <div class="policy-section" id="cookies">
          <h2>4. Cookies and analytics</h2>
          <p>We use cookies and similar technologies to keep you signed in, measure site performance, and improve usability. You can control cookies using your browser settings.</p>
        </div>

        <div class="policy-section" id="security">
          <h2>5. Security</h2>
          <p>We use technical and organizational safeguards, including access controls, encryption in transit, and audit logs, to protect your information.</p>
        </div>

        <div class="policy-section" id="retention">
          <h2>6. Data retention</h2>
          <p>We keep personal information only as long as needed for educational services, compliance, and legitimate business purposes. When data is no longer required, we securely delete or anonymize it.</p>
        </div>

        <div class="policy-section" id="rights">
          <h2>7. Your rights</h2>
          <p>You can request access, corrections, deletion, or a copy of your personal data. You may also object to certain processing or withdraw consent where applicable.</p>
        </div>

        <div class="policy-highlight" id="contact">
          <strong>Contact</strong>
          <p>Questions or requests? Email us at <a href="mailto:info@hqacademy.com">info@hqacademy.com</a> and we will respond promptly.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
