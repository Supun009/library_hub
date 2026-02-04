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

// Fetch Members
$search = $_GET['search'] ?? '';
$query = "
    SELECT m.member_id, m.full_name, m.email, m.join_date, u.username,
           (SELECT COUNT(*) FROM issues i WHERE i.member_id = m.member_id AND i.return_date IS NULL) as active_loans
    FROM members m
    JOIN users u ON m.user_id = u.user_id
";

if ($search) {
    $query .= " WHERE m.full_name LIKE :search OR m.email LIKE :search OR u.username LIKE :search";
}

$query .= " ORDER BY m.member_id DESC";
$stmt = $pdo->prepare($query);

if ($search) {
    $stmt->bindValue(':search', "%$search%");
}
$stmt->execute();
$members = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl text-gray-900 mb-1">Member Management</h1>
        <p class="text-gray-600">Register and manage library members</p>
    </div>
    <button onclick="document.getElementById('addMemberForm').classList.toggle('hidden')" class="btn btn-primary">
        <i data-lucide="user-plus"></i>
        Add New Member
    </button>
</div>

<?php if ($error): ?>
    <div class="mb-4 p-3 bg-red-100 text-red-700 rounded border border-red-200"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="mb-4 p-3 bg-green-100 text-green-700 rounded border border-green-200"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<!-- Add Member Form (Hidden by default) -->
<div id="addMemberForm" class="hidden mb-6 bg-white p-6 rounded shadow-sm border border-gray-200">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Register New Member</h2>
    <form method="POST">
        <input type="hidden" name="action" value="add_member">
        <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1rem;">
            <div>
                <label class="block text-sm text-gray-700 mb-1">Full Name</label>
                <input type="text" name="full_name" class="form-control" required placeholder="e.g. John Doe">
            </div>
            <div>
                <label class="block text-sm text-gray-700 mb-1">Email</label>
                <input type="email" name="email" class="form-control" required placeholder="john@example.com">
            </div>
            <div>
                <label class="block text-sm text-gray-700 mb-1">Username / Student ID</label>
                <input type="text" name="username" class="form-control" required placeholder="e.g. STU001">
            </div>
            <div>
                <label class="block text-sm text-gray-700 mb-1">Password</label>
                <input type="password" name="password" class="form-control" required placeholder="Default password">
            </div>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary">Register Member</button>
            <button type="button" onclick="document.getElementById('addMemberForm').classList.add('hidden')" class="btn" style="background: #e5e7eb; color: #374151;">Cancel</button>
        </div>
    </form>
</div>

<!-- Search Bar -->
<div class="mb-6 bg-white p-4 rounded shadow-sm border border-gray-200">
    <form method="GET" class="header-search" style="margin: 0; max-width: 100%;">
        <i data-lucide="search"></i>
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search members by name, email, or ID...">
    </form>
</div>

<!-- Members Table -->
<div class="table-container">
    <div class="table-header">
        <h2 class="text-lg font-semibold text-gray-900">Registered Members</h2>
        <p class="text-gray-500 text-sm mt-1">Total: <?php echo count($members); ?> member(s)</p>
    </div>
    <div class="overflow-x-auto">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Student ID</th>
                    <th>Email</th>
                    <th>Join Date</th>
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
                                <span class="badge <?php echo $member['active_loans'] > 0 ? 'badge-blue' : 'badge-gray'; ?>">
                                    <?php echo $member['active_loans']; ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn" style="padding: 0.25rem 0.5rem; color: var(--primary-color);">
                                    <i data-lucide="edit" style="width: 16px; height: 16px;"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center p-6 text-gray-500">No members found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
