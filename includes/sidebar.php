<!-- includes/sidebar.php -->
<?php
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

// Helper to check active state
function isActive($pattern) {
    global $currentRoute;
    // Special case for Member Home to prevent overlap with other /member/* routes
    if ($pattern === '/member') {
        return $currentRoute === '/member' || $currentRoute === '/member/';
    }
    // Check for exact match or prefix match (e.g. /admin/books/add matches /admin/books)
    return $currentRoute === $pattern || strpos($currentRoute, $pattern) === 0;
}
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <h1 class="text-xl font-semibold text-indigo-700">LibraryHub</h1>
    </div>
    <ul class="sidebar-nav">
        <?php if (hasRole('admin')): ?>
            <li>
                <a href="<?php echo url('admin/dashboard'); ?>" class="<?php echo isActive('/admin/dashboard') ? 'active' : ''; ?>">
                    <i data-lucide="layout-dashboard"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="<?php echo url('admin/books'); ?>" class="<?php echo isActive('/admin/books') ? 'active' : ''; ?>">
                    <i data-lucide="book-open"></i>
                    <span>Book Catalog</span>
                </a>
            </li>
            <li>
                <a href="<?php echo url('admin/search'); ?>" class="<?php echo isActive('/admin/search') ? 'active' : ''; ?>">
                    <i data-lucide="search"></i>
                    <span>Advanced Search</span>
                </a>
            </li>
            <li>
                <a href="<?php echo url('admin/members'); ?>" class="<?php echo isActive('/admin/members') ? 'active' : ''; ?>">
                    <i data-lucide="users"></i>
                    <span>Member Management</span>
                </a>
            </li>
            <li>
                <a href="<?php echo url('admin/transactions'); ?>" class="<?php echo isActive('/admin/transactions') || isActive('/admin/issue') || isActive('/admin/return') ? 'active' : ''; ?>">
                    <i data-lucide="arrow-left-right"></i>
                    <span>Transactions</span>
                </a>
            </li>
            <li>
                <a href="<?php echo url('admin/profile'); ?>" class="<?php echo isActive('/admin/profile') ? 'active' : ''; ?>">
                    <i data-lucide="settings"></i>
                    <span>Profile & Settings</span>
                </a>
            </li>
        <?php elseif (hasRole('member')): ?>
            <li>
                <a href="<?php echo url('member'); ?>" class="<?php echo isActive('/member') ? 'active' : ''; ?>">
                    <i data-lucide="book-open"></i>
                    <span>Browse Catalog</span>
                </a>
            </li>
            <li>
                <a href="<?php echo url('member/search'); ?>" class="<?php echo isActive('/member/search') ? 'active' : ''; ?>">
                    <i data-lucide="search"></i>
                    <span>Advanced Search</span>
                </a>
            </li>
            <li>
                <a href="<?php echo url('member/loans'); ?>" class="<?php echo isActive('/member/loans') ? 'active' : ''; ?>">
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
