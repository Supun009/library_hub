<?php
// member/search.php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireRole('member');

$pageTitle = 'Advanced Search';

// Initialize filters
$filters = [
    'title' => $_GET['title'] ?? '',
    'author' => $_GET['author'] ?? '',
    'category' => $_GET['category'] ?? 'All',
    'isbn' => $_GET['isbn'] ?? '',
    'year_from' => $_GET['year_from'] ?? '',
    'year_to' => $_GET['year_to'] ?? '',
    'status' => $_GET['status'] ?? 'all'
];

$results = [];
$hasSearched = !empty($_GET);

// Fetch Categories for Dropdown
$categories = $pdo->query("SELECT category_name FROM categories")->fetchAll(PDO::FETCH_COLUMN);

if ($hasSearched) {
    $query = "
        SELECT b.book_id, b.title, b.isbn, b.publication_year, c.category_name, s.status_name,
               GROUP_CONCAT(a.name SEPARATOR ', ') as authors
        FROM books b
        LEFT JOIN categories c ON b.category_id = c.category_id
        LEFT JOIN status s ON b.status_id = s.status_id
        LEFT JOIN book_authors ba ON b.book_id = ba.book_id
        LEFT JOIN authors a ON ba.author_id = a.author_id
    ";

    $conditions = [];
    $params = [];

    // Exclude deleted books
    $conditions[] = "b.deleted_at IS NULL";
    $conditions[] = "s.status_name != 'Deleted'";

    if (!empty($filters['title'])) {
        $conditions[] = "b.title LIKE ?";
        $params[] = "%" . $filters['title'] . "%";
    }

    if (!empty($filters['isbn'])) {
        $conditions[] = "b.isbn LIKE ?";
        $params[] = "%" . $filters['isbn'] . "%";
    }

    if ($filters['category'] !== 'All' && !empty($filters['category'])) {
        $conditions[] = "c.category_name = ?";
        $params[] = $filters['category'];
    }

    if (!empty($filters['year_from'])) {
        $conditions[] = "b.publication_year >= ?";
        $params[] = $filters['year_from'];
    }

    if (!empty($filters['year_to'])) {
        $conditions[] = "b.publication_year <= ?";
        $params[] = $filters['year_to'];
    }

    if ($filters['status'] === 'available') {
        $conditions[] = "s.status_name = 'Available'";
    } elseif ($filters['status'] === 'issued') {
        $conditions[] = "s.status_name = 'Issued'";
    }

    if (!empty($filters['author'])) {
        $conditions[] = "a.name LIKE ?";
        $params[] = "%" . $filters['author'] . "%";
    }

    if (count($conditions) > 0) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    $query .= " GROUP BY b.book_id ORDER BY b.title ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-6">
    <h1 class="page-heading">Advanced Search</h1>
    <p class="text-sm text-gray-600">Search for books using multiple filters</p>
</div>

<!-- Search Form -->
<?php 
$actionUrl = url('member/search');
include __DIR__ . '/../includes/search_form.php'; 
?>

<!-- Search Results -->
<?php if ($hasSearched): ?>
    <div class="table-container">
        <div class="table-header">
            <h2 class="text-lg font-semibold text-gray-900">Search Results</h2>
            <p class="text-gray-500 text-sm mt-1">Found <?php echo count($results); ?> book(s) matching your criteria</p>
        </div>
        
        <?php if (count($results) > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th class="whitespace-nowrap">Title</th>
                            <th class="whitespace-nowrap">Author(s)</th>
                            <th class="whitespace-nowrap">Category</th>
                            <th class="whitespace-nowrap">ISBN</th>
                            <th class="whitespace-nowrap">Year</th>
                            <th class="whitespace-nowrap">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $book): ?>
                            <tr>
                                <td class="font-medium text-gray-900">
                                    <div class="truncate max-w-xs" title="<?php echo htmlspecialchars($book['title']); ?>">
                                        <?php echo htmlspecialchars($book['title']); ?>
                                    </div>
                                </td>
                                <td class="text-gray-900">
                                    <div class="truncate max-w-xs" title="<?php echo htmlspecialchars($book['authors']); ?>">
                                        <?php echo htmlspecialchars($book['authors']); ?>
                                    </div>
                                </td>
                                <td class="text-gray-600 whitespace-nowrap"><?php echo htmlspecialchars($book['category_name']); ?></td>
                                <td class="text-gray-600 whitespace-nowrap"><?php echo htmlspecialchars($book['isbn']); ?></td>
                                <td class="text-gray-600 whitespace-nowrap"><?php echo htmlspecialchars($book['publication_year'] ?? 'N/A'); ?></td>
                                <td class="whitespace-nowrap">
                                    <span class="badge <?php echo $book['status_name'] === 'Available' ? 'badge-green' : 'badge-red'; ?>">
                                        <?php echo htmlspecialchars($book['status_name']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="p-12 text-center text-gray-500">
                No books found matching your search criteria.
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
