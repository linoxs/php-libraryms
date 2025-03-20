<?php
/**
 * Database connection check script
 */

require_once __DIR__ . '/includes/db.php';

// Test database connection
try {
    $db = get_db_connection();
    echo "Database connection successful!\n";
    
    // Check if tables exist
    $result = $db->query('SELECT name FROM sqlite_master WHERE type="table"');
    $tables = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $tables[] = $row['name'];
    }
    
    echo "Found " . count($tables) . " tables in the database:\n";
    echo implode(", ", $tables) . "\n\n";
    
    // Check if users table has data
    $result = $db->query('SELECT COUNT(*) as count FROM users');
    $row = $result->fetchArray(SQLITE3_ASSOC);
    echo "Number of users: " . $row['count'] . "\n";
    
    // Check if books table has data
    $result = $db->query('SELECT COUNT(*) as count FROM books');
    $row = $result->fetchArray(SQLITE3_ASSOC);
    echo "Number of books: " . $row['count'] . "\n";
    
    // Check if transactions table has data
    $result = $db->query('SELECT COUNT(*) as count FROM transactions');
    $row = $result->fetchArray(SQLITE3_ASSOC);
    echo "Number of transactions: " . $row['count'] . "\n";
    
    // Check database file size
    $db_path = __DIR__ . '/database/library.db';
    if (file_exists($db_path)) {
        echo "Database file size: " . round(filesize($db_path) / 1024, 2) . " KB\n";
    } else {
        echo "WARNING: Database file does not exist at expected path: $db_path\n";
    }
    
    // Check database directory permissions
    $db_dir = __DIR__ . '/database';
    if (is_dir($db_dir)) {
        echo "Database directory exists and has permissions: " . substr(sprintf('%o', fileperms($db_dir)), -4) . "\n";
    } else {
        echo "WARNING: Database directory does not exist at expected path: $db_dir\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: Database connection failed: " . $e->getMessage() . "\n";
}
?>
