# J.I.OJO Construction Services

Enterprise management system for J.I.OJO Construction Services. A single-page application for managing construction projects, manpower, materials, payroll, cash releases, and administrative documents.

## Tech Stack

- **Backend:** PHP 8+ (no framework)
- **Database:** MySQL via PDO
- **Frontend:** Vanilla JavaScript (ES6+)
- **CSS:** Pure CSS with custom properties (responsive)
- **Icons:** Font Awesome 6.5.1
- **Fonts:** Google Fonts - Plus Jakarta Sans

## Requirements

- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Apache / Nginx / IIS (or any web server with PHP support)
- XAMPP / Laragon / WAMP recommended

## Local Setup Using XAMPP

### Step 1: Install the project

Copy the project folder to your web root:
- **XAMPP:** `C:\xampp\htdocs\ojo-main`
- **Laragon:** `C:\laragon\www\ojo-main`
- **WAMP:** `C:\wamp64\www\ojo-main`

### Step 2: Create the database

**Option A — Using phpMyAdmin:**

1. Open `http://localhost/phpmyadmin`
2. Click "Import" tab
3. Click "Choose File" and select `database.sql` from the project folder
4. Click "Go" to execute the import

**Option B — Using MySQL CLI:**

```bash
mysql -u root -p < database.sql
```

### Step 3: Configure database connection

The file `backend/db.php` is pre-configured for local development:

- Host: `localhost`
- Database: `construction_management`
- Username: `root`
- Password: (empty)

If your MySQL setup uses different credentials, edit `backend/db.php` accordingly.

### Step 4: Run the project

Start Apache and MySQL services in XAMPP/Laragon/WAMP, then open:

```
http://localhost/ojo-main
```

### Step 5: Login

Use the default admin account:

- **Email:** `admin@construction.com`
- **Password:** `Admin123`

**IMPORTANT:** Change the default password after first login!

## How to Change the Admin Password

The default admin is inserted in `database.sql` with a bcrypt-hashed password. To generate a new password hash, run:

```bash
php -r "echo password_hash('YourNewPassword', PASSWORD_DEFAULT);"
```

Then update the `password` field in the `admins` table via phpMyAdmin.

## Project Structure

```
ojo-main/
├── index.php              # SPA entry point (HTML shell)
├── database.sql           # Database setup script
├── README.md              # This file
├── backend/
│   ├── db.php             # Database connection
│   ├── api.php            # API request router
│   └── AppSystem.php      # Business logic class
├── css/
│   └── style.css          # Application stylesheet
├── js/
│   └── app.js             # Frontend application logic
└── uploads/               # Uploaded files (created at runtime)
    ├── manpower/          # Worker photos
    └── ntp/               # NTP documents
```

## Troubleshooting

| Problem | Solution |
|---------|----------|
| **"Database connection failed"** | Make sure MySQL is running and credentials in `backend/db.php` are correct |
| **"Table not found" errors** | Re-import `database.sql` via phpMyAdmin |
| **Login not working** | Check the `admins` table has the default admin record |
| **Blank page / 500 error** | Enable PHP error reporting temporarily or check Apache error logs |
| **File uploads not working** | Ensure `uploads/` directories are writable by the web server |
| **Session/login issues** | Clear browser cookies and reload |

## Security Notes

- Change the default admin password immediately after first login
- The default database credentials (root / no password) are for local development only. Use strong credentials in production.
- All sensitive backend actions require an authenticated session
- File uploads are validated for type and size (max 5MB)
- All dynamic data is HTML-escaped before rendering to prevent XSS

## Modules

- **Dashboard** — Quick stats, upcoming deadlines, global search
- **Record List (Manpower)** — Worker management with skill folders
- **Award Cost** — Job description reference with amounts
- **Payroll** — Smart ledger with nested transaction view
- **Cash Release** — Categorized outgoing cash logging
- **Notice to Proceed (NTP)** — Document uploads, project status transition
- **Projects (Sites)** — Checklist, material issuance, manpower assignment
- **Material Supplier** — Supplier directory + inventory management
