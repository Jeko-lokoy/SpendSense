<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$currentUser = require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}

$expenseId = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($expenseId <= 0) {
    flash('error', 'Invalid expense selected.');
    redirect('dashboard.php');
}

$stmt = db()->prepare('DELETE FROM expenses WHERE id = :id AND user_id = :user_id');
$stmt->execute([
    'id' => $expenseId,
    'user_id' => $currentUser['id'],
]);

flash('success', 'Expense deleted successfully.');
redirect('dashboard.php');
