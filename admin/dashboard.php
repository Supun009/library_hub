<?php
// admin/dashboard.php
require_once '../config/db_config.php';
require_once '../includes/auth_middleware.php';

requireRole('admin');

$pageTitle = 'Admin Dashboard';
include '../includes/header.php';
?>

<div class="dashboard-stats">
    <h1>Welcome, Administrator</h1>
    <p>This is the admin dashboard where you can manage books and members.</p>
    
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
        <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="color: var(--text-light); font-size: 0.875rem;">Total Books</h3>
            <p style="font-size: 2rem; font-weight: 700; color: var(--primary-color);">0</p>
        </div>
        <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="color: var(--text-light); font-size: 0.875rem;">Active Members</h3>
            <p style="font-size: 2rem; font-weight: 700; color: var(--primary-color);">0</p>
        </div>
        <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="color: var(--text-light); font-size: 0.875rem;">Issued Books</h3>
            <p style="font-size: 2rem; font-weight: 700; color: var(--primary-color);">0</p>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
