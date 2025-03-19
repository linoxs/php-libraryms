<?php
/**
 * Admin Publishers Management
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
$publisher_id = sanitize_input($_GET['id'] ?? '');

// Process publisher actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_publisher'])) {
        // Sanitize input
        $name = sanitize_input($_POST['name'] ?? '');
        $address = sanitize_input($_POST['address'] ?? '');
        $contact_info = sanitize_input($_POST['contact_info'] ?? '');
        
        // Validate input
        if (empty($name)) {
            $errors['name'] = 'Name is required';
        }
        
        // If no validation errors, add publisher
        if (empty($errors)) {
            $publisher_id = create_publisher($name, $address, $contact_info);
            
            if ($publisher_id) {
                set_flash_message('success', 'Publisher added successfully');
                header('Location: /admin/publishers.php');
                exit;
            } else {
                $errors['add'] = 'Failed to add publisher';
            }
        }
    } elseif (isset($_POST['edit_publisher'])) {
        // Sanitize input
        $publisher_id = intval($_POST['publisher_id'] ?? 0);
        $name = sanitize_input($_POST['name'] ?? '');
        $address = sanitize_input($_POST['address'] ?? '');
        $contact_info = sanitize_input($_POST['contact_info'] ?? '');
        
        // Validate input
        if (empty($name)) {
            $errors['name'] = 'Name is required';
        }
        
        // If no validation errors, update publisher
        if (empty($errors)) {
            $result = update_publisher($publisher_id, $name, $address, $contact_info);
            
            if ($result) {
                set_flash_message('success', 'Publisher updated successfully');
                header('Location: /admin/publishers.php');
                exit;
            } else {
                $errors['edit'] = 'Failed to update publisher';
            }
        }
    } elseif (isset($_POST['delete_publisher'])) {
        // Delete publisher
        $publisher_id = intval($_POST['publisher_id'] ?? 0);
        
        if ($publisher_id > 0) {
            $result = delete_publisher($publisher_id);
            
            if ($result) {
                set_flash_message('success', 'Publisher deleted successfully');
                header('Location: /admin/publishers.php');
                exit;
            } else {
                set_flash_message('error', 'Failed to delete publisher. Make sure no books are associated with this publisher.');
                header('Location: /admin/publishers.php');
                exit;
            }
        } else {
            set_flash_message('error', 'Invalid publisher ID');
            header('Location: /admin/publishers.php');
            exit;
        }
    }
}

// Get publisher data for edit
$edit_publisher = null;
if ($action === 'edit' && $publisher_id) {
    $edit_publisher = get_publisher_by_id($publisher_id);
    
    if (!$edit_publisher) {
        set_flash_message('error', 'Publisher not found');
        header('Location: /admin/publishers.php');
        exit;
    }
}

// Get all publishers
$publishers = get_all_publishers();

// Page content
Layout::header('Manage Publishers');
Layout::bodyStart();
?>

<div class="admin-publishers">
    <Layout::pageTitle('Manage Publishers');
    
    <div class="action-buttons">
        <button id="add-publisher-btn" class="btn btn-primary">Add New Publisher</button>
    </div>
    
    <!-- Publishers Table -->
    <div class="publishers-table">
        <?php if (empty($publishers)): ?>
            <p>No publishers found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Address</th>
                            <th>Contact Info</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($publishers as $publisher): ?>
                            <tr>
                                <td><?php echo $publisher['id']; ?></td>
                                <td><?php echo htmlspecialchars($publisher['name']); ?></td>
                                <td><?php echo htmlspecialchars($publisher['address']); ?></td>
                                <td><?php echo htmlspecialchars($publisher['contact_info']); ?></td>
                                <td>
                                    <div class="action-links">
                                        <a href="/admin/publishers.php?action=edit&id=<?php echo $publisher['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                        <button class="btn btn-sm btn-danger delete-publisher-btn" data-id="<?php echo $publisher['id']; ?>" data-name="<?php echo htmlspecialchars($publisher['name']); ?>">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Add Publisher Modal -->
    <div id="add-publisher-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Publisher</h3>
                <button type="button" class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <?php
                Layout::formStart('/admin/publishers.php', 'post', 'add-publisher-form');
                
                // Name field
                $name_error = isset($errors['name']) ? $errors['name'] : null;
                $name_label = Layout::label('name', 'Name');
                $name_input = Layout::textInput('name', $_POST['name'] ?? '', 'name', 'Enter publisher name', true);
                Layout::formGroup($name_label, $name_input, $name_error);
                
                // Address field
                $address_error = isset($errors['address']) ? $errors['address'] : null;
                $address_label = Layout::label('address', 'Address');
                $address_input = Layout::textarea('address', $_POST['address'] ?? '', 'address', 3);
                Layout::formGroup($address_label, $address_input, $address_error);
                
                // Contact Info field
                $contact_info_error = isset($errors['contact_info']) ? $errors['contact_info'] : null;
                $contact_info_label = Layout::label('contact_info', 'Contact Info');
                $contact_info_input = Layout::textInput('contact_info', $_POST['contact_info'] ?? '', 'contact_info', 'Enter contact information');
                Layout::formGroup($contact_info_label, $contact_info_input, $contact_info_error);
                
                // Submit button
                echo Layout::submitButton('Add Publisher', 'add_publisher');
                
                Layout::formEnd();
                ?>
            </div>
        </div>
    </div>
    
    <!-- Edit Publisher Modal -->
    <?php if ($edit_publisher): ?>
    <div id="edit-publisher-modal" class="modal show">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Publisher</h3>
                <button type="button" class="close-modal" onclick="window.location='/admin/publishers.php'">&times;</button>
            </div>
            <div class="modal-body">
                <?php
                Layout::formStart('/admin/publishers.php', 'post', 'edit-publisher-form');
                
                // Hidden publisher ID field
                echo '<input type="hidden" name="publisher_id" value="' . $edit_publisher['id'] . '">';
                
                // Name field
                $name_error = isset($errors['name']) ? $errors['name'] : null;
                $name_label = Layout::label('name', 'Name');
                $name_input = Layout::textInput('name', $_POST['name'] ?? $edit_publisher['name'], 'name', 'Enter publisher name', true);
                Layout::formGroup($name_label, $name_input, $name_error);
                
                // Address field
                $address_error = isset($errors['address']) ? $errors['address'] : null;
                $address_label = Layout::label('address', 'Address');
                $address_input = Layout::textarea('address', $_POST['address'] ?? $edit_publisher['address'], 'address', 3);
                Layout::formGroup($address_label, $address_input, $address_error);
                
                // Contact Info field
                $contact_info_error = isset($errors['contact_info']) ? $errors['contact_info'] : null;
                $contact_info_label = Layout::label('contact_info', 'Contact Info');
                $contact_info_input = Layout::textInput('contact_info', $_POST['contact_info'] ?? $edit_publisher['contact_info'], 'contact_info', 'Enter contact information');
                Layout::formGroup($contact_info_label, $contact_info_input, $contact_info_error);
                
                // Submit button
                echo Layout::submitButton('Update Publisher', 'edit_publisher');
                
                Layout::formEnd();
                ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Delete Publisher Modal -->
    <div id="delete-publisher-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Delete Publisher</h3>
                <button type="button" class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the publisher "<span id="delete-publisher-name"></span>"?</p>
                <p>This action cannot be undone. Any books associated with this publisher will have their publisher set to none.</p>
                
                <?php
                Layout::formStart('/admin/publishers.php', 'post', 'delete-publisher-form');
                
                // Hidden publisher ID field
                echo '<input type="hidden" name="publisher_id" id="delete-publisher-id" value="">';
                
                // Submit button
                echo Layout::submitButton('Delete Publisher', 'delete_publisher', ['class' => 'btn-danger']);
                
                Layout::formEnd();
                ?>
            </div>
        </div>
    </div>
</div>

<style>
    .admin-publishers {
        margin-bottom: 2rem;
    }
    
    .action-buttons {
        margin-bottom: 1.5rem;
    }
    
    .publishers-table {
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
        // Add Publisher Modal
        const addPublisherBtn = document.getElementById('add-publisher-btn');
        const addPublisherModal = document.getElementById('add-publisher-modal');
        
        if (addPublisherBtn && addPublisherModal) {
            addPublisherBtn.addEventListener('click', function() {
                addPublisherModal.classList.add('show');
            });
            
            const closeButtons = addPublisherModal.querySelectorAll('.close-modal');
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    addPublisherModal.classList.remove('show');
                });
            });
        }
        
        // Delete Publisher Modal
        const deleteButtons = document.querySelectorAll('.delete-publisher-btn');
        const deletePublisherModal = document.getElementById('delete-publisher-modal');
        const deletePublisherId = document.getElementById('delete-publisher-id');
        const deletePublisherName = document.getElementById('delete-publisher-name');
        
        if (deleteButtons.length > 0 && deletePublisherModal && deletePublisherId && deletePublisherName) {
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    
                    deletePublisherId.value = id;
                    deletePublisherName.textContent = name;
                    
                    deletePublisherModal.classList.add('show');
                });
            });
            
            const closeButtons = deletePublisherModal.querySelectorAll('.close-modal');
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    deletePublisherModal.classList.remove('show');
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
