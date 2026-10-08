<?php
/**
 * Generate & stream a PDF invoice for a completed booking.
 */

require_once __DIR__ . '/../includes/auth.php';
require_login();

/* --- Load Composer autoload --- */
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    die('DomPDF not installed. Run: composer require dompdf/dompdf');
}
require_once $autoload;

use Dompdf\Dompdf;
use Dompdf\Options;

$user      = current_user();
$bookingId = (int)($_GET['booking'] ?? 0);

/* --- Load booking (must belong to this user) --- */
$booking = db_run(
    "SELECT b.*, s.title AS service_title, s.price AS service_price
     FROM bookings b
     LEFT JOIN services s ON s.id = b.service_id
     WHERE b.id = ? AND (b.user_id = ? OR b.email = ?) LIMIT 1",
    [$bookingId, $user['id'], $user['email']]
)->fetch();

if (!$booking) {
    http_response_code(404);
    die('Booking not found.');
}

/* --- Only allow invoices for completed bookings --- */
if ($booking['status'] !== 'completed') {
    http_response_code(403);
    die('Invoice is only available for completed bookings.');
}

/* --- Build invoice data --- */
$invoiceNumber = 'INV-' . date('Y') . '-' . str_pad((string)$booking['id'], 5, '0', STR_PAD_LEFT);
$invoiceDate   = date('F j, Y');
$serviceTitle  = $booking['service_title'] ?? 'Plumbing Service';
$servicePrice  = $booking['service_price'] ?? 'From $89';

/* Try to parse numeric price out of "From $89" */
$priceNumeric = 89.00;
if (preg_match('/\$([\d,]+(?:\.\d{2})?)/', (string)$servicePrice, $m)) {
    $priceNumeric = (float)str_replace(',', '', $m[1]);
}
$tax   = round($priceNumeric * 0.10, 2);
$total = $priceNumeric + $tax;

/* --- Build HTML for PDF --- */
$html = '
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 13px; margin: 0; padding: 40px; }
    .header { border-bottom: 3px solid #0ea5e9; padding-bottom: 20px; margin-bottom: 30px; }
    .header h1 { color: #1e3a8a; font-size: 26px; margin: 0 0 6px; }
    .header p { margin: 2px 0; color: #6b7280; }
    .invoice-title { font-size: 32px; color: #1e3a8a; letter-spacing: 4px; text-align: right; margin-top: -60px; }
    .meta { display: table; width: 100%; margin-bottom: 30px; }
    .meta .col { display: table-cell; width: 50%; vertical-align: top; }
    .meta h3 { font-size: 11px; letter-spacing: 2px; color: #9ca3af; text-transform: uppercase; margin: 0 0 6px; }
    .meta p { margin: 2px 0; }
    table.items { width: 100%; border-collapse: collapse; margin-top: 20px; }
    table.items th { background: #1e3a8a; color: #fff; text-align: left; padding: 12px; font-size: 12px; letter-spacing: 1px; }
    table.items td { padding: 12px; border-bottom: 1px solid #e5e7eb; }
    .right { text-align: right; }
    .total-row td { font-weight: bold; font-size: 15px; background: #f1f5f9; border-top: 2px solid #1e3a8a; }
    .badge { display: inline-block; background: #10b981; color: #fff; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: bold; letter-spacing: 1px; }
    .footer { margin-top: 60px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 11px; color: #9ca3af; }
    .footer strong { color: #1e3a8a; }
  </style>
</head>
<body>

  <div class="header">
    <h1>AquaFix Pro</h1>
    <p>Premium Plumbing Solutions</p>
    <p>123 Main Street, Springfield, IL 62704</p>
    <p>055 866 5790 • info@aquafixpro.com</p>
    <div class="invoice-title">INVOICE</div>
  </div>

  <div class="meta">
    <div class="col">
      <h3>Billed To</h3>
      <p><strong>' . htmlspecialchars($booking['name']) . '</strong></p>
      <p>' . htmlspecialchars($booking['email']) . '</p>
      <p>' . htmlspecialchars($booking['phone']) . '</p>
      <p>' . nl2br(htmlspecialchars((string)$booking['address'])) . '</p>
    </div>
    <div class="col right">
      <h3>Invoice Details</h3>
      <p><strong>Invoice #:</strong> ' . htmlspecialchars($invoiceNumber) . '</p>
      <p><strong>Date:</strong> ' . htmlspecialchars($invoiceDate) . '</p>
      <p><strong>Booking #:</strong> ' . (int)$booking['id'] . '</p>
      <p><strong>Status:</strong> <span class="badge">PAID</span></p>
    </div>
  </div>

  <table class="items">
    <thead>
      <tr>
        <th>SERVICE</th>
        <th>DATE</th>
        <th class="right">AMOUNT</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>' . htmlspecialchars($serviceTitle) . '</td>
        <td>' . htmlspecialchars(date('M j, Y', strtotime($booking['booking_date']))) . '</td>
        <td class="right">$' . number_format($priceNumeric, 2) . '</td>
      </tr>
      <tr>
        <td colspan="2" class="right">Subtotal</td>
        <td class="right">$' . number_format($priceNumeric, 2) . '</td>
      </tr>
      <tr>
        <td colspan="2" class="right">Tax (10%)</td>
        <td class="right">$' . number_format($tax, 2) . '</td>
      </tr>
      <tr class="total-row">
        <td colspan="2" class="right">TOTAL DUE</td>
        <td class="right">$' . number_format($total, 2) . '</td>
      </tr>
    </tbody>
  </table>

  <div class="footer">
    <p><strong>Thank you for choosing AquaFix Pro!</strong></p>
    <p>This invoice was generated on ' . date('F j, Y \a\t g:i A') . '</p>
    <p>Questions? Call 055 866 5790 or email info@aquafixpro.com</p>
  </div>

</body>
</html>
';

/* --- Generate PDF --- */
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

/* --- Log the action --- */
log_activity((int)$user['id'], 'invoice_download', "Downloaded invoice $invoiceNumber");

/* --- Stream to browser --- */
$filename = "AquaFix-Invoice-{$booking['id']}.pdf";
$dompdf->stream($filename, ['Attachment' => true]);
exit;