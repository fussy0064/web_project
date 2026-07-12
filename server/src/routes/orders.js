// routes/orders.js
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

/** POST /api/orders - place a new order (logged-in users only) */
async function create(req, res) {
  if (!req.session.user_id) {
    return sendJSON(res, 401, { message: 'Please login to place order' });
  }

  let body;
  try {
    body = await readJSONBody(req);
  } catch (e) {
    return sendJSON(res, 400, { message: 'Invalid order data' });
  }

  const user_id = req.session.user_id;
  let items = body.items || [];
  items = items.map((item) => ({ ...item, id: item.id ?? item.product_id }));

  const subtotal = body.subtotal || 0;
  const shipping = body.shipping || 0;
  const tax = body.tax || 0;
  const total = body.total || 0;
  const shipping_address = body.shipping_address || '';
  const phone = body.phone || '';
  const city = body.city || '';
  const payment_method = body.payment_method || 'COD';

  if (items.length === 0 || !shipping_address) {
    return sendJSON(res, 400, { message: 'Invalid order data' });
  }

  const order_number = `ORD-${new Date().toISOString().slice(0, 10).replace(/-/g, '')}-${String(Math.floor(Math.random() * 9999) + 1).padStart(4, '0')}`;

  const conn = await db.getConnection();
  try {
    await conn.beginTransaction();

    const [orderResult] = await conn.query(
      `INSERT INTO orders (user_id, order_number, subtotal, shipping, tax, total, shipping_address, phone, city, payment_method)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [user_id, order_number, subtotal, shipping, tax, total, shipping_address, phone, city, payment_method]
    );
    const order_id = orderResult.insertId;

    for (const item of items) {
      const [productRows] = await conn.query('SELECT seller_id, price FROM products WHERE id = ?', [item.id]);
      const product = productRows[0];
      if (!product) continue;

      const item_total = item.price * item.quantity;

      await conn.query(
        `INSERT INTO order_items (order_id, product_id, seller_id, quantity, price, total) VALUES (?, ?, ?, ?, ?, ?)`,
        [order_id, item.id, product.seller_id, item.quantity, item.price, item_total]
      );

      await conn.query('UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?', [item.quantity, item.id]);

      await conn.query(
        `INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'New Order', ?, 'new_order')`,
        [product.seller_id, `New order #${order_id} received`]
      );
    }

    await conn.query('DELETE FROM cart WHERE user_id = ?', [user_id]);
    await conn.query(
      'INSERT INTO system_logs (user_id, action, description) VALUES (?, ?, ?)',
      [user_id, 'order_create', `Created order #${order_id}`]
    );

    await conn.commit();

    sendJSON(res, 200, {
      message: 'Order placed successfully',
      order: { id: order_id, order_number, total },
    });
  } catch (e) {
    await conn.rollback();
    sendJSON(res, 500, { message: 'Order failed: ' + e.message });
  } finally {
    conn.release();
  }
}

/** GET /api/orders - own order history, or all orders for admin (?all=true) */
async function history(req, res, query) {
  if (!req.session.user_id) {
    return sendJSON(res, 401, { message: 'Unauthorized' });
  }

  const user_id = req.session.user_id;
  const role = req.session.role || 'customer';

  let orders;
  if (role === 'admin' && query.all === 'true') {
    orders = await db.query(
      `SELECT o.*, u.username as customer_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC`
    );
  } else {
    orders = await db.query('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC', [user_id]);
  }

  for (const order of orders) {
    order.items = await db.query(
      `SELECT oi.*, p.name as product_name, p.image_url FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?`,
      [order.id]
    );
  }

  sendJSON(res, 200, orders);
}

/** DELETE /api/orders/:id - admin only, removes order and its items */
async function remove(req, res, id) {
  if (!req.session.user_id || req.session.role !== 'admin') {
    return sendJSON(res, 403, { message: 'Access denied. Admin only.' });
  }

  const orderId = parseInt(id, 10);
  const conn = await db.getConnection();

  try {
    await conn.beginTransaction();
    await conn.query('DELETE FROM order_items WHERE order_id = ?', [orderId]);
    await conn.query('DELETE FROM orders WHERE id = ?', [orderId]);
    await conn.query(
      'INSERT INTO system_logs (user_id, action, description) VALUES (?, ?, ?)',
      [req.session.user_id, 'order_delete', `Deleted order #${orderId}`]
    );
    await conn.commit();
    sendJSON(res, 200, { message: 'Order deleted successfully' });
  } catch (e) {
    await conn.rollback();
    sendJSON(res, 500, { message: 'Error deleting order: ' + e.message });
  } finally {
    conn.release();
  }
}

module.exports = { create, history, remove };
