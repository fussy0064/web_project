# Electronics Ordering System

A web-based platform for ordering electronics.

## Project Structure

This project is organized into the following structure:

- **`public/`**: The web root directory. Contains all publicly accessible files.
  - `api/`: PHP Backend API endpoints.
  - `css/`: Stylesheets.
  - `js/`: JavaScript files.
  - `uploads/`: User-uploaded images.
  - `*.html`: Frontend HTML pages.
- **`database/`**: Database schema files (`database.sql`).
- **`.gitignore`**: Files ignored by Git.

## Deployment Instructions

1. **Database Setup**:
   - Create a MySQL database (e.g., `electronics_db`).
   - Import `database/database.sql` into your database.

2. **Configuration**:
   - Open `public/api/config.php`.
   - Update `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` with your database credentials.
   - Update `BASE_URL` to match your deployment URL path (e.g., `/` if hosted at root, or `/your-subfolder/public`).

3. **Web Server**:
   - Point your web server (Apache/Nginx) document root to the `public/` directory.
   - Alternatively, if using XAMPP/WAMP, place the project in `htdocs` and access via `http://localhost/path/to/project/public/`.

## Development

- Frontend files are in `public/`.
- Backend logic is in `public/api/`.
- To modify styles, edit `public/css/`.
- To modify scripts, edit `public/js/`.

## Git

- Initialize: `git init`
- Add files: `git add .`
- Commit: `git commit -m "Initial structure"`
