<?php
// Test SQLite3 functionality
try {
    echo "Testing SQLite3 extension...\n";
    
    if (class_exists('SQLite3')) {
        echo "SQLite3 class exists!\n";
        
        $db_path = __DIR__ . '/database/test.db';
        $db_dir = dirname($db_path);
        
        // Create database directory if it doesn't exist
        if (!file_exists($db_dir)) {
            echo "Creating database directory...\n";
            mkdir($db_dir, 0755, true);
        }
        
        echo "Attempting to create/open SQLite database at: $db_path\n";
        $db = new SQLite3($db_path);
        echo "Successfully created/opened SQLite database!\n";
        
        // Test basic functionality
        echo "Testing basic SQLite functionality...\n";
        $db->exec('CREATE TABLE IF NOT EXISTS test (id INTEGER PRIMARY KEY, name TEXT)');
        $db->exec("INSERT INTO test (name) VALUES ('Test record')");
        $result = $db->query('SELECT * FROM test');
        $row = $result->fetchArray(SQLITE3_ASSOC);
        echo "Retrieved test record: " . json_encode($row) . "\n";
        
        echo "SQLite3 is working correctly!\n";
    } else {
        echo "ERROR: SQLite3 class does not exist despite extension being loaded!\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

// Check PHP version and configuration
echo "\nPHP Version: " . phpversion() . "\n";
echo "Loaded PHP Modules:\n";
$modules = get_loaded_extensions();
sort($modules);
echo implode(", ", $modules) . "\n";

// Check if PDO SQLite is working
try {
    echo "\nTesting PDO SQLite as an alternative...\n";
    $pdo_db_path = __DIR__ . '/database/test_pdo.db';
    $pdo = new PDO('sqlite:' . $pdo_db_path);
    echo "PDO SQLite connection successful!\n";
    
    // Test basic functionality
    $pdo->exec('CREATE TABLE IF NOT EXISTS test_pdo (id INTEGER PRIMARY KEY, name TEXT)');
    $pdo->exec("INSERT INTO test_pdo (name) VALUES ('Test PDO record')");
    $stmt = $pdo->query('SELECT * FROM test_pdo');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Retrieved PDO test record: " . json_encode($row) . "\n";
    
    echo "PDO SQLite is working correctly!\n";
} catch (PDOException $e) {
    echo "PDO ERROR: " . $e->getMessage() . "\n";
}
?>
