<?php
/* =========================================================
   Shared auth helpers. Include at top of every page.
   ========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/users-data.php';

/* =========================================================
   Escape helper
   ========================================================= */
function e($s) {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/* =========================================================
   Price formatting
   ========================================================= */
function format_price($n) {
    return '$' . number_format((float) $n, 2);
}

/* =========================================================
   Star rendering — used by products.php, product.php, reviews
   ========================================================= */
function render_stars($rating) {
    $rating = (float) $rating;
    $full   = (int) floor($rating);
    $half   = ($rating - $full) >= 0.5;
    $empty  = 5 - $full - ($half ? 1 : 0);

    $html = '';
    for ($i = 0; $i < $full;  $i++) $html .= '<i class="fa-solid fa-star"></i>';
    if ($half)                      $html .= '<i class="fa-solid fa-star-half-stroke"></i>';
    for ($i = 0; $i < $empty; $i++) $html .= '<i class="fa-regular fa-star"></i>';
    return $html;
}

/* =========================================================
   User lookup / creation / login
   ========================================================= */
function find_user($email) {
    $email = strtolower(trim($email));
    return $_SESSION['users'][$email] ?? null;
}

function create_user($name, $email, $password, $role) {
    $email = strtolower(trim($email));

    if (isset($_SESSION['users'][$email])) {
        return [false, 'An account with that email already exists.'];
    }

    $nextId = 1;
    foreach ($_SESSION['users'] as $u) {
        if ($u['id'] >= $nextId) $nextId = $u['id'] + 1;
    }

    $status = ($role === 'seller') ? 'pending' : 'approved';

    $_SESSION['users'][$email] = [
        'id'       => $nextId,
        'name'     => $name,
        'email'    => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role'     => $role,
        'status'   => $status,
        'created'  => date('Y-m-d H:i'),
    ];

    return [true, null];
}

function attempt_login($email, $password) {
    $user = find_user($email);

    if (!$user) {
        return [false, 'No account found with that email.'];
    }
    if (!password_verify($password, $user['password'])) {
        return [false, 'Incorrect password.'];
    }
    if ($user['role'] === 'seller' && $user['status'] !== 'approved') {
        return [false, 'Your seller account is still awaiting admin approval.'];
    }

    $_SESSION['user'] = [
        'id'    => $user['id'],
        'name'  => $user['name'],
        'email' => $user['email'],
        'role'  => $user['role'],
    ];

    return [true, null];
}

function logout() {
    unset($_SESSION['user']);
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function is_logged_in() {
    return current_user() !== null;
}

function is_admin() {
    $u = current_user();
    return $u && ($u['role'] ?? '') === 'admin';
}

function is_seller() {
    $u = current_user();
    return $u && ($u['role'] ?? '') === 'seller';
}

/* =========================================================
   Access guards
   ========================================================= */
function require_login($redirect = '/login.php') {
    if (!is_logged_in()) {
        header('Location: ' . $redirect);
        exit;
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        exit('Forbidden — admins only.');
    }
}

function require_seller() {
    require_login();

    $u = current_user();
    if (!$u || ($u['role'] ?? '') !== 'seller') {
        http_response_code(403);
        exit('Forbidden — sellers only.');
    }

    $full = find_user($u['email']);
    if (!$full || $full['status'] !== 'approved') {
        flash_set('error', 'Your seller account is not approved yet.');
        header('Location: /login.php');
        exit;
    }
}

/* =========================================================
   Flash messages
   ========================================================= */
function flash_set($type, $message) {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_get() {
    $msgs = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $msgs;
}

/* =========================================================
   Seller product storage — case-insensitive matching
   ========================================================= */
function get_seller_products($sellerEmail) {
    if (!isset($_SESSION['products'])) {
        $_SESSION['products'] = [];
    }

    $needle = strtolower(trim($sellerEmail));
    $list   = [];

    foreach ($_SESSION['products'] as $id => $p) {
        if (strtolower(trim($p['seller_email'] ?? '')) === $needle) {
            $list[$id] = $p;
        }
    }
    return $list;
}

function get_product($id) {
    return $_SESSION['products'][$id] ?? null;
}

function save_product($product) {
    if (!isset($_SESSION['products'])) {
        $_SESSION['products'] = [];
    }
    $_SESSION['products'][$product['id']] = $product;
}

function delete_product($id) {
    unset($_SESSION['products'][$id]);
}

function next_product_id() {
    if (empty($_SESSION['products'])) {
        return 1001;
    }
    return max(array_keys($_SESSION['products'])) + 1;
}

/* =========================================================
   Merge demo catalog + seller uploads
   ========================================================= */
function get_all_products($demoProducts) {
    $all = [];

    foreach ($demoProducts as $p) {
        $p['seller_email'] = $p['seller_email'] ?? 'admin@eshopping.test';
        $p['seller_name']  = $p['seller_name']  ?? 'G-SHOPPING';
        $p['location']     = $p['location']     ?? 'Warehouse';
        $p['stock']        = $p['stock']        ?? 999;
        $p['source']       = 'demo';

        if (empty($p['image_url'])) {
            $p['image_url'] = $p['image'] ?? '';
        }

        $all[(int) $p['id']] = $p;
    }

    if (!empty($_SESSION['products'])) {
        foreach ($_SESSION['products'] as $id => $p) {
            $id = (int) $id;
            while (isset($all[$id])) { $id++; }

            $p['id']     = $id;
            $p['source'] = 'seller';

            $p['image']    = $p['image'] ?? '';
            $p['stock']    = $p['stock'] ?? 0;
            $p['location'] = $p['location'] ?? '';

            if ($p['image'] !== '' && !preg_match('#^https?://#', $p['image'])) {
                $p['image_url'] = '/seller/uploads/' . $p['image'];
            } else {
                $p['image_url'] = $p['image'];
            }

            $all[$id] = $p;
        }
    }

    return $all;
}

function normalize_products(&$products) {
    foreach ($products as &$p) {
        $p['image_url'] = $p['image_url']
            ?? (preg_match('#^https?://#', $p['image'] ?? '')
                ? $p['image']
                : '/seller/uploads/' . ($p['image'] ?? ''));

        $p['rating']   = isset($p['rating'])   ? (float) $p['rating'] : 5.0;
        $p['oldPrice'] = $p['oldPrice'] ?? null;

        if (array_key_exists('stock', $p)) {
            $p['inStock'] = ((int) $p['stock']) > 0;
        } else {
            $p['inStock'] = $p['inStock'] ?? true;
        }
    }
    unset($p);
}

/* =========================================================
   Order status helpers
   ========================================================= */
function order_status_label($status) {
    $map = [
        'pending'   => 'Pending',
        'preparing' => 'Preparing',
        'shipped'   => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
    ];
    return $map[$status] ?? ucfirst((string) $status);
}

function order_status_class($status) {
    $map = [
        'pending'   => 'status-pending',
        'preparing' => 'status-preparing',
        'shipped'   => 'status-shipped',
        'delivered' => 'status-delivered',
        'cancelled' => 'status-cancelled',
    ];
    return $map[$status] ?? 'status-pending';
}

function get_seller_orders($sellerEmail) {
    $orders = $_SESSION['orders'] ?? [];
    $result = [];
    $needle = strtolower(trim($sellerEmail));

    foreach ($orders as $id => $order) {
        $myItems    = [];
        $mySubtotal = 0;

        foreach (($order['items'] ?? []) as $row) {
            $rowSeller = strtolower(trim($row['product']['seller_email'] ?? ''));
            if ($rowSeller === $needle) {
                $myItems[]   = $row;
                $mySubtotal += (float) ($row['line'] ?? 0);
            }
        }

        if (!empty($myItems)) {
            $result[$id] = [
                'order'       => $order,
                'my_items'    => $myItems,
                'my_subtotal' => $mySubtotal,
            ];
        }
    }

    return $result;
}

function set_order_status($orderId, $status) {
    $allowed = ['pending', 'preparing', 'shipped', 'delivered', 'cancelled'];
    if (!in_array($status, $allowed, true)) return false;
    if (!isset($_SESSION['orders'][$orderId])) return false;

    $_SESSION['orders'][$orderId]['status']         = $status;
    $_SESSION['orders'][$orderId]['status_updated'] = date('Y-m-d H:i');
    return true;
}