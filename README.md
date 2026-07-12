# Fussy Electronics — Ordering System

A multi-role electronics marketplace (admin / seller / customer) with product
listings, cart & checkout, order tracking, and seller/admin dashboards.

The backend was migrated from PHP to **plain Node.js** (built-in `http`
module only — no Express or any other web framework) backed by **MySQL**.
The frontend remains plain **HTML, CSS, and JavaScript**.

## Stack

- **Backend:** Node.js (`http` module, hand-written router — no framework)
- **Database:** MySQL / MariaDB
- **Frontend:** HTML, CSS, vanilla JS
- **Libraries used (not frameworks):**
  - `mysql2` — MySQL driver
  - `bcryptjs` — password hashing (pure JS, no native build step)
  - `busboy` — multipart/form-data parsing (product image uploads)
  - `dotenv` — loads `.env` config

## Project layout

```
database/
  database.sql        # schema + seed data (categories, default admin)
public/                # static frontend (served directly by the Node server)
  css/, js/, uploads/
  index.html, login.html, register.html, cart.html, order.html,
  checkout.html, profile.html, admin.html, seller-dashboard.html
server/
  server.js            # entry point — http server, routing, static files
  src/
    db.js              # MySQL connection pool
    session.js         # cookie-based sessions (replaces PHP $_SESSION)
    http-utils.js       # JSON body parsing, cookies, response helpers
    upload.js           # multipart form parsing for image uploads
    guards.js           # login/role checks
    static.js           # serves public/ as static files
    router.js           # route table -> handlers
    routes/
      auth.js, products.js, orders.js, admin.js, seller.js, user.js, contact.js
```

## Setup

### 1. Create the database

```bash
mysql -u root -p < database/database.sql
```

This creates the `electronics_db` database, all tables, the default
categories, and a default admin account:

- **email:** `admin@electroshop.com`
- **password:** `admin123`

Change this password after first login.

> The schema also creates a dedicated `contact_messages` table used by the
> contact form (this table was referenced by the old PHP code but was
> missing from the original `database.sql`).

### 2. Create a dedicated database user (recommended)

Rather than connecting as `root`, create an app-specific user:

```sql
CREATE USER 'fussy_app'@'localhost' IDENTIFIED BY 'choose-a-strong-password';
GRANT ALL PRIVILEGES ON electronics_db.* TO 'fussy_app'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Configure environment variables

```bash
cd server
cp .env.example .env
```

Edit `.env` with your DB credentials:

```
PORT=3000
DB_HOST=127.0.0.1
DB_USER=fussy_app
DB_PASS=choose-a-strong-password
DB_NAME=electronics_db
```

### 4. Install dependencies and run

```bash
cd server
npm install
npm start
```

The server listens on `http://localhost:3000` and serves both the API
(under `/api/...`) and the static frontend (`/`, `/login.html`, etc.) from
the `public/` folder — everything runs from a single Node process, no
Apache/PHP required.

## API overview

All endpoints are under `/api`. Sessions are cookie-based (`sid` cookie,
`HttpOnly`), matching how the frontend already expects auth to work.

| Area     | Endpoint                                   | Notes                              |
|----------|---------------------------------------------|-------------------------------------|
| Auth     | `POST /api/auth/register`                  | |
|          | `POST /api/auth/login`                     | |
|          | `GET/POST /api/auth/logout`                | |
|          | `GET /api/auth/check_session`               | |
| Products | `GET /api/products`                        | filters: `category`, `search`, `location`, `min_price`, `max_price` |
|          | `GET /api/products/:id`                    | |
|          | `POST /api/products`                       | seller/admin, JSON or multipart (image) |
|          | `PUT /api/products/:id`                    | seller (own) or admin |
|          | `DELETE /api/products/:id`                 | soft delete |
| Orders   | `POST /api/orders`                          | place order |
|          | `GET /api/orders`                          | own history, or `?all=true` for admin |
|          | `DELETE /api/orders/:id`                   | admin only |
| Admin    | `GET /api/admin/dashboard_stats`           | |
|          | `GET/POST /api/admin/users`                | |
|          | `DELETE /api/admin/users/:id`              | soft delete + deactivates their products |
|          | `POST /api/admin/users/:id/role`           | |
|          | `GET /api/admin/orders`                    | |
|          | `POST /api/admin/stock`                    | |
| Seller   | `GET /api/seller/products`                 | seller's own products |
|          | `GET /api/seller/orders`, `/orders/recent` | |
|          | `GET /api/seller/dashboard_stats`          | |
|          | `GET /api/seller/notifications`            | |
|          | `POST /api/seller/notifications/read-all`  | |
|          | `POST /api/seller/notifications/:id/read`  | |
|          | `POST /api/seller/orders/status`           | |
| User     | `GET/PUT /api/user/profile`                | |
|          | `GET /api/user/stats`                      | |
| Contact  | `POST /api/contact`                        | |

## What changed from the PHP version

- All `.php` files under `public/api/` were removed and rewritten as Node.js
  route handlers using the same URL structure the frontend already called,
  updated to a consistent, RESTful `/api/...` convention (see table above).
- PHP sessions (`$_SESSION`) were replaced with a small in-memory,
  cookie-based session store (`server/src/session.js`).
- `password_hash()`/`password_verify()` were replaced with `bcryptjs`; the
  default admin password hash in `database.sql` was regenerated to match.
- File uploads (`move_uploaded_file`) were replaced with `busboy`-based
  multipart parsing; uploaded images are stored in `public/uploads/` and
  served statically, returned as root-relative paths (e.g.
  `/uploads/xyz.jpg`) so the frontend no longer needs to guess a base URL.
- The `.htaccess` file (Apache-specific) was removed — routing and static
  file serving are now handled entirely by `server.js`.
- Fixed a pre-existing broken link: the seller role had no working
  dashboard page (`seller_dashboard.html` was referenced but never existed).
  Added `public/seller-dashboard.html` + `public/js/seller-dashboard.js` with
  product management, order status updates, and notifications, backed by a
  small addition (`GET /api/seller/products`).
- Removed the old `orders/delete.php` + `admin/delete_order.php` duplication
  and the redundant/legacy `seller/notify_seller.php` stock-double-deduction
  endpoint (order creation already handles stock and notifications).
- `orders/delete.php`-style POST-with-body calls were converted to proper
  `DELETE` requests against `/api/orders/:id`; likewise for
  `products/delete.php`, `admin/delete_user.php`.

## Notes

- The shopping cart itself is still kept in the browser's `localStorage`
  (as in the original app) and only becomes a real `orders` row at checkout.
- CORS is enabled permissively for convenience during development; lock this
  down (`server.js`) before exposing the server publicly.
- For production, put this behind a process manager (e.g. `pm2`) and a
  reverse proxy (Nginx) that terminates HTTPS, the same way the other
  projects in this workspace are deployed on EC2.
