<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = current_user();

if (($_GET['mark'] ?? '') === 'all') {
    db_run("UPDATE notifications SET is_read = 1 WHERE user_id = ?", [$user['id']]);
    redirect('notifications.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'read_one') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    db_run("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?", [$id, $user['id']]);
    redirect('notifications.php');
}

$notes = db_run(
    "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100",
    [$user['id']]
)->fetchAll();

$pageTitle = 'Notifications — ' . SITE_NAME;
$baseHref  = '../';
include __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
  <div class="container">
    <h1>Notifications</h1>
    <p>Updates about your bookings</p>
  </div>
</section>

<section class="section">
  <div class="container">

    <div class="filter-bar" style="justify-content:flex-start;">
      <a href="dashboard.php" class="filter-btn">Dashboard</a>
      <a href="my-bookings.php" class="filter-btn">My Bookings</a>
      <a href="notifications.php" class="filter-btn active">Notifications</a>
      <a href="profile.php" class="filter-btn">Profile</a>
      <a href="../auth/logout.php" class="filter-btn">Logout</a>
    </div>

    <div style="text-align:right;margin:20px 0;">
      <a href="notifications.php?mark=all" class="btn btn--outline btn--sm">Mark all as read</a>
    </div>

    <?php if (!$notes): ?>
      <div class="alert alert--info">No notifications yet.</div>
    <?php else: ?>
      <div style="display:grid;gap:12px;">
        <?php foreach ($notes as $n): ?>
          <div class="card card__body" style="<?= $n['is_read'] ? 'opacity:.7;' : '' ?>">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
              <div>
                <strong><?= e($n['title']) ?></strong>
                <div style="font-size:0.85rem;color:var(--c-text-muted);">
                  <?= e(date('M j, Y H:i', strtotime($n['created_at']))) ?>
                </div>
                <p style="margin-top:8px;"><?= e($n['body']) ?></p>
                <?php if (!empty($n['link'])): ?>
                  <a href="<?= e($n['link']) ?>" class="btn btn--ghost btn--sm" style="margin-top:8px;">View</a>
                <?php endif; ?>
              </div>
              <?php if (!$n['is_read']): ?>
                <form method="POST">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="read_one">
                  <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                  <button class="btn btn--ghost btn--sm">Mark read</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>