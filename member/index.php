<?php
// member/index.php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireRole('member');

$pageTitle = 'Browse Catalog';

// Fetch Books
$search = trim($_GET['search'] ?? '');
$filter = $_GET['category'] ?? '';

$query = "
    SELECT b.title, b.isbn, c.category_name, s.status_name,
           GROUP_CONCAT(a.name SEPARATOR ', ') as authors
    FROM books b
    LEFT JOIN categories c ON b.category_id = c.category_id
    LEFT JOIN status s ON b.status_id = s.status_id
    LEFT JOIN book_authors ba ON b.book_id = ba.book_id
    LEFT JOIN authors a ON ba.author_id = a.author_id
";

$conditions = [];
$params = [];

if ($search) {
    $conditions[] = "(b.title LIKE ? OR b.isbn LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter && $filter !== 'All') {
    $conditions[] = "c.category_name = ?";
    $params[] = $filter;
}

if (count($conditions) > 0) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " GROUP BY b.book_id ORDER BY b.book_id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$books = $stmt->fetchAll();

// Fetch Categories for Filter
$cats = $pdo->query("SELECT category_name FROM categories")->fetchAll(PDO::FETCH_COLUMN);

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <h1 class="page-heading">Book Catalog</h1>
    <p class="text-gray-600">Browse and search available books</p>
</div>

<!-- Search and Filter -->
<div class="mb-6 bg-white p-4 rounded shadow-sm border border-gray-200">
    <form method="GET" id="searchForm" class="flex flex-col md:flex-row gap-4 items-center">
        <div class="header-search w-full md:flex-1 m-0 max-w-none relative">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 cursor-pointer" onclick="this.closest('form').submit()"></i>
            <input 
                type="text" 
                name="search" 
                id="searchInput" 
                value="<?php echo htmlspecialchars($search); ?>" 
                placeholder="Search by title or author..." 
                class="pl-10 block w-full rounded-md border border-gray-300 bg-white py-2 pr-3 text-sm shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                oninput="handleSearchInput(this)"
            >
        </div>
        <div class="w-full md:w-auto">
            <details class="group relative w-full md:w-[220px]">
                <summary class="flex items-center justify-between w-full cursor-pointer rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 list-none">
                    <span class="truncate">
                        <?php echo $filter && $filter !== 'All' ? htmlspecialchars($filter) : 'All Categories'; ?>
                    </span>
                    <i data-lucide="chevron-down" class="h-4 w-4 text-gray-400 transition-transform group-open:rotate-180"></i>
                </summary>
                <div class="absolute right-0 z-10 mt-1 w-full max-h-60 overflow-y-auto min-w-[220px] origin-top-right rounded-md border border-gray-200 bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                    <a 
                        href="?<?php echo http_build_query(array_merge($_GET, ['category' => 'All'])); ?>"
                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 <?php echo !$filter || $filter === 'All' ? 'bg-gray-50 font-medium text-indigo-600' : ''; ?>"
                    >
                        All Categories
                    </a>
                    <?php foreach ($cats as $cat): ?>
                        <a 
                            href="?<?php echo http_build_query(array_merge($_GET, ['category' => $cat])); ?>"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 <?php echo $filter === $cat ? 'bg-gray-50 font-medium text-indigo-600' : ''; ?>"
                        >
                            <?php echo htmlspecialchars($cat); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </details>
        </div>
    </form>
</div>

<script>
function handleSearchInput(input) {
    // If search is cleared, submit form to reload all books with current filter
    if (input.value.trim() === '') {
        input.form.submit();
    }
}
</script>

<!-- Books Grid -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
    <?php if (count($books) > 0): ?>
        <?php foreach ($books as $book): ?>
            <div class="bg-white p-5 rounded shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                <div class="mb-3">
                    <h3 class="font-semibold text-gray-900 mb-1 truncate" title="<?php echo htmlspecialchars($book['title']); ?>">
                        <?php echo htmlspecialchars($book['title']); ?>
                    </h3>
                    <p class="text-sm text-gray-600 mb-1" style="min-height: 1.25rem;"><?php echo htmlspecialchars($book['authors']); ?></p>
                    <p class="text-xs text-gray-500">ISBN: <?php echo htmlspecialchars($book['isbn']); ?></p>
                </div>

                <div class="flex items-center justify-between mb-4">
                    <span class="badge badge-gray"><?php echo htmlspecialchars($book['category_name']); ?></span>
                    <span class="badge <?php echo $book['status_name'] === 'Available' ? 'badge-green' : 'badge-red'; ?>">
                        <?php echo htmlspecialchars($book['status_name']); ?>
                    </span>
                </div>

                <button class="btn w-full" disabled
                        style="background-color: <?php echo $book['status_name'] === 'Available' ? 'var(--primary-color)' : '#e5e7eb'; ?>; 
                               color: <?php echo $book['status_name'] === 'Available' ? 'white' : '#9ca3af'; ?>;">
                    <?php echo $book['status_name'] === 'Available' ? 'Available to Borrow' : 'Currently Issued'; ?>
                </button>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-span-full text-center py-12">
            <p class="text-gray-500">No books found matching your search criteria.</p>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
    // Close details dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        const details = document.querySelectorAll('details');
        details.forEach(detail => {
            if (detail.hasAttribute('open') && !detail.contains(e.target)) {
                detail.removeAttribute('open');
            }
        });
    });
</script>
