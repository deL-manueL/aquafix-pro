# AquaFix Pro — Premium Plumbing Services Platform

A complete digital business platform for plumbing services, built with PHP, MySQL, and modern web technologies. Level 400 final year university project.

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?logo=php)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?logo=mysql)
![Composer](https://img.shields.io/badge/Composer-2.x-885630?logo=composer)
![Status](https://img.shields.io/badge/status-production--ready-brightgreen)

---

## ✨ Features

### 🔐 Authentication & Security
- User registration with server-side validation
- Bcrypt-hashed passwords with per-user salt
- Role-Based Access Control (Customer / Admin / Technician)
- CSRF protection on every form
- Rate limiting on login attempts
- Session management with regeneration

### 👤 Customer Features
- Personal dashboard with stats
- Booking history with status tracking
- Order history with visual tracking timeline
- Real-time notifications (bell icon)
- Profile management
- Reviews and ratings for completed services
- Downloadable PDF invoices

### 🛒 Commerce (Dual System)
- 15 physical products across 11 categories
- Service packages (one-time + annual subscriptions)
- Unified cart for products AND packages
- 5 payment methods:
  - 💳 Visa / Mastercard
  - 🅿️ PayPal
  - 📱 MTN Mobile Money (Ghana)
  - 📱 Telecel Cash (Ghana)
  - 💵 Cash on Delivery
- Complete order lifecycle tracking
- Full refund workflow with approval/rejection

### 🛡️ Admin Panel
- Analytics dashboard with Chart.js
- Booking management with status workflow
- Product CRUD
- Package CRUD
- Order management with stage updates
- Refund approval and processing
- Customer list
- Complete activity audit log

### 💬 Communication & Automation
- Live chat with AI chatbot (reads real database)
- Human agent handoff
- QR code check-in for services
- Contact form submissions
- Newsletter subscribers

### 🌟 Advanced
- Multi-currency (USD, GHS, EUR, GBP, NGN) with live rates
- 7-image hero slideshow
- PDF invoice generation via DomPDF
- QR code generation via endroid/qr-code
- Real-time notification system

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8 |
| Database | MySQL 5.7+ with PDO |
| Frontend | HTML5, CSS3, JavaScript, AJAX |
| Package Manager | Composer |
| PDF Generation | DomPDF |
| QR Generation | endroid/qr-code |
| QR Scanning | html5-qrcode |
| Charts | Chart.js |
| Server | Apache (XAMPP) |

---

## 📦 Installation

### Prerequisites
- PHP 8.0+
- MySQL 5.7+
- Composer

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/del-manuel/aquafix-pro.git
   cd aquafix-pro