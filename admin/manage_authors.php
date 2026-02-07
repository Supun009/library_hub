<?php
// admin/manage_authors.php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireRole('admin');

$pageTitle = 'Manage Authors';
$error = '';
$success = '';

// Handle Add Author
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_author') {
    $authorName = trim($_POST['author_name']);
    
    if (empty($authorName)) {
        $error = "Author name is required.";
    } else {
        try {
            // Check if author already exists
            $stmt = $pdo->prepare("SELECT author_id FROM authors WHERE name = ?");
            $stmt->execute([$authorName]);
            
            if ($stmt->fetchColumn()) {
                $error = "Author already exists.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO authors (name) VALUES (?)");
                $stmt->execute([$authorName]);
                $success = "Author added successfully!";
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Handle Delete Author
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_author') {
    $authorId = $_POST['author_id'];
    
    try {
        // Check if author is in use
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM book_authors WHERE author_id = ?");
        $stmt->execute([$authorId]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $error = "Cannot delete author. They are associated with $count book(s).";
        } else {
            $stmt = $pdo->prepare("DELETE FROM authors WHERE author_id = ?");
            $stmt->execute([$authorId]);
            // Redirect to prevent form resubmission and clear state
            redirect('admin/authors?msg=deleted');
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Check for success message
if (isset($_GET['msg']) && $_GET['msg'] === 'deleted') {
    $success = "Author deleted successfully!";
}

// Pagination settings
$itemsPerPage = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $itemsPerPage;
$search = $_GET['search'] ?? '';

// Build Base Query
$baseQuery = "FROM authors a LEFT JOIN book_authors ba ON a.author_id = ba.author_id";
$whereClause = "";
$params = [];

if (!empty($search)) {
    $whereClause = " WHERE a.name LIKE :search";
    $params[':search'] = "%$search%";
}

// Get total count
$totalStmt = $pdo->prepare("SELECT COUNT(DISTINCT a.author_id) $baseQuery $whereClause");
foreach ($params as $key => $value) {
    $totalStmt->bindValue($key, $value);
}
$totalStmt->execute();
$totalAuthors = $totalStmt->fetchColumn();
$totalPages = ceil($totalAuthors / $itemsPerPage);

// Fetch authors with pagination
$query = "
    SELECT a.author_id, a.name, COUNT(ba.book_id) as book_count
    $baseQuery
    $whereClause
    GROUP BY a.author_id, a.name
    ORDER BY a.name ASC
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$authors = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <h1 class="page-heading">Manage Authors</h1>
    <p class="text-sm text-gray-600">Add, view, and manage book authors.</p>
</div>

<?php if ($error): ?>
    <div class="mb-4 rounded-md border border-red-200 bg-red-100 px-4 py-3 text-sm text-red-700">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="mb-4 rounded-md border border-green-200 bg-green-100 px-4 py-3 text-sm text-green-700">
        <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<!-- Add Author Form -->
<div class="mb-6 rounded-md border border-gray-200 bg-white p-6 shadow-sm">
    <h2 class="mb-4 text-lg font-semibold text-gray-900">Add New Author</h2>
    <form method="POST" class="flex flex-col sm:flex-row gap-2">
        <input type="hidden" name="action" value="add_author">
        <div class="flex-1">
            <input
                type="text"
                name="author_name"
                placeholder="Enter author name"
                required
                class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
            >
        </div>
        <button
            type="submit"
            class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 transition-colors w-full sm:w-auto"
        >
            <i data-lucide="plus" class="mr-2 h-4 w-4"></i>
            Add Author
        </button>
    </form>
</div>

<div class="rounded-md border border-gray-200 bg-white shadow-sm">
    <div class="border-b border-gray-200 px-6 py-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h2 class="text-lg font-semibold text-gray-900">
                All Authors (<?php echo $totalAuthors; ?>)
                <?php if ($totalPages > 1): ?>
                    <span class="text-sm font-normal text-gray-500">- Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                <?php endif; ?>
            </h2>
            <div class="relative w-full sm:w-auto">
                <form method="GET" action="" class="relative">
                    <input
                        type="text"
                        name="search"
                        value="<?php echo htmlspecialchars($search); ?>"
                        id="searchAuthors"
                        placeholder="Search authors..."
                        class="block w-full sm:w-64 rounded-md border border-gray-300 px-3 py-2 pl-10 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                    >
                    <i data-lucide="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                </form>
            </div>
        </div>
    </div>
    
    <?php if (empty($authors)): ?>
        <div class="px-6 py-12 text-center">
            <i data-lucide="user" class="mx-auto h-12 w-12 text-gray-400"></i>
            <p class="mt-2 text-sm text-gray-500">No authors found. Add your first author above.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Author Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Books</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($authors as $author): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">
                                <?php echo htmlspecialchars($author['name']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                <?php echo $author['book_count']; ?> book(s)
                            </td>
                            <td class="px-6 py-4 text-right text-sm whitespace-nowrap">
                                <button
                                    type="button"
                                    onclick="confirmDeleteAuthor(<?php echo $author['author_id']; ?>)"
                                    class="inline-flex items-center gap-1 text-red-600 hover:text-red-800 disabled:opacity-50 disabled:cursor-not-allowed disabled:text-gray-400"
                                    <?php echo $author['book_count'] > 0 ? 'disabled title="Cannot delete author in use"' : ''; ?>
                                >
                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                    Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
// Render pagination
if ($totalPages > 1) {
    require_once __DIR__ . '/../includes/pagination.php';
    renderPagination($page, $totalAuthors, $itemsPerPage);
}
?>

<script>
// Search functionality
let searchTimeout;
document.getElementById('searchAuthors')?.addEventListener('input', function(e) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        e.target.closest('form').submit();
    }, 500);
});

function confirmDeleteAuthor(authorId) {
    openDeleteModal(function() {
        // Create a temporary form to submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = window.location.href;
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_author';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'author_id';
        idInput.value = authorId;
        
        form.appendChild(actionInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    });
}
</script>

<?php 
include __DIR__ . '/../includes/delete_modal.php';
include __DIR__ . '/../includes/footer.php'; 
?>
