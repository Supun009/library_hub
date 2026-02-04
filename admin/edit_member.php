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
        try {
            $stmt = $pdo->prepare("UPDATE members SET status = ? WHERE member_id = ?");
            $stmt->execute([$newStatus, $memberId]);
            $success = "Member status updated to " . ucfirst($newStatus);
        } catch (PDOException $e) {
            $error = "Error updating status: " . $e->getMessage();
        }
    } elseif ($action === 'delete_member') {
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

// Fetch Member Data
$stmt = $pdo->prepare("
    SELECT m.*, u.username 
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

<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl text-gray-900 mb-1">Edit Member</h1>
        <p class="text-gray-600">Update details for <?php echo htmlspecialchars($member['full_name']); ?></p>
    </div>
    <a href="manage_members.php" class="btn" style="background: #e5e7eb; color: #374151;">
        <i data-lucide="arrow-left"></i>
        Back to List
    </a>
</div>

<?php if ($error): ?>
    <div class="mb-4 p-3 bg-red-100 text-red-700 rounded border border-red-200"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="mb-4 p-3 bg-green-100 text-green-700 rounded border border-green-200"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Edit User Details Form -->
    <div class="lg:col-span-2">
        <div class="bg-white p-6 rounded shadow-sm border border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Personal Information</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_details">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Full Name</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($member['full_name']); ?>" class="form-control" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($member['email']); ?>" class="form-control" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Phone Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($member['phone_number'] ?? ''); ?>" class="form-control">
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Actions Card -->
    <div class="lg:col-span-1 space-y-6">
        <!-- Status Card -->
        <div class="bg-white p-6 rounded shadow-sm border border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Account Status</h2>
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm text-gray-600">Current Status:</span>
                <span class="badge <?php echo $member['status'] === 'active' ? 'badge-green' : 'badge-red'; ?>">
                    <?php echo ucfirst($member['status']); ?>
                </span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="toggle_status">
                <?php if ($member['status'] === 'active'): ?>
                    <input type="hidden" name="status" value="inactive">
                    <button type="submit" class="btn w-full text-red-700 border border-red-200 hover:bg-red-50">
                        <i data-lucide="ban"></i> Deactivate Account
                    </button>
                <?php else: ?>
                    <input type="hidden" name="status" value="active">
                    <button type="submit" class="btn w-full text-green-700 border border-green-200 hover:bg-green-50">
                        <i data-lucide="check-circle"></i> Activate Account
                    </button>
                <?php endif; ?>
            </form>
        </div>

        <!-- Delete Card -->
        <div class="bg-white p-6 rounded shadow-sm border border-red-200">
            <h2 class="text-lg font-semibold text-red-700 mb-2">Delete Member</h2>
            <p class="text-sm text-gray-500 mb-4">This will soft-delete the member. They will no longer appear in active lists.</p>
            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this member?');">
                <input type="hidden" name="action" value="delete_member">
                <button type="submit" class="btn w-full bg-red-600 text-white hover:bg-red-700">
                    <i data-lucide="trash-2"></i> Delete Member
                </button>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
