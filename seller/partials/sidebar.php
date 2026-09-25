<?php
$active = $active ?? 'overview';

if (!isset($pendingCount)) {
    $pendingCount = 0;
    if (function_exists('get_seller_orders') && isset($me)) {
        foreach (get_seller_orders($me['email']) as $row) {
            if (($row['order']['status'] ?? 'pending') === 'pending') {
                $pendingCount++;
            }
        }
    }
}
?>
<aside class="seller-sidebar">
    <div class="seller-sidebar-brand">
        <div class="avatar"><?= e(strtoupper(substr($me['name'], 0, 1))) ?></div>
        <div class="meta">
            <strong><?= e($me['name']) ?></strong>
            <small>Seller</small>
        </div>
    </div>

    <ul class="seller-sidebar-links">
        <li>
            <a href="/seller/index.php" class="<?= $active === 'overview' ? 'active' : '' ?>">
                <i class="fa-solid fa-gauge-high"></i>
                <span class="label">Overview</span>
            </a>
        </li>
        <li>
            <a href="/seller/orders.php" class="<?= $active === 'orders' ? 'active' : '' ?>">
                <i class="fa-solid fa-receipt"></i>
                <span class="label">Orders</span>
                <?php if ($pendingCount > 0): ?>
                    <span class="badge"><?= $pendingCount ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a href="/seller/productx.php" class="<?= $active === 'productx' ? 'active' : '' ?>">
                <i class="fa-solid fa-box-open"></i>
                <span class="label">Products</span>
            </a>
        </li>
        <li>
            <a href="/seller/add-product.php" class="<?= $active === 'add' ? 'active' : '' ?>">
                <i class="fa-solid fa-plus"></i>
                <span class="label">Add product</span>
            </a>
        </li>
    </ul>

    <div class="seller-sidebar-footer">
        <a href="/index.php">
            <i class="fa-solid fa-store"></i> View storefront
        </a>
        <a href="/logout.php">
            <i class="fa-solid fa-right-from-bracket"></i> Sign out
        </a>
    </div>
</aside>