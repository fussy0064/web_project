# Fussy Electronics — Ordering System

A multi-role electronics marketplace (admin / seller / customer) with product
listings, cart & checkout, order tracking, and admin/seller dashboards.

**Stack:** Plain PHP 8 (no framework) + MySQL/PDO on the backend, plain
HTML/CSS/JavaScript on the frontend. Designed to run under Apache (e.g.
XAMPP) or any PHP-capable web server — or see
[Deploying to Vercel](#deploying-to-vercel-with-aiven-mysql--cloudflare-r2)
below for a serverless option.

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

## Deploying to Vercel (with Aiven MySQL + Cloudflare R2)

Vercel doesn't run PHP or host a database natively, so this deployment
combines three services: **Vercel** (frontend + PHP via a community
runtime), **Aiven** (managed MySQL), and **Cloudflare R2** (S3-compatible
object storage for product images, since Vercel Functions have no
persistent local disk).

### 1. Database (Aiven)

1. Create a free MySQL service at [aiven.io](https://aiven.io).
2. From the service's "Overview" page, note the **Host**, **Port**,
   **User**, **Password**, and **Database name** (usually `defaultdb`).
3. Load the schema. From your own machine (not this deployment), with the
   `mysql` client:
   ```bash
   mysql --host=<HOST> --port=<PORT> --user=<USER> -p --ssl-mode=REQUIRED <DATABASE> < database/database.sql
   ```
   (Aiven's console may also offer a browser-based query tool if you'd
   rather paste the file contents there instead.)

### 2. Object storage (Cloudflare R2)

1. In the Cloudflare dashboard, go to R2 → create a bucket.
2. Create an R2 API token (R2 → Manage API Tokens) with **read+write**
   access to that bucket. Note the **Access Key ID** and **Secret Access
   Key** shown (only shown once).
3. Note your **Account ID** (shown in the R2 overview page URL/sidebar).
4. Either enable the bucket's public `r2.dev` URL, or connect a custom
   domain to it, and note that public base URL.

### 3. Vercel project

1. Import this repo into Vercel.
2. **Project Settings → General → Root Directory**: set to `public`
   (this is what makes `public/index.html` serve at `/`, and
   `public/api/auth/login.php` serve at `/api/auth/login.php`, matching
   every hardcoded path already in the frontend JS).
3. **Project Settings → Environment Variables**, add:

   | Variable | Value |
   |---|---|
   | `DB_HOST` | from Aiven |
   | `DB_PORT` | from Aiven |
   | `DB_USER` | from Aiven |
   | `DB_PASS` | from Aiven |
   | `DB_NAME` | from Aiven (`defaultdb`) |
   | `APP_ORIGIN` | your Vercel URL, e.g. `https://electro-hub-topaz.vercel.app` |
   | `R2_ACCOUNT_ID` | from Cloudflare |
   | `R2_ACCESS_KEY_ID` | from Cloudflare |
   | `R2_SECRET_ACCESS_KEY` | from Cloudflare |
   | `R2_BUCKET` | your bucket name |
   | `R2_PUBLIC_URL` | your bucket's public base URL |
   | `DIAGNOSTIC_KEY` | any random string you make up, for step 4 below |

   `VERCEL=1` and `USE_DB_SESSIONS` don't need to be set manually — Vercel
   sets `VERCEL=1` automatically, which `config.php` already detects to
   switch on DB-backed sessions and the smaller (4MB) upload cap.

4. Deploy, then **verify before using the real app**:
   - `https://<your-app>/api/_diagnostics/test_db.php?key=<DIAGNOSTIC_KEY>`
     should return `"db_connected": true`.
   - `https://<your-app>/api/_diagnostics/test_storage.php?key=<DIAGNOSTIC_KEY>`
     should return `"upload_succeeded": true` with a working
     `uploaded_url` you can open in a browser.
   - **Delete both files under `public/api/_diagnostics/` once confirmed**
     — they're meant to be temporary and shouldn't stay in production.

### Known constraints of this setup

- **Vercel's Hobby (free) plan is non-commercial only** per its terms — if
  this ever takes real payments, you need Vercel Pro ($20/mo).
- **Aiven's free tier auto-sleeps after inactivity** — the first request
  after a quiet period may be slow while it wakes up. Fine for a demo;
  consider a paid tier or self-hosting on EC2 (like your other projects)
  for something meant to stay reliably live.
- **R2's PHP integration here isn't officially documented** — it's
  implemented directly against R2's S3-compatible API using the standard,
  publicly-documented AWS Signature V4 algorithm (verified byte-for-byte
  against AWS's own published test vectors), not a reverse-engineered
  proprietary contract. Still, test it via the diagnostic endpoint above
  before trusting it with real product images.
- **Uploads are capped at 4MB on Vercel** (`UPLOAD_MAX_MB` env var),
  below Vercel Functions' hard 4.5MB request body limit — this is a
  platform limit, not something this app's code can raise.

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

## Follow-up hardening (also done)

- **Session cookies:** `config.php` now sets `HttpOnly`, `SameSite=Lax`
  always, and `Secure` automatically once the site is served over HTTPS
  (detects `X-Forwarded-Proto`/`HTTPS`/port 443, so it works behind a
  reverse proxy too, and doesn't break plain-HTTP local development).
- **Login rate limiting:** a new `login_attempts` table tracks failed
  logins by email and IP. After 5 failed attempts within 15 minutes
  (`LOGIN_MAX_ATTEMPTS`/`LOGIN_LOCKOUT_MINUTES` in `config.php`), further
  attempts — even with the correct password — are blocked with `429` until
  the window passes. A successful login clears the counter.
- **Password policy:** minimum length raised from 6 to 8 characters
  (`MIN_PASSWORD_LENGTH` in `config.php`), applied consistently in
  registration, admin user creation, and profile password changes.
- **Upload size limits:** confirmed the real gap — PHP's actual
  `upload_max_filesize` defaults to 2MB, well below the app's own 5MB cap,
  so files between 2–5MB were being silently rejected by PHP itself before
  the app ever saw them. `.htaccess` now raises `upload_max_filesize` to 5M
  and `post_max_size` to 6M to match (mod_php only — see the comment in
  `.htaccess` if you're on php-fpm instead). Verified with a real 3MB image
  upload that previously would have failed.
- **Found and fixed a real deployment bug while testing this:**
  `public/uploads/` wasn't writable by the web server user out of the box,
  so every image upload failed with a generic 500. Error messages now
  distinguish this case and log the real cause server-side (without
  leaking server file paths to the API response). **You need to run this
  after deploying/pulling on your server:**
  ```bash
  sudo chown -R www-data:www-data public/uploads   # or apache:apache on Amazon Linux
  sudo chmod -R 775 public/uploads
  ```

## Recommendations (not yet changed — your call)

- **`admin/delete_order.php` vs `orders/delete.php`:** both delete an order
  and both are now correctly admin-gated, but they're duplicates — only
  `orders/delete.php` is actually called by the frontend. Safe to leave,
  but worth consolidating at some point to reduce surface area.
- **Password complexity:** length is now enforced (8+), but there's no
  check for character variety. Consider requiring at least one letter and
  one number if you want to go further.
- **Rate-limit table cleanup:** `login_attempts` rows are never deleted
  except on a successful login for that email. Consider a periodic cleanup
  (e.g. a cron job deleting rows older than a day) so the table doesn't
  grow unbounded on a busy site.

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
