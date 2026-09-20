<?php
// includes/csrf.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate a cryptographically secure CSRF token and store it.
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        try {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            // Fallback just in case
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        }
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate incoming CSRF token strictly via timing-attack safe comparison.
 */
function verify_csrf_token($token) {
    if (isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }
    return false;
}

/**
 * Helper to generate CSRF hidden field input component.
 */
function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}
