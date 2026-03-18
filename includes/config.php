<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'getc_ltd');
define('DB_USER', 'postgres');
define('DB_PASS', 'numugisha');

// PostgreSQL hosting connection string
define('PG_HOSTING_CONNECTION', 'postgresql://neondb_owner:npg_1Ph7cfQdlOSi@ep-dry-fire-ads2x4xy-pooler.c-2.us-east-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require');

// Production database configuration (for hosting)
define('PROD_DB_HOST', 'ep-dry-fire-ads2x4xy-pooler.c-2.us-east-1.aws.neon.tech');
define('PROD_DB_NAME', 'neondb');
define('PROD_DB_USER', 'neondb_owner');
define('PROD_DB_PASS', 'npg_1Ph7cfQdlOSi');

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