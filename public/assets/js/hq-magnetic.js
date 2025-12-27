/**
 * HQ Magnetic Buttons
 * Desktop only (min-width: 1024px) with hover capability
 * Subtle magnetic pull effect on mouse movement
 */
(function() {
  'use strict';
  
  // Only run on desktop with hover and no reduced motion
  const isDesktop = window.matchMedia('(min-width: 1024px) and (hover: hover)').matches;
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  
  if (!isDesktop || prefersReducedMotion) {
    return;
  }
  
  const config = {
    magneticDistance: 60, // Distance at which magnetic effect starts (px)
    maxPull: 12, // Maximum pull distance (px)
    smoothing: 0.2, // Easing factor (0-1, lower = smoother)
    resetDuration: 400 // Duration to return to center (ms)
  };
  
  class MagneticButton {
    constructor(element) {
      this.element = element;
      this.currentX = 0;
      this.currentY = 0;
      this.targetX = 0;
      this.targetY = 0;
      this.animationId = null;
      
      this.element.style.transition = '';
      
      this.boundMouseMove = this.onMouseMove.bind(this);
      this.boundMouseLeave = this.onMouseLeave.bind(this);
      
      this.element.addEventListener('mousemove', this.boundMouseMove);
      this.element.addEventListener('mouseleave', this.boundMouseLeave);
    }
    
    onMouseMove(e) {
      const rect = this.element.getBoundingClientRect();
      const centerX = rect.left + rect.width / 2;
      const centerY = rect.top + rect.height / 2;
      const mouseX = e.clientX;
      const mouseY = e.clientY;
      
      // Calculate distance from center
      const deltaX = mouseX - centerX;
      const deltaY = mouseY - centerY;
      const distance = Math.sqrt(deltaX * deltaX + deltaY * deltaY);
      
      // Only apply magnetic effect if within range
      if (distance < config.magneticDistance) {
        const strength = Math.max(0, 1 - (distance / config.magneticDistance));
        this.targetX = (deltaX / distance) * config.maxPull * strength;
        this.targetY = (deltaY / distance) * config.maxPull * strength;
      } else {
        this.targetX = 0;
        this.targetY = 0;
      }
      
      if (!this.animationId) {
        this.animate();
      }
    }
    
    onMouseLeave() {
      this.targetX = 0;
      this.targetY = 0;
      
      // Smooth return to center
      if (Math.abs(this.currentX) > 0.1 || Math.abs(this.currentY) > 0.1) {
        if (!this.animationId) {
          this.animate();
        }
      } else {
        this.element.style.transform = 'translate(0, 0)';
      }
    }
    
    animate() {
      // Smooth interpolation
      this.currentX += (this.targetX - this.currentX) * config.smoothing;
      this.currentY += (this.targetY - this.currentY) * config.smoothing;
      
      // Apply transform
      this.element.style.transform = `translate(${this.currentX}px, ${this.currentY}px)`;
      
      // Continue animating if not at rest
      if (Math.abs(this.targetX - this.currentX) > 0.1 || Math.abs(this.targetY - this.currentY) > 0.1) {
        this.animationId = requestAnimationFrame(() => this.animate());
      } else {
        this.animationId = null;
      }
    }
    
    destroy() {
      this.element.removeEventListener('mousemove', this.boundMouseMove);
      this.element.removeEventListener('mouseleave', this.boundMouseLeave);
      
      if (this.animationId) {
        cancelAnimationFrame(this.animationId);
      }
    }
  }
  
  // Initialize magnetic effect on all .hq-magnetic elements
  function initMagnetic() {
    const magneticElements = document.querySelectorAll('.hq-magnetic');
    const instances = [];
    
    magneticElements.forEach(element => {
      instances.push(new MagneticButton(element));
    });
    
    return instances;
  }
  
  // Auto-add .hq-magnetic class to primary CTAs
  function autoAddMagnetic() {
    const ctaButtons = document.querySelectorAll('.btn-primary, .btn-cta, .btn-lg.btn-primary');
    
    ctaButtons.forEach(btn => {
      if (!btn.classList.contains('hq-magnetic')) {
        btn.classList.add('hq-magnetic');
      }
    });
  }
  
  // Init on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      autoAddMagnetic();
      initMagnetic();
    });
  } else {
    autoAddMagnetic();
    initMagnetic();
  }
  
  // Re-init on dynamic content
  const observer = new MutationObserver(() => {
    autoAddMagnetic();
    initMagnetic();
  });
  
  observer.observe(document.body, { childList: true, subtree: true });
  
})();
