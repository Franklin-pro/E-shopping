<?php
session_start();

/* =========================================================
   Load product catalog
   ========================================================= */
require __DIR__ . '/js/products-data.php';

/* =========================================================
   Build an id → product lookup
   ========================================================= */
$byId = [];
foreach ($products as $p) {
    $byId[(int) $p['id']] = $p;
}

/* =========================================================
   Handle POST: add / remove / clear
   ========================================================= */
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

        // Return user to wherever they came from (usually products.php)
        // so they keep browsing after clicking Add.
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

/* =========================================================
   Read cart from session
   ========================================================= */
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
    $lineTotal = $product['price'] * $qty;

    $subtotal += $lineTotal;
    $totalQty += $qty;

    $items[] = [
        'product' => $product,
        'qty'     => $qty,
        'line'    => $lineTotal,
    ];
}

/* =========================================================
   Totals — delivery fee + grand total
   ========================================================= */
$deliveryFee = $subtotal > 0 ? 2.50 : 0.00;
$grandTotal  = $subtotal + $deliveryFee;

/* =========================================================
   Helper
   ========================================================= */
function format_price($n) {
    return '$' . number_format($n, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart | E-SHOPPING</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-inner">
            <a href="index.php" class="logo">E-SHOPPING</a>

            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="orders.php">Orders</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>

            <div class="nav-btn">
                <a href="cart.php" class="cart" aria-label="Cart">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span class="cart-count"><?= $totalQty ?></span>
                </a>
                <button onclick="location.href='login.php'">Login</button>
                <button onclick="location.href='register.php'" class="reg">Register</button>
            </div>
        </div>
    </nav>

    <main class="products-page">
        <header class="products-header">
            <h1>Your Cart</h1>
            <p>
                <?php if ($totalQty > 0): ?>
                    You have <?= $totalQty ?> item<?= $totalQty === 1 ? '' : 's' ?> in your cart.
                <?php else: ?>
                    Your cart is currently empty.
                <?php endif; ?>
            </p>
        </header>

        <?php if (empty($items)): ?>
            <!-- ---------- Empty cart ---------- -->
            <div class="empty-state">
                <i class="fa-solid fa-cart-shopping"></i>
                <h3>Your cart is empty</h3>
                <p>Add some products to get started.</p>
                <p style="margin-top: 2rem;">
                    <a href="products.php" class="btn btn-primary">Browse Products</a>
                </p>
            </div>
        <?php else: ?>
            <!-- ---------- Cart table ---------- -->
            <table class="cart-table">
                <thead>
                    <tr>
                        <th colspan="2">Product</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $row): ?>
                        <tr>
                            <td>
                                <img
                                    src="<?= htmlspecialchars($row['product']['image']) ?>"
                                    alt="<?= htmlspecialchars($row['product']['name']) ?>"
                                    class="cart-thumb"
                                >
                            </td>
                            <td>
                                <div class="cart-item-name">
                                    <?= htmlspecialchars($row['product']['name']) ?>
                                </div>
                                <div class="cart-item-category">
                                    <?= htmlspecialchars($row['product']['category']) ?>
                                </div>
                            </td>
                            <td><?= format_price($row['product']['price']) ?></td>
                            <td><?= (int) $row['qty'] ?></td>
                            <td class="cart-line-total"><?= format_price($row['line']) ?></td>
                            <td>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="remove" value="<?= (int) $row['product']['id'] ?>">
                                    <button
                                        type="submit"
                                        class="cart-remove"
                                        aria-label="Remove <?= htmlspecialchars($row['product']['name']) ?>"
                                        onclick="return confirm('Remove this item from your cart?');"
                                    >
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" style="text-align:right; font-weight:700; color:#555;">
                            Subtotal
                        </td>
                        <td class="cart-line-total"><?= format_price($subtotal) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>

            <!-- ---------- Actions ---------- -->
            <div class="cart-actions">
                <form method="post">
                    <button
                        type="submit"
                        name="clear"
                        value="1"
                        class="btn btn-ghost-red"
                        onclick="return confirm('Clear all items from your cart?');"
                    >
                        <i class="fa-solid fa-trash-can"></i> Clear cart
                    </button>
                </form>

                <a href="products.php" class="btn btn-ghost-red">
                    <i class="fa-solid fa-arrow-left"></i> Continue shopping
                </a>
            </div>

            <!-- ---------- Summary ---------- -->
            <div class="cart-summary">
                <h3>Order summary</h3>

                <div class="cart-summary-row">
                    <span>Subtotal (<?= $totalQty ?> item<?= $totalQty === 1 ? '' : 's' ?>)</span>
                    <span><?= format_price($subtotal) ?></span>
                </div>

                <div class="cart-summary-row">
                    <span>Delivery fee</span>
                    <span><?= $deliveryFee > 0 ? format_price($deliveryFee) : '—' ?></span>
                </div>

                <div class="cart-summary-row total">
                    <span>Total</span>
                    <span><?= format_price($grandTotal) ?></span>
                </div>

                <a
                    href="checkout.php"
                    class="btn btn-primary"
                    style="display:block; margin-top:1.6rem;"
                >
                    Proceed to checkout
                </a>
            </div>
        <?php endif; ?>
    </main>
    <script src="/js/script.js"></script>
</body>
</html>