<?php
/**
 * Admin Transactions Management
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../layouts/Layout.php';
require_once __DIR__ . '/../components/Alert.php';

// Require admin privileges
require_admin();

// Initialize variables
$errors = [];
$success = false;
$action = sanitize_input($_GET['action'] ?? '');
$transaction_id = sanitize_input($_GET['id'] ?? '');
$filter = sanitize_input($_GET['filter'] ?? 'all');
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;

// Process transaction actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_transaction'])) {
        // Sanitize input
        $user_id = intval($_POST['user_id'] ?? 0);
        $book_id = intval($_POST['book_id'] ?? 0);
        $due_date = sanitize_input($_POST['due_date'] ?? '');
        
        // Validate input
        if ($user_id <= 0) {
            $errors['user_id'] = 'Please select a valid user';
        } elseif (!get_user_by_id($user_id)) {
            $errors['user_id'] = 'User not found';
        }
        
        if ($book_id <= 0) {
            $errors['book_id'] = 'Please select a valid book';
        } else {
            $book = get_book_by_id($book_id);
            if (!$book) {
                $errors['book_id'] = 'Book not found';
            } elseif ($book['available'] <= 0) {
                $errors['book_id'] = 'Book is not available for borrowing';
            }
        }
        
        if (empty($due_date)) {
            $errors['due_date'] = 'Due date is required';
        } elseif (strtotime($due_date) < strtotime(date('Y-m-d'))) {
            $errors['due_date'] = 'Due date cannot be in the past';
        }
        
        // If no validation errors, add transaction
        if (empty($errors)) {
            // Calculate days between today and due date
            $today = new DateTime(date('Y-m-d'));
            $due = new DateTime($due_date);
            $days_diff = $today->diff($due)->days;
            
            $transaction_id = borrow_book($user_id, $book_id, $days_diff);
            
            if ($transaction_id) {
                set_flash_message('success', 'Transaction added successfully');
                header('Location: /admin/transactions.php');
                exit;
            } else {
                $errors['add'] = 'Failed to add transaction';
            }
        }
    } elseif (isset($_POST['return_book'])) {
        // Return book
        $transaction_id = intval($_POST['transaction_id'] ?? 0);
        
        if ($transaction_id > 0) {
            $result = return_book($transaction_id);
            
            if ($result) {
                set_flash_message('success', 'Book returned successfully');
                header('Location: /admin/transactions.php');
                exit;
            } else {
                set_flash_message('error', 'Failed to return book');
                header('Location: /admin/transactions.php');
                exit;
            }
        } else {
            set_flash_message('error', 'Invalid transaction ID');
            header('Location: /admin/transactions.php');
            exit;
        }
    }
}

// Get all users for select dropdown
$users = get_all_users();
$user_options = [0 => 'Select User'];
foreach ($users as $user) {
    $user_options[$user['id']] = $user['username'] . ' (' . $user['full_name'] . ')';
}

// Get all books for select dropdown
$books = get_all_books();
$book_options = [0 => 'Select Book'];
foreach ($books as $book) {
    if ($book['available'] > 0) {
        $book_options[$book['id']] = $book['title'] . ' by ' . $book['author'] . ' (' . $book['available'] . ' available)';
    }
}

// Get transactions with pagination and filtering
$filter_condition = '';
switch ($filter) {
    case 'active':
        $filter_condition = "AND t.returned_at IS NULL";
        break;
    case 'returned':
        $filter_condition = "AND t.returned_at IS NOT NULL";
        break;
    case 'overdue':
        $filter_condition = "AND t.returned_at IS NULL AND t.due_date < DATE('now')";
        break;
    default:
        $filter_condition = '';
        break;
}

$total_transactions = count_transactions($filter_condition);
$total_pages = ceil($total_transactions / $per_page);
$transactions = get_all_transactions($page, $per_page, $filter_condition);

// Page content
Layout::header('Manage Transactions');
Layout::bodyStart();
?>

<div class="admin-transactions">
    <?php Layout::pageTitle('Manage Transactions'); ?>
    
    <div class="action-buttons">
        <button id="add-transaction-btn" class="btn btn-primary">Add New Transaction</button>
    </div>
    
    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <a href="/admin/transactions.php?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">All Transactions</a>
        <a href="/admin/transactions.php?filter=active" class="filter-tab <?php echo $filter === 'active' ? 'active' : ''; ?>">Active</a>
        <a href="/admin/transactions.php?filter=returned" class="filter-tab <?php echo $filter === 'returned' ? 'active' : ''; ?>">Returned</a>
        <a href="/admin/transactions.php?filter=overdue" class="filter-tab <?php echo $filter === 'overdue' ? 'active' : ''; ?>">Overdue</a>
    </div>
    
    <!-- Transactions Table -->
    <div class="transactions-table">
        <?php if (empty($transactions)): ?>
            <p>No transactions found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Book</th>
                            <th>Borrow Date</th>
                            <th>Due Date</th>
                            <th>Return Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <?php
                            $is_overdue = is_null($transaction['returned_at']) && strtotime($transaction['due_date']) < time();
                            $status_class = $is_overdue ? 'status-overdue' : (is_null($transaction['returned_at']) ? 'status-active' : 'status-returned');
                            $status_text = $is_overdue ? 'Overdue' : (is_null($transaction['returned_at']) ? 'Active' : 'Returned');
                            ?>
                            <tr>
                                <td><?php echo $transaction['id']; ?></td>
                                <td><?php echo htmlspecialchars($transaction['username']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['title']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($transaction['borrowed_at'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($transaction['due_date'])); ?></td>
                                <td>
                                    <?php echo $transaction['returned_at'] ? date('M d, Y', strtotime($transaction['returned_at'])) : '-'; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                </td>
                                <td>
                                    <?php if (is_null($transaction['returned_at'])): ?>
                                        <button class="btn btn-sm btn-success return-book-btn" data-id="<?php echo $transaction['id']; ?>" data-book="<?php echo htmlspecialchars($transaction['title']); ?>" data-user="<?php echo htmlspecialchars($transaction['username']); ?>">Return Book</button>
                                    <?php else: ?>
                                        <span class="text-muted">No actions</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <?php echo generate_pagination($page, $total_pages, '/admin/transactions.php?filter=' . $filter . '&page=%d'); ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Add Transaction Modal -->
    <div id="add-transaction-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Transaction</h3>
                <button type="button" class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <?php
                Layout::formStart('/admin/transactions.php', 'post', 'add-transaction-form');
                
                // User field
                $user_id_error = isset($errors['user_id']) ? $errors['user_id'] : null;
                $user_id_label = Layout::label('user_id', 'User');
                $user_id_input = Layout::select('user_id', $user_options, $_POST['user_id'] ?? 0, 'user_id', true);
                Layout::formGroup($user_id_label, $user_id_input, $user_id_error);
                
                // Book field
                $book_id_error = isset($errors['book_id']) ? $errors['book_id'] : null;
                $book_id_label = Layout::label('book_id', 'Book');
                $book_id_input = Layout::select('book_id', $book_options, $_POST['book_id'] ?? 0, 'book_id', true);
                Layout::formGroup($book_id_label, $book_id_input, $book_id_error);
                
                // Due date field
                $due_date_error = isset($errors['due_date']) ? $errors['due_date'] : null;
                $due_date_label = Layout::label('due_date', 'Due Date');
                $default_due_date = date('Y-m-d', strtotime('+14 days'));
                $due_date_input = Layout::dateInput('due_date', $_POST['due_date'] ?? $default_due_date, 'due_date', date('Y-m-d'), null, true);
                Layout::formGroup($due_date_label, $due_date_input, $due_date_error);
                
                // Submit button
                echo Layout::submitButton('Add Transaction', 'add_transaction');
                
                Layout::formEnd();
                ?>
            </div>
        </div>
    </div>
    
    <!-- Return Book Modal -->
    <div id="return-book-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Return Book</h3>
                <button type="button" class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to mark the book "<span id="return-book-title"></span>" as returned by <span id="return-book-user"></span>?</p>
                
                <?php
                Layout::formStart('/admin/transactions.php', 'post', 'return-book-form');
                
                // Hidden transaction ID field
                echo '<input type="hidden" name="transaction_id" id="return-transaction-id" value="">';
                
                // Submit button
                echo Layout::submitButton('Return Book', 'return_book', ['class' => 'btn-success']);
                
                Layout::formEnd();
                ?>
            </div>
        </div>
    </div>
</div>

<style>
    .admin-transactions {
        margin-bottom: 2rem;
    }
    
    .action-buttons {
        margin-bottom: 1.5rem;
    }
    
    .filter-tabs {
        display: flex;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid #ddd;
    }
    
    .filter-tab {
        padding: 0.5rem 1rem;
        margin-right: 0.5rem;
        text-decoration: none;
        color: #333;
        border-radius: 4px 4px 0 0;
    }
    
    .filter-tab.active {
        background-color: #007bff;
        color: white;
    }
    
    .transactions-table {
        margin-bottom: 1.5rem;
    }
    
    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.875rem;
    }
    
    .status-active {
        background-color: #28a745;
        color: white;
    }
    
    .status-returned {
        background-color: #6c757d;
        color: white;
    }
    
    .status-overdue {
        background-color: #dc3545;
        color: white;
    }
    
    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.5);
    }
    
    .modal.show {
        display: block;
    }
    
    .modal-content {
        background-color: #fff;
        margin: 10% auto;
        padding: 0;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        width: 80%;
        max-width: 600px;
        position: relative;
        animation: modalFadeIn 0.3s;
    }
    
    .modal-header {
        padding: 1rem;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-header h3 {
        margin: 0;
    }
    
    .modal-body {
        padding: 1.5rem;
    }
    
    .close-modal {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #555;
    }
    
    .close-modal:hover {
        color: #000;
    }
    
    @keyframes modalFadeIn {
        from { opacity: 0; transform: translateY(-50px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<script>
    // Modal functionality
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM fully loaded');
        
        // Add Transaction Modal
        const addTransactionBtn = document.getElementById('add-transaction-btn');
        const addTransactionModal = document.getElementById('add-transaction-modal');
        
        if (addTransactionBtn && addTransactionModal) {
            console.log('Add transaction button and modal found');
            addTransactionBtn.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Add transaction button clicked');
                addTransactionModal.classList.add('show');
            });
            
            const closeButtons = addTransactionModal.querySelectorAll('.close-modal');
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    addTransactionModal.classList.remove('show');
                });
            });
        } else {
            console.error('Add transaction button or modal not found', { 
                addTransactionBtn: !!addTransactionBtn, 
                addTransactionModal: !!addTransactionModal 
            });
        }
        
        // Return Book Modal
        const returnButtons = document.querySelectorAll('.return-book-btn');
        const returnBookModal = document.getElementById('return-book-modal');
        const returnTransactionId = document.getElementById('return-transaction-id');
        const returnBookTitle = document.getElementById('return-book-title');
        const returnBookUser = document.getElementById('return-book-user');
        
        console.log('Return book buttons found:', returnButtons.length);
        
        if (returnButtons.length > 0 && returnBookModal && returnTransactionId && returnBookTitle && returnBookUser) {
            returnButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Return book button clicked');
                    const id = this.getAttribute('data-id');
                    const book = this.getAttribute('data-book');
                    const user = this.getAttribute('data-user');
                    
                    console.log('Return book data:', { id, book, user });
                    
                    returnTransactionId.value = id;
                    returnBookTitle.textContent = book;
                    returnBookUser.textContent = user;
                    
                    returnBookModal.classList.add('show');
                });
            });
            
            const closeButtons = returnBookModal.querySelectorAll('.close-modal');
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    returnBookModal.classList.remove('show');
                });
            });
        } else {
            console.error('Return book elements not found', { 
                returnButtons: returnButtons.length, 
                returnBookModal: !!returnBookModal,
                returnTransactionId: !!returnTransactionId,
                returnBookTitle: !!returnBookTitle,
                returnBookUser: !!returnBookUser
            });
        }
        
        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal') && event.target.classList.contains('show')) {
                console.log('Closing modal by clicking outside');
                event.target.classList.remove('show');
            }
        });
    });
</script>

<?php
Layout::bodyEnd();
Layout::footer();
?>
