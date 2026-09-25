<?php
session_start();

require_once __DIR__ . '/auth.php';
require __DIR__ . '/js/products-data.php';

/* =========================================================
   Merge demo catalog + seller uploads, then normalize.
   ========================================================= */
$products = array_values(get_all_products($products));
normalize_products($products);

/* =========================================================
   Query string inputs
   ========================================================= */
$search   = trim($_GET['q']        ?? '');
$category = trim($_GET['category'] ?? 'All');
$sort     = trim($_GET['sort']     ?? 'default');

/* =========================================================
   Build category list
   ========================================================= */
$categories = ['All'];
foreach ($products as $p) {
    $cat = $p['category'] ?? '';
    if ($cat !== '' && !in_array($cat, $categories, true)) {
        $categories[] = $cat;
    }
}
unset($p);

/* =========================================================
   Filter
   ========================================================= */
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
    $needle = strtolower($search);

    return $matchesCategory && str_contains($haystack, $needle);
});

$filtered = array_values($filtered);

/* =========================================================
   Sort
   ========================================================= */
usort($filtered, function ($a, $b) use ($sort) {
    switch ($sort) {
        case 'price-asc':   return ($a['price'] ?? 0) <=> ($b['price'] ?? 0);
        case 'price-desc':  return ($b['price'] ?? 0) <=> ($a['price'] ?? 0);
        case 'name-asc':    return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
        case 'rating-desc': return ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0);
        default:            return 0;
    }
});

/* =========================================================
   build_url — page-specific helper
   ========================================================= */
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

/* =========================================================
   Cart count
   ========================================================= */
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
    <title>Shop Products | G-SHOPPING</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-inner">
            <a href="index.php" class="logo">G-SHOPPING</a>

            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="products.php" class="active">Products</a></li>
                <li><a href="orders.php">Orders</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>

            <div class="nav-btn">
                <a href="cart.php" class="cart" aria-label="Cart">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span class="cart-count" id="cartCount"><?= (int) $cartCount ?></span>
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

    <main class="products-page">
        <header class="products-header">
            <h1>Our Products</h1>
            <p>Browse our catalog — search, filter by category, and sort by price.</p>
        </header>

        <!-- Search + sort -->
        <form class="products-toolbar" method="get" action="products.php">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input
                    type="text"
                    name="q"
                    id="searchInput"
                    value="<?= htmlspecialchars($search) ?>"
                    placeholder="Search products..."
                    aria-label="Search products"
                    autocomplete="off"
                >
            </div>

            <select
                name="sort"
                id="sortSelect"
                class="sort-select"
                aria-label="Sort products"
                onchange="this.form.submit()"
            >
                <option value="default"     <?= $sort === 'default'     ? 'selected' : '' ?>>Sort: Featured</option>
                <option value="price-asc"   <?= $sort === 'price-asc'   ? 'selected' : '' ?>>Price: Low to High</option>
                <option value="price-desc"  <?= $sort === 'price-desc'  ? 'selected' : '' ?>>Price: High to Low</option>
                <option value="name-asc"    <?= $sort === 'name-asc'    ? 'selected' : '' ?>>Name: A – Z</option>
                <option value="rating-desc" <?= $sort === 'rating-desc' ? 'selected' : '' ?>>Top Rated</option>
            </select>

            <?php if ($category !== 'All'): ?>
                <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
            <?php endif; ?>
        </form>

        <!-- Category filter chips -->
        <div class="category-filters">
            <?php foreach ($categories as $cat): ?>
                <a
                    class="filter-chip <?= $category === $cat ? 'active' : '' ?>"
                    href="<?= htmlspecialchars(build_url(['category' => $cat])) ?>"
                ><?= htmlspecialchars($cat) ?></a>
            <?php endforeach; ?>
        </div>

        <p class="result-count">
            <?php if ($totalResults > 0): ?>
                Showing <?= $totalResults ?> product<?= $totalResults === 1 ? '' : 's' ?>
            <?php endif; ?>
        </p>

        <?php if ($totalResults === 0): ?>
            <div class="empty-state">
                <i class="fa-solid fa-box-open"></i>
                <h3>No products found</h3>
                <p>Try a different search term or category.</p>
            </div>
        <?php else: ?>
            <section class="product-grid">
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
            </section>
        <?php endif; ?>
    </main>
    <script src="/js/script.js"></script>
</body>
</html>