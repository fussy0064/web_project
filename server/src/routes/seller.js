// routes/seller.js
const db = require('../db');
const { sendJSON, readJSONBody } = require('../http-utils');
const { requireRole } = require('../guards');

/** GET /api/seller/orders - all orders containing this seller's items (admin sees all) */
async function allOrders(req, res) {
  if (!requireRole(req, res, ['seller', 'admin'])) return;

  const seller_id = req.session.user_id;
  let sql = `SELECT o.id, o.created_at, o.total, o.status, u.username as customer_name, u.id as customer_id,
             COUNT(oi.id) as item_count
             FROM orders o
             JOIN order_items oi ON o.id = oi.order_id
             JOIN users u ON o.user_id = u.id`;
  const params = [];

  if (req.session.role === 'seller') {
    sql += ' WHERE oi.seller_id = ?';
    params.push(seller_id);
  }
  sql += ' GROUP BY o.id ORDER BY o.created_at DESC';

  const orders = await db.query(sql, params);
  sendJSON(res, 200, orders);
}

/** GET /api/seller/orders/recent */
async function recentOrders(req, res) {
  if (!requireRole(req, res, ['seller', 'admin'])) return;

  const orders = await db.query(
    `SELECT DISTINCT o.id, o.created_at, o.total, o.status, u.username as customer_name
     FROM orders o
     JOIN order_items oi ON o.id = oi.order_id
     JOIN users u ON o.user_id = u.id
     WHERE oi.seller_id = ?
     ORDER BY o.created_at DESC
     LIMIT 5`,
    [req.session.user_id]
  );
  sendJSON(res, 200, orders);
}

/** GET /api/seller/products - seller's own products (active + inactive), admin sees all */
async function myProducts(req, res) {
  if (!requireRole(req, res, ['seller', 'admin'])) return;

  let sql = `SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id`;
  const params = [];

  if (req.session.role === 'seller') {
    sql += ' WHERE p.seller_id = ?';
    params.push(req.session.user_id);
  }
  sql += ' ORDER BY p.created_at DESC';

  const products = await db.query(sql, params);
  sendJSON(res, 200, products);
}

/** GET /api/seller/dashboard_stats */
async function dashboardStats(req, res) {
  if (!requireRole(req, res, ['seller', 'admin'])) return;

  const seller_id = req.session.user_id;

  const [[productResult]] = [await db.query('SELECT COUNT(*) as my_products FROM products WHERE seller_id = ?', [seller_id])];
  const [[orderResult]] = [await db.query(
    `SELECT COUNT(DISTINCT o.id) as active_orders FROM orders o
     JOIN order_items oi ON o.id = oi.order_id
     WHERE oi.seller_id = ? AND o.status IN ('pending', 'processing')`,
    [seller_id]
  )];
  const [[stockResult]] = [await db.query('SELECT COALESCE(SUM(stock_quantity), 0) as total_stock FROM products WHERE seller_id = ?', [seller_id])];
  const [[revenueResult]] = [await db.query(
    `SELECT COALESCE(SUM(oi.price * oi.quantity), 0) as my_revenue FROM order_items oi
     JOIN orders o ON o.id = oi.order_id
     WHERE oi.seller_id = ? AND o.status = 'delivered'`,
    [seller_id]
  )];

  sendJSON(res, 200, {
    my_products: productResult.my_products || 0,
    active_orders: orderResult.active_orders || 0,
    total_stock: stockResult.total_stock || 0,
    my_revenue: revenueResult.my_revenue || 0,
  });
}

/** GET /api/seller/notifications */
async function notifications(req, res) {
  if (!requireRole(req, res, ['seller', 'admin'])) return;

  const rows = await db.query(
    `SELECT id, title, message, type, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20`,
    [req.session.user_id]
  );
  sendJSON(res, 200, { notifications: rows });
}

/** POST /api/seller/notifications/read-all */
async function markAllNotificationsRead(req, res) {
  if (!requireRole(req, res, ['seller', 'admin'])) return;
  await db.query('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [req.session.user_id]);
  sendJSON(res, 200, { message: 'All notifications marked as read' });
}

/** POST /api/seller/notifications/:id/read */
async function markNotificationRead(req, res, id) {
  if (!requireRole(req, res, ['seller', 'admin'])) return;
  await db.query('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?', [id, req.session.user_id]);
  sendJSON(res, 200, { message: 'Notification marked as read' });
}

/** POST /api/seller/orders/status - update order status (seller must own an item in the order, or admin) */
async function updateOrderStatus(req, res) {
  if (!requireRole(req, res, ['seller', 'admin'])) return;

  let body;
  try {
    body = await readJSONBody(req);
  } catch (e) {
    return sendJSON(res, 400, { message: 'Invalid data' });
  }

  const order_id = body.order_id || 0;
  const status = body.status || '';
  const validStatuses = ['pending', 'processing', 'ready', 'shipped', 'delivered', 'cancelled'];

  if (!order_id || !validStatuses.includes(status)) {
    return sendJSON(res, 400, { message: 'Invalid data' });
  }

  if (req.session.role === 'seller') {
    const rows = await db.query('SELECT 1 FROM order_items WHERE order_id = ? AND seller_id = ?', [order_id, req.session.user_id]);
    if (rows.length === 0) {
      return sendJSON(res, 403, { message: 'Order not found or access denied' });
    }
  }

  await db.query('UPDATE orders SET status = ? WHERE id = ?', [status, order_id]);
  sendJSON(res, 200, { message: 'Order status updated successfully' });
}

module.exports = {
  allOrders,
  recentOrders,
  dashboardStats,
  notifications,
  markAllNotificationsRead,
  markNotificationRead,
  updateOrderStatus,
  myProducts,
};
