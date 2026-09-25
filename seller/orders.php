<?php
require_once __DIR__ . '/../auth.php';
require_seller();

$me      = current_user();
$flashes = flash_get();
$active  = 'orders';

/* ---------- Handle status update ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $orderId = $_POST['order_id'];
    $status  = $_POST['status'];

    $sellerOrders = get_seller_orders($me['email']);
    if (isset($sellerOrders[$orderId])) {
        set_order_status($orderId, $status);
        flash_set('success', 'Order #' . $orderId . ' marked as ' . order_status_label($status) . '.');
    } else {
        flash_set('error', 'Order not found.');
    }
    header('Location: orders.php');
    exit;
}

/* ---------- Load orders ---------- */
$sellerOrders = array_reverse(get_seller_orders($me['email']), true);

$pendingCount = 0;
foreach ($sellerOrders as $row) {
    if (($row['order']['status'] ?? 'pending') === 'pending') $pendingCount++;
}

/* Filter */
$filter = $_GET['status'] ?? 'all';
if (in_array($filter, ['pending', 'preparing', 'shipped', 'delivered', 'cancelled'], true)) {
    $sellerOrders = array_filter($sellerOrders, function ($row) use ($filter) {
        return ($row['order']['status'] ?? 'pending') === $filter;
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders | My shop</title>
    <link rel="stylesheet" href="seller-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-inner">
            <a href="/index.php" class="logo">E-SHOPPING</a>
            <ul class="nav-links">
                <li><a href="/index.php">Home</a></li>
                <li><a href="/products.php">Products</a></li>
                <li><a href="/orders.php">Orders</a></li>
                <li><a href="/seller/index.php" class="active">My shop</a></li>
            </ul>
            <div class="nav-btn">
                <a href="/cart.php" class="cart" aria-label="Cart">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span class="cart-count">0</span>
                </a>
                <button onclick="location.href='/logout.php'">Sign out</button>
            </div>
        </div>
    </nav>

    <div class="seller-shell">
        <?php require __DIR__ . '/partials/sidebar.php'; ?>

        <main class="seller-content">
            <header class="seller-header">
                <h1>Incoming orders</h1>
                <p>Prepare, ship, and track orders that contain your products.</p>
            </header>

            <?php foreach ($flashes as $f): ?>
                <div class="alert alert-<?= e($f['type']) ?>">
                    <i class="fa-solid fa-circle-info"></i>
                    <span><?= e($f['message']) ?></span>
                </div>
            <?php endforeach; ?>

            <div class="order-filters">
                <?php
                    $tabs = [
                        'all'       => 'All',
                        'pending'   => 'Pending',
                        'preparing' => 'Preparing',
                        'shipped'   => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ];
                    foreach ($tabs as $key => $label):
                        $isActive = ($filter === $key);
                        $url = $key === 'all' ? 'orders.php' : 'orders.php?status=' . urlencode($key);
                ?>
                    <a href="<?= $url ?>" class="order-filter-chip <?= $isActive ? 'active' : '' ?>">
                        <?= e($label) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <section class="seller-section">
                <header>
                    <h2><i class="fa-solid fa-list-check"></i> Orders</h2>
                    <span class="count">
                        <?= count($sellerOrders) ?> order<?= count($sellerOrders) === 1 ? '' : 's' ?>
                    </span>
                </header>

                <?php if (empty($sellerOrders)): ?>
                    <div class="seller-empty">
                        <i class="fa-solid fa-receipt"></i>
                        <h3>No orders to show</h3>
                        <p><?= $filter === 'all' ? 'When customers order your products, they will show up here.' : 'No orders match this status.' ?></p>
                    </div>
                <?php else: ?>
                    <div class="order-list">
                        <?php foreach ($sellerOrders as $orderId => $row): ?>
                            <?php
                                $o        = $row['order']     ?? [];
                                $myItems  = $row['my_items']  ?? [];
                                $mySub    = (float) ($row['my_subtotal'] ?? 0);

                                $status    = $o['status'] ?? 'pending';
                                $statusCls = order_status_class($status);

                                $orderDisplayId = $o['id'] ?? $orderId;
                                $ts             = !empty($o['date']) ? strtotime($o['date']) : false;
                                $orderDateFmt   = $ts ? date('M j, Y · g:i A', $ts) : 'Unknown date';

                                $customer        = $o['customer'] ?? [];
                                $customerName    = $customer['fullName'] ?? 'Unknown customer';
                                $customerAddr    = $customer['address']  ?? '';
                                $customerCity    = $customer['city']     ?? '';
                                $customerZip     = $customer['zip']      ?? '';
                                $customerCountry = $customer['country']  ?? '';
                                $customerPhone   = $customer['phone']    ?? '';
                            ?>
                            <article class="seller-order-card">
                                <header class="seller-order-head">
                                    <div class="seller-order-id">
                                        <strong>#<?= e($orderDisplayId) ?></strong>
                                        <span class="seller-order-date"><?= e($orderDateFmt) ?></span>
                                    </div>
                                    <div class="seller-order-head-right">
                                        <span class="status-badge <?= e($statusCls) ?>">
                                            <?= e(order_status_label($status)) ?>
                                        </span>
                                        <span class="seller-order-total"><?= format_price($mySub) ?></span>
                                    </div>
                                </header>

                                <div class="seller-order-body">
                                    <div class="seller-order-items">
                                        <?php foreach ($myItems as $it): ?>
                                            <?php
                                                $prod     = $it['product'] ?? [];
                                                $prodName = $prod['name'] ?? 'Product';
                                                $prodImg  = $prod['image'] ?? '';
                                                $prodUrl  = $prod['image_url'] ?? '';

                                                if ($prodUrl === '') {
                                                    if ($prodImg !== '' && preg_match('#^https?://#', $prodImg)) {
                                                        $prodUrl = $prodImg;
                                                    } elseif ($prodImg !== '') {
                                                        $prodUrl = '/seller/uploads/' . $prodImg;
                                                    }
                                                }

                                                $prodPrice = (float) ($prod['price'] ?? 0);
                                                $qty       = (int)   ($it['qty'] ?? 0);
                                                $line      = (float) ($it['line'] ?? 0);
                                            ?>
                                            <div class="seller-order-item">
                                                <?php if ($prodUrl !== ''): ?>
                                                    <img src="<?= e($prodUrl) ?>"
                                                         alt="<?= e($prodName) ?>"
                                                         class="seller-order-thumb">
                                                <?php else: ?>
                                                    <div class="seller-order-thumb" style="display:grid; place-items:center; color:#aaa; background:#f0f0f0;">
                                                        <i class="fa-solid fa-image"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="seller-order-item-info">
                                                    <div class="seller-order-item-name"><?= e($prodName) ?></div>
                                                    <div class="seller-order-item-meta">
                                                        <?= $qty ?> × <?= format_price($prodPrice) ?>
                                                    </div>
                                                </div>
                                                <div class="seller-order-item-line"><?= format_price($line) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <aside class="seller-order-info">
                                        <h4>Ship to</h4>
                                        <strong><?= e($customerName) ?></strong>
                                        <?php if ($customerAddr): ?><span><?= e($customerAddr) ?></span><?php endif; ?>
                                        <?php if ($customerCity || $customerZip): ?>
                                            <span><?= e($customerCity) ?><?= $customerCity && $customerZip ? ', ' : '' ?><?= e($customerZip) ?></span>
                                        <?php endif; ?>
                                        <?php if ($customerCountry): ?><span><?= e($customerCountry) ?></span><?php endif; ?>
                                        <?php if ($customerPhone): ?>
                                            <span style="margin-top:.6rem; display:block;">
                                                <i class="fa-solid fa-phone"></i> <?= e($customerPhone) ?>
                                            </span>
                                        <?php endif; ?>
                                    </aside>
                                </div>

                                <footer class="seller-order-actions">
                                    <?php if ($status === 'pending'): ?>
                                        <form method="post" style="display:inline">
                                            <input type="hidden" name="order_id" value="<?= e($orderDisplayId) ?>">
                                            <input type="hidden" name="status" value="preparing">
                                            <button class="btn-mini approve">
                                                <i class="fa-solid fa-play"></i> Start preparing
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($status === 'preparing'): ?>
                                        <form method="post" style="display:inline">
                                            <input type="hidden" name="order_id" value="<?= e($orderDisplayId) ?>">
                                            <input type="hidden" name="status" value="shipped">
                                            <button class="btn-mini approve">
                                                <i class="fa-solid fa-truck-fast"></i> Mark as shipped
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($status === 'shipped'): ?>
                                        <form method="post" style="display:inline">
                                            <input type="hidden" name="order_id" value="<?= e($orderDisplayId) ?>">
                                            <input type="hidden" name="status" value="delivered">
                                            <button class="btn-mini approve">
                                                <i class="fa-solid fa-circle-check"></i> Mark as delivered
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (in_array($status, ['pending', 'preparing'], true)): ?>
                                        <form method="post" style="display:inline"
                                              onsubmit="return confirm('Cancel this order?');">
                                            <input type="hidden" name="order_id" value="<?= e($orderDisplayId) ?>">
                                            <input type="hidden" name="status" value="cancelled">
                                            <button class="btn-mini reject">
                                                <i class="fa-solid fa-xmark"></i> Cancel
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (in_array($status, ['shipped', 'delivered', 'cancelled'], true)): ?>
                                        <span class="seller-order-done">
                                            <i class="fa-solid fa-check"></i>
                                            Order <?= e(order_status_label($status)) ?>
                                        </span>
                                    <?php endif; ?>
                                </footer>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>