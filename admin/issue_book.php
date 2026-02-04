<?php
// admin/issue_book.php
require_once '../config/db_config.php';
require_once '../includes/auth_middleware.php';

requireRole('admin');

$pageTitle = 'Issue Book';
$error = '';
$success = '';

// Handle Issue Book
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'issue_book') {
    $memberId = $_POST['member_id'];
    $bookId = $_POST['book_id'];
    $dueDate = $_POST['due_date'];

    if (empty($memberId) || empty($bookId) || empty($dueDate)) {
        $error = "All fields are required for issuing.";
    } else {
        try {
            // Check availability
            $stmt = $pdo->prepare("SELECT status_id FROM books WHERE book_id = ?");
            $stmt->execute([$bookId]);
            $bookStatus = $stmt->fetchColumn();
            
            // Get 'Available' status ID
            $stmt = $pdo->query("SELECT status_id FROM status WHERE status_name = 'Available'");
            $availableStatusId = $stmt->fetchColumn();

             // Get 'Issued' status ID
             $stmt = $pdo->query("SELECT status_id FROM status WHERE status_name = 'Issued'");
             $issuedStatusId = $stmt->fetchColumn();

             if ($bookStatus != $availableStatusId) {
                 $error = "This book is not available for issue.";
             } else {
                $pdo->beginTransaction();
                
                // 1. Insert Issue Record
                $stmt = $pdo->prepare("INSERT INTO issues (book_id, member_id, issue_date, due_date) VALUES (?, ?, CURDATE(), ?)");
                $stmt->execute([$bookId, $memberId, $dueDate]);
                
                // 2. Update Book Status to Issued
                $stmt = $pdo->prepare("UPDATE books SET status_id = ? WHERE book_id = ?");
                $stmt->execute([$issuedStatusId, $bookId]);
                
                $pdo->commit();
                $success = "Book issued successfully.";
             }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Issue failed: " . $e->getMessage();
        }
    }
}

// Fetch Lists
$members = $pdo->query("SELECT member_id, full_name, (SELECT username FROM users WHERE users.user_id = members.user_id) as uid FROM members WHERE deleted_at IS NULL AND status = 'active'")->fetchAll();
$booksAvailable = $pdo->query("SELECT book_id, title, isbn FROM books WHERE status_id = (SELECT status_id FROM status WHERE status_name = 'Available')")->fetchAll();

include '../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl text-gray-900 mb-1">Issue Book</h1>
    <p class="text-gray-600">Issue a book to a member</p>
</div>

<?php if ($error): ?>
    <div class="mb-4 p-3 bg-red-100 text-red-700 rounded border border-red-200"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="mb-4 p-3 bg-green-100 text-green-700 rounded border border-green-200"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="stats-grid" style="grid-template-columns: 2fr 1fr; gap: 1.5rem;">
    <!-- Form Card -->
    <div class="bg-white p-6 rounded shadow-sm border border-gray-200">
        <form method="POST">
            <input type="hidden" name="action" value="issue_book">
            <div class="mb-4">
                <label class="block text-sm text-gray-700 mb-1">Member *</label>
                <select name="member_id" class="form-control" required>
                    <option value="">Select Member</option>
                    <?php foreach ($members as $m): ?>
                        <option value="<?php echo $m['member_id']; ?>">
                            <?php echo htmlspecialchars($m['full_name'] . " (" . $m['uid'] . ")"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm text-gray-700 mb-1">Book *</label>
                <select name="book_id" class="form-control" required>
                    <option value="">Select Book</option>
                    <?php foreach ($booksAvailable as $b): ?>
                        <option value="<?php echo $b['book_id']; ?>">
                            <?php echo htmlspecialchars($b['title'] . " - " . $b['isbn']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm text-gray-700 mb-1">Due Date *</label>
                <div class="relative">
                    <input type="date" name="due_date" class="form-control" required value="<?php echo date('Y-m-d', strtotime('+14 days')); ?>">
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-full">Issue Book</button>
        </form>
    </div>

    <!-- Info Card -->
    <div class="bg-white p-6 rounded shadow-sm border border-gray-200">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Guidelines</h2>
        <div class="bg-blue-50 border border-blue-200 rounded p-4 mb-4">
            <ul class="text-sm text-blue-800" style="list-style: disc; padding-left: 1.25rem;">
                <li class="mb-1">Standard loan period is 14 days.</li>
                <li class="mb-1">Select member and available book.</li>
                <li>Verify member status before issuing.</li>
            </ul>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
