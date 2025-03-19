<?php
/**
 * Admin Users Management
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../layouts/Layout.php';
require_once __DIR__ . '/../components/Alert.php';

// Require admin privileges
require_admin();

// Initialize variables
$errors = [];
$success = false;
$action = sanitize_input($_GET['action'] ?? '');
$user_id = sanitize_input($_GET['id'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;

// Process user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        // Sanitize input
        $username = sanitize_input($_POST['username'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $full_name = sanitize_input($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $role = sanitize_input($_POST['role'] ?? 'member');
        
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
        
        if (!in_array($role, ['admin', 'member'])) {
            $errors['role'] = 'Invalid role';
        }
        
        // If no validation errors, add user
        if (empty($errors)) {
            $user_id = create_user($username, $password, $email, $full_name, $role);
            
            if ($user_id) {
                set_flash_message('success', 'User added successfully');
                header('Location: /admin/users.php');
                exit;
            } else {
                $errors['add'] = 'Failed to add user';
            }
        }
    } elseif (isset($_POST['edit_user'])) {
        // Sanitize input
        $user_id = intval($_POST['user_id'] ?? 0);
        $username = sanitize_input($_POST['username'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $full_name = sanitize_input($_POST['full_name'] ?? '');
        $role = sanitize_input($_POST['role'] ?? 'member');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Get current user data
        $current_user = get_user_by_id($user_id);
        
        // Validate input
        if (empty($username)) {
            $errors['username'] = 'Username is required';
        } elseif (strlen($username) < 3 || strlen($username) > 20) {
            $errors['username'] = 'Username must be between 3 and 20 characters';
        } elseif ($username !== $current_user['username'] && get_user_by_username($username)) {
            $errors['username'] = 'Username already exists';
        }
        
        if (empty($email)) {
            $errors['email'] = 'Email is required';
        } elseif (!is_valid_email($email)) {
            $errors['email'] = 'Invalid email format';
        } elseif ($email !== $current_user['email'] && get_user_by_email($email)) {
            $errors['email'] = 'Email already exists';
        }
        
        if (empty($full_name)) {
            $errors['full_name'] = 'Full name is required';
        }
        
        if (!empty($password) && strlen($password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters';
        }
        
        if (!empty($password) && $password !== $confirm_password) {
            $errors['confirm_password'] = 'Passwords do not match';
        }
        
        if (!in_array($role, ['admin', 'member'])) {
            $errors['role'] = 'Invalid role';
        }
        
        // If no validation errors, update user
        if (empty($errors)) {
            // Update user data
            $result = update_user($user_id, [
                'username' => $username,
                'email' => $email,
                'full_name' => $full_name,
                'role' => $role
            ]);
            
            // Update password if provided
            if (!empty($password)) {
                $password_result = update_user_password($user_id, $password);
                $result = $result && $password_result;
            }
            
            if ($result) {
                set_flash_message('success', 'User updated successfully');
                header('Location: /admin/users.php');
                exit;
            } else {
                $errors['edit'] = 'Failed to update user';
            }
        }
    } elseif (isset($_POST['delete_user'])) {
        // Delete user
        $user_id = intval($_POST['user_id'] ?? 0);
        
        // Prevent self-deletion
        if ($user_id === get_current_user_id()) {
            set_flash_message('error', 'You cannot delete your own account');
            header('Location: /admin/users.php');
            exit;
        }
        
        if ($user_id > 0) {
            $result = delete_user($user_id);
            
            if ($result) {
                set_flash_message('success', 'User deleted successfully');
                header('Location: /admin/users.php');
                exit;
            } else {
                set_flash_message('error', 'Failed to delete user');
                header('Location: /admin/users.php');
                exit;
            }
        } else {
            set_flash_message('error', 'Invalid user ID');
            header('Location: /admin/users.php');
            exit;
        }
    }
}

// Get user data for edit
$edit_user = null;
if ($action === 'edit' && $user_id) {
    $edit_user = get_user_by_id($user_id);
    
    if (!$edit_user) {
        set_flash_message('error', 'User not found');
        header('Location: /admin/users.php');
        exit;
    }
}

// Get users with pagination
$total_users = count_users();
$total_pages = ceil($total_users / $per_page);
$users = get_all_users($page, $per_page);

// Page content
Layout::header('Manage Users');
Layout::bodyStart();
?>

<div class="admin-users">
    <Layout::pageTitle('Manage Users');
    
    <div class="action-buttons">
        <button id="add-user-btn" class="btn btn-primary">Add New User</button>
    </div>
    
    <!-- Users Table -->
    <div class="users-table">
        <?php if (empty($users)): ?>
            <p>No users found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo ucfirst($user['role']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="action-links">
                                        <a href="/admin/users.php?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                        <?php if ($user['id'] !== get_current_user_id()): ?>
                                            <button class="btn btn-sm btn-danger delete-user-btn" data-id="<?php echo $user['id']; ?>" data-username="<?php echo htmlspecialchars($user['username']); ?>">Delete</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <?php echo generate_pagination($page, $total_pages, '/admin/users.php?page=%d'); ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Add User Modal -->
    <div id="add-user-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New User</h3>
                <button type="button" class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <?php
                Layout::formStart('/admin/users.php', 'post', 'add-user-form');
                
                // Username field
                $username_error = isset($errors['username']) ? $errors['username'] : null;
                $username_label = Layout::label('username', 'Username');
                $username_input = Layout::textInput('username', $_POST['username'] ?? '', 'username', 'Enter username', true);
                Layout::formGroup($username_label, $username_input, $username_error);
                
                // Email field
                $email_error = isset($errors['email']) ? $errors['email'] : null;
                $email_label = Layout::label('email', 'Email');
                $email_input = Layout::emailInput('email', $_POST['email'] ?? '', 'email', 'Enter email', true);
                Layout::formGroup($email_label, $email_input, $email_error);
                
                // Full name field
                $full_name_error = isset($errors['full_name']) ? $errors['full_name'] : null;
                $full_name_label = Layout::label('full_name', 'Full Name');
                $full_name_input = Layout::textInput('full_name', $_POST['full_name'] ?? '', 'full_name', 'Enter full name', true);
                Layout::formGroup($full_name_label, $full_name_input, $full_name_error);
                
                // Password field
                $password_error = isset($errors['password']) ? $errors['password'] : null;
                $password_label = Layout::label('password', 'Password');
                $password_input = Layout::passwordInput('password', 'password', 'Enter password', true);
                Layout::formGroup($password_label, $password_input, $password_error);
                
                // Confirm password field
                $confirm_password_error = isset($errors['confirm_password']) ? $errors['confirm_password'] : null;
                $confirm_password_label = Layout::label('confirm_password', 'Confirm Password');
                $confirm_password_input = Layout::passwordInput('confirm_password', 'confirm_password', 'Confirm password', true);
                Layout::formGroup($confirm_password_label, $confirm_password_input, $confirm_password_error);
                
                // Role field
                $role_error = isset($errors['role']) ? $errors['role'] : null;
                $role_label = Layout::label('role', 'Role');
                $role_input = Layout::select('role', ['member' => 'Member', 'admin' => 'Admin'], $_POST['role'] ?? 'member', 'role', true);
                Layout::formGroup($role_label, $role_input, $role_error);
                
                // Submit button
                echo Layout::submitButton('Add User', 'add_user');
                
                Layout::formEnd();
                ?>
            </div>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <?php if ($edit_user): ?>
    <div id="edit-user-modal" class="modal show">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit User</h3>
                <button type="button" class="close-modal" onclick="window.location='/admin/users.php'">&times;</button>
            </div>
            <div class="modal-body">
                <?php
                Layout::formStart('/admin/users.php', 'post', 'edit-user-form');
                
                // Hidden user ID field
                echo '<input type="hidden" name="user_id" value="' . $edit_user['id'] . '">';
                
                // Username field
                $username_error = isset($errors['username']) ? $errors['username'] : null;
                $username_label = Layout::label('username', 'Username');
                $username_input = Layout::textInput('username', $_POST['username'] ?? $edit_user['username'], 'username', 'Enter username', true);
                Layout::formGroup($username_label, $username_input, $username_error);
                
                // Email field
                $email_error = isset($errors['email']) ? $errors['email'] : null;
                $email_label = Layout::label('email', 'Email');
                $email_input = Layout::emailInput('email', $_POST['email'] ?? $edit_user['email'], 'email', 'Enter email', true);
                Layout::formGroup($email_label, $email_input, $email_error);
                
                // Full name field
                $full_name_error = isset($errors['full_name']) ? $errors['full_name'] : null;
                $full_name_label = Layout::label('full_name', 'Full Name');
                $full_name_input = Layout::textInput('full_name', $_POST['full_name'] ?? $edit_user['full_name'], 'full_name', 'Enter full name', true);
                Layout::formGroup($full_name_label, $full_name_input, $full_name_error);
                
                // Password field (optional for edit)
                $password_error = isset($errors['password']) ? $errors['password'] : null;
                $password_label = Layout::label('password', 'Password (leave blank to keep current)');
                $password_input = Layout::passwordInput('password', 'password', 'Enter new password');
                Layout::formGroup($password_label, $password_input, $password_error);
                
                // Confirm password field
                $confirm_password_error = isset($errors['confirm_password']) ? $errors['confirm_password'] : null;
                $confirm_password_label = Layout::label('confirm_password', 'Confirm Password');
                $confirm_password_input = Layout::passwordInput('confirm_password', 'confirm_password', 'Confirm new password');
                Layout::formGroup($confirm_password_label, $confirm_password_input, $confirm_password_error);
                
                // Role field
                $role_error = isset($errors['role']) ? $errors['role'] : null;
                $role_label = Layout::label('role', 'Role');
                $role_input = Layout::select('role', ['member' => 'Member', 'admin' => 'Admin'], $_POST['role'] ?? $edit_user['role'], 'role', true);
                Layout::formGroup($role_label, $role_input, $role_error);
                
                // Submit button
                echo Layout::submitButton('Update User', 'edit_user');
                
                Layout::formEnd();
                ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Delete User Modal -->
    <div id="delete-user-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Delete User</h3>
                <button type="button" class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the user "<span id="delete-user-username"></span>"?</p>
                <p>This action cannot be undone.</p>
                
                <?php
                Layout::formStart('/admin/users.php', 'post', 'delete-user-form');
                
                // Hidden user ID field
                echo '<input type="hidden" name="user_id" id="delete-user-id" value="">';
                
                // Submit button
                echo Layout::submitButton('Delete User', 'delete_user', ['class' => 'btn-danger']);
                
                Layout::formEnd();
                ?>
            </div>
        </div>
    </div>
</div>

<style>
    .admin-users {
        margin-bottom: 2rem;
    }
    
    .action-buttons {
        margin-bottom: 1.5rem;
    }
    
    .users-table {
        margin-bottom: 1.5rem;
    }
    
    .action-links {
        display: flex;
        gap: 0.5rem;
    }
</style>

<script>
    // Modal functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Add User Modal
        const addUserBtn = document.getElementById('add-user-btn');
        const addUserModal = document.getElementById('add-user-modal');
        
        if (addUserBtn && addUserModal) {
            addUserBtn.addEventListener('click', function() {
                addUserModal.classList.add('show');
            });
            
            const closeButtons = addUserModal.querySelectorAll('.close-modal');
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    addUserModal.classList.remove('show');
                });
            });
        }
        
        // Delete User Modal
        const deleteButtons = document.querySelectorAll('.delete-user-btn');
        const deleteUserModal = document.getElementById('delete-user-modal');
        const deleteUserId = document.getElementById('delete-user-id');
        const deleteUserUsername = document.getElementById('delete-user-username');
        
        if (deleteButtons.length > 0 && deleteUserModal && deleteUserId && deleteUserUsername) {
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const username = this.getAttribute('data-username');
                    
                    deleteUserId.value = id;
                    deleteUserUsername.textContent = username;
                    
                    deleteUserModal.classList.add('show');
                });
            });
            
            const closeButtons = deleteUserModal.querySelectorAll('.close-modal');
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    deleteUserModal.classList.remove('show');
                });
            });
        }
        
        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        });
    });
</script>

<?php
Layout::bodyEnd();
Layout::footer();
?>
