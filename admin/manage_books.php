<?php
// admin/manage_books.php
require_once '../config/db_config.php';
require_once '../includes/auth_middleware.php';

requireRole('admin');

$pageTitle = 'Book Catalog';
$error = '';
$success = '';

// Handle Add Book
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_book') {
    $title = trim($_POST['title']);
    $isbn = trim($_POST['isbn']);
    $authorName = trim($_POST['author']);
    $categoryName = trim($_POST['category']); // Simplified for demo: text input or select

    if (empty($title) || empty($isbn) || empty($authorName) || empty($categoryName)) {
        $error = "All fields are required.";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Get or Create Category
            $stmt = $pdo->prepare("SELECT category_id FROM categories WHERE category_name = ?");
            $stmt->execute([$categoryName]);
            $categoryId = $stmt->fetchColumn();
            if (!$categoryId) {
                $stmt = $pdo->prepare("INSERT INTO categories (category_name) VALUES (?)");
                $stmt->execute([$categoryName]);
                $categoryId = $pdo->lastInsertId();
            }

            // 2. Get Status ID (Available)
            $stmt = $pdo->query("SELECT status_id FROM status WHERE status_name = 'Available'");
            $statusId = $stmt->fetchColumn();
            if (!$statusId) {
                // Insert status if not exists (should be handled by schema, but safety net)
                $pdo->exec("INSERT INTO status (status_name) VALUES ('Available')");
                $statusId = $pdo->lastInsertId();
            }

            // 3. Insert Book
            $stmt = $pdo->prepare("INSERT INTO books (title, isbn, category_id, status_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $isbn, $categoryId, $statusId]);
            $bookId = $pdo->lastInsertId();

            // 4. Get or Create Author & Link
            $stmt = $pdo->prepare("SELECT author_id FROM authors WHERE name = ?");
            $stmt->execute([$authorName]);
            $authorId = $stmt->fetchColumn();
            if (!$authorId) {
                $stmt = $pdo->prepare("INSERT INTO authors (name) VALUES (?)");
                $stmt->execute([$authorName]);
                $authorId = $pdo->lastInsertId();
            }
            
            $stmt = $pdo->prepare("INSERT INTO book_authors (book_id, author_id) VALUES (?, ?)");
            $stmt->execute([$bookId, $authorId]);

            $pdo->commit();
            $success = "Book added successfully.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Datebase Error: " . $e->getMessage();
        }
    }
}

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

// Fetch Categories for Filter
$cats = $pdo->query("SELECT category_name FROM categories")->fetchAll(PDO::FETCH_COLUMN);

include '../includes/header.php';
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl text-gray-900 mb-1">Book Catalog</h1>
        <p class="text-gray-600">Browse and manage library books</p>
    </div>
    <button onclick="document.getElementById('addBookForm').classList.toggle('hidden')" class="btn btn-primary">
        <i data-lucide="plus"></i>
        Add New Book
    </button>
</div>

<?php if ($error): ?>
    <div class="mb-4 p-3 bg-red-100 text-red-700 rounded border border-red-200"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="mb-4 p-3 bg-green-100 text-green-700 rounded border border-green-200"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<!-- Add Book Form -->
<div id="addBookForm" class="hidden mb-6 bg-white p-6 rounded shadow-sm border border-gray-200">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Add New Book</h2>
    <form method="POST">
        <input type="hidden" name="action" value="add_book">
        <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1rem;">
            <div>
                <label class="block text-sm text-gray-700 mb-1">Book Title</label>
                <input type="text" name="title" class="form-control" required placeholder="Book Title">
            </div>
            <div>
                <label class="block text-sm text-gray-700 mb-1">ISBN</label>
                <input type="text" name="isbn" class="form-control" required placeholder="ISBN-13">
            </div>
            <div>
                <label class="block text-sm text-gray-700 mb-1">Author</label>
                <input type="text" name="author" class="form-control" required placeholder="Author Name">
            </div>
            <div>
                <label class="block text-sm text-gray-700 mb-1">Category</label>
                <input type="text" name="category" class="form-control" required placeholder="Category (e.g. Science)">
            </div>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary">Save Book</button>
            <button type="button" onclick="document.getElementById('addBookForm').classList.add('hidden')" class="btn" style="background: #e5e7eb; color: #374151;">Cancel</button>
        </div>
    </form>
</div>

<!-- Search & Filter -->
<div class="mb-6 bg-white p-4 rounded shadow-sm border border-gray-200">
    <form method="GET" class="flex gap-4 items-center">
        <div class="header-search" style="margin: 0; flex: 1; max-width: none;">
            <i data-lucide="search"></i>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by title or ISBN...">
        </div>
        <select name="category" class="form-control" style="width: 200px;" onchange="this.form.submit()">
            <option value="All">All Categories</option>
            <?php foreach ($cats as $cat): ?>
                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $filter === $cat ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<!-- Books Grid -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
    <?php foreach ($books as $book): ?>
        <div class="bg-white p-5 rounded shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
            <div class="mb-3">
                <h3 class="font-semibold text-gray-900 mb-1 truncate" title="<?php echo htmlspecialchars($book['title']); ?>">
                    <?php echo htmlspecialchars($book['title']); ?>
                </h3>
                <p class="text-sm text-gray-600 mb-1"><?php echo htmlspecialchars($book['authors']); ?></p>
                <p class="text-xs text-gray-500">ISBN: <?php echo htmlspecialchars($book['isbn']); ?></p>
            </div>
            
            <div class="flex items-center justify-between mb-4">
                <span class="badge badge-gray"><?php echo htmlspecialchars($book['category_name']); ?></span>
                <span class="badge <?php echo $book['status_name'] === 'Available' ? 'badge-green' : 'badge-red'; ?>">
                    <?php echo htmlspecialchars($book['status_name']); ?>
                </span>
            </div>
            
            <button class="btn w-full <?php echo $book['status_name'] === 'Available' ? 'btn-primary' : ''; ?>" 
                    style="justify-content: center; background-color: <?php echo $book['status_name'] === 'Available' ? '' : '#e5e7eb; color: #9ca3af; cursor: not-allowed;'; ?>"
                    <?php echo $book['status_name'] === 'Available' ? '' : 'disabled'; ?>>
                <?php echo $book['status_name'] === 'Available' ? 'Issue Book' : 'Not Available'; ?>
            </button>
        </div>
    <?php endforeach; ?>
    <?php if (count($books) === 0): ?>
        <div class="col-span-full text-center py-12 text-gray-500">
            No books found.
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
