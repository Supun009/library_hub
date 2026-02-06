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
            $success = "Author deleted successfully!";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Pagination settings
$itemsPerPage = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $itemsPerPage;

// Get total count
$totalStmt = $pdo->query("SELECT COUNT(*) FROM authors");
$totalAuthors = $totalStmt->fetchColumn();
$totalPages = ceil($totalAuthors / $itemsPerPage);

// Fetch authors with pagination
$stmt = $pdo->prepare("
    SELECT a.author_id, a.name, COUNT(ba.book_id) as book_count
    FROM authors a
    LEFT JOIN book_authors ba ON a.author_id = ba.author_id
    GROUP BY a.author_id, a.name
    ORDER BY a.name ASC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$authors = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-semibold text-gray-900 mb-1">Manage Authors</h1>
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
    <form method="POST" class="flex gap-2">
        <input type="hidden" name="action" value="add_author">
        <input
            type="text"
            name="author_name"
            placeholder="Enter author name"
            required
            class="block flex-1 rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
        >
        <button
            type="submit"
            class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 transition-colors"
        >
            <i data-lucide="plus" class="mr-2 h-4 w-4"></i>
            Add Author
        </button>
    </form>
</div>

<div class="rounded-md border border-gray-200 bg-white shadow-sm">
    <div class="border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">
                All Authors (<?php echo $totalAuthors; ?>)
                <?php if ($totalPages > 1): ?>
                    <span class="text-sm font-normal text-gray-500">- Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                <?php endif; ?>
            </h2>
            <div class="relative">
                <input
                    type="text"
                    id="searchAuthors"
                    placeholder="Search authors..."
                    class="block w-64 rounded-md border border-gray-300 px-3 py-2 pl-10 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
                <i data-lucide="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
            </div>
        </div>
    </div>
    
    <?php if (empty($authors)): ?>
        <div class="px-6 py-12 text-center">
            <i data-lucide="user" class="mx-auto h-12 w-12 text-gray-400"></i>
            <p class="mt-2 text-sm text-gray-500">No authors found. Add your first author above.</p>
        </div>
    <?php else: ?>
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
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($author['name']); ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?php echo $author['book_count']; ?> book(s)
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this author?');">
                                <input type="hidden" name="action" value="delete_author">
                                <input type="hidden" name="author_id" value="<?php echo $author['author_id']; ?>">
                                <button
                                    type="submit"
                                    class="inline-flex items-center gap-1 text-red-600 hover:text-red-800"
                                    <?php echo $author['book_count'] > 0 ? 'disabled title="Cannot delete author in use"' : ''; ?>
                                >
                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="border-t border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $itemsPerPage, $totalAuthors); ?> of <?php echo $totalAuthors; ?> authors
                </div>
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            <i data-lucide="chevron-left" class="mr-1 h-4 w-4"></i>
                            Previous
                        </a>
                    <?php endif; ?>
                    
                    <div class="flex gap-1">
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <a href="?page=<?php echo $i; ?>" class="inline-flex items-center justify-center rounded-md border <?php echo $i === $page ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'; ?> px-3 py-2 text-sm font-medium min-w-[40px]">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Next
                            <i data-lucide="chevron-right" class="ml-1 h-4 w-4"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Search functionality
document.getElementById('searchAuthors')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const authorName = row.querySelector('td:first-child')?.textContent.toLowerCase() || '';
        if (authorName.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
