<?php
require_once __DIR__ . '/../auth.php';
require_seller();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$me = current_user();
$id = (int) ($_POST['id'] ?? 0);
$p  = get_product($id);

if (!$p || $p['seller_email'] !== $me['email']) {
    flash_set('error', 'Product not found.');
    header('Location: index.php');
    exit;
}

delete_product($id);
flash_set('success', 'Product deleted.');

header('Location: index.php');
exit;