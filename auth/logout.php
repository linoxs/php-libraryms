<?php
/**
 * Logout page
 */

require_once __DIR__ . '/../includes/session.php';

// Log out user
logout_user();

// Redirect to login page
header('Location: /auth/login.php');
exit;
