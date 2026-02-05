<!-- includes/header.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System</title>

    <!-- Tailwind CSS (step 1: add alongside existing styles, do not remove old CSS yet) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Existing custom stylesheet (kept for now to avoid breaking layout) -->
    <link rel="stylesheet" href="/lib_system/library_system/assets/css/style.css">

    <!-- Unpkg for Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Global Search Script -->
    <script src="/lib_system/library_system/assets/js/global-search.js" defer></script>
</head>
<body>
    <div class="wrapper">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <div class="main-content">
            <header class="top-header">
                <!-- Global Search -->
                <div class="header-search">
                    <i data-lucide="search"></i>
                    <input type="text" placeholder="Search books, authors, or ISBN...">
                </div>

                <div class="user-menu">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                        <span class="user-role"><?php echo hasRole('admin') ? 'Administrator' : 'Member'; ?></span>
                    </div>
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 2)); ?>
                    </div>
                    <a href="/lib_system/library_system/auth/logout.php" class="btn-logout" title="Logout">
                        <i data-lucide="log-out" style="width: 20px; height: 20px;"></i>
                    </a>
                </div>
            </header>
            
            <div class="content">
