<?php
session_start();

require_once __DIR__ . '/auth.php';
require __DIR__ . '/js/products-data.php';

/* Merge demo catalog + seller uploads, then normalize. */
$products = array_values(get_all_products($products));
normalize_products($products);

/* Lookup map: id => product */
$byId = [];
foreach ($products as $p) {
    $byId[(int) $p['id']] = $p;
}

/* Handle POST actions (add / remove / clear) */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ---- Add to cart ---- */
    if (isset($_POST['id'])) {
        $id = (int) $_POST['id'];

        if ($id > 0 && isset($byId[$id]) && !empty($byId[$id]['inStock'])) {
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + 1;
        }

        $back = $_SERVER['HTTP_REFERER'] ?? 'products.php';
        header('Location: ' . $back);
        exit;
    }

    /* ---- Remove one item ---- */
    if (isset($_POST['remove'])) {
        $rid = (int) $_POST['remove'];
        if (isset($_SESSION['cart'][$rid])) {
            unset($_SESSION['cart'][$rid]);
        }
        header('Location: cart.php');
        exit;
    }

    /* ---- Clear all ---- */
    if (isset($_POST['clear'])) {
        $_SESSION['cart'] = [];
        header('Location: cart.php');
        exit;
    }
}

/* Read cart from session */
$cart     = $_SESSION['cart'] ?? [];
$items    = [];
$subtotal = 0;
$totalQty = 0;

foreach ($cart as $id => $qty) {
    $id  = (int) $id;
    $qty = (int) $qty;

    if ($qty <= 0 || !isset($byId[$id])) {
        continue;
    }

    $product   = $byId[$id];
    $lineTotal = (float) ($product['price'] ?? 0) * $qty;

    $subtotal += $lineTotal;
    $totalQty += $qty;

    $items[] = [
        'product' => $product,
        'qty'     => $qty,
        'line'    => $lineTotal,
    ];
}

/* Totals */
$deliveryFee = $subtotal > 0 ? 2.50 : 0.00;
$grandTotal  = $subtotal + $deliveryFee;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your cart | Franklin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,500;12..96,600;12..96,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.css">
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="shop.css">
    <link rel="stylesheet" href="account.css">
</head>
<body>
<header class="fr-nav">
    <div class="fr-wrap fr-nav-inner">
        <a href="index.php" class="fr-logo"><span class="fr-logo-mark">F</span>Franklin</a>

        <nav class="fr-links" aria-label="Main">
            <a href="index.php">Home</a>
            <a href="products.php">Products</a>
            <a href="orders.php">Orders</a>
            <a href="contact.php">Contact</a>
        </nav>

        <div class="fr-actions">
            <a href="cart.php" class="fr-cart" aria-label="Cart, <?= (int) $totalQty ?> items">
                <i class="fa-solid fa-bag-shopping"></i>
                <?php if ($totalQty > 0): ?><span class="fr-cart-count"><?= (int) $totalQty ?></span><?php endif; ?>
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
        <h1>Your cart</h1>
        <p>
            <?php if ($totalQty > 0): ?>
                You have <?= $totalQty ?> item<?= $totalQty === 1 ? '' : 's' ?> in your cart.
            <?php else: ?>
                Your cart is currently empty.
            <?php endif; ?>
        </p>
    </header>

<?php if (empty($items)): ?>
    <div class="fr-empty">
        <i class="fa-solid fa-bag-shopping"></i>
        <h3>Your cart is empty</h3>
        <p>Add some products to get started.</p>
        <p style="margin-top:24px"><a href="products.php" class="fr-btn fr-btn-primary">Browse products</a></p>
    </div>
<?php else: ?>
    <div class="fr-layout">
        <div>
            <div class="fr-lines">
                <?php foreach ($items as $row): ?>
                    <?php
                        $p         = $row['product'] ?? [];
                        $pid       = (int) ($p['id'] ?? 0);
                        $pname     = $p['name']         ?? 'Product';
                        $pcat      = $p['category']     ?? '';
                        $pimage    = $p['image_url']    ?? ('/seller/uploads/' . ($p['image'] ?? ''));
                        $pprice    = (float) ($p['price'] ?? 0);
                        $psource   = $p['source']       ?? '';
                        $pseller   = $p['seller_name']  ?? 'Seller';
                        $lineQty   = (int) ($row['qty']  ?? 0);
                        $lineTotal = (float) ($row['line'] ?? 0);
                    ?>
                    <article class="fr-line">
                        <a href="product.php?id=<?= $pid ?>"><img src="<?= htmlspecialchars($pimage) ?>" alt="<?= htmlspecialchars($pname) ?>"></a>
                        <div>
                            <a href="product.php?id=<?= $pid ?>" class="fr-line-name"><?= htmlspecialchars($pname) ?></a>
                            <?php if ($pcat !== ''): ?><small><?= htmlspecialchars($pcat) ?></small><?php endif; ?>
                            <?php if ($psource === 'seller'): ?><small>Sold by <?= htmlspecialchars($pseller) ?></small><?php endif; ?>
                            <span class="unit"><?= format_price($pprice) ?> each</span>
                        </div>
                        <div class="fr-line-end">
                            <span class="fr-qty">Qty <?= $lineQty ?></span>
                            <span class="fr-line-total"><?= format_price($lineTotal) ?></span>
                        </div>
                        <form method="post">
                            <input type="hidden" name="remove" value="<?= $pid ?>">
                            <button type="submit" class="fr-remove"
                                    aria-label="Remove <?= htmlspecialchars($pname) ?>"
                                    onclick="return confirm('Remove this item from your cart?');">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="fr-cart-actions">
                <a href="products.php" class="fr-btn fr-btn-outline"><i class="fa-solid fa-arrow-left"></i> Continue shopping</a>
                <form method="post">
                    <button type="submit" name="clear" value="1" class="fr-btn fr-btn-danger"
                            onclick="return confirm('Clear all items from your cart?');">
                        <i class="fa-solid fa-trash-can"></i> Clear cart
                    </button>
                </form>
            </div>
        </div>

        <aside class="fr-panel fr-sticky">
            <h3>Order summary</h3>
            <div class="fr-sum"><span>Subtotal (<?= $totalQty ?> item<?= $totalQty === 1 ? '' : 's' ?>)</span><span><?= format_price($subtotal) ?></span></div>
            <div class="fr-sum"><span>Delivery fee</span><span><?= $deliveryFee > 0 ? format_price($deliveryFee) : '—' ?></span></div>
            <div class="fr-sum total"><span>Total</span><span><?= format_price($grandTotal) ?></span></div>
            <a href="checkout.php" class="fr-btn fr-btn-primary fr-btn-lg fr-btn-block" style="margin-top:20px">Proceed to checkout</a>
        </aside>
    </div>
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