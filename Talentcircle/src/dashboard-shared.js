// Shared persisted state helpers for connecting dashboard pages.
// Loaded by HTML pages via <script src="src/dashboard-shared.js"></script>.

(function (global) {
  const KEY = 'talentcircle.state.v1';

  function safeJsonParse(str, fallback) {
    try { return JSON.parse(str); } catch { return fallback; }
  }

  function defaultState() {
    return {
      version: 1,
      matchedProfileIds: [],
      notifications: [],
      sessions: [], // {id,date,type,partner,title,time}
      reviewsHelpful: [], // {reviewId}
      // Aggregate counters (optional, but helpful)
      aggregates: {
        unreadCount: 0,
        totalSessions: 0,
        placements: 0,
        candidates: 0,
        avgRating: null
      }
    };
  }

  function getState() {
    const raw = localStorage.getItem(KEY);
    if (!raw) return defaultState();
    const s = safeJsonParse(raw, defaultState());
    // shallow merge to ensure new keys exist
    return { ...defaultState(), ...(s || {}) };
  }

  function setState(next) {
    localStorage.setItem(KEY, JSON.stringify(next));
  }

  function uid(prefix = 'n') {
    return `${prefix}_${Date.now()}_${Math.floor(Math.random() * 1e6)}`;
  }

  function bumpUnreadCount(state) {
    const unread = (state.notifications || []).filter(n => n.unread).length;
    state.aggregates = state.aggregates || {};
    state.aggregates.unreadCount = unread;
  }

  function pushNotification(type, payload) {
    const state = getState();
    state.notifications = state.notifications || [];

    const n = {
      id: uid('notif'),
      type,
      unread: true,
      priority: !!payload.priority,
      createdAt: Date.now(),
      title: payload.title || 'Notification',
      body: payload.body || '',
      time: payload.time || 'Just now',
      meta: payload.meta || {}
    };

    state.notifications.unshift(n);
    bumpUnreadCount(state);
    setState(state);
    return n;
  }

  function markAllNotificationsRead() {
    const state = getState();
    (state.notifications || []).forEach(n => { n.unread = false; });
    bumpUnreadCount(state);
    setState(state);
  }

  function dismissNotificationById(id) {
    const state = getState();
    const idx = (state.notifications || []).findIndex(n => n.id === id);
    if (idx >= 0) state.notifications.splice(idx, 1);
    bumpUnreadCount(state);
    setState(state);
  }

  function setNotificationRead(id) {
    const state = getState();
    const n = (state.notifications || []).find(x => x.id === id);
    if (n) n.unread = false;
    bumpUnreadCount(state);
    setState(state);
  }

  function getUnreadCount() {
    const state = getState();
    return (state.aggregates && state.aggregates.unreadCount) != null
      ? state.aggregates.unreadCount
      : (state.notifications || []).filter(n => n.unread).length;
  }

  function toggleMatchProfile(profileId) {
    const state = getState();
    const ids = new Set(state.matchedProfileIds || []);
    const already = ids.has(profileId);
    if (already) ids.delete(profileId); else ids.add(profileId);
    state.matchedProfileIds = Array.from(ids);
    setState(state);
    return !already; // true if newly matched
  }

  function isProfileMatched(profileId) {
    const state = getState();
    return (state.matchedProfileIds || []).includes(profileId);
  }

  function ensureAggregatesDefaults() {
    const state = getState();
    state.aggregates = state.aggregates || {};
    bumpUnreadCount(state);
    setState(state);
  }

  // Expose
  global.TalentCircleShared = {
    KEY,
    getState,
    setState,
    pushNotification,
    markAllNotificationsRead,
    dismissNotificationById,
    setNotificationRead,
    getUnreadCount,
    toggleMatchProfile,
    isProfileMatched,
    ensureAggregatesDefaults
  };
})(window);

