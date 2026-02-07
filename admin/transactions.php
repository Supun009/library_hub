<?php
// admin/transactions.php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireRole('admin');

$pageTitle = 'Transactions List';
$success = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'issued') {
        $count = isset($_GET['count']) ? intval($_GET['count']) : 1;
        $success = $count > 1 
            ? "Successfully issued $count books." 
            : "Book issued successfully.";
    } elseif ($_GET['msg'] === 'returned') {
        $count = isset($_GET['count']) ? intval($_GET['count']) : 1;
        $success = $count > 1 
            ? "Successfully returned $count books." 
            : "Book returned successfully.";
    }
}

// Fetch Transactions with Pagination
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'i.issue_date DESC';
$statusFilter = $_GET['status'] ?? 'Active'; // Default to Active (Not Returned)
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$itemsPerPage = 15;
$offset = ($currentPage - 1) * $itemsPerPage;

$validSorts = [
    'i.issue_date DESC' => 'Date (Newest)',
    'i.issue_date ASC' => 'Date (Oldest)',
    'm.full_name ASC' => 'Member (A-Z)',
    'b.title ASC' => 'Book (A-Z)'
];

if (!array_key_exists($sort, $validSorts)) {
    $sort = 'i.issue_date DESC';
}

// Build base query
$baseQuery = "
    FROM issues i
    JOIN members m ON i.member_id = m.member_id
    JOIN books b ON i.book_id = b.book_id
";

$conditions = [];
$params = [];

if ($search) {
    $conditions[] = "(m.full_name LIKE :search_name OR b.title LIKE :search_title OR i.issue_id = :search_id)";
    $params['search_name'] = "%$search%";
    $params['search_title'] = "%$search%";
    $params['search_id'] = $search;
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

$whereClause = '';
if (count($conditions) > 0) {
    $whereClause = " WHERE " . implode(" AND ", $conditions);
}

// Count total items for pagination
$countQuery = "SELECT COUNT(*) as total " . $baseQuery . $whereClause;
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$totalItems = $countStmt->fetch()['total'];

// Fetch transactions with pagination
$query = "
    SELECT i.issue_id, i.issue_date, i.due_date, i.return_date, i.fine_amount,
           m.full_name, m.member_id,
           b.title, b.isbn
    " . $baseQuery . $whereClause . "
    ORDER BY $sort
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($query);

// Bind all parameters using named parameters
foreach ($params as $key => $value) {
    $stmt->bindValue(":$key", $value);
}

// Bind pagination parameters as integers
$stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$transactions = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h1 class="page-heading">Detailed Transactions</h1>
        <p class="text-sm text-gray-600">View and manage all borrowing history</p>
    </div>
    <div class="flex gap-2 w-full sm:w-auto">
        <a
            href="<?php echo url('admin/issue'); ?>"
            class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 transition-colors"
        >
            <i data-lucide="arrow-up-right" class="h-4 w-4"></i>
            Issue Book
        </a>
        <a
            href="<?php echo url('admin/return'); ?>"
            class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
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
    <form method="GET" class="flex flex-col sm:flex-row flex-wrap items-center gap-4">
        <div class="relative w-full sm:flex-1 min-w-[200px]">
            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
            <input
                type="text"
                name="search"
                value="<?php echo htmlspecialchars($search); ?>"
                placeholder="Search by Member, Book, or ID..."
                class="block w-full rounded-md border border-gray-300 bg-white py-2 pl-9 pr-3 text-sm shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
            >
        </div>
        
        <div class="grid grid-cols-2 sm:flex sm:flex-row gap-2 w-full sm:w-auto">
            <!-- Status Dropdown -->
            <div class="relative w-full sm:w-auto">
                <details class="group relative w-full sm:w-[150px]">
                    <summary class="flex items-center justify-between w-full cursor-pointer rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 list-none">
                        <span class="truncate">
                            <?php 
                                $statusLabels = [
                                    'Active' => 'Active',
                                    'Overdue' => 'Overdue',
                                    'Returned' => 'Returned',
                                    'All' => 'All History'
                                ];
                                echo $statusLabels[$statusFilter] ?? 'Active';
                            ?>
                        </span>
                        <i data-lucide="chevron-down" class="h-4 w-4 text-gray-400 transition-transform group-open:rotate-180"></i>
                    </summary>
                    <div class="absolute right-0 z-10 mt-1 w-full min-w-[150px] origin-top-right rounded-md border border-gray-200 bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                        <?php foreach ($statusLabels as $val => $label): ?>
                            <a 
                                href="?<?php echo http_build_query(array_merge($_GET, ['status' => $val, 'page' => 1])); ?>"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 <?php echo $statusFilter === $val ? 'bg-gray-50 font-medium text-indigo-600' : ''; ?>"
                            >
                                <?php echo $label; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </details>
            </div>

            <!-- Sort Dropdown -->
            <div class="relative w-full sm:w-auto">
                <details class="group relative w-full sm:w-[220px]">
                    <summary class="flex items-center justify-between w-full cursor-pointer rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 list-none">
                        <span class="truncate">
                            <?php echo $validSorts[$sort] ?? 'Date (Newest)'; ?>
                        </span>
                        <i data-lucide="chevron-down" class="h-4 w-4 text-gray-400 transition-transform group-open:rotate-180"></i>
                    </summary>
                    <div class="absolute right-0 z-10 mt-1 w-full min-w-[220px] origin-top-right rounded-md border border-gray-200 bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                        <?php foreach ($validSorts as $val => $label): ?>
                            <a 
                                href="?<?php echo http_build_query(array_merge($_GET, ['sort' => $val])); ?>"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 <?php echo $sort === $val ? 'bg-gray-50 font-medium text-indigo-600' : ''; ?>"
                            >
                                <?php echo $label; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </details>
            </div>
        </div>
    </form>
</div>

<!-- Transactions Table -->
<div class="table-container">
    <div class="table-header">
        <h2 class="text-lg font-semibold text-gray-900">Transaction History</h2>
        <p class="text-gray-500 text-sm mt-1">Total: <?php echo $totalItems; ?> transaction(s)</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="whitespace-nowrap">ID</th>
                    <th class="whitespace-nowrap">Member</th>
                    <th class="whitespace-nowrap">Book</th>
                    <th class="whitespace-nowrap">Issue Date</th>
                    <th class="whitespace-nowrap">Due Date</th>
                    <th class="whitespace-nowrap">Return Date</th>
                    <th class="whitespace-nowrap">Status</th>
                    <!-- <th>Actions</th> -->
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
                        <tr class="hover:bg-gray-50">
                            <td class="text-gray-500 whitespace-nowrap px-4 py-3"><?php echo $t['issue_id']; ?></td>
                            <td class="font-medium whitespace-nowrap px-4 py-3"><?php echo htmlspecialchars($t['full_name']); ?></td>
                            <td class="min-w-[200px] px-4 py-3">
                                <p class="text-sm text-gray-900 truncate" title="<?php echo htmlspecialchars($t['title']); ?>"><?php echo htmlspecialchars($t['title']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($t['isbn']); ?></p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3"><?php echo $t['issue_date']; ?></td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="<?php echo $isOverdue ? 'text-red-600 font-semibold' : ''; ?>">
                                    <?php echo $t['due_date']; ?>
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3"><?php echo $t['return_date'] ? $t['return_date'] : '-'; ?></td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="badge <?php echo $statusClass; ?>">
                                    <?php echo $status; ?>
                                </span>
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

<?php
// Include and render pagination
require_once __DIR__ . '/../includes/pagination.php';
renderPagination($currentPage, $totalItems, $itemsPerPage, [
    'search' => $search,
    'status' => $statusFilter,
    'sort' => $sort
]);
?>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
    // Close details dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        const details = document.querySelectorAll('details');
        details.forEach(detail => {
            if (detail.hasAttribute('open') && !detail.contains(e.target)) {
                detail.removeAttribute('open');
            }
        });
    });
</script>
