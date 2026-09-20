<?php
// includes/helpers.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Core utility for protecting against XSS mapping output to secure constraints.
 */
function sanitize($data) {
    if (is_null($data)) return '';
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Safe redirect handling
 */
function redirect($path) {
    // If not defined, fallback directly tracking local assumption
    $base = defined('BASE_URL') ? BASE_URL : '/college_cms';
    
    // Check if path is absolute vs relative
    if (strpos($path, 'http') === 0) {
        header('Location: ' . $path);
    } else {
        header('Location: ' . $base . $path);
    }
    exit;
}

/**
 * Creates temporary notifications that expire after rendering.
 * @param string $message The flash output
 * @param string $type The status typification (success, error, info)
 */
function set_flash_message($message, $type = 'success') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Fetch and clear standard flash messages.
 */
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $msg;
    }
    return null;
}
