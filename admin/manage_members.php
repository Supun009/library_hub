<?php
// admin/manage_members.php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../includes/validation_helper.php';

requireRole('admin');

$pageTitle = 'Member Management';
$error = '';
$success = '';

// Handle Add Member Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_member') {
    $fullName = sanitizeInput($_POST['full_name']);
    $email = sanitizeInput($_POST['email']);
    $username = sanitizeInput($_POST['username']); // We'll double as student ID/username
    $password = $_POST['password'];

    $usernameValidation = validateUsername($username);
    $emailValidation = validateEmail($email);
    $passwordValidation = validatePassword($password);

    if (empty($fullName) || empty($email) || empty($username) || empty($password)) {
        $error = "All fields are required.";
    } elseif ($usernameValidation !== true) {
        $error = $usernameValidation;
    } elseif ($emailValidation !== true) {
        $error = $emailValidation;
    } elseif ($passwordValidation !== true) {
        $error = $passwordValidation;
    } else {
        try {
            $pdo->beginTransaction();

            // Check for duplicate email before proceeding (username/email uniqueness handled by DB constraints but good to check)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                 throw new Exception("Email already registered.");
            }

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
        } catch (Exception $e) { // Catch Exception to include our custom throw
            $pdo->rollBack();
            if ($e instanceof PDOException && $e->errorInfo[1] == 1062) {
                $error = "Username or Email already exists.";
            } else {
                $error = "Error: " . $e->getMessage();
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
    $countQuery .= " AND (m.full_name LIKE :search OR m.email LIKE :search OR u.username LIKE :search OR m.phone_number LIKE :search)";
}

$countStmt = $pdo->prepare($countQuery);
if ($search) {
    $countStmt->bindValue(':search', "%$search%");
}
$countStmt->execute();
$totalItems = $countStmt->fetch()['total'];

// Fetch members with pagination
$query = "
    SELECT m.member_id, m.full_name, m.email, m.phone_number, m.join_date, m.status, u.username,
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
    $query .= " AND (m.full_name LIKE :search OR m.email LIKE :search OR u.username LIKE :search OR m.phone_number LIKE :search)";
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

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h1 class="page-heading">Member Management</h1>
        <p class="text-sm text-gray-600">Register and manage library members</p>
    </div>
    <button
        data-testid="add-member-button"
        onclick="document.getElementById('addMemberForm').classList.toggle('hidden')"
        class="inline-flex items-center justify-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 transition-colors w-full sm:w-auto"
    >
        <i data-lucide="user-plus" class="h-4 w-4"></i>
        Add New Member
    </button>
</div>

<?php if ($error): ?>
    <div data-testid="error-alert" class="mb-4 rounded-md border border-red-200 bg-red-100 px-4 py-3 text-sm text-red-700">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>
<?php if ($success): ?>
    <div data-testid="success-alert" class="mb-4 rounded-md border border-green-200 bg-green-100 px-4 py-3 text-sm text-green-700">
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
                    data-testid="input-full-name"
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
                    data-testid="input-email"
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
                    data-testid="input-username"
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
                    data-testid="input-password"
                    required
                    placeholder="Default password"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-2">
            <button
                type="submit"
                data-testid="submit-register-member"
                class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 transition-colors w-full sm:w-auto"
            >
                Register Member
            </button>
            <button
                type="button"
                onclick="document.getElementById('addMemberForm').classList.add('hidden')"
                class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 transition-colors w-full sm:w-auto"
            >
                Cancel
            </button>
        </div>
    </form>
</div>

<!-- Search Bar and Filters -->
<div class="mb-6 rounded border border-gray-200 bg-white p-4 shadow-sm">
    <form method="GET" class="flex flex-col gap-3 py-2 md:flex-row md:items-center">
        <!-- Search Input -->
        <div class="relative flex-grow">
            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
            <input
                type="text"
                name="search"
                data-testid="search-members"
                value="<?php echo htmlspecialchars($search); ?>"
                placeholder="Search members by name, email, or ID..."
                class="block w-full rounded-md border border-gray-300 bg-white py-2 pl-9 pr-3 text-sm shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
            >
        </div>
        
        <!-- Status Filter -->
        <div class="relative w-full md:w-auto">
            <details class="group relative w-full md:w-[180px]">
                <summary class="flex items-center justify-between w-full cursor-pointer rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 list-none">
                    <span class="truncate">
                        <?php 
                            $statusLabels = [
                                'all' => 'All Members',
                                'active' => 'Active Only',
                                'inactive' => 'Inactive Only'
                            ];
                            echo $statusLabels[$statusFilter] ?? 'All Members';
                        ?>
                    </span>
                    <i data-lucide="chevron-down" class="h-4 w-4 text-gray-400 transition-transform group-open:rotate-180"></i>
                </summary>
                <div class="absolute right-0 z-10 mt-1 w-full min-w-[180px] origin-top-right rounded-md border border-gray-200 bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
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
    </form>
</div>

<!-- Members Table -->
<div class="table-container">
    <div class="table-header">
        <h2 class="text-lg font-semibold text-gray-900">Registered Members</h2>
        <p class="text-gray-500 text-sm mt-1">Total: <?php echo $totalItems; ?> member(s)</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="whitespace-nowrap">Name</th>
                    <th class="whitespace-nowrap">Username</th>
                    <th class="whitespace-nowrap">Email</th>
                    <th class="whitespace-nowrap">Phone</th>
                    <th class="whitespace-nowrap">Join Date</th>
                    <th class="whitespace-nowrap">Status</th>
                    <th class="whitespace-nowrap">Active Loans</th>
                    <th class="whitespace-nowrap">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($members) > 0): ?>
                    <?php foreach ($members as $member): ?>
                        <tr>
                            <td class="font-medium whitespace-nowrap"><?php echo htmlspecialchars($member['full_name']); ?></td>
                            <td class="text-gray-500 whitespace-nowrap"><?php echo htmlspecialchars($member['username']); ?></td>
                            <td class="text-gray-500 whitespace-nowrap"><?php echo htmlspecialchars($member['email']); ?></td>
                            <td class="text-gray-500 whitespace-nowrap"><?php echo htmlspecialchars($member['phone_number'] ?? '-'); ?></td>
                            <td class="text-gray-500 whitespace-nowrap"><?php echo htmlspecialchars($member['join_date']); ?></td>
                            <td class="whitespace-nowrap">
                                <span class="badge <?php echo $member['status'] === 'active' ? 'badge-green' : 'badge-red'; ?>">
                                    <?php echo ucfirst($member['status']); ?>
                                </span>
                            </td>
                            <td class="whitespace-nowrap">
                                <span class="badge <?php echo $member['active_loans'] > 0 ? 'badge-blue' : 'badge-gray'; ?>">
                                    <?php echo $member['active_loans']; ?>
                                </span>
                            </td>
                            <td class="whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <a
                                        href="<?php echo url('admin/members/history?id=' . $member['member_id']); ?>"
                                        class="inline-flex items-center justify-center rounded-md border border-blue-200 bg-white px-2 py-1 text-xs font-medium text-blue-600 hover:bg-blue-50 transition-colors"
                                        title="View Issue History"
                                    >
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </a>
                                    <a
                                        href="<?php echo url('admin/members/edit?id=' . $member['member_id']); ?>"
                                        class="inline-flex items-center justify-center rounded-md border border-indigo-200 bg-white px-2 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50 transition-colors"
                                        title="Edit Member"
                                    >
                                        <i data-lucide="edit" class="h-4 w-4"></i>
                                    </a>
                                </div>
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
require_once __DIR__ . '/../includes/pagination.php';
renderPagination($currentPage, $totalItems, $itemsPerPage, [
    'search' => $search,
    'status' => $statusFilter
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
