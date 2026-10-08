<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
start_session();

if (empty($_SESSION['admin_logged_in'])) redirect('login.php');

if (!function_exists('money')) {
    function money(float $n): string { return '$' . number_format($n, 2); }
}

$flash = null;
if (!empty($_SESSION['pkg_flash'])) { $flash = $_SESSION['pkg_flash']; unset($_SESSION['pkg_flash']); }

$packages = db_run("SELECT * FROM packages ORDER BY sort_order ASC, id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Packages — <?= e(SITE_NAME) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="admin-layout">
  <aside class="admin-sidebar">
    <div class="logo">
      <span class="logo__icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg></span>
      AquaFix<span style="color:var(--c-accent)">Pro</span>
    </div>
    <nav class="admin-nav">
      <a href="index.php?tab=overview">Overview</a>
      <a href="index.php?tab=bookings">Bookings</a>
      <a href="index.php?tab=users">Users</a>
      <a href="index.php?tab=messages">Messages</a>
      <a href="index.php?tab=reviews">Reviews</a>
      <a href="packages.php" class="active">Packages</a>
      <a href="orders.php">Orders</a>
      <a href="chats.php">Live Chats</a>
      <a href="scan.php">📱 Scan QR</a>
      <a href="index.php?tab=newsletter">Newsletter</a>
      <a href="index.php?tab=activity">Activity Log</a>
      <a href="../index.php">View Website</a>
      <a href="logout.php" style="color:#f87171;">Logout</a>
    </nav>
  </aside>

  <main class="admin-main">
    <div class="admin-header">
      <div>
        <h2 style="margin-bottom:4px;">Packages</h2>
        <p style="margin:0;font-size:0.9rem;">Add, edit, or hide packages shown to customers.</p>
      </div>
      <a href="package_edit.php" class="btn btn--accent btn--sm">+ Add New Package</a>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert--<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
    <?php endif; ?>

    <?php if (!$packages): ?>
      <div class="alert alert--info">
        No packages yet. <a href="package_edit.php" style="color:var(--c-accent);font-weight:600;">Create the first one &rarr;</a>
      </div>
    <?php else: ?>
      <table class="admin-table">
        <thead>
          <tr><th>#</th><th>Title</th><th>Price</th><th>Badge</th><th>Visible</th><th>Sort</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($packages as $p): ?>
            <tr>
              <td>#<?= (int)$p['id'] ?></td>
              <td>
                <strong><?= e($p['title']) ?></strong><br>
                <small style="color:var(--c-text-muted);"><?= e($p['subtitle'] ?? '') ?></small>
              </td>
              <td><?= money((float)$p['price']) ?><?= e($p['price_suffix'] ?? '') ?></td>
              <td>
                <?php if (!empty($p['badge'])): ?>
                  <span class="badge badge--done"><?= e($p['badge']) ?></span>
                <?php else: ?>&mdash;<?php endif; ?>
              </td>
              <td>
                <?php if ($p['is_active']): ?>
                  <span class="badge badge--done">Visible</span>
                <?php else: ?>
                  <span class="badge badge--cancelled">Hidden</span>
                <?php endif; ?>
              </td>
              <td><?= (int)$p['sort_order'] ?></td>
              <td>
                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                  <a href="package_edit.php?id=<?= (int)$p['id'] ?>" class="btn btn--outline btn--sm">Edit</a>
                  <a href="package_delete.php?id=<?= (int)$p['id'] ?>&csrf=<?= e(csrf_token()) ?>"
                     class="btn btn--ghost btn--sm" style="color:var(--c-error);"
                     onclick="return confirm('Delete this package permanently?');">Delete</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </main>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>