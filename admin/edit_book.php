<?php
// admin/edit_book.php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireRole('admin');

$bookId = $_GET['id'] ?? null;
if (!$bookId) {
    redirect('admin/books');
    exit;
}

$pageTitle = 'Edit Book';
$error = '';
$success = '';

// Fetch Categories for Dropdowns
$categories = $pdo->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC")->fetchAll();
// Fetch Authors for Dropdown
$authors = $pdo->query("SELECT author_id, name FROM authors ORDER BY name ASC")->fetchAll();

// Fetch Current Book Details
try {
    $stmt = $pdo->prepare("
        SELECT * FROM books 
        WHERE book_id = ?
    ");
    $stmt->execute([$bookId]);
    $book = $stmt->fetch();
    
    if (!$book) {
        redirect('admin/books');
        exit;
    }

    // Fetch Current Authors for this book
    $stmt = $pdo->prepare("SELECT author_id FROM book_authors WHERE book_id = ?");
    $stmt->execute([$bookId]);
    $currentAuthorIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    $error = "Error fetching book details: " . $e->getMessage();
}

// Handle Update Book
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_book') {
    $title = trim($_POST['title']);
    $isbn = trim($_POST['isbn']);
    $categoryId = $_POST['category_id'] ?? '';
    $pubYear = $_POST['publication_year'] ?? NULL;
    $totalCopies = (int)($_POST['total_copies'] ?? 1);
    
    // Author IDs - Expecting an array of author IDs
    $authorIds = $_POST['author_ids'] ?? [];

    if (empty($title) || empty($isbn) || empty($categoryId) || empty($authorIds)) {
        $error = "Title, ISBN, Category, and at least one Author are required.";
    } elseif (!empty($pubYear) && (!is_numeric($pubYear) || $pubYear < 1000 || $pubYear > date('Y') + 1)) {
        $error = "Please enter a valid publication year (1000-" . (date('Y') + 1) . ").";
    } elseif ($totalCopies < 1) {
        $error = "Number of copies must be at least 1.";
    } else {
        try {
            // Check for duplicate ISBN (excluding current book)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE isbn = ? AND book_id != ? AND deleted_at IS NULL");
            $stmt->execute([$isbn, $bookId]);
            if ($stmt->fetchColumn() > 0) {
                $error = "A book with this ISBN already exists.";
            } else {
                $pdo->beginTransaction();

                // Calculate difference in copies to adjust available copies
                $copyDiff = $totalCopies - $book['total_copies'];
                $newAvailable = $book['available_copies'] + $copyDiff;
                
                if ($newAvailable < 0) {
                    throw new Exception("Cannot reduce total copies below currently rented amount.");
                }

                // Update Status if stock changes (e.g. if we add stock to an out-of-stock book)
                // If new available > 0, set to Available. If 0, depends on logic, but usually Available if > 0.
                // Assuming status logic: if avail > 0 then Available, else Out of Stock?
                // The DB has status_id. Let's get "Available" ID.
                $stmt = $pdo->query("SELECT status_id FROM status WHERE status_name = 'Available'");
                $availStatusId = $stmt->fetchColumn();
                // Get "Out of Stock" ID just in case
                $stmt = $pdo->query("SELECT status_id FROM status WHERE status_name = 'Out of Stock'");
                $oosStatusId = $stmt->fetchColumn(); 
                if (!$oosStatusId) {
                     $pdo->exec("INSERT INTO status (status_name) VALUES ('Out of Stock')");
                     $oosStatusId = $pdo->lastInsertId();
                }

                $newStatusId = ($newAvailable > 0) ? $availStatusId : $oosStatusId;

                // Update Book
                $stmt = $pdo->prepare("
                    UPDATE books 
                    SET title = ?, isbn = ?, category_id = ?, status_id = ?, publication_year = ?, total_copies = ?, available_copies = ?
                    WHERE book_id = ?
                ");
                $stmt->execute([$title, $isbn, $categoryId, $newStatusId, $pubYear ?: NULL, $totalCopies, $newAvailable, $bookId]);

                // Update Authors: Sync strategy (Delete all, Insert new)
                $pdo->prepare("DELETE FROM book_authors WHERE book_id = ?")->execute([$bookId]);
                
                $uniqueAuthorIds = array_unique(array_filter($authorIds));
                 if (empty($uniqueAuthorIds)) {
                    throw new Exception("At least one valid author is required.");
                }
                
                foreach ($uniqueAuthorIds as $authorId) {
                    $stmt = $pdo->prepare("INSERT INTO book_authors (book_id, author_id) VALUES (?, ?)");
                    $stmt->execute([$bookId, $authorId]);
                }

                $pdo->commit();
                redirect('admin/books?msg=book_updated');
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
        <h1 class="page-heading">Edit Book</h1>
        <p class="text-sm text-gray-600">Update book details.</p>
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
    <form method="POST" id="editBookForm">
        <input type="hidden" name="action" value="update_book">
        
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 mb-4">
            <!-- Title -->
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium text-gray-700">Book Title *</label>
                <input
                    type="text"
                    name="title"
                    required
                    placeholder="e.g. The Pragmatic Programmer"
                    value="<?php echo htmlspecialchars($_POST['title'] ?? $book['title']); ?>"
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
                    value="<?php echo htmlspecialchars($_POST['isbn'] ?? $book['isbn']); ?>"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
            </div>

            <!-- Publication Year -->
             <div class="relative">
                <label class="mb-1 block text-sm font-medium text-gray-700">Publication Year</label>
                <input
                    type="text"
                    id="publication_year_display"
                    placeholder="Select Year"
                    readonly
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 cursor-pointer bg-white"
                    onclick="toggleYearDropdown(event)"
                    value="<?php echo htmlspecialchars($_POST['publication_year'] ?? $book['publication_year'] ?? ''); ?>"
                >
                <input type="hidden" name="publication_year" id="publication_year" value="<?php echo htmlspecialchars($_POST['publication_year'] ?? $book['publication_year'] ?? ''); ?>">
                
                <div id="year_dropdown_container" class="hidden absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto">
                    <?php
                    $currentYear = date('Y');
                    // Range: Next year down to 1900
                    for ($year = $currentYear + 1; $year >= 1900; $year--) {
                        echo "<div class='cursor-pointer px-3 py-2 hover:bg-gray-100 text-sm' onclick=\"selectYear('$year')\">$year</div>";
                    }
                    ?>
                </div>
            </div>

            <!-- Stock / Copies -->
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Number of Copies *</label>
                <input
                    type="number"
                    name="total_copies"
                    required
                    min="1"
                    value="<?php echo htmlspecialchars($_POST['total_copies'] ?? $book['total_copies']); ?>"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
                <p class="mt-1 text-xs text-gray-500">Currently Available: <?php echo $book['available_copies']; ?></p>
            </div>
            
            <!-- Category -->
            <div class="relative">
                <label class="mb-1 block text-sm font-medium text-gray-700">Category *</label>
                <input type="hidden" name="category_id" id="category_id_hidden" value="<?php echo htmlspecialchars($_POST['category_id'] ?? $book['category_id']); ?>" required>
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
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Add Author
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
                Update Book
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

// Initialize form data for edit
window.initialAuthors = <?php echo json_encode($currentAuthorIds); ?>;

// Custom Year Dropdown Logic
function toggleYearDropdown(e) {
    e.stopPropagation();
    const container = document.getElementById('year_dropdown_container');
    container.classList.toggle('hidden');
}

function selectYear(year) {
    document.getElementById('publication_year').value = year;
    document.getElementById('publication_year_display').value = year;
    document.getElementById('year_dropdown_container').classList.add('hidden');
}

document.addEventListener('click', function(e) {
    const container = document.getElementById('year_dropdown_container');
    const input = document.getElementById('publication_year_display');
    if (container && !container.contains(e.target) && e.target !== input) {
        container.classList.add('hidden');
    }
});
</script>
<script src="<?php echo url('admin/js/add_book.js'); ?>"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
