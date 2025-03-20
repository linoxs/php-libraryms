<?php
/**
 * Member Transaction History
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../layouts/Layout.php';
require_once __DIR__ . '/../components/Alert.php';

// Require member privileges
require_login();

// Get current user
$user_id = get_current_user_id();
$user = get_user_by_id($user_id);

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;

// Get transaction history
$transaction_history = get_user_transaction_history($user_id, null);
$total_transactions = count($transaction_history);
$total_pages = ceil($total_transactions / $per_page);

// Adjust page if out of bounds
if ($page < 1) $page = 1;
if ($page > $total_pages && $total_pages > 0) $page = $total_pages;

// Get paginated transaction history
$offset = ($page - 1) * $per_page;
$paginated_history = array_slice($transaction_history, $offset, $per_page);

// Page content
Layout::header('Transaction History');
Layout::bodyStart();
?>

<div class="member-history">
    <?php Layout::pageTitle('Transaction History'); ?>
    
    <?php if (empty($transaction_history)): ?>
        <div class="alert alert-info">You don't have any transaction history yet.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Book</th>
                        <th>Author</th>
                        <th>Borrowed On</th>
                        <th>Due Date</th>
                        <th>Returned On</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($paginated_history as $transaction): ?>
                        <?php
                        $status = $transaction['status'];
                        $status_class = '';
                        
                        if ($status === 'borrowed') {
                            $current_date = date('Y-m-d H:i:s');
                            if (strtotime($transaction['due_date']) < strtotime($current_date)) {
                                $status = 'overdue';
                                $status_class = 'status-overdue';
                            } else {
                                $status_class = 'status-active';
                            }
                        } else if ($status === 'returned') {
                            $status_class = 'status-returned';
                        }
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($transaction['title']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['author']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($transaction['borrowed_at'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($transaction['due_date'])); ?></td>
                            <td>
                                <?php echo $transaction['returned_at'] ? date('M d, Y', strtotime($transaction['returned_at'])) : '-'; ?>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo ucfirst($status); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="/member/history.php?page=<?php echo $page - 1; ?>" class="pagination-link">&laquo; Previous</a>
                <?php endif; ?>
                
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1) {
                    echo '<a href="/member/history.php?page=1" class="pagination-link">1</a>';
                    if ($start_page > 2) {
                        echo '<span class="pagination-ellipsis">...</span>';
                    }
                }
                
                for ($i = $start_page; $i <= $end_page; $i++) {
                    $active_class = $i === $page ? 'pagination-link-active' : '';
                    echo '<a href="/member/history.php?page=' . $i . '" class="pagination-link ' . $active_class . '">' . $i . '</a>';
                }
                
                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) {
                        echo '<span class="pagination-ellipsis">...</span>';
                    }
                    echo '<a href="/member/history.php?page=' . $total_pages . '" class="pagination-link">' . $total_pages . '</a>';
                }
                ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="/member/history.php?page=<?php echo $page + 1; ?>" class="pagination-link">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
    .member-history {
        margin-bottom: 2rem;
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 1.5rem;
    }
    
    .data-table th,
    .data-table td {
        padding: 0.75rem;
        border-bottom: 1px solid #eee;
    }
    
    .data-table th {
        background-color: #f8f9fa;
        text-align: left;
        font-weight: 600;
    }
    
    .data-table tr:hover {
        background-color: #f8f9fa;
    }
    
    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .status-active {
        background-color: #e3f2fd;
        color: #0d6efd;
    }
    
    .status-overdue {
        background-color: #f8d7da;
        color: #dc3545;
    }
    
    .status-returned {
        background-color: #d1e7dd;
        color: #198754;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 1.5rem;
    }
    
    .pagination-link {
        display: inline-block;
        padding: 0.5rem 0.75rem;
        margin: 0 0.25rem;
        border-radius: 4px;
        background-color: #f8f9fa;
        color: #0d6efd;
        text-decoration: none;
    }
    
    .pagination-link:hover {
        background-color: #e9ecef;
    }
    
    .pagination-link-active {
        background-color: #0d6efd;
        color: white;
    }
    
    .pagination-ellipsis {
        display: inline-block;
        padding: 0.5rem 0.75rem;
        margin: 0 0.25rem;
    }
</style>

<?php
Layout::bodyEnd();
Layout::footer();
?>
