// routes/user.js
const bcrypt = require('bcryptjs');
const db = require('../db');
const { sendJSON, readJSONBody } = require('../http-utils');

async function logAction(userId, action, description) {
  try {
    await db.query(
      'INSERT INTO system_logs (user_id, action, description) VALUES (?, ?, ?)',
      [userId, action, description]
    );
  } catch (e) {}
}

/** GET /api/user/profile */
async function getProfile(req, res) {
  if (!req.session.user_id) return sendJSON(res, 401, { message: 'Unauthorized' });

  const rows = await db.query(
    'SELECT id, username, email, role, created_at, status FROM users WHERE id = ?',
    [req.session.user_id]
  );
  if (rows.length === 0) return sendJSON(res, 404, { message: 'User not found' });

  sendJSON(res, 200, rows[0]);
}

/** PUT /api/user/profile - update own email/password */
async function updateProfile(req, res) {
  if (!req.session.user_id) return sendJSON(res, 401, { message: 'Unauthorized' });

  let body;
  try {
    body = await readJSONBody(req);
  } catch (e) {
    return sendJSON(res, 400, { message: 'Invalid JSON input' });
  }

  const { email, password } = body;
  const updates = [];
  const params = [];

  if (email) {
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      return sendJSON(res, 400, { message: 'Invalid email format' });
    }
    const existing = await db.query('SELECT id FROM users WHERE email = ? AND id != ?', [email, req.session.user_id]);
    if (existing.length > 0) {
      return sendJSON(res, 409, { message: 'Email already in use' });
    }
    updates.push('email = ?');
    params.push(email);
  }

  if (password) {
    if (password.length < 6) {
      return sendJSON(res, 400, { message: 'Password must be at least 6 characters' });
    }
    updates.push('password_hash = ?');
    params.push(await bcrypt.hash(password, 10));
  }

  if (updates.length === 0) {
    return sendJSON(res, 200, { message: 'No changes provided' });
  }

  params.push(req.session.user_id);
  await db.query(`UPDATE users SET ${updates.join(', ')} WHERE id = ?`, params);

  if (email) req.session.email = email;
  await logAction(req.session.user_id, 'profile_update', 'User updated profile');

  sendJSON(res, 200, { message: 'Profile updated successfully' });
}

/** GET /api/user/stats */
async function stats(req, res) {
  if (!req.session.user_id) return sendJSON(res, 401, { message: 'Unauthorized' });
  const user_id = req.session.user_id;

  const [[orderResult]] = [await db.query('SELECT COUNT(*) as order_count FROM orders WHERE user_id = ?', [user_id])];
  const [[cartResult]] = [await db.query('SELECT COUNT(*) as cart_count FROM cart WHERE user_id = ?', [user_id])];
  const [[spentResult]] = [await db.query("SELECT COALESCE(SUM(total), 0) as total_spent FROM orders WHERE user_id = ? AND status != 'cancelled'", [user_id])];
  const [[pendingResult]] = [await db.query("SELECT COUNT(*) as pending_count FROM orders WHERE user_id = ? AND status = 'pending'", [user_id])];

  sendJSON(res, 200, {
    order_count: Number(orderResult.order_count) || 0,
    cart_items: Number(cartResult.cart_count) || 0,
    total_spent: parseFloat(spentResult.total_spent) || 0,
    pending_orders: Number(pendingResult.pending_count) || 0,
  });
}

module.exports = { getProfile, updateProfile, stats };
