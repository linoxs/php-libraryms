<?php
/**
 * Member Dashboard
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

// Get user's active transactions
$active_transactions = get_user_active_transactions($user_id);

// Get user's transaction history
$transaction_history = get_user_transaction_history($user_id, 5);

// Get overdue books
$overdue_books = get_user_overdue_books($user_id);

// Get statistics
$total_borrowed = count_user_transactions($user_id);
$currently_borrowed = count_user_active_transactions($user_id);
$overdue_count = count_user_overdue_books($user_id);

// Page content
Layout::header('Member Dashboard');
Layout::bodyStart();
?>

<div class="member-dashboard">
    <?php Layout::pageTitle('Member Dashboard'); ?>
    
    <div class="welcome-message">
        <h3>Welcome, <?php echo htmlspecialchars($user['full_name']); ?>!</h3>
        <p>Here's an overview of your library activity.</p>
    </div>
    
    <!-- Statistics Cards -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-book"></i>
            </div>
            <div class="stat-content">
                <h4>Total Borrowed</h4>
                <p class="stat-value"><?php echo $total_borrowed; ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-book-reader"></i>
            </div>
            <div class="stat-content">
                <h4>Currently Borrowed</h4>
                <p class="stat-value"><?php echo $currently_borrowed; ?></p>
            </div>
        </div>
        
        <div class="stat-card <?php echo $overdue_count > 0 ? 'stat-card-warning' : ''; ?>">
            <div class="stat-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <h4>Overdue Books</h4>
                <p class="stat-value"><?php echo $overdue_count; ?></p>
            </div>
        </div>
    </div>
    
    <!-- Currently Borrowed Books -->
    <div class="dashboard-section">
        <h3>Currently Borrowed Books</h3>
        
        <?php if (empty($active_transactions)): ?>
            <p>You don't have any books currently borrowed.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Book</th>
                            <th>Author</th>
                            <th>Borrowed On</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($active_transactions as $transaction): ?>
                            <?php
                            $is_overdue = strtotime($transaction['due_date']) < time();
                            $status_class = $is_overdue ? 'status-overdue' : 'status-active';
                            $status_text = $is_overdue ? 'Overdue' : 'Active';
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($transaction['title']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['author']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($transaction['borrowed_at'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($transaction['due_date'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Overdue Books Warning -->
    <?php if (!empty($overdue_books)): ?>
        <div class="dashboard-section overdue-warning">
            <h3>Overdue Books</h3>
            <p>The following books are overdue. Please return them as soon as possible to avoid penalties.</p>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Book</th>
                            <th>Author</th>
                            <th>Due Date</th>
                            <th>Days Overdue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($overdue_books as $book): ?>
                            <?php
                            $days_overdue = floor((time() - strtotime($book['due_date'])) / (60 * 60 * 24));
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($book['title']); ?></td>
                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($book['due_date'])); ?></td>
                                <td><?php echo $days_overdue; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Recent Transaction History -->
    <div class="dashboard-section">
        <h3>Recent Transaction History</h3>
        
        <?php if (empty($transaction_history)): ?>
            <p>You don't have any transaction history yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Book</th>
                            <th>Borrowed On</th>
                            <th>Due Date</th>
                            <th>Returned On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transaction_history as $transaction): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($transaction['title']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($transaction['borrowed_at'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($transaction['due_date'])); ?></td>
                                <td>
                                    <?php echo $transaction['returned_at'] ? date('M d, Y', strtotime($transaction['returned_at'])) : '-'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="view-all-link">
                <a href="/member/transactions.php" class="btn btn-outline-primary">View All Transactions</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .member-dashboard {
        margin-bottom: 2rem;
    }
    
    .welcome-message {
        margin-bottom: 2rem;
    }
    
    .stats-cards {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        flex: 1;
        min-width: 200px;
        padding: 1.5rem;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
    }
    
    .stat-card-warning {
        background-color: #fff3cd;
        border-left: 4px solid #ffc107;
    }
    
    .stat-icon {
        font-size: 2rem;
        margin-right: 1rem;
        color: #007bff;
    }
    
    .stat-card-warning .stat-icon {
        color: #dc3545;
    }
    
    .stat-content h4 {
        margin: 0 0 0.5rem 0;
        font-size: 1rem;
    }
    
    .stat-value {
        font-size: 1.5rem;
        font-weight: bold;
        margin: 0;
    }
    
    .dashboard-section {
        margin-bottom: 2rem;
        padding: 1.5rem;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .dashboard-section h3 {
        margin-top: 0;
        margin-bottom: 1rem;
        font-size: 1.25rem;
    }
    
    .overdue-warning {
        background-color: #fff3cd;
        border-left: 4px solid #ffc107;
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
    
    .status-overdue {
        background-color: #dc3545;
        color: white;
    }
    
    .view-all-link {
        margin-top: 1rem;
        text-align: right;
    }
</style>

<?php
Layout::bodyEnd();
Layout::footer();
?>
