<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../config/currency.php';
require_once __DIR__ . '/../includes/cart_functions.php';
start_session();

if (empty($_SESSION['admin_logged_in'])) redirect('login.php');

$id = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'stage') {
        $newStage = $_POST['stage'] ?? '';
        if (in_array($newStage, ['placed','processing','shipped','delivered','cancelled'], true)) {
            db_run("UPDATE orders SET tracking_stage = ? WHERE id = ?", [$newStage, $id]);
            db_run("INSERT INTO order_events (order_id, stage, note, created_at) VALUES (?, ?, ?, NOW())",
                [$id, $newStage, "Status updated by admin"]);

            $o = db_run("SELECT * FROM orders WHERE id = ? LIMIT 1", [$id])->fetch();
            if ($o && !empty($o['user_id'])) {
                db_run(
                    "INSERT INTO notifications (user_id, title, body, link, created_at)
                     VALUES (?, ?, ?, ?, NOW())",
                    [
                        (int)$o['user_id'],
                        "Order {$o['order_number']} update",
                        "Tracking status: " . ucfirst($newStage),
                        "../user/order-view.php?id=$id"
                    ]
                );
            }
        }
    }

    if ($action === 'mark_paid') {
        db_run("UPDATE orders SET payment_status = 'paid' WHERE id = ?", [$id]);
        db_run("INSERT INTO order_events (order_id, stage, note, created_at) VALUES (?, 'paid', 'Payment received (admin)', NOW())", [$id]);
    }

    /* ---- Admin approves refund ---- */
    if ($action === 'approve_refund') {
        $amount = (float)$_POST['amount'];
        db_run("UPDATE orders SET refund_status = 'approved', refund_amount = ?, refund_admin_note = ? WHERE id = ?",
            [$amount, trim($_POST['admin_note'] ?? ''), $id]);
        db_run("INSERT INTO order_events (order_id, stage, note, created_at) VALUES (?, 'refund_approved', ?, NOW())",
            [$id, 'Refund approved for ' . money($amount)]);

        $o = db_run("SELECT * FROM orders WHERE id = ? LIMIT 1", [$id])->fetch();
        if ($o && !empty($o['user_id'])) {
            db_run(
                "INSERT INTO notifications (user_id, title, body, link, created_at)
                 VALUES (?, ?, ?, ?, NOW())",
                [
                    (int)$o['user_id'],
                    "✅ Refund approved",
                    "Your refund of " . money($amount) . " has been approved and is being processed.",
                    "../user/order-view.php?id=$id"
                ]
            );
        }
    }

    /* ---- Admin rejects refund ---- */
    if ($action === 'reject_refund') {
        $note = trim($_POST['admin_note'] ?? '');
        if ($note === '') $note = 'Refund rejected by admin';
        db_run("UPDATE orders SET refund_status = 'rejected', refund_admin_note = ? WHERE id = ?", [$note, $id]);
        db_run("INSERT INTO order_events (order_id, stage, note, created_at) VALUES (?, 'refund_rejected', ?, NOW())",
            [$id, 'Refund rejected: ' . $note]);

        $o = db_run("SELECT * FROM orders WHERE id = ? LIMIT 1", [$id])->fetch();
        if ($o && !empty($o['user_id'])) {
            db_run(
                "INSERT INTO notifications (user_id, title, body, link, created_at)
                 VALUES (?, ?, ?, ?, NOW())",
                [
                    (int)$o['user_id'],
                    "❌ Refund rejected",
                    "Your refund request was rejected. Reason: " . $note,
                    "../user/order-view.php?id=$id"
                ]
            );
        }
    }

    /* ---- Admin marks refund as processed (money sent) ---- */
    if ($action === 'process_refund') {
        $amount = (float)($_POST['amount'] ?? 0);
        if ($amount <= 0) $amount = (float) db_run("SELECT total FROM orders WHERE id = ?", [$id])->fetch()['total'];

        db_run("UPDATE orders SET payment_status = 'refunded', refund_status = 'processed', refund_amount = ?, refund_processed_at = NOW() WHERE id = ?",
            [$amount, $id]);
        db_run("INSERT INTO order_events (order_id, stage, note, created_at) VALUES (?, 'refunded', ?, NOW())",
            [$id, 'Refund processed — ' . money($amount)]);

        $o = db_run("SELECT * FROM orders WHERE id = ? LIMIT 1", [$id])->fetch();
        if ($o && !empty($o['user_id'])) {
            db_run(
                "INSERT INTO notifications (user_id, title, body, link, created_at)
                 VALUES (?, ?, ?, ?, NOW())",
                [
                    (int)$o['user_id'],
                    "💸 Refund completed",
                    "Your refund of " . money($amount) . " has been completed. Funds will arrive in 3–5 business days.",
                    "../user/order-view.php?id=$id"
                ]
            );
        }
    }

    redirect('order-view.php?id=' . $id);
}

$order = db_run("SELECT * FROM orders WHERE id = ? LIMIT 1", [$id])->fetch();
if (!$order) { redirect('orders.php'); }

$items  = db_run("SELECT * FROM order_items WHERE order_id = ?", [$id])->fetchAll();
$events = db_run("SELECT * FROM order_events WHERE order_id = ? ORDER BY created_at ASC", [$id])->fetchAll();

$payLabels = [
    'pay_now'          => '💳 Card (Pay Now)',
    'pay_on_delivery'  => '💵 Pay on Delivery',
    'visa_mastercard'  => '💳 Visa / Mastercard',
    'paypal'           => '🅿️ PayPal',
    'mtn_momo'         => '📱 MTN Mobile Money',
    'telecel_cash'     => '📱 Telecel Cash',
    'cash_on_delivery' => '💵 Cash on Delivery',
];
$payLabel = $payLabels[$order['payment_method']] ?? ucfirst(str_replace('_',' ',$order['payment_method']));

$refundStage = $order['refund_status'] ?? 'none';
$refundAmount = $order['refund_amount'] ?? $order['total'];
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order <?= e($order['order_number']) ?> — Admin</title>
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
      <a href="../index.php">View Website</a>
      <a href="logout.php" style="color:#f87171;">Logout</a>
    </nav>
  </aside>

  <main class="admin-main">
    <div class="admin-header">
      <div>
        <h2 style="margin-bottom:4px;">Order <?= e($order['order_number']) ?></h2>
        <p style="margin:0;font-size:0.9rem;"><?= e(date('F j, Y \a\t g:i A', strtotime($order['created_at']))) ?></p>
      </div>
      <a href="orders.php" class="btn btn--outline btn--sm">← All Orders</a>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;" class="order-admin-grid">

      <div>
        <div class="card card__body" style="margin-bottom:20px;">
          <h3 style="margin-bottom:16px;">Customer & Delivery</h3>
          <p><strong><?= e($order['customer_name']) ?></strong></p>
          <p><?= e($order['customer_email']) ?></p>
          <p><?= e($order['customer_phone']) ?></p>
          <p style="margin-top:8px;"><?= nl2br(e($order['shipping_address'] ?? '')) ?></p>
          <?php if (!empty($order['shipping_city'])): ?><p><?= e($order['shipping_city']) ?></p><?php endif; ?>
        </div>

        <div class="card card__body" style="margin-bottom:20px;">
          <h3 style="margin-bottom:16px;">Items</h3>
          <table class="admin-table">
            <thead><tr><th>Item</th><th>Price</th><th>Qty</th><th style="text-align:right;">Subtotal</th></tr></thead>
            <tbody>
              <?php foreach ($items as $it): ?>
                <tr>
                  <td><?= e($it['title']) ?></td>
                  <td><?= money((float)$it['price']) ?></td>
                  <td><?= (int)$it['qty'] ?></td>
                  <td style="text-align:right;"><?= money((float)$it['price'] * (int)$it['qty']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr><td colspan="3" style="text-align:right;">Subtotal</td><td style="text-align:right;"><?= money((float)$order['subtotal']) ?></td></tr>
              <tr><td colspan="3" style="text-align:right;">Tax</td><td style="text-align:right;"><?= money((float)$order['tax']) ?></td></tr>
              <tr style="background:var(--c-bg-alt);">
                <td colspan="3" style="text-align:right;font-weight:700;">Total</td>
                <td style="text-align:right;font-weight:700;"><?= money((float)$order['total']) ?></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <!-- ============ REFUND MANAGEMENT PANEL ============ -->
        <?php if ($refundStage !== 'none' || $order['payment_status'] === 'refund_requested'): ?>
          <div class="card card__body" style="border-left:5px solid <?= $refundStage === 'processed' ? '#10b981' : ($refundStage === 'rejected' ? '#ef4444' : ($refundStage === 'approved' ? '#3b82f6' : '#f59e0b')) ?>;">
            <h3 style="margin-bottom:16px;">💸 Refund Management</h3>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
              <div>
                <div style="font-size:0.75rem;color:var(--c-text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">Amount</div>
                <div style="font-size:1.4rem;font-weight:800;color:var(--c-primary);"><?= money((float)$refundAmount) ?></div>
              </div>
              <div>
                <div style="font-size:0.75rem;color:var(--c-text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">Status</div>
                <div style="font-size:1rem;font-weight:700;">
                  <?php
                    $labels = [
                        'requested' => '⏳ Pending Review',
                        'approved'  => '✅ Approved',
                        'rejected'  => '❌ Rejected',
                        'processed' => '✅ Processed',
                    ];
                    echo $labels[$refundStage] ?? ucfirst($refundStage);
                  ?>
                </div>
              </div>
            </div>

            <?php if (!empty($order['refund_reason'])): ?>
              <div style="margin-bottom:16px;">
                <div style="font-size:0.75rem;color:var(--c-text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">Customer Reason</div>
                <p style="margin:0;font-size:0.92rem;"><?= nl2br(e($order['refund_reason'])) ?></p>
              </div>
            <?php endif; ?>

            <?php if (!empty($order['refund_admin_note'])): ?>
              <div style="margin-bottom:16px;padding:12px 16px;background:var(--c-bg-alt);border-radius:8px;">
                <div style="font-size:0.75rem;color:var(--c-text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">Admin Note</div>
                <p style="margin:0;font-size:0.9rem;"><?= nl2br(e($order['refund_admin_note'])) ?></p>
              </div>
            <?php endif; ?>

            <!-- Actions depending on stage -->
            <?php if ($refundStage === 'requested'): ?>
              <div style="display:grid;gap:12px;">
                <form method="POST" style="display:grid;gap:8px;">
                  <input type="hidden" name="action" value="approve_refund">
                  <input type="hidden" name="amount" value="<?= (float)$refundAmount ?>">
                  <input type="text" name="admin_note" class="form-control" placeholder="Optional note (visible to customer only if rejected)">
                  <button class="btn btn--primary btn--sm" onclick="return confirm('Approve this refund?');">✅ Approve Refund</button>
                </form>

                <form method="POST" style="display:grid;gap:8px;">
                  <input type="hidden" name="action" value="reject_refund">
                  <input type="text" name="admin_note" class="form-control" placeholder="Reason for rejection (required)" required>
                  <button class="btn btn--outline btn--sm" style="color:var(--c-error);border-color:var(--c-error);" onclick="return confirm('Reject this refund?');">❌ Reject Refund</button>
                </form>
              </div>
            <?php elseif ($refundStage === 'approved'): ?>
              <form method="POST" style="display:grid;gap:8px;">
                <input type="hidden" name="action" value="process_refund">
                <input type="hidden" name="amount" value="<?= (float)$refundAmount ?>">
                <button class="btn btn--primary btn--sm" onclick="return confirm('Mark this refund as completed?');">💸 Mark as Processed (Money Sent)</button>
              </form>
            <?php endif; ?>

          </div>
        <?php endif; ?>
      </div>

      <div>
        <div class="card card__body" style="margin-bottom:20px;">
          <h3 style="margin-bottom:16px;">Tracking Stage</h3>
          <p style="font-size:0.85rem;color:var(--c-text-muted);margin-bottom:12px;">
            Current: <strong><?= ucfirst($order['tracking_stage']) ?></strong>
          </p>
          <form method="POST" style="display:grid;gap:8px;">
            <input type="hidden" name="action" value="stage">
            <?php foreach (['placed','processing','shipped','delivered','cancelled'] as $s): ?>
              <button type="submit" name="stage" value="<?= $s ?>"
                      class="btn <?= $order['tracking_stage'] === $s ? 'btn--primary' : 'btn--outline' ?> btn--sm"
                      <?= $order['tracking_stage'] === $s ? 'disabled' : '' ?>>
                <?= ucfirst($s) ?>
              </button>
            <?php endforeach; ?>
          </form>
        </div>

        <div class="card card__body">
          <h3 style="margin-bottom:16px;">Payment</h3>
          <p><strong>Method:</strong> <?= e($payLabel) ?></p>
          <p style="margin-top:8px;"><strong>Status:</strong>
            <span class="badge badge--<?= $order['payment_status'] === 'paid' ? 'done' : ($order['payment_status'] === 'refunded' ? 'cancelled' : 'pending') ?>">
              <?= ucfirst(str_replace('_',' ',$order['payment_status'])) ?>
            </span>
          </p>
          <?php if ($order['payment_status'] === 'unpaid'): ?>
            <form method="POST" style="margin-top:12px;">
              <input type="hidden" name="action" value="mark_paid">
              <button class="btn btn--primary btn--sm btn--block">Mark as Paid</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="card card__body" style="margin-top:20px;">
      <h3 style="margin-bottom:16px;">Order Timeline</h3>
      <?php foreach ($events as $ev): ?>
        <div style="display:flex;gap:14px;padding:10px 0;border-bottom:1px solid var(--c-border);">
          <div style="font-size:1.2rem;">•</div>
          <div style="flex:1;">
            <strong><?= e(ucfirst(str_replace('_',' ', $ev['stage']))) ?></strong>
            <div style="font-size:0.85rem;color:var(--c-text-muted);"><?= e($ev['note']) ?></div>
          </div>
          <div style="font-size:0.78rem;color:var(--c-text-muted);">
            <?= e(date('M j, Y H:i', strtotime($ev['created_at']))) ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </main>
</div>

<style>
@media (max-width:900px) { .order-admin-grid { grid-template-columns:1fr !important; } }
</style>

<script src="../assets/js/main.js"></script>
</body>
</html>