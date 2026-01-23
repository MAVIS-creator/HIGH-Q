/**
 * Mobile & Tablet Animation Engine
 * Lightweight, performance-focused animation triggers
 * Uses IntersectionObserver for scroll-triggered reveals
 * No heavy libraries - pure vanilla JS
 */

(function() {
  'use strict';

  // Exit early if IntersectionObserver not supported
  if (typeof IntersectionObserver === 'undefined') {
    console.warn('IntersectionObserver not supported - animations disabled');
    return;
  }

  // Configuration
  const config = {
    // Thresholds for triggering animations (when element is 0% visible)
    threshold: 0.05,
    // Root margin - start animation before element reaches viewport
    rootMargin: '0px 0px -50px 0px',
    // Only animate on mobile/tablet
    enableOnDesktop: true
  };

  /**
   * Initialize animation observer
   * Watches for elements with data-animate attribute
   */
  function initAnimationObserver() {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          // Element is in viewport - trigger animation
          entry.target.classList.add('animated');

          // Optionally unobserve after animation (for better performance)
          // observer.unobserve(entry.target);
        }
      });
    }, {
      threshold: config.threshold,
      rootMargin: config.rootMargin
    });

    // Observe all elements marked for animation
    document.querySelectorAll('[data-animate]').forEach((el) => {
      observer.observe(el);
    });
  }

  /**
   * Detect if device is touch-capable (mobile/tablet)
   */
  function isTouchDevice() {
    return (
      (typeof window !== 'undefined' && 'ontouchstart' in window) ||
      (typeof navigator !== 'undefined' && navigator.maxTouchPoints > 0)
    );
  }

  /**
   * Add animation classes to common elements
   * This automatically animates certain elements without needing markup changes
   */
  function autoApplyAnimations() {
    // Cards
    document.querySelectorAll('.card, [class*="card-"]').forEach((el, idx) => {
      el.setAttribute('data-animate', 'true');
      el.style.animationDelay = `${idx * 0.05}s`;
    });

    // Buttons
    document.querySelectorAll('.btn, [class*="btn-"]').forEach((el) => {
      el.classList.add('animatable-button');
    });

    // Navigation items
    document.querySelectorAll('.nav-item').forEach((el, idx) => {
      el.setAttribute('data-animate', 'true');
      el.style.animationDelay = `${idx * 0.05}s`;
    });

    // List items
    document.querySelectorAll('main ul li, main ol li').forEach((el, idx) => {
      el.setAttribute('data-animate', 'true');
      el.style.animationDelay = `${idx * 0.05}s`;
    });

    // Feature sections
    document.querySelectorAll('[class*="feature"], [class*="section"]').forEach((el) => {
      el.setAttribute('data-animate', 'true');
    });
  }

  /**
   * Enhance button interactions with haptic feedback (if available)
   */
  function setupButtonAnimations() {
    document.querySelectorAll('.btn, [class*="btn-"], button').forEach((button) => {
      button.addEventListener('click', function(e) {
        // Trigger haptic feedback on mobile if available
        if (navigator.vibrate) {
          navigator.vibrate(10); // 10ms vibration
        }

        // Create ripple effect (optional)
        createRipple(e, this);
      });

      // Active state animation
      button.addEventListener('touchstart', function() {
        this.classList.add('active-touch');
      });

      button.addEventListener('touchend', function() {
        this.classList.remove('active-touch');
      });
    });
  }

  /**
   * Create a ripple effect on click
   */
  function createRipple(event, element) {
    const rect = element.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = event.clientX - rect.left - size / 2;
    const y = event.clientY - rect.top - size / 2;

    const ripple = document.createElement('span');
    ripple.style.width = ripple.style.height = size + 'px';
    ripple.style.left = x + 'px';
    ripple.style.top = y + 'px';
    ripple.classList.add('ripple');

    // Remove ripple after animation
    ripple.addEventListener('animationend', () => ripple.remove());

    element.appendChild(ripple);
  }

  /**
   * Pause animations when user scrolls quickly
   * Reduce animation jank during fast scrolling
   */
  function setupScrollOptimization() {
    let ticking = false;
    let isScrolling = false;

    window.addEventListener('scroll', () => {
      if (!ticking) {
        window.requestAnimationFrame(() => {
          if (!isScrolling) {
            document.documentElement.classList.add('is-scrolling');
            isScrolling = true;
          }

          // Stop marking as scrolling after scroll ends
          clearTimeout(window.scrollTimeout);
          window.scrollTimeout = setTimeout(() => {
            document.documentElement.classList.remove('is-scrolling');
            isScrolling = false;
          }, 150);

          ticking = false;
        });
        ticking = true;
      }
    }, { passive: true });
  }

  /**
   * Performance monitoring (development only)
   */
  function setupPerformanceMonitoring() {
    if (window.location.search.includes('debug-animations')) {
      console.log('🎬 Mobile Animations Debug Mode Enabled');
      console.log('Device:', isTouchDevice() ? 'Touch' : 'Non-touch');
      console.log('Viewport:', window.innerWidth + 'x' + window.innerHeight);
      console.log('Animations will trigger on scroll');

      // Log animation triggers
      document.addEventListener('animationstart', (e) => {
        console.log('▶ Animation started:', e.animationName);
      }, true);
    }
  }

  /**
   * Initialize on DOM ready
   */
  function init() {
    // Add page-loading class to trigger initial animations
    document.documentElement.classList.add('page-loading');

    // Auto-apply animations to common elements
    autoApplyAnimations();

    // Setup animation observer for scroll reveals
    initAnimationObserver();

    // Setup button interactions
    setupButtonAnimations();

    // Optimize during scroll
    setupScrollOptimization();

    // Debug mode
    setupPerformanceMonitoring();

    // Remove page-loading class after initial animations
    window.addEventListener('load', () => {
      setTimeout(() => {
        document.documentElement.classList.remove('page-loading');
      }, 1000);
    });

    console.log('✅ Mobile Animations initialized');
  }

  // Run when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();

/**
 * Style utilities for scroll optimization
 * Add this to your CSS:
 *
 * html.is-scrolling * {
 *   animation-play-state: paused !important;
 * }
 *
 * This pauses animations during fast scrolling to improve performance
 */
