<?php
session_start();

require_once __DIR__ . '/auth.php';
require __DIR__ . '/js/products-data.php';

/* =========================================================
   Merge demo catalog + seller uploads, then normalize.
   Needed so order items (which carry image_url) render
   correctly for both demo and seller products.
   ========================================================= */
$products = array_values(get_all_products($products));
normalize_products($products);

/* =========================================================
   Load orders from session (newest first)
   ========================================================= */
$orders = $_SESSION['orders'] ?? [];
$orders = array_reverse($orders, true);

$orderCount = count($orders);

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
   Grand total across all orders
   ========================================================= */
$lifetimeTotal = 0;
foreach ($orders as $o) {
    $lifetimeTotal += (float) ($o['total'] ?? 0);
}

/* =========================================================
   Payment label helpers (page-specific)
   ========================================================= */
function payment_label($payment) {
    return $payment === 'card' ? 'Paid online' : 'Cash on delivery';
}

function payment_class($payment) {
    return $payment === 'card' ? 'status-paid' : 'status-pending';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | G-SHOPPING</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-inner">
            <a href="index.php" class="logo">G-SHOPPING</a>

            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="orders.php" class="active">Orders</a></li>
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

    <main class="orders-page">
        <header class="orders-header">
            <h1>My Orders</h1>
            <p>
                <?php if ($orderCount > 0): ?>
                    You have <?= $orderCount ?> order<?= $orderCount === 1 ? '' : 's' ?> placed.
                <?php else: ?>
                    You haven't placed any orders yet.
                <?php endif; ?>
            </p>
        </header>

        <?php if ($orderCount === 0): ?>
            <!-- ---------- Empty state ---------- -->
            <div class="orders-empty">
                <i class="fa-solid fa-receipt"></i>
                <h3>No orders yet</h3>
                <p>When you place an order, it will show up here.</p>
                <a href="products.php" class="btn btn-primary">
                    <i class="fa-solid fa-bag-shopping"></i> Start shopping
                </a>
            </div>

        <?php else: ?>

            <!-- ---------- Stats strip ---------- -->
            <div class="orders-stats">
                <div class="stat-card">
                    <i class="fa-solid fa-box"></i>
                    <span class="stat-value"><?= $orderCount ?></span>
                    <span class="stat-label">Total Orders</span>
                </div>
                <div class="stat-card">
                    <i class="fa-solid fa-dollar-sign"></i>
                    <span class="stat-value"><?= format_price($lifetimeTotal) ?></span>
                    <span class="stat-label">Lifetime Spend</span>
                </div>
                <div class="stat-card">
                    <i class="fa-solid fa-truck-fast"></i>
                    <span class="stat-value">2–4 days</span>
                    <span class="stat-label">Avg. Delivery</span>
                </div>
            </div>

            <!-- ---------- Orders list ---------- -->
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                    <?php
                        /* Safe reads — never crash on a bad order */
                        $oid      = $order['id']     ?? 'UNKNOWN';
                        $odate    = $order['date']   ?? '';
                        $ostatus  = $order['status'] ?? 'pending';
                        $opayment = $order['payment'] ?? 'cod';
                        $ototal   = (float) ($order['total'] ?? 0);
                        $osubtot  = (float) ($order['subtotal'] ?? 0);
                        $odeliv   = (float) ($order['delivery'] ?? 0);
                        $ocust    = $order['customer'] ?? [];
                        $oitems   = $order['items']    ?? [];

                        $itemCount = 0;
                        foreach ($oitems as $row) {
                            $itemCount += (int) ($row['qty'] ?? 0);
                        }

                        $ts       = $odate !== '' ? strtotime($odate) : false;
                        $dateFmt  = $ts ? date('M j, Y · g:i A', $ts) : 'Unknown date';

                        $firstProductId = (int) ($oitems[0]['product']['id'] ?? 0);
                    ?>
                    <article class="order-card" data-order-id="<?= htmlspecialchars($oid) ?>">
                        <header class="order-head" role="button" tabindex="0" aria-expanded="false">
                            <div class="order-head-left">
                                <span class="order-id">#<?= htmlspecialchars($oid) ?></span>
                                <span class="order-date">Placed <?= htmlspecialchars($dateFmt) ?></span>
                            </div>

                            <div class="order-head-right">
                                <span class="status-badge <?= e(order_status_class($ostatus)) ?>">
                                    <?= e(order_status_label($ostatus)) ?>
                                </span>
                                <span class="order-status <?= e(payment_class($opayment)) ?>">
                                    <?= e(payment_label($opayment)) ?>
                                </span>
                                <span class="order-total"><?= format_price($ototal) ?></span>
                                <i class="fa-solid fa-chevron-down order-chevron"></i>
                            </div>
                        </header>

                        <div class="order-body">
                            <div class="order-body-inner">
                                <!-- Items -->
                                <div class="order-items">
                                    <?php foreach ($oitems as $row): ?>
                                        <?php
                                            $prod      = $row['product'] ?? [];
                                            $pname     = $prod['name']      ?? 'Product';
                                            $pimage    = $prod['image_url'] ?? ('/seller/uploads/' . ($prod['image'] ?? ''));
                                            $pcat      = $prod['category']  ?? '';
                                            $pprice    = (float) ($prod['price'] ?? 0);
                                            $qty       = (int)   ($row['qty']  ?? 0);
                                            $line      = (float) ($row['line'] ?? 0);
                                        ?>
                                        <div class="order-item">
                                            <img
                                                src="<?= htmlspecialchars($pimage) ?>"
                                                alt="<?= htmlspecialchars($pname) ?>"
                                                class="order-item-thumb"
                                            >
                                            <div class="order-item-info">
                                                <div class="order-item-name">
                                                    <?= htmlspecialchars($pname) ?>
                                                </div>
                                                <div class="order-item-meta">
                                                    <?= $qty ?> × <?= format_price($pprice) ?>
                                                    <?php if ($pcat !== ''): ?>
                                                        · <?= htmlspecialchars($pcat) ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="order-item-line">
                                                <?= format_price($line) ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Info sidebar -->
                                <aside class="order-info">
                                    <div class="order-info-block">
                                        <h4>Shipped to</h4>
                                        <strong><?= htmlspecialchars($ocust['fullName'] ?? 'Unknown') ?></strong>
                                        <?php if (!empty($ocust['address'])): ?><span><?= htmlspecialchars($ocust['address']) ?></span><?php endif; ?>
                                        <?php if (!empty($ocust['city']) || !empty($ocust['zip'])): ?>
                                            <span><?= htmlspecialchars($ocust['city'] ?? '') ?><?= !empty($ocust['city']) && !empty($ocust['zip']) ? ', ' : '' ?><?= htmlspecialchars($ocust['zip'] ?? '') ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($ocust['country'])): ?><span><?= htmlspecialchars($ocust['country']) ?></span><?php endif; ?>
                                    </div>

                                    <div class="order-info-block">
                                        <h4>Contact</h4>
                                        <?php if (!empty($ocust['email'])): ?>
                                            <span><i class="fa-solid fa-envelope"></i> <?= htmlspecialchars($ocust['email']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($ocust['phone'])): ?>
                                            <span><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($ocust['phone']) ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="order-info-block">
                                        <h4>Summary</h4>
                                        <div class="order-summary-rows">
                                            <div class="row">
                                                <span>Subtotal (<?= $itemCount ?> item<?= $itemCount === 1 ? '' : 's' ?>)</span>
                                                <span><?= format_price($osubtot) ?></span>
                                            </div>
                                            <div class="row">
                                                <span>Delivery</span>
                                                <span><?= $odeliv > 0 ? format_price($odeliv) : '—' ?></span>
                                            </div>
                                            <div class="row total">
                                                <span>Total</span>
                                                <span><?= format_price($ototal) ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div style="display:grid; gap:.8rem; margin-top:.4rem;">
                                        <a href="download-order.php?order=<?= urlencode($oid) ?>"
                                           class="btn btn-primary"
                                           style="width:100%; text-align:center; padding: 0.8rem 1.4rem; font-size: 1.3rem;">
                                            <i class="fa-solid fa-download"></i> Download receipt
                                        </a>
                                        <?php if ($firstProductId > 0): ?>
                                            <a href="product.php?id=<?= $firstProductId ?>"
                                               class="btn btn-ghost-dark"
                                               style="width:100%; text-align:center; padding: 0.8rem 1.4rem; font-size: 1.3rem;">
                                                <i class="fa-solid fa-rotate-right"></i> Buy it again
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </aside>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </main>

    <script>
        /* Accordion — click a header to expand the order details */
        document.querySelectorAll('.order-card').forEach((card) => {
            const head = card.querySelector('.order-head');

            const toggle = () => {
                const isOpen = card.classList.toggle('open');
                head.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            };

            head.addEventListener('click', toggle);

            head.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggle();
                }
            });
        });
    </script>
</body>
</html>