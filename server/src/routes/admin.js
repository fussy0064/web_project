// routes/admin.js
const bcrypt = require('bcryptjs');
const db = require('../db');
const { sendJSON, readJSONBody } = require('../http-utils');
const { requireRole } = require('../guards');

async function logAction(userId, action, description) {
  try {
    await db.query(
      'INSERT INTO system_logs (user_id, action, description) VALUES (?, ?, ?)',
      [userId, action, description]
    );
  } catch (e) {}
}

/** GET /api/admin/dashboard_stats */
async function dashboardStats(req, res) {
  if (!requireRole(req, res, ['admin'])) return;

  res.setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
  res.setHeader('Pragma', 'no-cache');

  const [[userResult]] = [await db.query('SELECT COUNT(*) as total_users FROM users')];
  const [[productResult]] = [await db.query('SELECT COUNT(*) as total_products FROM products')];
  const [[orderCountResult]] = [await db.query("SELECT COUNT(*) as total_orders FROM orders WHERE status != 'cancelled'")];
  const [[revenueResult]] = [await db.query("SELECT COALESCE(SUM(total), 0) as total_revenue FROM orders WHERE status = 'delivered'")];
  const [[sellerResult]] = [await db.query("SELECT COUNT(*) as active_sellers FROM users WHERE role = 'seller' AND status = 'active'")];
  const [[todayResult]] = [await db.query("SELECT COALESCE(SUM(total), 0) as daily_revenue FROM orders WHERE DATE(created_at) = CURDATE() AND status = 'delivered'")];
  const [[yesterdayResult]] = [await db.query('SELECT COUNT(*) as yesterday_users FROM users WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)')];
  const [[logResult]] = [await db.query('SELECT COUNT(*) as total_logs FROM system_logs WHERE DATE(created_at) = CURDATE()')];

  const userGrowth = yesterdayResult.yesterday_users > 0
    ? Math.round(((userResult.total_users - yesterdayResult.yesterday_users) / yesterdayResult.yesterday_users) * 10000) / 100
    : 0;

  sendJSON(res, 200, {
    total_users: userResult.total_users || 0,
    total_products: productResult.total_products || 0,
    total_orders: orderCountResult.total_orders || 0,
    total_revenue: revenueResult.total_revenue || 0,
    active_sellers: sellerResult.active_sellers || 0,
    daily_revenue: todayResult.daily_revenue || 0,
    user_growth: userGrowth,
    product_growth: 5,
    order_growth: 12,
    seller_growth: 2,
    system_health: 98,
    total_logs: logResult.total_logs || 0,
  });
}

/** GET /api/admin/users */
async function getUsers(req, res) {
  if (!requireRole(req, res, ['admin'])) return;

  const users = await db.query(
    `SELECT id, username, email, role, status, created_at FROM users WHERE status = 'active' ORDER BY created_at DESC`
  );
  users.forEach((u) => { u.is_admin = u.role === 'admin'; });
  sendJSON(res, 200, users);
}

/** POST /api/admin/users - create a new user */
async function createUser(req, res) {
  if (!requireRole(req, res, ['admin'])) return;

  let body;
  try {
    body = await readJSONBody(req);
  } catch (e) {
    return sendJSON(res, 400, { message: 'Invalid JSON input' });
  }

  const { username = '', email = '', password = '' } = body;
  const validRoles = ['admin', 'seller', 'customer'];
  const role = validRoles.includes(body.role) ? body.role : 'customer';

  if (!username || !email || !password) {
    return sendJSON(res, 400, { message: 'Missing required fields' });
  }

  const existing = await db.query('SELECT id FROM users WHERE email = ? OR username = ?', [email, username]);
  if (existing.length > 0) {
    return sendJSON(res, 409, { message: 'User already exists' });
  }

  const password_hash = await bcrypt.hash(password, 10);
  const result = await db.query(
    'INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)',
    [username, email, password_hash, role]
  );

  await logAction(req.session.user_id, 'create_user', `Created user ${username} (${role})`);

  sendJSON(res, 200, { message: 'User created successfully', user_id: result.insertId });
}

/** DELETE /api/admin/users/:id - soft delete user + their products */
async function deleteUser(req, res, id) {
  if (!requireRole(req, res, ['admin'])) return;

  const user_id = parseInt(id, 10);
  if (user_id === req.session.user_id) {
    return sendJSON(res, 400, { message: 'Cannot delete yourself' });
  }

  const conn = await db.getConnection();
  try {
    await conn.beginTransaction();

    const [existing] = await conn.query('SELECT id FROM users WHERE id = ?', [user_id]);
    if (existing.length === 0) {
      await conn.rollback();
      return sendJSON(res, 404, { message: 'User not found' });
    }

    await conn.query("UPDATE users SET status = 'inactive' WHERE id = ?", [user_id]);
    const [prodResult] = await conn.query("UPDATE products SET status = 'inactive' WHERE seller_id = ?", [user_id]);

    await conn.query(
      'INSERT INTO system_logs (user_id, action, description) VALUES (?, ?, ?)',
      [req.session.user_id, 'deactivate_user', `Deactivated user ID ${user_id} and ${prodResult.affectedRows} products`]
    );

    await conn.commit();
    sendJSON(res, 200, { message: 'User and their products deactivated successfully' });
  } catch (e) {
    await conn.rollback();
    sendJSON(res, 500, { message: 'Database error: ' + e.message });
  } finally {
    conn.release();
  }
}

/** POST /api/admin/users/:id/role - change a user's role */
async function promoteUser(req, res, id) {
  if (!requireRole(req, res, ['admin'])) return;

  let body;
  try {
    body = await readJSONBody(req);
  } catch (e) {
    return sendJSON(res, 400, { message: 'Invalid data' });
  }

  const user_id = parseInt(id, 10);
  const role = body.role || body.new_role || '';

  if (!user_id || !['admin', 'seller', 'customer'].includes(role)) {
    return sendJSON(res, 400, { message: 'Invalid data' });
  }
  if (user_id === req.session.user_id) {
    return sendJSON(res, 400, { message: 'Cannot modify your own role' });
  }

  await db.query('UPDATE users SET role = ? WHERE id = ?', [role, user_id]);
  await logAction(req.session.user_id, 'role_change', `Changed user role to ${role}`);

  sendJSON(res, 200, { message: 'User role updated successfully' });
}

/** GET /api/admin/orders - all orders with items */
async function getOrders(req, res) {
  if (!requireRole(req, res, ['admin'])) return;

  const orders = await db.query(
    `SELECT o.*, u.username as customer_name, u.email as customer_email, COUNT(oi.id) as item_count
     FROM orders o
     JOIN users u ON o.user_id = u.id
     LEFT JOIN order_items oi ON o.id = oi.order_id
     GROUP BY o.id
     ORDER BY o.created_at DESC`
  );

  for (const order of orders) {
    order.items = await db.query(
      `SELECT oi.*, p.name as product_name, p.image_url FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?`,
      [order.id]
    );
  }

  sendJSON(res, 200, orders);
}

/** POST /api/admin/stock - add stock to a product */
async function addStock(req, res) {
  if (!requireRole(req, res, ['admin'])) return;

  let body;
  try {
    body = await readJSONBody(req);
  } catch (e) {
    return sendJSON(res, 400, { message: 'Invalid data' });
  }

  const product_id = body.product_id || 0;
  const quantity = body.quantity || 0;
  const notes = body.notes || '';

  if (!product_id || quantity <= 0) {
    return sendJSON(res, 400, { message: 'Invalid product or quantity' });
  }

  const rows = await db.query('SELECT stock_quantity, seller_id FROM products WHERE id = ?', [product_id]);
  if (rows.length === 0) {
    return sendJSON(res, 404, { message: 'Product not found' });
  }

  const previous_quantity = rows[0].stock_quantity;
  const new_quantity = previous_quantity + Number(quantity);

  await db.query('UPDATE products SET stock_quantity = ? WHERE id = ?', [new_quantity, product_id]);

  try {
    await db.query(
      `INSERT INTO inventory_logs (product_id, user_id, type, quantity, previous_quantity, new_quantity, notes)
       VALUES (?, ?, 'add', ?, ?, ?, ?)`,
      [product_id, req.session.user_id, quantity, previous_quantity, new_quantity, notes]
    );
  } catch (e) {}

  try {
    await db.query(
      `INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Stock Added', ?, 'system')`,
      [rows[0].seller_id, `Admin added ${quantity} units to your product`]
    );
  } catch (e) {}

  sendJSON(res, 200, { message: 'Stock added successfully' });
}

module.exports = { dashboardStats, getUsers, createUser, deleteUser, promoteUser, getOrders, addStock };
