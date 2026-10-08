<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../config/currency.php';
require_once __DIR__ . '/../includes/cart_functions.php';
start_session();

if (empty($_SESSION['admin_logged_in'])) redirect('login.php');

$stage  = $_GET['stage']  ?? '';
$status = $_GET['status'] ?? '';
$q      = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM orders WHERE is_deleted = 0";
$params = [];

if (in_array($stage, ['placed','processing','shipped','delivered','cancelled'], true)) {
    $sql .= " AND tracking_stage = ?"; $params[] = $stage;
}
if (in_array($status, ['unpaid','paid','refund_requested','refunded'], true)) {
    $sql .= " AND payment_status = ?"; $params[] = $status;
}
if ($q !== '') {
    $sql .= " AND (order_number LIKE ? OR customer_name LIKE ? OR customer_email LIKE ?)";
    $like = "%$q%";
    $params[] = $like; $params[] = $like; $params[] = $like;
}
$sql .= " ORDER BY created_at DESC LIMIT 500";

$orders = db_run($sql, $params)->fetchAll();

/* Payment method labels */
$payLabels = [
    'pay_now'          => '💳 Card (Pay Now)',
    'pay_on_delivery'  => '💵 Pay on Delivery',
    'visa_mastercard'  => '💳 Visa / Mastercard',
    'paypal'           => '🅿️ PayPal',
    'mtn_momo'         => '📱 MTN Mobile Money',
    'telecel_cash'     => '📱 Telecel Cash',
    'cash_on_delivery' => '💵 Cash on Delivery',
];
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Orders — <?= e(SITE_NAME) ?></title>
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
      <a href="orders.php" class="active">Orders</a>
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
        <h2 style="margin-bottom:4px;">Orders</h2>
        <p style="margin:0;font-size:0.9rem;"><?= count($orders) ?> order(s) found</p>
      </div>
    </div>

    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px;">
      <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search order # or name" class="form-control" style="max-width:280px;">
      <select name="stage" class="form-control" style="max-width:180px;">
        <option value="">All stages</option>
        <?php foreach (['placed','processing','shipped','delivered','cancelled'] as $s): ?>
          <option value="<?= $s ?>" <?= $stage === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="status" class="form-control" style="max-width:180px;">
        <option value="">All payments</option>
        <?php foreach (['unpaid','paid','refund_requested','refunded'] as $s): ?>
          <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn--primary btn--sm">Filter</button>
      <a href="orders.php" class="btn btn--ghost btn--sm">Clear</a>
    </form>

    <?php if (!$orders): ?>
      <div class="alert alert--info">No orders match your filters.</div>
    <?php else: ?>
      <table class="admin-table">
        <thead>
          <tr><th>Order #</th><th>Customer</th><th>Total</th><th>Payment</th><th>Stage</th><th>Date</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td><strong><?= e($o['order_number']) ?></strong></td>
              <td><?= e($o['customer_name']) ?><br><small style="color:var(--c-text-muted);"><?= e($o['customer_email']) ?></small></td>
              <td><?= money((float)$o['total']) ?></td>
              <td>
                <span class="badge badge--<?= $o['payment_status'] === 'paid' ? 'done' : ($o['payment_status'] === 'refunded' ? 'cancelled' : 'pending') ?>">
                  <?= ucfirst(str_replace('_',' ',$o['payment_status'])) ?>
                </span>
              </td>
              <td>
                <span class="badge badge--<?= $o['tracking_stage'] === 'delivered' ? 'done' : ($o['tracking_stage'] === 'cancelled' ? 'cancelled' : 'pending') ?>">
                  <?= ucfirst($o['tracking_stage']) ?>
                </span>
              </td>
              <td><?= e(date('M j, Y H:i', strtotime($o['created_at']))) ?></td>
              <td><a href="order-view.php?id=<?= (int)$o['id'] ?>" class="btn btn--outline btn--sm">Manage</a></td>
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