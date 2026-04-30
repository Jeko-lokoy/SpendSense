<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? 'SpendSense';
$currentUser = $currentUser ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escape($pageTitle) ?> | SpendSense</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="page-shell">
    <header class="topbar">
        <a class="brand" href="<?= $currentUser ? 'dashboard.php' : 'index.php' ?>">SpendSense</a>
        <nav class="nav">
            <?php if ($currentUser): ?>
                <span class="nav-user">Hi, <?= escape($currentUser['name']) ?></span>
                <a href="dashboard.php">Dashboard</a>
                <a href="expense_form.php">Add Expense</a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a class="button-link" href="register.php">Register</a>
            <?php endif; ?>
        </nav>
    </header>
    <main class="container">
