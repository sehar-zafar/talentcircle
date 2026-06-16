// Animated counters for home page stats - black/purple theme compatible
// Usage: add class='counter' data-target='10000' data-suffix='K+' to elements

class Counter {
  constructor() {
    this.init();
  }

  init() {
    const counters = document.querySelectorAll('.counter');
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          this.animateCounter(entry.target);
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.5 });

    counters.forEach(counter => observer.observe(counter));
  }

  animateCounter(element) {
    const target = parseInt(element.getAttribute('data-target') || '0');
    const suffix = element.getAttribute('data-suffix') || '';
    const duration = parseInt(element.getAttribute('data-duration') || '2000');
    let start = 0;
    const increment = target / (duration / 16); // ~60fps
    const timer = setInterval(() => {
      start += increment;
      if (start >= target) {
        start = target;
        clearInterval(timer);
      }
      element.textContent = Math.floor(start).toLocaleString() + suffix;
    }, 16);
    
    // Animate entrance
    element.style.opacity = '0';
    element.style.transform = 'translateY(20px)';
    element.animate([
      { opacity: 0, transform: 'translateY(20px)' },
      { opacity: 1, transform: 'translateY(0)' }
    ], { duration: 800, easing: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)' });
  }
}

// Parallax effect for hero
class Parallax {
  constructor() {
    window.addEventListener('scroll', this.handleScroll.bind(this));
    this.handleScroll();
  }

  handleScroll() {
    const scrolled = window.pageYOffset;
    const parallax = document.querySelector('.parallax-hero');
    if (parallax) {
      parallax.style.transform = `translateY(${scrolled * 0.5}px)`;
    }
  }
}

// Testimonial carousel
class TestimonialCarousel {
  constructor() {
    this.current = 0;
    this.testimonials = document.querySelectorAll('.testimonial-item');
    if (this.testimonials.length > 0) {
      this.initCarousel();
    }
  }

  initCarousel() {
    setInterval(() => {
      this.testimonials[this.current].classList.remove('active');
      this.current = (this.current + 1) % this.testimonials.length;
      this.testimonials[this.current].classList.add('active');
    }, 5000);
  }
}

// Featured works hover effects
function initFeaturedWorks() {
  const works = document.querySelectorAll('.work-item');
  works.forEach(work => {
    work.addEventListener('mouseenter', () => {
      work.querySelector('.work-overlay').style.opacity = '1';
      work.style.transform = 'scale(1.05) translateY(-10px)';
    });
    work.addEventListener('mouseleave', () => {
      work.querySelector('.work-overlay').style.opacity = '0';
      work.style.transform = 'scale(1) translateY(0)';
    });
  });
}

// 3D Globe (simple CSS/JS version)
function initGlobe() {
  const canvas = document.getElementById('globe-canvas');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  canvas.width = 400;
  canvas.height = 400;
  const centerX = canvas.width / 2;
  const centerY = canvas.height / 2;
  const radius = 150;

  function drawGlobe() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    // Globe glow
    const gradient = ctx.createRadialGradient(centerX, centerY, 0, centerX, centerY, radius);
    gradient.addColorStop(0, 'rgba(138, 43, 226, 0.6)');
    gradient.addColorStop(0.5, 'rgba(138, 43, 226, 0.2)');
    gradient.addColorStop(1, 'rgba(138, 43, 226, 0)');
    ctx.fillStyle = gradient;
    ctx.beginPath();
    ctx.arc(centerX, centerY, radius, 0, Math.PI * 2);
    ctx.fill();

    // Globe surface
    ctx.fillStyle = 'rgba(255, 255, 255, 0.1)';
    ctx.beginPath();
    ctx.arc(centerX, centerY, radius - 10, 0, Math.PI * 2);
    ctx.fill();

    // Continents/lines (rotating)
    ctx.save();
    ctx.translate(centerX, centerY);
    ctx.rotate(Date.now() * 0.0005);
    ctx.strokeStyle = 'rgba(255, 255, 255, 0.3)';
    ctx.lineWidth = 2;
    ctx.beginPath();
    for (let i = 0; i < 12; i++) {
      const angle = (i / 12) * Math.PI * 2;
      ctx.moveTo(0, 0);
      ctx.lineTo(Math.cos(angle) * (radius - 20), Math.sin(angle) * (radius - 20));
    }
    ctx.stroke();
    ctx.restore();

    // Shine
    const shine = ctx.createRadialGradient(centerX - 50, centerY - 50, 0, centerX - 50, centerY - 50, 80);
    shine.addColorStop(0, 'rgba(255, 255, 255, 0.6)');
    shine.addColorStop(1, 'rgba(255, 255, 255, 0)');
    ctx.fillStyle = shine;
    ctx.beginPath();
    ctx.arc(centerX - 50, centerY - 50, 80, 0, Math.PI * 2);
    ctx.fill();

    requestAnimationFrame(drawGlobe);
  }
  drawGlobe();
}

// Init all on load
document.addEventListener('DOMContentLoaded', () => {
  new Counter();
  new Parallax();
  new TestimonialCarousel();
  initFeaturedWorks();
  initGlobe();
});

