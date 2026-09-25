<?php
session_start();

require_once __DIR__ . '/auth.php';
require __DIR__ . '/js/products-data.php';

/* =========================================================
   Merge demo catalog + seller uploads, then normalize so
   every product has image_url, rating, inStock, oldPrice.
   ========================================================= */
$products = array_values(get_all_products($products));
normalize_products($products);

/* =========================================================
   Take only the first 6 products for the featured section
   ========================================================= */
$featured = array_slice($products, 0, 6);

/* =========================================================
   Cart count for the navbar badge
   ========================================================= */
$cartCount = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cartCount += (int) $qty;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-SHOPPING</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-inner">
            <a href="index.php" class="logo">E-SHOPPING</a>

            <ul class="nav-links">
                <li><a href="index.php" class="active">Home</a></li>
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

    <!-- Hero -->
    <section class="hero">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Welcome to E-SHOPPING</h1>
            <p>Your one-stop shop for all your needs — quality products, fast delivery, and prices you'll love.</p>
            <div class="hero-actions">
                <a href="products.php" class="btn btn-primary">Shop Now</a>
                <a href="about.php" class="btn btn-ghost">Learn More</a>
            </div>
        </div>
    </section>

    <!-- Featured products -->
    <section class="featured-section">
        <header class="featured-header">
            <h2>Featured Products</h2>
            <p>Handpicked items from our catalog just for you.</p>
        </header>

        <?php if (empty($featured)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-box-open"></i>
                <h3>No products yet</h3>
                <p>Check back soon — our catalog is being filled.</p>
            </div>
        <?php else: ?>
            <div class="product-grid featured-grid">
                <?php foreach ($featured as $p): ?>
                    <?php
                        $pid      = (int) ($p['id'] ?? 0);
                        $pname    = $p['name']        ?? 'Untitled';
                        $pcat     = $p['category']    ?? '';
                        $pdesc    = $p['description'] ?? '';
                        $pprice   = (float) ($p['price'] ?? 0);
                        $pold     = $p['oldPrice']    ?? null;
                        $prating  = (float) ($p['rating'] ?? 0);
                        $pinStock = !empty($p['inStock']);
                        $pimage   = $p['image_url']   ?? '';
                        $psource  = $p['source']      ?? '';
                        $pseller  = $p['seller_name'] ?? 'Seller';
                    ?>
                    <article class="product-card" data-id="<?= $pid ?>">
                        <a href="product.php?id=<?= $pid ?>" class="product-image-link">
                            <div class="product-image">
                                <img
                                    src="<?= htmlspecialchars($pimage) ?>"
                                    alt="<?= htmlspecialchars($pname) ?>"
                                    loading="lazy"
                                >
                                <?php if (!empty($pold)): ?>
                                    <span class="badge sale">Sale</span>
                                <?php endif; ?>
                                <?php if (!$pinStock): ?>
                                    <span class="badge out">Out of stock</span>
                                <?php endif; ?>
                                <?php if ($psource === 'seller'): ?>
                                    <span class="badge" style="background:#111; top:auto; bottom:10px; left:10px;">
                                        By <?= htmlspecialchars($pseller) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </a>

                        <div class="product-body">
                            <p class="product-category"><?= htmlspecialchars($pcat) ?></p>
                            <h3 class="product-name">
                                <a href="product.php?id=<?= $pid ?>">
                                    <?= htmlspecialchars($pname) ?>
                                </a>
                            </h3>
                            <p class="product-desc"><?= htmlspecialchars($pdesc) ?></p>

                            <div class="product-rating">
                                <span class="stars"><?= render_stars($prating) ?></span>
                                <span class="rating-value"><?= number_format($prating, 1) ?></span>
                            </div>

                            <div class="product-footer">
                                <div class="product-price">
                                    <span class="price-current"><?= format_price($pprice) ?></span>
                                    <?php if (!empty($pold)): ?>
                                        <span class="price-old"><?= format_price($pold) ?></span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($pinStock): ?>
                                    <form action="cart.php" method="post">
                                        <input type="hidden" name="id" value="<?= $pid ?>">
                                        <button class="add-to-cart" type="submit">
                                            <i class="fa-solid fa-cart-plus"></i> Add
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="add-to-cart" disabled>
                                        <i class="fa-solid fa-cart-plus"></i> N/A
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="featured-cta">
            <a href="products.php" class="btn btn-primary">
                View All Products <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </section>
</body>
</html>