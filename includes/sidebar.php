<!-- includes/sidebar.php -->
<?php
// Define page groups for active state highlighting
$activePage = basename($_SERVER['PHP_SELF']);

// Map nested pages to their parent menu items
$pageGroups = [
    'dashboard' => ['dashboard.php'],
    'books' => ['manage_books.php', 'add_book.php', 'edit_book.php'],
    'search' => ['search.php'],
    'members' => ['manage_members.php', 'add_member.php', 'edit_member.php'],
    'transactions' => ['transactions.php', 'issue_book.php', 'return_book.php'],
    'profile' => ['profile.php'],
    // Member pages
    'browse' => ['index.php'],
    'member_search' => ['search.php'],
    'loans' => ['my_loans.php']
];

// Helper function to check if current page belongs to a group
function isActiveGroup($group, $activePage, $pageGroups) {
    return isset($pageGroups[$group]) && in_array($activePage, $pageGroups[$group]);
}
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <h1 class="text-xl font-semibold text-indigo-700">LibraryHub</h1>
    </div>
    <ul class="sidebar-nav">
        <?php if (hasRole('admin')): ?>
            <li>
                <a href="<?php echo adminUrl('dashboard.php'); ?>" class="<?php echo isActiveGroup('dashboard', $activePage, $pageGroups) ? 'active' : ''; ?>">
                    <i data-lucide="layout-dashboard"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="<?php echo adminUrl('manage_books.php'); ?>" class="<?php echo isActiveGroup('books', $activePage, $pageGroups) ? 'active' : ''; ?>">
                    <i data-lucide="book-open"></i>
                    <span>Book Catalog</span>
                </a>
            </li>
            <li>
                <a href="<?php echo adminUrl('search.php'); ?>" class="<?php echo isActiveGroup('search', $activePage, $pageGroups) ? 'active' : ''; ?>">
                    <i data-lucide="search"></i>
                    <span>Advanced Search</span>
                </a>
            </li>
            <li>
                <a href="<?php echo adminUrl('manage_members.php'); ?>" class="<?php echo isActiveGroup('members', $activePage, $pageGroups) ? 'active' : ''; ?>">
                    <i data-lucide="users"></i>
                    <span>Member Management</span>
                </a>
            </li>
            <li>
                <a href="<?php echo adminUrl('transactions.php'); ?>" class="<?php echo isActiveGroup('transactions', $activePage, $pageGroups) ? 'active' : ''; ?>">
                    <i data-lucide="arrow-left-right"></i>
                    <span>Transactions</span>
                </a>
            </li>
            <li>
                <a href="<?php echo adminUrl('profile.php'); ?>" class="<?php echo isActiveGroup('profile', $activePage, $pageGroups) ? 'active' : ''; ?>">
                    <i data-lucide="settings"></i>
                    <span>Profile & Settings</span>
                </a>
            </li>
        <?php elseif (hasRole('member')): ?>
            <li>
                <a href="<?php echo memberUrl('index.php'); ?>" class="<?php echo isActiveGroup('browse', $activePage, $pageGroups) ? 'active' : ''; ?>">
                    <i data-lucide="book-open"></i>
                    <span>Browse Catalog</span>
                </a>
            </li>
            <li>
                <a href="<?php echo memberUrl('search.php'); ?>" class="<?php echo isActiveGroup('member_search', $activePage, $pageGroups) ? 'active' : ''; ?>">
                    <i data-lucide="search"></i>
                    <span>Advanced Search</span>
                </a>
            </li>
            <li>
                <a href="<?php echo memberUrl('my_loans.php'); ?>" class="<?php echo isActiveGroup('loans', $activePage, $pageGroups) ? 'active' : ''; ?>">
                    <i data-lucide="history"></i>
                    <span>My Loans</span>
                </a>
            </li>
        <?php endif; ?>
        <li class="mt-auto border-t border-gray-200 pt-4">
            <a
                href="<?php echo authUrl('logout.php'); ?>"
                class="flex items-center gap-3 rounded px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors"
            >
                <i data-lucide="log-out" class="h-4 w-4"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</aside>
