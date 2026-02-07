<?php
// admin/manage_books.php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireRole('admin');

$pageTitle = 'Book Catalog';
$error = '';
$success = '';

// Check for success message from add_book.php
if (isset($_GET['msg']) && $_GET['msg'] === 'book_added') {
    $success = "Book added successfully.";
}
if (isset($_GET['msg']) && $_GET['msg'] === 'book_deleted') {
    $success = "Book deleted successfully.";
}
if (isset($_GET['msg']) && $_GET['msg'] === 'book_updated') {
    $success = "Book updated successfully.";
}

// Handle Delete Book
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_book') {
    $bookId = $_POST['book_id'];
    try {
        // Check for active loans in issues table
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM issues WHERE book_id = ? AND return_date IS NULL");
        $stmt->execute([$bookId]);
        $activeLoans = $stmt->fetchColumn();
        
        if ($activeLoans > 0) {
            $error = "Cannot delete book. It has active loans.";
        } else {
            // Fetch 'Deleted' status ID
            $stmt = $pdo->prepare("SELECT status_id FROM status WHERE status_name = 'Deleted'");
            $stmt->execute();
            $deletedStatusId = $stmt->fetchColumn();

            if ($deletedStatusId) {
                // Soft Delete the book with status update
                $stmt = $pdo->prepare("UPDATE books SET deleted_at = NOW(), status_id = ? WHERE book_id = ?");
                $stmt->execute([$deletedStatusId, $bookId]);
            } else {
                // Fallback if status not found (shouldn't happen with correct migration)
                $stmt = $pdo->prepare("UPDATE books SET deleted_at = NOW() WHERE book_id = ?");
                $stmt->execute([$bookId]);
            }
            
            redirect('admin/books?msg=book_deleted');
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch Categories for Filter
$categories = $pdo->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC")->fetchAll();

// Fetch Books with Pagination
$search = $_GET['search'] ?? '';
$filter = $_GET['category'] ?? '';
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$itemsPerPage = 12; 
$offset = ($currentPage - 1) * $itemsPerPage;

// Build base query
$baseQuery = "
    FROM books b
    LEFT JOIN categories c ON b.category_id = c.category_id
    LEFT JOIN status s ON b.status_id = s.status_id
    LEFT JOIN book_authors ba ON b.book_id = ba.book_id
    LEFT JOIN authors a ON ba.author_id = a.author_id
    WHERE b.deleted_at IS NULL AND s.status_name != 'Deleted' 
";

$conditions = [];
$params = [];

if ($search) {
    $conditions[] = "(b.title LIKE :search_title OR b.isbn LIKE :search_isbn OR a.name LIKE :search_author)";
    $params['search_title'] = "%$search%";
    $params['search_isbn'] = "%$search%";
    $params['search_author'] = "%$search%";
}

if ($filter && $filter !== 'All') {
    $conditions[] = "c.category_name = :category";
    $params['category'] = $filter;
}

$whereClause = '';
if (count($conditions) > 0) {
    $whereClause = " AND " . implode(" AND ", $conditions);
}

// Count total books for pagination
$countQuery = "SELECT COUNT(DISTINCT b.book_id) as total " . $baseQuery . $whereClause;
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$totalItems = $countStmt->fetch()['total'];

// Fetch books with pagination
$query = "
    SELECT b.book_id, b.title, b.isbn, b.total_copies, b.available_copies, c.category_name, s.status_name,
           GROUP_CONCAT(a.name SEPARATOR ', ') as authors
    " . $baseQuery . $whereClause . "
    GROUP BY b.book_id 
    ORDER BY b.book_id DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($query);

// Bind all parameters (search/filter + pagination)
foreach ($params as $key => $value) {
    $stmt->bindValue(":$key", $value, PDO::PARAM_STR);
}

// Bind pagination parameters
$stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$books = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- Real-time Search Script -->
<script>
    window.categoriesData = <?php echo json_encode($categories); ?>;
</script>
<script src="<?php echo asset('js/book-catalog-search.js'); ?>?v=<?php echo time(); ?>"></script>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="page-heading">Manage Books</h1>
        <p class="text-sm text-gray-600">Browse and manage library books</p>
    </div>
    <a
        href="<?php echo url('admin/books/add'); ?>"
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
                placeholder="Search by title, author, or ISBN..."
                class="block w-full rounded-md border border-gray-300 bg-white py-2 pl-9 pr-3 text-sm shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
            >
        </div>
        
        <div class="relative w-full md:max-w-xs">
            <input type="hidden" name="category" id="category_hidden" value="<?php echo htmlspecialchars($filter === 'All' ? '' : $filter); ?>">
            <div class="relative">
                 <input
                    type="text"
                    id="category_search"
                    placeholder="All Categories"
                    autocomplete="off"
                    value="<?php echo htmlspecialchars($filter === 'All' ? '' : $filter); ?>"
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
    </form>
</div>

<!-- Books Grid -->
<div class="mb-4">
    <h2 class="text-lg font-semibold text-gray-900">Available Books</h2>
    <p class="text-gray-500 text-sm mt-1">Total: <?php echo $totalItems; ?> book(s)</p>
</div>

<div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
    <?php foreach ($books as $book): ?>
        <div class="flex h-full flex-col rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition-shadow hover:shadow-md">
            
            <div class="flex-grow min-w-0">
                <h3
                    class="mb-1 truncate text-lg font-bold text-gray-900"
                    title="<?php echo htmlspecialchars($book['title']); ?>"
                >
                    <?php echo htmlspecialchars($book['title']); ?>
                </h3>
                <p class="mb-2 text-sm italic text-gray-600 truncate" title="<?php echo htmlspecialchars($book['authors']); ?>">
                    <?php echo htmlspecialchars($book['authors']); ?>
                </p>
                <div class="flex justify-between items-center text-xs text-gray-500 mb-2">
                    <span class="font-mono truncate mr-2" title="ISBN: <?php echo htmlspecialchars($book['isbn']); ?>">ISBN: <?php echo htmlspecialchars($book['isbn']); ?></span>
                    <span class="font-semibold whitespace-nowrap <?php echo $book['available_copies'] > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                        Stock: <?php echo $book['available_copies'] . '/' . $book['total_copies']; ?>
                    </span>
                </div>
            </div>
            
            <div class="mt-6 mb-4 flex items-center justify-between">
                <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
                    <?php echo htmlspecialchars($book['category_name']); ?>
                </span>
                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo $book['available_copies'] > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $book['available_copies'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                </span>
            </div>
            
            <div class="mt-4 flex gap-2">
                <a href="<?php echo url('admin/books/edit?id=' . $book['book_id']); ?>" 
                   class="flex-1 inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    <i data-lucide="edit" class="mr-2 h-4 w-4"></i>
                    Edit
                </a>
                <button 
                    onclick="confirmDeleteBook(<?php echo $book['book_id']; ?>)"
                    class="flex-1 inline-flex items-center justify-center rounded-md border border-transparent bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed disabled:text-gray-400"
                    <?php echo ($book['available_copies'] < $book['total_copies']) ? 'disabled title="Cannot delete book with active loans"' : ''; ?>
                >
                    <i data-lucide="trash-2" class="mr-2 h-4 w-4"></i>
                    Delete
                </button>
            </div>
            
        </div>
    <?php endforeach; ?>
</div>

<?php
// Include and render pagination
require_once __DIR__ . '/../includes/pagination.php';
renderPagination($currentPage, $totalItems, $itemsPerPage, [
    'search' => $search,
    'category' => $filter
]);
?>


<script>
function confirmDeleteBook(bookId) {
    openDeleteModal(function() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = window.location.href;
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_book';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'book_id';
        idInput.value = bookId;
        
        form.appendChild(actionInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }, "Delete Book", "Are you sure you want to delete this book? This action cannot be undone.");
}
</script>

<?php 
include __DIR__ . '/../includes/delete_modal.php';
include __DIR__ . '/../includes/footer.php'; 
?>
