<?php
// admin/edit_member.php
require_once '../config/db_config.php';
require_once '../includes/auth_middleware.php';

requireRole('admin');

$memberId = $_GET['id'] ?? null;
if (!$memberId) {
    header("Location: manage_members.php");
    exit;
}

$pageTitle = 'Edit Member';
$error = '';
$success = '';

// Handle Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_details') {
        $fullName = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        
        if (empty($fullName) || empty($email)) {
            $error = "Name and Email are required.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE members SET full_name = ?, email = ?, phone_number = ? WHERE member_id = ?");
                $stmt->execute([$fullName, $email, $phone, $memberId]);
                $success = "Member details updated.";
            } catch (PDOException $e) {
                $error = "Error updating details: " . $e->getMessage();
            }
        }
    } elseif ($action === 'toggle_status') {
        $newStatus = $_POST['status'];
        
        // Check if member has issued books before deactivating
        if ($newStatus === 'inactive') {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM issues WHERE member_id = ? AND return_date IS NULL");
            $stmt->execute([$memberId]);
            $issuedCount = $stmt->fetch()['count'];
            
            if ($issuedCount > 0) {
                $error = "Cannot deactivate member. They have $issuedCount unreturned book(s). Please ensure all books are returned first.";
            } else {
                try {
                    $stmt = $pdo->prepare("UPDATE members SET status = ? WHERE member_id = ?");
                    $stmt->execute([$newStatus, $memberId]);
                    $success = "Member account deactivated successfully.";
                } catch (PDOException $e) {
                    $error = "Error updating status: " . $e->getMessage();
                }
            }
        } else {
            // Activating member
            try {
                $stmt = $pdo->prepare("UPDATE members SET status = ? WHERE member_id = ?");
                $stmt->execute([$newStatus, $memberId]);
                $success = "Member account activated successfully.";
            } catch (PDOException $e) {
                $error = "Error updating status: " . $e->getMessage();
            }
        }
    } elseif ($action === 'delete_member') {
        // Check if member has issued books before deleting
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM issues WHERE member_id = ? AND return_date IS NULL");
        $stmt->execute([$memberId]);
        $issuedCount = $stmt->fetch()['count'];
        
        if ($issuedCount > 0) {
            $error = "Cannot delete member. They have $issuedCount unreturned book(s). Please ensure all books are returned first.";
        } else {
            try {
                // Soft delete
                $stmt = $pdo->prepare("UPDATE members SET deleted_at = CURRENT_TIMESTAMP WHERE member_id = ?");
                $stmt->execute([$memberId]);
                header("Location: manage_members.php?msg=member_deleted");
                exit;
            } catch (PDOException $e) {
                $error = "Error deleting member: " . $e->getMessage();
            }
        }
    }
}

// Fetch Member Data with issued books count
$stmt = $pdo->prepare("
    SELECT m.*, u.username,
           (SELECT COUNT(*) FROM issues WHERE member_id = m.member_id AND return_date IS NULL) as issued_books_count,
           (SELECT COUNT(*) FROM issues WHERE member_id = m.member_id AND return_date IS NULL AND due_date < CURDATE()) as overdue_books_count
    FROM members m 
    JOIN users u ON m.user_id = u.user_id 
    WHERE m.member_id = ? AND m.deleted_at IS NULL
");
$stmt->execute([$memberId]);
$member = $stmt->fetch();

if (!$member) {
    die("Member not found or deleted.");
}

include '../includes/header.php';
?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="mb-1 text-2xl font-semibold text-gray-900">Edit Member</h1>
        <p class="text-sm text-gray-600">Update details for <?php echo htmlspecialchars($member['full_name']); ?></p>
    </div>
    <a
        href="manage_members.php"
        class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 transition-colors"
    >
        <i data-lucide="arrow-left" class="h-4 w-4"></i>
        Back to List
    </a>
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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Edit User Details Form -->
    <div class="lg:col-span-2">
        <div class="rounded border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">Personal Information</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_details">
                <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Full Name</label>
                        <input
                            type="text"
                            name="full_name"
                            value="<?php echo htmlspecialchars($member['full_name']); ?>"
                            required
                            class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                        >
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Email Address</label>
                        <input
                            type="email"
                            name="email"
                            value="<?php echo htmlspecialchars($member['email']); ?>"
                            required
                            class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                        >
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Phone Number</label>
                        <input
                            type="text"
                            name="phone"
                            value="<?php echo htmlspecialchars($member['phone_number'] ?? ''); ?>"
                            class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                        >
                    </div>
                </div>
                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 transition-colors"
                    >
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Actions Card -->
    <div class="lg:col-span-1 space-y-6">
        <!-- Status Card -->
        <div class="rounded border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">Account Status</h2>
            
            <!-- Current Status -->
            <div class="mb-4 flex items-center justify-between">
                <span class="text-sm text-gray-600">Current Status:</span>
                <?php
                    $statusClass = 'badge-gray';
                    if ($member['status'] === 'active') {
                        $statusClass = 'badge-green';
                    } elseif ($member['status'] === 'suspended') {
                        $statusClass = 'badge-yellow';
                    } elseif ($member['status'] === 'inactive') {
                        $statusClass = 'badge-red';
                    }
                ?>
                <span class="badge <?php echo $statusClass; ?>">
                    <?php echo ucfirst($member['status']); ?>
                </span>
            </div>
            
            <!-- Suspended Notice -->
            <?php if ($member['status'] === 'suspended'): ?>
                <div class="mb-4 rounded-md border border-orange-200 bg-orange-50 p-3">
                    <div class="flex items-start gap-2">
                        <i data-lucide="alert-triangle" class="h-4 w-4 text-orange-600 mt-0.5"></i>
                        <div class="text-sm">
                            <p class="font-medium text-orange-800">Auto-Suspended</p>
                            <p class="text-orange-700 mt-1">Member has long overdue books</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Issued Books Info -->
            <?php if ($member['issued_books_count'] > 0): ?>
                <div class="mb-4 rounded-md border border-yellow-200 bg-yellow-50 p-3">
                    <div class="flex items-start gap-2">
                        <i data-lucide="alert-circle" class="h-4 w-4 text-yellow-600 mt-0.5"></i>
                        <div class="text-sm">
                            <p class="font-medium text-yellow-800">
                                <?php echo $member['issued_books_count']; ?> Unreturned Book(s)
                            </p>
                            <?php if ($member['overdue_books_count'] > 0): ?>
                                <p class="text-yellow-700 mt-1">
                                    <?php echo $member['overdue_books_count']; ?> Overdue
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="action" value="toggle_status">
                
                <?php if ($member['status'] === 'active'): ?>
                    <!-- Active → Deactivate -->
                    <input type="hidden" name="status" value="inactive">
                    <?php if ($member['issued_books_count'] > 0): ?>
                        <!-- Disabled button with tooltip -->
                        <div class="relative group">
                            <button
                                type="button"
                                disabled
                                class="inline-flex w-full items-center justify-center gap-2 rounded-md border border-gray-200 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-400 cursor-not-allowed"
                            >
                                <i data-lucide="ban" class="h-4 w-4"></i>
                                Deactivate Account
                            </button>
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-64 p-2 bg-gray-900 text-white text-xs rounded shadow-lg z-10">
                                Cannot deactivate. Member has <?php echo $member['issued_books_count']; ?> unreturned book(s).
                            </div>
                        </div>
                    <?php else: ?>
                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-md border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50 transition-colors"
                        >
                            <i data-lucide="ban" class="h-4 w-4"></i>
                            Deactivate Account
                        </button>
                    <?php endif; ?>
                    
                <?php elseif ($member['status'] === 'suspended'): ?>
                    <!-- Suspended → Activate (Unsuspend) -->
                    <input type="hidden" name="status" value="active">
                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-md border border-green-200 bg-white px-4 py-2 text-sm font-medium text-green-700 hover:bg-green-50 transition-colors"
                    >
                        <i data-lucide="check-circle" class="h-4 w-4"></i>
                        Unsuspend & Activate
                    </button>
                    <p class="mt-2 text-xs text-gray-500">
                        Note: Member should return overdue books first
                    </p>
                    
                <?php else: ?>
                    <!-- Inactive → Activate -->
                    <input type="hidden" name="status" value="active">
                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-md border border-green-200 bg-white px-4 py-2 text-sm font-medium text-green-700 hover:bg-green-50 transition-colors"
                    >
                        <i data-lucide="check-circle" class="h-4 w-4"></i>
                        Activate Account
                    </button>
                <?php endif; ?>
            </form>
        </div>

        <!-- Delete Card -->
        <div class="rounded border border-red-200 bg-white p-6 shadow-sm">
            <h2 class="mb-2 text-lg font-semibold text-red-700">Delete Member</h2>
            <p class="mb-4 text-sm text-gray-500">This will soft-delete the member. They will no longer appear in active lists.</p>
            
            <?php if ($member['issued_books_count'] > 0): ?>
                <!-- Disabled delete button -->
                <div class="relative group">
                    <button
                        type="button"
                        disabled
                        class="inline-flex w-full items-center justify-center gap-2 rounded-md bg-gray-300 px-4 py-2 text-sm font-medium text-gray-500 cursor-not-allowed"
                    >
                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                        Delete Member
                    </button>
                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block w-64 p-2 bg-gray-900 text-white text-xs rounded shadow-lg z-10">
                        Cannot delete. Member has <?php echo $member['issued_books_count']; ?> unreturned book(s).
                    </div>
                </div>
            <?php else: ?>
                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this member?');">
                    <input type="hidden" name="action" value="delete_member">
                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 transition-colors"
                    >
                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                        Delete Member
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
