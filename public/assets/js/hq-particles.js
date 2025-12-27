/**
 * HQ Particles - Neural Network Animation
 * Desktop only (min-width: 1024px)
 * Respects prefers-reduced-motion
 */
(function() {
  'use strict';
  
  // Only run on desktop with no reduced motion preference
  const isDesktop = window.matchMedia('(min-width: 1024px)').matches;
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  
  if (!isDesktop || prefersReducedMotion) {
    return;
  }
  
  // Configuration
  const config = {
    particleCount: 50,
    maxDistance: 150,
    particleSpeed: 0.5,
    particleSize: 2,
    lineWidth: 1,
    particleColor: 'rgba(255, 255, 255, 0.6)',
    lineColor: 'rgba(255, 255, 255, 0.2)'
  };
  
  class Particle {
    constructor(canvas) {
      this.canvas = canvas;
      this.x = Math.random() * canvas.width;
      this.y = Math.random() * canvas.height;
      this.vx = (Math.random() - 0.5) * config.particleSpeed;
      this.vy = (Math.random() - 0.5) * config.particleSpeed;
      this.radius = config.particleSize;
    }
    
    update() {
      this.x += this.vx;
      this.y += this.vy;
      
      // Bounce off edges
      if (this.x < 0 || this.x > this.canvas.width) this.vx *= -1;
      if (this.y < 0 || this.y > this.canvas.height) this.vy *= -1;
      
      // Keep within bounds
      this.x = Math.max(0, Math.min(this.canvas.width, this.x));
      this.y = Math.max(0, Math.min(this.canvas.height, this.y));
    }
    
    draw(ctx) {
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
      ctx.fillStyle = config.particleColor;
      ctx.fill();
    }
  }
  
  class ParticleSystem {
    constructor(container) {
      this.container = container;
      this.canvas = document.createElement('canvas');
      this.canvas.className = 'particles-canvas';
      this.ctx = this.canvas.getContext('2d');
      this.particles = [];
      this.animationId = null;
      
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
      for (let i = 0; i < config.particleCount; i++) {
        this.particles.push(new Particle(this.canvas));
      }
      this.animate();
    }
    
    connectParticles() {
      for (let i = 0; i < this.particles.length; i++) {
        for (let j = i + 1; j < this.particles.length; j++) {
          const dx = this.particles[i].x - this.particles[j].x;
          const dy = this.particles[i].y - this.particles[j].y;
          const distance = Math.sqrt(dx * dx + dy * dy);
          
          if (distance < config.maxDistance) {
            const opacity = 1 - (distance / config.maxDistance);
            this.ctx.beginPath();
            this.ctx.strokeStyle = config.lineColor.replace('0.2', (opacity * 0.2).toString());
            this.ctx.lineWidth = config.lineWidth;
            this.ctx.moveTo(this.particles[i].x, this.particles[i].y);
            this.ctx.lineTo(this.particles[j].x, this.particles[j].y);
            this.ctx.stroke();
          }
        }
      }
    }
    
    animate() {
      this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
      
      // Update and draw particles
      this.particles.forEach(particle => {
        particle.update();
        particle.draw(this.ctx);
      });
      
      // Connect nearby particles
      this.connectParticles();
      
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
  
  // Initialize particles on hero sections
  function initParticles() {
    const heroSections = document.querySelectorAll('.hero, .about-hero, .contact-hero, .courses-hero');
    
    heroSections.forEach(hero => {
      // Check if already initialized
      if (hero.querySelector('.particles-container')) {
        return;
      }
      
      const container = document.createElement('div');
      container.className = 'particles-container';
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
