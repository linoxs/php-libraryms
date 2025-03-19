<?php
/**
 * Alert component for displaying messages
 */

class Alert {
    /**
     * Display a success alert
     */
    public static function success($message) {
        self::render('success', $message);
    }
    
    /**
     * Display an error alert
     */
    public static function error($message) {
        self::render('error', $message);
    }
    
    /**
     * Display an info alert
     */
    public static function info($message) {
        self::render('info', $message);
    }
    
    /**
     * Display a warning alert
     */
    public static function warning($message) {
        self::render('warning', $message);
    }
    
    /**
     * Render an alert
     */
    private static function render($type, $message) {
        ?>
<div class="alert alert-<?php echo $type; ?>">
    <?php echo $message; ?>
</div>
<?php
    }
    
    /**
     * Display a dismissible alert
     */
    public static function dismissible($type, $message) {
        ?>
<div class="alert alert-<?php echo $type; ?> alert-dismissible">
    <button type="button" class="close" onclick="this.parentElement.style.display='none'">&times;</button>
    <?php echo $message; ?>
</div>
<?php
    }
}
