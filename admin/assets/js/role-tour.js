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
      const selector = step.selector || '';
      const el = selector ? document.querySelector(selector) : null;
      if (selector && !el) return;

      const introText = step.title
        ? '<strong>' + step.title + '</strong><br>' + step.intro
        : step.intro;

      if (el) {
        steps.push({ element: el, intro: introText });
      } else {
        steps.push({ intro: introText });
      }
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
      nextLabel: 'Next',
      prevLabel: 'Back',
      doneLabel: 'Finish',
      skipLabel: 'Skip'
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
