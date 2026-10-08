<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/cart_functions.php';
require_once __DIR__ . '/product_functions.php';
require_once __DIR__ . '/../config/currency.php';
start_session();

/* ---------- Auto-detect base path for CSS/JS ---------- */
if (!isset($baseHref)) {
    $baseHref = '';
    $curFile = $_SERVER['SCRIPT_NAME'] ?? '';
    if (strpos($curFile, '/auth/')  !== false
     || strpos($curFile, '/user/')  !== false
     || strpos($curFile, '/admin/') !== false) {
        $baseHref = '../';
    }
}

/* ---------- Load current user + notifications count ---------- */
$__u = null; $__unread = 0;
if (!empty($_SESSION['user_id'])) {
    try {
        $__u = db_run("SELECT * FROM users WHERE id = ? LIMIT 1", [$_SESSION['user_id']])->fetch();
        if ($__u) {
            $row = db_run("SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0", [$__u['id']])->fetch();
            $__unread = (int)($row['c'] ?? 0);
        }
    } catch (Exception $e) {}
}

/* ---------- Combined cart count ---------- */
$__cartCount = 0;
try {
    $pkgCount = (int) cart_count();
    $prodCount = !empty($_SESSION['product_cart']) && is_array($_SESSION['product_cart'])
        ? (int) array_sum($_SESSION['product_cart'])
        : 0;
    $__cartCount = $pkgCount + $prodCount;
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? SITE_NAME . ' — ' . SITE_TAGLINE) ?></title>
  <meta name="description" content="<?= e($pageDesc ?? 'Premium plumbing services — leak detection, drain cleaning, water heaters, emergency repairs. 24/7 service.') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= $baseHref ?>assets/css/style.css">
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%230ea5e9' d='M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z'/%3E%3C/svg%3E">
</head>
<body>

<div class="loader" id="loader"><div class="loader__spinner"></div></div>

<div class="topbar">
  <div class="container">
    <div class="topbar__left">
      <a href="tel:<?= e(SITE_PHONE) ?>"><?= e(SITE_PHONE) ?></a>
      <span>•</span>
      <span><?= e(SITE_HOURS) ?></span>
    </div>
    <div class="topbar__right">
      <a href="<?= $baseHref ?>booking.php">Book Online</a>
      <span class="sep"></span>
      <?php if ($__u): ?>
        <a href="<?= $baseHref ?>user/dashboard.php">Hi, <?= e(explode(' ', $__u['name'])[0]) ?></a>
        <?php if ($__unread > 0): ?>
          <a href="<?= $baseHref ?>user/notifications.php" style="background:#ef4444;padding:2px 8px;border-radius:999px;font-size:0.75rem;color:#fff;"><?= $__unread ?> new</a>
        <?php endif; ?>
        <a href="<?= $baseHref ?>auth/logout.php">Logout</a>
      <?php else: ?>
        <a href="<?= $baseHref ?>auth/login.php">Login</a>
        <a href="<?= $baseHref ?>auth/register.php">Sign Up</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<nav class="navbar" id="navbar">
  <div class="container">
    <a href="<?= $baseHref ?>index.php" class="logo">
      <span class="logo__icon">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
      </span>
      AquaFix<span style="color:var(--c-accent)">Pro</span>
    </a>
    <div class="nav-links">
      <a href="<?= $baseHref ?>index.php"     class="<?= ($activePage ?? '') === 'home'     ? 'active' : '' ?>">Home</a>
      <a href="<?= $baseHref ?>services.php"  class="<?= ($activePage ?? '') === 'services' ? 'active' : '' ?>">Services</a>
      <a href="<?= $baseHref ?>products.php"  class="<?= ($activePage ?? '') === 'products' ? 'active' : '' ?>">Products</a>
      <a href="<?= $baseHref ?>shop.php"      class="<?= ($activePage ?? '') === 'shop'     ? 'active' : '' ?>">Packages</a>
      <a href="<?= $baseHref ?>about.php"     class="<?= ($activePage ?? '') === 'about'    ? 'active' : '' ?>">About</a>
      <a href="<?= $baseHref ?>contact.php"   class="<?= ($activePage ?? '') === 'contact'  ? 'active' : '' ?>">Contact</a>
    </div>
    <div class="nav-actions">

      <!-- 💱 Currency switcher -->
      <div class="currency-switcher" style="position:relative;">
        <button type="button" class="currency-btn" id="currencyBtn"
                style="display:flex;align-items:center;gap:6px;padding:8px 12px;border-radius:999px;background:var(--c-bg-alt);border:1px solid var(--c-border);font-size:0.82rem;font-weight:600;cursor:pointer;color:var(--c-text);">
          <span><?= e(currency_symbol()) ?></span>
          <span><?= e(current_currency()) ?></span>
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6,9 12,15 18,9"/></svg>
        </button>

        <div id="currencyMenu" style="display:none;position:absolute;top:calc(100% + 8px);right:0;background:#fff;border:1px solid var(--c-border);border-radius:12px;box-shadow:0 12px 32px rgba(0,0,0,0.15);min-width:230px;z-index:9999;overflow:hidden;">
          <?php foreach (currency_list() as $code => $info): ?>
            <?php $isActive = ($code === current_currency()); ?>
            <a href="<?= $baseHref ?>set_currency.php?c=<?= urlencode($code) ?>"
               style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;text-decoration:none;color:#1f2937;font-size:0.88rem;<?= $isActive ? 'background:#f1f5f9;font-weight:700;' : '' ?>">
              <span><?= e($info['symbol']) ?> <?= e($code) ?> — <?= e($info['name']) ?></span>
              <?php if ($isActive): ?><span style="color:#10b981;">✓</span><?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- 🛒 Cart icon -->
      <a href="<?= $baseHref ?>cart.php" title="Cart" style="position:relative;display:inline-flex;align-items:center;">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
        </svg>
        <span id="cartBadge" style="position:absolute;top:-6px;right:-6px;background:#0ea5e9;color:#fff;font-size:0.7rem;padding:1px 6px;border-radius:999px;<?= $__cartCount > 0 ? '' : 'display:none;' ?>"><?= (int)$__cartCount ?></span>
      </a>

      <?php if ($__u): ?>
        <a href="<?= $baseHref ?>user/notifications.php" style="position:relative;display:inline-flex;align-items:center;" title="Notifications">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          <?php if ($__unread > 0): ?>
            <span style="position:absolute;top:-6px;right:-6px;background:#ef4444;color:#fff;font-size:0.7rem;padding:1px 6px;border-radius:999px;"><?= $__unread ?></span>
          <?php endif; ?>
        </a>
      <?php endif; ?>
      <button class="theme-toggle" id="themeToggle" title="Toggle dark mode"></button>
      <a href="<?= $baseHref ?>booking.php" class="btn btn--accent btn--sm">Get a Quote</a>
      <button class="hamburger" id="hamburger"><span></span></button>
    </div>
  </div>
</nav>

<div class="mobile-menu" id="mobileMenu">
  <a href="<?= $baseHref ?>index.php">Home</a>
  <a href="<?= $baseHref ?>services.php">Services</a>
  <a href="<?= $baseHref ?>products.php">Products</a>
  <a href="<?= $baseHref ?>shop.php">Packages</a>
  <a href="<?= $baseHref ?>cart.php">🛒 Cart (<?= (int)$__cartCount ?>)</a>
  <a href="<?= $baseHref ?>about.php">About</a>
  <a href="<?= $baseHref ?>contact.php">Contact</a>
  <a href="<?= $baseHref ?>booking.php">Book Now</a>
  <?php if ($__u): ?>
    <a href="<?= $baseHref ?>user/dashboard.php">My Dashboard</a>
    <a href="<?= $baseHref ?>user/my-orders.php">My Orders</a>
    <a href="<?= $baseHref ?>auth/logout.php">Logout</a>
  <?php else: ?>
    <a href="<?= $baseHref ?>auth/login.php">Login</a>
    <a href="<?= $baseHref ?>auth/register.php">Sign Up</a>
  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const btn  = document.getElementById('currencyBtn');
  const menu = document.getElementById('currencyMenu');
  if (!btn || !menu) return;

  btn.addEventListener('click', function(e) {
    e.stopPropagation();
    menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
  });

  document.addEventListener('click', function() {
    menu.style.display = 'none';
  });

  menu.addEventListener('click', function(e) {
    e.stopPropagation();
  });
});
</script>