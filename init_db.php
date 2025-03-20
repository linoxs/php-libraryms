<?php
/**
 * Database Initialization Script
 * 
 * This script initializes the database with sample data.
 * It should be run once to set up the database for testing.
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Initialize database
initialize_db();

// Check if the database is already initialized
$users = db_get_all('users');
foreach ($users as $user) {
    if ($user['username'] === 'admin') {
        echo "Database already initialized. Skipping...\n";
        exit;
    }
}

echo "Initializing database with sample data...\n";

// Create admin user
$admin_id = create_user('admin', 'admin123', 'admin@library.com', 'Administrator', 'admin');
if (!$admin_id) {
    echo "Failed to create admin user\n";
    exit;
}
echo "Created admin user (ID: $admin_id)\n";

// Create sample members
$member_ids = [];
$member_ids[] = create_user('john', 'password', 'john@example.com', 'John Doe', 'member');
$member_ids[] = create_user('jane', 'password', 'jane@example.com', 'Jane Smith', 'member');
$member_ids[] = create_user('bob', 'password', 'bob@example.com', 'Bob Johnson', 'member');
echo "Created " . count($member_ids) . " sample members\n";

// Create sample publishers
$publisher_ids = [];
$publisher_ids[] = create_publisher('Penguin Random House', '1745 Broadway, New York, NY 10019', 'contact@penguinrandomhouse.com');
$publisher_ids[] = create_publisher('HarperCollins', '195 Broadway, New York, NY 10007', 'contact@harpercollins.com');
$publisher_ids[] = create_publisher('Simon & Schuster', '1230 Avenue of the Americas, New York, NY 10020', 'contact@simonandschuster.com');
$publisher_ids[] = create_publisher('Macmillan Publishers', '120 Broadway, New York, NY 10271', 'contact@macmillan.com');
$publisher_ids[] = create_publisher('Hachette Book Group', '1290 Avenue of the Americas, New York, NY 10104', 'contact@hachettebookgroup.com');
echo "Created " . count($publisher_ids) . " sample publishers\n";

// Create sample books
$book_ids = [];

// Fiction books
$book_ids[] = create_book('To Kill a Mockingbird', 'Harper Lee', $publisher_ids[0], 1960, '9780446310789', 'Fiction', 5);
$book_ids[] = create_book('1984', 'George Orwell', $publisher_ids[0], 1949, '9780451524935', 'Fiction', 3);
$book_ids[] = create_book('The Great Gatsby', 'F. Scott Fitzgerald', $publisher_ids[1], 1925, '9780743273565', 'Fiction', 4);
$book_ids[] = create_book('Pride and Prejudice', 'Jane Austen', $publisher_ids[1], 1813, '9780141439518', 'Fiction', 2);
$book_ids[] = create_book('The Catcher in the Rye', 'J.D. Salinger', $publisher_ids[2], 1951, '9780316769488', 'Fiction', 3);

// Non-fiction books
$book_ids[] = create_book('Sapiens: A Brief History of Humankind', 'Yuval Noah Harari', $publisher_ids[2], 2011, '9780062316097', 'Non-Fiction', 2);
$book_ids[] = create_book('Educated', 'Tara Westover', $publisher_ids[3], 2018, '9780399590504', 'Non-Fiction', 3);
$book_ids[] = create_book('The Immortal Life of Henrietta Lacks', 'Rebecca Skloot', $publisher_ids[3], 2010, '9781400052189', 'Non-Fiction', 2);

// Science fiction books
$book_ids[] = create_book('Dune', 'Frank Herbert', $publisher_ids[4], 1965, '9780441172719', 'Science Fiction', 3);
$book_ids[] = create_book('The Hitchhiker\'s Guide to the Galaxy', 'Douglas Adams', $publisher_ids[4], 1979, '9780345391803', 'Science Fiction', 4);
$book_ids[] = create_book('Neuromancer', 'William Gibson', $publisher_ids[0], 1984, '9780441569595', 'Science Fiction', 2);

// Mystery books
$book_ids[] = create_book('The Girl with the Dragon Tattoo', 'Stieg Larsson', $publisher_ids[1], 2005, '9780307454546', 'Mystery', 3);
$book_ids[] = create_book('Gone Girl', 'Gillian Flynn', $publisher_ids[2], 2012, '9780307588371', 'Mystery', 2);
$book_ids[] = create_book('The Da Vinci Code', 'Dan Brown', $publisher_ids[3], 2003, '9780307474278', 'Mystery', 4);

// Fantasy books
$book_ids[] = create_book('Harry Potter and the Philosopher\'s Stone', 'J.K. Rowling', $publisher_ids[4], 1997, '9780747532743', 'Fantasy', 5);
$book_ids[] = create_book('The Hobbit', 'J.R.R. Tolkien', $publisher_ids[0], 1937, '9780618260300', 'Fantasy', 3);
$book_ids[] = create_book('A Game of Thrones', 'George R.R. Martin', $publisher_ids[1], 1996, '9780553593716', 'Fantasy', 4);

// Romance books
$book_ids[] = create_book('Pride and Prejudice', 'Jane Austen', $publisher_ids[2], 1813, '9780141439518', 'Romance', 3);
$book_ids[] = create_book('Outlander', 'Diana Gabaldon', $publisher_ids[3], 1991, '9780440212560', 'Romance', 2);
$book_ids[] = create_book('The Notebook', 'Nicholas Sparks', $publisher_ids[4], 1996, '9780553593716', 'Romance', 3);

echo "Created " . count($book_ids) . " sample books\n";

// Create sample transactions
$transaction_ids = [];

// Active transactions
$transaction_ids[] = borrow_book($member_ids[0], $book_ids[0]);
$transaction_ids[] = borrow_book($member_ids[0], $book_ids[5]);
$transaction_ids[] = borrow_book($member_ids[1], $book_ids[8]);
$transaction_ids[] = borrow_book($member_ids[2], $book_ids[14]);


// Overdue transactions
$transaction_ids[] = db_insert(
    "INSERT INTO transactions (user_id, book_id, borrowed_at, due_date, status) VALUES (:user_id, :book_id, :borrowed_at, :due_date, 'overdue')",
    [
        ':user_id' => $member_ids[1], 
        ':book_id' => $book_ids[2], 
        ':borrowed_at' => date('Y-m-d', strtotime('-20 days')), 
        ':due_date' => date('Y-m-d', strtotime('-5 days'))
    ]
);
$transaction_ids[] = db_insert(
    "INSERT INTO transactions (user_id, book_id, borrowed_at, due_date, status) VALUES (:user_id, :book_id, :borrowed_at, :due_date, 'overdue')",
    [
        ':user_id' => $member_ids[2], 
        ':book_id' => $book_ids[10], 
        ':borrowed_at' => date('Y-m-d', strtotime('-15 days')), 
        ':due_date' => date('Y-m-d', strtotime('-1 days'))
    ]
);

// Returned transactions
$returned_transaction_id = db_insert(
    "INSERT INTO transactions (user_id, book_id, borrowed_at, due_date, status) VALUES (:user_id, :book_id, :borrowed_at, :due_date, 'returned')",
    [
        ':user_id' => $member_ids[0], 
        ':book_id' => $book_ids[3], 
        ':borrowed_at' => date('Y-m-d', strtotime('-30 days')), 
        ':due_date' => date('Y-m-d', strtotime('-16 days'))
    ]
);
db_execute(
    "UPDATE transactions SET returned_at = :returned_at WHERE id = :id",
    [':returned_at' => date('Y-m-d', strtotime('-18 days')), ':id' => $returned_transaction_id]
);

$returned_transaction_id = db_insert(
    "INSERT INTO transactions (user_id, book_id, borrowed_at, due_date, status) VALUES (:user_id, :book_id, :borrowed_at, :due_date, 'returned')",
    [
        ':user_id' => $member_ids[1], 
        ':book_id' => $book_ids[7], 
        ':borrowed_at' => date('Y-m-d', strtotime('-25 days')), 
        ':due_date' => date('Y-m-d', strtotime('-11 days'))
    ]
);
db_execute(
    "UPDATE transactions SET returned_at = :returned_at WHERE id = :id",
    [':returned_at' => date('Y-m-d', strtotime('-10 days')), ':id' => $returned_transaction_id]
);

$returned_transaction_id = db_insert(
    "INSERT INTO transactions (user_id, book_id, borrowed_at, due_date, status) VALUES (:user_id, :book_id, :borrowed_at, :due_date, 'returned')",
    [
        ':user_id' => $member_ids[2], 
        ':book_id' => $book_ids[12], 
        ':borrowed_at' => date('Y-m-d', strtotime('-40 days')), 
        ':due_date' => date('Y-m-d', strtotime('-26 days'))
    ]
);
db_execute(
    "UPDATE transactions SET returned_at = :returned_at WHERE id = :id",
    [':returned_at' => date('Y-m-d', strtotime('-25 days')), ':id' => $returned_transaction_id]
);

echo "Created sample transactions\n";

// Update book availability based on transactions
$books = db_get_all('books');

foreach ($books as $book) {
    // Count active transactions for this book
    $transactions = db_get_all('transactions');
    $active_count = 0;
    
    foreach ($transactions as $transaction) {
        if ($transaction['book_id'] == $book['id'] && empty($transaction['returned_at'])) {
            $active_count++;
        }
    }
    
    // Calculate available copies
    $available = max(0, $book['quantity'] - $active_count);
    
    // Update book availability
    db_update_record('books', $book['id'], ['available' => $available]);
}

echo "Updated book availability\n";
echo "Database initialization complete!\n";

echo "\nSample Login Credentials:\n";
echo "Admin: username='admin', password='admin123'\n";
echo "Member: username='john', password='password'\n";
echo "Member: username='jane', password='password'\n";
echo "Member: username='bob', password='password'\n";
?>
