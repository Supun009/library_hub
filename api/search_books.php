<?php
// api/search_books.php
require_once '../config/db_config.php';
require_once '../includes/auth_middleware.php';

requireLogin();

header('Content-Type: application/json');

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$itemsPerPage = 12;
$offset = ($page - 1) * $itemsPerPage;

try {
    // Build base query
    $baseQuery = "
        FROM books b
        LEFT JOIN categories c ON b.category_id = c.category_id
        LEFT JOIN status s ON b.status_id = s.status_id
        LEFT JOIN book_authors ba ON b.book_id = ba.book_id
        LEFT JOIN authors a ON ba.author_id = a.author_id
    ";
    
    $conditions = [];
    $params = [];
    
    if ($search) {
        $conditions[] = "(b.title LIKE :search_title OR b.isbn LIKE :search_isbn OR a.name LIKE :search_author)";
        $params['search_title'] = "%$search%";
        $params['search_isbn'] = "%$search%";
        $params['search_author'] = "%$search%";
    }
    
    if ($category && $category !== 'All') {
        $conditions[] = "c.category_name = :category";
        $params['category'] = $category;
    }
    
    $whereClause = '';
    if (count($conditions) > 0) {
        $whereClause = " WHERE " . implode(" AND ", $conditions);
    }
    
    // Count total books
    $countQuery = "SELECT COUNT(DISTINCT b.book_id) as total " . $baseQuery . $whereClause;
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalItems = $countStmt->fetch()['total'];
    
    // Fetch books
    $query = "
        SELECT b.book_id, b.title, b.isbn, c.category_name, s.status_name,
               GROUP_CONCAT(DISTINCT a.name SEPARATOR ', ') as authors
        " . $baseQuery . $whereClause . "
        GROUP BY b.book_id 
        ORDER BY b.book_id DESC
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($query);
    
    // Bind search/filter parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value, PDO::PARAM_STR);
    }
    
    // Bind pagination parameters
    $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'books' => $books,
        'totalItems' => $totalItems,
        'currentPage' => $page,
        'totalPages' => ceil($totalItems / $itemsPerPage),
        'itemsPerPage' => $itemsPerPage
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
}
