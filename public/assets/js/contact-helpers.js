/* contact-helpers.js
   Shared helpers for contact page interactions (fallbacks)
   - Read more / Show less toggle for FAQ
   - Schedule modal open/close handlers with body scroll lock
*/
(function(){
  if (window.hq_contact_helpers_loaded) return; window.hq_contact_helpers_loaded = true;
  function initReadMore(){
    try{
      var cards = document.querySelectorAll('.faq-card');
      cards.forEach(function(card){
        var clamped = card.querySelector('.faq-clamped');
        if (!clamped) return;
        var btn = card.querySelector('.faq-readmore');
        if (!btn) return;
        if (btn.dataset.hqFaqBound === '1') return;

        btn.dataset.hqFaqBound = '1';
        btn.setAttribute('aria-expanded', 'false');
        btn.classList.remove('expanded');
        card.classList.remove('expanded');
        clamped.classList.remove('faq-clamped--expanded');
        clamped.style.maxHeight = '6.4em';
        clamped.style.overflow = 'hidden';

        requestAnimationFrame(function() {
          var overflows = clamped.scrollHeight > clamped.clientHeight + 8;
          if (!overflows) {
            btn.style.display = 'none';
            return;
          }
          btn.style.display = '';
        });

        btn.addEventListener('click', function(){
          var expanded = btn.getAttribute('aria-expanded') === 'true';
          if (expanded) {
            clamped.classList.remove('faq-clamped--expanded');
            clamped.style.maxHeight = '6.4em';
            clamped.style.overflow = 'hidden';
            card.classList.remove('expanded');
            btn.classList.remove('expanded');
            btn.setAttribute('aria-expanded', 'false');
          } else {
            clamped.classList.add('faq-clamped--expanded');
            clamped.style.maxHeight = clamped.scrollHeight + 'px';
            clamped.style.overflow = 'visible';
            card.classList.add('expanded');
            btn.classList.add('expanded');
            btn.setAttribute('aria-expanded', 'true');
            setTimeout(function(){
              clamped.scrollIntoView({behavior:'smooth', block:'nearest'});
            }, 60);
          }
        });
      });
    }catch(e){console && console.error && console.error('readmore init failed', e);}  
  }

  function initScheduleModal(){
    try{
      var openSchedule = document.getElementById('openSchedule');
      var modal = document.getElementById('modalBackdrop');
      var cancel = document.getElementById('cancelSchedule');
      var confirm = document.getElementById('confirmSchedule');
      function showModal(){ if(!modal) return; modal.classList.add('open'); modal.style.display='flex'; modal.setAttribute('aria-hidden','false'); var inner = modal.querySelector('.modal'); if(inner){ inner.style.transform='none'; inner.style.opacity='1'; inner.style.pointerEvents='auto'; } document.documentElement.style.overflow='hidden'; document.body.style.overflow='hidden'; }
      function hideModal(){ if(!modal) return; modal.classList.remove('open'); modal.style.display='none'; modal.setAttribute('aria-hidden','true'); var inner = modal.querySelector('.modal'); if(inner){ inner.style.transform='translateY(0)'; inner.style.opacity='1'; inner.style.pointerEvents='auto'; } document.documentElement.style.overflow=''; document.body.style.overflow=''; }
      if (openSchedule) { openSchedule.addEventListener('click', function(e){ e.preventDefault(); showModal(); }); openSchedule.addEventListener('keypress', function(e){ if(e.key==='Enter' || e.key===' ') { e.preventDefault(); showModal(); } }); }
      if (cancel) cancel.addEventListener('click', function(e){ e.preventDefault(); hideModal(); });
      if (modal) modal.addEventListener('click', function(e){ if (e.target === modal) hideModal(); });
      document.addEventListener('keydown', function(e){ if (e.key === 'Escape') hideModal(); });
    }catch(e){console && console.error && console.error('schedule init failed', e);}  
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function(){ initReadMore(); initScheduleModal(); }); else { initReadMore(); initScheduleModal(); }
})();
