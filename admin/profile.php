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
        // Admin doesn't have a 'members' entry in this schema usually, but let's assume they might or we update 'users' table or a separate 'admins' table?
        // Wait, the schema allows admins to be in 'users'. But where is their specific data?
        // The screenshot implies "Sarah Johnson".
        // Let's assume for this demo that we are updating a row in 'members' if the admin is also a member, OR we just update 'users' table for password and maybe create a generic profiles table?
        // Actually, let's just stick to updating the 'members' table if the user exists there, otherwise we might need an 'admins' table.
        // Simplified: We'll assume the logged-in admin has a record in 'members' for their profile info (Name, Email, Phone).
        // If not, we'll check 'users'.
        // For the sake of the UI matching, let's upsert into 'members' for the admin for now or check if they exist.
        
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
        $maxBooks = $_POST['max_books_per_member'];
        
        try {
            $pdo->beginTransaction();
            $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('fine_per_day', ?)")->execute([$finePerDay]);
            $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('loan_period_days', ?)")->execute([$loanPeriod]);
            $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('max_books_per_member', ?)")->execute([$maxBooks]);
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
    <div class="mb-6 rounded <?php echo $messageType === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 border-red-200'; ?> p-4">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Sidebar Navigation -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded shadow-sm border border-gray-200 p-2">
            <nav class="space-y-1">
                <a href="?tab=profile" class="flex items-center gap-3 px-4 py-3 rounded text-sm font-medium transition-colors <?php echo $activeTab === 'profile' ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50'; ?>">
                    <i data-lucide="user" style="width: 18px;"></i>
                    Profile
                </a>
                <a href="?tab=password" class="flex items-center gap-3 px-4 py-3 rounded text-sm font-medium transition-colors <?php echo $activeTab === 'password' ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50'; ?>">
                    <i data-lucide="lock" style="width: 18px;"></i>
                    Password
                </a>
                <a href="?tab=settings" class="flex items-center gap-3 px-4 py-3 rounded text-sm font-medium transition-colors <?php echo $activeTab === 'settings' ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50'; ?>">
                    <i data-lucide="settings" style="width: 18px;"></i>
                    System Settings
                </a>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div class="lg:col-span-3">
        <div class="bg-white rounded shadow-sm border border-gray-200 p-6">
            
            <!-- Profile Section -->
            <?php if ($activeTab === 'profile'): ?>
                <div class="flex items-center gap-3 mb-6">
                    <i data-lucide="user" class="text-indigo-600" style="width: 24px; height: 24px;"></i>
                    <h2 class="text-xl text-gray-900 font-semibold">Profile Information</h2>
                </div>

                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Full Name</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($userProfile['full_name'] ?? ''); ?>" class="form-control" required>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($userProfile['email'] ?? ''); ?>" class="form-control" required>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Phone Number</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($userProfile['phone_number'] ?? ''); ?>" class="form-control" placeholder="+1 234-567-8900">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Employee ID (Username)</label>
                            <input type="text" value="<?php echo htmlspecialchars($userProfile['username']); ?>" class="form-control bg-gray-50 text-gray-500" disabled>
                        </div>
                        <div class="md:col-span-2">
                             <label class="block text-sm text-gray-700 mb-1">Role</label>
                             <input type="text" value="<?php echo ucfirst($userProfile['role_name']); ?>" class="form-control bg-gray-50 text-gray-500" disabled>
                        </div>
                    </div>
                    <div class="flex justify-end pt-4">
                        <button type="submit" class="btn btn-primary">
                            <i data-lucide="save" style="width: 18px;"></i>
                            Save Changes
                        </button>
                    </div>
                </form>

            <!-- Password Section -->
            <?php elseif ($activeTab === 'password'): ?>
                <div class="flex items-center gap-3 mb-6">
                    <i data-lucide="lock" class="text-indigo-600" style="width: 24px; height: 24px;"></i>
                    <h2 class="text-xl text-gray-900 font-semibold">Change Password</h2>
                </div>

                <form method="POST" class="max-w-md space-y-4">
                    <input type="hidden" name="action" value="change_password">
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required placeholder="Enter current password">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">New Password</label>
                        <input type="password" name="new_password" class="form-control" required placeholder="Enter new password">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required placeholder="Confirm new password">
                    </div>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded p-4 mt-4">
                        <h3 class="text-sm font-semibold text-blue-900 mb-2">Password Requirements</h3>
                        <ul class="text-sm text-blue-800 list-disc pl-5">
                            <li>At least 8 characters long</li>
                            <li>Include uppercase & lowercase letters</li>
                            <li>Include numbers</li>
                        </ul>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button type="submit" class="btn btn-primary">
                            <i data-lucide="save" style="width: 18px;"></i>
                            Update Password
                        </button>
                    </div>
                </form>

            <!-- System Settings Section -->
            <?php elseif ($activeTab === 'settings'): ?>
                <div class="flex items-center gap-3 mb-6">
                    <i data-lucide="settings" class="text-indigo-600" style="width: 24px; height: 24px;"></i>
                    <h2 class="text-xl text-gray-900 font-semibold">System Settings</h2>
                </div>

                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <div>
                        <h3 class="text-base text-gray-900 font-medium mb-3">Library Policies</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Fine Per Day ($)</label>
                                <input type="number" step="0.01" name="fine_per_day" value="<?php echo htmlspecialchars($settings['fine_per_day'] ?? '0.50'); ?>" class="form-control">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Max Books Per Member</label>
                                <input type="number" name="max_books_per_member" value="<?php echo htmlspecialchars($settings['max_books_per_member'] ?? '5'); ?>" class="form-control">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Loan Period (Days)</label>
                                <input type="number" name="loan_period_days" value="<?php echo htmlspecialchars($settings['loan_period_days'] ?? '14'); ?>" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4 border-t border-gray-200">
                        <button type="submit" class="btn btn-primary">
                            <i data-lucide="save" style="width: 18px;"></i>
                            Save Settings
                        </button>
                    </div>
                </form>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
