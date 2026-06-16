// Centralized API base configuration for the frontend
// Usage: call apiUrl('/api/xyz')

(function () {
  const DEFAULT_API_ORIGIN = 'http://localhost:8000'; // <-- CHANGE THIS ONCE

  // Allow overriding without editing code
  // 1) localStorage: setItem('tc_api_origin', 'http://...')
  // 2) global window var (set before this script runs)
  // 3) fallback default
  function resolveApiOrigin() {
    try {
      const ls = localStorage.getItem('tc_api_origin');
      if (ls && typeof ls === 'string') return ls;
    } catch (_) {}

    if (typeof window !== 'undefined' && window.TC_API_ORIGIN) {
      if (typeof window.TC_API_ORIGIN === 'string') return window.TC_API_ORIGIN;
    }

    return DEFAULT_API_ORIGIN;
  }

  window.TC_API_ORIGIN = resolveApiOrigin();

  window.apiUrl = function apiUrl(path) {
    // ensure path starts with '/'
    const p = (path || '').toString();
    const normalized = p.startsWith('/') ? p : '/' + p;
    return window.TC_API_ORIGIN.replace(/\/$/, '') + normalized;
  };
})();

