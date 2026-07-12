# Fussy Electronics — Ordering System

A multi-role electronics marketplace (admin / seller / customer) with product
listings, cart & checkout, order tracking, and admin/seller dashboards.

**Stack:** Plain PHP 8 (no framework) + MySQL/PDO on the backend, plain
HTML/CSS/JavaScript on the frontend. Designed to run under Apache (e.g.
XAMPP) or any PHP-capable web server.

## Project layout

```
database/
  database.sql          # schema + seed data (categories, default admin)
public/
  api/                   # PHP backend, organized by resource
    config.php           # DB connection, CORS, session bootstrap, helpers
    auth/                # login, register, logout, check_session
    products/            # list, get one, create, update, delete (soft)
    orders/               # create, history, delete
    admin/                # dashboard stats, user & order management, stock
    seller/                # seller dashboard stats, products, orders, notifications
    user/                  # profile, stats
    contact_submit.php     # contact form handler
  css/, js/, uploads/
  index.html, login.html, register.html, cart.html, order.html,
  checkout.html, profile.html, admin.html, seller-dashboard.html
```

## Setup (XAMPP / Apache + PHP + MySQL)

1. Place this project under your web root, e.g.
   `C:\xampp\htdocs\Electronics_Ordering_System\web_project\` (the frontend
   already assumes this path in a few places — see "Notes" below).

2. Create the database:

   ```bash
   mysql -u root -p < database/database.sql
   ```

   This creates the `electronics_db` database, all tables, default
   categories, and a default admin account:

   - **email:** `admin@electroshop.com`
   - **password:** `admin123`

   Change this password after first login.

3. Check `public/api/config.php` — it defaults to `DB_USER=root`,
   `DB_PASS=''` (typical XAMPP defaults) and
   `BASE_URL=/Electronics_Ordering_System/web_project/public`. Adjust these
   to match your actual database credentials and URL path.

4. Start Apache + MySQL (via the XAMPP control panel or your usual method)
   and open `http://localhost/Electronics_Ordering_System/web_project/public/`.

## Bug fixes included in this pass

- **Missing `contact_messages` table:** `contact_submit.php` inserted into
  this table, but it was never defined in `database.sql`. Added.
- **Broken seller dashboard link:** `auth.js` redirected sellers to
  `seller_dashboard.html` (wrong filename, wrong path) which never existed.
  Added `public/seller-dashboard.html` + `public/js/seller-dashboard.js`,
  fixed the redirect, and added `public/api/seller/products.php` (a seller's
  own product listing, needed for product management — didn't previously
  exist).
- **Wrong column name in `seller/dashboard_stats.php`:** the revenue query
  referenced `oi.price_at_purchase`, a column that doesn't exist in
  `order_items` (the actual column is `price`). This silently 500'd every
  time a seller loaded their dashboard. Fixed.

## Notes

- The shopping cart is kept in the browser's `localStorage` and only
  becomes a real `orders` row at checkout.
- Some frontend files use an absolute path
  (`/Electronics_Ordering_System/web_project/public/api/...`) to call the
  API, matching a local XAMPP deployment at that path. If you deploy
  elsewhere, update `API_BASE`/`API_URL` in `public/js/*.js` accordingly.
- For production (e.g. the AWS EC2 + HTTPS setup used for other projects in
  this workspace), put this behind Apache with `mod_php` or `php-fpm`, and
  make sure `public/api/config.php` uses real, non-default DB credentials.
