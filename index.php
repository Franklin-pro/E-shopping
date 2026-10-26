<?php
session_start();

require_once __DIR__ . '/auth.php';
require __DIR__ . '/js/products-data.php';

/* Merge demo catalog + seller uploads, then normalize */
$products = array_values(get_all_products($products));
normalize_products($products);

$featured = array_slice($products, 0, 6);
$spotlight = array_slice($products, 0, 3);          // hero visuals
$categories = array_slice(array_values(array_unique(array_filter(array_column($products, 'category')))), 0, 6);
$productCount = count($products);

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
    <title>Franklin — Quality products, delivered fast</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,500;12..96,600;12..96,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.css">
    <link rel="stylesheet" href="home.css">
</head>
<body>

<!-- ================= Navbar ================= -->
<header class="fr-nav">
    <div class="fr-wrap fr-nav-inner">
        <a href="index.php" class="fr-logo"><span class="fr-logo-mark">F</span>Franklin</a>

        <nav class="fr-links" aria-label="Main">
            <a href="index.php" class="is-active">Home</a>
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

<main>

<!-- ================= Hero ================= -->
<section class="fr-hero">
    <div class="fr-wrap fr-hero-grid">
        <div class="fr-hero-copy">
            <p class="fr-pill"><i class="fa-solid fa-truck-fast"></i> Fast delivery on every order</p>
            <h1>Everything you need, from sellers you can trust.</h1>
            <p class="fr-lead">Browse <?= (int) $productCount ?> quality products at prices you'll love — add to cart in one click and track your order from the Orders page.</p>

            <div class="fr-cta">
                <a href="products.php" class="fr-btn fr-btn-primary fr-btn-lg">Shop now <i class="fa-solid fa-arrow-right"></i></a>
                <a href="about.php" class="fr-btn fr-btn-outline fr-btn-lg">Learn more</a>
            </div>

            <?php if (!empty($categories)): ?>
                <div class="fr-cats" aria-label="Popular categories">
                    <span>Popular:</span>
                    <?php foreach ($categories as $c): ?>
                        <a href="products.php?category=<?= urlencode($c) ?>"><?= htmlspecialchars($c) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="fr-hero-visual" aria-hidden="<?= empty($spotlight) ? 'true' : 'false' ?>">
            <?php if (!empty($spotlight)): ?>
                <?php foreach ($spotlight as $i => $s): ?>
                    <a href="product.php?id=<?= (int) $s['id'] ?>" class="fr-tile fr-tile-<?= $i + 1 ?>">
                        <img src="<?= htmlspecialchars($s['image_url'] ?? '') ?>" alt="<?= htmlspecialchars($s['name'] ?? '') ?>">
                        <?php if ($i === 0): ?>
                            <span class="fr-tile-tag">
                                <strong><?= htmlspecialchars($s['name'] ?? '') ?></strong>
                                <span><?= format_price((float) ($s['price'] ?? 0)) ?></span>
                            </span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="fr-tile fr-tile-1 fr-tile-empty"><i class="fa-solid fa-box-open"></i></div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ================= Benefits ================= -->
<section class="fr-benefits">
    <div class="fr-wrap fr-benefits-grid">
        <div><i class="fa-solid fa-truck-fast"></i><p><strong>Fast delivery</strong><span>Orders shipped promptly</span></p></div>
        <div><i class="fa-solid fa-shield-halved"></i><p><strong>Secure checkout</strong><span>Your details stay protected</span></p></div>
        <div><i class="fa-solid fa-store"></i><p><strong>Independent sellers</strong><span>Products from real shops</span></p></div>
        <div><i class="fa-solid fa-headset"></i><p><strong>Real support</strong><span>Reach us from the Contact page</span></p></div>
    </div>
</section>

<!-- ================= Featured ================= -->
<section class="fr-section">
    <div class="fr-wrap">
        <div class="fr-section-head">
            <div>
                <h2>Featured products</h2>
                <p>Handpicked items from our catalog.</p>
            </div>
            <a href="products.php" class="fr-link">View all products <i class="fa-solid fa-arrow-right"></i></a>
        </div>

        <?php if (empty($featured)): ?>
            <div class="fr-empty">
                <i class="fa-solid fa-box-open"></i>
                <h3>No products yet</h3>
                <p>The catalog is being filled. Check back soon.</p>
            </div>
        <?php else: ?>
            <div class="fr-grid">
                <?php foreach ($featured as $p): ?>
                    <?php
                        $pid      = (int) ($p['id'] ?? 0);
                        $pname    = $p['name']        ?? 'Untitled';
                        $pcat     = $p['category']    ?? '';
                        $pprice   = (float) ($p['price'] ?? 0);
                        $pold     = $p['oldPrice']    ?? null;
                        $prating  = (float) ($p['rating'] ?? 0);
                        $pinStock = !empty($p['inStock']);
                        $pimage   = $p['image_url']   ?? '';
                        $psource  = $p['source']      ?? '';
                        $pseller  = $p['seller_name'] ?? 'Seller';
                    ?>
                    <article class="fr-card" data-id="<?= $pid ?>">
                        <a href="product.php?id=<?= $pid ?>" class="fr-card-img">
                            <img src="<?= htmlspecialchars($pimage) ?>" alt="<?= htmlspecialchars($pname) ?>" loading="lazy">
                            <?php if (!empty($pold)): ?><span class="fr-badge fr-badge-sale">Sale</span><?php endif; ?>
                            <?php if (!$pinStock): ?><span class="fr-badge fr-badge-out">Out of stock</span><?php endif; ?>
                        </a>

                        <div class="fr-card-body">
                            <p class="fr-card-meta">
                                <?= htmlspecialchars($pcat) ?>
                                <?php if ($psource === 'seller'): ?><span>by <?= htmlspecialchars($pseller) ?></span><?php endif; ?>
                            </p>
                            <h3><a href="product.php?id=<?= $pid ?>"><?= htmlspecialchars($pname) ?></a></h3>

                            <div class="fr-rating">
                                <span class="stars"><?= render_stars($prating) ?></span>
                                <span><?= number_format($prating, 1) ?></span>
                            </div>

                            <div class="fr-card-foot">
                                <div class="fr-price">
                                    <strong><?= format_price($pprice) ?></strong>
                                    <?php if (!empty($pold)): ?><s><?= format_price($pold) ?></s><?php endif; ?>
                                </div>

                                <?php if ($pinStock): ?>
                                    <form action="cart.php" method="post">
                                        <input type="hidden" name="id" value="<?= $pid ?>">
                                        <button class="fr-add" type="submit"><i class="fa-solid fa-plus"></i> Add</button>
                                    </form>
                                <?php else: ?>
                                    <button class="fr-add" disabled>Unavailable</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ================= Seller band ================= -->
<section class="fr-wrap">
    <div class="fr-band">
        <div>
            <h2>Have something to sell?</h2>
            <p>Open your shop on Franklin and reach shoppers the same day you upload.</p>
        </div>
        <a href="register.php" class="fr-btn fr-btn-light fr-btn-lg">Create a seller account</a>
    </div>
</section>

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

</body>
</html>