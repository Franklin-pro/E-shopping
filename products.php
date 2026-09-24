<?php
session_start();

require __DIR__ . '/js/products-data.php';

/* =========================================================
   Read + sanitize query-string inputs
   ========================================================= */
$search   = trim($_GET['q']        ?? '');
$category = trim($_GET['category'] ?? 'All');
$sort     = trim($_GET['sort']     ?? 'default');

/* =========================================================
   Build the category list
   ========================================================= */
$categories = ['All'];
foreach ($products as $p) {
    if (!in_array($p['category'], $categories, true)) {
        $categories[] = $p['category'];
    }
}

/* =========================================================
   Filter: search + category
   ========================================================= */
$filtered = array_filter($products, function ($p) use ($search, $category) {
    $matchesCategory = ($category === 'All' || $p['category'] === $category);

    if ($search === '') {
        return $matchesCategory;
    }

    $haystack = strtolower($p['name'] . ' ' . $p['category'] . ' ' . $p['description']);
    $needle   = strtolower($search);

    return $matchesCategory && str_contains($haystack, $needle);
});

/* =========================================================
   Sort
   ========================================================= */
usort($filtered, function ($a, $b) use ($sort) {
    switch ($sort) {
        case 'price-asc':   return $a['price'] <=> $b['price'];
        case 'price-desc':  return $b['price'] <=> $a['price'];
        case 'name-asc':    return strcasecmp($a['name'], $b['name']);
        case 'rating-desc': return $b['rating'] <=> $a['rating'];
        default:            return 0;
    }
});

/* =========================================================
   Helpers
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

/**
 * Build a URL preserving current filters but overriding some.
 */
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
   Cart count for the navbar badge
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
    <title>Shop Products | E-SHOPPING</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-inner">
            <a href="index.php" class="logo">E-SHOPPING</a>

            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="products.php" class="active">Products</a></li>
                <li><a href="orders.php">Orders</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>

            <div class="nav-btn">
                <a href="cart.php" class="cart" aria-label="Cart">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span class="cart-count" id="cartCount"><?= $cartCount ?></span>
                </a>
                <button onclick="location.href='login.php'">Login</button>
                <button onclick="location.href='register.php'" class="reg">Register</button>
            </div>
        </div>
    </nav>

    <main class="products-page">
        <header class="products-header">
            <h1>Our Products</h1>
            <p>Browse our catalog — search, filter by category, and sort by price.</p>
        </header>

        <!-- Search + sort — GET form so filters live in the URL -->
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
                    <article class="product-card" data-id="<?= (int) $p['id'] ?>">
                        <!-- Image links to the detail page -->
                        <a href="product.php?id=<?= (int) $p['id'] ?>" class="product-image-link">
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
                        </a>

                        <div class="product-body">
                            <p class="product-category"><?= htmlspecialchars($p['category']) ?></p>
                            <h3 class="product-name">
                                <!-- Name also links to the detail page -->
                                <a href="product.php?id=<?= (int) $p['id'] ?>">
                                    <?= htmlspecialchars($p['name']) ?>
                                </a>
                            </h3>
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
            </section>
        <?php endif; ?>
    </main>
    <script src="/js/script.js"></script>
</body>
</html>