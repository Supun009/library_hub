<?php
// admin/ajax_search_members.php
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireRole('admin');

header('Content-Type: application/json');

$searchTerm = $_GET['q'] ?? '';

if (strlen($searchTerm) < 2) {
    echo json_encode([]);
    exit;
}

try {
    // Search for active members by name or username
    $stmt = $pdo->prepare("
        SELECT m.member_id, m.full_name, u.username as uid,
               (SELECT COUNT(*) FROM issues WHERE member_id = m.member_id AND return_date IS NULL) as issued_books_count
        FROM members m
        JOIN users u ON m.user_id = u.user_id
        WHERE m.status = 'active'
          AND (m.full_name LIKE :search OR u.username LIKE :search)
        ORDER BY m.full_name
        LIMIT 10
    ");
    
    $searchParam = '%' . $searchTerm . '%';
    $stmt->execute(['search' => $searchParam]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($members);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
