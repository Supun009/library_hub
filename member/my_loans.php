<?php
// member/my_loans.php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireRole('member');

$pageTitle = 'My Loans';

$userId = $_SESSION['user_id'];
$memberId = $pdo->query("SELECT member_id FROM members WHERE user_id = $userId")->fetchColumn();

// Pagination for Active Loans
$activePage = isset($_GET['active_page']) ? max(1, intval($_GET['active_page'])) : 1;
$activeItemsPerPage = 10;
$activeOffset = ($activePage - 1) * $activeItemsPerPage;

// Count total active loans
$countStmt = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM issues i
    WHERE i.member_id = ? AND i.return_date IS NULL
");
$countStmt->execute([$memberId]);
$totalActiveLoans = $countStmt->fetch()['total'];

// Fetch Active Loans with pagination
$stmt = $pdo->prepare("
    SELECT i.issue_id, b.title, b.isbn, i.issue_date, i.due_date, i.fine_amount
    FROM issues i
    JOIN books b ON i.book_id = b.book_id
    WHERE i.member_id = :member_id AND i.return_date IS NULL
    ORDER BY i.due_date ASC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':member_id', $memberId, PDO::PARAM_INT);
$stmt->bindValue(':limit', $activeItemsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $activeOffset, PDO::PARAM_INT);
$stmt->execute();
$activeLoans = $stmt->fetchAll();

// Pagination for Loan History
$historyPage = isset($_GET['history_page']) ? max(1, intval($_GET['history_page'])) : 1;
$historyItemsPerPage = 10;
$historyOffset = ($historyPage - 1) * $historyItemsPerPage;

// Count total loan history
$countStmt = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM issues i
    WHERE i.member_id = ? AND i.return_date IS NOT NULL
");
$countStmt->execute([$memberId]);
$totalLoanHistory = $countStmt->fetch()['total'];

// Fetch Loan History with pagination
$stmt = $pdo->prepare("
    SELECT i.issue_id, b.title, i.issue_date, i.return_date, i.fine_amount
    FROM issues i
    JOIN books b ON i.book_id = b.book_id
    WHERE i.member_id = :member_id AND i.return_date IS NOT NULL
    ORDER BY i.return_date DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':member_id', $memberId, PDO::PARAM_INT);
$stmt->bindValue(':limit', $historyItemsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $historyOffset, PDO::PARAM_INT);
$stmt->execute();
$loanHistory = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <h1 class="page-heading">My Loans</h1>
    <p class="text-sm text-gray-600">Track your borrowed books and history</p>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <!-- Active Loans Section -->
    <div class="lg:col-span-2">
        <div class="mb-6 table-container">
            <div class="table-header">
                <h2 class="text-lg font-semibold text-gray-900">Current Active Loans</h2>
                    <p class="mt-1 text-sm text-gray-500">You have <?php echo $totalActiveLoans; ?> book(s) currently issued.</p>
            </div>
            
            <?php if (count($activeLoans) > 0): ?>
                <div class="overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th>Book Details</th>
                                <th>Issue Date</th>
                                <th>Due Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activeLoans as $loan): ?>
                                <?php 
                                    $dueDate = new DateTime($loan['due_date']);
                                    $today = new DateTime();
                                    $isOverdue = $today > $dueDate;
                                ?>
                                <tr>
                                    <td>
                                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($loan['title']); ?></div>
                                        <div class="text-xs text-gray-500">ISBN: <?php echo htmlspecialchars($loan['isbn']); ?></div>
                                    </td>
                            <td class="text-gray-600"><?php echo $loan['issue_date']; ?></td>
                            <td class="<?php echo $isOverdue ? 'font-medium text-red-600' : 'text-gray-600'; ?>">
                                        <?php echo $loan['due_date']; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $isOverdue ? 'badge-red' : 'badge-green'; ?>">
                                            <?php echo $isOverdue ? 'Overdue' : 'Active'; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php
                // Include and render pagination for active loans
                require_once __DIR__ . '/../includes/pagination.php';
                renderPagination($activePage, $totalActiveLoans, $activeItemsPerPage, ['history_page' => $historyPage], 'active_page');
                ?>
            <?php else: ?>
                <div class="bg-white p-8 text-center">
                    <div class="mb-2 text-gray-400">
                        <i data-lucide="book-open" class="mx-auto h-12 w-12"></i>
                    </div>
                    <p class="text-gray-500">You don't have any active loans right now.</p>
                    <a
                        href="<?php echo url('member'); ?>"
                        class="mt-4 inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 transition-colors"
                    >
                        Browse Catalog
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Loan History Sidebar -->
    <div>
        <div class="rounded border border-gray-200 bg-white shadow-sm">
             <div class="border-b border-gray-200 p-4">
                <h2 class="text-lg font-semibold text-gray-900">Loan History</h2>
                <p class="mt-1 text-xs text-gray-500">Total: <?php echo $totalLoanHistory; ?> record(s)</p>
            </div>
            <div class="p-2">
                <?php if (count($loanHistory) > 0): ?>
                    <?php foreach ($loanHistory as $history): ?>
                        <div class="rounded p-3 border-b border-gray-100 last:border-0 hover:bg-gray-50 transition-colors">
                            <div class="mb-1 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($history['title']); ?></div>
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span>Returned: <?php echo $history['return_date']; ?></span>
                                <?php if ($history['fine_amount'] > 0): ?>
                                    <span class="font-medium text-red-600">Fine: $<?php echo number_format($history['fine_amount'], 2); ?></span>
                                <?php else: ?>
                                    <span class="text-green-600">On Time</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-4 text-center text-sm text-gray-500">No history available.</div>
                <?php endif; ?>
            </div>
            <?php if ($totalLoanHistory > 0): ?>
                <div class="border-t border-gray-200 p-3">
                    <?php
                    // Include and render pagination for loan history
                    require_once __DIR__ . '/../includes/pagination.php';
                    renderPagination($historyPage, $totalLoanHistory, $historyItemsPerPage, ['active_page' => $activePage], 'history_page');
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
