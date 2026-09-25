<?php
require_once __DIR__ . '/auth.php';

/* If already logged in, redirect appropriately */
if (is_logged_in()) {
    $u = current_user();
    header('Location: ' . ($u['role'] === 'admin' ? '/admin/index.php' : 'index.php'));
    exit;
}

$errors = [];
$oldEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $oldEmail = $email;

    if ($email === '')    $errors[] = 'Please enter your email.';
    if ($password === '') $errors[] = 'Please enter your password.';

    if (empty($errors)) {
        [$ok, $err] = attempt_login($email, $password);

        if ($ok) {
            $u = current_user();
            flash_set('success', 'Welcome back, ' . $u['name'] . '!');

         if ($u['role'] === 'admin') {
    header('Location: /admin/index.php');
} elseif ($u['role'] === 'seller') {
    header('Location: /seller/index.php');
} else {
    header('Location: /index.php');
}
        } else {
            $errors[] = $err;
        }
    }
}

$flashes = flash_get();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | E-SHOPPING</title>
    <link rel="stylesheet" href="auth-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.css">
</head>
<body>
    <div class="auth-shell">
        <!-- LEFT: Visual panel -->
        <aside class="auth-visual">
            <a href="index.php" class="brand">E-SHOPPING</a>

            <div class="visual-content">
                <h2>Welcome back to your favorite shop.</h2>
                <p>Sign in to track orders, manage your listings, or approve sellers — all in one place.</p>

                <ul class="visual-features">
                    <li><i class="fa-solid fa-shield-halved"></i> Secure session-based login</li>
                    <li><i class="fa-solid fa-truck-fast"></i> Track your orders in real time</li>
                    <li><i class="fa-solid fa-store"></i> Sell on E-SHOPPING in minutes</li>
                </ul>
            </div>

            <span style="font-size:1.25rem; opacity:.8; position:relative; z-index:1;">
                © <?= date('Y') ?> E-SHOPPING
            </span>
        </aside>

        <!-- RIGHT: Form -->
        <section class="auth-form-panel">
            <h1>Sign in</h1>
            <p class="subtitle">Enter your credentials to continue.</p>

            <?php foreach ($flashes as $f): ?>
                <div class="alert alert-<?= e($f['type']) ?>">
                    <i class="fa-solid fa-circle-info"></i>
                    <span><?= e($f['message']) ?></span>
                </div>
            <?php endforeach; ?>

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

            <form method="post" class="auth-form" novalidate>
                <div class="field">
                    <label for="email">Email address</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-envelope leading"></i>
                        <input type="email" id="email" name="email"
                               placeholder="you@example.com"
                               value="<?= e($oldEmail) ?>" required autofocus>
                    </div>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-lock leading"></i>
                        <input type="password" id="password" name="password"
                               placeholder="Your password" required>
                        <button type="button" class="toggle-pw" data-target="password" aria-label="Show password">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-auth">
                    <i class="fa-solid fa-right-to-bracket"></i> Sign in
                </button>
            </form>

            <div class="auth-divider">New here?</div>
            <p class="auth-alt">
                Don't have an account? <a href="register.php">Create one</a>
            </p>

            <div class="demo-box">
                <strong>Demo accounts</strong><br>
                Admin — <code>admin@eshopping.test</code> / <code>admin123</code><br>
                Customer — <code>customer@eshopping.test</code> / <code>customer123</code><br>
                Seller — <code>seller@eshopping.test</code> / <code>seller123</code>
            </div>
        </section>
    </div>

    <script>
        // Show / hide password
        document.querySelectorAll('.toggle-pw').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = document.getElementById(btn.dataset.target);
                const icon  = btn.querySelector('i');
                const show  = input.type === 'password';
                input.type  = show ? 'text' : 'password';
                icon.classList.toggle('fa-eye', !show);
                icon.classList.toggle('fa-eye-slash', show);
            });
        });
    </script>
</body>
</html>