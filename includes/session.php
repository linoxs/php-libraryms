<?php
/**
 * Session handling functions
 */

// Start session if not already started
function session_start_safe() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Initialize session
function initialize_session() {
    session_start_safe();
    
    // Set session cookie parameters for better security
    $current_cookie_params = session_get_cookie_params();
    session_set_cookie_params(
        $current_cookie_params["lifetime"],
        $current_cookie_params["path"],
        $current_cookie_params["domain"],
        true,  // Secure flag - only send over HTTPS
        true   // HttpOnly flag - not accessible via JavaScript
    );
    
    // Regenerate session ID periodically to prevent session fixation
    if (!isset($_SESSION['last_regeneration'])) {
        regenerate_session_id();
    } else {
        // Regenerate session ID every 30 minutes
        $interval = 30 * 60;
        if (time() - $_SESSION['last_regeneration'] >= $interval) {
            regenerate_session_id();
        }
    }
}

// Regenerate session ID
function regenerate_session_id() {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Set a flash message to be displayed on the next page load
function set_flash_message($type, $message) {
    session_start_safe();
    $_SESSION['flash_messages'][$type] = $message;
}

// Get flash messages and clear them
function get_flash_messages() {
    session_start_safe();
    
    $messages = isset($_SESSION['flash_messages']) ? $_SESSION['flash_messages'] : [];
    
    // Clear flash messages
    $_SESSION['flash_messages'] = [];
    
    return $messages;
}

// Set user session after successful login
function set_user_session($user) {
    session_start_safe();
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['logged_in'] = true;
    
    // Set last activity time for session timeout
    $_SESSION['last_activity'] = time();
    
    // Regenerate session ID on login for security
    regenerate_session_id();
}

// Check if user is logged in
function is_logged_in() {
    session_start_safe();
    
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// Check if current user is an admin
function is_admin() {
    session_start_safe();
    
    return is_logged_in() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Get current user ID
function get_current_user_id() {
    session_start_safe();
    
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

// Get current user role
function get_current_user_role() {
    session_start_safe();
    
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

// Check for session timeout (inactive for too long)
function check_session_timeout() {
    session_start_safe();
    
    if (is_logged_in()) {
        // Set timeout period (30 minutes)
        $timeout_duration = 30 * 60;
        
        // Check if last activity was set
        if (isset($_SESSION['last_activity'])) {
            // Calculate time since last activity
            $time_since_last_activity = time() - $_SESSION['last_activity'];
            
            // If user has been inactive for too long, log them out
            if ($time_since_last_activity >= $timeout_duration) {
                logout_user();
                set_flash_message('info', 'You were logged out due to inactivity.');
                
                // Redirect to login page
                header('Location: /auth/login.php');
                exit;
            }
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = time();
    }
}

// Log out user
function logout_user() {
    session_start_safe();
    
    // Unset all session variables
    $_SESSION = [];
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
}

// Redirect if user is not logged in
function require_login() {
    if (!is_logged_in()) {
        set_flash_message('error', 'Please log in to access this page.');
        header('Location: /auth/login.php');
        exit;
    }
    
    // Check for session timeout
    check_session_timeout();
}

// Redirect if user is not an admin
function require_admin() {
    require_login();
    
    if (!is_admin()) {
        set_flash_message('error', 'You do not have permission to access this page.');
        header('Location: /index.php');
        exit;
    }
}
