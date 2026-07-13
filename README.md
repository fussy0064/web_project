# Fussy Electronics — Ordering System

A multi-role electronics marketplace (admin / seller / customer) with product
listings, cart & checkout, order tracking, and admin/seller dashboards.

**Stack:** Plain PHP 8 (no framework) + MySQL/PDO on the backend, plain
HTML/CSS/JavaScript on the frontend. Designed to run under Apache (e.g.
XAMPP) or any PHP-capable web server.

## Setup (XAMPP / Apache + PHP + MySQL)

1. Place this project under your web root, e.g.
   `C:\xampp\htdocs\Electronics_Ordering_System\web_project\` (the frontend
   assumes this path — see "Notes" below).

2. Create the database:

   ```bash
   mysql -u root -p < database/database.sql
   ```

   **Seeded admin account:**
   - **email:** `admin@electroshop.com`
   - **password:** `admin123`

   Change this password after first login. This account can: view the
   dashboard (users/products/orders/revenue stats), create and deactivate
   users, promote/demote any user's role, view and delete any order, view
   and manage all products, and add stock to any product.

3. Check `public/api/config.php` — it defaults to `DB_USER=root`,
   `DB_PASS=''` (typical XAMPP defaults). Adjust to match your real DB
   credentials, and add your production domain to the `$allowedOrigins`
   list (see Security section below).

4. Start Apache + MySQL and open
   `http://localhost/Electronics_Ordering_System/web_project/public/`.

## Security fixes made

A full audit turned up several real, exploitable issues. All are fixed:

- **`orders/delete.php` had no authentication check at all.** Any visitor,
  logged in or not, could delete any order by ID — and this was the exact
  endpoint the admin dashboard calls. Now requires admin login.
- **Unrestricted file uploads.** `products/create.php`/`update.php` saved
  uploaded images using whatever extension the browser sent, with no
  validation — a file named `shell.php` would have been saved into a
  web-served directory and could have been executed directly. Now
  whitelists image extensions and verifies the upload is a real image via
  `getimagesize()`.
- **A debug log was publicly downloadable.** `orders/create.php` wrote
  every order attempt (shipping address, phone, items) to
  `public/api/orders/order_debug.log`, sitting inside the web root with no
  protection. Removed the logging, deleted the file, and added an
  `.htaccess` rule blocking `.log`/`.sql`/`.env` files as defense-in-depth.
- **Inconsistent DB credentials.** `auth/login.php`, `auth/logout.php`,
  `auth/check_session.php`, and `contact_submit.php` each hardcoded their
  *own* separate DB connection instead of using `config.php`'s settings —
  changing your DB password in `config.php` for production would silently
  NOT apply to login/logout/session-check/contact. All four now share
  `config.php`'s connection.
- **Wide-open CORS.** `config.php` reflected *any* website's `Origin`
  header back as allowed, combined with credentialed requests — a
  malicious site could ride a logged-in admin's session. Restricted to an
  explicit allow-list (`$allowedOrigins` in `config.php` — add your real
  domain there).
- **Stored XSS across the app.** Usernames, customer names, and
  product name/description/image fields are all user-controlled (via
  registration or seller listings — `image_url` can even be set to any
  string directly via the API without a real upload) and were injected into
  the page via `innerHTML` with zero escaping, in `admin.js`, `main.js`,
  `profile.js`, `cart.html`, `checkout.html`, and `order.html`. A malicious
  username or product name could run arbitrary JavaScript in **any**
  visitor's browser, including the admin's. Added an `escapeHtml()` helper
  and applied it everywhere user-controlled text is rendered.
- **The seeded admin password never worked.** The password hash committed
  in `database.sql` did not actually verify against `admin123`
  (confirmed with `password_verify()`) — the documented default admin login
  has never worked, even before any of these changes. Regenerated and
  verified.
- **Broken relative path in `order.html`.** Its product-loading fetch used
  `../../public/api/products/get_products.php`, which resolves to the wrong
  directory (missing the `web_project` path segment) and would 404. Fixed
  to use the same absolute path convention as the rest of the app.
- Removed `seller/notify_seller.php` — unused by the frontend, and its
  stock-deduction logic duplicated (and could double-count against) the
  deduction that already happens in `orders/create.php`.
- `seller/dashboard_stats.php` queried a nonexistent `oi.price_at_purchase`
  column (`order_items` only has `price`), which 500'd the seller revenue
  stat every time. Fixed.
- `database.sql` was missing the `contact_messages` table that
  `contact_submit.php` inserts into. Added.
- Added `public/seller-dashboard.html` + `public/js/seller-dashboard.js`
  (product management, order status updates, notifications) and a new
  `public/api/seller/products.php` endpoint — the seller role previously had
  no working dashboard at all (the redirect pointed at a file that never
  existed, at the wrong path).

## Recommendations (not yet changed — your call)

- **Session cookie hardening:** for a production HTTPS deployment, set
  `session.cookie_secure=1` and `session.cookie_samesite=Lax` (via
  `ini_set` in `config.php` before `session_start()`, or in `php.ini`), so
  session cookies aren't sent over plain HTTP or cross-site.
- **Rate limiting / brute-force protection:** `auth/login.php` has no limit
  on failed attempts. Consider adding a simple lockout (e.g. via
  `system_logs`) after N failed attempts per email/IP.
- **Password policy:** minimum length is 6 characters everywhere passwords
  are set. Consider raising this and/or checking against a common-password
  list for a real production launch.
- **`admin/delete_order.php` vs `orders/delete.php`:** both delete an order
  and both are now correctly admin-gated, but they're duplicates — only
  `orders/delete.php` is actually called by the frontend. Safe to leave,
  but worth consolidating at some point to reduce surface area.
- **Upload size limits:** confirm `upload_max_filesize`/`post_max_size` in
  `php.ini` are set to sensible values for your hosting — the app doesn't
  enforce its own size cap beyond whatever PHP allows through.

## Notes

- The shopping cart is kept in the browser's `localStorage` and only
  becomes a real `orders` row at checkout.
- Some frontend files use an absolute path
  (`/Electronics_Ordering_System/web_project/public/api/...`) to call the
  API, matching a local XAMPP deployment at that path. If you deploy
  elsewhere, update `API_BASE`/`API_URL` in `public/js/*.js` accordingly,
  and add the new origin to `$allowedOrigins` in `config.php`.
- For production (e.g. the AWS EC2 + HTTPS setup used for other projects in
  this workspace), put this behind Apache with `mod_php` or `php-fpm`, use
  real non-default DB credentials, and apply the recommendations above.
