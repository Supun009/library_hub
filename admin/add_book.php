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
    $pubYear = $_POST['publication_year'] ?? NULL;
    
    // Author Logic - Expecting an array
    $rawAuthors = $_POST['authors'] ?? []; // Array of ['id' => ..., 'new_name' => ...]

    if (empty($title) || empty($isbn) || empty($categoryId) || empty($rawAuthors)) {
        $error = "Title, ISBN, Category, and at least one Author are required.";
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
                $stmt = $pdo->prepare("INSERT INTO books (title, isbn, category_id, status_id, publication_year) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$title, $isbn, $categoryId, $statusId, $pubYear ?: NULL]);
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
                header("Location: manage_books.php?msg=book_added");
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
    <form method="POST" id="addBookForm">
        <input type="hidden" name="action" value="add_book">
        
        <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1rem;">
            <!-- Title -->
            <div style="grid-column: span 2;">
                <label class="block text-sm text-gray-700 mb-1">Book Title *</label>
                <input type="text" name="title" class="form-control" required placeholder="e.g. The Pragmatic Programmer" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
            </div>
            
            <!-- ISBN -->
            <div>
                <label class="block text-sm text-gray-700 mb-1">ISBN *</label>
                <input type="text" name="isbn" class="form-control" required placeholder="ISBN-13" value="<?php echo htmlspecialchars($_POST['isbn'] ?? ''); ?>">
            </div>

            <!-- Publication Year -->
            <div>
                <label class="block text-sm text-gray-700 mb-1">Publication Year</label>
                <input type="number" name="publication_year" class="form-control" placeholder="e.g. 2023" min="1000" max="<?php echo date('Y') + 1; ?>" value="<?php echo htmlspecialchars($_POST['publication_year'] ?? ''); ?>">
            </div>
            
            <!-- Category -->
            <div>
                <label class="block text-sm text-gray-700 mb-1">Category *</label>
                <select name="category_id" class="form-control" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Authors Section -->
        <div class="mb-6">
            <div class="flex justify-between items-center mb-2">
                <label class="block text-sm text-gray-700">Authors *</label>
                <button type="button" onclick="addAuthorRow()" class="text-sm text-blue-600 font-medium hover:text-blue-800 flex items-center gap-1">
                    <i data-lucide="plus-circle" style="width: 16px;"></i> Add Another Author
                </button>
            </div>
            
            <div id="authors-container" class="space-y-3">
                <!-- Rows will be added here by JS, start with one -->
            </div>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary">Save Book</button>
            <a href="manage_books.php" class="btn" style="background: #e5e7eb; color: #374151;">Cancel</a>
        </div>
    </form>
</div>

<!-- Author Validation Data for JS -->
<script>
const existingAuthors = <?php echo json_encode($authors); ?>;

function createAuthorRow(index) {
    const row = document.createElement('div');
    row.className = 'flex gap-2 items-start author-row';
    row.innerHTML = `
        <div class="flex-grow">
            <select name="authors[${index}][id]" class="form-control" required onchange="toggleAuthorInput(this, ${index})">
                <option value="">Select Author</option>
                ${existingAuthors.map(a => `<option value="${a.author_id}">${escapeHtml(a.name)}</option>`).join('')}
                <option value="new" class="font-bold text-blue-600">+ Add New Author</option>
            </select>
            <input type="text" name="authors[${index}][new_name]" class="form-control mt-2 hidden" placeholder="Enter New Author Name">
        </div>
        ${index > 0 ? `
        <button type="button" onclick="this.closest('.author-row').remove()" class="p-2 text-red-500 hover:bg-red-50 rounded" title="Remove">
            <i data-lucide="trash-2" style="width: 18px;"></i>
        </button>` : ''}
    `;
    return row;
}

function addAuthorRow() {
    const container = document.getElementById('authors-container');
    const index = container.children.length;
    container.appendChild(createAuthorRow(index));
    lucide.createIcons();
}

function toggleAuthorInput(select, index) {
    const input = select.nextElementSibling;
    if (select.value === 'new') {
        input.classList.remove('hidden');
        input.required = true;
        input.focus();
    } else {
        input.classList.add('hidden');
        input.required = false;
        input.value = '';
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialize with one row
document.addEventListener('DOMContentLoaded', () => {
    addAuthorRow();
});
</script>

<?php include '../includes/footer.php'; ?>
