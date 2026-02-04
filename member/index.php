<?php
// member/index.php
require_once '../config/db_config.php';
require_once '../includes/auth_middleware.php';

requireRole('member');

$pageTitle = 'Browse Books';
include '../includes/header.php';
?>

<div class="catalog-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1>Library Catalog</h1>
        <div class="search-box">
            <input type="text" placeholder="Search books..." class="form-control" style="width: 300px;">
        </div>
    </div>
    
    <div class="books-grid">
        <p>No books available in the library yet.</p>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
