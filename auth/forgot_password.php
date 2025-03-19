<?php
/**
 * Forgot password page
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

// Process forgot password form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $email = sanitize_input($_POST['email'] ?? '');
    
    // Validate input
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!is_valid_email($email)) {
        $errors['email'] = 'Invalid email format';
    } else {
        // Check if email exists
        $user = get_user_by_email($email);
        
        if (!$user) {
            $errors['email'] = 'No account found with this email';
        }
    }
    
    // If no validation errors, create password reset token
    if (empty($errors)) {
        $token = create_password_reset_token($email);
        
        if ($token) {
            // In a real application, you would send an email with the reset link
            // For this demo, we'll just show the token on the page
            $reset_link = "/auth/reset_password.php?email=" . urlencode($email) . "&token=" . urlencode($token);
            $success = true;
        } else {
            $errors['reset'] = 'Failed to create password reset token. Please try again.';
        }
    }
}

// Page content
Layout::header('Forgot Password');
Layout::bodyStart();
?>

<div class="auth-container">
    <Layout::pageTitle('Forgot Password');
    
    <?php if (isset($errors['reset'])): ?>
        <?php Alert::error($errors['reset']); ?>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <?php Alert::success('Password reset link has been created.'); ?>
        
        <div class="reset-link-info">
            <p>In a real application, an email would be sent with a password reset link.</p>
            <p>For demonstration purposes, here is the reset link:</p>
            <a href="<?php echo $reset_link; ?>" class="reset-link"><?php echo $reset_link; ?></a>
        </div>
    <?php else: ?>
        <div class="auth-form">
            <p>Enter your email address and we'll send you a link to reset your password.</p>
            
            <?php
            Layout::formStart('/auth/forgot_password.php', 'post');
            
            // Email field
            $email_error = isset($errors['email']) ? $errors['email'] : null;
            $email_label = Layout::label('email', 'Email');
            $email_input = Layout::emailInput('email', $email ?? '', 'email', 'Enter your email', true);
            Layout::formGroup($email_label, $email_input, $email_error);
            
            // Submit button
            echo Layout::submitButton('Send Reset Link');
            
            Layout::formEnd();
            ?>
            
            <div class="auth-links">
                <p>Remember your password? <a href="/auth/login.php">Login</a></p>
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
    
    .reset-link-info {
        background-color: #fff;
        padding: 2rem;
        border-radius: 0.25rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        margin-top: 1.5rem;
    }
    
    .reset-link {
        display: block;
        word-break: break-all;
        background-color: #f8f9fa;
        padding: 0.5rem;
        border-radius: 0.25rem;
        margin-top: 0.5rem;
    }
</style>

<?php
Layout::bodyEnd();
Layout::footer();
?>
