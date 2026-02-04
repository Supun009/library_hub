<?php
// admin/transactions.php
require_once '../config/db_config.php';
require_once '../includes/auth_middleware.php';

requireRole('admin');

$pageTitle = 'Transactions List';
$success = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'issued') {
        $success = "Book issued successfully.";
    } elseif ($_GET['msg'] === 'returned') {
        $success = "Book returned successfully.";
    }
}

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

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="mb-1 text-2xl font-semibold text-gray-900">Detailed Transactions</h1>
        <p class="text-sm text-gray-600">View and manage all borrowing history</p>
    </div>
    <div class="flex gap-2">
        <a
            href="issue_book.php"
            class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 transition-colors"
        >
            <i data-lucide="arrow-up-right" class="h-4 w-4"></i>
            Issue Book
        </a>
        <a
            href="return_book.php"
            class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
        >
            <i data-lucide="arrow-down-left" class="h-4 w-4"></i>
            Return Book
        </a>
    </div>
</div>

<?php if ($success): ?>
    <div class="mb-4 rounded-md border border-green-200 bg-green-100 px-4 py-3 text-sm text-green-700">
        <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<!-- Search & Sort -->
<div class="mb-6 rounded-md border border-gray-200 bg-white p-4 shadow-sm">
    <form method="GET" class="flex flex-wrap items-center gap-4">
        <div class="relative min-w-[220px] flex-1">
            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
            <input
                type="text"
                name="search"
                value="<?php echo htmlspecialchars($search); ?>"
                placeholder="Search by Member, Book, or ID..."
                class="block w-full rounded-md border border-gray-300 bg-white py-2 pl-9 pr-3 text-sm shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
            >
        </div>
        
        <select
            name="status"
            onchange="this.form.submit()"
            class="block w-full max-w-[150px] rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
        >
            <option value="Active" <?php echo $statusFilter === 'Active' ? 'selected' : ''; ?>>Active (Issued)</option>
            <option value="Overdue" <?php echo $statusFilter === 'Overdue' ? 'selected' : ''; ?>>Overdue</option>
            <option value="Returned" <?php echo $statusFilter === 'Returned' ? 'selected' : ''; ?>>Returned</option>
            <option value="All" <?php echo $statusFilter === 'All' ? 'selected' : ''; ?>>All History</option>
        </select>

        <select
            name="sort"
            onchange="this.form.submit()"
            class="block w-full max-w-[220px] rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
        >
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
                                    <a
                                        href="return_book.php?id=<?php echo $t['issue_id']; ?>"
                                        title="Return Book"
                                        class="inline-flex items-center justify-center rounded-md border border-blue-200 bg-white px-3 py-2 text-sm font-medium text-blue-600 hover:bg-blue-50 transition-colors"
                                    >
                                        <i data-lucide="corner-down-left" class="h-4 w-4"></i>
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
