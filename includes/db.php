<?php
/**
 * Database connection handler
 * Uses SQLite for data storage
 */

function get_db_connection() {
    static $db = null;
    
    if ($db === null) {
        $db_path = __DIR__ . '/../database/library.db';
        
        // Create database directory if it doesn't exist
        $db_dir = dirname($db_path);
        if (!file_exists($db_dir)) {
            mkdir($db_dir, 0755, true);
        }
        
        // Create/open the database
        try {
            // Ensure we're using the correct class with full namespace if needed
            if (!class_exists('SQLite3')) {
                die("SQLite3 extension is not available. Please install or enable the SQLite3 extension.");
            }
            
            $db = new \SQLite3($db_path);
            $db->enableExceptions(true);
            
            // Set pragmas for better performance and safety
            $db->exec('PRAGMA foreign_keys = ON');
            $db->exec('PRAGMA journal_mode = WAL');
        } catch (\Exception $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    return $db;
}

/**
 * Initialize the database with schema if it doesn't exist
 */
function initialize_db() {
    $db = get_db_connection();
    
    // Check if the database is new by looking for the users table
    $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
    if (!$result->fetchArray()) {
        // Database is new, initialize with schema
        $schema_file = __DIR__ . '/../sql/schema.sql';
        $schema_sql = file_get_contents($schema_file);
        
        // Execute schema SQL as separate statements
        $statements = explode(';', $schema_sql);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                try {
                    $db->exec($statement);
                } catch (\Exception $e) {
                    die("Error initializing database: " . $e->getMessage() . " in statement: " . $statement);
                }
            }
        }
        
        return true; // Database was initialized
    }
    
    return false; // Database already existed
}

/**
 * Execute a query and return the result
 */
function db_query($sql, $params = []) {
    $db = get_db_connection();
    
    try {
        $stmt = $db->prepare($sql);
        
        // Bind parameters if provided
        if (!empty($params)) {
            foreach ($params as $param => $value) {
                $type = is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT;
                $stmt->bindValue($param, $value, $type);
            }
        }
        
        $result = $stmt->execute();
        return $result;
    } catch (\Exception $e) {
        error_log("Database query error: " . $e->getMessage() . " in query: " . $sql);
        return false;
    }
}

/**
 * Execute an insert query and return the last insert ID
 */
function db_insert($sql, $params = []) {
    $db = get_db_connection();
    
    try {
        $stmt = $db->prepare($sql);
        
        // Bind parameters if provided
        if (!empty($params)) {
            foreach ($params as $param => $value) {
                $type = is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT;
                $stmt->bindValue($param, $value, $type);
            }
        }
        
        $result = $stmt->execute();
        
        if ($result) {
            return $db->lastInsertRowID();
        }
        
        return false;
    } catch (\Exception $e) {
        error_log("Database insert error: " . $e->getMessage() . " in query: " . $sql);
        return false;
    }
}

/**
 * Execute an update or delete query and return the number of affected rows
 */
function db_execute($sql, $params = []) {
    $db = get_db_connection();
    
    try {
        $stmt = $db->prepare($sql);
        
        // Bind parameters if provided
        if (!empty($params)) {
            foreach ($params as $param => $value) {
                $type = is_int($value) ? SQLITE3_INTEGER : SQLITE3_TEXT;
                $stmt->bindValue($param, $value, $type);
            }
        }
        
        $result = $stmt->execute();
        
        if ($result) {
            // For SQLite, we need to use changes() to get the number of affected rows
            return $db->changes();
        }
        
        return false;
    } catch (\Exception $e) {
        error_log("Database execute error: " . $e->getMessage() . " in query: " . $sql);
        return false;
    }
}

/**
 * Fetch a single row as an associative array
 */
function db_fetch_assoc($result) {
    if ($result) {
        return $result->fetchArray(SQLITE3_ASSOC);
    }
    return false;
}

/**
 * Fetch a single row from a result set as an associative array
 */
function db_fetch($result) {
    if ($result) {
        return $result->fetchArray(SQLITE3_ASSOC);
    }
    return false;
}

/**
 * Fetch all rows as an array of associative arrays
 */
function db_fetch_all($result) {
    $rows = [];
    
    if ($result) {
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
    }
    
    return $rows;
}

/**
 * Get all records from a table
 */
function db_get_all($table, $order_by = 'id') {
    $result = db_query("SELECT * FROM $table ORDER BY $order_by");
    return db_fetch_all($result);
}
