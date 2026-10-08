<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../config/currency.php';
require_once __DIR__ . '/../includes/cart_functions.php';
start_session();

if (empty($_SESSION['admin_logged_in'])) redirect('login.php');

if (!function_exists('money')) {
    function money(float $n): string { return '$' . number_format($n, 2); }
}

/* Handle delete */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        db_run("DELETE FROM products WHERE id = ?", [$id]);
        $_SESSION['prod_flash'] = ['type' => 'success', 'msg' => 'Product deleted.'];
    }
    redirect('products.php');
}

/* Handle toggle visibility */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        db_run("UPDATE products SET is_active = 1 - is_active WHERE id = ?", [$id]);
        $_SESSION['prod_flash'] = ['type' => 'success', 'msg' => 'Product visibility toggled.'];
    }
    redirect('products.php');
}

$flash = null;
if (!empty($_SESSION['prod_flash'])) { $flash = $_SESSION['prod_flash']; unset($_SESSION['prod_flash']); }

$products = db_run("SELECT * FROM products ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Products — Admin</title>
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
      <a href="products.php" class="active">Products</a>
      <a href="packages.php">Packages</a>
      <a href="orders.php">Orders</a>
      <a href="chats.php">Live Chats</a>
      <a href="scan.php">📱 Scan QR</a>
      <a href="index.php?tab=activity">Activity Log</a>
      <a href="../index.php">View Website</a>
      <a href="logout.php" style="color:#f87171;">Logout</a>
    </nav>
  </aside>

  <main class="admin-main">
    <div class="admin-header">
      <div>
        <h2 style="margin-bottom:4px;">Products</h2>
        <p style="margin:0;font-size:0.9rem;"><?= count($products) ?> product(s) in store</p>
      </div>
      <a href="product_edit.php" class="btn btn--accent btn--sm">+ Add Product</a>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert--<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
    <?php endif; ?>

    <?php if (!$products): ?>
      <div class="alert alert--info">
        No products yet. <a href="product_edit.php" style="color:var(--c-accent);font-weight:600;">Create the first one &rarr;</a>
      </div>
    <?php else: ?>
      <table class="admin-table">
        <thead>
          <tr><th>Image</th><th>Title</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($products as $p): ?>
            <tr>
              <td>
                <img src="<?= e($p['image']) ?>" alt="" style="width:50px;height:50px;object-fit:cover;border-radius:8px;"
                     onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2250%22 height=%2250%22><rect width=%2250%22 height=%2250%22 fill=%22%23e2e8f0%22/><text x=%2225%22 y=%2230%22 font-size=%2220%22 text-anchor=%22middle%22>🔧</text></svg>'">
              </td>
              <td>
                <strong><?= e($p['title']) ?></strong>
                <?php if (!empty($p['badge'])): ?><span class="badge badge--done" style="margin-left:6px;"><?= e($p['badge']) ?></span><?php endif; ?>
              </td>
              <td style="font-size:0.85rem;"><?= e(str_replace('_', ' ', $p['category'])) ?></td>
              <td><strong><?= money((float)$p['price']) ?></strong></td>
              <td><?= (int)$p['stock'] ?></td>
              <td>
                <?php if ($p['is_active']): ?>
                  <span class="badge badge--done">Visible</span>
                <?php else: ?>
                  <span class="badge badge--cancelled">Hidden</span>
                <?php endif; ?>
              </td>
              <td>
                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                  <a href="product_edit.php?id=<?= (int)$p['id'] ?>" class="btn btn--outline btn--sm">Edit</a>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button class="btn btn--ghost btn--sm" style="color:var(--c-warning);"><?= $p['is_active'] ? 'Hide' : 'Show' ?></button>
                  </form>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this product?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button class="btn btn--ghost btn--sm" style="color:var(--c-error);">Delete</button>
                  </form>
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