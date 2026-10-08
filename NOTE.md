# AquaFix Pro — PHP Version

A complete plumbing company website built with **plain HTML, CSS, JavaScript, and PHP** (no frameworks, no build tools). Uses **JSON file storage** for data — no database server or extensions needed. Works on any PHP installation.

## Requirements

- **PHP 8.0+** (no special extensions needed — uses built-in JSON functions)
- A web server: Apache (with .htaccess) or PHP's built-in dev server

## Quick Start (PHP Built-in Server)

```bash
cd php-version
php -S localhost:8000
```

Open http://localhost:8000 in your browser.

## Quick Start (XAMPP / MAMP / Shared Hosting)

1. Copy the entire `php-version` folder to your web server's document root (`htdocs` for XAMPP, `public_html` for shared hosting, etc.).
2. Visit the site in your browser — data files are created automatically in `config/data/` on first form submission.

## Admin Panel

- **URL:** `http://localhost:8000/admin/login.php`
- **Username:** `admin`
- **Password:** `aquafix2024`

Change these credentials in `config/config.php` (lines with `ADMIN_USER` and `ADMIN_PASS`).

## File Structure

```
php-version/
├── index.php              # Home page
├── about.php              # About page
├── services.php           # Services listing page
├── contact.php            # Contact page with working form
├── booking.php            # 4-step booking wizard
├── 404.php                # Custom 404 page
├── .htaccess              # Apache config (URLs, security)
├── README.md              # This file
│
├── config/
│   ├── config.php         # Site settings, helpers, data
│   ├── database.php       # JSON file-based storage functions
│   └── data/              # JSON data files (auto-created)
│
├── includes/
│   ├── header.php         # Shared header (nav, top bar)
│   └── footer.php         # Shared footer (links, floating buttons)
│
├── assets/
│   ├── css/style.css      # Full design system
│   └── js/main.js         # Dark mode, animations, forms
│
├── admin/
│   ├── login.php          # Admin login page
│   └── index.php          # Admin dashboard (bookings, messages)
│
└── api/
    └── newsletter.php     # Newsletter signup endpoint
```

## Features

- **6 pages:** Home, About, Services, Contact, Booking, 404
- **Admin dashboard:** View/manage bookings, messages, newsletter subscribers
- **Working forms:** Contact form and booking wizard save to JSON files
- **Booking management:** Update booking status (pending → confirmed → done)
- **Dark mode:** Toggle in the navbar, saved to localStorage
- **Animations:** Fade-in on scroll, animated stat counters
- **Responsive:** Mobile menu, responsive grids, optimized for all screen sizes
- **Floating buttons:** WhatsApp chat + back-to-top
- **Cookie consent banner**
- **Loading animation**

## Customization

- **Site info** (name, phone, email, address): `config/config.php`
- **Services list:** `config/config.php` → `get_services()`
- **Testimonials:** `config/config.php` → `get_testimonials()`
- **Admin credentials:** `config/config.php` → `ADMIN_USER` / `ADMIN_PASS`
- **Colors / fonts / layout:** `assets/css/style.css` (CSS variables at top)

## Security Notes

- All user input is escaped with `htmlspecialchars()` to prevent XSS
- Form data is stored via PDO prepared statements to prevent SQL injection
- For production: change the admin password, enable HTTPS, and restrict `config/` directory access (already handled in `.htaccess`)
