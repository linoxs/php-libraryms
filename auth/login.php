<?php
/**
 * Login page
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../layouts/Layout.php';
require_once __DIR__ . '/../components/Alert.php';

// Initialize session
initialize_session();

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: /index.php');
    exit;
}

$errors = [];

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($username)) {
        $errors['username'] = 'Username is required';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }
    
    // If no validation errors, attempt to login
    if (empty($errors)) {
        // Get user by username
        $user = get_user_by_username($username);
        
        if ($user && verify_password($password, $user['password'])) {
            // Set user session
            set_user_session($user);
            
            // Redirect based on role
            if ($user['role'] === 'admin') {
                header('Location: /admin/dashboard.php');
            } else {
                header('Location: /member/dashboard.php');
            }
            exit;
        } else {
            $errors['login'] = 'Invalid username or password';
        }
    }
}

// Page content
Layout::header('Login');
Layout::bodyStart();
?>

<div class="auth-container">
    <?php Layout::pageTitle('Login'); ?>
    
    <?php if (isset($errors['login'])): ?>
        <?php Alert::error($errors['login']); ?>
    <?php endif; ?>
    
    <div class="auth-form">
        <?php
        Layout::formStart('/auth/login.php', 'post');
        
        // Username field
        $username_error = isset($errors['username']) ? $errors['username'] : null;
        $username_label = Layout::label('username', 'Username');
        $username_input = Layout::textInput('username', $username ?? '', 'username', 'Enter your username', true);
        Layout::formGroup($username_label, $username_input, $username_error);
        
        // Password field
        $password_error = isset($errors['password']) ? $errors['password'] : null;
        $password_label = Layout::label('password', 'Password');
        $password_input = Layout::passwordInput('password', 'password', 'Enter your password', true);
        Layout::formGroup($password_label, $password_input, $password_error);
        
        // Submit button
        echo Layout::submitButton('Login');
        
        Layout::formEnd();
        ?>
        
        <div class="auth-links">
            <p>Don't have an account? <a href="/auth/register.php">Register</a></p>
            <p><a href="/auth/forgot_password.php">Forgot Password?</a></p>
        </div>
    </div>
</div>

<style>
    .auth-container {
        max-width: 500px;
        margin: 0 auto;
    }
    
    .auth-form {
        background-color: #fff;
        padding: 2rem;
        border-radius: 0.25rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .auth-links {
        margin-top: 1.5rem;
        text-align: center;
    }
    
    .auth-links p {
        margin-bottom: 0.5rem;
    }
</style>

<?php
Layout::bodyEnd();
Layout::footer();
?>
