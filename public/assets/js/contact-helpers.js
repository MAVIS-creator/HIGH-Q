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
      var cards = document.querySelectorAll('.faq-card');
      cards.forEach(function(card){
        var clamped = card.querySelector('.faq-clamped');
        if (!clamped) return;

        var content = clamped.querySelector('p') || clamped;
        var btn = card.querySelector('.faq-readmore');
        var readText;
        var lessText;
        var createdButton = false;

        if (!btn) {
          btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'faq-readmore';
          btn.innerHTML = '<span class="read-text">Read more</span><span class="less-text">Show less</span>';
          clamped.insertAdjacentElement('afterend', btn);
          createdButton = true;
        }

        if (btn.dataset.hqFaqBound === '1') return;

        readText = btn.querySelector('.read-text');
        lessText = btn.querySelector('.less-text');
        if (!readText || !lessText) {
          btn.innerHTML = '<span class="read-text">Read more</span><span class="less-text">Show less</span>';
        }

        var style = window.getComputedStyle(content);
        var lineHeight = parseFloat(style.lineHeight) || parseFloat(style.fontSize)*1.2 || 16;
        var maxH = Math.round(lineHeight * clampLines);

        if (content.scrollHeight > maxH + 2) {
          clamped.style.maxHeight = maxH + 'px';
          clamped.style.overflow = 'hidden';
          btn.setAttribute('aria-expanded', 'false');
          btn.classList.remove('expanded');
          btn.dataset.hqFaqBound = '1';

          btn.addEventListener('click', function(){
            var expanded = btn.getAttribute('aria-expanded') === 'true';
            clamped.classList.toggle('faq-clamped--expanded', !expanded);
            card.classList.toggle('expanded', !expanded);
            btn.classList.toggle('expanded', !expanded);
            btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            clamped.style.maxHeight = expanded ? (maxH + 'px') : '';

            if (!expanded) {
              setTimeout(function(){
                clamped.scrollIntoView({behavior:'smooth', block:'nearest'});
              }, 60);
            }
          });
        } else {
          card.classList.remove('expanded');
          clamped.style.maxHeight = '';
          clamped.style.overflow = '';
          btn.style.display = 'none';
          if (createdButton) {
            btn.setAttribute('hidden', 'hidden');
          }
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
