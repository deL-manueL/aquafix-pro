<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$user = current_user();
$id   = (int)($_GET['id'] ?? 0);

$order = db_run(
    "SELECT * FROM orders WHERE id = ? AND (user_id = ? OR customer_email = ?) LIMIT 1",
    [$id, $user['id'], $user['email']]
)->fetch();

if (!$order) {
    set_flash('Order not found.', 'error');
    redirect('my-orders.php');
}

$items  = db_run("SELECT * FROM order_items WHERE order_id = ?", [$id])->fetchAll();
$events = db_run("SELECT * FROM order_events WHERE order_id = ? ORDER BY created_at ASC", [$id])->fetchAll();

$stages = [
    'placed'     => ['label' => 'Order Placed',      'icon' => '📝'],
    'processing' => ['label' => 'Processing',        'icon' => '⚙️'],
    'shipped'    => ['label' => 'Out for Delivery',  'icon' => '🚚'],
    'delivered'  => ['label' => 'Delivered',         'icon' => '✅'],
];
$currentIdx  = array_search($order['tracking_stage'], array_keys($stages), true);
$isCancelled = $order['tracking_stage'] === 'cancelled';

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

/* -------- Refund eligibility -------- */
$paymentStatus = $order['payment_status'];
$refundStage   = $order['refund_status'] ?? 'none';

$canRequestRefund = (
    $paymentStatus === 'paid'
    && in_array($order['tracking_stage'], ['delivered','cancelled','shipped'], true)
    && $refundStage === 'none'
);

$refundPending    = in_array($refundStage, ['requested','approved'], true);
$refundRejected   = $refundStage === 'rejected';
$refundProcessed  = ($paymentStatus === 'refunded') || ($refundStage === 'processed');

$refundAmount = $order['refund_amount'] ?? $order['total'];

$flash = get_flash();
$pageTitle = 'Order ' . $order['order_number'] . ' — ' . SITE_NAME;
$baseHref = '../';
include __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
  <div class="container">
    <h1>Order <?= e($order['order_number']) ?></h1>
    <p>Placed on <?= e(date('F j, Y \a\t g:i A', strtotime($order['created_at']))) ?></p>
  </div>
</section>

<section class="section">
  <div class="container" style="max-width:960px;">

    <div class="filter-bar" style="justify-content:flex-start;">
      <a href="my-orders.php" class="filter-btn active">← All Orders</a>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert--<?= e($flash['type']) ?>" style="margin-top:20px;"><?= e($flash['msg']) ?></div>
    <?php endif; ?>

    <?php if (!$isCancelled): ?>
      <div class="card card__body" style="margin-top:24px;">
        <h3 style="margin-bottom:24px;">Order Tracking</h3>
        <div style="display:flex;justify-content:space-between;position:relative;">
          <?php $i = 0; foreach ($stages as $key => $stage): ?>
            <?php $done = $currentIdx !== false && $i <= $currentIdx; ?>
            <div style="flex:1;text-align:center;position:relative;z-index:2;">
              <div style="width:56px;height:56px;border-radius:50%;margin:0 auto 12px;display:grid;place-items:center;font-size:1.5rem;
                background:<?= $done ? 'linear-gradient(135deg,#1e3a8a,#0ea5e9)' : '#e2e8f0' ?>;
                color:<?= $done ? '#fff' : '#94a3b8' ?>;">
                <?= $stage['icon'] ?>
              </div>
              <div style="font-weight:600;font-size:0.9rem;color:<?= $done ? 'var(--c-primary)' : 'var(--c-text-muted)' ?>;">
                <?= $stage['label'] ?>
              </div>
            </div>
            <?php if ($i < count($stages) - 1): ?>
              <div style="position:absolute;top:28px;left:<?= (100 / count($stages)) * ($i + 0.5) ?>%;width:<?= 100 / count($stages) ?>%;height:2px;
                background:<?= $done && $i < $currentIdx ? 'var(--c-primary)' : '#e2e8f0' ?>;z-index:1;"></div>
            <?php endif; ?>
            <?php $i++; endforeach; ?>
        </div>
      </div>
    <?php else: ?>
      <div class="alert alert--error" style="margin-top:24px;">
        ⚠️ This order has been <strong>cancelled</strong>.
        <?php if ($paymentStatus === 'paid' && !$refundProcessed): ?>
          <div style="margin-top:8px;font-size:0.9rem;">You can request a refund below since payment was already made.</div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="card card__body" style="margin-top:20px;">
      <h3 style="margin-bottom:16px;">Order Details</h3>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;" class="order-info-grid">
        <div>
          <h4 style="font-size:0.85rem;color:var(--c-text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:10px;">Delivery</h4>
          <p><strong><?= e($order['customer_name']) ?></strong></p>
          <p><?= e($order['customer_email']) ?></p>
          <p><?= e($order['customer_phone']) ?></p>
          <p style="margin-top:8px;"><?= nl2br(e($order['shipping_address'] ?? '')) ?></p>
          <?php if (!empty($order['shipping_city'])): ?><p><?= e($order['shipping_city']) ?></p><?php endif; ?>
        </div>
        <div>
          <h4 style="font-size:0.85rem;color:var(--c-text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:10px;">Payment</h4>
          <p><strong>Method:</strong> <?= e($payLabel) ?></p>
          <p><strong>Status:</strong>
            <span class="badge badge--<?= $paymentStatus === 'paid' ? 'done' : ($paymentStatus === 'refunded' ? 'cancelled' : 'pending') ?>">
              <?= ucfirst(str_replace('_', ' ', $paymentStatus)) ?>
            </span>
          </p>
          <?php if (!empty($order['notes'])): ?>
            <p style="margin-top:12px;"><strong>Notes:</strong> <?= e($order['notes']) ?></p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- ============ REFUND STATUS BOX ============ -->
    <?php if ($refundPending || $refundRejected || $refundProcessed): ?>
      <div class="card card__body" style="margin-top:20px;border-left:5px solid <?= $refundProcessed ? '#10b981' : ($refundRejected ? '#ef4444' : '#f59e0b') ?>;">
        <h3 style="margin-bottom:16px;">💸 Refund Status</h3>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
          <div>
            <div style="font-size:0.8rem;color:var(--c-text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">Refund Amount</div>
            <div style="font-size:1.5rem;font-weight:800;color:var(--c-primary);"><?= money((float)$refundAmount) ?></div>
          </div>
          <div>
            <div style="font-size:0.8rem;color:var(--c-text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">Status</div>
            <div style="font-size:1.1rem;font-weight:700;">
              <?php
                $refundStatusLabels = [
                    'requested' => '⏳ Pending Review',
                    'approved'  => '✅ Approved — Processing',
                    'rejected'  => '❌ Rejected',
                    'processed' => '✅ Refunded',
                ];
                echo $refundStatusLabels[$refundStage] ?? ucfirst($refundStage);
              ?>
            </div>
          </div>
        </div>

        <?php if (!empty($order['refund_reason'])): ?>
          <div style="margin-top:16px;">
            <div style="font-size:0.8rem;color:var(--c-text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">Your Reason</div>
            <p style="margin:0;font-size:0.92rem;"><?= nl2br(e($order['refund_reason'])) ?></p>
          </div>
        <?php endif; ?>

        <?php if ($refundRejected && !empty($order['refund_admin_note'])): ?>
          <div style="margin-top:16px;padding:12px 16px;background:#fef2f2;border-radius:8px;">
            <div style="font-size:0.8rem;color:#991b1b;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">Reason For Rejection</div>
            <p style="margin:0;font-size:0.9rem;color:#991b1b;"><?= nl2br(e($order['refund_admin_note'])) ?></p>
          </div>
        <?php endif; ?>

        <?php if ($refundProcessed): ?>
          <div style="margin-top:16px;padding:12px 16px;background:#ecfdf5;border-radius:8px;">
            <p style="margin:0;font-size:0.9rem;color:#065f46;">
              ✅ Your refund has been processed. Funds will arrive in your original payment method within 3–5 business days.
            </p>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="card card__body" style="margin-top:20px;">
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
          <tr><td colspan="3" style="text-align:right;">Tax (10%)</td><td style="text-align:right;"><?= money((float)$order['tax']) ?></td></tr>
          <tr style="background:var(--c-bg-alt);">
            <td colspan="3" style="text-align:right;font-weight:700;">Total</td>
            <td style="text-align:right;font-weight:700;color:var(--c-primary);font-size:1.1rem;"><?= money((float)$order['total']) ?></td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- ============ ACTION BUTTONS ============ -->
    <div style="display:flex;gap:12px;margin-top:20px;flex-wrap:wrap;">
      <?php if (in_array($order['tracking_stage'], ['placed','processing'], true)): ?>
        <form method="POST" action="order-action.php" onsubmit="return confirm('Cancel this order?');">
          <input type="hidden" name="action" value="cancel">
          <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
          <button class="btn btn--outline" style="color:var(--c-error);border-color:var(--c-error);">Cancel Order</button>
        </form>
      <?php endif; ?>

      <?php if ($canRequestRefund): ?>
        <button type="button" class="btn btn--outline" style="color:var(--c-warning);border-color:var(--c-warning);"
                onclick="document.getElementById('refundModal').style.display='flex';">
          💸 Request Refund
        </button>
      <?php endif; ?>

      <?php if (in_array($order['tracking_stage'], ['delivered','cancelled'], true)): ?>
        <form method="POST" action="order-action.php" onsubmit="return confirm('Remove this order from your history?');">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
          <button class="btn btn--ghost" style="color:var(--c-text-muted);">🗑 Delete from History</button>
        </form>
      <?php endif; ?>
    </div>

  </div>
</section>

<!-- ============ REFUND REQUEST MODAL ============ -->
<div id="refundModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;padding:24px;">
  <div class="card" style="max-width:500px;width:100%;background:#fff;border-radius:16px;overflow:hidden;">
    <div style="padding:20px 24px;border-bottom:1px solid var(--c-border);display:flex;justify-content:space-between;align-items:center;">
      <h3 style="margin:0;">💸 Request a Refund</h3>
      <button type="button" onclick="document.getElementById('refundModal').style.display='none';"
              style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--c-text-muted);">×</button>
    </div>

    <form method="POST" action="order-action.php" style="padding:24px;">
      <input type="hidden" name="action" value="refund">
      <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">

      <div style="background:var(--c-bg-alt);padding:16px;border-radius:8px;margin-bottom:20px;">
        <div style="font-size:0.85rem;color:var(--c-text-muted);">Refund Amount</div>
        <div style="font-size:1.5rem;font-weight:800;color:var(--c-primary);"><?= money((float)$refundAmount) ?></div>
      </div>

      <div class="form-group">
        <label>Why do you want a refund? <span class="req">*</span></label>
        <select name="reason" class="form-control" required>
          <option value="">-- Choose a reason --</option>
          <option value="Wrong item delivered">Wrong item delivered</option>
          <option value="Item arrived damaged">Item arrived damaged</option>
          <option value="Item not as described">Item not as described</option>
          <option value="Order arrived late">Order arrived late</option>
          <option value="Changed my mind">Changed my mind</option>
          <option value="Order was cancelled after payment">Order was cancelled after payment</option>
          <option value="Other">Other (describe below)</option>
        </select>
      </div>

      <div class="form-group">
        <label>Additional details <small style="color:var(--c-text-muted);">(optional)</small></label>
        <textarea name="reason_details" class="form-control" rows="3" placeholder="Tell us more about the issue..."></textarea>
      </div>

      <div class="alert alert--info" style="font-size:0.85rem;">
        ℹ️ Refunds are processed within 3–5 business days to your original payment method after approval.
      </div>

      <div style="display:flex;gap:12px;margin-top:16px;">
        <button type="button" class="btn btn--outline" style="flex:1;"
                onclick="document.getElementById('refundModal').style.display='none';">Cancel</button>
        <button type="submit" class="btn btn--accent" style="flex:1;">Submit Refund Request</button>
      </div>
    </form>
  </div>
</div>

<style>
@media (max-width:700px) { .order-info-grid { grid-template-columns:1fr !important; } }
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>