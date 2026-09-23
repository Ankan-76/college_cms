<?php
// views/admin/delete-admin.php
// POST-only endpoint for deleting admin accounts.
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth_middleware.php';
require_once __DIR__ . '/../../includes/permission_middleware.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_role('ADMIN');
require_permission('manage_admins');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/views/admin/manage-admins.php');
}

// CSRF verification
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash_message('Invalid CSRF token.', 'error');
    redirect('/views/admin/manage-admins.php');
}

$adminId = filter_input(INPUT_POST, 'admin_id', FILTER_VALIDATE_INT);
if (!$adminId) {
    set_flash_message('Invalid admin ID.', 'error');
    redirect('/views/admin/manage-admins.php');
}

// Safety: prevent self-deletion
if ($adminId == $_SESSION['user_id']) {
    set_flash_message('You cannot delete your own account.', 'error');
    redirect('/views/admin/manage-admins.php');
}

use Config\Database;
$db = Database::getInstance()->getConnection();

// Safety: prevent deletion of Super Admin accounts
$stmt = $db->prepare("SELECT role, name FROM admins WHERE id = ?");
$stmt->execute([$adminId]);
$target = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$target) {
    set_flash_message('Admin account not found.', 'error');
    redirect('/views/admin/manage-admins.php');
}

if ($target['role'] === 'SUPER ADMIN') {
    set_flash_message('Super Admin accounts cannot be deleted.', 'error');
    redirect('/views/admin/manage-admins.php');
}

// Perform deletion (admin_permissions cascade via FK ON DELETE CASCADE)
try {
    $deleteStmt = $db->prepare("DELETE FROM admins WHERE id = ?");
    $deleteStmt->execute([$adminId]);
    set_flash_message("Admin '{$target['name']}' has been deleted successfully.", 'success');
} catch (\Exception $e) {
    error_log("Delete admin error: " . $e->getMessage());
    set_flash_message('Failed to delete admin account. Please try again.', 'error');
}

redirect('/views/admin/manage-admins.php');
