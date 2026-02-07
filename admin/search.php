<?php
// admin/search.php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireRole('admin');

$pageTitle = 'Advanced Search';

// Initialize filters
$filters = [
    'title' => $_GET['title'] ?? '',
    'author' => $_GET['author'] ?? '',
    'category' => $_GET['category'] ?? 'All',
    'isbn' => $_GET['isbn'] ?? '',
    'year_from' => $_GET['year_from'] ?? '',
    'year_to' => $_GET['year_to'] ?? '',
    'status' => $_GET['status'] ?? 'all'
];

$results = [];
$hasSearched = !empty($_GET);

// Fetch Categories for Dropdown
$categories = $pdo->query("SELECT category_name FROM categories")->fetchAll(PDO::FETCH_COLUMN);

if ($hasSearched) {
    $query = "
        SELECT b.book_id, b.title, b.isbn, b.publication_year, c.category_name, s.status_name,
               GROUP_CONCAT(a.name SEPARATOR ', ') as authors
        FROM books b
        LEFT JOIN categories c ON b.category_id = c.category_id
        LEFT JOIN status s ON b.status_id = s.status_id
        LEFT JOIN book_authors ba ON b.book_id = ba.book_id
        LEFT JOIN authors a ON ba.author_id = a.author_id
    ";

    $conditions = [];
    $params = [];

    // Exclude deleted books
    $conditions[] = "b.deleted_at IS NULL";
    $conditions[] = "s.status_name != 'Deleted'";

    if (!empty($filters['title'])) {
        $conditions[] = "b.title LIKE ?";
        $params[] = "%" . $filters['title'] . "%";
    }

    if (!empty($filters['isbn'])) {
        $conditions[] = "b.isbn LIKE ?";
        $params[] = "%" . $filters['isbn'] . "%";
    }

    if ($filters['category'] !== 'All') {
        $conditions[] = "c.category_name = ?";
        $params[] = $filters['category'];
    }

    if (!empty($filters['year_from'])) {
        $conditions[] = "b.publication_year >= ?";
        $params[] = $filters['year_from'];
    }

    if (!empty($filters['year_to'])) {
        $conditions[] = "b.publication_year <= ?";
        $params[] = $filters['year_to'];
    }

    if ($filters['status'] === 'available') {
        $conditions[] = "s.status_name = 'Available'";
    } elseif ($filters['status'] === 'issued') {
        $conditions[] = "s.status_name = 'Issued'";
    }

    // Author search requires HAVING or Subquery because of GROUP_CONCAT, 
    // but for simple LIKE matching on the joined table before grouping:
    if (!empty($filters['author'])) {
        // We filter by author BEFORE grouping to ensure we catch books with that author
        // But since we want to show ALL authors for that book, we need to be careful.
        // The standard WHERE clause works fine for filtering rows, but we need to group by book_id.
        $conditions[] = "a.name LIKE ?";
        $params[] = "%" . $filters['author'] . "%";
    }

    if (count($conditions) > 0) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    $query .= " GROUP BY b.book_id ORDER BY b.title ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>

<script>
    // Transform simple array to object array to match JS expectation
    window.categoriesData = <?php echo json_encode(array_map(function($c) { return ['category_name' => $c]; }, $categories)); ?>;
</script>
<script src="<?php echo asset('js/book-catalog-search.js'); ?>?v=<?php echo time(); ?>"></script>

<div class="mb-6">
    <h1 class="page-heading">Advanced Search</h1>
    <p class="text-gray-600">Search for books using multiple filters</p>
</div>

<!-- Search Form -->
<div class="mb-6 rounded border border-gray-200 bg-white p-6 shadow-sm">
    <form method="GET">
        <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2">
            <!-- Book Title -->
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Book Title</label>
                <input
                    type="text"
                    name="title"
                    value="<?php echo htmlspecialchars($filters['title']); ?>"
                    placeholder="Enter book title"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
            </div>

            <!-- Author Name -->
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Author Name</label>
                <input
                    type="text"
                    name="author"
                    value="<?php echo htmlspecialchars($filters['author']); ?>"
                    placeholder="Enter author name"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
            </div>

            <!-- Category -->
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Category</label>
                <input type="hidden" name="category" id="category_hidden" value="<?php echo htmlspecialchars($filters['category'] === 'All' ? '' : $filters['category']); ?>">
                <div class="relative">
                     <input
                        type="text"
                        id="category_search"
                        placeholder="All Categories"
                        autocomplete="off"
                        value="<?php echo htmlspecialchars($filters['category'] === 'All' ? '' : $filters['category']); ?>"
                        class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                    >
                    <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                        <i data-lucide="chevron-down" class="h-4 w-4 text-gray-400"></i>
                    </div>
                </div>
                <div id="category_dropdown" class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg hidden max-h-60 overflow-auto">
                    <!-- Categories will be populated here -->
                </div>
            </div>

            <!-- ISBN -->
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">ISBN</label>
                <input
                    type="text"
                    name="isbn"
                    value="<?php echo htmlspecialchars($filters['isbn']); ?>"
                    placeholder="Enter ISBN"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
            </div>

            <!-- Publication Year From -->
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Publication Year (From)</label>
                <input
                    type="number"
                    name="year_from"
                    value="<?php echo htmlspecialchars($filters['year_from']); ?>"
                    placeholder="e.g., 2000"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
            </div>

            <!-- Publication Year To -->
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700">Publication Year (To)</label>
                <input
                    type="number"
                    name="year_to"
                    value="<?php echo htmlspecialchars($filters['year_to']); ?>"
                    placeholder="e.g., 2024"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
            </div>

            <!-- Availability Status -->
            <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-medium text-gray-700">Availability Status</label>
                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input
                            type="radio"
                            name="status"
                            value="all"
                            <?php echo $filters['status'] === 'all' ? 'checked' : ''; ?>
                        >
                        <span>All Books</span>
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input
                            type="radio"
                            name="status"
                            value="available"
                            <?php echo $filters['status'] === 'available' ? 'checked' : ''; ?>
                        >
                        <span>Available Only</span>
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input
                            type="radio"
                            name="status"
                            value="issued"
                            <?php echo $filters['status'] === 'issued' ? 'checked' : ''; ?>
                        >
                        <span>Issued Only</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button
                type="submit"
                class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 transition-colors"
            >
                <i data-lucide="search" class="h-4 w-4"></i>
                Search Books
            </button>
            <a
                href="<?php echo url('admin/search'); ?>"
                class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 transition-colors"
            >
                <i data-lucide="x" class="h-4 w-4"></i>
                Reset Filters
            </a>
        </div>
    </form>
</div>

<!-- Search Results -->
<?php if ($hasSearched): ?>
    <div class="table-container">
        <div class="table-header">
            <h2 class="text-lg font-semibold text-gray-900">Search Results</h2>
            <p class="text-gray-500 text-sm mt-1">Found <?php echo count($results); ?> book(s) matching your criteria</p>
        </div>
        
        <?php if (count($results) > 0): ?>
            <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author(s)</th>
                            <th>Category</th>
                            <th>ISBN</th>
                            <th>Year</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $book): ?>
                            <tr>
                                <td class="font-medium text-gray-900">
                                    <div class="truncate max-w-xs" title="<?php echo htmlspecialchars($book['title']); ?>">
                                        <?php echo htmlspecialchars($book['title']); ?>
                                    </div>
                                </td>
                                <td class="text-gray-900">
                                    <div class="truncate max-w-xs" title="<?php echo htmlspecialchars($book['authors']); ?>">
                                        <?php echo htmlspecialchars($book['authors']); ?>
                                    </div>
                                </td>
                                <td class="text-gray-600"><?php echo htmlspecialchars($book['category_name']); ?></td>
                                <td class="text-gray-600"><?php echo htmlspecialchars($book['isbn']); ?></td>
                                <td class="text-gray-600"><?php echo htmlspecialchars($book['publication_year'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge <?php echo $book['status_name'] === 'Available' ? 'badge-green' : 'badge-red'; ?>">
                                        <?php echo htmlspecialchars($book['status_name']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-12 text-center text-gray-500">
                No books found matching your search criteria.
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
