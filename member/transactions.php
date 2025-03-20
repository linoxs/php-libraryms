<?php
/**
 * Member Transactions History
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../layouts/Layout.php';
require_once __DIR__ . '/../components/Alert.php';

// Require member privileges
require_login();

// Initialize variables
$user_id = get_current_user_id();
$filter = sanitize_input($_GET['filter'] ?? 'all');
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;

// Get transactions with pagination and filtering
$filter_condition = "WHERE t.user_id = $user_id";
switch ($filter) {
    case 'active':
        $filter_condition .= " AND t.returned_at IS NULL";
        break;
    case 'returned':
        $filter_condition .= " AND t.returned_at IS NOT NULL";
        break;
    case 'overdue':
        $filter_condition .= " AND t.returned_at IS NULL AND t.due_date < DATE('now')";
        break;
    default:
        break;
}

$total_transactions = count_transactions($filter_condition);
$total_pages = ceil($total_transactions / $per_page);
$transactions = get_all_transactions($page, $per_page, $filter_condition);

// Page content
Layout::header('My Transactions');
Layout::bodyStart();
?>

<div class="member-transactions">
    <?php Layout::pageTitle('My Transactions'); ?>
    
    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <a href="/member/transactions.php?filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">All Transactions</a>
        <a href="/member/transactions.php?filter=active" class="filter-tab <?php echo $filter === 'active' ? 'active' : ''; ?>">Currently Borrowed</a>
        <a href="/member/transactions.php?filter=returned" class="filter-tab <?php echo $filter === 'returned' ? 'active' : ''; ?>">Returned</a>
        <a href="/member/transactions.php?filter=overdue" class="filter-tab <?php echo $filter === 'overdue' ? 'active' : ''; ?>">Overdue</a>
    </div>
    
    <!-- Transactions Table -->
    <div class="transactions-table">
        <?php if (empty($transactions)): ?>
            <div class="no-transactions">
                <p>No transactions found for the selected filter.</p>
                <?php if ($filter !== 'all'): ?>
                    <p><a href="/member/transactions.php" class="btn btn-outline-primary">View All Transactions</a></p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Book</th>
                            <th>Author</th>
                            <th>Borrowed On</th>
                            <th>Due Date</th>
                            <th>Return Date</th>
                            <th>Status</th>
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
                                <td><?php echo htmlspecialchars($transaction['title']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['author']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($transaction['borrowed_at'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($transaction['due_date'])); ?></td>
                                <td>
                                    <?php echo $transaction['returned_at'] ? date('M d, Y', strtotime($transaction['returned_at'])) : '-'; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <?php echo generate_pagination($page, $total_pages, '/member/transactions.php?filter=' . $filter . '&page=%d'); ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <?php if ($filter === 'overdue'): ?>
    <div class="overdue-notice">
        <h3>About Overdue Books</h3>
        <p>Please return overdue books as soon as possible to avoid penalties. If you have any questions or need an extension, please contact the library staff.</p>
    </div>
    <?php endif; ?>
</div>

<style>
    .member-transactions {
        margin-bottom: 2rem;
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
    
    .no-transactions {
        text-align: center;
        padding: 2rem;
        background-color: #f8f9fa;
        border-radius: 8px;
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
    
    .overdue-notice {
        background-color: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 1rem;
        border-radius: 4px;
    }
    
    .overdue-notice h3 {
        margin-top: 0;
        font-size: 1.25rem;
    }
</style>

<?php
Layout::bodyEnd();
Layout::footer();
?>
