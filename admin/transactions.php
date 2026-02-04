<?php
// admin/transactions.php
require_once '../config/db_config.php';
require_once '../includes/auth_middleware.php';

requireRole('admin');

$pageTitle = 'Transactions List';

// Fetch Transactions
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'i.issue_date DESC';
$statusFilter = $_GET['status'] ?? 'Active'; // Default to Active (Not Returned)

$validSorts = [
    'i.issue_date DESC' => 'Date (Newest)',
    'i.issue_date ASC' => 'Date (Oldest)',
    'm.full_name ASC' => 'Member (A-Z)',
    'b.title ASC' => 'Book (A-Z)'
];

if (!array_key_exists($sort, $validSorts)) {
    $sort = 'i.issue_date DESC';
}

$query = "
    SELECT i.issue_id, i.issue_date, i.due_date, i.return_date, i.fine_amount,
           m.full_name, m.member_id,
           b.title, b.isbn
    FROM issues i
    JOIN members m ON i.member_id = m.member_id
    JOIN books b ON i.book_id = b.book_id
";

$conditions = [];
$params = [];

if ($search) {
    $conditions[] = "(m.full_name LIKE ? OR b.title LIKE ? OR i.issue_id = ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = $search;
}

// Status Filtering Logic
if ($statusFilter === 'Active') {
    $conditions[] = "i.return_date IS NULL";
} elseif ($statusFilter === 'Returned') {
    $conditions[] = "i.return_date IS NOT NULL";
} elseif ($statusFilter === 'Overdue') {
    $conditions[] = "(i.return_date IS NULL AND i.due_date < CURDATE())";
}
// 'All' implies no extra condition

if (count($conditions) > 0) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY $sort";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl text-gray-900 mb-1">Detailed Transactions</h1>
        <p class="text-gray-600">View and manage all borrowing history</p>
    </div>
    <div class="flex gap-2">
        <a href="issue_book.php" class="btn btn-primary">
            <i data-lucide="arrow-up-right"></i> Issue Book
        </a>
        <a href="return_book.php" class="btn btn-secondary" style="background: white; border: 1px solid var(--gray-300); color: var(--gray-700);">
            <i data-lucide="arrow-down-left"></i> Return Book
        </a>
    </div>
</div>

<!-- Search & Sort -->
<div class="mb-6 bg-white p-4 rounded shadow-sm border border-gray-200">
    <form method="GET" class="flex gap-4 items-center flex-wrap">
        <div class="header-search" style="margin: 0; flex: 1; min-width: 200px;">
            <i data-lucide="search"></i>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by Member, Book, or ID...">
        </div>
        
        <select name="status" class="form-control" style="width: 150px;" onchange="this.form.submit()">
            <option value="Active" <?php echo $statusFilter === 'Active' ? 'selected' : ''; ?>>Active (Issued)</option>
            <option value="Overdue" <?php echo $statusFilter === 'Overdue' ? 'selected' : ''; ?>>Overdue</option>
            <option value="Returned" <?php echo $statusFilter === 'Returned' ? 'selected' : ''; ?>>Returned</option>
            <option value="All" <?php echo $statusFilter === 'All' ? 'selected' : ''; ?>>All History</option>
        </select>

        <select name="sort" class="form-control" style="width: 200px;" onchange="this.form.submit()">
            <?php foreach ($validSorts as $val => $label): ?>
                <option value="<?php echo $val; ?>" <?php echo $sort === $val ? 'selected' : ''; ?>>
                    <?php echo $label; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<!-- Transactions Table -->
<div class="table-container">
    <div class="overflow-x-auto">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Member</th>
                    <th>Book</th>
                    <th>Issue Date</th>
                    <th>Due Date</th>
                    <th>Return Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($transactions) > 0): ?>
                    <?php foreach ($transactions as $t): ?>
                        <?php 
                            $isOverdue = !$t['return_date'] && strtotime($t['due_date']) < time();
                            $status = $t['return_date'] ? 'Returned' : ($isOverdue ? 'Overdue' : 'Issued');
                            $statusClass = $t['return_date'] ? 'badge-green' : ($isOverdue ? 'badge-red' : 'badge-blue');
                        ?>
                        <tr>
                            <td class="text-gray-500">#<?php echo $t['issue_id']; ?></td>
                            <td class="font-medium"><?php echo htmlspecialchars($t['full_name']); ?></td>
                            <td>
                                <p class="text-sm text-gray-900"><?php echo htmlspecialchars($t['title']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($t['isbn']); ?></p>
                            </td>
                            <td><?php echo $t['issue_date']; ?></td>
                            <td>
                                <span class="<?php echo $isOverdue ? 'text-red-600 font-semibold' : ''; ?>">
                                    <?php echo $t['due_date']; ?>
                                </span>
                            </td>
                            <td><?php echo $t['return_date'] ? $t['return_date'] : '-'; ?></td>
                            <td>
                                <span class="badge <?php echo $statusClass; ?>">
                                    <?php echo $status; ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!$t['return_date']): ?>
                                    <a href="return_book.php?id=<?php echo $t['issue_id']; ?>" class="btn text-blue-600 hover:bg-blue-50" title="Return Book">
                                        <i data-lucide="corner-down-left" style="width: 16px; height: 16px;"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center p-6 text-gray-500">No transactions found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
