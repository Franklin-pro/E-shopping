<?php
session_start();

require_once __DIR__ . '/auth.php';
require __DIR__ . '/js/products-data.php';

/* Merge demo catalog + seller uploads, then normalize. */
$products = array_values(get_all_products($products));
normalize_products($products);

/* Load seed reviews (optional) */
$seedReviews = [];
$reviewsFile = __DIR__ . '/reviews-data.php';
if (file_exists($reviewsFile)) {
    require $reviewsFile;
}

/* Find the product by ?id= */
$id = (int) ($_GET['id'] ?? 0);

$product = null;
foreach ($products as $p) {
    if ((int) $p['id'] === $id) {
        $product = $p;
        break;
    }
}
unset($p);

/* Handle the review form (POST) */
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

/* Merge seed reviews + session reviews */
$seed        = $seedReviews[$id] ?? [];
$fromSession = $_SESSION['reviews'][$id] ?? [];
$reviews     = array_merge($fromSession, $seed);

/* Rating stats */
$reviewCount = count($reviews);
$avgRating   = $reviewCount > 0
    ? array_sum(array_column($reviews, 'rating')) / $reviewCount
    : 0.0;

$starCounts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
foreach ($reviews as $r) {
    $s = (int) ($r['rating'] ?? 0);
    if (isset($starCounts[$s])) {
        $starCounts[$s]++;
    }
}

/* Related products (same category, excluding current) */
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

/* Cart count */
$cartCount = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cartCount += (int) $qty;
    }
}

/* time_ago() — page-specific helper */
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
    <title><?= $product ? htmlspecialchars($product['name']) : 'Product not found' ?> | Franklin</title>
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
<?php if (!$product): ?>
    <div class="fr-empty">
        <i class="fa-solid fa-box-open"></i>
        <h3>Product not found</h3>
        <p>The product you're looking for doesn't exist.</p>
        <p style="margin-top:24px"><a href="products.php" class="fr-btn fr-btn-primary">Back to products</a></p>
    </div>
<?php else: ?>
    <?php
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

    <nav class="fr-crumbs" aria-label="Breadcrumb">
        <a href="index.php">Home</a><span>/</span>
        <a href="products.php">Products</a><span>/</span>
        <a href="products.php?category=<?= urlencode($pcat) ?>"><?= htmlspecialchars($pcat) ?></a><span>/</span>
        <span class="current"><?= htmlspecialchars($pname) ?></span>
    </nav>

    <!-- Product detail -->
    <section class="fr-detail">
        <div class="fr-detail-img">
            <img src="<?= htmlspecialchars($pimage) ?>" alt="<?= htmlspecialchars($pname) ?>">
            <?php if (!empty($pold)): ?><span class="fr-badge fr-badge-sale">Sale</span><?php endif; ?>
            <?php if (!$pinStock): ?><span class="fr-badge fr-badge-out">Out of stock</span><?php endif; ?>
            <?php if ($psource === 'seller'): ?><span class="fr-seller-tag">By <?= htmlspecialchars($pseller) ?></span><?php endif; ?>
        </div>

        <div class="fr-detail-info">
            <p class="fr-card-meta"><?= htmlspecialchars($pcat) ?></p>
            <h1><?= htmlspecialchars($pname) ?></h1>

            <div class="fr-rating">
                <span class="stars"><?= render_stars($displayRating) ?></span>
                <span><?= number_format($displayRating, 1) ?></span>
                <a href="#reviews">(<?= $reviewCount ?> review<?= $reviewCount === 1 ? '' : 's' ?>)</a>
            </div>

            <div class="fr-detail-price">
                <strong><?= format_price($pprice) ?></strong>
                <?php if (!empty($pold)): ?>
                    <s><?= format_price($pold) ?></s>
                    <span class="fr-save">Save <?= format_price($pold - $pprice) ?></span>
                <?php endif; ?>
            </div>

            <p class="fr-detail-desc"><?= htmlspecialchars($pdesc) ?></p>

            <ul class="fr-meta">
                <li><i class="fa-solid fa-tag"></i><span>Category: <strong><?= htmlspecialchars($pcat) ?></strong></span></li>
                <?php if ($psource === 'seller'): ?>
                    <li><i class="fa-solid fa-store"></i><span>Sold by <strong><?= htmlspecialchars($pseller) ?></strong></span></li>
                    <?php if ($plocation !== ''): ?>
                        <li><i class="fa-solid fa-location-dot"></i><span>Ships from <strong><?= htmlspecialchars($plocation) ?></strong></span></li>
                    <?php endif; ?>
                <?php endif; ?>
                <li>
                    <i class="fa-solid fa-circle-check <?= $pinStock ? 'ok' : 'no' ?>"></i>
                    <span><?= $pinStock ? 'In stock — ships within 24h' : 'Currently out of stock' ?></span>
                </li>
                <li><i class="fa-solid fa-truck-fast"></i><span>Free delivery on orders over $50</span></li>
                <li><i class="fa-solid fa-rotate-left"></i><span>30-day easy returns</span></li>
            </ul>

            <div class="fr-detail-actions">
                <?php if ($pinStock): ?>
                    <form action="cart.php" method="post">
                        <input type="hidden" name="id" value="<?= $pid ?>">
                        <button class="fr-btn fr-btn-primary fr-btn-lg" type="submit"><i class="fa-solid fa-cart-plus"></i> Add to cart</button>
                    </form>
                <?php else: ?>
                    <button class="fr-btn fr-btn-primary fr-btn-lg" disabled>Out of stock</button>
                <?php endif; ?>
                <a href="products.php" class="fr-btn fr-btn-outline fr-btn-lg"><i class="fa-solid fa-arrow-left"></i> Keep shopping</a>
            </div>
        </div>
    </section>

    <!-- Reviews -->
    <section class="fr-reviews" id="reviews">
        <header>
            <h2>Customer reviews</h2>
            <p>What people are saying about this product.</p>
        </header>

        <div class="fr-reviews-layout">
            <aside>
                <div class="fr-panel">
                    <div class="fr-score">
                        <b><?= number_format($avgRating, 1) ?></b>
                        <div>
                            <span class="stars"><?= render_stars($avgRating) ?></span>
                            <small><?= $reviewCount ?> review<?= $reviewCount === 1 ? '' : 's' ?></small>
                        </div>
                    </div>
                    <ul class="fr-bars">
                        <?php foreach ([5, 4, 3, 2, 1] as $star): ?>
                            <?php
                                $count = $starCounts[$star];
                                $pct   = $reviewCount > 0 ? ($count / $reviewCount) * 100 : 0;
                            ?>
                            <li>
                                <span><?= $star ?> <i class="fa-solid fa-star"></i></span>
                                <span class="fr-bar"><span style="width:<?= number_format($pct, 1, '.', '') ?>%"></span></span>
                                <span class="n"><?= $count ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="fr-panel">
                    <h3>Write a review</h3>

                    <?php if ($success): ?>
                        <div class="fr-alert fr-alert-ok" role="status">
                            <i class="fa-solid fa-circle-check"></i> Thanks — your review was posted.
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="fr-alert fr-alert-err" role="alert">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <ul>
                                <?php foreach ($errors as $err): ?>
                                    <li><?= htmlspecialchars($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="product.php?id=<?= $pid ?>#reviews">
                        <div class="fr-field">
                            <label for="author">Your name</label>
                            <input type="text" id="author" name="author" required maxlength="60"
                                   value="<?= htmlspecialchars($_POST['author'] ?? '') ?>" placeholder="Jane D.">
                        </div>

                        <fieldset class="fr-field">
                            <legend>Rating</legend>
                            <div class="fr-star-input">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>"
                                           <?= (($_POST['rating'] ?? 0) == $i) ? 'checked' : '' ?> required>
                                    <label for="star<?= $i ?>" title="<?= $i ?> star<?= $i === 1 ? '' : 's' ?>">
                                        <i class="fa-solid fa-star"></i>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </fieldset>

                        <div class="fr-field">
                            <label for="comment">Comment</label>
                            <textarea id="comment" name="comment" rows="4" required maxlength="1000"
                                      placeholder="Share your experience with this product"><?= htmlspecialchars($_POST['comment'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" class="fr-btn fr-btn-primary fr-btn-block">
                            <i class="fa-solid fa-paper-plane"></i> Post review
                        </button>
                    </form>
                </div>
            </aside>

            <div class="fr-reviews-list">
                <?php if (empty($reviews)): ?>
                    <div class="fr-empty">
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
                        <article class="fr-review">
                            <header class="fr-review-head">
                                <div class="fr-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr($rauthor, 0, 1))) ?></div>
                                <div class="fr-review-meta">
                                    <strong><?= htmlspecialchars($rauthor) ?></strong>
                                    <span class="stars"><?= render_stars($rrating) ?></span>
                                    <small><?= time_ago($rdate) ?></small>
                                </div>
                            </header>
                            <p class="fr-review-body"><?= nl2br(htmlspecialchars($rcomment)) ?></p>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Related -->
    <?php if (!empty($related)): ?>
        <section class="fr-related">
            <header>
                <h2>You might also like</h2>
                <p>More <?= htmlspecialchars($pcat) ?> picks.</p>
            </header>

            <div class="fr-grid fr-grid-4">
                <?php foreach ($related as $p): ?>
                    <?php
                        $rid      = (int) ($p['id'] ?? 0);
                        $rname    = $p['name']      ?? 'Untitled';
                        $rcat     = $p['category']  ?? '';
                        $rprice   = (float) ($p['price'] ?? 0);
                        $rold     = $p['oldPrice']  ?? null;
                        $rimage   = $p['image_url'] ?? '';
                        $rinStock = !empty($p['inStock']);
                    ?>
                    <article class="fr-card">
                        <a href="product.php?id=<?= $rid ?>" class="fr-card-img">
                            <img src="<?= htmlspecialchars($rimage) ?>" alt="<?= htmlspecialchars($rname) ?>" loading="lazy">
                            <?php if (!empty($rold)): ?><span class="fr-badge fr-badge-sale">Sale</span><?php endif; ?>
                            <?php if (!$rinStock): ?><span class="fr-badge fr-badge-out">Out of stock</span><?php endif; ?>
                        </a>
                        <div class="fr-card-body">
                            <p class="fr-card-meta"><?= htmlspecialchars($rcat) ?></p>
                            <h3><a href="product.php?id=<?= $rid ?>"><?= htmlspecialchars($rname) ?></a></h3>
                            <div class="fr-card-foot">
                                <div class="fr-price"><strong><?= format_price($rprice) ?></strong></div>
                                <a href="product.php?id=<?= $rid ?>" class="fr-add-link">View</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
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