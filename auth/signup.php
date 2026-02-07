<?php
// auth/signup.php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

require_once __DIR__ . '/../includes/validation_helper.php';

if (isLoggedIn()) {
    redirect(url('index.php'));
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = sanitizeInput($_POST['full_name']);
    $username = trim($_POST['username']); // Don't fully sanitize username yet to check for invalid chars correctly, but trim is good.
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validations
    $usernameValidation = validateUsername($username);
    $emailValidation = validateEmail($email);
    $passwordValidation = validatePassword($password);

    if (empty($fullName) || empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif ($usernameValidation !== true) {
        $error = $usernameValidation;
    } elseif ($emailValidation !== true) {
        $error = $emailValidation;
    } elseif ($passwordValidation !== true) {
        $error = $passwordValidation;
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if username or email exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            $error = "Username already taken.";
        } else {
            $stmt = $pdo->prepare("SELECT member_id FROM members WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $error = "Email already registered.";
            } else {
                // Register user (always as member)
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Dynamically fetch member role ID
                $stmt = $pdo->prepare("SELECT role_id FROM roles WHERE role_name = 'member'");
                $stmt->execute();
                $role = $stmt->fetch();

                if (!$role) {
                    $error = "System configuration error: 'member' role not found in database.";
                } else {
                    $role_id = $role['role_id'];

                    try {
                        $pdo->beginTransaction();

                        $stmt = $pdo->prepare("INSERT INTO users (username, password, role_id) VALUES (?, ?, ?)");
                        $stmt->execute([$username, $hashed_password, $role_id]);
                        $user_id = $pdo->lastInsertId();

                        // Create member entry
                        $stmt = $pdo->prepare("INSERT INTO members (user_id, full_name, email) VALUES (?, ?, ?)");
                        $stmt->execute([$user_id, $fullName, $email]);

                        $pdo->commit();
                        
                        // Auto login
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['username'] = $username;
                        $_SESSION['role_id'] = $role_id;
                        
                        redirect(url('/'));

                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $error = "Registration failed: " . $e->getMessage();
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Library System</title>

    <!-- Tailwind CSS (step 1: add alongside existing styles, do not remove old CSS yet) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Existing custom stylesheet (kept for now to avoid breaking layout) -->
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="mb-6 text-center">
            <h1 class="text-2xl font-semibold text-gray-900">Create Account</h1>
            <p class="mt-1 text-sm text-gray-500">Get started with your library account</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-4 rounded-md border border-red-200 bg-red-100 px-4 py-3 text-sm text-red-700">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-4">
            <div>
                <label for="full_name" class="mb-1 block text-sm font-medium text-gray-700">Full Name</label>
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    required
                    placeholder="John Doe"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
            </div>

            <div>
                <label for="username" class="mb-1 block text-sm font-medium text-gray-700">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    required
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
            </div>

            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-gray-700">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    placeholder="your.email@example.com"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-gray-700">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
            </div>

            <div>
                <label for="confirm_password" class="mb-1 block text-sm font-medium text-gray-700">Confirm Password</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    required
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
            </div>
            
            <button
                type="submit"
                class="mt-2 inline-flex w-full items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 transition-colors"
            >
                Sign Up
            </button>
            
            <div class="mt-4 text-center text-sm text-gray-600">
                Already have an account?
                <a href="<?php echo url('login'); ?>" class="font-medium text-indigo-600 hover:text-indigo-700">Sign in</a>
            </div>
        </form>
    </div>
</body>
</html>
