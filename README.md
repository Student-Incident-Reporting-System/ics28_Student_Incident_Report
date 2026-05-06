# Student Safety & Incident Reporting System

A full-featured web application for managing student safety incidents, built with PHP, MySQL, Bootstrap 5, Chart.js, and DataTables.

---

## Features

| Feature | Details |
|---|---|
| **Login / Logout** | Secure session-based auth with password hashing, session regeneration |
| **Dashboard** | 3 Chart.js charts (doughnut, line, bar), stat cards, recent incidents table |
| **Incidents CRUD** | Create, Read, Update, Delete with confirmation modal |
| **Students CRUD** | Full student management with incident count display |
| **User Management** | Admin-only user CRUD with role control |
| **Activity Logs** | Full audit trail of all user actions |
| **SQL Joins** | INNER JOIN, LEFT JOIN used throughout with comments |
| **Search & Sort** | DataTables integration on all tables |
| **Responsive** | Bootstrap 5 responsive layout with collapsible sidebar |

---

## SQL Joins Used

| Location | Join Type | Purpose |
|---|---|---|
| `dashboard.php` | `INNER JOIN` | incidents ↔ students, categories, users |
| `dashboard.php` | `LEFT JOIN` | students ↔ incidents (include students with 0 incidents) |
| `incidents.php` | `INNER JOIN` | incidents ↔ students, categories, users |
| `students.php` | `LEFT JOIN` | students ↔ incidents (count per student) |
| `users.php` | `LEFT JOIN` | users ↔ incidents + activity_logs |
| `activity_logs.php` | `INNER JOIN` | logs ↔ users |
| `activity_logs.php` | `LEFT JOIN` | logs ↔ incidents (optional incident code) |

---

## Setup Instructions

### Requirements
- PHP 7.4+ with MySQLi extension
- MySQL 5.7+ or MariaDB 10.3+
- A local server (XAMPP, WAMP, Laragon, MAMP, etc.)

### Steps

1. **Copy files** to your web server's document root (e.g., `htdocs/student_safety/`)

2. **Configure database** — edit `db.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');        // your MySQL password
   define('DB_NAME', 'student_safety');
   ```

3. **Run setup** — open in browser:
   ```
   http://localhost/student_safety/setup.php
   ```

4. **Delete setup.php** after successful setup (security!)

5. **Login** at `http://localhost/student_safety/`

### Demo Credentials

| Username | Password | Role |
|---|---|---|
| `admin` | `password` | Admin |
| `jsmith` | `password` | Staff |
| `mjohnson` | `password` | Staff |

---

## File Structure

```
student_safety/
├── index.php           # Login page
├── logout.php          # Session termination
├── dashboard.php       # Main dashboard with charts
├── incidents.php       # Incident CRUD
├── students.php        # Student CRUD
├── users.php           # User management (admin only)
├── activity_logs.php   # Audit trail
├── db.php              # Database connection + logActivity()
├── auth.php            # Session helpers
├── layout_header.php   # Shared HTML header + sidebar
├── layout_footer.php   # Shared HTML footer + scripts
├── setup.php           # One-time DB setup (delete after use)
└── schema.sql          # Database schema + seed data
```

---

## Security Notes

- Passwords hashed with `password_hash()` (bcrypt)
- All queries use prepared statements (no SQL injection)
- Session ID regenerated on login (prevents session fixation)
- Session fully destroyed on logout
- Admin-only routes protected with `requireAdmin()`
- All output escaped with `htmlspecialchars()`
