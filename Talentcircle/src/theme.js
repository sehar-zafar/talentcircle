// Black Theme Manager & Enhancements
class BlackThemeManager {
  constructor() {
    this.initTheme();
    this.initParticles();
    this.initDarkToggle();
  }

  initTheme() {
    const savedTheme = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);
    document.documentElement.classList.add('dark');
  }

  toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme');
    const newTheme = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
  }

  initDarkToggle() {
    const toggle = document.getElementById('themeToggle');
    if (toggle) {
      toggle.addEventListener('click', () => this.toggleTheme());
    }
  }

  // Matrix Rain Particles
  initParticles() {
    const particlesContainer = document.createElement('div');
    particlesContainer.className = 'particles fixed inset-0 pointer-events-none z-10';
    particlesContainer.id = 'particles';
    document.body.appendChild(particlesContainer);

    this.createParticles(50);
  }

  createParticles(count) {
    const container = document.getElementById('particles');
    for (let i = 0; i < count; i++) {
      const particle = document.createElement('div');
      particle.className = 'particle';
      particle.style.left = Math.random() * 100 + '%';
      particle.style.animationDuration = (Math.random() * 20 + 15) + 's';
      particle.style.animationDelay = Math.random() * 5 + 's';
      container.appendChild(particle);
    }
  }

  // Update Navbar Auth
  updateAuthNavbar() {
    const token = localStorage.getItem('token');
    const authBtns = document.querySelectorAll('#authBtn');
    authBtns.forEach(btn => {
      if (token) {
        btn.textContent = 'Profile';
        btn.href = 'profile-v2.html';
      } else {
        btn.textContent = 'Get Started';
        btn.href = 'signin.html';
      }
    });
  }
}

// Intersection Observer for animations
function initScrollAnimations() {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('animate');
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.section-reveal, .stagger-list, .card-hover').forEach(el => {
    observer.observe(el);
  });
}

// Navbar scroll effect
function initNavbarEffect() {
  window.addEventListener('scroll', () => {
    const nav = document.querySelector('nav');
    if (window.scrollY > 100) {
      nav?.classList.add('shadow-lg');
    } else {
      nav?.classList.remove('shadow-lg');
    }
  });
}

// Stats Counter Animation
function animateCounters() {
  const counters = document.querySelectorAll('.stats-counter');
  counters.forEach(counter => {
    const target = parseInt(counter.getAttribute('data-target'));
    const increment = target / 100;
    let current = 0;
    
    const updateCounter = () => {
      if (current < target) {
        current += increment;
        counter.textContent = Math.floor(current) + '+';
        requestAnimationFrame(updateCounter);
      } else {
        counter.textContent = target + '+';
      }
    };
    
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          updateCounter();
          observer.unobserve(entry.target);
        }
      });
    });
    observer.observe(counter);
  });
}

// Initialize everything
document.addEventListener('DOMContentLoaded', () => {
  new BlackThemeManager();
  initScrollAnimations();
  initNavbarEffect();
  animateCounters();
  
  // Update auth on load
  setTimeout(() => new BlackThemeManager().updateAuthNavbar(), 100);
});

// Navbar Dropdown Click Handler
function initDropdowns() {
  const navItems = document.querySelectorAll('.unique-nav-item');
  navItems.forEach(item => {
    item.addEventListener('click', (e) => {
      e.stopPropagation();
      e.preventDefault();
      const dropdown = item.querySelector('.unique-dropdown');
      const isActive = item.classList.contains('active-dropdown');
      
      // Close all other dropdowns
      document.querySelectorAll('.unique-nav-item').forEach(i => {
        i.classList.remove('active-dropdown');
      });
      
      if (!isActive) {
        item.classList.add('active-dropdown');
      }
    });
  });

  // Close on outside click
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.unique-nav-item')) {
      document.querySelectorAll('.unique-nav-item').forEach(i => {
        i.classList.remove('active-dropdown');
      });
    }
  });
}

// Mobile menu toggle
function toggleMobileMenu() {
  const menu = document.getElementById('mobileMenu');
  menu?.classList.toggle('hidden');
}

// Initialize everything
document.addEventListener('DOMContentLoaded', () => {
  new BlackThemeManager();
  initScrollAnimations();
  initNavbarEffect();
  animateCounters();
  initDropdowns();  // Add dropdown init
  
  // Update auth on load
  setTimeout(() => new BlackThemeManager().updateAuthNavbar(), 100);
});

