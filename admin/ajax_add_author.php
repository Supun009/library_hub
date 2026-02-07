<?php
// admin/ajax_add_author.php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireRole('admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$authorName = trim($_POST['author_name'] ?? '');

if (empty($authorName)) {
    echo json_encode(['success' => false, 'message' => 'Author name is required']);
    exit;
}

try {
    // Check if author already exists
    $stmt = $pdo->prepare("SELECT author_id, name FROM authors WHERE name = ?");
    $stmt->execute([$authorName]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        echo json_encode([
            'success' => true,
            'message' => 'Author already exists',
            'author' => [
                'author_id' => $existing['author_id'],
                'name' => $existing['name']
            ]
        ]);
    } else {
        // Insert new author
        $stmt = $pdo->prepare("INSERT INTO authors (name) VALUES (?)");
        $stmt->execute([$authorName]);
        $authorId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Author added successfully',
            'author' => [
                'author_id' => $authorId,
                'name' => $authorName
            ]
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
