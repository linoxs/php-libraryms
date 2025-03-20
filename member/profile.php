<?php
/**
 * Member Profile
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../layouts/Layout.php';
require_once __DIR__ . '/../components/Alert.php';

// Require member privileges
require_login();

// Initialize variables
$errors = [];
$success = false;
$user_id = get_current_user_id();
$user = get_user_by_id($user_id);

// Process profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Sanitize input
    $full_name = sanitize_input($_POST['full_name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    
    // Validate input
    if (empty($full_name)) {
        $errors['full_name'] = 'Full name is required';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!is_valid_email($email)) {
        $errors['email'] = 'Invalid email format';
    } elseif ($email !== $user['email'] && get_user_by_email($email)) {
        $errors['email'] = 'Email already exists';
    }
    
    // If no validation errors, update profile
    if (empty($errors)) {
        $result = update_user($user_id, [
            'full_name' => $full_name,
            'email' => $email
        ]);
        
        if ($result) {
            $success = true;
            $user = get_user_by_id($user_id); // Refresh user data
            set_flash_message('success', 'Profile updated successfully');
        } else {
            $errors['update'] = 'Failed to update profile';
        }
    }
}

// Process password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    // Sanitize input
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate input
    if (empty($current_password)) {
        $errors['current_password'] = 'Current password is required';
    } elseif (!verify_password($current_password, $user['password'])) {
        $errors['current_password'] = 'Current password is incorrect';
    }
    
    if (empty($new_password)) {
        $errors['new_password'] = 'New password is required';
    } elseif (strlen($new_password) < 6) {
        $errors['new_password'] = 'New password must be at least 6 characters';
    }
    
    if ($new_password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    // If no validation errors, update password
    if (empty($errors)) {
        $result = update_user_password($user_id, $new_password);
        
        if ($result) {
            $success = true;
            set_flash_message('success', 'Password changed successfully');
        } else {
            $errors['change_password'] = 'Failed to change password';
        }
    }
}

// Page content
Layout::header('My Profile');
Layout::bodyStart();
?>

<div class="member-profile">
    <Layout::pageTitle('My Profile');
    
    <div class="profile-container">
        <!-- Profile Information Section -->
        <div class="profile-section">
            <h3>Profile Information</h3>
            
            <?php
            Layout::formStart('/member/profile.php', 'post', 'profile-form');
            
            // Username field (read-only)
            $username_label = Layout::label('username', 'Username');
            $username_input = '<input type="text" id="username" name="username" value="' . htmlspecialchars($user['username']) . '" class="form-control" readonly>';
            Layout::formGroup($username_label, $username_input);
            
            // Full name field
            $full_name_error = isset($errors['full_name']) ? $errors['full_name'] : null;
            $full_name_label = Layout::label('full_name', 'Full Name');
            $full_name_input = Layout::textInput('full_name', $_POST['full_name'] ?? $user['full_name'], 'full_name', 'Enter your full name', true);
            Layout::formGroup($full_name_label, $full_name_input, $full_name_error);
            
            // Email field
            $email_error = isset($errors['email']) ? $errors['email'] : null;
            $email_label = Layout::label('email', 'Email');
            $email_input = Layout::emailInput('email', $_POST['email'] ?? $user['email'], 'email', 'Enter your email', true);
            Layout::formGroup($email_label, $email_input, $email_error);
            
            // Role field (read-only)
            $role_label = Layout::label('role', 'Role');
            $role_input = '<input type="text" id="role" name="role" value="' . ucfirst($user['role']) . '" class="form-control" readonly>';
            Layout::formGroup($role_label, $role_input);
            
            // Registration date (read-only)
            $registered_label = Layout::label('registered', 'Registered On');
            $registered_input = '<input type="text" id="registered" name="registered" value="' . date('M d, Y', strtotime($user['created_at'])) . '" class="form-control" readonly>';
            Layout::formGroup($registered_label, $registered_input);
            
            // Submit button
            echo Layout::submitButton('Update Profile', 'update_profile');
            
            Layout::formEnd();
            ?>
        </div>
        
        <!-- Change Password Section -->
        <div class="profile-section">
            <h3>Change Password</h3>
            
            <?php
            Layout::formStart('/member/profile.php', 'post', 'password-form');
            
            // Current password field
            $current_password_error = isset($errors['current_password']) ? $errors['current_password'] : null;
            $current_password_label = Layout::label('current_password', 'Current Password');
            $current_password_input = Layout::passwordInput('current_password', 'current_password', 'Enter your current password', true);
            Layout::formGroup($current_password_label, $current_password_input, $current_password_error);
            
            // New password field
            $new_password_error = isset($errors['new_password']) ? $errors['new_password'] : null;
            $new_password_label = Layout::label('new_password', 'New Password');
            $new_password_input = Layout::passwordInput('new_password', 'new_password', 'Enter your new password', true);
            Layout::formGroup($new_password_label, $new_password_input, $new_password_error);
            
            // Confirm password field
            $confirm_password_error = isset($errors['confirm_password']) ? $errors['confirm_password'] : null;
            $confirm_password_label = Layout::label('confirm_password', 'Confirm New Password');
            $confirm_password_input = Layout::passwordInput('confirm_password', 'confirm_password', 'Confirm your new password', true);
            Layout::formGroup($confirm_password_label, $confirm_password_input, $confirm_password_error);
            
            // Submit button
            echo Layout::submitButton('Change Password', 'change_password');
            
            Layout::formEnd();
            ?>
        </div>
        
        <!-- Account Activity Section -->
        <div class="profile-section">
            <h3>Account Activity</h3>
            
            <div class="activity-stats">
                <div class="activity-stat">
                    <span class="stat-label">Total Books Borrowed:</span>
                    <span class="stat-value"><?php echo count_user_transactions($user_id); ?></span>
                </div>
                
                <div class="activity-stat">
                    <span class="stat-label">Currently Borrowed:</span>
                    <span class="stat-value"><?php echo count_user_active_transactions($user_id); ?></span>
                </div>
                
                <div class="activity-stat">
                    <span class="stat-label">Overdue Books:</span>
                    <span class="stat-value"><?php echo count_user_overdue_books($user_id); ?></span>
                </div>
            </div>
            
            <div class="activity-links">
                <a href="/member/transactions.php" class="btn btn-outline-primary">View My Transactions</a>
                <a href="/member/books.php" class="btn btn-outline-primary">Browse Books</a>
            </div>
        </div>
    </div>
</div>

<style>
    .member-profile {
        margin-bottom: 2rem;
    }
    
    .profile-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }
    
    .profile-section {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        padding: 1.5rem;
    }
    
    .profile-section h3 {
        margin-top: 0;
        margin-bottom: 1.5rem;
        font-size: 1.25rem;
        border-bottom: 1px solid #eee;
        padding-bottom: 0.5rem;
    }
    
    .activity-stats {
        margin-bottom: 1.5rem;
    }
    
    .activity-stat {
        margin-bottom: 0.5rem;
        display: flex;
        justify-content: space-between;
    }
    
    .stat-label {
        font-weight: bold;
    }
    
    .activity-links {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
</style>

<?php
Layout::bodyEnd();
Layout::footer();
?>
