<?php
// admin/profile.php
require_once '../config/db_config.php';
require_once '../includes/auth_middleware.php';

requireRole('admin');

$pageTitle = 'Profile & Settings';
$currentUserId = $_SESSION['user_id'];
$activeTab = $_GET['tab'] ?? 'profile';
$message = '';
$messageType = '';

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {        
        $fullName = $_POST['full_name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        
        try {
            // Check if member record exists for this user
            $stmt = $pdo->prepare("SELECT member_id FROM members WHERE user_id = ?");
            $stmt->execute([$currentUserId]);
            $memberExists = $stmt->fetchColumn();
            
            if ($memberExists) {
                $stmt = $pdo->prepare("UPDATE members SET full_name = ?, email = ?, phone_number = ? WHERE user_id = ?");
                $stmt->execute([$fullName, $email, $phone, $currentUserId]);
            } else {
                // Insert new member record for this admin
                $stmt = $pdo->prepare("INSERT INTO members (user_id, full_name, email, phone_number) VALUES (?, ?, ?, ?)");
                $stmt->execute([$currentUserId, $fullName, $email, $phone]);
            }
            $message = "Profile updated successfully.";
            $messageType = "success";
        } catch (PDOException $e) {
            $message = "Error updating profile: " . $e->getMessage();
            $messageType = "error";
        }
    } elseif ($action === 'change_password') {
        $currentPwd = $_POST['current_password'];
        $newPwd = $_POST['new_password'];
        $confirmPwd = $_POST['confirm_password'];
        
        if ($newPwd !== $confirmPwd) {
            $message = "New passwords do not match.";
            $messageType = "error";
        } else {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmt->execute([$currentUserId]);
            $hash = $stmt->fetchColumn();
            
            if (password_verify($currentPwd, $hash)) {
                $newHash = password_hash($newPwd, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->execute([$newHash, $currentUserId]);
                $message = "Password changed successfully.";
                $messageType = "success";
            } else {
                $message = "Incorrect current password.";
                $messageType = "error";
            }
        }
        $activeTab = 'password';
    } elseif ($action === 'update_settings') {
        $finePerDay = $_POST['fine_per_day'];
        $loanPeriod = $_POST['loan_period_days'];
        
        try {
            $pdo->beginTransaction();
            $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('fine_per_day', ?)")->execute([$finePerDay]);
            $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('loan_period_days', ?)")->execute([$loanPeriod]);
            $pdo->commit();
            $message = "System settings updated.";
            $messageType = "success";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = "Error updating settings: " . $e->getMessage();
            $messageType = "error";
        }
        $activeTab = 'settings';
    }
}

// Fetch Data
// Profile
$stmt = $pdo->prepare("SELECT m.full_name, m.email, m.phone_number, u.username, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id LEFT JOIN members m ON u.user_id = m.user_id WHERE u.user_id = ?");
$stmt->execute([$currentUserId]);
$userProfile = $stmt->fetch();

// Settings
$settings = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);

include '../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl text-gray-900 mb-1">Profile & Settings</h1>
    <p class="text-gray-600">Manage your account and system settings</p>
</div>

<?php if ($message): ?>
    <div class="mb-6 rounded-md border p-4 text-sm <?php echo $messageType === 'success'
        ? 'border-green-200 bg-green-100 text-green-700'
        : 'border-red-200 text-red-700'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Sidebar Navigation -->
    <div class="lg:col-span-1">
        <?php include __DIR__ . '/components/profile_sidebar.php'; ?>
    </div>

    <!-- Main Content -->
    <div class="lg:col-span-3">
        <div class="rounded border border-gray-200 bg-white p-6 shadow-sm">
            <?php
            // Render the appropriate component based on active tab
            if ($activeTab === 'profile') {
                include __DIR__ . '/components/profile_form.php';
            } elseif ($activeTab === 'password') {
                include __DIR__ . '/components/password_form.php';
            } elseif ($activeTab === 'settings') {
                include __DIR__ . '/components/settings_form.php';
            }
            ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
