<?php
// admin/member_history.php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireRole('admin');

$memberId = $_GET['id'] ?? null;
if (!$memberId) {
    redirect('admin/members');
    exit;
}

$pageTitle = 'Member Issue History';

// Fetch Member Details
$stmt = $pdo->prepare("
    SELECT m.*, u.username
    FROM members m 
    JOIN users u ON m.user_id = u.user_id 
    WHERE m.member_id = ?
");
$stmt->execute([$memberId]);
$member = $stmt->fetch();

if (!$member) {
    die("Member not found.");
}

// Pagination
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$itemsPerPage = 15;
$offset = ($currentPage - 1) * $itemsPerPage;

// Status filter
$statusFilter = $_GET['status'] ?? 'all'; // 'all', 'active', 'returned', 'overdue'

// Count total issues
$countQuery = "
    SELECT COUNT(*) as total
    FROM issues i
    WHERE i.member_id = ?
";

if ($statusFilter === 'active') {
    $countQuery .= " AND i.return_date IS NULL AND i.due_date >= CURDATE()";
} elseif ($statusFilter === 'returned') {
    $countQuery .= " AND i.return_date IS NOT NULL";
} elseif ($statusFilter === 'overdue') {
    $countQuery .= " AND i.return_date IS NULL AND i.due_date < CURDATE()";
}

$countStmt = $pdo->prepare($countQuery);
$countStmt->execute([$memberId]);
$totalItems = $countStmt->fetch()['total'];

// Fetch Issue Records
$query = "
    SELECT 
        i.issue_id,
        i.issue_date,
        i.due_date,
        i.return_date,
        i.fine_amount,
        b.title as book_title,
        b.isbn,
        CASE 
            WHEN i.return_date IS NOT NULL THEN 'Returned'
            WHEN i.due_date < CURDATE() THEN 'Overdue'
            ELSE 'Active'
        END as status
    FROM issues i
    JOIN books b ON i.book_id = b.book_id
    WHERE i.member_id = :member_id
";

if ($statusFilter === 'active') {
    $query .= " AND i.return_date IS NULL AND i.due_date >= CURDATE()";
} elseif ($statusFilter === 'returned') {
    $query .= " AND i.return_date IS NOT NULL";
} elseif ($statusFilter === 'overdue') {
    $query .= " AND i.return_date IS NULL AND i.due_date < CURDATE()";
}

$query .= " ORDER BY i.issue_date DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);
$stmt->bindValue(':member_id', $memberId, PDO::PARAM_INT);
$stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$issues = $stmt->fetchAll();

// Calculate statistics
$statsStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_issues,
        SUM(CASE WHEN return_date IS NULL THEN 1 ELSE 0 END) as active_issues,
        SUM(CASE WHEN return_date IS NOT NULL THEN 1 ELSE 0 END) as returned_issues,
        SUM(CASE WHEN return_date IS NULL AND due_date < CURDATE() THEN 1 ELSE 0 END) as overdue_issues,
        COALESCE(SUM(fine_amount), 0) as total_fines
    FROM issues
    WHERE member_id = ?
");
$statsStmt->execute([$memberId]);
$stats = $statsStmt->fetch();

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="mb-1 text-2xl font-semibold text-gray-900">Issue History</h1>
        <p class="text-sm text-gray-600">
            All borrowing records for <span class="font-medium text-gray-900"><?php echo htmlspecialchars($member['full_name']); ?></span>
        </p>
    </div>
    <a
        href="<?php echo url('admin/members'); ?>"
        class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 transition-colors"
    >
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Back to Members
    </a>
</div>

<!-- Member Info Card -->
<div class="mb-6 rounded border border-gray-200 bg-white p-6 shadow-sm">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <p class="text-sm text-gray-500">Member Name</p>
            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($member['full_name']); ?></p>
        </div>
        <div>
            <p class="text-sm text-gray-500">Username</p>
            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($member['username']); ?></p>
        </div>
        <div>
            <p class="text-sm text-gray-500">Email</p>
            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($member['email']); ?></p>
        </div>
        <div>
            <p class="text-sm text-gray-500">Status</p>
            <span class="badge <?php echo $member['status'] === 'active' ? 'badge-green' : 'badge-red'; ?>">
                <?php echo ucfirst($member['status']); ?>
            </span>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="mb-6 grid grid-cols-1 md:grid-cols-5 gap-4">
    <div class="rounded border border-gray-200 bg-white p-4 shadow-sm">
        <p class="text-sm text-gray-500 mb-1">Total Issues</p>
        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_issues']; ?></p>
    </div>
    <div class="rounded border border-blue-200 bg-blue-50 p-4 shadow-sm">
        <p class="text-sm text-blue-700 mb-1">Active Loans</p>
        <p class="text-2xl font-bold text-blue-900"><?php echo $stats['active_issues']; ?></p>
    </div>
    <div class="rounded border border-green-200 bg-green-50 p-4 shadow-sm">
        <p class="text-sm text-green-700 mb-1">Returned</p>
        <p class="text-2xl font-bold text-green-900"><?php echo $stats['returned_issues']; ?></p>
    </div>
    <div class="rounded border border-red-200 bg-red-50 p-4 shadow-sm">
        <p class="text-sm text-red-700 mb-1">Overdue</p>
        <p class="text-2xl font-bold text-red-900"><?php echo $stats['overdue_issues']; ?></p>
    </div>
    <div class="rounded border border-yellow-200 bg-yellow-50 p-4 shadow-sm">
        <p class="text-sm text-yellow-700 mb-1">Total Fines</p>
        <p class="text-2xl font-bold text-yellow-900">$<?php echo number_format($stats['total_fines'], 2); ?></p>
    </div>
</div>

<!-- Filter Bar -->
<div class="mb-6 rounded border border-gray-200 bg-white p-4 shadow-sm">
    <form method="GET" class="flex items-center gap-3">
        <input type="hidden" name="id" value="<?php echo $memberId; ?>">
        <label class="text-sm font-medium text-gray-700">Filter:</label>
        <select
            name="status"
            onchange="this.form.submit()"
            class="block rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
        >
            <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Records</option>
            <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active Loans</option>
            <option value="overdue" <?php echo $statusFilter === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
            <option value="returned" <?php echo $statusFilter === 'returned' ? 'selected' : ''; ?>>Returned</option>
        </select>
    </form>
</div>

<!-- Issue Records Table -->
<div class="table-container">
    <div class="table-header">
        <h2 class="text-lg font-semibold text-gray-900">Borrowing Records</h2>
        <p class="text-gray-500 text-sm mt-1">Total: <?php echo $totalItems; ?> record(s)</p>
    </div>
    <div class="overflow-x-auto">
        <table>
            <thead>
                <tr>
                    <th>Book Title</th>
                    <th>ISBN</th>
                    <th>Issue Date</th>
                    <th>Due Date</th>
                    <th>Return Date</th>
                    <th>Fine</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($issues) > 0): ?>
                    <?php foreach ($issues as $issue): ?>
                        <tr>
                            <td class="font-medium"><?php echo htmlspecialchars($issue['book_title']); ?></td>
                            <td class="text-gray-500"><?php echo htmlspecialchars($issue['isbn']); ?></td>
                            <td class="text-gray-500"><?php echo date('M d, Y', strtotime($issue['issue_date'])); ?></td>
                            <td class="text-gray-500"><?php echo date('M d, Y', strtotime($issue['due_date'])); ?></td>
                            <td class="text-gray-500">
                                <?php echo $issue['return_date'] ? date('M d, Y', strtotime($issue['return_date'])) : '-'; ?>
                            </td>
                            <td class="text-gray-500">
                                <?php echo $issue['fine_amount'] > 0 ? '$' . number_format($issue['fine_amount'], 2) : '-'; ?>
                            </td>
                            <td>
                                <?php
                                    $badgeClass = 'badge-gray';
                                    if ($issue['status'] === 'Active') {
                                        $badgeClass = 'badge-blue';
                                    } elseif ($issue['status'] === 'Returned') {
                                        $badgeClass = 'badge-green';
                                    } elseif ($issue['status'] === 'Overdue') {
                                        $badgeClass = 'badge-red';
                                    }
                                ?>
                                <span class="badge <?php echo $badgeClass; ?>">
                                    <?php echo $issue['status']; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center p-6 text-gray-500">
                            No issue records found for this member.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Include and render pagination
if ($totalItems > $itemsPerPage) {
    require_once __DIR__ . '/../includes/pagination.php';
    renderPagination($currentPage, $totalItems, $itemsPerPage, [
        'id' => $memberId,
        'status' => $statusFilter
    ]);
}
?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
