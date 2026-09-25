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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin dashboard | E-SHOPPING</title>

    <!-- main site CSS (one folder up) -->
    <link rel="stylesheet" href="../style.css">

    <!-- admin-specific CSS (same folder as this file) -->
    <link rel="stylesheet" href="admin-style.css">

    <!-- icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-inner">
            <a href="/index.php" class="logo">E-SHOPPING</a>
            <ul class="nav-links">
                <li><a href="/index.php">Home</a></li>
                <li><a href="/products.php">Products</a></li>
                <li><a href="/orders.php">Orders</a></li>
                <li><a href="/admin/index.php" class="active">Admin</a></li>
            </ul>
            <div class="nav-btn">
                <a href="/cart.php" class="cart" aria-label="Cart">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span class="cart-count">0</span>
                </a>
                <button onclick="location.href='/logout.php'">Sign out</button>
            </div>
        </div>
    </nav>

    <main class="admin-page">
        <div class="admin-topbar">
            <div class="who">
                <span class="user-avatar"><?= e(strtoupper(substr($me['name'], 0, 1))) ?></span>
                <span>
                    Signed in as <strong><?= e($me['name']) ?></strong>
                    <span class="pill pill-admin">Admin</span>
                </span>
            </div>
            <a href="/logout.php" class="btn-link">
                <i class="fa-solid fa-right-from-bracket"></i> Sign out
            </a>
        </div>

        <header class="admin-header">
            <h1>Admin dashboard</h1>
            <p>Manage users, review pending sellers, and keep the marketplace healthy.</p>
        </header>

        <?php foreach ($flashes as $f): ?>
            <div class="alert alert-<?= e($f['type']) ?>">
                <i class="fa-solid fa-circle-info"></i>
                <span><?= e($f['message']) ?></span>
            </div>
        <?php endforeach; ?>

        <!-- ============= Stats ============= -->
        <div class="admin-stats">
            <div class="admin-stat">
                <i class="fa-solid fa-users"></i>
                <span class="value"><?= count($allUsers) ?></span>
                <span class="label">Total users</span>
            </div>
            <div class="admin-stat">
                <i class="fa-solid fa-clock"></i>
                <span class="value"><?= count($pendingSellers) ?></span>
                <span class="label">Pending sellers</span>
            </div>
            <div class="admin-stat">
                <i class="fa-solid fa-store"></i>
                <span class="value"><?= count($approvedSellers) ?></span>
                <span class="label">Approved sellers</span>
            </div>
            <div class="admin-stat">
                <i class="fa-solid fa-user"></i>
                <span class="value"><?= count($customers) ?></span>
                <span class="label">Customers</span>
            </div>
        </div>

        <!-- ============= Pending sellers ============= -->
        <section class="admin-section">
            <header>
                <h2><i class="fa-solid fa-user-clock"></i> Pending seller requests</h2>
                <span class="count"><?= count($pendingSellers) ?> awaiting review</span>
            </header>

            <?php if (empty($pendingSellers)): ?>
                <div class="admin-empty">
                    <i class="fa-solid fa-circle-check"></i>
                    No pending seller requests — all caught up!
                </div>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingSellers as $email => $u): ?>
                            <tr>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-avatar"><?= e(strtoupper(substr($u['name'], 0, 1))) ?></div>
                                        <div>
                                            <div class="user-name"><?= e($u['name']) ?></div>
                                            <div class="user-email"><?= e($u['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="pill pill-seller">Seller</span></td>
                                <td>
                                    <span class="status-badge status-pending">
                                        <i class="fa-solid fa-clock"></i> Pending
                                    </span>
                                </td>
                                <td><?= e($u['created']) ?></td>
                                <td>
                                    <div class="actions">
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="email" value="<?= e($email) ?>">
                                            <button type="submit" class="btn-mini approve">
                                                <i class="fa-solid fa-check"></i> Approve
                                            </button>
                                        </form>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="email" value="<?= e($email) ?>">
                                            <button type="submit" class="btn-mini reject">
                                                <i class="fa-solid fa-xmark"></i> Reject
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <!-- ============= Approved sellers ============= -->
        <section class="admin-section">
            <header>
                <h2><i class="fa-solid fa-store"></i> Approved sellers</h2>
                <span class="count"><?= count($approvedSellers) ?> active</span>
            </header>

            <?php if (empty($approvedSellers)): ?>
                <div class="admin-empty">
                    <i class="fa-solid fa-store-slash"></i>
                    No approved sellers yet.
                </div>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($approvedSellers as $email => $u): ?>
                            <tr>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-avatar"><?= e(strtoupper(substr($u['name'], 0, 1))) ?></div>
                                        <div>
                                            <div class="user-name"><?= e($u['name']) ?></div>
                                            <div class="user-email"><?= e($u['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge status-approved">
                                        <i class="fa-solid fa-circle-check"></i> Approved
                                    </span>
                                </td>
                                <td><?= e($u['created']) ?></td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="email" value="<?= e($email) ?>">
                                        <button type="submit" class="btn-mini reject">
                                            <i class="fa-solid fa-ban"></i> Revoke
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <!-- ============= Customers ============= -->
        <section class="admin-section">
            <header>
                <h2><i class="fa-solid fa-user"></i> Customers</h2>
                <span class="count"><?= count($customers) ?> registered</span>
            </header>

            <?php if (empty($customers)): ?>
                <div class="admin-empty">
                    <i class="fa-solid fa-user-slash"></i>
                    No customers yet.
                </div>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $email => $u): ?>
                            <tr>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-avatar"><?= e(strtoupper(substr($u['name'], 0, 1))) ?></div>
                                        <div>
                                            <div class="user-name"><?= e($u['name']) ?></div>
                                            <div class="user-email"><?= e($u['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="pill pill-customer">Customer</span></td>
                                <td>
                                    <span class="status-badge status-approved">
                                        <i class="fa-solid fa-circle-check"></i> Active
                                    </span>
                                </td>
                                <td><?= e($u['created']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <!-- ============= Admins ============= -->
        <section class="admin-section">
            <header>
                <h2><i class="fa-solid fa-shield-halved"></i> Administrators</h2>
                <span class="count"><?= count($admins) ?> admin<?= count($admins) === 1 ? '' : 's' ?></span>
            </header>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Registered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $email => $u): ?>
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar"><?= e(strtoupper(substr($u['name'], 0, 1))) ?></div>
                                    <div>
                                        <div class="user-name"><?= e($u['name']) ?></div>
                                        <div class="user-email"><?= e($u['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="pill pill-admin">Admin</span></td>
                            <td><?= e($u['created']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>