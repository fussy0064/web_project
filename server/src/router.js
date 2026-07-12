// router.js - a tiny hand-written router (no Express/other framework)
const auth = require('./routes/auth');
const products = require('./routes/products');
const orders = require('./routes/orders');
const admin = require('./routes/admin');
const seller = require('./routes/seller');
const user = require('./routes/user');
const contact = require('./routes/contact');
const { sendJSON } = require('./http-utils');

// Each route: [method, path-with-:params, handler(req, res, params, query)]
const routes = [
  ['POST', '/api/auth/login', (req, res) => auth.login(req, res)],
  ['POST', '/api/auth/register', (req, res) => auth.register(req, res)],
  ['GET', '/api/auth/logout', (req, res) => auth.logout(req, res)],
  ['POST', '/api/auth/logout', (req, res) => auth.logout(req, res)],
  ['GET', '/api/auth/check_session', (req, res) => auth.checkSession(req, res)],

  ['GET', '/api/products', (req, res, params, query) => products.list(req, res, query)],
  ['GET', '/api/products/:id', (req, res, params) => products.getOne(req, res, params.id)],
  ['POST', '/api/products', (req, res) => products.create(req, res)],
  ['PUT', '/api/products/:id', (req, res, params) => products.update(req, res, params.id)],
  ['DELETE', '/api/products/:id', (req, res, params) => products.remove(req, res, params.id)],

  ['POST', '/api/orders', (req, res) => orders.create(req, res)],
  ['GET', '/api/orders', (req, res, params, query) => orders.history(req, res, query)],
  ['DELETE', '/api/orders/:id', (req, res, params) => orders.remove(req, res, params.id)],

  ['GET', '/api/admin/dashboard_stats', (req, res) => admin.dashboardStats(req, res)],
  ['GET', '/api/admin/users', (req, res) => admin.getUsers(req, res)],
  ['POST', '/api/admin/users', (req, res) => admin.createUser(req, res)],
  ['DELETE', '/api/admin/users/:id', (req, res, params) => admin.deleteUser(req, res, params.id)],
  ['POST', '/api/admin/users/:id/role', (req, res, params) => admin.promoteUser(req, res, params.id)],
  ['GET', '/api/admin/orders', (req, res) => admin.getOrders(req, res)],
  ['POST', '/api/admin/stock', (req, res) => admin.addStock(req, res)],

  ['GET', '/api/seller/products', (req, res) => seller.myProducts(req, res)],
  ['GET', '/api/seller/orders', (req, res) => seller.allOrders(req, res)],
  ['GET', '/api/seller/orders/recent', (req, res) => seller.recentOrders(req, res)],
  ['GET', '/api/seller/dashboard_stats', (req, res) => seller.dashboardStats(req, res)],
  ['GET', '/api/seller/notifications', (req, res) => seller.notifications(req, res)],
  ['POST', '/api/seller/notifications/read-all', (req, res) => seller.markAllNotificationsRead(req, res)],
  ['POST', '/api/seller/notifications/:id/read', (req, res, params) => seller.markNotificationRead(req, res, params.id)],
  ['POST', '/api/seller/orders/status', (req, res) => seller.updateOrderStatus(req, res)],

  ['GET', '/api/user/profile', (req, res) => user.getProfile(req, res)],
  ['PUT', '/api/user/profile', (req, res) => user.updateProfile(req, res)],
  ['GET', '/api/user/stats', (req, res) => user.stats(req, res)],

  ['POST', '/api/contact', (req, res) => contact.submit(req, res)],
];

// Pre-compile each route pattern into a regex with named capture groups.
const compiled = routes.map(([method, pattern, handler]) => {
  const paramNames = [];
  const regexStr = pattern
    .split('/')
    .map((segment) => {
      if (segment.startsWith(':')) {
        paramNames.push(segment.slice(1));
        return '([^/]+)';
      }
      return segment.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    })
    .join('/');
  return { method, regex: new RegExp(`^${regexStr}$`), paramNames, handler };
});

/** Attempts to match and dispatch the request. Returns true if a route handled it. */
async function handleApi(req, res, pathname, query) {
  if (!pathname.startsWith('/api/')) return false;

  for (const route of compiled) {
    if (route.method !== req.method) continue;
    const match = pathname.match(route.regex);
    if (!match) continue;

    const params = {};
    route.paramNames.forEach((name, i) => { params[name] = match[i + 1]; });

    try {
      await route.handler(req, res, params, query);
    } catch (e) {
      console.error('Route error:', e);
      if (!res.headersSent) sendJSON(res, 500, { message: 'Internal server error' });
    }
    return true;
  }

  sendJSON(res, 404, { message: 'API endpoint not found' });
  return true;
}

module.exports = { handleApi };
