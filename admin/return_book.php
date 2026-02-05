<?php
// admin/return_book.php
require_once '../config/db_config.php';
require_once '../includes/auth_middleware.php';

requireRole('admin');

$pageTitle = 'Return Book';
$error = '';
$success = '';

// Handle Return Book (Multiple Books Support)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'return_book') {
    $issueIds = $_POST['issue_ids'] ?? []; // Array of issue IDs
    $fines = $_POST['fines'] ?? []; // Array of fines corresponding to each issue

    if (empty($issueIds)) {
        $error = "Please select at least one book to return.";
    } else {
        try {
            // Validate all issues exist and are not already returned
            $invalidIssues = [];
            foreach ($issueIds as $issueId) {
                $stmt = $pdo->prepare("SELECT issue_id FROM issues WHERE issue_id = ? AND return_date IS NULL");
                $stmt->execute([$issueId]);
                if (!$stmt->fetch()) {
                    $invalidIssues[] = $issueId;
                }
            }

            if (!empty($invalidIssues)) {
                $error = "The following transaction IDs are invalid or already returned: " . implode(", ", $invalidIssues);
            } else {
                $pdo->beginTransaction();
                
                // Process all returns
                foreach ($issueIds as $index => $issueId) {
                    $fine = isset($fines[$index]) ? floatval($fines[$index]) : 0;
                    
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
                }
                
                $pdo->commit();
                $returnCount = count($issueIds);
                header("Location: transactions.php?msg=returned&count=$returnCount");
                exit;
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Return failed: " . $e->getMessage();
        }
    }
}

// Fetch fine rate from settings
$stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'fine_per_day'");
$finePerDay = $stmt ? floatval($stmt->fetchColumn()) : 0.50; // Default to 0.50 if not found

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
            $prefillFine = $daysOverdue * $finePerDay;
        }
    }
}

include '../includes/header.php';
?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 mb-1">Issue Book</h1>
        <p class="text-sm text-gray-600">Issue a book to a member</p>
    </div>
    <a
        href="transactions.php"
        class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 transition-colors"
    >
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Back to Transactions
    </a>
</div>

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

        <!-- Step 2: Select Loan(s) -->
        <?php if ($selectedMemberId && count($memberLoans) > 0): ?>
            <div class="bg-white p-6 rounded shadow-sm border border-gray-200">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">2. Select Books to Return</h2>
                    <button
                        type="button"
                        onclick="toggleSelectAll()"
                        class="inline-flex items-center gap-1 rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-blue-700 transition-colors"
                    >
                        <i data-lucide="check-square" class="h-3 w-3"></i>
                        Select All
                    </button>
                </div>
                <div class="space-y-3" id="loans-container">
                    <?php foreach ($memberLoans as $loan): ?>
                        <?php 
                            $isOverdue = $loan['days_overdue'] > 0;
                            $estFine = $isOverdue ? $loan['days_overdue'] * $finePerDay : 0;
                        ?>
                        <div class="loan-item cursor-pointer rounded border p-3 hover:bg-gray-50 transition-colors" 
                             data-issue-id="<?php echo $loan['issue_id']; ?>"
                             data-fine="<?php echo $estFine; ?>"
                             onclick="toggleLoanSelection(this, event)">
                            <div class="flex items-start gap-3">
                                <input
                                    type="checkbox"
                                    class="loan-checkbox mt-1 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    data-issue-id="<?php echo $loan['issue_id']; ?>"
                                    data-fine="<?php echo $estFine; ?>"
                                    onclick="event.stopPropagation(); toggleLoanSelection(this.closest('.loan-item'), event);"
                                >
                                <div class="flex-1">
                                    <div class="mb-1 flex items-start justify-between">
                                        <span class="font-medium text-gray-900"><?php echo htmlspecialchars($loan['title']); ?></span>
                                        <?php if ($isOverdue): ?>
                                            <span class="badge badge-red text-xs text-red-600 bg-red-50 px-2 py-0.5 rounded">Overdue</span>
                                        <?php else: ?>
                                            <span class="badge badge-green text-xs text-green-600 bg-green-50 px-2 py-0.5 rounded">On Time</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mb-2 text-sm text-gray-500">
                                        Due: <?php echo $loan['due_date']; ?> (ISBN: <?php echo $loan['isbn']; ?>)
                                    </div>
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-gray-400">ID: #<?php echo $loan['issue_id']; ?></span>
                                        <?php if ($isOverdue): ?>
                                            <span class="text-red-600 font-medium">Fine: $<?php echo number_format($estFine, 2); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div id="selection-summary" class="mt-4 hidden rounded border border-indigo-200 bg-indigo-50 p-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-indigo-900"><span id="selected-count">0</span> book(s) selected</span>
                        <span class="font-semibold text-indigo-900">Total Fine: $<span id="total-fine">0.00</span></span>
                    </div>
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
            <form method="POST" id="returnForm">
                <input type="hidden" name="action" value="return_book">
                
                <!-- Hidden inputs for selected books will be added here by JavaScript -->
                <div id="selected-books-container"></div>

                <div class="mb-4 rounded border border-blue-200 bg-blue-50 p-4">
                    <p class="text-sm text-blue-800" id="return-instructions">
                        Select one or more books from the list on the left to process returns.
                    </p>
                </div>
                
                <div class="mb-4 rounded border border-yellow-200 bg-yellow-50 p-4">
                    <h3 class="mb-1 text-sm font-semibold text-yellow-900">Fine Policy</h3>
                    <p class="text-sm text-yellow-800">$<?php echo number_format($finePerDay, 2); ?> per day overdue.</p>
                </div>

                <button
                    type="submit"
                    id="submitBtn"
                    disabled
                    class="inline-flex w-full items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed"
                >
                    Confirm Return
                </button>
            </form>
        </div>
    </div>
</div>

<script>
let selectedLoans = new Map(); // Map of issue_id => fine_amount

function toggleLoanSelection(element, event) {
    if (event) event.stopPropagation();
    
    const checkbox = element.querySelector('.loan-checkbox');
    const issueId = element.dataset.issueId;
    const fine = parseFloat(element.dataset.fine);
    
    // Toggle checkbox
    checkbox.checked = !checkbox.checked;
    
    // Update selection
    if (checkbox.checked) {
        selectedLoans.set(issueId, fine);
        element.classList.add('bg-indigo-50', 'border-indigo-300');
    } else {
        selectedLoans.delete(issueId);
        element.classList.remove('bg-indigo-50', 'border-indigo-300');
    }
    
    updateReturnForm();
}

function toggleSelectAll() {
    const allItems = document.querySelectorAll('.loan-item');
    const allSelected = selectedLoans.size === allItems.length;
    
    allItems.forEach(item => {
        const checkbox = item.querySelector('.loan-checkbox');
        const issueId = item.dataset.issueId;
        const fine = parseFloat(item.dataset.fine);
        
        if (allSelected) {
            // Deselect all
            checkbox.checked = false;
            selectedLoans.delete(issueId);
            item.classList.remove('bg-indigo-50', 'border-indigo-300');
        } else {
            // Select all
            checkbox.checked = true;
            selectedLoans.set(issueId, fine);
            item.classList.add('bg-indigo-50', 'border-indigo-300');
        }
    });
    
    updateReturnForm();
}

function updateReturnForm() {
    const container = document.getElementById('selected-books-container');
    const summary = document.getElementById('selection-summary');
    const submitBtn = document.getElementById('submitBtn');
    const instructions = document.getElementById('return-instructions');
    const selectedCount = document.getElementById('selected-count');
    const totalFineEl = document.getElementById('total-fine');
    
    // Clear existing inputs
    container.innerHTML = '';
    
    // Calculate total fine
    let totalFine = 0;
    let index = 0;
    
    selectedLoans.forEach((fine, issueId) => {
        // Add hidden inputs for issue IDs
        const issueInput = document.createElement('input');
        issueInput.type = 'hidden';
        issueInput.name = 'issue_ids[]';
        issueInput.value = issueId;
        container.appendChild(issueInput);
        
        // Add hidden inputs for fines
        const fineInput = document.createElement('input');
        fineInput.type = 'hidden';
        fineInput.name = 'fines[]';
        fineInput.value = fine.toFixed(2);
        container.appendChild(fineInput);
        
        totalFine += fine;
        index++;
    });
    
    // Update UI
    const count = selectedLoans.size;
    selectedCount.textContent = count;
    totalFineEl.textContent = totalFine.toFixed(2);
    
    if (count > 0) {
        summary.classList.remove('hidden');
        submitBtn.disabled = false;
        instructions.textContent = `${count} book(s) selected for return. Total fine: $${totalFine.toFixed(2)}`;
    } else {
        summary.classList.add('hidden');
        submitBtn.disabled = true;
        instructions.textContent = 'Select one or more books from the list on the left to process returns.';
    }
}
</script>

<?php include '../includes/footer.php'; ?>
