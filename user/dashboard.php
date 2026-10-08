<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = current_user();

/* ---- Load bookings ---- */
$bookings = db_run(
    "SELECT b.*, s.title AS service_title
     FROM bookings b
     LEFT JOIN services s ON s.id = b.service_id
     WHERE b.user_id = ? OR b.email = ?
     ORDER BY b.created_at DESC",
    [$user['id'], $user['email']]
)->fetchAll();

$total     = count($bookings);
$pending   = count(array_filter($bookings, fn($b) => $b['status'] === 'pending'));
$confirmed = count(array_filter($bookings, fn($b) => $b['status'] === 'confirmed'));
$completed = count(array_filter($bookings, fn($b) => $b['status'] === 'completed'));

/* ---- Load orders ---- */
$orders = db_run(
    "SELECT * FROM orders
     WHERE (user_id = ? OR customer_email = ?) AND is_deleted = 0
     ORDER BY created_at DESC",
    [$user['id'], $user['email']]
)->fetchAll();

$orderTotal     = count($orders);
$orderPending   = count(array_filter($orders, fn($o) => in_array($o['tracking_stage'], ['placed','processing'], true)));
$orderDelivered = count(array_filter($orders, fn($o) => $o['tracking_stage'] === 'delivered'));
$orderSpent     = 0;
foreach ($orders as $o) {
    if (in_array($o['payment_status'], ['paid','refund_requested','refunded'], true)) {
        $orderSpent += (float)$o['total'];
    }
}

$unread = unread_notifications((int)$user['id']);
$flash  = get_flash();

$pageTitle = 'My Dashboard — ' . SITE_NAME;
$baseHref  = '../';
include __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
  <div class="container">
    <h1>Hello, <?= e($user['name']) ?> 👋</h1>
    <p>Here's a snapshot of your account</p>
  </div>
</section>

<section class="section">
  <div class="container">

    <?php if ($flash): ?>
      <div class="alert alert--<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
    <?php endif; ?>

    <!-- ============ USER NAVIGATION (with My Orders) ============ -->
    <div class="filter-bar" style="justify-content:flex-start;">
      <a href="dashboard.php" class="filter-btn active">Dashboard</a>
      <a href="my-bookings.php" class="filter-btn">My Bookings</a>
      <a href="my-orders.php" class="filter-btn">My Orders</a>
      <a href="notifications.php" class="filter-btn">
        Notifications<?= $unread ? ' (' . $unread . ')' : '' ?>
      </a>
      <a href="profile.php" class="filter-btn">Profile</a>
      <a href="../auth/logout.php" class="filter-btn">Logout</a>
    </div>

    <!-- ============ BOOKING STATS ============ -->
    <h3 style="margin:32px 0 16px;">📅 Booking Stats</h3>
    <div class="admin-cards">
      <div class="admin-card">
        <div class="num"><?= $total ?></div>
        <div class="lbl">Total Bookings</div>
      </div>
      <div class="admin-card">
        <div class="num"><?= $pending ?></div>
        <div class="lbl">Pending</div>
      </div>
      <div class="admin-card">
        <div class="num"><?= $confirmed ?></div>
        <div class="lbl">Confirmed</div>
      </div>
      <div class="admin-card">
        <div class="num"><?= $completed ?></div>
        <div class="lbl">Completed</div>
      </div>
    </div>

    <!-- ============ ORDER STATS ============ -->
    <h3 style="margin:32px 0 16px;">🛒 Order Stats</h3>
    <div class="admin-cards">
      <div class="admin-card">
        <div class="num"><?= $orderTotal ?></div>
        <div class="lbl">Total Orders</div>
      </div>
      <div class="admin-card">
        <div class="num"><?= $orderPending ?></div>
        <div class="lbl">In Progress</div>
      </div>
      <div class="admin-card">
        <div class="num"><?= $orderDelivered ?></div>
        <div class="lbl">Delivered</div>
      </div>
      <div class="admin-card">
        <div class="num" style="font-size:1.5rem;"><?= money($orderSpent) ?></div>
        <div class="lbl">Total Spent</div>
      </div>
    </div>

    <!-- ============ RECENT BOOKINGS ============ -->
    <h3 style="margin:32px 0 16px;">Recent Bookings</h3>

    <?php if (!$bookings): ?>
      <div class="alert alert--info">
        You haven't booked any service yet.
        <a href="<?= $baseHref ?>booking.php" style="color:var(--c-accent);font-weight:600;">Book now &rarr;</a>
      </div>
    <?php else: ?>
      <table class="admin-table">
        <thead>
          <tr><th>Service</th><th>Date</th><th>Time</th><th>Status</th><th>Created</th></tr>
        </thead>
        <tbody>
          <?php foreach (array_slice($bookings, 0, 5) as $b): ?>
            <tr>
              <td><?= e($b['service_title'] ?? '—') ?></td>
              <td><?= e($b['booking_date'] ? date('M j, Y', strtotime($b['booking_date'])) : '—') ?></td>
              <td><?= e($b['time_slot']) ?></td>
              <td><span class="badge badge--<?= e($b['status']) ?>"><?= ucfirst(str_replace('_', ' ', e($b['status']))) ?></span></td>
              <td><?= e(date('M j, Y', strtotime($b['created_at']))) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div style="margin-top:16px;">
        <a href="my-bookings.php" class="btn btn--outline btn--sm">View All Bookings</a>
      </div>
    <?php endif; ?>

    <!-- ============ RECENT ORDERS ============ -->
    <h3 style="margin:40px 0 16px;">Recent Orders</h3>

    <?php if (!$orders): ?>
      <div class="alert alert--info">
        You haven't placed any order yet.
        <a href="<?= $baseHref ?>products.php" style="color:var(--c-accent);font-weight:600;">Browse products &rarr;</a>
      </div>
    <?php else: ?>
      <table class="admin-table">
        <thead>
          <tr><th>Order #</th><th>Date</th><th>Total</th><th>Payment</th><th>Tracking</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach (array_slice($orders, 0, 5) as $o): ?>
            <tr>
              <td><strong><?= e($o['order_number']) ?></strong></td>
              <td><?= e(date('M j, Y', strtotime($o['created_at']))) ?></td>
              <td><strong><?= money((float)$o['total']) ?></strong></td>
              <td>
                <span class="badge badge--<?= $o['payment_status'] === 'paid' ? 'done' : ($o['payment_status'] === 'refunded' ? 'cancelled' : 'pending') ?>">
                  <?= ucfirst(str_replace('_',' ', $o['payment_status'])) ?>
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
      <div style="margin-top:16px;">
        <a href="my-orders.php" class="btn btn--outline btn--sm">View All Orders</a>
      </div>
    <?php endif; ?>

  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>