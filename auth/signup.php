<?php
// auth/signup.php
require_once '../config/db_config.php';
require_once '../includes/auth_middleware.php';

if (isLoggedIn()) {
    header("Location: /lib_system/library_system/index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $role = $_POST['role']; // For demonstration purposes, allowing role selection
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($username) || empty($password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if username exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            $error = "Username already taken.";
        } else {
            // Register user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role_id = ($role === 'admin') ? 1 : 2;

            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("INSERT INTO users (username, password, role_id) VALUES (?, ?, ?)");
                $stmt->execute([$username, $hashed_password, $role_id]);
                $user_id = $pdo->lastInsertId();

                // If member, create member entry stub
                if ($role_id == 2) {
                    $stmt = $pdo->prepare("INSERT INTO members (user_id, full_name, email) VALUES (?, ?, ?)");
                    // Using placeholder data since we just want a basic signup flow for now
                    $stmt->execute([$user_id, $username, $username . '@example.com']); 
                }

                $pdo->commit();
                
                // Auto login
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['role_id'] = $role_id;
                
                header("Location: /lib_system/library_system/index.php");
                exit();

            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Registration failed: " . $e->getMessage();
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
    <link rel="stylesheet" href="/lib_system/library_system/assets/css/style.css">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-header">
            <h1>Create Account</h1>
            <p class="text-light">Get started with your library account</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="role" class="form-label">Role</label>
                <select name="role" id="role" class="form-control">
                    <option value="member">Member</option>
                    <option value="admin">Admin (Demo)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">Sign Up</button>
            
            <div class="text-center mt-4">
                <span class="text-sm">Already have an account? <a href="login.php" class="text-indigo">Sign in</a></span>
            </div>
        </form>
    </div>
</body>
</html>
