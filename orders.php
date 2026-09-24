<?php
session_start();
// unset($_SESSION['cart']);
// unset($_SESSION['orders']);
// unset($_SESSION['reviews']);
// header('Location: index.php');
// exit;
/* =========================================================
   Load product catalog (so order items can be looked up,
   though checkout already stored full item snapshots)
   ========================================================= */
require __DIR__ . '/js/products-data.php';

$byId = [];
foreach ($products as $p) {
    $byId[(int) $p['id']] = $p;
}

/* =========================================================
   Helpers
   ========================================================= */
function format_price($n) {
    return '$' . number_format($n, 2);
}

function status_label($payment) {
    return $payment === 'card' ? 'Paid online' : 'Cash on delivery';
}

function status_class($payment) {
    return $payment === 'card' ? 'status-paid' : 'status-pending';
}

/* =========================================================
   Load orders from session (newest first)
   ========================================================= */
$orders = $_SESSION['orders'] ?? [];
$orders = array_reverse($orders, true);   // newest first

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
   Grand total across all orders (nice stat)
   ========================================================= */
$lifetimeTotal = 0;
foreach ($orders as $o) {
    $lifetimeTotal += (float) $o['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | E-SHOPPING</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.css">
  
</head>
<body>
    <nav class="navbar">
        <div class="nav-inner">
            <a href="index.php" class="logo">E-SHOPPING</a>

            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="orders.php" class="active">Orders</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>

            <div class="nav-btn">
                <a href="cart.php" class="cart" aria-label="Cart">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span class="cart-count"><?= $cartCount ?></span>
                </a>
                <button onclick="location.href='login.php'">Login</button>
                <button onclick="location.href='register.php'" class="reg">Register</button>
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
                        $itemCount = 0;
                        foreach ($order['items'] as $row) {
                            $itemCount += (int) $row['qty'];
                        }
                    ?>
                    <article class="order-card" data-order-id="<?= htmlspecialchars($order['id']) ?>">
                        <header class="order-head" role="button" tabindex="0" aria-expanded="false">
                            <div class="order-head-left">
                                <span class="order-id">#<?= htmlspecialchars($order['id']) ?></span>
                                <span class="order-date">
                                    Placed <?= date('M j, Y · g:i A', strtotime($order['date'])) ?>
                                </span>
                            </div>

                            <div class="order-head-right">
                                <span class="order-status <?= status_class($order['payment']) ?>">
                                    <?= status_label($order['payment']) ?>
                                </span>
                                <span class="order-total"><?= format_price($order['total']) ?></span>
                                <i class="fa-solid fa-chevron-down order-chevron"></i>
                            </div>
                        </header>

                        <div class="order-body">
                            <div class="order-body-inner">
                                <!-- Items -->
                                <div class="order-items">
                                    <?php foreach ($order['items'] as $row): ?>
                                        <div class="order-item">
                                            <img
                                                src="<?= htmlspecialchars($row['product']['image']) ?>"
                                                alt="<?= htmlspecialchars($row['product']['name']) ?>"
                                                class="order-item-thumb"
                                            >
                                            <div class="order-item-info">
                                                <div class="order-item-name">
                                                    <?= htmlspecialchars($row['product']['name']) ?>
                                                </div>
                                                <div class="order-item-meta">
                                                    <?= (int) $row['qty'] ?> × <?= format_price($row['product']['price']) ?>
                                                    · <?= htmlspecialchars($row['product']['category']) ?>
                                                </div>
                                            </div>
                                            <div class="order-item-line">
                                                <?= format_price($row['line']) ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Info sidebar -->
                                <aside class="order-info">
                                    <div class="order-info-block">
                                        <h4>Shipped to</h4>
                                        <strong><?= htmlspecialchars($order['customer']['fullName']) ?></strong>
                                        <span><?= htmlspecialchars($order['customer']['address']) ?></span>
                                        <span>
                                            <?= htmlspecialchars($order['customer']['city']) ?>,
                                            <?= htmlspecialchars($order['customer']['zip']) ?>
                                        </span>
                                        <span><?= htmlspecialchars($order['customer']['country']) ?></span>
                                    </div>

                                    <div class="order-info-block">
                                        <h4>Contact</h4>
                                        <span><i class="fa-solid fa-envelope"></i> <?= htmlspecialchars($order['customer']['email']) ?></span>
                                        <span><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($order['customer']['phone']) ?></span>
                                    </div>

                                    <div class="order-info-block">
                                        <h4>Summary</h4>
                                        <div class="order-summary-rows">
                                            <div class="row">
                                                <span>Subtotal (<?= $itemCount ?> item<?= $itemCount === 1 ? '' : 's' ?>)</span>
                                                <span><?= format_price($order['subtotal']) ?></span>
                                            </div>
                                            <div class="row">
                                                <span>Delivery</span>
                                                <span><?= $order['delivery'] > 0 ? format_price($order['delivery']) : '—' ?></span>
                                            </div>
                                            <div class="row total">
                                                <span>Total</span>
                                                <span><?= format_price($order['total']) ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <a href="product.php?id=<?= (int) $order['items'][0]['product']['id'] ?>" class="btn btn-ghost-dark" style="width:100%; text-align:center; padding: 0.8rem 1.4rem; font-size: 1.3rem;">
                                        <i class="fa-solid fa-rotate-right"></i> Buy it again
                                    </a>
                                </aside>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </main>

    <script>
        /* =====================================================
           Accordion — click a header to expand the order details
           ===================================================== */
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