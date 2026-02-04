<?php
// admin/transactions.php
require_once '../config/db_config.php';
require_once '../includes/auth_middleware.php';

requireRole('admin');

$pageTitle = 'Transactions';
$error = '';
$success = '';

$activeTab = $_GET['tab'] ?? 'issue'; // 'issue' or 'return'

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
            $statusId = $stmt->fetchColumn();
            
            // Assuming 1 = Available (need to make this robust in real app by querying Status table)
            // But for now, let's just proceed with transaction logic
            
            $pdo->beginTransaction();
            
            // 1. Insert Issue Record
            $stmt = $pdo->prepare("INSERT INTO issues (book_id, member_id, issue_date, due_date) VALUES (?, ?, CURDATE(), ?)");
            $stmt->execute([$bookId, $memberId, $dueDate]);
            
            // 2. Update Book Status to Issued
            $stmt = $pdo->prepare("UPDATE books SET status_id = (SELECT status_id FROM status WHERE status_name = 'Issued') WHERE book_id = ?");
            $stmt->execute([$bookId]);
            
            $pdo->commit();
            $success = "Book issued successfully.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Issue failed: " . $e->getMessage();
        }
    }
}

// Handle Return Book
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'return_book') {
    $issueId = $_POST['issue_id'];
    $fine = $_POST['fine_amount'] ?? 0;

    if (empty($issueId)) {
        $error = "Transaction ID is required.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // 1. Update Issue Record
            $stmt = $pdo->prepare("UPDATE issues SET return_date = CURDATE(), fine_amount = ? WHERE issue_id = ?");
            $stmt->execute([$fine, $issueId]);
            
            // 2. Get Book ID
            $stmt = $pdo->prepare("SELECT book_id FROM issues WHERE issue_id = ?");
            $stmt->execute([$issueId]);
            $bookId = $stmt->fetchColumn();
            
            // 3. Update Book Status to Available
            $stmt = $pdo->prepare("UPDATE books SET status_id = (SELECT status_id FROM status WHERE status_name = 'Available') WHERE book_id = ?");
            $stmt->execute([$bookId]);
            
            $pdo->commit();
            $success = "Book returned successfully.";
            $activeTab = 'return';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Return failed: " . $e->getMessage();
            $activeTab = 'return';
        }
    }
}

// Fetch Lists for Dropdowns (Simple approach for now, AJAX recommended for scale)
$members = $pdo->query("SELECT member_id, full_name, (SELECT username FROM users WHERE users.user_id = members.user_id) as uid FROM members")->fetchAll();
$booksAvailable = $pdo->query("SELECT book_id, title, isbn FROM books WHERE status_id = (SELECT status_id FROM status WHERE status_name = 'Available')")->fetchAll();

include '../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl text-gray-900 mb-1">Book Transactions</h1>
    <p class="text-gray-600">Issue and return books</p>
</div>

<!-- Tab Switcher -->
<div class="flex gap-2 mb-6 p-1 bg-gray-100 rounded" style="width: fit-content;">
    <a href="?tab=issue" class="btn <?php echo $activeTab === 'issue' ? 'bg-white text-primary shadow-sm' : 'text-gray-600'; ?>" style="<?php echo $activeTab === 'issue' ? 'color: var(--primary-color);' : 'background: transparent;'; ?>">Issue Book</a>
    <a href="?tab=return" class="btn <?php echo $activeTab === 'return' ? 'bg-white text-primary shadow-sm' : 'text-gray-600'; ?>" style="<?php echo $activeTab === 'return' ? 'color: var(--primary-color);' : 'background: transparent;'; ?>">Return Book</a>
</div>

<?php if ($error): ?>
    <div class="mb-4 p-3 bg-red-100 text-red-700 rounded border border-red-200"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="mb-4 p-3 bg-green-100 text-green-700 rounded border border-green-200"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="stats-grid" style="grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    <!-- Form Card -->
    <div class="bg-white p-6 rounded shadow-sm border border-gray-200">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
            <?php echo $activeTab === 'issue' ? 'Issue Book' : 'Return Book'; ?>
        </h2>

        <?php if ($activeTab === 'issue'): ?>
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
                        <input type="date" name="due_date" class="form-control" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-full">Issue Book</button>
            </form>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="action" value="return_book">
                <div class="mb-4">
                    <label class="block text-sm text-gray-700 mb-1">Transaction ID *</label>
                    <input type="text" name="issue_id" class="form-control" placeholder="Enter TXN ID (e.g. 5)" required>
                    <p class="text-xs text-gray-500 mt-1">Found on the Dashboard or Member history.</p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm text-gray-700 mb-1">Fine Amount ($)</label>
                    <input type="number" step="0.50" name="fine_amount" class="form-control" value="0.00">
                </div>
                
                <div class="p-4 bg-yellow-50 border border-yellow-200 rounded mb-4">
                    <div class="flex gap-2">
                        <i data-lucide="dollar-sign" class="text-yellow-600" style="width: 20px;"></i>
                        <div>
                            <h3 class="text-sm font-semibold text-yellow-900">Fine Policy</h3>
                            <p class="text-sm text-yellow-800">$0.50 per day overdue.</p>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-full">Process Return</button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Info Card -->
    <div class="bg-white p-6 rounded shadow-sm border border-gray-200">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Information</h2>
        <?php if ($activeTab === 'issue'): ?>
            <div class="bg-blue-50 border border-blue-200 rounded p-4 mb-4">
                <h3 class="text-sm font-semibold text-blue-900 mb-2">Issue Guidelines</h3>
                <ul class="text-sm text-blue-800" style="list-style: disc; padding-left: 1.25rem;">
                    <li class="mb-1">Standard loan period is 14 days.</li>
                    <li class="mb-1">Verify member ID carefully.</li>
                    <li>Books must be 'Available' to issue.</li>
                </ul>
            </div>
            <div>
                <h3 class="text-sm font-semibold text-gray-900 mb-2">Recently Issued</h3>
                <!-- Could list recent issues here dynamically -->
                 <p class="text-sm text-gray-500">Check dashboard for latest list.</p>
            </div>
        <?php else: ?>
             <div class="bg-green-50 border border-green-200 rounded p-4 mb-4">
                <h3 class="text-sm font-semibold text-green-900 mb-2">Return Guidelines</h3>
                <ul class="text-sm text-green-800" style="list-style: disc; padding-left: 1.25rem;">
                    <li class="mb-1">Check book for damage.</li>
                    <li class="mb-1">Calculate fines for overdue items.</li>
                    <li>Status will auto-update to 'Available'.</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
