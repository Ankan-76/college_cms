<?php
// includes/permission_middleware.php
// RBAC Permission Middleware — gates admin pages by module-level permissions.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/auth_middleware.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Check if the current admin user is a Super Admin.
 * Includes a fallback for pre-RBAC sessions that lack admin_role.
 * 
 * @return bool
 */
function is_super_admin(): bool {
    // If admin_role isn't set in session yet (pre-RBAC login), load it from DB
    if (!isset($_SESSION['admin_role']) && isset($_SESSION['role_name']) && $_SESSION['role_name'] === 'ADMIN' && isset($_SESSION['user_id'])) {
        try {
            $db = \Config\Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT role FROM admins WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $role = $stmt->fetchColumn();
            $_SESSION['admin_role'] = $role ?: 'SUPER ADMIN';
        } catch (\Exception $e) {
            $_SESSION['admin_role'] = 'SUPER ADMIN'; // Safe fallback
        }
    }
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'SUPER ADMIN';
}

/**
 * Load (and cache) the current admin's permitted module keys into session.
 * Super Admins get a wildcard ['*'] — they bypass all checks.
 * 
 * @return array List of module_key strings, or ['*'] for Super Admin.
 */
function get_admin_permissions(): array {
    // Return cached permissions if already loaded
    if (isset($_SESSION['admin_permissions'])) {
        return $_SESSION['admin_permissions'];
    }
    
    // Not an admin — return empty
    if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'ADMIN') {
        return [];
    }
    
    // Super Admin bypasses everything
    if (is_super_admin()) {
        $_SESSION['admin_permissions'] = ['*'];
        return ['*'];
    }
    
    // Load from database for sub-admins
    try {
        $db = \Config\Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT m.module_key 
            FROM admin_permissions ap
            JOIN modules m ON ap.module_id = m.id
            WHERE ap.admin_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $permissions = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $_SESSION['admin_permissions'] = $permissions;
        return $permissions;
    } catch (\Exception $e) {
        error_log("Permission middleware error: " . $e->getMessage());
        return [];
    }
}

/**
 * Non-redirecting permission check.
 * Returns true if the current admin can access the given module.
 * 
 * @param string $module_key The module identifier (e.g., 'students', 'faculty')
 * @return bool
 */
function has_permission(string $module_key): bool {
    // Non-admin roles don't use this system
    if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'ADMIN') {
        return false;
    }
    
    // Super Admin has all permissions
    if (is_super_admin()) {
        return true;
    }
    
    $permissions = get_admin_permissions();
    return in_array($module_key, $permissions, true);
}

/**
 * Middleware: Require the logged-in admin to have a specific module permission.
 * If the admin lacks the permission, they are redirected to the dashboard with an
 * "Access Denied" flash message.
 * 
 * Super Admins always pass this check.
 * 
 * @param string $module_key The module identifier to check against.
 */
function require_permission(string $module_key): void {
    // First ensure user is authenticated and is an admin
    require_auth();
    
    if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'ADMIN') {
        set_flash_message('Access Denied: You do not have permission to access that resource.', 'error');
        redirect('/');
        return;
    }
    
    // Super Admin bypasses all permission checks
    if (is_super_admin()) {
        return;
    }
    
    // Check specific module permission
    if (!has_permission($module_key)) {
        set_flash_message('Access Denied: You do not have permission to access this module.', 'error');
        redirect('/views/admin/dashboard.php');
    }
}

/**
 * Reload admin permissions from the database into the session.
 * Useful after the Super Admin modifies another admin's permissions,
 * or when you need to force-refresh cached permissions.
 */
function reload_admin_permissions(): void {
    unset($_SESSION['admin_permissions']);
    get_admin_permissions();
}
