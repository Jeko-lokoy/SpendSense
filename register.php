<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

require_guest();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    store_old([
        'name' => $name,
        'email' => $email,
    ]);

    if ($name === '') {
        $errors[] = 'Name is required.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $checkStmt = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $checkStmt->execute(['email' => $email]);

        if ($checkStmt->fetch()) {
            $errors[] = 'That email is already registered.';
        } else {
            $stmt = db()->prepare(
                'INSERT INTO users (name, email, password, created_at)
                 VALUES (:name, :email, :password, NOW())'
            );
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
            ]);

            clear_old();
            flash('success', 'Registration successful. You can now log in.');
            redirect('login.php');
        }
    }
}

$pageTitle = 'Register';
require_once __DIR__ . '/partials/header.php';
?>
<section class="auth-card">
    <h1>Create Account</h1>
    <p class="muted">Start tracking your personal expenses.</p>

    <?php if ($errors): ?>
        <div class="alert error">
            <?php foreach ($errors as $error): ?>
                <p><?= escape($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" class="form-grid">
        <label>
            <span>Name</span>
            <input type="text" name="name" value="<?= escape(old('name')) ?>" required>
        </label>

        <label>
            <span>Email</span>
            <input type="email" name="email" value="<?= escape(old('email')) ?>" required>
        </label>

        <label>
            <span>Password</span>
            <input type="password" name="password" required>
        </label>

        <label>
            <span>Confirm Password</span>
            <input type="password" name="confirm_password" required>
        </label>

        <button type="submit" class="button-primary full-width">Register</button>
    </form>
</section>
<?php clear_old(); require_once __DIR__ . '/partials/footer.php'; ?>
