<?php
// admin/issue_book.php
require_once '../config/db_config.php';
require_once '../includes/auth_middleware.php';

requireRole('admin');

$pageTitle = 'Issue Book';
$error = '';
$success = '';

// Handle Issue Book (Multiple Books Support)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'issue_book') {
    $memberId = $_POST['member_id'];
    $bookIds = $_POST['book_ids'] ?? []; // Array of book IDs
    $dueDate = $_POST['due_date'];

    if (empty($memberId) || empty($bookIds) || empty($dueDate)) {
        $error = "Please select a member and at least one book.";
    } else {
        try {
            // Check if member is active
            $stmt = $pdo->prepare("SELECT status, full_name FROM members WHERE member_id = ? AND deleted_at IS NULL");
            $stmt->execute([$memberId]);
            $member = $stmt->fetch();
            
            if (!$member) {
                $error = "Member not found.";
            } elseif ($member['status'] === 'suspended') {
                $error = "Cannot issue books. Member account is suspended due to long overdue books. Please return overdue books and unsuspend the account first.";
            } elseif ($member['status'] === 'inactive') {
                $error = "Cannot issue books. Member account is inactive. Please activate the account first.";
            } elseif ($member['status'] !== 'active') {
                $error = "Cannot issue books. Member account is not active.";
            } else {
                // Get status IDs
                $stmt = $pdo->query("SELECT status_id FROM status WHERE status_name = 'Available'");
                $availableStatusId = $stmt->fetchColumn();

                $stmt = $pdo->query("SELECT status_id FROM status WHERE status_name = 'Issued'");
                $issuedStatusId = $stmt->fetchColumn();

                // Validate all books are available
                $unavailableBooks = [];
                foreach ($bookIds as $bookId) {
                    $stmt = $pdo->prepare("SELECT title, status_id FROM books WHERE book_id = ?");
                    $stmt->execute([$bookId]);
                    $book = $stmt->fetch();
                    
                    if (!$book || $book['status_id'] != $availableStatusId) {
                        $unavailableBooks[] = $book ? $book['title'] : "Book ID: $bookId";
                    }
                }

                if (!empty($unavailableBooks)) {
                    $error = "The following books are not available: " . implode(", ", $unavailableBooks);
                } else {
                    $pdo->beginTransaction();
                    
                    // Issue all books
                    foreach ($bookIds as $bookId) {
                        // 1. Insert Issue Record
                        $stmt = $pdo->prepare("INSERT INTO issues (book_id, member_id, issue_date, due_date) VALUES (?, ?, CURDATE(), ?)");
                        $stmt->execute([$bookId, $memberId, $dueDate]);
                        
                        // 2. Update Book Status to Issued
                        $stmt = $pdo->prepare("UPDATE books SET status_id = ? WHERE book_id = ?");
                        $stmt->execute([$issuedStatusId, $bookId]);
                    }
                    
                    $pdo->commit();
                    $bookCount = count($bookIds);
                    header("Location: transactions.php?msg=issued&count=$bookCount");
                    exit;
                }
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Issue failed: " . $e->getMessage();
        }
    }
}

// Fetch Lists - Only active members
$members = $pdo->query("
    SELECT m.member_id, m.full_name, m.status,
           (SELECT username FROM users WHERE users.user_id = m.user_id) as uid,
           (SELECT COUNT(*) FROM issues WHERE member_id = m.member_id AND return_date IS NULL) as issued_books_count
    FROM members m
    WHERE m.deleted_at IS NULL AND m.status = 'active'
    ORDER BY m.full_name
")->fetchAll();

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

<div class="grid grid-cols-1 gap-6 md:grid-cols-[2fr,1fr]">
    <!-- Form Card -->
    <div class="rounded border border-gray-200 bg-white p-6 shadow-sm">
        <form method="POST" id="issueForm">
            <input type="hidden" name="action" value="issue_book">
            <div class="mb-4">
                <label class="mb-1 block text-sm font-medium text-gray-700">Member *</label>
                <select
                    name="member_id"
                    required
                    class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                >
                    <option value="">Select Member</option>
                    <?php foreach ($members as $m): ?>
                        <option value="<?php echo $m['member_id']; ?>">
                            <?php echo htmlspecialchars($m['full_name'] . " (" . $m['uid'] . ")"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <div class="mb-2 flex items-center justify-between">
                    <label class="block text-sm font-medium text-gray-700">Books *</label>
                    <button
                        type="button"
                        onclick="addBookRow()"
                        class="inline-flex items-center gap-1 rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-blue-700 transition-colors"
                    >
                        <i data-lucide="plus" class="h-3 w-3"></i>
                        Add Book
                    </button>
                </div>
                <div id="books-container" class="space-y-2">
                    <!-- Book rows will be added here by JavaScript -->
                </div>
            </div>

            <div class="mb-4">
                <label class="mb-1 block text-sm font-medium text-gray-700">Due Date *</label>
                <div class="relative">
                    <input
                        type="date"
                        name="due_date"
                        required
                        value="<?php echo date('Y-m-d', strtotime('+14 days')); ?>"
                        class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                    >
                </div>
            </div>

            <button
                type="submit"
                class="inline-flex w-full items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 transition-colors"
            >
                Issue Selected Books
            </button>
        </form>
    </div>

    <!-- Info Card -->
    <div class="rounded border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Guidelines</h2>
        <div class="mb-4 rounded border border-blue-200 bg-blue-50 p-4">
            <ul class="list-disc pl-5 text-sm text-blue-800">
                <li class="mb-1">Standard loan period is 14 days.</li>
                <li class="mb-1">You can issue multiple books at once.</li>
                <li class="mb-1">Select member and available books.</li>
                <li>Verify member status before issuing.</li>
            </ul>
        </div>
    </div>
</div>

<script>
const availableBooks = <?php echo json_encode($booksAvailable); ?>;
let bookRowIndex = 0;

function createBookRow(index) {
    const row = document.createElement('div');
    row.className = 'flex items-start gap-2 book-row';
    row.innerHTML = `
        <select
            name="book_ids[]"
            required
            class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
        >
            <option value="">Select Book</option>
            ${availableBooks.map(b => `<option value="${b.book_id}">${escapeHtml(b.title)} - ${escapeHtml(b.isbn)}</option>`).join('')}
        </select>
        ${index > 0 ? `
        <button
            type="button"
            onclick="this.closest('.book-row').remove(); updateBookCount();"
            title="Remove Book"
            class="inline-flex h-10 min-w-[42px] items-center justify-center rounded-md border border-red-200 bg-white px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors"
        >
            <i data-lucide="trash-2" class="h-4 w-4"></i>
        </button>` : ''}
    `;
    return row;
}

function addBookRow() {
    const container = document.getElementById('books-container');
    container.appendChild(createBookRow(bookRowIndex++));
    lucide.createIcons();
    updateBookCount();
}

function updateBookCount() {
    const count = document.querySelectorAll('.book-row').length;
    if (count === 0) {
        addBookRow(); // Always have at least one row
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialize with one book row
document.addEventListener('DOMContentLoaded', () => {
    addBookRow();
});
</script>

<?php include '../includes/footer.php'; ?>
