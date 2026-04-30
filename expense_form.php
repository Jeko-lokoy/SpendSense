<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$currentUser = require_auth();
$expenseId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $expenseId > 0;
$errors = [];

$formData = [
    'title' => '',
    'amount' => '',
    'category' => '',
    'expense_date' => date('Y-m-d'),
    'note' => '',
];

if ($isEdit) {
    $stmt = db()->prepare(
        'SELECT id, title, amount, category, expense_date, note
         FROM expenses
         WHERE id = :id AND user_id = :user_id
         LIMIT 1'
    );
    $stmt->execute([
        'id' => $expenseId,
        'user_id' => $currentUser['id'],
    ]);
    $expense = $stmt->fetch();

    if (!$expense) {
        flash('error', 'Expense not found.');
        redirect('dashboard.php');
    }

    $formData = [
        'title' => $expense['title'],
        'amount' => (string) $expense['amount'],
        'category' => $expense['category'],
        'expense_date' => $expense['expense_date'],
        'note' => (string) $expense['note'],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'title' => trim($_POST['title'] ?? ''),
        'amount' => trim($_POST['amount'] ?? ''),
        'category' => trim($_POST['category'] ?? ''),
        'expense_date' => trim($_POST['expense_date'] ?? ''),
        'note' => trim($_POST['note'] ?? ''),
    ];

    $errors = validate_expense($formData);

    if (!$errors) {
        if ($isEdit) {
            $stmt = db()->prepare(
                'UPDATE expenses
                 SET title = :title,
                     amount = :amount,
                     category = :category,
                     expense_date = :expense_date,
                     note = :note,
                     updated_at = NOW()
                 WHERE id = :id AND user_id = :user_id'
            );
            $stmt->execute([
                'title' => $formData['title'],
                'amount' => (float) $formData['amount'],
                'category' => $formData['category'],
                'expense_date' => $formData['expense_date'],
                'note' => $formData['note'] !== '' ? $formData['note'] : null,
                'id' => $expenseId,
                'user_id' => $currentUser['id'],
            ]);
            flash('success', 'Expense updated successfully.');
        } else {
            $stmt = db()->prepare(
                'INSERT INTO expenses (user_id, title, amount, category, expense_date, note, created_at, updated_at)
                 VALUES (:user_id, :title, :amount, :category, :expense_date, :note, NOW(), NOW())'
            );
            $stmt->execute([
                'user_id' => $currentUser['id'],
                'title' => $formData['title'],
                'amount' => (float) $formData['amount'],
                'category' => $formData['category'],
                'expense_date' => $formData['expense_date'],
                'note' => $formData['note'] !== '' ? $formData['note'] : null,
            ]);
            flash('success', 'Expense added successfully.');
        }

        redirect('dashboard.php');
    }
}

$pageTitle = $isEdit ? 'Edit Expense' : 'Add Expense';
require_once __DIR__ . '/partials/header.php';
?>
<section class="auth-card wide-card">
    <h1><?= $isEdit ? 'Edit Expense' : 'Add Expense' ?></h1>
    <p class="muted">Fill in the details below to keep your tracker updated.</p>

    <?php if ($errors): ?>
        <div class="alert error">
            <?php foreach ($errors as $error): ?>
                <p><?= escape($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" class="form-grid">
        <label>
            <span>Title</span>
            <input type="text" name="title" value="<?= escape($formData['title']) ?>" required>
        </label>

        <label>
            <span>Amount</span>
            <input type="number" name="amount" step="0.01" min="0.01" value="<?= escape($formData['amount']) ?>" required>
        </label>

        <label>
            <span>Category</span>
            <select name="category" required>
                <option value="">Select Category</option>
                <?php foreach (EXPENSE_CATEGORIES as $category): ?>
                    <option value="<?= escape($category) ?>" <?= $formData['category'] === $category ? 'selected' : '' ?>><?= escape($category) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            <span>Date</span>
            <input type="date" name="expense_date" value="<?= escape($formData['expense_date']) ?>" required>
        </label>

        <label class="full-span">
            <span>Note (Optional)</span>
            <textarea name="note" rows="4" placeholder="Add extra details if needed"><?= escape($formData['note']) ?></textarea>
        </label>

        <div class="form-actions">
            <button type="submit" class="button-primary"><?= $isEdit ? 'Update Expense' : 'Save Expense' ?></button>
            <a class="button-secondary" href="dashboard.php">Cancel</a>
        </div>
    </form>
</section>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
