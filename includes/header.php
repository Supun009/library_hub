<!-- includes/header.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System</title>
    <link rel="stylesheet" href="/lib_system/library_system/assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <div class="main-content">
            <header class="top-header">
                <div class="page-title">
                    <h2><?php echo isset($pageTitle) ? $pageTitle : 'Library System'; ?></h2>
                </div>
                <div class="user-menu">
                    <span class="user-name">Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                    <a href="/lib_system/library_system/auth/logout.php" class="btn-logout">Logout</a>
                </div>
            </header>
            
            <div class="content">
