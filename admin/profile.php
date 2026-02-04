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
    <div class="mb-6 rounded-md border p-4 text-sm <?php echo $messageType === 'success'
        ? 'border-green-200 bg-green-100 text-green-700'
        : 'border-red-200 text-red-700'; ?>">
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
        <div class="rounded border border-gray-200 bg-white p-6 shadow-sm">
            
            <!-- Profile Section -->
            <?php if ($activeTab === 'profile'): ?>
                <div class="mb-6 flex items-center gap-3">
                    <i data-lucide="user" class="h-6 w-6 text-indigo-600"></i>
                    <h2 class="text-xl font-semibold text-gray-900">Profile Information</h2>
                </div>

                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Full Name</label>
                            <input
                                type="text"
                                name="full_name"
                                value="<?php echo htmlspecialchars($userProfile['full_name'] ?? ''); ?>"
                                required
                                class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                            >
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Email Address</label>
                            <input
                                type="email"
                                name="email"
                                value="<?php echo htmlspecialchars($userProfile['email'] ?? ''); ?>"
                                required
                                class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                            >
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Phone Number</label>
                            <input
                                type="text"
                                name="phone"
                                value="<?php echo htmlspecialchars($userProfile['phone_number'] ?? ''); ?>"
                                placeholder="+1 234-567-8900"
                                class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                            >
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Employee ID (Username)</label>
                            <input
                                type="text"
                                value="<?php echo htmlspecialchars($userProfile['username']); ?>"
                                disabled
                                class="block w-full cursor-not-allowed rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500"
                            >
                        </div>
                        <div class="md:col-span-2">
                             <label class="mb-1 block text-sm font-medium text-gray-700">Role</label>
                             <input
                                type="text"
                                value="<?php echo ucfirst($userProfile['role_name']); ?>"
                                disabled
                                class="block w-full cursor-not-allowed rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500"
                             >
                        </div>
                    </div>
                    <div class="flex justify-end pt-4">
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 transition-colors"
                        >
                            <i data-lucide="save" class="h-4 w-4"></i>
                            Save Changes
                        </button>
                    </div>
                </form>

            <!-- Password Section -->
            <?php elseif ($activeTab === 'password'): ?>
                <div class="mb-6 flex items-center gap-3">
                    <i data-lucide="lock" class="h-6 w-6 text-indigo-600"></i>
                    <h2 class="text-xl font-semibold text-gray-900">Change Password</h2>
                </div>

                <form method="POST" class="max-w-md space-y-4">
                    <input type="hidden" name="action" value="change_password">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Current Password</label>
                        <input
                            type="password"
                            name="current_password"
                            required
                            placeholder="Enter current password"
                            class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                        >
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">New Password</label>
                        <input
                            type="password"
                            name="new_password"
                            required
                            placeholder="Enter new password"
                            class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                        >
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Confirm New Password</label>
                        <input
                            type="password"
                            name="confirm_password"
                            required
                            placeholder="Confirm new password"
                            class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                        >
                    </div>
                    
                    <div class="mt-4 rounded border border-blue-200 bg-blue-50 p-4">
                        <h3 class="mb-2 text-sm font-semibold text-blue-900">Password Requirements</h3>
                        <ul class="list-disc pl-5 text-sm text-blue-800">
                            <li>At least 8 characters long</li>
                            <li>Include uppercase & lowercase letters</li>
                            <li>Include numbers</li>
                        </ul>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 transition-colors"
                        >
                            <i data-lucide="save" class="h-4 w-4"></i>
                            Update Password
                        </button>
                    </div>
                </form>

            <!-- System Settings Section -->
            <?php elseif ($activeTab === 'settings'): ?>
                <div class="mb-6 flex items-center gap-3">
                    <i data-lucide="settings" class="h-6 w-6 text-indigo-600"></i>
                    <h2 class="text-xl font-semibold text-gray-900">System Settings</h2>
                </div>

                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <div>
                        <h3 class="text-base text-gray-900 font-medium mb-3">Library Policies</h3>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Fine Per Day ($)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    name="fine_per_day"
                                    value="<?php echo htmlspecialchars($settings['fine_per_day'] ?? '0.50'); ?>"
                                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                                >
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Max Books Per Member</label>
                                <input
                                    type="number"
                                    name="max_books_per_member"
                                    value="<?php echo htmlspecialchars($settings['max_books_per_member'] ?? '5'); ?>"
                                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                                >
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700">Loan Period (Days)</label>
                                <input
                                    type="number"
                                    name="loan_period_days"
                                    value="<?php echo htmlspecialchars($settings['loan_period_days'] ?? '14'); ?>"
                                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                                >
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end border-t border-gray-200 pt-4">
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 transition-colors"
                        >
                            <i data-lucide="save" class="h-4 w-4"></i>
                            Save Settings
                        </button>
                    </div>
                </form>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
