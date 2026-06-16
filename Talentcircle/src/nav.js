// Unified Navigation JS for TalentCircle - Merged nav-common.js + nav.js (lint fixed)
// Paste NAVBAR_TEMPLATE/FOOTER_TEMPLATE into pages, then add <script src="./src/nav.js"></script>
// Call talentNav.initTalentNav() at end of page script

const NAVBAR_TEMPLATE = `
<nav id="mainNav" class="fixed top-0 w-full z-50 px-6 md:px-12 lg:px-16 py-6 border-b border-white/20 bg-black/90 backdrop-blur-md flex items-center justify-between">
  <a href="home.html">
    <img src="images/logo.png" class="w-32 md:w-40 lg:w-[213px]" alt="TalentCircle Logo">
  </a>
  <ul id="desktopNav" class="hidden md:flex space-x-8 lg:space-x-12 text-lg font-medium">
    <li><a href="home.html" class="nav-link hover:text-purple-400 transition-all duration-300">Home</a></li>
    <li><a href="about.html" class="nav-link hover:text-purple-400 transition-all duration-300">About</a></li>
    <li><a href="features.html" class="nav-link hover:text-purple-400 transition-all duration-300">Features</a></li>
    <li><a href="certificates.html" class="nav-link hover:text-purple-400 transition-all duration-300">Certificates</a></li>
    <li><a href="get-started.html" class="nav-link hover:text-purple-400 transition-all duration-300">Get Started</a></li>
  </ul>
  <div id="authContainer" class="flex items-center space-x-2 lg:space-x-4"></div>
  <button id="mobileBtn" class="md:hidden text-3xl">☰</button>
</nav>

<div id="mobileNav" class="md:hidden fixed top-[5.5rem] left-0 right-0 bg-black/95 backdrop-blur-md z-40 p-8 text-center space-y-6 hidden">
  <a href="home.html" class="block py-4 text-xl font-semibold hover:text-purple-400 transition-all duration-300">Home</a>
  <a href="about.html" class="block py-4 text-xl font-semibold hover:text-purple-400 transition-all duration-300">About</a>
  <a href="features.html" class="block py-4 text-xl font-semibold hover:text-purple-400 transition-all duration-300">Features</a>
  <a href="certificates.html" class="block py-4 text-xl font-semibold hover:text-purple-400 transition-all duration-300">Certificates</a>
  <a href="get-started.html" class="block py-4 text-xl font-semibold hover:text-purple-400 transition-all duration-300">Get Started</a>
</div>`;

const FOOTER_TEMPLATE = `
<footer class="w-full py-12 border-t border-white/10 bg-black/50">
  <div class="max-w-6xl mx-auto px-6 text-center text-gray-400 text-sm md:text-base">
    <div class="flex flex-wrap justify-center gap-6 mb-6">
      <a href="home.html" class="hover:text-purple-400 transition-all duration-300">Home</a>
      <a href="about.html" class="hover:text-purple-400 transition-all duration-300">About</a>
      <a href="features.html" class="hover:text-purple-400 transition-all duration-300">Features</a>
      <a href="certificates.html" class="hover:text-purple-400 transition-all duration-300">Certificates</a>
      <a href="#" class="hover:text-purple-400 transition-all duration-300">Privacy</a>
      <a href="#" class="hover:text-purple-400 transition-all duration-300">Terms</a>
    </div>
    <p>&copy; 2024 Talent Circle. All rights reserved.</p>
  </div>
</footer>`;

// Main init function
function initTalentNav() {
  const token = localStorage.getItem('token');
  const userStr = localStorage.getItem('user') || '{}';
  const user = JSON.parse(userStr);
  const isLoggedIn = token && user.id;

  const authContainer = document.getElementById('authContainer');
  if (!authContainer) {
    console.warn('No authContainer found - navbar not initialized');
    return;
  }

  if (isLoggedIn) {
    authContainer.innerHTML = `
      <span class="hidden lg:inline text-sm bg-white/20 px-4 py-2 rounded-full font-medium">${user.name || 'User'}</span>
      <a href="chat.html" class="bg-blue-600 hover:bg-blue-500 text-sm px-6 py-3 rounded-full font-medium transition-all lg:inline hidden"><i class="fas fa-comments mr-1"></i>Chat</a>
      <a href="dashboard.html" class="bg-emerald-600 hover:bg-emerald-500 text-sm px-6 py-3 rounded-full font-medium transition-all duration-300">Dashboard</a>
      <button onclick="talentLogout()" class="bg-red-500 hover:bg-red-400 text-sm px-6 py-3 rounded-full font-medium transition-all duration-300">Logout</button>
    `;
  } else {
    authContainer.innerHTML = `
      <a href="signin.html" class="bg-gradient-to-r from-purple-600 to-purple-500 hover:from-purple-500 hover:to-purple-400 text-sm px-8 py-3 rounded-full font-medium shadow-lg hover:shadow-xl transition-all duration-300 cta-glow">Get Started</a>
    `;
  }

  // Mobile toggle
  const mobileBtn = document.getElementById('mobileBtn');
  const mobileNav = document.getElementById('mobileNav');
  if (mobileBtn && mobileNav) {
    mobileBtn.onclick = () => mobileNav.classList.toggle('hidden');
    mobileNav.onclick = (e) => {
      if (e.target === mobileNav) mobileNav.classList.add('hidden');
    };
  }

  // Navbar scroll effect
  window.onscroll = () => {
    const nav = document.querySelector('#mainNav');
    if (nav) nav.style.background = window.scrollY > 100 ? 'rgba(0,0,0,0.95)' : 'rgba(0,0,0,0.90)';
  };
}

// Global functions
function talentLogout() {
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  location.reload();
}

function checkAuth(requiredRole = null) {
  const token = localStorage.getItem('token');
  if (!token) {
    location.href = 'signin.html';
    return false;
  }
  const user = JSON.parse(localStorage.getItem('user') || '{}');
  if (requiredRole && user.role !== requiredRole) {
    location.href = user.role === 'admin' ? 'admin-panel.html' : 'user-panel.html';
    return false;
  }
  return true;
}

// Expose globally
window.talentNav = {
  initTalentNav,
  talentLogout,
  checkAuth 
};

// Auto-init if elements exist
if (document.getElementById('authContainer')) {
  window.addEventListener('load', window.talentNav.initTalentNav);
}

