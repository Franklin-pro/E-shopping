<?php
session_start();

/* =========================================================
   Load product catalog (same file products.php uses)
   ========================================================= */
require __DIR__ . '/js/products-data.php';

/**
 * @var array<int, array{
 *   id: int,
 *   name: string,
 *   category: string,
 *   price: float,
 *   oldPrice: float|null,
 *   rating: float,
 *   image: string,
 *   description: string,
 *   inStock: bool
 * }> $products
 */

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

/* =========================================================
   Helpers (same as products.php — you could move these to
   a shared includes/helpers.php later)
   ========================================================= */
function format_price($n) {
    return '$' . number_format($n, 2);
}

function render_stars($rating) {
    $full  = floor($rating);
    $half  = ($rating - $full) >= 0.5;
    $empty = 5 - $full - ($half ? 1 : 0);

    $html = '';
    for ($i = 0; $i < $full;  $i++) $html .= '<i class="fa-solid fa-star"></i>';
    if ($half)                      $html .= '<i class="fa-solid fa-star-half-stroke"></i>';
    for ($i = 0; $i < $empty; $i++) $html .= '<i class="fa-regular fa-star"></i>';
    return $html;
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
                    <span class="cart-count"><?= $cartCount ?></span>
                </a>
                <button onclick="location.href='login.php'">Login</button>
                <button onclick="location.href='register.php'" class="reg">Register</button>
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

        <div class="product-grid featured-grid">
            <?php foreach ($featured as $p): ?>
                <article class="product-card" data-id="<?= (int) $p['id'] ?>">
                    <div class="product-image">
                        <img
                            src="<?= htmlspecialchars($p['image']) ?>"
                            alt="<?= htmlspecialchars($p['name']) ?>"
                            loading="lazy"
                        >
                        <?php if ($p['oldPrice']): ?>
                            <span class="badge sale">Sale</span>
                        <?php endif; ?>
                        <?php if (!$p['inStock']): ?>
                            <span class="badge out">Out of stock</span>
                        <?php endif; ?>
                    </div>

                    <div class="product-body">
                        <p class="product-category"><?= htmlspecialchars($p['category']) ?></p>
                        <h3 class="product-name"><?= htmlspecialchars($p['name']) ?></h3>
                        <p class="product-desc"><?= htmlspecialchars($p['description']) ?></p>

                        <div class="product-rating">
                            <span class="stars"><?= render_stars($p['rating']) ?></span>
                            <span class="rating-value"><?= number_format($p['rating'], 1) ?></span>
                        </div>

                        <div class="product-footer">
                            <div class="product-price">
                                <span class="price-current"><?= format_price($p['price']) ?></span>
                                <?php if ($p['oldPrice']): ?>
                                    <span class="price-old"><?= format_price($p['oldPrice']) ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if ($p['inStock']): ?>
                                <form action="cart.php" method="post">
                                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
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

        <div class="featured-cta">
            <a href="products.php" class="btn btn-primary">
                View All Products <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </section>
</body>
</html>