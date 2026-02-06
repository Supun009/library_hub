<?php
// admin/manage_categories.php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireRole('admin');

$pageTitle = 'Manage Categories';
$error = '';
$success = '';

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_category') {
    $categoryName = trim($_POST['category_name']);
    
    if (empty($categoryName)) {
        $error = "Category name is required.";
    } else {
        try {
            // Check if category already exists
            $stmt = $pdo->prepare("SELECT category_id FROM categories WHERE category_name = ?");
            $stmt->execute([$categoryName]);
            
            if ($stmt->fetchColumn()) {
                $error = "Category already exists.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO categories (category_name) VALUES (?)");
                $stmt->execute([$categoryName]);
                $success = "Category added successfully!";
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Handle Delete Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_category') {
    $categoryId = $_POST['category_id'];
    
    try {
        // Check if category is in use
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE category_id = ?");
        $stmt->execute([$categoryId]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $error = "Cannot delete category. It is being used by $count book(s).";
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
            $stmt->execute([$categoryId]);
            $success = "Category deleted successfully!";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch all categories with book count
$stmt = $pdo->query("
    SELECT c.category_id, c.category_name, COUNT(b.book_id) as book_count
    FROM categories c
    LEFT JOIN books b ON c.category_id = b.category_id
    GROUP BY c.category_id, c.category_name
    ORDER BY c.category_name ASC
");
$categories = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-semibold text-gray-900 mb-1">Manage Categories</h1>
    <p class="text-sm text-gray-600">Add, view, and manage book categories.</p>
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

<!-- Add Category Form -->
<div class="mb-6 rounded-md border border-gray-200 bg-white p-6 shadow-sm">
    <h2 class="mb-4 text-lg font-semibold text-gray-900">Add New Category</h2>
    <form method="POST" class="flex gap-2">
        <input type="hidden" name="action" value="add_category">
        <input
            type="text"
            name="category_name"
            placeholder="Enter category name"
            required
            class="block flex-1 rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
        >
        <button
            type="submit"
            class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 transition-colors"
        >
            <i data-lucide="plus" class="mr-2 h-4 w-4"></i>
            Add Category
        </button>
    </form>
</div>

<!-- Categories List -->
<div class="rounded-md border border-gray-200 bg-white shadow-sm">
    <div class="border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">All Categories (<?php echo count($categories); ?>)</h2>
            <div class="relative">
                <input
                    type="text"
                    id="searchCategories"
                    placeholder="Search categories..."
                    class="block w-64 rounded-md border border-gray-300 px-3 py-2 pl-10 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
                <i data-lucide="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
            </div>
        </div>
    </div>
    
    <?php if (empty($categories)): ?>
        <div class="px-6 py-12 text-center">
            <i data-lucide="folder-open" class="mx-auto h-12 w-12 text-gray-400"></i>
            <p class="mt-2 text-sm text-gray-500">No categories found. Add your first category above.</p>
        </div>
    <?php else: ?>
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Category Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Books</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($categories as $category): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <?php echo $category['book_count']; ?> book(s)
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                <input type="hidden" name="action" value="delete_category">
                                <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">
                                <button
                                    type="submit"
                                    class="inline-flex items-center gap-1 text-red-600 hover:text-red-800"
                                    <?php echo $category['book_count'] > 0 ? 'disabled title="Cannot delete category in use"' : ''; ?>
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
</div>

<script>
// Search functionality
document.getElementById('searchCategories')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const categoryName = row.querySelector('td:first-child')?.textContent.toLowerCase() || '';
        if (categoryName.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
