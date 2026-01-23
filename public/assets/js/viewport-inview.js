// viewport-inview.js
// Marks card-like elements with .in-view when they enter the viewport.
(function(){
  var selectors = [
    '.program-card', '.tutor-card', '.value-card', '.post-card', '.card', 
    '.stat', '.achievement', '.sidebar-card', '.register-sidebar .card',
    '.testimonial-mini', '.wall-testimony-card', '.video-card', '.feature-card',
    '.ceo-card', '.ceo-quote', '.ceo-heading', '.hero-left', '.hero-right',
    '.news-card', '.footer-about', '.footer-links', '.footer-contact'
  ];
  var nodes = [];
  selectors.forEach(function(sel){ document.querySelectorAll(sel).forEach(function(n){ nodes.push(n); }); });
  if (!nodes.length) return;
  function markInView(el){ if(!el.classList.contains('in-view')) el.classList.add('in-view'); }
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function(entries){
      entries.forEach(function(ent){ if (ent.isIntersecting) { markInView(ent.target); try { io.unobserve(ent.target); } catch(e){} } });
    }, { root: null, rootMargin: '0px 0px -8% 0px', threshold: 0.06 });
    nodes.forEach(function(n){ io.observe(n); });
  } else {
    var isSmall = window.innerWidth <= 768;
    if (isSmall) {
      nodes.forEach(function(n){ markInView(n); });
    } else {
      window.addEventListener('load', function(){ setTimeout(function(){ nodes.forEach(markInView); }, 240); });
    }
  }
  try { if (!document.documentElement.classList.contains('is-loaded')) { requestAnimationFrame(function(){ requestAnimationFrame(function(){ document.documentElement.classList.add('is-loaded'); }); }); } } catch(e){}
})();
