/**
 * HQ Particles - Floating Bubble Animation
 * Works on ALL pages including home.php
 * Home.php uses white/gold bubbles (yellow bg)
 * Other pages use yellow bubbles (blue bg)
 * Respects prefers-reduced-motion
 */
(function() {
  'use strict';
  
  // Reduced motion check
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  
  if (prefersReducedMotion) {
    return;
  }
  
  // Configuration - Different colors for different pages
  const configYellow = {
    particleCount: 35,
    particleSpeed: 0.15,
    minParticleSize: 6,
    maxParticleSize: 20,
    particleColor: 'rgba(255, 215, 0, 0.35)', // Yellow with transparency
    particleGradient: 'rgba(255, 215, 0, 0.55)', // Brighter yellow for gradient
    borderColor: 'rgba(255, 215, 0, 0.25)'
  };
  
  // For home.php (yellow background) - use white/light gold bubbles
  const configHome = {
    particleCount: 30,
    particleSpeed: 0.12,
    minParticleSize: 5,
    maxParticleSize: 18,
    particleColor: 'rgba(255, 255, 255, 0.25)', // White with transparency
    particleGradient: 'rgba(255, 255, 255, 0.4)', // Brighter white
    borderColor: 'rgba(255, 255, 255, 0.2)'
  };
  
  class Particle {
    constructor(canvas, config) {
      this.canvas = canvas;
      this.config = config;
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
      this.y += this.vy - 0.08; // Slight upward drift
      this.wobble += this.wobbleSpeed;
      
      // Add gentle wobble
      this.x += Math.sin(this.wobble) * 0.08;
      
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
      
      gradient.addColorStop(0, this.config.particleGradient);
      gradient.addColorStop(1, this.config.particleColor);
      
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
      ctx.fillStyle = gradient;
      ctx.fill();
      
      // Subtle border
      ctx.strokeStyle = this.config.borderColor;
      ctx.lineWidth = 1;
      ctx.stroke();
    }
  }
  
  class ParticleSystem {
    constructor(container, config) {
      this.container = container;
      this.config = config;
      this.canvas = document.createElement('canvas');
      this.canvas.className = 'particles-canvas';
      this.canvas.style.cssText = `
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 1;
      `;
      this.ctx = this.canvas.getContext('2d');
      this.particles = [];
      this.animationId = null;
      
      this.container.style.position = 'relative';
      this.container.appendChild(this.canvas);
      
      // Initial resize after a brief delay to ensure layout is complete
      requestAnimationFrame(() => {
        this.resize();
        this.init();
      });
      
      // Handle resize with debounce
      let resizeTimeout;
      window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => this.resize(), 100);
      });
    }
    
    resize() {
      // Use getBoundingClientRect for more accurate sizing
      const rect = this.container.getBoundingClientRect();
      const width = rect.width || this.container.offsetWidth || 800;
      const height = rect.height || this.container.offsetHeight || 400;
      
      this.canvas.width = width;
      this.canvas.height = height;
      
      // Re-initialize particles if they exist with new bounds
      if (this.particles.length > 0) {
        this.particles.forEach(p => {
          if (p.x > width) p.x = Math.random() * width;
          if (p.y > height) p.y = Math.random() * height;
          p.canvas = this.canvas;
        });
      }
    }
    
    init() {
      // Clear existing particles
      this.particles = [];
      
      for (let i = 0; i < this.config.particleCount; i++) {
        this.particles.push(new Particle(this.canvas, this.config));
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
  
  // Check if this is the home page
  function isHomePage() {
    const path = window.location.pathname.toLowerCase();
    return path.endsWith('home.php') || 
           path.endsWith('/high-q/') || 
           path.endsWith('/high-q') ||
           path === '/' ||
           document.body.classList.contains('page-home');
  }
  
  // Initialize particles on hero sections
  function initParticles() {
    const isHome = isHomePage();
    
    // Get all hero sections
    const allHeros = document.querySelectorAll('.hero, .about-hero, .contact-hero, .courses-hero, .path-hero, .register-hero');
    
    if (allHeros.length === 0) {
      // No hero sections found, retry after a short delay
      setTimeout(initParticles, 100);
      return;
    }
    
    allHeros.forEach(hero => {
      // Check if already initialized
      if (hero.querySelector('.particles-container')) {
        return;
      }
      
      // Ensure the hero has position relative for absolute positioning of particles
      const computedStyle = window.getComputedStyle(hero);
      if (computedStyle.position === 'static') {
        hero.style.position = 'relative';
      }
      
      // Determine which config to use
      const isMainHomeHero = isHome && hero.classList.contains('hero');
      const config = isMainHomeHero ? configHome : configYellow;
      
      const container = document.createElement('div');
      container.className = 'particles-container';
      container.style.cssText = `
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1;
        pointer-events: none;
        overflow: hidden;
      `;
      
      // Insert at the beginning of the hero
      if (hero.firstChild) {
        hero.insertBefore(container, hero.firstChild);
      } else {
        hero.appendChild(container);
      }
      
      new ParticleSystem(container, config);
    });
  }
  
  // Initialize with proper timing
  function init() {
    // Wait a tick to ensure CSS is applied
    requestAnimationFrame(() => {
      initParticles();
    });
  }
  
  // Init on DOM ready or window load (whichever is appropriate)
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else if (document.readyState === 'interactive') {
    // DOM is ready but resources may not be loaded
    init();
  } else {
    // Document is complete
    init();
  }
  
  // Also try on window load as a fallback for late-loading pages
  window.addEventListener('load', () => {
    setTimeout(initParticles, 200);
  });
  
})();
