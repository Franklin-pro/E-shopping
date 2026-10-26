<?php
session_start();

require_once __DIR__ . '/auth.php';
require __DIR__ . '/js/products-data.php';

/* Merge demo catalog + seller uploads, then normalize. */
$products = array_values(get_all_products($products));
normalize_products($products);

/* Query string inputs */
$search   = trim($_GET['q']        ?? '');
$category = trim($_GET['category'] ?? 'All');
$sort     = trim($_GET['sort']     ?? 'default');

/* Category list */
$categories = ['All'];
foreach ($products as $p) {
    $cat = $p['category'] ?? '';
    if ($cat !== '' && !in_array($cat, $categories, true)) {
        $categories[] = $cat;
    }
}
unset($p);

/* Filter */
$filtered = array_filter($products, function ($p) use ($search, $category) {
    $matchesCategory = ($category === 'All' || ($p['category'] ?? '') === $category);

    if ($search === '') {
        return $matchesCategory;
    }

    $haystack = strtolower(
        ($p['name'] ?? '') . ' ' .
        ($p['category'] ?? '') . ' ' .
        ($p['description'] ?? '')
    );

    return $matchesCategory && str_contains($haystack, strtolower($search));
});
$filtered = array_values($filtered);

/* Sort */
usort($filtered, function ($a, $b) use ($sort) {
    switch ($sort) {
        case 'price-asc':   return ($a['price'] ?? 0) <=> ($b['price'] ?? 0);
        case 'price-desc':  return ($b['price'] ?? 0) <=> ($a['price'] ?? 0);
        case 'name-asc':    return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
        case 'rating-desc': return ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0);
        default:            return 0;
    }
});

/* build_url — page-specific helper */
function build_url(array $overrides = []) {
    $params = array_merge([
        'q'        => $_GET['q']        ?? '',
        'category' => $_GET['category'] ?? 'All',
        'sort'     => $_GET['sort']     ?? 'default',
    ], $overrides);

    if ($params['q'] === '')           unset($params['q']);
    if ($params['category'] === 'All') unset($params['category']);
    if ($params['sort'] === 'default') unset($params['sort']);

    return 'products.php' . ($params ? '?' . http_build_query($params) : '');
}

/* Cart count */
$cartCount = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cartCount += (int) $qty;
    }
}

$totalResults = count($filtered);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Products | Franklin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,500;12..96,600;12..96,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.css">
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="shop.css">
</head>
<body>
<header class="fr-nav">
    <div class="fr-wrap fr-nav-inner">
        <a href="index.php" class="fr-logo"><span class="fr-logo-mark">F</span>Franklin</a>

        <nav class="fr-links" aria-label="Main">
            <a href="index.php">Home</a>
            <a href="products.php" class="is-active">Products</a>
            <a href="orders.php">Orders</a>
            <a href="contact.php">Contact</a>
        </nav>

        <div class="fr-actions">
            <a href="cart.php" class="fr-cart" aria-label="Cart, <?= (int) $cartCount ?> items">
                <i class="fa-solid fa-bag-shopping"></i>
                <?php if ($cartCount > 0): ?><span class="fr-cart-count" id="cartCount"><?= (int) $cartCount ?></span><?php endif; ?>
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
    <header class="fr-page-head">
        <h1>Our products</h1>
        <p>Search the catalog, filter by category and sort by price or rating.</p>
    </header>

    <form class="fr-toolbar" method="get" action="products.php">
        <div class="fr-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="q" id="searchInput"
                   value="<?= htmlspecialchars($search) ?>"
                   placeholder="Search products"
                   aria-label="Search products" autocomplete="off">
        </div>

        <select name="sort" id="sortSelect" class="fr-select" aria-label="Sort products" onchange="this.form.submit()">
            <option value="default"     <?= $sort === 'default'     ? 'selected' : '' ?>>Sort: Featured</option>
            <option value="price-asc"   <?= $sort === 'price-asc'   ? 'selected' : '' ?>>Price: low to high</option>
            <option value="price-desc"  <?= $sort === 'price-desc'  ? 'selected' : '' ?>>Price: high to low</option>
            <option value="name-asc"    <?= $sort === 'name-asc'    ? 'selected' : '' ?>>Name: A to Z</option>
            <option value="rating-desc" <?= $sort === 'rating-desc' ? 'selected' : '' ?>>Top rated</option>
        </select>

        <?php if ($category !== 'All'): ?>
            <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
        <?php endif; ?>
    </form>

    <div class="fr-chips" aria-label="Categories">
        <?php foreach ($categories as $cat): ?>
            <a class="fr-chip <?= $category === $cat ? 'is-active' : '' ?>"
               href="<?= htmlspecialchars(build_url(['category' => $cat])) ?>"><?= htmlspecialchars($cat) ?></a>
        <?php endforeach; ?>
    </div>

    <p class="fr-count">
        <?php if ($totalResults > 0): ?>
            Showing <?= $totalResults ?> product<?= $totalResults === 1 ? '' : 's' ?>
        <?php endif; ?>
    </p>

    <?php if ($totalResults === 0): ?>
        <div class="fr-empty">
            <i class="fa-solid fa-box-open"></i>
            <h3>No products found</h3>
            <p>Try a different search term or category.</p>
        </div>
    <?php else: ?>
        <section class="fr-grid fr-grid-4">
            <?php foreach ($filtered as $p): ?>
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
                        <p class="fr-card-desc"><?= htmlspecialchars($pdesc) ?></p>

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
        </section>
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