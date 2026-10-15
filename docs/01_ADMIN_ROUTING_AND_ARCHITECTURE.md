# 01. Admin Architecture, Routing & Private Slug Specification

## 1. Executive Summary & Objective

This document defines the high-level system architecture, folder organization, routing rules, and deployment standard for the **DreamIT Administrative Control Panel**. The admin panel is engineered as an isolated, production-grade management engine that controls all content on the public-facing platform without hardcoded URLs, allowing instant transition between local development (XAMPP) and live hosting (cPanel/VPS).

---

## 2. Directory Isolation Architecture

The admin panel resides in a dedicated, isolated directory (`admin/`) completely decoupled from frontend user views. No frontend controller or layout mixes with administrative logic.

```
c:/xampp/htdocs/dreamit/
├── admin/                           # Isolated Admin Panel Root
│   ├── index.php                    # Admin Front-Controller & Router
│   ├── auth/                        # Authentication Controllers & Views
│   │   ├── login.php                # Password verification screen
│   │   ├── otp-verify.php           # 6-digit OTP verification screen
│   │   ├── forgot-password.php      # Password reset request screen
│   │   ├── reset-password.php       # New password entry screen
│   │   └── logout.php               # Secure session termination
│   ├── includes/                    # Reusable Admin Components
│   │   ├── auth-guard.php           # Session, role & RBAC protection middleware
│   │   ├── header.php               # Top navigation bar & breadcrumbs
│   │   ├── sidebar.php              # Collapsible navigation menu
│   │   ├── footer.php               # Script tags & toast container
│   │   └── csrf.php                 # Admin CSRF token generation & validation
│   ├── modules/                     # Core Business Logic Modules
│   │   ├── dashboard/               # Metric boxes, activity feeds, quick shortcuts
│   │   ├── courses/                 # Course cards grid, multi-step wizard, curriculum editor
│   │   ├── departments/             # Department category cards & modal manager
│   │   ├── projects/                # Student work cards grid & portfolio manager
│   │   ├── stories/                 # Student success stories & video testimonial manager
│   │   ├── gallery/                 # Photo & campus event gallery cards
│   │   ├── faqs/                    # Dual-column FAQ manager (course-specific & global)
│   │   ├── enquiries/               # Admission inquiries, CRM status cards & contact logs
│   │   ├── settings/                # Site settings, contact info, branding & header/footer
│   │   └── seo/                     # Page-by-page SEO meta, OpenGraph & schema controls
│   ├── api/                         # Admin AJAX / REST Endpoints
│   │   ├── courses.php              # Course CRUD, reorder & status toggle
│   │   ├── projects.php             # Project CRUD & feature toggle
│   │   ├── settings.php             # Site settings update handler
│   │   ├── media-upload.php         # Image/document upload handler with mime validation
│   │   └── enquiries.php            # Inquiry status workflow handler
│   └── assets/                      # Admin Assets (No external framework dependencies)
│       ├── css/
│       │   ├── admin-theme.css      # Core tokens: Navy (#0B2046), surface cards, shadows
│       │   ├── admin-components.css # Badges, pills, modals, wizards, cards, action menus
│       │   └── admin-responsive.css # Mobile/tablet responsive layout adjustments
│       └── js/
│           ├── admin-core.js        # Sidebar toggle, modal manager, toast notifications
│           ├── admin-wizard.js      # Multi-step progressive form engine
│           └── admin-ajax.js        # Standardized fetch wrapper with CSRF injection
├── config/                          # Shared Global Configurations
│   ├── database.php                 # PDO Singleton Connection
│   ├── env.php                      # .env Loader & Parser
│   ├── email.php                    # SMTP / PHP Mailer Configuration
│   └── security.php                 # Sanitization, hashing & rate limiting
├── database/                        # Database Schema & Seeders
│   └── schema.sql                   # Normalized MySQL Schema
└── frontend/                        # Public Website Pages & Assets
```

---

## 3. Private Custom Slug Routing Standard

To prevent unauthorized automated attacks and bots from scanning standard paths like `/admin`, the panel supports a **dynamic private slug**.

### 3.1 Environment Variable Configuration (`.env`)
```ini
# Private URL slug for accessing admin panel
ADMIN_SLUG=cpanel-dreamit
```

### 3.2 Apache URL Rewriting (`.htaccess`)
The root `.htaccess` routes requests matching `ADMIN_SLUG` directly into the `admin/` front-controller without exposing physical folder paths:

```apache
# Rewrite Rule for Private Admin Slug
RewriteCond %{REQUEST_URI} ^/dreamit/cpanel-dreamit/?(.*)$ [OR]
RewriteCond %{REQUEST_URI} ^/cpanel-dreamit/?(.*)$
RewriteRule ^(cpanel-dreamit|admin)/(.*)$ admin/index.php?route=$2 [QSA,L]
```

### 3.3 Dynamic Base URL Auto-Detection Helper
The helper function `admin_url()` automatically detects the active environment and builds fully qualified URLs without hardcoding:

```php
function admin_url(string $path = ''): string {
    $slug = env('ADMIN_SLUG', 'admin');
    $base = base_url($slug);
    $path = ltrim($path, '/');
    return $path !== '' ? rtrim($base, '/') . '/' . $path : rtrim($base, '/') . '/';
}
```

- **Local Development**: `http://localhost/dreamit/admin/` (or `http://localhost/dreamit/cpanel-dreamit/`)
- **Live Production Server**: `https://dreamitnaogaon.com/cpanel-dreamit/`
- **Result**: Switching `.env` from development to production requires **zero code edits**.

---

## 4. Front-Controller & Routing Lifecycle

All administrative requests enter via `admin/index.php`. The router parses the `route` query parameter and enforces authentication before rendering.

### Lifecycle Pipeline:
1. **Bootstrap & Environment**: Load `config/env.php`, `config/database.php`, `config/security.php`, and `includes/helpers.php`.
2. **Security Headers & Secure Session**: Call `apply_security_headers()` and `configure_secure_session()`.
3. **Route Extraction**: Extract `$_GET['route']` (e.g. `courses`, `courses/create`, `settings/seo`).
4. **Auth Exemption Check**: If the route is an auth route (`login`, `otp-verify`, `forgot-password`, `reset-password`), skip auth guard.
5. **Auth Guard Middleware**: Verify `$_SESSION['admin_logged_in'] === true` and check session timeout (default 30 mins idle timeout). If invalid, redirect to `admin_url('login')`.
6. **Controller Dispatch**: Route to appropriate module view (`modules/{module}/index.php` or `modules/{module}/{action}.php`).
7. **404 Handling**: If the requested view does not exist, render an administrative 404 card with navigation breadcrumbs.

---

## 5. Role-Based Access Control (RBAC) Matrix

The `admins` table defines three access tiers via the `role` enum:

| Module / Action | `super_admin` | `admin` | `editor` |
| :--- | :---: | :---: | :---: |
| **Dashboard Analytics** | Full Access | Full Access | View Only |
| **Course Management** (Create/Edit/Delete/Publish) | Full Access | Full Access | Create/Edit Drafts Only |
| **Student Work & Projects** | Full Access | Full Access | Create/Edit |
| **Inquiries & Admission Leads** | Full Access | View & Update Status | View Only |
| **FAQs & Success Stories** | Full Access | Full Access | Full Access |
| **Site Settings (Branding, Contact, Maps)** | Full Access | View Only | No Access |
| **SEO Meta Controls** | Full Access | Full Access | Edit Content Only |
| **Admin User Accounts Management** | Full Access | No Access | No Access |

---

## 6. Zero Hardcoding Standard for Live Deployment

To ensure 100% plug-and-play capability on live cPanel/Apache/Nginx:
1. All asset paths use `admin_url('assets/css/...')` or `base_url(...)`.
2. All form actions point to dynamic route helpers `admin_url('courses/store')`.
3. Database credentials exist strictly in `.env`.
4. File uploads are stored in `uploads/` with database references storing relative paths (`uploads/courses/thumbnail-123.webp`).
5. All redirects utilize `header('Location: ' . admin_url('...'))` instead of hardcoded relative directory hops (`../../`).
