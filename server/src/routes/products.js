// routes/products.js
const db = require('../db');
const { sendJSON, readJSONBody } = require('../http-utils');
const { requireRole } = require('../guards');
const { parseMultipart } = require('../upload');

async function logAction(userId, action, description) {
  try {
    await db.query(
      'INSERT INTO system_logs (user_id, action, description) VALUES (?, ?, ?)',
      [userId, action, description]
    );
  } catch (e) {}
}

/** GET /api/products?category=&search=&location=&min_price=&max_price= */
async function list(req, res, query) {
  let sql = `SELECT p.*, u.username as seller_name, c.name as category_name
             FROM products p
             JOIN users u ON p.seller_id = u.id
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.status = 'active'`;
  const params = [];

  if (query.category && query.category !== 'all') {
    sql += ' AND p.category_id = ?';
    params.push(query.category);
  }
  if (query.location && query.location !== 'all') {
    sql += ' AND p.city = ?';
    params.push(query.location);
  }
  if (query.min_price) {
    sql += ' AND p.price >= ?';
    params.push(query.min_price);
  }
  if (query.max_price) {
    sql += ' AND p.price <= ?';
    params.push(query.max_price);
  }
  if (query.search) {
    sql += ' AND (p.name LIKE ? OR p.description LIKE ? OR p.brand LIKE ?)';
    const like = `%${query.search}%`;
    params.push(like, like, like);
  }

  sql += ' ORDER BY p.created_at DESC';

  const products = await db.query(sql, params);
  products.forEach((p) => {
    p.is_available = p.stock_quantity > 0 ? 1 : 0;
    p.price = parseFloat(p.price);
    p.stock_quantity = parseInt(p.stock_quantity, 10);
  });

  sendJSON(res, 200, products);
}

/** GET /api/products/:id */
async function getOne(req, res, id) {
  const productId = parseInt(id, 10);
  if (!productId || productId <= 0) {
    return sendJSON(res, 400, { message: 'Invalid product ID' });
  }

  const rows = await db.query(
    `SELECT p.*, c.name as category_name, u.username as seller_name, u.email as seller_email
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     LEFT JOIN users u ON p.seller_id = u.id
     WHERE p.id = ?`,
    [productId]
  );

  if (rows.length === 0) {
    return sendJSON(res, 404, { message: 'Product not found' });
  }

  sendJSON(res, 200, rows[0]);
}

/** POST /api/products (seller or admin) - JSON or multipart with optional image */
async function create(req, res) {
  if (!requireRole(req, res, ['seller', 'admin'])) return;

  const contentType = req.headers['content-type'] || '';
  let fields = {};
  let imagePath = null;

  try {
    if (contentType.includes('multipart/form-data')) {
      const parsed = await parseMultipart(req);
      fields = parsed.fields;
      imagePath = parsed.imagePath;
    } else {
      fields = await readJSONBody(req);
    }
  } catch (e) {
    return sendJSON(res, 400, { message: e.message || 'Invalid request body' });
  }

  const seller_id = fields.seller_id || req.session.user_id;
  const name = fields.name || '';
  const category_id = fields.category_id || 0;
  const brand = fields.brand || '';
  const model = fields.model || '';
  const description = fields.description || '';
  const price = parseFloat(fields.price || 0);
  const stock_quantity = parseInt(fields.stock_quantity || 0, 10);
  const condition = fields.condition || 'New';
  const warranty = fields.warranty || 'No warranty';
  const image_url = imagePath || fields.image_url || '';

  if (!name || !description || !(price > 0)) {
    return sendJSON(res, 400, { message: 'Required fields missing or invalid' });
  }

  try {
    const result = await db.query(
      `INSERT INTO products (seller_id, name, category_id, brand, model, description, price, stock_quantity, image_url, \`condition\`, warranty)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [seller_id, name, category_id, brand, model, description, price, stock_quantity, image_url, condition, warranty]
    );

    await logAction(seller_id, 'product_create', `Created product: ${name}`);

    sendJSON(res, 200, { message: 'Product created successfully', product_id: result.insertId });
  } catch (e) {
    sendJSON(res, 500, { message: 'Failed to create product: ' + e.message });
  }
}

/** PUT /api/products/:id (seller who owns it, or admin) - JSON or multipart with optional image */
async function update(req, res, id) {
  const productId = parseInt(id, 10);
  if (!req.session.user_id) return sendJSON(res, 401, { message: 'Unauthorized' });

  if (req.session.role === 'seller') {
    const owned = await db.query('SELECT id FROM products WHERE id = ? AND seller_id = ?', [productId, req.session.user_id]);
    if (owned.length === 0) {
      return sendJSON(res, 403, { message: 'Access denied: You can only update your own products' });
    }
  } else if (req.session.role !== 'admin') {
    return sendJSON(res, 403, { message: 'Access denied' });
  }

  const contentType = req.headers['content-type'] || '';
  let data = {};

  try {
    if (contentType.includes('multipart/form-data')) {
      const parsed = await parseMultipart(req);
      data = parsed.fields;
      if (parsed.imagePath) data.image_url = parsed.imagePath;
    } else {
      data = await readJSONBody(req);
    }
  } catch (e) {
    return sendJSON(res, 400, { message: e.message || 'Invalid request body' });
  }

  const allowedFields = ['name', 'category_id', 'brand', 'model', 'description', 'price', 'stock_quantity', 'image_url', 'condition', 'warranty', 'status'];
  const setClauses = [];
  const params = [];

  for (const field of allowedFields) {
    if (data[field] !== undefined) {
      setClauses.push(`\`${field}\` = ?`);
      params.push(data[field]);
    }
  }

  if (setClauses.length === 0) {
    return sendJSON(res, 400, { message: 'No fields to update' });
  }

  params.push(productId);

  try {
    await db.query(`UPDATE products SET ${setClauses.join(', ')} WHERE id = ?`, params);
    await logAction(req.session.user_id, 'product_update', `Updated product ID ${productId}`);
    sendJSON(res, 200, { message: 'Product updated successfully' });
  } catch (e) {
    sendJSON(res, 500, { message: 'Error updating product: ' + e.message });
  }
}

/** DELETE /api/products/:id - soft delete (seller who owns it, or admin) */
async function remove(req, res, id) {
  const productId = parseInt(id, 10);
  if (!req.session.user_id) return sendJSON(res, 401, { message: 'Unauthorized' });

  if (req.session.role === 'seller') {
    const owned = await db.query('SELECT id FROM products WHERE id = ? AND seller_id = ?', [productId, req.session.user_id]);
    if (owned.length === 0) {
      return sendJSON(res, 403, { message: 'Access denied: You can only delete your own products' });
    }
  } else if (req.session.role !== 'admin') {
    return sendJSON(res, 403, { message: 'Access denied' });
  }

  const result = await db.query("UPDATE products SET status = 'inactive' WHERE id = ?", [productId]);

  if (result.affectedRows > 0) {
    sendJSON(res, 200, { message: 'Product blocked/deactivated successfully (Soft Delete)' });
  } else {
    sendJSON(res, 404, { message: 'Product not found or already inactive' });
  }
}

module.exports = { list, getOne, create, update, remove };
