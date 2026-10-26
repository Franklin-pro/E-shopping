<?php
session_start();

require_once __DIR__ . '/auth.php';
require __DIR__ . '/js/products-data.php';

/* Merge demo catalog + seller uploads, then normalize. */
$products = array_values(get_all_products($products));
normalize_products($products);

$byId = [];
foreach ($products as $p) {
    $byId[(int) $p['id']] = $p;
}

/* Page-specific helper — reads the cart from session */
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

/* If we just placed an order, show the confirmation view */
$orderId = $_GET['order'] ?? '';
$order   = ($orderId !== '' && isset($_SESSION['orders'][$orderId]))
    ? $_SESSION['orders'][$orderId]
    : null;

/* Handle checkout form submission */
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

/* Cart for the form view (when there is no completed order) */
[$items, $subtotal] = $order ? [[], 0] : get_cart_items($byId);
$totalQty            = 0;
foreach ($items as $row) {
    $totalQty += (int) ($row['qty'] ?? 0);
}
$deliveryFee = $subtotal > 0 ? 2.50 : 0.00;
$grandTotal  = $subtotal + $deliveryFee;

/* Cart count for navbar badge */
$cartCount = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cartCount += (int) $qty;
    }
}

/* old() — page-specific helper for form field preservation */
function old($key, $default = '') {
    return htmlspecialchars($_POST[$key] ?? $default, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Franklin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,500;12..96,600;12..96,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.css">
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="shop.css">
    <link rel="stylesheet" href="account.css">
</head>
<body>
<header class="fr-nav">
    <div class="fr-wrap fr-nav-inner">
        <a href="index.php" class="fr-logo"><span class="fr-logo-mark">F</span>Franklin</a>

        <nav class="fr-links" aria-label="Main">
            <a href="index.php">Home</a>
            <a href="products.php">Products</a>
            <a href="orders.php">Orders</a>
            <a href="contact.php">Contact</a>
        </nav>

        <div class="fr-actions">
            <a href="cart.php" class="fr-cart" aria-label="Cart, <?= (int) $cartCount ?> items">
                <i class="fa-solid fa-bag-shopping"></i>
                <?php if ($cartCount > 0): ?><span class="fr-cart-count"><?= (int) $cartCount ?></span><?php endif; ?>
            </a>

            <?php if (is_logged_in()): ?>
                <?php $u = current_user(); ?>
                <?php if (($u['role'] ?? '') === 'seller'): ?>
                    <a href="seller/index.php" class="fr-btn fr-btn-quiet">My shop</a>
                <?php elseif (($u['role'] ?? '') === 'admin'): ?>
                    <a href="admin/index.php" class="fr-btn fr-btn-quiet">Admin</a>
                <?php endif; ?>
                <a href="logout.php" class="fr-btn fr-btn-dark">Sign out</a>
            <?php else: ?>
                <a href="login.php" class="fr-btn fr-btn-quiet">Log in</a>
                <a href="register.php" class="fr-btn fr-btn-dark">Register</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="fr-wrap fr-page">

<?php if ($order): ?>
    <?php
        $ocust   = $order['customer'] ?? [];
        $oitems  = $order['items']    ?? [];
        $ostatus = $order['status']   ?? 'pending';
        $isCard  = ($order['payment'] ?? 'cod') === 'card';
    ?>
    <!-- Order confirmation -->
    <div class="fr-confirm">
        <i class="fa-solid fa-check"></i>
        <h1>Thank you, <?= htmlspecialchars($ocust['fullName'] ?? 'there') ?>!</h1>
        <p>Your order has been placed.</p>
        <span class="fr-code">#<?= htmlspecialchars($order['id'] ?? '') ?></span>
        <p>Status: <span class="status-badge <?= e(order_status_class($ostatus)) ?>"><?= e(order_status_label($ostatus)) ?></span></p>

        <div class="fr-panel">
            <h3>Order summary</h3>
            <?php foreach ($oitems as $row): ?>
                <?php
                    $prod = $row['product'] ?? [];
                    $qty  = (int)   ($row['qty']  ?? 0);
                    $line = (float) ($row['line'] ?? 0);
                ?>
                <div class="fr-mini">
                    <span><?= htmlspecialchars($prod['name'] ?? 'Product') ?> <span class="q">× <?= $qty ?></span></span>
                    <span><?= format_price($line) ?></span>
                </div>
            <?php endforeach; ?>

            <div class="fr-sum"><span>Subtotal</span><span><?= format_price($order['subtotal'] ?? 0) ?></span></div>
            <div class="fr-sum"><span>Delivery fee</span><span><?= (($order['delivery'] ?? 0) > 0) ? format_price($order['delivery']) : '—' ?></span></div>
            <div class="fr-sum total"><span><?= $isCard ? 'Total paid' : 'Total due on delivery' ?></span><span><?= format_price($order['total'] ?? 0) ?></span></div>

            <p style="margin-top:18px;font-size:.92rem">
                Shipping to <?= htmlspecialchars($ocust['address'] ?? '') ?>,
                <?= htmlspecialchars($ocust['city'] ?? '') ?>,
                <?= htmlspecialchars($ocust['country'] ?? '') ?>,
                <?= $isCard ? 'paid by card' : 'pay with cash on delivery' ?>.
                A confirmation was sent to <?= htmlspecialchars($ocust['email'] ?? '') ?>.
            </p>
        </div>

        <div class="fr-btns">
            <a href="orders.php" class="fr-btn fr-btn-primary fr-btn-lg">View my orders</a>
            <a href="products.php" class="fr-btn fr-btn-outline fr-btn-lg">Continue shopping</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.showToast) {
                window.showToast('Order placed successfully!', 'success');
            }
        });
    </script>

<?php elseif (empty($items)): ?>
    <header class="fr-page-head"><h1>Checkout</h1></header>
    <div class="fr-empty">
        <i class="fa-solid fa-bag-shopping"></i>
        <h3>Your cart is empty</h3>
        <p>Add some products before checking out.</p>
        <p style="margin-top:24px"><a href="products.php" class="fr-btn fr-btn-primary">Browse products</a></p>
    </div>

<?php else: ?>
    <header class="fr-page-head">
        <h1>Checkout</h1>
        <p>Almost done. Add your details and your order is on its way.</p>
    </header>

    <?php if (!empty($errors)): ?>
        <div class="fr-alert fr-alert-err" role="alert">
            <i class="fa-solid fa-circle-exclamation"></i>
            <ul>
                <?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="checkout.php" id="checkoutForm">
        <div class="fr-layout">
            <div>
                <div class="fr-panel">
                    <h3><span class="fr-step">1</span>Shipping details</h3>

                    <label class="fr-field">Full name <span class="req">*</span>
                        <input type="text" name="fullName" value="<?= old('fullName') ?>" required>
                    </label>

                    <div class="fr-row">
                        <label class="fr-field">Email <span class="req">*</span>
                            <input type="email" name="email" value="<?= old('email') ?>" required>
                        </label>
                        <label class="fr-field">Phone <span class="req">*</span>
                            <input type="tel" name="phone" value="<?= old('phone') ?>" required>
                        </label>
                    </div>

                    <label class="fr-field">Street address <span class="req">*</span>
                        <input type="text" name="address" value="<?= old('address') ?>" required>
                    </label>

                    <div class="fr-row cols-3">
                        <label class="fr-field">City <span class="req">*</span>
                            <input type="text" name="city" value="<?= old('city') ?>" required>
                        </label>
                        <label class="fr-field">Postal / ZIP code <span class="req">*</span>
                            <input type="text" name="zip" value="<?= old('zip') ?>" required>
                        </label>
                        <label class="fr-field">Country <span class="req">*</span>
                            <input type="text" name="country" value="<?= old('country') ?>" required>
                        </label>
                    </div>
                </div>

                <div class="fr-panel" style="margin-top:20px">
                    <h3><span class="fr-step">2</span>Payment method</h3>

                    <div class="fr-pay">
                        <label>
                            <input type="radio" name="payment" value="card" id="payCard"
                                <?= (($_POST['payment'] ?? 'card') === 'card') ? 'checked' : '' ?>>
                            <i class="fa-solid fa-credit-card"></i> Credit / debit card
                        </label>
                        <label>
                            <input type="radio" name="payment" value="cod" id="payCod"
                                <?= (($_POST['payment'] ?? '') === 'cod') ? 'checked' : '' ?>>
                            <i class="fa-solid fa-money-bill-wave"></i> Cash on delivery
                        </label>
                    </div>

                    <div id="cardFields">
                        <label class="fr-field">Card number <span class="req">*</span>
                            <input type="text" name="cardNumber" placeholder="1234 5678 9012 3456"
                                   value="<?= old('cardNumber') ?>" inputmode="numeric" autocomplete="cc-number">
                        </label>
                        <div class="fr-row">
                            <label class="fr-field">Expiry (MM/YY) <span class="req">*</span>
                                <input type="text" name="cardExpiry" placeholder="MM/YY"
                                       value="<?= old('cardExpiry') ?>" autocomplete="cc-exp">
                            </label>
                            <label class="fr-field">CVV <span class="req">*</span>
                                <input type="text" name="cardCvv" placeholder="123"
                                       value="<?= old('cardCvv') ?>" inputmode="numeric" autocomplete="cc-csc">
                            </label>
                        </div>
                    </div>
                </div>

                <p style="margin-top:20px">
                    <a href="cart.php" class="fr-btn fr-btn-outline"><i class="fa-solid fa-arrow-left"></i> Back to cart</a>
                </p>
            </div>

            <aside class="fr-panel fr-sticky">
                <h3>Order summary (<?= $totalQty ?> item<?= $totalQty === 1 ? '' : 's' ?>)</h3>

                <?php foreach ($items as $row): ?>
                    <?php
                        $prod = $row['product'] ?? [];
                        $qty  = (int)   ($row['qty']  ?? 0);
                        $line = (float) ($row['line'] ?? 0);
                    ?>
                    <div class="fr-mini">
                        <span><?= htmlspecialchars($prod['name'] ?? 'Product') ?> <span class="q">× <?= $qty ?></span></span>
                        <span><?= format_price($line) ?></span>
                    </div>
                <?php endforeach; ?>

                <div class="fr-sum" style="margin-top:8px"><span>Subtotal</span><span><?= format_price($subtotal) ?></span></div>
                <div class="fr-sum"><span>Delivery fee</span><span><?= $deliveryFee > 0 ? format_price($deliveryFee) : '—' ?></span></div>
                <div class="fr-sum total"><span>Total</span><span><?= format_price($grandTotal) ?></span></div>

                <button type="submit" class="fr-btn fr-btn-primary fr-btn-lg fr-btn-block" style="margin-top:20px">
                    <i class="fa-solid fa-lock"></i> Place order
                </button>
                <p class="fr-secure"><i class="fa-solid fa-shield-halved"></i> Your details are only used for this order</p>
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

<footer class="fr-footer">
    <div class="fr-wrap fr-footer-inner">
        <a href="index.php" class="fr-logo"><span class="fr-logo-mark">F</span>Franklin</a>
        <nav aria-label="Footer">
            <a href="products.php">Products</a>
            <a href="orders.php">Orders</a>
            <a href="about.php">About</a>
            <a href="contact.php">Contact</a>
        </nav>
        <p>&copy; <?= date('Y') ?> Franklin</p>
    </div>
</footer>

<script src="/js/script.js"></script>
</body>
</html>