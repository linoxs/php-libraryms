<?php
/**
 * Member Books Browsing
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../layouts/Layout.php';
require_once __DIR__ . '/../components/Alert.php';

// Require member privileges
require_login();

// Initialize variables
$errors = [];
$success = false;
$search = sanitize_input($_GET['search'] ?? '');
$genre = sanitize_input($_GET['genre'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 12;

// Process borrow book action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrow_book'])) {
    $book_id = intval($_POST['book_id'] ?? 0);
    $user_id = get_current_user_id();
    
    // Validate book
    if ($book_id <= 0) {
        $errors['book'] = 'Invalid book selected';
    } else {
        $book = get_book_by_id($book_id);
        if (!$book) {
            $errors['book'] = 'Book not found';
        } elseif ($book['available'] <= 0) {
            $errors['book'] = 'Book is not available for borrowing';
        }
    }
    
    // Check if user already has this book borrowed
    if (empty($errors) && has_active_borrow($user_id, $book_id)) {
        $errors['book'] = 'You already have this book borrowed';
    }
    
    // Check if user has reached maximum allowed books (e.g., 5)
    if (empty($errors) && count_user_active_transactions($user_id) >= 5) {
        $errors['book'] = 'You have reached the maximum number of books you can borrow (5)';
    }
    
    // If no validation errors, borrow book
    if (empty($errors)) {
        // Default due date is 14 days from now
        $due_date = date('Y-m-d', strtotime('+14 days'));
        
        $transaction_id = borrow_book($user_id, $book_id, $due_date);
        
        if ($transaction_id) {
            set_flash_message('success', 'Book borrowed successfully. Please return it by ' . date('M d, Y', strtotime($due_date)));
            header('Location: /member/books.php');
            exit;
        } else {
            $errors['borrow'] = 'Failed to borrow book';
        }
    }
}

// Get all available genres for filter
$genres = get_all_genres();

// Get books with pagination, search, and genre filter
$filter_conditions = [];
if (!empty($search)) {
    $filter_conditions[] = "(title LIKE '%$search%' OR author LIKE '%$search%' OR isbn LIKE '%$search%')";
}
if (!empty($genre)) {
    $filter_conditions[] = "genre = '$genre'";
}

$filter_condition = !empty($filter_conditions) ? "WHERE " . implode(" AND ", $filter_conditions) : "";
$total_books = count_books_with_condition($filter_condition);
$total_pages = ceil($total_books / $per_page);
$books = get_books_with_condition($filter_condition, $page, $per_page);

// Page content
Layout::header('Browse Books');
Layout::bodyStart();
?>

<div class="member-books">
    <?php Layout::pageTitle('Browse Books'); ?>
    
    <!-- Search and Filter Section -->
    <div class="search-filter-section">
        <div class="search-box">
            <form action="/member/books.php" method="get" class="search-form">
                <?php if (!empty($genre)): ?>
                    <input type="hidden" name="genre" value="<?php echo htmlspecialchars($genre); ?>">
                <?php endif; ?>
                <div class="input-group">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by title, author, or ISBN" class="form-control">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <?php if (!empty($search) || !empty($genre)): ?>
                        <a href="/member/books.php" class="btn btn-outline-secondary">Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <div class="genre-filter">
            <form action="/member/books.php" method="get" class="genre-form">
                <?php if (!empty($search)): ?>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                <?php endif; ?>
                <div class="input-group">
                    <select name="genre" class="form-control" onchange="this.form.submit()">
                        <option value="">All Genres</option>
                        <?php foreach ($genres as $g): ?>
                            <option value="<?php echo htmlspecialchars($g); ?>" <?php echo $genre === $g ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($g); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Books Grid -->
    <div class="books-grid">
        <?php if (empty($books)): ?>
            <div class="no-books-found">
                <p>No books found matching your criteria.</p>
                <?php if (!empty($search) || !empty($genre)): ?>
                    <p><a href="/member/books.php" class="btn btn-outline-primary">View All Books</a></p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="books-container">
                <?php foreach ($books as $book): ?>
                    <div class="book-card">
                        <div class="book-cover">
                            <!-- Placeholder book cover with first letter of title -->
                            <div class="book-cover-placeholder">
                                <?php echo strtoupper(substr($book['title'], 0, 1)); ?>
                            </div>
                        </div>
                        <div class="book-details">
                            <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                            <p class="book-author">by <?php echo htmlspecialchars($book['author']); ?></p>
                            <p class="book-genre"><?php echo htmlspecialchars($book['genre']); ?></p>
                            <p class="book-availability">
                                <?php if ($book['available'] > 0): ?>
                                    <span class="available">Available (<?php echo $book['available']; ?>)</span>
                                <?php else: ?>
                                    <span class="unavailable">Unavailable</span>
                                <?php endif; ?>
                            </p>
                            <button class="btn btn-sm btn-primary view-details-btn" data-id="<?php echo $book['id']; ?>">View Details</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <?php 
                $pagination_url = '/member/books.php?page=%d';
                if (!empty($search)) {
                    $pagination_url .= '&search=' . urlencode($search);
                }
                if (!empty($genre)) {
                    $pagination_url .= '&genre=' . urlencode($genre);
                }
                echo generate_pagination($page, $total_pages, $pagination_url); 
                ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Book Details Modal -->
    <div id="book-details-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-book-title">Book Details</h3>
                <button type="button" class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="book-details-container">
                    <div class="book-cover-large">
                        <div class="book-cover-placeholder large" id="modal-book-cover">A</div>
                    </div>
                    <div class="book-info">
                        <p><strong>Author:</strong> <span id="modal-book-author"></span></p>
                        <p><strong>Publisher:</strong> <span id="modal-book-publisher"></span></p>
                        <p><strong>Year:</strong> <span id="modal-book-year"></span></p>
                        <p><strong>ISBN:</strong> <span id="modal-book-isbn"></span></p>
                        <p><strong>Genre:</strong> <span id="modal-book-genre"></span></p>
                        <p><strong>Available:</strong> <span id="modal-book-available"></span> of <span id="modal-book-quantity"></span></p>
                        
                        <div id="borrow-section">
                            <?php
                            Layout::formStart('/member/books.php', 'post', 'borrow-book-form');
                            echo '<input type="hidden" name="book_id" id="modal-book-id" value="">';
                            echo Layout::submitButton('Borrow Book', 'borrow_book', ['id' => 'borrow-btn', 'class' => 'btn-primary']);
                            Layout::formEnd();
                            ?>
                            <div id="already-borrowed-message" style="display: none;">
                                <p class="text-info">You already have this book borrowed.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .member-books {
        margin-bottom: 2rem;
    }
    
    .search-filter-section {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 2rem;
    }
    
    .search-box {
        flex: 3;
        min-width: 300px;
    }
    
    .genre-filter {
        flex: 1;
        min-width: 200px;
    }
    
    .books-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .book-card {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        transition: transform 0.3s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    .book-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }
    
    .book-cover {
        height: 180px;
        background-color: #f8f9fa;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    
    .book-cover-placeholder {
        width: 100px;
        height: 150px;
        background-color: #007bff;
        color: white;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 3rem;
        font-weight: bold;
        border-radius: 4px;
    }
    
    .book-cover-placeholder.large {
        width: 150px;
        height: 200px;
        font-size: 4rem;
    }
    
    .book-details {
        padding: 1rem;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    
    .book-title {
        margin: 0 0 0.5rem 0;
        font-size: 1.1rem;
        font-weight: bold;
        line-height: 1.3;
    }
    
    .book-author {
        margin: 0 0 0.5rem 0;
        font-size: 0.9rem;
        color: #555;
    }
    
    .book-genre {
        margin: 0 0 0.5rem 0;
        font-size: 0.8rem;
        color: #666;
        background-color: #f8f9fa;
        display: inline-block;
        padding: 0.2rem 0.5rem;
        border-radius: 4px;
    }
    
    .book-availability {
        margin: 0 0 1rem 0;
        font-size: 0.9rem;
    }
    
    .available {
        color: #28a745;
        font-weight: bold;
    }
    
    .unavailable {
        color: #dc3545;
        font-weight: bold;
    }
    
    .view-details-btn {
        margin-top: auto;
    }
    
    .no-books-found {
        text-align: center;
        padding: 2rem;
        background-color: #f8f9fa;
        border-radius: 8px;
    }
    
    .book-details-container {
        display: flex;
        gap: 1.5rem;
    }
    
    .book-cover-large {
        flex-shrink: 0;
    }
    
    .book-info {
        flex-grow: 1;
    }
    
    #borrow-section {
        margin-top: 1.5rem;
        padding-top: 1rem;
        border-top: 1px solid #eee;
    }
    
    @media (max-width: 768px) {
        .book-details-container {
            flex-direction: column;
        }
        
        .book-cover-large {
            margin-bottom: 1rem;
            align-self: center;
        }
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
        max-width: 800px;
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
    document.addEventListener('DOMContentLoaded', function() {
        // Book Details Modal
        const viewDetailsButtons = document.querySelectorAll('.view-details-btn');
        const bookDetailsModal = document.getElementById('book-details-modal');
        const closeModalButtons = document.querySelectorAll('.close-modal');
        
        // Book details elements
        const modalBookId = document.getElementById('modal-book-id');
        const modalBookTitle = document.getElementById('modal-book-title');
        const modalBookCover = document.getElementById('modal-book-cover');
        const modalBookAuthor = document.getElementById('modal-book-author');
        const modalBookPublisher = document.getElementById('modal-book-publisher');
        const modalBookYear = document.getElementById('modal-book-year');
        const modalBookIsbn = document.getElementById('modal-book-isbn');
        const modalBookGenre = document.getElementById('modal-book-genre');
        const modalBookAvailable = document.getElementById('modal-book-available');
        const modalBookQuantity = document.getElementById('modal-book-quantity');
        const borrowBtn = document.getElementById('borrow-btn');
        const alreadyBorrowedMessage = document.getElementById('already-borrowed-message');
        
        // Book data (would be populated from database in a real app)
        const books = <?php echo json_encode($books); ?>;
        
        // Get user's active borrows
        const userActiveBorrows = <?php echo json_encode(get_user_borrowed_book_ids(get_current_user_id())); ?>;
        
        if (viewDetailsButtons.length > 0 && bookDetailsModal) {
            viewDetailsButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const bookId = parseInt(this.getAttribute('data-id'));
                    const book = books.find(b => parseInt(b.id) === bookId);
                    
                    if (book) {
                        // Populate modal with book details
                        modalBookId.value = book.id;
                        modalBookTitle.textContent = book.title;
                        modalBookCover.textContent = book.title.charAt(0).toUpperCase();
                        modalBookAuthor.textContent = book.author;
                        modalBookPublisher.textContent = book.publisher_name || 'Not specified';
                        modalBookYear.textContent = book.year;
                        modalBookIsbn.textContent = book.isbn || 'Not specified';
                        modalBookGenre.textContent = book.genre;
                        modalBookAvailable.textContent = book.available;
                        modalBookQuantity.textContent = book.quantity;
                        
                        // Check if user already has this book borrowed
                        const alreadyBorrowed = userActiveBorrows.includes(parseInt(book.id));
                        
                        // Show/hide borrow button based on availability and current borrows
                        if (book.available <= 0) {
                            borrowBtn.disabled = true;
                            borrowBtn.textContent = 'Not Available';
                            alreadyBorrowedMessage.style.display = 'none';
                        } else if (alreadyBorrowed) {
                            borrowBtn.style.display = 'none';
                            alreadyBorrowedMessage.style.display = 'block';
                        } else {
                            borrowBtn.disabled = false;
                            borrowBtn.textContent = 'Borrow Book';
                            borrowBtn.style.display = 'block';
                            alreadyBorrowedMessage.style.display = 'none';
                        }
                        
                        // Show modal
                        bookDetailsModal.classList.add('show');
                    }
                });
            });
            
            // Close modal functionality
            closeModalButtons.forEach(button => {
                button.addEventListener('click', function() {
                    bookDetailsModal.classList.remove('show');
                });
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === bookDetailsModal) {
                    bookDetailsModal.classList.remove('show');
                }
            });
        }
    });
</script>

<?php
Layout::bodyEnd();
Layout::footer();
?>
