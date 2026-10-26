<?php
require_once __DIR__ . '/../auth.php';
require_admin();

/* =========================================================
   Handle approve / reject actions
   ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $email  = strtolower(trim($_POST['email'] ?? ''));

    if ($email !== '' && isset($_SESSION['users'][$email])) {
        if ($action === 'approve') {
            $_SESSION['users'][$email]['status'] = 'approved';
            flash_set('success', 'Approved seller: ' . $email);
        } elseif ($action === 'reject') {
            $_SESSION['users'][$email]['status'] = 'rejected';
            flash_set('info', 'Rejected seller: ' . $email);
        }
    }

    // Absolute path from project root (leading slash)
    header('Location: /admin/index.php');
    exit;
}

$flashes = flash_get();

/* =========================================================
   Split users by status / role for the dashboard
   ========================================================= */
$allUsers        = $_SESSION['users'] ?? [];
$pendingSellers  = [];
$approvedSellers = [];
$rejectedSellers = [];
$customers       = [];
$admins          = [];

foreach ($allUsers as $email => $u) {
    if ($u['role'] === 'admin') {
        $admins[$email] = $u;
    } elseif ($u['role'] === 'seller') {
        if ($u['status'] === 'pending')       $pendingSellers[$email]  = $u;
        elseif ($u['status'] === 'approved')  $approvedSellers[$email] = $u;
        elseif ($u['status'] === 'rejected')  $rejectedSellers[$email] = $u;
    } else {
        $customers[$email] = $u;
    }
}

$me = current_user();

/* Cart count for the navbar badge */
$cartCount = 0;
foreach (($_SESSION['cart'] ?? []) as $qty) {
    $cartCount += (int) $qty;
}

/* Renders the avatar + name + email cell */
function user_cell(array $u): void { ?>
    <div class="ad-user">
        <span class="ad-avatar"><?= e(mb_strtoupper(mb_substr($u['name'] ?? '?', 0, 1))) ?></span>
        <div>
            <strong><?= e($u['name'] ?? '') ?></strong>
            <small><?= e($u['email'] ?? '') ?></small>
        </div>
    </div>
<?php }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin dashboard | Franklin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,500;12..96,600;12..96,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.css">

    <!-- shared site styles (one folder up) -->
    <link rel="stylesheet" href="../home.css">
    <!-- admin-specific styles (same folder as this file) -->
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>

<header class="fr-nav">
    <div class="fr-wrap fr-nav-inner">
        <a href="/index.php" class="fr-logo"><span class="fr-logo-mark">F</span>Franklin</a>

        <nav class="fr-links" aria-label="Main">
            <a href="/index.php">Home</a>
            <a href="/products.php">Products</a>
            <a href="/orders.php">Orders</a>
            <a href="/admin/index.php" class="is-active">Admin</a>
        </nav>

        <div class="fr-actions">
            <a href="/cart.php" class="fr-cart" aria-label="Cart, <?= (int) $cartCount ?> items">
                <i class="fa-solid fa-bag-shopping"></i>
                <?php if ($cartCount > 0): ?><span class="fr-cart-count"><?= (int) $cartCount ?></span><?php endif; ?>
            </a>
            <a href="/logout.php" class="fr-btn fr-btn-dark">Sign out</a>
        </div>
    </div>
</header>

<main class="fr-wrap ad-page">
    <header class="ad-head">
        <div>
            <h1>Admin dashboard</h1>
            <p>Review seller requests and manage the people on Franklin.</p>
        </div>
        <div class="ad-me">
            <span class="ad-avatar"><?= e(mb_strtoupper(mb_substr($me['name'], 0, 1))) ?></span>
            <span><?= e($me['name']) ?> <span class="ad-pill ad-pill-admin">Admin</span></span>
        </div>
    </header>

    <?php foreach ($flashes as $f): ?>
        <div class="ad-alert ad-alert-<?= e($f['type']) ?>" role="status">
            <i class="fa-solid fa-circle-info"></i>
            <span><?= e($f['message']) ?></span>
        </div>
    <?php endforeach; ?>

    <!-- ============= Stats ============= -->
    <div class="ad-stats">
        <div class="ad-stat"><i class="fa-solid fa-users"></i><div><b><?= count($allUsers) ?></b><span>Total users</span></div></div>
        <div class="ad-stat <?= count($pendingSellers) > 0 ? 'is-alert' : '' ?>"><i class="fa-solid fa-clock"></i><div><b><?= count($pendingSellers) ?></b><span>Pending sellers</span></div></div>
        <div class="ad-stat"><i class="fa-solid fa-store"></i><div><b><?= count($approvedSellers) ?></b><span>Approved sellers</span></div></div>
        <div class="ad-stat"><i class="fa-solid fa-user"></i><div><b><?= count($customers) ?></b><span>Customers</span></div></div>
    </div>

    <!-- ============= Pending sellers ============= -->
    <section class="ad-section <?= count($pendingSellers) > 0 ? 'is-pending' : '' ?>">
        <header>
            <h2><i class="fa-solid fa-user-clock"></i> Pending seller requests</h2>
            <span class="ad-count"><?= count($pendingSellers) ?> awaiting review</span>
        </header>

        <?php if (empty($pendingSellers)): ?>
            <div class="ad-empty"><i class="fa-solid fa-circle-check"></i> No pending seller requests. You're all caught up.</div>
        <?php else: ?>
            <div class="ad-scroll">
                <table class="ad-table">
                    <thead>
                        <tr><th>User</th><th>Role</th><th>Status</th><th>Registered</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingSellers as $email => $u): ?>
                            <tr>
                                <td><?php user_cell($u); ?></td>
                                <td><span class="ad-pill ad-pill-seller">Seller</span></td>
                                <td><span class="ad-status ad-status-pending"><i class="fa-solid fa-clock"></i> Pending</span></td>
                                <td><?= e($u['created']) ?></td>
                                <td>
                                    <div class="ad-actions">
                                        <form method="post">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="email" value="<?= e($email) ?>">
                                            <button type="submit" class="ad-btn ad-btn-approve"><i class="fa-solid fa-check"></i> Approve</button>
                                        </form>
                                        <form method="post">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="email" value="<?= e($email) ?>">
                                            <button type="submit" class="ad-btn ad-btn-reject"><i class="fa-solid fa-xmark"></i> Reject</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <!-- ============= Approved sellers ============= -->
    <section class="ad-section">
        <header>
            <h2><i class="fa-solid fa-store"></i> Approved sellers</h2>
            <span class="ad-count"><?= count($approvedSellers) ?> active</span>
        </header>

        <?php if (empty($approvedSellers)): ?>
            <div class="ad-empty"><i class="fa-solid fa-store-slash"></i> No approved sellers yet.</div>
        <?php else: ?>
            <div class="ad-scroll">
                <table class="ad-table">
                    <thead>
                        <tr><th>User</th><th>Status</th><th>Registered</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($approvedSellers as $email => $u): ?>
                            <tr>
                                <td><?php user_cell($u); ?></td>
                                <td><span class="ad-status ad-status-approved"><i class="fa-solid fa-circle-check"></i> Approved</span></td>
                                <td><?= e($u['created']) ?></td>
                                <td>
                                    <form method="post" onsubmit="return confirm('Revoke this seller\'s access?');">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="email" value="<?= e($email) ?>">
                                        <button type="submit" class="ad-btn ad-btn-reject"><i class="fa-solid fa-ban"></i> Revoke</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <!-- ============= Customers ============= -->
    <section class="ad-section">
        <header>
            <h2><i class="fa-solid fa-user"></i> Customers</h2>
            <span class="ad-count"><?= count($customers) ?> registered</span>
        </header>

        <?php if (empty($customers)): ?>
            <div class="ad-empty"><i class="fa-solid fa-user-slash"></i> No customers yet.</div>
        <?php else: ?>
            <div class="ad-scroll">
                <table class="ad-table">
                    <thead>
                        <tr><th>User</th><th>Role</th><th>Status</th><th>Registered</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $email => $u): ?>
                            <tr>
                                <td><?php user_cell($u); ?></td>
                                <td><span class="ad-pill">Customer</span></td>
                                <td><span class="ad-status ad-status-approved"><i class="fa-solid fa-circle-check"></i> Active</span></td>
                                <td><?= e($u['created']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <!-- ============= Admins ============= -->
    <section class="ad-section">
        <header>
            <h2><i class="fa-solid fa-shield-halved"></i> Administrators</h2>
            <span class="ad-count"><?= count($admins) ?> admin<?= count($admins) === 1 ? '' : 's' ?></span>
        </header>

        <div class="ad-scroll">
            <table class="ad-table">
                <thead>
                    <tr><th>User</th><th>Role</th><th>Registered</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $email => $u): ?>
                        <tr>
                            <td><?php user_cell($u); ?></td>
                            <td><span class="ad-pill ad-pill-admin">Admin</span></td>
                            <td><?= e($u['created']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<footer class="fr-footer">
    <div class="fr-wrap fr-footer-inner">
        <a href="/index.php" class="fr-logo"><span class="fr-logo-mark">F</span>Franklin</a>
        <nav aria-label="Footer">
            <a href="/products.php">Products</a>
            <a href="/orders.php">Orders</a>
            <a href="/contact.php">Contact</a>
        </nav>
        <p>&copy; <?= date('Y') ?> Franklin</p>
    </div>
</footer>

</body>
</html>