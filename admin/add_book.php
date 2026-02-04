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

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 mb-1">Add New Book</h1>
        <p class="text-sm text-gray-600">Enter details to add a new book to the catalog.</p>
    </div>
    <a
        href="manage_books.php"
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
            </div>
            
            <!-- Category -->
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Category *</label>
                <select
                    name="category_id"
                    required
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                    <?php endforeach; ?>
                </select>
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
                href="manage_books.php"
                class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 transition-colors"
            >
                Cancel
            </a>
        </div>
    </form>
</div>

<!-- Author Validation Data for JS -->
<script>
const existingAuthors = <?php echo json_encode($authors); ?>;

function createAuthorRow(index) {
    const row = document.createElement('div');
    row.className = 'flex items-start gap-2 author-row';
    row.innerHTML = `
        <div class="flex-grow">
            <select
                name="authors[${index}][id]"
                required
                onchange="toggleAuthorInput(this, ${index})"
                class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
            >
                <option value="">Select Author</option>
                <option value="new" style="font-weight: bold; color: #2563eb;">+ Add New Author</option>
                ${existingAuthors.map(a => `<option value="${a.author_id}">${escapeHtml(a.name)}</option>`).join('')}
            </select>
            <input
                type="text"
                name="authors[${index}][new_name]"
                placeholder="Enter New Author Name"
                class="hidden mt-2 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
            >
        </div>
        ${index > 0 ? `
        <button
            type="button"
            onclick="this.closest('.author-row').remove(); lucide.createIcons();"
            title="Remove Author"
            class="inline-flex h-10 min-w-[42px] items-center justify-center rounded-md border border-red-200 bg-white px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors"
        >
            <i data-lucide="trash-2" class="h-4 w-4"></i>
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
