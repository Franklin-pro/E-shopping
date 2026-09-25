<?php
session_start();

/* =========================================================
   Validate the requested order ID
   ========================================================= */
$orderId = $_GET['order'] ?? '';

if ($orderId === '' || empty($_SESSION['orders'][$orderId])) {
    header('Location: orders.php');
    exit;
}

$order = $_SESSION['orders'][$orderId];

/* =========================================================
   Helpers
   ========================================================= */
function format_price($n) {
    return '$' . number_format($n, 2);
}

function e($s) {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/* =========================================================
   Build a standalone HTML receipt
   ========================================================= */
$itemCount = 0;
foreach ($order['items'] as $row) {
    $itemCount += (int) $row['qty'];
}

// Filename the browser will save it as
$filename = 'order-' . preg_replace('/[^A-Za-z0-9\-]/', '', $order['id']) . '.html';

// Send download headers BEFORE any output
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order <?= e($order['id']) ?> — E-SHOPPING</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #222;
            background: #f7f7f7;
            margin: 0;
            padding: 40px 20px;
            font-size: 14px;
            line-height: 1.5;
        }
        .receipt {
            max-width: 720px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 12px;
            padding: 40px;
        }
        .receipt-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #111;
            padding-bottom: 20px;
            margin-bottom: 24px;
        }
        .receipt-header h1 {
            margin: 0;
            font-size: 24px;
            letter-spacing: .05em;
        }
        .receipt-header .meta {
            text-align: right;
            font-size: 13px;
            color: #666;
        }
        .receipt-header .meta strong {
            display: block;
            color: #111;
            font-size: 15px;
            margin-bottom: 4px;
            font-family: monospace;
        }
        h2 {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #888;
            margin: 24px 0 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        table th, table td {
            text-align: left;
            padding: 10px 8px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        table th {
            background: #fafafa;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #666;
        }
        table td.num, table th.num {
            text-align: right;
            white-space: nowrap;
        }
        .totals {
            margin-top: 20px;
            margin-left: auto;
            width: 280px;
        }
        .totals .row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 13px;
            color: #555;
        }
        .totals .row.total {
            border-top: 1px solid #111;
            margin-top: 8px;
            padding-top: 12px;
            font-size: 16px;
            font-weight: bold;
            color: #111;
        }
        .shipping {
            margin-top: 8px;
            padding: 16px;
            background: #fafafa;
            border-radius: 8px;
            font-size: 13px;
            color: #444;
            line-height: 1.6;
        }
        .shipping strong { display: block; color: #111; margin-bottom: 4px; }
        .footer {
            margin-top: 32px;
            text-align: center;
            font-size: 12px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 16px;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .receipt { border: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="receipt-header">
            <h1>E-SHOPPING</h1>
            <div class="meta">
                <strong>#<?= e($order['id']) ?></strong>
                <?= e(date('F j, Y · g:i A', strtotime($order['date']))) ?><br>
                Payment: <?= $order['payment'] === 'card' ? 'Paid online' : 'Cash on delivery' ?>
            </div>
        </div>

        <h2>Shipped to</h2>
        <div class="shipping">
            <strong><?= e($order['customer']['fullName']) ?></strong>
            <?= e($order['customer']['address']) ?><br>
            <?= e($order['customer']['city']) ?>, <?= e($order['customer']['zip']) ?><br>
            <?= e($order['customer']['country']) ?><br>
            <br>
            <?= e($order['customer']['email']) ?><br>
            <?= e($order['customer']['phone']) ?>
        </div>

        <h2>Items (<?= $itemCount ?>)</h2>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th class="num">Qty</th>
                    <th class="num">Unit price</th>
                    <th class="num">Line total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order['items'] as $row): ?>
                    <tr>
                        <td><?= e($row['product']['name']) ?></td>
                        <td><?= e($row['product']['category']) ?></td>
                        <td class="num"><?= (int) $row['qty'] ?></td>
                        <td class="num"><?= format_price($row['product']['price']) ?></td>
                        <td class="num"><?= format_price($row['line']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals">
            <div class="row">
                <span>Subtotal</span>
                <span><?= format_price($order['subtotal']) ?></span>
            </div>
            <div class="row">
                <span>Delivery fee</span>
                <span><?= $order['delivery'] > 0 ? format_price($order['delivery']) : '—' ?></span>
            </div>
            <div class="row total">
                <span>Total</span>
                <span><?= format_price($order['total']) ?></span>
            </div>
        </div>

        <div class="footer">
            Thank you for shopping with E-SHOPPING.<br>
            This receipt was generated on <?= e(date('F j, Y \a\t g:i A')) ?>.
        </div>
    </div>
</body>
</html>