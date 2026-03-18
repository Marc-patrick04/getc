<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'getc_ltd');
define('DB_USER', 'postgres');
define('DB_PASS', 'numugisha');

// Site configuration
define('SITE_NAME', 'GETC Ltd');
define('SITE_URL', 'http://localhost/getc-website');
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/getc-website/uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

// Admin email for notifications
define('ADMIN_EMAIL', 'admin@getcltd.com');

// Colors
define('PRIMARY_BLUE', '#222472');
define('SECONDARY_ORANGE', '#f16c20');
define('WHITE', '#ffffff');
define('YELLOW_ACCENT', '#ffd700');

// Session start
session_start();
?>