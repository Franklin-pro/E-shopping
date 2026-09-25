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
   Load seed reviews (optional)
   ========================================================= */
$seedReviews = [];
$reviewsFile = __DIR__ . '/reviews-data.php';
if (file_exists($reviewsFile)) {
    require $reviewsFile;
}

/* =========================================================
   Find the product by ?id=
   ========================================================= */
$id = (int) ($_GET['id'] ?? 0);

$product = null;
foreach ($products as $p) {
    if ((int) $p['id'] === $id) {
        $product = $p;
        break;
    }
}
unset($p);

/* =========================================================
   Handle the review form (POST)
   ========================================================= */
$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $product) {
    $author  = trim($_POST['author']  ?? '');
    $rating  = (int) ($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($author === '') {
        $errors[] = 'Please enter your name.';
    } elseif (mb_strlen($author) > 60) {
        $errors[] = 'Name is too long (max 60 characters).';
    }

    if ($rating < 1 || $rating > 5) {
        $errors[] = 'Please choose a rating between 1 and 5 stars.';
    }

    if ($comment === '') {
        $errors[] = 'Please write a comment.';
    } elseif (mb_strlen($comment) < 4) {
        $errors[] = 'Comment is too short.';
    } elseif (mb_strlen($comment) > 1000) {
        $errors[] = 'Comment is too long (max 1000 characters).';
    }

    if (empty($errors)) {
        if (!isset($_SESSION['reviews'])) {
            $_SESSION['reviews'] = [];
        }
        if (!isset($_SESSION['reviews'][$id])) {
            $_SESSION['reviews'][$id] = [];
        }

        array_unshift($_SESSION['reviews'][$id], [
            'author'  => $author,
            'rating'  => $rating,
            'comment' => $comment,
            'date'    => date('Y-m-d H:i'),
        ]);

        $success = true;
    }
}

/* =========================================================
   Merge seed reviews + session reviews
   ========================================================= */
$seed        = $seedReviews[$id] ?? [];
$fromSession = $_SESSION['reviews'][$id] ?? [];
$reviews     = array_merge($fromSession, $seed);

/* =========================================================
   Rating stats
   ========================================================= */
$reviewCount = count($reviews);
$avgRating   = $reviewCount > 0
    ? array_sum(array_column($reviews, 'rating')) / $reviewCount
    : 0.0;

$starCounts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
foreach ($reviews as $r) {
    $starCounts[(int) $r['rating']]++;
}

/* =========================================================
   Related products (same category, excluding current)
   ========================================================= */
$related = [];
if ($product) {
    foreach ($products as $p) {
        if ((int) $p['id'] !== (int) $product['id']
            && ($p['category'] ?? '') === ($product['category'] ?? '')) {
            $related[] = $p;
        }
    }
    $related = array_slice($related, 0, 4);
}
unset($p);

/* =========================================================
   Cart count for navbar
   ========================================================= */
$cartCount = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cartCount += (int) $qty;
    }
}

/* =========================================================
   time_ago() — page-specific helper. format_price() and
   render_stars() now live in auth.php.
   ========================================================= */
function time_ago($datetime) {
    $ts = strtotime($datetime);
    if (!$ts) return $datetime;
    $diff = time() - $ts;

    if ($diff < 60)          return 'Just now';
    if ($diff < 3600)        return floor($diff / 60) . ' min ago';
    if ($diff < 86400)       return floor($diff / 3600) . ' h ago';
    if ($diff < 86400 * 30)  return floor($diff / 86400) . ' d ago';
    return date('M j, Y', $ts);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $product ? htmlspecialchars($product['name']) : 'Product Not Found' ?> | G-SHOPPING</title>
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

    <main class="products-page">
        <?php if (!$product): ?>
            <div class="empty-state">
                <i class="fa-solid fa-box-open"></i>
                <h3>Product not found</h3>
                <p>The product you're looking for doesn't exist.</p>
                <p style="margin-top: 2rem;">
                    <a href="products.php" class="btn btn-primary">Back to Products</a>
                </p>
            </div>
        <?php else: ?>
            <?php
                /* Pull fields into locals with safe defaults */
                $pid       = (int) ($product['id'] ?? 0);
                $pname     = $product['name']        ?? 'Untitled';
                $pcat      = $product['category']    ?? '';
                $pdesc     = $product['description'] ?? '';
                $pprice    = (float) ($product['price'] ?? 0);
                $pold      = $product['oldPrice']    ?? null;
                $prating   = (float) ($product['rating'] ?? 0);
                $pinStock  = !empty($product['inStock']);
                $pimage    = $product['image_url']   ?? '';
                $psource   = $product['source']      ?? '';
                $pseller   = $product['seller_name'] ?? 'Seller';
                $plocation = $product['location']    ?? '';

                $displayRating = $avgRating > 0 ? $avgRating : $prating;
            ?>

            <!-- ---------- Breadcrumb ---------- -->
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="index.php">Home</a>
                <span>/</span>
                <a href="products.php">Products</a>
                <span>/</span>
                <a href="products.php?category=<?= urlencode($pcat) ?>">
                    <?= htmlspecialchars($pcat) ?>
                </a>
                <span>/</span>
                <span class="current"><?= htmlspecialchars($pname) ?></span>
            </nav>

            <!-- ---------- Product detail ---------- -->
            <section class="product-detail">
                <div class="product-detail-image">
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

                <div class="product-detail-info">
                    <p class="product-category"><?= htmlspecialchars($pcat) ?></p>
                    <h1 class="product-detail-name"><?= htmlspecialchars($pname) ?></h1>

                    <div class="product-rating">
                        <span class="stars"><?= render_stars($displayRating) ?></span>
                        <span class="rating-value"><?= number_format($displayRating, 1) ?></span>
                        <a href="#reviews" class="rating-link">
                            (<?= $reviewCount ?> review<?= $reviewCount === 1 ? '' : 's' ?>)
                        </a>
                    </div>

                    <div class="product-detail-price">
                        <span class="price-current"><?= format_price($pprice) ?></span>
                        <?php if (!empty($pold)): ?>
                            <span class="price-old"><?= format_price($pold) ?></span>
                            <span class="price-save">
                                Save <?= format_price($pold - $pprice) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <p class="product-detail-desc"><?= htmlspecialchars($pdesc) ?></p>

                    <ul class="product-meta">
                        <li>
                            <i class="fa-solid fa-tag"></i>
                            Category: <strong><?= htmlspecialchars($pcat) ?></strong>
                        </li>

                        <?php if ($psource === 'seller'): ?>
                            <li>
                                <i class="fa-solid fa-store"></i>
                                Sold by <strong><?= htmlspecialchars($pseller) ?></strong>
                            </li>
                            <?php if ($plocation !== ''): ?>
                                <li>
                                    <i class="fa-solid fa-location-dot"></i>
                                    Ships from <strong><?= htmlspecialchars($plocation) ?></strong>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <li>
                            <i class="fa-solid fa-circle-check" style="color: <?= $pinStock ? '#1e9e5a' : '#c33' ?>"></i>
                            <?= $pinStock ? 'In stock — ships within 24h' : 'Currently out of stock' ?>
                        </li>
                        <li><i class="fa-solid fa-truck-fast"></i> Free delivery on orders over $50</li>
                        <li><i class="fa-solid fa-rotate-left"></i> 30-day easy returns</li>
                    </ul>

                    <div class="product-detail-actions">
                        <?php if ($pinStock): ?>
                            <form action="cart.php" method="post">
                                <input type="hidden" name="id" value="<?= $pid ?>">
                                <button class="btn btn-primary btn-lg" type="submit">
                                    <i class="fa-solid fa-cart-plus"></i> Add to Cart
                                </button>
                            </form>
                        <?php else: ?>
                            <button class="btn btn-primary btn-lg" disabled>
                                <i class="fa-solid fa-cart-plus"></i> Out of stock
                            </button>
                        <?php endif; ?>

                        <a href="products.php" class="btn btn-ghost-dark">
                            <i class="fa-solid fa-arrow-left"></i> Keep shopping
                        </a>
                    </div>
                </div>
            </section>

            <!-- ---------- Reviews ---------- -->
            <section class="reviews-section" id="reviews">
                <header class="reviews-header">
                    <h2>Customer Reviews</h2>
                    <p>What people are saying about this product.</p>
                </header>

                <div class="reviews-layout">
                    <aside class="reviews-sidebar">
                        <div class="rating-summary">
                            <div class="rating-summary-score">
                                <span class="score"><?= number_format($avgRating, 1) ?></span>
                                <span class="stars"><?= render_stars($avgRating) ?></span>
                                <span class="count">
                                    <?= $reviewCount ?> review<?= $reviewCount === 1 ? '' : 's' ?>
                                </span>
                            </div>

                            <ul class="rating-breakdown">
                                <?php foreach ([5, 4, 3, 2, 1] as $star): ?>
                                    <?php
                                        $count = $starCounts[$star];
                                        $pct   = $reviewCount > 0 ? ($count / $reviewCount) * 100 : 0;
                                    ?>
                                    <li>
                                        <span class="label"><?= $star ?> <i class="fa-solid fa-star"></i></span>
                                        <span class="bar"><span style="width: <?= number_format($pct, 1) ?>%"></span></span>
                                        <span class="num"><?= $count ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <div class="review-form-card">
                            <h3>Write a review</h3>

                            <?php if ($success): ?>
                                <div class="alert alert-success">
                                    <i class="fa-solid fa-circle-check"></i>
                                    Thanks — your review was posted!
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-error">
                                    <i class="fa-solid fa-circle-exclamation"></i>
                                    <ul>
                                        <?php foreach ($errors as $err): ?>
                                            <li><?= htmlspecialchars($err) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <form method="post" action="product.php?id=<?= $pid ?>#reviews">
                                <label>
                                    Your name
                                    <input
                                        type="text"
                                        name="author"
                                        required
                                        maxlength="60"
                                        value="<?= htmlspecialchars($_POST['author'] ?? '') ?>"
                                        placeholder="Jane D."
                                    >
                                </label>

                                <label>
                                    Rating
                                    <div class="star-picker">
                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                            <input
                                                type="radio"
                                                id="star<?= $i ?>"
                                                name="rating"
                                                value="<?= $i ?>"
                                                <?= (($_POST['rating'] ?? 0) == $i) ? 'checked' : '' ?>
                                                required
                                            >
                                            <label for="star<?= $i ?>" title="<?= $i ?> star<?= $i === 1 ? '' : 's' ?>">
                                                <i class="fa-solid fa-star"></i>
                                            </label>
                                        <?php endfor; ?>
                                    </div>
                                </label>

                                <label>
                                    Comment
                                    <textarea
                                        name="comment"
                                        rows="4"
                                        required
                                        maxlength="1000"
                                        placeholder="Share your experience with this product..."
                                    ><?= htmlspecialchars($_POST['comment'] ?? '') ?></textarea>
                                </label>

                                <button type="submit" class="btn btn-primary" style="width:100%;">
                                    <i class="fa-solid fa-paper-plane"></i> Post Review
                                </button>
                            </form>
                        </div>
                    </aside>

                    <div class="reviews-list">
                        <?php if (empty($reviews)): ?>
                            <div class="empty-state">
                                <i class="fa-regular fa-comment-dots"></i>
                                <h3>No reviews yet</h3>
                                <p>Be the first to review this product.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($reviews as $r): ?>
                                <?php
                                    $rauthor  = $r['author']  ?? 'Anonymous';
                                    $rrating  = (int) ($r['rating'] ?? 0);
                                    $rcomment = $r['comment'] ?? '';
                                    $rdate    = $r['date']    ?? '';
                                ?>
                                <article class="review-item">
                                    <header class="review-head">
                                        <div class="review-avatar">
                                            <?= htmlspecialchars(mb_strtoupper(mb_substr($rauthor, 0, 1))) ?>
                                        </div>
                                        <div class="review-meta">
                                            <strong><?= htmlspecialchars($rauthor) ?></strong>
                                            <div class="review-stars">
                                                <?= render_stars($rrating) ?>
                                                <span class="review-date">· <?= time_ago($rdate) ?></span>
                                            </div>
                                        </div>
                                    </header>
                                    <p class="review-body"><?= nl2br(htmlspecialchars($rcomment)) ?></p>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- ---------- Related products ---------- -->
            <?php if (!empty($related)): ?>
                <section class="related-section">
                    <header class="featured-header">
                        <h2>You might also like</h2>
                        <p>More <?= htmlspecialchars($pcat) ?> picks.</p>
                    </header>

                    <div class="product-grid">
                        <?php foreach ($related as $p): ?>
                            <?php
                                $rid      = (int) ($p['id'] ?? 0);
                                $rname    = $p['name']        ?? 'Untitled';
                                $rcat     = $p['category']    ?? '';
                                $rprice   = (float) ($p['price'] ?? 0);
                                $rold     = $p['oldPrice']    ?? null;
                                $rimage   = $p['image_url']   ?? '';
                                $rinStock = !empty($p['inStock']);
                            ?>
                            <article class="product-card">
                                <a href="product.php?id=<?= $rid ?>" class="product-image-link">
                                    <div class="product-image">
                                        <img
                                            src="<?= htmlspecialchars($rimage) ?>"
                                            alt="<?= htmlspecialchars($rname) ?>"
                                            loading="lazy"
                                        >
                                        <?php if (!empty($rold)): ?>
                                            <span class="badge sale">Sale</span>
                                        <?php endif; ?>
                                        <?php if (!$rinStock): ?>
                                            <span class="badge out">Out of stock</span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                                <div class="product-body">
                                    <p class="product-category"><?= htmlspecialchars($rcat) ?></p>
                                    <h3 class="product-name">
                                        <a href="product.php?id=<?= $rid ?>">
                                            <?= htmlspecialchars($rname) ?>
                                        </a>
                                    </h3>
                                    <div class="product-footer">
                                        <div class="product-price">
                                            <span class="price-current"><?= format_price($rprice) ?></span>
                                        </div>
                                        <a href="product.php?id=<?= $rid ?>" class="add-to-cart">
                                            View
                                        </a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

        <?php endif; ?>
    </main>
    <script src="/js/script.js"></script>
</body>
</html>