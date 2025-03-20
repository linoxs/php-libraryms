<?php
/**
 * Admin Overdue Books
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../layouts/Layout.php';
require_once __DIR__ . '/../components/Alert.php';

// Require admin privileges
require_admin();

// Initialize variables
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$search = sanitize_input($_GET['search'] ?? '');

// Process return book action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_book'])) {
    $transaction_id = intval($_POST['transaction_id'] ?? 0);
    
    if ($transaction_id > 0) {
        $result = return_book($transaction_id);
        
        if ($result) {
            set_flash_message('success', 'Book returned successfully');
            header('Location: /admin/overdue.php');
            exit;
        } else {
            set_flash_message('error', 'Failed to return book');
            header('Location: /admin/overdue.php');
            exit;
        }
    } else {
        set_flash_message('error', 'Invalid transaction ID');
        header('Location: /admin/overdue.php');
        exit;
    }
}

// Get overdue books with pagination and search
$filter_condition = "WHERE t.return_date IS NULL AND t.due_date < DATE('now')";
if (!empty($search)) {
    $filter_condition .= " AND (u.username LIKE '%$search%' OR u.full_name LIKE '%$search%' OR b.title LIKE '%$search%' OR b.author LIKE '%$search%')";
}

$total_overdue = count_transactions($filter_condition);
$total_pages = ceil($total_overdue / $per_page);
$overdue_books = get_all_transactions($page, $per_page, $filter_condition);

// Page content
Layout::header('Overdue Books');
Layout::bodyStart();
?>

<div class="admin-overdue">
    <Layout::pageTitle('Overdue Books');
    
    <!-- Search Form -->
    <div class="search-section">
        <?php Layout::searchForm('/admin/overdue.php', 'Search by user or book', $search); ?>
    </div>
    
    <!-- Overdue Books Table -->
    <div class="overdue-table">
        <?php if (empty($overdue_books)): ?>
            <div class="no-overdue">
                <p>No overdue books found.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Book</th>
                            <th>Borrowed On</th>
                            <th>Due Date</th>
                            <th>Days Overdue</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($overdue_books as $book): ?>
                            <?php
                            $days_overdue = floor((time() - strtotime($book['due_date'])) / (60 * 60 * 24));
                            ?>
                            <tr>
                                <td><?php echo $book['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($book['username']); ?>
                                    <div class="text-muted small"><?php echo htmlspecialchars($book['full_name']); ?></div>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($book['title']); ?>
                                    <div class="text-muted small">by <?php echo htmlspecialchars($book['author']); ?></div>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($book['borrow_date'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($book['due_date'])); ?></td>
                                <td>
                                    <span class="days-overdue"><?php echo $days_overdue; ?> days</span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-success return-book-btn" data-id="<?php echo $book['id']; ?>" data-book="<?php echo htmlspecialchars($book['title']); ?>" data-user="<?php echo htmlspecialchars($book['username']); ?>">Return Book</button>
                                    <a href="mailto:<?php echo htmlspecialchars($book['email']); ?>" class="btn btn-sm btn-outline-primary">Contact User</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <?php 
                $pagination_url = '/admin/overdue.php?page=%d';
                if (!empty($search)) {
                    $pagination_url .= '&search=' . urlencode($search);
                }
                echo generate_pagination($page, $total_pages, $pagination_url); 
                ?>
            <?php endif; ?>
            
            <!-- Summary -->
            <div class="overdue-summary">
                <p>Total overdue books: <strong><?php echo $total_overdue; ?></strong></p>
            </div>
        <?php endif; ?>
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
                Layout::formStart('/admin/overdue.php', 'post', 'return-book-form');
                
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
    .admin-overdue {
        margin-bottom: 2rem;
    }
    
    .search-section {
        margin-bottom: 1.5rem;
    }
    
    .overdue-table {
        margin-bottom: 1.5rem;
    }
    
    .no-overdue {
        text-align: center;
        padding: 2rem;
        background-color: #f8f9fa;
        border-radius: 8px;
    }
    
    .days-overdue {
        color: #dc3545;
        font-weight: bold;
    }
    
    .overdue-summary {
        margin-top: 1rem;
        text-align: right;
    }
</style>

<script>
    // Modal functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Return Book Modal
        const returnButtons = document.querySelectorAll('.return-book-btn');
        const returnBookModal = document.getElementById('return-book-modal');
        const returnTransactionId = document.getElementById('return-transaction-id');
        const returnBookTitle = document.getElementById('return-book-title');
        const returnBookUser = document.getElementById('return-book-user');
        
        if (returnButtons.length > 0 && returnBookModal && returnTransactionId && returnBookTitle && returnBookUser) {
            returnButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const book = this.getAttribute('data-book');
                    const user = this.getAttribute('data-user');
                    
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
        }
        
        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        });
    });
</script>

<?php
Layout::bodyEnd();
Layout::footer();
?>
