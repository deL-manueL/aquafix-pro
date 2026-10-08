<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = current_user();

/* Handle cancel */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $row = db_run("SELECT * FROM bookings WHERE id = ? AND (user_id = ? OR email = ?) LIMIT 1",
        [$id, $user['id'], $user['email']])->fetch();
    if ($row && in_array($row['status'], ['pending','confirmed'], true)) {
        db_run("UPDATE bookings SET status = 'cancelled' WHERE id = ?", [$id]);
        log_activity((int)$user['id'], 'booking_cancel', "Cancelled booking #$id");
        set_flash('Booking cancelled.', 'info');
    }
    redirect('my-bookings.php');
}

$bookings = db_run(
    "SELECT b.*, s.title AS service_title
     FROM bookings b
     LEFT JOIN services s ON s.id = b.service_id
     WHERE b.user_id = ? OR b.email = ?
     ORDER BY b.created_at DESC",
    [$user['id'], $user['email']]
)->fetchAll();

$flash = get_flash();
$pageTitle = 'My Bookings — ' . SITE_NAME;
$baseHref  = '../';
include __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
  <div class="container">
    <h1>My Bookings</h1>
    <p>All your past and upcoming appointments</p>
  </div>
</section>

<section class="section">
  <div class="container">

    <div class="filter-bar" style="justify-content:flex-start;">
      <a href="dashboard.php" class="filter-btn">Dashboard</a>
      <a href="my-bookings.php" class="filter-btn active">My Bookings</a>
      <a href="notifications.php" class="filter-btn">Notifications</a>
      <a href="profile.php" class="filter-btn">Profile</a>
      <a href="../auth/logout.php" class="filter-btn">Logout</a>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert--<?= e($flash['type']) ?>" style="margin-top:20px;"><?= e($flash['msg']) ?></div>
    <?php endif; ?>

    <div style="margin-top:24px;">
      <?php if (!$bookings): ?>
        <div class="alert alert--info">
          No bookings yet. <a href="<?= $baseHref ?>booking.php" style="color:var(--c-accent);font-weight:600;">Book a service &rarr;</a>
        </div>
      <?php else: ?>
        <table class="admin-table">
          <thead>
            <tr><th>#</th><th>Service</th><th>Date</th><th>Time</th><th>Status</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php foreach ($bookings as $b): ?>
              <tr>
                <td>#<?= (int)$b['id'] ?></td>
                <td><?= e($b['service_title'] ?? '—') ?></td>
                <td><?= e($b['booking_date'] ? date('M j, Y', strtotime($b['booking_date'])) : '—') ?></td>
                <td><?= e($b['time_slot']) ?></td>
                <td><span class="badge badge--<?= e($b['status']) ?>"><?= ucfirst(str_replace('_', ' ', e($b['status']))) ?></span></td>
                <td>
                  <?php if ($b['status'] === 'confirmed'): ?>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                      <a href="qr.php?booking=<?= (int)$b['id'] ?>" class="btn btn--primary btn--sm" title="Show this to your technician">
                        📱 QR Code
                      </a>
                      <form method="POST" style="display:inline;" onsubmit="return confirm('Cancel this booking?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="cancel">
                        <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                        <button class="btn btn--ghost btn--sm" style="color:var(--c-error);">Cancel</button>
                      </form>
                    </div>

                  <?php elseif ($b['status'] === 'pending'): ?>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Cancel this booking?')">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="cancel">
                      <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                      <button class="btn btn--ghost btn--sm" style="color:var(--c-error);">Cancel</button>
                    </form>

                  <?php elseif ($b['status'] === 'in_progress'): ?>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                      <span style="color:var(--c-warning);font-size:0.85rem;font-weight:600;">🚧 In Progress</span>
                      <a href="qr.php?booking=<?= (int)$b['id'] ?>" class="btn btn--ghost btn--sm">📱 View QR</a>
                    </div>

                  <?php elseif ($b['status'] === 'completed'): ?>
                    <?php
                    $hasReview = db_run("SELECT id FROM reviews WHERE booking_id = ? LIMIT 1", [(int)$b['id']])->fetch();
                    ?>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                      <?php if ($hasReview): ?>
                        <span style="color:var(--c-success);font-size:0.85rem;font-weight:600;">⭐ Reviewed</span>
                      <?php else: ?>
                        <a href="review.php?booking=<?= (int)$b['id'] ?>" class="btn btn--accent btn--sm">⭐ Rate</a>
                      <?php endif; ?>
                      <a href="invoice.php?booking=<?= (int)$b['id'] ?>" class="btn btn--outline btn--sm" target="_blank">📄 Invoice</a>
                    </div>

                  <?php else: ?>
                    &mdash;
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>