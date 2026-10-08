<?php
/**
 * Handles order actions: cancel, refund request, soft-delete.
 */

require_once __DIR__ . '/../includes/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('my-orders.php');
}

$user   = current_user();
$action = $_POST['action'] ?? '';
$id     = (int)($_POST['id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');
$details = trim($_POST['reason_details'] ?? '');

$order = db_run(
    "SELECT * FROM orders WHERE id = ? AND (user_id = ? OR customer_email = ?) LIMIT 1",
    [$id, $user['id'], $user['email']]
)->fetch();

if (!$order) {
    set_flash('Order not found.', 'error');
    redirect('my-orders.php');
}

$refundStage = $order['refund_status'] ?? 'none';

switch ($action) {

    case 'cancel':
        if (!in_array($order['tracking_stage'], ['placed','processing'], true)) {
            set_flash('This order can no longer be cancelled.', 'info');
            break;
        }
        db_run("UPDATE orders SET tracking_stage = 'cancelled', status = 'cancelled' WHERE id = ?", [$id]);
        db_run("INSERT INTO order_events (order_id, stage, note, created_at) VALUES (?, 'cancelled', 'Order cancelled by customer', NOW())", [$id]);
        log_activity((int)$user['id'], 'order_cancel', "Cancelled order {$order['order_number']}");
        set_flash('Order cancelled.', 'info');
        break;

    case 'refund':
        /* ---- Validate eligibility ---- */
        if ($order['payment_status'] !== 'paid') {
            set_flash('This order is not eligible for a refund.', 'info');
            break;
        }
        if (!in_array($order['tracking_stage'], ['delivered','cancelled','shipped'], true)) {
            set_flash('Refunds are only available after the order is shipped, delivered, or cancelled.', 'info');
            break;
        }
        if ($refundStage !== 'none') {
            set_flash('A refund has already been requested for this order.', 'info');
            break;
        }
        if ($reason === '') {
            set_flash('Please select a reason for your refund.', 'error');
            break;
        }

        /* ---- Combine reason + details ---- */
        $fullReason = $reason;
        if ($details !== '') $fullReason .= "\n\n" . $details;

        /* ---- Save refund request ---- */
        db_run(
            "UPDATE orders SET 
                payment_status = 'refund_requested',
                refund_status = 'requested',
                refund_reason = ?,
                refund_amount = ?,
                refund_requested_at = NOW()
             WHERE id = ?",
            [$fullReason, (float)$order['total'], $id]
        );

        db_run(
            "INSERT INTO order_events (order_id, stage, note, created_at) 
             VALUES (?, 'refund_requested', ?, NOW())",
            [$id, 'Refund requested: ' . $reason]
        );

        /* ---- Notify admin ---- */
        foreach (db_run("SELECT id FROM users WHERE role='admin'")->fetchAll() as $a) {
            db_run(
                "INSERT INTO notifications (user_id, title, body, link, created_at)
                 VALUES (?, ?, ?, ?, NOW())",
                [
                    (int)$a['id'],
                    "💸 Refund requested",
                    "Order {$order['order_number']}: " . $reason,
                    "../admin/order-view.php?id=$id"
                ]
            );
        }

        log_activity((int)$user['id'], 'refund_request', "Requested refund for {$order['order_number']}");
        set_flash('Refund request submitted. Our team will review it shortly.', 'success');
        break;

    case 'delete':
        if (!in_array($order['tracking_stage'], ['delivered','cancelled'], true)) {
            set_flash('Only delivered or cancelled orders can be removed.', 'info');
            break;
        }
        db_run("UPDATE orders SET is_deleted = 1 WHERE id = ?", [$id]);
        log_activity((int)$user['id'], 'order_delete', "Removed order {$order['order_number']} from history");
        set_flash('Order removed from your history.', 'info');
        redirect('my-orders.php');
        break;

    default:
        set_flash('Unknown action.', 'error');
}

redirect('order-view.php?id=' . $id);