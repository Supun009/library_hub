<?php
// auth/login.php
require_once '../config/db_config.php';
require_once '../includes/auth_middleware.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (hasRole('admin')) header("Location: /lib_system/library_system/admin/dashboard.php");
    else header("Location: /lib_system/library_system/member/index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
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
                header("Location: /lib_system/library_system/admin/dashboard.php");
            } else { // Member
                header("Location: /lib_system/library_system/member/index.php");
            }
            exit();
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
    <link rel="stylesheet" href="/lib_system/library_system/assets/css/style.css">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-header">
            <h1>Library Portal</h1>
            <p class="text-light">Sign in to your account</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username" class="form-control" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            
            <div class="text-center mt-4">
                <span class="text-sm">Don't have an account? <a href="signup.php" class="text-indigo">Sign up</a></span>
            </div>
        </form>
    </div>
</body>
</html>
