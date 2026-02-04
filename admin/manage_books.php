<?php
// admin/manage_books.php
require_once '../config/db_config.php';
require_once '../includes/auth_middleware.php';

requireRole('admin');

$pageTitle = 'Book Catalog';
$error = '';
$success = '';

// Check for success message from add_book.php
if (isset($_GET['msg']) && $_GET['msg'] === 'book_added') {
    $success = "Book added successfully.";
}

// Fetch Categories for Filter
$categories = $pdo->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC")->fetchAll();

// Fetch Books
$search = $_GET['search'] ?? '';
$filter = $_GET['category'] ?? '';

$query = "
    SELECT b.book_id, b.title, b.isbn, c.category_name, s.status_name,
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

include '../includes/header.php';
?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="mb-1 text-2xl font-semibold text-gray-900">Book Catalog</h1>
        <p class="text-sm text-gray-600">Browse and manage library books</p>
    </div>
    <a
        href="add_book.php"
        class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 transition-colors"
    >
        <i data-lucide="plus" class="h-4 w-4"></i>
        Add New Book
    </a>
</div>

<?php if ($success): ?>
    <div class="mb-4 rounded-md border border-green-200 bg-green-100 px-4 py-3 text-sm text-green-700">
        <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<!-- Search & Filter -->
<div class="mb-6 rounded-md border border-gray-200 bg-white p-4 shadow-sm">
    <form method="GET" class="flex flex-wrap items-center gap-4">
        <div class="relative flex-1 min-w-[220px]">
            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
            <input
                type="text"
                name="search"
                value="<?php echo htmlspecialchars($search); ?>"
                placeholder="Search by title or ISBN..."
                class="block w-full rounded-md border border-gray-300 bg-white py-2 pl-9 pr-3 text-sm shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
            >
        </div>
        <select
            name="category"
            onchange="this.form.submit()"
            class="block w-full max-w-xs rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
        >
            <option value="All">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo htmlspecialchars($cat['category_name']); ?>" <?php echo $filter === $cat['category_name'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['category_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<!-- Books Grid -->


<div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
    <?php foreach ($books as $book): ?>
        <div class="flex h-full flex-col rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition-shadow hover:shadow-md">
            
            <div class="flex-grow">
                <h3
                    class="mb-1 truncate text-lg font-bold text-gray-900"
                    title="<?php echo htmlspecialchars($book['title']); ?>"
                >
                    <?php echo htmlspecialchars($book['title']); ?>
                </h3>
                <p class="mb-2 text-sm italic text-gray-600">
                    <?php echo htmlspecialchars($book['authors']); ?>
                </p>
                <p class="text-xs font-mono text-gray-400">
                    ISBN: <?php echo htmlspecialchars($book['isbn']); ?>
                </p>
            </div>
            
            <div class="mt-6 mb-4 flex items-center justify-between">
                <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
                    <?php echo htmlspecialchars($book['category_name']); ?>
                </span>
                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo $book['status_name'] === 'Available' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo htmlspecialchars($book['status_name']); ?>
                </span>
            </div>
            
            <button
                class="inline-flex w-full items-center justify-center rounded-md px-4 py-2 text-sm font-medium shadow-sm transition-colors <?php echo $book['status_name'] === 'Available'
                    ? 'bg-indigo-600 text-white hover:bg-indigo-700'
                    : 'cursor-not-allowed bg-gray-100 text-gray-400'; ?>"
                <?php echo $book['status_name'] === 'Available' ? '' : 'disabled'; ?>
            >
                <?php echo $book['status_name'] === 'Available' ? 'Issue Book' : 'Not Available'; ?>
            </button>
            
        </div>
    <?php endforeach; ?>
</div>

<?php include '../includes/footer.php'; ?>
