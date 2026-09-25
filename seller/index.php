<?php
require_once __DIR__ . '/../auth.php';
require_seller();

$me       = current_user();
$products = get_seller_products($me['email']);
$flashes  = flash_get();
$active   = 'overview';

$totalProducts = count($products);
$totalValue    = 0;
$totalStock    = 0;
foreach ($products as $p) {
    $totalValue += (float) $p['price'] * (int) $p['stock'];
    $totalStock += (int) $p['stock'];
}

$sellerOrders = array_reverse(get_seller_orders($me['email']), true);

$pendingCount = 0;
foreach ($sellerOrders as $row) {
    if (($row['order']['status'] ?? 'pending') === 'pending') $pendingCount++;
}

$recentOrders   = array_slice($sellerOrders, 0, 3, true);
$recentProducts = array_slice($products, 0, 3, true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller dashboard | E-SHOPPING</title>
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
                <h1>Welcome back, <?= e(explode(' ', $me['name'])[0]) ?></h1>
                <p>Here's a snapshot of your shop.</p>
            </header>

            <?php foreach ($flashes as $f): ?>
                <div class="alert alert-<?= e($f['type']) ?>">
                    <i class="fa-solid fa-circle-info"></i>
                    <span><?= e($f['message']) ?></span>
                </div>
            <?php endforeach; ?>

            <div class="seller-stats">
                <div class="seller-stat">
                    <i class="fa-solid fa-box"></i>
                    <span class="value"><?= $totalProducts ?></span>
                    <span class="label">Products</span>
                </div>
                <div class="seller-stat">
                    <i class="fa-solid fa-cubes"></i>
                    <span class="value"><?= $totalStock ?></span>
                    <span class="label">Total stock</span>
                </div>
                <div class="seller-stat">
                    <i class="fa-solid fa-dollar-sign"></i>
                    <span class="value"><?= format_price($totalValue) ?></span>
                    <span class="label">Inventory value</span>
                </div>
                <div class="seller-stat">
                    <i class="fa-solid fa-receipt"></i>
                    <span class="value"><?= count($sellerOrders) ?></span>
                    <span class="label">Orders</span>
                </div>
            </div>

            <section class="seller-section">
                <header>
                    <h2><i class="fa-solid fa-clock-rotate-left"></i> Recent orders</h2>
                    <a href="orders.php" class="btn btn-ghost">
                        View all <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </header>

                <?php if (empty($recentOrders)): ?>
                    <div class="seller-empty">
                        <i class="fa-solid fa-receipt"></i>
                        <h3>No orders yet</h3>
                        <p>When customers order your products, they'll show up here.</p>
                    </div>
                <?php else: ?>
                    <div class="order-list">
                        <?php foreach ($recentOrders as $orderId => $row): ?>
                            <?php
                                $o      = $row['order'];
                                $status = $o['status'] ?? 'pending';
                            ?>
                            <article class="seller-order-card">
                                <header class="seller-order-head">
                                    <div class="seller-order-id">
                                        <strong>#<?= e($o['id'] ?? $orderId) ?></strong>
                                        <span class="seller-order-date">
                                            <?php
                                                $ts = !empty($o['date']) ? strtotime($o['date']) : false;
                                                echo e($ts ? date('M j, Y · g:i A', $ts) : 'Unknown date');
                                            ?>
                                        </span>
                                    </div>
                                    <div class="seller-order-head-right">
                                        <span class="status-badge <?= e(order_status_class($status)) ?>">
                                            <?= e(order_status_label($status)) ?>
                                        </span>
                                        <span class="seller-order-total"><?= format_price($row['my_subtotal']) ?></span>
                                    </div>
                                </header>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="seller-section">
                <header>
                    <h2><i class="fa-solid fa-box-open"></i> Recent products</h2>
                    <a href="products.php" class="btn btn-ghost">
                        View all <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </header>

                <?php if (empty($recentProducts)): ?>
                    <div class="seller-empty">
                        <i class="fa-solid fa-box-open"></i>
                        <h3>No products yet</h3>
                        <p>Add your first product to start selling.</p>
                        <a href="add-product.php" class="btn btn-primary">
                            <i class="fa-solid fa-plus"></i> Add your first product
                        </a>
                    </div>
                <?php else: ?>
                    <table class="product-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentProducts as $id => $p): ?>
                                <tr>
                                    <td>
                                        <div class="prod-cell">
                                            <?php if (!empty($p['image'])): ?>
                                                <img src="uploads/<?= e($p['image']) ?>"
                                                     alt="<?= e($p['name']) ?>"
                                                     class="prod-thumb">
                                            <?php else: ?>
                                                <div class="prod-thumb" style="display:grid; place-items:center; color:#aaa;">
                                                    <i class="fa-solid fa-image"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="prod-name"><?= e($p['name']) ?></div>
                                                <div class="prod-cat"><?= e($p['category']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="price-tag"><?= format_price($p['price']) ?></span></td>
                                    <td><?= (int) $p['stock'] ?></td>
                                    <td>
                                        <span class="loc-badge">
                                            <i class="fa-solid fa-location-dot"></i>
                                            <?= e($p['location']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>