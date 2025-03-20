<?php
/**
 * Library Management System
 * Home page
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/layouts/Layout.php';
require_once __DIR__ . '/components/Alert.php';

// Initialize database if needed
initialize_db();

// Initialize session
initialize_session();

// Get recent books
$recent_books = db_query(
    "SELECT b.*, p.name as publisher_name 
     FROM books b 
     LEFT JOIN publishers p ON b.publisher_id = p.id 
     ORDER BY b.id DESC LIMIT 5"
);
$recent_books = db_fetch_all($recent_books);

// Page content
Layout::header('Home');
Layout::bodyStart();
?>

<div class="home-page">
    <Layout::pageTitle('Welcome to the Library Management System');
    
    <?php if (!is_logged_in()): ?>
        <div class="welcome-message">
            <p>Please <a href="/auth/login.php">login</a> or <a href="/auth/register.php">register</a> to access the library services.</p>
        </div>
    <?php else: ?>
        <div class="welcome-message">
            <p>Welcome back, <?php echo $_SESSION['username']; ?>!</p>
            <?php if (is_admin()): ?>
                <p>Go to your <a href="/admin/dashboard.php">Admin Dashboard</a> to manage the library.</p>
            <?php else: ?>
                <p>Go to your <a href="/member/dashboard.php">Member Dashboard</a> to view your borrowed books and more.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div class="recent-books">
        <Layout::sectionTitle('Recently Added Books');
        
        <?php if (empty($recent_books)): ?>
            <p>No books available.</p>
        <?php else: ?>
            <div class="book-list">
                <?php foreach ($recent_books as $book): ?>
                    <div class="book-card">
                        <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                        <p><strong>Author:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
                        <p><strong>Genre:</strong> <?php echo htmlspecialchars($book['genre']); ?></p>
                        <p><strong>Year:</strong> <?php echo htmlspecialchars($book['year']); ?></p>
                        <p><strong>Publisher:</strong> <?php echo htmlspecialchars($book['publisher_name']); ?></p>
                        <p><strong>Available:</strong> <?php echo $book['available']; ?> of <?php echo $book['quantity']; ?></p>
                        
                        <?php if (is_logged_in() && !is_admin() && $book['available'] > 0): ?>
                            <a href="/member/books.php?action=borrow&id=<?php echo $book['id']; ?>" class="btn btn-primary">Borrow</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="library-info">
        <Layout::sectionTitle('About Our Library');
        
        <p>Our library offers a wide range of books across various genres. We are committed to providing a seamless experience for our members to borrow and return books.</p>
        
        <div class="library-stats">
            <?php
            $total_books = db_query("SELECT COUNT(*) as count FROM books");
            $total_books = db_fetch_assoc($total_books)['count'];
            
            $total_members = db_query("SELECT COUNT(*) as count FROM users WHERE role = 'member'");
            $total_members = db_fetch_assoc($total_members)['count'];
            
            $total_publishers = db_query("SELECT COUNT(*) as count FROM publishers");
            $total_publishers = db_fetch_assoc($total_publishers)['count'];
            ?>
            
            <div class="stat-card">
                <h4>Total Books</h4>
                <p class="stat-number"><?php echo $total_books; ?></p>
            </div>
            
            <div class="stat-card">
                <h4>Members</h4>
                <p class="stat-number"><?php echo $total_members; ?></p>
            </div>
            
            <div class="stat-card">
                <h4>Publishers</h4>
                <p class="stat-number"><?php echo $total_publishers; ?></p>
            </div>
        </div>
    </div>
</div>

<style>
    .home-page {
        margin-bottom: 2rem;
    }
    
    .welcome-message {
        background-color: #e3f2fd;
        padding: 1rem;
        border-radius: 0.25rem;
        margin-bottom: 2rem;
    }
    
    .book-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-top: 1rem;
    }
    
    .book-card {
        background-color: #fff;
        border-radius: 0.25rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        padding: 1rem;
    }
    
    .book-card h3 {
        margin-top: 0;
        margin-bottom: 0.5rem;
        color: #343a40;
    }
    
    .book-card p {
        margin-bottom: 0.5rem;
    }
    
    .book-card .btn {
        margin-top: 0.5rem;
    }
    
    .library-info {
        margin-top: 2rem;
    }
    
    .library-stats {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-top: 1rem;
    }
    
    .stat-card {
        background-color: #fff;
        border-radius: 0.25rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        padding: 1rem;
        text-align: center;
    }
    
    .stat-card h4 {
        margin-top: 0;
        color: #343a40;
    }
    
    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        color: #007bff;
        margin: 0.5rem 0;
    }
</style>

<?php
Layout::bodyEnd();
Layout::footer();
?>
