<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

require_guest();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    store_old(['email' => $email]);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id, name, email, password FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = 'Invalid email or password.';
        } else {
            $_SESSION['user_id'] = (int) $user['id'];
            clear_old();
            flash('success', 'Welcome back, ' . $user['name'] . '!');
            redirect('dashboard.php');
        }
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/partials/header.php';
?>
<section class="auth-card">
    <h1>Login</h1>
    <p class="muted">Access your personal dashboard.</p>

    <?php if ($message = flash('success')): ?>
        <div class="alert success"><p><?= escape($message) ?></p></div>
    <?php endif; ?>

    <?php if ($message = flash('error')): ?>
        <div class="alert error"><p><?= escape($message) ?></p></div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert error">
            <?php foreach ($errors as $error): ?>
                <p><?= escape($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" class="form-grid">
        <label>
            <span>Email</span>
            <input type="email" name="email" value="<?= escape(old('email')) ?>" required>
        </label>

        <label>
            <span>Password</span>
            <input type="password" name="password" required>
        </label>

        <button type="submit" class="button-primary full-width">Login</button>
    </form>
</section>
<?php clear_old(); require_once __DIR__ . '/partials/footer.php'; ?>
