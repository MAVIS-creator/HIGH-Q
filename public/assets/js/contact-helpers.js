/* contact-helpers.js
   Shared helpers for contact page interactions (fallbacks)
   - Read more / Show less toggle for FAQ
   - Schedule modal open/close handlers with body scroll lock
*/
(function(){
  if (window.hq_contact_helpers_loaded) return; window.hq_contact_helpers_loaded = true;
  function initReadMore(){
    try{
      var clampLines = 4;
      var cards = document.querySelectorAll('.faq-card p');
      cards.forEach(function(p, idx){
        if (p.nextElementSibling && p.nextElementSibling.classList && p.nextElementSibling.classList.contains('faq-readmore')) return;
        var style = window.getComputedStyle(p);
        var lineHeight = parseFloat(style.lineHeight) || parseFloat(style.fontSize)*1.2 || 16;
        var maxH = Math.round(lineHeight * clampLines);
        if (p.scrollHeight > maxH + 2) {
          p.classList.add('faq-clamped');
          p.style.maxHeight = maxH + 'px';
          p.style.overflow = 'hidden';
          var btn = document.createElement('button');
          btn.type = 'button'; btn.className = 'faq-readmore'; btn.setAttribute('aria-expanded','false'); btn.textContent = 'Read more';
          btn.addEventListener('click', function(){
            var expanded = btn.getAttribute('aria-expanded') === 'true';
            if (!expanded) { p.style.maxHeight=''; p.classList.add('faq-clamped--expanded'); btn.setAttribute('aria-expanded','true'); btn.textContent='Show less'; setTimeout(function(){ p.scrollIntoView({behavior:'smooth', block:'nearest'}); },60); }
            else { p.style.maxHeight = maxH + 'px'; p.classList.remove('faq-clamped--expanded'); btn.setAttribute('aria-expanded','false'); btn.textContent='Read more'; }
          });
          p.insertAdjacentElement('afterend', btn);
        }
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
