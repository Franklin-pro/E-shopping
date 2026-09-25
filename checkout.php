<?php
session_start();

require_once __DIR__ . '/auth.php';
require __DIR__ . '/js/products-data.php';

/* =========================================================
   Merge demo catalog + seller uploads, then normalize.
   ========================================================= */
$products = array_values(get_all_products($products));
normalize_products($products);

$byId = [];
foreach ($products as $p) {
    $byId[(int) $p['id']] = $p;
}

/* =========================================================
   Page-specific helper — reads the cart from session
   ========================================================= */
function get_cart_items($byId) {
    $cart     = $_SESSION['cart'] ?? [];
    $items    = [];
    $subtotal = 0;

    foreach ($cart as $id => $qty) {
        $id  = (int) $id;
        $qty = (int) $qty;

        if ($qty <= 0 || !isset($byId[$id])) {
            continue;
        }

        $product   = $byId[$id];
        $lineTotal = (float) ($product['price'] ?? 0) * $qty;
        $subtotal += $lineTotal;

        $items[] = [
            'product' => $product,
            'qty'     => $qty,
            'line'    => $lineTotal,
        ];
    }

    return [$items, $subtotal];
}

/* =========================================================
   If we just placed an order, show the confirmation view
   ========================================================= */
$orderId = $_GET['order'] ?? '';
$order   = ($orderId !== '' && isset($_SESSION['orders'][$orderId]))
    ? $_SESSION['orders'][$orderId]
    : null;

/* =========================================================
   Handle checkout form submission
   ========================================================= */
$errors = [];

if (!$order && $_SERVER['REQUEST_METHOD'] === 'POST') {

    [$items, $subtotal] = get_cart_items($byId);

    $fullName = trim($_POST['fullName'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $address  = trim($_POST['address']  ?? '');
    $city     = trim($_POST['city']     ?? '');
    $zip      = trim($_POST['zip']      ?? '');
    $country  = trim($_POST['country']  ?? '');
    $payment  = $_POST['payment']       ?? 'card';

    $cardNumber = trim($_POST['cardNumber'] ?? '');
    $cardExpiry = trim($_POST['cardExpiry'] ?? '');
    $cardCvv    = trim($_POST['cardCvv']    ?? '');

    if (empty($items)) {
        $errors[] = 'Your cart is empty — add something before checking out.';
    }

    if ($fullName === '')                    $errors[] = 'Please enter your full name.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($phone === '')                       $errors[] = 'Please enter a phone number.';
    if ($address === '')                     $errors[] = 'Please enter a shipping address.';
    if ($city === '')                        $errors[] = 'Please enter a city.';
    if ($zip === '')                         $errors[] = 'Please enter a postal / ZIP code.';
    if ($country === '')                     $errors[] = 'Please enter a country.';

    if ($payment === 'card') {
        $digits = preg_replace('/\D/', '', $cardNumber);
        if (strlen($digits) < 13 || strlen($digits) > 19) {
            $errors[] = 'Please enter a valid card number.';
        }
        if (!preg_match('/^\d{2}\s*\/\s*\d{2}$/', $cardExpiry)) {
            $errors[] = 'Please enter the card expiry as MM/YY.';
        }
        if (!preg_match('/^\d{3,4}$/', $cardCvv)) {
            $errors[] = 'Please enter a valid CVV.';
        }
    }

    if (empty($errors)) {
        $deliveryFee = $subtotal > 0 ? 2.50 : 0.00;
        $grandTotal  = $subtotal + $deliveryFee;

        $newOrderId = 'ORD-' . strtoupper(bin2hex(random_bytes(4)));

        if (!isset($_SESSION['orders'])) {
            $_SESSION['orders'] = [];
        }

        $_SESSION['orders'][$newOrderId] = [
            'id'       => $newOrderId,
            'date'     => date('Y-m-d H:i'),
            'status'   => 'pending',
            'items'    => $items,
            'subtotal' => $subtotal,
            'delivery' => $deliveryFee,
            'total'    => $grandTotal,
            'payment'  => $payment,
            'customer' => [
                'fullName' => $fullName,
                'email'    => $email,
                'phone'    => $phone,
                'address'  => $address,
                'city'     => $city,
                'zip'      => $zip,
                'country'  => $country,
            ],
        ];

        $_SESSION['cart'] = [];
        header('Location: checkout.php?order=' . urlencode($newOrderId));
        exit;
    }
}

/* =========================================================
   Cart for the form view (when there is no completed order)
   ========================================================= */
[$items, $subtotal] = $order ? [[], 0] : get_cart_items($byId);
$totalQty            = 0;
foreach ($items as $row) {
    $totalQty += (int) ($row['qty'] ?? 0);
}
$deliveryFee = $subtotal > 0 ? 2.50 : 0.00;
$grandTotal  = $subtotal + $deliveryFee;

/* =========================================================
   Cart count for navbar badge
   ========================================================= */
$cartCount = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cartCount += (int) $qty;
    }
}

/* =========================================================
   old() — page-specific helper for form field preservation
   ========================================================= */
function old($key, $default = '') {
    return htmlspecialchars($_POST[$key] ?? $default, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | G-SHOPPING</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.css">
    <style>
        /* =========================================================
           Checkout-page-only layout (1rem = 10px)
           ========================================================= */
        .checkout-grid {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 2rem;
            align-items: start;
        }
        @media (max-width: 900px) {
            .checkout-grid { grid-template-columns: 1fr; }
        }

        .checkout-section {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 12px;
            padding: 2.4rem 2.8rem;
            margin-bottom: 2rem;
        }
        .checkout-section h3 {
            margin: 0 0 1.8rem;
            font-size: 1.7rem;
        }
        .checkout-section h3 span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: #111;
            color: #fff;
            font-size: 1.3rem;
            margin-right: 1rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.6rem;
        }
        .form-row.cols-3 { grid-template-columns: 1fr 1fr 1fr; }
        @media (max-width: 560px) {
            .form-row, .form-row.cols-3 { grid-template-columns: 1fr; }
        }

        .checkout-section label {
            display: block;
            font-size: 1.4rem;
            font-weight: 600;
            color: #444;
            margin-bottom: 1.6rem;
        }
        .checkout-section label span.req { color: #c33; }

        .checkout-section input[type="text"],
        .checkout-section input[type="email"],
        .checkout-section input[type="tel"] {
            display: block;
            width: 100%;
            margin-top: 0.6rem;
            padding: 1rem 1.3rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1.5rem;
            font-family: inherit;
            color: #222;
            box-sizing: border-box;
        }

        .payment-options {
            display: flex;
            gap: 1.6rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        .payment-option {
            flex: 1;
            min-width: 160px;
            border: 1.5px solid #ddd;
            border-radius: 10px;
            padding: 1.4rem 1.6rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 1rem;
            font-weight: 600;
            font-size: 1.4rem;
            transition: border-color .15s ease, background .15s ease;
        }
        .payment-option:has(input:checked) {
            border-color: #111;
            background: #f7f7f7;
        }
        .payment-option input { margin: 0; }
        #cardFields.hidden { display: none; }

        .checkout-summary {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 12px;
            padding: 2.4rem 2.8rem;
            position: sticky;
            top: 1.5rem;
        }
        .checkout-summary h3 {
            margin: 0 0 1.6rem;
            font-size: 1.7rem;
        }
        .checkout-summary-item {
            display: flex;
            justify-content: space-between;
            gap: 1.2rem;
            font-size: 1.4rem;
            padding: 0.8rem 0;
            border-bottom: 1px dashed #eee;
        }
        .checkout-summary-item .name { color: #333; }
        .checkout-summary-item .qty { color: #888; }

        .cart-summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            font-size: 1.5rem;
            color: #555;
        }
        .cart-summary-row.total {
            font-size: 1.8rem;
            font-weight: 700;
            color: #111;
            border-top: 1px solid #eee;
            margin-top: 0.8rem;
            padding-top: 1.3rem;
        }

        /* ---- Confirmation ---- */
        .order-confirm {
            max-width: 640px;
            margin: 0 auto;
            text-align: center;
            padding: 4.8rem 2.4rem;
        }
        .order-confirm i.fa-circle-check {
            font-size: 5.6rem;
            color: #1e9e5a;
            margin-bottom: 1.6rem;
        }
        .order-confirm h1 {
            margin: 0 0 0.6rem;
            font-size: 2.8rem;
        }
        .order-confirm p {
            font-size: 1.5rem;
            color: #555;
        }
        .order-confirm .order-id {
            display: inline-block;
            background: #f4f4f4;
            border-radius: 8px;
            padding: 0.5rem 1.3rem;
            font-family: monospace;
            font-size: 1.5rem;
            margin: 1rem 0 2.6rem;
        }
        .order-confirm .checkout-summary {
            text-align: left;
            margin-top: 2.4rem;
            position: static;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-inner">
            <a href="index.php" class="logo">G-SHOPPING</a>

            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="orders.php">Orders</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>

            <div class="nav-btn">
                <a href="cart.php" class="cart" aria-label="Cart">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span class="cart-count"><?= (int) $cartCount ?></span>
                </a>

                <?php if (is_logged_in()): ?>
                    <?php $u = current_user(); ?>
                    <?php if (($u['role'] ?? '') === 'seller'): ?>
                        <button onclick="location.href='seller/index.php'">My shop</button>
                    <?php elseif (($u['role'] ?? '') === 'admin'): ?>
                        <button onclick="location.href='admin/index.php'">Admin</button>
                    <?php endif; ?>
                    <button onclick="location.href='logout.php'">Sign out</button>
                <?php else: ?>
                    <button onclick="location.href='login.php'">Login</button>
                    <button onclick="location.href='register.php'" class="reg">Register</button>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="products-page">

        <?php if ($order): ?>
            <?php
                $ocust  = $order['customer'] ?? [];
                $oitems = $order['items']    ?? [];
                $ostatus = $order['status']  ?? 'pending';
            ?>
            <!-- =========================================================
                 Order confirmation
                 ========================================================= -->
            <div class="order-confirm">
                <i class="fa-solid fa-circle-check"></i>
                <h1>Thank you, <?= htmlspecialchars($ocust['fullName'] ?? 'there') ?>!</h1>
                <p>Your order has been placed successfully.</p>
                <span class="order-id">#<?= htmlspecialchars($order['id'] ?? '') ?></span>

                <p style="margin-bottom: 2.4rem; font-size: 1.4rem;">
                    Status:
                    <span class="status-badge <?= e(order_status_class($ostatus)) ?>">
                        <?= e(order_status_label($ostatus)) ?>
                    </span>
                </p>

                <div class="checkout-summary">
                    <h3>Order summary</h3>
                    <?php foreach ($oitems as $row): ?>
                        <?php
                            $prod  = $row['product'] ?? [];
                            $qty   = (int)   ($row['qty']  ?? 0);
                            $line  = (float) ($row['line'] ?? 0);
                        ?>
                        <div class="checkout-summary-item">
                            <span class="name">
                                <?= htmlspecialchars($prod['name'] ?? 'Product') ?>
                                <span class="qty">× <?= $qty ?></span>
                            </span>
                            <span><?= format_price($line) ?></span>
                        </div>
                    <?php endforeach; ?>

                    <div class="cart-summary-row">
                        <span>Subtotal</span>
                        <span><?= format_price($order['subtotal'] ?? 0) ?></span>
                    </div>
                    <div class="cart-summary-row">
                        <span>Delivery fee</span>
                        <span><?= (($order['delivery'] ?? 0) > 0) ? format_price($order['delivery']) : '—' ?></span>
                    </div>
                    <div class="cart-summary-row total">
                        <span>Total paid</span>
                        <span><?= format_price($order['total'] ?? 0) ?></span>
                    </div>

                    <p style="margin-top:1.6rem; font-size:1.4rem; color:#777;">
                        Shipping to <?= htmlspecialchars($ocust['address'] ?? '') ?>,
                        <?= htmlspecialchars($ocust['city'] ?? '') ?>,
                        <?= htmlspecialchars($ocust['country'] ?? '') ?> —
                        paid via <?= ($order['payment'] ?? 'cod') === 'card' ? 'card' : 'cash on delivery' ?>.
                        A confirmation was sent to <?= htmlspecialchars($ocust['email'] ?? '') ?>.
                    </p>
                </div>

                <p style="margin-top:3.2rem;">
                    <a href="orders.php" class="btn btn-primary">View my orders</a>
                    <a href="products.php" class="btn btn-ghost-dark" style="margin-left:1rem;">Continue shopping</a>
                </p>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    if (window.showToast) {
                        window.showToast('Order placed successfully!', 'success');
                    }
                });
            </script>

        <?php elseif (empty($items)): ?>
            <!-- =========================================================
                 Nothing to check out
                 ========================================================= -->
            <header class="products-header">
                <h1>Checkout</h1>
            </header>
            <div class="empty-state">
                <i class="fa-solid fa-cart-shopping"></i>
                <h3>Your cart is empty</h3>
                <p>Add some products before checking out.</p>
                <p style="margin-top: 3.2rem;">
                    <a href="products.php" class="btn btn-primary">Browse Products</a>
                </p>
            </div>

        <?php else: ?>
            <!-- =========================================================
                 Checkout form
                 ========================================================= -->
            <header class="products-header">
                <h1>Checkout</h1>
                <p>Almost done — just a few details and your order is on its way.</p>
            </header>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error" style="margin-bottom:2.4rem;">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <ul style="margin:.6rem 0 0 1.9rem;">
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="checkout.php" id="checkoutForm">
                <div class="checkout-grid">
                    <div>
                        <!-- ---- Shipping info ---- -->
                        <div class="checkout-section">
                            <h3><span>1</span>Shipping details</h3>

                            <label>Full name <span class="req">*</span>
                                <input type="text" name="fullName" value="<?= old('fullName') ?>" required>
                            </label>

                            <div class="form-row">
                                <label>Email <span class="req">*</span>
                                    <input type="email" name="email" value="<?= old('email') ?>" required>
                                </label>
                                <label>Phone <span class="req">*</span>
                                    <input type="tel" name="phone" value="<?= old('phone') ?>" required>
                                </label>
                            </div>

                            <label>Street address <span class="req">*</span>
                                <input type="text" name="address" value="<?= old('address') ?>" required>
                            </label>

                            <div class="form-row cols-3">
                                <label>City <span class="req">*</span>
                                    <input type="text" name="city" value="<?= old('city') ?>" required>
                                </label>
                                <label>Postal / ZIP code <span class="req">*</span>
                                    <input type="text" name="zip" value="<?= old('zip') ?>" required>
                                </label>
                                <label>Country <span class="req">*</span>
                                    <input type="text" class="input" name="country" value="<?= old('country') ?>" required>
                                </label>
                            </div>
                        </div>

                        <!-- ---- Payment ---- -->
                        <div class="checkout-section">
                            <h3><span>2</span>Payment method</h3>

                            <div class="payment-options">
                                <label class="payment-option">
                                    <input type="radio" class="input" name="payment" value="card" id="payCard"
                                        <?= (($_POST['payment'] ?? 'card') === 'card') ? 'checked' : '' ?>>
                                    <i class="fa-solid fa-credit-card"></i> Credit / debit card
                                </label>
                                <label class="payment-option">
                                    <input type="radio" class="input" name="payment" value="cod" id="payCod"
                                        <?= (($_POST['payment'] ?? '') === 'cod') ? 'checked' : '' ?>>
                                    <i class="fa-solid fa-money-bill-wave"></i> Cash on delivery
                                </label>
                            </div>

                            <div id="cardFields">
                                <label>Card number <span class="req">*</span>
                                    <input type="text" class="input" name="cardNumber" placeholder="1234 5678 9012 3456"
                                        value="<?= old('cardNumber') ?>" inputmode="numeric">
                                </label>
                                <div class="form-row">
                                    <label>Expiry (MM/YY) <span class="req">*</span>
                                        <input type="text" class="input" name="cardExpiry" placeholder="MM/YY"
                                            value="<?= old('cardExpiry') ?>">
                                    </label>
                                    <label>CVV <span class="req">*</span>
                                        <input type="text" class="input" name="cardCvv" placeholder="123"
                                            value="<?= old('cardCvv') ?>" inputmode="numeric">
                                    </label>
                                </div>
                            </div>
                        </div>

                        <a href="cart.php" class="btn btn-ghost-dark">
                            <i class="fa-solid fa-arrow-left"></i> Back to cart
                        </a>
                    </div>

                    <!-- ---- Order summary sidebar ---- -->
                    <aside class="checkout-summary">
                        <h3>Order summary (<?= $totalQty ?> item<?= $totalQty === 1 ? '' : 's' ?>)</h3>

                        <?php foreach ($items as $row): ?>
                            <?php
                                $prod  = $row['product'] ?? [];
                                $qty   = (int)   ($row['qty']  ?? 0);
                                $line  = (float) ($row['line'] ?? 0);
                            ?>
                            <div class="checkout-summary-item">
                                <span class="name">
                                    <?= htmlspecialchars($prod['name'] ?? 'Product') ?>
                                    <span class="qty">× <?= $qty ?></span>
                                </span>
                                <span><?= format_price($line) ?></span>
                            </div>
                        <?php endforeach; ?>

                        <div class="cart-summary-row">
                            <span>Subtotal</span>
                            <span><?= format_price($subtotal) ?></span>
                        </div>
                        <div class="cart-summary-row">
                            <span>Delivery fee</span>
                            <span><?= $deliveryFee > 0 ? format_price($deliveryFee) : '—' ?></span>
                        </div>
                        <div class="cart-summary-row total">
                            <span>Total</span>
                            <span><?= format_price($grandTotal) ?></span>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%; margin-top:2.2rem;">
                            <i class="fa-solid fa-lock"></i> Place order
                        </button>
                    </aside>
                </div>
            </form>

            <script>
                // Show/hide card fields depending on the chosen payment method.
                (function () {
                    var cardFields = document.getElementById('cardFields');
                    var radios = document.querySelectorAll('input[name="payment"]');
                    function sync() {
                        var isCard = document.getElementById('payCard').checked;
                        cardFields.classList.toggle('hidden', !isCard);
                    }
                    radios.forEach(function (r) { r.addEventListener('change', sync); });
                    sync();
                })();
            </script>
        <?php endif; ?>

    </main>

    <script src="/js/script.js"></script>
</body>
</html>