(function () {
  const adminBase = (window.HQ_ADMIN_PATH || '').replace(/\/$/, '');
  const apiBase = adminBase ? adminBase + '/api' : 'api';

  async function fetchTourStatus() {
    const res = await fetch(apiBase + '/tour.php?action=status', {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    if (!res.ok) {
      throw new Error('Failed to load tour status');
    }
    return res.json();
  }

  async function postTourAction(action) {
    const fd = new FormData();
    fd.append('action', action);
    fd.append('_csrf', (window.HQ_CSRF && window.HQ_CSRF.tour) ? window.HQ_CSRF.tour : '');

    await fetch(apiBase + '/tour.php', {
      method: 'POST',
      body: fd,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
  }

  function buildIntroSteps(rawSteps) {
    if (!Array.isArray(rawSteps)) return [];

    const steps = [];
    rawSteps.forEach(function (step) {
      if (!step || !step.intro) return;

      const slug = step.slug || '';
      let previewHtml = '';
      
      if (slug) {
        const previewUrl = adminBase + '/index.php?pages=' + encodeURIComponent(slug);
        previewHtml = `
          <div class="hq-tour-preview-shell">
            <div class="hq-tour-preview-label">Live preview</div>
            <div class="hq-tour-preview-frame">
              <div class="hq-tour-preview-overlay" aria-hidden="true"></div>
              <iframe src="${previewUrl}" class="hq-tour-preview-iframe" tabindex="-1"></iframe>
            </div>
          </div>
        `;
      }

      const introText = `
        <div class="hq-tour-step">
          ${step.title ? `<h3 class="hq-tour-step-title">${step.title}</h3>` : ''}
          <p class="hq-tour-step-copy">${step.intro}</p>
          ${previewHtml}
        </div>
      `;

      // Deliberately omitting the `element` property so Intro.js centers it on screen.
      // This ensures mobile users are not broken by hidden sidebars.
      steps.push({ intro: introText });
    });

    return steps;
  }

  async function startRoleTourIfNeeded(forceStart) {
    // Prevent reruns while user navigates quickly in same tab.
    if (!forceStart && sessionStorage.getItem('hq_role_tour_running') === '1') return;

    let payload;
    try {
      payload = await fetchTourStatus();
    } catch (e) {
      return;
    }

    if (!payload || payload.status !== 'ok' || (!payload.show_tour && !forceStart)) {
      return;
    }

    if (typeof window.introJs !== 'function') {
      return;
    }

    const steps = buildIntroSteps(payload.steps || []);
    if (!steps.length) {
      return;
    }

    sessionStorage.setItem('hq_role_tour_running', '1');

    try {
      await postTourAction('start');
    } catch (e) {
      // Ignore start audit failure; tour can still proceed.
    }

    const tour = window.introJs();
    tour.setOptions({
      steps: steps,
      showProgress: true,
      showBullets: false,
      nextLabel: 'Next <i class="bx bx-chevron-right"></i>',
      prevLabel: '<i class="bx bx-chevron-left"></i> Back',
      doneLabel: '<i class="bx bx-check"></i> Finish Tour',
      skipLabel: 'Skip',
      tooltipClass: 'hq-intro-modal',
      scrollTo: false,
      exitOnOverlayClick: false
    });

    let completed = false;

    tour.oncomplete(async function () {
      completed = true;
      try { await postTourAction('complete'); } catch (e) {}
      sessionStorage.removeItem('hq_role_tour_running');
    });

    tour.onexit(async function () {
      if (!completed) {
        try { await postTourAction('skip'); } catch (e) {}
      }
      sessionStorage.removeItem('hq_role_tour_running');
    });

    tour.start();
  }

  window.HQ_START_ROLE_TOUR = function () {
    sessionStorage.removeItem('hq_role_tour_running');
    return startRoleTourIfNeeded(true);
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startRoleTourIfNeeded);
  } else {
    startRoleTourIfNeeded();
  }
})();
