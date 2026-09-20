<?php
// config/app.php
define('APP_NAME', 'College Management System');
define('BASE_URL', '/college_cms'); // Assuming served locally from xampp/htdocs

// Define application-wide secure configurations
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_samesite', 'Strict');
// For production, uncomment secure flag (requires HTTPS)
// ini_set('session.cookie_secure', '1');

// Error reporting for dev (disable for production)
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

define('SESSION_LIFETIME', 86400); // 1 day

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
