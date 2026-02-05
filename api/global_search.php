<?php
// api/global_search.php
require_once '../config/db_config.php';
require_once '../includes/auth_middleware.php';

// Ensure user is logged in
requireLogin();

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';
$results = [];

if (strlen($query) >= 2) {
    try {
        // Search for books by title, ISBN, or author
        $searchQuery = "
            SELECT DISTINCT
                b.book_id,
                b.title,
                b.isbn,
                c.category_name,
                s.status_name,
                GROUP_CONCAT(DISTINCT a.name SEPARATOR ', ') as authors
            FROM books b
            LEFT JOIN categories c ON b.category_id = c.category_id
            LEFT JOIN status s ON b.status_id = s.status_id
            LEFT JOIN book_authors ba ON b.book_id = ba.book_id
            LEFT JOIN authors a ON ba.author_id = a.author_id
            WHERE b.title LIKE :query
               OR b.isbn LIKE :query
               OR a.name LIKE :query
            GROUP BY b.book_id
            ORDER BY b.title ASC
            LIMIT 10
        ";
        
        $stmt = $pdo->prepare($searchQuery);
        $stmt->execute(['query' => "%$query%"]);
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($books as $book) {
            $results[] = [
                'type' => 'book',
                'id' => $book['book_id'],
                'title' => $book['title'],
                'subtitle' => $book['authors'] ? 'by ' . $book['authors'] : 'No author',
                'meta' => 'ISBN: ' . $book['isbn'],
                'status' => $book['status_name'],
                'category' => $book['category_name'],
                'url' => '/lib_system/library_system/admin/manage_books.php?search=' . urlencode($book['isbn'])
            ];
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
        exit;
    }
}

echo json_encode($results);
