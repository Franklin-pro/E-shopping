<?php
require_once __DIR__ . '/../auth.php';
require_seller();

$me = current_user();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$p  = get_product($id);

/* Security: only the owner can edit */
if (!$p || $p['seller_email'] !== $me['email']) {
    flash_set('error', 'Product not found.');
    header('Location: index.php');
    exit;
}

$errors = [];
$old = [
    'name'        => $p['name'],
    'description' => $p['description'],
    'price'       => (string) $p['price'],
    'stock'       => (string) $p['stock'],
    'category'    => $p['category'],
    'location'    => $p['location'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'name'        => trim($_POST['name'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'price'       => trim($_POST['price'] ?? ''),
        'stock'       => trim($_POST['stock'] ?? '1'),
        'category'    => trim($_POST['category'] ?? ''),
        'location'    => trim($_POST['location'] ?? ''),
    ];

    if ($old['name'] === '')                                     $errors[] = 'Product name is required.';
    if ($old['description'] === '')                              $errors[] = 'Description is required.';
    if (!is_numeric($old['price']) || (float) $old['price'] <= 0) $errors[] = 'Price must be a positive number.';
    if (!ctype_digit($old['stock']) || (int) $old['stock'] < 0)   $errors[] = 'Stock must be a whole number.';
    if ($old['category'] === '')                                 $errors[] = 'Category is required.';
    if ($old['location'] === '')                                 $errors[] = 'Location is required.';

    // Optional new image
    $imageName = $p['image'];
    if (!empty($_FILES['image']['name'])) {
        $up = handle_upload('image', ['jpg', 'jpeg', 'png', 'gif', 'webp'], 5 * 1024 * 1024);
        if ($up[0]) { $imageName = $up[1]; }
        else        { $errors[] = 'Image: ' . $up[1]; }
    }

    // Optional new document
    $documentName = $p['document'] ?? null;
    if (!empty($_FILES['document']['name'])) {
        $up = handle_upload('document', ['pdf', 'doc', 'docx', 'txt'], 10 * 1024 * 1024);
        if ($up[0]) { $documentName = $up[1]; }
        else        { $errors[] = 'Document: ' . $up[1]; }
    }

    if (empty($errors)) {
        $p['name']        = $old['name'];
        $p['description'] = $old['description'];
        $p['price']       = (float) $old['price'];
        $p['stock']       = (int) $old['stock'];
        $p['category']    = $old['category'];
        $p['location']    = $old['location'];
        $p['image']       = $imageName;
        $p['document']    = $documentName;
        $p['updated']     = date('Y-m-d H:i');

        save_product($p);

        flash_set('success', 'Product updated.');
        header('Location: index.php');
        exit;
    }
}

function handle_upload($field, $allowedExts, $maxBytes) {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return [false, 'Upload failed.'];
    }
    $file = $_FILES[$field];
    if ($file['size'] > $maxBytes) {
        return [false, 'File too large (max ' . round($maxBytes / 1048576, 1) . ' MB).'];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts, true)) {
        return [false, 'Allowed: ' . implode(', ', $allowedExts)];
    }
    $uploadDir = __DIR__ . '/uploads';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $safeName = bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $safeName)) {
        return [false, 'Could not save file.'];
    }
    return [true, $safeName];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit product | G-SHOPPING</title>
    <link rel="stylesheet" href="seller-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-inner">
            <a href="/index.php" class="logo">G-SHOPPING</a>
            <ul class="nav-links">
                <li><a href="/index.php">Home</a></li>
                <li><a href="/products.php">Products</a></li>
                <li><a href="/seller/index.php" class="active">My shop</a></li>
            </ul>
            <div class="nav-btn">
                <button onclick="location.href='/logout.php'">Sign out</button>
            </div>
        </div>
    </nav>

    <main class="seller-page">
        <a href="index.php" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Back to my shop
        </a>

        <header class="seller-header">
            <h1>Edit product</h1>
            <p>Update details, replace the photo, or add a document.</p>
        </header>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <section class="seller-section">
            <div class="form-wrap">
                <form method="post" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">

                    <div class="form-grid">
                        <div class="field full">
                            <label>Product name <span class="req">*</span></label>
                            <input type="text" name="name" value="<?= e($old['name']) ?>" required>
                        </div>

                        <div class="field full">
                            <label>Description <span class="req">*</span></label>
                            <textarea name="description" required><?= e($old['description']) ?></textarea>
                        </div>

                        <div class="field">
                            <label>Price (USD) <span class="req">*</span></label>
                            <input type="number" name="price" step="0.01" min="0.01"
                                   value="<?= e($old['price']) ?>" required>
                        </div>

                        <div class="field">
                            <label>Stock <span class="req">*</span></label>
                            <input type="number" name="stock" min="0" step="1"
                                   value="<?= e($old['stock']) ?>" required>
                        </div>

                        <div class="field">
                            <label>Category <span class="req">*</span></label>
                            <select name="category" required>
                                <?php
                                    $cats = ['Electronics', 'Fashion', 'Home & Kitchen', 'Beauty', 'Sports', 'Books', 'Toys', 'Other'];
                                    foreach ($cats as $c):
                                ?>
                                    <option value="<?= e($c) ?>" <?= $old['category'] === $c ? 'selected' : '' ?>>
                                        <?= e($c) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field">
                            <label>Location <span class="req">*</span></label>
                            <input type="text" name="location" value="<?= e($old['location']) ?>" required>
                        </div>

                        <div class="field full">
                            <label>Replace photo (optional)</label>
                            <div class="file-box">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <div class="file-text">
                                    <strong>Click to upload a new image</strong>
                                    <small>Leave empty to keep current image</small>
                                </div>
                                <input type="file" name="image" accept="image/*">
                            </div>
                        </div>

                        <div class="field full">
                            <label>Replace document (optional)</label>
                            <div class="file-box">
                                <i class="fa-solid fa-file-arrow-up"></i>
                                <div class="file-text">
                                    <strong>Click to upload a new document</strong>
                                    <small>Leave empty to keep current file</small>
                                </div>
                                <input type="file" name="document" accept=".pdf,.doc,.docx,.txt">
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-check"></i> Save changes
                        </button>
                        <a href="index.php" class="btn btn-ghost">Cancel</a>
                    </div>
                </form>
            </div>
        </section>
    </main>
</body>
</html>