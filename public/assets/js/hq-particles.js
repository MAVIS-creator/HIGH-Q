/**
 * HQ Particles - Floating Bubble Animation
 * All pages except home.php hero (min-width: 768px)
 * Respects prefers-reduced-motion
 */
(function() {
  'use strict';
  
  // Reduced motion check
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  
  if (prefersReducedMotion) {
    return;
  }
  
  // Configuration - Bubble effects
  const config = {
    particleCount: 40,
    particleSpeed: 0.2,
    minParticleSize: 8,
    maxParticleSize: 25,
    particleColor: 'rgba(255, 215, 0, 0.4)', // Yellow with transparency
    particleGradient: 'rgba(255, 215, 0, 0.6)' // Brighter yellow for gradient
  };
  
  class Particle {
    constructor(canvas) {
      this.canvas = canvas;
      this.x = Math.random() * canvas.width;
      this.y = Math.random() * canvas.height;
      this.radius = Math.random() * (config.maxParticleSize - config.minParticleSize) + config.minParticleSize;
      this.vx = (Math.random() - 0.5) * config.particleSpeed;
      this.vy = (Math.random() - 0.5) * config.particleSpeed;
      this.opacity = Math.random() * 0.4 + 0.2; // 0.2 to 0.6
      this.wobble = Math.random() * Math.PI * 2;
      this.wobbleSpeed = Math.random() * 0.02 + 0.01;
    }
    
    update() {
      // Gentle floating motion
      this.x += this.vx;
      this.y += this.vy - 0.1; // Slight upward drift
      this.wobble += this.wobbleSpeed;
      
      // Add gentle wobble
      this.x += Math.sin(this.wobble) * 0.1;
      
      // Wrap around screen
      if (this.x < -this.radius) this.x = this.canvas.width + this.radius;
      if (this.x > this.canvas.width + this.radius) this.x = -this.radius;
      if (this.y < -this.radius) this.y = this.canvas.height + this.radius;
      if (this.y > this.canvas.height + this.radius) this.y = -this.radius;
    }
    
    draw(ctx) {
      // Draw bubble with gradient
      const gradient = ctx.createRadialGradient(
        this.x - this.radius * 0.3,
        this.y - this.radius * 0.3,
        0,
        this.x,
        this.y,
        this.radius
      );
      
      gradient.addColorStop(0, config.particleGradient.replace('0.6', '0.8'));
      gradient.addColorStop(1, config.particleColor);
      
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
      ctx.fillStyle = gradient;
      ctx.fill();
      
      // Optional: subtle border
      ctx.strokeStyle = 'rgba(255, 215, 0, 0.3)';
      ctx.lineWidth = 1;
      ctx.stroke();
    }
  }
  
  class ParticleSystem {
    constructor(container) {
      this.container = container;
      this.canvas = document.createElement('canvas');
      this.canvas.className = 'particles-canvas';
      this.canvas.style.position = 'absolute';
      this.canvas.style.top = '0';
      this.canvas.style.left = '0';
      this.canvas.style.pointerEvents = 'none';
      this.canvas.style.zIndex = '1';
      this.ctx = this.canvas.getContext('2d');
      this.particles = [];
      this.animationId = null;
      
      this.container.style.position = 'relative';
      this.container.appendChild(this.canvas);
      this.resize();
      this.init();
      
      // Handle resize
      window.addEventListener('resize', () => this.resize());
    }
    
    resize() {
      this.canvas.width = this.container.offsetWidth;
      this.canvas.height = this.container.offsetHeight;
    }
    
    init() {
      // Clear existing particles
      this.particles = [];
      
      for (let i = 0; i < config.particleCount; i++) {
        this.particles.push(new Particle(this.canvas));
      }
      this.animate();
    }
    
    animate() {
      this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
      
      // Update and draw particles
      this.particles.forEach(particle => {
        particle.update();
        particle.draw(this.ctx);
      });
      
      this.animationId = requestAnimationFrame(() => this.animate());
    }
    
    destroy() {
      if (this.animationId) {
        cancelAnimationFrame(this.animationId);
      }
      if (this.canvas && this.canvas.parentNode) {
        this.canvas.parentNode.removeChild(this.canvas);
      }
    }
  }
  
  // Initialize particles on hero sections (except home.php main hero)
  function initParticles() {
    // Get all hero sections
    const allHeros = document.querySelectorAll('.hero, .about-hero, .contact-hero, .courses-hero');
    
    allHeros.forEach(hero => {
      // SKIP home.php main hero (check if body has 'home' class or if hero is first on page)
      const isHomePage = document.body.classList.contains('page-home') || 
                         (hero.classList.contains('hero') && 
                          hero === document.querySelector('.hero'));
      const isMainHero = isHomePage && hero.classList.contains('hero');
      
      if (isMainHero) {
        console.log('Skipping particles on home.php main hero');
        return; // Skip home hero
      }
      
      // Check if already initialized
      if (hero.querySelector('.particles-container')) {
        return;
      }
      
      const container = document.createElement('div');
      container.className = 'particles-container';
      container.style.position = 'absolute';
      container.style.top = '0';
      container.style.left = '0';
      container.style.width = '100%';
      container.style.height = '100%';
      container.style.zIndex = '1';
      container.style.pointerEvents = 'none';
      
      hero.style.position = 'relative';
      hero.insertBefore(container, hero.firstChild);
      
      new ParticleSystem(container);
    });
  }
  
  // Lazy init on scroll or immediate if hero is in viewport
  let initialized = false;
  
  function checkAndInit() {
    if (initialized) return;
    
    const heroSections = document.querySelectorAll('.hero, .about-hero, .contact-hero, .courses-hero');
    const hasHeroInViewport = Array.from(heroSections).some(hero => {
      // Skip home main hero
      const isHomePage = document.body.classList.contains('page-home') || 
                         (hero.classList.contains('hero') && 
                          hero === document.querySelector('.hero'));
      const isMainHero = isHomePage && hero.classList.contains('hero');
      
      if (isMainHero) return false;
      
      const rect = hero.getBoundingClientRect();
      return rect.top < window.innerHeight && rect.bottom > 0;
    });
    
    if (hasHeroInViewport) {
      initialized = true;
      initParticles();
      window.removeEventListener('scroll', checkAndInit);
    }
  }
  
  // Init on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', checkAndInit);
  } else {
    checkAndInit();
  }
  
  // Also check on scroll
  window.addEventListener('scroll', checkAndInit, { passive: true });
  
})();
