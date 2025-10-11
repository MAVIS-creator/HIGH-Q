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

    document.querySelectorAll('.hq-reveal-left, .hq-reveal-right, .hq-slide-in').forEach(el => {
      observer.observe(el);
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

  // init after DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => { setupObserver(); setupTilt(); });
  } else {
    setupObserver(); setupTilt();
  }
})();
