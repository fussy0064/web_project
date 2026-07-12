// session.js - minimal cookie-based session store (replaces PHP $_SESSION)
const { parseCookies, serializeCookie, newId } = require('./http-utils');

const COOKIE_NAME = 'sid';
const MAX_AGE_SECONDS = 60 * 60 * 24 * 7; // 7 days

// sessionId -> { user_id, username, email, role, expiresAt }
const store = new Map();

// Periodically clear expired sessions so the Map doesn't grow forever.
setInterval(() => {
  const now = Date.now();
  for (const [id, sess] of store.entries()) {
    if (sess.expiresAt < now) store.delete(id);
  }
}, 60 * 60 * 1000).unref();

/** Attach `req.session` (plain object) and `req.sessionId`. Call once per request. */
function loadSession(req, res) {
  const cookies = parseCookies(req);
  let sid = cookies[COOKIE_NAME];
  let session = sid ? store.get(sid) : null;

  if (session && session.expiresAt < Date.now()) {
    store.delete(sid);
    session = null;
  }

  if (!session) {
    sid = newId();
    session = { expiresAt: Date.now() + MAX_AGE_SECONDS * 1000 };
    store.set(sid, session);
    res.setHeader('Set-Cookie', serializeCookie(COOKIE_NAME, sid, {
      maxAge: MAX_AGE_SECONDS,
      sameSite: 'Lax',
    }));
  }

  req.sessionId = sid;
  req.session = session;
}

/** Persist changes made to req.session and refresh its expiry. */
function saveSession(req) {
  req.session.expiresAt = Date.now() + MAX_AGE_SECONDS * 1000;
  store.set(req.sessionId, req.session);
}

/** Destroy the session entirely (logout). */
function destroySession(req, res) {
  store.delete(req.sessionId);
  res.setHeader('Set-Cookie', serializeCookie(COOKIE_NAME, '', { maxAge: 0 }));
}

module.exports = { loadSession, saveSession, destroySession };
