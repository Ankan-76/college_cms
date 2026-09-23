<?php
// controllers/process_inquiry.php — Administrative processor for admission inquiries
require_once __DIR__ . '/../config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/InquiryController.php';

use Controllers\InquiryController;

$controller = new InquiryController();
$action = $_POST['action'] ?? ($_GET['action'] ?? '');
$redirectUrl = '/views/admin/admission-inquiries.php';

// ── Action: Update Status & Counselor Notes (Admin Only) ───────
if ($action === 'update_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_role('ADMIN');

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('Action failed: Invalid CSRF token.', 'error');
        redirect($redirectUrl);
    }

    $id = (int)($_POST['id'] ?? 0);
    $status = sanitize($_POST['status'] ?? 'pending');
    $adminNotes = isset($_POST['admin_notes']) ? trim($_POST['admin_notes']) : null;

    if ($id <= 0) {
        set_flash_message('Invalid inquiry ID specified.', 'error');
        redirect($redirectUrl);
    }

    $controller->updateStatus($id, $status, $adminNotes);
    redirect($redirectUrl);

// ── Action: Delete Inquiry (Admin Only) ────────────────────────
} elseif ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_role('ADMIN');

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('Action failed: Invalid CSRF token.', 'error');
        redirect($redirectUrl);
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        set_flash_message('Invalid inquiry ID specified.', 'error');
        redirect($redirectUrl);
    }

    $controller->deleteInquiry($id);
    redirect($redirectUrl);

} else {
    redirect($redirectUrl);
}
