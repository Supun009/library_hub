<?php
// admin/manage_members.php
require_once '../config/db_config.php';
require_once '../includes/auth_middleware.php';

requireRole('admin');

$pageTitle = 'Member Management';
$error = '';
$success = '';

// Handle Add Member Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_member') {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']); // We'll double as student ID/username
    $password = $_POST['password'];

    if (empty($fullName) || empty($email) || empty($username) || empty($password)) {
        $error = "All fields are required.";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Create User
            $hashedPwd = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role_id) VALUES (?, ?, (SELECT role_id FROM roles WHERE role_name = 'member'))");
            $stmt->execute([$username, $hashedPwd]);
            $userId = $pdo->lastInsertId();

            // 2. Create Member
            $stmt = $pdo->prepare("INSERT INTO members (user_id, full_name, email) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $fullName, $email]);

            $pdo->commit();
            $success = "Member registered successfully.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->errorInfo[1] == 1062) {
                $error = "Username or Email already exists.";
            } else {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
}

// Check for delete success
if (isset($_GET['msg']) && $_GET['msg'] === 'member_deleted') {
    $success = "Member deleted successfully.";
}

// Fetch Members with Pagination
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? 'all'; // 'all', 'active', 'inactive'
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$itemsPerPage = 10;
$offset = ($currentPage - 1) * $itemsPerPage;

// Count total members for pagination
$countQuery = "
    SELECT COUNT(*) as total
    FROM members m
    JOIN users u ON m.user_id = u.user_id
    WHERE 1=1
";

// Apply status filter to count
if ($statusFilter === 'active') {
    $countQuery .= " AND m.status = 'active'";
} elseif ($statusFilter === 'inactive') {
    $countQuery .= " AND m.status = 'inactive'";
}

// Apply search filter to count
if ($search) {
    $countQuery .= " AND (m.full_name LIKE :search OR m.email LIKE :search OR u.username LIKE :search)";
}

$countStmt = $pdo->prepare($countQuery);
if ($search) {
    $countStmt->bindValue(':search', "%$search%");
}
$countStmt->execute();
$totalItems = $countStmt->fetch()['total'];

// Fetch members with pagination
$query = "
    SELECT m.member_id, m.full_name, m.email, m.join_date, m.status, u.username,
           (SELECT COUNT(*) FROM issues i WHERE i.member_id = m.member_id AND i.return_date IS NULL) as active_loans
    FROM members m
    JOIN users u ON m.user_id = u.user_id
    WHERE 1=1
";

// Apply status filter
if ($statusFilter === 'active') {
    $query .= " AND m.status = 'active'";
} elseif ($statusFilter === 'inactive') {
    $query .= " AND m.status = 'inactive'";
}

// Apply search filter
if ($search) {
    $query .= " AND (m.full_name LIKE :search OR m.email LIKE :search OR u.username LIKE :search)";
}

$query .= " ORDER BY m.member_id DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($query);

if ($search) {
    $stmt->bindValue(':search', "%$search%");
}
$stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$members = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="mb-1 text-2xl font-semibold text-gray-900">Member Management</h1>
        <p class="text-sm text-gray-600">Register and manage library members</p>
    </div>
    <button
        onclick="document.getElementById('addMemberForm').classList.toggle('hidden')"
        class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 transition-colors"
    >
        <i data-lucide="user-plus" class="h-4 w-4"></i>
        Add New Member
    </button>
</div>

<?php if ($error): ?>
    <div class="mb-4 rounded-md border border-red-200 bg-red-100 px-4 py-3 text-sm text-red-700">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="mb-4 rounded-md border border-green-200 bg-green-100 px-4 py-3 text-sm text-green-700">
        <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<!-- Add Member Form (Hidden by default) -->
<div id="addMemberForm" class="hidden mb-6 rounded border border-gray-200 bg-white p-6 shadow-sm">
    <h2 class="mb-4 text-lg font-semibold text-gray-900">Register New Member</h2>
    <form method="POST">
        <input type="hidden" name="action" value="add_member">
        <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Full Name</label>
                <input
                    type="text"
                    name="full_name"
                    required
                    placeholder="e.g. John Doe"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                <input
                    type="email"
                    name="email"
                    required
                    placeholder="john@example.com"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Username / Student ID</label>
                <input
                    type="text"
                    name="username"
                    required
                    placeholder="e.g. STU001"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Password</label>
                <input
                    type="password"
                    name="password"
                    required
                    placeholder="Default password"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
            </div>
        </div>
        <div class="flex gap-2">
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 transition-colors"
            >
                Register Member
            </button>
            <button
                type="button"
                onclick="document.getElementById('addMemberForm').classList.add('hidden')"
                class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 transition-colors"
            >
                Cancel
            </button>
        </div>
    </form>
</div>

<!-- Search Bar and Filters -->
<div class="mb-6 rounded border border-gray-200 bg-white p-4 shadow-sm">
    <form method="GET" class="flex flex-col gap-3 md:flex-row md:items-center">
        <!-- Search Input -->
        <div class="relative flex-grow">
            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
            <input
                type="text"
                name="search"
                value="<?php echo htmlspecialchars($search); ?>"
                placeholder="Search members by name, email, or ID..."
                class="block w-full rounded-md border border-gray-300 bg-white py-2 pl-9 pr-3 text-sm shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
            >
        </div>
        
        <!-- Status Filter -->
        <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Status:</label>
            <select
                name="status"
                onchange="this.form.submit()"
                class="block rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
            >
                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Members</option>
                <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active Only</option>
                <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive Only</option>
            </select>
        </div>
    </form>
</div>

<!-- Members Table -->
<div class="table-container">
    <div class="table-header">
        <h2 class="text-lg font-semibold text-gray-900">Registered Members</h2>
        <p class="text-gray-500 text-sm mt-1">Total: <?php echo $totalItems; ?> member(s)</p>
    </div>
    <div class="overflow-x-auto">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Student ID</th>
                    <th>Email</th>
                    <th>Join Date</th>
                    <th>Status</th>
                    <th>Active Loans</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($members) > 0): ?>
                    <?php foreach ($members as $member): ?>
                        <tr>
                            <td class="font-medium"><?php echo htmlspecialchars($member['full_name']); ?></td>
                            <td class="text-gray-500"><?php echo htmlspecialchars($member['username']); ?></td>
                            <td class="text-gray-500"><?php echo htmlspecialchars($member['email']); ?></td>
                            <td class="text-gray-500"><?php echo htmlspecialchars($member['join_date']); ?></td>
                            <td>
                                <span class="badge <?php echo $member['status'] === 'active' ? 'badge-green' : 'badge-red'; ?>">
                                    <?php echo ucfirst($member['status']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $member['active_loans'] > 0 ? 'badge-blue' : 'badge-gray'; ?>">
                                    <?php echo $member['active_loans']; ?>
                                </span>
                            </td>
                            <td>
                                <a
                                    href="edit_member.php?id=<?php echo $member['member_id']; ?>"
                                    class="inline-flex items-center justify-center rounded-md border border-indigo-200 bg-white px-2 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50 transition-colors"
                                >
                                    <i data-lucide="edit" class="h-4 w-4"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center p-6 text-gray-500">No members found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Include and render pagination
require_once '../includes/pagination.php';
renderPagination($currentPage, $totalItems, $itemsPerPage, [
    'search' => $search,
    'status' => $statusFilter
]);
?>

<?php include '../includes/footer.php'; ?>
