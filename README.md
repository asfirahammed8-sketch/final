# DreamIT Web Application - Creative IT Institute

DreamIT is a production-grade, full-stack web application designed for Creative IT Institute. Built with PHP 8.2+ and MySQL 8.0+, the application features a modular architecture, a RESTful administrative backend, robust security controls, dynamic database persistence, responsive mobile UI/UX, and comprehensive SEO management.

---

## Architecture & Technology Stack

- **Backend**: PHP 8.2+ (PDO with prepared statements, modular controllers, rate limiting, exception handling)
- **Database**: MySQL 8.0+ / MariaDB (InnoDB, UTF-8 mb4 character encoding, foreign keys, indexed queries)
- **Frontend**: Native HTML5, CSS3 (Design Tokens, CSS Grid, Flexbox, smooth transitions), Modern ES6+ JavaScript (Fetch API, asynchronous UI updates)
- **Admin Panel**: Secure dashboard with session regeneration, CSRF protection, AJAX CRUD APIs, course wizards, and content management
- **Security**: Password hashing (`PASSWORD_BCRYPT`), prepared SQL queries against SQL injection, context-aware output escaping against XSS, token-based CSRF defense, IP rate limiting, and strict security headers

---

## Project Directory Structure

```
├── admin/                      # Admin Panel Module
│   ├── api/                    # RESTful AJAX endpoints (courses, projects, gallery, FAQs, etc.)
│   ├── assets/                 # Admin CSS & JS theme components
│   ├── auth/                   # Authentication handlers (login, logout, reset-password, 2FA)
│   ├── includes/               # Admin layout partials & auth-guard middleware
│   └── views/                  # Dashboard and CRUD view components
├── api/                        # Public API endpoints (e.g. enquiry submission)
├── config/                     # Application & Environment Configurations
│   ├── database.php            # Singleton PDO database connection handler
│   ├── email.php               # SMTP email dispatch settings
│   ├── env.php                 # Environment variable loader
│   ├── input-validation.php    # Input sanitization and validation rules
│   └── security.php            # CSRF and security header helper functions
├── database/                   # Schema, Migration, and Seed Scripts
│   ├── final_verification.php # Automated verification & health check script
│   ├── schema.sql              # Base database table structure
│   └── setup.php               # Interactive / CLI database migration & seeder
├── docs/                       # Technical & Architectural Documentation
├── frontend/                   # Frontend Views & Assets
│   ├── assets/                 # Custom CSS styles, JS scripts, and images
│   ├── data/                   # Fallback data manifests
│   └── pages/                  # Page template views
├── includes/                   # Core Global Helpers
│   ├── csrf.php                # CSRF validation token routines
│   ├── error-handler.php       # Global exception & error handler
│   ├── footer.php              # Global responsive footer partial
│   ├── header.php              # Global header navigation partial
│   ├── helpers.php             # General utility functions and settings fetchers
│   ├── logger.php              # Structured system logger
│   └── rate_limit.php          # Request throttling / rate limiter
├── .env.example                # Safe environment variable configuration template
├── .htaccess                   # Apache URL rewriting & security headers rules
├── index.php                   # Public entry point / router
└── README.md                   # System documentation
```

---

## Installation & Setup Guide

### 1. Requirements
- PHP 8.2 or higher (with `pdo_mysql`, `gd`, `json`, `mbstring` extensions enabled)
- MySQL 8.0+ or MariaDB 10.4+
- Web Server: Apache (with `mod_rewrite` enabled) or Nginx

### 2. Environment Configuration
Copy `.env.example` to `.env` in the root directory:
```bash
cp .env.example .env
```
Edit `.env` and set your local database credentials:
```ini
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/dreamit

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=dreamit
DB_USER=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
```

### 3. Database Initialization & Seeding
Execute the setup script via CLI to automatically create the database, execute `schema.sql`, apply table auto-upgrades, and seed production content:
```bash
php database/setup.php --force
```

### 4. Database Health Verification
Verify that all database tables, columns, constraints, and initial records are correctly populated:
```bash
php database/final_verification.php
```

---

## Admin Panel Access

- **Login URL**: `http://localhost/dreamit/admin/auth/login.php`
- **Default Super Admin Email**: `admin@dreamitnaogaon.com`
- **Default Super Admin Password**: `admin123`
*(Note: Change password immediately upon first login in production.)*

---

## Production Deployment Checklist

1. **Environment Settings**:
   Set `APP_ENV=production` and `APP_DEBUG=false` in `.env`.
2. **Database Permissions**:
   Ensure the database user has standard SELECT, INSERT, UPDATE, DELETE, and ALTER privileges.
3. **Directory Permissions**:
   Ensure `uploads/` and `logs/` directories are writable by the web server user (`chmod 775`).
4. **HTTPS / SSL**:
   Enforce HTTPS in `.htaccess` or reverse proxy configuration to secure session cookies and payload transmission.
5. **Security Headers**:
   Verify response headers include `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, and `X-XSS-Protection: 1; mode=block`.

---

## License

Copyright © Creative IT Institute. All rights reserved.
