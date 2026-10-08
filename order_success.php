<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
start_session();

$orderId = (int)($_GET['order'] ?? 0);
$order = null; $items = [];
if ($orderId) {
    $order = db_run("SELECT * FROM orders WHERE id = ? LIMIT 1", [$orderId])->fetch();
    if ($order) $items = db_run("SELECT * FROM order_items WHERE order_id = ?", [$orderId])->fetchAll();
}
if (!$order) redirect('shop.php');

$pageTitle = 'Order Confirmed — ' . SITE_NAME;
include __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-top:80px;">
  <div class="container" style="max-width:640px;text-align:center;">
    <div style="width:100px;height:100px;border-radius:50%;background:rgba(16,185,129,.12);display:grid;place-items:center;margin:0 auto 24px;">
      <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="var(--c-success)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20,6 9,17 4,12"/></svg>
    </div>

    <h1>Order Confirmed!</h1>
    <p style="font-size:1.05rem;margin:16px 0 32px;">
      Thank you, <strong><?= e($order['customer_name']) ?></strong>!<br>
      Your order <strong><?= e($order['order_number']) ?></strong> has been placed.
    </p>

    <div class="card card__body" style="text-align:left;">
      <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--c-border);">
        <span style="color:var(--c-text-muted);">Payment Method</span>
        <strong><?= $order['payment_method'] === 'pay_now' ? '💳 Pay Now' : '🚚 Pay on Delivery' ?></strong>
      </div>
      <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--c-border);">
        <span style="color:var(--c-text-muted);">Payment Status</span>
        <span class="badge badge--<?= $order['payment_status'] === 'paid' ? 'done' : 'pending' ?>">
          <?= ucfirst($order['payment_status']) ?>
        </span>
      </div>
      <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--c-border);">
        <span style="color:var(--c-text-muted);">Delivery</span>
        <strong style="max-width:60%;text-align:right;"><?= e($order['shipping_address'] ?? '—') ?></strong>
      </div>

      <h4 style="margin:20px 0 12px;">Items</h4>
      <?php foreach ($items as $it): ?>
        <div style="display:flex;justify-content:space-between;padding:6px 0;font-size:0.9rem;">
          <span><?= e($it['title']) ?> × <?= (int)$it['qty'] ?></span>
          <strong>$<?= number_format((float)$it['price'] * (int)$it['qty'], 2) ?></strong>
        </div>
      <?php endforeach; ?>

      <hr style="border:none;border-top:2px solid var(--c-border);margin:16px 0 12px;">
      <div style="display:flex;justify-content:space-between;font-size:1.2rem;">
        <strong>Total</strong>
        <strong style="color:var(--c-primary);">$<?= number_format((float)$order['total'], 2) ?></strong>
      </div>
    </div>

    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:24px;">
      <?php if (!empty($_SESSION['user_id'])): ?>
        <a href="user/order-view.php?id=<?= (int)$order['id'] ?>" class="btn btn--primary btn--lg">Track Order</a>
        <a href="user/my-orders.php" class="btn btn--outline btn--lg">All My Orders</a>
      <?php endif; ?>
      <a href="shop.php" class="btn btn--outline btn--lg">Continue Shopping</a>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>