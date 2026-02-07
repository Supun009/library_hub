<?php
// admin/ajax_add_category.php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireRole('admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$categoryName = trim($_POST['category_name'] ?? '');

if (empty($categoryName)) {
    echo json_encode(['success' => false, 'message' => 'Category name is required']);
    exit;
}

try {
    // Check if category already exists
    $stmt = $pdo->prepare("SELECT category_id, category_name FROM categories WHERE category_name = ?");
    $stmt->execute([$categoryName]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        echo json_encode([
            'success' => true,
            'message' => 'Category already exists',
            'category' => [
                'category_id' => $existing['category_id'],
                'category_name' => $existing['category_name']
            ]
        ]);
    } else {
        // Insert new category
        $stmt = $pdo->prepare("INSERT INTO categories (category_name) VALUES (?)");
        $stmt->execute([$categoryName]);
        $categoryId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Category added successfully',
            'category' => [
                'category_id' => $categoryId,
                'category_name' => $categoryName
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
