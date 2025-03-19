<?php
/**
 * Admin Dashboard
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../layouts/Layout.php';
require_once __DIR__ . '/../components/Alert.php';

// Require admin privileges
require_admin();

// Get dashboard statistics
$total_books = db_query("SELECT COUNT(*) as count FROM books");
$total_books = db_fetch_assoc($total_books)['count'];

$total_members = db_query("SELECT COUNT(*) as count FROM users WHERE role = 'member'");
$total_members = db_fetch_assoc($total_members)['count'];

$total_publishers = db_query("SELECT COUNT(*) as count FROM publishers");
$total_publishers = db_fetch_assoc($total_publishers)['count'];

$total_transactions = db_query("SELECT COUNT(*) as count FROM transactions");
$total_transactions = db_fetch_assoc($total_transactions)['count'];

$borrowed_books = db_query("SELECT COUNT(*) as count FROM transactions WHERE status = 'borrowed'");
$borrowed_books = db_fetch_assoc($borrowed_books)['count'];

$overdue_books = db_query("SELECT COUNT(*) as count FROM transactions WHERE status = 'borrowed' AND due_date < datetime('now')");
$overdue_books = db_fetch_assoc($overdue_books)['count'];

// Get recent transactions
$recent_transactions = db_query(
    "SELECT t.*, u.username, u.full_name, b.title, b.author 
     FROM transactions t 
     JOIN users u ON t.user_id = u.id 
     JOIN books b ON t.book_id = b.id 
     ORDER BY t.borrowed_at DESC LIMIT 5"
);
$recent_transactions = db_fetch_all($recent_transactions);

// Get books with low availability
$low_availability_books = db_query(
    "SELECT b.*, p.name as publisher_name 
     FROM books b 
     LEFT JOIN publishers p ON b.publisher_id = p.id 
     WHERE b.available <= 2 AND b.available > 0 
     ORDER BY b.available ASC LIMIT 5"
);
$low_availability_books = db_fetch_all($low_availability_books);

// Page content
Layout::header('Admin Dashboard');
Layout::bodyStart();
?>

<div class="admin-dashboard">
    <Layout::pageTitle('Admin Dashboard');
    
    <div class="dashboard-stats">
        <div class="stat-card">
            <h3>Books</h3>
            <p class="stat-number"><?php echo $total_books; ?></p>
            <a href="/admin/books.php" class="stat-link">Manage Books</a>
        </div>
        
        <div class="stat-card">
            <h3>Members</h3>
            <p class="stat-number"><?php echo $total_members; ?></p>
            <a href="/admin/users.php" class="stat-link">Manage Users</a>
        </div>
        
        <div class="stat-card">
            <h3>Publishers</h3>
            <p class="stat-number"><?php echo $total_publishers; ?></p>
            <a href="/admin/publishers.php" class="stat-link">Manage Publishers</a>
        </div>
        
        <div class="stat-card">
            <h3>Transactions</h3>
            <p class="stat-number"><?php echo $total_transactions; ?></p>
            <a href="/admin/transactions.php" class="stat-link">View All</a>
        </div>
        
        <div class="stat-card">
            <h3>Books Out</h3>
            <p class="stat-number"><?php echo $borrowed_books; ?></p>
        </div>
        
        <div class="stat-card">
            <h3>Overdue</h3>
            <p class="stat-number"><?php echo $overdue_books; ?></p>
            <a href="/admin/overdue.php" class="stat-link">View Overdue</a>
        </div>
    </div>
    
    <div class="dashboard-sections">
        <div class="dashboard-section">
            <Layout::sectionTitle('Recent Transactions');
            
            <?php if (empty($recent_transactions)): ?>
                <p>No transactions yet.</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Book</th>
                            <th>Borrowed</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_transactions as $transaction): ?>
                            <tr>
                                <td><?php echo $transaction['id']; ?></td>
                                <td><?php echo htmlspecialchars($transaction['full_name']); ?> (<?php echo htmlspecialchars($transaction['username']); ?>)</td>
                                <td><?php echo htmlspecialchars($transaction['title']); ?> by <?php echo htmlspecialchars($transaction['author']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($transaction['borrowed_at'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($transaction['due_date'])); ?></td>
                                <td>
                                    <?php if ($transaction['status'] === 'borrowed'): ?>
                                        <span class="status-borrowed">Borrowed</span>
                                    <?php elseif ($transaction['status'] === 'returned'): ?>
                                        <span class="status-returned">Returned</span>
                                    <?php elseif ($transaction['status'] === 'overdue'): ?>
                                        <span class="status-overdue">Overdue</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="view-all">
                    <a href="/admin/transactions.php">View All Transactions</a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="dashboard-section">
            <Layout::sectionTitle('Books with Low Availability');
            
            <?php if (empty($low_availability_books)): ?>
                <p>No books with low availability.</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Available</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($low_availability_books as $book): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($book['title']); ?></td>
                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                <td><?php echo $book['available']; ?></td>
                                <td><?php echo $book['quantity']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="view-all">
                    <a href="/admin/books.php">Manage Books</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .admin-dashboard {
        margin-bottom: 2rem;
    }
    
    .dashboard-stats {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background-color: #fff;
        border-radius: 0.25rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        padding: 1.5rem;
        text-align: center;
    }
    
    .stat-card h3 {
        margin-top: 0;
        color: #343a40;
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: bold;
        color: #007bff;
        margin: 0.5rem 0;
    }
    
    .stat-link {
        display: block;
        margin-top: 0.5rem;
    }
    
    .dashboard-sections {
        display: grid;
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .dashboard-section {
        background-color: #fff;
        border-radius: 0.25rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        padding: 1.5rem;
    }
    
    .view-all {
        margin-top: 1rem;
        text-align: right;
    }
    
    .status-borrowed {
        color: #007bff;
    }
    
    .status-returned {
        color: #28a745;
    }
    
    .status-overdue {
        color: #dc3545;
    }
    
    @media (min-width: 768px) {
        .dashboard-sections {
            grid-template-columns: 1fr 1fr;
        }
    }
</style>

<?php
Layout::bodyEnd();
Layout::footer();
?>
