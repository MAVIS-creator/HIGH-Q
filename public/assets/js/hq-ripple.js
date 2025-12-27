/**
 * HQ Ripple Effect
 * Mobile/Touch devices only
 * Native-feeling touch feedback
 */
(function() {
  'use strict';
  
  // Only run on mobile/touch devices
  const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  
  if (!isTouchDevice || prefersReducedMotion) {
    return;
  }
  
  const config = {
    rippleDuration: 600, // Duration of ripple animation (ms)
    rippleColor: 'rgba(255, 255, 255, 0.4)',
    maxSize: 300 // Maximum ripple size (px)
  };
  
  function createRipple(e, element) {
    // Remove existing ripples
    const existingRipples = element.querySelectorAll('.hq-ripple-effect');
    existingRipples.forEach(r => r.remove());
    
    // Create ripple element
    const ripple = document.createElement('span');
    ripple.className = 'hq-ripple-effect';
    ripple.style.position = 'absolute';
    ripple.style.borderRadius = '50%';
    ripple.style.background = config.rippleColor;
    ripple.style.pointerEvents = 'none';
    ripple.style.transform = 'translate(-50%, -50%) scale(0)';
    ripple.style.transition = `transform ${config.rippleDuration}ms ease-out, opacity ${config.rippleDuration}ms ease-out`;
    ripple.style.opacity = '1';
    
    // Get click position relative to element
    const rect = element.getBoundingClientRect();
    const x = e.touches ? e.touches[0].clientX : e.clientX;
    const y = e.touches ? e.touches[0].clientY : e.clientY;
    
    ripple.style.left = (x - rect.left) + 'px';
    ripple.style.top = (y - rect.top) + 'px';
    
    // Calculate ripple size (should cover entire element)
    const size = Math.max(rect.width, rect.height, config.maxSize);
    ripple.style.width = size + 'px';
    ripple.style.height = size + 'px';
    
    // Add to element
    element.appendChild(ripple);
    
    // Trigger animation
    requestAnimationFrame(() => {
      ripple.style.transform = 'translate(-50%, -50%) scale(1)';
      ripple.style.opacity = '0';
    });
    
    // Remove after animation
    setTimeout(() => {
      ripple.remove();
    }, config.rippleDuration);
  }
  
  function initRipple() {
    // Auto-add .hq-ripple to buttons
    const buttons = document.querySelectorAll('.btn, button, .card, a[href]');
    
    buttons.forEach(element => {
      if (!element.classList.contains('hq-ripple')) {
        element.classList.add('hq-ripple');
      }
      
      // Ensure element has position
      const computedStyle = window.getComputedStyle(element);
      if (computedStyle.position === 'static') {
        element.style.position = 'relative';
      }
      
      // Ensure overflow is hidden for ripple containment
      if (computedStyle.overflow === 'visible') {
        element.style.overflow = 'hidden';
      }
    });
    
    // Add touch/click listeners
    document.addEventListener('touchstart', function(e) {
      const target = e.target.closest('.hq-ripple');
      if (target) {
        createRipple(e, target);
      }
    }, { passive: true });
    
    // Fallback for click on touch devices
    document.addEventListener('click', function(e) {
      const target = e.target.closest('.hq-ripple');
      if (target && !e.touches) {
        createRipple(e, target);
      }
    });
  }
  
  // Init on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRipple);
  } else {
    initRipple();
  }
  
  // Re-init on dynamic content
  const observer = new MutationObserver(initRipple);
  observer.observe(document.body, { childList: true, subtree: true });
  
})();
