<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/product_functions.php';
start_session();

if (empty($_SESSION['admin_logged_in'])) redirect('login.php');

$id     = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;
$errors = [];

$prod = [
    'slug'       => '',
    'title'      => '',
    'short_desc' => '',
    'description'=> '',
    'category'   => 'water_storage',
    'price'      => '',
    'sale_price' => '',
    'stock'      => 50,
    'sku'        => '',
    'image'      => '',
    'badge'      => '',
    'is_active'  => 1,
];

/* Load existing */
if ($isEdit) {
    $row = db_run("SELECT * FROM products WHERE id = ? LIMIT 1", [$id])->fetch();
    if (!$row) {
        $_SESSION['prod_flash'] = ['type' => 'error', 'msg' => 'Product not found.'];
        redirect('products.php');
    }
    $prod = array_merge($prod, $row);
}

/* Save */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prod['slug']        = trim($_POST['slug'] ?? '');
    $prod['title']       = trim($_POST['title'] ?? '');
    $prod['short_desc']  = trim($_POST['short_desc'] ?? '');
    $prod['description'] = trim($_POST['description'] ?? '');
    $prod['category']    = trim($_POST['category'] ?? 'water_storage');
    $prod['price']       = $_POST['price'] ?? '';
    $prod['sale_price']  = $_POST['sale_price'] ?? '';
    $prod['stock']       = (int)($_POST['stock'] ?? 0);
    $prod['sku']         = trim($_POST['sku'] ?? '');
    $prod['image']       = trim($_POST['image'] ?? '');
    $prod['badge']       = trim($_POST['badge'] ?? '');
    $prod['is_active']   = !empty($_POST['is_active']) ? 1 : 0;

    if ($prod['title'] === '') $errors[] = 'Title is required.';
    if ($prod['price'] === '' || !is_numeric($prod['price']) || $prod['price'] < 0) $errors[] = 'Valid price required.';

    if ($prod['slug'] === '') {
        $prod['slug'] = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $prod['title']));
        $prod['slug'] = trim($prod['slug'], '-');
    }

    if (!$errors) {
        $dup = db_run("SELECT id FROM products WHERE slug = ? AND id <> ? LIMIT 1", [$prod['slug'], $isEdit ? $id : 0])->fetch();
        if ($dup) $errors[] = 'That slug is already used.';
    }

    if (!$errors) {
        $salePrice = ($prod['sale_price'] !== '' && is_numeric($prod['sale_price'])) ? (float)$prod['sale_price'] : null;

        if ($isEdit) {
            db_run(
                "UPDATE products SET slug=?, title=?, short_desc=?, description=?, category=?,
                 price=?, sale_price=?, stock=?, sku=?, image=?, badge=?, is_active=? WHERE id=?",
                [$prod['slug'], $prod['title'], $prod['short_desc'], $prod['description'], $prod['category'],
                 $prod['price'], $salePrice, $prod['stock'], $prod['sku'] ?: null,
                 $prod['image'], $prod['badge'] ?: null, $prod['is_active'], $id]
            );
            $_SESSION['prod_flash'] = ['type' => 'success', 'msg' => 'Product updated.'];
        } else {
            db_run(
                "INSERT INTO products (slug, title, short_desc, description, category, price, sale_price,
                  stock, sku, image, badge, is_active, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [$prod['slug'], $prod['title'], $prod['short_desc'], $prod['description'], $prod['category'],
                 $prod['price'], $salePrice, $prod['stock'], $prod['sku'] ?: null,
                 $prod['image'], $prod['badge'] ?: null, $prod['is_active']]
            );
            $_SESSION['prod_flash'] = ['type' => 'success', 'msg' => 'Product created.'];
        }
        redirect('products.php');
    }
}

$categories = product_categories();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $isEdit ? 'Edit' : 'Add' ?> Product — Admin</title>
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
        <h2 style="margin-bottom:4px;"><?= $isEdit ? 'Edit Product' : 'Add New Product' ?></h2>
      </div>
      <a href="products.php" class="btn btn--outline btn--sm">← Back</a>
    </div>

    <?php if ($errors): ?>
      <div class="alert alert--error">
        <?php foreach ($errors as $er): ?><div><?= e($er) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" style="max-width:800px;">
      <div class="card card__body">
        <div class="form-grid">
          <div class="form-group">
            <label>Title <span class="req">*</span></label>
            <input type="text" name="title" class="form-control" required value="<?= e($prod['title']) ?>">
          </div>
          <div class="form-group">
            <label>Slug <small style="color:var(--c-text-muted)">(auto if blank)</small></label>
            <input type="text" name="slug" class="form-control" value="<?= e($prod['slug']) ?>">
          </div>
        </div>

        <div class="form-group">
          <label>Short Description</label>
          <input type="text" name="short_desc" class="form-control" value="<?= e($prod['short_desc']) ?>">
        </div>

        <div class="form-group">
          <label>Full Description</label>
          <textarea name="description" class="form-control" rows="4"><?= e($prod['description']) ?></textarea>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label>Category <span class="req">*</span></label>
            <select name="category" class="form-control" required>
              <?php foreach ($categories as $key => $label): ?>
                <option value="<?= $key ?>" <?= $prod['category'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Badge <small style="color:var(--c-text-muted)">(optional)</small></label>
            <input type="text" name="badge" class="form-control" placeholder="NEW" value="<?= e($prod['badge']) ?>">
          </div>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label>Price <span class="req">*</span></label>
            <input type="number" step="0.01" min="0" name="price" class="form-control" required value="<?= e($prod['price']) ?>">
          </div>
          <div class="form-group">
            <label>Sale Price <small style="color:var(--c-text-muted)">(optional)</small></label>
            <input type="number" step="0.01" min="0" name="sale_price" class="form-control" value="<?= e($prod['sale_price']) ?>">
          </div>
        </div>

        <div class="form-grid">
          <div class="form-group">
            <label>Stock</label>
            <input type="number" min="0" name="stock" class="form-control" value="<?= (int)$prod['stock'] ?>">
          </div>
          <div class="form-group">
            <label>SKU <small style="color:var(--c-text-muted)">(optional)</small></label>
            <input type="text" name="sku" class="form-control" value="<?= e($prod['sku']) ?>">
          </div>
        </div>

        <div class="form-group">
          <label>Image URL</label>
          <input type="text" name="image" class="form-control"
                 placeholder="https://example.com/product.jpg"
                 value="<?= e($prod['image']) ?>">
          <small style="color:var(--c-text-muted);">Paste a URL to an image, or leave blank to show icon</small>
        </div>

        <?php if (!empty($prod['image'])): ?>
          <div style="margin:12px 0;">
            <img src="<?= e($prod['image']) ?>" alt="Preview" style="max-width:150px;border-radius:8px;">
          </div>
        <?php endif; ?>

        <div class="form-group">
          <label>Visibility</label>
          <label style="display:flex;align-items:center;gap:8px;font-weight:400;margin-top:8px;">
            <input type="checkbox" name="is_active" value="1" <?= !empty($prod['is_active']) ? 'checked' : '' ?>>
            Show on storefront
          </label>
        </div>

        <div style="display:flex;gap:12px;margin-top:16px;">
          <a href="products.php" class="btn btn--outline">Cancel</a>
          <button type="submit" class="btn btn--primary" style="flex:1;"><?= $isEdit ? 'Save Changes' : 'Create Product' ?></button>
        </div>
      </div>
    </form>
  </main>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>