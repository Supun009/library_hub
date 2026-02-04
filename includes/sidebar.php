<!-- includes/sidebar.php -->
<aside class="sidebar">
    <div class="sidebar-header">
        <h1>LibraryHub</h1>
    </div>
    <ul class="sidebar-nav">
        <?php if (hasRole('admin')): ?>
            <li>
                <a href="/lib_system/library_system/admin/dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i data-lucide="layout-dashboard"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="/lib_system/library_system/admin/manage_books.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_books.php' ? 'active' : ''; ?>">
                    <i data-lucide="book-open"></i>
                    <span>Book Catalog</span>
                </a>
            </li>
            <li>
                <a href="/lib_system/library_system/admin/manage_members.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_members.php' ? 'active' : ''; ?>">
                    <i data-lucide="users"></i>
                    <span>Member Management</span>
                </a>
            </li>
            <li>
                <a href="/lib_system/library_system/admin/transactions.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'transactions.php' ? 'active' : ''; ?>">
                    <i data-lucide="arrow-left-right"></i>
                    <span>Transactions</span>
                </a>
            </li>
        <?php elseif (hasRole('member')): ?>
            <li>
                <a href="/lib_system/library_system/member/index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <i data-lucide="book-open"></i>
                    <span>Browse Catalog</span>
                </a>
            </li>
            <li>
                <a href="/lib_system/library_system/member/my_loans.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'my_loans.php' ? 'active' : ''; ?>">
                    <i data-lucide="history"></i>
                    <span>My Loans</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</aside>
