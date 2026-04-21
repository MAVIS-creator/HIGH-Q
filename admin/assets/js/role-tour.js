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
        // Create an iframe to preview the page instantly
        const previewUrl = adminBase + '/index.php?pages=' + encodeURIComponent(slug);
        previewHtml = `
          <div style="margin-top: 15px; border-radius: 12px; overflow: hidden; height: 220px; position: relative; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border: 1px solid #eaeaea;">
            <div style="position: absolute; inset: 0; z-index: 10; cursor: default;"></div>
            <iframe src="${previewUrl}" style="width: 100%; height: 800px; border: none; transform: scale(0.4); transform-origin: top left; pointer-events: none;" tabindex="-1"></iframe>
          </div>
        `;
      }

      const introText = `
        <div style="text-align: center; padding: 10px 4px;">
          ${step.title ? `<h3 style="margin: 0 0 10px 0; font-size: 1.25rem; font-weight: 800; color: #111;">${step.title}</h3>` : ''}
          <p style="margin: 0; font-size: 0.95rem; color: #444; line-height: 1.6;">${step.intro}</p>
          ${previewHtml}
        </div>
      `;

      // Deliberately omitting the `element` property so Intro.js centers it on screen.
      // This ensures mobile users are not broken by hidden sidebars.
      steps.push({ intro: introText });
    });

    return steps;
  }

  async function startRoleTourIfNeeded() {
    // Prevent reruns while user navigates quickly in same tab.
    if (sessionStorage.getItem('hq_role_tour_running') === '1') return;

    let payload;
    try {
      payload = await fetchTourStatus();
    } catch (e) {
      return;
    }

    if (!payload || payload.status !== 'ok' || !payload.show_tour) {
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
      tooltipClass: 'hq-intro-modal'
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

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startRoleTourIfNeeded);
  } else {
    startRoleTourIfNeeded();
  }
})();
