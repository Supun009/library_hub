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
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>?v=<?php echo time(); ?>">

    <!-- Unpkg for Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Mobile Navigation Script -->
    <script src="<?php echo asset('js/mobile-nav.js'); ?>?v=<?php echo time(); ?>" defer></script>
    
    <!-- Global Search Script -->
    <script src="<?php echo asset('js/global-search.js'); ?>?v=<?php echo time(); ?>" defer></script>
    <!-- Global URL Helper -->
    <script>
        window.baseUrl = '<?php echo rtrim(getBaseUrl(), '/'); ?>';
        window.url = function(path) {
            return window.baseUrl + '/' + path.replace(/^\//, '');
        };

        // Prevent back button caching (BFCache)
        window.onpageshow = function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        };
    </script>
</head>
<body>
    <div id="sidebar-overlay"></div>
    <div class="wrapper">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <div class="main-content">
            <header class="top-header">
                <!-- Mobile Hamburger Button -->
                <button id="hamburger-btn" class="md:hidden p-2 mr-4 text-gray-600 hover:text-gray-900 focus:outline-none">
                    <i data-lucide="menu"></i>
                </button>
                
                <!-- Global Search -->
                <div class="header-search hidden md:block">
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
                    <a href="<?php echo url('logout'); ?>" class="btn-logout" title="Logout">
                        <i data-lucide="log-out" style="width: 20px; height: 20px;"></i>
                    </a>
                </div>
            </header>
            
            <div class="content">
