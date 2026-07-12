// routes/auth.js
const bcrypt = require('bcryptjs');
const db = require('../db');
const { sendJSON, readJSONBody } = require('../http-utils');
const { saveSession, destroySession } = require('../session');

function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

async function logAction(userId, action, description) {
  try {
    await db.query(
      'INSERT INTO system_logs (user_id, action, description) VALUES (?, ?, ?)',
      [userId, action, description]
    );
  } catch (e) {
    // logging failures should never break the request
  }
}

async function login(req, res) {
  if (req.method !== 'POST') return sendJSON(res, 405, { message: 'Method not allowed' });

  let body;
  try {
    body = await readJSONBody(req);
  } catch (e) {
    return sendJSON(res, 400, { message: 'Invalid JSON input' });
  }

  const email = body.email || '';
  const password = body.password || '';

  if (!email || !password) {
    return sendJSON(res, 400, { message: 'Email and password are required' });
  }

  const rows = await db.query(
    'SELECT id, username, email, password_hash, role FROM users WHERE email = ?',
    [email]
  );

  if (rows.length !== 1) {
    return sendJSON(res, 401, { message: 'Invalid credentials' });
  }

  const user = rows[0];
  const ok = await bcrypt.compare(password, user.password_hash);

  if (!ok) {
    return sendJSON(res, 401, { message: 'Invalid credentials' });
  }

  req.session.user_id = user.id;
  req.session.username = user.username;
  req.session.email = user.email;
  req.session.role = user.role;
  saveSession(req);

  await logAction(user.id, 'login', 'User logged in successfully');

  sendJSON(res, 200, {
    message: 'Login successful',
    user: { id: user.id, username: user.username, email: user.email, role: user.role },
  });
}

async function register(req, res) {
  if (req.method !== 'POST') return sendJSON(res, 405, { message: 'Method not allowed' });

  let body;
  try {
    body = await readJSONBody(req);
  } catch (e) {
    return sendJSON(res, 400, { message: 'Invalid JSON input' });
  }

  const { username = '', email = '', password = '' } = body;

  if (!username || !email || !password) {
    return sendJSON(res, 400, { message: 'All fields are required' });
  }
  if (!isValidEmail(email)) {
    return sendJSON(res, 400, { message: 'Invalid email format' });
  }
  if (password.length < 6) {
    return sendJSON(res, 400, { message: 'Password must be at least 6 characters' });
  }

  const existing = await db.query(
    'SELECT id FROM users WHERE email = ? OR username = ?',
    [email, username]
  );
  if (existing.length > 0) {
    return sendJSON(res, 409, { message: 'Username or email already exists' });
  }

  const hashed = await bcrypt.hash(password, 10);
  const result = await db.query(
    "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'customer')",
    [username, email, hashed]
  );

  await logAction(result.insertId, 'registration', 'New user registered');

  sendJSON(res, 201, { message: 'Registration successful', user_id: result.insertId });
}

async function logout(req, res) {
  destroySession(req, res);
  sendJSON(res, 200, { message: 'Logged out successfully' });
}

async function checkSession(req, res) {
  if (!req.session.user_id) {
    return sendJSON(res, 200, { authenticated: false });
  }

  const rows = await db.query(
    'SELECT id, username, email, role, status FROM users WHERE id = ?',
    [req.session.user_id]
  );
  const user = rows[0];

  if (!user) {
    destroySession(req, res);
    return sendJSON(res, 200, { authenticated: false });
  }

  sendJSON(res, 200, {
    authenticated: true,
    user: { id: user.id, username: user.username, email: user.email, role: user.role },
    role: user.role,
  });
}

module.exports = { login, register, logout, checkSession };
