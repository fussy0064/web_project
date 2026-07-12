// guards.js - authentication/authorization helpers used by route handlers
const { sendJSON } = require('./http-utils');

/** Returns true (and does nothing else) if logged in; otherwise sends 401 and returns false. */
function requireLogin(req, res) {
  if (!req.session.user_id) {
    sendJSON(res, 401, { message: 'Unauthorized' });
    return false;
  }
  return true;
}

/** Returns true if the session role is in allowedRoles; otherwise sends 403 and returns false. */
function requireRole(req, res, allowedRoles) {
  if (!req.session.user_id || !allowedRoles.includes(req.session.role)) {
    sendJSON(res, 403, { message: 'Access denied' });
    return false;
  }
  return true;
}

module.exports = { requireLogin, requireRole };
