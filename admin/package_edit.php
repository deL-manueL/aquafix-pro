<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
start_session();

if (empty($_SESSION['admin_logged_in'])) redirect('login.php');

$id     = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;
$errors = [];

$pkg = [
    'slug' => '', 'title' => '', 'subtitle' => '', 'description' => '',
    'price' => '', 'price_suffix' => '', 'features' => '',
    'icon' => 'droplet', 'badge' => '', 'is_active' => 1, 'sort_order' => 0,
];

if ($isEdit) {
    $row = db_run("SELECT * FROM packages WHERE id = ? LIMIT 1", [$id])->fetch();
    if (!$row) {
        $_SESSION['pkg_flash'] = ['type' => 'error', 'msg' => 'Package not found.'];
        redirect('packages.php');
    }
    $features = json_decode($row['features'] ?? '[]', true) ?: [];
    $row['features'] = implode("\n", $features);
    $pkg = array_merge($pkg, $row);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pkg['slug']         = trim($_POST['slug'] ?? '');
    $pkg['title']        = trim($_POST['title'] ?? '');
    $pkg['subtitle']     = trim($_POST['subtitle'] ?? '');
    $pkg['description']  = trim($_POST['description'] ?? '');
    $pkg['price']        = $_POST['price'] ?? '';
    $pkg['price_suffix'] = trim($_POST['price_suffix'] ?? '');
    $pkg['features']     = trim($_POST['features'] ?? '');
    $pkg['icon']         = trim($_POST['icon'] ?? 'droplet');
    $pkg['badge']        = trim($_POST['badge'] ?? '');
    $pkg['is_active']    = !empty($_POST['is_active']) ? 1 : 0;
    $pkg['sort_order']   = (int)($_POST['sort_order'] ?? 0);

    if ($pkg['title'] === '') $errors[] = 'Title is required.';
    if ($pkg['price'] === '') $errors[] = 'Price is required.';
    if (!is_numeric($pkg['price']) || $pkg['price'] < 0) $errors[] = 'Price must be a positive number.';

    if ($pkg['slug'] === '') {
        $pkg['slug'] = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $pkg['title']));
        $pkg['slug'] = trim($pkg['slug'], '-');
    }

    if (!$errors) {
        $dup = db_run("SELECT id FROM packages WHERE slug = ? AND id <> ? LIMIT 1", [$pkg['slug'], $isEdit ? $id : 0])->fetch();
        if ($dup) $errors[] = 'That slug is already used.';
    }

    if (!$errors) {
        $features = array_values(array_filter(array_map('trim', explode("\n", $pkg['features']))));
        $featuresJson = json_encode($features);

        if ($isEdit) {
            db_run(
                "UPDATE packages SET slug=?, title=?, subtitle=?, description=?, price=?, price_suffix=?,
                 features=?, icon=?, badge=?, is_active=?, sort_order=? WHERE id=?",
                [$pkg['slug'], $pkg['title'], $pkg['subtitle'], $pkg['description'],
                 $pkg['price'], $pkg['price_suffix'] ?: null, $featuresJson, $pkg['icon'],
                 $pkg['badge'] ?: null, $pkg['is_active'], $pkg['sort_order'], $id]
            );
            $_SESSION['pkg_flash'] = ['type' => 'success', 'msg' => 'Package updated.'];
        } else {
            db_run(
                "INSERT INTO packages (slug, title, subtitle, description, price, price_suffix, features, icon, badge, is_active, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$pkg['slug'], $pkg['title'], $pkg['subtitle'], $pkg['description'],
                 $pkg['price'], $pkg['price_suffix'] ?: null, $featuresJson, $pkg['icon'],
                 $pkg['badge'] ?: null, $pkg['is_active'], $pkg['sort_order']]
            );
            $_SESSION['pkg_flash'] = ['type' => 'success', 'msg' => 'Package created.'];
        }
        redirect('packages.php');
    }
}

$icons = ['droplet','waves','flame','git-branch','shower-head','alert-triangle','siren','shield-check','sparkles'];
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $isEdit ? 'Edit' : 'Add' ?> Package — <?= e(SITE_NAME) ?></title>
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
      <a href="index.php?tab=activity">Activity Log</a>
      <a href="../index.php">View Website</a>
      <a href="logout.php" style="color:#f87171;">Logout</a>
    </nav>
  </aside>

  <main class="admin-main">
    <div class="admin-header">
      <div>
        <h2 style="margin-bottom:4px;"><?= $isEdit ? 'Edit Package' : 'Add New Package' ?></h2>
      </div>
      <a href="packages.php" class="btn btn--outline btn--sm">← Back</a>
    </div>

    <?php if ($errors): ?>
      <div class="alert alert--error">
        <?php foreach ($errors as $er): ?><div><?= e($er) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" style="max-width:760px;">
      <div class="card card__body">
        <div class="form-grid">
          <div class="form-group">
            <label>Title <span class="req">*</span></label>
            <input type="text" name="title" class="form-control" required value="<?= e($pkg['title']) ?>">
          </div>
          <div class="form-group">
            <label>Slug <small style="color:var(--c-text-muted)">(auto if blank)</small></label>
            <input type="text" name="slug" class="form-control" value="<?= e($pkg['slug']) ?>">
          </div>
        </div>
        <div class="form-group">
          <label>Subtitle</label>
          <input type="text" name="subtitle" class="form-control" value="<?= e($pkg['subtitle']) ?>">
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" class="form-control" rows="3"><?= e($pkg['description']) ?></textarea>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label>Price <span class="req">*</span></label>
            <input type="number" step="0.01" min="0" name="price" class="form-control" required value="<?= e($pkg['price']) ?>">
          </div>
          <div class="form-group">
            <label>Price Suffix</label>
            <input type="text" name="price_suffix" class="form-control" placeholder="/year" value="<?= e($pkg['price_suffix']) ?>">
          </div>
        </div>
        <div class="form-group">
          <label>Features <small style="color:var(--c-text-muted)">(one per line)</small></label>
          <textarea name="features" class="form-control" rows="6"><?= e($pkg['features']) ?></textarea>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label>Icon</label>
            <select name="icon" class="form-control">
              <?php foreach ($icons as $ic): ?>
                <option value="<?= $ic ?>" <?= $pkg['icon'] === $ic ? 'selected' : '' ?>><?= ucfirst(str_replace('-', ' ', $ic)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Badge <small style="color:var(--c-text-muted)">(optional)</small></label>
            <input type="text" name="badge" class="form-control" placeholder="MOST POPULAR" value="<?= e($pkg['badge']) ?>">
          </div>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label>Sort Order</label>
            <input type="number" name="sort_order" class="form-control" value="<?= (int)$pkg['sort_order'] ?>">
          </div>
          <div class="form-group">
            <label>Visibility</label>
            <label style="display:flex;align-items:center;gap:8px;font-weight:400;margin-top:8px;">
              <input type="checkbox" name="is_active" value="1" <?= !empty($pkg['is_active']) ? 'checked' : '' ?>>
              Show on storefront
            </label>
          </div>
        </div>
        <div style="display:flex;gap:12px;margin-top:16px;">
          <a href="packages.php" class="btn btn--outline">Cancel</a>
          <button type="submit" class="btn btn--primary" style="flex:1;"><?= $isEdit ? 'Save Changes' : 'Create Package' ?></button>
        </div>
      </div>
    </form>
  </main>
</div>

<script src="../assets/js/main.js"></script>
</body>
</html>