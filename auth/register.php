<?php
/**
 * Registration page
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

// Process registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $username = sanitize_input($_POST['username'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $full_name = sanitize_input($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate input
    if (empty($username)) {
        $errors['username'] = 'Username is required';
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $errors['username'] = 'Username must be between 3 and 20 characters';
    } elseif (get_user_by_username($username)) {
        $errors['username'] = 'Username already exists';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!is_valid_email($email)) {
        $errors['email'] = 'Invalid email format';
    } elseif (get_user_by_email($email)) {
        $errors['email'] = 'Email already exists';
    }
    
    if (empty($full_name)) {
        $errors['full_name'] = 'Full name is required';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    // If no validation errors, create user
    if (empty($errors)) {
        $user_id = create_user($username, $password, $email, $full_name);
        
        if ($user_id) {
            // Set success message
            set_flash_message('success', 'Registration successful! You can now log in.');
            
            // Redirect to login page
            header('Location: /auth/login.php');
            exit;
        } else {
            $errors['register'] = 'Registration failed. Please try again.';
        }
    }
}

// Page content
Layout::header('Register');
Layout::bodyStart();
?>

<div class="auth-container">
    <Layout::pageTitle('Register');
    
    <?php if (isset($errors['register'])): ?>
        <?php Alert::error($errors['register']); ?>
    <?php endif; ?>
    
    <div class="auth-form">
        <?php
        Layout::formStart('/auth/register.php', 'post');
        
        // Username field
        $username_error = isset($errors['username']) ? $errors['username'] : null;
        $username_label = Layout::label('username', 'Username');
        $username_input = Layout::textInput('username', $username ?? '', 'username', 'Choose a username', true);
        Layout::formGroup($username_label, $username_input, $username_error);
        
        // Email field
        $email_error = isset($errors['email']) ? $errors['email'] : null;
        $email_label = Layout::label('email', 'Email');
        $email_input = Layout::emailInput('email', $email ?? '', 'email', 'Enter your email', true);
        Layout::formGroup($email_label, $email_input, $email_error);
        
        // Full name field
        $full_name_error = isset($errors['full_name']) ? $errors['full_name'] : null;
        $full_name_label = Layout::label('full_name', 'Full Name');
        $full_name_input = Layout::textInput('full_name', $full_name ?? '', 'full_name', 'Enter your full name', true);
        Layout::formGroup($full_name_label, $full_name_input, $full_name_error);
        
        // Password field
        $password_error = isset($errors['password']) ? $errors['password'] : null;
        $password_label = Layout::label('password', 'Password');
        $password_input = Layout::passwordInput('password', 'password', 'Choose a password', true);
        Layout::formGroup($password_label, $password_input, $password_error);
        
        // Confirm password field
        $confirm_password_error = isset($errors['confirm_password']) ? $errors['confirm_password'] : null;
        $confirm_password_label = Layout::label('confirm_password', 'Confirm Password');
        $confirm_password_input = Layout::passwordInput('confirm_password', 'confirm_password', 'Confirm your password', true);
        Layout::formGroup($confirm_password_label, $confirm_password_input, $confirm_password_error);
        
        // Submit button
        echo Layout::submitButton('Register');
        
        Layout::formEnd();
        ?>
        
        <div class="auth-links">
            <p>Already have an account? <a href="/auth/login.php">Login</a></p>
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
</style>

<?php
Layout::bodyEnd();
Layout::footer();
?>
