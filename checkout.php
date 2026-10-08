<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/cart_functions.php';
require_once __DIR__ . '/includes/product_functions.php';
start_session();

if (empty($_SESSION['product_cart']) || !is_array($_SESSION['product_cart'])) {
    $_SESSION['product_cart'] = [];
}

$pkgItems = cart_items();
$pkgSub   = 0;
foreach ($pkgItems as $i) $pkgSub += (float)$i['line'];

$prodItems = [];
$prodSub   = 0;
if (!empty($_SESSION['product_cart'])) {
    $ids = array_map('intval', array_keys($_SESSION['product_cart']));
    $ids = array_filter($ids, fn($i) => $i > 0);
    if ($ids) {
        $place = implode(',', array_fill(0, count($ids), '?'));
        $rows  = db_run("SELECT * FROM products WHERE id IN ($place) AND is_active = 1", array_values($ids))->fetchAll();
        foreach ($rows as $r) {
            $qty = (int)($_SESSION['product_cart'][$r['id']] ?? 0);
            if ($qty <= 0) continue;
            $price = product_effective_price($r);
            $r['qty']  = $qty;
            $r['unit'] = $price;
            $r['line'] = $price * $qty;
            $prodItems[] = $r;
            $prodSub += $r['line'];
        }
    }
}

if (!$pkgItems && !$prodItems) {
    $_SESSION['flash_msg']  = 'Your cart is empty.';
    $_SESSION['flash_type'] = 'info';
    redirect('products.php');
}

$loggedUser = null;
if (!empty($_SESSION['user_id'])) {
    $loggedUser = db_run("SELECT id, name, email, phone FROM users WHERE id = ? LIMIT 1", [$_SESSION['user_id']])->fetch();
}

$paymentMethods = [
    'visa_mastercard'  => ['icon' => '💳', 'name' => 'Visa / Mastercard',         'desc' => 'Pay securely with your credit or debit card'],
    'paypal'           => ['icon' => '🅿️', 'name' => 'PayPal',                   'desc' => 'Pay with your PayPal account or linked card'],
    'mtn_momo'         => ['icon' => '📱', 'name' => 'MTN Mobile Money (Ghana)', 'desc' => 'Authorize payment via MTN MoMo prompt'],
    'telecel_cash'     => ['icon' => '📱', 'name' => 'Telecel Cash (Ghana)',     'desc' => 'Authorize payment via Telecel Cash prompt'],
    'cash_on_delivery' => ['icon' => '💵', 'name' => 'Cash on Delivery',         'desc' => 'Pay in cash when your order is delivered'],
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name      = trim($_POST['name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['address'] ?? '');
    $city      = trim($_POST['city'] ?? '');
    $payMethod = $_POST['payment_method'] ?? 'cash_on_delivery';
    $notes     = trim($_POST['notes'] ?? '');

    if ($name === '')    $errors[] = 'Name is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if ($phone === '')   $errors[] = 'Phone is required.';
    if ($address === '') $errors[] = 'Delivery address is required.';
    if (!array_key_exists($payMethod, $paymentMethods)) $payMethod = 'cash_on_delivery';

    if ($payMethod === 'visa_mastercard') {
        $cardNum  = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
        $cardName = trim($_POST['card_name'] ?? '');
        $cardExp  = trim($_POST['card_exp'] ?? '');
        $cardCvv  = trim($_POST['card_cvv'] ?? '');
        if (strlen($cardNum) < 13)       $errors[] = 'Valid card number required.';
        if ($cardName === '')            $errors[] = 'Cardholder name required.';
        if (!preg_match('#^\d{2}/\d{2}$#', $cardExp)) $errors[] = 'Card expiry must be MM/YY.';
        if (strlen($cardCvv) < 3)        $errors[] = 'Valid CVV required.';
    }

    if ($payMethod === 'mtn_momo') {
        $momoNum  = preg_replace('/\s+/', '', $_POST['momo_number'] ?? '');
        $momoName = trim($_POST['momo_name'] ?? '');
        if (!preg_match('/^(0|233)\d{9}$/', $momoNum)) $errors[] = 'Valid MTN number required (e.g., 0241234567).';
        if ($momoName === '')                          $errors[] = 'Name on MTN account required.';
    }

    if ($payMethod === 'telecel_cash') {
        $tcNum  = preg_replace('/\s+/', '', $_POST['tc_number'] ?? '');
        $tcName = trim($_POST['tc_name'] ?? '');
        if (!preg_match('/^(0|233)\d{9}$/', $tcNum)) $errors[] = 'Valid Telecel number required (e.g., 0501234567).';
        if ($tcName === '')                          $errors[] = 'Name on Telecel account required.';
    }

    if (!$errors) {
        $subtotal = $pkgSub + $prodSub;
        $tax      = round($subtotal * 0.10, 2);
        $total    = $subtotal + $tax;
        $orderNum = 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

        $payStatus = in_array($payMethod, ['visa_mastercard','paypal','mtn_momo','telecel_cash'], true) ? 'paid' : 'unpaid';

        try {
            db_run(
                "INSERT INTO orders
                 (user_id, order_number, customer_name, customer_email, customer_phone,
                  shipping_address, shipping_city, shipping_phone,
                  subtotal, tax, total, status, payment_method, payment_status, tracking_stage, notes, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'paid', ?, ?, 'placed', ?, NOW())",
                [
                    $loggedUser['id'] ?? null, $orderNum,
                    $name, $email, $phone,
                    $address, $city, $phone,
                    $subtotal, $tax, $total,
                    $payMethod, $payStatus, $notes
                ]
            );
            $orderId = (int) db()->lastInsertId();

            foreach ($pkgItems as $it) {
                db_run(
                    "INSERT INTO order_items (order_id, package_id, title, price, qty, item_type)
                     VALUES (?, ?, ?, ?, ?, 'package')",
                    [$orderId, (int)$it['id'], $it['title'], (float)$it['price'], (int)$it['qty']]
                );
            }

            foreach ($prodItems as $it) {
                db_run(
                    "INSERT INTO order_items (order_id, package_id, title, price, qty, item_type)
                     VALUES (?, NULL, ?, ?, ?, 'product')",
                    [$orderId, $it['title'], (float)$it['unit'], (int)$it['qty']]
                );
            }

            db_run("INSERT INTO order_events (order_id, stage, note, created_at) VALUES (?, 'placed', 'Order placed successfully', NOW())", [$orderId]);
            if ($payStatus === 'paid') {
                $methodLabel = $paymentMethods[$payMethod]['name'];
                db_run("INSERT INTO order_events (order_id, stage, note, created_at) VALUES (?, 'paid', ?, NOW())",
                    [$orderId, "Payment received via $methodLabel"]);
            }

            if ($loggedUser) {
                $methodLabel = $paymentMethods[$payMethod]['name'];
                db_run(
                    "INSERT INTO notifications (user_id, title, body, link, created_at)
                     VALUES (?, ?, ?, ?, NOW())",
                    [
                        (int)$loggedUser['id'],
                        "Order $orderNum received",
                        "Total: " . money($total) . " — Payment: $methodLabel (" . ($payStatus === 'paid' ? 'Paid' : 'Unpaid') . ")",
                        "../user/order-view.php?id=$orderId"
                    ]
                );
            }

            foreach (db_run("SELECT id FROM users WHERE role='admin'")->fetchAll() as $a) {
                db_run(
                    "INSERT INTO notifications (user_id, title, body, link, created_at)
                     VALUES (?, ?, ?, ?, NOW())",
                    [
                        (int)$a['id'],
                        "New order $orderNum",
                        "$name • " . money($total) . " • " . $paymentMethods[$payMethod]['name'],
                        "../admin/order-view.php?id=$orderId"
                    ]
                );
            }

            cart_clear();
            $_SESSION['product_cart'] = [];

            redirect('order_success.php?order=' . $orderId);
        } catch (Exception $e) {
            $errors[] = 'Order failed: ' . $e->getMessage();
        }
    }
}

$subtotal = $pkgSub + $prodSub;
$tax      = round($subtotal * 0.10, 2);
$total    = $subtotal + $tax;

$selectedMethod = $_POST['payment_method'] ?? 'cash_on_delivery';

$pageTitle  = 'Checkout — ' . SITE_NAME;
$activePage = 'shop';
include __DIR__ . '/includes/header.php';
?>

<section class="page-header">
  <div class="container">
    <h1>Checkout</h1>
    <p>Choose how you'd like to pay</p>
  </div>
</section>

<section class="section">
  <div class="container" style="max-width:1000px;">
    <?php if ($errors): ?>
      <div class="alert alert--error">
        <?php foreach ($errors as $e): ?><div><?= e($e) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 380px;gap:32px;" class="checkout-grid">
      <div>
        <form method="POST" id="checkoutForm">
          <div class="card card__body" style="margin-bottom:20px;">
            <h3 style="margin-bottom:16px;">📍 Delivery Details</h3>
            <div class="form-grid">
              <div class="form-group">
                <label>Full Name <span class="req">*</span></label>
                <input type="text" name="name" class="form-control" required value="<?= e($_POST['name'] ?? $loggedUser['name'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label>Phone <span class="req">*</span></label>
                <input type="tel" name="phone" class="form-control" required value="<?= e($_POST['phone'] ?? $loggedUser['phone'] ?? '') ?>">
              </div>
            </div>
            <div class="form-group">
              <label>Email <span class="req">*</span></label>
              <input type="email" name="email" class="form-control" required value="<?= e($_POST['email'] ?? $loggedUser['email'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label>Delivery Address <span class="req">*</span></label>
              <textarea name="address" class="form-control" rows="2" required><?= e($_POST['address'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
              <label>City</label>
              <input type="text" name="city" class="form-control" value="<?= e($_POST['city'] ?? '') ?>">
            </div>
          </div>

          <div class="card card__body" style="margin-bottom:20px;">
            <h3 style="margin-bottom:16px;">💳 Payment Method</h3>

            <?php foreach ($paymentMethods as $key => $m): ?>
              <?php $checked = ($selectedMethod === $key); ?>
              <label class="pay-method-card <?= $checked ? 'pay-method-card--active' : '' ?>"
                     data-method="<?= e($key) ?>"
                     style="display:flex;gap:14px;align-items:flex-start;padding:16px;border:2px solid var(--c-border);border-radius:12px;cursor:pointer;margin-bottom:12px;transition:all 0.2s;">
                <input type="radio" name="payment_method" value="<?= e($key) ?>" style="margin-top:4px;" <?= $checked ? 'checked' : '' ?>>
                <div style="flex:1;">
                  <div style="font-weight:700;display:flex;align-items:center;gap:8px;">
                    <span style="font-size:1.3rem;"><?= e($m['icon']) ?></span>
                    <span><?= e($m['name']) ?></span>
                  </div>
                  <div style="font-size:0.85rem;color:var(--c-text-muted);margin-top:4px;"><?= e($m['desc']) ?></div>
                </div>
              </label>
            <?php endforeach; ?>

            <!-- VISA / MASTERCARD -->
            <div id="fields_visa_mastercard" class="pay-fields" style="display:<?= $selectedMethod === 'visa_mastercard' ? 'block' : 'none' ?>;margin-top:20px;padding:20px;background:var(--c-bg-alt);border-radius:12px;">
              <div class="form-group">
                <label>Card Number</label>
                <input type="text" name="card_number" class="form-control" placeholder="4242 4242 4242 4242" maxlength="19">
              </div>
              <div class="form-group">
                <label>Cardholder Name</label>
                <input type="text" name="card_name" class="form-control" placeholder="John Smith">
              </div>
              <div class="form-grid">
                <div class="form-group">
                  <label>Expiry (MM/YY)</label>
                  <input type="text" name="card_exp" class="form-control" placeholder="12/28" maxlength="5">
                </div>
                <div class="form-group">
                  <label>CVV</label>
                  <input type="text" name="card_cvv" class="form-control" placeholder="123" maxlength="4">
                </div>
              </div>
              <p style="font-size:0.78rem;color:var(--c-text-muted);">🔒 Demo mode — no real card is charged.</p>
            </div>

            <!-- PAYPAL -->
            <div id="fields_paypal" class="pay-fields" style="display:<?= $selectedMethod === 'paypal' ? 'block' : 'none' ?>;margin-top:20px;padding:20px;background:var(--c-bg-alt);border-radius:12px;">
              <div style="display:flex;gap:14px;align-items:flex-start;">
                <div style="font-size:2rem;">🅿️</div>
                <div>
                  <strong style="display:block;margin-bottom:6px;">You'll be redirected to PayPal</strong>
                  <p style="font-size:0.88rem;color:var(--c-text-muted);margin:0;">After clicking "Place Order", you'll be sent to PayPal's secure page to complete your payment. You can use your PayPal balance or a linked card.</p>
                </div>
              </div>
              <p style="font-size:0.78rem;color:var(--c-text-muted);margin-top:16px;">🔒 In this demo, PayPal payment is simulated.</p>
            </div>

            <!-- MTN MOMO -->
            <div id="fields_mtn_momo" class="pay-fields" style="display:<?= $selectedMethod === 'mtn_momo' ? 'block' : 'none' ?>;margin-top:20px;padding:20px;background:#fff3cd;border-radius:12px;border:1px solid #ffe69c;">
              <div style="display:flex;gap:14px;align-items:flex-start;margin-bottom:16px;">
                <div style="font-size:2rem;">📱</div>
                <div>
                  <strong style="color:#856404;">MTN Mobile Money</strong>
                  <p style="font-size:0.85rem;color:#856404;margin:4px 0 0;">Enter your MTN MoMo number. You'll receive a prompt to authorize the payment.</p>
                </div>
              </div>
              <div class="form-group">
                <label>MTN MoMo Number</label>
                <input type="tel" name="momo_number" class="form-control" placeholder="024 123 4567" maxlength="13">
                <small style="color:#856404;font-size:0.78rem;">Format: 0241234567 or 233241234567</small>
              </div>
              <div class="form-group">
                <label>Name on MTN Account</label>
                <input type="text" name="momo_name" class="form-control" placeholder="John Smith">
              </div>
              <p style="font-size:0.78rem;color:#856404;">🔒 Demo mode — no real deduction occurs.</p>
            </div>

            <!-- TELECEL CASH -->
            <div id="fields_telecel_cash" class="pay-fields" style="display:<?= $selectedMethod === 'telecel_cash' ? 'block' : 'none' ?>;margin-top:20px;padding:20px;background:#fce4ec;border-radius:12px;border:1px solid #f8bbd0;">
              <div style="display:flex;gap:14px;align-items:flex-start;margin-bottom:16px;">
                <div style="font-size:2rem;">📱</div>
                <div>
                  <strong style="color:#880e4f;">Telecel Cash</strong>
                  <p style="font-size:0.85rem;color:#880e4f;margin:4px 0 0;">Enter your Telecel number. You'll receive a prompt to authorize the payment.</p>
                </div>
              </div>
              <div class="form-group">
                <label>Telecel Number</label>
                <input type="tel" name="tc_number" class="form-control" placeholder="050 123 4567" maxlength="13">
                <small style="color:#880e4f;font-size:0.78rem;">Format: 0501234567 or 233501234567</small>
              </div>
              <div class="form-group">
                <label>Name on Telecel Account</label>
                <input type="text" name="tc_name" class="form-control" placeholder="John Smith">
              </div>
              <p style="font-size:0.78rem;color:#880e4f;">🔒 Demo mode — no real deduction occurs.</p>
            </div>

            <!-- CASH ON DELIVERY -->
            <div id="fields_cash_on_delivery" class="pay-fields" style="display:<?= $selectedMethod === 'cash_on_delivery' ? 'block' : 'none' ?>;margin-top:20px;padding:20px;background:var(--c-bg-alt);border-radius:12px;">
              <div style="display:flex;gap:14px;align-items:flex-start;">
                <div style="font-size:2rem;">💵</div>
                <div>
                  <strong style="display:block;margin-bottom:6px;">Pay on Delivery</strong>
                  <p style="font-size:0.88rem;color:var(--c-text-muted);margin:0;">You'll pay in cash when the order is delivered to your address. Please have the exact amount ready.</p>
                </div>
              </div>
            </div>

          </div>

          <div class="card card__body" style="margin-bottom:20px;">
            <h3 style="margin-bottom:12px;">📝 Order Notes <span style="font-weight:400;color:var(--c-text-muted);font-size:0.85rem;">(optional)</span></h3>
            <textarea name="notes" class="form-control" rows="3"><?= e($_POST['notes'] ?? '') ?></textarea>
          </div>

          <button type="submit" class="btn btn--accent btn--block btn--lg">
            🔒 Place Order — <?= money($total) ?>
          </button>
        </form>
      </div>

      <div class="card card__body" style="height:fit-content;position:sticky;top:20px;">
        <h3 style="margin-bottom:16px;">Order Summary</h3>

        <?php if ($prodItems): ?>
          <div style="font-size:0.78rem;font-weight:600;color:var(--c-text-muted);letter-spacing:1px;margin:8px 0 6px;">📦 PRODUCTS</div>
          <?php foreach ($prodItems as $it): ?>
            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--c-border);font-size:0.9rem;">
              <span><?= e($it['title']) ?> ×<?= (int)$it['qty'] ?></span>
              <span><?= money($it['line']) ?></span>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($pkgItems): ?>
          <div style="font-size:0.78rem;font-weight:600;color:var(--c-text-muted);letter-spacing:1px;margin:14px 0 6px;">🎁 PACKAGES</div>
          <?php foreach ($pkgItems as $it): ?>
            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--c-border);font-size:0.9rem;">
              <span><?= e($it['title']) ?> ×<?= (int)$it['qty'] ?></span>
              <span><?= money((float)$it['line']) ?></span>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <div style="margin-top:16px;">
          <div style="display:flex;justify-content:space-between;padding:6px 0;">
            <span>Subtotal</span><strong><?= money($subtotal) ?></strong>
          </div>
          <div style="display:flex;justify-content:space-between;padding:6px 0;">
            <span>Tax (10%)</span><strong><?= money($tax) ?></strong>
          </div>
          <hr style="border:none;border-top:1px solid var(--c-border);margin:12px 0;">
          <div style="display:flex;justify-content:space-between;font-size:1.15rem;">
            <strong>Total</strong>
            <strong style="color:var(--c-primary);"><?= money($total) ?></strong>
          </div>
        </div>

        <a href="cart.php" style="display:block;text-align:center;margin-top:16px;font-size:0.85rem;color:var(--c-text-muted);">← Back to cart</a>
      </div>
    </div>
  </div>
</section>

<style>
@media (max-width:800px) { .checkout-grid { grid-template-columns:1fr !important; } }
.pay-method-card:hover { border-color: var(--c-accent); }
.pay-method-card--active { border-color: var(--c-primary) !important; background: #f0f7ff !important; }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const radios = document.querySelectorAll('input[name="payment_method"]');
  const cards  = document.querySelectorAll('.pay-method-card');
  const fields = document.querySelectorAll('.pay-fields');

  function update() {
    const selected = document.querySelector('input[name="payment_method"]:checked');
    if (!selected) return;
    const val = selected.value;

    cards.forEach(c => c.classList.toggle('pay-method-card--active', c.dataset.method === val));

    fields.forEach(f => f.style.display = 'none');
    const box = document.getElementById('fields_' + val);
    if (box) box.style.display = 'block';
  }

  radios.forEach(r => r.addEventListener('change', update));
  update();
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>