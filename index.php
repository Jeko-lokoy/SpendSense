<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

if (current_user() !== null) {
    redirect('dashboard.php');
}

$pageTitle = 'Welcome';
require_once __DIR__ . '/partials/header.php';
?>
<section class="hero">
    <div class="hero-copy">
        <span class="eyebrow">Personal Expense Tracker</span>
        <h1>Track spending, stay on budget, and keep every account private.</h1>
        <p>SpendSense helps each user manage expenses, monitor monthly budgets, search records, and understand category spending from a simple dashboard.</p>
        <div class="hero-actions">
            <a class="button-primary" href="register.php">Create an Account</a>
            <a class="button-secondary" href="login.php">Log In</a>
        </div>
    </div>
    <div class="hero-card">
        <h2>Included Features</h2>
        <ul class="feature-list">
            <li>Secure registration and login</li>
            <li>Personal dashboard summaries</li>
            <li>Expense add, edit, view, and delete</li>
            <li>Monthly budget tracking and warnings</li>
            <li>Search and filter tools</li>
            <li>Category spending summary</li>
        </ul>
    </div>
</section>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
