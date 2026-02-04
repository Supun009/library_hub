<?php
// member/my_loans.php
require_once '../config/db_config.php';
require_once '../includes/auth_middleware.php';

requireRole('member');

$pageTitle = 'My Loans';

$userId = $_SESSION['user_id'];
$memberId = $pdo->query("SELECT member_id FROM members WHERE user_id = $userId")->fetchColumn();

// Fetch Active Loans
$stmt = $pdo->prepare("
    SELECT i.issue_id, b.title, b.isbn, i.issue_date, i.due_date, i.fine_amount
    FROM issues i
    JOIN books b ON i.book_id = b.book_id
    WHERE i.member_id = ? AND i.return_date IS NULL
    ORDER BY i.due_date ASC
");
$stmt->execute([$memberId]);
$activeLoans = $stmt->fetchAll();

// Fetch Loan History
$stmt = $pdo->prepare("
    SELECT i.issue_id, b.title, i.issue_date, i.return_date, i.fine_amount
    FROM issues i
    JOIN books b ON i.book_id = b.book_id
    WHERE i.member_id = ? AND i.return_date IS NOT NULL
    ORDER BY i.return_date DESC
    LIMIT 10
");
$stmt->execute([$memberId]);
$loanHistory = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl text-gray-900 mb-1">My Loans</h1>
    <p class="text-gray-600">Track your borrowed books and history</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Active Loans Section -->
    <div class="lg:col-span-2">
        <div class="table-container mb-6">
            <div class="table-header">
                <h2 class="text-lg font-semibold text-gray-900">Current Active Loans</h2>
                <p class="text-gray-500 text-sm mt-1">You have <?php echo count($activeLoans); ?> book(s) currently issued.</p>
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
                                    <td class="<?php echo $isOverdue ? 'text-red-600 font-medium' : 'text-gray-600'; ?>">
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
            <?php else: ?>
                <div class="p-8 text-center bg-white">
                    <div class="text-gray-400 mb-2">
                        <i data-lucide="book-open" style="width: 48px; height: 48px; margin: 0 auto;"></i>
                    </div>
                    <p class="text-gray-500">You don't have any active loans right now.</p>
                    <a href="index.php" class="btn btn-primary mt-4">Browse Catalog</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Loan History Sidebar -->
    <div>
        <div class="bg-white rounded shadow-sm border border-gray-200">
             <div class="p-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Loan History</h2>
            </div>
            <div class="p-2">
                <?php if (count($loanHistory) > 0): ?>
                    <?php foreach ($loanHistory as $history): ?>
                        <div class="p-3 border-b border-gray-100 last:border-0 hover:bg-gray-50 transition-colors rounded">
                            <div class="font-medium text-sm text-gray-900 mb-1"><?php echo htmlspecialchars($history['title']); ?></div>
                            <div class="flex justify-between items-center text-xs text-gray-500">
                                <span>Returned: <?php echo $history['return_date']; ?></span>
                                <?php if ($history['fine_amount'] > 0): ?>
                                    <span class="text-red-600 font-medium">Fine: $<?php echo number_format($history['fine_amount'], 2); ?></span>
                                <?php else: ?>
                                    <span class="text-green-600">On Time</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-4 text-center text-gray-500 text-sm">No history available.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
