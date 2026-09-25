<?php
require_once __DIR__ . '/../auth.php';
require_seller();

$me     = current_user();
$errors = [];
$old    = [
    'name'        => '',
    'description' => '',
    'price'       => '',
    'stock'       => '1',
    'category'    => '',
    'location'    => '',
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

    /* ---------- Validate text fields ---------- */
    if ($old['name'] === '')                                    $errors[] = 'Product name is required.';
    if ($old['description'] === '')                             $errors[] = 'Description is required.';
    if (!is_numeric($old['price']) || (float) $old['price'] <= 0) $errors[] = 'Price must be a positive number.';
    if (!ctype_digit($old['stock']) || (int) $old['stock'] < 0)  $errors[] = 'Stock must be a whole number.';
    if ($old['category'] === '')                                $errors[] = 'Category is required.';
    if ($old['location'] === '')                                $errors[] = 'Location is required.';

    /* ---------- Handle image upload ---------- */
    $imageName = null;
    if (!empty($_FILES['image']['name'])) {
        $up = handle_upload('image', ['jpg', 'jpeg', 'png', 'gif', 'webp'], 5 * 1024 * 1024);
        if ($up[0]) {
            $imageName = $up[1];
        } else {
            $errors[] = 'Image: ' . $up[1];
        }
    } else {
        $errors[] = 'A product image is required.';
    }

    /* ---------- Handle optional document upload ---------- */
    $documentName = null;
    if (!empty($_FILES['document']['name'])) {
        $up = handle_upload('document', ['pdf', 'doc', 'docx', 'txt'], 10 * 1024 * 1024);
        if ($up[0]) {
            $documentName = $up[1];
        } else {
            $errors[] = 'Document: ' . $up[1];
        }
    }

    /* ---------- Save ---------- */
    if (empty($errors)) {
        $id = next_product_id();

        save_product([
            'id'           => $id,
            'name'         => $old['name'],
            'description'  => $old['description'],
            'price'        => (float) $old['price'],
            'stock'        => (int) $old['stock'],
            'category'     => $old['category'],
            'location'     => $old['location'],
            'image'        => $imageName,
            'document'     => $documentName,
            'seller_email' => $me['email'],
            'seller_name'  => $me['name'],
            'created'      => date('Y-m-d H:i'),
        ]);

        flash_set('success', 'Product "' . $old['name'] . '" added successfully.');
        header('Location: index.php');
        exit;
    }
}

/* =========================================================
   Upload helper — validates and moves the file
   Returns [true, filename] or [false, errorMessage]
   ========================================================= */
function handle_upload($field, $allowedExts, $maxBytes) {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return [false, 'Upload failed.'];
    }

    $file = $_FILES[$field];
    if ($file['size'] > $maxBytes) {
        return [false, 'File is too large (max ' . round($maxBytes / 1048576, 1) . ' MB).'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts, true)) {
        return [false, 'Allowed types: ' . implode(', ', $allowedExts)];
    }

    $uploadDir = __DIR__ . '/uploads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Random safe filename — prevents overwrite and path tricks
    $safeName = bin2hex(random_bytes(8)) . '.' . $ext;
    $dest     = $uploadDir . '/' . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return [false, 'Could not save the file.'];
    }

    return [true, $safeName];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add product | E-SHOPPING</title>
    <link rel="stylesheet" href="seller-style.css">
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
            <h1>Add a new product</h1>
            <p>Upload a photo, set the price, and tell customers where you're based.</p>
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
                    <div class="form-grid">

                        <div class="field full">
                            <label for="name">Product name <span class="req">*</span></label>
                            <input type="text" id="name" name="name"
                                   placeholder="e.g. Handmade leather wallet"
                                   value="<?= e($old['name']) ?>" required>
                        </div>

                        <div class="field full">
                            <label for="description">Description <span class="req">*</span></label>
                            <textarea id="description" name="description"
                                      placeholder="Tell customers what makes this product special..."
                                      required><?= e($old['description']) ?></textarea>
                        </div>

                        <div class="field">
                            <label for="price">Price (USD) <span class="req">*</span></label>
                            <input type="number" id="price" name="price"
                                   step="0.01" min="0.01"
                                   placeholder="19.99"
                                   value="<?= e($old['price']) ?>" required>
                        </div>

                        <div class="field">
                            <label for="stock">Stock quantity <span class="req">*</span></label>
                            <input type="number" id="stock" name="stock"
                                   min="0" step="1"
                                   value="<?= e($old['stock']) ?>" required>
                        </div>

                        <div class="field">
                            <label for="category">Category <span class="req">*</span></label>
                            <select id="category" name="category" required>
                                <option value="">— Choose a category —</option>
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
                            <label for="location">Location <span class="req">*</span></label>
                            <input type="text" id="location" name="location"
                                   placeholder="e.g. Kigali, Rwanda"
                                   value="<?= e($old['location']) ?>" required>
                            <span class="hint">Where the product ships from.</span>
                        </div>

                        <div class="field full">
                            <label>Product photo <span class="req">*</span></label>
                            <div class="file-box">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <div class="file-text">
                                    <strong>Click to upload an image</strong>
                                    <small>JPG, PNG, GIF, or WebP · max 5 MB</small>
                                </div>
                                <input type="file" name="image" id="image" accept="image/*" required>
                            </div>
                            <div class="preview" id="preview">
                                <img id="previewImg" src="" alt="Preview">
                            </div>
                        </div>

                        <div class="field full">
                            <label>Supporting document (optional)</label>
                            <div class="file-box">
                                <i class="fa-solid fa-file-arrow-up"></i>
                                <div class="file-text">
                                    <strong>Click to upload a document</strong>
                                    <small>PDF, DOC, DOCX, or TXT · max 10 MB</small>
                                </div>
                                <input type="file" name="document" id="document"
                                       accept=".pdf,.doc,.docx,.txt">
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-check"></i> Save product
                        </button>
                        <a href="index.php" class="btn btn-ghost">Cancel</a>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <script>
        // Live image preview
        const imgInput   = document.getElementById('image');
        const preview    = document.getElementById('preview');
        const previewImg = document.getElementById('previewImg');

        imgInput.addEventListener('change', () => {
            const file = imgInput.files[0];
            if (!file) { preview.style.display = 'none'; return; }
            previewImg.src = URL.createObjectURL(file);
            preview.style.display = 'block';
        });
    </script>
</body>
</html>