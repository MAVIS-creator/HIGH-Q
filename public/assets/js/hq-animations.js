// HQ Animations helper
// - Toggles `.hq-in` when elements enter the viewport (for reveal/slide effects)
// - Adds a lightweight tilt effect for `.hq-tilt` on pointer devices (desktop)

(function(){
  if (typeof window === 'undefined') return;

  function setupObserver() {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('hq-in');
        } else {
          // optional: remove to allow re-trigger
          // entry.target.classList.remove('hq-in');
        }
      });
    }, { threshold: 0.12 });

    // Observe generic reveal elements
    document.querySelectorAll('.hq-reveal-left, .hq-reveal-right, .hq-slide-in').forEach(el => {
      observer.observe(el);
    });

    // Observe aside targets specially so we can choose mobile/desktop class variants
    document.querySelectorAll('aside.hq-aside-target').forEach(aside => {
      // choose initial class based on viewport
      if (window.matchMedia('(min-width: 1025px)').matches) {
        aside.classList.add('hq-slide-in','hq-right');
      } else {
        aside.classList.add('hq-slide-in','hq-up');
      }
      observer.observe(aside);
    });
  }

  function setupTilt() {
    // Only enable tilt on devices with a pointer and sufficiently large screen
    if (!window.matchMedia('(pointer: fine) and (min-width: 1025px)').matches) return;

    const tiltElements = Array.from(document.querySelectorAll('.hq-tilt'));
    if (!tiltElements.length) return;

    const onMove = (e) => {
      tiltElements.forEach(el => {
        const rect = el.getBoundingClientRect();
        const cx = rect.left + rect.width/2;
        const cy = rect.top + rect.height/2;
        const dx = (e.clientX - cx) / rect.width;
        const dy = (e.clientY - cy) / rect.height;
        const rx = (dy * 6).toFixed(2);
        const ry = (dx * -6).toFixed(2);
        el.style.transform = `perspective(800px) rotateX(${rx}deg) rotateY(${ry}deg) translateY(-6px)`;
      });
    };

    const onLeave = () => {
      tiltElements.forEach(el => el.style.transform = '');
    };

    window.addEventListener('mousemove', onMove);
    window.addEventListener('mouseleave', onLeave);
    // cleanup on unload
    window.addEventListener('unload', () => {
      window.removeEventListener('mousemove', onMove);
      window.removeEventListener('mouseleave', onLeave);
    });
  }

  function autoApplyClasses() {
    // Only operate on public pages that include the body.hq-public marker
    if (!document.body.classList.contains('hq-public')) return;

    try {
      // Cards: add glass + slide-in + tilt + hover glow
      document.querySelectorAll('.card:not(.hq-applied)').forEach((el, i) => {
        // skip admin area cards (rough heuristic)
        if (el.closest('#admin')) return;
        el.classList.add('hq-glass', 'hq-slide-in', 'hq-up', 'hq-tilt', 'hq-hover-glow', 'hq-applied');
        // if card has a body, mark for stagger
        const body = el.querySelector('.card-body');
        if (body) body.classList.add('hq-stagger');
      });

      // Hero CTAs and primary site buttons
      document.querySelectorAll('.hero-ctas a, .hero-ctas .btn, .btn-primary, .btn-hq-ghost, .btn-enroll').forEach(btn => {
        if (btn.classList.contains('hq-applied')) return;
        btn.classList.add('hq-cta', 'hq-delay-2', 'hq-applied');
      });

      // Program cards / grids
      document.querySelectorAll('.program-card, .programs-grid .program-card, .tutor-card, .value-card').forEach((el, i) => {
        if (el.classList.contains('hq-applied')) return;
        el.classList.add('hq-slide-in', 'hq-up', 'hq-applied');
        const cb = el.querySelector('.card-body'); if (cb) cb.classList.add('hq-stagger');
      });

      // CEO card specifically (homepage)
      document.querySelectorAll('.ceo-card:not(.hq-applied)').forEach(el=>{
        el.classList.add('hq-slide-in','hq-right','hq-applied');
        const cb = el.querySelector('.card-body'); if (cb) cb.classList.add('hq-stagger');
      });

      // FAQ clamp / read-more: apply to .faq-card paragraphs on mobile
      // configurable lines: set to 4 (mobile) by default, can be adjusted
      const faqClampLines = 4; // change to 3 for tighter clamp
      document.querySelectorAll('.faq-card p').forEach((p, idx) => {
        if (!window.matchMedia('(max-width: 768px)').matches) return;
        const lineHeight = parseFloat(getComputedStyle(p).lineHeight) || 16;
        const maxHeight = Math.round(lineHeight * faqClampLines);
        if (p.scrollHeight > maxHeight) {
          p.classList.add('faq-clamped');
          p.setAttribute('aria-hidden', 'false');
          // add toggle button if not present
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'faq-readmore';
          btn.setAttribute('aria-expanded', 'false');
          const rid = 'faq-read-' + idx;
          btn.setAttribute('aria-controls', rid);
          p.id = rid + '-text';
          btn.textContent = 'Read more';
          btn.addEventListener('click', () => {
            const expanded = p.classList.toggle('faq-clamped--expanded');
            btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            btn.textContent = expanded ? 'Show less' : 'Read more';
            if (expanded) {
              // smooth scroll to make sure expanded text is visible
              setTimeout(()=> {
                p.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
              }, 60);
            }
          });
          // append after paragraph
          p.insertAdjacentElement('afterend', btn);
        }
      });

    } catch (e) { /* fail silently */ }
  }

  // init after DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => { setupObserver(); setupTilt(); autoApplyClasses(); });
  } else {
    setupObserver(); setupTilt(); autoApplyClasses();
  }
})();
