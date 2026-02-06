<?php
// auth/login.php
require_once '../config/db_config.php';
require_once '../includes/auth_middleware.php';
require_once '../includes/validation_helper.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (hasRole('admin')) redirect(adminUrl('dashboard.php'));
    else redirect(memberUrl('index.php'));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT user_id, username, password, role_id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Login Success
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_id'] = $user['role_id'];

            if ($user['role_id'] == 1) { // Admin
                redirect(adminUrl('dashboard.php'));
            } else { // Member
                redirect(memberUrl('index.php'));
            }
        } else {
            $error = "Invalid credentials.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Library System</title>

    <!-- Tailwind CSS (step 1: add alongside existing styles, do not remove old CSS yet) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Existing custom stylesheet (kept for now to avoid breaking layout) -->
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="mb-6 text-center">
            <h1 class="text-2xl font-semibold text-gray-900">Library Portal</h1>
            <p class="mt-1 text-sm text-gray-500">Sign in to your account</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-4 rounded-md border border-red-200 bg-red-100 px-4 py-3 text-sm text-red-700">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-4">
            <div>
                <label for="username" class="mb-1 block text-sm font-medium text-gray-700">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    data-testid="login-username"
                    required
                    autofocus
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-gray-700">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    data-testid="login-password"
                    required
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
            </div>

            <button
                type="submit"
                data-testid="login-submit"
                class="mt-2 inline-flex w-full items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 transition-colors"
            >
                Sign In
            </button>

            <div class="mt-4 text-center text-sm text-gray-600">
                Don't have an account?
                <a href="signup.php" class="font-medium text-indigo-600 hover:text-indigo-700">Sign up</a>
            </div>
        </form>
    </div>
</body>
</html>
