<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

const EXPENSE_CATEGORIES = [
    'Food',
    'Transportation',
    'School',
    'Bills',
    'Shopping',
    'Savings',
    'Others',
];

function selected_month(): int
{
    $month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('n');
    return max(1, min(12, $month));
}

function selected_year(): int
{
    $year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
    return max(2000, min(2100, $year));
}

function month_options(): array
{
    $months = [];

    for ($month = 1; $month <= 12; $month++) {
        $months[$month] = date('F', mktime(0, 0, 0, $month, 1));
    }

    return $months;
}

function year_options(int $span = 5): array
{
    $currentYear = (int) date('Y');
    $years = [];

    for ($year = $currentYear - $span; $year <= $currentYear + 1; $year++) {
        $years[] = $year;
    }

    return $years;
}

function build_expense_filters(array $input): array
{
    $conditions = ['user_id = :user_id'];
    $params = ['user_id' => $input['user_id']];

    if (!empty($input['search'])) {
        $searchValue = '%' . trim($input['search']) . '%';
        $conditions[] = '(title LIKE :search_title OR category LIKE :search_category OR note LIKE :search_note)';
        $params['search_title'] = $searchValue;
        $params['search_category'] = $searchValue;
        $params['search_note'] = $searchValue;
    }

    if (!empty($input['category']) && in_array($input['category'], EXPENSE_CATEGORIES, true)) {
        $conditions[] = 'category = :category';
        $params['category'] = $input['category'];
    }

    if (!empty($input['date'])) {
        $conditions[] = 'expense_date = :expense_date';
        $params['expense_date'] = $input['date'];
    }

    if (!empty($input['month']) && ctype_digit((string) $input['month'])) {
        $month = (int) $input['month'];
        if ($month >= 1 && $month <= 12) {
            $conditions[] = 'MONTH(expense_date) = :month';
            $params['month'] = $month;
        }
    }

    if (!empty($input['year']) && ctype_digit((string) $input['year'])) {
        $conditions[] = 'YEAR(expense_date) = :year';
        $params['year'] = (int) $input['year'];
    }

    return [
        'sql' => implode(' AND ', $conditions),
        'params' => $params,
    ];
}

function get_budget(int $userId, int $month, int $year): ?array
{
    $stmt = db()->prepare(
        'SELECT id, budget_limit, month, year
         FROM budgets
         WHERE user_id = :user_id AND month = :month AND year = :year
         LIMIT 1'
    );
    $stmt->execute([
        'user_id' => $userId,
        'month' => $month,
        'year' => $year,
    ]);

    return $stmt->fetch() ?: null;
}

function save_budget(int $userId, int $month, int $year, float $limit): void
{
    $existing = get_budget($userId, $month, $year);

    if ($existing) {
        $stmt = db()->prepare(
            'UPDATE budgets
             SET budget_limit = :budget_limit, updated_at = NOW()
             WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([
            'budget_limit' => $limit,
            'id' => $existing['id'],
            'user_id' => $userId,
        ]);
        return;
    }

    $stmt = db()->prepare(
        'INSERT INTO budgets (user_id, month, year, budget_limit, created_at, updated_at)
         VALUES (:user_id, :month, :year, :budget_limit, NOW(), NOW())'
    );
    $stmt->execute([
        'user_id' => $userId,
        'month' => $month,
        'year' => $year,
        'budget_limit' => $limit,
    ]);
}

function dashboard_stats(int $userId, int $month, int $year): array
{
    $totalStmt = db()->prepare('SELECT COALESCE(SUM(amount), 0) AS total FROM expenses WHERE user_id = :user_id');
    $totalStmt->execute(['user_id' => $userId]);
    $totalExpenses = (float) $totalStmt->fetch()['total'];

    $monthStmt = db()->prepare(
        'SELECT COALESCE(SUM(amount), 0) AS total
         FROM expenses
         WHERE user_id = :user_id AND MONTH(expense_date) = :month AND YEAR(expense_date) = :year'
    );
    $monthStmt->execute([
        'user_id' => $userId,
        'month' => $month,
        'year' => $year,
    ]);
    $monthlyTotal = (float) $monthStmt->fetch()['total'];

    $categoryStmt = db()->prepare(
        'SELECT category, COALESCE(SUM(amount), 0) AS total
         FROM expenses
         WHERE user_id = :user_id AND MONTH(expense_date) = :month AND YEAR(expense_date) = :year
         GROUP BY category
         ORDER BY total DESC, category ASC'
    );
    $categoryStmt->execute([
        'user_id' => $userId,
        'month' => $month,
        'year' => $year,
    ]);
    $categorySummary = $categoryStmt->fetchAll();

    $recentStmt = db()->prepare(
        'SELECT id, title, amount, category, expense_date, note
         FROM expenses
         WHERE user_id = :user_id
         ORDER BY expense_date DESC, id DESC
         LIMIT 5'
    );
    $recentStmt->execute(['user_id' => $userId]);
    $recentExpenses = $recentStmt->fetchAll();

    return [
        'total_expenses' => $totalExpenses,
        'monthly_total' => $monthlyTotal,
        'category_summary' => $categorySummary,
        'recent_expenses' => $recentExpenses,
    ];
}

function format_currency(float $amount): string
{
    return 'PHP ' . number_format($amount, 2);
}

function validate_expense(array $data): array
{
    $errors = [];

    if (trim($data['title'] ?? '') === '') {
        $errors[] = 'Title is required.';
    }

    if (!isset($data['amount']) || !is_numeric($data['amount']) || (float) $data['amount'] <= 0) {
        $errors[] = 'Amount must be greater than 0.';
    }

    if (empty($data['category']) || !in_array($data['category'], EXPENSE_CATEGORIES, true)) {
        $errors[] = 'Please select a valid category.';
    }

    if (empty($data['expense_date']) || !strtotime((string) $data['expense_date'])) {
        $errors[] = 'Please provide a valid expense date.';
    }

    return $errors;
}
