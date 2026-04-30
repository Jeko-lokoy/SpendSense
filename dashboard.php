<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$currentUser = require_auth();
$month = selected_month();
$year = selected_year();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_budget'])) {
    $budgetLimit = $_POST['budget_limit'] ?? '';

    if (!is_numeric($budgetLimit) || (float) $budgetLimit < 0) {
        flash('error', 'Budget limit must be 0 or greater.');
    } else {
        save_budget((int) $currentUser['id'], $month, $year, (float) $budgetLimit);
        flash('success', 'Monthly budget saved.');
    }

    redirect('dashboard.php?month=' . $month . '&year=' . $year);
}

$stats = dashboard_stats((int) $currentUser['id'], $month, $year);
$budget = get_budget((int) $currentUser['id'], $month, $year);
$budgetLimit = $budget ? (float) $budget['budget_limit'] : 0.0;
$remainingBudget = $budgetLimit - $stats['monthly_total'];
$isOverBudget = $budgetLimit > 0 && $remainingBudget < 0;

$filters = [
    'search' => trim($_GET['search'] ?? ''),
    'category' => trim($_GET['category'] ?? ''),
    'date' => trim($_GET['date'] ?? ''),
    'month' => trim($_GET['filter_month'] ?? ''),
    'year' => trim($_GET['filter_year'] ?? ''),
    'user_id' => (int) $currentUser['id'],
];

$builtFilters = build_expense_filters($filters);
$expenseStmt = db()->prepare(
    'SELECT id, title, amount, category, expense_date, note, created_at
     FROM expenses
     WHERE ' . $builtFilters['sql'] . '
     ORDER BY expense_date DESC, id DESC'
);
$expenseStmt->execute($builtFilters['params']);
$expenses = $expenseStmt->fetchAll();
$expenseCount = count($expenses);
$categoryCount = count($stats['category_summary']);
$budgetUsagePercent = $budgetLimit > 0 ? min(100, ($stats['monthly_total'] / $budgetLimit) * 100) : 0;
$topCategory = $stats['category_summary'][0]['category'] ?? 'No category yet';
$selectedPeriod = month_options()[$month] . ' ' . $year;

$pageTitle = 'Dashboard';
require_once __DIR__ . '/partials/header.php';
?>
<?php if ($message = flash('success')): ?>
    <div class="alert success"><p><?= escape($message) ?></p></div>
<?php endif; ?>

<?php if ($message = flash('error')): ?>
    <div class="alert error"><p><?= escape($message) ?></p></div>
<?php endif; ?>

<section class="dashboard-hero">
    <article class="dashboard-lead">
        <div class="dashboard-head">
            <div>
                <span class="eyebrow">Financial Overview</span>
                <h1>Expense dashboard for <?= escape(month_options()[$month]) ?> <?= escape((string) $year) ?></h1>
                <p class="muted">Monitor spending, manage your monthly limit, and review your records from one place.</p>
            </div>
            <div class="hero-actions">
                <a class="button-primary" href="expense_form.php">Add Expense</a>
            </div>
        </div>

        <div class="stat-grid">
            <article class="stat-card">
                <span>Total Expenses</span>
                <strong><?= escape(format_currency($stats['total_expenses'])) ?></strong>
                <small>All-time spending across your account</small>
            </article>
            <article class="stat-card">
                <span>Monthly Total</span>
                <strong><?= escape(format_currency($stats['monthly_total'])) ?></strong>
                <small>For <?= escape($selectedPeriod) ?> with <?= $expenseCount ?> matching expense<?= $expenseCount === 1 ? '' : 's' ?></small>
            </article>
            <article class="stat-card">
                <span>Budget Status</span>
                <strong><?= $budgetLimit > 0 ? escape(format_currency($budgetLimit)) : 'Not Set' ?></strong>
                <small><?= $budgetLimit > 0 ? escape(number_format($budgetUsagePercent, 0)) . '% used for ' . escape($selectedPeriod) : 'Add a budget for ' . escape($selectedPeriod) ?></small>
            </article>
            <article class="stat-card <?= $isOverBudget ? 'danger' : 'success' ?>">
                <span>Remaining Budget</span>
                <strong><?= escape(format_currency($remainingBudget)) ?></strong>
                <small><?= $isOverBudget ? 'Balance for ' . escape($selectedPeriod) . ' is over limit' : 'Balance remaining for ' . escape($selectedPeriod) ?></small>
            </article>
        </div>
    </article>

    <aside class="budget-spotlight">
        <div class="spotlight-top">
            <span class="eyebrow">Current Period</span>
            <h2><?= escape(month_options()[$month]) ?> <?= escape((string) $year) ?></h2>
            <p class="muted"><?= $categoryCount ?> active categor<?= $categoryCount === 1 ? 'y' : 'ies' ?> in <?= escape($selectedPeriod) ?></p>
        </div>

        <div class="progress-block">
            <div class="progress-meta">
                <span>Budget usage</span>
                <strong><?= $budgetLimit > 0 ? escape(number_format($budgetUsagePercent, 0)) . '%' : '0%' ?></strong>
            </div>
            <div class="progress-track">
                <span class="progress-fill <?= $isOverBudget ? 'over' : '' ?>" style="width: <?= max(8, $budgetLimit > 0 ? min(100, $budgetUsagePercent) : 8) ?>%"></span>
            </div>
            <p class="muted">
                <?= $budgetLimit > 0
                    ? ($isOverBudget
                        ? 'You are over budget for ' . escape($selectedPeriod) . ' by ' . escape(format_currency(abs($remainingBudget))) . '.'
                        : 'You still have ' . escape(format_currency($remainingBudget)) . ' remaining for ' . escape($selectedPeriod) . '.')
                    : 'No budget has been set for ' . escape($selectedPeriod) . ' yet.' ?>
            </p>
        </div>

        <div class="spotlight-metrics">
            <div>
                <span>Top category</span>
                <strong><?= escape($topCategory) ?></strong>
            </div>
            <div>
                <span>Recent records</span>
                <strong><?= count($stats['recent_expenses']) ?></strong>
            </div>
        </div>

        <form method="get" class="period-card">
            <h3>Change reporting period</h3>
            <div class="period-controls">
                <label>
                    <span>Month</span>
                    <select name="month">
                        <?php foreach (month_options() as $value => $label): ?>
                            <option value="<?= $value ?>" <?= $value === $month ? 'selected' : '' ?>><?= escape($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Year</span>
                    <select name="year">
                        <?php foreach (year_options() as $optionYear): ?>
                            <option value="<?= $optionYear ?>" <?= $optionYear === $year ? 'selected' : '' ?>><?= $optionYear ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <button type="submit" class="button-secondary full-width">Update View</button>
        </form>
    </aside>
</section>

<section class="dashboard-main">
    <div class="main-stack">
        <article class="panel">
            <div class="panel-head">
                <div>
                    <h2>Search and Filter</h2>
                    <p class="panel-copy">Quickly narrow down expenses by keyword, category, date, or reporting period.</p>
                </div>
            </div>
            <form method="get" class="filter-grid dashboard-filters">
                <input type="hidden" name="month" value="<?= $month ?>">
                <input type="hidden" name="year" value="<?= $year ?>">
                <label>
                    <span>Search</span>
                    <input type="text" name="search" value="<?= escape($filters['search']) ?>" placeholder="Title, category, or note">
                </label>
                <label>
                    <span>Category</span>
                    <select name="category">
                        <option value="">All Categories</option>
                        <?php foreach (EXPENSE_CATEGORIES as $category): ?>
                            <option value="<?= escape($category) ?>" <?= $filters['category'] === $category ? 'selected' : '' ?>><?= escape($category) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Date</span>
                    <input type="date" name="date" value="<?= escape($filters['date']) ?>">
                </label>
                <label>
                    <span>Filter Month</span>
                    <select name="filter_month">
                        <option value="">Any</option>
                        <?php foreach (month_options() as $value => $label): ?>
                            <option value="<?= $value ?>" <?= $filters['month'] === (string) $value ? 'selected' : '' ?>><?= escape($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Filter Year</span>
                    <select name="filter_year">
                        <option value="">Any</option>
                        <?php foreach (year_options(8) as $optionYear): ?>
                            <option value="<?= $optionYear ?>" <?= $filters['year'] === (string) $optionYear ? 'selected' : '' ?>><?= $optionYear ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="filter-actions compact-actions">
                    <button type="submit" class="button-primary">Apply Filters</button>
                    <a class="button-secondary" href="dashboard.php?month=<?= $month ?>&year=<?= $year ?>">Reset</a>
                </div>
            </form>
        </article>

        <article class="panel">
            <div class="panel-head">
                <div>
                    <h2>Expense Register</h2>
                    <p class="panel-copy">A complete list of expenses matching your current filters, including their exact expense dates.</p>
                </div>
                <a class="button-primary small" href="expense_form.php">Add Expense</a>
            </div>

            <?php if ($expenses): ?>
                <div class="summary-table">
                    <table class="data-table">
                        <thead>
                        <tr>
                            <th>Expense</th>
                            <th>Amount</th>
                            <th>Category</th>
                            <th>Date</th>
                            <th>Note</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($expenses as $expense): ?>
                            <tr>
                                <td>
                                    <div class="expense-title-cell">
                                        <strong><?= escape($expense['title']) ?></strong>
                                        <span>#<?= (int) $expense['id'] ?> | <?= escape(date('F d, Y', strtotime($expense['expense_date']))) ?></span>
                                    </div>
                                </td>
                                <td><strong><?= escape(format_currency((float) $expense['amount'])) ?></strong></td>
                                <td><span class="category-pill"><?= escape($expense['category']) ?></span></td>
                                <td>
                                    <div class="expense-date-cell">
                                        <strong><?= escape(date('F d, Y', strtotime($expense['expense_date']))) ?></strong>
                                        <span><?= escape(date('F Y', strtotime($expense['expense_date']))) ?></span>
                                    </div>
                                </td>
                                <td><?= escape((string) ($expense['note'] ?: 'No note')) ?></td>
                                <td class="actions-cell">
                                    <a class="text-link" href="expense_form.php?id=<?= (int) $expense['id'] ?>">Edit</a>
                                    <form method="post" action="delete_expense.php" onsubmit="return confirm('Delete this expense?');">
                                        <input type="hidden" name="id" value="<?= (int) $expense['id'] ?>">
                                        <button type="submit" class="text-button danger-text">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="empty-state">No expenses found for your current search or filters.</p>
            <?php endif; ?>
        </article>
    </div>

    <aside class="side-stack">
        <article class="panel">
            <div class="panel-head">
                <div>
                    <h2>Budget Control</h2>
                    <p class="panel-copy">Set or update your spending target for <?= escape($selectedPeriod) ?>.</p>
                </div>
            </div>
            <form method="post" class="form-grid">
                <input type="hidden" name="save_budget" value="1">
                <label>
                    <span>Budget for <?= escape(month_options()[$month]) ?> <?= $year ?></span>
                    <input type="number" name="budget_limit" min="0" step="0.01" value="<?= escape((string) $budgetLimit) ?>" required>
                </label>
                <button type="submit" class="button-primary">Save Budget</button>
            </form>
        </article>

        <article class="panel">
            <div class="panel-head">
                <div>
                    <h2>Category Summary</h2>
                    <p class="panel-copy">How your spending for <?= escape($selectedPeriod) ?> is distributed by category.</p>
                </div>
            </div>
            <?php if ($stats['category_summary']): ?>
                <div class="category-summary-list">
                    <?php foreach ($stats['category_summary'] as $row): ?>
                        <?php $share = $stats['monthly_total'] > 0 ? (($row['total'] / $stats['monthly_total']) * 100) : 0; ?>
                        <div class="category-summary-item">
                            <div class="category-summary-head">
                                <strong><?= escape($row['category']) ?></strong>
                                <span><?= escape(format_currency((float) $row['total'])) ?></span>
                            </div>
                            <div class="mini-track">
                                <span class="mini-fill" style="width: <?= max(6, min(100, $share)) ?>%"></span>
                            </div>
                            <small><?= escape(number_format($share, 0)) ?>% of <?= escape($selectedPeriod) ?> total</small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty-state">No category data yet for this month.</p>
            <?php endif; ?>
        </article>

        <article class="panel">
            <div class="panel-head">
                <div>
                    <h2>Recent Expenses</h2>
                    <p class="panel-copy">Your five latest expense records with their exact expense dates.</p>
                </div>
            </div>
            <?php if ($stats['recent_expenses']): ?>
                <div class="recent-list">
                    <?php foreach ($stats['recent_expenses'] as $expense): ?>
                        <div class="recent-item">
                            <div>
                                <strong><?= escape($expense['title']) ?></strong>
                                <p><?= escape($expense['category']) ?> | <?= escape(date('F d, Y', strtotime($expense['expense_date']))) ?></p>
                            </div>
                            <span><?= escape(format_currency((float) $expense['amount'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty-state">You have no expenses yet.</p>
            <?php endif; ?>
        </article>
    </aside>
</section>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
