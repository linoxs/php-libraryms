<?php
/**
 * Reset password page
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
$success = false;

// Get email and token from URL
$email = sanitize_input($_GET['email'] ?? '');
$token = sanitize_input($_GET['token'] ?? '');

// Validate token
$valid_token = false;
if (!empty($email) && !empty($token)) {
    $valid_token = verify_password_reset_token($email, $token);
}

// If token is invalid, show error
if (!$valid_token) {
    $errors['token'] = 'Invalid or expired password reset link';
}

// Process reset password form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    // Sanitize input
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate input
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    // If no validation errors, reset password
    if (empty($errors)) {
        // Get user by email
        $user = get_user_by_email($email);
        
        if ($user) {
            // Update password
            $result = update_user_password($user['id'], $password);
            
            if ($result) {
                // Delete token
                delete_password_reset_token($email);
                
                // Set success message
                set_flash_message('success', 'Password has been reset successfully. You can now log in with your new password.');
                
                // Redirect to login page
                header('Location: /auth/login.php');
                exit;
            } else {
                $errors['reset'] = 'Failed to reset password. Please try again.';
            }
        } else {
            $errors['reset'] = 'User not found';
        }
    }
}

// Page content
Layout::header('Reset Password');
Layout::bodyStart();
?>

<div class="auth-container">
    <Layout::pageTitle('Reset Password');
    
    <?php if (isset($errors['token'])): ?>
        <?php Alert::error($errors['token']); ?>
        <div class="auth-links">
            <p><a href="/auth/forgot_password.php">Request a new password reset link</a></p>
            <p><a href="/auth/login.php">Back to Login</a></p>
        </div>
    <?php elseif (isset($errors['reset'])): ?>
        <?php Alert::error($errors['reset']); ?>
    <?php else: ?>
        <div class="auth-form">
            <p>Enter your new password below.</p>
            
            <?php
            Layout::formStart('/auth/reset_password.php?email=' . urlencode($email) . '&token=' . urlencode($token), 'post');
            
            // Password field
            $password_error = isset($errors['password']) ? $errors['password'] : null;
            $password_label = Layout::label('password', 'New Password');
            $password_input = Layout::passwordInput('password', 'password', 'Enter your new password', true);
            Layout::formGroup($password_label, $password_input, $password_error);
            
            // Confirm password field
            $confirm_password_error = isset($errors['confirm_password']) ? $errors['confirm_password'] : null;
            $confirm_password_label = Layout::label('confirm_password', 'Confirm New Password');
            $confirm_password_input = Layout::passwordInput('confirm_password', 'confirm_password', 'Confirm your new password', true);
            Layout::formGroup($confirm_password_label, $confirm_password_input, $confirm_password_error);
            
            // Submit button
            echo Layout::submitButton('Reset Password');
            
            Layout::formEnd();
            ?>
            
            <div class="auth-links">
                <p><a href="/auth/login.php">Back to Login</a></p>
            </div>
        </div>
    <?php endif; ?>
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
