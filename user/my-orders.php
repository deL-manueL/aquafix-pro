<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = current_user();

$orders = db_run(
    "SELECT o.*,
            (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
     FROM orders o
     WHERE (o.user_id = ? OR o.customer_email = ?) AND o.is_deleted = 0
     ORDER BY o.created_at DESC",
    [$user['id'], $user['email']]
)->fetchAll();

$flash = get_flash();
$pageTitle = 'My Orders — ' . SITE_NAME;
$baseHref = '../';
include __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
  <div class="container">
    <h1>My Orders</h1>
    <p>Your purchase history and tracking</p>
  </div>
</section>

<section class="section">
  <div class="container">

    <div class="filter-bar" style="justify-content:flex-start;">
      <a href="dashboard.php" class="filter-btn">Dashboard</a>
      <a href="my-bookings.php" class="filter-btn">My Bookings</a>
      <a href="my-orders.php" class="filter-btn active">My Orders</a>
      <a href="notifications.php" class="filter-btn">Notifications</a>
      <a href="profile.php" class="filter-btn">Profile</a>
      <a href="../auth/logout.php" class="filter-btn">Logout</a>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert--<?= e($flash['type']) ?>" style="margin-top:20px;"><?= e($flash['msg']) ?></div>
    <?php endif; ?>

    <div style="margin-top:24px;">
      <?php if (!$orders): ?>
        <div class="alert alert--info">
          No orders yet. <a href="../shop.php" style="color:var(--c-accent);font-weight:600;">Browse the shop &rarr;</a>
        </div>
      <?php else: ?>
        <table class="admin-table">
          <thead>
            <tr><th>Order #</th><th>Date</th><th>Items</th><th>Total</th><th>Payment</th><th>Tracking</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $o): ?>
              <tr>
                <td><strong><?= e($o['order_number']) ?></strong></td>
                <td><?= e(date('M j, Y', strtotime($o['created_at']))) ?></td>
                <td><?= (int)$o['item_count'] ?></td>
                <td><strong>$<?= number_format((float)$o['total'], 2) ?></strong></td>
                <td>
                  <span class="badge badge--<?= $o['payment_status'] === 'paid' ? 'done' : ($o['payment_status'] === 'refunded' ? 'cancelled' : 'pending') ?>">
                    <?= ucfirst(str_replace('_', ' ', $o['payment_status'])) ?>
                  </span>
                </td>
                <td>
                  <span class="badge badge--<?= $o['tracking_stage'] === 'delivered' ? 'done' : ($o['tracking_stage'] === 'cancelled' ? 'cancelled' : 'pending') ?>">
                    <?= ucfirst($o['tracking_stage']) ?>
                  </span>
                </td>
                <td>
                  <a href="order-view.php?id=<?= (int)$o['id'] ?>" class="btn btn--outline btn--sm">View</a>
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