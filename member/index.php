<?php
// member/index.php
require_once '../config/db_config.php';
require_once '../includes/auth_middleware.php';

requireRole('member');

$pageTitle = 'Browse Catalog';

// Fetch Books
$search = $_GET['search'] ?? '';
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

include '../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl text-gray-900 mb-1">Book Catalog</h1>
    <p class="text-gray-600">Browse and search available books</p>
</div>

<!-- Search and Filter -->
<div class="mb-6 bg-white p-4 rounded shadow-sm border border-gray-200">
    <form method="GET" class="flex flex-col md:flex-row gap-4">
        <div class="header-search" style="margin: 0; flex: 1; max-width: none;">
            <i data-lucide="search"></i>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by title or author...">
        </div>
        <div class="flex items-center gap-2">
            <i data-lucide="filter" class="text-gray-400" style="width: 18px;"></i>
            <select name="category" class="form-control" style="width: 200px;" onchange="this.form.submit()">
                <option value="All">All Categories</option>
                <?php foreach ($cats as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $filter === $cat ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

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

<?php include '../includes/footer.php'; ?>
