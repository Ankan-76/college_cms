<?php
// includes/auth_middleware.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Middleware: Verify user is authenticated. Otherwise route to landing page.
 */
function require_auth() {
    if (!isset($_SESSION['user_id'])) {
        redirect('/');
    }
}

/**
 * Middleware: Strictly check the logged-in user possesses valid Role designation block.
 *
 * @param array|string $roles
 */
function require_role($roles) {
    require_auth();
    
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    if (!isset($_SESSION['role_name']) || !in_array($_SESSION['role_name'], $roles)) {
        set_flash_message('Access Denied: You do not possess adequate permissions to access that resource.', 'error');
        
        // Eject back into respective portal or landing
        if (isset($_SESSION['role_name'])) {
            $role = strtolower($_SESSION['role_name']);
            redirect("/views/{$role}/dashboard.php");
        } else {
            redirect('/');
        }
    }
}

/**
 * Middleware: Defend purely public-facing routes from authenticated users
 * Prevents logged-in users from seeing the login screen.
 */
function require_guest() {
    if (isset($_SESSION['user_id']) && isset($_SESSION['role_name'])) {
        $role = strtolower($_SESSION['role_name']);
        redirect("/views/{$role}/dashboard.php");
    }
}
