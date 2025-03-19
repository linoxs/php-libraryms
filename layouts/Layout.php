<?php
/**
 * Layout class with static methods for rendering page layout components
 */

class Layout {
    /**
     * Render the HTML header with title and common meta tags
     */
    public static function header($title = 'Library Management System') {
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - Library Management System</title>
    <link rel="stylesheet" href="/assets/styles.css">
    <script src="https://unpkg.com/htmx.org@1.9.2"></script>
</head>
<?php
    }

    /**
     * Start the body section and render the navigation
     */
    public static function bodyStart() {
        require_once __DIR__ . '/../includes/session.php';
        session_start_safe();
        $role = get_current_user_role();
        $is_logged_in = is_logged_in();
        ?>
<body>
    <header class="main-header">
        <div class="container">
            <h1 class="site-title">Library Management System</h1>
            <nav class="main-nav">
                <ul>
                    <li><a href="/index.php">Home</a></li>
                    <?php if ($is_logged_in): ?>
                        <?php if ($role === 'admin'): ?>
                            <li><a href="/admin/dashboard.php">Admin Dashboard</a></li>
                            <li><a href="/admin/books.php">Manage Books</a></li>
                            <li><a href="/admin/publishers.php">Manage Publishers</a></li>
                            <li><a href="/admin/users.php">Manage Users</a></li>
                        <?php else: ?>
                            <li><a href="/member/dashboard.php">My Dashboard</a></li>
                            <li><a href="/member/books.php">Browse Books</a></li>
                            <li><a href="/member/history.php">Borrow History</a></li>
                        <?php endif; ?>
                        <li><a href="/auth/logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="/auth/login.php">Login</a></li>
                        <li><a href="/auth/register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    
    <main class="main-content">
        <div class="container">
            <?php self::flashMessages(); ?>
<?php
    }

    /**
     * End the body section
     */
    public static function bodyEnd() {
        ?>
        </div>
    </main>
<?php
    }

    /**
     * Render the footer
     */
    public static function footer() {
        ?>
    <footer class="main-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Library Management System</p>
        </div>
    </footer>
</body>
</html>
<?php
    }

    /**
     * Display flash messages
     */
    public static function flashMessages() {
        require_once __DIR__ . '/../includes/session.php';
        $messages = get_flash_messages();
        
        if (!empty($messages)) {
            foreach ($messages as $type => $message) {
                echo '<div class="alert alert-' . $type . '">' . $message . '</div>';
            }
        }
    }

    /**
     * Render a page title
     */
    public static function pageTitle($title) {
        ?>
<h2 class="page-title"><?php echo $title; ?></h2>
<?php
    }

    /**
     * Render a section title
     */
    public static function sectionTitle($title) {
        ?>
<h3 class="section-title"><?php echo $title; ?></h3>
<?php
    }

    /**
     * Render a card
     */
    public static function card($title, $content, $footer = null) {
        ?>
<div class="card">
    <?php if ($title): ?>
    <div class="card-header">
        <h3><?php echo $title; ?></h3>
    </div>
    <?php endif; ?>
    
    <div class="card-body">
        <?php echo $content; ?>
    </div>
    
    <?php if ($footer): ?>
    <div class="card-footer">
        <?php echo $footer; ?>
    </div>
    <?php endif; ?>
</div>
<?php
    }

    /**
     * Render a data table
     */
    public static function table($headers, $rows, $id = null) {
        $table_id = $id ? ' id="' . $id . '"' : '';
        ?>
<div class="table-responsive">
    <table class="data-table"<?php echo $table_id; ?>>
        <thead>
            <tr>
                <?php foreach ($headers as $header): ?>
                <th><?php echo $header; ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
            <tr>
                <td colspan="<?php echo count($headers); ?>" class="text-center">No data available</td>
            </tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                <tr>
                    <?php foreach ($row as $cell): ?>
                    <td><?php echo $cell; ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php
    }

    /**
     * Render a form
     */
    public static function formStart($action, $method = 'post', $id = null, $enctype = null) {
        $form_id = $id ? ' id="' . $id . '"' : '';
        $form_enctype = $enctype ? ' enctype="' . $enctype . '"' : '';
        ?>
<form action="<?php echo $action; ?>" method="<?php echo $method; ?>"<?php echo $form_id; ?><?php echo $form_enctype; ?>>
<?php
    }

    /**
     * End a form
     */
    public static function formEnd() {
        ?>
</form>
<?php
    }

    /**
     * Render a form group with label and input
     */
    public static function formGroup($label, $input, $error = null) {
        ?>
<div class="form-group">
    <?php echo $label; ?>
    <?php echo $input; ?>
    <?php if ($error): ?>
    <div class="form-error"><?php echo $error; ?></div>
    <?php endif; ?>
</div>
<?php
    }

    /**
     * Create a label element
     */
    public static function label($for, $text) {
        return '<label for="' . $for . '">' . $text . '</label>';
    }

    /**
     * Create a text input element
     */
    public static function textInput($name, $value = '', $id = null, $placeholder = '', $required = false, $attributes = []) {
        $input_id = $id ?: $name;
        $required_attr = $required ? ' required' : '';
        $placeholder_attr = $placeholder ? ' placeholder="' . $placeholder . '"' : '';
        $extra_attrs = '';
        
        foreach ($attributes as $attr => $attr_value) {
            $extra_attrs .= ' ' . $attr . '="' . $attr_value . '"';
        }
        
        return '<input type="text" id="' . $input_id . '" name="' . $name . '" value="' . htmlspecialchars($value) . '"' . $placeholder_attr . $required_attr . $extra_attrs . '>';
    }

    /**
     * Create a password input element
     */
    public static function passwordInput($name, $id = null, $placeholder = '', $required = false, $attributes = []) {
        $input_id = $id ?: $name;
        $required_attr = $required ? ' required' : '';
        $placeholder_attr = $placeholder ? ' placeholder="' . $placeholder . '"' : '';
        $extra_attrs = '';
        
        foreach ($attributes as $attr => $attr_value) {
            $extra_attrs .= ' ' . $attr . '="' . $attr_value . '"';
        }
        
        return '<input type="password" id="' . $input_id . '" name="' . $name . '"' . $placeholder_attr . $required_attr . $extra_attrs . '>';
    }

    /**
     * Create an email input element
     */
    public static function emailInput($name, $value = '', $id = null, $placeholder = '', $required = false, $attributes = []) {
        $input_id = $id ?: $name;
        $required_attr = $required ? ' required' : '';
        $placeholder_attr = $placeholder ? ' placeholder="' . $placeholder . '"' : '';
        $extra_attrs = '';
        
        foreach ($attributes as $attr => $attr_value) {
            $extra_attrs .= ' ' . $attr . '="' . $attr_value . '"';
        }
        
        return '<input type="email" id="' . $input_id . '" name="' . $name . '" value="' . htmlspecialchars($value) . '"' . $placeholder_attr . $required_attr . $extra_attrs . '>';
    }

    /**
     * Create a number input element
     */
    public static function numberInput($name, $value = '', $id = null, $min = null, $max = null, $required = false, $attributes = []) {
        $input_id = $id ?: $name;
        $required_attr = $required ? ' required' : '';
        $min_attr = $min !== null ? ' min="' . $min . '"' : '';
        $max_attr = $max !== null ? ' max="' . $max . '"' : '';
        $extra_attrs = '';
        
        foreach ($attributes as $attr => $attr_value) {
            $extra_attrs .= ' ' . $attr . '="' . $attr_value . '"';
        }
        
        return '<input type="number" id="' . $input_id . '" name="' . $name . '" value="' . htmlspecialchars($value) . '"' . $min_attr . $max_attr . $required_attr . $extra_attrs . '>';
    }

    /**
     * Create a textarea element
     */
    public static function textarea($name, $value = '', $id = null, $rows = 5, $required = false, $attributes = []) {
        $input_id = $id ?: $name;
        $required_attr = $required ? ' required' : '';
        $extra_attrs = '';
        
        foreach ($attributes as $attr => $attr_value) {
            $extra_attrs .= ' ' . $attr . '="' . $attr_value . '"';
        }
        
        return '<textarea id="' . $input_id . '" name="' . $name . '" rows="' . $rows . '"' . $required_attr . $extra_attrs . '>' . htmlspecialchars($value) . '</textarea>';
    }

    /**
     * Create a select element
     */
    public static function select($name, $options, $selected = '', $id = null, $required = false, $attributes = []) {
        $input_id = $id ?: $name;
        $required_attr = $required ? ' required' : '';
        $extra_attrs = '';
        
        foreach ($attributes as $attr => $attr_value) {
            $extra_attrs .= ' ' . $attr . '="' . $attr_value . '"';
        }
        
        $select = '<select id="' . $input_id . '" name="' . $name . '"' . $required_attr . $extra_attrs . '>';
        
        foreach ($options as $value => $label) {
            $selected_attr = ($value == $selected) ? ' selected' : '';
            $select .= '<option value="' . $value . '"' . $selected_attr . '>' . $label . '</option>';
        }
        
        $select .= '</select>';
        
        return $select;
    }

    /**
     * Create a submit button
     */
    public static function submitButton($text = 'Submit', $name = 'submit', $attributes = []) {
        $extra_attrs = '';
        
        foreach ($attributes as $attr => $attr_value) {
            $extra_attrs .= ' ' . $attr . '="' . $attr_value . '"';
        }
        
        return '<button type="submit" name="' . $name . '" class="btn btn-primary"' . $extra_attrs . '>' . $text . '</button>';
    }

    /**
     * Create a button
     */
    public static function button($text, $type = 'button', $attributes = []) {
        $extra_attrs = '';
        
        foreach ($attributes as $attr => $attr_value) {
            $extra_attrs .= ' ' . $attr . '="' . $attr_value . '"';
        }
        
        return '<button type="' . $type . '" class="btn"' . $extra_attrs . '>' . $text . '</button>';
    }

    /**
     * Create a link styled as a button
     */
    public static function linkButton($text, $href, $class = 'btn', $attributes = []) {
        $extra_attrs = '';
        
        foreach ($attributes as $attr => $attr_value) {
            $extra_attrs .= ' ' . $attr . '="' . $attr_value . '"';
        }
        
        return '<a href="' . $href . '" class="' . $class . '"' . $extra_attrs . '>' . $text . '</a>';
    }

    /**
     * Create a search form
     */
    public static function searchForm($action, $placeholder = 'Search...', $value = '') {
        ?>
<form action="<?php echo $action; ?>" method="get" class="search-form">
    <div class="search-group">
        <input type="text" name="search" value="<?php echo htmlspecialchars($value); ?>" placeholder="<?php echo $placeholder; ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </div>
</form>
<?php
    }

    /**
     * Render a modal dialog
     */
    public static function modal($id, $title, $content, $footer = null) {
        ?>
<div id="<?php echo $id; ?>" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><?php echo $title; ?></h3>
            <button type="button" class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
            <?php echo $content; ?>
        </div>
        <?php if ($footer): ?>
        <div class="modal-footer">
            <?php echo $footer; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php
    }
}
