# Electronics Ordering Website - Project Report

## 1. System Overview
The Electronics Ordering Website is a full-stack web application designed to facilitate the browsing and purchasing of electronic products. It targets individual consumers looking for the latest gadgets and administrators managing the product inventory.

**Key Features:**
- **User Authentication:** Secure registration and login for customers and administrators.
- **Product Catalog:** Browsing and searching of electronic products by category or name.
- **Shopping Cart:** Adding items to a persistent cart (Local Storage) and managing quantities.
- **Order System:** Secure checkout process and order history tracking.
- **Admin Dashboard:** Product inventory management (CRUD) and order monitoring.

## 2. Database Design
The system uses a MySQL relational database with six tables normalized to 3NF.

### Schema Overview
- **users**: Stores user credentials and access roles (`customer`, `admin`).
- **categories**: Defines product categories to organize the catalog.
- **products**: Stores product details, linked to categories via `category_id`.
- **orders**: Records order metadata (user, date, total, status).
- **order_items**: comprehensive list of items in each order, preserving the price at purchase time.
- **payments**: Tracks payment status and methods for orders.

### Normalization (3NF)
- **1NF**: All columns contain atomic values.
- **2NF**: All non-key attributes are fully functional dependent on the primary key (no partial dependencies).
- **3NF**: No transitive dependencies (e.g., product price is in the `products` table, not repeated in `orders` unnecessarily, though snapshot is kept in `order_items` for historical accuracy).

## 3. System Architecture
The application follows a standard **Client-Server Architecture**.

- **Frontend:**
  - **HTML5:** Provides the semantic structure of the pages.
  - **CSS3:** Custom styling handling layout (Flexbox/Grid) and responsiveness.
  - **JavaScript (Vanilla):** Handles client-side logic, DOM manipulation, and AJAX/Fetch API calls to the backend.

- **Backend:**
  - **PHP (8.4):** Server-side logic, API endpoints, and session management.
  - **MySQL:** Relational database management system.
  - **RESTful API:** Communication interface between frontend and backend.

## 4. Security Implementation
- **Password Hashing:** `password_hash()` (Bcrypt) is used to securely store user passwords.
- **Input Sanitization:** Prepared statements (`PDO`) are used for all SQL queries to prevent SQL Injection.
- **Authorization:** Role-based access control checks on sensitive API endpoints (Admin only).
- **Session Management:** Secure PHP sessions for tracking user login state.

## 5. Implementation Details
- **Fetch API:** Used for asynchronous data loading (Products, Orders) without page reloads.
- **Local Storage:** Used for client-side cart management before checkout.
- **Dynamic DOM:** JavaScript generates HTML for product cards and tables dynamically based on API responses.

## 6. Installation & Usage
1. Import `database.sql` into MySQL.
2. Configure `config/db.php` with database credentials.
3. Start the PHP server: `php -S localhost:8000 -t public`
4. Access the site at `http://localhost:8000`.
