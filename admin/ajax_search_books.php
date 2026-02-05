<?php
// admin/ajax_search_books.php
require_once '../config/db_config.php';
require_once '../includes/auth_middleware.php';

requireRole('admin');

header('Content-Type: application/json');

$searchTerm = $_GET['q'] ?? '';

if (strlen($searchTerm) < 2) {
    echo json_encode([]);
    exit;
}

try {
    // Search for available books by title or ISBN
    $stmt = $pdo->prepare("
        SELECT b.book_id, b.title, b.isbn
        FROM books b
        WHERE b.status_id = (SELECT status_id FROM status WHERE status_name = 'Available')
          AND (b.title LIKE :search OR b.isbn LIKE :search)
        ORDER BY b.title
        LIMIT 10
    ");
    
    $searchParam = '%' . $searchTerm . '%';
    $stmt->execute(['search' => $searchParam]);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($books);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
