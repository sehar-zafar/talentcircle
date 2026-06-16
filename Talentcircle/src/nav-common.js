// Shared Navigation JS for TalentCircle - Pure JS (lint fixed)

const NAVBAR_TEMPLATE = `
<nav class="fixed top-0 w-full z-50 px-6 md:px-12 lg:px-16 py-6 border-b border-white/20 bg-black/90 backdrop-blur-md flex items-center justify-between">
  <a href="home.html">
    <img src="images/logo.png" class="w-32 md:w-40 lg:w-[213px]" alt="TalentCircle Logo">
  </a>
  <ul id="desktopNav" class="hidden md:flex space-x-8 lg:space-x-12 text-lg font-medium">
    <li><a href="home.html" class="nav-link hover:text-purple-400 transition-all">Home</a></li>
    <li><a href="about.html" class="nav-link hover:text-purple-400 transition-all">About</a></li>
    <li><a href="features.html" class="nav-link hover:text-purple-400 transition-all">Features</a></li>
    <li><a href="certificates.html" class="nav-link hover:text-purple-400 transition-all">Certificates</a></li>
    <li><a href="get-started.html" class="nav-link hover:text-purple-400 transition-all">Get Started</a></li>
  </ul>
  <div id="authContainer" class="flex items-center space-x-2 lg:space-x-4"></div>
  <button id="mobileBtn" class="md:hidden text-3xl hamburger">☰</button>
</nav>

<div id="mobileNav" class="md:hidden fixed top-[5.5rem] left-0 right-0 bg-black/95 backdrop-blur-md z-40 p-8 text-center space-y-6 hidden">
  <a href="home.html" class="block py-4 text-xl font-semibold hover:text-purple-400 transition-all">Home</a>
  <a href="about.html" class="block py-4 text-xl font-semibold hover:text-purple-400 transition-all">About</a>
  <a href="features.html" class="block py-4 text-xl font-semibold hover:text-purple-400 transition-all">Features</a>
  <a href="certificates.html" class="block py-4 text-xl font-semibold hover:text-purple-400 transition-all">Certificates</a>
  <a href="get-started.html" class="block py-4 text-xl font-semibold hover:text-purple-400 transition-all">Get Started</a>
</div>
`;

const FOOTER_TEMPLATE = `
<footer class="w-full py-12 mt-24 border-t border-white/10 bg-black/50">
  <div class="max-w-6xl mx-auto px-6 text-center">
    <div class="flex flex-wrap justify-center items-center gap-6 mb-6 text-sm md:text-base">
      <a href="home.html" class="hover:text-purple-400 transition-all">Home</a>
      <a href="about.html" class="hover:text-purple-400 transition-all">About</a>
      <a href="features.html" class="hover:text-purple-400 transition-all">Features</a>
      <a href="certificates.html" class="hover:text-purple-400 transition-all">Certificates</a>
      <a href="#" class="hover:text-purple-400 transition-all">Privacy</a>
      <a href="#" class="hover:text-purple-400 transition-all">Terms</a>
    </div>
    <p class="text-gray-500 text-xs">&copy; 2024 Talent Circle. All rights reserved.</p>
  </div>
</footer>
`;

// Init function
function initTalentNav() {
  // Inject navbar if placeholder exists
  const navbarPlaceholder = document.getElementById('navbar-placeholder');
  if (navbarPlaceholder) {
    navbarPlaceholder.outerHTML = NAVBAR_TEMPLATE;
  }

  const token = localStorage.getItem('token');
  const userStr = localStorage.getItem('user') || '{}';
  const user = JSON.parse(userStr);
  const isLoggedIn = token && user.id;
  
  const authContainer = document.getElementById('authContainer');
  if (!authContainer) return console.warn('No authContainer found');

  if (isLoggedIn) {
    authContainer.innerHTML = '<span class="hidden lg:inline text-sm bg-white/20 px-4 py-2 rounded-full font-medium">' + (user.name || 'User') + '</span>' +
      '<a href="chat-list.html#ai-mentor" class="bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-400 hover:to-blue-400 text-sm px-6 py-3 rounded-full font-semibold transition-all mr-2 ai-glow">' +
        '<i class="fas fa-robot mr-1"></i>AI Mentor' +
      '</a>' +
      '<a href="chat-list.html" class="bg-blue-600 hover:bg-blue-500 text-sm px-6 py-3 rounded-full font-medium transition-all lg:inline hidden">' +
        '<i class="fas fa-comments mr-1"></i>Messages' +
      '</a>' +
      '<a href="profile.html" class="bg-purple-600 hover:bg-purple-500 text-sm px-6 py-3 rounded-full font-medium transition-all mr-2">Profile</a>' +
      '<a href="' + (user.role === 'admin' ? 'admin-panel.html' : 'user-panel.html') + '" class="bg-emerald-600 hover:bg-emerald-500 text-sm px-6 py-3 rounded-full font-medium transition-all">Dashboard</a>' +
      '<button onclick="talentLogout()" class="bg-red-500 hover:bg-red-400 text-sm px-6 py-3 rounded-full font-medium transition-all">Logout</button>';
  } else {
    authContainer.innerHTML = '<a href="signin.html" class="bg-gradient-to-r from-purple-600 to-purple-500 hover:from-purple-500 hover:to-purple-400 text-sm px-8 py-3 rounded-full font-medium shadow-lg hover:shadow-xl transition-all cta-glow">Get Started</a>';
  }

  const mobileBtn = document.getElementById('mobileBtn');
  const mobileNav = document.getElementById('mobileNav');
  if (mobileBtn && mobileNav) {
    mobileBtn.onclick = function() { mobileNav.classList.toggle('hidden'); };
    mobileNav.onclick = function(e) {
      if (e.target === mobileNav) mobileNav.classList.add('hidden');
    };
  }

  window.onscroll = function() {
    const nav = document.querySelector('nav');
    nav.style.background = window.scrollY > 100 ? 'rgba(0,0,0,0.95)' : 'rgba(0,0,0,0.90)';
  };
}

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
  let user = {};
  try {
    user = JSON.parse(localStorage.getItem('user') || '{}');
  } catch (e) {
    user = {};
  }
  if (requiredRole && user.role !== requiredRole) {
    location.href = 'user-panel.html';
    return false;
  }
  return true;
}

window.talentNav = { initTalentNav, talentLogout, checkAuth };

if (document.getElementById('authContainer')) {
  window.addEventListener('load', window.talentNav.initTalentNav);
}

