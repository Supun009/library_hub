<!-- includes/sidebar.php -->
<aside class="sidebar">
    <div class="sidebar-header">
        LMS Portal
    </div>
    <ul class="sidebar-nav">
        <?php if (hasRole('admin')): ?>
            <li><a href="/lib_system/library_system/admin/dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
            <li><a href="/lib_system/library_system/admin/manage_books.php">Manage Books</a></li>
            <li><a href="/lib_system/library_system/admin/manage_members.php">Manage Members</a></li>
        <?php elseif (hasRole('member')): ?>
            <li><a href="/lib_system/library_system/member/index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Browse Catalog</a></li>
            <li><a href="/lib_system/library_system/member/my_loans.php">My Loans</a></li>
        <?php endif; ?>
    </ul>
</aside>
