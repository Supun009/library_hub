<?php
// admin/add_book.php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

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
    $newCategoryName = trim($_POST['new_category_name'] ?? '');
    $pubYear = $_POST['publication_year'] ?? NULL;
    
    // Author Logic - Expecting an array
    $rawAuthors = $_POST['authors'] ?? []; // Array of ['id' => ..., 'new_name' => ...]

    if (empty($title) || empty($isbn) || empty($categoryId) || empty($rawAuthors)) {
        $error = "Title, ISBN, Category, and at least one Author are required.";
    } elseif ($categoryId === 'new' && empty($newCategoryName)) {
        $error = "Please enter a name for the new category.";
    } elseif (!empty($pubYear) && (!is_numeric($pubYear) || $pubYear < 1000 || $pubYear > date('Y') + 1)) {
        $error = "Please enter a valid publication year (1000-" . (date('Y') + 1) . ").";
    } else {
        try {
            // Check for duplicate ISBN
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE isbn = ?");
            $stmt->execute([$isbn]);
            if ($stmt->fetchColumn() > 0) {
                $error = "A book with this ISBN already exists.";
            } else {
                $pdo->beginTransaction();

                // 1. Handle New Category
                if ($categoryId === 'new') {
                    // Check if exists
                    $stmt = $pdo->prepare("SELECT category_id FROM categories WHERE category_name = ?");
                    $stmt->execute([$newCategoryName]);
                    $existingCatId = $stmt->fetchColumn();
                    
                    if ($existingCatId) {
                        $categoryId = $existingCatId;
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO categories (category_name) VALUES (?)");
                        $stmt->execute([$newCategoryName]);
                        $categoryId = $pdo->lastInsertId();
                    }
                }

                // 2. Get Status ID (Available)
                $stmt = $pdo->query("SELECT status_id FROM status WHERE status_name = 'Available'");
                $statusId = $stmt->fetchColumn();
                if (!$statusId) {
                    $pdo->exec("INSERT INTO status (status_name) VALUES ('Available')");
                    $statusId = $pdo->lastInsertId();
                }

                // 2. Insert Book
                $totalCopies = (int)($_POST['total_copies'] ?? 1);
                // Ensure at least 1 copy
                if ($totalCopies < 1) $totalCopies = 1;
                
                $stmt = $pdo->prepare("INSERT INTO books (title, isbn, category_id, status_id, publication_year, total_copies, available_copies) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $isbn, $categoryId, $statusId, $pubYear ?: NULL, $totalCopies, $totalCopies]);
                $bookId = $pdo->lastInsertId();

                // 3. Handle Authors
                $uniqueAuthorIds = [];

                foreach ($rawAuthors as $inputUser) {
                    $authId = $inputUser['id'] ?? '';
                    $newName = trim($inputUser['new_name'] ?? '');

                    if (empty($authId)) continue;

                    $finalAuthorId = null;

                    if ($authId === 'new') {
                        if (!empty($newName)) {
                            // Check if new name actually exists
                            $stmt = $pdo->prepare("SELECT author_id FROM authors WHERE name = ?");
                            $stmt->execute([$newName]);
                            $existingId = $stmt->fetchColumn();
                            
                            if ($existingId) {
                                $finalAuthorId = $existingId;
                            } else {
                                $stmt = $pdo->prepare("INSERT INTO authors (name) VALUES (?)");
                                $stmt->execute([$newName]);
                                $finalAuthorId = $pdo->lastInsertId();
                            }
                        }
                    } else {
                        $finalAuthorId = $authId;
                    }

                    if ($finalAuthorId && !in_array($finalAuthorId, $uniqueAuthorIds)) {
                        $uniqueAuthorIds[] = $finalAuthorId;
                        // Link Author to Book
                        $stmt = $pdo->prepare("INSERT INTO book_authors (book_id, author_id) VALUES (?, ?)");
                        $stmt->execute([$bookId, $finalAuthorId]);
                    }
                }

                if (empty($uniqueAuthorIds)) {
                    throw new Exception("No valid authors provided.");
                }

                $pdo->commit();
                // Redirect to Manage Books with success message
                redirect('admin/books?msg=book_added');
                exit;
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Error: " . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 mb-1">Add New Book</h1>
        <p class="text-sm text-gray-600">Enter details to add a new book to the catalog.</p>
    </div>
    <a
        href="<?php echo url('admin/books'); ?>"
        class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 transition-colors"
    >
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Back to Catalog
    </a>
</div>

<?php if ($error): ?>
    <div class="mb-4 rounded-md border border-red-200 bg-red-100 px-4 py-3 text-sm text-red-700">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div class="rounded-md border border-gray-200 bg-white p-6 shadow-sm">
    <form method="POST" id="addBookForm">
        <input type="hidden" name="action" value="add_book">
        
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 mb-4">
            <!-- Title -->
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700">Book Title *</label>
                <input
                    type="text"
                    name="title"
                    required
                    placeholder="e.g. The Pragmatic Programmer"
                    value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
            </div>
            
            <!-- ISBN -->
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">ISBN *</label>
                <input
                    type="text"
                    name="isbn"
                    required
                    placeholder="ISBN-13"
                    value="<?php echo htmlspecialchars($_POST['isbn'] ?? ''); ?>"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
            </div>

            <!-- Publication Year -->
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Publication Year</label>
                <input
                    type="number"
                    name="publication_year"
                    placeholder="e.g. 2023"
                    min="1000"
                    max="<?php echo date('Y') + 1; ?>"
                    value="<?php echo htmlspecialchars($_POST['publication_year'] ?? ''); ?>"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
                <p class="mt-1 text-xs text-gray-500">YYYY format (1000 - <?php echo date('Y') + 1; ?>)</p>
            </div>

            <!-- Stock / Copies -->
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Number of Copies *</label>
                <input
                    type="number"
                    name="total_copies"
                    required
                    min="1"
                    value="<?php echo htmlspecialchars($_POST['total_copies'] ?? '1'); ?>"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
            </div>
            
            <!-- Category -->
            <div class="relative">
                <label class="mb-1 block text-sm font-medium text-gray-700">Category *</label>
                
                <!-- Search Input -->
                <div id="category_search_container">
                    <input type="hidden" name="category_id" id="category_id_hidden" value="<?php echo htmlspecialchars($_POST['category_id'] ?? ''); ?>" required>
                    <input
                        type="text"
                        id="category_search"
                        placeholder="Search category..."
                        autocomplete="off"
                        class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                    >
                    <div id="category_dropdown" class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg hidden max-h-60 overflow-auto">
                        <!-- Categories will be populated here -->
                    </div>
                </div>

                <!-- New Category Input -->
                <div id="new_category_input_container" class="hidden items-center gap-2">
                    <input
                        type="text"
                        name="new_category_name"
                        id="new_category_name"
                        placeholder="Enter New Category Name"
                        class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                    >
                    <button type="button" id="cancel_new_category" class="text-sm text-red-600 hover:text-red-800 font-medium whitespace-nowrap">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Authors Section -->
        <div class="mb-6">
            <div class="mb-2 flex items-center justify-between">
                <label class="text-sm font-medium text-gray-700">Authors *</label>
                <button
                    type="button"
                    onclick="addAuthorRow()"
                    class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 transition-colors"
                >
                    <i data-lucide="plus-circle" class="h-4 w-4"></i>
                    Add Another Author
                </button>
            </div>

            <div id="authors-container" class="space-y-3">
                <!-- Rows will be added here by JS, start with one -->
            </div>
        </div>

        <div class="flex gap-2">
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 transition-colors"
            >
                Save Book
            </button>
            <a
                href="<?php echo url('admin/books'); ?>"
                class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 transition-colors"
            >
                Cancel
            </a>
        </div>
    </form>
</div>

<!-- Scripts -->
<script>
// Initialize data for external script
window.authorsData = <?php echo json_encode($authors); ?>;
window.categoriesData = <?php echo json_encode($categories); ?>;
</script>
<script src="<?php echo url('admin/js/add_book.js'); ?>"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
