<?php
// admin/return_book.php
require_once '../config/db_config.php';
require_once '../includes/auth_middleware.php';

requireRole('admin');

$pageTitle = 'Return Book';
$error = '';
$success = '';

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
            header("Location: transactions.php?msg=returned");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Return failed: " . $e->getMessage();
        }
    }
}

// 1. Fetch Members who have active loans
$membersWithLoans = $pdo->query("
    SELECT DISTINCT m.member_id, m.full_name, u.username
    FROM members m
    JOIN users u ON m.user_id = u.user_id
    JOIN issues i ON m.member_id = i.member_id
    WHERE i.return_date IS NULL
    ORDER BY m.full_name ASC
")->fetchAll();

// 2. Handle Selected Member to Show Loans
$selectedMemberId = $_GET['member_id'] ?? '';
$memberLoans = [];

if ($selectedMemberId) {
    $stmt = $pdo->prepare("
        SELECT i.issue_id, i.issue_date, i.due_date, b.title, b.isbn, DATEDIFF(CURDATE(), i.due_date) as days_overdue
        FROM issues i
        JOIN books b ON i.book_id = b.book_id
        WHERE i.member_id = ? AND i.return_date IS NULL
        ORDER BY i.due_date ASC
    ");
    $stmt->execute([$selectedMemberId]);
    $memberLoans = $stmt->fetchAll();
}

// Pre-fill logic from URL (if clicking from Transactions list)
$prefillId = $_GET['id'] ?? '';
$prefillFine = 0;

if ($prefillId) {
    // Check if ID is valid active issue
    $stmt = $pdo->prepare("SELECT DATEDIFF(CURDATE(), due_date) as days_overdue FROM issues WHERE issue_id = ? AND return_date IS NULL");
    $stmt->execute([$prefillId]);
    $daysOverdue = $stmt->fetchColumn();
    // fetchColumn returns false if no row, check explicitly
    if ($daysOverdue !== false) {
        if ($daysOverdue > 0) {
            $prefillFine = $daysOverdue * 0.50;
        }
    }
}

include '../includes/header.php';
?>

<div class="mb-6">
    <h1 class="text-2xl text-gray-900 mb-1">Return Book</h1>
    <p class="text-gray-600">Process book returns and fines</p>
</div>

<?php if ($error): ?>
    <div class="mb-4 p-3 bg-red-100 text-red-700 rounded border border-red-200"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="mb-4 p-3 bg-green-100 text-green-700 rounded border border-green-200"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    
    <!-- Left Column: Find Member & Loans -->
    <div class="space-y-6">
        <!-- Step 1: Find Member -->
        <div class="bg-white p-6 rounded shadow-sm border border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">1. Find Active Loans</h2>
            <div class="mb-4">
                <label class="mb-1 block text-sm font-medium text-gray-700">Select Member with Active Loans</label>
                <select
                    id="memberSelect"
                    onchange="window.location.href='?member_id='+this.value"
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
                    <option value="">-- Select Member --</option>
                    <?php foreach ($membersWithLoans as $m): ?>
                        <option value="<?php echo $m['member_id']; ?>" <?php echo $selectedMemberId == $m['member_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($m['full_name'] . " (" . $m['username'] . ")"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Step 2: Select Loan -->
        <?php if ($selectedMemberId && count($memberLoans) > 0): ?>
            <div class="bg-white p-6 rounded shadow-sm border border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">2. Active Loans</h2>
                <div class="space-y-3">
                    <?php foreach ($memberLoans as $loan): ?>
                        <?php 
                            $isOverdue = $loan['days_overdue'] > 0;
                            $estFine = $isOverdue ? $loan['days_overdue'] * 0.50 : 0;
                        ?>
                        <div class="cursor-pointer rounded border p-3 hover:bg-gray-50" 
                             onclick="selectLoan(<?php echo $loan['issue_id']; ?>, <?php echo $estFine; ?>)">
                            <div class="mb-1 flex items-start justify-between">
                                <span class="font-medium text-gray-900"><?php echo htmlspecialchars($loan['title']); ?></span>
                                <?php if ($isOverdue): ?>
                                    <span class="badg badge-red text-xs text-red-600 bg-red-50 px-2 py-0.5 rounded">Overdue</span>
                                <?php else: ?>
                                    <span class="badge badge-green text-xs text-green-600 bg-green-50 px-2 py-0.5 rounded">On Time</span>
                                <?php endif; ?>
                            </div>
                            <div class="mb-2 text-sm text-gray-500">
                                Due: <?php echo $loan['due_date']; ?> (ISBN: <?php echo $loan['isbn']; ?>)
                            </div>
                             <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-400">ID: #<?php echo $loan['issue_id']; ?></span>
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-md border border-indigo-200 bg-white px-2 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50 transition-colors"
                                >
                                    Select
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php elseif ($selectedMemberId): ?>
            <div class="bg-yellow-50 p-4 rounded text-yellow-700">
                No active loans found for this member.
            </div>
        <?php endif; ?>
    </div>

    <!-- Right Column: Return Form -->
    <div>
        <div class="bg-white p-6 rounded shadow-sm border border-gray-200 sticky top-4">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">3. Process Return</h2>
            <form method="POST">
                <input type="hidden" name="action" value="return_book">
                
                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700">Transaction ID *</label>
                    <input
                        type="text"
                        name="issue_id"
                        id="issueIdsInput"
                        placeholder="Enter ID or Select from list"
                        required
                        value="<?php echo htmlspecialchars($prefillId); ?>"
                        <?php echo $prefillId ? 'readonly' : ''; ?>
                        class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                    >
                </div>

                <div class="mb-4">
                    <label class="mb-1 block text-sm font-medium text-gray-700">Fine Amount ($)</label>
                    <input
                        type="number"
                        step="0.50"
                        name="fine_amount"
                        id="fineInput"
                        value="<?php echo $prefillFine; ?>"
                        class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                    >
                </div>
                
                <div class="mb-4 rounded border border-yellow-200 bg-yellow-50 p-4">
                    <h3 class="mb-1 text-sm font-semibold text-yellow-900">Fine Policy</h3>
                    <p class="text-sm text-yellow-800">$0.50 per day overdue.</p>
                </div>

                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 transition-colors"
                >
                    Confirm Return
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function selectLoan(id, fine) {
    document.getElementById('issueIdsInput').value = id;
    document.getElementById('fineInput').value = fine.toFixed(2);
    // Optional: Scroll to form on mobile
    if (window.innerWidth < 768) {
        document.getElementById('issueIdsInput').scrollIntoView({behavior: 'smooth'});
    }
}
</script>

<?php include '../includes/footer.php'; ?>
