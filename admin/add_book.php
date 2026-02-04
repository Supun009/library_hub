<?php
// admin/add_book.php
require_once '../config/db_config.php';
require_once '../includes/auth_middleware.php';

requireRole('admin');

$pageTitle = 'Add New Book';
$error = '';
$success = '';

// Fetch Categories for Dropdowns
$categories = $pdo->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC")->fetchAll();
// Fetch Authors for Dropdown
$authors = $pdo->query("SELECT author_id, name FROM authors ORDER BY name ASC")->fetchAll();

// Handle Add Book
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_book') {
    $title = trim($_POST['title']);
    $isbn = trim($_POST['isbn']);
    $categoryId = $_POST['category_id'] ?? '';
    
    // Author Logic
    $authorId = $_POST['author_id'] ?? '';
    $newAuthorName = trim($_POST['new_author_name'] ?? '');

    if ($authorId === 'new') {
        $authorName = $newAuthorName; // For validation check
    } else {
        $authorName = 'Existing'; // Placeholder to pass empty check
    }

    if (empty($title) || empty($isbn) || empty($categoryId) || ($authorId === 'new' && empty($newAuthorName)) || empty($authorId)) {
        $error = "All fields are required.";
    } else {
        try {
            // Check for duplicate ISBN
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE isbn = ?");
            $stmt->execute([$isbn]);
            if ($stmt->fetchColumn() > 0) {
                $error = "A book with this ISBN already exists.";
            } else {
                $pdo->beginTransaction();

                // 1. Get Status ID (Available)
                $stmt = $pdo->query("SELECT status_id FROM status WHERE status_name = 'Available'");
                $statusId = $stmt->fetchColumn();
                if (!$statusId) {
                    $pdo->exec("INSERT INTO status (status_name) VALUES ('Available')");
                    $statusId = $pdo->lastInsertId();
                }

                // 2. Insert Book
                $stmt = $pdo->prepare("INSERT INTO books (title, isbn, category_id, status_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $isbn, $categoryId, $statusId]);
                $bookId = $pdo->lastInsertId();

                // 3. Handle Author
                if ($authorId === 'new') {
                    // Check if new name actually exists to avoid dups
                    $stmt = $pdo->prepare("SELECT author_id FROM authors WHERE name = ?");
                    $stmt->execute([$newAuthorName]);
                    $existingId = $stmt->fetchColumn();
                    
                    if ($existingId) {
                        $authorId = $existingId;
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO authors (name) VALUES (?)");
                        $stmt->execute([$newAuthorName]);
                        $authorId = $pdo->lastInsertId();
                    }
                }
                
                // Link Author to Book
                $stmt = $pdo->prepare("INSERT INTO book_authors (book_id, author_id) VALUES (?, ?)");
                $stmt->execute([$bookId, $authorId]);

                $pdo->commit();
                // Redirect to Manage Books with success message
                header("Location: manage_books.php?msg=book_added");
                exit;
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl text-gray-900 mb-1">Add New Book</h1>
        <p class="text-gray-600">Enter details to add a new book to the catalog.</p>
    </div>
    <a href="manage_books.php" class="btn" style="background: #e5e7eb; color: #374151;">
        <i data-lucide="arrow-left"></i>
        Back to Catalog
    </a>
</div>

<?php if ($error): ?>
    <div class="mb-4 p-3 bg-red-100 text-red-700 rounded border border-red-200"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="bg-white p-6 rounded shadow-sm border border-gray-200">
    <form method="POST">
        <input type="hidden" name="action" value="add_book">
        <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1rem;">
            <div>
                <label class="block text-sm text-gray-700 mb-1">Book Title</label>
                <input type="text" name="title" class="form-control" required placeholder="Book Title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
            </div>
            <div>
                <label class="block text-sm text-gray-700 mb-1">ISBN</label>
                <input type="text" name="isbn" class="form-control" required placeholder="ISBN-13" value="<?php echo htmlspecialchars($_POST['isbn'] ?? ''); ?>">
            </div>
            <div>
                <label class="block text-sm text-gray-700 mb-1">Author Name</label>
                <select name="author_id" class="form-control" required onchange="toggleNewAuthor(this.value)">
                    <option value="">Select Author</option>
                    <?php foreach ($authors as $a): ?>
                        <option value="<?php echo $a['author_id']; ?>"><?php echo htmlspecialchars($a['name']); ?></option>
                    <?php endforeach; ?>
                    <option value="new" class="font-semibold text-indigo-600">+ Add New Author</option>
                </select>
                <input type="text" name="new_author_name" id="new_author_input" class="form-control mt-2 hidden" placeholder="Enter New Author Name">
            </div>
            <div>
                <label class="block text-sm text-gray-700 mb-1">Category</label>
                <select name="category_id" class="form-control" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary">Save Book</button>
            <a href="manage_books.php" class="btn" style="background: #e5e7eb; color: #374151;">Cancel</a>
        </div>
    </form>
</div>

<script>
function toggleNewAuthor(value) {
    const input = document.getElementById('new_author_input');
    if (value === 'new') {
        input.classList.remove('hidden');
        input.required = true;
        input.focus();
    } else {
        input.classList.add('hidden');
        input.required = false;
        input.value = '';
    }
}
</script>

<?php include '../includes/footer.php'; ?>
