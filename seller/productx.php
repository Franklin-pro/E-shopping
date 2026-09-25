<?php
require_once __DIR__ . '/../auth.php';
require_seller();

$me       = current_user();
$products = get_seller_products($me['email']);
$flashes  = flash_get();
$active   = 'products';

$totalValue = 0;
$totalStock = 0;
foreach ($products as $p) {
    $totalValue += (float) $p['price'] * (int) $p['stock'];
    $totalStock += (int) $p['stock'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | My shop</title>
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
                <h1>My products</h1>
                <p>Add, edit, and remove the products you're selling.</p>
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
                    <span class="value"><?= count($products) ?></span>
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
            </div>

            <section class="seller-section">
                <header>
                    <h2><i class="fa-solid fa-box-open"></i> Products</h2>
                    <a href="add-product.php" class="btn btn-primary">
                        <i class="fa-solid fa-plus"></i> Add product
                    </a>
                </header>

                <?php if (empty($products)): ?>
                    <div class="seller-empty">
                        <i class="fa-solid fa-box-open"></i>
                        <h3>No products yet</h3>
                        <p>Upload your first product to start selling.</p>
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
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $id => $p): ?>
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
                                    <td>
                                        <div class="actions">
                                            <a href="edit-product.php?id=<?= (int) $id ?>" class="btn-mini edit">
                                                <i class="fa-solid fa-pen"></i> Edit
                                            </a>
                                            <form method="post" action="delete-product.php"
                                                  style="display:inline;"
                                                  onsubmit="return confirm('Delete this product?');">
                                                <input type="hidden" name="id" value="<?= (int) $id ?>">
                                                <button type="submit" class="btn-mini delete">
                                                    <i class="fa-solid fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
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