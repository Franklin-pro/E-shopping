<?php
require_once __DIR__ . '/auth.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$errors = [];
$old = ['name' => '', 'email' => '', 'role' => 'customer'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';
    $role     = $_POST['role'] ?? 'customer';

    $old = ['name' => $name, 'email' => $email, 'role' => $role];

    if ($name === '')                         $errors[] = 'Please enter your name.';
    if (strlen($name) > 80)                   $errors[] = 'Name is too long.';

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (strlen($password) < 6)                $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm)               $errors[] = 'Passwords do not match.';

    if (!in_array($role, ['customer', 'seller'], true)) {
        $errors[] = 'Please choose an account type.';
    }

    if (empty($errors)) {
        [$ok, $err] = create_user($name, $email, $password, $role);

        if (!$ok) {
            $errors[] = $err;
        } else {
            if ($role === 'seller') {
                flash_set('info', 'Account created! A seller account must be approved by an admin before you can log in.');
                header('Location: login.php');
            } else {
                flash_set('success', 'Account created — you can sign in now.');
                header('Location: login.php');
            }
            exit;
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
    <title>Create account | G-SHOPPING</title>
    <link rel="stylesheet" href="auth-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.css">
</head>
<body>
    <div class="auth-shell">
        <!-- LEFT: Visual -->
        <aside class="auth-visual">
            <a href="index.php" class="brand">G-SHOPPING</a>

            <div class="visual-content">
                <h2>Join thousands of happy shoppers and sellers.</h2>
                <p>Create your free account in seconds and unlock everything G-SHOPPING has to offer.</p>

                <ul class="visual-features">
                    <li><i class="fa-solid fa-user"></i> Shop as a customer instantly</li>
                    <li><i class="fa-solid fa-store"></i> Apply as a seller in one click</li>
                    <li><i class="fa-solid fa-circle-check"></i> Verified sellers get the badge</li>
                </ul>
            </div>

            <span style="font-size:1.25rem; opacity:.8; position:relative; z-index:1;">
                © <?= date('Y') ?> G-SHOPPING
            </span>
        </aside>

        <!-- RIGHT: Form -->
        <section class="auth-form-panel">
            <h1>Create account</h1>
            <p class="subtitle">It only takes a minute.</p>

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
                    <label for="name">Full name</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-user leading"></i>
                        <input type="text" id="name" name="name"
                               placeholder="Jane Doe"
                               value="<?= e($old['name']) ?>" required autofocus>
                    </div>
                </div>

                <div class="field">
                    <label for="email">Email address</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-envelope leading"></i>
                        <input type="email" id="email" name="email"
                               placeholder="you@example.com"
                               value="<?= e($old['email']) ?>" required>
                    </div>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-lock leading"></i>
                        <input type="password" id="password" name="password"
                               placeholder="At least 6 characters" required>
                        <button type="button" class="toggle-pw" data-target="password" aria-label="Show password">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="field">
                    <label for="confirm">Confirm password</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-lock leading"></i>
                        <input type="password" id="confirm" name="confirm"
                               placeholder="Repeat password" required>
                        <button type="button" class="toggle-pw" data-target="confirm" aria-label="Show password">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Role picker -->
                <div class="field">
                    <label>Account type</label>
                    <div class="role-picker">
                        <label class="role-option">
                            <input type="radio" name="role" value="customer"
                                   <?= $old['role'] === 'customer' ? 'checked' : '' ?>>
                            <span class="radio-dot"></span>
                            <span class="role-text">
                                <strong>Customer</strong>
                                <small>Buy products. Instant access.</small>
                            </span>
                        </label>
                        <label class="role-option">
                            <input type="radio" name="role" value="seller"
                                   <?= $old['role'] === 'seller' ? 'checked' : '' ?>>
                            <span class="radio-dot"></span>
                            <span class="role-text">
                                <strong>Seller</strong>
                                <small>Sell products. Admin approval required.</small>
                            </span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-auth">
                    <i class="fa-solid fa-user-plus"></i> Create account
                </button>
            </form>

            <div class="auth-divider">Already registered?</div>
            <p class="auth-alt">
                Have an account? <a href="login.php">Sign in</a>
            </p>
        </section>
    </div>

    <script>
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