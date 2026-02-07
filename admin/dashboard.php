<?php
// admin/dashboard.php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireRole('admin');

$pageTitle = 'Dashboard';
include __DIR__ . '/../includes/header.php';

// Fetch statistics
try {
    // Total Books
    $stmt = $pdo->query("SELECT COUNT(*) FROM books");
    $totalBooks = $stmt->fetchColumn();

    // Active Loans (Status = Issued)
    $stmt = $pdo->query("SELECT COUNT(*) FROM books WHERE status_id = (SELECT status_id FROM status WHERE status_name = 'Issued')");
    $activeLoans = $stmt->fetchColumn();

    // Overdue Items (Simple logic: issues past due date with no return date)
    $stmt = $pdo->query("SELECT COUNT(*) FROM issues WHERE return_date IS NULL AND issue_date < DATE_SUB(CURDATE(), INTERVAL 14 DAY)"); // Assuming 14 day loan
    $overdueItems = $stmt->fetchColumn();

    // Total Members
    $stmt = $pdo->query("SELECT COUNT(*) FROM members");
    $totalMembers = $stmt->fetchColumn();

    // Recent Transactions
    $stmt = $pdo->query("
        SELECT i.issue_id, m.full_name as member_name, b.title as book_title, 
               i.issue_date, i.return_date
        FROM issues i
        JOIN members m ON i.member_id = m.member_id
        JOIN books b ON i.book_id = b.book_id
        ORDER BY i.issue_date DESC
        LIMIT 5
    ");
    $recentTransactions = $stmt->fetchAll();

} catch (PDOException $e) {
    echo "Error fetching stats: " . $e->getMessage();
}
?>

<div class="mb-6">
    <h1 class="page-heading">Dashboard</h1>
    <p class="text-sm text-gray-600">Welcome back! Here's what's happening today.</p>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <p class="text-gray-500 mb-1">Total Books</p>
                <h3 class="text-2xl font-semibold text-gray-900"><?php echo number_format($totalBooks); ?></h3>
            </div>
            <div class="stat-icon blue">
                <i data-lucide="book-open"></i>
            </div>
        </div>
        <p class="text-xs text-gray-500">+ new this month</p>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div>
                <p class="text-gray-500 mb-1">Active Loans</p>
                <h3 class="text-2xl font-semibold text-gray-900"><?php echo number_format($activeLoans); ?></h3>
            </div>
            <div class="stat-icon green">
                <i data-lucide="trending-up"></i>
            </div>
        </div>
        <p class="text-xs text-gray-500">Currently issued</p>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div>
                <p class="text-gray-500 mb-1">Overdue Items</p>
                <h3 class="text-2xl font-semibold text-gray-900"><?php echo number_format($overdueItems); ?></h3>
            </div>
            <div class="stat-icon red">
                <i data-lucide="alert-triangle"></i>
            </div>
        </div>
        <p class="text-xs text-gray-500">Action required</p>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div>
                <p class="text-gray-500 mb-1">Total Members</p>
                <h3 class="text-2xl font-semibold text-gray-900"><?php echo number_format($totalMembers); ?></h3>
            </div>
            <div class="stat-icon indigo">
                <i data-lucide="users"></i>
            </div>
        </div>
        <p class="text-xs text-gray-500">Registered users</p>
    </div>
</div>

<!-- Recent Transactions -->
<div class="table-container">
    <div class="table-header">
        <h2 class="text-lg font-semibold text-gray-900">Recent Transactions</h2>
        <p class="text-gray-500 text-sm mt-1">Latest book issues and returns</p>
    </div>
    <div class="overflow-x-auto">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Member</th>
                    <th>Book</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($recentTransactions) > 0): ?>
                    <?php foreach ($recentTransactions as $txn): ?>
                        <tr>
                            <td><?php echo $txn['issue_id']; ?></td>
                            <td><?php echo htmlspecialchars($txn['member_name']); ?></td>
                            <td><?php echo htmlspecialchars($txn['book_title']); ?></td>
                            <td><?php echo $txn['return_date'] ? $txn['return_date'] : $txn['issue_date']; ?></td>
                            <td>
                                <span class="badge <?php echo $txn['return_date'] ? 'badge-gray' : 'badge-green'; ?>">
                                    <?php echo $txn['return_date'] ? 'Completed' : 'Active'; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No transactions found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
