<!-- includes/sidebar.php -->
<?php
$config = require __DIR__ . '/../config/app_config.php';

// Determine current route for active state highlighting
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = parse_url(getBaseUrl(), PHP_URL_PATH);

// Strip base path from current path to get the route
if (strpos($currentPath, $basePath) === 0) {
    $currentRoute = substr($currentPath, strlen($basePath));
} else {
    $currentRoute = $currentPath;
}
$currentRoute = '/' . ltrim($currentRoute, '/');


// Helper to check active state - now accepts current route as parameter
function isActive($pattern, $currentRoute) {
    // Special case for member home - ignore trailing slash
    if ($pattern === '/member') {
        return rtrim($currentRoute, '/') === '/member';
    }
    
    // For all other routes: exact match OR starts with pattern
    $result = $currentRoute === $pattern || strpos($currentRoute, $pattern) === 0;
    
    return $result;
}
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <h1 class="text-xl font-semibold text-indigo-700"><?php echo $config['app_name'] ?? 'LibraryHub'; ?></h1>
    </div>
    <ul class="sidebar-nav">
        <?php if (hasRole('admin')): ?>
            <li>
                <a href="<?php echo url('admin/dashboard'); ?>" class="<?php echo isActive('/admin/dashboard', $currentRoute) ? 'active' : ''; ?>">
                    <i data-lucide="layout-dashboard"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="<?php echo url('admin/books'); ?>" class="<?php echo isActive('/admin/books', $currentRoute) ? 'active' : ''; ?>">
                    <i data-lucide="book-open"></i>
                    <span>Manage Books</span>
                </a>
            </li>
            <li>
                <a href="<?php echo url('admin/search'); ?>" class="<?php echo isActive('/admin/search', $currentRoute) ? 'active' : ''; ?>">
                    <i data-lucide="search"></i>
                    <span>Advanced Search</span>
                </a>
            </li>
            <li>
                <a href="<?php echo url('admin/categories'); ?>" class="<?php echo isActive('/admin/categories', $currentRoute) ? 'active' : ''; ?>">
                    <i data-lucide="folder"></i>
                    <span>Categories</span>
                </a>
            </li>
            <li>
                <a href="<?php echo url('admin/authors'); ?>" class="<?php echo isActive('/admin/authors', $currentRoute) ? 'active' : ''; ?>">
                    <i data-lucide="user-pen"></i>
                    <span>Authors</span>
                </a>
            </li>
            
            <li>
                <a href="<?php echo url('admin/transactions'); ?>" class="<?php echo isActive('/admin/transactions', $currentRoute) || isActive('/admin/issue', $currentRoute) || isActive('/admin/return', $currentRoute) ? 'active' : ''; ?>">
                    <i data-lucide="arrow-left-right"></i>
                    <span>Transactions</span>
                </a>
            </li>
            <li>
                <a href="<?php echo url('admin/members'); ?>" class="<?php echo isActive('/admin/members', $currentRoute) ? 'active' : ''; ?>">
                    <i data-lucide="users"></i>
                    <span>Member Management</span>
                </a>
            </li>
            <li>
                <a href="<?php echo url('admin/profile'); ?>" class="<?php echo isActive('/admin/profile', $currentRoute) ? 'active' : ''; ?>">
                    <i data-lucide="settings"></i>
                    <span>Profile & Settings</span>
                </a>
            </li>
        <?php elseif (hasRole('member')): ?>
            <li>
                <a href="<?php echo url('member'); ?>" class="<?php echo isActive('/member', $currentRoute) ? 'active' : ''; ?>">
                    <i data-lucide="book-open"></i>
                    <span>Browse Catalog</span>
                </a>
            </li>
            <li>
                <a href="<?php echo url('member/search'); ?>" class="<?php echo isActive('/member/search', $currentRoute) ? 'active' : ''; ?>">
                    <i data-lucide="search"></i>
                    <span>Advanced Search</span>
                </a>
            </li>
            <li>
                <a href="<?php echo url('member/loans'); ?>" class="<?php echo isActive('/member/loans', $currentRoute) ? 'active' : ''; ?>">
                    <i data-lucide="history"></i>
                    <span>My Loans</span>
                </a>
            </li>
        <?php endif; ?>
        <li class="mt-auto border-t border-gray-200 pt-4">
            <a
                href="<?php echo url('logout'); ?>"
                class="flex items-center gap-3 rounded px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors"
            >
                <i data-lucide="log-out" class="h-4 w-4"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</aside>
