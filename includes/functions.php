<?php
/**
 * Common utility functions for the Library Management System
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';

/**
 * Sanitize user input
 */
function sanitize_input($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitize_input($value);
        }
        return $input;
    }
    
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Hash password using bcrypt
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password against hash
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate a random token for password reset
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Get user by ID
 */
function get_user_by_id($user_id) {
    $result = db_query(
        "SELECT id, username, email, full_name, role, created_at, updated_at 
         FROM users 
         WHERE id = :id",
        [':id' => $user_id]
    );
    
    return db_fetch_assoc($result);
}

/**
 * Get user by username
 */
function get_user_by_username($username) {
    $result = db_query(
        "SELECT * FROM users WHERE username = :username",
        [':username' => $username]
    );
    
    return db_fetch_assoc($result);
}

/**
 * Get user by email
 */
function get_user_by_email($email) {
    $result = db_query(
        "SELECT * FROM users WHERE email = :email",
        [':email' => $email]
    );
    
    return db_fetch_assoc($result);
}

/**
 * Create a new user
 */
function create_user($username, $password, $email, $full_name, $role = 'member') {
    $hashed_password = hash_password($password);
    
    return db_insert(
        "INSERT INTO users (username, password, email, full_name, role) 
         VALUES (:username, :password, :email, :full_name, :role)",
        [
            ':username' => $username,
            ':password' => $hashed_password,
            ':email' => $email,
            ':full_name' => $full_name,
            ':role' => $role
        ]
    );
}

/**
 * Update user information
 */
function update_user($user_id, $data) {
    $fields = [];
    $params = [':id' => $user_id];
    
    // Build the SET clause dynamically based on provided data
    foreach ($data as $field => $value) {
        if (in_array($field, ['username', 'email', 'full_name', 'role'])) {
            $fields[] = "$field = :$field";
            $params[":$field"] = $value;
        }
    }
    
    // Add updated_at timestamp
    $fields[] = "updated_at = CURRENT_TIMESTAMP";
    
    if (empty($fields)) {
        return false;
    }
    
    $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
    
    return db_execute($sql, $params);
}

/**
 * Update user password
 */
function update_user_password($user_id, $new_password) {
    $hashed_password = hash_password($new_password);
    
    return db_execute(
        "UPDATE users SET password = :password, updated_at = CURRENT_TIMESTAMP WHERE id = :id",
        [':password' => $hashed_password, ':id' => $user_id]
    );
}

/**
 * Delete a user
 */
function delete_user($user_id) {
    return db_execute(
        "DELETE FROM users WHERE id = :id",
        [':id' => $user_id]
    );
}

/**
 * Get all users with pagination
 */
function get_all_users($page = 1, $per_page = 10) {
    $offset = ($page - 1) * $per_page;
    
    $result = db_query(
        "SELECT id, username, email, full_name, role, created_at, updated_at 
         FROM users 
         ORDER BY id 
         LIMIT :limit OFFSET :offset",
        [':limit' => $per_page, ':offset' => $offset]
    );
    
    return db_fetch_all($result);
}

/**
 * Count total users
 */
function count_users() {
    $result = db_query("SELECT COUNT(*) as count FROM users");
    $row = db_fetch_assoc($result);
    return $row['count'];
}

/**
 * Get book by ID
 */
function get_book_by_id($book_id) {
    $result = db_query(
        "SELECT b.*, p.name as publisher_name 
         FROM books b 
         LEFT JOIN publishers p ON b.publisher_id = p.id 
         WHERE b.id = :id",
        [':id' => $book_id]
    );
    
    return db_fetch_assoc($result);
}

/**
 * Get all books with pagination and optional search
 */
function get_all_books($page = 1, $per_page = 10, $search = null) {
    $offset = ($page - 1) * $per_page;
    $params = [':limit' => $per_page, ':offset' => $offset];
    
    $sql = "SELECT b.*, p.name as publisher_name 
            FROM books b 
            LEFT JOIN publishers p ON b.publisher_id = p.id";
    
    // Add search condition if provided
    if ($search) {
        $sql .= " WHERE b.title LIKE :search OR b.author LIKE :search OR b.genre LIKE :search";
        $params[':search'] = "%$search%";
    }
    
    $sql .= " ORDER BY b.id LIMIT :limit OFFSET :offset";
    
    $result = db_query($sql, $params);
    
    return db_fetch_all($result);
}

/**
 * Count total books with optional search
 */
function count_books($search = null) {
    $sql = "SELECT COUNT(*) as count FROM books";
    $params = [];
    
    // Add search condition if provided
    if ($search) {
        $sql .= " WHERE title LIKE :search OR author LIKE :search OR genre LIKE :search";
        $params[':search'] = "%$search%";
    }
    
    $result = db_query($sql, $params);
    $row = db_fetch_assoc($result);
    return $row['count'];
}

/**
 * Create a new book
 */
function create_book($title, $author, $publisher_id, $year, $isbn, $genre, $quantity) {
    return db_insert(
        "INSERT INTO books (title, author, publisher_id, year, isbn, genre, quantity, available) 
         VALUES (:title, :author, :publisher_id, :year, :isbn, :genre, :quantity, :available)",
        [
            ':title' => $title,
            ':author' => $author,
            ':publisher_id' => $publisher_id,
            ':year' => $year,
            ':isbn' => $isbn,
            ':genre' => $genre,
            ':quantity' => $quantity,
            ':available' => $quantity // Initially all copies are available
        ]
    );
}

/**
 * Update book information
 */
function update_book($book_id, $data) {
    $fields = [];
    $params = [':id' => $book_id];
    
    // Build the SET clause dynamically based on provided data
    foreach ($data as $field => $value) {
        if (in_array($field, ['title', 'author', 'publisher_id', 'year', 'isbn', 'genre', 'quantity', 'available'])) {
            $fields[] = "$field = :$field";
            $params[":$field"] = $value;
        }
    }
    
    // Add updated_at timestamp
    $fields[] = "updated_at = CURRENT_TIMESTAMP";
    
    if (empty($fields)) {
        return false;
    }
    
    $sql = "UPDATE books SET " . implode(', ', $fields) . " WHERE id = :id";
    
    return db_execute($sql, $params);
}

/**
 * Delete a book
 */
function delete_book($book_id) {
    return db_execute(
        "DELETE FROM books WHERE id = :id",
        [':id' => $book_id]
    );
}

/**
 * Get publisher by ID
 */
function get_publisher_by_id($publisher_id) {
    $result = db_query(
        "SELECT * FROM publishers WHERE id = :id",
        [':id' => $publisher_id]
    );
    
    return db_fetch_assoc($result);
}

/**
 * Get all publishers
 */
function get_all_publishers() {
    $result = db_query("SELECT * FROM publishers ORDER BY name");
    
    return db_fetch_all($result);
}

/**
 * Create a new publisher
 */
function create_publisher($name, $address, $contact_info) {
    return db_insert(
        "INSERT INTO publishers (name, address, contact_info) 
         VALUES (:name, :address, :contact_info)",
        [
            ':name' => $name,
            ':address' => $address,
            ':contact_info' => $contact_info
        ]
    );
}

/**
 * Update publisher information
 */
function update_publisher($publisher_id, $name, $address, $contact_info) {
    return db_execute(
        "UPDATE publishers 
         SET name = :name, address = :address, contact_info = :contact_info, updated_at = CURRENT_TIMESTAMP 
         WHERE id = :id",
        [
            ':id' => $publisher_id,
            ':name' => $name,
            ':address' => $address,
            ':contact_info' => $contact_info
        ]
    );
}

/**
 * Delete a publisher
 */
function delete_publisher($publisher_id) {
    return db_execute(
        "DELETE FROM publishers WHERE id = :id",
        [':id' => $publisher_id]
    );
}

/**
 * Borrow a book
 */
function borrow_book($user_id, $book_id, $due_days = 14) {
    // Check if book is available
    $book = get_book_by_id($book_id);
    
    if (!$book || $book['available'] <= 0) {
        return false;
    }
    
    // Calculate due date
    $due_date = date('Y-m-d H:i:s', strtotime("+$due_days days"));
    
    // Start transaction
    $db = get_db_connection();
    $db->exec('BEGIN TRANSACTION');
    
    try {
        // Create transaction record
        $transaction_id = db_insert(
            "INSERT INTO transactions (user_id, book_id, due_date, status) 
             VALUES (:user_id, :book_id, :due_date, 'borrowed')",
            [
                ':user_id' => $user_id,
                ':book_id' => $book_id,
                ':due_date' => $due_date
            ]
        );
        
        if (!$transaction_id) {
            throw new Exception("Failed to create transaction record");
        }
        
        // Update book availability
        $result = db_execute(
            "UPDATE books 
             SET available = available - 1, updated_at = CURRENT_TIMESTAMP 
             WHERE id = :id AND available > 0",
            [':id' => $book_id]
        );
        
        if (!$result) {
            throw new Exception("Failed to update book availability");
        }
        
        // Commit transaction
        $db->exec('COMMIT');
        
        return $transaction_id;
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->exec('ROLLBACK');
        error_log("Borrow book error: " . $e->getMessage());
        return false;
    }
}

/**
 * Return a book
 */
function return_book($transaction_id) {
    // Get transaction details
    $result = db_query(
        "SELECT * FROM transactions WHERE id = :id AND status = 'borrowed'",
        [':id' => $transaction_id]
    );
    
    $transaction = db_fetch_assoc($result);
    
    if (!$transaction) {
        return false;
    }
    
    // Start transaction
    $db = get_db_connection();
    $db->exec('BEGIN TRANSACTION');
    
    try {
        // Update transaction record
        $result = db_execute(
            "UPDATE transactions 
             SET returned_at = CURRENT_TIMESTAMP, status = 'returned' 
             WHERE id = :id",
            [':id' => $transaction_id]
        );
        
        if (!$result) {
            throw new Exception("Failed to update transaction record");
        }
        
        // Update book availability
        $result = db_execute(
            "UPDATE books 
             SET available = available + 1, updated_at = CURRENT_TIMESTAMP 
             WHERE id = :id",
            [':id' => $transaction['book_id']]
        );
        
        if (!$result) {
            throw new Exception("Failed to update book availability");
        }
        
        // Commit transaction
        $db->exec('COMMIT');
        
        return true;
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->exec('ROLLBACK');
        error_log("Return book error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user's borrowed books
 */
function get_user_borrowed_books($user_id) {
    $result = db_query(
        "SELECT t.*, b.title, b.author, b.isbn 
         FROM transactions t 
         JOIN books b ON t.book_id = b.id 
         WHERE t.user_id = :user_id AND t.status = 'borrowed' 
         ORDER BY t.borrowed_at DESC",
        [':user_id' => $user_id]
    );
    
    return db_fetch_all($result);
}

/**
 * Get user's active transactions (currently borrowed books)
 */
function get_user_active_transactions($user_id) {
    $result = db_query(
        "SELECT t.*, b.title, b.author, b.isbn 
         FROM transactions t 
         JOIN books b ON t.book_id = b.id 
         WHERE t.user_id = :user_id AND t.status = 'borrowed' 
         ORDER BY t.borrowed_at DESC",
        [':user_id' => $user_id]
    );
    
    return db_fetch_all($result);
}

/**
 * Get user's borrow history
 */
function get_user_borrow_history($user_id) {
    $result = db_query(
        "SELECT t.*, b.title, b.author, b.isbn 
         FROM transactions t 
         JOIN books b ON t.book_id = b.id 
         WHERE t.user_id = :user_id 
         ORDER BY t.borrowed_at DESC",
        [':user_id' => $user_id]
    );
    
    return db_fetch_all($result);
}

/**
 * Get user's transaction history with limit
 */
function get_user_transaction_history($user_id, $limit = null) {
    $sql = "SELECT t.*, b.title, b.author, b.isbn 
            FROM transactions t 
            JOIN books b ON t.book_id = b.id 
            WHERE t.user_id = :user_id 
            ORDER BY t.borrowed_at DESC";
    
    if ($limit !== null) {
        $sql .= " LIMIT :limit";
        $result = db_query($sql, [':user_id' => $user_id, ':limit' => $limit]);
    } else {
        $result = db_query($sql, [':user_id' => $user_id]);
    }
    
    return db_fetch_all($result);
}

/**
 * Get all transactions with pagination
 */
function get_all_transactions($page = 1, $per_page = 10) {
    $offset = ($page - 1) * $per_page;
    
    $result = db_query(
        "SELECT t.*, u.username, u.full_name, b.title, b.author 
         FROM transactions t 
         JOIN users u ON t.user_id = u.id 
         JOIN books b ON t.book_id = b.id 
         ORDER BY t.borrowed_at DESC 
         LIMIT :limit OFFSET :offset",
        [':limit' => $per_page, ':offset' => $offset]
    );
    
    return db_fetch_all($result);
}

/**
 * Count total transactions
 */
function count_transactions() {
    $result = db_query("SELECT COUNT(*) as count FROM transactions");
    $row = db_fetch_assoc($result);
    return $row['count'];
}

/**
 * Get overdue books
 */
function get_overdue_books() {
    $current_date = date('Y-m-d H:i:s');
    
    $result = db_query(
        "SELECT t.*, u.username, u.full_name, b.title, b.author 
         FROM transactions t 
         JOIN users u ON t.user_id = u.id 
         JOIN books b ON t.book_id = b.id 
         WHERE t.status = 'borrowed' AND t.due_date < :current_date 
         ORDER BY t.due_date",
        [':current_date' => $current_date]
    );
    
    return db_fetch_all($result);
}

/**
 * Get user's overdue books
 */
function get_user_overdue_books($user_id) {
    $current_date = date('Y-m-d H:i:s');
    
    $result = db_query(
        "SELECT t.*, b.title, b.author, b.isbn 
         FROM transactions t 
         JOIN books b ON t.book_id = b.id 
         WHERE t.user_id = :user_id AND t.status = 'borrowed' AND t.due_date < :current_date 
         ORDER BY t.due_date",
        [':user_id' => $user_id, ':current_date' => $current_date]
    );
    
    return db_fetch_all($result);
}

/**
 * Count all transactions for a user
 */
function count_user_transactions($user_id) {
    $result = db_query(
        "SELECT COUNT(*) as count FROM transactions WHERE user_id = :user_id",
        [':user_id' => $user_id]
    );
    
    $data = db_fetch($result);
    return $data ? (int)$data['count'] : 0;
}

/**
 * Count active transactions for a user
 */
function count_user_active_transactions($user_id) {
    $result = db_query(
        "SELECT COUNT(*) as count FROM transactions WHERE user_id = :user_id AND status = 'borrowed'",
        [':user_id' => $user_id]
    );
    
    $data = db_fetch($result);
    return $data ? (int)$data['count'] : 0;
}

/**
 * Count overdue books for a user
 */
function count_user_overdue_books($user_id) {
    $current_date = date('Y-m-d H:i:s');
    
    $result = db_query(
        "SELECT COUNT(*) as count FROM transactions 
         WHERE user_id = :user_id AND status = 'borrowed' AND due_date < :current_date",
        [':user_id' => $user_id, ':current_date' => $current_date]
    );
    
    $data = db_fetch($result);
    return $data ? (int)$data['count'] : 0;
}

/**
 * Update overdue status
 */
function update_overdue_status() {
    $current_date = date('Y-m-d H:i:s');
    
    return db_execute(
        "UPDATE transactions 
         SET status = 'overdue' 
         WHERE status = 'borrowed' AND due_date < :current_date",
        [':current_date' => $current_date]
    );
}

/**
 * Create password reset token
 */
function create_password_reset_token($email) {
    // Generate token
    $token = generate_token();
    
    // Set expiration time (1 hour from now)
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Delete any existing tokens for this email
    db_execute(
        "DELETE FROM password_resets WHERE email = :email",
        [':email' => $email]
    );
    
    // Insert new token
    $result = db_insert(
        "INSERT INTO password_resets (email, token, expires_at) 
         VALUES (:email, :token, :expires_at)",
        [
            ':email' => $email,
            ':token' => $token,
            ':expires_at' => $expires_at
        ]
    );
    
    if ($result) {
        return $token;
    }
    
    return false;
}

/**
 * Verify password reset token
 */
function verify_password_reset_token($email, $token) {
    $current_time = date('Y-m-d H:i:s');
    
    $result = db_query(
        "SELECT * FROM password_resets 
         WHERE email = :email AND token = :token AND expires_at > :current_time",
        [
            ':email' => $email,
            ':token' => $token,
            ':current_time' => $current_time
        ]
    );
    
    return db_fetch_assoc($result) !== false;
}

/**
 * Delete password reset token
 */
function delete_password_reset_token($email) {
    return db_execute(
        "DELETE FROM password_resets WHERE email = :email",
        [':email' => $email]
    );
}

/**
 * Generate pagination links
 */
function generate_pagination($current_page, $total_pages, $url_pattern) {
    $pagination = '<div class="pagination">';
    
    // Previous page link
    if ($current_page > 1) {
        $pagination .= '<a href="' . sprintf($url_pattern, $current_page - 1) . '">&laquo; Previous</a>';
    } else {
        $pagination .= '<span class="disabled">&laquo; Previous</span>';
    }
    
    // Page links
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $current_page + 2);
    
    if ($start_page > 1) {
        $pagination .= '<a href="' . sprintf($url_pattern, 1) . '">1</a>';
        if ($start_page > 2) {
            $pagination .= '<span class="ellipsis">...</span>';
        }
    }
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        if ($i == $current_page) {
            $pagination .= '<span class="current">' . $i . '</span>';
        } else {
            $pagination .= '<a href="' . sprintf($url_pattern, $i) . '">' . $i . '</a>';
        }
    }
    
    if ($end_page < $total_pages) {
        if ($end_page < $total_pages - 1) {
            $pagination .= '<span class="ellipsis">...</span>';
        }
        $pagination .= '<a href="' . sprintf($url_pattern, $total_pages) . '">' . $total_pages . '</a>';
    }
    
    // Next page link
    if ($current_page < $total_pages) {
        $pagination .= '<a href="' . sprintf($url_pattern, $current_page + 1) . '">Next &raquo;</a>';
    } else {
        $pagination .= '<span class="disabled">Next &raquo;</span>';
    }
    
    $pagination .= '</div>';
    
    return $pagination;
}
