<?php
/**
 * Admin Books Management
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
$book_id = sanitize_input($_GET['id'] ?? '');
$search = sanitize_input($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;

// Process book actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_book']) || isset($_POST['edit_book'])) {
        // Sanitize input
        $title = sanitize_input($_POST['title'] ?? '');
        $author = sanitize_input($_POST['author'] ?? '');
        $publisher_id = intval($_POST['publisher_id'] ?? 0);
        $year = intval($_POST['year'] ?? 0);
        $isbn = sanitize_input($_POST['isbn'] ?? '');
        $genre = sanitize_input($_POST['genre'] ?? '');
        $quantity = intval($_POST['quantity'] ?? 1);
        $available = isset($_POST['edit_book']) ? intval($_POST['available'] ?? 0) : $quantity;
        
        // Validate input
        if (empty($title)) {
            $errors['title'] = 'Title is required';
        }
        
        if (empty($author)) {
            $errors['author'] = 'Author is required';
        }
        
        if ($year <= 0) {
            $errors['year'] = 'Valid year is required';
        }
        
        if ($quantity <= 0) {
            $errors['quantity'] = 'Quantity must be greater than 0';
        }
        
        if (isset($_POST['edit_book']) && ($available < 0 || $available > $quantity)) {
            $errors['available'] = 'Available must be between 0 and ' . $quantity;
        }
        
        // If no validation errors, add or update book
        if (empty($errors)) {
            if (isset($_POST['add_book'])) {
                // Add new book
                $book_id = create_book($title, $author, $publisher_id, $year, $isbn, $genre, $quantity);
                
                if ($book_id) {
                    set_flash_message('success', 'Book added successfully');
                    header('Location: /admin/books.php');
                    exit;
                } else {
                    $errors['add'] = 'Failed to add book';
                }
            } elseif (isset($_POST['edit_book'])) {
                // Update existing book
                $book_id = intval($_POST['book_id'] ?? 0);
                
                if ($book_id > 0) {
                    $result = update_book($book_id, [
                        'title' => $title,
                        'author' => $author,
                        'publisher_id' => $publisher_id,
                        'year' => $year,
                        'isbn' => $isbn,
                        'genre' => $genre,
                        'quantity' => $quantity,
                        'available' => $available
                    ]);
                    
                    if ($result) {
                        set_flash_message('success', 'Book updated successfully');
                        header('Location: /admin/books.php');
                        exit;
                    } else {
                        $errors['edit'] = 'Failed to update book';
                    }
                } else {
                    $errors['edit'] = 'Invalid book ID';
                }
            }
        }
    } elseif (isset($_POST['delete_book'])) {
        // Delete book
        $book_id = intval($_POST['book_id'] ?? 0);
        
        if ($book_id > 0) {
            $result = delete_book($book_id);
            
            if ($result) {
                set_flash_message('success', 'Book deleted successfully');
                header('Location: /admin/books.php');
                exit;
            } else {
                set_flash_message('error', 'Failed to delete book');
                header('Location: /admin/books.php');
                exit;
            }
        } else {
            set_flash_message('error', 'Invalid book ID');
            header('Location: /admin/books.php');
            exit;
        }
    }
}

// Get book data for edit
$edit_book = null;
if ($action === 'edit' && $book_id) {
    $edit_book = get_book_by_id($book_id);
    
    if (!$edit_book) {
        set_flash_message('error', 'Book not found');
        header('Location: /admin/books.php');
        exit;
    }
}

// Get all publishers for select dropdown
$publishers = get_all_publishers();
$publisher_options = [0 => 'Select Publisher'];
foreach ($publishers as $publisher) {
    $publisher_options[$publisher['id']] = $publisher['name'];
}

// Get books with pagination and search
$total_books = count_books($search);
$total_pages = ceil($total_books / $per_page);
$books = get_all_books($page, $per_page, $search);

// Page content
Layout::header('Manage Books');
Layout::bodyStart();
?>

<div class="admin-books">
    <Layout::pageTitle('Manage Books');
    
    <div class="action-buttons">
        <button id="add-book-btn" class="btn btn-primary">Add New Book</button>
    </div>
    
    <!-- Search Form -->
    <div class="search-section">
        <?php Layout::searchForm('/admin/books.php', 'Search by title, author, or genre', $search); ?>
    </div>
    
    <!-- Books Table -->
    <div class="books-table">
        <?php if (empty($books)): ?>
            <p>No books found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Publisher</th>
                            <th>Year</th>
                            <th>ISBN</th>
                            <th>Genre</th>
                            <th>Available</th>
                            <th>Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $book): ?>
                            <tr>
                                <td><?php echo $book['id']; ?></td>
                                <td><?php echo htmlspecialchars($book['title']); ?></td>
                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                <td><?php echo htmlspecialchars($book['publisher_name'] ?? 'None'); ?></td>
                                <td><?php echo $book['year']; ?></td>
                                <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                                <td><?php echo htmlspecialchars($book['genre']); ?></td>
                                <td><?php echo $book['available']; ?></td>
                                <td><?php echo $book['quantity']; ?></td>
                                <td>
                                    <div class="action-links">
                                        <a href="/admin/books.php?action=edit&id=<?php echo $book['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                        <button class="btn btn-sm btn-danger delete-book-btn" data-id="<?php echo $book['id']; ?>" data-title="<?php echo htmlspecialchars($book['title']); ?>">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <?php echo generate_pagination($page, $total_pages, '/admin/books.php?page=%d' . ($search ? '&search=' . urlencode($search) : '')); ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Add Book Modal -->
    <div id="add-book-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Book</h3>
                <button type="button" class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <?php
                Layout::formStart('/admin/books.php', 'post', 'add-book-form');
                
                // Title field
                $title_error = isset($errors['title']) ? $errors['title'] : null;
                $title_label = Layout::label('title', 'Title');
                $title_input = Layout::textInput('title', $_POST['title'] ?? '', 'title', 'Enter book title', true);
                Layout::formGroup($title_label, $title_input, $title_error);
                
                // Author field
                $author_error = isset($errors['author']) ? $errors['author'] : null;
                $author_label = Layout::label('author', 'Author');
                $author_input = Layout::textInput('author', $_POST['author'] ?? '', 'author', 'Enter author name', true);
                Layout::formGroup($author_label, $author_input, $author_error);
                
                // Publisher field
                $publisher_error = isset($errors['publisher_id']) ? $errors['publisher_id'] : null;
                $publisher_label = Layout::label('publisher_id', 'Publisher');
                $publisher_input = Layout::select('publisher_id', $publisher_options, $_POST['publisher_id'] ?? 0, 'publisher_id');
                Layout::formGroup($publisher_label, $publisher_input, $publisher_error);
                
                // Year field
                $year_error = isset($errors['year']) ? $errors['year'] : null;
                $year_label = Layout::label('year', 'Year');
                $year_input = Layout::numberInput('year', $_POST['year'] ?? date('Y'), 'year', 1000, date('Y'), true);
                Layout::formGroup($year_label, $year_input, $year_error);
                
                // ISBN field
                $isbn_error = isset($errors['isbn']) ? $errors['isbn'] : null;
                $isbn_label = Layout::label('isbn', 'ISBN');
                $isbn_input = Layout::textInput('isbn', $_POST['isbn'] ?? '', 'isbn', 'Enter ISBN');
                Layout::formGroup($isbn_label, $isbn_input, $isbn_error);
                
                // Genre field
                $genre_error = isset($errors['genre']) ? $errors['genre'] : null;
                $genre_label = Layout::label('genre', 'Genre');
                $genre_input = Layout::textInput('genre', $_POST['genre'] ?? '', 'genre', 'Enter genre');
                Layout::formGroup($genre_label, $genre_input, $genre_error);
                
                // Quantity field
                $quantity_error = isset($errors['quantity']) ? $errors['quantity'] : null;
                $quantity_label = Layout::label('quantity', 'Quantity');
                $quantity_input = Layout::numberInput('quantity', $_POST['quantity'] ?? 1, 'quantity', 1, null, true);
                Layout::formGroup($quantity_label, $quantity_input, $quantity_error);
                
                // Submit button
                echo Layout::submitButton('Add Book', 'add_book');
                
                Layout::formEnd();
                ?>
            </div>
        </div>
    </div>
    
    <!-- Edit Book Modal -->
    <?php if ($edit_book): ?>
    <div id="edit-book-modal" class="modal show">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Book</h3>
                <button type="button" class="close-modal" onclick="window.location='/admin/books.php'">&times;</button>
            </div>
            <div class="modal-body">
                <?php
                Layout::formStart('/admin/books.php', 'post', 'edit-book-form');
                
                // Hidden book ID field
                echo '<input type="hidden" name="book_id" value="' . $edit_book['id'] . '">';
                
                // Title field
                $title_error = isset($errors['title']) ? $errors['title'] : null;
                $title_label = Layout::label('title', 'Title');
                $title_input = Layout::textInput('title', $_POST['title'] ?? $edit_book['title'], 'title', 'Enter book title', true);
                Layout::formGroup($title_label, $title_input, $title_error);
                
                // Author field
                $author_error = isset($errors['author']) ? $errors['author'] : null;
                $author_label = Layout::label('author', 'Author');
                $author_input = Layout::textInput('author', $_POST['author'] ?? $edit_book['author'], 'author', 'Enter author name', true);
                Layout::formGroup($author_label, $author_input, $author_error);
                
                // Publisher field
                $publisher_error = isset($errors['publisher_id']) ? $errors['publisher_id'] : null;
                $publisher_label = Layout::label('publisher_id', 'Publisher');
                $publisher_input = Layout::select('publisher_id', $publisher_options, $_POST['publisher_id'] ?? $edit_book['publisher_id'], 'publisher_id');
                Layout::formGroup($publisher_label, $publisher_input, $publisher_error);
                
                // Year field
                $year_error = isset($errors['year']) ? $errors['year'] : null;
                $year_label = Layout::label('year', 'Year');
                $year_input = Layout::numberInput('year', $_POST['year'] ?? $edit_book['year'], 'year', 1000, date('Y'), true);
                Layout::formGroup($year_label, $year_input, $year_error);
                
                // ISBN field
                $isbn_error = isset($errors['isbn']) ? $errors['isbn'] : null;
                $isbn_label = Layout::label('isbn', 'ISBN');
                $isbn_input = Layout::textInput('isbn', $_POST['isbn'] ?? $edit_book['isbn'], 'isbn', 'Enter ISBN');
                Layout::formGroup($isbn_label, $isbn_input, $isbn_error);
                
                // Genre field
                $genre_error = isset($errors['genre']) ? $errors['genre'] : null;
                $genre_label = Layout::label('genre', 'Genre');
                $genre_input = Layout::textInput('genre', $_POST['genre'] ?? $edit_book['genre'], 'genre', 'Enter genre');
                Layout::formGroup($genre_label, $genre_input, $genre_error);
                
                // Quantity field
                $quantity_error = isset($errors['quantity']) ? $errors['quantity'] : null;
                $quantity_label = Layout::label('quantity', 'Quantity');
                $quantity_input = Layout::numberInput('quantity', $_POST['quantity'] ?? $edit_book['quantity'], 'quantity', 1, null, true);
                Layout::formGroup($quantity_label, $quantity_input, $quantity_error);
                
                // Available field
                $available_error = isset($errors['available']) ? $errors['available'] : null;
                $available_label = Layout::label('available', 'Available');
                $available_input = Layout::numberInput('available', $_POST['available'] ?? $edit_book['available'], 'available', 0, $edit_book['quantity'], true);
                Layout::formGroup($available_label, $available_input, $available_error);
                
                // Submit button
                echo Layout::submitButton('Update Book', 'edit_book');
                
                Layout::formEnd();
                ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Delete Book Modal -->
    <div id="delete-book-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Delete Book</h3>
                <button type="button" class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the book "<span id="delete-book-title"></span>"?</p>
                <p>This action cannot be undone.</p>
                
                <?php
                Layout::formStart('/admin/books.php', 'post', 'delete-book-form');
                
                // Hidden book ID field
                echo '<input type="hidden" name="book_id" id="delete-book-id" value="">';
                
                // Submit button
                echo Layout::submitButton('Delete Book', 'delete_book', ['class' => 'btn-danger']);
                
                Layout::formEnd();
                ?>
            </div>
        </div>
    </div>
</div>

<style>
    .admin-books {
        margin-bottom: 2rem;
    }
    
    .action-buttons {
        margin-bottom: 1.5rem;
    }
    
    .search-section {
        margin-bottom: 1.5rem;
    }
    
    .books-table {
        margin-bottom: 1.5rem;
    }
    
    .action-links {
        display: flex;
        gap: 0.5rem;
    }
</style>

<script>
    // Modal functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Add Book Modal
        const addBookBtn = document.getElementById('add-book-btn');
        const addBookModal = document.getElementById('add-book-modal');
        
        if (addBookBtn && addBookModal) {
            addBookBtn.addEventListener('click', function() {
                addBookModal.classList.add('show');
            });
            
            const closeButtons = addBookModal.querySelectorAll('.close-modal');
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    addBookModal.classList.remove('show');
                });
            });
        }
        
        // Delete Book Modal
        const deleteButtons = document.querySelectorAll('.delete-book-btn');
        const deleteBookModal = document.getElementById('delete-book-modal');
        const deleteBookId = document.getElementById('delete-book-id');
        const deleteBookTitle = document.getElementById('delete-book-title');
        
        if (deleteButtons.length > 0 && deleteBookModal && deleteBookId && deleteBookTitle) {
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const title = this.getAttribute('data-title');
                    
                    deleteBookId.value = id;
                    deleteBookTitle.textContent = title;
                    
                    deleteBookModal.classList.add('show');
                });
            });
            
            const closeButtons = deleteBookModal.querySelectorAll('.close-modal');
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    deleteBookModal.classList.remove('show');
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
